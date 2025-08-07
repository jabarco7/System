<?php
session_start();
error_reporting(0);
include('include/config.php');

if (empty($_SESSION['id'])) {
    header('location:logout.php');
    exit();
}

if (isset($_POST['submit'])) {

    // جمع القيم
    $docspecialization_raw = isset($_POST['Doctorspecialization']) ? $_POST['Doctorspecialization'] : '';
    $docname   = trim($_POST['docname'] ?? '');
    $docaddress= trim($_POST['clinicaddress'] ?? '');
    $docfees   = trim($_POST['docfees'] ?? '');
    $doccontactno = trim($_POST['doccontact'] ?? '');
    $docemail  = trim($_POST['docemail'] ?? '');
    $password  = md5($_POST['npass'] ?? '');

    // 1) تنظيف أي حشو مثل "add-doctor.php:269" أو شرطات زائدة
    $docspecialization = preg_replace('/\badd-doctor\.php:\d+\s*/i', '', $docspecialization_raw);
    $docspecialization = trim($docspecialization, " -\t\n\r\0\x0B");

    // 2) التحقق من أن التخصص موجود في جدول doctorspecilization (قائمة بيضاء)
    $check = $con->prepare("SELECT 1 FROM doctorspecilization WHERE specilization = ? LIMIT 1");
    $check->bind_param("s", $docspecialization);
    $check->execute();
    $check->store_result();
    $exists = $check->num_rows > 0;
    $check->close();

    if (!$exists) {
        $_SESSION['error'] = "تخصص غير صالح. يرجى الاختيار من القائمة فقط.";
    } elseif ($docspecialization && $docname && $docaddress && $docfees && $doccontactno && $docemail && $password) {
        // 3) الإدراج الآمن
        $stmt = $con->prepare("INSERT INTO doctors(specilization, doctorName, address, docFees, contactno, docEmail, password)
                               VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $docspecialization, $docname, $docaddress, $docfees, $doccontactno, $docemail, $password);

        if ($stmt->execute()) {
            $_SESSION['success'] = "تمت إضافة معلومات الطبيب بنجاح!";
            $stmt->close();
            header('Location: Manage-doctors.php');
            exit();
        } else {
            $_SESSION['error'] = "حدث خطأ أثناء الإضافة. حاول مرة أخرى.";
            $stmt->close();
        }
    } else {
        $_SESSION['error'] = "يرجى تعبئة جميع الحقول بشكل صحيح.";
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>المسؤول | اضافة طبيب</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        :root{--primary:#3498db;--secondary:#2c3e50;--success:#27ae60;--danger:#e74c3c;--light:#f8f9fa;--dark:#343a40}
        *{font-family:'Tajawal',sans-serif}
        body{background-color:#f0f5f9;padding-top:20px}
        .main-container{max-width:1200px;margin:40px auto 0}
        .page-header{background:linear-gradient(90deg,var(--primary),#4aa8e0);color:#fff;padding:20px;border-radius:10px;margin-bottom:30px;box-shadow:0 5px 15px rgba(0,0,0,.1)}
        .page-header h1{font-weight:700;margin-bottom:0}
        .breadcrumb{background:transparent;padding:0;margin-bottom:0}
        .breadcrumb-item a{color:rgba(255,255,255,.8);text-decoration:none}
        .breadcrumb-item.active{color:#fff}
        .breadcrumb-item+.breadcrumb-item::before{color:rgba(255,255,255,.8);content:">"}
        .form-container{background:#fff;border-radius:10px;padding:25px;box-shadow:0 5px 15px rgba(0,0,0,.05);margin-bottom:30px}
        .form-header{border-bottom:2px solid var(--primary);padding-bottom:15px;margin-bottom:25px}
        .form-header h5{font-weight:700;color:var(--secondary);display:flex;align-items:center;gap:10px}
        .form-header h5 i{color:var(--primary);background:rgba(52,152,219,.1);width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center}
        .form-label{font-weight:600;color:var(--secondary);margin-bottom:8px}
        .form-control,.form-select{padding:12px 15px;border-radius:8px;border:1px solid #ddd;transition:.3s}
        .form-control:focus,.form-select:focus{border-color:var(--primary);box-shadow:0 0 0 .25rem rgba(52,152,219,.25)}
        .btn-submit{background:linear-gradient(90deg,var(--success),#2ecc71);color:#fff;padding:12px 30px;border-radius:8px;font-weight:600;border:none;transition:.3s;display:flex;align-items:center;gap:10px;margin-top:20px}
        .btn-submit:hover{transform:translateY(-3px);box-shadow:0 5px 15px rgba(46,204,113,.3)}
        .form-group{margin-bottom:25px}
        .alert-message{padding:15px 20px;border-radius:8px;font-weight:600;margin-bottom:25px;animation:fadeIn .5s;display:flex;align-items:center;gap:10px}
        .alert-success{background:rgba(39,174,96,.15);color:var(--success);border-left:4px solid var(--success)}
        .alert-error{background:rgba(231,76,60,.15);color:var(--danger);border-left:4px solid var(--danger)}
        @keyframes fadeIn{from{opacity:0;transform:translateY(-10px)}to{opacity:1;transform:translateY(0)}}
        .password-strength{height:5px;border-radius:5px;margin-top:5px;background:#eee;overflow:hidden}
        .password-strength-meter{height:100%;width:0;transition:width .3s}
        .strength-weak{background:var(--danger);width:30%}
        .strength-medium{background:#f39c12;width:60%}
        .strength-strong{background:var(--success);width:100%}
        .password-tips{font-size:.85rem;color:#666;margin-top:5px}
        @media (max-width:768px){.page-header{padding:15px}.form-container{padding:20px}.main-container{padding:0 15px}}
    </style>
</head>
<body>
<div class="main-container">
    <?php include('include/header.php'); ?>

    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h1><i class="fas fa-user-md me-2"></i>إضافة طبيب جديد</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">المسؤول</a></li>
                        <li class="breadcrumb-item active">إضافة طبيب</li>
                    </ol>
                </nav>
            </div>
            <a href="Manage-doctors.php" class="btn btn-light">
                <i class="fas fa-arrow-left me-2"></i>العودة إلى قائمة الأطباء
            </a>
        </div>
    </div>

    <?php if(!empty($_SESSION['success'])): ?>
        <div class="alert-message alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo htmlspecialchars($_SESSION['success - add-doctor.php:124']); unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if(!empty($_SESSION['error'])): ?>
        <div class="alert-message alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($_SESSION['error - add-doctor.php:131']); unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <div class="form-container">
        <div class="form-header">
            <h5><i class="fas fa-user-plus"></i>معلومات الطبيب الأساسية</h5>
        </div>

        <form role="form" name="adddoc" method="post" onsubmit="return valid();">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label" for="Doctorspecialization">تخصص الطبيب</label>
                        <select name="Doctorspecialization" id="Doctorspecialization" class="form-select" required>
                            <option value="">اختر التخصص...</option>
                            <?php
                            $ret = mysqli_query($con, "SELECT specilization FROM doctorspecilization ORDER BY specilization ASC");
                            while ($row = mysqli_fetch_assoc($ret)) {
                                echo '<option value=" - add-doctor.php:150'.htmlentities($row['specilization']).'">'.htmlentities($row['specilization']).'</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label" for="docname">اسم الطبيب</label>
                        <input type="text" name="docname" class="form-control" placeholder="أدخل اسم الطبيب" required>
                    </div>
                </div>

                <div class="col-12">
                    <div class="form-group">
                        <label class="form-label" for="clinicaddress">موقع عيادة الطبيب</label>
                        <textarea name="clinicaddress" class="form-control" placeholder="أدخل موقع عيادة الطبيب" rows="3" required></textarea>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label" for="docfees">رسوم الاستشارة (ريال)</label>
                        <input type="text" name="docfees" class="form-control" placeholder="أدخل رسوم الاستشارة" required>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label" for="doccontact">رقم هاتف الطبيب</label>
                        <input type="text" name="doccontact" class="form-control" placeholder="أدخل رقم الهاتف" required>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label" for="docemail">البريد الإلكتروني</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" id="docemail" name="docemail" class="form-control" placeholder="أدخل البريد الإلكتروني" required onblur="checkemailAvailability()">
                        </div>
                        <div id="email-availability-status" class="mt-2"></div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label" for="npass">كلمة المرور</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" id="npass" name="npass" class="form-control" placeholder="كلمة المرور" required onkeyup="checkPasswordStrength()">
                        </div>
                        <div class="password-strength mt-2">
                            <div class="password-strength-meter" id="password-strength-meter"></div>
                        </div>
                        <div class="password-tips">يجب أن تحتوي كلمة المرور على 8 أحرف على الأقل، وتشمل أرقام وحروف كبيرة وصغيرة</div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label" for="cfpass">تأكيد كلمة المرور</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" name="cfpass" class="form-control" placeholder="تأكيد كلمة المرور" required>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <button type="submit" name="submit" id="submit" class="btn btn-submit">
                        <i class="fas fa-save"></i> حفظ الطبيب
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function valid(){
    const f = document.adddoc;
    const p = f.npass.value, c = f.cfpass.value;
    if (p.length < 8){ alert("كلمة المرور يجب أن تحتوي على 8 أحرف على الأقل!"); return false; }
    if (!/[A-Z]/.test(p) || !/[a-z]/.test(p) || !/[0-9]/.test(p)){ alert("كلمة المرور يجب أن تحتوي على أحرف كبيرة وصغيرة وأرقام!"); return false; }
    if (p !== c){ alert("حقل كلمة المرور وحقل تأكيد كلمة المرور غير متطابقين!"); f.cfpass.focus(); return false; }
    return true;
}
function checkemailAvailability(){
    const email = $("#docemail").val();
    if(!email){ $("#email-availability-status").html(''); return; }
    $("#email-availability-status").html('<div class="text-info"><i class="fas fa-spinner fa-spin"></i> جار التحقق...</div>');
    $.ajax({
        url:"check_availability.php",
        data:'emailid='+encodeURIComponent(email),
        type:"POST",
        success:function(data){ $("#email-availability-status").html(data); },
        error:function(){ $("#email-availability-status").html('<div class="text-danger">خطأ في الاتصال بالخادم</div>'); }
    });
}
function checkPasswordStrength(){
    const password = $("#npass").val();
    const meter = $("#password-strength-meter");
    meter.removeClass("strength-weak strength-medium strength-strong");
    if(!password.length) return;
    let s=0;
    if (password.length>=8) s++;
    if (/[A-Z]/.test(password)) s++;
    if (/[a-z]/.test(password)) s++;
    if (/\d/.test(password)) s++;
    if (/[^A-Za-z0-9]/.test(password)) s++;
    if (s<2) meter.addClass("strength-weak");
    else if (s<4) meter.addClass("strength-medium");
    else meter.addClass("strength-strong");
}
setTimeout(()=>{$('.alert-message').fadeOut('slow');},5000);
</script>
</body>
</html>
