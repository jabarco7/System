<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('include/config.php');

if (empty($_SESSION['id'])) {
  header('location:logout.php');
  exit;
}

$userId = (int)$_SESSION['id'];

/* ===== CSRF ===== */
if (empty($_SESSION['csrf'])) {
  $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

/* ===== Helpers: وجود الجداول/الأعمدة ===== */
function tableExists(mysqli $con, $table)
{
  $dbRow = $con->query("SELECT DATABASE() AS db");
  if (!$dbRow) return false;
  $dbRes = $dbRow->fetch_assoc();
  if (!$dbRes || empty($dbRes['db'])) return false;
  $db = $dbRes['db'];
  $stmt = $con->prepare("SELECT 1 FROM information_schema.tables WHERE table_schema=? AND table_name=? LIMIT 1");
  if (!$stmt) return false;
  $stmt->bind_param("ss", $db, $table);
  $stmt->execute();
  $stmt->store_result();
  $ok = $stmt->num_rows > 0;
  $stmt->close();
  return $ok;
}
function columnExists(mysqli $con, $table, $col)
{
  $dbRow = $con->query("SELECT DATABASE() AS db");
  if (!$dbRow) return false;
  $db = $dbRow->fetch_assoc()['db'] ?? '';
  if (!$db) return false;
  $stmt = $con->prepare("SELECT 1 FROM information_schema.columns WHERE table_schema=? AND table_name=? AND column_name=? LIMIT 1");
  if (!$stmt) return false;
  $stmt->bind_param("sss", $db, $table, $col);
  $stmt->execute();
  $stmt->store_result();
  $ok = $stmt->num_rows > 0;
  $stmt->close();
  return $ok;
}
function norm_time($s)
{
  $s = trim((string)$s);
  if ($s === '') return $s;
  if (preg_match('/am|pm/i', $s)) {
    $ts = strtotime($s);
    return $ts ? date('H:i', $ts) : substr($s, 0, 5);
  }
  if (preg_match('/^\d{1,2}:\d{2}/', $s, $m)) return $m[0];
  return substr($s, 0, 5);
}

/* ===== قراءة دوام الطبيب =====
   يعيد مصفوفة فيها أيام وأكتر من فترة لكل يوم:
   ['days'=> [ 0 => [ ['start'=>'HH:MM','end'=>'HH:MM','slot'=>15], ... ], 1 => [...], ... ]]
*/
function getDoctorSchedule(mysqli $con, $doctorId)
{
  $out = ['days' => []];

  // أولاً: جدول doctor_schedule
  if (tableExists($con, 'doctor_schedule')) {
    $stmt = $con->prepare("SELECT day_of_week, start_time, end_time, slot_minutes, active FROM doctor_schedule WHERE doctor_id=? ORDER BY day_of_week, start_time");
    if ($stmt) {
      $stmt->bind_param("i", $doctorId);
      $stmt->execute();
      $res = $stmt->get_result();
      while ($r = $res->fetch_assoc()) {
        $d = (int)$r['day_of_week'];          // 0..6 أو 1..7
        if ($d >= 1 && $d <= 7) $d = $d % 7;   // حوّل 1..7 إلى 0..6
        if ($d < 0 || $d > 6) continue;
        if ((int)$r['active'] !== 1) continue;
        $out['days'][$d] = $out['days'][$d] ?? [];
        $out['days'][$d][] = [
          'start' => norm_time($r['start_time']),
          'end'   => norm_time($r['end_time']),
          'slot'  => max(5, min(120, (int)$r['slot_minutes'] ?: 30))
        ];
      }
      $stmt->close();
      if (!empty($out['days'])) return $out;
    }
  }

  // ثانياً: fallback أعمدة في doctors إن وُجدت (workingDays, startTime, endTime)
  $hasWorkingDays = columnExists($con, 'doctors', 'workingDays') || columnExists($con, 'doctors', 'workDays');
  $hasStart       = columnExists($con, 'doctors', 'startTime');
  $hasEnd         = columnExists($con, 'doctors', 'endTime');

  if ($hasWorkingDays && $hasStart && $hasEnd) {
    $colDays = columnExists($con, 'doctors', 'workingDays') ? 'workingDays' : 'workDays';
    $stmt = $con->prepare("SELECT $colDays AS days, startTime, endTime FROM doctors WHERE id=?");
    if ($stmt) {
      $stmt->bind_param("i", $doctorId);
      $stmt->execute();
      $r = $stmt->get_result()->fetch_assoc();
      $stmt->close();
      if ($r) {
        $st = norm_time($r['startTime']);
        $en = norm_time($r['endTime']);
        $map = [
          'الاحد' => 0,
          'الأحد' => 0,
          'احد' => 0,
          'sun' => 0,
          'الاثنين' => 1,
          'الإثنين' => 1,
          'اثنين' => 1,
          'mon' => 1,
          'الثلاثاء' => 2,
          'ثلاثاء' => 2,
          'tue' => 2,
          'الاربعاء' => 3,
          'الأربعاء' => 3,
          'اربعاء' => 3,
          'wed' => 3,
          'الخميس' => 4,
          'thu' => 4,
          'الجمعة' => 5,
          'fri' => 5,
          'السبت' => 6,
          'sat' => 6
        ];
        $parts = array_filter(array_map('trim', preg_split('/[,|\s]+/u', mb_strtolower($r['days'], 'UTF-8'))));
        foreach ($parts as $p) {
          if ($p === '') continue;
          if (is_numeric($p)) {
            $d = (int)$p;
            if ($d >= 1 && $d <= 7) $d = $d % 7;
            if ($d >= 0 && $d <= 6) $out['days'][$d] = [['start' => $st, 'end' => $en, 'slot' => 30]];
          } else {
            $p = strtr($p, ['أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ى' => 'ي']);
            $d = $map[$p] ?? null;
            if ($d !== null) $out['days'][$d] = [['start' => $st, 'end' => $en, 'slot' => 30]];
          }
        }
      }
    }
  }
  return $out;
}

/* ===== إنشاء حجز (POST) ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_submit'])) {
  $back = 'book-appointment.php'; // اسم الملف الحالي
  if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
    $_SESSION['msg'] = 'فشل التحقق الأمني. أعد المحاولة.';
    header("Location: $back");
    exit;
  }

  $doctorId = (int)($_POST['doctor_id'] ?? 0);
  $date     = trim($_POST['date'] ?? '');
  $time     = trim($_POST['time'] ?? '');
  $note     = trim($_POST['note'] ?? '');

  if ($doctorId <= 0 || $date === '' || $time === '') {
    $_SESSION['msg'] = 'يرجى اختيار التخصص والطبيب واليوم والوقت.';
    header("Location: $back");
    exit;
  }

  // بيانات الطبيب + الرسوم من DB (لا نعتمد على أي قيمة من النموذج)
  $doc = null;
  if ($stmt = $con->prepare("SELECT id, doctorName, specilization, docFees AS consultancyFees FROM doctors WHERE id=? LIMIT 1")) {
    $stmt->bind_param("i", $doctorId);
    $stmt->execute();
    $doc = $stmt->get_result()->fetch_assoc();
    $stmt->close();
  }
  if (!$doc) {
    $_SESSION['msg'] = 'الطبيب غير موجود.';
    header("Location: $back");
    exit;
  }

  // التحقق من الدوام (اليوم والوقت)
  $sched = getDoctorSchedule($con, $doctorId);
  $dow   = (int)date('w', strtotime($date)); // 0 أحد .. 6 سبت
  if (empty($sched['days']) || empty($sched['days'][$dow])) {
    $_SESSION['msg'] = 'اليوم المختار خارج دوام الطبيب.';
    header("Location: $back");
    exit;
  }
  // تأكد أن الوقت ضمن أي فترة من فترات نفس اليوم
  $okTime = false;
  foreach ($sched['days'][$dow] as $p) {
    if ($time >= $p['start'] && $time < $p['end']) {
      $okTime = true;
      break;
    }
  }
  if (!$okTime) {
    $_SESSION['msg'] = 'الوقت المختار خارج دوام الطبيب.';
    header("Location: $back");
    exit;
  }

  // منع التعارض
  $busy = 0;
  if ($stmt = $con->prepare("SELECT COUNT(*) FROM appointment WHERE doctorId=? AND appointmentDate=? AND appointmentTime=? AND userStatus=1 AND doctorStatus=1")) {
    $stmt->bind_param("iss", $doctorId, $date, $time);
    $stmt->execute();
    $stmt->bind_result($busy);
    $stmt->fetch();
    $stmt->close();
  }
  if ($busy > 0) {
    $_SESSION['msg'] = 'الوقت محجوز بالفعل، اختر وقتًا آخر.';
    header("Location: $back");
    exit;
  }

  // الحفظ (بدون userNote لأن الجدول عندك ما فيه هذا العمود)
  $stmt = $con->prepare("INSERT INTO appointment (userId, doctorId, doctorSpecialization, consultancyFees, appointmentDate, appointmentTime, postingDate, userStatus, doctorStatus)
                           VALUES (?,?,?,?,?,?, NOW(), 1, 1)");
  if ($stmt) {
    $stmt->bind_param("iiisss", $userId, $doctorId, $doc['specilization'], $doc['consultancyFees'], $date, $time);
    $ok = $stmt->execute();
    $stmt->close();
    $_SESSION['msg'] = $ok ? 'تم حجز الموعد بنجاح ✅' : 'تعذر حفظ الحجز.';
  } else {
    $_SESSION['msg'] = 'تعذر حفظ الحجز.';
  }
  header("Location: $back");
  exit;
}

/* ===== AJAX ===== */
if (isset($_GET['ajax'])) {
  header('Content-Type: application/json; charset=utf-8');
  $action = $_GET['ajax'];

  if ($action === 'specs') {
    $arr = [];
    $q = $con->query("SELECT DISTINCT specilization FROM doctors WHERE specilization<>'' ORDER BY specilization");
    while ($r = $q->fetch_assoc()) $arr[] = $r['specilization'];
    echo json_encode(['ok' => true, 'data' => $arr], JSON_UNESCAPED_UNICODE);
    exit;
  }

  if ($action === 'doctors_by_spec') {
    $spec = trim($_GET['spec'] ?? '');
    $out = [];
    if ($spec !== '') {
      $stmt = $con->prepare("SELECT id, doctorName FROM doctors WHERE specilization=? ORDER BY doctorName");
      $stmt->bind_param("s", $spec);
      $stmt->execute();
      $res = $stmt->get_result();
      while ($r = $res->fetch_assoc()) $out[] = $r;
      $stmt->close();
    }
    echo json_encode(['ok' => true, 'data' => $out], JSON_UNESCAPED_UNICODE);
    exit;
  }

  if ($action === 'doctor_info') {
    $id = (int)($_GET['id'] ?? 0);
    // 👈 تصحيح: docFees AS consultancyFees
    $stmt = $con->prepare("SELECT id, doctorName, specilization, docFees AS consultancyFees FROM doctors WHERE id=? LIMIT 1");
    if (!$stmt) {
      http_response_code(500);
      echo json_encode(['ok' => false, 'msg' => 'DB Error'], JSON_UNESCAPED_UNICODE);
      exit;
    }
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $doc = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$doc) {
      http_response_code(404);
      echo json_encode(['ok' => false, 'msg' => 'الطبيب غير موجود'], JSON_UNESCAPED_UNICODE);
      exit;
    }
    $sched = getDoctorSchedule($con, $id);
    echo json_encode(['ok' => true, 'data' => $doc, 'schedule' => $sched], JSON_UNESCAPED_UNICODE);
    exit;
  }

  if ($action === 'slots') {
    $id = (int)($_GET['id'] ?? 0);
    $date = trim($_GET['date'] ?? '');
    if ($id <= 0 || !$date) {
      http_response_code(400);
      echo json_encode(['ok' => false, 'msg' => 'بيانات غير مكتملة'], JSON_UNESCAPED_UNICODE);
      exit;
    }

    $sched = getDoctorSchedule($con, $id);
    $dow = (int)date('w', strtotime($date));
    $slots = [];

    if (!empty($sched['days'][$dow])) {
      // توليد الساعات من كل الفترات المسجلة لهذا اليوم
      foreach ($sched['days'][$dow] as $p) {
        $start = $p['start'];
        $end = $p['end'];
        $stepMin = (int)$p['slot'] ?: 30;
        $t = strtotime($start);
        $endTs = strtotime($end);
        while ($t < $endTs) {
          $slots[] = date('H:i', $t);
          $t = strtotime("+{$stepMin} minutes", $t);
        }
      }
      $slots = array_values(array_unique($slots));
      sort($slots);

      // احذف المحجوز
      $busy = [];
      $stmt = $con->prepare("SELECT appointmentTime FROM appointment WHERE doctorId=? AND appointmentDate=? AND userStatus=1 AND doctorStatus=1");
      $stmt->bind_param("is", $id, $date);
      $stmt->execute();
      $res = $stmt->get_result();
      while ($r = $res->fetch_assoc()) $busy[] = substr($r['appointmentTime'], 0, 5);
      $stmt->close();

      $slots = array_values(array_diff($slots, $busy));
    }

    echo json_encode(['ok' => true, 'data' => $slots, 'hasSchedule' => !empty($sched['days'])], JSON_UNESCAPED_UNICODE);
    exit;
  }

  echo json_encode(['ok' => false, 'msg' => 'طلب غير معروف'], JSON_UNESCAPED_UNICODE);
  exit;
}

/* ===== بيانات العرض ===== */
$patientName = $_SESSION['patient_name'] ?? 'المريض';
$nextAppt = null;
if ($stmt = mysqli_prepare(
  $con,
  "SELECT a.id, a.appointmentDate, a.appointmentTime, a.consultancyFees,
            d.doctorName, d.specilization
     FROM appointment a
     JOIN doctors d ON d.id = a.doctorId
     WHERE a.userId = ? AND a.userStatus = 1 AND a.doctorStatus = 1
           AND a.appointmentDate >= CURDATE()
     ORDER BY a.appointmentDate ASC, STR_TO_DATE(a.appointmentTime, '%H:%i') ASC
     LIMIT 1"
)) {
  mysqli_stmt_bind_param($stmt, "i", $userId);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  if ($res && mysqli_num_rows($res) > 0) $nextAppt = mysqli_fetch_assoc($res);
  mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
  <meta charset="UTF-8" />
  <title>تسجيل دخول مريض</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">

  <style>
    :root {
      --primary: #3498db;
      --secondary: #1a2530
    }

    body {
      font-family: 'Tajawal', sans-serif;
      background: #f0f5f9;
      color: #333;
      padding-top: 60px;
      padding-bottom: 76px
    }

    .layout {
      display: flex;
      gap: 20px
    }

    .sidebar {
      width: 260px;
      background: #fff;
      border-radius: 15px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, .05);
      padding: 20px;
      height: 100%
    }

    .patient-card {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 14px;
      border: 1px dashed #e5eef6;
      border-radius: 12px;
      background: #fbfdff;
      margin-bottom: 16px
    }

    .patient-avatar {
      width: 44px;
      height: 44px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(90deg, var(--primary), #4aa8e0);
      color: #fff;
      font-weight: 700
    }

    .menu {
      list-style: none;
      padding: 0;
      margin: 0
    }

    .menu li {
      margin-bottom: 8px
    }

    .menu a {
      display: flex;
      align-items: center;
      gap: 10px;
      text-decoration: none;
      color: #22313f;
      padding: 10px 12px;
      border-radius: 10px
    }

    .menu a:hover,
    .menu a.active {
      background: #eef7ff;
      color: #0d6efd
    }

    .badge-soft {
      background: #eef7ff;
      color: #0d6efd;
      border-radius: 30px;
      padding: 2px 10px;
      font-size: .8rem
    }

    .app-header {
      background: linear-gradient(90deg, #3498db, #4aa8e0);
      color: #fff;
      padding: 22px 26px;
      border-radius: 10px;
      margin-bottom: 20px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, .1)
    }

    .main {
      flex: 1
    }

    .card-clean {
      background: #fff;
      border-radius: 15px;
      padding: 22px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, .05)
    }

    .disease-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
      gap: 14px
    }

    .disease-card {
      background: #fff;
      border: 1px solid #e9f1f7;
      border-radius: 14px;
      padding: 16px;
      text-align: center;
      cursor: pointer;
      transition: .2s;
      box-shadow: 0 2px 8px rgba(0, 0, 0, .02)
    }

    .disease-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 8px 18px rgba(0, 0, 0, .08)
    }

    .disease-card i {
      font-size: 1.8rem;
      color: #3498db;
      margin-bottom: 8px
    }

    .appt-card {
      display: flex;
      gap: 14px;
      align-items: center
    }

    .appt-icon {
      width: 48px;
      height: 48px;
      border-radius: 10px;
      background: #eef7ff;
      display: flex;
      align-items: center;
      justify-content: center
    }

    /* نافذة الحجز */
    .rt-modal {
      position: fixed;
      inset: 0;
      display: none;
      z-index: 200000
    }

    .rt-modal.open {
      display: block
    }

    .rt-backdrop {
      position: absolute;
      inset: 0;
      background: rgba(0, 0, 0, .55)
    }

    .rt-dialog {
      position: relative;
      margin: 6vh auto;
      width: min(92vw, 900px);
      max-width: 900px;
      background: #fff;
      border-radius: 14px;
      box-shadow: 0 12px 40px rgba(0, 0, 0, .25);
      overflow: hidden;
      direction: rtl;
      text-align: right;
      max-height: 88vh;
      display: flex;
      flex-direction: column
    }

    .rt-header,
    .rt-footer {
      padding: 12px 16px;
      border-bottom: 1px solid #eee
    }

    .rt-footer {
      border-bottom: 0;
      border-top: 1px solid #eee;
      display: flex;
      gap: 8px
    }

    .rt-title {
      margin: 0;
      font-weight: 700
    }

    .rt-close {
      position: absolute;
      left: 12px;
      top: 8px;
      border: 0;
      background: transparent;
      font-size: 26px;
      cursor: pointer
    }

    .rt-body {
      padding: 16px;
      overflow: auto
    }

    .muted {
      color: #777
    }

    .sched-box {
      background: #f8fbff;
      border: 1px solid #e6f1fb;
      border-radius: 10px;
      padding: 12px
    }

    .sched-box .day {
      display: flex;
      justify-content: space-between;
      padding: 6px 10px;
      border-radius: 8px
    }

    .sched-box .day.on {
      background: #eef7ff
    }

    .form-control,
    .form-select {
      border-radius: 10px
    }

    .badge-day {
      display: inline-block;
      background: #eef7ff;
      color: #0d6efd;
      border-radius: 20px;
      padding: 2px 10px;
      margin: 0 0 0 6px
    }

    .readonly-field {
      background: #f5f6f8 !important;
      pointer-events: none;
      color: #333
    }
  </style>
</head>

<body>
  <div class="container-xxl">
    <div class="layout">
      <!-- Sidebar -->
      <aside class="sidebar">
        <div class="patient-card">
          <div class="patient-avatar"><?php echo mb_substr($patientName, 0, 1, 'UTF-8'); ?></div>
          <div>
            <div style="font-weight:700"><?php echo htmlspecialchars($patientName); ?></div>
            <div class="text-muted" style="font-size:.9rem">مرحباً بك</div>
          </div>
        </div>
        <ul class="menu">
          <li><a href="#choose-disease" class="active"><i class="fa-solid fa-notes-medical"></i><span>اختيار نوع المرض</span><span class="ms-auto badge-soft">الآن</span></a></li>
          <li><a href="#" class="js-open-book"><i class="fa-regular fa-calendar-plus"></i><span>حجز موعد</span></a></li>
          <li><a href="appointment-history.php"><i class="fa-regular fa-calendar-check"></i><span>حجوزاتي</span></a></li>
          <li><a href="edit-profile.php"><i class="fa-regular fa-id-badge"></i><span>ملفي الطبي</span></a></li>
          <li><a href="view-medhistory.php"><i class="fa-solid fa-vial"></i><span>نتائج الفحوصات</span></a></li>
          <li><a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i><span>تسجيل الخروج</span></a></li>
        </ul>
      </aside>

      <main class="main">
        <div class="app-header">
          <h1 class="m-0">تسجيل دخول مريض</h1>
        </div>

        <!-- موعدي القادم -->
        <section class="card-clean mb-3">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="m-0" style="font-weight:700;color:#1a2530">موعدي القادم</h5>
          </div>

          <?php if ($nextAppt): ?>
            <div class="appt-card">
              <div class="appt-icon"><i class="fa-regular fa-calendar-check"></i></div>
              <div class="flex-grow-1">
                <div class="fw-bold"><?php echo htmlspecialchars($nextAppt['doctorName']); ?> — <span class="text-muted"><?php echo htmlspecialchars($nextAppt['specilization']); ?></span></div>
                <div class="text-muted small">
                  التاريخ: <?php echo htmlspecialchars($nextAppt['appointmentDate']); ?> —
                  الوقت: <?php echo htmlspecialchars($nextAppt['appointmentTime']); ?> —
                  الرسوم: <span class="fw-bold"><?php echo htmlspecialchars($nextAppt['consultancyFees']); ?></span>
                </div>
              </div>
              <div class="d-flex gap-2">
                <a class="btn btn-sm btn-outline-primary" href="appointment-history.php"><i class="fa-regular fa-eye me-1"></i> التفاصيل</a>
                <a class="btn btn-sm btn-outline-danger" href="appointment-history.php"><i class="fa-regular fa-calendar-xmark me-1"></i> إلغاء</a>
              </div>
            </div>
          <?php else: ?>
            <div class="d-flex align-items-center">
              <div class="appt-icon me-2"><i class="fa-regular fa-calendar"></i></div>
              <div class="flex-grow-1">
                <div class="fw-bold">لا توجد مواعيد قادمة</div>
                <div class="text-muted small">احجز موعدك الآن.</div>
              </div>
              <button class="btn btn-primary js-open-book"><i class="fa-regular fa-calendar-plus me-1"></i> حجز موعد</button>
            </div>
          <?php endif; ?>
        </section>

        <!-- اختيار نوع المرض (اختصار يفتح الحجز مع تحديد التخصص) -->
        <section id="choose-disease" class="card-clean mb-3">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="m-0" style="font-weight:700;color:#1a2530">اختر نوع المرض الذي تعاني منه</h5>
            <small class="text-muted">سيساعدنا هذا على توجيهك للتخصص المناسب</small>
          </div>
          <div class="disease-grid">
            <a class="disease-card js-open-book" data-spec="أمراض القلب"><i class="fa-solid fa-heart-pulse"></i>
              <div>أمراض القلب</div>
            </a>
            <a class="disease-card js-open-book" data-spec="الباطنية"><i class="fa-solid fa-staff-snake"></i>
              <div>الباطنية</div>
            </a>
            <a class="disease-card js-open-book" data-spec="العظام"><i class="fa-solid fa-bone"></i>
              <div>العظام</div>
            </a>
            <a class="disease-card js-open-book" data-spec="الأسنان"><i class="fa-solid fa-tooth"></i>
              <div>الأسنان</div>
            </a>
            <a class="disease-card js-open-book" data-spec="العيون"><i class="fa-regular fa-eye"></i>
              <div>العيون</div>
            </a>
            <a class="disease-card js-open-book" data-spec="الجلدية"><i class="fa-regular fa-face-smile"></i>
              <div>الجلدية</div>
            </a>
            <a class="disease-card js-open-book" data-spec="أطفال"><i class="fa-solid fa-baby"></i>
              <div>أطفال</div>
            </a>
            <a class="disease-card js-open-book" data-spec="نساء وولادة"><i class="fa-solid fa-person-pregnant"></i>
              <div>نساء وولادة</div>
            </a>
          </div>
        </section>

        <?php if (isset($_SESSION['msg'])): ?>
          <div class="alert alert-success rounded-3"><i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($_SESSION['msg']); ?></div>
          <?php unset($_SESSION['msg']); ?>
        <?php endif; ?>
      </main>
    </div>
  </div>

  <!-- نافذة الحجز -->
  <div id="bookModal" class="rt-modal" aria-hidden="true">
    <div class="rt-backdrop" data-close></div>
    <div class="rt-dialog" role="dialog" aria-modal="true" aria-labelledby="bookTitle">
      <button class="rt-close" data-close aria-label="إغلاق">&times;</button>
      <div class="rt-header">
        <h4 id="bookTitle" class="rt-title"><i class="fa-regular fa-calendar-plus"></i> حجز موعد جديد</h4>
      </div>

      <div class="rt-body">
        <form id="bookForm" method="post" action="">
          <input type="hidden" name="csrf" value="<?php echo $_SESSION['csrf']; ?>">
          <input type="hidden" name="book_submit" value="1">

          <div class="row g-3">
            <div class="col-lg-6">
              <label class="form-label">التخصص</label>
              <select class="form-select" id="bf-spec" required>
                <option value="">— اختر التخصص —</option>
              </select>
            </div>
            <div class="col-lg-6">
              <label class="form-label">الطبيب</label>
              <select class="form-select" id="bf-doctor" name="doctor_id" disabled required>
                <option value="">— اختر الطبيب —</option>
              </select>
            </div>

            <!-- اختيار يوم ضمن أيام دوام الطبيب -->
            <div class="col-lg-6">
              <label class="form-label">اليوم المتاح</label>
              <select class="form-select" id="bf-day" disabled required>
                <option value="">— اختر اليوم —</option>
              </select>
              <div class="form-text">
                <span class="muted">سيُحجز لأقرب تاريخ يوافق اليوم المختار:</span>
                <strong id="bf-date-human" class="ms-1"></strong>
              </div>
              <!-- التاريخ الفعلي يُرسل مخفيًا للخادم -->
              <input type="hidden" id="bf-date" name="date" value="">
            </div>

            <div class="col-lg-6">
              <label class="form-label">الوقت المتاح</label>
              <select class="form-select" id="bf-time" name="time" disabled required>
                <option value="">— اختر الوقت —</option>
              </select>
            </div>

            <div class="col-lg-6">
              <label class="form-label">الرسوم</label>
              <input type="text" class="form-control readonly-field" id="bf-fees" readonly tabindex="-1">
            </div>
            <div class="col-lg-6">
              <label class="form-label">التخصص المختار</label>
              <input type="text" class="form-control readonly-field" id="bf-spec-ro" readonly tabindex="-1">
            </div>

            <div class="col-12">
              <label class="form-label">ملاحظات (اختياري)</label>
              <textarea class="form-control" name="note" rows="3" placeholder="سبب الزيارة أو أي معلومات للطبيب"></textarea>
            </div>
          </div>
        </form>

        <div class="row mt-3 g-3">
          <div class="col-lg-12">
            <div class="sched-box">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <strong>دوام الطبيب</strong>
                <small class="muted">يظهر بعد اختيار الطبيب</small>
              </div>
              <div id="schedList" class="d-grid" style="grid-template-columns:1fr;gap:6px"></div>
            </div>
          </div>
        </div>
      </div>

      <div class="rt-footer">
        <button type="button" class="btn btn-secondary" data-close>إغلاق</button>
        <button type="submit" form="bookForm" class="btn btn-primary">تأكيد الحجز</button>
      </div>
    </div>
  </div>


  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script>
    (function($) {
      const $modal = $('#bookModal');
      const dayNames = ['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
      let allowedDays = []; // أرقام الأيام المتاحة 0..6

      const openModal = (prefSpec = '') => {
        $('body').addClass('rt-modal-open');
        $modal.addClass('open').attr('aria-hidden', 'false');
        loadSpecs().then(() => {
          if (prefSpec) $('#bf-spec').val(prefSpec).trigger('change');
        });
      };
      const closeModal = () => {
        $('body').removeClass('rt-modal-open');
        $modal.removeClass('open').attr('aria-hidden', 'true');
        resetForm();
      };
      $(document).on('click', '[data-close]', closeModal);
      $(document).on('click', '.js-open-book', function(e) {
        e.preventDefault();
        openModal($(this).data('spec') || '');
      });

      function resetForm() {
        $('#bf-spec').val('');
        $('#bf-doctor').prop('disabled', true).html('<option value="">— اختر الطبيب —</option>');
        $('#bf-day').prop('disabled', true).html('<option value="">— اختر اليوم —</option>');
        $('#bf-time').prop('disabled', true).html('<option value="">— اختر الوقت —</option>');
        $('#bf-fees').val('');
        $('#bf-spec-ro').val('');
        $('#bf-date').val('');
        $('#bf-date-human').text('');
        $('#schedList').empty();
        allowedDays = [];
      }

      // تحميل التخصصات
      let cachedSpecs = null;

      function loadSpecs() {
        if (cachedSpecs) {
          fillSpecs(cachedSpecs);
          return Promise.resolve();
        }
        return $.getJSON('?ajax=specs').then(j => {
          if (j.ok) {
            cachedSpecs = j.data;
            fillSpecs(j.data);
          }
        });
      }

      function fillSpecs(data) {
        const $s = $('#bf-spec').empty().append('<option value="">— اختر التخصص —</option>');
        data.forEach(sp => $s.append('<option value="' + escapeHtml(sp) + '">' + escapeHtml(sp) + '</option>'));
      }

      // عند اختيار التخصص
      $('#bf-spec').on('change', function() {
        const spec = $(this).val();
        $('#bf-spec-ro').val(spec || '');
        $('#bf-doctor').prop('disabled', true).html('<option value="">— اختر الطبيب —</option>');
        $('#bf-day').prop('disabled', true).html('<option value="">— اختر اليوم —</option>');
        $('#bf-time').prop('disabled', true).html('<option value="">— اختر الوقت —</option>');
        $('#bf-fees').val('');
        $('#bf-date').val('');
        $('#bf-date-human').text('');
        $('#schedList').empty();
        allowedDays = [];
        if (!spec) return;

        $.getJSON('?ajax=doctors_by_spec', {
          spec
        }).done(j => {
          if (j.ok) {
            const $d = $('#bf-doctor').prop('disabled', j.data.length === 0);
            if (j.data.length === 0) $d.html('<option value="">لا يوجد أطباء لهذا التخصص</option>');
            else {
              $d.html('<option value="">— اختر الطبيب —</option>');
              j.data.forEach(row => $d.append('<option value="' + row.id + '">' + escapeHtml(row.doctorName) + '</option>'));
            }
          }
        });
      });

      // عند اختيار الطبيب
      $('#bf-doctor').on('change', function() {
        const id = $(this).val();
        $('#bf-day').prop('disabled', !id).html('<option value="">— اختر اليوم —</option>');
        $('#bf-time').prop('disabled', true).html('<option value="">— اختر الوقت —</option>');
        $('#bf-fees').val('');
        $('#bf-date').val('');
        $('#bf-date-human').text('');
        $('#schedList').empty();
        allowedDays = [];
        if (!id) return;

        $.getJSON('?ajax=doctor_info', {
          id
        }).done(j => {
          if (!j.ok) return;
          // الرسوم (غير قابلة للتعديل)
          $('#bf-fees').val(j.data.consultancyFees || '');

          // رسم الدوام + بناء قائمة الأيام المتاحة
          const daysObj = (j.schedule && j.schedule.days) ? j.schedule.days : {};
          renderSchedule(daysObj);

          allowedDays = Object.keys(daysObj).map(n => parseInt(n, 10)).sort((a, b) => a - b);
          const $day = $('#bf-day');
          if (allowedDays.length) {
            $day.append(allowedDays.map(d => '<option value="' + d + '">' + dayNames[d] + '</option>').join(''))
              .prop('disabled', false);
          } else {
            $day.html('<option value="">لا يوجد دوام مُسجّل</option>').prop('disabled', true);
          }
        });
      });

      // عند اختيار يوم
      $('#bf-day').on('change', function() {
        const dow = $(this).val() === '' ? null : parseInt($(this).val(), 10);
        $('#bf-time').prop('disabled', true).html('<option value="">— اختر الوقت —</option>');
        $('#bf-date').val('');
        $('#bf-date-human').text('');
        if (dow === null || isNaN(dow)) return;

        // احسب أقرب تاريخ يوافق هذا اليوم
        const dateStr = nextDateForDay(dow); // YYYY-MM-DD
        $('#bf-date').val(dateStr);
        $('#bf-date-human').text(humanDate(dateStr));

        // حمّل الأوقات المتاحة لهذا التاريخ
        const id = $('#bf-doctor').val();
        $('#bf-time').html('<option value="">— جاري التحميل —</option>');
        $.getJSON('?ajax=slots', {
          id,
          date: dateStr
        }).done(s => {
          const $t = $('#bf-time').empty();
          if (!s.ok || s.data.length === 0) {
            $t.append('<option value="">لا توجد أوقات متاحة</option>').prop('disabled', true);
          } else {
            $t.append('<option value="">— اختر الوقت —</option>');
            s.data.forEach(tm => $t.append('<option value="' + tm + '">' + tm + '</option>'));
            $t.prop('disabled', false);
          }
        });
      });

      // أدوات
      function nextDateForDay(dow) {
        const d = new Date();
        d.setHours(0, 0, 0, 0);
        let diff = (dow - d.getDay() + 7) % 7; // أقرب نفس اليوم (اليوم أو القادم)
        const target = new Date(d);
        target.setDate(d.getDate() + diff);
        return target.toISOString().slice(0, 10);
      }

      function humanDate(yyyy_mm_dd) {
        const d = new Date(yyyy_mm_dd + 'T00:00:00');
        const dn = ['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'][d.getDay()];
        return dn + ' ' + d.toLocaleDateString('ar-EG');
      }

      function renderSchedule(daysObj) {
        const $box = $('#schedList').empty();
        const names = ['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
        if (!Object.keys(daysObj || {}).length) {
          $box.html('<div class="muted">لا يوجد دوام مُسجّل لهذا الطبيب.</div>');
          return;
        }
        for (let i = 0; i < 7; i++) {
          if (daysObj[i]) {
            // لو في أكثر من فترة لليوم
            const spans = daysObj[i].map(p => ('<strong>' + p.start + '</strong> — <strong>' + p.end + '</strong> (' + (p.slot || 30) + ' د)'));
            $box.append('<div class="day on"><div>' + names[i] + '</div><div>' + spans.join(' ، ') + '</div></div>');
          } else {
            $box.append('<div class="day"><div>' + names[i] + '</div><div class="muted">إجازة</div></div>');
          }
        }
      }

      function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, m => ({
          '&': '&amp;',
          '<': '&lt;',
          '>': '&gt;',
          '"': '&quot;',
          "'": '&#039;'
        } [m]));
      }

      // إغلاق بـ ESC
      $(document).on('keydown', e => {
        if (e.key === 'Escape') $modal.removeClass('open');
      });

    })(jQuery);
  </script>
</body>

</html>