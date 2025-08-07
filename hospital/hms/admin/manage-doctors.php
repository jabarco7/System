<?php
session_start();
include('include/config.php');

if (empty($_SESSION['id'])) {
    header('location:logout.php'); exit();
}

/* CSRF token */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$CSRF = $_SESSION['csrf_token'];

/* حذف طبيب عبر POST + CSRF */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action']) && $_POST['action']==='delete') {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        $_SESSION['msg'] = 'خطأ في التحقق الأمني (CSRF). أعد المحاولة.'; header('location: manage-doctors.php'); exit();
    }
    $docid = (int)($_POST['id'] ?? 0);
    $stmt = $con->prepare("DELETE FROM doctors WHERE id = ?");
    $stmt->bind_param("i", $docid);
    if ($stmt->execute()) $_SESSION['msg'] = "تم حذف الطبيب بنجاح!";
    else $_SESSION['msg'] = "خطأ في حذف الطبيب: ".$con->error;
    $stmt->close();
    header('location: manage-doctors.php'); exit();
}

/* فلاتر البحث */
$q_name   = trim($_GET['name']  ?? '');
$q_email  = trim($_GET['email'] ?? '');
$q_spec   = trim($_GET['spec']  ?? '');
function clean_spec($s){ $s=preg_replace('/\badd-doctor\.php:\d+\s*/i','',(string)$s); return trim($s," -\t\n\r\0\x0B"); }

/* بناء شرط البحث */
$where = [];
$params = [];
$types  = '';

if ($q_name !== '') { $where[] = "doctorName LIKE ?"; $params[] = "%$q_name%"; $types.='s'; }
if ($q_email!== '') { $where[] = "docEmail  LIKE ?";  $params[] = "%$q_email%";$types.='s'; }
if ($q_spec !== '') { $where[] = "specilization = ?"; $params[] = $q_spec;     $types.='s'; }

$where_sql = $where ? ('WHERE '.implode(' AND ',$where)) : '';

/* تعداد السجلات للصفحة */
$sql_total = "SELECT COUNT(*) AS total FROM doctors $where_sql";
$stmt = $con->prepare($sql_total);
if ($where) { $stmt->bind_param($types, ...$params); }
$stmt->execute(); $res = $stmt->get_result();
$total_records = (int)($res->fetch_assoc()['total'] ?? 0);
$stmt->close();

/* ترقيم الصفحات */
$per_page = 5;
$page = max((int)($_GET['page'] ?? 1), 1);
$offset = ($page-1)*$per_page;
$total_pages = max((int)ceil($total_records/$per_page), 1);

/* جلب السجلات */
$sql_list = "SELECT * FROM doctors $where_sql ORDER BY id DESC LIMIT ? OFFSET ?";
$stmt = $con->prepare($sql_list);
if ($where) {
    $bindTypes = $types.'ii'; $params2 = array_merge($params, [ $per_page, $offset ]);
    $stmt->bind_param($bindTypes, ...$params2);
} else {
    $stmt->bind_param('ii', $per_page, $offset);
}
$stmt->execute(); $rows = $stmt->get_result();
$cnt = $offset+1;

/* قائمة التخصصات للفلاتر */
$specs = [];
$rsp = mysqli_query($con, "SELECT specilization FROM doctorspecilization ORDER BY specilization ASC");
while($r = mysqli_fetch_assoc($rsp)){ $specs[] = $r['specilization']; }
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إدارة الأطباء</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body{font-family:'Tajawal',sans-serif;background:#f0f5f9;padding-top:40px}
        .page-header{background:linear-gradient(90deg,#3498db,#4aa8e0);color:#fff;padding:25px 30px;border-radius:10px;margin-bottom:20px}
        .table th{background:#3498db;color:#fff;font-weight:600}
        .action-btn{padding:6px 12px;border-radius:6px;font-size:.85rem}
        .btn-delete{background:rgba(231,76,60,.12);color:#e74c3c;border:1px solid rgba(231,76,60,.3)}
        .btn-delete:hover{background:rgba(231,76,60,.2)}
        .badge-spec{background:rgba(52,152,219,.12);color:#3498db;border:1px solid rgba(52,152,219,.25);font-weight:600}
        .filter-card{background:#fff;border-radius:10px;padding:15px 15px;box-shadow:0 5px 15px rgba(0,0,0,.05);margin-bottom:15px}
        .pagination .page-link{color:#3498db}
        .pagination .page-item.active .page-link{background:#3498db;border-color:#3498db}
        .main-content{margin-right:20px;padding:20px}
    </style>
</head>
<body>
        <?php include('include/sidebar.php'); ?>
    <?php include('include/header.php'); ?>
<div class="main-content">
<div class="container ">
    <div class="page-header text-center">
        <h2><i class="fas fa-user-md me-2"></i>إدارة الأطباء</h2>
    </div>

    <?php if (!empty($_SESSION['msg'])): ?>
        <?php $isErr = (stripos($_SESSION['msg'], 'خطأ') !== false); ?>
        <div class="alert <?='alert-'.($isErr?'danger':'success')?> text-center">
            <?= htmlspecialchars($_SESSION['msg']); ?>
        </div>
        <?php unset($_SESSION['msg']); ?>
    <?php endif; ?>

    <!-- فلاتر البحث -->
    <div class="filter-card">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label">الاسم</label>
                <input type="text" name="name" value="<?= htmlspecialchars($q_name) ?>" class="form-control" placeholder="ابحث بالاسم">
            </div>
            <div class="col-md-4">
                <label class="form-label">الإيميل</label>
                <input type="text" name="email" value="<?= htmlspecialchars($q_email) ?>" class="form-control" placeholder="ابحث بالإيميل">
            </div>
            <div class="col-md-3">
                <label class="form-label">التخصص</label>
                <select name="spec" class="form-select">
                    <option value="">الكل</option>
                    <?php foreach($specs as $s): ?>
                        <option value="<?= htmlspecialchars($s) ?>" <?= $q_spec===$s?'selected':'' ?>>
                            <?= htmlspecialchars($s) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-1 d-grid">
                <button class="btn btn-primary"><i class="fas fa-search"></i></button>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-bordered align-middle">
            <thead>
            <tr>
                <th class="text-center" style="width:70px">#</th>
                <th>رقم الطبيب</th>
                <th>الإيميل</th>
                <th>التخصص</th>
                <th>اسم الطبيب</th>
                <th>تاريخ الإنشاء</th>
                <th class="text-center" style="width:120px">الإجراءات</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($rows && $rows->num_rows>0): ?>
                <?php while ($row = $rows->fetch_assoc()): ?>
                    <?php
                    $spec = clean_spec($row['specilization'] ?? 'غير محدد');
                    $name = $row['doctorName'] ?? 'غير معروف';
                    $email= $row['docEmail'] ?? 'غير متوفر';
                    $contact = $row['contactno'] ?? 'غير متوفر';
                    $date = $row['creationDate'] ? date('H:i:s Y-m-d', strtotime($row['creationDate'])) : 'غير محدد';
                    ?>
                    <tr>
                        <td class="text-center"><?= $cnt++ ?></td>
                        <td><?= htmlspecialchars($contact) ?></td>
                        <td><?= htmlspecialchars($email) ?></td>
                        <td><span class="badge rounded-pill badge-spec px-3 py-2"><?= htmlspecialchars($spec ?: 'غير محدد') ?></span></td>
                        <td><?= htmlspecialchars($name) ?></td>
                        <td><?= htmlspecialchars($date) ?></td>
                        <td class="text-center">
                            <form method="post" onsubmit="return confirm('هل أنت متأكد أنك تريد حذف هذا الطبيب؟');" style="display:inline">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($CSRF) ?>">
                                <button type="submit" class="action-btn btn-delete">
                                    <i class="fas fa-trash-alt me-1"></i> حذف
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="7" class="text-center text-muted py-4">لا توجد سجلات متاحة</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($total_pages > 1): ?>
        <nav class="mt-3">
            <ul class="pagination justify-content-center">
                <?php
                // بناء رابط يحتفظ بالفلاتر
                function q($p){ $q=$_GET; $q['page']=$p; return '?'.http_build_query($q); }
                ?>
                <li class="page-item <?= $page<=1?'disabled':'' ?>">
                    <a class="page-link" href="<?= $page<=1?'#':q($page-1) ?>">&laquo; السابق</a>
                </li>
                <?php for($i=1;$i<=$total_pages;$i++): ?>
                    <li class="page-item <?= $i==$page?'active':'' ?>"><a class="page-link" href="<?= q($i) ?>"><?= $i ?></a></li>
                <?php endfor; ?>
                <li class="page-item <?= $page>=$total_pages?'disabled':'' ?>">
                    <a class="page-link" href="<?= $page>=$total_pages?'#':q($page+1) ?>">التالي &raquo;</a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
</div>
</div> <!-- end of container -->

</body>
</html>
