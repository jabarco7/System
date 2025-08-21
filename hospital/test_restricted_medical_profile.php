<?php
require_once 'hms/include/config.php';

echo "<h1>🔒 اختبار الملف الطبي المقيد</h1>";

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
        echo "<p><a href='hms/my-medical-profile.php' target='_blank' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔗 فتح الملف الطبي المقيد</a></p>";
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
    // User is logged in, show restricted profile info
    $userId = (int)$_SESSION['id'];
    
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>✅ جلسة نشطة - اختبار التقييد</h3>";
    echo "<p><strong>User ID:</strong> $userId</p>";
    
    // Get user info
    $userStmt = mysqli_prepare($con, "SELECT id, fullName, email, gender, city, address, regDate, updationDate FROM users WHERE id = ?");
    mysqli_stmt_bind_param($userStmt, 'i', $userId);
    mysqli_stmt_execute($userStmt);
    $userResult = mysqli_stmt_get_result($userStmt);
    $userInfo = mysqli_fetch_assoc($userResult);
    mysqli_stmt_close($userStmt);
    
    // Get patient info
    $patientInfo = null;
    if ($userInfo) {
        $patientStmt = mysqli_prepare($con, "SELECT * FROM tblpatient WHERE PatientEmail = ?");
        mysqli_stmt_bind_param($patientStmt, 's', $userInfo['email']);
        mysqli_stmt_execute($patientStmt);
        $patientResult = mysqli_stmt_get_result($patientStmt);
        $patientInfo = mysqli_fetch_assoc($patientResult);
        mysqli_stmt_close($patientStmt);
    }
    
    if ($userInfo) {
        echo "<div style='display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0;'>";
        
        // Protected data column
        echo "<div style='background: white; padding: 15px; border-radius: 8px; border-left: 4px solid #dc3545;'>";
        echo "<h4>🔒 البيانات المحمية (غير قابلة للتعديل)</h4>";
        echo "<p><strong>الاسم:</strong> " . htmlspecialchars($userInfo['fullName']) . "</p>";
        echo "<p><strong>البريد:</strong> " . htmlspecialchars($userInfo['email']) . "</p>";
        echo "<p><strong>الجنس:</strong> " . htmlspecialchars($userInfo['gender'] ?? 'غير محدد') . "</p>";
        echo "<p><strong>المدينة:</strong> " . htmlspecialchars($userInfo['city'] ?? 'غير محدد') . "</p>";
        echo "<p><strong>تاريخ التسجيل:</strong> " . ($userInfo['regDate'] ? date('Y-m-d', strtotime($userInfo['regDate'])) : 'غير محدد') . "</p>";
        echo "<p style='color: #dc3545; font-weight: bold;'>❌ هذه البيانات لا يمكن تعديلها من قبل المريض</p>";
        echo "</div>";
        
        // Editable data column
        echo "<div style='background: white; padding: 15px; border-radius: 8px; border-left: 4px solid #28a745;'>";
        echo "<h4>✏️ البيانات القابلة للتعديل</h4>";
        echo "<p><strong>رقم الهاتف:</strong> " . htmlspecialchars($patientInfo['PatientContno'] ?? 'غير محدد') . "</p>";
        echo "<p><strong>العنوان:</strong> " . htmlspecialchars($userInfo['address'] ?? 'غير محدد') . "</p>";
        echo "<p><strong>العمر:</strong> " . htmlspecialchars($patientInfo['PatientAge'] ?? 'غير محدد') . "</p>";
        echo "<p><strong>آخر تحديث:</strong> " . ($userInfo['updationDate'] ? date('Y-m-d H:i', strtotime($userInfo['updationDate'])) : 'لم يتم التحديث') . "</p>";
        echo "<p style='color: #28a745; font-weight: bold;'>✅ هذه البيانات يمكن تعديلها من قبل المريض</p>";
        echo "</div>";
        
        echo "</div>";
        
        echo "<p><a href='hms/my-medical-profile.php' target='_blank' style='background: #007bff; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 18px;'>🏥 فتح الملف الطبي المقيد</a></p>";
    } else {
        echo "<p style='color: #dc3545;'><strong>خطأ:</strong> لا يوجد مستخدم بهذا الـ ID!</p>";
    }
    echo "</div>";
}

// Restriction policy explanation
echo "<div style='background: #e3f2fd; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>🛡️ سياسة التقييد الجديدة</h3>";

echo "<div style='display: grid; grid-template-columns: 1fr 1fr; gap: 20px;'>";

echo "<div>";
echo "<h4 style='color: #dc3545;'>🔒 البيانات المحمية (غير قابلة للتعديل):</h4>";
echo "<ul>";
echo "<li>✋ <strong>الاسم الكامل:</strong> محمي لأسباب أمنية</li>";
echo "<li>✋ <strong>البريد الإلكتروني:</strong> مرتبط بتسجيل الدخول</li>";
echo "<li>✋ <strong>الجنس:</strong> بيانات أساسية محمية</li>";
echo "<li>✋ <strong>المدينة:</strong> بيانات إدارية محمية</li>";
echo "<li>✋ <strong>كلمة المرور:</strong> تم إزالتها من نموذج التعديل</li>";
echo "<li>✋ <strong>التاريخ المرضي:</strong> يحتاج موافقة طبية</li>";
echo "</ul>";
echo "</div>";

echo "<div>";
echo "<h4 style='color: #28a745;'>✏️ البيانات القابلة للتعديل:</h4>";
echo "<ul>";
echo "<li>✅ <strong>رقم الهاتف:</strong> معلومات اتصال شخصية</li>";
echo "<li>✅ <strong>العنوان:</strong> معلومات سكن قابلة للتغيير</li>";
echo "<li>✅ <strong>العمر:</strong> بيانات شخصية قابلة للتحديث</li>";
echo "</ul>";
echo "<p style='color: #28a745; font-weight: bold;'>💡 هذه البيانات فقط يمكن للمريض تعديلها بنفسه</p>";
echo "</div>";

echo "</div>";
echo "</div>";

// Benefits of restriction
echo "<div style='background: #fff3cd; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>🎯 فوائد التقييد</h3>";

echo "<div style='display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px;'>";

echo "<div style='background: white; padding: 15px; border-radius: 8px; text-align: center;'>";
echo "<div style='font-size: 2rem; margin-bottom: 10px;'>🔐</div>";
echo "<h4>الأمان</h4>";
echo "<p>حماية البيانات الحساسة من التعديل غير المصرح به</p>";
echo "</div>";

echo "<div style='background: white; padding: 15px; border-radius: 8px; text-align: center;'>";
echo "<div style='font-size: 2rem; margin-bottom: 10px;'>⚡</div>";
echo "<h4>البساطة</h4>";
echo "<p>واجهة أبسط تركز على البيانات القابلة للتعديل فقط</p>";
echo "</div>";

echo "<div style='background: white; padding: 15px; border-radius: 8px; text-align: center;'>";
echo "<div style='font-size: 2rem; margin-bottom: 10px;'>🎯</div>";
echo "<h4>التحكم</h4>";
echo "<p>تحكم أفضل في صلاحيات المرضى والبيانات الطبية</p>";
echo "</div>";

echo "</div>";
echo "</div>";

// Test buttons
if (isset($_SESSION['id'])) {
    echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h4>🧪 أدوات الاختبار</h4>";
    echo "<div style='display: flex; gap: 15px; flex-wrap: wrap;'>";
    
    echo "<a href='hms/my-medical-profile.php' target='_blank' style='background: #28a745; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;'>";
    echo "🏥 الملف الطبي المقيد";
    echo "</a>";
    
    echo "<a href='test_enhanced_medical_profile.php' target='_blank' style='background: #6c757d; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;'>";
    echo "📝 النسخة السابقة (للمقارنة)";
    echo "</a>";
    
    echo "<a href='hms/dashboard.php' target='_blank' style='background: #007bff; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;'>";
    echo "🏠 لوحة التحكم";
    echo "</a>";
    
    echo "</div>";
    echo "</div>";
}

// Instructions
echo "<div style='background: #d1ecf1; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h4>📋 تعليمات الاختبار</h4>";
echo "<ol>";
echo "<li><strong>أنشئ جلسة تجريبية</strong> أو سجل الدخول كمريض</li>";
echo "<li><strong>افتح الملف الطبي المقيد</strong> وتحقق من التقييد</li>";
echo "<li><strong>تأكد من أن البيانات المحمية</strong> تظهر كـ readonly</li>";
echo "<li><strong>جرب تعديل البيانات المسموحة فقط</strong> (الهاتف، العنوان، العمر)</li>";
echo "<li><strong>تأكد من عدم وجود حقول</strong> للاسم أو البريد أو كلمة المرور</li>";
echo "</ol>";

echo "<h4>🔍 ما يجب التحقق منه:</h4>";
echo "<ul>";
echo "<li>✅ البيانات المحمية تظهر كـ readonly مع خلفية رمادية</li>";
echo "<li>✅ فقط 3 حقول قابلة للتعديل: الهاتف، العنوان، العمر</li>";
echo "<li>✅ رسائل واضحة تشرح سياسة التقييد</li>";
echo "<li>✅ التحديث يعمل فقط للحقول المسموحة</li>";
echo "<li>✅ لا توجد حقول لتعديل الاسم أو البريد أو كلمة المرور</li>";
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
