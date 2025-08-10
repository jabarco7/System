<?php
// System/hospital/hms/api/book-appointment.php
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../include/config.php';

$userId   = $_SESSION['id'] ?? 0;
$doctorId = isset($_POST['doctorId']) ? (int)$_POST['doctorId'] : 0;
$date     = $_POST['date'] ?? '';
$time     = $_POST['time'] ?? '';

if (!$userId) { echo json_encode(['ok'=>false,'msg'=>'الرجاء تسجيل الدخول']); exit; }
if ($doctorId<=0 || !$date || !$time) { echo json_encode(['ok'=>false,'msg'=>'بيانات غير مكتملة']); exit; }
if (!strtotime("$date $time")) { echo json_encode(['ok'=>false,'msg'=>'صيغة تاريخ/وقت غير صالحة']); exit; }

$dow = (int)date('w', strtotime($date)); // 0=Sunday

// ضمن الدوام؟
$sched = $con->prepare("
  SELECT start_time, end_time, slot_minutes
  FROM doctor_schedule
  WHERE doctorId=? AND day_of_week=?
");
$sched->bind_param('ii', $doctorId, $dow);
$sched->execute();
$res = $sched->get_result();
$okInSchedule = false;
while ($row = $res->fetch_assoc()) {
  if ($time >= $row['start_time'] && $time < $row['end_time']) { $okInSchedule = true; break; }
}
$sched->close();

if (!$okInSchedule) {
  echo json_encode(['ok'=>false,'msg'=>'الوقت خارج دوام الطبيب']);
  exit;
}

// غياب؟
$un = $con->prepare("
  SELECT 1
  FROM doctor_unavailable
  WHERE doctorId=? AND date=?
    AND ( (start_time IS NULL AND end_time IS NULL)
          OR (COALESCE(start_time,'00:00:00') <= ? AND COALESCE(end_time,'23:59:59') > ?) )
  LIMIT 1
");
$un->bind_param('isss', $doctorId, $date, $time, $time);
$un->execute();
$un->store_result();
if ($un->num_rows > 0) { echo json_encode(['ok'=>false,'msg'=>'الوقت محجوب (إجازة)']); $un->close(); exit; }
$un->close();

// محجوز؟
$chk = $con->prepare("
  SELECT 1 FROM appointment
  WHERE doctorId=? AND appointmentDate=? AND appointmentTime=?
    AND userStatus=1 AND doctorStatus=1
  LIMIT 1
");
$chk->bind_param('iss', $doctorId, $date, $time);
$chk->execute();
$chk->store_result();
if ($chk->num_rows > 0) { echo json_encode(['ok'=>false,'msg'=>'تم حجز هذا الوقت للتو. اختر وقتًا آخر.']); $chk->close(); exit; }
$chk->close();

// اجلب التخصص والرسوم
$docQ = $con->prepare("SELECT specilization, docFees FROM doctors WHERE id=? LIMIT 1");
$docQ->bind_param('i', $doctorId);
$docQ->execute();
$docRes = $docQ->get_result();
$spec = ''; $fees = 0;
if ($d = $docRes->fetch_assoc()) { $spec = $d['specilization']; $fees = (int)$d['docFees']; }
$docQ->close();

// أدخل الموعد
$userStatus = 1; $docStatus = 1;
$ins = $con->prepare("
  INSERT INTO appointment
    (doctorSpecialization, doctorId, userId, consultancyFees, appointmentDate, appointmentTime, userStatus, doctorStatus)
  VALUES (?,?,?,?,?,?,?,?)
");
$ins->bind_param('siiissii', $spec, $doctorId, $userId, $fees, $date, $time, $userStatus, $docStatus);

if ($ins->execute()) {
  echo json_encode(['ok'=>true,'msg'=>'تم حجز موعدك بنجاح']);
} else {
  echo json_encode(['ok'=>false,'msg'=>'تعذر تثبيت الحجز، جرّب وقتًا آخر.']);
}
$ins->close();
