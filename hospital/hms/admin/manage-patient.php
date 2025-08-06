<?php
session_start();
error_reporting(0);
include('include/config.php');
if(strlen($_SESSION['id']) == 0) {
    header('location:logout.php');
} else {
    // Pagination variables
    $records_per_page = 10;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $page = max($page, 1);
    $offset = ($page - 1) * $records_per_page;

    // Get total number of patients
    $total_records_query = mysqli_query($con, "SELECT COUNT(*) as total FROM tblpatient");
    $total_records_result = mysqli_fetch_assoc($total_records_query);
    $total_records = $total_records_result['total'];
    $total_pages = ceil($total_records / $records_per_page);

    // Fetch patients for current page
    $cnt = ($page - 1) * $records_per_page + 1;
    $sql = mysqli_query($con, "SELECT * FROM tblpatient ORDER BY ID DESC LIMIT $records_per_page OFFSET $offset");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>المسؤول | عرض المرضى</title>
    
    <!-- Bootstrap 5 RTL -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #3498db;
            --secondary: #2c3e50;
            --success: #27ae60;
            --info: #2980b9;
            --warning: #f39c12;
            --light: #f8f9fa;
            --dark: #343a40;
        }
        
        body {
            font-family: 'Tajawal', sans-serif;
            background: linear-gradient(135deg, #f5f7fa, #e4edf9);
            min-height: 100vh;
            padding-top: 65px;
        }
        
        .app-content {
            margin-right: 20px;
            transition: margin-right 0.3s ease;
        }
        
        .main-content {
            padding-top: 40px;

        }
        
        .page-header {
            background: linear-gradient(90deg, var(--primary), #4aa8e0);
            color: white;
            padding: 25px 30px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .page-header h1 {
            font-weight: 700;
            margin-bottom: 0;
        }
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            position: relative;
            overflow: hidden;
            padding: 25px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: transform .3s;
            background: #fff;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .stat-card i {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        
        .stat-card h3 {
            font-weight: 700;
            margin-bottom: 5px;
            font-size: 1.8rem;
        }
        
        .stat-card p {
            color: #6c757d;
            margin-bottom: 0;
            font-size: 1.05rem;
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-bottom: 25px;
            overflow: hidden;
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding: 15px 20px;
            font-weight: 600;
            font-size: 1.1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
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
            border-bottom: 1px solid rgba(0,0,0,0.05);
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
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-view {
            background-color: rgba(52, 152, 219, 0.15);
            color: var(--primary);
            border: 1px solid rgba(52, 152, 219, 0.3);
        }
        
        .btn-view:hover {
            background-color: rgba(52, 152, 219, 0.3);
        }
        
        .search-box {
            display: flex !important;
            gap: 10px;
            flex-wrap:wrap;
			
        }
        
.search-box input {
    width: 300px; 
    max-width: 100%;
    border-radius: 30px;
    padding: 8px 20px;
}
        
        .search-box .btn {
            border-radius: 30px;
            padding: 8px 20px;
        }
        
        .no-records {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
        
        .no-records i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #d1d8e0;
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
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
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
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
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
        
        @media (max-width: 992px) {
            .app-content {
                margin-right: 0;
            }
            
            .search-box {
                width: 100%;
                margin-top: 15px;
            }
            
            .search-box input {
                min-width: 100%;
            }
        }
        
        @media (max-width: 768px) {
            .stat-card {
                padding: 15px;
            }
            
            .stat-card i {
                font-size: 2rem;
            }
            
            .stat-card h3 {
                font-size: 1.5rem;
            }
            
            .pagination-container {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <?php include('include/header.php'); ?>
    <?php include('include/sidebar.php'); ?>
    
    <!-- Main Content -->
    <div class="app-content">
        <div class="main-content">
            <div class="container-fluid">
                <div class="page-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1><i class="fas fa-users me-3"></i>إدارة المرضى</h1>
                        </div>
                        <div class="d-flex align-items-center">
                            <span class="badge bg-light text-dark fs-6">المسؤول</span>
                        </div>
                    </div>
                </div>
                
                <!-- Stats Cards -->
                <div class="stats-container">
                    <?php
                    $total_patients = mysqli_query($con, "SELECT COUNT(*) as total FROM tblpatient");
                    $total_result = mysqli_fetch_assoc($total_patients);
                    $total_count = $total_result['total'];
                    
                    $male_patients = mysqli_query($con, "SELECT COUNT(*) as total FROM tblpatient WHERE PatientGender = 'ذكر'");
                    $male_result = mysqli_fetch_assoc($male_patients);
                    $male_count = $male_result['total'];
                    
                    $female_patients = mysqli_query($con, "SELECT COUNT(*) as total FROM tblpatient WHERE PatientGender = 'أنثى'");
                    $female_result = mysqli_fetch_assoc($female_patients);
                    $female_count = $female_result['total'];
                    
                    $recent_patients = mysqli_query($con, "SELECT COUNT(*) as total FROM tblpatient WHERE CreationDate >= NOW() - INTERVAL 7 DAY");
                    $recent_result = mysqli_fetch_assoc($recent_patients);
                    $recent_count = $recent_result['total'];
                    ?>
                    <div class="stat-card">
                        <i class="fas fa-users text-primary"></i>
                        <h3><?php echo $total_count; ?></h3>
                        <p>إجمالي المرضى</p>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-male text-info"></i>
                        <h3><?php echo $male_count; ?></h3>
                        <p>مرضى ذكور</p>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-female text-warning"></i>
                        <h3><?php echo $female_count; ?></h3>
                        <p>مرضى إناث</p>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-clock text-success"></i>
                        <h3><?php echo $recent_count; ?></h3>
                        <p>مرضى جدد هذا الأسبوع</p>
                    </div>
                </div>
                
                <!-- Patients Table -->
                <div class="card">
                    <div class="card-header">
                        <span><i class="fas fa-table me-2"></i>قائمة المرضى</span>
                        <div class="search-box">
                            <input type="text" class="form-control" placeholder="ابحث باسم المريض أو رقم الهاتف..." id="searchInput">
                            <button class="btn btn-primary" onclick="filterTable()">
                                <i class="fas fa-search me-1"></i> بحث
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-container">
                            <table class="table" id="patientsTable">
                                <thead>
                                    <tr>
                                        <th class="text-center">#</th>
                                        <th>اسم المريض</th>
                                        <th>رقم الاتصال</th>
                                        <th>الجنس</th>
                                        <th>تاريخ الإنشاء</th>
                                        <th>تاريخ التحديث</th>
                                        <th class="text-center">الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if (mysqli_num_rows($sql) > 0) {
                                        while ($row = mysqli_fetch_array($sql)) {
                                    ?>
                                            <tr>
                                                <td class="textcenter - manage-patient.php:439"><?php echo $cnt; ?></td>
                                                <td><?php echo htmlspecialchars($row['PatientName - manage-patient.php:440']); ?></td>
                                                <td><?php echo htmlspecialchars($row['PatientContno - manage-patient.php:441']); ?></td>
                                                <td>
                                                    <?php if ($row['PatientGender'] == 'ذكر'): ?>
                                                        <span class="badge bg-info">ذكر</span>
                                                    <?php elseif ($row['PatientGender'] == 'أنثى'): ?>
                                                        <span class="badge bg-warning">أنثى</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">غير محدد</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($row['CreationDate - manage-patient.php:451']); ?></td>
                                                <td><?php echo htmlspecialchars($row['UpdationDate - manage-patient.php:452']); ?></td>
                                                <td class="text-center">
                                                    <a href="viewpatient.php?viewid=<?php echo $row['ID']; ?> - manage-patient.php:454" 
                                                       class="action-btn btn-view" target="_blank">
                                                        <i class="fas fa-eye me-1"></i>عرض
                                                    </a>
                                                </td>
                                            </tr>
                                    <?php 
                                            $cnt++;
                                        }
                                    } else {
                                        echo '<tr><td colspan="7 - manage-patient.php:464" class="text-center py-5">
                                            <div class="no-records">
                                                <i class="fas fa-user-injured"></i>
                                                <h4>لا توجد سجلات متاحة</h4>
                                                <p class="text-muted">لم يتم إضافة أي مرضى حتى الآن</p>
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
                                عرض <?php echo min($cnt - 1, $records_per_page); ?> من أصل <?php echo $total_records; ?> مريض
                            </div>
                            
                            <nav>
                                <ul class="pagination">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="pagelink - manage-patient.php:488" href="?page=<?php echo $page - 1; ?>" aria-label="السابق">
                                                <span aria-hidden="true">&laquo;</span>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php 
                                    $start = max(1, $page - 2);
                                    $end = min($total_pages, $page + 2);
                                    
                                    if ($start > 1) {
                                        echo '<li class="pageitem"><a class="pagelink" href="?page=1">1</a></li> - manage-patient.php:499';
                                        if ($start > 2) {
                                            echo '<li class="pageitem disabled"><span class="pagelink">...</span></li> - manage-patient.php:501';
                                        }
                                    }
                                    
                                    for ($i = $start; $i <= $end; $i++) {
                                        $active = ($i == $page) ? 'active' : '';
                                        echo '<li class="pageitem - manage-patient.php:507' . $active . '"><a class="page-link" href="?page=' . $i . '">' . $i . '</a></li>';
                                    }
                                    
                                    if ($end < $total_pages) {
                                        if ($end < $total_pages - 1) {
                                            echo '<li class="pageitem disabled"><span class="pagelink">...</span></li> - manage-patient.php:512';
                                        }
                                        echo '<li class="pageitem"><a class="pagelink" href="?page= - manage-patient.php:514' . $total_pages . '">' . $total_pages . '</a></li>';
                                    }
                                    ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="pagelink - manage-patient.php:520" href="?page=<?php echo $page + 1; ?>" aria-label="التالي">
                                                <span aria-hidden="true">&raquo;</span>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                            
                            <div class="page-jump">
                                <span>اذهب إلى الصفحة:</span>
                                <select class="form-select" onchange="window.location.href='?page='+this.value">
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <option value="<?php echo $i; ?> - manage-patient.php:532" <?php if($i == $page) echo 'selected'; ?>>
                                            <?php echo $i; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

	<?php include('include/setting.php'); ?>
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // وظيفة تصفية الجدول
        function filterTable() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toUpperCase();
            const table = document.getElementById('patientsTable');
            const tr = table.getElementsByTagName('tr');
            
            for (let i = 1; i < tr.length; i++) {
                const td = tr[i].getElementsByTagName('td');
                let show = false;
                
                // البحث في اسم المريض ورقم الهاتف
                for (let j = 1; j < 3; j++) {
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
        
        // إضافة تأثيرات تفاعلية للأزرار
        document.querySelectorAll('.action-btn').forEach(button => {
            button.addEventListener('mouseover', function() {
                this.style.transform = 'scale(1.05)';
            });
            
            button.addEventListener('mouseout', function() {
                this.style.transform = 'scale(1)';
            });
        });
    </script>
</body>
</html>
<?php } ?>