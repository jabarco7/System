<?php
require_once 'hms/include/config.php';

echo "<h2>🧪 اختبار حجز المواعيد من المريض</h2>";

// Handle appointment booking simulation
if (isset($_POST['simulate_booking'])) {
    $doctorId = (int)$_POST['doctor_id'];
    $userId = (int)$_POST['user_id'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    
    echo "<h3>📝 محاكاة حجز موعد...</h3>";
    
    // Get doctor info
    $docStmt = mysqli_prepare($con, "SELECT doctorName, specilization, docFees FROM doctors WHERE id = ?");
    mysqli_stmt_bind_param($docStmt, 'i', $doctorId);
    mysqli_stmt_execute($docStmt);
    $docResult = mysqli_stmt_get_result($docStmt);
    $docInfo = mysqli_fetch_assoc($docResult);
    mysqli_stmt_close($docStmt);
    
    // Get user info
    $userStmt = mysqli_prepare($con, "SELECT fullName, email FROM users WHERE id = ?");
    mysqli_stmt_bind_param($userStmt, 'i', $userId);
    mysqli_stmt_execute($userStmt);
    $userResult = mysqli_stmt_get_result($userStmt);
    $userInfo = mysqli_fetch_assoc($userResult);
    mysqli_stmt_close($userStmt);
    
    if ($docInfo && $userInfo) {
        echo "<p><strong>الطبيب:</strong> " . htmlspecialchars($docInfo['doctorName']) . " - " . htmlspecialchars($docInfo['specilization']) . "</p>";
        echo "<p><strong>المريض:</strong> " . htmlspecialchars($userInfo['fullName']) . " (" . htmlspecialchars($userInfo['email']) . ")</p>";
        echo "<p><strong>الموعد:</strong> $date في $time</p>";
        
        // Insert appointment (same as the real booking system)
        $insertStmt = mysqli_prepare($con, "
            INSERT INTO appointment 
            (doctorSpecialization, doctorId, userId, consultancyFees, appointmentDate, appointmentTime, userStatus, doctorStatus, postingDate)
            VALUES (?, ?, ?, ?, ?, ?, 1, 1, NOW())
        ");
        
        mysqli_stmt_bind_param($insertStmt, 'siisss', 
            $docInfo['specilization'], 
            $doctorId, 
            $userId, 
            $docInfo['docFees'], 
            $date, 
            $time
        );
        
        if (mysqli_stmt_execute($insertStmt)) {
            $newAppointmentId = mysqli_insert_id($con);
            echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; margin: 20px 0; border: 1px solid #c3e6cb;'>";
            echo "<h4>✅ تم حجز الموعد بنجاح!</h4>";
            echo "<p><strong>رقم الموعد الجديد:</strong> $newAppointmentId</p>";
            echo "<p><strong>الحالة:</strong> نشط ومؤكد</p>";
            echo "</div>";
            
            // Now check if it appears in patient's appointment history
            echo "<h4>🔍 التحقق من ظهور الموعد في سجل المريض:</h4>";
            $checkStmt = mysqli_prepare($con, "
                SELECT 
                    a.id, a.doctorSpecialization, a.consultancyFees,
                    a.appointmentDate, a.appointmentTime, a.postingDate,
                    a.userStatus, a.doctorStatus,
                    d.doctorName AS docname
                FROM appointment a
                JOIN doctors d ON d.id = a.doctorId
                WHERE a.userId = ? AND a.id = ?
            ");
            mysqli_stmt_bind_param($checkStmt, 'ii', $userId, $newAppointmentId);
            mysqli_stmt_execute($checkStmt);
            $checkResult = mysqli_stmt_get_result($checkStmt);
            $foundAppointment = mysqli_fetch_assoc($checkResult);
            mysqli_stmt_close($checkStmt);
            
            if ($foundAppointment) {
                echo "<p style='color: #28a745; font-weight: bold;'>✅ الموعد يظهر في سجل المريض بشكل صحيح!</p>";
                echo "<ul>";
                echo "<li><strong>اسم الطبيب:</strong> " . htmlspecialchars($foundAppointment['docname']) . "</li>";
                echo "<li><strong>التخصص:</strong> " . htmlspecialchars($foundAppointment['doctorSpecialization']) . "</li>";
                echo "<li><strong>الرسوم:</strong> " . htmlspecialchars($foundAppointment['consultancyFees']) . "</li>";
                echo "<li><strong>التاريخ/الوقت:</strong> " . $foundAppointment['appointmentDate'] . " - " . $foundAppointment['appointmentTime'] . "</li>";
                echo "</ul>";
            } else {
                echo "<p style='color: #dc3545; font-weight: bold;'>❌ الموعد لا يظهر في سجل المريض!</p>";
            }
            
        } else {
            echo "<div style='background: #f8d7da; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
            echo "<h4>❌ فشل في حجز الموعد</h4>";
            echo "<p>خطأ: " . mysqli_error($con) . "</p>";
            echo "</div>";
        }
        mysqli_stmt_close($insertStmt);
    }
}

// Show current appointments for testing
echo "<h3>📋 المواعيد الحالية في النظام</h3>";
$allAppts = mysqli_query($con, "
    SELECT 
        a.id,
        d.doctorName,
        u.fullName AS patientName,
        a.appointmentDate,
        a.appointmentTime,
        a.postingDate,
        a.userStatus,
        a.doctorStatus
    FROM appointment a
    JOIN doctors d ON d.id = a.doctorId
    JOIN users u ON u.id = a.userId
    ORDER BY a.postingDate DESC
    LIMIT 15
");

echo "<table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>";
echo "<tr style='background: #6c757d; color: white;'>";
echo "<th style='padding: 10px; border: 1px solid #ddd;'>رقم الموعد</th>";
echo "<th style='padding: 10px; border: 1px solid #ddd;'>الطبيب</th>";
echo "<th style='padding: 10px; border: 1px solid #ddd;'>المريض</th>";
echo "<th style='padding: 10px; border: 1px solid #ddd;'>التاريخ</th>";
echo "<th style='padding: 10px; border: 1px solid #ddd;'>الوقت</th>";
echo "<th style='padding: 10px; border: 1px solid #ddd;'>تاريخ الحجز</th>";
echo "<th style='padding: 10px; border: 1px solid #ddd;'>الحالة</th>";
echo "</tr>";

while ($row = mysqli_fetch_assoc($allAppts)) {
    $status = ($row['userStatus'] && $row['doctorStatus']) ? '✅ نشط' : '❌ ملغي';
    $isRecent = (strtotime($row['postingDate']) > strtotime('-1 hour'));
    $bgColor = $isRecent ? 'background: #d4edda;' : '';
    
    echo "<tr style='$bgColor'>";
    echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $row['id'] . ($isRecent ? ' 🆕' : '') . "</td>";
    echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($row['doctorName']) . "</td>";
    echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($row['patientName']) . "</td>";
    echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $row['appointmentDate'] . "</td>";
    echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $row['appointmentTime'] . "</td>";
    echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $row['postingDate'] . "</td>";
    echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $status . "</td>";
    echo "</tr>";
}
echo "</table>";

// Booking simulation form
$doctors = [];
$doctorResult = mysqli_query($con, "SELECT id, doctorName, specilization FROM doctors LIMIT 5");
while ($row = mysqli_fetch_assoc($doctorResult)) {
    $doctors[] = $row;
}

$users = [];
$userResult = mysqli_query($con, "SELECT id, fullName, email FROM users LIMIT 5");
while ($row = mysqli_fetch_assoc($userResult)) {
    $users[] = $row;
}

if (!empty($doctors) && !empty($users)) {
    echo "<h3>📅 محاكاة حجز موعد جديد</h3>";
    echo "<form method='post' style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    
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
    echo "<label><strong>اختر المريض:</strong></label>";
    echo "<select name='user_id' required style='width: 100%; padding: 8px; margin-top: 5px;'>";
    echo "<option value=''>-- اختر مريض --</option>";
    foreach ($users as $user) {
        echo "<option value='" . $user['id'] . "'>" . htmlspecialchars($user['fullName'] . ' (' . $user['email'] . ')') . "</option>";
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
    echo "<option value='15:00:00'>03:00 مساءً</option>";
    echo "</select>";
    echo "</div>";
    
    echo "<button type='submit' name='simulate_booking' style='background: #007bff; color: white; padding: 12px 25px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;'>";
    echo "📅 محاكاة حجز الموعد";
    echo "</button>";
    echo "</form>";
}

// Test links
echo "<h3>🔗 روابط الاختبار</h3>";
echo "<div style='background: #e3f2fd; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h4>اختبر الصفحات التالية:</h4>";
echo "<ul>";
echo "<li><a href='hms/appointment-history.php' target='_blank' style='color: #1976d2; font-weight: bold; font-size: 18px;'>📋 سجل مواعيد المريض (appointment-history.php)</a></li>";
echo "<li><a href='hms/dashboard.php' target='_blank' style='color: #1976d2;'>🏠 لوحة تحكم المريض</a></li>";
echo "<li><a href='hms/book-appointment.php' target='_blank' style='color: #1976d2;'>📅 حجز موعد حقيقي</a></li>";
echo "<li><a href='hms/doctor/appointment-history.php' target='_blank' style='color: #1976d2;'>👨‍⚕️ سجل مواعيد الطبيب</a></li>";
echo "</ul>";
echo "</div>";

echo "<div style='background: #fff3cd; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h4>📋 خطوات الاختبار:</h4>";
echo "<ol>";
echo "<li><strong>احجز موعد تجريبي</strong> باستخدام النموذج أعلاه</li>";
echo "<li><strong>انتقل إلى سجل مواعيد المريض</strong> للتحقق من ظهور الموعد</li>";
echo "<li><strong>انتقل إلى سجل مواعيد الطبيب</strong> للتحقق من ظهور الموعد هناك أيضاً</li>";
echo "<li><strong>اختبر التحديث التلقائي</strong> - ستحصل على إشعار عند وجود مواعيد جديدة</li>";
echo "</ol>";
echo "</div>";

echo "<div style='background: #d1ecf1; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h4>✨ الميزات الجديدة:</h4>";
echo "<ul>";
echo "<li><strong>🔔 إشعارات فورية:</strong> عند حجز مواعيد جديدة</li>";
echo "<li><strong>🔄 تحديث تلقائي:</strong> كل 30 ثانية للتحقق من المواعيد الجديدة</li>";
echo "<li><strong>📱 أرقام الهواتف:</strong> تظهر بشكل صحيح في جميع الصفحات</li>";
echo "<li><strong>⚡ استجابة فورية:</strong> المواعيد تظهر فوراً بعد الحجز</li>";
echo "</ul>";
echo "</div>";

mysqli_close($con);
?>
