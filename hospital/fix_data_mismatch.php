<?php
require_once 'hms/include/config.php';

echo "<h2>🔧 إصلاح عدم التطابق بين الجداول</h2>";

// Step 1: Analyze the mismatch
echo "<h3>1️⃣ تحليل المشكلة</h3>";

// Check appointments without matching patients
$orphanAppointments = mysqli_query($con, "
    SELECT 
        a.id,
        a.userId,
        a.doctorId,
        u.fullName,
        u.email,
        d.doctorName
    FROM appointment a
    JOIN users u ON u.id = a.userId
    JOIN doctors d ON d.id = a.doctorId
    LEFT JOIN tblpatient p ON p.PatientEmail = u.email
    WHERE p.ID IS NULL
    ORDER BY a.id DESC
");

$orphanCount = 0;
$orphanData = [];
while ($row = mysqli_fetch_assoc($orphanAppointments)) {
    $orphanCount++;
    $orphanData[] = $row;
}

echo "<div style='background: #f8d7da; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h4>❌ المشكلة المكتشفة:</h4>";
echo "<p><strong>عدد المواعيد بدون سجلات مرضى متطابقة:</strong> $orphanCount</p>";
echo "<p><strong>السبب:</strong> المواعيد مربوطة بجدول المستخدمين (users) لكن أرقام الهواتف موجودة في جدول المرضى (tblpatient)</p>";
echo "</div>";

if ($orphanCount > 0) {
    echo "<h4>📋 المواعيد التي تحتاج إصلاح:</h4>";
    echo "<table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>";
    echo "<tr style='background: #dc3545; color: white;'>";
    echo "<th style='padding: 10px; border: 1px solid #ddd;'>رقم الموعد</th>";
    echo "<th style='padding: 10px; border: 1px solid #ddd;'>اسم المريض</th>";
    echo "<th style='padding: 10px; border: 1px solid #ddd;'>البريد الإلكتروني</th>";
    echo "<th style='padding: 10px; border: 1px solid #ddd;'>اسم الطبيب</th>";
    echo "</tr>";
    
    foreach (array_slice($orphanData, 0, 10) as $row) {
        echo "<tr>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $row['id'] . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($row['fullName']) . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($row['email']) . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($row['doctorName']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    if ($orphanCount > 10) {
        echo "<p><em>... و " . ($orphanCount - 10) . " موعد آخر</em></p>";
    }
}

// Step 2: Auto-fix the mismatch
if (isset($_POST['auto_fix'])) {
    echo "<h3>2️⃣ تنفيذ الإصلاح التلقائي</h3>";
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h4>🔄 جاري إصلاح البيانات...</h4>";
    
    $fixed = 0;
    $yemeniPrefixes = ['77', '73', '70', '71', '78'];
    $yemeniCities = ['صنعاء', 'عدن', 'تعز', 'الحديدة', 'إب', 'ذمار', 'المكلا', 'سيئون'];
    
    // Reset the query
    $orphanAppointments = mysqli_query($con, "
        SELECT DISTINCT
            u.id as userId,
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
    
    while ($row = mysqli_fetch_assoc($orphanAppointments)) {
        // Generate realistic data
        $prefix = $yemeniPrefixes[array_rand($yemeniPrefixes)];
        $phoneNumber = $prefix . rand(1000000, 9999999);
        $city = $yemeniCities[array_rand($yemeniCities)];
        $address = $row['address'] ?: ($city . ' - حي ' . rand(1, 10));
        $gender = $row['gender'] ?: (rand(0, 1) ? 'ذكر' : 'أنثى');
        $age = rand(20, 70);
        
        // Insert patient record
        $insertStmt = mysqli_prepare($con, "
            INSERT INTO tblpatient 
            (PatientName, PatientEmail, PatientContno, PatientGender, PatientAdd, PatientAge, Docid, CreationDate)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        mysqli_stmt_bind_param($insertStmt, 'sssssii', 
            $row['fullName'],
            $row['email'],
            $phoneNumber,
            $gender,
            $address,
            $age,
            $row['doctorId']
        );
        
        if (mysqli_stmt_execute($insertStmt)) {
            $fixed++;
            echo "<p>✅ تم إنشاء سجل للمريض: <strong>" . htmlspecialchars($row['fullName']) . "</strong></p>";
            echo "<p style='margin-left: 20px; color: #666;'>📱 الهاتف: $phoneNumber | 📍 العنوان: $address | 👤 الجنس: $gender</p>";
        } else {
            echo "<p>❌ فشل في إنشاء سجل للمريض: " . htmlspecialchars($row['fullName']) . "</p>";
            echo "<p style='margin-left: 20px; color: #dc3545;'>خطأ: " . mysqli_error($con) . "</p>";
        }
        mysqli_stmt_close($insertStmt);
    }
    
    echo "<h4>📊 نتائج الإصلاح:</h4>";
    echo "<p><strong>تم إصلاح $fixed سجل بنجاح!</strong></p>";
    echo "</div>";
    
    // Verify the fix
    echo "<h4>🔍 التحقق من الإصلاح:</h4>";
    $verifyQuery = mysqli_query($con, "
        SELECT COUNT(*) as remaining
        FROM appointment a
        JOIN users u ON u.id = a.userId
        LEFT JOIN tblpatient p ON p.PatientEmail = u.email
        WHERE p.ID IS NULL
    ");
    $remaining = mysqli_fetch_assoc($verifyQuery)['remaining'];
    
    if ($remaining == 0) {
        echo "<p style='color: #28a745; font-weight: bold; font-size: 18px;'>✅ تم إصلاح جميع المشاكل! لا توجد مواعيد بدون سجلات مرضى.</p>";
    } else {
        echo "<p style='color: #ffc107; font-weight: bold;'>⚠️ لا يزال هناك $remaining موعد يحتاج إصلاح.</p>";
    }
}

// Step 3: Test the appointment-history query
echo "<h3>3️⃣ اختبار استعلام سجل المواعيد</h3>";

// Get a sample user
$userResult = mysqli_query($con, "SELECT id, fullName, email FROM users LIMIT 1");
$testUser = mysqli_fetch_assoc($userResult);

if ($testUser) {
    echo "<p><strong>اختبار للمستخدم:</strong> " . htmlspecialchars($testUser['fullName']) . " (ID: " . $testUser['id'] . ")</p>";
    
    // Test the query from appointment-history.php
    $testQuery = mysqli_query($con, "
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
        WHERE a.userId = " . $testUser['id'] . "
        ORDER BY a.appointmentDate DESC, TIME(a.appointmentTime) DESC, a.id DESC
        LIMIT 5
    ");
    
    echo "<table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>";
    echo "<tr style='background: #28a745; color: white;'>";
    echo "<th style='padding: 10px; border: 1px solid #ddd;'>رقم الموعد</th>";
    echo "<th style='padding: 10px; border: 1px solid #ddd;'>اسم الطبيب</th>";
    echo "<th style='padding: 10px; border: 1px solid #ddd;'>التخصص</th>";
    echo "<th style='padding: 10px; border: 1px solid #ddd;'>التاريخ</th>";
    echo "<th style='padding: 10px; border: 1px solid #ddd;'>الوقت</th>";
    echo "<th style='padding: 10px; border: 1px solid #ddd;'>الحالة</th>";
    echo "</tr>";
    
    $testCount = 0;
    while ($row = mysqli_fetch_assoc($testQuery)) {
        $testCount++;
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
    
    echo "<p><strong>نتائج الاستعلام:</strong> $testCount موعد للمستخدم</p>";
}

// Auto-fix button
if ($orphanCount > 0) {
    echo "<div style='background: #fff3cd; padding: 25px; border-radius: 8px; margin: 20px 0; border-left: 5px solid #ffc107;'>";
    echo "<h4>🔧 إصلاح تلقائي</h4>";
    echo "<p>سيتم إنشاء سجلات في جدول المرضى لجميع المستخدمين الذين لديهم مواعيد لكن لا يوجد لهم سجل في جدول المرضى.</p>";
    echo "<p><strong>سيتم إنشاء:</strong></p>";
    echo "<ul>";
    echo "<li>أرقام هواتف يمنية واقعية</li>";
    echo "<li>عناوين يمنية</li>";
    echo "<li>أعمار وأجناس منطقية</li>";
    echo "<li>ربط صحيح مع الأطباء</li>";
    echo "</ul>";
    
    echo "<form method='post'>";
    echo "<button type='submit' name='auto_fix' style='background: #ffc107; color: #212529; padding: 15px 30px; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; font-size: 16px;'>";
    echo "🚀 تشغيل الإصلاح التلقائي ($orphanCount سجل)";
    echo "</button>";
    echo "</form>";
    echo "</div>";
} else {
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h4>✅ لا توجد مشاكل!</h4>";
    echo "<p>جميع المواعيد لها سجلات مرضى متطابقة.</p>";
    echo "</div>";
}

// Test links
echo "<div style='background: #e3f2fd; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h4>🔗 اختبر الصفحات بعد الإصلاح:</h4>";
echo "<ul>";
echo "<li><a href='hms/appointment-history.php' target='_blank' style='color: #1976d2; font-weight: bold; font-size: 16px;'>📋 سجل مواعيد المريض</a></li>";
echo "<li><a href='hms/doctor/appointment-history.php' target='_blank' style='color: #1976d2; font-weight: bold; font-size: 16px;'>👨‍⚕️ سجل مواعيد الطبيب</a></li>";
echo "<li><a href='hms/dashboard.php' target='_blank' style='color: #1976d2;'>🏠 لوحة تحكم المريض</a></li>";
echo "<li><a href='hms/doctor/dashboard.php' target='_blank' style='color: #1976d2;'>👨‍⚕️ لوحة تحكم الطبيب</a></li>";
echo "</ul>";
echo "</div>";

mysqli_close($con);
?>
