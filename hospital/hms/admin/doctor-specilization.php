<?php
session_start();
error_reporting(0);
include('include/config.php');

if (strlen($_SESSION['id']) == 0) {
    header('location:logout.php');
} else {
    if (isset($_POST['submit'])) {
        $doctorspecilization = mysqli_real_escape_string($con, $_POST['doctorspecilization']);
        $sql = mysqli_query($con, "INSERT INTO doctorSpecilization(specilization) VALUES('$doctorspecilization')");
        $_SESSION['msg'] = "تمت إضافة تخصص الطبيب بنجاح!!";
    }

    if (isset($_GET['del'])) {
        $sid = intval($_GET['id']);
        mysqli_query($con, "DELETE FROM doctorSpecilization WHERE id = '$sid'");
        $_SESSION['msg'] = "تم حذف البيانات !!";
    }

    // Pagination setup
    $limit = 5;
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $start_from = ($page - 1) * $limit;
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم | تخصصات الأطباء</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
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
        
        .main-content {
            margin-right: 20px;
            padding: 20px;
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
        
        .panel {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
            overflow: hidden;
        }
        
        .panel-heading {
            background-color: var(--primary);
            color: white;
            padding: 15px 20px;
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .panel-body {
            padding: 20px;
        }
        
        .form-control {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 12px 15px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 8px rgba(52, 152, 219, 0.3);
        }
        
        .btn-primary {
            background: linear-gradient(90deg, var(--primary), #4aa8e0);
            border: none;
            padding: 10px 25px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }
        
        .table-container {
            overflow-x: auto;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
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
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
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
            justify-content: center;
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
            text-align: center;
            width: 100%;
            margin-bottom: 10px;
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
            .main-content {
                margin-right: 0;
            }
            
            .pagination-container {
                flex-direction: column;
                gap: 15px;
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
    <!-- Top Navigation -->
    <?php include('include/header.php'); ?>
    <?php include('include/sidebar.php'); ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1><i class="fas fa-stethoscope me-3"></i>إدارة تخصصات الأطباء</h1>
                </div>
            </div>
        </div>
        
        <!-- Display Messages -->
        <?php if (isset($_SESSION['msg'])): ?>
            <div class="alertmessage <?php echo strpos($_SESSION['msg'], 'حذف') !== false ? 'alerterror' : 'alertsuccess'; ?> - doctor-specilization.php:325">
                <i class="fas <?php echo strpos($_SESSION['msg'], 'حذف') !== false ? 'faexclamationcircle' : 'facheckcircle'; ?> me2 - doctor-specilization.php:326"></i>
                <?php echo htmlspecialchars($_SESSION['msg - doctor-specilization.php:327']); ?>
            </div>
            <?php unset($_SESSION['msg']); ?>
        <?php endif; ?>
        
        <!-- Add Specialization Form -->
        <div class="panel">
            <div class="panel-heading">
                <h5 class="panel-title"><i class="fas fa-plus-circle me-2"></i>إضافة تخصص جديد</h5>
            </div>
            <div class="panel-body">
                <form role="form" name="dcotorspcl" method="post">
                    <div class="mb-3">
                        <label class="form-label fw-bold">اسم التخصص الطبي</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user-md"></i></span>
                            <input type="text" name="doctorspecilization" class="form-control" placeholder="أدخل اسم التخصص" required>
                        </div>
                    </div>
                    <button type="submit" name="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>حفظ التخصص
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Specializations List -->
        <div class="panel">
            <div class="panel-heading">
                <h5 class="panel-title"><i class="fas fa-list me-2"></i>قائمة التخصصات الطبية</h5>
            </div>
            <div class="panel-body">
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th class="text-center">#</th>
                                <th>التخصص الطبي</th>
                                <th>تاريخ الإنشاء</th>
                                <th>تاريخ التعديل</th>
                                <th class="text-center">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = mysqli_query($con, "SELECT * FROM doctorSpecilization LIMIT $start_from, $limit");
                            $cnt = $start_from + 1;
                            $has_records = false;
                            
                            if ($sql && mysqli_num_rows($sql) > 0) {
                                while ($row = mysqli_fetch_array($sql)) {
                                    $has_records = true;
                            ?>
                                    <tr>
                                        <td class="textcenter - doctor-specilization.php:381"><?php echo $cnt; ?></td>
                                        <td><?php echo htmlspecialchars($row['specilization - doctor-specilization.php:382']); ?></td>
                                        <td><?php echo htmlspecialchars($row['creationDate - doctor-specilization.php:383']); ?></td>
                                        <td><?php echo htmlspecialchars($row['updationDate - doctor-specilization.php:384']); ?></td>
                                        <td class="text-center">
                                            <a href="editdoctorspecialization.php?id=<?php echo $row['id']; ?> - doctor-specilization.php:386" class="action-btn btn-edit" title="تعديل">
                                                <i class="fas fa-edit me-1"></i> تعديل
                                            </a>
                                            <a href="?id=<?php echo $row['id']; ?>&del=delete - doctor-specilization.php:389" onClick="return confirm('هل أنت متأكد أنك تريد حذف هذا التخصص؟')" class="action-btn btn-delete" title="حذف">
                                                <i class="fas fa-trash-alt me-1"></i> حذف
                                            </a>
                                        </td>
                                    </tr>
                            <?php 
                                    $cnt++;
                                }
                            } else {
                                echo '<tr><td colspan="5 - doctor-specilization.php:398" class="text-center py-5">
                                    <div class="no-records">
                                        <i class="fas fa-folder-open"></i>
                                        <h4>لا توجد تخصصات مسجلة</h4>
                                        <p class="text-muted">لم يتم إضافة أي تخصصات حتى الآن</p>
                                    </div>
                                </td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php
                $result = mysqli_query($con, "SELECT COUNT(*) as total FROM doctorSpecilization");
                $row = mysqli_fetch_assoc($result);
                $total_records = $row['total'];
                $total_pages = ceil($total_records / $limit);
                
                if ($total_pages > 1):
                ?>
                <div class="pagination-container">
                    <div class="page-info">
                        عرض <?php echo min($cnt - 1, $limit); ?> من أصل <?php echo $total_records; ?> تخصص
                    </div>
                    
                    <nav>
                        <ul class="pagination">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="pagelink - doctor-specilization.php:429" href="?page=<?php echo $page - 1; ?>" aria-label="السابق">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php 
                            $start = max(1, $page - 2);
                            $end = min($total_pages, $page + 2);
                            
                            if ($start > 1) {
                                echo '<li class="pageitem"><a class="pagelink" href="?page=1">1</a></li> - doctor-specilization.php:440';
                                if ($start > 2) {
                                    echo '<li class="pageitem disabled"><span class="pagelink">...</span></li> - doctor-specilization.php:442';
                                }
                            }
                            
                            for ($i = $start; $i <= $end; $i++) {
                                $active = ($i == $page) ? 'active' : '';
                                echo '<li class="pageitem - doctor-specilization.php:448' . $active . '"><a class="page-link" href="?page=' . $i . '">' . $i . '</a></li>';
                            }
                            
                            if ($end < $total_pages) {
                                if ($end < $total_pages - 1) {
                                    echo '<li class="pageitem disabled"><span class="pagelink">...</span></li> - doctor-specilization.php:453';
                                }
                                echo '<li class="pageitem"><a class="pagelink" href="?page= - doctor-specilization.php:455' . $total_pages . '">' . $total_pages . '</a></li>';
                            }
                            ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="pagelink - doctor-specilization.php:461" href="?page=<?php echo $page + 1; ?>" aria-label="التالي">
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
		<?php include('include/setting.php'); ?>


    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // إخفاء رسالة التنبيه بعد 5 ثوانٍ
        setTimeout(() => {
            const alert = document.querySelector('.alert-message');
            if (alert) {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            }
        }, 5000);
        
        // التأكيد على الحذف
        document.querySelectorAll('.btn-delete').forEach(button => {
            button.addEventListener('click', function(e) {
                if (!confirm('هل أنت متأكد أنك تريد حذف هذا التخصص؟')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>
<?php } ?>