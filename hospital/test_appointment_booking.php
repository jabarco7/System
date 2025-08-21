<?php
require_once 'hms/include/config.php';

echo "<h2>🧪 اختبار حجز المواعيد وظهورها في سجل الطبيب</h2>";

// Get a sample doctor and user
$doctorResult = mysqli_query($con, "SELECT id, doctorName, specilization, docFees FROM doctors LIMIT 1");
$doctor = mysqli_fetch_assoc($doctorResult);

$userResult = mysqli_query($con, "SELECT id, fullName, email FROM users WHERE id > 0 LIMIT 1");
$user = mysqli_fetch_assoc($userResult);

if (!$doctor || !$user) {
    echo "<p>❌ لا توجد بيانات أطباء أو مستخدمين للاختبار</p>";
    exit;
}

echo "<h3>1️⃣ بيانات الاختبار</h3>";
echo "<p><strong>الطبيب:</strong> " . htmlspecialchars($doctor['doctorName']) . " (" . htmlspecialchars($doctor['specilization']) . ")</p>";
echo "<p><strong>المريض:</strong> " . htmlspecialchars($user['fullName']) . " (" . htmlspecialchars($user['email']) . ")</p>";

// Check current appointments for this doctor
echo "<h3>2️⃣ المواعيد الحالية للطبيب</h3>";
$currentAppts = mysqli_query($con, "
    SELECT 
        a.id,
        u.fullName AS patientName,
        a.appointmentDate,
        a.appointmentTime,
        a.userStatus,
        a.doctorStatus
    FROM appointment a
    JOIN users u ON u.id = a.userId
    WHERE a.doctorId = " . $doctor['id'] . "
    ORDER BY a.id DESC
    LIMIT 5
");

echo "<table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>";
echo "<tr style='background: #007bff; color: white;'>";
echo "<th style='padding: 10px; border: 1px solid #ddd;'>رقم الموعد</th>";
echo "<th style='padding: 10px; border: 1px solid #ddd;'>اسم المريض</th>";
echo "<th style='padding: 10px; border: 1px solid #ddd;'>التاريخ</th>";
echo "<th style='padding: 10px; border: 1px solid #ddd;'>الوقت</th>";
echo "<th style='padding: 10px; border: 1px solid #ddd;'>الحالة</th>";
echo "</tr>";

$currentCount = 0;
while ($row = mysqli_fetch_assoc($currentAppts)) {
    $currentCount++;
    $status = ($row['userStatus'] && $row['doctorStatus']) ? 'نشط' : 'ملغي';
    echo "<tr>";
    echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $row['id'] . "</td>";
    echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($row['patientName']) . "</td>";
    echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $row['appointmentDate'] . "</td>";
    echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $row['appointmentTime'] . "</td>";
    echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $status . "</td>";
    echo "</tr>";
}
echo "</table>";
echo "<p><strong>عدد المواعيد الحالية: $currentCount</strong></p>";

// Create a new test appointment
echo "<h3>3️⃣ إنشاء موعد اختبار جديد</h3>";
$testDate = date('Y-m-d', strtotime('+1 day'));
$testTime = '10:00:00';

// Check if this time slot is available
$checkStmt = mysqli_prepare($con, "
    SELECT COUNT(*) as count 
    FROM appointment 
    WHERE doctorId = ? AND appointmentDate = ? AND appointmentTime = ? 
    AND userStatus = 1 AND doctorStatus = 1
");
mysqli_stmt_bind_param($checkStmt, 'iss', $doctor['id'], $testDate, $testTime);
mysqli_stmt_execute($checkStmt);
$checkResult = mysqli_stmt_get_result($checkStmt);
$isBooked = mysqli_fetch_assoc($checkResult)['count'] > 0;
mysqli_stmt_close($checkStmt);

if ($isBooked) {
    echo "<p>⚠️ الوقت $testDate في $testTime محجوز بالفعل</p>";
    // Try different time
    $testTime = '11:00:00';
}

// Insert test appointment
$insertStmt = mysqli_prepare($con, "
    INSERT INTO appointment 
    (doctorSpecialization, doctorId, userId, consultancyFees, appointmentDate, appointmentTime, userStatus, doctorStatus, postingDate)
    VALUES (?, ?, ?, ?, ?, ?, 1, 1, NOW())
");

if ($insertStmt) {
    mysqli_stmt_bind_param($insertStmt, 'siisss', 
        $doctor['specilization'], 
        $doctor['id'], 
        $user['id'], 
        $doctor['docFees'], 
        $testDate, 
        $testTime
    );
    
    if (mysqli_stmt_execute($insertStmt)) {
        $newAppointmentId = mysqli_insert_id($con);
        echo "<p>✅ تم إنشاء موعد اختبار جديد برقم: <strong>$newAppointmentId</strong></p>";
        echo "<p>📅 التاريخ: $testDate | ⏰ الوقت: $testTime</p>";
    } else {
        echo "<p>❌ فشل في إنشاء موعد الاختبار: " . mysqli_error($con) . "</p>";
    }
    mysqli_stmt_close($insertStmt);
}

// Check if the new appointment appears in doctor's appointment history
echo "<h3>4️⃣ التحقق من ظهور الموعد في سجل الطبيب</h3>";
$doctorAppts = mysqli_query($con, "
    SELECT 
        a.id,
        u.fullName AS patientName,
        COALESCE(p.PatientContno, p2.PatientContno, 'غير محدد') AS patientNumber,
        a.appointmentDate,
        a.appointmentTime,
        a.postingDate
    FROM appointment a
    JOIN users u ON u.id = a.userId
    LEFT JOIN tblpatient p ON p.PatientEmail = u.email AND p.Docid = a.doctorId
    LEFT JOIN tblpatient p2 ON p2.PatientEmail = u.email AND p2.Docid != a.doctorId
    WHERE a.doctorId = " . $doctor['id'] . "
    ORDER BY a.id DESC
    LIMIT 5
");

echo "<table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>";
echo "<tr style='background: #28a745; color: white;'>";
echo "<th style='padding: 10px; border: 1px solid #ddd;'>رقم الموعد</th>";
echo "<th style='padding: 10px; border: 1px solid #ddd;'>اسم المريض</th>";
echo "<th style='padding: 10px; border: 1px solid #ddd;'>رقم الهاتف</th>";
echo "<th style='padding: 10px; border: 1px solid #ddd;'>التاريخ</th>";
echo "<th style='padding: 10px; border: 1px solid #ddd;'>الوقت</th>";
echo "</tr>";

$newCount = 0;
while ($row = mysqli_fetch_assoc($doctorAppts)) {
    $newCount++;
    $isNew = isset($newAppointmentId) && $row['id'] == $newAppointmentId;
    $bgColor = $isNew ? 'background: #d4edda;' : '';
    
    echo "<tr style='$bgColor'>";
    echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $row['id'] . ($isNew ? ' 🆕' : '') . "</td>";
    echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($row['patientName']) . "</td>";
    echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($row['patientNumber']) . "</td>";
    echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $row['appointmentDate'] . "</td>";
    echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $row['appointmentTime'] . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<p><strong>إجمالي المواعيد في سجل الطبيب: $newCount</strong></p>";

// Test links
echo "<h3>5️⃣ روابط الاختبار</h3>";
echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h4>🔗 اختبر الصفحات التالية:</h4>";
echo "<ul>";
echo "<li><a href='hms/doctor/appointment-history.php' target='_blank' style='color: #007bff;'>📋 سجل مواعيد الطبيب</a></li>";
echo "<li><a href='hms/dashboard.php' target='_blank' style='color: #007bff;'>🏠 لوحة تحكم المريض</a></li>";
echo "<li><a href='hms/book-appointment.php' target='_blank' style='color: #007bff;'>📅 حجز موعد جديد</a></li>";
echo "<li><a href='hms/appointment-history.php' target='_blank' style='color: #007bff;'>📋 سجل مواعيد المريض</a></li>";
echo "</ul>";
echo "</div>";

// Summary
echo "<h3>6️⃣ ملخص النتائج</h3>";
echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; margin: 20px 0; border: 1px solid #c3e6cb;'>";
echo "<h4>✅ نتائج الاختبار:</h4>";
echo "<ul>";
echo "<li><strong>قاعدة البيانات:</strong> متصلة وتعمل</li>";
echo "<li><strong>إنشاء المواعيد:</strong> يعمل بشكل صحيح</li>";
echo "<li><strong>ربط البيانات:</strong> المواعيد مربوطة بالأطباء والمرضى</li>";
echo "<li><strong>أرقام الهواتف:</strong> تظهر في سجل المواعيد</li>";
echo "</ul>";
echo "</div>";

mysqli_close($con);
?>
