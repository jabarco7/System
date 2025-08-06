<?php
session_start();
include('include/config.php');
if (strlen($_SESSION['id']) == 0) {
    header('location:logout.php');
    exit();
}

// Pagination variables
$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max($page, 1); // Ensure page is at least 1
$offset = ($page - 1) * $records_per_page;

// Get total number of records
$total_records_query = mysqli_query($con, "SELECT COUNT(*) as total FROM doctorslog");
$total_records_result = mysqli_fetch_assoc($total_records_query);
$total_records = $total_records_result['total'];
$total_pages = ceil($total_records / $records_per_page);

// Fetch records for current page
$cnt = ($page - 1) * $records_per_page + 1;
$sql = mysqli_query($con, "SELECT * FROM doctorslog ORDER BY loginTime DESC LIMIT $records_per_page OFFSET $offset");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم | سجلات جلسات الأطباء</title>
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
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
        }
        
        .status-success {
            background-color: rgba(39, 174, 96, 0.15);
            color: var(--success);
        }
        
        .status-failed {
            background-color: rgba(231, 76, 60, 0.15);
            color: var(--danger);
        }
        
        /* تصميم محسّن للترقيم الصفحي */
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
            <a class="nav-link" href="manage-doctors.php"><i class="fas fa-user-md"></i> <span>إدارة الأطباء</span></a>
            <a class="nav-link" href="manage-patients.php"><i class="fas fa-user-injured"></i> <span>إدارة المرضى</span></a>
            <a class="nav-link active" href="doctor-logs.php"><i class="fas fa-history"></i> <span>سجلات الجلسات</span></a>
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
                    <h1><i class="fas fa-history me-3"></i>سجلات جلسات الأطباء</h1>
                </div>
            </div>
        </div>
        
        <!-- Stats Cards -->
        <div class="stats-container">
            <?php
            // إحصائيات حقيقية
            $total_query = mysqli_query($con, "SELECT COUNT(*) as total FROM doctorslog");
            $total_result = mysqli_fetch_assoc($total_query);
            $total_count = $total_result['total'];
            
            $success_query = mysqli_query($con, "SELECT COUNT(*) as total FROM doctorslog WHERE status = 1");
            $success_result = mysqli_fetch_assoc($success_query);
            $success_count = $success_result['total'];
            
            $failed_query = mysqli_query($con, "SELECT COUNT(*) as total FROM doctorslog WHERE status = 0");
            $failed_result = mysqli_fetch_assoc($failed_query);
            $failed_count = $failed_result['total'];
            
            $active_query = mysqli_query($con, "SELECT COUNT(DISTINCT uid) as total FROM doctorslog WHERE logout > NOW() - INTERVAL 7 DAY");
            $active_result = mysqli_fetch_assoc($active_query);
            $active_count = $active_result['total'];
            ?>
            <div class="stat-card">
                <i class="fas fa-history"></i>
                <h3><?php echo $total_count; ?></h3>
                <p>مجموع الجلسات</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-check-circle"></i>
                <h3><?php echo $success_count; ?></h3>
                <p>جلسات ناجحة</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-exclamation-circle"></i>
                <h3><?php echo $failed_count; ?></h3>
                <p>جلسات فاشلة</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-user-md"></i>
                <h3><?php echo $active_count; ?></h3>
                <p>أطباء نشيطون</p>
            </div>
        </div>
        
        <!-- Logs Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-table me-2"></i>سجلات جلسات الأطباء</span>
                <div class="d-flex">
                    <input type="text" class="form-control form-control-sm me-2" placeholder="بحث..." id="searchInput">
                    <button class="btn btn-sm btn-primary" onclick="filterTable()"><i class="fas fa-filter me-1"></i> تصفية</button>
                </div>
            </div>
            <div class="card-body">
                <?php if (isset($_SESSION['msg'])): ?>
                    <div class="alert alertinfo - doctor-logs.php:443"><?php echo htmlentities($_SESSION['msg']); ?></div>
                    <?php unset($_SESSION['msg']); ?>
                <?php endif; ?>
                
                <div class="table-container">
                    <table class="table" id="logsTable">
                        <thead>
                            <tr>
                                <th class="text-center">#</th>
                                <th>معرف المستخدم</th>
                                <th>اسم الطبيب</th>
                                <th>عنوان IP</th>
                                <th>وقت تسجيل الدخول</th>
                                <th>وقت تسجيل الخروج</th>
                                <th class="text-center">الحالة</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $has_records = false;
                            if ($sql) {
                                while ($row = mysqli_fetch_array($sql)) {
                                    $has_records = true;
                                    // استخدام أسماء الحقول الصحيحة بدون التعليقات
                                    $uid = $row['uid'] ?? 'غير معروف';
                                    $username = $row['username'] ?? 'غير معروف';
                                    $userip = $row['userip'] ?? 'غير معروف';
                                    $loginTime = $row['loginTime'] ?? 'غير معروف';
                                    $logout = $row['logout'] ?? 'غير معروف';
                                    $status = $row['status'] ?? 0;
                                    
                                    $status_class = ($status == 1) ? 'status-success' : 'status-failed';
                                    $status_text = ($status == 1) ? 'نجاح' : 'فشل';
                            ?>
                                    <tr>
                                        <td class="textcenter - doctor-logs.php:478"><?php echo $cnt; ?></td>
                                        <td><?php echo htmlspecialchars($uid); ?></td>
                                        <td><?php echo htmlspecialchars($username); ?></td>
                                        <td><?php echo htmlspecialchars($userip); ?></td>
                                        <td><?php echo htmlspecialchars($loginTime); ?></td>
                                        <td><?php echo htmlspecialchars($logout); ?></td>
                                        <td class="textcenter - doctor-logs.php:484"><span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                                    </tr>
                            <?php
                                    $cnt++;
                                }
                            } else {
                                echo '<tr><td colspan="7" class="textcenter textdanger">حدث خطأ في جلب البيانات من قاعدة البيانات</td></tr> - doctor-logs.php:490';
                            }
                            
                            if (!$has_records) {
                                echo '<tr><td colspan="7" class="norecords"><i class="fas fainbox"></i><h4>لا توجد سجلات متاحة</h4><p class="textmuted">لم يتم تسجيل أي جلسات حتى الآن</p></td></tr> - doctor-logs.php:494';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination with improved design -->
                <div class="pagination-container">
                    <div class="page-info">
                        عرض <?php echo min($cnt - 1, $records_per_page); ?> من أصل <?php echo $total_records; ?> سجل
                    </div>
                    
                    <div class="page-jump">
                        <span>الانتقال إلى:</span>
                        <select id="pageSelect" onchange="location.href='doctor-logs.php?page=' + this.value">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <option value="<?php echo $i; ?> - doctor-logs.php:511" <?php if ($i == $page) echo 'selected'; ?>>
                                    الصفحة <?php echo $i; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
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
            const table = document.getElementById('logsTable');
            const tr = table.getElementsByTagName('tr');
            
            // ابدأ من الصف 1 لتخطي رأس الجدول
            for (let i = 1; i < tr.length; i++) {
                const td = tr[i].getElementsByTagName('td');
                let show = false;
                
                for (let j = 0; j < td.length; j++) {
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
    </script>
</body>
</html>