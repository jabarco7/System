<?php
// File: System/hospital/hms/admin/manage-doctor-schedule.php
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('include/config.php');

if (empty($_SESSION['id'])) {
  header('location:logout.php');
  exit;
}

/* CSRF */
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$CSRF = $_SESSION['csrf_token'];

$doctorId = isset($_GET['doctorId']) ? (int)$_GET['doctorId'] : 0;

/* أطباء */
$doctors = [];
$rs = mysqli_query($con, "SELECT id,doctorName,specilization FROM doctors ORDER BY doctorName ASC");
while ($r = mysqli_fetch_assoc($rs)) $doctors[] = $r;

/* 🔧 ترقيم الأيام (مصَحَّح) */
$days = [
  0 => 'الأحد',
  1 => 'الأثنين',
  2 => 'الثلاثاء',
  3 => 'الأربعاء',
  4 => 'الخميس',
  5 => 'الجمعة',
  6 => 'السبت',
];

function ok_time($t){ return (bool)preg_match('/^\d{2}:\d{2}$/', $t); }
function flash($m){ $_SESSION['msg'] = $m; }

/* POST */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
    flash('خطأ في التحقق الأمني (CSRF).');
    header('Location: manage-doctor-schedule.php' . ($doctorId ? '?doctorId=' . $doctorId : ''));
    exit;
  }

  $action = $_POST['action'] ?? '';

  /* إضافة/استبدال الدوام (يدعم فترتين) */
  if ($action === 'add_schedule') {
    $did   = (int)($_POST['doctor_id'] ?? 0);

    // الأيام المحددة من واجهة "الأيام" (قيمها الآن مطابقة للأسماء)
    $daysSel = isset($_POST['days']) && is_array($_POST['days']) ? array_map('intval', $_POST['days']) : [];
    if (empty($daysSel) && isset($_POST['day_of_week'])) $daysSel = [(int)$_POST['day_of_week']];

    // الفترة 1 (أساسية)
    $start1 = trim($_POST['start_time'] ?? '');
    $end1   = trim($_POST['end_time'] ?? '');
    // الفترة 2 (اختيارية)
    $start2 = trim($_POST['start_time2'] ?? '');
    $end2   = trim($_POST['end_time2'] ?? '');

    $slot    = (int)($_POST['slot_minutes'] ?? 30);
    $active  = isset($_POST['active']) ? 1 : 0;
    $replace = isset($_POST['replace']) ? 1 : 0;

    // تنظيف القيم ضمن النطاق الصحيح 0..6
    $daysSel = array_values(array_filter(array_unique($daysSel), fn($d) => $d >= 0 && $d <= 6));

    $range1_ok     = ok_time($start1) && ok_time($end1) && strtotime($start1) < strtotime($end1);
    $range2_filled = ($start2 !== '' || $end2 !== '');
    $range2_ok     = !$range2_filled || (ok_time($start2) && ok_time($end2) && strtotime($start2) < strtotime($end2));

    if ($did <= 0 || empty($daysSel) || !$range1_ok || !$range2_ok || $slot < 5 || $slot > 120) {
      flash('تحقق من المدخلات: اختر طبيبًا، يومًا واحدًا على الأقل، وفترات وقت صحيحة. (الفترة الثانية اختيارية)');
      header('Location: manage-doctor-schedule.php?doctorId=' . $did);
      exit;
    }

    $okAll = true; $errors = [];
    $del = $con->prepare("DELETE FROM doctor_schedule WHERE doctor_id=? AND day_of_week=?");
    $ins = $con->prepare("INSERT INTO doctor_schedule (doctor_id, day_of_week, start_time, end_time, slot_minutes, active) VALUES (?,?,?,?,?,?)");

    foreach ($daysSel as $dow) {
      if ($replace) {
        $del->bind_param('ii', $did, $dow);
        if (!$del->execute()) { $okAll = false; $errors[] = 'حذف يوم ' . ($days[$dow] ?? $dow) . ': ' . $del->error; }
      }
      // الفترة 1
      $ins->bind_param('iissii', $did, $dow, $start1, $end1, $slot, $active);
      if (!$ins->execute()) { $okAll = false; $errors[] = 'إدراج يوم ' . ($days[$dow] ?? $dow) . ' (١): ' . $ins->error; }

      // الفترة 2 إن وُجدت
      if ($range2_filled) {
        $ins->bind_param('iissii', $did, $dow, $start2, $end2, $slot, $active);
        if (!$ins->execute()) { $okAll = false; $errors[] = 'إدراج يوم ' . ($days[$dow] ?? $dow) . ' (٢): ' . $ins->error; }
      }
    }
    $del->close(); $ins->close();

    flash($okAll ? 'تم حفظ فترات الدوام للأيام المختارة.' : ('تم التنفيذ مع أخطاء: ' . implode(' | ', $errors)));
    header('Location: manage-doctor-schedule.php?doctorId=' . $did);
    exit;
  }

  if ($action === 'delete_schedule') {
    $did = (int)($_POST['doctor_id'] ?? 0);
    $id  = (int)($_POST['id'] ?? 0);
    if ($id > 0 && $did > 0) {
      if ($st = $con->prepare("DELETE FROM doctor_schedule WHERE id=? AND doctor_id=?")) {
        $st->bind_param('ii', $id, $did);
        if ($st->execute()) flash('تم حذف الفترة.');
        else flash('لم يتم الحذف: ' . $st->error);
        $st->close();
      } else flash('تعذر تجهيز الحذف: ' . $con->error);
    }
    header('Location: manage-doctor-schedule.php?doctorId=' . $did);
    exit;
  }

  if ($action === 'add_unavail') {
    $did   = (int)($_POST['doctor_id'] ?? 0);
    $date  = trim($_POST['date'] ?? '');
    $full  = isset($_POST['is_full_day']) ? 1 : 0;
    $us    = $full ? null : trim($_POST['u_start_time'] ?? '');
    $ue    = $full ? null : trim($_POST['u_end_time'] ?? '');
    $reason= trim($_POST['reason'] ?? '');

    $ok = ($did > 0 && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date));
    if (!$full) $ok = $ok && ok_time($us) && ok_time($ue) && strtotime($us) < strtotime($ue);

    if (!$ok) {
      flash('تحقق من مدخلات الغياب: تاريخ/وقت غير صحيح.');
    } else {
      if ($st = $con->prepare("INSERT INTO doctor_unavailable (doctor_id,date,start_time,end_time,reason,is_full_day) VALUES (?,?,?,?,?,?)")) {
        $st->bind_param('issssi', $did, $date, $us, $ue, $reason, $full);
        if ($st->execute()) flash('تم تسجيل الغياب/الإجازة.');
        else flash('تعذر الإضافة: ' . $st->error);
        $st->close();
      } else flash('تعذر تجهيز الإدراج: ' . $con->error);
    }
    header('Location: manage-doctor-schedule.php?doctorId=' . $did);
    exit;
  }

  if ($action === 'delete_unavail') {
    $did = (int)($_POST['doctor_id'] ?? 0);
    $id  = (int)($_POST['id'] ?? 0);
    if ($id > 0 && $did > 0) {
      if ($st = $con->prepare("DELETE FROM doctor_unavailable WHERE id=? AND doctor_id=?")) {
        $st->bind_param('ii', $id, $did);
        if ($st->execute()) flash('تم حذف سجل الغياب.');
        else flash('لم يتم الحذف: ' . $st->error);
        $st->close();
      } else flash('تعذر تجهيز الحذف: ' . $con->error);
    }
    header('Location: manage-doctor-schedule.php?doctorId=' . $did);
    exit;
  }
}

/* بيانات العرض */
$doc = null; $schedule = []; $unav = [];
if ($doctorId > 0) {
  if ($st = $con->prepare("SELECT id,doctorName,specilization FROM doctors WHERE id=?")) {
    $st->bind_param('i', $doctorId);
    $st->execute();
    $doc = $st->get_result()->fetch_assoc();
    $st->close();
  }
  if ($st = $con->prepare("SELECT id,day_of_week,start_time,end_time,slot_minutes,active FROM doctor_schedule WHERE doctor_id=? ORDER BY day_of_week ASC,start_time ASC")) {
    $st->bind_param('i', $doctorId);
    $st->execute();
    $schedule = $st->get_result();
    $st->close();
  }
  if ($st = $con->prepare("SELECT id,date,start_time,end_time,reason,is_full_day FROM doctor_unavailable WHERE doctor_id=? ORDER BY date DESC,start_time ASC")) {
    $st->bind_param('i', $doctorId);
    $st->execute();
    $unav = $st->get_result();
    $st->close();
  }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>إدارة دوام الأطباء</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">
  <style>
    body{font-family:'Tajawal',sans-serif;background:#f0f5f9;padding-top:40px}
    .main-content{margin-right:20px;padding:20px}
    .page-header{background:linear-gradient(90deg,#3498db,#4aa8e0);color:#fff;padding:22px 26px;border-radius:10px;margin-bottom:20px}
    .card{border:none;border-radius:12px;box-shadow:0 5px 15px rgba(0,0,0,.06)}
    .card-header{background:#fff;border-bottom:1px solid #eee;border-radius:12px 12px 0 0}
    .table th{background:#3498db;color:#fff}
    .badge-on{background:#eef7ff;color:#0d6efd;border:1px solid #cfe5ff}
    .days-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:8px}
    @media (max-width:768px){.days-grid{grid-template-columns:repeat(3,1fr)}}
    .day-pill input{display:none}
    .day-pill label{display:block;text-align:center;border:1px solid #dbe5f1;border-radius:10px;padding:10px 0;cursor:pointer;background:#fff;font-weight:600}
    .day-pill input:checked+label{background:#eef7ff;border-color:#8fc2ff;color:#0d6efd;box-shadow:0 0 0 2px rgba(13,110,253,.15) inset}
    .range-title{font-weight:700;font-size:.95rem;color:#0d6efd}
    .muted{color:#6c7a92}
  </style>
</head>
<body>

  <?php include('include/sidebar.php'); ?>
  <?php include('include/header.php'); ?>

  <div class="main-content">
    <div class="page-header d-flex justify-content-between align-items-center">
      <h4 class="m-0"><i class="fa-regular fa-calendar me-2"></i>إدارة دوام الأطباء</h4>
      <form method="get" class="d-flex gap-2 align-items-center">
        <select name="doctorId" class="form-select">
          <option value="">— اختر الطبيب —</option>
          <?php foreach ($doctors as $d): ?>
            <option value="<?= (int)$d['id'] ?>" <?= $doctorId == (int)$d['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($d['doctorName'] . ' — ' . $d['specilization']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <button class="btn btn-light"><i class="fa fa-arrow-rotate-left"></i> انتقال</button>
      </form>
    </div>

    <?php if (!empty($_SESSION['msg'])): ?>
      <div class="alert alert-info"><?= htmlspecialchars($_SESSION['msg']); ?></div>
      <?php unset($_SESSION['msg']); ?>
    <?php endif; ?>

    <?php if ($doctorId > 0 && $doc): ?>
      <div class="mb-3">
        <span class="badge badge-on px-3 py-2"><i class="fa-solid fa-user-doctor me-1"></i> <?= htmlspecialchars($doc['doctorName']) ?></span>
        <span class="badge badge-on px-3 py-2"><i class="fa-solid fa-stethoscope me-1"></i> <?= htmlspecialchars($doc['specilization']) ?></span>
      </div>

      <div class="row g-3">
        <div class="col-lg-6">
          <div class="card">
            <div class="card-header"><strong>إضافة فترة دوام</strong> <small class="text-muted">— اختر يومًا أو أكثر</small></div>
            <div class="card-body">
              <form method="post" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($CSRF) ?>">
                <input type="hidden" name="action" value="add_schedule">
                <input type="hidden" name="doctor_id" value="<?= (int)$doctorId ?>">

                <div class="col-12">
                  <label class="form-label">الأيام</label>
                  <div class="days-grid">
                    <?php foreach ($days as $k => $v): $id = 'd_' . $k; ?>
                      <div class="day-pill">
                        <input type="checkbox" id="<?= $id ?>" name="days[]" value="<?= $k ?>">
                        <label for="<?= $id ?>"><?= $v ?></label>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </div>

                <!-- الفترة (1) الأساسية -->
                <div class="col-12"><div class="range-title">الفترة (١)</div></div>
                <div class="col-4">
                  <label class="form-label">من</label>
                  <input type="time" name="start_time" class="form-control" required>
                </div>
                <div class="col-4">
                  <label class="form-label">إلى</label>
                  <input type="time" name="end_time" class="form-control" required>
                </div>
                <div class="col-4">
                  <label class="form-label">مدة الشريحة (دقيقة)</label>
                  <input type="number" name="slot_minutes" class="form-control" value="30" min="5" max="120" required>
                </div>

                <!-- الفترة (2) الاختيارية -->
                <div class="col-12"><div class="range-title">الفترة (٢) <span class="muted">(اختياري)</span></div></div>
                <div class="col-6">
                  <label class="form-label">من</label>
                  <input type="time" name="start_time2" class="form-control" placeholder="—">
                </div>
                <div class="col-6">
                  <label class="form-label">إلى</label>
                  <input type="time" name="end_time2" class="form-control" placeholder="—">
                </div>

                <div class="col-6 d-flex align-items-center gap-3">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="active" id="active" checked>
                    <label class="form-check-label" for="active">مفعل</label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="replace" id="replace">
                    <label class="form-check-label" for="replace">استبدال الموجود لهذه الأيام</label>
                  </div>
                </div>

                <div class="col-12 d-grid">
                  <button class="btn btn-primary"><i class="fa fa-plus me-1"></i> حفظ الدوام</button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <div class="col-lg-6">
          <div class="card">
            <div class="card-header"><strong>تسجيل غياب/إجازة</strong></div>
            <div class="card-body">
              <form method="post" class="row g-2" id="unavailForm">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($CSRF) ?>">
                <input type="hidden" name="action" value="add_unavail">
                <input type="hidden" name="doctor_id" value="<?= (int)$doctorId ?>">
                <div class="col-6">
                  <label class="form-label">التاريخ</label>
                  <input type="date" name="date" class="form-control" required>
                </div>
                <div class="col-6 d-flex align-items-end">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_full_day" id="is_full_day" checked>
                    <label class="form-check-label" for="is_full_day">يوم كامل</label>
                  </div>
                </div>
                <div class="col-6 time-range">
                  <label class="form-label">من</label>
                  <input type="time" name="u_start_time" class="form-control" disabled>
                </div>
                <div class="col-6 time-range">
                  <label class="form-label">إلى</label>
                  <input type="time" name="u_end_time" class="form-control" disabled>
                </div>
                <div class="col-12">
                  <label class="form-label">السبب (اختياري)</label>
                  <input type="text" name="reason" class="form-control" placeholder="إجازة / مؤتمر / طارئ ...">
                </div>
                <div class="col-12 d-grid">
                  <button class="btn btn-secondary"><i class="fa fa-plus me-1"></i> إضافة</button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <div class="col-12">
          <div class="card">
            <div class="card-header"><strong>فترات الدوام</strong></div>
            <div class="card-body table-responsive">
              <table class="table table-bordered align-middle">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>اليوم</th>
                    <th>من</th>
                    <th>إلى</th>
                    <th>مدة الشريحة</th>
                    <th>الحالة</th>
                    <th class="text-center" style="width:120px">إجراء</th>
                  </tr>
                </thead>
                <tbody>
                  <?php $i=1;
                  if ($schedule instanceof mysqli_result && $schedule->num_rows > 0):
                    while ($row = $schedule->fetch_assoc()): ?>
                      <tr>
                        <td><?= $i++ ?></td>
                        <td><?= htmlspecialchars($days[(int)$row['day_of_week']] ?? $row['day_of_week']) ?></td>
                        <td><?= htmlspecialchars(substr($row['start_time'],0,5)) ?></td>
                        <td><?= htmlspecialchars(substr($row['end_time'],0,5)) ?></td>
                        <td><?= (int)$row['slot_minutes'] ?> دقيقة</td>
                        <td><?= ((int)$row['active'] === 1 ? '<span class="badge bg-success">مفعل</span>' : '<span class="badge bg-secondary">متوقف</span>') ?></td>
                        <td class="text-center">
                          <form method="post" onsubmit="return confirm('حذف هذه الفترة؟');" style="display:inline">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($CSRF) ?>">
                            <input type="hidden" name="action" value="delete_schedule">
                            <input type="hidden" name="doctor_id" value="<?= (int)$doctorId ?>">
                            <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger"><i class="fa fa-trash"></i></button>
                          </form>
                        </td>
                      </tr>
                    <?php endwhile;
                  else: ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">لا توجد فترات دوام مضافة.</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="col-12">
          <div class="card">
            <div class="card-header"><strong>سجل الغياب/الإجازات</strong></div>
            <div class="card-body table-responsive">
              <table class="table table-bordered align-middle">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>التاريخ</th>
                    <th>المدة</th>
                    <th>السبب</th>
                    <th class="text-center" style="width:120px">إجراء</th>
                  </tr>
                </thead>
                <tbody>
                  <?php $j=1;
                  if ($unav instanceof mysqli_result && $unav->num_rows > 0):
                    while ($row = $unav->fetch_assoc()): ?>
                      <tr>
                        <td><?= $j++ ?></td>
                        <td><?= htmlspecialchars($row['date']) ?></td>
                        <td><?= ((int)$row['is_full_day'] === 1) ? 'يوم كامل' : (htmlspecialchars(substr($row['start_time'],0,5)) . ' - ' . htmlspecialchars(substr($row['end_time'],0,5))) ?></td>
                        <td><?= htmlspecialchars($row['reason'] ?: '—') ?></td>
                        <td class="text-center">
                          <form method="post" onsubmit="return confirm('حذف هذا السجل؟');" style="display:inline">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($CSRF) ?>">
                            <input type="hidden" name="action" value="delete_unavail">
                            <input type="hidden" name="doctor_id" value="<?= (int)$doctorId ?>">
                            <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger"><i class="fa fa-trash"></i></button>
                          </form>
                        </td>
                      </tr>
                    <?php endwhile;
                  else: ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">لا توجد سجلات.</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

      </div>
    <?php else: ?>
      <div class="alert alert-warning">اختر طبيباً لبدء إدارة الدوام.</div>
    <?php endif; ?>
  </div>

  <script>
    /* ✅ الآن أيام العمل = 0..4 (الأحد–الخميس) وعطلة الأسبوع = 5,6 (الجمعة/السبت) */
    function markWeekdays(){
      clearDays(false);
      for(let i=0;i<=4;i++){ const e=document.getElementById('d_'+i); if(e) e.checked=true; }
    }
    function markWeekends(){
      clearDays(false);
      ['d_5','d_6'].forEach(id=>{ const e=document.getElementById(id); if(e) e.checked=true; });
    }
    function clearDays(focus=true){
      for(let i=0;i<=6;i++){ const e=document.getElementById('d_'+i); if(e) e.checked=false; }
      if(focus){ const e=document.getElementById('d_0'); if(e) e.focus(); }
    }

    const fullChk = document.getElementById('is_full_day');
    const timeInputs = document.querySelectorAll('#unavailForm .time-range input');
    function toggleTimes(){
      const dis = fullChk && fullChk.checked;
      timeInputs.forEach(i=>{ i.disabled = dis; if(dis) i.value=''; });
    }
    if(fullChk){ fullChk.addEventListener('change', toggleTimes); toggleTimes(); }
  </script>
</body>
</html>
