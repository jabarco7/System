<?php
session_start();
error_reporting(0);
include('include/config.php');

if (empty($_SESSION['id'])) {
  header('location:logout.php'); exit;
}

$docId = (int)$_SESSION['id'];

/* ==== Data ==== */
$stats = [
  'patients' => 0,
  'today'    => 0,
  'week'     => 0,
  'lastVisit'=> null,
  'next'     => null,
];

/* إجمالي المرضى (tblpatient) */
if ($st = $con->prepare("SELECT COUNT(*) FROM tblpatient WHERE Docid=?")) {
  $st->bind_param('i', $docId); $st->execute(); $st->bind_result($stats['patients']); $st->fetch(); $st->close();
}

/* مواعيد اليوم */
if ($st = $con->prepare("SELECT COUNT(*) FROM appointment WHERE doctorId=? AND appointmentDate=CURDATE() AND userStatus=1 AND doctorStatus=1")) {
  $st->bind_param('i', $docId); $st->execute(); $st->bind_result($stats['today']); $st->fetch(); $st->close();
}

/* مواعيد الأسبوع */
if ($st = $con->prepare("SELECT COUNT(*) FROM appointment WHERE doctorId=? AND appointmentDate BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND userStatus=1 AND doctorStatus=1")) {
  $st->bind_param('i', $docId); $st->execute(); $st->bind_result($stats['week']); $st->fetch(); $st->close();
}

/* آخر زيارة (أحدث موعد مكتمل/محجوز) */
if ($st = $con->prepare("SELECT MAX(CONCAT(appointmentDate,' ',appointmentTime)) FROM appointment WHERE doctorId=?")) {
  $st->bind_param('i', $docId); $st->execute(); $st->bind_result($stats['lastVisit']); $st->fetch(); $st->close();
}

/* أقرب موعد قادم */
if ($st = $con->prepare("
  SELECT u.fullName, a.appointmentDate, a.appointmentTime
  FROM appointment a
  JOIN users u ON u.id=a.userId
  WHERE a.doctorId=? AND CONCAT(a.appointmentDate,' ',a.appointmentTime) >= NOW()
    AND a.userStatus=1 AND a.doctorStatus=1
  ORDER BY a.appointmentDate ASC, STR_TO_DATE(a.appointmentTime,'%H:%i') ASC
  LIMIT 1
")) {
  $st->bind_param('i', $docId); $st->execute(); $res = $st->get_result();
  $stats['next'] = $res ? $res->fetch_assoc() : null; $st->close();
}

/* جدول مواعيد اليوم (أول 8 عناصر) */
$todayRows = [];
if ($st = $con->prepare("
  SELECT a.id, u.fullName, a.appointmentTime, a.doctorSpecialization
  FROM appointment a
  JOIN users u ON u.id=a.userId
  WHERE a.doctorId=? AND a.appointmentDate=CURDATE() AND a.userStatus=1 AND a.doctorStatus=1
  ORDER BY STR_TO_DATE(a.appointmentTime,'%H:%i') ASC
  LIMIT 8
")) {
  $st->bind_param('i', $docId); $st->execute(); $res = $st->get_result();
  while ($r = $res->fetch_assoc()) $todayRows[] = $r; $st->close();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>الطبيب | لوحة التحكم</title>
  <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="assets/css/hms-unified.css?v=1.0">
  <style>
    body{font-family:'Tajawal',sans-serif;background:#f0f5f9}
    .page-header{
      background:linear-gradient(90deg,#3498db,#4aa8e0);
      color:#fff;border-radius:12px;padding:16px 18px;margin:20px 20px 12px;
      display:flex;align-items:center;justify-content:space-between
    }
    .page-header h1{margin:0;font-size:1.25rem}
    .container-section{margin:0 20px 24px}
    .grid{display:grid;gap:16px}
    @media(min-width:992px){ .grid.cols-3{grid-template-columns:repeat(3,1fr)} }
    @media(min-width:1200px){ .grid.cols-4{grid-template-columns:repeat(4,1fr)} }
    .card{
      background:#fff;border-radius:14px;box-shadow:0 8px 20px rgba(0,0,0,.06);
      padding:18px
    }
    .stat{
      display:flex;align-items:center;gap:14px
    }
    .stat .icon{
      width:52px;height:52px;border-radius:14px;display:flex;align-items:center;justify-content:center;
      background:#f2f7ff;color:#0d6efd;font-size:22px
    }
    .stat .value{font-size:1.6rem;font-weight:700;line-height:1}
    .muted{color:#6c757d}
    .card h3{font-size:1rem;margin:0 0 4px 0}
    .link-btn{display:inline-flex;align-items:center;gap:6px;border:1px solid #e6edf6;border-radius:10px;padding:8px 12px;background:#fff}
    .link-btn:hover{background:#f5f8fd;text-decoration:none}
    .table{width:100%;border-collapse:collapse}
    .table th,.table td{padding:10px 8px;border-bottom:1px solid #eef2f7}
    .table th{white-space:nowrap;color:#6b7280;font-weight:700;font-size:.9rem}
    .empty{color:#8a94a6;text-align:center;padding:18px}
 
  </style>
</head>
<body>

  <?php include('include/header.php'); ?>
  <?php include('include/sidebar.php'); ?>

  <div class="main-content">

    <!-- Header -->
    <div class="page-header">
      <div>
        <h1><i class="fa fa-gauge-high"></i> لوحة التحكم</h1>
        <div class="muted">مرحباً بك دكتور 👋 — أتمنى لك يومًا موفقًا</div>
      </div>
      <a class="link-btn" href="appointment-history.php"><i class="fa fa-calendar-check"></i> سجل المواعيد</a>
    </div>

    <!-- Stats -->
    <div class="container-section">
      <div class="grid cols-4">
        <div class="card">
          <div class="stat">
            <div class="icon"><i class="fa fa-users"></i></div>
            <div>
              <div class="value"><?php echo (int)$stats['patients']; ?></div>
              <div class="muted">إجمالي المرضى</div>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="stat">
            <div class="icon"><i class="fa fa-sun"></i></div>
            <div>
              <div class="value"><?php echo (int)$stats['today']; ?></div>
              <div class="muted">مواعيد اليوم</div>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="stat">
            <div class="icon"><i class="fa fa-calendar-week"></i></div>
            <div>
              <div class="value"><?php echo (int)$stats['week']; ?></div>
              <div class="muted">خلال 7 أيام</div>
            </div>
          </div>
        </div>

        <div class="card">
          <h3>أقرب موعد قادم</h3>
          <?php if ($stats['next']): ?>
            <div class="muted">
              <i class="fa fa-user"></i>
              <?php echo htmlspecialchars($stats['next']['fullName']); ?>
            </div>
            <div>
              <i class="fa fa-clock"></i>
              <?php
                echo htmlspecialchars($stats['next']['appointmentDate']).' — '.
                     htmlspecialchars(substr($stats['next']['appointmentTime'],0,5));
              ?>
            </div>
          <?php else: ?>
            <div class="empty">لا يوجد موعد قادم</div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Today appointments -->
    <div class="container-section">
      <div class="card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
          <h3 style="margin:0"><i class="fa fa-calendar-day"></i> مواعيد اليوم</h3>
          <a class="link-btn" href="appointment-history.php"><i class="fa fa-list"></i> الكل</a>
        </div>

        <?php if ($todayRows): ?>
          <div class="table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>المريض</th>
                  <th>الوقت</th>
                  <th>التخصص</th>
                </tr>
              </thead>
              <tbody>
                <?php $i=1; foreach ($todayRows as $r): ?>
                  <tr>
                    <td><?php echo $i++; ?>.</td>
                    <td><?php echo htmlspecialchars($r['fullName']); ?></td>
                    <td><?php echo htmlspecialchars(substr($r['appointmentTime'],0,5)); ?></td>
                    <td><?php echo htmlspecialchars($r['doctorSpecialization'] ?: '—'); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="empty"><i class="fa fa-face-smile"></i> لا توجد مواعيد اليوم.</div>
        <?php endif; ?>
      </div>
    </div>


  </div><!-- /.main-content -->

  <?php include('include/footer.php'); ?>
  <?php include('include/setting.php'); ?>

  <script src="vendor/jquery/jquery.min.js"></script>
  <script src="vendor/bootstrap/js/bootstrap.min.js"></script>
  <script src="vendor/modernizr/modernizr.js"></script>
  <script src="vendor/jquery-cookie/jquery.cookie.js"></script>
  <script src="vendor/perfect-scrollbar/perfect-scrollbar.min.js"></script>
  <script src="vendor/switchery/switchery.min.js"></script>
  <script src="vendor/maskedinput/jquery.maskedinput.min.js"></script>
  <script src="vendor/bootstrap-touchspin/jquery.bootstrap-touchspin.min.js"></script>
  <script src="vendor/autosize/autosize.min.js"></script>
  <script src="vendor/selectFx/classie.js"></script>
  <script src="vendor/selectFx/selectFx.js"></script>
  <script src="vendor/select2/select2.min.js"></script>
  <script src="vendor/bootstrap-datepicker/bootstrap-datepicker.min.js"></script>
  <script src="vendor/bootstrap-timepicker/bootstrap-timepicker.min.js"></script>
  <script src="assets/js/main.js"></script>
  <script src="assets/js/form-elements.js"></script>
  <script>
    jQuery(function(){
      if (window.FormElements && FormElements.init) FormElements.init();
      if (window.Main && Main.init) Main.init();
    });
  </script>
</body>
</html>
