<?php
session_start();

error_reporting(0);
include('include/config.php');

if (!isset($_SESSION['id']) || strlen($_SESSION['id']) == 0) {
  header('location:logout.php');
  exit;
}

$docId = (int)$_SESSION['id'];

/* استلام المعرفات */
$vid = isset($_GET['viewid']) ? (int)$_GET['viewid'] : 0;   // المتوقع من tblpatient.ID
$uid = isset($_GET['uid'])    ? (int)$_GET['uid']    : 0;   // قد يأتي من صفحة إدارة المرضى (users.id)

/* محاولة تحويل uid إلى viewid عبر الإيميل */
if ($vid <= 0 && $uid > 0) {
  if ($st = $con->prepare("
      SELECT p.ID
      FROM tblpatient p
      JOIN users u ON u.id = ?
      WHERE p.PatientEmail = u.email AND p.Docid = ?
      LIMIT 1
  ")) {
    $st->bind_param("ii", $uid, $docId);
    $st->execute();
    $st->bind_result($foundPid);
    if ($st->fetch()) {
      $vid = (int)$foundPid;
    }
    $st->close();
  }
  // إن تم العثور على السجل نحول بالرابط الصحيح
  if ($vid > 0) {
    header("Location: view-patient.php?viewid=".$vid);
    exit;
  }
}

/* لو ما في viewid صالح نرجع برسالة واضحة */
if ($vid <= 0) {
  // ملاحظة: تقدر تغيّر الرسالة حسب رغبتك
  header('Location: manage-patient.php?msg=no_patient_record');
  exit;
}

/* تأكيد أن السجل يخص هذا الطبيب */
$owns = false;
if ($st = $con->prepare("SELECT ID FROM tblpatient WHERE ID=? AND Docid=? LIMIT 1")) {
  $st->bind_param("ii", $vid, $docId);
  $st->execute();
  $st->store_result();
  $owns = $st->num_rows > 0;
  $st->close();
}
if (!$owns) { header('location:manage-patient.php?msg=not_owned'); exit; }

/* إضافة تاريخ طبي */
if (isset($_POST['submit'])) {
  $bp     = trim($_POST['bp'] ?? '');
  $bs     = trim($_POST['bs'] ?? '');
  $weight = trim($_POST['weight'] ?? '');
  $temp   = trim($_POST['temp'] ?? '');
  $pres   = trim($_POST['pres'] ?? '');

  if ($st = $con->prepare("INSERT INTO tblmedicalhistory
          (PatientID, BloodPressure, BloodSugar, Weight, Temperature, MedicalPres)
          VALUES (?,?,?,?,?,?)")) {
    $st->bind_param("isssss", $vid, $bp, $bs, $weight, $temp, $pres);
    if ($st->execute()) {
      // رجوع لنفس الصفحة بدل التنقّل لصفحة الإدارة
      header("Location: view-patient.php?viewid=".$vid."&saved=1");
      exit;
    } else {
      $save_error = true;
    }
    $st->close();
  }
}

/* بيانات المريض */
$patient = null;
if ($st = $con->prepare("SELECT * FROM tblpatient WHERE ID=? LIMIT 1")) {
  $st->bind_param("i", $vid);
  $st->execute();
  $res = $st->get_result();
  $patient = $res->fetch_assoc();
  $st->close();
}

/* تاريخه الطبي */
$history = [];
if ($st = $con->prepare("SELECT BloodPressure, Weight, BloodSugar, Temperature, MedicalPres, CreationDate
                         FROM tblmedicalhistory WHERE PatientID=? ORDER BY ID DESC")) {
  $st->bind_param("i", $vid);
  $st->execute();
  $res = $st->get_result();
  while ($r = $res->fetch_assoc()) $history[] = $r;
  $st->close();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <title>طبيب | إدارة المرضى</title>

  <!-- ملفاتك -->
  <link rel="stylesheet" href="vendor/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="vendor/fontawesome/css/font-awesome.min.css">
  <link rel="stylesheet" href="vendor/themify-icons/themify-icons.min.css">
  <link href="vendor/animate.css/animate.min.css" rel="stylesheet">
  <link href="vendor/perfect-scrollbar/perfect-scrollbar.min.css" rel="stylesheet">
  <link href="vendor/switchery/switchery.min.css" rel="stylesheet">
  <link href="vendor/bootstrap-touchspin/jquery.bootstrap-touchspin.min.css" rel="stylesheet">
  <link href="vendor/select2/select2.min.css" rel="stylesheet">
  <link href="vendor/bootstrap-datepicker/bootstrap-datepicker3.standalone.min.css" rel="stylesheet">
  <link href="vendor/bootstrap-timepicker/bootstrap-timepicker.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/styles.css">
  <link rel="stylesheet" href="assets/css/plugins.css">
  <link rel="stylesheet" href="assets/css/themes/theme-1.css" id="skin_color" />
  <link rel="stylesheet" href="assets/css/hms-unified.css?v=1.1">

  <style>
    :root{ --header-h:72px; --sidebar-w:280px; }
    body{ padding-top:var(--header-h); }
    .main-content{ margin-right:var(--sidebar-w); padding:20px; min-height:calc(100vh - var(--header-h)); }
    @media (max-width:991px){ .main-content{ margin-right:0; } }
    table.table-bordered th{ white-space:nowrap; }
  </style>
</head>
<body>
  <?php include('include/header.php'); ?>
  <?php include('include/sidebar.php'); ?>

  <div class="main-content">
    <div class="wrap-content container" id="container">
      <section id="page-title">
        <div class="row">
          <div class="col-sm-8">
            <h1 class="mainTitle">طبيب | إدارة المرضى</h1>
          </div>
          <ol class="breadcrumb">
            <li><span>طبيب</span></li>
            <li class="active"><span>إدارة المرضى</span></li>
          </ol>
        </div>
        <?php if (isset($_GET['saved'])): ?>
          <div class="alert alert-success" style="margin-top:10px;">تم حفظ التاريخ الطبي بنجاح.</div>
        <?php endif; ?>
        <?php if (!empty($save_error)): ?>
          <div class="alert alert-danger" style="margin-top:10px;">حدث خطأ أثناء الحفظ. حاول مرة أخرى.</div>
        <?php endif; ?>
      </section>

      <div class="container-fluid container-fullw bg-white">
        <div class="row">
          <div class="col-md-12">
            <h5 class="over-title margin-bottom-15">تفاصيل <span class="text-bold">المريض</span></h5>

            <?php if ($patient): ?>
              <table class="table table-bordered">
                <tr class="text-center">
                  <td colspan="4" style="font-size:18px;color:#0d6efd">تفاصيل المريض</td>
                </tr>
                <tr>
                  <th>اسم المريض</th>
                  <td><?= htmlspecialchars($patient['PatientName']) ?></td>
                  <th>البريد الإلكتروني</th>
                  <td><?= htmlspecialchars($patient['PatientEmail']) ?></td>
                </tr>
                <tr>
                  <th>رقم الجوال</th>
                  <td><?= htmlspecialchars($patient['PatientContno']) ?></td>
                  <th>العنوان</th>
                  <td><?= htmlspecialchars($patient['PatientAdd']) ?></td>
                </tr>
                <tr>
                  <th>الجنس</th>
                  <td><?= htmlspecialchars($patient['PatientGender']) ?></td>
                  <th>العمر</th>
                  <td><?= htmlspecialchars($patient['PatientAge']) ?></td>
                </tr>
                <tr>
                  <th>تاريخ طبي سابق</th>
                  <td><?= htmlspecialchars($patient['PatientMedhis']) ?></td>
                  <th>تاريخ التسجيل</th>
                  <td><?= htmlspecialchars($patient['CreationDate']) ?></td>
                </tr>
              </table>
            <?php else: ?>
              <div class="alert alert-warning">لا يوجد ملف مريض مطابق.</div>
            <?php endif; ?>

            <table class="table table-bordered">
              <tr class="text-center">
                <th colspan="7">التاريخ الطبي</th>
              </tr>
              <tr>
                <th>#</th>
                <th>ضغط الدم</th>
                <th>الوزن</th>
                <th>سكر الدم</th>
                <th>درجة الحرارة</th>
                <th>الوصفة الطبية</th>
                <th>تاريخ الزيارة</th>
              </tr>
              <?php if ($history): $i=1; foreach ($history as $h): ?>
                <tr>
                  <td><?= $i++ ?></td>
                  <td><?= htmlspecialchars($h['BloodPressure']) ?></td>
                  <td><?= htmlspecialchars($h['Weight']) ?></td>
                  <td><?= htmlspecialchars($h['BloodSugar']) ?></td>
                  <td><?= htmlspecialchars($h['Temperature']) ?></td>
                  <td><?= nl2br(htmlspecialchars($h['MedicalPres'])) ?></td>
                  <td><?= htmlspecialchars($h['CreationDate']) ?></td>
                </tr>
              <?php endforeach; else: ?>
                <tr><td colspan="7" class="text-center">لا يوجد تاريخ طبي بعد.</td></tr>
              <?php endif; ?>
            </table>

            <p class="text-center">
              <button class="btn btn-primary" data-toggle="modal" data-target="#medicalModal">إضافة التاريخ الطبي</button>
            </p>

            <!-- Modal -->
            <div class="modal fade" id="medicalModal" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog">
                <form method="post" class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title">إضافة التاريخ الطبي</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                      <span aria-hidden="true">&times;</span>
                    </button>
                  </div>
                  <div class="modal-body">
                    <div class="form-group">
                      <label>ضغط الدم</label>
                      <input name="bp" class="form-control" required>
                    </div>
                    <div class="form-group">
                      <label>سكر الدم</label>
                      <input name="bs" class="form-control" required>
                    </div>
                    <div class="form-group">
                      <label>الوزن</label>
                      <input name="weight" class="form-control" required>
                    </div>
                    <div class="form-group">
                      <label>درجة حرارة الجسم</label>
                      <input name="temp" class="form-control" required>
                    </div>
                    <div class="form-group">
                      <label>الوصفة الطبية</label>
                      <textarea name="pres" rows="5" class="form-control" required></textarea>
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">إغلاق</button>
                    <button type="submit" name="submit" class="btn btn-primary">حفظ</button>
                  </div>
                </form>
              </div>
            </div>
            <!-- /Modal -->

          </div>
        </div>
      </div>

    </div>
  </div>

  <?php include('include/footer.php'); ?>
  <?php include('include/setting.php'); ?>

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
    jQuery(function(){
      if (window.Main&&Main.init) Main.init();
      if (window.FormElements&&FormElements.init) FormElements.init();
    });
  </script>
</body>
</html>
