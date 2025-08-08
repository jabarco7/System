<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include("include/config.php");

// لو ما وصلنا للإيميل من الخطوة السابقة نرجّع المستخدم
if (empty($_SESSION['reset_email'])) {
    header('Location: forgot-password.php');
    exit;
}

// CSRF token
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // تحقق CSRF
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        $msg = 'فشل التحقق الأمني. حدّث الصفحة وحاول مجددًا.';
    } else {
        $email   = $_SESSION['reset_email'];
        $pass1   = $_POST['password']        ?? '';
        $pass2   = $_POST['password_again']  ?? '';

        // تحققات أساسية
        if ($pass1 === '' || $pass2 === '') {
            $msg = 'يرجى إدخال كلمة المرور وتأكيدها.';
        } elseif ($pass1 !== $pass2) {
            $msg = 'كلمتا المرور غير متطابقتين.';
        } elseif (strlen($pass1) < 8) {
            $msg = 'كلمة المرور يجب أن تكون 8 أحرف على الأقل.';
        } else {
            // توليد هاش آمن
            $hash = password_hash($pass1, PASSWORD_DEFAULT);

            // تحديث كلمة المرور للمستخدم بحسب الإيميل
            if (!$stmt = $con->prepare("UPDATE users SET password = ? WHERE TRIM(LOWER(email)) = TRIM(LOWER(?)) LIMIT 1")) {
                $msg = 'خطأ في تجهيز الاستعلام: '.$con->error;
            } else {
                $stmt->bind_param("ss", $hash, $email);
                $ok = $stmt->execute();
                if ($ok && $stmt->affected_rows >= 0) {
                    // تنظيف السيشن ومن ثم التحويل لتسجيل الدخول
                    unset($_SESSION['reset_email'], $_SESSION['csrf']);
                    echo "<script>alert('تم تحديث كلمة المرور بنجاح. يمكنك الآن تسجيل الدخول.');</script>";
                    echo "<script>window.location.href='user-login.php';</script>";
                    exit;
                } else {
                    $msg = 'تعذر تحديث كلمة المرور. حاول لاحقًا.';
                }
                $stmt->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <title>تعيين كلمة مرور جديدة</title>
  <link rel="stylesheet" href="vendor/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="vendor/fontawesome/css/font-awesome.min.css">
</head>
<body class="login">
  <div class="row" style="margin-top:40px;">
    <div class="main-login col-xs-10 col-xs-offset-1 col-sm-8 col-sm-offset-2 col-md-4 col-md-offset-4">
      <div class="logo margin-top-30">
        <a href="../index.php"><h2>إعادة تعيين كلمة المرور</h2></a>
      </div>

      <div class="box-login">
        <?php if(!empty($msg)): ?>
          <div class="alert alert-danger"><?php echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <form method="post" action="" novalidate style="margin-top: 20px;">
          <input type="hidden" name="csrf" value="<?php echo $_SESSION['csrf']; ?>">
          <fieldset>
            <legend>تعيين كلمة مرور جديدة</legend>
            <div class="form-group">
              <label for="password">كلمة المرور الجديدة</label>
              <input type="password" class="form-control" id="password" name="password" placeholder="********" required>
            </div>
            <div class="form-group">
              <label for="password_again">تأكيد كلمة المرور</label>
              <input type="password" class="form-control" id="password_again" name="password_again" placeholder="********" required>
            </div>
            <div class="form-actions" style="margin-top: 10px;">
              <button type="submit" class="btn btn-primary pull-right" name="change">
                تحديث <i class="fa fa-arrow-circle-right"></i>
              </button>
              <a class="btn btn-link" href="user-login.php">رجوع</a>
            </div>
          </fieldset>
        </form>

        <div class="copyright" style="margin-top:15px;">
          &copy; <span class="text-bold text-uppercase">نظام إدارة المستشفيات</span>
        </div>
      </div>
    </div>
  </div>

  <script src="vendor/jquery/jquery.min.js"></script>
  <script src="vendor/bootstrap/js/bootstrap.min.js"></script>
</body>
</html>
