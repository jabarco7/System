<?php
require_once 'hms/include/config.php';

echo "<h2>🔍 التحقق من تدفق المواعيد</h2>";

// 1. Check if appointments are being created correctly
echo "<h3>1️⃣ فحص إنشاء المواعيد</h3>";

// Get latest appointments
$latestAppts = mysqli_query($con, "
    SELECT 
        a.id,
        a.doctorId,
        a.userId,
        u.fullName AS patientName,
        d.doctorName,
        a.appointmentDate,
        a.appointmentTime,
        a.postingDate,
        a.userStatus,
        a.doctorStatus
    FROM appointment a
    JOIN users u ON u.id = a.userId
    JOIN doctors d ON d.id = a.doctorId
    ORDER BY a.id DESC
    LIMIT 10
");

echo "<table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>";
echo "<tr style='background: #007bff; color: white;'>";
echo "<th style='padding: 10px; border: 1px solid #ddd;'>رقم الموعد</th>";
echo "<th style='padding: 10px; border: 1px solid #ddd;'>الطبيب</th>";
echo "<th style='padding: 10px; border: 1px solid #ddd;'>المريض</th>";
echo "<th style='padding: 10px; border: 1px solid #ddd;'>التاريخ</th>";
echo "<th style='padding: 10px; border: 1px solid #ddd;'>الوقت</th>";
echo "<th style='padding: 10px; border: 1px solid #ddd;'>تاريخ الإنشاء</th>";
echo "<th style='padding: 10px; border: 1px solid #ddd;'>الحالة</th>";
echo "</tr>";

$appointmentCount = 0;
while ($row = mysqli_fetch_assoc($latestAppts)) {
    $appointmentCount++;
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

echo "<p><strong>إجمالي المواعيد الأخيرة: $appointmentCount</strong></p>";

// 2. Test appointment-history.php query
echo "<h3>2️⃣ اختبار استعلام سجل المواعيد</h3>";

// Get a doctor ID for testing
$doctorResult = mysqli_query($con, "SELECT id, doctorName FROM doctors LIMIT 1");
$testDoctor = mysqli_fetch_assoc($doctorResult);

if ($testDoctor) {
    $testDoctorId = $testDoctor['id'];
    echo "<p><strong>اختبار للطبيب:</strong> " . htmlspecialchars($testDoctor['doctorName']) . " (ID: $testDoctorId)</p>";
    
    // Run the same query as appointment-history.php
    $testQuery = mysqli_query($con, "
        SELECT
            a.id,
            u.fullName                     AS patientName,
            a.consultancyFees,
            a.appointmentDate,
            a.appointmentTime,
            a.postingDate,
            a.userStatus,
            a.doctorStatus,
            COALESCE(p.PatientContno, p2.PatientContno, 'غير محدد') AS patientNumber
        FROM appointment a
        JOIN users u
          ON u.id = a.userId
        LEFT JOIN tblpatient p
          ON p.PatientEmail = u.email AND p.Docid = a.doctorId
        LEFT JOIN tblpatient p2
          ON p2.PatientEmail = u.email AND p2.Docid != a.doctorId
        WHERE a.doctorId = $testDoctorId
        ORDER BY a.appointmentDate DESC, STR_TO_DATE(a.appointmentTime, '%H:%i') DESC, a.id DESC
        LIMIT 5
    ");
    
    echo "<table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>";
    echo "<tr style='background: #28a745; color: white;'>";
    echo "<th style='padding: 10px; border: 1px solid #ddd;'>رقم الموعد</th>";
    echo "<th style='padding: 10px; border: 1px solid #ddd;'>اسم المريض</th>";
    echo "<th style='padding: 10px; border: 1px solid #ddd;'>رقم الهاتف</th>";
    echo "<th style='padding: 10px; border: 1px solid #ddd;'>التاريخ</th>";
    echo "<th style='padding: 10px; border: 1px solid #ddd;'>الوقت</th>";
    echo "<th style='padding: 10px; border: 1px solid #ddd;'>الحالة</th>";
    echo "</tr>";
    
    $queryResultCount = 0;
    while ($row = mysqli_fetch_assoc($testQuery)) {
        $queryResultCount++;
        $status = ($row['userStatus'] && $row['doctorStatus']) ? '✅ نشط' : '❌ ملغي';
        
        echo "<tr>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $row['id'] . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($row['patientName']) . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($row['patientNumber']) . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $row['appointmentDate'] . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $row['appointmentTime'] . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $status . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p><strong>نتائج استعلام سجل المواعيد: $queryResultCount موعد</strong></p>";
}

// 3. Create a test appointment and verify it appears
echo "<h3>3️⃣ إنشاء موعد تجريبي والتحقق من ظهوره</h3>";

if (isset($_POST['create_test'])) {
    $testDoctorId = (int)$_POST['test_doctor_id'];
    $testUserId = (int)$_POST['test_user_id'];
    $testDate = $_POST['test_date'];
    $testTime = $_POST['test_time'];
    
    // Get doctor info
    $docStmt = mysqli_prepare($con, "SELECT specilization, docFees FROM doctors WHERE id = ?");
    mysqli_stmt_bind_param($docStmt, 'i', $testDoctorId);
    mysqli_stmt_execute($docStmt);
    $docResult = mysqli_stmt_get_result($docStmt);
    $docInfo = mysqli_fetch_assoc($docResult);
    mysqli_stmt_close($docStmt);
    
    if ($docInfo) {
        // Insert test appointment
        $insertStmt = mysqli_prepare($con, "
            INSERT INTO appointment 
            (doctorSpecialization, doctorId, userId, consultancyFees, appointmentDate, appointmentTime, userStatus, doctorStatus, postingDate)
            VALUES (?, ?, ?, ?, ?, ?, 1, 1, NOW())
        ");
        
        mysqli_stmt_bind_param($insertStmt, 'siisss', 
            $docInfo['specilization'], 
            $testDoctorId, 
            $testUserId, 
            $docInfo['docFees'], 
            $testDate, 
            $testTime
        );
        
        if (mysqli_stmt_execute($insertStmt)) {
            $newTestId = mysqli_insert_id($con);
            echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
            echo "<h4>✅ تم إنشاء موعد تجريبي جديد!</h4>";
            echo "<p><strong>رقم الموعد:</strong> $newTestId</p>";
            echo "<p><strong>الطبيب ID:</strong> $testDoctorId</p>";
            echo "<p><strong>المريض ID:</strong> $testUserId</p>";
            echo "<p><strong>التاريخ:</strong> $testDate</p>";
            echo "<p><strong>الوقت:</strong> $testTime</p>";
            echo "<p><a href='hms/doctor/appointment-history.php' target='_blank' style='background: #007bff; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px;'>🔄 تحقق من سجل المواعيد الآن</a></p>";
            echo "</div>";
        } else {
            echo "<div style='background: #f8d7da; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
            echo "<h4>❌ فشل في إنشاء الموعد التجريبي</h4>";
            echo "<p>خطأ: " . mysqli_error($con) . "</p>";
            echo "</div>";
        }
        mysqli_stmt_close($insertStmt);
    }
}

// Form to create test appointment
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
    echo "<form method='post' style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h4>📅 إنشاء موعد تجريبي</h4>";
    
    echo "<div style='margin-bottom: 15px;'>";
    echo "<label><strong>الطبيب:</strong></label>";
    echo "<select name='test_doctor_id' required style='width: 100%; padding: 8px; margin-top: 5px;'>";
    echo "<option value=''>-- اختر طبيب --</option>";
    foreach ($doctors as $doc) {
        echo "<option value='" . $doc['id'] . "'>" . htmlspecialchars($doc['doctorName'] . ' - ' . $doc['specilization']) . "</option>";
    }
    echo "</select>";
    echo "</div>";
    
    echo "<div style='margin-bottom: 15px;'>";
    echo "<label><strong>المريض:</strong></label>";
    echo "<select name='test_user_id' required style='width: 100%; padding: 8px; margin-top: 5px;'>";
    echo "<option value=''>-- اختر مريض --</option>";
    foreach ($users as $user) {
        echo "<option value='" . $user['id'] . "'>" . htmlspecialchars($user['fullName'] . ' (' . $user['email'] . ')') . "</option>";
    }
    echo "</select>";
    echo "</div>";
    
    echo "<div style='margin-bottom: 15px;'>";
    echo "<label><strong>التاريخ:</strong></label>";
    echo "<input type='date' name='test_date' value='" . date('Y-m-d', strtotime('+1 day')) . "' required style='width: 100%; padding: 8px; margin-top: 5px;'>";
    echo "</div>";
    
    echo "<div style='margin-bottom: 15px;'>";
    echo "<label><strong>الوقت:</strong></label>";
    echo "<select name='test_time' required style='width: 100%; padding: 8px; margin-top: 5px;'>";
    echo "<option value='09:00:00'>09:00 صباحاً</option>";
    echo "<option value='10:00:00'>10:00 صباحاً</option>";
    echo "<option value='11:00:00'>11:00 صباحاً</option>";
    echo "<option value='14:00:00'>02:00 مساءً</option>";
    echo "<option value='15:00:00'>03:00 مساءً</option>";
    echo "</select>";
    echo "</div>";
    
    echo "<button type='submit' name='create_test' style='background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>";
    echo "📅 إنشاء موعد تجريبي";
    echo "</button>";
    echo "</form>";
}

// 4. Direct links for testing
echo "<h3>4️⃣ روابط الاختبار المباشر</h3>";
echo "<div style='background: #e3f2fd; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h4>🔗 اختبر الصفحات:</h4>";
echo "<ul>";
echo "<li><a href='hms/doctor/appointment-history.php' target='_blank' style='color: #1976d2; font-weight: bold;'>📋 سجل مواعيد الطبيب (appointment-history.php)</a></li>";
echo "<li><a href='hms/dashboard.php' target='_blank' style='color: #1976d2;'>🏠 لوحة تحكم المريض</a></li>";
echo "<li><a href='hms/book-appointment.php' target='_blank' style='color: #1976d2;'>📅 حجز موعد من المريض</a></li>";
echo "<li><a href='hms/appointment-history.php' target='_blank' style='color: #1976d2;'>📋 سجل مواعيد المريض</a></li>";
echo "</ul>";
echo "</div>";

// 5. Summary and instructions
echo "<h3>5️⃣ تعليمات الاختبار</h3>";
echo "<div style='background: #fff3cd; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h4>📋 خطوات التحقق:</h4>";
echo "<ol>";
echo "<li><strong>احجز موعد تجريبي</strong> باستخدام النموذج أعلاه</li>";
echo "<li><strong>انتقل إلى سجل المواعيد</strong> للطبيب المختار</li>";
echo "<li><strong>تأكد من ظهور الموعد الجديد</strong> في أعلى القائمة</li>";
echo "<li><strong>تحقق من عرض رقم الهاتف</strong> للمريض</li>";
echo "<li><strong>اختبر الفلترة</strong> بالحالة والتاريخ</li>";
echo "</ol>";
echo "</div>";

echo "<div style='background: #d1ecf1; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h4>ℹ️ ملاحظات مهمة:</h4>";
echo "<ul>";
echo "<li><strong>التحديث التلقائي:</strong> تم إضافة تحديث تلقائي كل 30 ثانية لصفحة سجل المواعيد</li>";
echo "<li><strong>الإشعارات:</strong> ستظهر إشعارات عند وجود مواعيد جديدة</li>";
echo "<li><strong>أرقام الهواتف:</strong> تم إصلاح عرض أرقام الهواتف في جميع الصفحات</li>";
echo "<li><strong>الترتيب:</strong> المواعيد مرتبة حسب التاريخ والوقت (الأحدث أولاً)</li>";
echo "</ul>";
echo "</div>";

mysqli_close($con);
?>
