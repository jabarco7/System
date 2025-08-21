<?php
session_start();
error_reporting(0);
include('include/config.php');

if (empty($_SESSION['id'])) {
  header('location:logout.php');
  exit;
}

$docId = (int)$_SESSION['id'];

/* ==== Data ==== */
$stats = [
  'patients' => 0,
  'today'    => 0,
  'week'     => 0,
  'lastVisit' => null,
  'next'     => null,
];

/* إجمالي المرضى (tblpatient) */
if ($st = $con->prepare("SELECT COUNT(*) FROM tblpatient WHERE Docid=?")) {
  $st->bind_param('i', $docId);
  $st->execute();
  $st->bind_result($stats['patients']);
  $st->fetch();
  $st->close();
}

/* مواعيد اليوم */
if ($st = $con->prepare("SELECT COUNT(*) FROM appointment WHERE doctorId=? AND appointmentDate=CURDATE() AND userStatus=1 AND doctorStatus=1")) {
  $st->bind_param('i', $docId);
  $st->execute();
  $st->bind_result($stats['today']);
  $st->fetch();
  $st->close();
}

/* مواعيد الأسبوع */
if ($st = $con->prepare("SELECT COUNT(*) FROM appointment WHERE doctorId=? AND appointmentDate BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND userStatus=1 AND doctorStatus=1")) {
  $st->bind_param('i', $docId);
  $st->execute();
  $st->bind_result($stats['week']);
  $st->fetch();
  $st->close();
}

/* آخر زيارة (أحدث موعد مكتمل/محجوز) */
if ($st = $con->prepare("SELECT MAX(CONCAT(appointmentDate,' ',appointmentTime)) FROM appointment WHERE doctorId=?")) {
  $st->bind_param('i', $docId);
  $st->execute();
  $st->bind_result($stats['lastVisit']);
  $st->fetch();
  $st->close();
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
  $st->bind_param('i', $docId);
  $st->execute();
  $res = $st->get_result();
  $stats['next'] = $res ? $res->fetch_assoc() : null;
  $st->close();
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
  $st->bind_param('i', $docId);
  $st->execute();
  $res = $st->get_result();
  while ($r = $res->fetch_assoc()) $todayRows[] = $r;
  $st->close();
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
    body {
      font-family: 'Tajawal', sans-serif;
      background: #f0f5f9
    }

    .page-header {
      background: linear-gradient(90deg, #3498db, #4aa8e0);
      color: #fff;
      border-radius: 12px;
      padding: 16px 18px;
      margin: 20px 40px 12px;
      display: flex;
      align-items: center;
      justify-content: space-between
    }

    .page-header h1 {
      margin: 0;
      font-size: 1.25rem
    }

    .container-section {
      margin: 0 20px 24px
    }

    .grid {
      display: grid;
      gap: 16px
    }

    @media(min-width:992px) {
      .grid.cols-3 {
        grid-template-columns: repeat(3, 1fr)
      }
    }

    @media(min-width:1200px) {
      .grid.cols-4 {
        grid-template-columns: repeat(4, 1fr)
      }
    }

    .card {
      background: #fff;
      border-radius: 14px;
      box-shadow: 0 8px 20px rgba(0, 0, 0, .06);
      padding: 18px
    }

    .stat {
      display: flex;
      align-items: center;
      gap: 14px
    }

    .stat .icon {
      width: 52px;
      height: 52px;
      border-radius: 14px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #f2f7ff;
      color: #0d6efd;
      font-size: 22px
    }

    .stat .value {
      font-size: 1.6rem;
      font-weight: 700;
      line-height: 1
    }

    .muted {
      color: #6c757d
    }

    .card h3 {
      font-size: 1rem;
      margin: 0 0 4px 0
    }

    .link-btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      border: 1px solid #e6edf6;
      border-radius: 10px;
      padding: 8px 12px;
      background: #fff
    }

    .link-btn:hover {
      background: #f5f8fd;
      text-decoration: none
    }

    .table {
      width: 100%;
      border-collapse: collapse
    }

    .table th,
    .table td {
      padding: 10px 8px;
      border-bottom: 1px solid #eef2f7
    }

    .table th {
      white-space: nowrap;
      color: #6b7280;
      font-weight: 700;
      font-size: .9rem
    }

    .empty {
      color: #8a94a6;
      text-align: center;
      padding: 18px
    }

    /* تنسيق خاص لجدول المرضى */
    .patient-info strong {
      color: #2c3e50;
      font-size: 14px;
    }

    .personal-details .badge {
      margin-bottom: 3px;
      font-size: 11px;
    }

    .stats-mini .badge {
      margin-bottom: 3px;
      font-size: 10px;
    }

    .table-striped tbody tr:nth-of-type(odd) {
      background-color: #f8f9fa;
    }

    .btn-group-vertical .btn {
      margin-bottom: 2px;
      font-size: 11px;
      padding: 4px 8px;
    }

    .badge-pink {
      background-color: #e91e63;
      color: white;
    }

    .table td {
      font-size: 12px;
      line-height: 1.4;
      vertical-align: top;
    }

    .table th {
      font-size: 13px;
      font-weight: 600;
    }

    .patient-info,
    .personal-details,
    .stats-mini {
      line-height: 1.3;
    }
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
              echo htmlspecialchars($stats['next']['appointmentDate']) . ' — ' .
                htmlspecialchars(substr($stats['next']['appointmentTime'], 0, 5));
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
                <?php $i = 1;
                foreach ($todayRows as $r): ?>
                  <tr>
                    <td><?php echo $i++; ?>.</td>
                    <td><?php echo htmlspecialchars($r['fullName']); ?></td>
                    <td><?php echo htmlspecialchars(substr($r['appointmentTime'], 0, 5)); ?></td>
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

    <!-- قسم المرضى -->
    <div class="container-section">
      <div class="card">
        <div class="card-header">
          <h5><i class="fa fa-users"></i> مرضاي</h5>
          <a href="manage-patient.php" class="link-btn">عرض الكل</a>
        </div>

        <?php
        /* جلب آخر 8 مرضى للطبيب */
        $recentPatients = [];
        if ($st = $con->prepare("
          SELECT
            p.ID,
            p.PatientName,
            p.PatientContno,
            p.PatientEmail,
            p.PatientGender,
            p.PatientAge,
            p.PatientAdd,
            p.PatientMedhis,
            p.CreationDate,
            p.UpdationDate,
            COUNT(m.ID) as medical_records_count,
            MAX(m.CreationDate) as last_medical_record,
            COUNT(DISTINCT a.id) as total_appointments,
            MAX(CONCAT(a.appointmentDate, ' ', a.appointmentTime)) as last_appointment
          FROM tblpatient p
          LEFT JOIN tblmedicalhistory m ON m.PatientID = p.ID
          LEFT JOIN appointment a ON a.userId = (SELECT u.id FROM users u WHERE u.email = p.PatientEmail LIMIT 1) AND a.doctorId = ?
          WHERE p.Docid = ?
          GROUP BY p.ID
          ORDER BY p.CreationDate DESC
          LIMIT 8
        ")) {
          $st->bind_param('ii', $docId, $docId);
          $st->bind_param('i', $docId);
          $st->execute();
          $res = $st->get_result();
          while ($row = $res->fetch_assoc()) {
            $recentPatients[] = $row;
          }
          $st->close();
        }
        ?>

        <?php if ($recentPatients): ?>
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>#</th>
                  <th>اسم المريض</th>
                  <th>معلومات الاتصال</th>
                  <th>التفاصيل الشخصية</th>
                  <th>العنوان</th>
                  <th>التاريخ المرضي</th>
                  <th>الإحصائيات</th>
                  <th>آخر نشاط</th>
                  <th>الإجراءات</th>
                </tr>
              </thead>
              <tbody>
                <?php $i = 1;
                foreach ($recentPatients as $patient): ?>
                  <tr>
                    <td><?php echo $i++; ?>.</td>

                    <!-- اسم المريض -->
                    <td>
                      <div class="patient-name">
                        <strong style="color: #2c3e50; font-size: 15px;">
                          <?php echo htmlspecialchars($patient['PatientName']); ?>
                        </strong>
                      </div>
                    </td>

                    <!-- معلومات الاتصال -->
                    <td>
                      <div class="contact-info">
                        <div style="margin-bottom: 5px;">
                          <i class="fa fa-phone" style="color: #27ae60; width: 16px;"></i>
                          <strong style="color: #27ae60;">
                            <?php echo htmlspecialchars($patient['PatientContno'] ?: 'غير محدد'); ?>
                          </strong>
                        </div>
                        <div>
                          <i class="fa fa-envelope" style="color: #3498db; width: 16px;"></i>
                          <small class="text-muted">
                            <?php echo htmlspecialchars($patient['PatientEmail']); ?>
                          </small>
                        </div>
                      </div>
                    </td>

                    <!-- التفاصيل الشخصية -->
                    <td>
                      <div class="personal-details">
                        <span class="badge <?php echo $patient['PatientGender'] === 'ذكر' ? 'badge-primary' : 'badge-pink'; ?>" style="margin-bottom: 5px;">
                          <i class="fa <?php echo $patient['PatientGender'] === 'ذكر' ? 'fa-mars' : 'fa-venus'; ?>"></i>
                          <?php echo htmlspecialchars($patient['PatientGender']); ?>
                        </span>
                        <br>
                        <span class="badge badge-info">
                          <i class="fa fa-birthday-cake"></i> <?php echo htmlspecialchars($patient['PatientAge']); ?> سنة
                        </span>
                      </div>
                    </td>

                    <!-- العنوان -->
                    <td>
                      <small class="text-muted">
                        <i class="fa fa-map-marker-alt"></i>
                        <?php
                        $address = htmlspecialchars($patient['PatientAdd'] ?: 'غير محدد');
                        echo strlen($address) > 50 ? substr($address, 0, 50) . '...' : $address;
                        ?>
                      </small>
                    </td>

                    <!-- التاريخ المرضي -->
                    <td>
                      <small class="text-muted" style="max-width: 200px; display: block;">
                        <i class="fa fa-notes-medical"></i>
                        <?php
                        $medHistory = htmlspecialchars($patient['PatientMedhis'] ?: 'لا يوجد تاريخ مرضي');
                        echo strlen($medHistory) > 60 ? substr($medHistory, 0, 60) . '...' : $medHistory;
                        ?>
                      </small>
                    </td>

                    <!-- الإحصائيات -->
                    <td>
                      <div class="stats-mini">
                        <span class="badge badge-success" title="السجلات الطبية">
                          <i class="fa fa-file-medical"></i> <?php echo (int)$patient['medical_records_count']; ?>
                        </span>
                        <br>
                        <span class="badge badge-warning" title="المواعيد">
                          <i class="fa fa-calendar"></i> <?php echo (int)$patient['total_appointments']; ?>
                        </span>
                      </div>
                    </td>

                    <!-- آخر نشاط -->
                    <td>
                      <small class="text-muted">
                        <strong>التسجيل:</strong><br>
                        <?php echo date('Y-m-d', strtotime($patient['CreationDate'])); ?>
                        <br>
                        <?php if ($patient['last_appointment']): ?>
                          <strong>آخر موعد:</strong><br>
                          <?php echo date('Y-m-d H:i', strtotime($patient['last_appointment'])); ?>
                        <?php endif; ?>
                        <?php if ($patient['last_medical_record']): ?>
                          <br><strong>آخر فحص:</strong><br>
                          <?php echo date('Y-m-d', strtotime($patient['last_medical_record'])); ?>
                        <?php endif; ?>
                      </small>
                    </td>

                    <!-- الإجراءات -->
                    <td>
                      <div class="btn-group-vertical btn-group-sm">
                        <a href="view-patient.php?viewid=<?php echo $patient['ID']; ?>" class="btn btn-primary btn-sm mb-1" title="عرض التفاصيل">
                          <i class="fa fa-eye"></i> عرض
                        </a>
                        <a href="edit-patient.php?editid=<?php echo $patient['ID']; ?>" class="btn btn-warning btn-sm mb-1" title="تعديل المعلومات">
                          <i class="fa fa-edit"></i> تعديل
                        </a>
                        <a href="../view-medhistory.php?pid=<?php echo $patient['ID']; ?>" class="btn btn-success btn-sm" title="السجل الطبي">
                          <i class="fa fa-notes-medical"></i> السجل
                        </a>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="empty">
            <i class="fa fa-user-plus"></i>
            لا يوجد مرضى مسجلون بعد.
            <br>
            <a href="add-patient.php" class="btn btn-primary btn-sm mt-2">إضافة مريض جديد</a>
          </div>
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
    jQuery(function() {
      if (window.FormElements && FormElements.init) FormElements.init();
      if (window.Main && Main.init) Main.init();
    });
  </script>
</body>

</html>