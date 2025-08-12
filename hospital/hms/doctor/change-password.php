<?php
session_start();
error_reporting(0);
include('include/config.php');

if (empty($_SESSION['id'])) {
  header('location:logout.php');
  exit;
}

$docId = (int)$_SESSION['id'];

/* CSRF */
if (empty($_SESSION['csrf'])) {
  $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$CSRF = $_SESSION['csrf'];

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
  // تحقق CSRF
  if (!hash_equals($CSRF, $_POST['csrf'] ?? '')) {
    $err = 'انتهت صلاحية الجلسة. حدّث الصفحة ثم حاول مرة أخرى.';
  } else {
    $cpass = (string)($_POST['cpass'] ?? '');
    $npass = (string)($_POST['npass'] ?? '');
    $cfpass= (string)($_POST['cfpass'] ?? '');

    // تحققات أساسية
    if ($cpass === '' || $npass === '' || $cfpass === '') {
      $err = 'يرجى تعبئة جميع الحقول.';
    } elseif ($npass !== $cfpass) {
      $err = 'كلمة المرور الجديدة وتأكيدها غير متطابقين.';
    } elseif (mb_strlen($npass) < 8) {
      $err = 'كلمة المرور الجديدة يجب أن تكون 8 أحرف/أرقام على الأقل.';
    } else {
      // اجلب كلمة المرور الحالية
      if ($st = $con->prepare("SELECT password FROM doctors WHERE id=? LIMIT 1")) {
        $st->bind_param('i', $docId);
        $st->execute();
        $res = $st->get_result();
        if ($row = $res->fetch_assoc()) {
          $stored = (string)$row['password'];

          // تحقق من كلمة المرور الحالية (دعم حديث/قديم)
          $oldOk = false;
          if (strlen($stored) > 0 && $stored[0] === '$') {
            // مخزنة بتجزئة قوية (bcrypt/argon)
            $oldOk = password_verify($cpass, $stored);
          } else {
            // قديم MD5
            $oldOk = (md5($cpass) === $stored);
          }

          if (!$oldOk) {
            $err = 'كلمة المرور الحالية غير صحيحة.';
          } else {
            // عدم السماح بإعادة نفس كلمة المرور
            $sameAsOld = (strlen($stored) > 0 && $stored[0] === '$') ? password_verify($npass, $stored) : (md5($npass) === $stored);
            if ($sameAsOld) {
              $err = 'الرجاء اختيار كلمة مرور مختلفة عن الحالية.';
            } else {
              // حضّر التجزئة الجديدة وفق النمط الحالي
              if (strlen($stored) > 0 && $stored[0] === '$') {
                $newHash = password_hash($npass, PASSWORD_DEFAULT);
              } else {
                $newHash = md5($npass); // إبقاء التوافق مع نظام تسجيل الدخول القديم
              }

              if ($up = $con->prepare("UPDATE doctors SET password=?, updationDate=NOW() WHERE id=?")) {
                $up->bind_param('si', $newHash, $docId);
                $ok = $up->execute();
                $up->close();

                if ($ok) {
                  $msg = 'تم تغيير كلمة المرور بنجاح.';
                } else {
                  $err = 'تعذر حفظ كلمة المرور الآن. حاول لاحقًا.';
                }
              } else {
                $err = 'خطأ داخلي أثناء التحديث.';
              }
            }
          }
        } else {
          $err = 'لم يتم العثور على الحساب.';
        }
        $st->close();
      } else {
        $err = 'خطأ داخلي أثناء الاستعلام.';
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <title>طبيب | تغيير كلمة المرور</title>

  <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">
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
  <link rel="stylesheet" href="assets/css/hms-unified.css?v=1.1">

  <style>
    body{font-family:'Tajawal',sans-serif;background:#f0f5f9}
    .page-head{
      background:linear-gradient(90deg,#3498db,#4aa8e0);
      color:#fff;border-radius:12px;padding:16px 18px;margin:20px 20px 12px
    }
    .page-head h1{margin:0;font-size:1.25rem}
    .container-fullw{border-radius:12px;background:#fff;box-shadow:0 8px 20px rgba(0,0,0,.06);padding:18px;margin:0 20px 24px}
    .form-control{border-radius:10px;height:44px}
    .btn-primary{border-radius:10px}
    .hint{color:#6c757d;font-size:.9rem}
    .toggle-eye{position:absolute;inset-inline-end:12px;top:50%;transform:translateY(-50%);cursor:pointer;color:#6c757d}
    .pos-rel{position:relative}
  </style>

  <script>
    function valid(){
      var f = document.forms['chngpwd'];
      if(!f.cpass.value){ alert('حقل كلمة المرور الحالية فارغ'); f.cpass.focus(); return false; }
      if(!f.npass.value){ alert('حقل كلمة المرور الجديدة فارغ'); f.npass.focus(); return false; }
      if(f.npass.value.length < 8){ alert('كلمة المرور الجديدة يجب أن تكون 8 أحرف/أرقام على الأقل.'); f.npass.focus(); return false; }
      if(!f.cfpass.value){ alert('حقل تأكيد كلمة المرور فارغ'); f.cfpass.focus(); return false; }
      if(f.npass.value !== f.cfpass.value){ alert('كلمة المرور الجديدة وتأكيدها غير متطابقين.'); f.cfpass.focus(); return false; }
      return true;
    }
  </script>
</head>
<body>
  <div id="app">
    <?php include('include/header.php');?>
    <?php include('include/sidebar.php');?>

    <div class="app-content">
      <div class="page-head">
        <h1><i class="fa fa-key"></i> تغيير كلمة المرور</h1>
      </div>

      <div class="container-fluid container-fullw bg-white">
        <div class="row">
          <div class="col-lg-8 col-md-12">
            <?php if ($err): ?>
              <div class="alert alert-danger"><?php echo htmlentities($err); ?></div>
            <?php elseif ($msg): ?>
              <div class="alert alert-success"><?php echo htmlentities($msg); ?></div>
            <?php endif; ?>

            <form role="form" name="chngpwd" method="post" onsubmit="return valid();" autocomplete="off">
              <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($CSRF); ?>">

              <div class="form-group pos-rel">
                <label>كلمة المرور الحالية</label>
                <input type="password" name="cpass" class="form-control" placeholder="أدخل كلمة المرور الحالية" required>
                <span class="toggle-eye" onclick="this.previousElementSibling.type = (this.previousElementSibling.type==='password' ? 'text' : 'password')"><i class="fa fa-eye"></i></span>
              </div>

              <div class="form-group pos-rel">
                <label>كلمة المرور الجديدة</label>
                <input type="password" name="npass" class="form-control" placeholder="كلمة المرور الجديدة" minlength="8" required>
                <span class="toggle-eye" onclick="this.previousElementSibling.type = (this.previousElementSibling.type==='password' ? 'text' : 'password')"><i class="fa fa-eye"></i></span>
                <div class="hint mt-1">يفضّل استخدام حروف كبيرة وصغيرة وأرقام ورموز.</div>
              </div>

              <div class="form-group pos-rel">
                <label>تأكيد كلمة المرور</label>
                <input type="password" name="cfpass" class="form-control" placeholder="أعد إدخال كلمة المرور" minlength="8" required>
                <span class="toggle-eye" onclick="this.previousElementSibling.type = (this.previousElementSibling.type==='password' ? 'text' : 'password')"><i class="fa fa-eye"></i></span>
              </div>

              <button type="submit" name="submit" class="btn btn-primary">
                حفظ
              </button>
            </form>
          </div>
        </div>
      </div>

    </div><!-- /.app-content -->
  </div><!-- /#app -->

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
    jQuery(function(){ if (window.Main) Main.init(); if (window.FormElements) FormElements.init(); });
  </script>
</body>
</html>
