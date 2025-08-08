<?php
session_start();
error_reporting(0);
include('include/config.php');
include('include/checklogin.php');
check_login();

$uid = (int)($_SESSION['id'] ?? 0);
$msg = ''; $err = '';

// اجلب البريد الحالي لعرضه في الحقل
$meStmt = mysqli_prepare($con, "SELECT email FROM users WHERE id=? LIMIT 1");
mysqli_stmt_bind_param($meStmt, 'i', $uid);
mysqli_stmt_execute($meStmt);
$meRes = mysqli_stmt_get_result($meStmt);
$me = mysqli_fetch_assoc($meRes);
mysqli_stmt_close($meStmt);
$currentEmail = $me['email'] ?? '';

if (isset($_POST['submit'])) {
    $email = trim($_POST['email'] ?? '');

    // تحقق من صحة البريد
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $err = 'الرجاء إدخال بريد إلكتروني صالح.';
    } else {
        // تحقق من عدم وجود البريد لمستخدم آخر
        $duStmt = mysqli_prepare($con, "SELECT id FROM users WHERE email=? AND id<>? LIMIT 1");
        mysqli_stmt_bind_param($duStmt, 'si', $email, $uid);
        mysqli_stmt_execute($duStmt);
        $duRes = mysqli_stmt_get_result($duStmt);
        $exists = mysqli_fetch_assoc($duRes);
        mysqli_stmt_close($duStmt);

        if ($exists) {
            $err = 'البريد الإلكتروني مستخدم بالفعل.';
        } else {
            // تحديث البريد
            $upStmt = mysqli_prepare($con, "UPDATE users SET email=?, updationDate=NOW() WHERE id=?");
            mysqli_stmt_bind_param($upStmt, 'si', $email, $uid);
            if (mysqli_stmt_execute($upStmt)) {
                $msg = 'تم تحديث بريدك الإلكتروني بنجاح.';
                $currentEmail = $email; // حدّث العرض الحالي
            } else {
                $err = 'تعذر حفظ التعديل الآن. حاول لاحقًا.';
            }
            mysqli_stmt_close($upStmt);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <title>تعديل البريد الإلكتروني</title>
    <link href="http://fonts.googleapis.com/css?family=Lato:300,400,600,700|Raleway:300,400,500,600,700" rel="stylesheet" type="text/css" />
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
        body{font-family:'Tajawal','Lato',sans-serif;background:#f0f5f9}
        .container-narrow{max-width:720px;margin:40px auto}
        .page-head{background:linear-gradient(90deg,#3498db,#4aa8e0);color:#fff;border-radius:12px;padding:16px 18px;margin-bottom:16px;text-align:center}
        .card-clean{background:#fff;border-radius:14px;box-shadow:0 8px 20px rgba(0,0,0,.06);padding:20px}
    </style>
</head>
<body>
<div class="container-narrow">
    <div class="page-head">
        <h4 class="m-0">تعديل البريد الإلكتروني</h4>
        <small class="opacity-75">حدّث بريدك لاستقبال الإشعارات</small>
    </div>

    <div class="card-clean">
        <?php if ($msg): ?><div class="alert alert-success"><?php echo htmlentities($msg); ?></div><?php endif; ?>
        <?php if ($err): ?><div class="alert alert-danger"><?php echo htmlentities($err); ?></div><?php endif; ?>

        <form method="post" autocomplete="off" id="updatemail">
            <div class="mb-3">
                <label class="form-label" for="email">البريد الإلكتروني</label>
                <input type="email" class="form-control" name="email" id="email"
                       onblur="userAvailability()" placeholder="example@domain.com"
                       value="<?php echo htmlentities($currentEmail); ?>" required>
                <small id="user-availability-status1" class="text-muted"></small>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" name="submit" id="submit" class="btn btn-primary">تحديث</button>
                <a href="edit-profile.php" class="btn btn-outline-secondary">رجوع</a>
            </div>
        </form>
    </div>
</div>

<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.min.js"></script>
<script src="vendor/modernizr/modernizr.js"></script>
<script src="vendor/jquery-cookie/jquery.cookie.js"></script>
<script src="vendor/perfect-scrollbar/perfect-scrollbar.min.js"></script>
<script src="vendor/switchery/switchery.min.js"></script>
<script src="assets/js/main.js"></script>
<script src="assets/js/form-elements.js"></script>
<script>
jQuery(function(){ if(window.Main){ Main.init(); } });

function userAvailability() {
  var email = $("#email").val().trim();
  if (!email) { $("#user-availability-status1").text(''); return; }
  $.ajax({
    url: "check_availability.php",
    type: "POST",
    data: { email: email },
    success: function(data){
      $("#user-availability-status1").html(data);
    }
  });
}
</script>
</body>
</html>
