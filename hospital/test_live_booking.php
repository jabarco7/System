<?php
require_once 'hms/include/config.php';

echo "<h2>🔴 اختبار حجز موعد مباشر</h2>";

// Handle appointment booking
if (isset($_POST['book_appointment'])) {
    $doctorId = (int)$_POST['doctor_id'];
    $userId = (int)$_POST['user_id'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    
    // Get doctor info
    $docStmt = mysqli_prepare($con, "SELECT specilization, docFees FROM doctors WHERE id = ?");
    mysqli_stmt_bind_param($docStmt, 'i', $doctorId);
    mysqli_stmt_execute($docStmt);
    $docResult = mysqli_stmt_get_result($docStmt);
    $docInfo = mysqli_fetch_assoc($docResult);
    mysqli_stmt_close($docStmt);
    
    if ($docInfo) {
        // Insert appointment
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
            $newId = mysqli_insert_id($con);
            echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
            echo "<h3>✅ تم حجز الموعد بنجاح!</h3>";
            echo "<p><strong>رقم الموعد:</strong> $newId</p>";
            echo "<p><strong>التاريخ:</strong> $date</p>";
            echo "<p><strong>الوقت:</strong> $time</p>";
            echo "</div>";
        } else {
            echo "<div style='background: #f8d7da; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
            echo "<h3>❌ فشل في حجز الموعد</h3>";
            echo "<p>خطأ: " . mysqli_error($con) . "</p>";
            echo "</div>";
        }
        mysqli_stmt_close($insertStmt);
    }
}

// Get available doctors and users
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

?>

<h3>📅 نموذج حجز موعد تجريبي</h3>
<form method="post" style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
    <div style="margin-bottom: 15px;">
        <label><strong>اختر الطبيب:</strong></label>
        <select name="doctor_id" required style="width: 100%; padding: 8px; margin-top: 5px;">
            <option value="">-- اختر طبيب --</option>
            <?php foreach ($doctors as $doc): ?>
                <option value="<?php echo $doc['id']; ?>">
                    <?php echo htmlspecialchars($doc['doctorName'] . ' - ' . $doc['specilization']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <div style="margin-bottom: 15px;">
        <label><strong>اختر المريض:</strong></label>
        <select name="user_id" required style="width: 100%; padding: 8px; margin-top: 5px;">
            <option value="">-- اختر مريض --</option>
            <?php foreach ($users as $user): ?>
                <option value="<?php echo $user['id']; ?>">
                    <?php echo htmlspecialchars($user['fullName'] . ' (' . $user['email'] . ')'); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <div style="margin-bottom: 15px;">
        <label><strong>التاريخ:</strong></label>
        <input type="date" name="date" value="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required style="width: 100%; padding: 8px; margin-top: 5px;">
    </div>
    
    <div style="margin-bottom: 15px;">
        <label><strong>الوقت:</strong></label>
        <select name="time" required style="width: 100%; padding: 8px; margin-top: 5px;">
            <option value="09:00:00">09:00 صباحاً</option>
            <option value="10:00:00">10:00 صباحاً</option>
            <option value="11:00:00">11:00 صباحاً</option>
            <option value="14:00:00">02:00 مساءً</option>
            <option value="15:00:00">03:00 مساءً</option>
            <option value="16:00:00">04:00 مساءً</option>
        </select>
    </div>
    
    <button type="submit" name="book_appointment" style="background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">
        📅 حجز الموعد
    </button>
</form>

<h3>🔍 التحقق من المواعيد الحالية</h3>
<?php
// Show current appointments for all doctors
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
    ORDER BY a.id DESC
    LIMIT 10
");

echo "<table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>";
echo "<tr style='background: #6c757d; color: white;'>";
echo "<th style='padding: 10px; border: 1px solid #ddd;'>رقم الموعد</th>";
echo "<th style='padding: 10px; border: 1px solid #ddd;'>الطبيب</th>";
echo "<th style='padding: 10px; border: 1px solid #ddd;'>المريض</th>";
echo "<th style='padding: 10px; border: 1px solid #ddd;'>التاريخ</th>";
echo "<th style='padding: 10px; border: 1px solid #ddd;'>الوقت</th>";
echo "<th style='padding: 10px; border: 1px solid #ddd;'>الحالة</th>";
echo "</tr>";

while ($row = mysqli_fetch_assoc($allAppts)) {
    $status = ($row['userStatus'] && $row['doctorStatus']) ? '✅ نشط' : '❌ ملغي';
    echo "<tr>";
    echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $row['id'] . "</td>";
    echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($row['doctorName']) . "</td>";
    echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($row['patientName']) . "</td>";
    echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $row['appointmentDate'] . "</td>";
    echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $row['appointmentTime'] . "</td>";
    echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $status . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<div style='background: #fff3cd; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
echo "<h4>📋 خطوات التحقق:</h4>";
echo "<ol>";
echo "<li>احجز موعد جديد باستخدام النموذج أعلاه</li>";
echo "<li>انتقل إلى <a href='hms/doctor/appointment-history.php' target='_blank'>سجل مواعيد الطبيب</a></li>";
echo "<li>تأكد من ظهور الموعد الجديد في القائمة</li>";
echo "<li>تحقق من عرض رقم هاتف المريض</li>";
echo "</ol>";
echo "</div>";

mysqli_close($con);
?>
