<?php
session_start();
require_once 'hms/include/config.php';

echo "<h1>🔍 تشخيص سريع لمشكلة المواعيد</h1>";

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>❌ المشكلة الأساسية: لا توجد جلسة نشطة!</h3>";
    echo "<p>المستخدم غير مسجل الدخول. هذا هو السبب في عدم ظهور أي مواعيد.</p>";
    echo "<h4>🔧 الحلول:</h4>";
    echo "<ol>";
    echo "<li><a href='hms/user-login.php' style='color: #721c24; font-weight: bold;'>تسجيل الدخول كمريض</a></li>";
    echo "<li>أو إنشاء جلسة تجريبية أدناه</li>";
    echo "</ol>";
    echo "</div>";
    
    // Create test session
    if (isset($_POST['create_test_session'])) {
        $testUserId = (int)$_POST['test_user_id'];
        $_SESSION['id'] = $testUserId;
        $_SESSION['login'] = 'test_user@example.com';
        
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
        echo "<h4>✅ تم إنشاء جلسة تجريبية!</h4>";
        echo "<p><strong>User ID:</strong> $testUserId</p>";
        echo "<p><a href='?' style='background: #28a745; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px;'>🔄 إعادة تحميل الصفحة</a></p>";
        echo "</div>";
    }
    
    // Show available users
    $users = [];
    $userResult = mysqli_query($con, "SELECT id, fullName, email FROM users LIMIT 10");
    while ($row = mysqli_fetch_assoc($userResult)) {
        $users[] = $row;
    }
    
    if (!empty($users)) {
        echo "<h3>👤 إنشاء جلسة تجريبية</h3>";
        echo "<form method='post' style='background: #fff3cd; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
        echo "<label><strong>اختر مستخدم للاختبار:</strong></label>";
        echo "<select name='test_user_id' required style='width: 100%; padding: 8px; margin: 10px 0;'>";
        echo "<option value=''>-- اختر مستخدم --</option>";
        foreach ($users as $user) {
            echo "<option value='" . $user['id'] . "'>" . htmlspecialchars($user['fullName'] . ' (' . $user['email'] . ')') . "</option>";
        }
        echo "</select>";
        echo "<button type='submit' name='create_test_session' style='background: #ffc107; color: #212529; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;'>";
        echo "🔐 إنشاء جلسة تجريبية";
        echo "</button>";
        echo "</form>";
    }
    
} else {
    // User is logged in, check appointments
    $userId = (int)$_SESSION['id'];
    
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>✅ المستخدم مسجل الدخول</h3>";
    echo "<p><strong>User ID:</strong> $userId</p>";
    
    // Get user info
    $userStmt = mysqli_prepare($con, "SELECT fullName, email FROM users WHERE id = ?");
    mysqli_stmt_bind_param($userStmt, 'i', $userId);
    mysqli_stmt_execute($userStmt);
    $userResult = mysqli_stmt_get_result($userStmt);
    $userInfo = mysqli_fetch_assoc($userResult);
    mysqli_stmt_close($userStmt);
    
    if ($userInfo) {
        echo "<p><strong>الاسم:</strong> " . htmlspecialchars($userInfo['fullName']) . "</p>";
        echo "<p><strong>البريد:</strong> " . htmlspecialchars($userInfo['email']) . "</p>";
    }
    echo "</div>";
    
    // Check appointments for this user
    echo "<h3>📋 البحث عن مواعيد المستخدم</h3>";
    
    // Simple query first
    $simpleQuery = mysqli_query($con, "SELECT COUNT(*) as count FROM appointment WHERE userId = $userId");
    $appointmentCount = mysqli_fetch_assoc($simpleQuery)['count'];
    
    echo "<p><strong>عدد المواعيد للمستخدم:</strong> $appointmentCount</p>";
    
    if ($appointmentCount == 0) {
        echo "<div style='background: #fff3cd; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
        echo "<h4>⚠️ لا توجد مواعيد لهذا المستخدم</h4>";
        echo "<p><strong>الأسباب المحتملة:</strong></p>";
        echo "<ul>";
        echo "<li>المستخدم لم يحجز أي مواعيد بعد</li>";
        echo "<li>المواعيد محجوزة بـ User ID مختلف</li>";
        echo "<li>تم حذف المواعيد من قاعدة البيانات</li>";
        echo "</ul>";
        
        echo "<h4>🔧 الحلول:</h4>";
        echo "<ol>";
        echo "<li><a href='hms/book-appointment.php' target='_blank' style='color: #856404; font-weight: bold;'>حجز موعد جديد</a></li>";
        echo "<li>أو إنشاء موعد تجريبي أدناه</li>";
        echo "</ol>";
        echo "</div>";
        
        // Create test appointment
        if (isset($_POST['create_test_appointment'])) {
            $doctorId = (int)$_POST['doctor_id'];
            $date = $_POST['date'];
            $time = $_POST['time'];
            
            // Get doctor info
            $docStmt = mysqli_prepare($con, "SELECT doctorName, specilization, docFees FROM doctors WHERE id = ?");
            mysqli_stmt_bind_param($docStmt, 'i', $doctorId);
            mysqli_stmt_execute($docStmt);
            $docResult = mysqli_stmt_get_result($docStmt);
            $docInfo = mysqli_fetch_assoc($docResult);
            mysqli_stmt_close($docStmt);
            
            if ($docInfo) {
                // Insert appointment
                $insertStmt = mysqli_prepare($con, "
                    INSERT INTO appointment 
                    (userId, doctorId, doctorSpecialization, consultancyFees, appointmentDate, appointmentTime, postingDate, userStatus, doctorStatus)
                    VALUES (?, ?, ?, ?, ?, ?, NOW(), 1, 1)
                ");
                
                mysqli_stmt_bind_param($insertStmt, 'iisiss', 
                    $userId, 
                    $doctorId, 
                    $docInfo['specilization'], 
                    $docInfo['docFees'], 
                    $date, 
                    $time
                );
                
                if (mysqli_stmt_execute($insertStmt)) {
                    $newId = mysqli_insert_id($con);
                    echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
                    echo "<h4>✅ تم إنشاء موعد تجريبي!</h4>";
                    echo "<p><strong>رقم الموعد:</strong> $newId</p>";
                    echo "<p><strong>الطبيب:</strong> " . htmlspecialchars($docInfo['doctorName']) . "</p>";
                    echo "<p><strong>التاريخ:</strong> $date في $time</p>";
                    echo "<p><a href='hms/appointment-history.php' target='_blank' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔄 تحقق من الصفحة الآن</a></p>";
                    echo "<p><a href='?' style='background: #007bff; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px;'>🔄 إعادة تحميل هذه الصفحة</a></p>";
                    echo "</div>";
                } else {
                    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
                    echo "<h4>❌ فشل في إنشاء الموعد</h4>";
                    echo "<p>خطأ: " . mysqli_error($con) . "</p>";
                    echo "</div>";
                }
                mysqli_stmt_close($insertStmt);
            }
        }
        
        // Show form to create test appointment
        $doctors = [];
        $doctorResult = mysqli_query($con, "SELECT id, doctorName, specilization FROM doctors LIMIT 5");
        while ($row = mysqli_fetch_assoc($doctorResult)) {
            $doctors[] = $row;
        }
        
        if (!empty($doctors)) {
            echo "<h4>📅 إنشاء موعد تجريبي</h4>";
            echo "<form method='post' style='background: #e3f2fd; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
            
            echo "<div style='margin-bottom: 15px;'>";
            echo "<label><strong>اختر الطبيب:</strong></label>";
            echo "<select name='doctor_id' required style='width: 100%; padding: 8px; margin-top: 5px;'>";
            echo "<option value=''>-- اختر طبيب --</option>";
            foreach ($doctors as $doc) {
                echo "<option value='" . $doc['id'] . "'>" . htmlspecialchars($doc['doctorName'] . ' - ' . $doc['specilization']) . "</option>";
            }
            echo "</select>";
            echo "</div>";
            
            echo "<div style='margin-bottom: 15px;'>";
            echo "<label><strong>التاريخ:</strong></label>";
            echo "<input type='date' name='date' value='" . date('Y-m-d', strtotime('+1 day')) . "' required style='width: 100%; padding: 8px; margin-top: 5px;'>";
            echo "</div>";
            
            echo "<div style='margin-bottom: 15px;'>";
            echo "<label><strong>الوقت:</strong></label>";
            echo "<select name='time' required style='width: 100%; padding: 8px; margin-top: 5px;'>";
            echo "<option value='09:00:00'>09:00 صباحاً</option>";
            echo "<option value='10:00:00'>10:00 صباحاً</option>";
            echo "<option value='11:00:00'>11:00 صباحاً</option>";
            echo "<option value='14:00:00'>02:00 مساءً</option>";
            echo "</select>";
            echo "</div>";
            
            echo "<button type='submit' name='create_test_appointment' style='background: #2196f3; color: white; padding: 12px 25px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;'>";
            echo "📅 إنشاء موعد تجريبي";
            echo "</button>";
            echo "</form>";
        }
        
    } else {
        // User has appointments, show them
        echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
        echo "<h4>✅ تم العثور على مواعيد!</h4>";
        echo "<p>المستخدم لديه $appointmentCount موعد</p>";
        
        // Show the appointments
        $appointmentQuery = mysqli_query($con, "
            SELECT 
                a.id,
                a.appointmentDate,
                a.appointmentTime,
                a.userStatus,
                a.doctorStatus,
                d.doctorName,
                a.doctorSpecialization
            FROM appointment a
            LEFT JOIN doctors d ON d.id = a.doctorId
            WHERE a.userId = $userId
            ORDER BY a.appointmentDate DESC, a.appointmentTime DESC
        ");
        
        echo "<table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>";
        echo "<tr style='background: #28a745; color: white;'>";
        echo "<th style='padding: 10px; border: 1px solid #ddd;'>رقم الموعد</th>";
        echo "<th style='padding: 10px; border: 1px solid #ddd;'>الطبيب</th>";
        echo "<th style='padding: 10px; border: 1px solid #ddd;'>التاريخ</th>";
        echo "<th style='padding: 10px; border: 1px solid #ddd;'>الوقت</th>";
        echo "<th style='padding: 10px; border: 1px solid #ddd;'>الحالة</th>";
        echo "</tr>";
        
        while ($row = mysqli_fetch_assoc($appointmentQuery)) {
            $status = ($row['userStatus'] && $row['doctorStatus']) ? '✅ نشط' : '❌ ملغي';
            
            echo "<tr>";
            echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $row['id'] . "</td>";
            echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($row['doctorName'] ?? 'غير محدد') . "</td>";
            echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $row['appointmentDate'] . "</td>";
            echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $row['appointmentTime'] . "</td>";
            echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $status . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<p><strong>إذا كانت المواعيد تظهر هنا لكن لا تظهر في الصفحة الأصلية، فالمشكلة في:</strong></p>";
        echo "<ul>";
        echo "<li>ملفات CSS أو JavaScript</li>";
        echo "<li>خطأ في كود العرض</li>";
        echo "<li>مشكلة في التخزين المؤقت للمتصفح</li>";
        echo "</ul>";
        echo "</div>";
    }
}

// Quick links
echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 5px solid #6c757d;'>";
echo "<h4>🔗 روابط سريعة:</h4>";
echo "<ul>";
echo "<li><a href='hms/appointment-history.php' target='_blank' style='color: #495057; font-weight: bold; font-size: 16px;'>📋 صفحة سجل المواعيد الأصلية</a></li>";
echo "<li><a href='hms/dashboard.php' target='_blank' style='color: #495057;'>🏠 لوحة تحكم المريض</a></li>";
echo "<li><a href='hms/book-appointment.php' target='_blank' style='color: #495057;'>📅 حجز موعد جديد</a></li>";
echo "<li><a href='hms/user-login.php' target='_blank' style='color: #495057;'>🔐 تسجيل الدخول</a></li>";
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
