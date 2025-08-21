<?php
require_once 'hms/include/config.php';

echo "<h2>🧪 اختبار صفحة الملف الطبي</h2>";

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
        echo "<p><a href='hms/my-medical-profile.php' target='_blank' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔗 فتح الملف الطبي</a></p>";
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
    // User is logged in, show profile info
    $userId = (int)$_SESSION['id'];
    
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>✅ جلسة نشطة</h3>";
    echo "<p><strong>User ID:</strong> $userId</p>";
    
    // Get user info
    $userStmt = mysqli_prepare($con, "SELECT fullName, email, gender, city, address FROM users WHERE id = ?");
    mysqli_stmt_bind_param($userStmt, 'i', $userId);
    mysqli_stmt_execute($userStmt);
    $userResult = mysqli_stmt_get_result($userStmt);
    $userInfo = mysqli_fetch_assoc($userResult);
    mysqli_stmt_close($userStmt);
    
    if ($userInfo) {
        echo "<p><strong>الاسم:</strong> " . htmlspecialchars($userInfo['fullName']) . "</p>";
        echo "<p><strong>البريد:</strong> " . htmlspecialchars($userInfo['email']) . "</p>";
        echo "<p><strong>الجنس:</strong> " . htmlspecialchars($userInfo['gender'] ?? 'غير محدد') . "</p>";
        echo "<p><strong>المدينة:</strong> " . htmlspecialchars($userInfo['city'] ?? 'غير محدد') . "</p>";
        
        // Check if patient record exists
        $patientStmt = mysqli_prepare($con, "SELECT * FROM tblpatient WHERE PatientEmail = ?");
        mysqli_stmt_bind_param($patientStmt, 's', $userInfo['email']);
        mysqli_stmt_execute($patientStmt);
        $patientResult = mysqli_stmt_get_result($patientStmt);
        $patientInfo = mysqli_fetch_assoc($patientResult);
        mysqli_stmt_close($patientStmt);
        
        if ($patientInfo) {
            echo "<p><strong>سجل المريض:</strong> ✅ موجود</p>";
            echo "<p><strong>رقم الهاتف:</strong> " . htmlspecialchars($patientInfo['PatientContno'] ?? 'غير محدد') . "</p>";
            echo "<p><strong>العمر:</strong> " . htmlspecialchars($patientInfo['PatientAge'] ?? 'غير محدد') . "</p>";
        } else {
            echo "<p><strong>سجل المريض:</strong> ❌ غير موجود (سيتم إنشاؤه عند التحديث)</p>";
        }
        
        echo "<p><a href='hms/my-medical-profile.php' target='_blank' style='background: #007bff; color: white; padding: 12px 25px; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 16px;'>🏥 فتح الملف الطبي</a></p>";
    } else {
        echo "<p style='color: #dc3545;'><strong>خطأ:</strong> لا يوجد مستخدم بهذا الـ ID!</p>";
    }
    echo "</div>";
}

// Test data creation
if (isset($_POST['create_test_data']) && isset($_SESSION['id'])) {
    $userId = (int)$_SESSION['id'];
    
    // Get user email
    $userStmt = mysqli_prepare($con, "SELECT email, fullName FROM users WHERE id = ?");
    mysqli_stmt_bind_param($userStmt, 'i', $userId);
    mysqli_stmt_execute($userStmt);
    $userResult = mysqli_stmt_get_result($userStmt);
    $userInfo = mysqli_fetch_assoc($userResult);
    mysqli_stmt_close($userStmt);
    
    if ($userInfo) {
        // Check if patient record exists
        $checkStmt = mysqli_prepare($con, "SELECT ID FROM tblpatient WHERE PatientEmail = ?");
        mysqli_stmt_bind_param($checkStmt, 's', $userInfo['email']);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);
        $exists = mysqli_fetch_assoc($checkResult);
        mysqli_stmt_close($checkStmt);
        
        if (!$exists) {
            // Create patient record
            $yemeniPrefixes = ['77', '73', '70', '71', '78'];
            $prefix = $yemeniPrefixes[array_rand($yemeniPrefixes)];
            $phoneNumber = $prefix . rand(1000000, 9999999);
            $age = rand(20, 70);
            $gender = rand(0, 1) ? 'ذكر' : 'أنثى';
            $address = 'صنعاء - حي ' . rand(1, 10);
            $medicalHistory = 'لا يوجد تاريخ مرضي مهم';
            
            $insertStmt = mysqli_prepare($con, "
                INSERT INTO tblpatient 
                (PatientName, PatientEmail, PatientContno, PatientGender, PatientAdd, PatientAge, PatientMedhis, Docid, CreationDate)
                VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())
            ");
            mysqli_stmt_bind_param($insertStmt, 'ssssssi', 
                $userInfo['fullName'], 
                $userInfo['email'], 
                $phoneNumber, 
                $gender, 
                $address, 
                $age, 
                $medicalHistory
            );
            
            if (mysqli_stmt_execute($insertStmt)) {
                echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
                echo "<h4>✅ تم إنشاء بيانات تجريبية!</h4>";
                echo "<p><strong>رقم الهاتف:</strong> $phoneNumber</p>";
                echo "<p><strong>العمر:</strong> $age</p>";
                echo "<p><strong>الجنس:</strong> $gender</p>";
                echo "<p><strong>العنوان:</strong> $address</p>";
                echo "</div>";
            } else {
                echo "<div style='background: #f8d7da; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
                echo "<h4>❌ فشل في إنشاء البيانات</h4>";
                echo "<p>خطأ: " . mysqli_error($con) . "</p>";
                echo "</div>";
            }
            mysqli_stmt_close($insertStmt);
        } else {
            echo "<div style='background: #fff3cd; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
            echo "<h4>⚠️ البيانات موجودة بالفعل</h4>";
            echo "<p>يوجد سجل مريض لهذا المستخدم بالفعل.</p>";
            echo "</div>";
        }
    }
}

// Features overview
echo "<h3>🎯 ميزات الملف الطبي الجديد</h3>";
echo "<div style='background: #e3f2fd; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h4>✨ الميزات المتوفرة:</h4>";
echo "<ul>";
echo "<li><strong>📊 إحصائيات شاملة:</strong> عدد المواعيد، المواعيد النشطة، آخر زيارة</li>";
echo "<li><strong>👤 معلومات شخصية:</strong> عرض جميع البيانات الحالية بشكل منظم</li>";
echo "<li><strong>✏️ تحديث البيانات:</strong> تعديل الاسم، الجنس، المدينة، العنوان</li>";
echo "<li><strong>📱 معلومات طبية:</strong> رقم الهاتف، العمر، التاريخ المرضي</li>";
echo "<li><strong>🔄 مزامنة تلقائية:</strong> ربط بيانات المستخدم مع سجل المريض</li>";
echo "<li><strong>📱 تصميم متجاوب:</strong> يعمل على جميع الأجهزة</li>";
echo "<li><strong>🎨 واجهة جميلة:</strong> تصميم عصري وسهل الاستخدام</li>";
echo "</ul>";
echo "</div>";

// Test buttons
if (isset($_SESSION['id'])) {
    echo "<div style='background: #fff3cd; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h4>🧪 أدوات الاختبار</h4>";
    echo "<form method='post' style='display: inline; margin-right: 10px;'>";
    echo "<button type='submit' name='create_test_data' style='background: #17a2b8; color: white; padding: 12px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;'>";
    echo "📝 إنشاء بيانات تجريبية";
    echo "</button>";
    echo "</form>";
    
    echo "<a href='hms/my-medical-profile.php' target='_blank' style='background: #28a745; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;'>";
    echo "🏥 فتح الملف الطبي";
    echo "</a>";
    echo "</div>";
}

// Quick links
echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h4>🔗 روابط سريعة</h4>";
echo "<ul>";
echo "<li><a href='hms/my-medical-profile.php' target='_blank' style='color: #007bff; font-weight: bold;'>🏥 الملف الطبي الجديد</a></li>";
echo "<li><a href='hms/edit-profile.php' target='_blank' style='color: #6c757d;'>📝 صفحة التعديل القديمة</a></li>";
echo "<li><a href='hms/dashboard.php' target='_blank' style='color: #28a745;'>🏠 لوحة تحكم المريض</a></li>";
echo "<li><a href='hms/appointment-history.php' target='_blank' style='color: #17a2b8;'>📅 سجل المواعيد</a></li>";
echo "<li><a href='hms/user-login.php' target='_blank' style='color: #ffc107;'>🔐 تسجيل الدخول</a></li>";
echo "</ul>";

if (isset($_SESSION['id'])) {
    echo "<p><a href='?logout=1' style='background: #dc3545; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px;'>🚪 تسجيل الخروج</a></p>";
}
echo "</div>";

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ?');
    exit;
}

mysqli_close($con);
?>
