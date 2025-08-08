<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include("include/config.php"); // الاتصال بقاعدة البيانات

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // قراءة البريد
    $email = strtolower(trim($_POST['email'] ?? ''));

    if ($email === '') {
        echo "<script>alert('يرجى إدخال البريد الإلكتروني.');</script>";
    } else {
        // استعلام آمن للتحقق من وجود الإيميل
        $stmt = $con->prepare("SELECT id FROM users WHERE TRIM(LOWER(email)) = ? LIMIT 1");
        if (!$stmt) {
            die("خطأ في تحضير الاستعلام: " . $con->error);
        }
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            // إذا وجد حساب → حفظ الإيميل في السيشن والتحويل لصفحة إعادة التعيين
            $_SESSION['reset_email'] = $email;
            header('Location: reset-password.php');
            exit;
        } else {
            echo "<script>alert('لا يوجد حساب بهذا البريد الإلكتروني.');</script>";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <title>إعادة تعيين كلمة مرور المريض</title>
  <link rel="stylesheet" href="vendor/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="vendor/fontawesome/css/font-awesome.min.css">
</head>
<body class="login">

  <div class="row" style="margin-top: 40px;">
    <div class="main-login col-xs-10 col-xs-offset-1 col-sm-8 col-sm-offset-2 col-md-4 col-md-offset-4">
      <div class="logo margin-top-30">
        <h2>إعادة تعيين كلمة مرور المريض</h2>
      </div>

      <div class="box-login">
        <form method="post" action="" novalidate style="margin-top: 20px;">
          <fieldset>
            <legend>استرجاع كلمة المرور</legend>
            <p>يرجى إدخال بريدك الإلكتروني لاستعادة كلمة المرور.</p>

            <div class="form-group">
              <label for="email">البريد الإلكتروني المسجل</label>
              <input type="email" class="form-control" id="email" name="email" placeholder="البريد الإلكتروني المسجل" required>
            </div>

            <div class="form-actions" style="margin-top: 10px;">
              <button type="submit" class="btn btn-primary pull-right" name="submit">
                متابعة <i class="fa fa-arrow-circle-right"></i>
              </button>
            </div>

            <div class="new-account" style="margin-top: 10px;">
              <a href="user-login.php">رجوع</a>
            </div>
          </fieldset>
        </form>

        <div class="copyright" style="margin-top: 15px;">
          &copy; <span class="text-bold text-uppercase">نظام إدارة المستشفيات</span>
        </div>
      </div>
    </div>
  </div>

  <script src="vendor/jquery/jquery.min.js"></script>
  <script src="vendor/bootstrap/js/bootstrap.min.js"></script>
</body>
</html>
