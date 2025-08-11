<?php
session_start();
error_reporting(0);
include('include/config.php');

/* تحقق جلسة الطبيب (نستخدم كلًا من id و dlogin) */
if (empty($_SESSION['id']) || empty($_SESSION['dlogin'])) {
    header('location:logout.php');
    exit;
}

$doctorId = (int)$_SESSION['id'];
$doctorEmailSess = $_SESSION['dlogin'];

/* CSRF */
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$CSRF = $_SESSION['csrf'];

/* حفظ التعديلات */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['submit'])) {
    if (!hash_equals($CSRF, $_POST['csrf'] ?? '')) {
        $_SESSION['msg'] = 'فشل التحقق الأمني. أعد المحاولة.';
        header('Location: doctor-profile.php'); exit;
    }

    $docspecialization = trim($_POST['Doctorspecialization'] ?? '');
    $docname           = trim($_POST['docname'] ?? '');
    $docaddress        = trim($_POST['clinicaddress'] ?? '');
    $docfees           = trim($_POST['docfees'] ?? '');
    $doccontactno      = trim($_POST['doccontact'] ?? '');

    /* تحسينات بسيطة للتحقق */
    if ($docspecialization!=='' && $docname!=='') {
        if ($stmt = $con->prepare("UPDATE doctors SET specilization=?, doctorName=?, address=?, docFees=?, contactno=? WHERE id=?")) {
            // ملاحظة: docFees حقل نصي بقاعدة البيانات، سنرسله كنص
            $contactAsInt = (is_numeric($doccontactno) ? (int)$doccontactno : null);
            $stmt->bind_param('ssssii', $docspecialization, $docname, $docaddress, $docfees, $contactAsInt, $doctorId);
            $ok = $stmt->execute();
            $stmt->close();
            $_SESSION['msg'] = $ok ? 'تم تحديث تفاصيل الطبيب بنجاح ✅' : 'تعذر تحديث البيانات حالياً.';
        } else {
            $_SESSION['msg'] = 'خطأ داخلي أثناء التحديث.';
        }
    } else {
        $_SESSION['msg'] = 'يرجى تعبئة الحقول المطلوبة.';
    }
    header('Location: doctor-profile.php'); exit;
}

/* جلب بيانات الطبيب الحالية */
$doc = null;
if ($stmt = $con->prepare("SELECT * FROM doctors WHERE docEmail=? LIMIT 1")) {
    $stmt->bind_param('s', $doctorEmailSess);
    $stmt->execute();
    $res = $stmt->get_result();
    $doc = $res ? $res->fetch_assoc() : null;
    $stmt->close();
}

/* جلب قائمة التخصصات */
$specs = [];
$q = mysqli_query($con, "SELECT specilization FROM doctorspecilization ORDER BY specilization ASC");
while($r = mysqli_fetch_assoc($q)) { $specs[] = $r['specilization']; }
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="utf-8">
    <title>طبيب | ملف تفاصيل الطبيب</title>

    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="vendor/fontawesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="vendor/themify-icons/themify-icons.min.css">
    <link href="vendor/animate.css/animate.min.css" rel="stylesheet">
    <link href="vendor/perfect-scrollbar/perfect-scrollbar.min.css" rel="stylesheet">
    <link href="vendor/switchery/switchery.min.css" rel="stylesheet">
    <link href="vendor/bootstrap-touchspin/jquery.bootstrap-touchspin.min.css" rel="stylesheet">
    <link href="vendor/select2/select2.min.css" rel="stylesheet">
    <link href="vendor/bootstrap-datepicker/bootstrap-datepicker3.standalone.min.css" rel="stylesheet">
    <link href="vendor/bootstrap-timepicker/bootstrap-timepicker.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/plugins.css">
    <link rel="stylesheet" href="assets/css/themes/theme-1.css" id="skin_color" />
	<link rel="stylesheet" href="assets/css/hms-unified.css?v=1.1">


    <style>
		
  /* اتجاه عام */
  html, body{direction: rtl; text-align: right;}

  /* اجعل كل الحقول يمين */
  input, select, textarea, .form-control, .form-select{direction: rtl; text-align: right;}

  /* قلب الشريط الجانبي لليمين — غطّي أشهر أسماء الكلاسات في القالب */
  .app-sidebar, .sidebar, .app-aside {
    right: 0 !important;
    left: auto !important;
  }

  /* افسح مكان للشريط الجانبي من اليمين بدلاً من اليسار */
  .app-content, .main-content {
    margin-right: 220px;   /* اضبطها على عرض شريطك الجانبي */
    margin-left: 0;
  }

  /* في الشاشات الصغيرة: لا هامش */
  @media (max-width: 992px){
    .app-content, .main-content { margin-right: 0; }
  }

  /* عناصر شائعة قد تكون مثبّتة لليسار في القالب القديم */
  .sidebar-container { right:0 !important; left:auto !important; }
  .navigation, .nav, .sidebar-menu { text-align: right; }
  .breadcrumb { direction: rtl; }


        body{font-family:'Tajawal',sans-serif;background:#f0f5f9}
        .page-head{background:linear-gradient(90deg,#3498db,#4aa8e0);color:#fff;border-radius:12px;padding:16px 18px;margin:20px 20px 12px}
        .page-head h1{margin:0;font-size:1.25rem}
        .container-fullw{border-radius:12px;background:#fff;box-shadow:0 8px 20px rgba(0,0,0,.06);padding:18px;margin:0 20px 24px}
        .form-control,.form-select{border-radius:10px}
        .section-title{font-weight:700;margin-bottom:10px}
        .muted{color:#6c757d}
        .alert-compact{border-radius:10px;padding:10px 12px;margin:0 20px 12px}
        .badge-soft{display:inline-block;border-radius:20px;background:#eef7ff;color:#0d6efd;padding:2px 10px;border:1px solid #cfe5ff}
        .btn-primary{border-radius:10px}
        .profile-meta{background:#fbfdff;border:1px dashed #e5eef6;border-radius:12px;padding:12px}
        .profile-meta div{margin-bottom:6px}
    </style>
</head>
<body>
<div id="app">
    <?php include('include/sidebar.php'); ?>
    <div class="app-content">
        <?php include('include/header.php'); ?>

        <div class="page-head">
            <h1><i class="fa fa-user-md"></i> الطبيب | ملف تفاصيل الطبيب</h1>
        </div>

        <?php if (!empty($_SESSION['msg'])): ?>
            <div class="alert alert-info alert-compact">
                <i class="fa fa-info-circle"></i> <?php echo htmlentities($_SESSION['msg']); ?>
            </div>
            <?php $_SESSION['msg']=""; ?>
        <?php endif; ?>

        <div class="container-fluid container-fullw">
            <div class="row g-3">
                <div class="col-lg-8">
                    <div class="section-title">تعديل تفاصيل الطبيب</div>
                    <form method="post" autocomplete="off">
                        <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($CSRF); ?>">

                        <div class="mb-3">
                            <label class="form-label">تخصص الطبيب</label>
                            <select name="Doctorspecialization" class="form-select" required>
                                <?php
                                  $curSpec = $doc['specilization'] ?? '';
                                  if ($curSpec!=='') echo '<option value="'.htmlspecialchars($curSpec).'" selected>'.htmlspecialchars($curSpec).'</option>';
                                  foreach($specs as $sp){
                                      if ($sp === $curSpec) continue;
                                      echo '<option value="'.htmlspecialchars($sp).'">'.htmlspecialchars($sp).'</option>';
                                  }
                                ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">اسم الطبيب</label>
                            <input type="text" name="docname" class="form-control" value="<?php echo htmlspecialchars($doc['doctorName'] ?? ''); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">عنوان عيادة الطبيب</label>
                            <textarea name="clinicaddress" class="form-control" rows="3" placeholder="العنوان بالتفصيل..."><?php echo htmlspecialchars($doc['address'] ?? ''); ?></textarea>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">رسوم الاستشارة (ر.س)</label>
                                <input type="text" name="docfees" class="form-control" value="<?php echo htmlspecialchars($doc['docFees'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">رقم الاتصال</label>
                                <input type="text" name="doccontact" class="form-control" value="<?php echo htmlspecialchars($doc['contactno'] ?? ''); ?>" required>
                            </div>
                        </div>

                        <div class="mt-3">
                            <label class="form-label">البريد الإلكتروني</label>
                            <input type="email" class="form-control" value="<?php echo htmlspecialchars($doc['docEmail'] ?? ''); ?>" readonly>
                            <div class="form-text muted">لا يمكن تعديل البريد من هذه الصفحة.</div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" name="submit" class="btn btn-primary">
                                <i class="fa fa-save me-1"></i> حفظ التعديلات
                            </button>
                        </div>
                    </form>
                </div>

                <div class="col-lg-4">
                    <div class="section-title">بيانات الملف</div>
                    <div class="profile-meta">
                        <div><span class="badge-soft"><i class="fa fa-user-md"></i> <?php echo htmlspecialchars($doc['doctorName'] ?? ''); ?></span></div>
                        <div class="muted">تاريخ إنشاء الملف: <strong><?php echo htmlspecialchars($doc['creationDate'] ?? '—'); ?></strong></div>
                        <div class="muted">آخر تحديث: <strong><?php echo htmlspecialchars($doc['updationDate'] ?? '—'); ?></strong></div>
                        <div class="muted">التخصص الحالي: <strong><?php echo htmlspecialchars($doc['specilization'] ?? '—'); ?></strong></div>
                    </div>
                </div>
            </div>
        </div>

        <?php include('include/footer.php'); ?>
        <?php include('include/setting.php'); ?>
    </div>
</div>

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
jQuery(function(){ if(window.Main) Main.init(); if(window.FormElements) FormElements.init(); });
</script>
</body>
</html>
