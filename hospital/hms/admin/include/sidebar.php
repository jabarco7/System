<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>المسؤول | لوحة التحكم</title>

    <!-- Bootstrap RTL -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- خط Tajawal -->
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
    body {
        font-family: 'Tajawal', sans-serif;
        background: linear-gradient(135deg, #f5f7fa, #e4edf9);
        min-height: 100vh;
        padding-top: 65px;
        /* لحساب ارتفاع الشريط العلوي */
    }

    /* الشريط الجانبي */
    #sidebar {
        position: fixed;
        right: 0 !important;
        left: auto !important;
        width: 280px;
        height: calc(100vh - 15px);
        background-color: #1a2530 !important;
        color: white;
        overflow-y: auto;
        z-index: 1020;
        padding: 1rem;
        transition: all 0.3s ease;
    }



    /* العناصر العامة */
    .user-avatar {
        width: 70px;
        height: 70px;
        border: 3px solid var(--bs-primary);
        background: #2c3e50;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
        color: var(--bs-primary);
        margin: 0 auto;
    }

    .nav-link {
        color: white !important;
        padding: 10px 15px !important;
        border-radius: 5px;
        margin-bottom: 5px;
    }


    .nav-link.active {
        background-color: #007bff !important;
    }

    .nav-link:hover {
        background-color: rgba(255, 255, 255, 0.1);
    }

    .stat-card {
        position: relative;
        overflow: hidden;
        padding: 25px;
        border-radius: 10px;
        text-align: center;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        transition: transform .3s;
        background: #fff;
        margin-bottom: 20px;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }

    .stat-card i {
        font-size: 2.8rem;
        margin-bottom: 15px;
        color: #007bff;
    }

    .section-title {
        font-weight: 700;
        color: #343a40;
        position: relative;
        padding-bottom: 15px;
        margin-bottom: 25px;
    }

    .section-title::after {
        content: "";
        position: absolute;
        bottom: 0;
        right: 0;
        width: 60px;
        height: 3px;
        background: linear-gradient(90deg, #007bff, #4aa8e0);
        border-radius: 3px;
    }

    /* القوائم المنسدلة */
    .collapse.show {
        display: block !important;
    }

    .nav-item .collapse {
        margin-top: 5px;

    }

    .nav-item .nav-link[data-bs-toggle="collapse"]::after {
        content: "\f078";
        font-family: "Font Awesome 6 Free";
        font-weight: 900;
        margin-right: auto;
        margin-left: 10px;
        transition: transform 0.3s;
    }

    .nav-item .nav-link[data-bs-toggle="collapse"][aria-expanded="true"]::after {
        transform: rotate(180deg);

    }

    /* زر القائمة على الأجهزة الصغيرة */
    #sidebarToggle {
        display: none;
        background: transparent;
        border: none;
        color: white;
        font-size: 1.5rem;
        cursor: pointer;
    }

    /* التجاوب مع أحجام الشاشات */
    @media (max-width: 992px) {
        #sidebar {
            transform: translateX(100%);

        }

        #sidebar.active {
            transform: translateX(0);
        }

        .main-content {
            margin-right: 0;
        }

        #sidebarToggle {
            display: block;
        }
    }

    @media (max-width: 768px) {
        .stat-card {
            padding: 15px;
        }

        .stat-card i {
            font-size: 2rem;
        }
    }

    @media (max-width: 576px) {
        body {
            padding-top: 60px;
        }

        #sidebar {
            height: calc(100vh - 60px);
        }

        .top-navbar {
            height: 60px;
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            font-size: 1.5rem;
        }
    }

    .collapse.show {
        display: block !important;
        visibility: visible !important;
    }

    .collapse.show {
        display: block !important;
        visibility: visible !important;
        height: auto !important;
        /* أحيانًا height يكون سبب الإخفاء */
        overflow: visible !important;
    }

    .main-content {
        padding-right: 280px;
    }
    </style>
</head>

<body>
    <!-- الشريط الجانبي -->
    <nav id="sidebar">
        <div class="p-3">
            <div class="text-center user-profile mb-3 border-bottom pb-3">
                <div class="user-avatar"><i class="fas fa-user-md"></i></div>
                <div class="user-name fs-5 fw-semibold">المسؤول</div>
                <div class="user-role badge bg-light text-primary">مدير النظام</div>
            </div>
            <div class="navbar-title mb-3"><span class="fs-5 text-primary">التنقل الرئيسي</span></div>
            <ul class="nav flex-column">
                <li class="nav-item mb-1">
                    <a href="dashboard.php" class="nav-link active d-flex align-items-center">
                        <i class="fas fa-home me-2"></i> لوحة التحكم
                    </a>
                </li>
                <li class="nav-item mb-1">
                    <a class="nav-link d-flex align-items-center" data-bs-toggle="collapse" href="#doctorsMenu"
                        role="button" aria-expanded="false">
                        <i class="fas fa-user-md me-2"></i> الأطباء
                    </a>
                    <div class="collapse" id="doctorsMenu">
                        <ul class="nav flex-column ps-4">
                            <li class="nav-item"><a href="doctor-specilization.php" class="nav-link">تخصص الطبيب</a>
                            </li>
                            <li class="nav-item"><a href="add-doctor.php" class="nav-link">إضافة طبيب</a></li>
                            <li class="nav-item"><a href="Manage-doctors.php" class="nav-link">إدارة الأطباء</a></li>
                        </ul>
                    </div>
                </li>
                <li class="nav-item mb-1"><a href="manage-users.php" class="nav-link"><i class="fas fa-users me-2"></i>
                        إدارة المستخدمين</a></li>
                <li class="nav-item mb-1"><a href="manage-patient.php" class="nav-link"><i
                            class="fas fa-user-injured me-2"></i> إدارة المرضى</a></li>
                
                            
                <li class="nav-item mb-1">
                    <a data-bs-toggle="collapse" href="#queriesMenu" class="nav-link d-flex align-items-center">
                        <i class="fas fa-comments me-2"></i> استعلامات الاتصال
                    </a>
                    <div class="collapse" id="queriesMenu">
                        <ul class="nav flex-column ps-4">
                            <li class="nav-item"><a href="unread-queries.php" class="nav-link">استعلام غير مقروء</a>
                            </li>
                            <li class="nav-item"><a href="read-query.php" class="nav-link">قراءة الاستعلام</a></li>
                        </ul>
                    </div>
                </li>
                <li class="nav-item mb-1"><a href="doctor-logs.php" class="nav-link"><i
                            class="fas fa-clipboard-list me-2"></i> سجلات الجلسات</a></li>
                <li class="nav-item mb-1"><a href="between-dates-reports.php" class="nav-link"><i
                            class="fas fa-chart-bar me-2"></i> تقارير التواريخ</a></li>
                <li class="nav-item"><a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt me-2"></i>
                        تسجيل الخروج</a></li>
            </ul>
        </div>
    </nav>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const mainContent = document.querySelector('.main-content');

        // تبديل الشريط الجانبي
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });

        // إغلاق الشريط الجانبي عند النقر خارجها على الشاشات الصغيرة
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 992) {
                if (!sidebar.contains(e.target) && e.target !== sidebarToggle && !sidebarToggle
                    .contains(e.target)) {
                    sidebar.classList.remove('active');
                }
            }
        });

        // إغلاق القوائم المنسدلة عند النقر خارجها
        document.addEventListener('click', function(e) {
            if (!e.target.matches('.nav-link[data-bs-toggle="collapse"]')) {
                document.querySelectorAll('.collapse.show').forEach(function(openCollapse) {
                    if (!openCollapse.contains(e.target)) {
                        const collapseInstance = bootstrap.Collapse.getInstance(openCollapse);
                        if (collapseInstance) {
                            collapseInstance.hide();
                        }
                    }
                });
            }
        });
    });
    </script>
</body>

</html>