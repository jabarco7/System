<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('include/config.php');

if (!isset($_SESSION['id']) || strlen($_SESSION['id']) == 0) {
    header('Location: logout.php');
    exit;
}

$uid = (int) $_SESSION['id'];

/* CSRF token */
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

/* ===== AJAX: تفاصيل موعد واحد ===== */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'appt' && isset($_GET['id'])) {
    header('Content-Type: application/json; charset=utf-8');

    $aid = (int)$_GET['id'];
    if ($aid <= 0) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'msg' => 'طلب غير صالح'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $sql = "
        SELECT 
            a.id,
            d.doctorName              AS docname,
            a.doctorSpecialization,
            a.consultancyFees,
            a.appointmentDate,
            a.appointmentTime,
            a.postingDate,
            a.userStatus,
            a.doctorStatus
        FROM appointment a
        JOIN doctors d ON d.id = a.doctorId
        WHERE a.userId = ? AND a.id = ?
        LIMIT 1
    ";
    if ($stmt = $con->prepare($sql)) {
        $stmt->bind_param("ii", $uid, $aid);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();

        if ($row) {
            echo json_encode(['ok' => true, 'data' => $row], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(404);
            echo json_encode(['ok' => false, 'msg' => 'الموعد غير موجود'], JSON_UNESCAPED_UNICODE);
        }
    } else {
        http_response_code(500);
        echo json_encode(['ok' => false, 'msg' => 'تعذر جلب البيانات حالياً'], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

/* إلغاء الموعد عبر POST */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_id'])) {
    $backPage = max(1, (int)($_POST['page'] ?? 1));

    $csrf_ok = hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '');
    if (!$csrf_ok) {
        $_SESSION['msg'] = "فشل التحقق الأمني. حاول مرة أخرى.";
        header("Location: appointment-history.php?page={$backPage}");
        exit;
    }

    $aid = (int) $_POST['cancel_id'];
    if ($aid > 0) {
        $stmt = $con->prepare("UPDATE appointment SET userStatus = 0 WHERE id = ? AND userId = ? AND userStatus = 1");
        if ($stmt) {
            $stmt->bind_param("ii", $aid, $uid);
            $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();
            $_SESSION['msg'] = ($affected > 0) ? "تم إلغاء موعدك!" : "لا يمكن إلغاء هذا الموعد.";
        } else {
            $_SESSION['msg'] = "تعذّر تنفيذ طلب الإلغاء حالياً.";
        }
    } else {
        $_SESSION['msg'] = "طلب غير صالح.";
    }

    header("Location: appointment-history.php?page={$backPage}");
    exit;
}

/* Pagination */
$perPage = 8;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

/* إجمالي السجلات */
$totalRows = 0;
if ($stmtCount = $con->prepare("SELECT COUNT(*) FROM appointment WHERE userId = ?")) {
    $stmtCount->bind_param("i", $uid);
    $stmtCount->execute();
    $stmtCount->bind_result($totalRows);
    $stmtCount->fetch();
    $stmtCount->close();
}
$totalPages = max(1, (int)ceil($totalRows / $perPage));

/* بيانات الصفحة الحالية */
$rows = [];
$stmt = $con->prepare("
    SELECT 
        a.id,
        d.doctorName              AS docname,
        a.doctorSpecialization,
        a.consultancyFees,
        a.appointmentDate,
        a.appointmentTime,
        a.postingDate,
        a.userStatus,
        a.doctorStatus
    FROM appointment a
    JOIN doctors d ON d.id = a.doctorId
    WHERE a.userId = ?
    ORDER BY a.id DESC
    LIMIT ? OFFSET ?
");
if ($stmt) {
    $stmt->bind_param("iii", $uid, $perPage, $offset);
    $stmt->execute();
    $stmt->bind_result(
        $a_id,
        $docname,
        $doctorSpecialization,
        $consultancyFees,
        $appointmentDate,
        $appointmentTime,
        $postingDate,
        $userStatus,
        $doctorStatus
    );
    while ($stmt->fetch()) {
        $rows[] = [
            'id'                   => (int)$a_id,
            'docname'              => $docname,
            'doctorSpecialization' => $doctorSpecialization,
            'consultancyFees'      => $consultancyFees,
            'appointmentDate'      => $appointmentDate,
            'appointmentTime'      => $appointmentTime,
            'postingDate'          => $postingDate,
            'userStatus'           => (int)$userStatus,
            'doctorStatus'         => (int)$doctorStatus,
        ];
    }
    $stmt->close();
}
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
        /* متغيرات عامة */
        :root{
          /* لو ارتفاع الهيدر عندك مختلف غيّر الرقم */
          --header-h: 64px;
          /* مقدار رفع السايدبار للأعلى */
          --sidebar-lift: 10px;
        }

        /* --- تنسيق الصفحة --- */
        body {
            font-family: 'Tajawal', sans-serif;
            background: #f0f5f9;
            margin: 0; padding: 0;
        }

        .container-narrow {
            max-width: 1000px;
            margin: 0 auto 24px !important; /* بدون هامش علوي */
        }

        .page-head {
            background: linear-gradient(90deg, #3498db, #4aa8e0);
            color: #fff;
            border-radius: 12px;
            padding: 16px 18px;
            margin: 0 0 16px;
            text-align: center;
            margin-top: 46px !important;   /* ارفع الشريط قليل */
            margin-bottom: 21px !important;/* وانزل المحتوى قليل */
        }

        .card-clean {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, .06);
            padding: 18px;
            margin-top: 8px !important;
        }

        .table thead th { background: #f5f8fc }
        .badge-soft { border-radius: 30px; padding: 6px 10px; font-weight: 600 }
        .badge-active { background: #e6f7ef; color: #0f8f4e; border: 1px solid #bfe9d1 }
        .badge-user-cancel { background: #fff3cd; color: #856404; border: 1px solid #ffeeba }
        .badge-doc-cancel { background: #fde2e1; color: #b21f2d; border: 1px solid #f5c6cb }
        .alert-compact { border-radius: 10px; padding: 10px 12px }
        .pagination .page-link { border-radius: 8px; margin: 0 3px }
        .btn-outline-danger { border: 1px solid #dc3545 }

        /* نافذة منبثقة */
        .rt-modal { position: fixed; inset: 0; z-index: 200000; display: none }
        .rt-modal.open { display: block }
        .rt-modal__backdrop { position: absolute; inset: 0; background: rgba(0,0,0,.55) }
        .rt-modal__dialog{
            position: relative; margin: 8vh auto;
            width: min(92vw, 680px); max-width: 680px;
            background:#fff; border-radius:12px; box-shadow:0 12px 40px rgba(0,0,0,.25);
            display:flex; flex-direction:column; overflow:hidden; direction:rtl; text-align:right;
            height:auto; max-height:80vh
        }
        @media (max-width:576px){ .rt-modal__dialog{ margin:5vh auto; width:94vw; max-height:88vh } }
        .rt-modal__header,.rt-modal__footer{ padding:12px 16px; border-bottom:1px solid #eee }
        .rt-modal__footer{ border-bottom:0; border-top:1px solid #eee; display:flex; gap:8px; justify-content:flex-start }
        .rt-modal__title{ margin:0; font-size:18px; font-weight:700 }
        .rt-modal__close{ position:absolute; top:8px; left:10px; border:0; background:transparent; font-size:26px; line-height:1; cursor:pointer; color:#333 }
        .rt-modal__body{ padding:14px 16px; overflow:auto; max-height:calc(80vh - 100px) }
        .rt-modal-open{ overflow:hidden }
        .info-label{ color:#555; font-weight:600; min-width:135px; display:inline-block; white-space:nowrap }

        /* إزالة أي إزاحة علوية عامة من الحاويات القياسية */
        .app-container, .main-content, .app-content, .content-wrapper, .wrap-content{
          margin-top:0 !important; padding-top:0 !important;
        }

        /* ===== رفع السايدبار قليلاً مع تعويض الارتفاع ===== */
        /* تنفع مع معظم قوالب HMS (id=sidebar أو .app-sidebar) */
        aside#sidebar.app-sidebar,
        #sidebar.app-sidebar,
        #sidebar{
          position: fixed !important;
          right: 0;
          top: calc(var(--header-h) - var(--sidebar-lift)) !important;
          height: calc(100vh - (var(--header-h) - var(--sidebar-lift))) !important;
          overflow-y: auto;
        }
        /* لو داخل السايدبار قسم بروفايل كان يتحرك، صفّره */
        #sidebar .user-profile{ margin-top: 0 !important; }

        /* على الشاشات الصغيرة عادة السايدبار يصير أوف-كانفاس، ما نرفع */
        @media (max-width: 991.98px){
          aside#sidebar.app-sidebar,
          #sidebar.app-sidebar,
          #sidebar{
            top: var(--header-h) !important;
            height: calc(100vh - var(--header-h)) !important;
          }
        }
    </style>
</head>

<body>
    <div class="app-container">
        <?php include('include/header.php'); ?>
        <?php include('include/sidebar.php'); ?>

        <div class="container-narrow">
            <div class="page-head">
                <h4 class="m-0">سجل المواعيد</h4>
                <small class="opacity-75">استعرض مواعيدك وقم بالإلغاء عند الحاجة</small>
            </div>

            <?php if (!empty($_SESSION['msg'])): ?>
                <div class="alert alert-info alert-compact">
                    <i class="fa fa-info-circle"></i>
                    <?php echo htmlentities($_SESSION['msg'], ENT_QUOTES, 'UTF-8'); ?>
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
                            if (count($rows) > 0):
                                foreach ($rows as $row):
                                    if ($row['userStatus'] === 1 && $row['doctorStatus'] === 1) {
                                        $statusHtml = '<span class="badge-soft badge-active">نشط</span>';
                                    } elseif ($row['userStatus'] === 0 && $row['doctorStatus'] === 1) {
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
                                            <button type="button" class="btn btn-info btn-sm btn-view" data-id="<?php echo (int)$row['id']; ?>">
                                                <i class="fa fa-eye"></i> عرض
                                            </button>
                                            <?php if ($row['userStatus'] === 1 && $row['doctorStatus'] === 1): ?>
                                                <form method="post" action="appointment-history.php" onsubmit="return confirm('هل تريد إلغاء هذا الموعد؟');" style="display:inline-block">
                                                    <input type="hidden" name="csrf" value="<?php echo $_SESSION['csrf']; ?>">
                                                    <input type="hidden" name="cancel_id" value="<?php echo (int)$row['id']; ?>">
                                                    <input type="hidden" name="page" value="<?php echo (int)$page; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="fa fa-times"></i> إلغاء
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php
                                endforeach;
                            else: ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted">لا توجد مواعيد لعرضها.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1): ?>
                    <nav class="d-flex justify-content-center mt-3">
                        <ul class="pagination">
                            <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="appointment-history.php?page=<?php echo max(1, $page - 1); ?>">السابق</a>
                            </li>
                            <?php
                            $start = max(1, $page - 2);
                            $end   = min($totalPages, $page + 2);
                            for ($p = $start; $p <= $end; $p++): ?>
                                <li class="page-item <?php echo ($p == $page) ? 'active' : ''; ?>">
                                    <a class="page-link" href="appointment-history.php?page=<?php echo $p; ?>"><?php echo $p; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="appointment-history.php?page=<?php echo min($totalPages, $page + 1); ?>">التالي</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- نافذة منبثقة (تفاصيل الموعد) -->
    <div id="rtApptModal" class="rt-modal" aria-hidden="true">
        <div class="rt-modal__backdrop" data-rt-close></div>
        <div class="rt-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="rtApptTitle">
            <button class="rt-modal__close" type="button" aria-label="إغلاق" data-rt-close>&times;</button>
            <div class="rt-modal__header">
                <h4 id="rtApptTitle" class="rt-modal__title"><i class="fa fa-calendar"></i> تفاصيل الموعد</h4>
            </div>
            <div class="rt-modal__body">
                <div id="ap-loading" class="text-center" style="padding:15px;">
                    <i class="fa fa-spinner fa-spin"></i> جاري التحميل...
                </div>
                <div id="ap-error" class="alert alert-danger" style="display:none;"></div>

                <div id="ap-content" style="display:none;">
                    <div class="row g-2">
                        <div class="col-sm-6">
                            <div><span class="info-label">اسم الطبيب:</span> <span id="apDoc"></span></div>
                        </div>
                        <div class="col-sm-6">
                            <div><span class="info-label">التخصص:</span> <span id="apSpec"></span></div>
                        </div>
                        <div class="col-sm-6">
                            <div><span class="info-label">الرسوم:</span> <span id="apFees"></span></div>
                        </div>
                        <div class="col-sm-6">
                            <div><span class="info-label">التاريخ:</span> <span id="apDate"></span></div>
                        </div>
                        <div class="col-sm-6">
                            <div><span class="info-label">الوقت:</span> <span id="apTime"></span></div>
                        </div>
                        <div class="col-sm-6">
                            <div><span class="info-label">تاريخ الإنشاء:</span> <span id="apPost"></span></div>
                        </div>
                        <div class="col-sm-12">
                            <div><span class="info-label">الحالة:</span> <span id="apStatus" class="badge-soft"></span></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="rt-modal__footer">
                <button type="button" class="btn btn-default" data-rt-close>إغلاق</button>
            </div>
        </div>
    </div>

    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.min.js"></script>
    <script src="vendor/modernizr/modernizr.js"></script>
    <script src="vendor/jquery-cookie/jquery.cookie.js"></script>
    <script src="vendor/perfect-scrollbar/perfect-scrollbar.min.js"></script>
    <script src="vendor/switchery/switchery.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script> jQuery(function(){ if (window.Main) Main.init(); }); </script>

    <script>
      jQuery(function($){
        var $overlay = $('#rtApptModal'),
            $load = $('#ap-loading'),
            $error = $('#ap-error'),
            $content = $('#ap-content'),
            $apDoc = $('#apDoc'),
            $apSpec = $('#apSpec'),
            $apFees = $('#apFees'),
            $apDate = $('#apDate'),
            $apTime = $('#apTime'),
            $apPost = $('#apPost'),
            $apStatus = $('#apStatus');

        function openOverlay(){ $('body').addClass('rt-modal-open'); $overlay.addClass('open').attr('aria-hidden','false'); }
        function closeOverlay(){ $('body').removeClass('rt-modal-open'); $overlay.removeClass('open').attr('aria-hidden','true'); }
        $(document).on('click','[data-rt-close]',closeOverlay);
        $(document).on('keydown',function(e){ if(e.key==='Escape') closeOverlay(); });

        function resetModal(){
          $load.show(); $error.hide().text(''); $content.hide();
          $apDoc.text(''); $apSpec.text(''); $apFees.text(''); $apDate.text(''); $apTime.text(''); $apPost.text('');
          $apStatus.removeClass('badge-active badge-user-cancel badge-doc-cancel').text('');
        }
        function statusLabel(u,d){
          if(u==1 && d==1) return {cls:'badge-active', txt:'نشط'};
          if(u==0 && d==1) return {cls:'badge-user-cancel', txt:'أُلغي بواسطتك'};
          return {cls:'badge-doc-cancel', txt:'أُلغي من الطبيب'};
        }
        function showAppt(id){
          resetModal(); openOverlay();
          $.getJSON(window.location.pathname, {ajax:'appt', id:id, _:Date.now()})
           .done(function(json){
              if(!json.ok) throw new Error(json.msg||'تعذر جلب البيانات');
              var d=json.data||{};
              $apDoc.text(d.docname||''); $apSpec.text(d.doctorSpecialization||'');
              $apFees.text(d.consultancyFees||''); $apDate.text(d.appointmentDate||'');
              $apTime.text(d.appointmentTime||''); $apPost.text(d.postingDate||'');
              var st=statusLabel(parseInt(d.userStatus,10), parseInt(d.doctorStatus,10));
              $apStatus.addClass(st.cls).text(st.txt);
              $load.hide(); $content.show();
           })
           .fail(function(xhr){
              $load.hide();
              var msg='حدث خطأ غير متوقع';
              try{ var j=JSON.parse(xhr.responseText); if(j&&j.msg) msg=j.msg; }catch(e){}
              $error.text(msg).show();
           });
        }
        $(document).on('click','.btn-view',function(){ showAppt($(this).data('id')); });
      });
    </script>
</body>
</html>
