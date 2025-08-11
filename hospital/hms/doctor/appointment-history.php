<?php
// doctor/appointment-history.php
session_start();
error_reporting(0);
include('include/config.php');

// التحقق من تسجيل الدخول
if (!isset($_SESSION['id']) || strlen($_SESSION['id']) == 0) {
    header('location:logout.php');
    exit();
}

/* تحديد هوية الطبيب */
$doctorId = 0;
$doctor   = null;

$sessionId    = (int)($_SESSION['id'] ?? 0);
$sessionEmail = trim($_SESSION['dlogin'] ?? '');

if ($sessionId > 0) {
    if ($st = $con->prepare("SELECT id, doctorName, specilization FROM doctors WHERE id=? LIMIT 1")) {
        $st->bind_param("i", $sessionId);
        $st->execute();
        $res = $st->get_result();
        $doctor = $res ? $res->fetch_assoc() : null;
        $st->close();
    }
}
if (!$doctor && $sessionEmail !== '') {
    if ($st = $con->prepare("SELECT id, doctorName, specilization FROM doctors WHERE docEmail=? LIMIT 1")) {
        $st->bind_param("s", $sessionEmail);
        $st->execute();
        $res = $st->get_result();
        $doctor = $res ? $res->fetch_assoc() : null;
        $st->close();
    }
}
if ($doctor) { $doctorId = (int)$doctor['id']; }
if ($doctorId <= 0) { header('location:logout.php'); exit; }

$doctorName     = $doctor['doctorName'];
$specialization = $doctor['specilization'];

/* إلغاء موعد (مع التحقق من المِلْكية) */
if (isset($_GET['cancel']) && isset($_GET['id'])) {
    $aid = (int)$_GET['id'];
    if ($aid > 0) {
        if ($st = $con->prepare("UPDATE appointment SET doctorStatus=0 WHERE id=? AND doctorId=? AND doctorStatus=1")) {
            $st->bind_param("ii", $aid, $doctorId);
            $st->execute();
            $_SESSION['msg'] = ($st->affected_rows > 0) ? "تم إلغاء الموعد بنجاح" : "لا يمكن إلغاء هذا الموعد.";
            $_SESSION['msg_type'] = ($st->affected_rows > 0) ? "success" : "danger";
            $st->close();
        } else {
            $_SESSION['msg'] = "تعذّر تنفيذ طلب الإلغاء حالياً.";
            $_SESSION['msg_type'] = "danger";
        }
    }
    header('Location: '.$_SERVER['PHP_SELF']);
    exit();
}

/* جلب المواعيد */
$appts = [];
if ($st = $con->prepare("
    SELECT 
        a.id,
        u.fullName        AS patientName,
        a.doctorSpecialization,
        a.consultancyFees,
        a.appointmentDate,   -- YYYY-MM-DD
        a.appointmentTime,   -- HH:MM[:SS]
        a.postingDate,
        a.userStatus,
        a.doctorStatus
    FROM appointment a
    JOIN users u ON u.id = a.userId
    WHERE a.doctorId = ?
    ORDER BY a.appointmentDate DESC, STR_TO_DATE(a.appointmentTime, '%H:%i') DESC, a.id DESC
")) {
    $st->bind_param("i", $doctorId);
    $st->execute();
    $res = $st->get_result();
    while ($row = $res->fetch_assoc()) { $appts[] = $row; }
    $st->close();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>الطبيب | سجل المواعيد</title>
  <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="assets/css/hms-unified.css?v=1.0">


</head>
<body>

  <!-- نفس الترتيب: أولاً الهيدر، ثم السايدبار، ثم .main-content -->
  <?php include('include/header.php'); ?>
  <?php include('include/sidebar.php'); ?>

  <div class="main-content">
    <div class="page-header">
      <h1><i class="fa-regular fa-calendar-check me-2"></i>سجل المواعيد</h1>
      <p class="mb-0">جميع مواعيدك مرتبة من الأحدث للأقدم</p>
    </div>

    <!-- فلاش -->
    <?php if(!empty($_SESSION['msg'])): ?>
      <div class="alert <?php echo (($_SESSION['msg_type']??'')==='danger')?'alert-danger':'alert-success'; ?>">
        <i class="fa-solid <?php echo (($_SESSION['msg_type']??'')==='danger')?'fa-circle-exclamation':'fa-circle-check'; ?>"></i>
        <?php echo htmlspecialchars($_SESSION['msg']); ?>
      </div>
      <?php $_SESSION['msg']=''; $_SESSION['msg_type']=''; ?>
    <?php endif; ?>

    <!-- هيدر داخلي (اسم الطبيب/التخصص) -->
    <div class="header-box">
      <h2 class="header-title"><i class="fa-solid fa-list"></i> القائمة</h2>
      <div class="user-info">
        <div class="user-avatar">د</div>
        <div>
          <div><strong>د. <?php echo htmlspecialchars($doctorName); ?></strong></div>
          <div class="muted"><?php echo htmlspecialchars($specialization); ?></div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <span class="muted"><i class="fa-solid fa-filter me-2"></i>فلاتر</span>
        <div class="d-flex gap-2 flex-wrap">
          <select id="statusFilter" class="form-select" style="min-width:160px">
            <option value="all">كل الحالات</option>
            <option value="active">نشطة</option>
            <option value="cancelled">ملغية</option>
            <option value="pending">قيد الانتظار</option>
          </select>
          <input type="date" id="dateFilter" class="form-control" />
        </div>
      </div>

      <?php if (count($appts)): ?>
        <div class="table-container">
          <table class="table" id="apptTable">
            <thead>
              <tr>
                <th>#</th>
                <th>اسم المريض</th>
                <th>التخصص</th>
                <th>الرسوم</th>
                <th>التاريخ/الوقت</th>
                <th>أنشئ</th>
                <th>الحالة</th>
                <th>إجراء</th>
              </tr>
            </thead>
            <tbody>
              <?php
                $i=1;
                foreach($appts as $row):
                  $isActive   = ((int)$row['userStatus']===1 && (int)$row['doctorStatus']===1);
                  $isUserCan  = ((int)$row['userStatus']===0);
                  $isDocCan   = ((int)$row['doctorStatus']===0);

                  if ($isActive)      { $stTxt='نشط';            $stCls='b-ok';   $flt='active'; }
                  elseif ($isUserCan) { $stTxt='ملغي من المريض'; $stCls='b-bad';  $flt='cancelled'; }
                  elseif ($isDocCan)  { $stTxt='ملغي منك';       $stCls='b-bad';  $flt='cancelled'; }
                  else                { $stTxt='قيد الانتظار';   $stCls='b-warn'; $flt='pending'; }

                  $tm   = substr($row['appointmentTime'],0,5);
                  $date = $row['appointmentDate']; // YYYY-MM-DD
              ?>
              <tr data-status="<?php echo $flt; ?>" data-date="<?php echo htmlspecialchars($date); ?>">
                <td><?php echo $i++; ?>.</td>
                <td><?php echo htmlspecialchars($row['patientName']); ?></td>
                <td><?php echo htmlspecialchars($row['doctorSpecialization']); ?></td>
                <td><?php echo htmlspecialchars($row['consultancyFees']); ?> ر.س</td>
                <td><?php echo htmlspecialchars($date.' - '.$tm); ?></td>
                <td><?php echo htmlspecialchars($row['postingDate']); ?></td>
                <td><span class="badge <?php echo $stCls; ?>"><?php echo $stTxt; ?></span></td>
                <td>
                  <?php if ($isActive): ?>
                    <a class="btn btn-cancel"
                       href="<?php echo $_SERVER['PHP_SELF'].'?cancel=1&id='.(int)$row['id']; ?>"
                       onclick="return confirm('هل أنت متأكد من إلغاء هذا الموعد؟');">
                      <i class="fa-solid fa-xmark"></i> إلغاء
                    </a>
                  <?php else: ?>
                    <span class="muted">—</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="p-4 text-center muted">
          <i class="fa-regular fa-calendar-xmark" style="font-size:38px;"></i>
          <div class="mt-2">لا توجد مواعيد مسجلة حتى الآن.</div>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <script>
    // فلترة الحالة
    document.getElementById('statusFilter').addEventListener('change', function(){
      const v = this.value;
      document.querySelectorAll('#apptTable tbody tr').forEach(tr=>{
        const ok = (v==='all') || (tr.dataset.status===v);
        tr.style.display = ok ? '' : 'none';
      });
    });

    // فلترة التاريخ
    document.getElementById('dateFilter').addEventListener('change', function(){
      const d = this.value;
      document.querySelectorAll('#apptTable tbody tr').forEach(tr=>{
        const ok = !d || (tr.dataset.date===d);
        tr.style.display = ok ? '' : 'none';
      });
    });
  </script>
</body>
</html>
