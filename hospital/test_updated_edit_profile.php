<?php
require_once 'hms/include/config.php';

echo "<h1>🔄 اختبار صفحة edit-profile.php المحدثة</h1>";

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
        echo "<p><a href='hms/edit-profile.php' target='_blank' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔗 فتح صفحة الملف الطبي المحدثة</a></p>";
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
    // User is logged in, show profile comparison
    $userId = (int)$_SESSION['id'];
    
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>✅ جلسة نشطة - مقارنة البيانات</h3>";
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
        $patientStmt = mysqli_prepare($con, "SELECT PatientContno, PatientAge, PatientMedhis FROM tblpatient WHERE PatientEmail = ?");
        mysqli_stmt_bind_param($patientStmt, 's', $userInfo['email']);
        mysqli_stmt_execute($patientStmt);
        $patientResult = mysqli_stmt_get_result($patientStmt);
        $patientInfo = mysqli_fetch_assoc($patientResult);
        mysqli_stmt_close($patientStmt);
    }
    
    if ($userInfo) {
        echo "<div style='display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0;'>";
        
        // Before (all data was editable)
        echo "<div style='background: white; padding: 15px; border-radius: 8px; border-left: 4px solid #dc3545;'>";
        echo "<h4>❌ قبل التحديث (كان يمكن تعديل كل شيء)</h4>";
        echo "<p><strong>الاسم:</strong> " . htmlspecialchars($userInfo['fullName']) . " <span style='color: #dc3545;'>✏️ قابل للتعديل</span></p>";
        echo "<p><strong>البريد:</strong> " . htmlspecialchars($userInfo['email']) . " <span style='color: #dc3545;'>✏️ قابل للتعديل</span></p>";
        echo "<p><strong>الجنس:</strong> " . htmlspecialchars($userInfo['gender'] ?? 'غير محدد') . " <span style='color: #dc3545;'>✏️ قابل للتعديل</span></p>";
        echo "<p><strong>المدينة:</strong> " . htmlspecialchars($userInfo['city'] ?? 'غير محدد') . " <span style='color: #dc3545;'>✏️ قابل للتعديل</span></p>";
        echo "<p><strong>العنوان:</strong> " . htmlspecialchars($userInfo['address'] ?? 'غير محدد') . " <span style='color: #dc3545;'>✏️ قابل للتعديل</span></p>";
        echo "<p><strong>رقم الهاتف:</strong> " . htmlspecialchars($patientInfo['PatientContno'] ?? 'غير محدد') . " <span style='color: #dc3545;'>✏️ قابل للتعديل</span></p>";
        echo "<p style='color: #dc3545; font-weight: bold;'>⚠️ مشكلة: كل البيانات قابلة للتعديل</p>";
        echo "</div>";
        
        // After (restricted editing)
        echo "<div style='background: white; padding: 15px; border-radius: 8px; border-left: 4px solid #28a745;'>";
        echo "<h4>✅ بعد التحديث (تعديل مقيد)</h4>";
        echo "<p><strong>الاسم:</strong> " . htmlspecialchars($userInfo['fullName']) . " <span style='color: #6c757d;'>🔒 محمي</span></p>";
        echo "<p><strong>البريد:</strong> " . htmlspecialchars($userInfo['email']) . " <span style='color: #6c757d;'>🔒 محمي</span></p>";
        echo "<p><strong>الجنس:</strong> " . htmlspecialchars($userInfo['gender'] ?? 'غير محدد') . " <span style='color: #6c757d;'>🔒 محمي</span></p>";
        echo "<p><strong>المدينة:</strong> " . htmlspecialchars($userInfo['city'] ?? 'غير محدد') . " <span style='color: #6c757d;'>🔒 محمي</span></p>";
        echo "<p><strong>العنوان:</strong> " . htmlspecialchars($userInfo['address'] ?? 'غير محدد') . " <span style='color: #28a745;'>✏️ قابل للتعديل</span></p>";
        echo "<p><strong>رقم الهاتف:</strong> " . htmlspecialchars($patientInfo['PatientContno'] ?? 'غير محدد') . " <span style='color: #28a745;'>✏️ قابل للتعديل</span></p>";
        echo "<p><strong>العمر:</strong> " . htmlspecialchars($patientInfo['PatientAge'] ?? 'غير محدد') . " <span style='color: #28a745;'>✏️ قابل للتعديل</span></p>";
        echo "<p style='color: #28a745; font-weight: bold;'>✅ محسن: فقط 3 حقول قابلة للتعديل</p>";
        echo "</div>";
        
        echo "</div>";
        
        echo "<p><a href='hms/edit-profile.php' target='_blank' style='background: #007bff; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 18px;'>🏥 فتح صفحة الملف الطبي المحدثة</a></p>";
    } else {
        echo "<p style='color: #dc3545;'><strong>خطأ:</strong> لا يوجد مستخدم بهذا الـ ID!</p>";
    }
    echo "</div>";
}

// Summary of changes
echo "<div style='background: #e3f2fd; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>📋 ملخص التحديثات على edit-profile.php</h3>";

echo "<div style='display: grid; grid-template-columns: 1fr 1fr; gap: 20px;'>";

echo "<div>";
echo "<h4 style='color: #dc3545;'>❌ ما تم إزالته:</h4>";
echo "<ul>";
echo "<li>🚫 حقل تعديل الاسم الكامل</li>";
echo "<li>🚫 حقل تعديل البريد الإلكتروني</li>";
echo "<li>🚫 حقل تعديل الجنس</li>";
echo "<li>🚫 حقل تعديل المدينة</li>";
echo "<li>🚫 إمكانية تعديل البيانات الأساسية</li>";
echo "</ul>";
echo "</div>";

echo "<div>";
echo "<h4 style='color: #28a745;'>✅ ما تم إضافته:</h4>";
echo "<ul>";
echo "<li>✅ قسم البيانات المحمية (للعرض فقط)</li>";
echo "<li>✅ قسم البيانات القابلة للتعديل</li>";
echo "<li>✅ حقل العمر للتعديل</li>";
echo "<li>✅ رسائل توضيحية للسياسة</li>";
echo "<li>✅ تصميم منظم بألوان مميزة</li>";
echo "</ul>";
echo "</div>";

echo "</div>";
echo "</div>";

// Technical details
echo "<div style='background: #fff3cd; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>🔧 التفاصيل التقنية</h3>";

echo "<h4>📝 التغييرات في الكود:</h4>";
echo "<ul>";
echo "<li><strong>معالجة النموذج:</strong> تم تقييد المعالجة لتشمل فقط الهاتف والعنوان والعمر</li>";
echo "<li><strong>قاعدة البيانات:</strong> تحديث محدود للحقول المسموحة فقط</li>";
echo "<li><strong>الواجهة:</strong> فصل البيانات المحمية عن القابلة للتعديل</li>";
echo "<li><strong>الأمان:</strong> حقول readonly للبيانات المحمية</li>";
echo "</ul>";

echo "<h4>🎨 التحسينات البصرية:</h4>";
echo "<ul>";
echo "<li><strong>ألوان مميزة:</strong> رمادي للمحمي، أخضر للقابل للتعديل</li>";
echo "<li><strong>أيقونات واضحة:</strong> قفل للمحمي، قلم للقابل للتعديل</li>";
echo "<li><strong>رسائل توضيحية:</strong> شرح السياسة والقيود</li>";
echo "<li><strong>تنظيم أفضل:</strong> كروت منفصلة لكل نوع بيانات</li>";
echo "</ul>";
echo "</div>";

// Test buttons
if (isset($_SESSION['id'])) {
    echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h4>🧪 أدوات الاختبار</h4>";
    echo "<div style='display: flex; gap: 15px; flex-wrap: wrap;'>";
    
    echo "<a href='hms/edit-profile.php' target='_blank' style='background: #28a745; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;'>";
    echo "🏥 صفحة الملف الطبي المحدثة";
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
echo "<div style='background: #d1ecf1; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h4>📋 تعليمات الاختبار</h4>";
echo "<ol>";
echo "<li><strong>أنشئ جلسة تجريبية</strong> أو سجل الدخول كمريض</li>";
echo "<li><strong>افتح صفحة edit-profile.php</strong> وتحقق من التقييد</li>";
echo "<li><strong>تأكد من أن البيانات المحمية</strong> تظهر في قسم منفصل كـ readonly</li>";
echo "<li><strong>جرب تعديل البيانات المسموحة فقط</strong> (الهاتف، العنوان، العمر)</li>";
echo "<li><strong>تأكد من عمل التحديث</strong> للحقول المسموحة فقط</li>";
echo "</ol>";

echo "<h4>🔍 ما يجب التحقق منه:</h4>";
echo "<ul>";
echo "<li>✅ البيانات المحمية في قسم منفصل بخلفية رمادية</li>";
echo "<li>✅ البيانات القابلة للتعديل في قسم منفصل بخلفية خضراء</li>";
echo "<li>✅ فقط 3 حقول قابلة للتعديل: الهاتف، العنوان، العمر</li>";
echo "<li>✅ رسائل واضحة تشرح سياسة التقييد</li>";
echo "<li>✅ التحديث يعمل فقط للحقول المسموحة</li>";
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
