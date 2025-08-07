<?php
session_start();
error_reporting(0);

include('include/config.php');
include_once('include/hardening.php'); // اختياري للتنظيف

if (empty($_SESSION['id'])) {
    header('location:logout.php');
    exit();
}

/* تحقق من المعرّف */
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    $_SESSION['msg'] = 'معرّف غير صالح.';
    header('location: doctor-specilization.php');
    exit();
}

/* تحديث التخصص */
if (isset($_POST['submit'])) {
    $docspecialization_raw = trim($_POST['doctorspecilization'] ?? '');
    // إزالة أي حشو
    $docspecialization = preg_replace('/\badd-doctor\.php:\d+\s*/i', '', $docspecialization_raw);
    $docspecialization = trim($docspecialization, " -\t\n\r\0\x0B");

    if ($docspecialization === '') {
        $_SESSION['msg'] = 'يرجى إدخال اسم التخصص.';
        $_SESSION['msg_type'] = 'error';
    } else {
        $stmt = $con->prepare("UPDATE doctorspecilization SET specilization = ? WHERE id = ?");
        $stmt->bind_param("si", $docspecialization, $id);
        if ($stmt->execute()) {
            $_SESSION['msg'] = "تم تحديث تخصص الطبيب بنجاح!";
            $_SESSION['msg_type'] = 'success';
        } else {
            $_SESSION['msg'] = "حدث خطأ أثناء التحديث: " . $con->error;
            $_SESSION['msg_type'] = 'error';
        }
        $stmt->close();
    }
    header("Location: editdoctorspecialization.php?id=".$id);
    exit();
}

/* جلب السجل */
$stmt = $con->prepare("SELECT specilization FROM doctorspecilization WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();

if (!$row) {
    $_SESSION['msg'] = 'لم يتم العثور على التخصص المطلوب.';
    $_SESSION['msg_type'] = 'error';
    header('location: doctor-specilization.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>المسؤول | تعديل تخصص الطبيب</title>
    
    <!-- الخطوط -->
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    
    <!-- الأيقونات -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #3498db;
            --primary-light: #4aa8e0;
            --secondary: #2c3e50;
            --danger: #e74c3c;
            --success: #2ecc71;
            --warning: #f39c12;
            --info: #17a2b8;
            --light: #f8f9fa;
            --dark: #343a40;
            --border-radius: 12px;
            --box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Tajawal', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
            min-height: 100vh;
            padding: 20px;
            color: #333;
            direction: rtl;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
            margin-bottom: 30px;
            border-bottom: 1px solid rgba(52, 152, 219, 0.2);
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .logo-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            box-shadow: var(--box-shadow);
        }
        
        .logo-text h1 {
            font-weight: 700;
            font-size: 1.8rem;
            color: var(--secondary);
        }
        
        .logo-text p {
            color: var(--primary);
            font-weight: 500;
            margin-top: 4px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--success), #27ae60);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.2rem;
        }
        
        .user-details {
            text-align: left;
        }
        
        .user-details h3 {
            font-size: 1.1rem;
            margin-bottom: 3px;
            color: var(--secondary);
        }
        
        .user-details p {
            font-size: 0.9rem;
            color: var(--primary);
        }
        
        .card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 30px;
            overflow: hidden;
            transition: var(--transition);
            transform: translateY(20px);
            opacity: 0;
        }
        
        .card.show {
            transform: translateY(0);
            opacity: 1;
        }
        
        .card-header {
            background: linear-gradient(90deg, var(--secondary), #34495e);
            color: white;
            padding: 20px;
            font-weight: 600;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .card-body {
            padding: 30px;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
            position: relative;
            overflow: hidden;
            animation: slideIn 0.5s forwards;
        }
        
        @keyframes slideIn {
            from { transform: translateX(30px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        .alert::before {
            content: "";
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            width: 5px;
        }
        
        .alert-success {
            background-color: rgba(46, 204, 113, 0.15);
            color: #27ae60;
        }
        
        .alert-success::before {
            background-color: #27ae60;
        }
        
        .alert-error {
            background-color: rgba(231, 76, 60, 0.15);
            color: #c0392b;
        }
        
        .alert-error::before {
            background-color: #c0392b;
        }
        
        .alert i {
            font-size: 1.4rem;
        }
        
        .alert-success i {
            color: #27ae60;
        }
        
        .alert-error i {
            color: #c0392b;
        }
        
        .alert-close {
            position: absolute;
            top: 10px;
            left: 15px;
            background: transparent;
            border: none;
            color: inherit;
            cursor: pointer;
            font-size: 1.2rem;
            opacity: 0.7;
            transition: var(--transition);
        }
        
        .alert-close:hover {
            opacity: 1;
            transform: scale(1.1);
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: var(--secondary);
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-control {
            width: 100%;
            padding: 15px 20px;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-size: 1.05rem;
            transition: var(--transition);
            background-color: #fafafa;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
            outline: none;
            background-color: white;
        }
        
        .btn {
            padding: 12px 28px;
            border-radius: 10px;
            font-size: 1.05rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            position: relative;
            overflow: hidden;
        }
        
        .btn::after {
            content: "";
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.4) 0%, rgba(255,255,255,0) 70%);
            transform: rotate(30deg);
            opacity: 0;
            transition: var(--transition);
        }
        
        .btn:hover::after {
            opacity: 1;
        }
        
        .btn-primary {
            background: linear-gradient(90deg, var(--primary), var(--primary-light));
            color: white;
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
        }
        
        .btn-primary:hover {
            background: linear-gradient(90deg, #2980b9, #3d9bdb);
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(52, 152, 219, 0.4);
        }
        
        .btn-outline {
            background: transparent;
            border: 1px solid #ddd;
            color: #666;
        }
        
        .btn-outline:hover {
            background-color: #f8f9fa;
            border-color: var(--primary);
            color: var(--primary);
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        
        .page-title {
            background: linear-gradient(90deg, var(--primary), var(--primary-light));
            color: white;
            padding: 25px 30px;
            border-radius: var(--border-radius);
            margin: 20px auto 30px;
            box-shadow: var(--box-shadow);
            position: relative;
            overflow: hidden;
        }
        
        .page-title::before {
            content: "";
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0) 70%);
            transform: rotate(30deg);
        }
        
        .page-title h1 {
            font-weight: 700;
            margin: 0;
            position: relative;
            z-index: 1;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .breadcrumb {
            display: flex;
            gap: 10px;
            list-style: none;
            padding: 0;
            margin: 15px 0 0;
            position: relative;
            z-index: 1;
        }
        
        .breadcrumb li {
            display: flex;
            align-items: center;
            gap: 10px;
            color: rgba(255, 255, 255, 0.9);
        }
        
        .breadcrumb li:not(:last-child)::after {
            content: ">";
            opacity: 0.7;
        }
        
        .breadcrumb li:last-child {
            font-weight: 600;
        }
        
        .info-box {
            background: white;
            border-radius: var(--border-radius);
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: var(--box-shadow);
            border-left: 4px solid var(--primary);
            transform: translateY(20px);
            opacity: 0;
        }
        
        .info-box.show {
            transform: translateY(0);
            opacity: 1;
        }
        
        .info-box h3 {
            color: var(--secondary);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .info-box ul {
            padding-right: 20px;
        }
        
        .info-box li {
            margin-bottom: 10px;
            line-height: 1.6;
            display: flex;
            align-items: flex-start;
            gap: 8px;
        }
        
        .info-box li::before {
            content: "•";
            color: var(--primary);
            font-size: 1.2rem;
        }
        
        .confirmation-message {
            background: white;
            border-radius: var(--border-radius);
            padding: 30px;
            text-align: center;
            box-shadow: var(--box-shadow);
            margin: 30px auto;
            max-width: 600px;
            display: none;
            animation: popIn 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        
        @keyframes popIn {
            0% { transform: scale(0.8); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }
        
        .confirmation-message i {
            font-size: 4rem;
            color: var(--success);
            margin-bottom: 20px;
            display: block;
        }
        
        .confirmation-message h2 {
            color: var(--secondary);
            margin-bottom: 15px;
        }
        
        .confirmation-message p {
            color: #666;
            margin-bottom: 25px;
            font-size: 1.1rem;
            line-height: 1.6;
        }
        
        .confirmation-message .btn {
            margin: 0 10px;
        }
        
        .system-status {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        
        .status-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.95rem;
        }
        
        .status-item i {
            font-size: 1.4rem;
        }
        
        .text-success {
            color: var(--success);
        }
        
        .text-info {
            color: var(--info);
        }
        
        .text-primary {
            color: var(--primary);
        }
        
        .text-warning {
            color: var(--warning);
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            
            .user-info {
                justify-content: center;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
            
            .system-status {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- الهيدر -->
        <div class="header">
            <div class="logo">
                <div class="logo-icon">
                    <i class="fas fa-user-md"></i>
                </div>
                <div class="logo-text">
                    <h1>نظام إدارة المستشفى</h1>
                    <p>لوحة تحكم المسؤول</p>
                </div>
            </div>
            
            <div class="user-info">
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="user-details">
                    <h3>المسؤول</h3>
                    <p>إدارة الأطباء</p>
                </div>
            </div>
        </div>
        
        <!-- عنوان الصفحة -->
        <div class="page-title">
            <h1>
                <i class="fas fa-stethoscope"></i>
                تعديل تخصص الطبيب
            </h1>
            <ul class="breadcrumb">
                <li><span>لوحة التحكم</span></li>
                <li><span>إدارة الأطباء</span></li>
                <li><span>تعديل التخصصات</span></li>
            </ul>
        </div>
        
        <!-- رسالة التأكيد بعد التحديث الناجح -->
        <?php if (isset($_SESSION['msg']) && $_SESSION['msg_type'] == 'success'): ?>
            <div class="confirmation-message" id="confirmationMessage">
                <i class="fas fa-check-circle"></i>
                <h2>تم التحديث بنجاح!</h2>
                <p>تم تحديث تخصص الطبيب بنجاح. سيتم تطبيق التغييرات على جميع الأطباء المرتبطين بهذا التخصص.</p>
                <div>
                    <a href="doctor-specilization.php" class="btn btn-primary">
                        <i class="fas fa-list"></i> العودة إلى قائمة التخصصات
                    </a>
                    <button class="btn btn-outline" onclick="closeConfirmation()">
                        <i class="fas fa-edit"></i> البقاء في الصفحة
                    </button>
                </div>
            </div>
            <?php unset($_SESSION['msg']); ?>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-8">
                <!-- بطاقة التعديل -->
                <div class="card" id="editCard">
                    <div class="card-header">
                        <i class="fas fa-edit"></i>
                        تعديل تخصص الطبيب
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['msg'])): ?>
                            <div class="alert <?php echo $_SESSION['msg_type'] == 'error' ? 'alerterror' : 'alertsuccess'; ?> - editdoctorspecialization.php:654">
                                <i class="<?php echo $_SESSION['msg_type'] == 'error' ? 'fas faexclamationcircle' : 'fas facheckcircle'; ?> - editdoctorspecialization.php:655"></i>
                                <span><?php echo htmlentities($_SESSION['msg - editdoctorspecialization.php:656']); ?></span>
                                <button class="alert-close" onclick="this.parentElement.style.display='none'">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <?php unset($_SESSION['msg']); ?>
                        <?php endif; ?>
                        
                        <form method="post" id="editForm">
                            <div class="form-group">
                                <label for="doctorspecilization">
                                    <i class="fas fa-tag"></i>
                                    اسم التخصص
                                </label>
                                <input
                                    type="text"
                                    id="doctorspecilization"
                                    name="doctorspecilization"
                                    class="form-control"
                                    value="<?php echo htmlspecialchars($row['specilization']); ?> - editdoctorspecialization.php:675"
                                    required
                                    placeholder="أدخل اسم التخصص الطبي"
                                >
                                <small class="text-muted">مثال: أمراض القلب، طب الأطفال، الجراحة العامة</small>
                            </div>
                            
                            <div class="action-buttons">
                                <button type="submit" name="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i>
                                    حفظ التعديلات
                                </button>
                                <a href="doctor-specilization.php" class="btn btn-outline">
                                    <i class="fas fa-arrow-right"></i>
                                    عودة للقائمة
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <!-- معلومات مساعدة -->
                <div class="info-box" id="infoBox">
                    <h3><i class="fas fa-info-circle"></i> معلومات هامة</h3>
                    <ul>
                        <li>تأكد من صحة اسم التخصص قبل الحفظ</li>
                        <li>استخدم أسماء واضحة وموحدة للتخصصات</li>
                        <li>سيؤثر التعديل على جميع الأطباء المرتبطين بهذا التخصص</li>
                        <li>يمكنك إضافة تخصصات جديدة من صفحة إدارة التخصصات</li>
                    </ul>
                </div>
                
                <!-- حالة النظام -->
                <div class="card" id="statusCard">
                    <div class="card-header">
                        <i class="fas fa-tasks"></i>
                        حالة النظام
                    </div>
                    <div class="card-body">
                        <div class="system-status">
                            <div class="status-item">
                                <i class="fas fa-database text-success"></i>
                                <span>قاعدة البيانات: نشطة</span>
                            </div>
                            <div class="status-item">
                                <i class="fas fa-shield-alt text-success"></i>
                                <span>الحماية: مفعلة</span>
                            </div>
                            <div class="status-item">
                                <i class="fas fa-users text-info"></i>
                                <span>عدد الأطباء: 42</span>
                            </div>
                            <div class="status-item">
                                <i class="fas fa-stethoscope text-primary"></i>
                                <span>عدد التخصصات: 12</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- الفوتر -->
        <footer class="footer">
            <div class="text-center p-3">
                <p>© 2023 نظام إدارة المستشفى. جميع الحقوق محفوظة.</p>
                <div class="d-flex justify-content-center gap-3 mt-2">
                    <a href="#" class="text-dark"><i class="fab fa-facebook"></i></a>
                    <a href="#" class="text-dark"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-dark"><i class="fab fa-linkedin"></i></a>
                </div>
            </div>
        </footer>
    </div>
    
    <script>
        // إضافة تأثيرات تفاعلية
        document.addEventListener('DOMContentLoaded', function() {
            // تحريك البطاقات عند التحميل
            setTimeout(() => {
                document.getElementById('editCard').classList.add('show');
                document.getElementById('infoBox').classList.add('show');
                document.getElementById('statusCard').classList.add('show');
            }, 300);
            
            // إظهار رسالة التأكيد إذا كانت موجودة
            const confirmationMessage = document.getElementById('confirmationMessage');
            if (confirmationMessage) {
                setTimeout(() => {
                    confirmationMessage.style.display = 'block';
                }, 500);
            }
            
            // التأكيد قبل إرسال النموذج
            const form = document.getElementById('editForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const input = document.getElementById('doctorspecilization');
                    if (input.value.trim() === '') {
                        e.preventDefault();
                        alert('يرجى إدخال اسم التخصص قبل الحفظ');
                        input.focus();
                    }
                });
            }
        });
        
        // إغلاق رسالة التأكيد
        function closeConfirmation() {
            document.getElementById('confirmationMessage').style.display = 'none';
        }
        
        // إخفاء رسائل التنبيه عند النقر على الزر
        function closeAlert(btn) {
            btn.parentElement.style.display = 'none';
        }
    </script>
</body>
</html>