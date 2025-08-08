<?php
session_start();
error_reporting(0);
include('include/config.php');

if (strlen($_SESSION['id']) == 0) {
    header('location:logout.php');
    exit();
}

// الإحصائيات
$users_count = mysqli_num_rows(mysqli_query($con, "SELECT * FROM users"));
$doctors_count = mysqli_num_rows(mysqli_query($con, "SELECT * FROM doctors"));
$appointments_count = mysqli_num_rows(mysqli_query($con, "SELECT * FROM appointment"));
$patients_count = mysqli_num_rows(mysqli_query($con, "SELECT * FROM tblpatient"));
$inquiries_count = mysqli_num_rows(mysqli_query($con, "SELECT * FROM tblcontactus WHERE IsRead IS NULL"));
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>المسؤول | لوحة التحكم</title>

    <!-- الخطوط والأيقونات -->
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">

    <!-- CSS مخصص -->
    <style>
        :root {
            --primary: #3498db;
            --secondary: #2c3e50;
            --success: #27ae60;
            --warning: #f39c12;
            --info: #2980b9;
            --light: #f8f9fa;
            --dark: #343a40;
            --sidebar-bg: #1a2530;
            --sidebar-hover: #3498db;
        }
        
        * {
            font-family: 'Tajawal', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa, #e4edf9);
            min-height: 100vh;
            color: #333;
            padding: 0;
            margin: 0;
        }
        
        .admin-dashboard {
            display: flex;
            min-height: 100vh;
        }
        
        /* تصميم الشريط الجانبي الحديث */
        .sidebar.app-aside {
            width: 280px;
            background: linear-gradient(180deg, var(--sidebar-bg) 0%, #121a24 100%);
            color: #f8f9fa;
            height: 100vh;
            position: fixed;
            top: 0;
            right: 0;
            overflow-y: auto;
            box-shadow: 3px 0 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            z-index: 1000;
        }
        
        .sidebar-container {
            padding: 15px;
        }
        
        .user-profile {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid #2c3e50;
            background: rgba(255, 255, 255, 0.05);
            margin-bottom: 15px;
        }
        
        .user-avatar {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            border: 3px solid var(--primary);
            margin: 0 auto 15px;
            overflow: hidden;
            background: #2c3e50;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: var(--primary);
        }
        
        .user-name {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 5px;
            color: white;
        }
        
        .user-role {
            color: var(--primary);
            font-size: 0.9rem;
            font-weight: 500;
            background: rgba(52, 152, 219, 0.15);
            padding: 3px 10px;
            border-radius: 15px;
            display: inline-block;
        }
        
        .navbar-title {
            padding: 15px 10px;
            margin-bottom: 15px;
            border-bottom: 1px solid #2c3e50;
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary);
            display: flex;
            align-items: center;
        }
        
        .navbar-title::before {
            content: '';
            display: block;
            width: 5px;
            height: 25px;
            background: var(--primary);
            margin-left: 10px;
            border-radius: 3px;
        }
        
        .main-navigation-menu {
            list-style: none;
            padding: 0;
        }
        
        .main-navigation-menu > li {
            margin-bottom: 5px;
            position: relative;
        }
        
        .main-navigation-menu > li > a {
            display: flex;
            align-items: center;
            padding: 14px 20px;
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s;
            position: relative;
        }
        
        .main-navigation-menu > li > a:hover,
        .main-navigation-menu > li.active > a {
            background: rgba(52, 152, 219, 0.15);
            color: white;
        }
        
        .main-navigation-menu > li > a:hover::before,
        .main-navigation-menu > li.active > a::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            height: 100%;
            width: 4px;
            background: var(--primary);
        }
        
        .item-media {
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 15px;
            font-size: 1.2rem;
            color: var(--primary);
            transition: all 0.3s;
        }
        
        .main-navigation-menu > li > a:hover .item-media,
        .main-navigation-menu > li.active > a .item-media {
            transform: scale(1.1);
            color: white;
        }
        
        .item-inner {
            flex-grow: 1;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .item-inner .title {
            font-size: 1rem;
            font-weight: 500;
        }
        
        .icon-arrow {
            font-size: 0.8rem;
            transition: transform 0.3s;
            color: #adb5bd;
        }
        
        .main-navigation-menu > li.active .icon-arrow {
            transform: rotate(180deg);
            color: white;
        }
        
        .sub-menu {
            list-style: none;
            padding-right: 30px;
            margin-top: 5px;
            display: none;
            animation: fadeIn 0.3s ease-out;
        }
        
        .main-navigation-menu > li.active .sub-menu {
            display: block;
        }
        
        .sub-menu li {
            margin-bottom: 3px;
        }
        
        .sub-menu li a {
            display: block;
            padding: 10px 15px;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s;
            font-size: 0.95rem;
            position: relative;
            padding-right: 25px;
        }
        
        .sub-menu li a:hover {
            background: rgba(52, 152, 219, 0.1);
            color: white;
        }
        
        .sub-menu li a::before {
            content: '';
            position: absolute;
            top: 50%;
            right: 10px;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--primary);
            transform: translateY(-50%);
        }
        
        /* تصميم المحتوى الرئيسي */
        .main-content {
            flex: 1;
            margin-right: 50px;
            padding: 20px;
            transition: margin-right 0.3s;
        }
        
        .top-navbar {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .welcome-section {
            background: linear-gradient(90deg, var(--primary), #4aa8e0);
            color: white;
            padding: 25px 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }
        
        .welcome-section h1 {
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .stat-card::before {
            content: "";
            position: absolute;
            top: -10px;
            right: -10px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            opacity: 0.1;
        }
        
        .stat-card.users::before {
            background-color: var(--primary);
        }
        
        .stat-card.doctors::before {
            background-color: var(--success);
        }
        
        .stat-card.appointments::before {
            background-color: var(--warning);
        }
        
        .stat-card.patients::before {
            background-color: var(--info);
        }
        
        .stat-card.inquiries::before {
            background-color: var(--secondary);
        }
        
        .stat-card i {
            font-size: 2.8rem;
            margin-bottom: 15px;
            background: linear-gradient(135deg, var(--primary), #4aa8e0);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        
        .stat-card.users i {
            background: linear-gradient(135deg, var(--primary), #4aa8e0);
        }
        
        .stat-card.doctors i {
            background: linear-gradient(135deg, var(--success), #2ecc71);
        }
        
        .stat-card.appointments i {
            background: linear-gradient(135deg, var(--warning), #f1c40f);
        }
        
        .stat-card.patients i {
            background: linear-gradient(135deg, var(--info), #3498db);
        }
        
        .stat-card.inquiries i {
            background: linear-gradient(135deg, var(--secondary), #34495e);
        }
        
        .stat-card h3 {
            font-weight: 800;
            margin-bottom: 5px;
            font-size: 2.2rem;
            color: var(--secondary);
        }
        
        .stat-card p {
            color: #6c757d;
            margin-bottom: 0;
            font-size: 1.1rem;
        }
        
        .recent-activity {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        
        .section-title {
            position: relative;
            padding-bottom: 15px;
            margin-bottom: 25px;
            font-weight: 700;
            color: var(--secondary);
        }
        
        .section-title::after {
            content: "";
            position: absolute;
            bottom: 0;
            right: 0;
            width: 60px;
            height: 3px;
            background: linear-gradient(90deg, var(--primary), #4aa8e0);
            border-radius: 3px;
        }
        
        .activity-item {
            display: flex;
            padding: 15px 0;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 15px;
            flex-shrink: 0;
        }
        
        .activity-users .activity-icon {
            background-color: rgba(52, 152, 219, 0.1);
            color: var(--primary);
        }
        
        .activity-doctors .activity-icon {
            background-color: rgba(46, 204, 113, 0.1);
            color: var(--success);
        }
        
        .activity-appointments .activity-icon {
            background-color: rgba(241, 196, 15, 0.1);
            color: var(--warning);
        }
        
        .activity-content {
            flex-grow: 1;
        }
        
        .activity-content h5 {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .activity-content p {
            color: #6c757d;
            margin-bottom: 0;
        }
        
        .activity-time {
            color: #6c757d;
            font-size: 0.85rem;
            min-width: 100px;
            text-align: left;
        }
        
        .dashboard-footer {
            text-align: center;
            padding: 20px;
            color: #6c757d;
            font-size: 0.9rem;
            border-top: 1px solid rgba(0,0,0,0.05);
            margin-top: 30px;
        }
        
        .user-profile-nav {
            display: flex;
            align-items: center;
        }
        
        .user-profile-nav img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-left: 10px;
            border: 2px solid var(--primary);
        }
        
        .mobile-toggle {
            display: none;
            background: var(--primary);
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1100;
            box-shadow: 0 3px 10px rgba(0,0,0,0.2);
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @media (max-width: 992px) {
            .sidebar.app-aside {
                transform: translateX(100%);
            }
            
            .sidebar.app-aside.active {
                transform: translateX(0);
            }
            
            
            .mobile-toggle {
                display: flex;
                align-items: center;
                justify-content: center;
            }
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .activity-item {
                flex-direction: column;
            }
            
            .activity-time {
                margin-top: 10px;
                text-align: right;
            }
        }
    </style>
</head>

<body>
    <?php include('include/header.php'); ?>
    <div class="admin-dashboard">
        <!-- الشريط الجانبي -->
        <?php include('include/sidebar.php'); ?>

        <!-- المحتوى الرئيسي -->
        <div class="main-content">
            <!-- شريط التنقل العلوي -->
            <div class="top-navbar">
                <div>
                    <h5 class="mb-0"><i class="fas fa-tachometer-alt me-2"></i>لوحة تحكم المسؤول</h5>
                </div>
                <div class="user-profile-nav">
                    <span>مرحباً، المسؤول</span>
                    <img src="https://ui-avatars.com/api/?name=المسؤول&background=3498db&color=fff" alt="Admin">
                </div>
            </div>

            <!-- الترحيب -->
            
          
                <div class="stat-card doctors">
                    <i class="fas fa-user-md"></i>
                    <h3><?php echo $doctors_count; ?></h3>
                    <p>إجمالي الأطباء</p>
                </div>

                <div class="stat-card appointments">
                    <i class="fas fa-calendar-check"></i>
                    <h3><?php echo $appointments_count; ?></h3>
                    <p>إجمالي المواعيد</p>
                </div>

                <div class="stat-card patients">
                    <i class="fas fa-user-injured"></i>
                    <h3><?php echo $patients_count; ?></h3>
                    <p>إجمالي المرضى</p>
                </div>

                <div class="stat-card inquiries">
                    <i class="fas fa-question-circle"></i>
                    <h3><?php echo $inquiries_count; ?></h3>
                    <p>استفسارات جديدة</p>
                </div>
            </div>

            <!-- النشاطات الحديثة -->
            <div class="recent-activity">
                <h4 class="section-title">النشاطات الحديثة</h4>

                <div class="activity-item activity-users">
                    <div class="activity-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div class="activity-content">
                        <h5>مستخدم جديد</h5>
                        <p>تم تسجيل مستخدم جديد في النظام</p>
                    </div>
                    <div class="activity-time">قبل ساعتين</div>
                </div>

                <div class="activity-item activity-doctors">
                    <div class="activity-icon">
                        <i class="fas fa-stethoscope"></i>
                    </div>
                    <div class="activity-content">
                        <h5>طبيب جديد</h5>
                        <p>تم إضافة طبيب جديد إلى النظام</p>
                    </div>
                    <div class="activity-time">قبل 5 ساعات</div>
                </div>

                <div class="activity-item activity-appointments">
                    <div class="activity-icon">
                        <i class="fas fa-calendar-plus"></i>
                    </div>
                    <div class="activity-content">
                        <h5>موعد جديد</h5>
                        <p>تم حجز موعد جديد مع الدكتور أحمد</p>
                    </div>
                    <div class="activity-time">منذ يوم واحد</div>
                </div>

                <div class="activity-item activity-users">
                    <div class="activity-icon">
                        <i class="fas fa-file-medical"></i>
                    </div>
                    <div class="activity-content">
                        <h5>تقرير جديد</h5>
                        <p>تم رفع تقرير طبي جديد</p>
                    </div>
                    <div class="activity-time">منذ يومين</div>
                </div>
            </div>

            <!-- التذييل -->
            <div class="dashboard-footer">
                نظام إدارة المستشفى &copy; <?php echo date('Y - dashboard.php:666'); ?> - جميع الحقوق محفوظة
            </div>
        </div>

        <!-- زر القائمة الجانبية في الجوال -->
        <button class="mobile-toggle">
            <i class="fas fa-bars"></i>
        </button>
    </div>
    	<?php include('include/setting.php');?>


    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // تبديل الشريط الجانبي في الجوال
        document.querySelector('.mobile-toggle').addEventListener('click', function () {
            document.querySelector('.sidebar.app-aside').classList.toggle('active');
        });

        // تأثير hover على البطاقات
        document.querySelectorAll('.stat-card').forEach(card => {
            card.addEventListener('mouseenter', () => card.style.transform = 'translateY(-5px)');
            card.addEventListener('mouseleave', () => card.style.transform = 'translateY(0)');
        });

        // القائمة الجانبية المنسدلة
        document.addEventListener('DOMContentLoaded', function () {
            const menuItems = document.querySelectorAll('.main-navigation-menu > li');
            menuItems.forEach(item => {
                const link = item.querySelector('a');
                if (link.getAttribute('href') === 'javascript:void(0)') {
                    link.addEventListener('click', function (e) {
                        e.preventDefault();
                        menuItems.forEach(other => {
                            if (other !== item) other.classList.remove('active');
                        });
                        item.classList.toggle('active');
                    });
                }
            });
        });
    </script>
</body>
</html>
