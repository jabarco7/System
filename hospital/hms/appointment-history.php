<?php
session_start();
error_reporting(0);
include('include/config.php');

if (strlen($_SESSION['id'] == 0)) {
    header('location:logout.php'); exit;
}

$uid = intval($_SESSION['id']);

// إلغاء الموعد
if (isset($_GET['cancel']) && isset($_GET['id'])) {
    $aid = intval($_GET['id']);
    mysqli_query($con, "UPDATE appointment SET userStatus='0' WHERE id='$aid' AND userId='$uid'");
    $_SESSION['msg'] = "تم إلغاء موعدك!";
    $backPage = max(1, intval($_GET['page'] ?? 1));
    header("Location: appointment-history.php?page={$backPage}");
    exit;
}

/* Pagination */
$perPage = 8;
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

/* إجمالي السجلات */
$countRes = mysqli_query($con, "
    SELECT COUNT(*) AS c
    FROM appointment a
    JOIN doctors d ON d.id = a.doctorId
    WHERE a.userId = '$uid'
");
$totalRows = (int)mysqli_fetch_assoc($countRes)['c'];
$totalPages = max(1, (int)ceil($totalRows / $perPage));

/* بيانات الصفحة الحالية */
$sql = mysqli_query($con, "
    SELECT d.doctorName AS docname, a.*
    FROM appointment a
    JOIN doctors d ON d.id = a.doctorId
    WHERE a.userId = '$uid'
    ORDER BY a.id DESC
    LIMIT $perPage OFFSET $offset
");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>المستخدم | سجل المواعيد</title>

    <link href="http://fonts.googleapis.com/css?family=Tajawal:300,400,500,700" rel="stylesheet">
    <link rel="stylesheet" href="vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="vendor/fontawesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="vendor/themify-icons/themify-icons.min.css">
    <link href="vendor/animate.css/animate.min.css" rel="stylesheet">
    <link href="vendor/perfect-scrollbar/perfect-scrollbar.min.css" rel="stylesheet">
    <link href="vendor/switchery/switchery.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/plugins.css">
    <link rel="stylesheet" href="assets/css/themes/theme-1.css" id="skin_color" />

    <style>
        body{font-family:'Tajawal',sans-serif;background:#f0f5f9}
        .container-narrow{max-width:1000px;margin:24px auto}
        .page-head{
            background:linear-gradient(90deg,#3498db,#4aa8e0);
            color:#fff;border-radius:12px;padding:16px 18px;margin-bottom:16px;text-align:center
        }
        .card-clean{
            background:#fff;border-radius:14px;box-shadow:0 8px 20px rgba(0,0,0,.06);padding:18px
        }
        .table thead th{background:#f5f8fc}
        .badge-soft{border-radius:30px;padding:6px 10px;font-weight:600}
        .badge-active{background:#e6f7ef;color:#0f8f4e;border:1px solid #bfe9d1}
        .badge-user-cancel{background:#fff3cd;color:#856404;border:1px solid #ffeeba}
        .badge-doc-cancel{background:#fde2e1;color:#b21f2d;border:1px solid #f5c6cb}
        .alert-compact{border-radius:10px;padding:10px 12px}
        .pagination .page-link{border-radius:8px;margin:0 3px}
    </style>
</head>
<body>
<div id="app">
    <!-- لا Sidebar -->
    <?php include('include/header.php'); ?>

    <div class="container-narrow">
        <div class="page-head">
            <h4 class="m-0">سجل المواعيد</h4>
            <small class="opacity-75">استعرض مواعيدك وقم بالإلغاء عند الحاجة</small>
        </div>

        <?php if (!empty($_SESSION['msg'])): ?>
            <div class="alert alert-success alert-compact">
                <i class="fa fa-check-circle"></i> <?php echo htmlentities($_SESSION['msg']); ?>
            </div>
            <?php $_SESSION['msg'] = ""; ?>
        <?php endif; ?>

        <div class="card-clean">
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="appointmentsTable">
                    <thead>
                        <tr>
                            <th class="center">#</th>
                            <th>اسم الطبيب</th>
                            <th>التخصص</th>
                            <th>رسوم الاستشارة</th>
                            <th>تاريخ / وقت الموعد</th>
                            <th>تاريخ الإنشاء</th>
                            <th>الحالة</th>
                            <th class="text-center">الإجراء</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $rowNum = $offset + 1;
                        while ($row = mysqli_fetch_assoc($sql)):
                            if ($row['userStatus']==1 && $row['doctorStatus']==1) {
                                $statusHtml = '<span class="badge-soft badge-active">نشط</span>';
                            } elseif ($row['userStatus']==0 && $row['doctorStatus']==1) {
                                $statusHtml = '<span class="badge-soft badge-user-cancel">أُلغي بواسطتك</span>';
                            } else {
                                $statusHtml = '<span class="badge-soft badge-doc-cancel">أُلغي من الطبيب</span>';
                            }
                        ?>
                        <tr>
                            <td class="center"><?php echo $rowNum++; ?>.</td>
                            <td><?php echo htmlentities($row['docname']); ?></td>
                            <td><?php echo htmlentities($row['doctorSpecialization']); ?></td>
                            <td><?php echo htmlentities($row['consultancyFees']); ?></td>
                            <td><?php echo htmlentities($row['appointmentDate']); ?> / <?php echo htmlentities($row['appointmentTime']); ?></td>
                            <td><?php echo htmlentities($row['postingDate']); ?></td>
                            <td><?php echo $statusHtml; ?></td>
                            <td class="text-center">
                                <?php if ($row['userStatus']==1 && $row['doctorStatus']==1): ?>
                                    <a href="appointment-history.php?id=<?php echo (int)$row['id']; ?>&cancel=1&page=<?php echo $page; ?>"
                                       onclick="return confirm('هل تريد إلغاء هذا الموعد؟');"
                                       class="btn btn-sm btn-outline-danger">
                                        <i class="fa fa-times"></i> إلغاء
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">تم الإلغاء</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if ($totalRows === 0): ?>
                        <tr><td colspan="8" class="text-center text-muted">لا توجد مواعيد لعرضها.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- الترقيم -->
            <?php if ($totalPages > 1): ?>
                <nav class="d-flex justify-content-center mt-3">
                    <ul class="pagination">
                        <li class="page-item <?php echo ($page<=1)?'disabled':''; ?>">
                            <a class="page-link" href="appointment-history.php?page=<?php echo max(1,$page-1); ?>">السابق</a>
                        </li>
                        <?php
                        // نافذة صفحات صغيرة حول الصفحة الحالية
                        $start = max(1, $page-2);
                        $end   = min($totalPages, $page+2);
                        for ($p=$start; $p<=$end; $p++): ?>
                            <li class="page-item <?php echo ($p==$page)?'active':''; ?>">
                                <a class="page-link" href="appointment-history.php?page=<?php echo $p; ?>"><?php echo $p; ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo ($page>=$totalPages)?'disabled':''; ?>">
                            <a class="page-link" href="appointment-history.php?page=<?php echo min($totalPages,$page+1); ?>">التالي</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>

    <?php include('include/footer.php'); ?>
</div>

<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.min.js"></script>
<script src="vendor/modernizr/modernizr.js"></script>
<script src="vendor/jquery-cookie/jquery.cookie.js"></script>
<script src="vendor/perfect-scrollbar/perfect-scrollbar.min.js"></script>
<script src="vendor/switchery/switchery.min.js"></script>
<script src="assets/js/main.js"></script>
<script>
jQuery(function(){ if (window.Main) Main.init(); });
</script>
</body>
</html>
