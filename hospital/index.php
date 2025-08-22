<?php
include_once('hms/include/config.php');

if (isset($_POST['submit'])) {
    $name      = trim($_POST['fullname']    ?? '');
    $email     = trim($_POST['emailid']     ?? '');
    $mobileno  = trim($_POST['mobileno']    ?? '');
    $dscrption = trim($_POST['description'] ?? '');

    if ($name !== '' && $email !== '' && $mobileno !== '' && $dscrption !== '') {
        $stmt = mysqli_prepare($con, "INSERT INTO tblcontactus (fullname, email, contactno, message) VALUES (?, ?, ?, ?)");
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
  <title>مستشفى عمران | الصفحة الرئيسية</title>

  <link rel="shortcut icon" href="assets/images/fav.jpg">
  <link rel="stylesheet" href="assets/css/bootstrap.min.css"> <!-- Bootstrap 4 -->
  <link rel="stylesheet" href="assets/css/fontawsom-all.min.css">
  <link rel="stylesheet" href="assets/css/animate.css">
  <link rel="stylesheet" href="assets/css/style.css"><!-- (اختياري) -->

  <style>
    :root{
      --primary:#2f7bdc;        /* أزرق أساسي */
      --primary-2:#4aa8e0;      /* تدرّج */
      --ink:#102033;            /* نص غامق */
      --muted:#6b7a8b;
      --bg:#f7faff;             /* خلفية فاتحة */
      --white:#fff;
    }

    *{box-sizing:border-box}
    body{background:var(--bg); color:var(--ink); font-weight:500}

    /* ===== Header ثابت أنيق ===== */
    .site-header{
      position:fixed; inset:0 auto auto 0; right:0; height:64px; z-index:1000;
      background:rgba(18, 54, 120, .95);
      backdrop-filter:saturate(140%) blur(6px);
      box-shadow:0 8px 24px rgba(0,0,0,.18);
    }
    .site-header .wrap{height:64px; display:flex; align-items:center; justify-content:space-between}
    .brand{display:flex; align-items:center; gap:.6rem; color:#fff; text-decoration:none; font-weight:800}
    .brand img{height:34px; border-radius:8px}
    .brand span{font-size:1.1rem}
    .nav-links{display:flex; margin:0; list-style:none; gap:1rem; align-items:center}
    .nav-links a{color:#e9f3ff; text-decoration:none; font-weight:700; opacity:.95}
    .nav-links a:hover{color:#9ed7ff}
    .btn-cta{
      background:linear-gradient(90deg, var(--primary), var(--primary-2));
      color:#fff; border-radius:999px; padding:.5rem 1rem; font-weight:800;
      box-shadow:0 10px 20px rgba(47,123,220,.25);
    }
    .burger{display:none; width:42px; height:42px; border:1px solid rgba(255,255,255,.2); background:transparent; border-radius:10px}
    .burger span, .burger span:before, .burger span:after{
      content:""; display:block; width:18px; height:2px; background:#fff; position:relative; margin:0 auto; transition:.25s
    }
    .burger span:before{position:absolute; top:-6px}
    .burger span:after{position:absolute; top:6px}

    @media (max-width: 992px){
      .burger{display:block}
      .nav-col{display:none}
      .nav-col.show{display:block; position:absolute; top:64px; right:0; left:0; background:rgba(18, 54, 120, .98); padding:12px 0}
      .nav-links{flex-direction:column; align-items:flex-start}
      .nav-links li{padding:6px 16px}
    }

    /* Spacer بسبب الهيدر الثابت */
    .header-spacer{height:64px}

    /* ===== Hero ===== */
    .hero{
      position:relative; min-height:72vh; display:flex; align-items:center; background:#0b4fb9; color:#fff; overflow:hidden;
    }
    .hero:before{
      content:""; position:absolute; inset:0; background:
        radial-gradient(1200px 600px at 110% -10%, rgba(255,255,255,.08), transparent 50%),
        radial-gradient(800px 400px at -10% 110%, rgba(255,255,255,.12), transparent 60%),
        linear-gradient(135deg, var(--primary), var(--primary-2));
      opacity:.96;
    }
    .hero .content{position:relative; z-index:2}
    .hero h1{font-weight:900; line-height:1.2}
    .hero p.lead{opacity:.95}
    .hero .cta-group{display:flex; gap:.75rem; flex-wrap:wrap}
    .hero .stat{
      background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.2);
      color:#fff; padding:.8rem 1rem; border-radius:14px; text-align:center; min-width:120px
    }
    .hero .stat .num{font-size:1.25rem; font-weight:900}

    /* ===== أقسام بطCards ===== */
    .section{padding:64px 0}
    .section .section-head{text-align:center; margin-bottom:28px}
    .section .section-head h2{font-weight:900; margin-bottom:6px}
    .section .section-head p{color:var(--muted)}
    .card-ghost{
      background:#fff; border-radius:16px; border:1px solid #e9eef6;
      box-shadow:0 10px 24px rgba(0,0,0,.06); transition:.25s transform, .25s box-shadow;
    }
    .card-ghost:hover{transform:translateY(-4px); box-shadow:0 16px 34px rgba(47,123,220,.16)}
    .icon-pill{
      width:46px; height:46px; border-radius:14px; display:grid; place-items:center;
      background:linear-gradient(90deg, var(--primary), var(--primary-2)); color:#fff; box-shadow:0 8px 18px rgba(47,123,220,.25)
    }

    /* ===== خدمات ===== */
    .svc-card{padding:18px}
    .svc-card h5{font-weight:800; margin:10px 0 6px}
    .svc-card p{color:var(--muted); margin:0}

    /* ===== نبذة + عرض المزيد ===== */
    .readmore > summary{cursor:pointer; font-weight:700; color:#0b74da; list-style:none}
    .readmore > summary::-webkit-details-marker{display:none}
    .readmore[open] > summary::after{content:" — عرض أقل"; font-weight:400}

    /* ===== أطباء ===== */
    .doc-card img{width:100%; height:210px; object-fit:cover; border-top-left-radius:16px; border-top-right-radius:16px}
    .doc-card .meta{padding:14px}
    .badge-soft{background:#e8f2ff; color:#0b4fb9; border-radius:999px; padding:.25rem .6rem; font-weight:700; font-size:.8rem}

    /* ===== آراء المرضى ===== */
    .testi{background:#fff; border:1px solid #e9eef6; border-radius:14px; padding:18px}
    .testi .who{font-weight:800}

    /* ===== تواصل ===== */
    .contact-wrap{background:#fff; border:1px solid #e9eef6; border-radius:16px; padding:22px}

    /* ===== Footer ===== */
    .footer{background:#0f1f3a; color:#e9f3ff; padding:40px 0}
    .footer a{color:#cfe6ff}
    .copy{background:#0a162b; color:#a9c6ef; padding:10px 0; text-align:center}
  </style>
</head>
<body>

<!-- Header -->
<header class="site-header">
  <div class="container">
    <div class="wrap">
      <a class="brand" href="#">
        <img src="assets/images/fav.jpg" alt="شعار المستشفى">
        <span>مستشفى عمران</span>
      </a>

      <button class="burger d-lg-none" id="burgerBtn" aria-label="قائمة">
        <span></span>
      </button>

      <div class="nav-col">
        <ul class="nav-links">
          <li><a href="#">الرئيسية</a></li>
          <li><a href="#services">الأقسام</a></li>
          <li><a href="#logins">تسجيلات الدخول</a></li>
          <li><a href="#about">من نحن</a></li>
          <li><a href="#doctors">الأطباء</a></li>
          <li><a href="#contact">اتصل بنا</a></li>
          <li class="d-none d-lg-inline"><a class="btn-cta" href="hms/user-login.php">حجز موعد</a></li>
        </ul>
        
      </div>
    </div>
  </div>
</header>
<div class="header-spacer"></div>

<!-- Hero -->
<section class="hero">
  <div class="container">
    <div class="row align-items-center content">
      <div class="col-lg-7">
        <h1 class="mb-3">رعاية آمنة وشاملة — على مدار الساعة</h1>
        <p class="lead mb-4">
          طوارئ 24/7، عيادات متعددة التخصصات، عناية مركزة، نساء وولادة، أشعة ومختبر — بخبرة فريق مؤهّل وأنظمة إلكترونية تُسرّع الخدمة.
        </p>
        <div class="cta-group">

          <a href="#services" class="btn btn-light font-weight-bold">تعرّف على الأقسام</a>
        </div>
        <div class="d-flex gap-2 mt-4">
          <div class="stat mr-2"><div class="num">+120</div><div>سرير</div></div>
          <div class="stat mr-2"><div class="num">+80</div><div>طبيبًا</div></div>
          <div class="stat"><div class="num">24/7</div><div>طوارئ</div></div>
        </div>
      </div>
      <div class="col-lg-5 d-none d-lg-block">
        <img src="assets/images/slider/slider_3.jpg" alt="مستشفى" class="img-fluid shadow" style="border-radius:18px">
      </div>
    </div>
  </div>
</section>

<!-- Services / Departments -->
<section id="services" class="section">
  <div class="container">
    <div class="section-head">
      <h2>الأقسام والخدمات</h2>
      <p>نُقدّم رعاية متكاملة عبر باقة من الأقسام الحيوية</p>
    </div>
    <div class="row">
      <div class="col-md-6 col-lg-4 mb-3">
        <div class="card-ghost svc-card h-100">
          <div class="d-flex align-items-center">
            <div class="icon-pill mr-3"><i class="fas fa-ambulance"></i></div>
            <div>
              <h5>قسم الطوارئ 24/7</h5>
              <p>استجابة سريعة مع فريق طوارئ متكامل.</p>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-6 col-lg-4 mb-3">
        <div class="card-ghost svc-card h-100">
          <div class="d-flex align-items-center">
            <div class="icon-pill mr-3"><i class="fas fa-user-md"></i></div>
            <div>
              <h5>العيادات الخارجية</h5>
              <p>تخصصات متعددة وجلسات متابعة دقيقة.</p>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-6 col-lg-4 mb-3">
        <div class="card-ghost svc-card h-100">
          <div class="d-flex align-items-center">
            <div class="icon-pill mr-3"><i class="fas fa-procedures"></i></div>
            <div>
              <h5>العناية المركزة</h5>
              <p>رعاية حرجة للكبار وحديثي الولادة.</p>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-6 col-lg-4 mb-3">
        <div class="card-ghost svc-card h-100">
            <div class="d-flex align-items-center">
              <div class="icon-pill mr-3"><i class="fas fa-baby"></i></div>
              <div>
                <h5>النساء والولادة</h5>
                <p>رعاية أمومة متكاملة وغرف ولادة مجهّزة.</p>
              </div>
            </div>
        </div>
      </div>
      <div class="col-md-6 col-lg-4 mb-3">
        <div class="card-ghost svc-card h-100">
          <div class="d-flex align-items-center">
            <div class="icon-pill mr-3"><i class="fas fa-x-ray"></i></div>
            <div>
              <h5>الأشعة والتصوير</h5>
              <p>موجات فوق صوتية، أشعّة، ومقطعية/رنين (حسب التجهيز).</p>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-6 col-lg-4 mb-3">
        <div class="card-ghost svc-card h-100">
          <div class="d-flex align-items-center">
            <div class="icon-pill mr-3"><i class="fas fa-vials"></i></div>
            <div>
              <h5>المختبر وبنك الدم</h5>
              <p>تحاليل دقيقة وسريعة مع ضبط جودة.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Logins -->
<section id="logins" class="section">
  <div class="container">
    <div class="section-head">
      <h2>تسجيلات الدخول</h2>
      <p>اختر نوع الحساب للدخول إلى النظام</p>
    </div>

    <div class="row">
      <!-- تسجيل دخول المستخدم (الإدارة) -->
      <div class="col-md-6 mb-3">
        <article class="card-ghost h-100 p-3 d-flex flex-column">
          <div class="d-flex align-items-center mb-2">
            <div class="icon-pill mr-3"><i class="fas fa-user-shield"></i></div>
            <h5 class="mb-0" style="font-weight:800">تسجيل دخول المستخدم (الإدارة)</h5>
          </div>
          <p class="text-muted mb-3">الدخول إلى لوحة التحكم وإدارة النظام.</p>
          <a href="hms/admin" class="btn btn-cta mt-auto align-self-start">ادخل الآن</a>
        </article>
      </div>

      <!-- تسجيل دخول الأطباء -->
      <div class="col-md-6 mb-3">
        <article class="card-ghost h-100 p-3 d-flex flex-column">
          <div class="d-flex align-items-center mb-2">
            <div class="icon-pill mr-3"><i class="fas fa-stethoscope"></i></div>
            <h5 class="mb-0" style="font-weight:800">تسجيل دخول الأطباء</h5>
          </div>
          <p class="text-muted mb-3">إدارة المواعيد وملفات المرضى.</p>
          <a href="hms/doctor" class="btn btn-cta mt-auto align-self-start">ادخل الآن</a>
        </article>
      </div>

      <!-- رابط اختياري للمرضى -->
      <div class="col-12 text-center mt-2">
        <a href="hms/user-login.php" class="font-weight-bold">مريض؟ احجز موعدك من هنا</a>
      </div>
    </div>
  </div>
</section>

<!-- About -->
<section id="about" class="section" style="padding-top:24px">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-lg-6 mb-3">
        <img src="assets/images/gallery/gallery_02.jpg" alt="عن المستشفى" class="img-fluid shadow" style="border-radius:18px">
      </div>
      <div class="col-lg-6">
        <div class="section-head text-right mb-2">
          <h2>من نحن</h2>
        </div>
        <p>
          في <strong>مستشفى عمران</strong> نضع سلامة المريض وكرامته في المقدّمة عبر بروتوكولات صارمة لمكافحة العدوى
          ونظام سجل طبي إلكتروني يضمن سرعة الخدمة ودقتها.
        </p>
        <details class="readmore mt-2">
          <summary>عرض المزيد</summary>
          <div class="mt-2">
            <ul class="mb-2">
              <li>فريق طبي وتمريضي ذو كفاءة عالية وتغطية 24/7.</li>
              <li>قبول شركات التأمين الصحي المعتمدة.</li>
              <li>برامج تأهيل وعلاج طبيعي وخدمات متابعة بعد الخروج.</li>
            </ul>
            <p class="mb-0">هدفنا أن نكون خيارك الأول للرعاية الصحية الشاملة.</p>
          </div>
        </details>
        <div class="mt-3">
          <a href="hms/user-login.php" class="btn btn-cta">احجز موعدًا</a>
          <a href="#contact" class="btn btn-outline-primary font-weight-bold">راسلنا</a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Doctors -->
<section id="doctors" class="section" style="padding-top:24px">
  <div class="container">
    <div class="section-head">
      <h2>نخبة من أطبائنا</h2>
      <p>عيّنات توضيحية — استبدلها ببيانات فعلية</p>
    </div>
    <div class="row">
      <div class="col-md-6 col-lg-4 mb-3">
        <div class="card-ghost doc-card h-100">
          <img src="assets/images/doctor.jpg" alt="طبيب">
          <div class="meta">
            <div class="d-flex align-items-center justify-content-between mb-1">
              <strong>د. أحمد القحطاني</strong>
              <span class="badge-soft">قلب</span>
            </div>
            <div class="text-muted small">خبرة 10 سنوات | مواعيد متاحة</div>
          </div>
        </div>
      </div>
      <div class="col-md-6 col-lg-4 mb-3">
        <div class="card-ghost doc-card h-100">
          <img src="assets/images/admin.jpg" alt="طبيبة">
          <div class="meta">
            <div class="d-flex align-items-center justify-content-between mb-1">
              <strong>د. هدى العزّي</strong>
              <span class="badge-soft">نساء وولادة</span>
            </div>
            <div class="text-muted small">خبرة 8 سنوات | مواعيد متاحة</div>
          </div>
        </div>
      </div>
      <div class="col-md-6 col-lg-4 mb-3">
        <div class="card-ghost doc-card h-100">
          <img src="assets/images/gallery/gallery_01.jpg" alt="طبيب أسنان">
          <div class="meta">
            <div class="d-flex align-items-center justify-content-between mb-1">
              <strong>د. سالم الريمي</strong>
              <span class="badge-soft">أسنان</span>
            </div>
            <div class="text-muted small">خبرة 7 سنوات | مواعيد متاحة</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Testimonials -->
<section class="section" style="padding-top:24px">
  <div class="container">
    <div class="section-head">
      <h2>ماذا يقول مرضانا</h2>
    </div>
    <div class="row">
      <div class="col-md-6 mb-3"><div class="testi h-100">“خدمة سريعة وتعامل راقٍ في الطوارئ.”<div class="who mt-2">— م. عبد الرحمن</div></div></div>
      <div class="col-md-6 mb-3"><div class="testi h-100">“قسم الولادة مجهّز والفريق مطمئن.”<div class="who mt-2">— أ. مريم</div></div></div>
    </div>
  </div>
</section>

<!-- Contact -->
<section id="contact" class="section" style="padding-top:24px">
  <div class="container">
    <div class="section-head">
      <h2>اتصل بنا</h2>
      <p>أرسل رسالتك وسيتم التواصل معك</p>
    </div>
    <div class="row">
      <div class="col-lg-7 mb-3">
        <form method="post" class="contact-wrap">
          <div class="form-group">
            <label>الاسم الكامل</label>
            <input type="text" name="fullname" class="form-control" placeholder="اكتب اسمك" required>
          </div>
          <div class="form-row">
            <div class="form-group col-md-6">
              <label>البريد الإلكتروني</label>
              <input type="email" name="emailid" class="form-control" placeholder="name@example.com" required>
            </div>
            <div class="form-group col-md-6">
              <label>رقم الجوال</label>
              <input type="text" name="mobileno" class="form-control" placeholder="7XXXXXXXX" required>
            </div>
          </div>
          <div class="form-group">
            <label>الرسالة</label>
            <textarea name="description" class="form-control" rows="5" placeholder="كيف يمكننا خدمتك؟" required></textarea>
          </div>
          <button class="btn btn-cta" type="submit" name="submit">إرسال</button>
        </form>
      </div>
      <div class="col-lg-5">
        <div class="contact-wrap">
          <h5 class="mb-3">بيانات التواصل</h5>
          <address class="mb-0">
            <?php
              $ret = mysqli_query($con, "SELECT * FROM tblpage WHERE PageType='contactus'");
              while ($row = mysqli_fetch_assoc($ret)) {
                echo nl2br(htmlspecialchars($row['PageDescription'])) . "<br>";
                echo "الهاتف: " . htmlspecialchars($row['MobileNumber']) . "<br>";
                echo 'البريد: <a href="mailto:' . htmlspecialchars($row['Email']) . '">' . htmlspecialchars($row['Email']) . "</a><br>";
                echo "المواعيد: " . htmlspecialchars($row['OpenningTime']);
              }
            ?>
          </address>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Footer -->
<footer class="footer">
  <div class="container">
    <div class="row">
      <div class="col-md-5 mb-3">
        <h5 class="text-white">مستشفى عمران</h5>
        <p class="mb-2">رعاية إنسانية مبنية على معايير الجودة وسلامة المرضى.</p>
      </div>
      <div class="col-md-4 mb-3">
        <h6 class="text-white">روابط</h6>
        <ul class="list-unstyled">
          <li><a href="#about">من نحن</a></li>
          <li><a href="#services">الأقسام</a></li>
          <li><a href="#logins">تسجيلات الدخول</a></li>
          <li><a href="#doctors">الأطباء</a></li>
          <li><a href="#contact">اتصل بنا</a></li>
        </ul>
      </div>
      <div class="col-md-3 mb-3">
        <h6 class="text-white">تواصل</h6>
        <div class="small">
          <div><i class="fa fa-map-marker-alt"></i> عمران — اليمن</div>
          <div><i class="fa fa-phone"></i> 7XXXXXXXX</div>
          <div><i class="fa fa-envelope"></i> info@example.com</div>
        </div>
      </div>
    </div>
  </div>
</footer>
<div class="copy">© جميع الحقوق محفوظة — مستشفى عمران</div>

<!-- Scripts -->
<script src="assets/js/jquery-3.2.1.min.js"></script>
<script src="assets/js/popper.min.js"></script>
<script src="assets/js/bootstrap.min.js"></script>

<script>
  // فتح/إغلاق قائمة الجوال
  (function(){
    var burger = document.getElementById('burgerBtn');
    var navCol = document.querySelector('.nav-col');
    if (burger && navCol){
      burger.addEventListener('click', function(){ navCol.classList.toggle('show'); });
    }
  })();

  // تحسين نص زر "عرض المزيد"
  document.querySelectorAll('.readmore').forEach(d => {
    const s = d.querySelector('summary');
    const more = 'عرض المزيد', less = 'عرض أقل';
    s.textContent = more;
    d.addEventListener('toggle', () => { s.textContent = d.open ? less : more; });
  });
</script>
</body>
</html>
