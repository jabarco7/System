<?php
session_start();
include('include/config.php');

$docId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($docId <= 0) {
  echo "<script>alert('رقم الطبيب غير صحيح');window.history.back();</script>";
  exit;
}

/* بيانات الطبيب */
$stmt = mysqli_prepare($con, "SELECT id, doctorName, specilization, address, docFees, contactno, docEmail, creationDate, updationDate FROM doctors WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 'i', $docId);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$doc = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$doc) {
  echo "<script>alert('الطبيب غير موجود');window.history.back();</script>";
  exit;
}

/* الأوقات المحجوزة خلال 7 أيام قادمة */
$today = new DateTime('today');
$end   = (new DateTime('today'))->modify('+7 days'); // 7 أيام
$booked = []; // key: Y-m-d, value: set of 'H:i'

$startDate = $today->format('Y-m-d');
$endDate   = $end->format('Y-m-d');

$q = mysqli_prepare($con, "
  SELECT appointmentDate, appointmentTime
  FROM appointment
  WHERE doctorId = ? 
    AND appointmentDate BETWEEN ? AND ?
    AND userStatus = 1 AND doctorStatus = 1
");
mysqli_stmt_bind_param($q, 'iss', $docId, $startDate, $endDate);
mysqli_stmt_execute($q);
$r = mysqli_stmt_get_result($q);
while ($row = mysqli_fetch_assoc($r)) {
  $d = $row['appointmentDate'];
  $t = substr($row['appointmentTime'], 0, 5); // HH:MM
  if (!isset($booked[$d])) $booked[$d] = [];
  $booked[$d][$t] = true;
}
mysqli_stmt_close($q);

/* توليد المواعيد المتاحة (9:00–17:00 كل 30 دقيقة) */
function build_slots($date) {
  // افتراض دوام العيادة 09:00–17:00
  $open = new DateTime("$date 09:00");
  $close= new DateTime("$date 17:00");
  $slots = [];
  for ($t = clone $open; $t <= $close; $t->modify('+30 minutes')) {
    $slots[] = $t->format('H:i');
  }
  return $slots;
}

// مصفوفة الأيام + الأوقات المتاحة بعد استثناء المحجوز
$availableByDay = []; // date => [times]
$loop = new DatePeriod($today, new DateInterval('P1D'), $end);
foreach ($loop as $day) {
  $d = $day->format('Y-m-d');
  $all = build_slots($d);
  $avail = [];
  foreach ($all as $time) {
    if (!isset($booked[$d][$time])) $avail[] = $time;
  }
  // لا تُظهر الماضي إذا هو اليوم الحالي
  if ($d === date('Y-m-d')) {
    $now = new DateTime();
    $avail = array_values(array_filter($avail, function($t) use ($d, $now) {
      return new DateTime("$d $t") > $now;
    }));
  }
  if ($avail) $availableByDay[$d] = $avail;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>ملف الطبيب | <?php echo htmlspecialchars($doc['doctorName']); ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
  <style>
    body{font-family:'Tajawal',sans-serif;background:#f0f5f9}
    .container-narrow{max-width:1000px;margin:30px auto}
    .card-clean{background:#fff;border-radius:14px;box-shadow:0 8px 20px rgba(0,0,0,.06);padding:20px}
    .header{background:linear-gradient(90deg,#3498db,#4aa8e0);color:#fff;border-radius:12px;padding:16px 18px;margin-bottom:16px;text-align:center}
    .label{color:#6c757d}
    .slot-btn{margin:4px 6px 0 0}
    .day-head{background:#f5f8fc;border-radius:10px;padding:8px 12px;margin-top:8px}
    .badge-soft{background:#eef7ff;color:#0d6efd;border-radius:30px;padding:2px 10px;font-size:.8rem}
  </style>
</head>
<body>
<div class="container-narrow">
  <div class="header">
    <h4 class="m-0">ملف الطبيب</h4>
  </div>

  <!-- بطاقة الطبيب -->
  <div class="card-clean mb-3">
    <div class="d-flex align-items-start gap-3">
      <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width:72px;height:72px;">
        <span style="font-weight:700;font-size:20px;"><?php echo mb_substr($doc['doctorName'],0,1,'UTF-8'); ?></span>
      </div>
      <div class="flex-grow-1">
        <h5 class="m-0"><?php echo htmlspecialchars($doc['doctorName']); ?></h5>
        <div class="label">التخصص:</div>
        <div class="mb-2"><?php echo htmlspecialchars($doc['specilization']); ?></div>

        <div class="row g-3">
          <div class="col-md-3">
            <div class="label">الرسوم:</div>
            <div><?php echo htmlspecialchars($doc['docFees']); ?></div>
          </div>
          <div class="col-md-3">
            <div class="label">البريد:</div>
            <div><?php echo htmlspecialchars($doc['docEmail']); ?></div>
          </div>
          <div class="col-md-3">
            <div class="label">رقم التواصل:</div>
            <div><?php echo htmlspecialchars($doc['contactno']); ?></div>
          </div>
          <div class="col-md-3">
            <div class="label">العنوان:</div>
            <div><?php echo nl2br(htmlspecialchars($doc['address'])); ?></div>
          </div>
        </div>

        <div class="mt-3 d-flex gap-2">
          <a href="book-appointment.php?doctor=<?php echo (int)$doc['id']; ?>" class="btn btn-primary">
            حجز موعد
          </a>
          <a href="doctors.php?spec=<?php echo urlencode($doc['specilization']); ?>" class="btn btn-outline-secondary">
            رجوع لقائمة الأطباء
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- المواعيد المتاحة -->
  <div class="card-clean">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <h5 class="m-0">المواعيد المتاحة خلال أسبوع</h5>
      <span class="badge-soft"><?php echo count($availableByDay); ?> يوم</span>
    </div>

    <?php if (!empty($availableByDay)): ?>
      <?php foreach ($availableByDay as $date => $times): ?>
        <div class="day-head">
          <strong><?php echo date('Y-m-d', strtotime($date)); ?></strong>
        </div>
        <div class="mt-2">
          <?php foreach ($times as $t): 
              $link = "book-appointment.php?doctor={$doc['id']}&date=".urlencode($date)."&time=".urlencode($t);
          ?>
            <a href="<?php echo $link; ?>" class="btn btn-sm btn-outline-primary slot-btn">
              <?php echo htmlspecialchars($t); ?>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="alert alert-info m-0">لا توجد مواعيد متاحة خلال الأسبوع القادم. يرجى المحاولة لاحقًا.</div>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
