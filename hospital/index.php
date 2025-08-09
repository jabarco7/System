<?php
include_once('hms/include/config.php');

if (isset($_POST['submit'])) {
    $name      = trim($_POST['fullname']    ?? '');
    $email     = trim($_POST['emailid']     ?? '');
    $mobileno  = trim($_POST['mobileno']    ?? '');
    $dscrption = trim($_POST['description'] ?? '');

    if ($name !== '' && $email !== '' && $mobileno !== '' && $dscrption !== '') {
        $stmt = mysqli_prepare(
            $con,
            "INSERT INTO tblcontactus (fullname, email, contactno, message) VALUES (?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmt, 'ssss', $name, $email, $mobileno, $dscrption);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        echo "<script>alert('تم إرسال معلوماتك بنجاح');</script>";
        echo "<script>window.location.href='index.php'</script>";
        exit;
    } else {
        echo "<script>alert('الرجاء تعبئة جميع الحقول');</script>";
        echo "<script>window.location.href='index.php'</script>";
        exit;
    }
}
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>نظام إدارة المستشفيات</title>

    <link rel="shortcut icon" href="assets/images/fav.jpg">
    <link rel="stylesheet" href="assets/css/bootstrap.min.css"> <!-- Bootstrap 4 -->
    <link rel="stylesheet" href="assets/css/fontawsom-all.min.css">
    <link rel="stylesheet" href="assets/css/animate.css">
    <link rel="stylesheet" type="text/css" href="assets/css/style.css" />

    <!-- تحسين قسم تسجيلات الدخول ليطابق ستايل لوحات الإدارة -->
    <style>
    :root{--primary:#2f7bdc;--primary2:#4aa8e0;--text:#1f2430;--muted:#6c7a89;--bg:#f7faff}
    /* هيدر داخلي (اختياري) */
    .hero-bar{background:linear-gradient(90deg,var(--primary),var(--primary2));padding:28px 0;color:#fff;margin-bottom:10px}
    .hero-bar h1{margin:0;font-weight:800;letter-spacing:.2px;font-size:26px}

    /* قسم تسجيلات الدخول */
    .login-section{padding:50px 0 40px;background:var(--bg)}
    .login-header{text-align:center;margin-bottom:26px}
    .login-header h2{font-weight:800;color:#1f2430;margin:0 0 6px}
    .login-header .sub{color:var(--muted)}
    .login-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:22px}
    @media (max-width:992px){.login-grid{grid-template-columns:1fr 1fr}}
    @media (max-width:576px){.login-grid{grid-template-columns:1fr}}

    .login-card{background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 10px 24px rgba(0,0,0,.06);transition:.25s transform,.25s box-shadow}
    .login-card:hover{transform:translateY(-4px);box-shadow:0 16px 34px rgba(47,123,220,.18)}
    .login-media{height:170px;background:#eef4ff;display:flex;align-items:center;justify-content:center;overflow:hidden}
    .login-media img{width:100%;height:100%;object-fit:cover}
    .login-body{padding:16px 16px 18px}
    .login-title{font-weight:800;font-size:18px;margin:0 0 6px;color:#273142}
    .login-sub{color:#6c7a89;font-size:13px;margin-bottom:12px}
    .btn-pill{border-radius:999px;padding:.55rem 1.05rem;font-weight:700;border:1px solid transparent;transition:.2s}
    .btn-primary-grad{background:linear-gradient(90deg,var(--primary),var(--primary2));color:#fff;box-shadow:0 8px 18px rgba(47,123,220,.25)}
    .btn-primary-grad:hover{filter:brightness(1.05)}
    </style>
</head>

<body>

<!-- ################# Header ####################### -->
<header id="menu-jk">
  <div id="nav-head" class="header-nav">
    <div class="container">
      <div class="row align-items-center">
        <div class="col-lg-2 col-md-3 col-sm-12" style="color:#000;font-weight:bold;font-size:30px;margin-top:1%!important;">
          مستشفى عمران
          <a data-toggle="collapse" data-target="#menu" href="#menu">
            <i class="fas d-block d-md-none small-menu fa-bars"></i>
          </a>
        </div>
        <div id="menu" class="col-lg-8 col-md-9 d-none d-md-block nav-item">
          <ul>
            <li><a href="#">الرئيسية</a></li>
            <li><a href="#services">الخدمات</a></li>
            <li><a href="#about_us">من نحن</a></li>
            <li><a href="#gallery">معرض</a></li>
            <li><a href="#contact_us">اتصل بنا</a></li>
            <li><a href="#logins">تسجيلات الدخول</a></li>
          </ul>
        </div>
        <div class="col-sm-2 d-none d-lg-block appoint">
          <a class="btn btn-success" href="hms/user-login.php">حجز موعد</a>
        </div>
      </div>
    </div>
  </div>
</header>

<!-- (اختياري) شريط عنوان علوي بسيط قبل السلايدر -->
<div class="hero-bar">
  <div class="container">
    <h1>نظام إدارة المستشفيات</h1>
  </div>
</div>

<!-- ################# Slider ####################### -->
<div class="slider-detail">
  <div id="carouselExampleIndicators" class="carousel slide" data-ride="carousel">
    <ol class="carousel-indicators">
      <li data-target="#carouselExampleIndicators" data-slide-to="0" class="active"></li>
      <li data-target="#carouselExampleIndicators" data-slide-to="1"></li>
    </ol>

    <div class="carousel-inner">
      <div class="carousel-item">
        <img class="d-block w-100" src="assets/images/slider/slider_2.jpg" alt="slide">
        <div class="carousel-cover"></div>
        <div class="carousel-caption vdg-cur d-none d-md-block">
          <h5 class="animated bounceInDown">نظام إدارة المستشفيات</h5>
        </div>
      </div>

      <div class="carousel-item active">
        <img class="d-block w-100" src="assets/images/slider/slider_3.jpg" alt="slide">
        <div class="carousel-cover"></div>
        <div class="carousel-caption vdg-cur d-none d-md-block">
          <h5 class="animated bounceInDown">نظام إدارة المستشفيات</h5>
        </div>
      </div>
    </div>

    <a class="carousel-control-prev" href="#carouselExampleIndicators" role="button" data-slide="prev">
      <span class="carousel-control-prev-icon" aria-hidden="true"></span>
      <span class="sr-only">السابق</span>
    </a>
    <a class="carousel-control-next" href="#carouselExampleIndicators" role="button" data-slide="next">
      <span class="carousel-control-next-icon" aria-hidden="true"></span>
      <span class="sr-only">التالي</span>
    </a>
  </div>
</div>

<!-- ################# Logins (موحّد) ####################### -->
<section id="logins" class="login-section">
  <div class="container">
    <div class="login-header">
      <h2>تسجيلات الدخول</h2>
      <div class="sub">اختر نوع الحساب للمتابعة</div>
    </div>

    <div class="login-grid">
      <!-- المستخدم/المدير -->
      <article class="login-card">
        <div class="login-media">
          <img src="assets/images/admin.jpg" alt="تسجيل دخول المستخدم">
        </div>
        <div class="login-body">
          <h3 class="login-title">تسجيل دخول المستخدم</h3>
          <div class="login-sub">الدخول إلى لوحة التحكم الإدارية</div>
          <a href="hms/admin" class="btn btn-pill btn-primary-grad">ادخل الآن</a>
        </div>
      </article>

      <!-- الأطباء -->
      <article class="login-card">
        <div class="login-media">
          <img src="assets/images/doctor.jpg" alt="تسجيل دخول الأطباء">
        </div>
        <div class="login-body">
          <h3 class="login-title">تسجيل دخول الأطباء</h3>
          <div class="login-sub">إدارة المواعيد وملفات المرضى</div>
          <a href="hms/doctor" class="btn btn-pill btn-primary-grad">ادخل الآن</a>
        </div>
      </article>

      <!-- المرضى -->
      <article class="login-card">
        <div class="login-media">
          <img src="assets/images/patient.jpg" alt="تسجيل دخول المريض">
        </div>
        <div class="login-body">
          <h3 class="login-title">تسجيل دخول المريض</h3>
          <div class="login-sub">حجز المواعيد ومتابعة الملف الطبي</div>
          <a href="hms/user-login.php" class="btn btn-pill btn-primary-grad">ادخل الآن</a>
        </div>
      </article>
    </div>
  </div>
</section>

<!-- ################# Services ####################### -->
<section id="services" class="key-features department">
  <div class="container">
    <div class="inner-title">
      <h2>مميزاتنا الرئيسية</h2>
      <p>ألق نظرة على بعض مميزاتنا الرئيسية</p>
    </div>

    <div class="row">
      <div class="col-lg-4 col-md-6"><div class="single-key"><i class="fas fa-heartbeat"></i><h5>القلب</h5></div></div>
      <div class="col-lg-4 col-md-6"><div class="single-key"><i class="fas fa-ribbon"></i><h5>تقويم أسنان</h5></div></div>
      <div class="col-lg-4 col-md-6"><div class="single-key"><i class="fab fa-monero"></i><h5>طبيب الأسنان</h5></div></div>
      <div class="col-lg-4 col-md-6"><div class="single-key"><i class="fas fa-capsules"></i><h5>خط أنابيب مستشفى عمران</h5></div></div>
      <div class="col-lg-4 col-md-6"><div class="single-key"><i class="fas fa-prescription-bottle-alt"></i><h5>فريق مستشفى عمران</h5></div></div>
      <div class="col-lg-4 col-md-6"><div class="single-key"><i class="far fa-thumbs-up"></i><h5>علاجات عالية الجودة</h5></div></div>
    </div>
  </div>
</section>

<!-- ################# About Us ####################### -->
<section id="about_us" class="about-us">
  <div class="row no-margin">
    <div class="col-sm-6 image-bg no-padding"></div>
    <div class="col-sm-6 abut-yoiu">
      <h3>نبذة عن مستشفانا</h3>
      <?php
      $ret = mysqli_query($con, "SELECT * FROM tblpage WHERE PageType='aboutus'");
      while ($row = mysqli_fetch_assoc($ret)) {
          echo '<p>'.htmlspecialchars($row['PageDescription']).'</p>';
      }
      ?>
    </div>
  </div>
</section>

<!-- ################# Gallery ####################### -->
<div id="gallery" class="gallery">
  <div class="container">
    <div class="inner-title">
      <h2>معرض الصور الخاص بنا</h2>
      <p>عرض معرض الصور الخاص بنا</p>
    </div>

    <div class="row">
      <div class="gallery-filter d-none d-sm-block">
        <button class="btn btn-default filter-button" data-filter="all">كل</button>
        <button class="btn btn-default filter-button" data-filter="hdpe">الأسنان</button>
        <button class="btn btn-default filter-button" data-filter="sprinkle">القلب</button>
        <button class="btn btn-default filter-button" data-filter="spray">الأعصاب</button>
        <button class="btn btn-default filter-button" data-filter="irrigation">المختبر</button>
      </div>

      <div class="gallery_product col-lg-4 col-md-4 col-sm-4 col-xs-6 filter hdpe">
        <img src="assets/images/gallery/gallery_01.jpg" class="img-responsive">
      </div>
      <div class="gallery_product col-lg-4 col-md-4 col-sm-4 col-xs-6 filter sprinkle">
        <img src="assets/images/gallery/gallery_02.jpg" class="img-responsive">
      </div>
      <div class="gallery_product col-lg-4 col-md-4 col-sm-4 col-xs-6 filter hdpe">
        <img src="assets/images/gallery/gallery_03.jpg" class="img-responsive">
      </div>
      <div class="gallery_product col-lg-4 col-md-4 col-sm-4 col-xs-6 filter irrigation">
        <img src="assets/images/gallery/gallery_04.jpg" class="img-responsive">
      </div>
      <div class="gallery_product col-lg-4 col-md-4 col-sm-4 col-xs-6 filter spray">
        <img src="assets/images/gallery/gallery_05.jpg" class="img-responsive">
      </div>
      <div class="gallery_product col-lg-4 col-md-4 col-sm-4 col-xs-6 filter spray">
        <img src="assets/images/gallery/gallery_06.jpg" class="img-responsive">
      </div>
    </div>
  </div>
</div>

<!-- ################# Contact Us ####################### -->
<section id="contact_us" class="contact-us-single">
  <div class="row no-margin">
    <div class="col-sm-12 cop-ck">
      <form method="post">
        <h2>نموذج الاتصال</h2>
        <div class="row cf-ro">
          <div class="col-sm-3"><label>ادخل الاسم:</label></div>
          <div class="col-sm-8"><input type="text" placeholder="ادخل الاسم" name="fullname" class="form-control input-sm" required></div>
        </div>
        <div class="row cf-ro">
          <div class="col-sm-3"><label>ادخل البريد الإلكتروني:</label></div>
          <div class="col-sm-8"><input type="email" name="emailid" placeholder="ادخل البريد الإلكتروني" class="form-control input-sm" required></div>
        </div>
        <div class="row cf-ro">
          <div class="col-sm-3"><label>رقم الجوال:</label></div>
          <div class="col-sm-8"><input type="text" name="mobileno" placeholder="ادخل رقم الجوال" class="form-control input-sm" required></div>
        </div>
        <div class="row cf-ro">
          <div class="col-sm-3"><label>ادخل الرسالة:</label></div>
          <div class="col-sm-8">
            <textarea rows="5" placeholder="ادخل رسالتك" class="form-control input-sm" name="description" required></textarea>
          </div>
        </div>
        <div class="row cf-ro">
          <div class="col-sm-3"><label></label></div>
          <div class="col-sm-8">
            <button class="btn btn-success btn-sm" type="submit" name="submit">إرسال رسالة</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</section>

<!-- ################# Footer ####################### -->
<footer class="footer">
  <div class="container">
    <div class="row">
      <div class="col-md-6 col-sm-12">
        <h2>روابط مفيدة</h2>
        <ul class="list-unstyled link-list">
          <li><a href="#about_us">من نحن</a><i class="fa fa-angle-right"></i></li>
          <li><a href="#services">خدمات</a><i class="fa fa-angle-right"></i></li>
          <li><a href="#logins">تسجيلات الدخول</a><i class="fa fa-angle-right"></i></li>
          <li><a href="#gallery">معرض</a><i class="fa fa-angle-right"></i></li>
          <li><a href="#contact_us">اتصل بنا</a><i class="fa fa-angle-right"></i></li>
        </ul>
      </div>

      <div class="col-md-6 col-sm-12 map-img">
        <h2>اتصل بنا</h2>
        <address class="md-margin-bottom-40">
          <?php
          $ret = mysqli_query($con, "SELECT * FROM tblpage WHERE PageType='contactus'");
          while ($row = mysqli_fetch_assoc($ret)) {
            echo nl2br(htmlspecialchars($row['PageDescription'])) . "<br>";
            echo "Phone: " . htmlspecialchars($row['MobileNumber']) . "<br>";
            echo 'Email: <a href="mailto:' . htmlspecialchars($row['Email']) . '">' . htmlspecialchars($row['Email']) . "</a><br>";
            echo "Timing: " . htmlspecialchars($row['OpenningTime']);
          }
          ?>
        </address>
      </div>
    </div>
  </div>
</footer>

<div class="copy">
  <div class="container">نظام إدارة المستشفيات</div>
</div>

<script src="assets/js/jquery-3.2.1.min.js"></script>
<script src="assets/js/popper.min.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
<script src="assets/plugins/scroll-nav/js/jquery.easing.min.js"></script>
<script src="assets/plugins/scroll-nav/js/scrolling-nav.js"></script>
<script src="assets/plugins/scroll-fixed/jquery-scrolltofixed-min.js"></script>
<script src="assets/js/script.js"></script>
</body>
</html>
