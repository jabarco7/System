<?php
require_once 'hms/include/config.php';

echo "<h1>🎯 اختبار نهائي لنظام المواعيد</h1>";

// Create a test appointment to verify the flow
if (isset($_POST['final_test'])) {
    $doctorId = (int)$_POST['doctor_id'];
    $userId = (int)$_POST['user_id'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    
    echo "<div style='background: #e3f2fd; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>🔄 تنفيذ اختبار شامل...</h3>";
    
    // Step 1: Get doctor and user info
    $docStmt = mysqli_prepare($con, "SELECT doctorName, specilization, docFees FROM doctors WHERE id = ?");
    mysqli_stmt_bind_param($docStmt, 'i', $doctorId);
    mysqli_stmt_execute($docStmt);
    $docResult = mysqli_stmt_get_result($docStmt);
    $docInfo = mysqli_fetch_assoc($docResult);
    mysqli_stmt_close($docStmt);
    
    $userStmt = mysqli_prepare($con, "SELECT fullName, email FROM users WHERE id = ?");
    mysqli_stmt_bind_param($userStmt, 'i', $userId);
    mysqli_stmt_execute($userStmt);
    $userResult = mysqli_stmt_get_result($userStmt);
    $userInfo = mysqli_fetch_assoc($userResult);
    mysqli_stmt_close($userStmt);
    
    if ($docInfo && $userInfo) {
        echo "<p>✅ <strong>الطبيب:</strong> " . htmlspecialchars($docInfo['doctorName']) . " - " . htmlspecialchars($docInfo['specilization']) . "</p>";
        echo "<p>✅ <strong>المريض:</strong> " . htmlspecialchars($userInfo['fullName']) . " (" . htmlspecialchars($userInfo['email']) . ")</p>";
        
        // Step 2: Create appointment (exactly like the real system)
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
            $newAppointmentId = mysqli_insert_id($con);
            echo "<p>✅ <strong>تم إنشاء الموعد برقم:</strong> $newAppointmentId</p>";
            
            // Step 3: Verify it appears in patient's appointment history
            echo "<h4>🔍 التحقق من ظهور الموعد في سجل المريض:</h4>";
            $patientCheckStmt = mysqli_prepare($con, "
                SELECT 
                    a.id, a.doctorSpecialization, a.consultancyFees,
                    a.appointmentDate, a.appointmentTime, a.postingDate,
                    a.userStatus, a.doctorStatus,
                    d.doctorName AS docname
                FROM appointment a
                JOIN doctors d ON d.id = a.doctorId
                WHERE a.userId = ? AND a.id = ?
            ");
            mysqli_stmt_bind_param($patientCheckStmt, 'ii', $userId, $newAppointmentId);
            mysqli_stmt_execute($patientCheckStmt);
            $patientResult = mysqli_stmt_get_result($patientCheckStmt);
            $patientAppointment = mysqli_fetch_assoc($patientResult);
            mysqli_stmt_close($patientCheckStmt);
            
            if ($patientAppointment) {
                echo "<p style='color: #28a745; font-weight: bold;'>✅ الموعد يظهر في سجل المريض!</p>";
            } else {
                echo "<p style='color: #dc3545; font-weight: bold;'>❌ الموعد لا يظهر في سجل المريض!</p>";
            }
            
            // Step 4: Verify it appears in doctor's appointment history
            echo "<h4>🔍 التحقق من ظهور الموعد في سجل الطبيب:</h4>";
            $doctorCheckStmt = mysqli_prepare($con, "
                SELECT
                    a.id,
                    u.fullName AS patientName,
                    a.consultancyFees,
                    a.appointmentDate,
                    a.appointmentTime,
                    a.postingDate,
                    a.userStatus,
                    a.doctorStatus,
                    COALESCE(p.PatientContno, p2.PatientContno, 'غير محدد') AS patientNumber
                FROM appointment a
                JOIN users u ON u.id = a.userId
                LEFT JOIN tblpatient p ON p.PatientEmail = u.email AND p.Docid = a.doctorId
                LEFT JOIN tblpatient p2 ON p2.PatientEmail = u.email AND p2.Docid != a.doctorId
                WHERE a.doctorId = ? AND a.id = ?
            ");
            mysqli_stmt_bind_param($doctorCheckStmt, 'ii', $doctorId, $newAppointmentId);
            mysqli_stmt_execute($doctorCheckStmt);
            $doctorResult = mysqli_stmt_get_result($doctorCheckStmt);
            $doctorAppointment = mysqli_fetch_assoc($doctorResult);
            mysqli_stmt_close($doctorCheckStmt);
            
            if ($doctorAppointment) {
                echo "<p style='color: #28a745; font-weight: bold;'>✅ الموعد يظهر في سجل الطبيب مع رقم الهاتف: " . htmlspecialchars($doctorAppointment['patientNumber']) . "</p>";
            } else {
                echo "<p style='color: #dc3545; font-weight: bold;'>❌ الموعد لا يظهر في سجل الطبيب!</p>";
            }
            
        } else {
            echo "<p style='color: #dc3545; font-weight: bold;'>❌ فشل في إنشاء الموعد: " . mysqli_error($con) . "</p>";
        }
        mysqli_stmt_close($insertStmt);
    }
    echo "</div>";
}

// Show available doctors and users for testing
$doctors = [];
$doctorResult = mysqli_query($con, "SELECT id, doctorName, specilization FROM doctors LIMIT 3");
while ($row = mysqli_fetch_assoc($doctorResult)) {
    $doctors[] = $row;
}

$users = [];
$userResult = mysqli_query($con, "SELECT id, fullName, email FROM users LIMIT 3");
while ($row = mysqli_fetch_assoc($userResult)) {
    $users[] = $row;
}

if (!empty($doctors) && !empty($users)) {
    echo "<h3>🎯 اختبار نهائي شامل</h3>";
    echo "<form method='post' style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 25px; border-radius: 12px; margin: 20px 0;'>";
    echo "<h4 style='color: white; margin-bottom: 20px;'>📅 إنشاء موعد واختبار ظهوره في كلا الصفحتين</h4>";
    
    echo "<div style='margin-bottom: 15px;'>";
    echo "<label style='color: white; font-weight: bold;'>اختر الطبيب:</label>";
    echo "<select name='doctor_id' required style='width: 100%; padding: 10px; margin-top: 5px; border: none; border-radius: 5px;'>";
    echo "<option value=''>-- اختر طبيب --</option>";
    foreach ($doctors as $doc) {
        echo "<option value='" . $doc['id'] . "'>" . htmlspecialchars($doc['doctorName'] . ' - ' . $doc['specilization']) . "</option>";
    }
    echo "</select>";
    echo "</div>";
    
    echo "<div style='margin-bottom: 15px;'>";
    echo "<label style='color: white; font-weight: bold;'>اختر المريض:</label>";
    echo "<select name='user_id' required style='width: 100%; padding: 10px; margin-top: 5px; border: none; border-radius: 5px;'>";
    echo "<option value=''>-- اختر مريض --</option>";
    foreach ($users as $user) {
        echo "<option value='" . $user['id'] . "'>" . htmlspecialchars($user['fullName'] . ' (' . $user['email'] . ')') . "</option>";
    }
    echo "</select>";
    echo "</div>";
    
    echo "<div style='display: flex; gap: 15px; margin-bottom: 20px;'>";
    echo "<div style='flex: 1;'>";
    echo "<label style='color: white; font-weight: bold;'>التاريخ:</label>";
    echo "<input type='date' name='date' value='" . date('Y-m-d', strtotime('+1 day')) . "' required style='width: 100%; padding: 10px; margin-top: 5px; border: none; border-radius: 5px;'>";
    echo "</div>";
    echo "<div style='flex: 1;'>";
    echo "<label style='color: white; font-weight: bold;'>الوقت:</label>";
    echo "<select name='time' required style='width: 100%; padding: 10px; margin-top: 5px; border: none; border-radius: 5px;'>";
    echo "<option value='09:00:00'>09:00 صباحاً</option>";
    echo "<option value='10:00:00'>10:00 صباحاً</option>";
    echo "<option value='11:00:00'>11:00 صباحاً</option>";
    echo "<option value='14:00:00'>02:00 مساءً</option>";
    echo "<option value='15:00:00'>03:00 مساءً</option>";
    echo "</select>";
    echo "</div>";
    echo "</div>";
    
    echo "<button type='submit' name='final_test' style='background: white; color: #667eea; padding: 15px 30px; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; font-weight: bold; width: 100%;'>";
    echo "🚀 تشغيل الاختبار النهائي";
    echo "</button>";
    echo "</form>";
}

echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 5px solid #007bff;'>";
echo "<h4>🎯 الهدف من الاختبار:</h4>";
echo "<p>التأكد من أن المواعيد الجديدة التي يحجزها المرضى تظهر <strong>فوراً</strong> في:</p>";
echo "<ul>";
echo "<li>✅ <strong>صفحة سجل مواعيد المريض</strong> (appointment-history.php)</li>";
echo "<li>✅ <strong>صفحة سجل مواعيد الطبيب</strong> (doctor/appointment-history.php)</li>";
echo "<li>✅ <strong>مع عرض أرقام الهواتف بشكل صحيح</strong></li>";
echo "</ul>";
echo "</div>";

mysqli_close($con);
?>
