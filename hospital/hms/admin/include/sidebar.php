<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام المستشفى - شريط التنقل</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #4895ef;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --light: #f8f9fa;
            --dark: #212529;
            --sidebar-bg: #ffffff;
            --sidebar-hover: #f0f7ff;
            --text-dark: #2b2d42;
            --text-light: #8d99ae;
            --border-color: #edf2f4;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Tajawal', sans-serif;
        }

        body {
            min-height: 100vh;
            background-color: #f8f9fa;
            color: var(--text-dark);
        }

        /* تصميم الشريط الجانبي */
        .sidebar-container {
 width: 280px;
			background-color: var(--sidebar-bg) #212529;
			color: var(--text-dark);
			display: flex;
			flex-direction: column;
			position: fixed;
			top: 0;
			right: 0;
			height: 100vh;
			box-shadow: 3px 0 15px rgba(0, 0, 0, 0.2);
			transition: all 0.3s ease;
		}
        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid var(--border-color);
            text-align: center;
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            text-decoration: none;
            font-size: 1.3rem;
            font-weight: 700;
        }

        .sidebar-brand i {
            margin-left: 10px;
            font-size: 1.5rem;
        }

        .user-profile {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid var(--border-color);
        }

        .user-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary);
            margin: 0 auto 10px;
            display: block;
            background-color: var(--primary-light);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
        }

        .user-name {
            font-weight: 700;
            margin-bottom: 5px;
            color: var(--text-dark);
        }

        .user-role {
            background: rgba(67, 97, 238, 0.1);
            color: var(--primary);
            padding: 3px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            display: inline-block;
        }

        .sidebar-menu {
            flex: 1;
            overflow-y: auto;
            padding: 15px 10px;
        }

        .menu-section-title {
            color: var(--text-light);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 10px 15px;
            margin: 15px 0 10px;
            font-weight: 600;
        }

        .menu-item {
            margin-bottom: 5px;
            position: relative;
        }

        .menu-link {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: var(--text-dark);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s;
            font-weight: 500;
        }

        .menu-link:hover, 
        .menu-item.active .menu-link {
            background: var(--sidebar-hover);
            color: var(--primary);
        }

        .menu-icon {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 10px;
            background: rgba(67, 97, 238, 0.1);
            border-radius: 8px;
            color: var(--primary);
            transition: all 0.3s;
        }

        .menu-item.active .menu-icon,
        .menu-link:hover .menu-icon {
            background: var(--primary);
            color: white;
        }

        .menu-text {
            flex: 1;
        }

        .menu-badge {
            background: var(--primary);
            color: white;
            font-size: 0.75rem;
            padding: 2px 8px;
            border-radius: 10px;
            font-weight: 600;
        }

        .menu-arrow {
            transition: transform 0.3s;
            color: var(--text-light);
            font-size: 0.8rem;
        }

        .menu-item.active .menu-arrow {
            transform: rotate(180deg);
            color: var(--primary);
        }

        .submenu {
            list-style: none;
            padding-right: 30px;
            margin-top: 5px;
            display: none;
            animation: fadeIn 0.3s ease-out;
        }

        .menu-item.active .submenu {
            display: block;
        }

        .submenu-item {
            margin-bottom: 3px;
        }

        .submenu-link {
            display: block;
            padding: 8px 15px;
            color: var(--text-light);
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.3s;
            font-size: 0.9rem;
            position: relative;
            font-weight: 500;
        }

        .submenu-link:hover {
            color: var(--primary);
            background: rgba(67, 97, 238, 0.05);
        }

        .submenu-link::before {
            content: '';
            position: absolute;
            top: 50%;
            right: 5px;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--primary);
            transform: translateY(-50%);
            opacity: 0.5;
        }

        .submenu-link:hover::before {
            opacity: 1;
        }

        .sidebar-footer {
            padding: 15px;
            border-top: 1px solid var(--border-color);
            text-align: center;
        }

        .sidebar-footer p {
            color: var(--text-light);
            font-size: 0.85rem;
        }

        /* زر التبديل للشريط الجانبي */
        /* المحتوى الرئيسي */
        .main-content {
            margin-right: 280px;
            padding: 20px;
            transition: margin-right 0.3s;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* التصميم المتجاوب */
        @media (max-width: 992px) {
            .sidebar-container {
                transform: translateX(100%);
            }
            
            .sidebar-container.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-right: 0;
            }
            
        }

        @media (max-width: 768px) {
            .sidebar-container {
                width: 260px;
            }
            
            .menu-link {
                padding: 10px 12px;
            }
        }
    </style>
</head>
<body>
    <!-- زر تبديل الشريط الجانبي -->

    <!-- الشريط الجانبي -->
    <div class="sidebar-container">
        <!-- رأس الشريط الجانبي -->
        <div class="sidebar-header">
            <a href="#" class="sidebar-brand">
                <i class="fas fa-hospital"></i>
                <span>نظام المستشفى</span>
            </a>
        </div>

        <!-- ملف تعريف المستخدم -->
        <div class="user-profile">
            <div class="user-avatar">
                <i class="fas fa-user-md"></i>
            </div>
            <h3 class="user-name">د. أحمد محمد</h3>
            <span class="user-role">مدير النظام</span>
        </div>

        <!-- قائمة التنقل -->
        <div class="sidebar-menu">
            <h5 class="menu-section-title">القائمة الرئيسية</h5>
            
            <ul class="menu-list">
                <li class="menu-item active">
                    <a href="dashboard.php" class="menu-link">
                        <div class="menu-icon">
                            <i class="fas fa-home"></i>
                        </div>
                        <div class="menu-text">لوحة التحكم</div>
                        <span class="menu-badge">5</span>
                    </a>
                </li>
                
                <li class="menu-item">
                    <a href="javascript:void(0)" class="menu-link">
                        <div class="menu-icon">
                            <i class="fas fa-user-md"></i>
                        </div>
                        <div class="menu-text">إدارة الأطباء</div>
                        <i class="fas fa-chevron-down menu-arrow"></i>
                    </a>
                    <ul class="submenu">
                        <li class="submenu-item">
                            <a href="doctor-specilization.php" class="submenu-link">التخصصات الطبية</a>
                        </li>
                        <li class="submenu-item">
                            <a href="add-doctor.php" class="submenu-link">إضافة طبيب جديد</a>
                        </li>
                        <li class="submenu-item">
                            <a href="Manage-doctors.php" class="submenu-link">قائمة الأطباء</a>
                        </li>
                    </ul>
                </li>
                
                <li class="menu-item">
                    <a href="manage-users.php" class="menu-link">
                        <div class="menu-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="menu-text">إدارة المستخدمين</div>
                        <span class="menu-badge">12</span>
                    </a>
                </li>
                
                <li class="menu-item">
                    <a href="manage-patient.php" class="menu-link">
                        <div class="menu-icon">
                            <i class="fas fa-user-injured"></i>
                        </div>
                        <div class="menu-text">إدارة المرضى</div>
                    </a>
                </li>
                
                <li class="menu-item">
                    <a href="appointment-history.php" class="menu-link">
                        <div class="menu-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="menu-text">المواعيد</div>
                        <span class="menu-badge">7</span>
                    </a>
                </li>
            </ul>
            
            <h5 class="menu-section-title">الاستفسارات والتقارير</h5>
            
            <ul class="menu-list">
                <li class="menu-item">
                    <a href="javascript:void(0)" class="menu-link">
                        <div class="menu-icon">
                            <i class="fas fa-comments"></i>
                        </div>
                        <div class="menu-text">الاستفسارات</div>
                        <i class="fas fa-chevron-down menu-arrow"></i>
                    </a>
                    <ul class="submenu">
                        <li class="submenu-item">
                            <a href="unread-queries.php" class="submenu-link">استفسارات جديدة <span class="menu-badge">5</span></a>
                        </li>
                        <li class="submenu-item">
                            <a href="read-query.php" class="submenu-link">الاستفسارات المجابة</a>
                        </li>
                    </ul>
                </li>
                
                <li class="menu-item">
                    <a href="doctor-logs.php" class="menu-link">
                        <div class="menu-icon">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <div class="menu-text">سجلات الأطباء</div>
                    </a>
                </li>
                
                <li class="menu-item">
                    <a href="reports.php" class="menu-link">
                        <div class="menu-icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <div class="menu-text">التقارير والإحصائيات</div>
                    </a>
                </li>
            </ul>
            
            <h5 class="menu-section-title">إعدادات النظام</h5>
            
            <ul class="menu-list">
                <li class="menu-item">
                    <a href="settings.php" class="menu-link">
                        <div class="menu-icon">
                            <i class="fas fa-cog"></i>
                        </div>
                        <div class="menu-text">الإعدادات</div>
                    </a>
                </li>
                
                <li class="menu-item">
                    <a href="logout.php" class="menu-link">
                        <div class="menu-icon">
                            <i class="fas fa-sign-out-alt"></i>
                        </div>
                        <div class="menu-text">تسجيل الخروج</div>
                    </a>
                </li>
            </ul>
        </div>

        <!-- تذييل الشريط الجانبي -->
        <div class="sidebar-footer">
            <p>الإصدار 1.0.0</p>
        </div>
    </div>

  
    <!-- أكواد الجافاسكريبت -->
    <script>
        // تبديل الشريط الجانبي على الأجهزة المحمولة

        // تفعيل القوائم المنسدلة
        document.addEventListener('DOMContentLoaded', function() {
            const menuItems = document.querySelectorAll('.menu-item');
            
            menuItems.forEach(item => {
                const link = item.querySelector('.menu-link');
                
                if (link.getAttribute('href') === 'javascript:void(0)') {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        
                        // إغلاق القوائم الأخرى
                        menuItems.forEach(otherItem => {
                            if (otherItem !== item && otherItem.classList.contains('active')) {
                                otherItem.classList.remove('active');
                            }
                        });
                        
                        // فتح/إغلاق القائمة الحالية
                        item.classList.toggle('active');
                    });
                }
            });
            
            // إغلاق القوائم عند النقر خارجها
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.menu-item')) {
                    menuItems.forEach(item => {
                        item.classList.remove('active');
                    });
                }
            });
        });
    </script>
</body>
</html>