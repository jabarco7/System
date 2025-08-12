<?php
session_start();
error_reporting(0);
include('include/config.php');

if (empty($_SESSION['id'])) {
  header('location:logout.php');
  exit;
}

$docId = (int)$_SESSION['id'];

$vid = isset($_GET['viewid']) ? (int)$_GET['viewid'] : 0;
$uid = isset($_GET['uid'])    ? (int)$_GET['uid']    : 0;

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
  if ($vid > 0) {
    header("Location: view-patient.php?viewid=" . $vid);
    exit;
  }
}

if ($vid <= 0) {
  header('Location: manage-patient.php?msg=no_patient_record');
  exit;
}

$owns = false;
if ($st = $con->prepare("SELECT ID FROM tblpatient WHERE ID=? AND Docid=? LIMIT 1")) {
  $st->bind_param("ii", $vid, $docId);
  $st->execute();
  $st->store_result();
  $owns = $st->num_rows > 0;
  $st->close();
}
if (!$owns) {
  header('location:manage-patient.php?msg=not_owned');
  exit;
}

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
      header("Location: view-patient.php?viewid=" . $vid . "&saved=1");
      exit;
    } else {
      $save_error = true;
    }
    $st->close();
  }
}

$patient = null;
if ($st = $con->prepare("SELECT * FROM tblpatient WHERE ID=? LIMIT 1")) {
  $st->bind_param("i", $vid);
  $st->execute();
  $res = $st->get_result();
  $patient = $res->fetch_assoc();
  $st->close();
}

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
  <title>الطبيب | تفاصيل المريض</title>

  <!-- ملفات القالب -->
  <link rel="stylesheet" href="vendor/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="vendor/fontawesome/css/font-awesome.min.css">
  <link rel="stylesheet" href="vendor/themify-icons/themify-icons.min.css">
  <link href="vendor/animate.css/animate.min.css" rel="stylesheet">
  <link href="vendor/bootstrap-touchspin/jquery.bootstrap-touchspin.min.css" rel="stylesheet">
  <link href="vendor/bootstrap-datepicker/bootstrap-datepicker3.standalone.min.css" rel="stylesheet">
  <link href="vendor/bootstrap-timepicker/bootstrap-timepicker.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/hms-unified.css?v=1.3">
  <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">

  <style>
    :root {
      --header-h: 72px;
      --sidebar-w: 280px;
    }

    /* === هيدر القالب (بدون تغيير الحجم) === */
    .navbar.navbar-default.navbar-static-top {
      height: var(--header-h);
      min-height: var(--header-h);
      padding: 0 16px;
    }

    .navbar.navbar-default .container-fluid {
      height: 100%;
      display: flex;
      align-items: center;
      gap: 16px;
    }

    /* === السايدبار (نفس صفحة "مرضاي") + إزالة أي خلفيات بيضاء داخله === */
    aside#sidebar {
      position: fixed !important;
      right: 0 !important;
      top: var(--header-h) !important;
      width: var(--sidebar-w);
      height: calc(100vh - var(--header-h));
      overflow-y: auto;
      z-index: 2005 !important;
      padding-top: 0 !important;
      background: #0d1b2a !important;
      /* خلفية داكنة صلبة */
    }

    /* أي عنصر داخل السايدبار كان يكتسب خلفية/ظل/حدود من القالب نجعله شفاف */
    #sidebar .nav,
    #sidebar .nav-item,
    #sidebar .nav-link,
    #sidebar .panel,
    #sidebar .card,
    #sidebar .container,
    #sidebar .container-fluid {
      background: transparent !important;
      border: 0 !important;
      box-shadow: none !important;
    }

    #sidebar .user-profile {
      margin-top: 0;
    }

    /* روابط السايدبار */
    #sidebar .nav {
      padding: 8px 12px;
    }

    #sidebar .nav-item {
      margin: 4px 0;
    }

    #sidebar .nav-link {
      display: flex;
      align-items: center;
      gap: 10px;
      height: 44px;
      padding: 10px 14px;
      border-radius: 12px;
      color: #cfd7e6;
      transition: .15s;
    }

    #sidebar .nav-link:hover {
      background: #0f1a28;
      color: #fff;
    }

    #sidebar .nav-link.active {
      background: #121c2b;
      color: #fff;
    }

    /* === المحتوى: فقط إزاحة يسار السايدبار، لا تغيير أحجام === */
    .app-content,
    .main-content {
      margin-right: var(--sidebar-w) !important;
      padding-top: 0 !important;
      position: relative;
      z-index: 1 !important;
    }

    /* تحسين صغير للجداول */
    body,
    .app-content {
      font-family: 'Tajawal', sans-serif;
      font-size: 14px;
      line-height: 1.6;
    }

    .table.table-bordered th {
      white-space: nowrap;
    }
  </style>
</head>

<body>
  <?php include('include/header.php'); ?>
  <?php include('include/sidebar.php'); ?>

  <div class="app-content">
    <div class="page-head">
      <h1><i class="fa fa-user-injured"></i> تفاصيل المريض</h1>
      <div>
        <a class="btn btn-light btn-sm" href="manage-patient.php"><i class="fa fa-arrow-right"></i> رجوع لإدارة المرضى</a>
      </div>
    </div>

    <?php if (isset($_GET['saved'])): ?>
      <div class="alert alert-success" style="margin:0 20px 12px">تم حفظ التاريخ الطبي بنجاح.</div>
    <?php endif; ?>
    <?php if (!empty($save_error)): ?>
      <div class="alert alert-danger" style="margin:0 20px 12px">حدث خطأ أثناء الحفظ. حاول مرة أخرى.</div>
    <?php endif; ?>

    <div class="container-fullw">
      <div class="row g-3">
        <div class="col-lg-8">
          <div class="card">
            <h5 class="mb-3" style="font-weight:700">بيانات المريض</h5>
            <?php if ($patient): ?>
              <table class="table table-bordered m-0">
                <tbody>
                  <tr class="text-center">
                    <td colspan="4" style="font-size:18px;color:#0d6efd">تفاصيل المريض</td>
                  </tr>
                  <tr>
                    <th style="white-space:nowrap">اسم المريض</th>
                    <td><?= htmlspecialchars($patient['PatientName']) ?></td>
                    <th style="white-space:nowrap">البريد الإلكتروني</th>
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
                </tbody>
              </table>
            <?php else: ?>
              <div class="alert alert-warning m-0">لا يوجد ملف مريض مطابق.</div>
            <?php endif; ?>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="card">
            <h5 class="mb-3" style="font-weight:700">إضافة تاريخ طبي</h5>
            <p class="text-muted mb-2">أضف زيارة/وصفة جديدة للمريض.</p>
            <button class="btn btn-primary" data-toggle="modal" data-target="#medicalModal">
              <i class="fa fa-plus"></i> إضافة التاريخ الطبي
            </button>
          </div>

          <div class="card">
            <h5 class="mb-2" style="font-weight:700">ملاحظات</h5>
            <div class="text-muted">يمكنك تدوين الملاحظات ضمن “الوصفة الطبية”.</div>
          </div>
        </div>
      </div>
    </div>

    <div class="container-fullw">
      <div class="card">
        <h5 class="mb-3" style="font-weight:700">السجل الطبي</h5>
        <div class="table-responsive">
          <table class="table table-bordered">
            <thead>
              <tr class="text-center">
                <th>#</th>
                <th>ضغط الدم</th>
                <th>الوزن</th>
                <th>سكر الدم</th>
                <th>درجة الحرارة</th>
                <th>الوصفة الطبية</th>
                <th>تاريخ الزيارة</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($history): $i = 1;
                foreach ($history as $h): ?>
                  <tr>
                    <td><?= $i++ ?></td>
                    <td><?= htmlspecialchars($h['BloodPressure']) ?></td>
                    <td><?= htmlspecialchars($h['Weight']) ?></td>
                    <td><?= htmlspecialchars($h['BloodSugar']) ?></td>
                    <td><?= htmlspecialchars($h['Temperature']) ?></td>
                    <td><?= nl2br(htmlspecialchars($h['MedicalPres'])) ?></td>
                    <td><?= htmlspecialchars($h['CreationDate']) ?></td>
                  </tr>
                <?php endforeach;
              else: ?>
                <tr>
                  <td colspan="7" class="text-center">لا يوجد تاريخ طبي بعد.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
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
    // تأكيد تفعيل الرابط المناسب في السايدبار
    (function() {
      var sb = document.getElementById('sidebar');
      if (!sb) return;
      var link = sb.querySelector('a[href*="manage-patient.php"], a[href*="view-patient.php"]');
      if (link) link.classList.add('active');
    })();
  </script>

  <!-- Modal -->
  <div class="modal fade" id="medicalModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <form method="post" class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">إضافة التاريخ الطبي</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="إغلاق">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-group"><label>ضغط الدم</label><input name="bp" class="form-control" required></div>
          <div class="form-group"><label>سكر الدم</label><input name="bs" class="form-control" required></div>
          <div class="form-group"><label>الوزن</label><input name="weight" class="form-control" required></div>
          <div class="form-group"><label>درجة حرارة الجسم</label><input name="temp" class="form-control" required></div>
          <div class="form-group"><label>الوصفة الطبية</label><textarea name="pres" rows="5" class="form-control" required></textarea></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">إغلاق</button>
          <button type="submit" name="submit" class="btn btn-primary">حفظ</button>
        </div>
      </form>
    </div>
  </div>
</body>

</html>