<?php
require_once 'hms/include/config.php';

echo "<h2>🔍 تشخيص مشكلة عدم ظهور الحجوزات</h2>";

// 1. Check session data
echo "<h3>1️⃣ فحص بيانات الجلسة</h3>";
session_start();
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
echo "<h4>📋 بيانات الجلسة الحالية:</h4>";
if (isset($_SESSION['id'])) {
    echo "<p>✅ <strong>User ID:</strong> " . $_SESSION['id'] . "</p>";
    echo "<p>✅ <strong>Login:</strong> " . ($_SESSION['login'] ?? 'غير محدد') . "</p>";
    
    // Get user details
    $userId = (int)$_SESSION['id'];
    $userStmt = mysqli_prepare($con, "SELECT id, fullName, email FROM users WHERE id = ?");
    mysqli_stmt_bind_param($userStmt, 'i', $userId);
    mysqli_stmt_execute($userStmt);
    $userResult = mysqli_stmt_get_result($userStmt);
    $userInfo = mysqli_fetch_assoc($userResult);
    mysqli_stmt_close($userStmt);
    
    if ($userInfo) {
        echo "<p>✅ <strong>اسم المستخدم:</strong> " . htmlspecialchars($userInfo['fullName']) . "</p>";
        echo "<p>✅ <strong>البريد الإلكتروني:</strong> " . htmlspecialchars($userInfo['email']) . "</p>";
    } else {
        echo "<p>❌ <strong>خطأ:</strong> لا يوجد مستخدم بهذا الـ ID في قاعدة البيانات!</p>";
    }
} else {
    echo "<p>❌ <strong>خطأ:</strong> لا توجد جلسة نشطة! المستخدم غير مسجل الدخول.</p>";
    echo "<p><a href='hms/user-login.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>تسجيل الدخول</a></p>";
}
echo "</div>";

// 2. Check all appointments in the system
echo "<h3>2️⃣ فحص جميع المواعيد في النظام</h3>";
$allAppointments = mysqli_query($con, "
    SELECT 
        a.id,
        a.userId,
        a.doctorId,
        u.fullName AS patientName,
        u.email AS patientEmail,
        d.doctorName,
        a.appointmentDate,
        a.appointmentTime,
        a.userStatus,
        a.doctorStatus,
        a.postingDate
    FROM appointment a
    LEFT JOIN users u ON u.id = a.userId
    LEFT JOIN doctors d ON d.id = a.doctorId
    ORDER BY a.postingDate DESC
    LIMIT 20
");

$totalAppointments = 0;
echo "<table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>";
echo "<tr style='background: #6c757d; color: white;'>";
echo "<th style='padding: 10px; border: 1px solid #ddd;'>رقم الموعد</th>";
echo "<th style='padding: 10px; border: 1px solid #ddd;'>User ID</th>";
echo "<th style='padding: 10px; border: 1px solid #ddd;'>اسم المريض</th>";
echo "<th style='padding: 10px; border: 1px solid #ddd;'>اسم الطبيب</th>";
echo "<th style='padding: 10px; border: 1px solid #ddd;'>التاريخ</th>";
echo "<th style='padding: 10px; border: 1px solid #ddd;'>الحالة</th>";
echo "</tr>";

while ($row = mysqli_fetch_assoc($allAppointments)) {
    $totalAppointments++;
    $status = ($row['userStatus'] && $row['doctorStatus']) ? '✅ نشط' : '❌ ملغي';
    $isCurrentUser = (isset($_SESSION['id']) && $row['userId'] == $_SESSION['id']);
    $bgColor = $isCurrentUser ? 'background: #d4edda;' : '';
    
    echo "<tr style='$bgColor'>";
    echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $row['id'] . ($isCurrentUser ? ' 👤' : '') . "</td>";
    echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $row['userId'] . "</td>";
    echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($row['patientName'] ?? 'غير موجود') . "</td>";
    echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($row['doctorName'] ?? 'غير موجود') . "</td>";
    echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $row['appointmentDate'] . " " . $row['appointmentTime'] . "</td>";
    echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $status . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<p><strong>إجمالي المواعيد في النظام:</strong> $totalAppointments</p>";

// 3. Test the exact query from appointment-history.php
if (isset($_SESSION['id'])) {
    echo "<h3>3️⃣ اختبار الاستعلام المستخدم في appointment-history.php</h3>";
    $userId = (int)$_SESSION['id'];
    
    echo "<p><strong>اختبار للمستخدم ID:</strong> $userId</p>";
    
    // The exact query from appointment-history.php
    $testQuery = mysqli_prepare($con, "
        SELECT 
            a.id,
            COALESCE(d.doctorName,'—') AS docname,
            COALESCE(a.doctorSpecialization, d.specilization) AS doctorSpecialization,
            a.consultancyFees,
            a.appointmentDate,
            a.appointmentTime,
            a.postingDate,
            COALESCE(a.userStatus,1) AS userStatus,
            COALESCE(a.doctorStatus,1) AS doctorStatus
        FROM appointment a
        LEFT JOIN doctors d ON d.id = a.doctorId
        WHERE a.userId = ?
        ORDER BY a.appointmentDate DESC, TIME(a.appointmentTime) DESC, a.id DESC
    ");
    
    mysqli_stmt_bind_param($testQuery, 'i', $userId);
    mysqli_stmt_execute($testQuery);
    $result = mysqli_stmt_get_result($testQuery);
    
    echo "<table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>";
    echo "<tr style='background: #007bff; color: white;'>";
    echo "<th style='padding: 10px; border: 1px solid #ddd;'>رقم الموعد</th>";
    echo "<th style='padding: 10px; border: 1px solid #ddd;'>اسم الطبيب</th>";
    echo "<th style='padding: 10px; border: 1px solid #ddd;'>التخصص</th>";
    echo "<th style='padding: 10px; border: 1px solid #ddd;'>التاريخ</th>";
    echo "<th style='padding: 10px; border: 1px solid #ddd;'>الوقت</th>";
    echo "<th style='padding: 10px; border: 1px solid #ddd;'>الحالة</th>";
    echo "</tr>";
    
    $userAppointmentCount = 0;
    while ($row = mysqli_fetch_assoc($result)) {
        $userAppointmentCount++;
        $status = ($row['userStatus'] && $row['doctorStatus']) ? '✅ نشط' : '❌ ملغي';
        
        echo "<tr>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $row['id'] . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($row['docname']) . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($row['doctorSpecialization']) . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $row['appointmentDate'] . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $row['appointmentTime'] . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $status . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    mysqli_stmt_close($testQuery);
    
    echo "<div style='background: " . ($userAppointmentCount > 0 ? '#d4edda' : '#f8d7da') . "; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    if ($userAppointmentCount > 0) {
        echo "<h4>✅ تم العثور على مواعيد!</h4>";
        echo "<p><strong>عدد المواعيد للمستخدم الحالي:</strong> $userAppointmentCount</p>";
        echo "<p>إذا كانت المواعيد تظهر هنا لكن لا تظهر في الصفحة الأصلية، فالمشكلة في الكود أو CSS.</p>";
    } else {
        echo "<h4>❌ لا توجد مواعيد للمستخدم الحالي</h4>";
        echo "<p><strong>السبب المحتمل:</strong></p>";
        echo "<ul>";
        echo "<li>المستخدم لم يحجز أي مواعيد بعد</li>";
        echo "<li>المواعيد محجوزة بـ User ID مختلف</li>";
        echo "<li>المواعيد ملغية (userStatus = 0 أو doctorStatus = 0)</li>";
        echo "</ul>";
    }
    echo "</div>";
}

// 4. Create a test appointment for current user
if (isset($_SESSION['id']) && isset($_POST['create_test_appointment'])) {
    echo "<h3>4️⃣ إنشاء موعد تجريبي</h3>";
    
    $userId = (int)$_SESSION['id'];
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
            echo "<h4>✅ تم إنشاء موعد تجريبي بنجاح!</h4>";
            echo "<p><strong>رقم الموعد:</strong> $newId</p>";
            echo "<p><strong>الطبيب:</strong> " . htmlspecialchars($docInfo['doctorName']) . "</p>";
            echo "<p><strong>التاريخ:</strong> $date في $time</p>";
            echo "<p><a href='hms/appointment-history.php' target='_blank' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔄 تحقق من الصفحة الآن</a></p>";
            echo "</div>";
        } else {
            echo "<div style='background: #f8d7da; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
            echo "<h4>❌ فشل في إنشاء الموعد</h4>";
            echo "<p>خطأ: " . mysqli_error($con) . "</p>";
            echo "</div>";
        }
        mysqli_stmt_close($insertStmt);
    }
}

// Test appointment creation form
if (isset($_SESSION['id'])) {
    $doctors = [];
    $doctorResult = mysqli_query($con, "SELECT id, doctorName, specilization FROM doctors LIMIT 5");
    while ($row = mysqli_fetch_assoc($doctorResult)) {
        $doctors[] = $row;
    }
    
    if (!empty($doctors)) {
        echo "<h3>4️⃣ إنشاء موعد تجريبي للمستخدم الحالي</h3>";
        echo "<form method='post' style='background: #e3f2fd; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
        echo "<h4>📅 حجز موعد تجريبي</h4>";
        
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
}

// 5. Quick links and summary
echo "<div style='background: #fff3cd; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h4>🔗 روابط سريعة:</h4>";
echo "<ul>";
echo "<li><a href='hms/appointment-history.php' target='_blank' style='color: #856404; font-weight: bold;'>📋 صفحة سجل المواعيد الأصلية</a></li>";
echo "<li><a href='hms/dashboard.php' target='_blank' style='color: #856404;'>🏠 لوحة تحكم المريض</a></li>";
echo "<li><a href='hms/book-appointment.php' target='_blank' style='color: #856404;'>📅 حجز موعد جديد</a></li>";
echo "<li><a href='hms/user-login.php' target='_blank' style='color: #856404;'>🔐 تسجيل الدخول</a></li>";
echo "</ul>";
echo "</div>";

mysqli_close($con);
?>
