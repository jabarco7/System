<?php
require_once 'hms/include/config.php';

echo "<h1>📋 ملخص صفحة الملف الطبي الجديدة</h1>";

// Check if the new medical profile page exists
$medicalProfileExists = file_exists(__DIR__ . '/hms/my-medical-profile.php');
$oldProfileExists = file_exists(__DIR__ . '/hms/edit-profile.php');

echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h2>📁 حالة الملفات</h2>";
echo "<ul>";
echo "<li><strong>الملف الطبي الجديد:</strong> " . ($medicalProfileExists ? '✅ موجود' : '❌ مفقود') . " <code>hms/my-medical-profile.php</code></li>";
echo "<li><strong>صفحة التعديل القديمة:</strong> " . ($oldProfileExists ? '✅ موجود' : '❌ مفقود') . " <code>hms/edit-profile.php</code></li>";
echo "</ul>";
echo "</div>";

echo "<div style='background: #e3f2fd; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h2>🎯 الميزات الجديدة في الملف الطبي</h2>";

echo "<h3>📊 لوحة معلومات شاملة:</h3>";
echo "<ul>";
echo "<li><strong>إحصائيات المريض:</strong> عدد المواعيد الإجمالي والنشط</li>";
echo "<li><strong>آخر زيارة:</strong> تاريخ آخر موعد طبي</li>";
echo "<li><strong>حالة الملف:</strong> مكتمل أم يحتاج تحديث</li>";
echo "<li><strong>صورة شخصية:</strong> أول حرف من الاسم كصورة رمزية</li>";
echo "</ul>";

echo "<h3>👤 عرض المعلومات الحالية:</h3>";
echo "<ul>";
echo "<li><strong>البيانات الأساسية:</strong> الاسم، البريد، الجنس، المدينة</li>";
echo "<li><strong>معلومات الاتصال:</strong> رقم الهاتف، العنوان</li>";
echo "<li><strong>البيانات الطبية:</strong> العمر، التاريخ المرضي</li>";
echo "<li><strong>تواريخ مهمة:</strong> تاريخ التسجيل وآخر تحديث</li>";
echo "</ul>";

echo "<h3>✏️ تحديث البيانات:</h3>";
echo "<ul>";
echo "<li><strong>نموذج تفاعلي:</strong> تعديل جميع البيانات في مكان واحد</li>";
echo "<li><strong>التحقق من البيانات:</strong> التأكد من صحة المدخلات</li>";
echo "<li><strong>مزامنة تلقائية:</strong> ربط بيانات المستخدم مع سجل المريض</li>";
echo "<li><strong>إنشاء تلقائي:</strong> إنشاء سجل مريض إذا لم يكن موجوداً</li>";
echo "</ul>";

echo "<h3>🎨 التصميم والواجهة:</h3>";
echo "<ul>";
echo "<li><strong>تصميم عصري:</strong> ألوان متدرجة وتأثيرات بصرية جميلة</li>";
echo "<li><strong>تجاوب كامل:</strong> يعمل على جميع أحجام الشاشات</li>";
echo "<li><strong>سهولة الاستخدام:</strong> واجهة بديهية ومنظمة</li>";
echo "<li><strong>رسائل واضحة:</strong> تأكيدات نجاح وتحذيرات خطأ</li>";
echo "</ul>";
echo "</div>";

echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h2>🔧 التحديثات المطبقة</h2>";

echo "<h3>📝 الملفات المحدثة:</h3>";
echo "<ul>";
echo "<li><strong>dashboard.php:</strong> تم تحديث الرابط من <code>edit-profile.php</code> إلى <code>my-medical-profile.php</code></li>";
echo "<li><strong>view-medhistory.php:</strong> تم تحديث رابط 'ملفي الطبي' في القائمة الجانبية</li>";
echo "</ul>";

echo "<h3>🆕 الملفات الجديدة:</h3>";
echo "<ul>";
echo "<li><strong>my-medical-profile.php:</strong> صفحة الملف الطبي الشاملة الجديدة</li>";
echo "<li><strong>test_medical_profile.php:</strong> صفحة اختبار للتأكد من عمل الصفحة</li>";
echo "</ul>";
echo "</div>";

// Test database connectivity and show sample data
echo "<div style='background: #fff3cd; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h2>🗄️ عينة من البيانات</h2>";

// Show sample users
echo "<h3>👥 المستخدمين المتاحين:</h3>";
$userQuery = mysqli_query($con, "SELECT id, fullName, email, gender, city FROM users LIMIT 5");
if ($userQuery) {
    echo "<table style='width: 100%; border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background: #f8f9fa;'>";
    echo "<th style='padding: 8px; border: 1px solid #ddd;'>ID</th>";
    echo "<th style='padding: 8px; border: 1px solid #ddd;'>الاسم</th>";
    echo "<th style='padding: 8px; border: 1px solid #ddd;'>البريد</th>";
    echo "<th style='padding: 8px; border: 1px solid #ddd;'>الجنس</th>";
    echo "<th style='padding: 8px; border: 1px solid #ddd;'>المدينة</th>";
    echo "</tr>";
    
    while ($row = mysqli_fetch_assoc($userQuery)) {
        echo "<tr>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $row['id'] . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($row['fullName']) . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($row['email']) . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($row['gender'] ?? 'غير محدد') . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($row['city'] ?? 'غير محدد') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: #dc3545;'>خطأ في الاتصال بقاعدة البيانات: " . mysqli_error($con) . "</p>";
}

// Show sample patients
echo "<h3>🏥 سجلات المرضى:</h3>";
$patientQuery = mysqli_query($con, "SELECT PatientName, PatientEmail, PatientContno, PatientGender, PatientAge FROM tblpatient LIMIT 5");
if ($patientQuery) {
    echo "<table style='width: 100%; border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background: #f8f9fa;'>";
    echo "<th style='padding: 8px; border: 1px solid #ddd;'>اسم المريض</th>";
    echo "<th style='padding: 8px; border: 1px solid #ddd;'>البريد</th>";
    echo "<th style='padding: 8px; border: 1px solid #ddd;'>الهاتف</th>";
    echo "<th style='padding: 8px; border: 1px solid #ddd;'>الجنس</th>";
    echo "<th style='padding: 8px; border: 1px solid #ddd;'>العمر</th>";
    echo "</tr>";
    
    while ($row = mysqli_fetch_assoc($patientQuery)) {
        echo "<tr>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($row['PatientName']) . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($row['PatientEmail']) . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($row['PatientContno']) . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($row['PatientGender']) . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($row['PatientAge']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: #dc3545;'>خطأ في جلب بيانات المرضى: " . mysqli_error($con) . "</p>";
}
echo "</div>";

// Quick access links
echo "<div style='background: #e3f2fd; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h2>🔗 روابط سريعة للاختبار</h2>";

echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;'>";

$testLinks = [
    'الملف الطبي الجديد' => ['url' => 'hms/my-medical-profile.php', 'icon' => '🏥', 'color' => '#28a745'],
    'صفحة الاختبار' => ['url' => 'test_medical_profile.php', 'icon' => '🧪', 'color' => '#17a2b8'],
    'لوحة تحكم المريض' => ['url' => 'hms/dashboard.php', 'icon' => '🏠', 'color' => '#007bff'],
    'سجل المواعيد' => ['url' => 'hms/appointment-history.php', 'icon' => '📅', 'color' => '#6f42c1'],
    'تسجيل الدخول' => ['url' => 'hms/user-login.php', 'icon' => '🔐', 'color' => '#ffc107'],
    'الصفحة القديمة' => ['url' => 'hms/edit-profile.php', 'icon' => '📝', 'color' => '#6c757d'],
];

foreach ($testLinks as $name => $info) {
    $exists = file_exists(__DIR__ . '/' . $info['url']);
    $status = $exists ? '' : ' (مفقود)';
    $opacity = $exists ? '1' : '0.5';
    
    echo "<div style='background: white; padding: 15px; border-radius: 8px; border-left: 4px solid " . $info['color'] . "; opacity: $opacity;'>";
    echo "<a href='" . $info['url'] . "' target='_blank' style='color: " . $info['color'] . "; font-weight: bold; text-decoration: none; display: block;'>";
    echo $info['icon'] . " " . $name . $status;
    echo "</a>";
    echo "</div>";
}

echo "</div>";
echo "</div>";

// Instructions
echo "<div style='background: #d1ecf1; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h2>📋 تعليمات الاستخدام</h2>";

echo "<h3>🚀 للبدء:</h3>";
echo "<ol>";
echo "<li><strong>افتح صفحة الاختبار:</strong> <a href='test_medical_profile.php' target='_blank' style='color: #0c5460; font-weight: bold;'>test_medical_profile.php</a></li>";
echo "<li><strong>أنشئ جلسة تجريبية</strong> أو سجل الدخول كمريض</li>";
echo "<li><strong>افتح الملف الطبي:</strong> <a href='hms/my-medical-profile.php' target='_blank' style='color: #0c5460; font-weight: bold;'>my-medical-profile.php</a></li>";
echo "<li><strong>جرب تحديث البيانات</strong> واختبر جميع الميزات</li>";
echo "</ol>";

echo "<h3>🔧 للمطورين:</h3>";
echo "<ul>";
echo "<li><strong>الملف الرئيسي:</strong> <code>hms/my-medical-profile.php</code></li>";
echo "<li><strong>قاعدة البيانات:</strong> جدولي <code>users</code> و <code>tblpatient</code></li>";
echo "<li><strong>الأمان:</strong> CSRF tokens وتنظيف المدخلات</li>";
echo "<li><strong>التصميم:</strong> CSS مدمج مع تصميم متجاوب</li>";
echo "</ul>";

echo "<h3>⚠️ ملاحظات مهمة:</h3>";
echo "<ul>";
echo "<li><strong>الجلسة مطلوبة:</strong> يجب تسجيل الدخول لاستخدام الصفحة</li>";
echo "<li><strong>المزامنة التلقائية:</strong> يتم ربط بيانات المستخدم مع سجل المريض</li>";
echo "<li><strong>الإنشاء التلقائي:</strong> إذا لم يوجد سجل مريض، يتم إنشاؤه تلقائياً</li>";
echo "<li><strong>التحقق من البيانات:</strong> الحقول المطلوبة محددة بوضوح</li>";
echo "</ul>";
echo "</div>";

mysqli_close($con);
?>
