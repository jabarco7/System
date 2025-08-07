<?php
session_start();
error_reporting(E_ALL);
include('include/config.php');

if (empty($_SESSION['id'])) {
    header('location:logout.php');
    exit;
}

/* إضافة تخصص */
if (isset($_POST['submit'])) {
    $doctorspecilization = trim($_POST['doctorspecilization'] ?? '');
    if ($doctorspecilization !== '') {
        $val = mysqli_real_escape_string($con, $doctorspecilization);
        $q = mysqli_query($con, "INSERT INTO doctorspecilization (specilization) VALUES ('$val')");
        $_SESSION['msg'] = $q ? "تمت إضافة تخصص الطبيب بنجاح!!" : ("خطأ أثناء الإضافة: ".mysqli_error($con));
        header("Location: doctor-specilization.php"); exit;
    } else {
        $_SESSION['msg'] = "الرجاء إدخال اسم التخصص.";
    }
}

/* حذف تخصص */
if (isset($_GET['del']) && isset($_GET['id'])) {
    $sid = (int)$_GET['id'];
    $q = mysqli_query($con, "DELETE FROM doctorspecilization WHERE id = $sid");
    $_SESSION['msg'] = $q ? "تم حذف البيانات !!" : ("خطأ في الحذف: ".mysqli_error($con));
    header("Location: doctor-specilization.php"); exit;
}

/* إعداد التصفح */
$limit = 5;
$page  = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
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
        :root{--primary:#3498db;--secondary:#2c3e50;--success:#27ae60;--danger:#e74c3c;--light:#f8f9fa;--dark:#343a40;--gray:#6c757d}
        body{font-family:'Tajawal',sans-serif;background:#f0f5f9;color:#333;padding-top:60px}
        .main-content{margin-right:20px;padding:20px}
        .page-header{background:linear-gradient(90deg,var(--primary),#4aa8e0);color:#fff;padding:25px 30px;border-radius:10px;margin-bottom:30px}
        .panel{background:#fff;border-radius:10px;box-shadow:0 5px 15px rgba(0,0,0,.05);margin-bottom:25px;overflow:hidden}
        .panel-heading{background:var(--primary);color:#fff;padding:15px 20px;font-weight:600;font-size:1.1rem}
        .panel-body{padding:20px}
        .form-control{border:1px solid #ddd;border-radius:8px;padding:12px 15px;transition:.3s}
        .form-control:focus{border-color:var(--primary);box-shadow:0 0 8px rgba(52,152,219,.3)}
        .btn-primary{background:linear-gradient(90deg,var(--primary),#4aa8e0);border:none;padding:10px 25px;border-radius:8px;font-weight:600;transition:.3s}
        .btn-primary:hover{transform:translateY(-2px);box-shadow:0 5px 15px rgba(52,152,219,.3)}
        .table-container{overflow-x:auto;border-radius:10px;box-shadow:0 3px 10px rgba(0,0,0,.05)}
        .table th{background:var(--primary);color:#fff;font-weight:600;padding:15px}
        .table td{padding:12px 15px;border-bottom:1px solid rgba(0,0,0,.05)}
        .table tr:nth-child(even){background:#f8fafc}
        .table tr:hover{background:rgba(52,152,219,.05)}
        .action-btn{padding:6px 12px;border-radius:5px;margin-left:5px;font-size:.85rem;transition:.3s;text-decoration:none;display:inline-flex;align-items:center;justify-content:center}
        .btn-edit{background:rgba(39,174,96,.15);color:var(--success);border:1px solid rgba(39,174,96,.3)}
        .btn-edit:hover{background:rgba(39,174,96,.3)}
        .btn-delete{background:rgba(231,76,60,.15);color:var(--danger);border:1px solid rgba(231,76,60,.3)}
        .btn-delete:hover{background:rgba(231,76,60,.3)}
        .alert-message{padding:12px 20px;border-radius:8px;font-weight:600;margin-bottom:20px;animation:fadeIn .5s;display:flex;align-items:center}
        .alert-success{background:rgba(39,174,96,.15);color:var(--success);border-left:4px solid var(--success)}
        .alert-error{background:rgba(231,76,60,.15);color:var(--danger);border-left:4px solid var(--danger)}
        @keyframes fadeIn{from{opacity:0;transform:translateY(-10px)}to{opacity:1;transform:translateY(0)}}
        .pagination-container{display:flex;justify-content:center;margin-top:30px;flex-wrap:wrap;background:#fff;padding:15px;border-radius:10px;box-shadow:0 3px 10px rgba(0,0,0,.08)}
        .pagination{display:flex;justify-content:center;flex-wrap:wrap;direction:rtl;margin:0;padding:0}
        .page-item{margin:3px}
        .page-item .page-link{color:var(--primary);border:1px solid #dee2e6;border-radius:50px;padding:8px 16px;background:#fff;font-weight:600;transition:.3s;min-width:40px;text-align:center;display:flex;align-items:center;justify-content:center}
        .page-item.active .page-link{background:linear-gradient(90deg,var(--primary),#4aa8e0);border-color:var(--primary);color:#fff;box-shadow:0 4px 10px rgba(52,152,219,.3)}
        .page-item.disabled .page-link{background:#f1f1f1;color:#ccc;cursor:not-allowed;border-color:#e0e0e0}
        .page-item .page-link:hover:not(.active):not(.disabled){background:rgba(52,152,219,.1);color:var(--primary);transform:translateY(-1px);box-shadow:0 2px 6px rgba(0,0,0,.08)}
        .no-records{text-align:center;padding:40px;color:var(--gray)}
        .no-records i{font-size:3rem;margin-bottom:15px;color:#d1d8e0}
        @media (max-width:768px){.main-content{margin-right:0}.pagination-container{flex-direction:column;gap:15px}.action-btn{margin-bottom:5px;display:block;width:100%;text-align:center}}
    </style>
</head>
<body>
<?php include('include/header.php'); ?>
<?php include('include/sidebar.php'); ?>

<div class="main-content">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <div><h1><i class="fas fa-stethoscope me-3"></i>إدارة تخصصات الأطباء</h1></div>
        </div>
    </div>

    <?php if(isset($_SESSION['msg'])): ?>
        <?php $isDelete = (strpos($_SESSION['msg'], 'حذف') !== false || strpos($_SESSION['msg'], 'خطأ') !== false); ?>
        <div class="alert-message <?= $isDelete ? 'alert-error' : 'alert-success' ?>">
            <i class="fas <?= $isDelete ? 'fa-exclamation-circle' : 'fa-check-circle' ?> me-2"></i>
            <?= htmlspecialchars($_SESSION['msg']); ?>
        </div>
        <?php unset($_SESSION['msg']); ?>
    <?php endif; ?>

    <div class="panel">
        <div class="panel-heading">
            <h5 class="panel-title"><i class="fas fa-plus-circle me-2"></i>إضافة تخصص جديد</h5>
        </div>
        <div class="panel-body">
            <form role="form" method="post">
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
                    // لاحظ: اسم الجدول الصحيح doctorspecilization
                    $sql = mysqli_query($con, "SELECT * FROM doctorspecilization ORDER BY id DESC LIMIT $start_from, $limit");
                    $cnt = $start_from + 1;
                    if ($sql && mysqli_num_rows($sql) > 0):
                        while ($row = mysqli_fetch_assoc($sql)):
                    ?>
                        <tr>
                            <td class="text-center"><?= $cnt ?></td>
                            <td><?= htmlspecialchars($row['specilization']) ?></td>
                            <td><?= htmlspecialchars($row['creationDate']) ?></td>
                            <td><?= htmlspecialchars($row['updationDate']) ?></td>
                            <td class="text-center">
                                <a href="editdoctorspecialization.php?id=<?= (int)$row['id'] ?>" class="action-btn btn-edit" title="تعديل">
    <i class="fas fa-edit me-1"></i> تعديل
</a>

                                <a href="?id=<?= (int)$row['id'] ?>&del=1" class="action-btn btn-delete" title="حذف"
                                   onclick="return confirm('هل أنت متأكد أنك تريد حذف هذا التخصص؟');">
                                    <i class="fas fa-trash-alt me-1"></i> حذف
                                </a>
                            </td>
                        </tr>
                    <?php
                            $cnt++;
                        endwhile;
                    else:
                    ?>
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <div class="no-records">
                                    <i class="fas fa-folder-open"></i>
                                    <h4>لا توجد تخصصات مسجلة</h4>
                                    <p class="text-muted">لم يتم إضافة أي تخصصات حتى الآن</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php
            $result = mysqli_query($con, "SELECT COUNT(*) AS total FROM doctorspecilization");
            $row = mysqli_fetch_assoc($result);
            $total_records = (int)$row['total'];
            $total_pages = max(1, (int)ceil($total_records / $limit));
            if ($total_pages > 1):
            ?>
            <div class="pagination-container">
                <div class="page-info">
                    عرض <?= min($limit, max(0, $cnt - 1 - $start_from)) ?> من أصل <?= $total_records ?> تخصص
                </div>
                <nav>
                    <ul class="pagination">
                        <li class="page-item <?= $page<=1?'disabled':'' ?>">
                            <a class="page-link" href="<?= $page<=1 ? '#' : '?page='.($page-1) ?>">&laquo;</a>
                        </li>
                        <?php
                        $start = max(1, $page - 2);
                        $end   = min($total_pages, $page + 2);
                        if ($start > 1) {
                            echo '<li class="pageitem"><a class="pagelink" href="?page=1">1</a></li> - doctor-specilization.php:204';
                            if ($start > 2) echo '<li class="pageitem disabled"><span class="pagelink">…</span></li> - doctor-specilization.php:205';
                        }
                        for ($i=$start; $i<=$end; $i++):
                        ?>
                            <li class="page-item <?= $i==$page?'active':'' ?>"><a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a></li>
                        <?php endfor;
                        if ($end < $total_pages) {
                            if ($end < $total_pages - 1) echo '<li class="pageitem disabled"><span class="pagelink">…</span></li> - doctor-specilization.php:212';
                            echo '<li class="pageitem"><a class="pagelink" href="?page= - doctor-specilization.php:213'.$total_pages.'">'.$total_pages.'</a></li>';
                        }
                        ?>
                        <li class="page-item <?= $page>=$total_pages?'disabled':'' ?>">
                            <a class="page-link" href="<?= $page>=$total_pages ? '#' : '?page='.($page+1) ?>">&raquo;</a>
                        </li>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include('include/setting.php'); ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
setTimeout(()=>{ document.querySelectorAll('.alert-message').forEach(a=>a.remove()); }, 5000);
</script>
</body>
</html>
