<?php
session_start();
error_reporting(0);
include('include/config.php');
include('include/hardening.php');

if (empty($_SESSION['id'])) {
    header('location:logout.php');
    exit;
}

// حذف مستخدم
if (isset($_GET['del']) && isset($_GET['id'])) {
    $uid = (int)$_GET['id'];
    if ($uid > 0) {
        $stmt = $con->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $stmt->close();
        $_SESSION['msg'] = "تم حذف المستخدم بنجاح!";
        header("Location: manage-users.php");
        exit;
    }
}

// PAGINATION
$limit = 10; // عدد المستخدمين لكل صفحة
$page  = isset($_GET['page']) ? max((int)$_GET['page'], 1) : 1;
$start = ($page - 1) * $limit;

$total_query   = mysqli_query($con, "SELECT COUNT(*) AS total FROM users");
$total_result  = mysqli_fetch_assoc($total_query);
$total_users   = (int)$total_result['total'];
$pages         = max(1, (int)ceil($total_users / $limit));

$sql = mysqli_query($con, "SELECT id, fullName, address, city, gender, email, regDate, updationDate FROM users ORDER BY id ASC LIMIT $start, $limit");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>المسؤول | إدارة المستخدمين</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        body {font-family:'Tajawal',sans-serif;background:#f0f5f9;padding-top:60px;}
        .main-content{margin-right:20px;padding:20px;}
        .page-header{background:linear-gradient(90deg,#3498db,#4aa8e0);color:#fff;padding:25px;border-radius:10px;margin-bottom:30px;}
        .card{border:none;border-radius:10px;box-shadow:0 5px 15px rgba(0,0,0,0.05);margin-bottom:25px;background:#fff;}
        .card-header{background:#fff;padding:15px 20px;font-weight:600;display:flex;justify-content:space-between;align-items:center;}
        .table-container{overflow-x:auto;}
        .table{width:100%;border-collapse:separate;min-width:800px;}
        .table th{background:#3498db;color:#fff;font-weight:600;padding:15px;text-align:right;}
        .table td{padding:12px 15px;border-bottom:1px solid #eee;text-align:right;vertical-align:top;}
        .table tr:nth-child(even){background:#f8fafc;}
        .action-btn{padding:6px 12px;border-radius:5px;margin-left:5px;font-size:.85rem;text-decoration:none;display:inline-block}
        .btn-delete{background:rgba(231,76,60,.15);color:#e74c3c;border:1px solid rgba(231,76,60,.3);}
        .btn-delete:hover{background:rgba(231,76,60,.3);}
        .alert-message{padding:12px 20px;border-radius:8px;font-weight:600;margin:0 0 20px;background:rgba(39,174,96,.15);color:#27ae60;}
        .search-container{max-width:300px;position:relative;}
        .search-container input{padding-right:40px;border-radius:30px;border:1px solid #ddd;height:36px;width:100%;}
        .search-container i{position:absolute;left:15px;top:50%;transform:translateY(-50%);color:#6c757d;}
        .pagination{margin-top:20px;text-align:center;}
        .pagination a{display:inline-block;padding:8px 12px;margin:0 5px;border:1px solid #ccc;border-radius:5px;background:#fff;color:#3498db;text-decoration:none;}
        .pagination a.active{background:#3498db;color:#fff;}
        .badge{display:inline-block;padding:3px 8px;border-radius:12px;font-size:.8rem}
        .badge-male{background:#e8f4ff;color:#0d6efd;}
        .badge-female{background:#ffe8f3;color:#d63384;}
        @media (max-width:768px){.card-header{flex-direction:column;align-items:flex-start;gap:15px}.search-container{max-width:100%;width:100%;}}
    </style>
</head>
<body>

<?php include('include/header.php'); ?>
<?php include('include/sidebar.php'); ?>

<div class="main-content">
    <div class="page-header">
        <h1><i class="fas fa-users me-3"></i>إدارة المستخدمين</h1>
        <p>عرض وتعديل وحذف بيانات المستخدمين</p>
    </div>

    <div class="card">
        <div class="card-header">
            <span><i class="fas fa-table me-2"></i>قائمة المستخدمين</span>
            <div class="search-container">
                <input type="text" id="search" placeholder="ابحث عن مستخدم...">
                <i class="fas fa-search"></i>
            </div>
        </div>
        <div class="card-body">
            <?php if (isset($_SESSION['msg'])): ?>
                <div class="alertmessage - manage-users.php:93"><?php echo htmlspecialchars($_SESSION['msg']); ?></div>
                <?php unset($_SESSION['msg']); ?>
            <?php endif; ?>

            <div class="table-container">
                <table class="table" id="users-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>المستخدم</th>
                            <th>المعلومات</th>
                            <th>البريد الإلكتروني</th>
                            <th>تاريخ الإنشاء</th>
                            <th>تاريخ التحديث</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $cnt = $start + 1;
                        if ($sql && mysqli_num_rows($sql) > 0):
                            while ($row = mysqli_fetch_assoc($sql)):
                        ?>
                        <tr>
                            <td><?php echo $cnt; ?></td>
                            <td>
                                <div><?php echo htmlspecialchars($row['fullName - manage-users.php:119'] ?: '—'); ?></div>
                                <small><?php echo htmlspecialchars($row['city - manage-users.php:120'] ?: ''); ?></small>
                            </td>
                            <td>
                                <div><?php echo htmlspecialchars($row['address - manage-users.php:123'] ?: ''); ?></div>
                                <?php if (!empty($row['gender'])): ?>
                                    <span class="badge <?php echo ($row['gender'] === 'ذكر' || strtolower($row['gender']) === 'male') ? 'badgemale' : 'badgefemale'; ?> - manage-users.php:125">
                                        <?php echo htmlspecialchars($row['gender - manage-users.php:126']); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['email - manage-users.php:130'] ?: ''); ?></td>
                            <td><?php echo htmlspecialchars($row['regDate - manage-users.php:131'] ?: ''); ?></td>
                            <td><?php echo htmlspecialchars($row['updationDate - manage-users.php:132'] ?: ''); ?></td>
                            <td>
                                <a href="?id=<?php echo (int)$row['id']; ?>&del=1 - manage-users.php:134"
                                   onclick="return confirm('هل أنت متأكد أنك تريد حذف هذا المستخدم؟')"
                                   class="action-btn btn-delete">
                                    <i class="fas fa-trash-alt me-1"></i> حذف
                                </a>
                            </td>
                        </tr>
                        <?php
                            $cnt++;
                            endwhile;
                        else:
                        ?>
                        <tr><td colspan="7" class="text-center">لا توجد سجلات.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- PAGINATION -->
            <div class="pagination">
                <?php for ($i = 1; $i <= $pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?> - manage-users.php:155" class="<?php echo ($i == $page) ? 'active' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
            </div>
        </div>
    </div>
</div>

<?php include('include/setting.php'); ?>

<script>
    // إخفاء التنبيه
    setTimeout(() => {
        const alert = document.querySelector('.alert-message');
        if (alert) { alert.style.opacity = '0'; setTimeout(() => alert.remove(), 400); }
    }, 4000);

    // بحث بسيط في الجدول
    document.getElementById('search').addEventListener('input', function () {
        const term = this.value.toLowerCase();
        document.querySelectorAll('#users-table tbody tr').forEach(tr => {
            tr.style.display = tr.innerText.toLowerCase().includes(term) ? '' : 'none';
        });
    });
</script>
</body>
</html>
