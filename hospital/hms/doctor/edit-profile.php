<?php
session_start();
error_reporting(0);
include('include/config.php');

/* تحقق الجلسة */
if (empty($_SESSION['id']) || empty($_SESSION['dlogin'])) {
    header('location:logout.php'); exit;
}

$doctorId        = (int)$_SESSION['id'];
$doctorEmailSess = $_SESSION['dlogin'];

/* جلب بيانات الطبيب */
$doc = null;
if ($st = $con->prepare("SELECT id, doctorName, specilization, address, docFees, contactno, docEmail, creationDate, updationDate FROM doctors WHERE docEmail=? LIMIT 1")) {
    $st->bind_param('s', $doctorEmailSess);
    $st->execute(); $res = $st->get_result();
    $doc = $res ? $res->fetch_assoc() : null;
    $st->close();
}
if (!$doc && $st = $con->prepare("SELECT id, doctorName, specilization, address, docFees, contactno, docEmail, creationDate, updationDate FROM doctors WHERE id=? LIMIT 1")) {
    $st->bind_param('i', $doctorId);
    $st->execute(); $res = $st->get_result();
    $doc = $res ? $res->fetch_assoc() : null;
    $st->close();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <title>الطبيب | ملفي الشخصي (عرض فقط)</title>

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
  <link rel="stylesheet" href="assets/css/styles.css">
  <link rel="stylesheet" href="assets/css/plugins.css">
  <link rel="stylesheet" href="assets/css/hms-unified.css?v=1.0">

  <style>
    /* ===== إصلاح المسافة العلوية ===== */
    html, body { margin:0; padding:0 !important; }
    #app { margin-top:0 !important; padding-top:0 !important; }
    header, .hms-topbar { margin-top:0 !important; }

    /* اتجاه عام */
    html, body{direction: rtl; text-align: right;}
    input, select, textarea, .form-control, .form-select{direction: rtl; text-align: right;}

    /* تخطيط مع سايدبار يمين */
    .app-sidebar, .sidebar, .app-aside { right: 0 !important; left: auto !important; }
    .app-content, .main-content { margin-right: 220px; margin-left: 0; }
    @media (max-width: 992px){ .app-content, .main-content { margin-right: 0; } }

    body{font-family:'Tajawal',sans-serif;background:#f0f5f9}
    .page-head{
      background:linear-gradient(90deg,#3498db,#4aa8e0);
      color:#fff;border-radius:12px;padding:16px 18px;margin:20px 20px 12px
    }
    .page-head h1{margin:0;font-size:1.25rem}
    .container-fullw{
      border-radius:12px;background:#fff;box-shadow:0 8px 20px rgba(0,0,0,.06);
      padding:18px;margin:0 20px 24px
    }
    .form-control,.form-select{border-radius:10px}
    .section-title{font-weight:700;margin-bottom:10px}
    .muted{color:#6c757d}
    .badge-soft{
      display:inline-block;border-radius:20px;background:#eef7ff;color:#0d6efd;
      padding:2px 10px;border:1px solid #cfe5ff
    }
    .profile-meta{background:#fbfdff;border:1px dashed #e5eef6;border-radius:12px;padding:12px}
    .profile-meta div{margin-bottom:6px}

    /* مظهر الحقول المعطلة */
    fieldset[disabled] .form-control,
    fieldset[disabled] .form-select{background:#f7f9fc; color:#555; cursor:not-allowed;}
  </style>
</head>
<body>
<div id="app">
  <?php include('include/header.php'); ?>
  <?php include('include/sidebar.php'); ?>

  <div class="app-content">
    <div class="page-head">
      <h1><i class="fa fa-user-md"></i> ملفي الشخصي</h1>
    </div>

    <div class="container-fluid container-fullw">
      <div class="row g-3">
        <div class="col-lg-8">
          <div class="section-title">بياناتي</div>

          <form>
            <fieldset disabled>
              <div class="mb-3">
                <label class="form-label">التخصص</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($doc['specilization'] ?? ''); ?>">
              </div>

              <div class="mb-3">
                <label class="form-label">الاسم</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($doc['doctorName'] ?? ''); ?>">
              </div>

              <div class="mb-3">
                <label class="form-label">العنوان</label>
                <textarea class="form-control" rows="3"><?php echo htmlspecialchars($doc['address'] ?? ''); ?></textarea>
              </div>

              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">رسوم الاستشارة (ر.س)</label>
                  <input type="text" class="form-control" value="<?php echo htmlspecialchars($doc['docFees'] ?? ''); ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label">رقم الاتصال</label>
                  <input type="text" class="form-control" value="<?php echo htmlspecialchars($doc['contactno'] ?? ''); ?>">
                </div>
              </div>

              <div class="mt-3">
                <label class="form-label">البريد الإلكتروني</label>
                <input type="email" class="form-control" value="<?php echo htmlspecialchars($doc['docEmail'] ?? ''); ?>">
              </div>
            </fieldset>
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

  </div><!-- /.app-content -->
</div><!-- /#app -->

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
