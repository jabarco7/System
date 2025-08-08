<?php
session_start();
// error_reporting(0);
include('include/config.php');
include('include/checklogin.php');
check_login();

$msg = '';
$err = '';

// CSRF token
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

$uid = (int)($_SESSION['id'] ?? 0);

// جلب بيانات المستخدم للعرض المسبق
$stmt = mysqli_prepare($con, "SELECT fullName, address, city, gender, email, regDate, updationDate FROM users WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 'i', $uid);
mysqli_stmt_execute($stmt);
$userRes = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($userRes);
mysqli_stmt_close($stmt);

if (isset($_POST['submit'])) {
    // تحقق CSRF
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        $err = 'جلسة غير صالحة، حدّث الصفحة وحاول مرة أخرى.';
    } else {
        $fname  = trim($_POST['fname'] ?? '');
        $address= trim($_POST['address'] ?? '');
        $city   = trim($_POST['city'] ?? '');
        $gender = trim($_POST['gender'] ?? '');

        if ($fname === '' || $city === '' || $gender === '') {
            $err = 'الرجاء تعبئة الحقول المطلوبة.';
        } else {
            $stmt = mysqli_prepare($con, "UPDATE users SET fullName = ?, address = ?, city = ?, gender = ?, updationDate = NOW() WHERE id = ?");
            mysqli_stmt_bind_param($stmt, 'ssssi', $fname, $address, $city, $gender, $uid);
            if (mysqli_stmt_execute($stmt)) {
                $msg = 'تم تحديث ملفك الشخصي بنجاح';
                // حدّث البيانات المعروضة فوراً
                $user['fullName'] = $fname;
                $user['address']  = $address;
                $user['city']     = $city;
                $user['gender']   = $gender;
                $user['updationDate'] = date('Y-m-d H:i:s');
                // حدّث اسم المريض في السيشن لو كنت تستخدمه في الواجهة
                $_SESSION['patient_name'] = $fname ?: ($_SESSION['patient_name'] ?? '');
            } else {
                $err = 'تعذر التحديث الآن. حاول لاحقًا.';
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>المستخدم | تحرير الملف الشخصي</title>

    <link href="http://fonts.googleapis.com/css?family=Tajawal:300,400,500,700" rel="stylesheet">
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
        body{background:#f0f5f9;font-family:'Tajawal',sans-serif}
        .container-narrow{max-width:900px;margin:24px auto}
        .page-head{
            background:linear-gradient(90deg,#3498db,#4aa8e0);
            color:#fff;border-radius:12px;padding:16px 18px;margin-bottom:16px;text-align:center
        }
        .card-clean{background:#fff;border-radius:14px;box-shadow:0 8px 20px rgba(0,0,0,.06);padding:20px}
        .meta small{color:#6c757d}
        .form-label{font-weight:600}
        .btn-primary{background-color: #3498db;border:none}
        .alert-compact{border-radius:10px;padding:10px 12px}
    </style>
</head>
<body>
<div id="app">

    <div class="container-narrow">
        <div class="page-head">
            <h4 class="m-0">تحرير الملف الشخصي</h4>
            <small class="opacity-75">قم بتعديل معلوماتك الأساسية</small>
        </div>

        <?php if ($msg): ?>
            <div class="alert alert-success alert-compact"><i class="fa fa-check-circle"></i> <?php echo htmlentities($msg); ?></div>
        <?php endif; ?>
        <?php if ($err): ?>
            <div class="alert alert-danger alert-compact"><i class="fa fa-exclamation-triangle"></i> <?php echo htmlentities($err); ?></div>
        <?php endif; ?>

        <div class="card-clean">
            <?php if ($user): ?>
                <div class="d-flex justify-content-between align-items-center mb-3 meta">
                    <div>
                        <div class="fw-bold"><?php echo htmlentities($user['fullName']); ?> — ملف المستخدم</div>
                        <small>تاريخ التسجيل: <?php echo htmlentities($user['regDate']); ?></small>
                        <?php if (!empty($user['updationDate'])): ?>
                            <small class="ms-2">آخر تحديث: <?php echo htmlentities($user['updationDate']); ?></small>
                        <?php endif; ?>
                    </div>
                    <span class="badge bg-light text-dark">البريد: <?php echo htmlentities($user['email']); ?></span>
                </div>
                <hr>

                <form method="post" autocomplete="off">
                    <input type="hidden" name="csrf" value="<?php echo $_SESSION['csrf']; ?>">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="fname">اسم المستخدم</label>
                            <input type="text" id="fname" name="fname" class="form-control" value="<?php echo htmlentities($user['fullName']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="city">المدينة</label>
                            <input type="text" id="city" name="city" class="form-control" value="<?php echo htmlentities($user['city']); ?>" required>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label" for="address">العنوان</label>
                            <textarea id="address" name="address" class="form-control" rows="3" placeholder="أدخل العنوان"><?php echo htmlentities($user['address']); ?></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="gender">الجنس</label>
                            <select id="gender" name="gender" class="form-control" required>
                                <?php
                                $g = $user['gender'] ?? '';
                                $opts = ['ذكر','انثى','اخر'];
                                if ($g && !in_array($g,$opts,true)) { echo '<option value="'.htmlentities($g).'" selected>'.htmlentities($g).'</option>'; }
                                foreach ($opts as $op) {
                                    $sel = ($op === $g) ? 'selected' : '';
                                    echo '<option value="'.$op.'" '.$sel.'>'.$op.'</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">البريد الإلكتروني</label>
                            <div class="input-group">
                                <input type="email" class="form-control" value="<?php echo htmlentities($user['email']); ?>" readonly>
                                <a class="btn btn-outline-secondary" href="change-emaild.php">تحديث البريد</a>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" name="submit" class="btn btn-primary">
                            <i class="fa fa-save"></i> تحديث
                        </button>
                        <a href="dashboard.php" class="btn btn-outline-secondary">
                            <i class="fa fa-arrow-right"></i> رجوع للوحة
                        </a>
                    </div>
                </form>
            <?php else: ?>
                <div class="alert alert-warning">لم يتم العثور على بيانات المستخدم.</div>
            <?php endif; ?>
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
<script>jQuery(function(){ if (window.Main) Main.init(); });</script>
</body>
</html>
