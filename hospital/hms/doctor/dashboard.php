<?php
session_start();
error_reporting(0);
include('include/config.php');

if (!isset($_SESSION['id']) || strlen($_SESSION['id']) == 0) {
  header('location:logout.php');
  exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>الطبيب | لوحة التحكم</title>

  <!-- ملفاتك الحالية (اتركها كما هي إن كنت تحتاجها) -->
  <link rel="stylesheet" href="vendor/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="vendor/fontawesome/css/font-awesome.min.css">
  <link rel="stylesheet" href="vendor/themify-icons/themify-icons.min.css">
  <link href="vendor/animate.css/animate.min.css" rel="stylesheet" media="screen">
  <link href="vendor/perfect-scrollbar/perfect-scrollbar.min.css" rel="stylesheet" media="screen">
  <link href="vendor/switchery/switchery.min.css" rel="stylesheet" media="screen">
  <link href="vendor/bootstrap-touchspin/jquery.bootstrap-touchspin.min.css" rel="stylesheet" media="screen">
  <link href="vendor/select2/select2.min.css" rel="stylesheet" media="screen">
  <link href="vendor/bootstrap-datepicker/bootstrap-datepicker3.standalone.min.css" rel="stylesheet" media="screen">
  <link href="vendor/bootstrap-timepicker/bootstrap-timepicker.min.css" rel="stylesheet" media="screen">
  <link rel="stylesheet" href="assets/css/styles.css">
  <link rel="stylesheet" href="assets/css/plugins.css">
  <link rel="stylesheet" href="assets/css/themes/theme-1.css" id="skin_color" />
  <link rel="stylesheet" href="assets/css/hms-unified.css?v=1.0">


  <!-- ضبط التخطيط ليطابق صفحة المواعيد -->
  <style>
    :root{
      --header-h:80px;        /* ارتفاع الهيدر */
      --sidebar-w:280px;      /* عرض السايدبار */
      --radius:12px;
      --shadow:0 6px 18px rgba(0,0,0,.06);
      --primary:#2c5fa5;
    }
    *{box-sizing:border-box;font-family:'Tajawal',sans-serif}
    html,body{margin:0;padding:0}
    body{
      background:#f1f5f9;
      color:#343a40;
      padding-top:var(--header-h);     /* يترك مساحة للهيدر الثابت */
    }

    /* إزاحة المحتوى عن السايدبار (يمين) */
    .main-content{
      margin-right:var(--sidebar-w);
      padding:20px;
      min-height:calc(100vh - var(--header-h));
      position:relative;
      z-index:1;
    }
    @media (max-width: 991px){
      .main-content{ margin-right:0; }
    }

    /* ترويسة جميلة للصفحة */
    .page-header{
      background: linear-gradient(90deg, #3498db, #4aa8e0);
      color:#fff;
      padding:22px;
      border-radius:10px;
      margin-bottom:22px;
      display:flex; align-items:center; justify-content:space-between;
    }
    .page-header h1{ margin:0; font-size:1.6rem; }

    /* كروت الروابط */
    .card{
      background:#fff;
      border:none;
      border-radius:10px;
      box-shadow:var(--shadow);
      padding:24px;
      text-align:center;
    }
    .card .fa-stack{ margin-bottom:10px; }
    .card .StepTitle{ font-size:1.2rem; color:#1f2d3d; margin-bottom:8px; }
    .card a{ text-decoration:none; color:#2c5fa5; font-weight:600; }
    .card a:hover{ text-decoration:underline; }

    /* تعطيل الأنماط القديمة التي كانت تضيف مسافات غير مرغوبة */
  </style>
</head>
<body>

  <!-- الهيدر أولًا (ثابت بالأعلى) -->
  <?php include('include/header.php'); ?>

  <!-- السايدبار (ثابت يمين) -->
  <?php include('include/sidebar.php'); ?>

  <!-- المحتوى الرئيسي مع إزاحة تلقائية -->
  <div class="main-content">

    <div class="page-header">
      <h1><i class="fa fa-dashboard"></i> لوحة التحكم</h1>
      <span>مرحبا بك دكتور 👋</span>
    </div>

    <div class="container-fluid">
      <div class="row g-3">
        <div class="col-md-6 col-lg-4">
          <div class="card">
            <span class="fa-stack fa-2x">
              <i class="fa fa-square fa-stack-2x text-primary"></i>
              <i class="fa fa-smile-o fa-stack-1x fa-inverse"></i>
            </span>
            <h2 class="StepTitle">ملفي الشخصي</h2>
            <p class="links cl-effect-1">
              <a href="edit-profile.php">تحديث الملف الشخصي</a>
            </p>
          </div>
        </div>

        <div class="col-md-6 col-lg-4">
          <div class="card">
            <span class="fa-stack fa-2x">
              <i class="fa fa-square fa-stack-2x text-primary"></i>
              <i class="fa fa-paperclip fa-stack-1x fa-inverse"></i>
            </span>
            <h2 class="StepTitle">مواعيدي</h2>
            <p class="cl-effect-1">
              <a href="appointment-history.php">عرض سجل المواعيد</a>
            </p>
          </div>
        </div>
      </div>
    </div>

  </div><!-- /.main-content -->

  <?php include('include/footer.php'); ?>
  <?php include('include/setting.php'); ?>

  <!-- سكربتاتك الحالية -->
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
    jQuery(function(){
      if (window.FormElements && FormElements.init) FormElements.init();
      if (window.Main && Main.init) Main.init();
    });
  </script>
</body>
</html>
