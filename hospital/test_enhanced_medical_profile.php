<?php
require_once 'hms/include/config.php';

echo "<h1>🧪 اختبار الملف الطبي المحسن</h1>";

// Create test session if needed
session_start();
if (!isset($_SESSION['id'])) {
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>❌ لا توجد جلسة نشطة</h3>";
    echo "<p>تحتاج لتسجيل الدخول أو إنشاء جلسة تجريبية لاختبار الصفحة.</p>";
    
    if (isset($_POST['create_session'])) {
        $testUserId = (int)$_POST['test_user_id'];
        $_SESSION['id'] = $testUserId;
        $_SESSION['login'] = 'test_session';
        
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
        echo "<h4>✅ تم إنشاء جلسة تجريبية!</h4>";
        echo "<p><strong>User ID:</strong> $testUserId</p>";
        echo "<p><a href='hms/my-medical-profile.php' target='_blank' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔗 فتح الملف الطبي المحسن</a></p>";
        echo "</div>";
    }
    
    // Show available users
    $users = [];
    $userResult = mysqli_query($con, "SELECT id, fullName, email FROM users LIMIT 10");
    while ($row = mysqli_fetch_assoc($userResult)) {
        $users[] = $row;
    }
    
    if (!empty($users)) {
        echo "<h4>👤 إنشاء جلسة تجريبية</h4>";
        echo "<form method='post' style='background: #fff3cd; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
        echo "<label><strong>اختر مستخدم للاختبار:</strong></label>";
        echo "<select name='test_user_id' required style='width: 100%; padding: 8px; margin: 10px 0;'>";
        echo "<option value=''>-- اختر مستخدم --</option>";
        foreach ($users as $user) {
            echo "<option value='" . $user['id'] . "'>" . htmlspecialchars($user['fullName'] . ' (' . $user['email'] . ')') . "</option>";
        }
        echo "</select>";
        echo "<button type='submit' name='create_session' style='background: #ffc107; color: #212529; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;'>";
        echo "🔐 إنشاء جلسة تجريبية";
        echo "</button>";
        echo "</form>";
    }
    echo "</div>";
} else {
    // User is logged in, show enhanced profile info
    $userId = (int)$_SESSION['id'];
    
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>✅ جلسة نشطة - اختبار البيانات المحسنة</h3>";
    echo "<p><strong>User ID:</strong> $userId</p>";
    
    // Get comprehensive user info
    $userStmt = mysqli_prepare($con, "SELECT id, fullName, email, gender, city, address, regDate, updationDate FROM users WHERE id = ?");
    mysqli_stmt_bind_param($userStmt, 'i', $userId);
    mysqli_stmt_execute($userStmt);
    $userResult = mysqli_stmt_get_result($userStmt);
    $userInfo = mysqli_fetch_assoc($userResult);
    mysqli_stmt_close($userStmt);
    
    if ($userInfo) {
        echo "<div style='display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0;'>";
        
        // User data column
        echo "<div style='background: white; padding: 15px; border-radius: 8px; border-left: 4px solid #007bff;'>";
        echo "<h4>👤 بيانات المستخدم</h4>";
        echo "<p><strong>الاسم:</strong> " . htmlspecialchars($userInfo['fullName']) . "</p>";
        echo "<p><strong>البريد:</strong> " . htmlspecialchars($userInfo['email']) . "</p>";
        echo "<p><strong>الجنس:</strong> " . htmlspecialchars($userInfo['gender'] ?? 'غير محدد') . "</p>";
        echo "<p><strong>المدينة:</strong> " . htmlspecialchars($userInfo['city'] ?? 'غير محدد') . "</p>";
        echo "<p><strong>العنوان:</strong> " . htmlspecialchars($userInfo['address'] ?? 'غير محدد') . "</p>";
        echo "<p><strong>تاريخ التسجيل:</strong> " . ($userInfo['regDate'] ? date('Y-m-d H:i', strtotime($userInfo['regDate'])) : 'غير محدد') . "</p>";
        echo "<p><strong>آخر تحديث:</strong> " . ($userInfo['updationDate'] ? date('Y-m-d H:i', strtotime($userInfo['updationDate'])) : 'لم يتم التحديث') . "</p>";
        echo "</div>";
        
        // Patient data column
        echo "<div style='background: white; padding: 15px; border-radius: 8px; border-left: 4px solid #28a745;'>";
        echo "<h4>🏥 بيانات المريض</h4>";
        
        $patientStmt = mysqli_prepare($con, "SELECT * FROM tblpatient WHERE PatientEmail = ?");
        mysqli_stmt_bind_param($patientStmt, 's', $userInfo['email']);
        mysqli_stmt_execute($patientStmt);
        $patientResult = mysqli_stmt_get_result($patientStmt);
        $patientInfo = mysqli_fetch_assoc($patientResult);
        mysqli_stmt_close($patientStmt);
        
        if ($patientInfo) {
            echo "<p><strong>رقم السجل:</strong> " . htmlspecialchars($patientInfo['ID']) . "</p>";
            echo "<p><strong>اسم المريض:</strong> " . htmlspecialchars($patientInfo['PatientName']) . "</p>";
            echo "<p><strong>رقم الهاتف:</strong> " . htmlspecialchars($patientInfo['PatientContno'] ?? 'غير محدد') . "</p>";
            echo "<p><strong>الجنس:</strong> " . htmlspecialchars($patientInfo['PatientGender'] ?? 'غير محدد') . "</p>";
            echo "<p><strong>العمر:</strong> " . htmlspecialchars($patientInfo['PatientAge'] ?? 'غير محدد') . "</p>";
            echo "<p><strong>العنوان:</strong> " . htmlspecialchars($patientInfo['PatientAdd'] ?? 'غير محدد') . "</p>";
            echo "<p><strong>الطبيب ID:</strong> " . htmlspecialchars($patientInfo['Docid'] ?? 'غير محدد') . "</p>";
            echo "<p><strong>تاريخ الإنشاء:</strong> " . ($patientInfo['CreationDate'] ? date('Y-m-d H:i', strtotime($patientInfo['CreationDate'])) : 'غير محدد') . "</p>";
            echo "<p><strong>آخر تحديث:</strong> " . ($patientInfo['UpdationDate'] ? date('Y-m-d H:i', strtotime($patientInfo['UpdationDate'])) : 'لم يتم التحديث') . "</p>";
            
            if ($patientInfo['PatientMedhis']) {
                echo "<p><strong>التاريخ المرضي:</strong></p>";
                echo "<div style='background: #f8f9fa; padding: 10px; border-radius: 5px; margin-top: 5px;'>";
                echo nl2br(htmlspecialchars($patientInfo['PatientMedhis']));
                echo "</div>";
            }
        } else {
            echo "<p style='color: #dc3545;'><strong>❌ لا يوجد سجل مريض</strong></p>";
            echo "<p>سيتم إنشاء سجل تلقائياً عند التحديث</p>";
        }
        echo "</div>";
        
        echo "</div>";
        
        // Statistics
        echo "<div style='background: #e3f2fd; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
        echo "<h4>📊 إحصائيات المريض</h4>";
        
        // Get appointment statistics
        $appointmentStats = [];
        $appointmentStats['total'] = 0;
        $appointmentStats['active'] = 0;
        $appointmentStats['last_visit'] = null;
        
        $statsStmt = mysqli_prepare($con, "SELECT COUNT(*) as total, SUM(CASE WHEN userStatus = 1 AND doctorStatus = 1 THEN 1 ELSE 0 END) as active, MAX(CONCAT(appointmentDate, ' ', appointmentTime)) as last_visit FROM appointment WHERE userId = ?");
        mysqli_stmt_bind_param($statsStmt, 'i', $userId);
        mysqli_stmt_execute($statsStmt);
        $statsResult = mysqli_stmt_get_result($statsStmt);
        $stats = mysqli_fetch_assoc($statsResult);
        mysqli_stmt_close($statsStmt);
        
        if ($stats) {
            $appointmentStats = $stats;
        }
        
        echo "<div style='display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px;'>";
        echo "<div style='background: white; padding: 15px; border-radius: 8px; text-align: center;'>";
        echo "<div style='font-size: 2rem; font-weight: bold; color: #007bff;'>" . $appointmentStats['total'] . "</div>";
        echo "<div>إجمالي المواعيد</div>";
        echo "</div>";
        echo "<div style='background: white; padding: 15px; border-radius: 8px; text-align: center;'>";
        echo "<div style='font-size: 2rem; font-weight: bold; color: #28a745;'>" . $appointmentStats['active'] . "</div>";
        echo "<div>المواعيد النشطة</div>";
        echo "</div>";
        echo "<div style='background: white; padding: 15px; border-radius: 8px; text-align: center;'>";
        echo "<div style='font-size: 1.2rem; font-weight: bold; color: #6c757d;'>" . ($appointmentStats['last_visit'] ? date('Y-m-d', strtotime($appointmentStats['last_visit'])) : 'لا يوجد') . "</div>";
        echo "<div>آخر زيارة</div>";
        echo "</div>";
        echo "</div>";
        echo "</div>";
        
        echo "<p><a href='hms/my-medical-profile.php' target='_blank' style='background: #007bff; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 18px;'>🏥 فتح الملف الطبي المحسن</a></p>";
    } else {
        echo "<p style='color: #dc3545;'><strong>خطأ:</strong> لا يوجد مستخدم بهذا الـ ID!</p>";
    }
    echo "</div>";
}

// Enhanced features overview
echo "<div style='background: #d1ecf1; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>✨ التحسينات الجديدة في الملف الطبي</h3>";

echo "<div style='display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;'>";

echo "<div>";
echo "<h4>📋 عرض البيانات المحسن:</h4>";
echo "<ul>";
echo "<li>✅ <strong>جميع بيانات المستخدم:</strong> الاسم، البريد، الجنس، المدينة، العنوان</li>";
echo "<li>✅ <strong>جميع بيانات المريض:</strong> رقم السجل، الهاتف، العمر، التاريخ المرضي</li>";
echo "<li>✅ <strong>معلومات الطبيب:</strong> اسم الطبيب المعالج</li>";
echo "<li>✅ <strong>التواريخ المفصلة:</strong> تاريخ التسجيل وآخر تحديث بالساعة والدقيقة</li>";
echo "<li>✅ <strong>إحصائيات شاملة:</strong> عدد المواعيد وآخر زيارة</li>";
echo "</ul>";
echo "</div>";

echo "<div>";
echo "<h4>✏️ تحديث البيانات الشامل:</h4>";
echo "<ul>";
echo "<li>✅ <strong>تعديل البريد الإلكتروني:</strong> مع تحذير من التأثير على تسجيل الدخول</li>";
echo "<li>✅ <strong>تغيير كلمة المرور:</strong> حقل اختياري لتحديث كلمة المرور</li>";
echo "<li>✅ <strong>خيارات الجنس المتعددة:</strong> دعم القيم العربية والإنجليزية</li>";
echo "<li>✅ <strong>جميع البيانات الطبية:</strong> الهاتف، العمر، التاريخ المرضي</li>";
echo "<li>✅ <strong>مزامنة تلقائية:</strong> ربط بيانات المستخدم مع سجل المريض</li>";
echo "</ul>";
echo "</div>";

echo "</div>";
echo "</div>";

// Test buttons
if (isset($_SESSION['id'])) {
    echo "<div style='background: #fff3cd; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h4>🧪 أدوات الاختبار</h4>";
    echo "<div style='display: flex; gap: 15px; flex-wrap: wrap;'>";
    
    echo "<a href='hms/my-medical-profile.php' target='_blank' style='background: #28a745; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;'>";
    echo "🏥 الملف الطبي المحسن";
    echo "</a>";
    
    echo "<a href='hms/edit-profile.php' target='_blank' style='background: #6c757d; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;'>";
    echo "📝 الصفحة القديمة (للمقارنة)";
    echo "</a>";
    
    echo "<a href='hms/dashboard.php' target='_blank' style='background: #007bff; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;'>";
    echo "🏠 لوحة التحكم";
    echo "</a>";
    
    echo "<a href='hms/appointment-history.php' target='_blank' style='background: #17a2b8; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;'>";
    echo "📅 سجل المواعيد";
    echo "</a>";
    
    echo "</div>";
    echo "</div>";
}

// Instructions
echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 5px solid #6c757d;'>";
echo "<h4>📋 تعليمات الاختبار</h4>";
echo "<ol>";
echo "<li><strong>أنشئ جلسة تجريبية</strong> أو سجل الدخول كمريض</li>";
echo "<li><strong>افتح الملف الطبي المحسن</strong> وتحقق من عرض جميع البيانات</li>";
echo "<li><strong>جرب تحديث البيانات</strong> واختبر جميع الحقول الجديدة</li>";
echo "<li><strong>اختبر تغيير البريد الإلكتروني</strong> وكلمة المرور</li>";
echo "<li><strong>تأكد من المزامنة</strong> بين بيانات المستخدم وسجل المريض</li>";
echo "</ol>";

echo "<h4>🔍 ما يجب التحقق منه:</h4>";
echo "<ul>";
echo "<li>✅ عرض جميع البيانات السابقة بشكل صحيح</li>";
echo "<li>✅ إمكانية تعديل جميع الحقول (ليس فقط الاسم والهاتف والمكان)</li>";
echo "<li>✅ ظهور معلومات الطبيب المعالج</li>";
echo "<li>✅ عرض التواريخ بالتفصيل</li>";
echo "<li>✅ الإحصائيات الصحيحة للمواعيد</li>";
echo "</ul>";
echo "</div>";

if (isset($_SESSION['id'])) {
    echo "<p><a href='?logout=1' style='background: #dc3545; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px;'>🚪 تسجيل الخروج</a></p>";
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ?');
    exit;
}

mysqli_close($con);
?>
