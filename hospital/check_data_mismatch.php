<?php
require_once 'hms/include/config.php';

echo "<h2>🔍 فحص عدم التطابق بين جداول البيانات</h2>";

// 1. Check appointments table
echo "<h3>1️⃣ فحص جدول المواعيد (appointment)</h3>";
$appointmentResult = mysqli_query($con, "
    SELECT 
        a.id,
        a.userId,
        a.doctorId,
        u.fullName AS patientName,
        u.email AS patientEmail,
        d.doctorName
    FROM appointment a
    LEFT JOIN users u ON u.id = a.userId
    LEFT JOIN doctors d ON d.id = a.doctorId
    ORDER BY a.id DESC
    LIMIT 10
");

echo "<table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>";
echo "<tr style='background: #007bff; color: white;'>";
echo "<th style='padding: 10px; border: 1px solid #ddd;'>رقم الموعد</th>";
echo "<th style='padding: 10px; border: 1px solid #ddd;'>User ID</th>";
echo "<th style='padding: 10px; border: 1px solid #ddd;'>اسم المريض</th>";
echo "<th style='padding: 10px; border: 1px solid #ddd;'>بريد المريض</th>";
echo "<th style='padding: 10px; border: 1px solid #ddd;'>Doctor ID</th>";
echo "<th style='padding: 10px; border: 1px solid #ddd;'>اسم الطبيب</th>";
echo "</tr>";

$appointmentEmails = [];
while ($row = mysqli_fetch_assoc($appointmentResult)) {
    if ($row['patientEmail']) {
        $appointmentEmails[] = $row['patientEmail'];
    }
    
    $userStatus = $row['patientName'] ? '✅' : '❌ مفقود';
    $doctorStatus = $row['doctorName'] ? '✅' : '❌ مفقود';
    
    echo "<tr>";
    echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $row['id'] . "</td>";
    echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $row['userId'] . "</td>";
    echo "<td style='padding: 8px; border: 1px solid #ddd;'>$userStatus " . htmlspecialchars($row['patientName'] ?? 'غير موجود') . "</td>";
    echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($row['patientEmail'] ?? 'غير موجود') . "</td>";
    echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $row['doctorId'] . "</td>";
    echo "<td style='padding: 8px; border: 1px solid #ddd;'>$doctorStatus " . htmlspecialchars($row['doctorName'] ?? 'غير موجود') . "</td>";
    echo "</tr>";
}
echo "</table>";

// 2. Check patients table
echo "<h3>2️⃣ فحص جدول المرضى (tblpatient)</h3>";
$patientResult = mysqli_query($con, "
    SELECT 
        ID,
        PatientName,
        PatientEmail,
        PatientContno,
        Docid
    FROM tblpatient
    ORDER BY ID DESC
    LIMIT 10
");

echo "<table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>";
echo "<tr style='background: #28a745; color: white;'>";
echo "<th style='padding: 10px; border: 1px solid #ddd;'>Patient ID</th>";
echo "<th style='padding: 10px; border: 1px solid #ddd;'>اسم المريض</th>";
echo "<th style='padding: 10px; border: 1px solid #ddd;'>بريد المريض</th>";
echo "<th style='padding: 10px; border: 1px solid #ddd;'>رقم الهاتف</th>";
echo "<th style='padding: 10px; border: 1px solid #ddd;'>Doctor ID</th>";
echo "</tr>";

$patientEmails = [];
while ($row = mysqli_fetch_assoc($patientResult)) {
    if ($row['PatientEmail']) {
        $patientEmails[] = $row['PatientEmail'];
    }
    
    echo "<tr>";
    echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $row['ID'] . "</td>";
    echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($row['PatientName']) . "</td>";
    echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($row['PatientEmail']) . "</td>";
    echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($row['PatientContno']) . "</td>";
    echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $row['Docid'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// 3. Check for email matches
echo "<h3>3️⃣ فحص التطابق بين البريد الإلكتروني</h3>";
$matchingEmails = array_intersect($appointmentEmails, $patientEmails);
$appointmentOnlyEmails = array_diff($appointmentEmails, $patientEmails);
$patientOnlyEmails = array_diff($patientEmails, $appointmentEmails);

echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h4>📊 إحصائيات التطابق:</h4>";
echo "<ul>";
echo "<li><strong>إيميلات متطابقة:</strong> " . count($matchingEmails) . "</li>";
echo "<li><strong>إيميلات في المواعيد فقط:</strong> " . count($appointmentOnlyEmails) . "</li>";
echo "<li><strong>إيميلات في المرضى فقط:</strong> " . count($patientOnlyEmails) . "</li>";
echo "</ul>";
echo "</div>";

if (!empty($appointmentOnlyEmails)) {
    echo "<h4>❌ إيميلات موجودة في المواعيد لكن غير موجودة في جدول المرضى:</h4>";
    echo "<ul>";
    foreach ($appointmentOnlyEmails as $email) {
        echo "<li>" . htmlspecialchars($email) . "</li>";
    }
    echo "</ul>";
}

// 4. Test the JOIN query used in appointment-history.php
echo "<h3>4️⃣ اختبار استعلام الربط المستخدم في appointment-history.php</h3>";

// Get a sample user ID
$userResult = mysqli_query($con, "SELECT id FROM users LIMIT 1");
$sampleUser = mysqli_fetch_assoc($userResult);

if ($sampleUser) {
    $testUserId = $sampleUser['id'];
    echo "<p><strong>اختبار للمستخدم ID:</strong> $testUserId</p>";
    
    $testQuery = mysqli_query($con, "
        SELECT 
            a.id, a.doctorSpecialization, a.consultancyFees,
            a.appointmentDate, a.appointmentTime, a.postingDate,
            a.userStatus, a.doctorStatus,
            d.doctorName AS docname,
            u.fullName AS userName,
            u.email AS userEmail
        FROM appointment a
        JOIN doctors d ON d.id = a.doctorId
        JOIN users u ON u.id = a.userId
        WHERE a.userId = $testUserId
        ORDER BY a.appointmentDate DESC, a.appointmentTime DESC
        LIMIT 5
    ");
    
    echo "<table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>";
    echo "<tr style='background: #6c757d; color: white;'>";
    echo "<th style='padding: 10px; border: 1px solid #ddd;'>رقم الموعد</th>";
    echo "<th style='padding: 10px; border: 1px solid #ddd;'>اسم الطبيب</th>";
    echo "<th style='padding: 10px; border: 1px solid #ddd;'>اسم المريض</th>";
    echo "<th style='padding: 10px; border: 1px solid #ddd;'>بريد المريض</th>";
    echo "<th style='padding: 10px; border: 1px solid #ddd;'>التاريخ</th>";
    echo "<th style='padding: 10px; border: 1px solid #ddd;'>الوقت</th>";
    echo "</tr>";
    
    $queryCount = 0;
    while ($row = mysqli_fetch_assoc($testQuery)) {
        $queryCount++;
        echo "<tr>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $row['id'] . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($row['docname']) . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($row['userName']) . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($row['userEmail']) . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $row['appointmentDate'] . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $row['appointmentTime'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p><strong>نتائج الاستعلام:</strong> $queryCount موعد</p>";
}

// 5. Create missing patient records
echo "<h3>5️⃣ إنشاء سجلات المرضى المفقودة</h3>";

if (isset($_POST['create_missing_patients'])) {
    echo "<div style='background: #e3f2fd; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h4>🔄 إنشاء سجلات المرضى المفقودة...</h4>";
    
    // Get users who have appointments but no patient records
    $missingPatientsQuery = mysqli_query($con, "
        SELECT DISTINCT 
            u.id,
            u.fullName,
            u.email,
            u.gender,
            u.address,
            a.doctorId
        FROM appointment a
        JOIN users u ON u.id = a.userId
        LEFT JOIN tblpatient p ON p.PatientEmail = u.email
        WHERE p.ID IS NULL
    ");
    
    $created = 0;
    $yemeniPrefixes = ['77', '73', '70', '71', '78'];
    
    while ($row = mysqli_fetch_assoc($missingPatientsQuery)) {
        $prefix = $yemeniPrefixes[array_rand($yemeniPrefixes)];
        $phoneNumber = $prefix . rand(1000000, 9999999);
        
        $insertStmt = mysqli_prepare($con, "
            INSERT INTO tblpatient 
            (PatientName, PatientEmail, PatientContno, PatientGender, PatientAdd, Docid, CreationDate)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        mysqli_stmt_bind_param($insertStmt, 'sssssi', 
            $row['fullName'],
            $row['email'],
            $phoneNumber,
            $row['gender'] ?: 'ذكر',
            $row['address'] ?: 'صنعاء',
            $row['doctorId']
        );
        
        if (mysqli_stmt_execute($insertStmt)) {
            $created++;
            echo "<p>✅ تم إنشاء سجل للمريض: " . htmlspecialchars($row['fullName']) . " - الهاتف: $phoneNumber</p>";
        }
        mysqli_stmt_close($insertStmt);
    }
    
    echo "<p><strong>تم إنشاء $created سجل مريض جديد</strong></p>";
    echo "</div>";
}

echo "<form method='post' style='background: #fff3cd; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h4>🔧 إصلاح عدم التطابق</h4>";
echo "<p>سيتم إنشاء سجلات في جدول المرضى للمستخدمين الذين لديهم مواعيد لكن لا يوجد لهم سجل في جدول المرضى.</p>";
echo "<button type='submit' name='create_missing_patients' style='background: #ffc107; color: #212529; padding: 12px 25px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;'>";
echo "🔧 إنشاء سجلات المرضى المفقودة";
echo "</button>";
echo "</form>";

// 6. Test links
echo "<div style='background: #d1ecf1; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h4>🔗 اختبر الصفحات بعد الإصلاح:</h4>";
echo "<ul>";
echo "<li><a href='hms/appointment-history.php' target='_blank' style='color: #0c5460; font-weight: bold;'>📋 سجل مواعيد المريض</a></li>";
echo "<li><a href='hms/doctor/appointment-history.php' target='_blank' style='color: #0c5460; font-weight: bold;'>👨‍⚕️ سجل مواعيد الطبيب</a></li>";
echo "<li><a href='hms/dashboard.php' target='_blank' style='color: #0c5460;'>🏠 لوحة تحكم المريض</a></li>";
echo "</ul>";
echo "</div>";

mysqli_close($con);
?>
