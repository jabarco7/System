<?php
require_once 'hms/include/config.php';

echo "<h2>🔧 تحديث أرقام الهواتف للمرضى</h2>";

// Yemeni phone prefixes
$yemeniPrefixes = ['77', '73', '70', '71', '78'];

// Update patients without phone numbers
$result = mysqli_query($con, "SELECT ID, PatientName FROM tblpatient WHERE PatientContno IS NULL OR PatientContno = ''");
$updated = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $prefix = $yemeniPrefixes[array_rand($yemeniPrefixes)];
    $phoneNumber = $prefix . rand(1000000, 9999999);
    
    $stmt = mysqli_prepare($con, "UPDATE tblpatient SET PatientContno = ? WHERE ID = ?");
    mysqli_stmt_bind_param($stmt, 'si', $phoneNumber, $row['ID']);
    
    if (mysqli_stmt_execute($stmt)) {
        echo "<p>✅ تم تحديث رقم هاتف المريض: " . htmlspecialchars($row['PatientName']) . " - الرقم الجديد: $phoneNumber</p>";
        $updated++;
    }
    
    mysqli_stmt_close($stmt);
}

echo "<h3>📊 النتائج:</h3>";
echo "<p><strong>تم تحديث $updated مريض بأرقام هواتف جديدة</strong></p>";

// Show updated results
$result = mysqli_query($con, "SELECT COUNT(*) as count FROM tblpatient WHERE PatientContno IS NOT NULL AND PatientContno != ''");
$count = mysqli_fetch_assoc($result)['count'];
echo "<p>إجمالي المرضى الذين لديهم أرقام هواتف الآن: $count</p>";

// Show sample
$result = mysqli_query($con, "SELECT PatientName, PatientContno FROM tblpatient WHERE PatientContno IS NOT NULL AND PatientContno != '' LIMIT 5");
echo "<h4>عينة من المرضى مع أرقام الهواتف:</h4>";
echo "<ul>";
while ($row = mysqli_fetch_assoc($result)) {
    echo "<li>" . htmlspecialchars($row['PatientName']) . ": " . htmlspecialchars($row['PatientContno']) . "</li>";
}
echo "</ul>";

echo "<p><a href='hms/doctor/appointment-history.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔄 اختبار سجل المواعيد الآن</a></p>";

mysqli_close($con);
?>
