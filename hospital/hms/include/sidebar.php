<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>المسؤول | لوحة التحكم</title>

  <!-- Bootstrap RTL -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet" />

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

  <style>
    body {
      font-family: 'Tajawal', sans-serif;
      background-color: #307fcfff;
      padding-top: 70px;
    }

    /* الشريط الجانبي */
    #sidebar {
      width: 280px;
      height: calc(100vh - 70px);
      position: fixed;
      top: 70px;
      right: 0;
      background-color: #1a2530 !important;
      color: white !important;
      transition: all 0.3s ease-in-out;
      overflow-y: auto;
      z-index: 1050; /* رفع القيمة لتكون فوق */
    }

    /* تصحيح ألوان روابط الشريط الجانبي */
    #sidebar .nav-link {
      color: white !important;
    }

    #sidebar,
    #sidebar.active {
      background-color: #1a2530 !important;
      color: white !important;
    }

    #sidebar .nav-link:hover,
    #sidebar .nav-link.active {
      background-color: #0d1a26 !important;
      color: #fff !important;
    }

    /* المحتوى الرئيسي */
    #content {
      margin-right: 280px;
      transition: all 0.3s ease-in-out;
    }

    /* عند تصغير الشاشة */
    @media (max-width: 992px) {
      #sidebar {
        transform: translateX(100%);
      }

      #sidebar.active {
        transform: translateX(0);
      }

      #content {
        margin-right: 0;
      }
    }

    .stat-card {
      border-radius: 10px;
      transition: transform 0.3s;
    }

    .stat-card:hover {
      transform: translateY(-5px);
    }
  </style>
</head>
<body>

  <!-- الشريط العلوي -->
  <header class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top" style="height: 70px; z-index: 1030;">
    <div class="container-fluid d-flex align-items-center justify-content-between px-3">
      <!-- زر إظهار الشريط للجوال -->
      <button class="d-block d-lg-none btn btn-outline-light" id="mobileToggle" title="إظهار القائمة">
        ☰
      </button>

      <!-- شعار -->
      <a class="navbar-brand text-white" href="#">HMS</a>

      <!-- العنوان -->
      <h5 class="text-white mb-0 d-none d-md-block">نظام إدارة المستشفيات</h5>

      <!-- المستخدم -->
      <div class="dropdown">
        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle"
           data-bs-toggle="dropdown" aria-expanded="false">
          <img src="assets/images/images.jpg" alt="المستخدم" width="32" height="32" class="rounded-circle ms-2" />
          <strong>المسؤول</strong>
        </a>
        <ul class="dropdown-menu dropdown-menu-end text-end">
          <li><a class="dropdown-item" href="change-password.php">تغيير كلمة المرور</a></li>
          <li><a class="dropdown-item" href="logout.php">تسجيل الخروج</a></li>
        </ul>
      </div>
    </div>
  </header>

  <!-- الهيكل العام -->
  <div class="d-flex">

    <!-- الشريط الجانبي -->
    <nav id="sidebar">
      <div class="p-3">
        <div class="text-center mb-4">
          <div class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
            <i class="fas fa-user-shield fa-2x text-white"></i>
          </div>
          <h5 class="mt-3 mb-1">المسؤول</h5>
          <span class="badge bg-secondary text-white">مدير النظام</span>
        </div>

        <ul class="nav flex-column">
          <li class="nav-item">
            <a class="nav-link active" href="dashboard.php">
              <i class="fas fa-home me-2"></i> لوحة التحكم
            </a>
          </li>

          <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#doctorsCollapse">
              <i class="fas fa-user-md me-2"></i> الأطباء
              <i class="fas fa-angle-down float-start mt-1"></i>
            </a>
            <div class="collapse show" id="doctorsCollapse">
              <ul class="nav flex-column ps-3">
                <li class="nav-item">
                  <a class="nav-link" href="doctor-specilization.php">تخصص الطبيب</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" href="add-doctor.php">إضافة طبيب</a>
                </li>
              </ul>
            </div>
          </li>

          <!-- المزيد من العناصر -->
        </ul>
      </div>
    </nav>

    <!-- محتوى الصفحة -->
    <div id="content" class="w-100">
      <div class="container-fluid py-3">
        <div class="row g-4 mb-4">
          <div class="col-md-4">
            <div class="stat-card bg-white p-4 shadow-sm">
              <div class="d-flex align-items-center">
                <div class="bg-primary bg-opacity-10 p-3 rounded me-3">
                  <i class="fas fa-users fa-2x text-primary"></i>
                </div>
                <div>
                  <h3 class="mb-0">245</h3>
                  <p class="text-muted mb-0">إجمالي المستخدمين</p>
                </div>
              </div>
            </div>
          </div>
          <!-- المزيد من البطاقات -->
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    const sidebar = document.getElementById('sidebar');
    const mobileToggle = document.getElementById('mobileToggle');

    mobileToggle.addEventListener('click', () => {
      sidebar.classList.toggle('active');
    });

    document.addEventListener('click', function (e) {
      if (!sidebar.contains(e.target) && !mobileToggle.contains(e.target)) {
        sidebar.classList.remove('active');
      }
    });
  </script>

</body>
</html>
