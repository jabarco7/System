<?php
session_start();
error_reporting(0);
include('include/config.php');

if (strlen($_SESSION['id']) == 0) {
  header('location:logout.php');
  exit();
}

/* ===== AJAX: إرجاع بيانات المريض ===== */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'patient' && isset($_GET['id'])) {
  header('Content-Type: application/json; charset=utf-8');
  $id = (int)$_GET['id'];

  $stmt = mysqli_prepare($con, "SELECT ID, PatientName, PatientContno, PatientEmail, PatientGender, PatientAdd, CreationDate, UpdationDate FROM tblpatient WHERE ID=? LIMIT 1");
  mysqli_stmt_bind_param($stmt, "i", $id);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  $row = $res ? mysqli_fetch_assoc($res) : null;

  if ($row) {
    echo json_encode(['ok'=>true,'data'=>$row], JSON_UNESCAPED_UNICODE);
  } else {
    http_response_code(404);
    echo json_encode(['ok'=>false,'msg'=>'المريض غير موجود'], JSON_UNESCAPED_UNICODE);
  }
  exit();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <title>المسؤول | عرض المرضى</title>

  <link href="http://fonts.googleapis.com/css?family=Lato:300,400,400italic,600,700|Raleway:300,400,500,600,700|Crete+Round:400italic" rel="stylesheet" type="text/css" />
  <link rel="stylesheet" href="vendor/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="vendor/fontawesome/css/font-awesome.min.css">
  <link rel="stylesheet" href="vendor/themify-icons/themify-icons.min.css">
  <link href="vendor/animate.css/animate.min.css" rel="stylesheet" media="screen">
  <link href="vendor/perfect-scrollbar/perfect-scrollbar.min.css" rel="stylesheet" media="screen">
  <link href="vendor/switchery/switchery.min.css" rel="stylesheet" media="screen">
  <link href="vendor/bootstrap-touchspin/jquery.bootstrap-touchspin.min.css" rel="stylesheet" media="screen">
  <link href="vendor/select2/select2.min.css" rel="stylesheet" media="screen">
  <link href="vendor/bootstrap-datepicker/bootstrap-datepicker3.standalone.min.css" rel="stylesheet" media="screen">
  <link href="vendor/bootstrap-timepicker/bootstrap-timepicker.min.css" rel="stylesheet" media="screen">
  <link rel="stylesheet" href="assets/css/styles.css">
  <link rel="stylesheet" href="assets/css/plugins.css">
  <link rel="stylesheet" href="assets/css/themes/theme-1.css" id="skin_color" />

  <style>
    body { direction: rtl; text-align: right; }
    table thead th, table tbody td { text-align: right; }
    th.center, td.center { text-align: center; }

    /* ——— مودال مخصص عالي الموثوقية ——— */
    .rt-modal { position: fixed; inset: 0; z-index: 200000; display: none; }
    .rt-modal.open { display: block; }
    .rt-modal__backdrop { position: absolute; inset: 0; background: rgba(0,0,0,.55); }
    .rt-modal__dialog {
      position: relative; margin: 4vh auto; width: 95%; max-width: 1400px; height: 84vh;
      background: #fff; border-radius: 10px; box-shadow: 0 12px 40px rgba(0,0,0,.25);
      display: flex; flex-direction: column; overflow: hidden;
    }
    .rt-modal__header, .rt-modal__footer { padding: 12px 16px; border-bottom: 1px solid #eee; }
    .rt-modal__footer { border-bottom: 0; border-top: 1px solid #eee; display:flex; gap:8px; justify-content:flex-start; }
    .rt-modal__title { margin: 0; font-size: 18px; font-weight: 700; }
    .rt-modal__close {
      position: absolute; top: 8px; left: 10px; border: 0; background: transparent;
      font-size: 26px; line-height: 1; cursor: pointer; color: #333;
    }
    .rt-modal__body { padding: 16px; overflow: auto; }
    .rt-modal-open { overflow: hidden; } /* منع تمرير الخلفية */

    .info-label { color:#555; font-weight:600; min-width:120px; display:inline-block; }
    .text-muted-dash { color:#888; }
    .btn-xs { padding: 4px 8px; font-size: 12px; }
  </style>
</head>
<body>
<div id="app">
  <?php include('include/sidebar.php'); ?>
  <div class="app-content">
    <?php include('include/header.php'); ?>

    <div class="main-content">
      <div class="wrap-content container" id="container">
        <!-- العنوان -->
        <section id="page-title">
          <div class="row">
            <div class="col-sm-8">
              <h1 class="mainTitle">المسؤول | عرض المرضى</h1>
            </div>
            <ol class="breadcrumb">
              <li><span>المسؤول</span></li>
              <li class="active"><span>عرض المرضى</span></li>
            </ol>
          </div>
        </section>

        <div class="container-fluid container-fullw bg-white">
          <div class="row">
            <div class="col-md-12">
              <h4 class="tittle-w3-agileits mb-4">التقارير بين التواريخ</h4>
              <?php
                $fdate = $_POST['fromdate'] ?? '';
                $tdate = $_POST['todate'] ?? '';
              ?>
              <h5 class="text-primary" style="text-align:center">
                التقارير من <?php echo htmlspecialchars($fdate); ?> إلى <?php echo htmlspecialchars($tdate); ?>
              </h5>

              <table class="table table-hover" id="patients-table">
                <thead>
                  <tr>
                    <th class="center">#</th>
                    <th>اسم المريض</th>
                    <th>رقم هاتف المريض</th>
                    <th>جنس المريض</th>
                    <th>تاريخ الإنشاء</th>
                    <th>تاريخ التعديل</th>
                    <th>الإجراء</th>
                  </tr>
                </thead>
                <tbody>
                <?php
                $cnt = 1;
                if ($fdate && $tdate) {
                  $stmt = mysqli_prepare($con, "SELECT * FROM tblpatient WHERE DATE(CreationDate) BETWEEN ? AND ? ORDER BY ID DESC");
                  mysqli_stmt_bind_param($stmt, "ss", $fdate, $tdate);
                  mysqli_stmt_execute($stmt);
                  $rs = mysqli_stmt_get_result($stmt);

                  while ($row = mysqli_fetch_assoc($rs)) {
                    ?>
                    <tr>
                      <td class="center"><?php echo $cnt; ?>.</td>
                      <td class="hidden-xs"><?php echo htmlspecialchars($row['PatientName']); ?></td>
                      <td><?php echo htmlspecialchars($row['PatientContno']); ?></td>
                      <td><?php echo htmlspecialchars($row['PatientGender']); ?></td>
                      <td><?php echo htmlspecialchars($row['CreationDate']); ?></td>
                      <td><?php echo htmlspecialchars($row['UpdationDate']); ?></td>
                      <td>
                        <button type="button"
                                class="btn btn-primary btn-xs btn-view"
                                data-id="<?php echo (int)$row['ID']; ?>">
                          عرض
                        </button>
                      </td>
                    </tr>
                    <?php
                    $cnt++;
                  }
                } else {
                  echo '<tr><td colspan="7" class="text-center text-danger">لم يتم تحديد التواريخ.</td></tr>';
                }
                ?>
                </tbody>
              </table>
            </div>
          </div>
        </div><!-- /container-fullw -->

      </div>
    </div>
  </div>

  <?php include('include/footer.php'); ?>
  <?php include('include/setting.php'); ?>
</div>

<!-- ===== مودال مخصص (بدون Bootstrap) ===== -->
<div id="rtPatientModal" class="rt-modal" aria-hidden="true">
  <div class="rt-modal__backdrop" data-rt-close></div>
  <div class="rt-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="rtModalTitle">
    <button class="rt-modal__close" type="button" aria-label="إغلاق" data-rt-close>&times;</button>
    <div class="rt-modal__header">
      <h4 id="rtModalTitle" class="rt-modal__title"><i class="fa fa-id-card-o"></i> بيانات المريض</h4>
    </div>
    <div class="rt-modal__body">
      <div id="pm-loading" class="text-center" style="padding:15px;">
        <i class="fa fa-spinner fa-spin"></i> جاري التحميل...
      </div>
      <div id="pm-error" class="alert alert-danger" style="display:none;"></div>

      <div id="pm-content" style="display:none;">
        <div class="row">
          <div class="col-sm-6"><div><span class="info-label">الاسم:</span> <span id="pName"></span></div></div>
          <div class="col-sm-6"><div><span class="info-label">الجنس:</span> <span id="pGender" class="label"></span></div></div>
          <div class="col-sm-6"><div><span class="info-label">الهاتف:</span> <span id="pPhone"></span></div></div>
          <div class="col-sm-6"><div><span class="info-label">البريد:</span> <span id="pEmail" class="text-muted-dash"></span></div></div>
          <div class="col-sm-12" style="margin-top:8px;"><div><span class="info-label">العنوان:</span> <span id="pAddress" class="text-muted-dash"></span></div></div>
          <div class="col-sm-6" style="margin-top:8px;"><div><span class="info-label">تاريخ الإنشاء:</span> <span id="pCreated"></span></div></div>
          <div class="col-sm-6" style="margin-top:8px;"><div><span class="info-label">آخر تعديل:</span> <span id="pUpdated"></span></div></div>
        </div>
      </div>
    </div>
    <div class="rt-modal__footer">
      <a id="openFullPage" class="btn btn-default" target="_blank" style="display:none;">فتح الصفحة الكاملة</a>
      <button type="button" class="btn btn-primary" onclick="window.print();"><i class="fa fa-print"></i> طباعة</button>
      <button type="button" class="btn btn-default" data-rt-close>إغلاق</button>
    </div>
  </div>
</div>

<!-- سكربتات -->
<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.min.js"></script>
<script src="vendor/modernizr/modernizr.js"></script>
<script src="vendor/jquery-cookie/jquery.cookie.js"></script>
<script src="vendor/perfect-scrollbar/perfect-scrollbar.min.js"></script>
<script src="vendor/switchery/switchery.min.js"></script>
<script src="vendor/maskedinput/jquery.maskedinput.min.js"></script>
<script src="vendor/bootstrap-touchspin/jquery.bootstrap-touchspin.min.js"></script>
<script src="vendor/autosize/autosize.min.js"></script>
<script src="vendor/selectFx/classie.js"></script>
<script src="vendor/selectFx/selectFx.js"></script>
<script src="vendor/select2/select2.min.js"></script>
<script src="vendor/bootstrap-datepicker/bootstrap-datepicker.min.js"></script>
<script src="vendor/bootstrap-timepicker/bootstrap-timepicker.min.js"></script>
<script src="assets/js/main.js"></script>
<script src="assets/js/form-elements.js"></script>

<script>
jQuery(function($){
  Main.init();
  FormElements.init();

  // عناصر المودال المخصص
  var $overlay = $('#rtPatientModal');
  var $load    = $('#pm-loading');
  var $error   = $('#pm-error');
  var $content = $('#pm-content');

  var $pName   = $('#pName');
  var $pGender = $('#pGender');
  var $pPhone  = $('#pPhone');
  var $pEmail  = $('#pEmail');
  var $pAddr   = $('#pAddress');
  var $pCr     = $('#pCreated');
  var $pUp     = $('#pUpdated');
  var $openFull= $('#openFullPage');

  function openOverlay(){
    $('body').addClass('rt-modal-open');
    $overlay.addClass('open').attr('aria-hidden', 'false');
  }
  function closeOverlay(){
    $('body').removeClass('rt-modal-open');
    $overlay.removeClass('open').attr('aria-hidden', 'true');
  }
  $(document).on('click','[data-rt-close]', closeOverlay);
  $(document).on('keydown', function(e){ if(e.key === 'Escape') closeOverlay(); });

  function resetModal(){
    $load.show();
    $error.hide().text('');
    $content.hide();

    $pName.text(''); $pPhone.text(''); $pEmail.text(''); $pAddr.text('');
    $pCr.text(''); $pUp.text('');
    $pGender.text('').removeClass('label-info label-warning label-default');
    $openFull.hide().attr('href','#');
  }

  function setGenderBadge(val){
    var g = (val||'').trim();
    $pGender.text(g || 'غير محدد')
            .addClass(g==='ذكر' ? 'label-info' : (g==='أنثى' ? 'label-warning' : 'label-default'));
  }

  function showPatient(id){
    resetModal();
    openOverlay();

    $.getJSON(window.location.pathname, { ajax:'patient', id:id, _:Date.now() })
      .done(function(json){
        if(!json.ok){ throw new Error(json.msg || 'تعذر جلب البيانات'); }
        var d = json.data || {};
        $pName.text(d.PatientName || '');
        $pPhone.text(d.PatientContno || '');
        $pEmail.text(d.PatientEmail || '—');
        $pAddr.text(d.PatientAdd || '—');
        $pCr.text(d.CreationDate || '');
        $pUp.text(d.UpdationDate || '');
        setGenderBadge(d.PatientGender);

        $openFull.attr('href','view-patient.php?viewid='+encodeURIComponent(d.ID)).show();

        $load.hide();
        $content.show();
      })
      .fail(function(xhr){
        $load.hide();
        var msg = 'حدث خطأ غير متوقع';
        try { var j = JSON.parse(xhr.responseText); if(j && j.msg) msg = j.msg; } catch(e){}
        $error.text(msg).show();
      });
  }

  $(document).on('click', '.btn-view', function(){
    var id = $(this).data('id');
    showPatient(id);
  });
});
</script>
</body>
</html>
