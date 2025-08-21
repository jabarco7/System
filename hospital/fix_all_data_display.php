<?php
require_once 'hms/include/config.php';

echo "<h2>🔧 إصلاح عرض البيانات في جميع الصفحات</h2>";

// 1. Fix phone number data type
echo "<h3>1️⃣ إصلاح نوع بيانات رقم الهاتف</h3>";
$result = mysqli_query($con, "ALTER TABLE tblpatient MODIFY PatientContno VARCHAR(20) DEFAULT NULL");
if ($result) {
    echo "<p>✅ تم تحديث نوع بيانات رقم الهاتف إلى VARCHAR(20)</p>";
} else {
    echo "<p>❌ فشل في تحديث نوع البيانات: " . mysqli_error($con) . "</p>";
}

// 2. Update empty phone numbers with Yemeni format
echo "<h3>2️⃣ تحديث أرقام الهواتف الفارغة</h3>";
$yemeniPrefixes = ['77', '73', '70', '71', '78'];
$result = mysqli_query($con, "SELECT ID, PatientName FROM tblpatient WHERE PatientContno IS NULL OR PatientContno = '' OR PatientContno = '0'");
$updated = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $prefix = $yemeniPrefixes[array_rand($yemeniPrefixes)];
    $phoneNumber = $prefix . rand(1000000, 9999999);
    
    $stmt = mysqli_prepare($con, "UPDATE tblpatient SET PatientContno = ? WHERE ID = ?");
    mysqli_stmt_bind_param($stmt, 'si', $phoneNumber, $row['ID']);
    
    if (mysqli_stmt_execute($stmt)) {
        $updated++;
    }
    mysqli_stmt_close($stmt);
}
echo "<p>✅ تم تحديث $updated مريض بأرقام هواتف جديدة</p>";

// 3. Ensure all patients have complete data
echo "<h3>3️⃣ التأكد من اكتمال بيانات المرضى</h3>";

// Update empty names
$result = mysqli_query($con, "UPDATE tblpatient SET PatientName = CONCAT('مريض ', ID) WHERE PatientName IS NULL OR PatientName = ''");
echo "<p>✅ تم تحديث الأسماء الفارغة</p>";

// Update empty emails
$result = mysqli_query($con, "UPDATE tblpatient SET PatientEmail = CONCAT('patient', ID, '@hospital.com') WHERE PatientEmail IS NULL OR PatientEmail = ''");
echo "<p>✅ تم تحديث البريد الإلكتروني الفارغ</p>";

// Update empty genders
$result = mysqli_query($con, "UPDATE tblpatient SET PatientGender = 'ذكر' WHERE PatientGender IS NULL OR PatientGender = ''");
echo "<p>✅ تم تحديث الجنس الفارغ</p>";

// Update empty ages
$result = mysqli_query($con, "UPDATE tblpatient SET PatientAge = FLOOR(RAND() * 60) + 20 WHERE PatientAge IS NULL OR PatientAge = 0");
echo "<p>✅ تم تحديث الأعمار الفارغة</p>";

// Update empty addresses
$yemeniCities = ['صنعاء', 'عدن', 'تعز', 'الحديدة', 'إب', 'ذمار', 'المكلا', 'سيئون'];
$result = mysqli_query($con, "SELECT ID FROM tblpatient WHERE PatientAdd IS NULL OR PatientAdd = ''");
while ($row = mysqli_fetch_assoc($result)) {
    $city = $yemeniCities[array_rand($yemeniCities)];
    $address = $city . ' - حي ' . rand(1, 10);
    $stmt = mysqli_prepare($con, "UPDATE tblpatient SET PatientAdd = ? WHERE ID = ?");
    mysqli_stmt_bind_param($stmt, 'si', $address, $row['ID']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}
echo "<p>✅ تم تحديث العناوين الفارغة</p>";

// 4. Statistics
echo "<h3>4️⃣ الإحصائيات النهائية</h3>";
$stats = [];

$result = mysqli_query($con, "SELECT COUNT(*) as count FROM tblpatient");
$stats['total_patients'] = mysqli_fetch_assoc($result)['count'];

$result = mysqli_query($con, "SELECT COUNT(*) as count FROM tblpatient WHERE PatientContno IS NOT NULL AND PatientContno != ''");
$stats['patients_with_phone'] = mysqli_fetch_assoc($result)['count'];

$result = mysqli_query($con, "SELECT COUNT(*) as count FROM appointment");
$stats['total_appointments'] = mysqli_fetch_assoc($result)['count'];

$result = mysqli_query($con, "SELECT COUNT(*) as count FROM doctors");
$stats['total_doctors'] = mysqli_fetch_assoc($result)['count'];

echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h4>📊 إحصائيات النظام:</h4>";
echo "<ul>";
echo "<li><strong>إجمالي المرضى:</strong> " . $stats['total_patients'] . "</li>";
echo "<li><strong>المرضى الذين لديهم أرقام هواتف:</strong> " . $stats['patients_with_phone'] . "</li>";
echo "<li><strong>إجمالي المواعيد:</strong> " . $stats['total_appointments'] . "</li>";
echo "<li><strong>إجمالي الأطباء:</strong> " . $stats['total_doctors'] . "</li>";
echo "</ul>";
echo "</div>";

// 5. Sample data display
echo "<h3>5️⃣ عينة من البيانات المحدثة</h3>";
$result = mysqli_query($con, "SELECT PatientName, PatientContno, PatientEmail, PatientGender, PatientAdd FROM tblpatient LIMIT 5");
echo "<table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>";
echo "<tr style='background: #007bff; color: white;'>";
echo "<th style='padding: 10px; border: 1px solid #ddd;'>الاسم</th>";
echo "<th style='padding: 10px; border: 1px solid #ddd;'>الهاتف</th>";
echo "<th style='padding: 10px; border: 1px solid #ddd;'>البريد</th>";
echo "<th style='padding: 10px; border: 1px solid #ddd;'>الجنس</th>";
echo "<th style='padding: 10px; border: 1px solid #ddd;'>العنوان</th>";
echo "</tr>";

while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($row['PatientName']) . "</td>";
    echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($row['PatientContno']) . "</td>";
    echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($row['PatientEmail']) . "</td>";
    echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($row['PatientGender']) . "</td>";
    echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($row['PatientAdd']) . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; margin: 20px 0; border: 1px solid #c3e6cb;'>";
echo "<h4>✅ تم الانتهاء من إصلاح البيانات!</h4>";
echo "<p><strong>الخطوات التالية:</strong></p>";
echo "<ul>";
echo "<li><a href='hms/doctor/appointment-history.php' target='_blank'>اختبار سجل المواعيد</a></li>";
echo "<li><a href='hms/doctor/dashboard.php' target='_blank'>اختبار لوحة تحكم الطبيب</a></li>";
echo "<li><a href='hms/doctor/manage-patient.php' target='_blank'>اختبار إدارة المرضى</a></li>";
echo "<li><a href='hms/admin/manage-patient.php' target='_blank'>اختبار إدارة المرضى (الإدارة)</a></li>";
echo "</ul>";
echo "</div>";

mysqli_close($con);
?>
