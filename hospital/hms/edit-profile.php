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

// جلب بيانات المريض الكاملة من جدول المرضى
$patient = null;
if ($user) {
    $stmt2 = mysqli_prepare($con, "SELECT PatientContno, PatientAge, PatientMedhis FROM tblpatient WHERE PatientEmail = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt2, 's', $user['email']);
    mysqli_stmt_execute($stmt2);
    $res2 = mysqli_stmt_get_result($stmt2);
    $patient = mysqli_fetch_assoc($res2);
    mysqli_stmt_close($stmt2);
}

if (isset($_POST['submit'])) {
    // تحقق CSRF
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        $err = 'جلسة غير صالحة، حدّث الصفحة وحاول مرة أخرى.';
    } else {
        // فقط الحقول المسموح بتعديلها: رقم الهاتف، العنوان، العمر
        $address = trim($_POST['address'] ?? '');
        $phone  = trim($_POST['phone'] ?? '');
        $age = (int)($_POST['age'] ?? 0);

        if (empty($phone) && empty($address) && $age <= 0) {
            $err = 'الرجاء تعبئة حقل واحد على الأقل للتحديث.';
        } else {
            // تحديث العنوان فقط في جدول المستخدمين
            $stmt = mysqli_prepare($con, "UPDATE users SET address = ?, updationDate = NOW() WHERE id = ?");
            mysqli_stmt_bind_param($stmt, 'si', $address, $uid);
            $userUpdated = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // تحديث أو إنشاء سجل في tblpatient (فقط الحقول المسموحة)
            $patientUpdated = true;
            if ($patient) {
                // تحديث السجل الموجود - فقط الهاتف والعنوان والعمر
                $stmt2 = mysqli_prepare($con, "UPDATE tblpatient SET PatientContno = ?, PatientAdd = ?, PatientAge = ?, UpdationDate = NOW() WHERE PatientEmail = ?");
                mysqli_stmt_bind_param($stmt2, 'ssis', $phone, $address, $age, $user['email']);
                $patientUpdated = mysqli_stmt_execute($stmt2);
                mysqli_stmt_close($stmt2);
            } else {
                // إنشاء سجل جديد مع البيانات الأساسية من المستخدم
                $stmt2 = mysqli_prepare($con, "INSERT INTO tblpatient (PatientName, PatientEmail, PatientContno, PatientGender, PatientAdd, PatientAge, Docid, CreationDate) VALUES (?, ?, ?, ?, ?, ?, 1, NOW())");
                mysqli_stmt_bind_param($stmt2, 'sssssi', $user['fullName'], $user['email'], $phone, $user['gender'], $address, $age);
                $patientUpdated = mysqli_stmt_execute($stmt2);
                mysqli_stmt_close($stmt2);
            }

            if ($userUpdated && $patientUpdated) {
                $msg = 'تم تحديث البيانات المسموحة بنجاح';
                // حدّث البيانات المعروضة فوراً
                $user['address'] = $address;
                $user['updationDate'] = date('Y-m-d H:i:s');

                // تحديث بيانات المريض المعروضة
                if (!$patient) {
                    $patient = [];
                }
                $patient['PatientContno'] = $phone;
                $patient['PatientAge'] = $age;

                // إعادة جلب البيانات المحدثة
                $stmt3 = mysqli_prepare($con, "SELECT PatientContno, PatientAge, PatientMedhis FROM tblpatient WHERE PatientEmail = ? LIMIT 1");
                mysqli_stmt_bind_param($stmt3, 's', $user['email']);
                mysqli_stmt_execute($stmt3);
                $res3 = mysqli_stmt_get_result($stmt3);
                $patient = mysqli_fetch_assoc($res3);
                mysqli_stmt_close($stmt3);
            } else {
                $err = 'تعذر التحديث الآن. حاول لاحقًا.';
            }
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
        body {
            background: #f0f5f9;
            font-family: 'Tajawal', sans-serif
        }

        .container-narrow {
            max-width: 900px;
            margin: 24px auto
        }

        .page-head {
            background: linear-gradient(90deg, #3498db, #4aa8e0);
            color: #fff;
            border-radius: 12px;
            padding: 16px 18px;
            margin-bottom: 16px;
            text-align: center
        }

        .card-clean {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, .06);
            padding: 20px
        }

        .meta small {
            color: #6c757d
        }

        .form-label {
            font-weight: 600
        }

        .btn-primary {
            background-color: #3498db;
            border: none
        }

        .alert-compact {
            border-radius: 10px;
            padding: 10px 12px
        }
    </style>
</head>

<body>
    <div id="app">

        <div class="container-narrow">
            <div class="page-head">
                <h4 class="m-0">ملفي الطبي</h4>
                <small class="opacity-75">يمكنك تعديل رقم الهاتف والعنوان والعمر فقط</small>
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

                    <!-- عرض البيانات المحمية -->
                    <div class="alert alert-info">
                        <h5><i class="fa fa-info-circle"></i> معلومات مهمة</h5>
                        <p class="mb-0">يمكنك تعديل <strong>رقم الهاتف والعنوان والعمر فقط</strong>. باقي البيانات محمية ولا يمكن تعديلها لأسباب أمنية.</p>
                    </div>

                    <!-- البيانات المحمية (للعرض فقط) -->
                    <div class="card mb-4" style="background-color: #f8f9fa;">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fa fa-lock"></i> بيانات محمية (غير قابلة للتعديل)</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">اسم المستخدم</label>
                                    <input type="text" class="form-control" value="<?php echo htmlentities($user['fullName']); ?>" readonly style="background-color: #e9ecef; cursor: not-allowed;">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">البريد الإلكتروني</label>
                                    <input type="email" class="form-control" value="<?php echo htmlentities($user['email']); ?>" readonly style="background-color: #e9ecef; cursor: not-allowed;">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">الجنس</label>
                                    <input type="text" class="form-control" value="<?php echo htmlentities($user['gender'] ?? 'غير محدد'); ?>" readonly style="background-color: #e9ecef; cursor: not-allowed;">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">المدينة</label>
                                    <input type="text" class="form-control" value="<?php echo htmlentities($user['city'] ?? 'غير محدد'); ?>" readonly style="background-color: #e9ecef; cursor: not-allowed;">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- النموذج للبيانات القابلة للتعديل -->
                    <form method="post" autocomplete="off">
                        <input type="hidden" name="csrf" value="<?php echo $_SESSION['csrf']; ?>">

                        <div class="card" style="background-color: #d4edda;">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fa fa-edit"></i> بيانات قابلة للتعديل</h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label" for="phone">رقم الهاتف</label>
                                        <input type="text" id="phone" name="phone" class="form-control" value="<?php echo htmlentities($patient['PatientContno'] ?? ''); ?>" placeholder="مثال: 77xxxxxxx" maxlength="15">
                                        <small class="form-text text-success">📱 يمكنك تحديث رقم هاتفك هنا</small>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label" for="age">العمر</label>
                                        <input type="number" id="age" name="age" class="form-control" value="<?php echo htmlentities($patient['PatientAge'] ?? ''); ?>" placeholder="اكتب عمرك" min="1" max="120">
                                        <small class="form-text text-success">🎂 يمكنك تحديث عمرك هنا</small>
                                    </div>

                                    <div class="col-md-12">
                                        <label class="form-label" for="address">العنوان</label>
                                        <textarea id="address" name="address" class="form-control" rows="3" placeholder="اكتب عنوانك الكامل..."><?php echo htmlentities($user['address'] ?? ''); ?></textarea>
                                        <small class="form-text text-success">🏠 يمكنك تحديث عنوانك هنا</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-warning mt-3">
                            <h6><i class="fa fa-shield-alt"></i> سياسة الحماية:</h6>
                            <ul class="mb-0">
                                <li>البيانات الأساسية (الاسم، البريد، الجنس، المدينة) محمية لأسباب أمنية</li>
                                <li>لتعديل البيانات المحمية، يرجى التواصل مع الإدارة</li>
                                <li>يمكنك تعديل معلومات الاتصال والبيانات الشخصية فقط</li>
                            </ul>
                        </div>

                        <div class="mt-4 d-flex gap-2">
                            <button type="submit" name="submit" class="btn btn-primary">
                                <i class="fa fa-save"></i> حفظ التحديثات المسموحة
                            </button>
                            <a href="dashboard.php" class="btn btn-outline-secondary">
                                <i class="fa fa-arrow-right"></i> رجوع للوحة
                            </a>
                        </div>
                    </form>
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
    <script>
        jQuery(function() {
            if (window.Main) Main.init();
        });
    </script>
</body>

</html>