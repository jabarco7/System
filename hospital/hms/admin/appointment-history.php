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

/* ---------------- Helpers: فحص أسماء الجداول/الأعمدة ---------------- */

function current_db(mysqli $con): string {
    $r = $con->query("SELECT DATABASE() db");
    $row = $r ? $r->fetch_assoc() : null;
    return $row['db'] ?? '';
}
function tableExists(mysqli $con, string $table): bool {
    $db = current_db($con);
    if ($db === '') return false;
    $st = $con->prepare("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=? AND TABLE_NAME=?");
    $st->bind_param("ss", $db, $table);
    $st->execute(); $st->store_result();
    $ok = $st->num_rows > 0; $st->close();
    return $ok;
}
function pickTable(mysqli $con, array $candidates, string $fallback=''): string {
    foreach ($candidates as $t) if (tableExists($con, $t)) return $t;
    return $fallback;
}
function columnExists(mysqli $con, string $table, string $col): bool {
    $db = current_db($con);
    if ($db === '') return false;
    $st = $con->prepare("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND COLUMN_NAME=?");
    $st->bind_param("sss", $db, $table, $col);
    $st->execute(); $st->store_result();
    $ok = $st->num_rows > 0; $st->close();
    return $ok;
}
function pickColumn(mysqli $con, string $table, array $candidates): string {
    foreach ($candidates as $c) if (columnExists($con, $table, $c)) return $c;
    return '';
}

/* ---------------- تحديد الجداول/الأعمدة الفعلية ---------------- */

// جدول المواعيد (إذا اسمك مختلف جرّب candidates)
$APPT_TBL = pickTable($con, ['appointment', 'appointments', 'tblappointment'], 'appointment');

// جدول الأطباء
$DOC_TBL  = pickTable($con, ['doctors', 'tbl_doctors', 'doctor'], 'doctors');

// أعمدة الأطباء
$docIdCol   = pickColumn($con, $DOC_TBL, ['id','doctorId','docId']);
$docNameCol = pickColumn($con, $DOC_TBL, ['doctorName','name']);
$docSpecCol = pickColumn($con, $DOC_TBL, ['specialization','specilization']); // نحل اختلاف a/i

// أعمدة المواعيد
$apptIdCol     = pickColumn($con, $APPT_TBL, ['id','apptId']);
$apptUserIdCol = pickColumn($con, $APPT_TBL, ['userId','user_id','uid']);
$apptDocIdCol  = pickColumn($con, $APPT_TBL, ['doctorId','doctor_id','docId']);
$apptSpecCol   = pickColumn($con, $APPT_TBL, ['doctorSpecialization','doctor_specialization','specialization','specilization']);
$feesCol       = pickColumn($con, $APPT_TBL, ['consultancyFees','fees','fee']);
$dateCol       = pickColumn($con, $APPT_TBL, ['appointmentDate','date','apptDate']);
$timeCol       = pickColumn($con, $APPT_TBL, ['appointmentTime','time','apptTime']);
$postingCol    = pickColumn($con, $APPT_TBL, ['postingDate','created_at','createdAt','creationDate']);
$userStatusCol = pickColumn($con, $APPT_TBL, ['userStatus','user_status']);
$doctorStCol   = pickColumn($con, $APPT_TBL, ['doctorStatus','doctor_status']);

// تأكيد الأعمدة الأساسية
if ($APPT_TBL === '' || $DOC_TBL === '' || $apptIdCol === '' || $apptUserIdCol === '') {
    die('تعذّر تحديد بنية الجداول. تأكد من أسماء الجداول/الأعمدة.');
}

/* CSRF token */
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

/* ===== AJAX: تفاصيل موعد واحد ===== */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'appt' && isset($_GET['id'])) {
    header('Content-Type: application/json; charset=utf-8');
    $aid = (int)$_GET['id'];
    if ($aid <= 0) { http_response_code(400); echo json_encode(['ok'=>false,'msg'=>'طلب غير صالح'], JSON_UNESCAPED_UNICODE); exit; }

    // تعبيرات SELECT مرنة
    $sel_docname = $docNameCol ? "d.`$docNameCol`" : "''";
    $sel_spec    = $apptSpecCol ? "a.`$apptSpecCol`" : ($docSpecCol ? "d.`$docSpecCol`" : "''");
    $sel_fees    = $feesCol ? "a.`$feesCol`" : "''";
    $sel_date    = $dateCol ? "a.`$dateCol`" : "''";
    $sel_time    = $timeCol ? "a.`$timeCol`" : "''";
    $sel_post    = $postingCol ? "a.`$postingCol`" : "''";
    $sel_uSt     = $userStatusCol ? "a.`$userStatusCol`" : "1";
    $sel_dSt     = $doctorStCol ? "a.`$doctorStCol`" : "1";

    $sql = "
        SELECT 
            a.`$apptIdCol`                                 AS id,
            $sel_docname                                   AS docname,
            $sel_spec                                      AS doctorSpecialization,
            $sel_fees                                      AS consultancyFees,
            $sel_date                                      AS appointmentDate,
            $sel_time                                      AS appointmentTime,
            $sel_post                                      AS postingDate,
            $sel_uSt                                       AS userStatus,
            $sel_dSt                                       AS doctorStatus
        FROM `$APPT_TBL` a
        LEFT JOIN `$DOC_TBL` d ON ".($docIdCol && $apptDocIdCol ? "d.`$docIdCol` = a.`$apptDocIdCol`" : "1=0")."
        WHERE a.`$apptUserIdCol` = ? AND a.`$apptIdCol` = ?
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

/* إلغاء الموعد عبر POST (يعمل فقط إذا كان لدينا عمود userStatus) */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_id']) && $userStatusCol !== '') {
    $backPage = max(1, (int)($_POST['page'] ?? 1));

    $csrf_ok = hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '');
    if (!$csrf_ok) {
        $_SESSION['msg'] = "فشل التحقق الأمني. حاول مرة أخرى.";
        header("Location: appointment-history.php?page={$backPage}");
        exit;
    }

    $aid = (int) $_POST['cancel_id'];
    if ($aid > 0) {
        $stmt = $con->prepare("UPDATE `$APPT_TBL` SET `$userStatusCol` = 0 WHERE `$apptIdCol` = ? AND `$apptUserIdCol` = ? AND `$userStatusCol` = 1");
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
if ($stmtCount = $con->prepare("SELECT COUNT(*) FROM `$APPT_TBL` WHERE `$apptUserIdCol` = ?")) {
    $stmtCount->bind_param("i", $uid);
    $stmtCount->execute();
    $stmtCount->bind_result($totalRows);
    $stmtCount->fetch();
    $stmtCount->close();
}
$totalPages = max(1, (int)ceil($totalRows / $perPage));

/* بيانات الصفحة الحالية */
$rows = [];
$sel_docname = $docNameCol ? "d.`$docNameCol`" : "''";
$sel_spec    = $apptSpecCol ? "a.`$apptSpecCol`" : ($docSpecCol ? "d.`$docSpecCol`" : "''");
$sel_fees    = $feesCol ? "a.`$feesCol`" : "''";
$sel_date    = $dateCol ? "a.`$dateCol`" : "''";
$sel_time    = $timeCol ? "a.`$timeCol`" : "''";
$sel_post    = $postingCol ? "a.`$postingCol`" : "''";
$sel_uSt     = $userStatusCol ? "a.`$userStatusCol`" : "1";
$sel_dSt     = $doctorStCol ? "a.`$doctorStCol`" : "1";

$sqlList = "
    SELECT 
        a.`$apptIdCol`                                 AS id,
        $sel_docname                                   AS docname,
        $sel_spec                                      AS doctorSpecialization,
        $sel_fees                                      AS consultancyFees,
        $sel_date                                      AS appointmentDate,
        $sel_time                                      AS appointmentTime,
        $sel_post                                      AS postingDate,
        $sel_uSt                                       AS userStatus,
        $sel_dSt                                       AS doctorStatus
    FROM `$APPT_TBL` a
    LEFT JOIN `$DOC_TBL` d ON ".($docIdCol && $apptDocIdCol ? "d.`$docIdCol` = a.`$apptDocIdCol`" : "1=0")."
    WHERE a.`$apptUserIdCol` = ?
    ORDER BY a.`$apptIdCol` DESC
    LIMIT ? OFFSET ?
";
if ($stmt = $con->prepare($sqlList)) {
    $stmt->bind_param("iii", $uid, $perPage, $offset);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($res && ($row = $res->fetch_assoc())) {
        $row['id'] = (int)$row['id'];
        $row['userStatus']   = (int)$row['userStatus'];
        $row['doctorStatus'] = (int)$row['doctorStatus'];
        $rows[] = $row;
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
        :root{ --header-h:64px; --sidebar-lift:10px; }
        body{font-family:'Tajawal',sans-serif;background:#f0f5f9;margin:0;padding:0}
        .container-narrow{max-width:1000px;margin:0 auto 24px!important}
        .page-head{background:linear-gradient(90deg,#3498db,#4aa8e0);color:#fff;border-radius:12px;padding:16px 18px;margin:46px 0 21px!important;text-align:center}
        .card-clean{background:#fff;border-radius:14px;box-shadow:0 8px 20px rgba(0,0,0,.06);padding:18px;margin-top:8px!important}
        .table thead th{background:#f5f8fc}
        .badge-soft{border-radius:30px;padding:6px 10px;font-weight:600}
        .badge-active{background:#e6f7ef;color:#0f8f4e;border:1px solid #bfe9d1}
        .badge-user-cancel{background:#fff3cd;color:#856404;border:1px solid #ffeeba}
        .badge-doc-cancel{background:#fde2e1;color:#b21f2d;border:1px solid #f5c6cb}
        .alert-compact{border-radius:10px;padding:10px 12px}
        .pagination .page-link{border-radius:8px;margin:0 3px}
        .btn-outline-danger{border:1px solid #dc3545}
        .rt-modal{position:fixed;inset:0;z-index:200000;display:none}
        .rt-modal.open{display:block}
        .rt-modal__backdrop{position:absolute;inset:0;background:rgba(0,0,0,.55)}
        .rt-modal__dialog{position:relative;margin:8vh auto;width:min(92vw,680px);max-width:680px;background:#fff;border-radius:12px;box-shadow:0 12px 40px rgba(0,0,0,.25);display:flex;flex-direction:column;overflow:hidden;direction:rtl;text-align:right;height:auto;max-height:80vh}
        @media (max-width:576px){.rt-modal__dialog{margin:5vh auto;width:94vw;max-height:88vh}}
        .rt-modal__header,.rt-modal__footer{padding:12px 16px;border-bottom:1px solid #eee}
        .rt-modal__footer{border-bottom:0;border-top:1px solid #eee;display:flex;gap:8px;justify-content:flex-start}
        .rt-modal__title{margin:0;font-size:18px;font-weight:700}
        .rt-modal__close{position:absolute;top:8px;left:10px;border:0;background:transparent;font-size:26px;line-height:1;cursor:pointer;color:#333}
        .rt-modal__body{padding:14px 16px;overflow:auto;max-height:calc(80vh - 100px)}
        .rt-modal-open{overflow:hidden}
        .info-label{color:#555;font-weight:600;min-width:135px;display:inline-block;white-space:nowrap}
        .app-container,.main-content,.app-content,.content-wrapper,.wrap-content{margin-top:0!important;padding-top:0!important}
        aside#sidebar.app-sidebar,#sidebar.app-sidebar,#sidebar{position:fixed!important;right:0;top:calc(var(--header-h) - var(--sidebar-lift))!important;height:calc(100vh - (var(--header-h) - var(--sidebar-lift)))!important;overflow-y:auto}
        #sidebar .user-profile{margin-top:0!important}
        @media (max-width:991.98px){aside#sidebar.app-sidebar,#sidebar.app-sidebar,#sidebar{top:var(--header-h)!important;height:calc(100vh - var(--header-h))!important}}
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
                                    $u = (int)$row['userStatus'];
                                    $d = (int)$row['doctorStatus'];
                                    if ($u === 1 && $d === 1) {
                                        $statusHtml = '<span class="badge-soft badge-active">نشط</span>';
                                    } elseif ($u === 0 && $d === 1) {
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
                                            <?php if ($userStatusCol !== '' && $u === 1 && $d === 1): ?>
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
                        <div class="col-sm-6"><div><span class="info-label">اسم الطبيب:</span> <span id="apDoc"></span></div></div>
                        <div class="col-sm-6"><div><span class="info-label">التخصص:</span> <span id="apSpec"></span></div></div>
                        <div class="col-sm-6"><div><span class="info-label">الرسوم:</span> <span id="apFees"></span></div></div>
                        <div class="col-sm-6"><div><span class="info-label">التاريخ:</span> <span id="apDate"></span></div></div>
                        <div class="col-sm-6"><div><span class="info-label">الوقت:</span> <span id="apTime"></span></div></div>
                        <div class="col-sm-6"><div><span class="info-label">تاريخ الإنشاء:</span> <span id="apPost"></span></div></div>
                        <div class="col-sm-12"><div><span class="info-label">الحالة:</span> <span id="apStatus" class="badge-soft"></span></div></div>
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
