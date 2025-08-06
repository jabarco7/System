<?php
session_start();
include('include/config.php');
if (strlen($_SESSION['id']) == 0) {
    header('location:logout.php');
    exit();
}

// Handle doctor deletion
if(isset($_GET['del'])) {
    $docid = intval($_GET['id']);
    $delete_query = mysqli_query($con, "DELETE FROM doctors WHERE id = '$docid'");
    if($delete_query) {
        $_SESSION['msg'] = "تم حذف الطبيب بنجاح!";
    } else {
        $_SESSION['msg'] = "خطأ في حذف الطبيب: " . mysqli_error($con);
    }
    header('location:manage-doctors.php');
    exit();
}

// Pagination variables
$records_per_page = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max($page, 1);
$offset = ($page - 1) * $records_per_page;

// Get total number of doctors
$total_records_query = mysqli_query($con, "SELECT COUNT(*) as total FROM doctors");
if(!$total_records_query) {
    die("خطأ في جلب البيانات: " . mysqli_error($con));
}
$total_records_result = mysqli_fetch_assoc($total_records_query);
$total_records = $total_records_result['total'];
$total_pages = ceil($total_records / $records_per_page);

// Fetch doctors for current page
$cnt = ($page - 1) * $records_per_page + 1;
$sql = mysqli_query($con, "SELECT * FROM doctors ORDER BY id DESC LIMIT $records_per_page OFFSET $offset");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم | إدارة الأطباء</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #3498db;
            --secondary: #2c3e50;
            --success: #27ae60;
            --danger: #e74c3c;
            --light: #f8f9fa;
            --dark: #343a40;
            --gray: #6c757d;
        }
        
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f0f5f9;
            color: #333;
            padding-top: 60px;
        }
        
        .sidebar {
            background: linear-gradient(180deg, var(--secondary) 0%, #1a2530 100%);
            color: white;
            position: fixed;
            top: 0;
            right: 0;
            bottom: 0;
            width: 250px;
            padding-top: 20px;
            box-shadow: 3px 0 15px rgba(0, 0, 0, 0.2);
            z-index: 1000;
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.85);
            padding: 12px 20px;
            margin: 5px 15px;
            border-radius: 5px;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link:hover, 
        .sidebar .nav-link.active {
            background-color: var(--primary);
            color: white;
        }
        
        .sidebar .nav-link i {
            width: 25px;
            text-align: center;
            margin-left: 10px;
        }
        
        .main-content {
            margin-right: 250px;
            padding: 20px;
        }
        
        .navbar {
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 250px;
            z-index: 999;
            height: 60px;
        }
        
        .page-header {
            background: linear-gradient(90deg, var(--primary), #4aa8e0);
            color: white;
            padding: 25px 30px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .page-header h1 {
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card i {
            font-size: 2.5rem;
            background: linear-gradient(135deg, var(--primary), #4aa8e0);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 15px;
        }
        
        .stat-card h3 {
            font-weight: 700;
            color: var(--secondary);
            margin-bottom: 5px;
            font-size: 1.8rem;
        }
        
        .stat-card p {
            color: var(--gray);
            margin-bottom: 0;
            font-size: 1.05rem;
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 15px 20px;
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .table-container {
            overflow-x: auto;
        }
        
        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .table th {
            background-color: var(--primary);
            color: white;
            font-weight: 600;
            padding: 15px;
        }
        
        .table td {
            padding: 12px 15px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        
        .table tr:hover {
            background-color: rgba(52, 152, 219, 0.05);
        }
        
        .action-btn {
            padding: 6px 12px;
            border-radius: 5px;
            margin-left: 5px;
            font-size: 0.85rem;
            transition: all 0.3s;
        }
        
        .btn-edit {
            background-color: rgba(39, 174, 96, 0.15);
            color: var(--success);
            border: 1px solid rgba(39, 174, 96, 0.3);
        }
        
        .btn-edit:hover {
            background-color: rgba(39, 174, 96, 0.3);
        }
        
        .btn-delete {
            background-color: rgba(231, 76, 60, 0.15);
            color: var(--danger);
            border: 1px solid rgba(231, 76, 60, 0.3);
        }
        
        .btn-delete:hover {
            background-color: rgba(231, 76, 60, 0.3);
        }
        
        .add-btn {
            background: linear-gradient(90deg, var(--success), #2ecc71);
            color: white;
            padding: 8px 20px;
            border-radius: 30px;
            font-weight: 600;
            border: none;
            transition: all 0.3s;
        }
        
        .add-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46, 204, 113, 0.3);
        }
        
        .alert-message {
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 600;
            margin-bottom: 20px;
            animation: fadeIn 0.5s;
            display: flex;
            align-items: center;
        }
        
        .alert-success {
            background-color: rgba(39, 174, 96, 0.15);
            color: var(--success);
            border-left: 4px solid var(--success);
        }
        
        .alert-error {
            background-color: rgba(231, 76, 60, 0.15);
            color: var(--danger);
            border-left: 4px solid var(--danger);
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .pagination-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 30px;
            flex-wrap: wrap;
            background-color: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            direction: rtl;
            margin: 0;
            padding: 0;
        }
        
        .page-item {
            margin: 3px;
        }
        
        .page-item .page-link {
            color: var(--primary);
            border: 1px solid #dee2e6;
            border-radius: 50px;
            padding: 8px 16px;
            background-color: #fff;
            font-weight: 600;
            transition: all 0.3s ease;
            min-width: 40px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .page-item.active .page-link {
            background: linear-gradient(90deg, var(--primary), #4aa8e0);
            border-color: var(--primary);
            color: #fff;
            box-shadow: 0 4px 10px rgba(52, 152, 219, 0.3);
        }
        
        .page-item.disabled .page-link {
            background-color: #f1f1f1;
            color: #ccc;
            cursor: not-allowed;
            border-color: #e0e0e0;
        }
        
        .page-item .page-link:hover:not(.active):not(.disabled) {
            background-color: rgba(52, 152, 219, 0.1);
            color: var(--primary);
            transform: translateY(-1px);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
        }
        
        .page-info {
            padding: 8px 15px;
            font-weight: 600;
            color: var(--secondary);
        }
        
        .page-jump {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .page-jump select {
            padding: 8px 15px;
            border-radius: 25px;
            border: 1px solid #ccc;
            font-weight: 600;
            color: var(--primary);
            background-color: #fff;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .page-jump select:hover {
            border-color: var(--primary);
            box-shadow: 0 0 5px rgba(52, 152, 219, 0.3);
        }
        
        .no-records {
            text-align: center;
            padding: 40px;
            color: var(--gray);
        }
        
        .no-records i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #d1d8e0;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 0;
                padding: 0;
            }
            
            .main-content {
                margin-right: 0;
            }
            
            .navbar {
                right: 0;
            }
            
            .pagination-container {
                flex-direction: column;
                gap: 15px;
            }
            
            .stats-container {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
            
            .action-btn {
                margin-bottom: 5px;
                display: block;
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="text-center mb-4">
            <h4 class="text-white">نظام المستشفى</h4>
            <hr class="bg-light">
        </div>
        <nav class="nav flex-column">
            <a class="nav-link" href="dashboard.php"><i class="fas fa-home"></i> <span>لوحة التحكم</span></a>
            <a class="nav-link active" href="manage-doctors.php"><i class="fas fa-user-md"></i> <span>إدارة الأطباء</span></a>
            <a class="nav-link" href="manage-patients.php"><i class="fas fa-user-injured"></i> <span>إدارة المرضى</span></a>
            <a class="nav-link" href="doctor-logs.php"><i class="fas fa-history"></i> <span>سجلات الجلسات</span></a>
            <a class="nav-link" href="appointments.php"><i class="fas fa-calendar-check"></i> <span>المواعيد</span></a>
            <a class="nav-link" href="reports.php"><i class="fas fa-chart-bar"></i> <span>التقارير</span></a>
            <a class="nav-link" href="settings.php"><i class="fas fa-cog"></i> <span>الإعدادات</span></a>
        </nav>
    </div>
    
    <!-- Top Navigation -->
    <nav class="navbar navbar-expand">
        <div class="container-fluid">
            <div class="d-flex align-items-center">
                <h5 class="mb-0">لوحة تحكم المسؤول</h5>
            </div>
            
            <div class="d-flex align-items-center">
                <div class="dropdown">
                    <a href="#" class="dropdown-toggle d-flex align-items-center text-dark text-decoration-none" id="userDropdown" data-bs-toggle="dropdown">
                        <img src="https://ui-avatars.com/api/?name=المسؤول&background=3498db&color=fff" class="rounded-circle me-2" width="35" height="35">
                        <span class="d-none d-md-inline">المسؤول</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end">
                        <a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> تسجيل الخروج</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1><i class="fas fa-user-md me-3"></i>إدارة الأطباء</h1>
                </div>
                <a href="add-doctor.php" class="add-btn">
                    <i class="fas fa-plus-circle me-2"></i>إضافة طبيب جديد
                </a>
            </div>
        </div>
        
        <!-- Stats Cards -->
        <div class="stats-container">
            <?php
            // إحصائيات حقيقية - تم إزالة الإشارة إلى عمود status غير الموجود
            $total_query = mysqli_query($con, "SELECT COUNT(*) as total FROM doctors");
            $total_result = mysqli_fetch_assoc($total_query);
            $total_count = $total_result['total'];
            
            // تم إزالة الاستعلام الذي يستخدم status
            $specialties_query = mysqli_query($con, "SELECT COUNT(DISTINCT specilization) as total FROM doctors");
            $specialties_result = mysqli_fetch_assoc($specialties_query);
            $specialties_count = $specialties_result['total'];
            
            $recent_query = mysqli_query($con, "SELECT COUNT(*) as total FROM doctors WHERE creationDate >= NOW() - INTERVAL 7 DAY");
            $recent_result = mysqli_fetch_assoc($recent_query);
            $recent_count = $recent_result['total'];
            
            // إحصائية جديدة لا تعتمد على status
            $top_specialty_query = mysqli_query($con, "SELECT specilization, COUNT(*) as count FROM doctors GROUP BY specilization ORDER BY count DESC LIMIT 1");
            $top_specialty_result = mysqli_fetch_assoc($top_specialty_query);
            $top_specialty = $top_specialty_result['specilization'] ?? 'لا يوجد';
            ?>
            <div class="stat-card">
                <i class="fas fa-user-md"></i>
                <h3><?php echo $total_count; ?></h3>
                <p>إجمالي الأطباء</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-stethoscope"></i>
                <h3><?php echo $specialties_count; ?></h3>
                <p>تخصصات طبية</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-clock"></i>
                <h3><?php echo $recent_count; ?></h3>
                <p>أطباء جدد هذا الأسبوع</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-star"></i>
                <h3><?php echo $top_specialty; ?></h3>
                <p>التخصص الأكثر توفراً</p>
            </div>
        </div>
        
        <!-- Doctors Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-table me-2"></i>قائمة الأطباء</span>
                <div class="d-flex">
                    <input type="text" class="form-control form-control-sm me-2" placeholder="بحث بالاسم أو التخصص..." id="searchInput">
                    <button class="btn btn-sm btn-primary" onclick="filterTable()"><i class="fas fa-search me-1"></i> بحث</button>
                </div>
            </div>
            <div class="card-body">
                <?php if (isset($_SESSION['msg'])): ?>
                    <div class="alertmessage <?php echo strpos($_SESSION['msg'], 'خطأ') !== false ? 'alerterror' : 'alertsuccess'; ?> - manage-doctors.php:527">
                        <i class="fas <?php echo strpos($_SESSION['msg'], 'خطأ') !== false ? 'faexclamationcircle' : 'facheckcircle'; ?> me2 - manage-doctors.php:528"></i>
                        <?php echo htmlspecialchars($_SESSION['msg - manage-doctors.php:529']); ?>
                    </div>
                    <?php unset($_SESSION['msg']); ?>
                <?php endif; ?>
                
                <div class="table-container">
                    <table class="table" id="doctorsTable">
                        <thead>
                            <tr>
                                <th class="text-center">#</th>
                                <th>التخصص</th>
                                <th>اسم الطبيب</th>
                                <th>تاريخ الإنشاء</th>
                                <th class="text-center">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $has_records = false;
                            if ($sql && mysqli_num_rows($sql) > 0) {
                                while ($row = mysqli_fetch_assoc($sql)) {
                                    $has_records = true;
                                    // التأكد من استخدام أسماء الحقول الصحيحة
                                    $specilization = isset($row['specilization']) ? $row['specilization'] : 'غير محدد';
                                    $doctorName = isset($row['doctorName']) ? $row['doctorName'] : 'غير معروف';
                                    $creationDate = isset($row['creationDate']) ? $row['creationDate'] : 'غير محدد';
                            ?>
                                    <tr>
                                        <td class="textcenter - manage-doctors.php:557"><?php echo $cnt; ?></td>
                                        <td><?php echo htmlspecialchars($specilization); ?></td>
                                        <td><?php echo htmlspecialchars($doctorName); ?></td>
                                        <td><?php echo htmlspecialchars($creationDate); ?></td>
                                        <td class="text-center">
                                            <a href="editdoctor.php?id=<?php echo $row['id']; ?> - manage-doctors.php:562" class="action-btn btn-edit">
                                                <i class="fas fa-edit me-1"></i>تعديل
                                            </a>
                                            <a href="?del=1&id=<?php echo $row['id']; ?> - manage-doctors.php:565" 
                                               onclick="return confirm('هل أنت متأكد أنك تريد حذف هذا الطبيب؟')" 
                                               class="action-btn btn-delete">
                                                <i class="fas fa-trash-alt me-1"></i>حذف
                                            </a>
                                        </td>
                                    </tr>
                            <?php
                                    $cnt++;
                                }
                            } else {
                                echo '<tr><td colspan="5 - manage-doctors.php:576" class="text-center py-5">
                                    <div class="no-records">
                                        <i class="fas fa-user-md-slash"></i>
                                        <h4>لا توجد سجلات متاحة</h4>
                                        <p class="text-muted">لم يتم إضافة أي أطباء حتى الآن</p>
                                    </div>
                                </td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination-container">
                    <div class="page-info">
                        عرض <?php echo min($cnt - 1, $records_per_page); ?> من أصل <?php echo $total_records; ?> طبيب
                    </div>
                    
                   
                    <nav>
                        <ul class="pagination">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="pagelink - manage-doctors.php:601" href="manage-doctors.php?page=<?php echo $page - 1; ?>" aria-label="السابق">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php 
                            $start = max(1, $page - 2);
                            $end = min($total_pages, $page + 2);
                            
                            if ($start > 1) {
                                echo '<li class="pageitem"><a class="pagelink" href="?page=1">1</a></li> - manage-doctors.php:612';
                                if ($start > 2) {
                                    echo '<li class="pageitem disabled"><span class="pagelink">...</span></li> - manage-doctors.php:614';
                                }
                            }
                            
                            for ($i = $start; $i <= $end; $i++) {
                                $active = ($i == $page) ? 'active' : '';
                                echo '<li class="pageitem - manage-doctors.php:620' . $active . '"><a class="page-link" href="manage-doctors.php?page=' . $i . '">' . $i . '</a></li>';
                            }
                            
                            if ($end < $total_pages) {
                                if ($end < $total_pages - 1) {
                                    echo '<li class="pageitem disabled"><span class="pagelink">...</span></li> - manage-doctors.php:625';
                                }
                                echo '<li class="pageitem"><a class="pagelink" href="?page= - manage-doctors.php:627' . $total_pages . '">' . $total_pages . '</a></li>';
                            }
                            ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="pagelink - manage-doctors.php:633" href="manage-doctors.php?page=<?php echo $page + 1; ?>" aria-label="التالي">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // وظيفة تصفية الجدول
        function filterTable() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toUpperCase();
            const table = document.getElementById('doctorsTable');
            const tr = table.getElementsByTagName('tr');
            
            for (let i = 1; i < tr.length; i++) {
                const td = tr[i].getElementsByTagName('td');
                let show = false;
                
                for (let j = 1; j < 3; j++) { // البحث في التخصص واسم الطبيب فقط
                    if (td[j]) {
                        const txtValue = td[j].textContent || td[j].innerText;
                        if (txtValue.toUpperCase().indexOf(filter) > -1) {
                            show = true;
                            break;
                        }
                    }
                }
                
                tr[i].style.display = show ? '' : 'none';
            }
        }
        
        // تفعيل البحث عند الضغط على Enter
        document.getElementById('searchInput').addEventListener('keyup', function(event) {
            if (event.key === 'Enter') {
                filterTable();
            }
        });
        
        // إخفاء رسالة التنبيه بعد 5 ثوانٍ
        setTimeout(() => {
            const alert = document.querySelector('.alert-message');
            if (alert) {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            }
        }, 5000);
    </script>
</body>
</html>