<?php
session_start();
error_reporting(0);
include('include/config.php');

if (empty($_SESSION['id'])) {
    header('location:logout.php');
    exit;
}

$userId = (int)$_SESSION['id'];

/* CSRF */
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

/* إلغاء موعد */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_id'])) {
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        $_SESSION['msg'] = 'فشل التحقق الأمني. حاول مرة أخرى.';
        header('Location: appointment-history.php');
        exit;
    }
    $aid = (int)($_POST['cancel_id'] ?? 0);
    if ($aid > 0) {
        if ($stmt = $con->prepare("UPDATE appointment SET userStatus=0 WHERE id=? AND userId=? AND userStatus=1")) {
            $stmt->bind_param('ii', $aid, $userId);
            $stmt->execute();
            $_SESSION['msg'] = ($stmt->affected_rows > 0) ? 'تم إلغاء موعدك ✅' : 'تعذّر إلغاء هذا الموعد.';
            $stmt->close();
        } else {
            $_SESSION['msg'] = 'تعذّر تنفيذ طلب الإلغاء.';
        }
    }
    header('Location: appointment-history.php');
    exit;
}

/* AJAX: التحقق من جديد */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'check_new') {
    header('Content-Type: application/json; charset=utf-8');
    $q = $con->prepare("SELECT COUNT(*) AS c FROM appointment WHERE userId=?");
    $q->bind_param('i', $userId);
    $q->execute();
    $r = $q->get_result()->fetch_assoc();
    $q->close();
    echo json_encode(['newCount' => (int)$r['c']]);
    exit;
}

/* جلب المواعيد (اصلاح جدول/عمود التخصص + معالجة نص/ID) */
$rows = [];
$sql = "
SELECT
  a.id,
  COALESCE(d.doctorName,'—') AS docname,

  /* اسم التخصص النهائي (يتحمل نص أو ID أو 0) */
  COALESCE(
    /* 1) النص المخزّن في الموعد إذا كان نصاً حقيقياً */
    NULLIF(
      CASE
        WHEN TRIM(a.doctorSpecialization) REGEXP '^[0-9]+$' OR TRIM(a.doctorSpecialization) IN ('','0') THEN NULL
        ELSE TRIM(a.doctorSpecialization)
      END, ''
    ),
    /* 2) اسم التخصص من جدول doctorspecilization على أساس رقم الموعد */
    ds_a.specilization,
    /* 3) النص الموجود في جدول الأطباء إذا كان نصاً */
    NULLIF(
      CASE
        WHEN TRIM(d.specilization) REGEXP '^[0-9]+$' OR TRIM(d.specilization) IN ('','0') THEN NULL
        ELSE TRIM(d.specilization)
      END, ''
    ),
    /* 4) اسم التخصص من جدول doctorspecilization على أساس رقم الطبيب */
    ds_d.specilization,
    /* 5) إن لم يوجد أي شيء */
    '—'
  ) AS doctorSpecialization,

  a.consultancyFees,
  a.appointmentDate,
  a.appointmentTime,
  a.postingDate,
  COALESCE(a.userStatus,1)   AS userStatus,
  COALESCE(a.doctorStatus,1) AS doctorStatus
FROM appointment a
LEFT JOIN doctors d
  ON d.id = a.doctorId
/* انتبه: اسم الجدول الصحيح عندك doctorspecilization وعموده specilization */
LEFT JOIN doctorspecilization ds_a
  ON ds_a.id = CAST(a.doctorSpecialization AS UNSIGNED)
LEFT JOIN doctorspecilization ds_d
  ON ds_d.id = CAST(d.specilization AS UNSIGNED)
WHERE a.userId = ?
ORDER BY
  a.appointmentDate DESC,
  /* دعم صيغ الوقت: 24h أو AM/PM */
  COALESCE(
    STR_TO_DATE(a.appointmentTime, '%H:%i'),
    STR_TO_DATE(a.appointmentTime, '%h:%i %p')
  ) DESC,
  a.id DESC
";
if ($stmt = $con->prepare($sql)) {
  $stmt->bind_param('i', $userId);
  $stmt->execute();
  $res = $stmt->get_result();
  while ($r = $res->fetch_assoc()) { $rows[] = $r; }
  $stmt->close();
}
$total = count($rows);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <title>المستخدم | سجل المواعيد</title>

  <link href="http://fonts.googleapis.com/css?family=Tajawal:300,400,500,700" rel="stylesheet">
  <link rel="stylesheet" href="vendor/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="vendor/fontawesome/css/font-awesome.min.css">
  <link rel="stylesheet" href="vendor/themify-icons/themify-icons.min.css">
  <link href="vendor/animate.css/animate.min.css" rel="stylesheet">
  <link href="vendor/perfect-scrollbar/perfect-scrollbar.min.css" rel="stylesheet">
  <link href="vendor/switchery/switchery.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/styles.css">
  <link rel="stylesheet" href="assets/css/plugins.css">
  <link rel="stylesheet" href="assets/css/themes/theme-1.css" id="skin_color" />

  <style>
    :root { --primary:#3498db; --primary2:#4aa8e0; --bg:#f0f5f9; --ok:#0f8f4e; --warn:#856404; --danger:#b21f2d; }
    body{font-family:'Tajawal',sans-serif;background:var(--bg)}
    .page-shell{max-width:1200px;margin:24px auto;padding:0 12px}
    .hero{background:linear-gradient(90deg,var(--primary),var(--primary2));color:#fff;border-radius:14px;padding:18px 20px;margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;gap:12px}
    .hero h1{margin:0;font-size:22px;font-weight:800}
    .card-wrap{background:#fff;border-radius:14px;box-shadow:0 8px 20px rgba(0,0,0,.06);padding:16px}
    .alert-compact{border-radius:12px;padding:10px 12px}
    .table thead th{background:#f7f9fc;border-top:0}
    .table tbody tr:hover{background:#fcfdff}
    .table td,.table th{vertical-align:middle}
    .badge-soft{border-radius:30px;padding:6px 10px;font-weight:700;display:inline-flex;gap:6px;align-items:center}
    .badge-active{background:#e6f7ef;color:var(--ok);border:1px solid #bfe9d1}
    .badge-user-cancel{background:#fff3cd;color:var(--warn);border:1px solid #ffeeba}
    .badge-doc-cancel{background:#fde2e1;color:var(--danger);border:1px solid #f5c6cb}
    .list-header{display:flex;gap:10px;align-items:center;justify-content:space-between;padding:8px 12px;border:1px solid #eef2f7;border-radius:10px;margin-bottom:10px;background:#fbfdff}
    .chip{background:#eef7ff;color:#0d6efd;border:1px solid #cfe5ff;border-radius:40px;padding:4px 10px;font-weight:600}
    .muted{color:#6c757d}
    .btn-cancel{background:#fff;border:1px solid #f1b5b5;color:#c12f2f;border-radius:10px}
    .btn-cancel:hover{background:#ffe9e9}
  </style>
</head>
<body>

<div class="page-shell">
  <div class="hero">
    <div>
      <h1>سجل المواعيد</h1>
      <div class="meta">جميع حجوزاتك مرتبة من الأحدث إلى الأقدم</div>
    </div>
    <div class="chip"><i class="fa fa-calendar-check-o"></i> الإجمالي: <?php echo (int)$total; ?></div>
  </div>

  <?php if (!empty($_SESSION['msg'])): ?>
    <div class="alert alert-info alert-compact">
      <i class="fa fa-info-circle"></i> <?php echo htmlentities($_SESSION['msg']); ?>
    </div>
    <?php $_SESSION['msg'] = ""; ?>
  <?php endif; ?>

  <div class="card-wrap">
    <div class="list-header">
      <div class="muted"><i class="fa fa-list-ul"></i> قائمة المواعيد</div>
      <div class="muted"><i class="fa fa-refresh"></i> آخر تحديث: الآن</div>
    </div>

    <div class="table-responsive">
      <table class="table table-hover" id="appointments">
        <thead>
        <tr>
          <th class="text-center" style="width:70px">#</th>
          <th>اسم الطبيب</th>
          <th>التخصص</th>
          <th>الرسوم</th>
          <th>تاريخ/وقت الموعد</th>
          <th>تاريخ الإنشاء</th>
          <th style="width:140px">الحالة</th>
          <th class="text-center" style="width:140px">إجراء</th>
        </tr>
        </thead>
        <tbody>
        <?php if ($rows): $i=1; foreach ($rows as $row): ?>
          <tr>
            <td class="text-center"><?php echo $i++; ?>.</td>
            <td class="fw-bold"><?php echo htmlentities($row['docname']); ?></td>
            <td><?php echo htmlentities($row['doctorSpecialization']); ?></td>
            <td><strong><?php echo htmlentities($row['consultancyFees']); ?></strong></td>
            <td><?php echo htmlentities($row['appointmentDate']); ?> — <?php echo htmlentities($row['appointmentTime']); ?></td>
            <td class="muted"><?php echo htmlentities($row['postingDate']); ?></td>
            <td>
              <?php
              if ((int)$row['userStatus'] === 1 && (int)$row['doctorStatus'] === 1) {
                  echo '<span class="badge-soft badge-active"><i class="fa fa-check-circle"></i> نشط</span>';
              } elseif ((int)$row['userStatus'] === 0 && (int)$row['doctorStatus'] === 1) {
                  echo '<span class="badge-soft badge-user-cancel"><i class="fa fa-user-times"></i> أُلغي بواسطتك</span>';
              } else {
                  echo '<span class="badge-soft badge-doc-cancel"><i class="fa fa-ban"></i> أُلغي من الطبيب</span>';
              }
              ?>
            </td>
            <td class="text-center">
              <?php if ((int)$row['userStatus'] === 1 && (int)$row['doctorStatus'] === 1): ?>
                <form method="post" onsubmit="return confirm('هل تريد إلغاء هذا الموعد؟');" style="display:inline-block">
                  <input type="hidden" name="csrf" value="<?php echo $_SESSION['csrf']; ?>">
                  <input type="hidden" name="cancel_id" value="<?php echo (int)$row['id']; ?>">
                  <button class="btn btn-cancel btn-sm"><i class="fa fa-times"></i> إلغاء</button>
                </form>
              <?php else: ?>
                <span class="muted">—</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; else: ?>
          <tr>
            <td colspan="8" class="text-center muted" style="padding:28px">
              <i class="fa fa-calendar-o" style="font-size:22px"></i>
              <div class="mt-2">لا توجد مواعيد لعرضها حالياً.</div>
            </td>
          </tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.min.js"></script>
<script src="vendor/modernizr/modernizr.js"></script>
<script src="vendor/jquery-cookie/jquery.cookie.js"></script>
<script src="vendor/perfect-scrollbar/perfect-scrollbar.min.js"></script>
<script src="vendor/switchery/switchery.min.js"></script>
<script src="assets/js/main.js"></script>
<script>
jQuery(function(){ if (window.Main) Main.init(); });

let lastAppointmentCount = <?php echo (int)$total; ?>;
function checkForNewAppointments() {
  fetch(window.location.pathname + '?ajax=check_new')
    .then(r => r.json())
    .then(d => {
      if ((d.newCount|0) > lastAppointmentCount) {
        const n = document.createElement('div');
        n.style.cssText = "position:fixed;top:20px;right:20px;z-index:1000;background:#28a745;color:#fff;padding:12px 16px;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,.3)";
        n.innerHTML = '<i class="fa fa-bell"></i> تم حجز موعد جديد! <button onclick="location.reload()" style="background:#fff;color:#28a745;border:0;padding:4px 8px;border-radius:4px;margin-inline-start:8px;cursor:pointer">تحديث</button>';
        document.body.appendChild(n);
        setTimeout(()=>{ if(n.parentElement) n.remove(); }, 8000);
        lastAppointmentCount = d.newCount|0;
      }
    }).catch(()=>{});
}
setInterval(checkForNewAppointments, 30000);
setTimeout(checkForNewAppointments, 2000);
</script>
</body>
</html>
