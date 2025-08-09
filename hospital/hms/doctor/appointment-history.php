<?php
session_start();
error_reporting(0);
include('include/config.php');

// التحقق من صحة تسجيل الدخول
if(!isset($_SESSION['id']) || strlen($_SESSION['id']) == 0) {
    header('location:logout.php');
    exit();
}

// استرجاع بيانات الطبيب
$docId = $_SESSION['id'];
$query = mysqli_query($con, "SELECT * FROM doctors WHERE id = '$docId'");
$doctor = mysqli_fetch_array($query);
$doctorName = $doctor['doctorName'];
$specialization = $doctor['specilization'];

// معالجة إلغاء الموعد
if(isset($_GET['cancel'])) {
    $id = intval($_GET['id']);
    $stmt = $con->prepare("UPDATE appointment SET doctorStatus='0' WHERE id=? AND doctorId=?");
    $stmt->bind_param("ii", $id, $_SESSION['id']);
    $stmt->execute();
    $_SESSION['msg'] = "تم إلغاء الموعد بنجاح";
    $_SESSION['msg_type'] = "success";
    header('Location: appointment-history.php');
    exit();
}

// استرجاع بيانات المواعيد
$sql = mysqli_query($con, "
    SELECT 
        u.fullName AS patientName,
        a.id,
        a.doctorSpecialization,
        a.consultancyFees,
        a.appointmentDate,
        a.appointmentTime,
        a.postingDate,
        a.userStatus,
        a.doctorStatus
    FROM appointment a
    JOIN users u ON u.id = a.userId 
    WHERE a.doctorId = '".$_SESSION['id']."'
    ORDER BY a.appointmentDate DESC, a.appointmentTime DESC
");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الطبيب | سجل المواعيد</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    :root {
        --primary: #2c5fa5;
        --secondary: #3a9e7f;
        --light: #f8f9fa;
        --dark: #343a40;
        --danger: #dc3545;
        --warning: #ffc107;
        --success: #28a745;
        --info: #17a2b8;
        --border-radius: 8px;
        --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Tajawal', sans-serif;
    }

    body {
        background-color: #f0f4f8;
        color: #333;
        line-height: 1.6;
    }

    .app-container {
        display: flex;
        min-height: 100vh;
    }

    /* الشريط الجانبي */
    .sidebar {
        width: 260px;
        background: linear-gradient(135deg, var(--primary), #1a4a8a);
        color: white;
        padding: 20px 0;
        transition: all 0.3s;
        box-shadow: 3px 0 15px rgba(0, 0, 0, 0.1);
    }

    .sidebar-header {
        padding: 0 20px 20px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .sidebar-header h2 {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 1.4rem;
    }

    .sidebar-menu {
        padding: 20px 0;
    }

    .sidebar-menu ul {
        list-style: none;
    }

    .sidebar-menu li a {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px 20px;
        color: rgba(255, 255, 255, 0.85);
        text-decoration: none;
        transition: all 0.2s;
        font-size: 1.05rem;
    }

    .sidebar-menu li a:hover,
    .sidebar-menu li a.active {
        background: rgba(255, 255, 255, 0.1);
        color: white;
        border-right: 4px solid var(--secondary);
    }

    .sidebar-menu li a i {
        width: 24px;
        text-align: center;
    }

    /* المحتوى الرئيسي */
    .main-content {
        flex: 1;
        padding: 20px;
        overflow-y: auto;
    }

    .header {
        background: white;
        border-radius: var(--border-radius);
        padding: 20px;
        margin-bottom: 25px;
        box-shadow: var(--box-shadow);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
    }

    .header h1 {
        color: var(--primary);
        font-size: 1.8rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .user-info {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .user-avatar {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: var(--primary);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        font-weight: bold;
    }

    .user-name {
        font-weight: 600;
        color: var(--dark);
    }

    .user-role {
        font-size: 0.9rem;
        color: var(--info);
    }

    .page-title {
        background: white;
        border-radius: var(--border-radius);
        padding: 20px;
        margin-bottom: 25px;
        box-shadow: var(--box-shadow);
    }

    .page-title h1 {
        color: var(--primary);
        font-size: 1.7rem;
        margin-bottom: 5px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .breadcrumb {
        display: flex;
        list-style: none;
        padding: 0;
        margin: 10px 0 0;
        color: #6c757d;
    }

    .breadcrumb li:not(:last-child)::after {
        content: ">";
        margin: 0 10px;
    }

    /* بطاقة المحتوى */
    .content-card {
        background: white;
        border-radius: var(--border-radius);
        padding: 25px;
        margin-bottom: 25px;
        box-shadow: var(--box-shadow);
    }

    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 1px solid #eee;
    }

    .card-title {
        color: var(--primary);
        font-size: 1.4rem;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .filters {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        min-width: 200px;
    }

    .filter-group label {
        margin-bottom: 5px;
        font-weight: 500;
        color: var(--dark);
    }

    .filter-group select,
    .filter-group input {
        padding: 10px 15px;
        border: 1px solid #ddd;
        border-radius: var(--border-radius);
        font-size: 1rem;
    }

    /* الجدول */
    .appointments-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 1rem;
    }

    .appointments-table th {
        background-color: #f1f7fd;
        color: var(--primary);
        text-align: right;
        padding: 15px;
        font-weight: 600;
        border-bottom: 2px solid #dee2e6;
    }

    .appointments-table td {
        padding: 14px 15px;
        border-bottom: 1px solid #eee;
        color: #555;
    }

    .appointments-table tr:hover td {
        background-color: #f8fafd;
    }

    .status-badge {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 500;
    }

    .status-active {
        background-color: rgba(40, 167, 69, 0.15);
        color: var(--success);
    }

    .status-cancelled {
        background-color: rgba(220, 53, 69, 0.15);
        color: var(--danger);
    }

    .status-pending {
        background-color: rgba(255, 193, 7, 0.15);
        color: var(--warning);
    }

    .action-btn {
        padding: 8px 15px;
        border-radius: var(--border-radius);
        font-size: 0.9rem;
        font-weight: 500;
        border: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s;
    }

    .btn-view {
        background-color: rgba(23, 162, 184, 0.1);
        color: var(--info);
    }

    .btn-view:hover {
        background-color: rgba(23, 162, 184, 0.2);
    }

    .btn-cancel {
        background-color: rgba(220, 53, 69, 0.1);
        color: var(--danger);
    }

    .btn-cancel:hover {
        background-color: rgba(220, 53, 69, 0.2);
    }

    .btn-completed {
        background-color: rgba(40, 167, 69, 0.1);
        color: var(--success);
    }

    .btn-completed:hover {
        background-color: rgba(40, 167, 69, 0.2);
    }

    /* رسالة التنبيه */
    .alert {
        padding: 15px 20px;
        border-radius: var(--border-radius);
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .alert-success {
        background-color: rgba(40, 167, 69, 0.15);
        color: var(--success);
        border-left: 4px solid var(--success);
    }

    .alert-danger {
        background-color: rgba(220, 53, 69, 0.15);
        color: var(--danger);
        border-left: 4px solid var(--danger);
    }

    .no-appointments {
        text-align: center;
        padding: 30px;
        color: #6c757d;
    }

    .no-appointments i {
        font-size: 3rem;
        margin-bottom: 15px;
        color: #ced4da;
    }

    /* الترقيم */
    .pagination {
        display: flex;
        justify-content: center;
        list-style: none;
        padding: 20px 0 10px;
        gap: 8px;
    }

    .pagination li a {
        display: block;
        padding: 8px 16px;
        border: 1px solid #ddd;
        border-radius: var(--border-radius);
        color: var(--primary);
        text-decoration: none;
        transition: all 0.2s;
    }

    .pagination li a:hover,
    .pagination li.active a {
        background-color: var(--primary);
        color: white;
        border-color: var(--primary);
    }

    /* تصميم متجاوب */
    @media (max-width: 992px) {
        .app-container {
            flex-direction: column;
        }

        .sidebar {
            width: 100%;
            height: auto;
        }

        .sidebar-menu ul {
            display: flex;
            overflow-x: auto;
            padding-bottom: 10px;
        }

        .sidebar-menu li {
            flex-shrink: 0;
        }

        .sidebar-menu li a {
            border-right: none;
            border-bottom: 4px solid transparent;
        }

        .sidebar-menu li a:hover,
        .sidebar-menu li a.active {
            border-right: none;
            border-bottom: 4px solid var(--secondary);
        }
    }

    @media (max-width: 768px) {
        .header {
            flex-direction: column;
            align-items: flex-start;
        }

        .filters {
            width: 100%;
        }

        .filter-group {
            min-width: 100%;
        }

        .appointments-table {
            display: block;
            overflow-x: auto;
        }

        .appointments-table td,
        .appointments-table th {
            min-width: 150px;
        }
    }
    </style>
</head>

<body>
    <div class="app-container">
        <!-- الشريط الجانبي -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-hospital"></i> نظام المواعيد الطبية</h2>
            </div>
            <nav class="sidebar-menu">
                <ul>
                    <li><a href="dashboard.php"><i class="fas fa-home"></i> لوحة التحكم</a></li>
                    <li><a href="appointment-history.php" class="active"><i class="fas fa-calendar-check"></i> سجل
                            المواعيد</a></li>
                    <li><a href="manage-patients.php"><i class="fas fa-user-injured"></i> إدارة المرضى</a></li>
                    <li><a href="medical-reports.php"><i class="fas fa-file-medical"></i> التقارير الطبية</a></li>
                    <li><a href="prescriptions.php"><i class="fas fa-prescription"></i> الوصفات الطبية</a></li>
                    <li><a href="settings.php"><i class="fas fa-cog"></i> الإعدادات</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a></li>
                </ul>
            </nav>
        </aside>

        <!-- المحتوى الرئيسي -->
        <main class="main-content">
            <!-- رأس الصفحة -->
            <header class="header">
                <h1><i class="fas fa-calendar-alt"></i> سجل المواعيد</h1>
                <div class="user-info">
                    <div class="user-avatar">د</div>
                    <div>
                        <div class="user-name">د. <?php echo $doctorName; ?></div>
                        <div class="user-role"><?php echo $specialization; ?></div>
                    </div>
                </div>
            </header>

            <!-- عنوان الصفحة -->
            <section class="page-title">
                <h1><i class="fas fa-history"></i> سجل المواعيد</h1>
                <ul class="breadcrumb">
                    <li>الطبيب</li>
                    <li>سجل المواعيد</li>
                </ul>
            </section>

            <!-- رسالة التنبيه -->
            <?php if(isset($_SESSION['msg'])) : ?>
            <div class="alert alert-<?php echo $_SESSION['msg_type'] ?? 'success'; ?>">
                <i
                    class="fas <?php echo ($_SESSION['msg_type'] ?? '') == 'danger' ? 'fa-exclamation-triangle' : 'fa-check-circle'; ?>"></i>
                <?php 
                    echo htmlentities($_SESSION['msg']); 
                    unset($_SESSION['msg']);
                    unset($_SESSION['msg_type']);
                ?>
            </div>
            <?php endif; ?>

            <!-- بطاقة المواعيد -->
            <div class="content-card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-list"></i> قائمة المواعيد</h2>
                    <div class="filters">
                        <div class="filter-group">
                            <label>حالة الموعد</label>
                            <select id="statusFilter">
                                <option value="all">جميع الحالات</option>
                                <option value="active">نشطة</option>
                                <option value="cancelled">ملغية</option>
                                <option value="pending">قيد الانتظار</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>التاريخ</label>
                            <input type="date" id="dateFilter">
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <?php if(mysqli_num_rows($sql) > 0): ?>
                    <table class="appointments-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>اسم المريض</th>
                                <th>التخصص</th>
                                <th>رسوم الاستشارة</th>
                                <th>تاريخ/وقت الموعد</th>
                                <th>تاريخ الإنشاء</th>
                                <th>الحالة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $cnt = 1;
                            while($row = mysqli_fetch_array($sql)) {
                                // تحديد حالة الموعد
                                $status = "";
                                $statusClass = "";
                                if($row['userStatus'] == 1 && $row['doctorStatus'] == 1) {
                                    $status = "نشط";
                                    $statusClass = "status-active";
                                } elseif($row['userStatus'] == 0) {
                                    $status = "ملغي من المريض";
                                    $statusClass = "status-cancelled";
                                } elseif($row['doctorStatus'] == 0) {
                                    $status = "ملغي منك";
                                    $statusClass = "status-cancelled";
                                } else {
                                    $status = "قيد الانتظار";
                                    $statusClass = "status-pending";
                                }
                            ?>
                            <tr>
                                <td><?php echo $cnt; ?></td>
                                <td><?php echo $row['patientName']; ?></td>
                                <td><?php echo $row['doctorSpecialization']; ?></td>
                                <td><?php echo $row['consultancyFees']; ?> ر.س</td>
                                <td><?php echo $row['appointmentDate']; ?> - <?php echo $row['appointmentTime']; ?></td>
                                <td><?php echo $row['postingDate']; ?></td>
                                <td><span class="status-badge <?php echo $statusClass; ?>"><?php echo $status; ?></span>
                                </td>
                                <td>
                                    <?php if($row['userStatus'] == 1 && $row['doctorStatus'] == 1) : ?>
                                    <a href="appointment-history.php?id=<?php echo $row['id']; ?>&cancel=update"
                                        class="action-btn btn-cancel"
                                        onclick="return confirm('هل أنت متأكد من رغبتك في إلغاء هذا الموعد؟')">
                                        <i class="fas fa-times"></i> إلغاء
                                    </a>
                                    <?php else : ?>
                                    <span class="text-muted">لا إجراء</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php 
                                $cnt++;
                            } 
                            ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="no-appointments">
                        <i class="fas fa-calendar-times"></i>
                        <h3>لا توجد مواعيد مسجلة</h3>
                        <p>لم يتم حجز أي موعد حتى الآن، سيظهر هنا أي موعد جديد يتم حجزه.</p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- الترقيم -->
                <ul class="pagination">
                    <li class="active"><a href="#">1</a></li>
                    <li><a href="#">2</a></li>
                    <li><a href="#">3</a></li>
                    <li><a href="#"><i class="fas fa-chevron-left"></i></a></li>
                </ul>
            </div>
        </main>
    </div>

    <script>
    // فلترة المواعيد حسب الحالة
    document.getElementById('statusFilter').addEventListener('change', function() {
        const status = this.value;
        const rows = document.querySelectorAll('.appointments-table tbody tr');

        rows.forEach(row => {
            const statusClass = row.querySelector('.status-badge').className;
            const showRow =
                status === 'all' ||
                (status === 'active' && statusClass.includes('status-active')) ||
                (status === 'cancelled' && statusClass.includes('status-cancelled')) ||
                (status === 'pending' && statusClass.includes('status-pending'));

            row.style.display = showRow ? '' : 'none';
        });
    });

    // فلترة حسب التاريخ
    document.getElementById('dateFilter').addEventListener('change', function() {
        const selectedDate = this.value;
        const rows = document.querySelectorAll('.appointments-table tbody tr');

        if (!selectedDate) {
            rows.forEach(row => row.style.display = '');
            return;
        }

        rows.forEach(row => {
            const dateCell = row.cells[4].textContent.split(' - ')[0];
            const [day, month, year] = dateCell.split('/');
            const formattedDate = `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`;

            row.style.display = formattedDate === selectedDate ? '' : 'none';
        });
    });
    </script>
</body>

</html>