<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);

include("include/config.php");

// رسالة نجاح عند العودة من التسجيل
$registeredMsg = '';
if (isset($_GET['registered']) && $_GET['registered'] == '1') {
    $registeredMsg = 'تم إنشاء الحساب بنجاح. من فضلك قم بتسجيل الدخول.';
}

if (isset($_POST['submit'])) {
    $email    = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $_SESSION['errmsg'] = 'الرجاء إدخال البريد الإلكتروني وكلمة المرور';
        header("Location: user-login.php");
        exit;
    }

    // جلب المستخدم
    $stmt = mysqli_prepare($con, "SELECT id, email, fullName, password FROM users WHERE email = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) === 1) {
        $row = mysqli_fetch_assoc($result);

        $hashedFromDB = $row['password'];
        $loginOk = false;

        if (password_verify($password, $hashedFromDB)) {
            $loginOk = true;
        } elseif ($hashedFromDB === md5($password)) {
            $loginOk = true;
        } elseif ($hashedFromDB === $password) {
            $loginOk = true;
        }

        if ($loginOk) {
            session_regenerate_id(true);
            $_SESSION['login'] = $email;
            $_SESSION['id']    = $row['id'];
            $_SESSION['patient_name'] = $row['fullName'] ?: $row['email'];

            // سجل دخول ناجح
            $pid    = $row['id'];
            $uip    = $_SERVER['REMOTE_ADDR'] ?? '';
            $status = 1;
            $logStmt = mysqli_prepare($con, "INSERT INTO userlog (uid, username, userip, status) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($logStmt, 'issi', $pid, $email, $uip, $status);
            mysqli_stmt_execute($logStmt);
            mysqli_stmt_close($logStmt);

            header("Location: dashboard.php");
            exit;
        } else {
            // كلمة مرور خاطئة
            $_SESSION['login'] = $email;
            $uip    = $_SERVER['REMOTE_ADDR'] ?? '';
            $status = 0;
            $logStmt = mysqli_prepare($con, "INSERT INTO userlog (username, userip, status) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($logStmt, 'ssi', $email, $uip, $status);
            mysqli_stmt_execute($logStmt);
            mysqli_stmt_close($logStmt);

            $_SESSION['errmsg'] = 'اسم المستخدم أو كلمة المرور غير صالحة';
            header("Location: user-login.php");
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

        $_SESSION['errmsg'] = 'اسم المستخدم أو كلمة المرور غير صالحة';
        header("Location: user-login.php");
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

    <!-- Toast مخصص -->
    <style>
      .toast-holder{
        position: fixed; top: 16px; left: 50%; transform: translateX(-50%);
        z-index: 9999; display:flex; flex-direction:column; gap:8px; pointer-events:none;
      }
      .toasty{
        min-width: 280px; max-width: 94vw; padding: 10px 14px; border-radius: 10px;
        box-shadow: 0 10px 24px rgba(0,0,0,.18); font-weight: 700; letter-spacing: .2px;
        display:flex; align-items:center; gap:10px; direction: rtl; opacity: 0; transform: translateY(-10px);
        transition: opacity .25s ease, transform .25s ease;
      }
      .toasty.show{ opacity: 1; transform: translateY(0); }
      .toasty.success{ background: #eaf9ef; color:#1c7a45; border:1px solid #bfe9cd; }
      .toasty.error{   background: #ffecec; color:#b23b3b; border:1px solid #ffcdcd; }
      .toasty .ico{ font-size: 18px; }
      .toasty .closex{ margin-inline-start:auto; cursor:pointer; pointer-events:auto; opacity:.6 }
      .toasty .closex:hover{ opacity:1 }
    </style>
</head>
<body class="login">
    <!-- حاوية التوست -->
    <div class="toast-holder" id="toastHolder"></div>

    <div class="row">
        <div class="main-login col-xs-10 col-xs-offset-1 col-sm-8 col-sm-offset-2 col-md-4 col-md-offset-4">
            <div class="logo margin-top-30">
                <a href="../index.php"><h2> مستشفى عمران | تسجيل دخول المريض</h2></a>
            </div>

            <div class="box-login">
                <form class="form-login" method="post" novalidate>
                    <fieldset>
                        <legend>تسجيل الدخول إلى حسابك</legend>
                        <p>يرجى إدخال بريدك وكلمة المرور لتسجيل الدخول.</p>

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

      // دالة إنشاء توست
      function showToast(message, type='success', autoHideMs=2000){
        const holder = document.getElementById('toastHolder');
        const el = document.createElement('div');
        el.className = 'toasty '+type;
        el.innerHTML = `
          <span class="ico">${type==='success' ? '✔️' : '⚠️'}</span>
          <span>${message}</span>
          <span class="closex" aria-label="close">✖</span>
        `;
        holder.appendChild(el);

        // إظهار بتحريك
        requestAnimationFrame(()=> el.classList.add('show'));

        // إغلاق يدوي
        el.querySelector('.closex').addEventListener('click', ()=> {
          el.classList.remove('show');
          setTimeout(()=> el.remove(), 250);
        });

        // إغلاق تلقائي
        if(autoHideMs){
          setTimeout(()=> {
            el.classList.remove('show');
            setTimeout(()=> el.remove(), 250);
          }, autoHideMs);
        }
      }

      // رسائل من السيرفر
      <?php if(!empty($registeredMsg)): ?>
        showToast(<?php echo json_encode($registeredMsg, JSON_UNESCAPED_UNICODE); ?>, 'success', 2200);
      <?php endif; ?>

      <?php if(!empty($_SESSION['errmsg'])): ?>
        showToast(<?php echo json_encode($_SESSION['errmsg'], JSON_UNESCAPED_UNICODE); ?>, 'error', 2500);
        <?php $_SESSION['errmsg'] = ""; ?>
      <?php endif; ?>
    </script>
</body>
</html>
