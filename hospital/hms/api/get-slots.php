<?php
// System/hospital/hms/api/get-slots.php
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../include/config.php';

$doctorId = isset($_GET['doctorId']) ? (int)$_GET['doctorId'] : 0;
$days     = isset($_GET['days']) ? max(1, min((int)$_GET['days'], 30)) : 7; // افتراضي 7 أيام
$tz       = new DateTimeZone('Asia/Aden');

if ($doctorId <= 0) {
  echo json_encode(['ok'=>false, 'msg'=>'doctorId مفقود']);
  exit;
}

$now = new DateTime('now', $tz);
$todayYmd = $now->format('Y-m-d');

$slots = [];

/* 1) اسحب دوام الطبيب */
$schedStmt = $con->prepare("
  SELECT day_of_week, start_time, end_time, slot_minutes
  FROM doctor_schedule
  WHERE doctorId = ?
");
$schedStmt->bind_param('i', $doctorId);
$schedStmt->execute();
$schedRes = $schedStmt->get_result();

$schedules = []; // day_of_week => rows[]
while ($r = $schedRes->fetch_assoc()) {
  $dow = (int)$r['day_of_week']; // 0=Sunday
  $schedules[$dow][] = [
    'start' => $r['start_time'],
    'end'   => $r['end_time'],
    'step'  => (int)$r['slot_minutes']
  ];
}
$schedStmt->close();

if (empty($schedules)) {
  echo json_encode(['ok'=>true, 'slots'=>[]]); // لا يوجد دوام مضبوط
  exit;
}

/* 2) المواعيد المحجوزة */
$fromDate = (new DateTime('today', $tz))->format('Y-m-d');
$toDate   = (new DateTime('today +'.$days.' day', $tz))->format('Y-m-d');

$bookedStmt = $con->prepare("
  SELECT appointmentDate, appointmentTime
  FROM appointment
  WHERE doctorId = ?
    AND userStatus = 1
    AND doctorStatus = 1
    AND appointmentDate BETWEEN ? AND ?
");
$bookedStmt->bind_param('iss', $doctorId, $fromDate, $toDate);
$bookedStmt->execute();
$bookedRes = $bookedStmt->get_result();

$booked = []; // 'Y-m-d H:i:s' => true
while ($b = $bookedRes->fetch_assoc()) {
  $booked[$b['appointmentDate'].' '.$b['appointmentTime']] = true;
}
$bookedStmt->close();

/* 3) الإجازات/الغياب */
$unStmt = $con->prepare("
  SELECT date, start_time, end_time
  FROM doctor_unavailable
  WHERE doctorId = ?
    AND date BETWEEN ? AND ?
");
$unStmt->bind_param('iss', $doctorId, $fromDate, $toDate);
$unStmt->execute();
$unRes = $unStmt->get_result();

$unavail = []; // date => [[start,end], ...] أو [[NULL,NULL]] لليوم كامل
while ($u = $unRes->fetch_assoc()) {
  $d = $u['date'];
  $unavail[$d][] = [$u['start_time'], $u['end_time']];
}
$unStmt->close();

function isBlocked($date, $time, $unavail) {
  if (empty($unavail[$date])) return false;
  foreach ($unavail[$date] as [$s,$e]) {
    if ($s === null && $e === null) return true; // اليوم كامل
    $s = $s ?? '00:00:00';
    $e = $e ?? '23:59:59';
    if ($time >= $s && $time < $e) return true;
  }
  return false;
}

/* 4) بناء الشرائح */
for ($i=0; $i<$days; $i++) {
  $dObj = new DateTime("+$i day", $tz);
  $ymd  = $dObj->format('Y-m-d');
  $dow  = (int)$dObj->format('w'); // 0=Sunday

  if (empty($schedules[$dow])) continue;

  foreach ($schedules[$dow] as $sch) {
    $step = max(5, (int)$sch['step']);
    $cur  = DateTime::createFromFormat('Y-m-d H:i:s', $ymd.' '.$sch['start'], $tz);
    $end  = DateTime::createFromFormat('Y-m-d H:i:s', $ymd.' '.$sch['end'],   $tz);

    // لا نعرض الماضي من يوم اليوم
    if ($ymd === $todayYmd && $cur < $now) {
      $cur = clone $now;
      $mins = (int)$cur->format('i');
      $mod  = $mins % $step;
      if ($mod !== 0) $cur->modify('+'.($step-$mod).' minutes');
      $cur->setTime((int)$cur->format('H'), (int)$cur->format('i'), 0);
    }

    while ($cur < $end) {
      $time = $cur->format('H:i:s');
      $key  = $ymd.' '.$time;

      if (!isset($booked[$key]) && !isBlocked($ymd, $time, $unavail)) {
        $slots[] = [
          'date'  => $ymd,
          'time'  => $time,
          'label' => $ymd.' — '.$cur->format('h:i A')
        ];
      }
      $cur->modify("+$step minutes");
    }
  }
}

echo json_encode(['ok'=>true, 'slots'=>$slots], JSON_UNESCAPED_UNICODE);
