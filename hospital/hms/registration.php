<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);

include("include/config.php");

// رسائل
$successMsg = '';
$errorMsg   = '';

if (isset($_POST['submit'])) {
    // قراءة القيم
    $full_name = trim($_POST['full_name'] ?? '');
    $address   = trim($_POST['address'] ?? '');
    $city      = trim($_POST['city'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $gender    = trim($_POST['gender'] ?? '');
    $age       = isset($_POST['age']) ? (int)$_POST['age'] : null;
    $med_hist  = trim($_POST['med_history'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $pass      = $_POST['password'] ?? '';
    $pass2     = $_POST['password_again'] ?? '';

    // تحقق بسيط
    if ($full_name === '' || $address === '' || $city === '' || $phone === '' || $gender === '' || $email === '' || $pass === '' || $pass2 === '') {
        $errorMsg = 'الرجاء تعبئة جميع الحقول المطلوبة.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMsg = 'البريد الإلكتروني غير صالح.';
    } elseif ($pass !== $pass2) {
        $errorMsg = 'كلمتا المرور غير متطابقتين.';
    }

    if ($errorMsg === '') {
        // فحص تكرار البريد في users
        $dupStmt = mysqli_prepare($con, "SELECT id FROM users WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($dupStmt, 's', $email);
        mysqli_stmt_execute($dupStmt);
        $dupRes = mysqli_stmt_get_result($dupStmt);

        if ($dupRes && mysqli_num_rows($dupRes) > 0) {
            $errorMsg = 'هذا البريد مسجّل مسبقًا.';
        }
        mysqli_stmt_close($dupStmt);
    }

    if ($errorMsg === '') {
        // إدراج في users (نفس أعمدتك المستخدمة سابقًا)
        $hashed = password_hash($pass, PASSWORD_DEFAULT);

        // نتأكد بسرعة من وجود الأعمدة (لو سكيمتك مختلفة)
        $cols = [];
        $resCols = mysqli_query($con, "SHOW COLUMNS FROM users");
        if ($resCols) {
            while ($c = mysqli_fetch_assoc($resCols)) $cols[] = strtolower($c['Field']);
            mysqli_free_result($resCols);
        }

        // نحاول الإدراج بحسب الأعمدة المتوفرة
        if (in_array('fullname', $cols) && in_array('address', $cols) && in_array('city', $cols)
            && in_array('phone', $cols) && in_array('gender', $cols) && in_array('email', $cols) && in_array('password', $cols)) {

            $stmtUser = mysqli_prepare($con, "INSERT INTO users (fullname, address, city, phone, gender, email, password) VALUES (?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmtUser, 'sssssss', $full_name, $address, $city, $phone, $gender, $email, $hashed);
        } else {
            // سكيمة مبسطة: fullName,email,password
            $stmtUser = mysqli_prepare($con, "INSERT INTO users (fullName, email, password) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmtUser, 'sss', $full_name, $email, $hashed);
        }

        $okUser = $stmtUser && mysqli_stmt_execute($stmtUser);
        $newUserId = $okUser ? mysqli_insert_id($con) : 0;
        if ($stmtUser) mysqli_stmt_close($stmtUser);

        if (!$okUser) {
            $errorMsg = 'تعذّر إنشاء حساب المستخدم. حاول لاحقًا.';
        } else {
            // إنشاء سجل مريض في tblpatient
            $docid = 0; // إن كان سيتم تحديده لاحقًا من حجز موعد مثلاً

            $stmtP = mysqli_prepare($con, "INSERT INTO tblpatient
                (Docid, PatientName, PatientContno, PatientEmail, PatientGender, PatientAdd, PatientAge, PatientMedhis, CreationDate)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");

            mysqli_stmt_bind_param(
                $stmtP,
                'isssssis',
                $docid,
                $full_name,
                $phone,
                $email,
                $gender,
                $address,   // العنوان (يمكنك ضم المدينة لو رغبت)
                $age,
                $med_hist
            );
            $okPatient = $stmtP && mysqli_stmt_execute($stmtP);
            if ($stmtP) mysqli_stmt_close($stmtP);

            if (!$okPatient) {
                // لو فشل إدراج المريض، بإمكانك حذف المستخدم الذي أُنشئ للتو (اختياري)
                // mysqli_query($con, "DELETE FROM users WHERE id={$newUserId}");
                $errorMsg = 'تم إنشاء حساب المستخدم، لكن تعذّر حفظ بيانات المريض.';
            } else {
                // نجاح كامل → نحوله لصفحة تسجيل الدخول برسالة نجاح
                header('Location: user-login.php?registered=1');
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تسجيل المستخدم</title>
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

    <style>
        body.login { direction: rtl; }
        .alert-compact{padding:10px 14px;border-radius:8px;margin-bottom:12px;font-weight:600}
        .alert-success-compact{background:#eaf9ef;color:#1c7a45;border:1px solid #bfe9cd}
        .alert-danger-compact{background:#ffecec;color:#b23b3b;border:1px solid #ffcdcd}
    </style>
</head>
<body class="login">
    <div class="row">
        <div class="main-login col-xs-10 col-xs-offset-1 col-sm-8 col-sm-offset-2 col-md-4 col-md-offset-4">
            <div class="logo margin-top-30">
                <a href="../index.php"><h2>مستشفى عمران | إنشاء حساب</h2></a>
            </div>

            <div class="box-register">
                <?php if($errorMsg): ?>
                    <div class="alert-compact alert-danger-compact"><?php echo htmlspecialchars($errorMsg); ?></div>
                <?php elseif($successMsg): ?>
                    <div class="alert-compact alert-success-compact"><?php echo htmlspecialchars($successMsg); ?></div>
                <?php endif; ?>

                <form name="registration" id="registration" method="post" novalidate>
                    <fieldset>
                        <legend>التسجيل</legend>
                        <p>ادخل بياناتك الشخصية أدناه:</p>

                        <div class="form-group">
                            <input type="text" class="form-control" name="full_name" placeholder="الاسم الكامل" required>
                        </div>

                        <div class="form-group">
                            <input type="text" class="form-control" name="address" placeholder="العنوان" required>
                        </div>

                        <div class="form-group">
                            <input type="text" class="form-control" name="city" placeholder="المدينة" required>
                        </div>

                        <div class="form-group">
                            <input type="number" min="0" class="form-control" name="age" placeholder="العمر (اختياري)">
                        </div>

                        <div class="form-group">
                            <input type="text" class="form-control" name="phone" placeholder="رقم الهاتف" required>
                        </div>

                        <div class="form-group">
                            <label class="block">الجنس</label>
                            <div class="clip-radio radio-primary">
                                <input type="radio" id="rg-female" name="gender" value="أنثى">
                                <label for="rg-female">أنثى</label>
                                <input type="radio" id="rg-male" name="gender" value="ذكر">
                                <label for="rg-male">ذكر</label>
                            </div>
                        </div>

                        <div class="form-group">
                            <textarea class="form-control" name="med_history" rows="3" placeholder="التاريخ الطبي (اختياري)"></textarea>
                        </div>

                        <p>ادخل تفاصيل حسابك أدناه:</p>

                        <div class="form-group">
                            <span class="input-icon">
                                <input type="email" class="form-control" name="email" id="email" placeholder="البريد الإلكتروني" required>
                                <i class="fa fa-envelope"></i>
                            </span>
                        </div>

                        <div class="form-group">
                            <span class="input-icon">
                                <input type="password" class="form-control" id="password" name="password" placeholder="كلمة المرور" required>
                                <i class="fa fa-lock"></i>
                            </span>
                        </div>

                        <div class="form-group">
                            <span class="input-icon">
                                <input type="password" class="form-control" name="password_again" placeholder="كلمة المرور مرة أخرى" required>
                                <i class="fa fa-lock"></i>
                            </span>
                        </div>

                        <div class="form-actions">
                            <p>لديك حساب بالفعل؟ <a href="user-login.php">تسجيل الدخول</a></p>
                            <button type="submit" class="btn btn-primary pull-right" id="submit" name="submit">
                                إنشاء <i class="fa fa-arrow-circle-right"></i>
                            </button>
                        </div>
                    </fieldset>
                </form>

                <div class="copyright">
                    &copy; <span class="current-year"></span><span class="text-bold text-uppercase"> HMS</span>. <span>جميع الحقوق محفوظة</span>
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
    <script src="vendor/jquery-validation/jquery.validate.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script src="assets/js/login.js"></script>
    <script>
      jQuery(function() {
        if (window.Main) Main.init();
        if (window.Login) Login.init();
        // تحقق بسيط من تطابق كلمات المرور قبل الإرسال
        document.getElementById('registration').addEventListener('submit', function(e){
            var p1 = document.getElementById('password').value;
            var p2 = document.querySelector('input[name="password_again"]').value;
            if (p1 !== p2) {
                e.preventDefault();
                alert('كلمتا المرور غير متطابقتين');
            }
        });
      });
    </script>
</body>
</html>
