<?php
require_once 'hms/include/config.php';

echo "<h1>🔧 إصلاح شامل لجميع مشاكل المواعيد</h1>";

// 1. Check and fix data synchronization
echo "<h2>1️⃣ مزامنة بيانات المرضى والمواعيد</h2>";

if (isset($_POST['sync_all_data'])) {
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>🔄 جاري المزامنة الشاملة...</h3>";
    
    $synced = 0;
    $created = 0;
    $updated = 0;
    $yemeniPrefixes = ['77', '73', '70', '71', '78'];
    $yemeniCities = ['صنعاء', 'عدن', 'تعز', 'الحديدة', 'إب', 'ذمار', 'المكلا', 'سيئون'];
    
    // Get all appointments with user data
    $appointmentQuery = mysqli_query($con, "
        SELECT DISTINCT
            u.id as userId,
            u.fullName,
            u.email,
            u.gender,
            u.address,
            a.doctorId
        FROM appointment a
        JOIN users u ON u.id = a.userId
        ORDER BY u.id
    ");
    
    while ($row = mysqli_fetch_assoc($appointmentQuery)) {
        $email = $row['email'];
        $fullName = $row['fullName'];
        $gender = $row['gender'] ?: (rand(0, 1) ? 'ذكر' : 'أنثى');
        $address = $row['address'] ?: ($yemeniCities[array_rand($yemeniCities)] . ' - حي ' . rand(1, 10));
        $doctorId = $row['doctorId'];
        
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
            // Update existing patient to match user data
            if ($existingPatient['PatientName'] !== $fullName) {
                $updateStmt = mysqli_prepare($con, "
                    UPDATE tblpatient 
                    SET PatientName = ?, PatientGender = ?, PatientAdd = ?, PatientAge = ?, Docid = ?
                    WHERE PatientEmail = ?
                ");
                mysqli_stmt_bind_param($updateStmt, 'sssiss', $fullName, $gender, $address, $age, $doctorId, $email);
                
                if (mysqli_stmt_execute($updateStmt)) {
                    $updated++;
                    echo "<p>🔄 تم تحديث: <strong>" . htmlspecialchars($fullName) . "</strong></p>";
                }
                mysqli_stmt_close($updateStmt);
            }
        } else {
            // Create new patient record
            $insertStmt = mysqli_prepare($con, "
                INSERT INTO tblpatient 
                (PatientName, PatientEmail, PatientContno, PatientGender, PatientAdd, PatientAge, Docid, CreationDate)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            mysqli_stmt_bind_param($insertStmt, 'sssssii', $fullName, $email, $phoneNumber, $gender, $address, $age, $doctorId);
            
            if (mysqli_stmt_execute($insertStmt)) {
                $created++;
                echo "<p>✅ تم إنشاء: <strong>" . htmlspecialchars($fullName) . "</strong> - الهاتف: $phoneNumber</p>";
            }
            mysqli_stmt_close($insertStmt);
        }
        $synced++;
    }
    
    echo "<h4>📊 نتائج المزامنة:</h4>";
    echo "<ul>";
    echo "<li><strong>إجمالي المعالج:</strong> $synced</li>";
    echo "<li><strong>سجلات محدثة:</strong> $updated</li>";
    echo "<li><strong>سجلات جديدة:</strong> $created</li>";
    echo "</ul>";
    echo "</div>";
}

// 2. Create test appointments for all users
if (isset($_POST['create_test_appointments'])) {
    echo "<div style='background: #e3f2fd; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>📅 إنشاء مواعيد تجريبية...</h3>";
    
    // Get users without appointments
    $usersWithoutAppts = mysqli_query($con, "
        SELECT u.id, u.fullName, u.email
        FROM users u
        LEFT JOIN appointment a ON a.userId = u.id
        WHERE a.id IS NULL
        LIMIT 10
    ");
    
    // Get available doctors
    $doctors = [];
    $doctorResult = mysqli_query($con, "SELECT id, doctorName, specilization, docFees FROM doctors LIMIT 5");
    while ($row = mysqli_fetch_assoc($doctorResult)) {
        $doctors[] = $row;
    }
    
    $appointmentsCreated = 0;
    while ($user = mysqli_fetch_assoc($usersWithoutAppts)) {
        if (!empty($doctors)) {
            $doctor = $doctors[array_rand($doctors)];
            $date = date('Y-m-d', strtotime('+' . rand(1, 30) . ' days'));
            $times = ['09:00:00', '10:00:00', '11:00:00', '14:00:00', '15:00:00', '16:00:00'];
            $time = $times[array_rand($times)];
            
            $insertStmt = mysqli_prepare($con, "
                INSERT INTO appointment 
                (userId, doctorId, doctorSpecialization, consultancyFees, appointmentDate, appointmentTime, postingDate, userStatus, doctorStatus)
                VALUES (?, ?, ?, ?, ?, ?, NOW(), 1, 1)
            ");
            
            mysqli_stmt_bind_param($insertStmt, 'iisiss', 
                $user['id'], 
                $doctor['id'], 
                $doctor['specilization'], 
                $doctor['docFees'], 
                $date, 
                $time
            );
            
            if (mysqli_stmt_execute($insertStmt)) {
                $appointmentsCreated++;
                echo "<p>✅ موعد لـ <strong>" . htmlspecialchars($user['fullName']) . "</strong> مع د. " . htmlspecialchars($doctor['doctorName']) . " في $date</p>";
            }
            mysqli_stmt_close($insertStmt);
        }
    }
    
    echo "<p><strong>تم إنشاء $appointmentsCreated موعد تجريبي</strong></p>";
    echo "</div>";
}

// 3. Check all appointment pages
echo "<h2>2️⃣ فحص جميع صفحات المواعيد</h2>";

$appointmentPages = [
    'المريض' => [
        'سجل المواعيد' => 'hms/appointment-history.php',
        'لوحة التحكم' => 'hms/dashboard.php',
        'حجز موعد' => 'hms/book-appointment.php',
    ],
    'الطبيب' => [
        'سجل المواعيد' => 'hms/doctor/appointment-history.php',
        'لوحة التحكم' => 'hms/doctor/dashboard.php',
        'إدارة المرضى' => 'hms/doctor/manage-patient.php',
    ],
    'الإدارة' => [
        'سجل المواعيد' => 'hms/admin/appointment-history.php',
        'إدارة المرضى' => 'hms/admin/manage-patient.php',
        'لوحة التحكم' => 'hms/admin/dashboard.php',
    ]
];

echo "<table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>";
echo "<tr style='background: #6c757d; color: white;'>";
echo "<th style='padding: 10px; border: 1px solid #ddd;'>القسم</th>";
echo "<th style='padding: 10px; border: 1px solid #ddd;'>الصفحة</th>";
echo "<th style='padding: 10px; border: 1px solid #ddd;'>الحالة</th>";
echo "<th style='padding: 10px; border: 1px solid #ddd;'>الرابط</th>";
echo "</tr>";

foreach ($appointmentPages as $section => $pages) {
    foreach ($pages as $name => $url) {
        $fullPath = __DIR__ . '/' . $url;
        $exists = file_exists($fullPath);
        $status = $exists ? '✅ موجود' : '❌ مفقود';
        $color = $exists ? '#28a745' : '#dc3545';
        
        echo "<tr>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'><strong>$section</strong></td>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>$name</td>";
        echo "<td style='padding: 8px; border: 1px solid #ddd; color: $color; font-weight: bold;'>$status</td>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>";
        if ($exists) {
            echo "<a href='$url' target='_blank' style='color: $color; font-weight: bold;'>فتح الصفحة</a>";
        } else {
            echo "<em>الملف غير موجود</em>";
        }
        echo "</td>";
        echo "</tr>";
    }
}
echo "</table>";

// 4. Statistics
echo "<h2>3️⃣ إحصائيات النظام</h2>";

$stats = [];
$stats['users'] = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as count FROM users"))['count'];
$stats['doctors'] = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as count FROM doctors"))['count'];
$stats['appointments'] = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as count FROM appointment"))['count'];
$stats['patients'] = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as count FROM tblpatient"))['count'];
$stats['active_appointments'] = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as count FROM appointment WHERE userStatus = 1 AND doctorStatus = 1"))['count'];

// Check data consistency
$consistencyQuery = mysqli_query($con, "
    SELECT 
        COUNT(CASE WHEN p.ID IS NULL THEN 1 END) as missing_patients,
        COUNT(CASE WHEN p.PatientName = u.fullName THEN 1 END) as matching_names,
        COUNT(*) as total_appointments
    FROM appointment a
    JOIN users u ON u.id = a.userId
    LEFT JOIN tblpatient p ON p.PatientEmail = u.email
");
$consistency = mysqli_fetch_assoc($consistencyQuery);

echo "<div style='display: flex; gap: 20px; margin: 20px 0;'>";

echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; flex: 1; border-left: 5px solid #007bff;'>";
echo "<h4>📊 إحصائيات عامة</h4>";
echo "<ul>";
echo "<li><strong>المستخدمين:</strong> " . $stats['users'] . "</li>";
echo "<li><strong>الأطباء:</strong> " . $stats['doctors'] . "</li>";
echo "<li><strong>المواعيد:</strong> " . $stats['appointments'] . "</li>";
echo "<li><strong>المرضى:</strong> " . $stats['patients'] . "</li>";
echo "<li><strong>المواعيد النشطة:</strong> " . $stats['active_appointments'] . "</li>";
echo "</ul>";
echo "</div>";

echo "<div style='background: " . ($consistency['missing_patients'] > 0 ? '#f8d7da' : '#d4edda') . "; padding: 20px; border-radius: 8px; flex: 1; border-left: 5px solid " . ($consistency['missing_patients'] > 0 ? '#dc3545' : '#28a745') . ";'>";
echo "<h4>🔍 تطابق البيانات</h4>";
echo "<ul>";
echo "<li><strong>إجمالي المواعيد:</strong> " . $consistency['total_appointments'] . "</li>";
echo "<li><strong>الأسماء المتطابقة:</strong> " . $consistency['matching_names'] . "</li>";
echo "<li><strong>المرضى المفقودين:</strong> " . $consistency['missing_patients'] . "</li>";
echo "</ul>";
if ($consistency['missing_patients'] == 0) {
    echo "<p style='color: #28a745; font-weight: bold;'>✅ جميع البيانات متطابقة!</p>";
} else {
    echo "<p style='color: #dc3545; font-weight: bold;'>❌ يحتاج مزامنة!</p>";
}
echo "</div>";

echo "</div>";

// Action buttons
echo "<div style='background: #fff3cd; padding: 25px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>🔧 إجراءات الإصلاح</h3>";
echo "<div style='display: flex; gap: 15px; flex-wrap: wrap;'>";

echo "<form method='post' style='display: inline;'>";
echo "<button type='submit' name='sync_all_data' style='background: #ffc107; color: #212529; padding: 15px 25px; border: none; border-radius: 8px; cursor: pointer; font-weight: bold;'>";
echo "🔄 مزامنة جميع البيانات";
echo "</button>";
echo "</form>";

echo "<form method='post' style='display: inline;'>";
echo "<button type='submit' name='create_test_appointments' style='background: #17a2b8; color: white; padding: 15px 25px; border: none; border-radius: 8px; cursor: pointer; font-weight: bold;'>";
echo "📅 إنشاء مواعيد تجريبية";
echo "</button>";
echo "</form>";

echo "</div>";
echo "</div>";

// Quick test links
echo "<div style='background: #e3f2fd; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>🔗 اختبار سريع للصفحات</h3>";
echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;'>";

$testLinks = [
    'سجل مواعيد المريض' => 'hms/appointment-history.php',
    'سجل مواعيد الطبيب' => 'hms/doctor/appointment-history.php',
    'سجل مواعيد الإدارة' => 'hms/admin/appointment-history.php',
    'حجز موعد جديد' => 'hms/book-appointment.php',
    'لوحة تحكم المريض' => 'hms/dashboard.php',
    'لوحة تحكم الطبيب' => 'hms/doctor/dashboard.php',
];

foreach ($testLinks as $name => $url) {
    $exists = file_exists(__DIR__ . '/' . $url);
    $color = $exists ? '#1976d2' : '#dc3545';
    $status = $exists ? '' : ' (مفقود)';
    
    echo "<div style='background: white; padding: 15px; border-radius: 8px; border: 1px solid #e0e0e0;'>";
    echo "<a href='$url' target='_blank' style='color: $color; font-weight: bold; text-decoration: none;'>$name$status</a>";
    echo "</div>";
}

echo "</div>";
echo "</div>";

mysqli_close($con);
?>
