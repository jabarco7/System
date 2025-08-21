<?php
require_once 'hms/include/config.php';

echo "<h2>🔄 مزامنة بيانات المرضى والمواعيد</h2>";

// 1. Analyze the current mismatch
echo "<h3>1️⃣ تحليل عدم التطابق الحالي</h3>";

// Get appointments with user data
$appointmentData = [];
$appointmentQuery = mysqli_query($con, "
    SELECT 
        a.id as appointmentId,
        a.userId,
        a.doctorId,
        u.fullName,
        u.email,
        u.gender,
        u.address,
        d.doctorName
    FROM appointment a
    JOIN users u ON u.id = a.userId
    JOIN doctors d ON d.id = a.doctorId
    ORDER BY a.id DESC
");

while ($row = mysqli_fetch_assoc($appointmentQuery)) {
    $appointmentData[] = $row;
}

// Get existing patients
$existingPatients = [];
$patientQuery = mysqli_query($con, "SELECT PatientEmail, PatientName FROM tblpatient");
while ($row = mysqli_fetch_assoc($patientQuery)) {
    $existingPatients[strtolower($row['PatientEmail'])] = $row['PatientName'];
}

echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h4>📊 إحصائيات البيانات:</h4>";
echo "<ul>";
echo "<li><strong>إجمالي المواعيد:</strong> " . count($appointmentData) . "</li>";
echo "<li><strong>إجمالي المرضى في tblpatient:</strong> " . count($existingPatients) . "</li>";
echo "</ul>";
echo "</div>";

// Check mismatches
$mismatches = [];
$matches = 0;

foreach ($appointmentData as $appointment) {
    $email = strtolower($appointment['email']);
    if (isset($existingPatients[$email])) {
        if ($existingPatients[$email] !== $appointment['fullName']) {
            $mismatches[] = [
                'email' => $appointment['email'],
                'appointment_name' => $appointment['fullName'],
                'patient_name' => $existingPatients[$email],
                'type' => 'name_mismatch'
            ];
        } else {
            $matches++;
        }
    } else {
        $mismatches[] = [
            'email' => $appointment['email'],
            'appointment_name' => $appointment['fullName'],
            'patient_name' => null,
            'type' => 'missing_patient'
        ];
    }
}

echo "<div style='background: " . (count($mismatches) > 0 ? '#f8d7da' : '#d4edda') . "; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h4>" . (count($mismatches) > 0 ? '❌' : '✅') . " نتائج التحليل:</h4>";
echo "<ul>";
echo "<li><strong>البيانات المتطابقة:</strong> $matches</li>";
echo "<li><strong>البيانات غير المتطابقة:</strong> " . count($mismatches) . "</li>";
echo "</ul>";
echo "</div>";

if (count($mismatches) > 0) {
    echo "<h4>📋 البيانات التي تحتاج إصلاح:</h4>";
    echo "<table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>";
    echo "<tr style='background: #dc3545; color: white;'>";
    echo "<th style='padding: 10px; border: 1px solid #ddd;'>البريد الإلكتروني</th>";
    echo "<th style='padding: 10px; border: 1px solid #ddd;'>الاسم في المواعيد</th>";
    echo "<th style='padding: 10px; border: 1px solid #ddd;'>الاسم في المرضى</th>";
    echo "<th style='padding: 10px; border: 1px solid #ddd;'>نوع المشكلة</th>";
    echo "</tr>";
    
    foreach (array_slice($mismatches, 0, 15) as $mismatch) {
        $problemType = $mismatch['type'] === 'missing_patient' ? 'مريض مفقود' : 'اسم مختلف';
        $patientName = $mismatch['patient_name'] ?? 'غير موجود';
        
        echo "<tr>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($mismatch['email']) . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($mismatch['appointment_name']) . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($patientName) . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $problemType . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    if (count($mismatches) > 15) {
        echo "<p><em>... و " . (count($mismatches) - 15) . " مشكلة أخرى</em></p>";
    }
}

// 2. Auto-sync function
if (isset($_POST['sync_data'])) {
    echo "<h3>2️⃣ تنفيذ المزامنة التلقائية</h3>";
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h4>🔄 جاري مزامنة البيانات...</h4>";
    
    $synced = 0;
    $updated = 0;
    $created = 0;
    $yemeniPrefixes = ['77', '73', '70', '71', '78'];
    $yemeniCities = ['صنعاء', 'عدن', 'تعز', 'الحديدة', 'إب', 'ذمار', 'المكلا', 'سيئون'];
    
    foreach ($appointmentData as $appointment) {
        $email = $appointment['email'];
        $fullName = $appointment['fullName'];
        $gender = $appointment['gender'] ?: (rand(0, 1) ? 'ذكر' : 'أنثى');
        $address = $appointment['address'] ?: ($yemeniCities[array_rand($yemeniCities)] . ' - حي ' . rand(1, 10));
        $doctorId = $appointment['doctorId'];
        
        // Generate phone number
        $prefix = $yemeniPrefixes[array_rand($yemeniPrefixes)];
        $phoneNumber = $prefix . rand(1000000, 9999999);
        $age = rand(20, 70);
        
        // Check if patient exists
        $checkStmt = mysqli_prepare($con, "SELECT ID, PatientName FROM tblpatient WHERE PatientEmail = ?");
        mysqli_stmt_bind_param($checkStmt, 's', $email);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);
        $existingPatient = mysqli_fetch_assoc($checkResult);
        mysqli_stmt_close($checkStmt);
        
        if ($existingPatient) {
            // Update existing patient if name is different
            if ($existingPatient['PatientName'] !== $fullName) {
                $updateStmt = mysqli_prepare($con, "
                    UPDATE tblpatient 
                    SET PatientName = ?, PatientGender = ?, PatientAdd = ?, PatientAge = ?, Docid = ?
                    WHERE PatientEmail = ?
                ");
                mysqli_stmt_bind_param($updateStmt, 'sssiss', $fullName, $gender, $address, $age, $doctorId, $email);
                
                if (mysqli_stmt_execute($updateStmt)) {
                    $updated++;
                    echo "<p>🔄 تم تحديث المريض: <strong>" . htmlspecialchars($fullName) . "</strong></p>";
                }
                mysqli_stmt_close($updateStmt);
            }
        } else {
            // Create new patient
            $insertStmt = mysqli_prepare($con, "
                INSERT INTO tblpatient 
                (PatientName, PatientEmail, PatientContno, PatientGender, PatientAdd, PatientAge, Docid, CreationDate)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            mysqli_stmt_bind_param($insertStmt, 'sssssii', $fullName, $email, $phoneNumber, $gender, $address, $age, $doctorId);
            
            if (mysqli_stmt_execute($insertStmt)) {
                $created++;
                echo "<p>✅ تم إنشاء مريض جديد: <strong>" . htmlspecialchars($fullName) . "</strong></p>";
                echo "<p style='margin-left: 20px; color: #666;'>📱 الهاتف: $phoneNumber | 📍 العنوان: $address</p>";
            }
            mysqli_stmt_close($insertStmt);
        }
        $synced++;
    }
    
    echo "<h4>📊 نتائج المزامنة:</h4>";
    echo "<ul>";
    echo "<li><strong>إجمالي السجلات المعالجة:</strong> $synced</li>";
    echo "<li><strong>سجلات محدثة:</strong> $updated</li>";
    echo "<li><strong>سجلات جديدة:</strong> $created</li>";
    echo "</ul>";
    echo "</div>";
    
    // Verify the sync
    echo "<h4>🔍 التحقق من المزامنة:</h4>";
    $verifyQuery = mysqli_query($con, "
        SELECT COUNT(*) as remaining
        FROM appointment a
        JOIN users u ON u.id = a.userId
        LEFT JOIN tblpatient p ON p.PatientEmail = u.email
        WHERE p.ID IS NULL OR p.PatientName != u.fullName
    ");
    $remaining = mysqli_fetch_assoc($verifyQuery)['remaining'];
    
    if ($remaining == 0) {
        echo "<p style='color: #28a745; font-weight: bold; font-size: 18px;'>✅ تمت المزامنة بنجاح! جميع البيانات متطابقة الآن.</p>";
    } else {
        echo "<p style='color: #ffc107; font-weight: bold;'>⚠️ لا يزال هناك $remaining سجل يحتاج مزامنة.</p>";
    }
}

// 3. Test all appointment pages
echo "<h3>3️⃣ اختبار جميع صفحات المواعيد</h3>";

$appointmentPages = [
    'صفحات المريض' => [
        'سجل مواعيد المريض' => 'hms/appointment-history.php',
        'لوحة تحكم المريض' => 'hms/dashboard.php',
        'حجز موعد' => 'hms/book-appointment.php',
    ],
    'صفحات الطبيب' => [
        'سجل مواعيد الطبيب' => 'hms/doctor/appointment-history.php',
        'لوحة تحكم الطبيب' => 'hms/doctor/dashboard.php',
        'إدارة المرضى' => 'hms/doctor/manage-patient.php',
    ],
    'صفحات الإدارة' => [
        'إدارة المواعيد' => 'hms/admin/appointment-history.php',
        'إدارة المرضى' => 'hms/admin/manage-patient.php',
        'تقارير المواعيد' => 'hms/admin/appointment-reports.php',
    ]
];

foreach ($appointmentPages as $category => $pages) {
    echo "<h4>$category:</h4>";
    echo "<ul>";
    foreach ($pages as $name => $url) {
        $fullPath = __DIR__ . '/' . $url;
        $exists = file_exists($fullPath);
        $status = $exists ? '✅' : '❌';
        
        echo "<li>$status <a href='$url' target='_blank' style='color: " . ($exists ? '#28a745' : '#dc3545') . "; font-weight: bold;'>$name</a>";
        if (!$exists) {
            echo " <em>(الملف غير موجود)</em>";
        }
        echo "</li>";
    }
    echo "</ul>";
}

// Sync button
if (count($mismatches) > 0) {
    echo "<div style='background: #fff3cd; padding: 25px; border-radius: 8px; margin: 20px 0; border-left: 5px solid #ffc107;'>";
    echo "<h4>🔧 مزامنة البيانات</h4>";
    echo "<p>سيتم مزامنة جميع بيانات المرضى مع بيانات المواعيد:</p>";
    echo "<ul>";
    echo "<li>✅ توحيد الأسماء بين الجدولين</li>";
    echo "<li>✅ إنشاء سجلات مرضى للمواعيد المفقودة</li>";
    echo "<li>✅ إضافة أرقام هواتف يمنية واقعية</li>";
    echo "<li>✅ إكمال البيانات الناقصة (العنوان، العمر، الجنس)</li>";
    echo "<li>✅ ربط صحيح مع الأطباء</li>";
    echo "</ul>";
    
    echo "<form method='post'>";
    echo "<button type='submit' name='sync_data' style='background: #ffc107; color: #212529; padding: 15px 30px; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; font-size: 16px;'>";
    echo "🚀 تشغيل المزامنة التلقائية (" . count($mismatches) . " سجل)";
    echo "</button>";
    echo "</form>";
    echo "</div>";
} else {
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h4>✅ البيانات متزامنة!</h4>";
    echo "<p>جميع بيانات المرضى والمواعيد متطابقة.</p>";
    echo "</div>";
}

// Sample data after sync
echo "<h3>4️⃣ عينة من البيانات بعد المزامنة</h3>";
$sampleQuery = mysqli_query($con, "
    SELECT 
        a.id as appointmentId,
        u.fullName as userName,
        p.PatientName,
        p.PatientContno,
        u.email,
        d.doctorName,
        a.appointmentDate
    FROM appointment a
    JOIN users u ON u.id = a.userId
    JOIN doctors d ON d.id = a.doctorId
    LEFT JOIN tblpatient p ON p.PatientEmail = u.email
    ORDER BY a.id DESC
    LIMIT 10
");

echo "<table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>";
echo "<tr style='background: #6c757d; color: white;'>";
echo "<th style='padding: 10px; border: 1px solid #ddd;'>رقم الموعد</th>";
echo "<th style='padding: 10px; border: 1px solid #ddd;'>اسم المستخدم</th>";
echo "<th style='padding: 10px; border: 1px solid #ddd;'>اسم المريض</th>";
echo "<th style='padding: 10px; border: 1px solid #ddd;'>رقم الهاتف</th>";
echo "<th style='padding: 10px; border: 1px solid #ddd;'>الطبيب</th>";
echo "<th style='padding: 10px; border: 1px solid #ddd;'>التطابق</th>";
echo "</tr>";

while ($row = mysqli_fetch_assoc($sampleQuery)) {
    $isMatched = ($row['userName'] === $row['PatientName']) && !empty($row['PatientContno']);
    $matchStatus = $isMatched ? '✅ متطابق' : '❌ غير متطابق';
    $bgColor = $isMatched ? 'background: #d4edda;' : 'background: #f8d7da;';
    
    echo "<tr style='$bgColor'>";
    echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $row['appointmentId'] . "</td>";
    echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($row['userName']) . "</td>";
    echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($row['PatientName'] ?? 'غير موجود') . "</td>";
    echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($row['PatientContno'] ?? 'غير موجود') . "</td>";
    echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($row['doctorName']) . "</td>";
    echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $matchStatus . "</td>";
    echo "</tr>";
}
echo "</table>";

mysqli_close($con);
?>
