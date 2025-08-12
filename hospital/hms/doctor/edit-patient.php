<?php
// doctor/edit-patient.php
session_start();
error_reporting(0);
include('include/config.php');

if (empty($_SESSION['id'])) {
  header('location:logout.php');
  exit;
}

$docId = (int)$_SESSION['id'];
$eid   = isset($_GET['editid']) ? (int)$_GET['editid'] : 0;
$uid   = isset($_GET['uid'])    ? (int)$_GET['uid']    : 0;

/**
 * لو الصفحة فُتحت بـ uid فقط: حاول إيجاد ملف المريض لنفس الطبيب عبر البريد،
 * وإن لم يوجد أنشئ ملفاً تلقائياً ثم أعد التوجيه بـ editid.
 * عدّل اسم عمود البريد في users إن كان مختلفاً (u.email).
 */
if ($eid <= 0 && $uid > 0) {
  // هل يوجد ملف مسبق؟
  if ($st = $con->prepare("
      SELECT p.ID
      FROM tblpatient p
      JOIN users u ON u.id = ?
      WHERE p.PatientEmail = u.email AND p.Docid = ?
      LIMIT 1
  ")) {
    $st->bind_param('ii', $uid, $docId);
    $st->execute();
    $st->bind_result($pid);
    if ($st->fetch()) { // موجود
      $st->close();
      header('Location: edit-patient.php?editid='.(int)$pid);
      exit;
    }
    $st->close();
  }

  // لا يوجد ملف -> أنشئ واحدًا من users
  $usr = null;
  if ($st = $con->prepare("SELECT fullName, email, gender, address FROM users WHERE id=? LIMIT 1")) {
    $st->bind_param('i', $uid);
    $st->execute();
    $res = $st->get_result();
    $usr = $res ? $res->fetch_assoc() : null;
    $st->close();
  }

  if ($usr) {
    $pname = trim($usr['fullName'] ?? '');
    $pemail= trim($usr['email'] ?? '');
    $pg    = trim($usr['gender'] ?? '');
    $pg    = (mb_strtolower($pg,'UTF-8')==='female')?'انثى':((mb_strtolower($pg,'UTF-8')==='male')?'ذكر':($pg?:'ذكر'));
    $padd  = trim($usr['address'] ?? '');
    $pcont = ''; // أضف رقم الهاتف لو موجود في users

    if ($st = $con->prepare("
        INSERT INTO tblpatient (Docid, PatientName, PatientEmail, PatientGender, PatientContno, PatientAdd)
        VALUES (?, ?, ?, ?, ?, ?)
    ")) {
      $st->bind_param('isssss', $docId, $pname, $pemail, $pg, $pcont, $padd);
      if ($st->execute()) {
        $newId = (int)$con->insert_id;
        $st->close();
        header('Location: edit-patient.php?editid='.$newId);
        exit;
      }
      $st->close();
    }
  }

  header('Location: manage-patient.php?msg='.urlencode('تعذر إنشاء ملف المريض تلقائيًا'));
  exit;
}

// الآن يجب أن يتوفر editid صالح
if ($eid <= 0) {
  header('Location: manage-patient.php?msg='.urlencode('اختر مريضاً أولاً'));
  exit;
}

// تأكيد ملكية السجل للطبيب الحالي
$owns = false;
if ($st = $con->prepare("SELECT ID FROM tblpatient WHERE ID=? AND Docid=? LIMIT 1")) {
  $st->bind_param('ii', $eid, $docId);
  $st->execute(); $st->store_result();
  $owns = $st->num_rows > 0;
  $st->close();
}
if (!$owns) {
  header('Location: manage-patient.php?msg='.urlencode('هذا السجل لا يخص حسابك'));
  exit;
}

// معالجة الحفظ
if (isset($_POST['submit'])) {
  $patname    = trim($_POST['patname'] ?? '');
  $patcontact = trim($_POST['patcontact'] ?? '');
  $patemail   = trim($_POST['patemail'] ?? '');
  $gender     = trim($_POST['gender'] ?? '');
  $pataddress = trim($_POST['pataddress'] ?? '');
  $patage     = trim($_POST['patage'] ?? '');
  $medhis     = trim($_POST['medhis'] ?? '');

  if ($patname==='' || $gender==='' || $patage==='') {
    echo "<script>alert('يرجى تعبئة الحقول المطلوبة');</script>";
  } else {
    if ($st = $con->prepare("UPDATE tblpatient SET
          PatientName=?, PatientContno=?, PatientEmail=?, PatientGender=?, PatientAdd=?, PatientAge=?, PatientMedhis=?
        WHERE ID=? AND Docid=?")) {
      $st->bind_param('ssssssiii',
        $patname, $patcontact, $patemail, $gender, $pataddress, $patage, $medhis, $eid, $docId
      );
      $ok = $st->execute();
      $st->close();

      if ($ok) {
        echo "<script>alert('تم تحديث معلومات المريض بنجاح');</script>";
        header('Location: manage-patient.php'); exit;
      } else {
        echo "<script>alert('تعذر التحديث حالياً');</script>";
      }
    } else {
      echo "<script>alert('خطأ داخلي أثناء التحديث');</script>";
    }
  }
}

// جلب بيانات المريض لعرضها في الفورم
$patient = null;
if ($st = $con->prepare("SELECT ID, PatientName, PatientContno, PatientEmail, PatientGender, PatientAdd, PatientAge, PatientMedhis, CreationDate
                         FROM tblpatient WHERE ID=? AND Docid=? LIMIT 1")) {
  $st->bind_param('ii', $eid, $docId);
  $st->execute();
  $res = $st->get_result();
  $patient = $res ? $res->fetch_assoc() : null;
  $st->close();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <title>طبيب | تعديل مريض</title>

  <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/styles.css">
  <link rel="stylesheet" href="assets/css/plugins.css">
  <link rel="stylesheet" href="assets/css/themes/theme-1.css" id="skin_color" />

</head>
<body>
<div id="app">
          <?php include('include/header.php'); ?>

  <?php include('include/sidebar.php'); ?>
  <div class="app-content">

    <div class="page-head">
      <h1><i class="fa fa-user"></i> تعديل بيانات المريض</h1>
      <a href="manage-patient.php" class="btn btn-outline btn-sm"><i class="fa fa-arrow-right"></i> رجوع</a>
    </div>

    <div class="container-fluid container-fullw bg-white">
      <div class="row">
        <div class="col-lg-8 col-md-12">
          <div class="panel panel-white">
            <div class="panel-heading"><h5 class="panel-title">تعديل مريض</h5></div>
            <div class="panel-body">

              <?php if (!$patient): ?>
                <div class="alert alert-warning">السجل غير موجود.</div>
              <?php else: ?>
              <form method="post" autocomplete="off">
                <div class="row">
                  <div class="col-md-6">
                    <label class="form-label">اسم المريض</label>
                    <div class="input-group">
                      <span class="input-group-addon"><i class="fa fa-user"></i></span>
                      <input type="text" name="patname" class="form-control" value="<?php echo htmlspecialchars($patient['PatientName']); ?>" required>
                    </div>
                  </div>

                  <div class="col-md-6">
                    <label class="form-label">رقم الاتصال</label>
                    <div class="input-group">
                      <span class="input-group-addon"><i class="fa fa-phone"></i></span>
                      <input type="text" name="patcontact" class="form-control" value="<?php echo htmlspecialchars($patient['PatientContno']); ?>" maxlength="15" pattern="[0-9]+" required>
                    </div>
                  </div>

                  <div class="col-md-6" style="margin-top:12px">
                    <label class="form-label">البريد الإلكتروني</label>
                    <div class="input-group">
                      <span class="input-group-addon"><i class="fa fa-envelope"></i></span>
                      <input type="email" name="patemail" class="form-control" value="<?php echo htmlspecialchars($patient['PatientEmail']); ?>" readonly>
                    </div>
                  </div>

                  <div class="col-md-6" style="margin-top:12px">
                    <label class="form-label d-block">الجنس</label>
                    <?php $g = $patient['PatientGender']; $isMale = ($g==='ذكر'); $isFemale = ($g==='انثى'); ?>
                    <div class="btn-group gender-group" data-toggle="buttons">
                      <label class="btn <?php echo $isMale ? 'active' : ''; ?>">
                        <input type="radio" name="gender" value="ذكر" <?php echo $isMale ? 'checked' : ''; ?>> ذكر
                      </label>
                      <label class="btn <?php echo $isFemale ? 'active' : ''; ?>">
                        <input type="radio" name="gender" value="انثى" <?php echo $isFemale ? 'checked' : ''; ?>> انثى
                      </label>
                    </div>
                  </div>

                  <div class="col-md-12" style="margin-top:12px">
                    <label class="form-label">عنوان المريض</label>
                    <textarea name="pataddress" class="form-control" rows="3" required><?php echo htmlspecialchars($patient['PatientAdd']); ?></textarea>
                  </div>

                  <div class="col-md-6" style="margin-top:12px">
                    <label class="form-label">عمر المريض</label>
                    <div class="input-group">
                      <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                      <input type="text" name="patage" class="form-control" value="<?php echo htmlspecialchars($patient['PatientAge']); ?>" required>
                    </div>
                  </div>

                  <div class="col-md-6" style="margin-top:12px">
                    <label class="form-label">تاريخ الإنشاء</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($patient['CreationDate']); ?>" readonly>
                  </div>

                  <div class="col-md-12" style="margin-top:12px">
                    <label class="form-label">التاريخ المرضي</label>
                    <textarea name="medhis" class="form-control" rows="4" placeholder="ادخل التاريخ الطبي للمريض (إن وجد)"><?php echo htmlspecialchars($patient['PatientMedhis']); ?></textarea>
                  </div>
                </div>

                <div class="text-end" style="margin-top:18px">
                  <button type="submit" name="submit" class="btn btn-primary"><i class="fa fa-save"></i> حفظ التعديلات</button>
                  <a href="view-patient.php?viewid=<?php echo (int)$patient['ID']; ?>" class="btn btn-outline">إلغاء</a>
                </div>
              </form>
              <?php endif; ?>

            </div>
          </div>
        </div>

        <div class="col-lg-4 col-md-12">
          <div class="panel panel-white" style="border-radius:12px">
            <div class="panel-heading"><h5 class="panel-title">معلومات</h5></div>
            <div class="panel-body">
              <ul class="list-unstyled" style="line-height:1.9">
                <li>• إن فتحت الصفحة بـ <code>uid</code> ولم يوجد ملف، يتم إنشاؤه تلقائيًا.</li>
                <li>• البريد الإلكتروني للعرض فقط.</li>
                <li>• تأكد من رقم الاتصال.</li>
              </ul>
              <a href="manage-patient.php" class="btn btn-outline" style="width:100%"><i class="fa fa-list"></i> قائمة المرضى</a>
            </div>
          </div>
        </div>

      </div>
    </div>

  </div>
</div>

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
  if (window.Main && Main.init) Main.init();
  if (window.FormElements && FormElements.init) FormElements.init();
  // تفعيل مظهر أزرار الجنس
  $('.gender-group .btn').on('click', function(){
    $(this).addClass('active').siblings().removeClass('active');
  });
});
</script>
</body>
</html>
