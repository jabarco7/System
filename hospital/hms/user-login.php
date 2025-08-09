<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);

include("include/config.php");

if (isset($_POST['submit'])) {
    $email    = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        echo "<script>alert('الرجاء إدخال البريد الإلكتروني وكلمة المرور');</script>";
        echo "<script>window.location.href='user-login.php'</script>";
        exit;
    }

    // جلب المستخدم من قاعدة البيانات (بدون contactno لأنه غير موجود)
    $stmt = mysqli_prepare($con, "SELECT id, email, fullName, password FROM users WHERE email = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) === 1) {
        $row = mysqli_fetch_assoc($result);

        $hashedFromDB = $row['password'];
        $loginOk = false;

        // password_hash()
        if (password_verify($password, $hashedFromDB)) {
            $loginOk = true;
        }
        // MD5
        elseif ($hashedFromDB === md5($password)) {
            $loginOk = true;
        }
        // نص عادي
        elseif ($hashedFromDB === $password) {
            $loginOk = true;
        }

        if ($loginOk) {
            session_regenerate_id(true);
            $_SESSION['login'] = $email;
            $_SESSION['id']    = (int)$row['id'];
            $_SESSION['patient_name'] = $row['fullName'] ?: $row['email'];

            // حفظ سجل الدخول (نجاح)
            $pid    = (int)$row['id'];
            $uip    = $_SERVER['REMOTE_ADDR'] ?? '';
            $status = 1;
            $logStmt = mysqli_prepare($con, "INSERT INTO userlog (uid, username, userip, status) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($logStmt, 'issi', $pid, $email, $uip, $status);
            mysqli_stmt_execute($logStmt);
            mysqli_stmt_close($logStmt);

            /* =========================================================
               إنشاء سجل مريض في tblpatient تلقائيًا إن لم يكن موجودًا
               - يدعم وجود/عدم وجود عمود UserID في tblpatient
            ========================================================== */
            $uid     = (int)$row['id'];
            $pname   = mysqli_real_escape_string($con, $row['fullName'] ?? '');
            $pemail  = mysqli_real_escape_string($con, $row['email'] ?? '');
            $pcont   = ''; // لا يوجد رقم هاتف في جدول users لديك

            // هل جدول tblpatient يحتوي عمود UserID؟
            $hasUserIdCol = false;
            if ($colRes = mysqli_query($con, "SHOW COLUMNS FROM tblpatient LIKE 'UserID'")) {
                $hasUserIdCol = (mysqli_num_rows($colRes) > 0);
                mysqli_free_result($colRes);
            }

            if ($hasUserIdCol) {
                // فحص بالـ UserID لمنع التكرار
                $existsRes = mysqli_query($con, "SELECT 1 FROM tblpatient WHERE UserID = {$uid} LIMIT 1");
                if ($existsRes && mysqli_num_rows($existsRes) === 0) {
                    mysqli_query($con, "
                        INSERT INTO tblpatient (UserID, PatientName, PatientEmail, PatientContno, CreationDate)
                        VALUES ({$uid}, '{$pname}', '{$pemail}', '{$pcont}', NOW())
                    ");
                }
                if ($existsRes) mysqli_free_result($existsRes);
            } else {
                // بدون UserID: نتحقق بالبريد لمنع التكرار
                $existsRes = mysqli_query($con, "SELECT 1 FROM tblpatient WHERE PatientEmail = '{$pemail}' LIMIT 1");
                if ($existsRes && mysqli_num_rows($existsRes) === 0) {
                    mysqli_query($con, "
                        INSERT INTO tblpatient (PatientName, PatientEmail, PatientContno, CreationDate)
                        VALUES ('{$pname}', '{$pemail}', '{$pcont}', NOW())
                    ");
                }
                if ($existsRes) mysqli_free_result($existsRes);
            }
            /* ==================== نهاية الإنشاء التلقائي ==================== */

            header("Location: dashboard.php");
            exit;
        } else {
            // كلمة مرور غير صحيحة
            $_SESSION['login'] = $email;
            $uip    = $_SERVER['REMOTE_ADDR'] ?? '';
            $status = 0;
            $logStmt = mysqli_prepare($con, "INSERT INTO userlog (username, userip, status) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($logStmt, 'ssi', $email, $uip, $status);
            mysqli_stmt_execute($logStmt);
            mysqli_stmt_close($logStmt);

            echo "<script>alert('اسم المستخدم أو كلمة المرور غير صالحة');</script>";
            echo "<script>window.location.href='user-login.php'</script>";
            exit;
        }
    } else {
        // لا يوجد مستخدم بهذا البريد
        $_SESSION['login'] = $email;
        $uip    = $_SERVER['REMOTE_ADDR'] ?? '';
        $status = 0;
        $logStmt = mysqli_prepare($con, "INSERT INTO userlog (username, userip, status) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($logStmt, 'ssi', $email, $uip, $status);
        mysqli_stmt_execute($logStmt);
        mysqli_stmt_close($logStmt);

        echo "<script>alert('اسم المستخدم أو كلمة المرور غير صالحة');</script>";
        echo "<script>window.location.href='user-login.php'</script>";
        exit;
    }
    mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
	<head>
		<meta charset="utf-8">
		<title>المستخدم - تسجيل الدخول</title>
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link href="http://fonts.googleapis.com/css?family=Lato:300,400,400italic,600,700|Raleway:300,400,500,600,700|Crete+Round:400italic" rel="stylesheet" type="text/css" />
		<link rel="stylesheet" href="vendor/bootstrap/css/bootstrap.min.css">
		<link rel="stylesheet" href="vendor/fontawesome/css/font-awesome.min.css">
		<link rel="stylesheet" href="vendor/themify-icons/themify-icons.min.css">
		<link href="vendor/animate.css/animate.min.css" rel="stylesheet" media="screen">
		<link href="vendor/perfect-scrollbar/perfect-scrollbar.min.css" rel="stylesheet" media="screen">
		<link href="vendor/switchery/switchery.min.css" rel="stylesheet" media="screen">
		<link rel="stylesheet" href="assets/css/styles.css">
		<link rel="stylesheet" href="assets/css/plugins.css">
		<link rel="stylesheet" href="assets/css/themes/theme-1.css" id="skin_color" />
	</head>
	<body class="login">
		<div class="row">
			<div class="main-login col-xs-10 col-xs-offset-1 col-sm-8 col-sm-offset-2 col-md-4 col-md-offset-4">
				<div class="logo margin-top-30">
					<a href="../index.php"><h2> مستشفى عمران | تسجيل دخول المريض</h2></a>
				</div>

				<div class="box-login">
					<form class="form-login" method="post" novalidate>
						<fieldset>
							<legend>تسجيل الدخول إلى حسابك</legend>
							<p>
								يرجى إدخال بريدك وكلمة المرور لتسجيل الدخول.<br>
								<span style="color:red;"><?php echo $_SESSION['errmsg'] ?? ''; ?><?php $_SESSION['errmsg']="";?></span>
							</p>
							<div class="form-group">
								<span class="input-icon">
									<input type="email" class="form-control" name="username" placeholder="البريد الإلكتروني" required>
									<i class="fa fa-user"></i>
								</span>
							</div>
							<div class="form-group form-actions">
								<span class="input-icon">
									<input type="password" class="form-control" name="password" placeholder="كلمة المرور" required>
									<i class="fa fa-lock"></i>
								</span>
								<a href="forgot-password.php">هل نسيت كلمة المرور؟</a>
							</div>
							<div class="form-actions">
								<button type="submit" class="btn btn-primary pull-right" name="submit">
									تسجيل الدخول <i class="fa fa-arrow-circle-right"></i>
								</button>
							</div>
							<div class="new-account">
								ليس لديك حساب حتى الآن؟
								<a href="registration.php">إنشاء حساب</a>
							</div>
						</fieldset>
					</form>

					<div class="copyright">
						<span class="text-bold text-uppercase">نظام إدارة المستشفيات</span>.
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
		<script src="assets/js/main.js"></script>
		<script src="assets/js/login.js"></script>
		<script>
			jQuery(function(){ if(window.Main){ Main.init(); } if(window.Login){ Login.init(); }});
		</script>
	</body>
</html>
