<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/System/vendor/autoload.php';
require_once 'include/config.php';

// إنشاء مولد البيانات الوهمية باللغة العربية
$faker = Faker\Factory::create('ar_SA');

// تنظيف البيانات القديمة (اختياري)
$clearOldData = isset($_GET['clear']) && $_GET['clear'] === 'true';

if ($clearOldData) {
    echo "<h3>🗑️ تنظيف البيانات القديمة...</h3>";
    mysqli_query($con, "DELETE FROM tblmedicalhistory WHERE PatientID > 4");
    mysqli_query($con, "DELETE FROM appointment WHERE id > 8");
    mysqli_query($con, "DELETE FROM tblpatient WHERE ID > 4");
    mysqli_query($con, "DELETE FROM users WHERE id > 8");
    mysqli_query($con, "DELETE FROM doctors WHERE id > 10");
    echo "<p>✅ تم تنظيف البيانات القديمة</p><hr>";
}

// دالة لتوليد كلمة مرور مشفرة
function generateHashedPassword($password = '123456')
{
    return md5($password);
}

// دالة لتوليد تاريخ عشوائي في المستقبل القريب
function generateFutureDate($days = 30)
{
    return date('Y-m-d', strtotime('+' . rand(1, $days) . ' days'));
}

// دالة لتوليد وقت عشوائي للمواعيد
function generateAppointmentTime()
{
    $hours = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '14:00', '14:30', '15:00', '15:30', '16:00', '16:30'];
    return $hours[array_rand($hours)];
}

echo "<h2>🏥 مولد البيانات الوهمية لنظام إدارة المستشفى</h2>";
echo "<div style='font-family: Arial; direction: rtl; text-align: right;'>";

// التخصصات الطبية المتاحة (مع التركيز على الاحتياجات اليمنية)
$specializations = [
    'Orthopedics',
    'Internal Medicine',
    'Obstetrics and Gynecology',
    'Dermatology',
    'Pediatrics',
    'Radiology',
    'General Surgery',
    'Ophthalmology',
    'Anesthesia',
    'ENT',
    'Endocrinologists',
    'Cardiology',
    'Neurology',
    'Psychiatry',
    'Urology',
    'Infectious Diseases',
    'Emergency Medicine',
    'Family Medicine',
    'Tropical Medicine',
    'Public Health'
];

// الأسماء اليمنية للأطباء
$yemeniDoctorNames = [
    'د. أحمد محمد الحوثي',
    'د. فاطمة علي الزبيدي',
    'د. محمد حسن الشامي',
    'د. عائشة أحمد الأهدل',
    'د. علي محمود الحضرمي',
    'د. زينب سالم المخلافي',
    'د. عمر يوسف الصنعاني',
    'د. مريم خالد العدني',
    'د. حسام الدين الحميري',
    'د. نور الهدى الكندي',
    'د. سعد عبدالله الهاشمي',
    'د. هدى إبراهيم التعزي',
    'د. خالد أحمد الإرياني',
    'د. سارة محمد الأغبري',
    'د. يوسف علي الشرعبي',
    'د. ليلى عبدالله الوزير',
    'د. رامي سعيد الأسودي',
    'د. سلمى جمال الحداد',
    'د. طارق عبدالرحمن الشيباني',
    'د. هاني عبدالله الرصاص',
    'د. منى أحمد الأرحبي',
    'د. وليد محمد الحكيمي',
    'د. رنا علي الجرادي',
    'د. ماجد حسن الشعبي',
    'د. نادية يوسف الحبيشي',
    'د. عبدالرحمن سالم الأشول',
    'د. إيمان خالد الدبعي',
    'د. محمود أحمد الحوري'
];

// أسماء يمنية للمرضى
$yemeniPatientNames = [
    'أحمد محمد علي',
    'فاطمة حسن أحمد',
    'محمد علي حسن',
    'عائشة أحمد محمد',
    'علي حسن محمود',
    'زينب سالم أحمد',
    'عمر يوسف علي',
    'مريم خالد حسن',
    'حسام الدين محمد',
    'نور الهدى أحمد',
    'سعد عبدالله علي',
    'هدى إبراهيم حسن',
    'خالد أحمد محمد',
    'سارة محمد علي',
    'يوسف علي أحمد',
    'ليلى عبدالله حسن',
    'رامي سعيد محمد',
    'سلمى جمال علي',
    'طارق عبدالرحمن أحمد',
    'هاني عبدالله حسن',
    'منى أحمد محمد',
    'وليد محمد علي',
    'رنا علي حسن',
    'ماجد حسن أحمد',
    'نادية يوسف محمد',
    'عبدالرحمن سالم علي',
    'إيمان خالد حسن',
    'محمود أحمد محمد',
    'سمية علي أحمد',
    'عبدالله حسن محمد'
];

// المدن العربية
$arabicCities = [
    'صنعاء',
    'عدن',
    'المحويت',
    'تعز',
    'حضرموت',
    'عمران',
    'إب',
    'الحديدة',
    'البيضاء',
    'ذمار',
    'المكلا',
    'المخا',
    'المعلا',
    'ريمة',
    'شبوة',
    'لحج',
    'أبين',
    'المهرة',
    'الجوف',
    'صعدة',
    'الضالع',
    'حجة',
];

// الأعراض والتاريخ المرضي (شائعة في اليمن)
$medicalConditions = [
    'ضغط الدم المرتفع',
    'السكري',
    'الربو',
    'حساسية الطعام',
    'آلام المفاصل',
    'الصداع النصفي',
    'أمراض القلب',
    'حصوات الكلى',
    'التهاب المعدة',
    'الأنيميا',
    'التهاب الجيوب الأنفية',
    'آلام الظهر',
    'اضطرابات النوم',
    'القلق والتوتر',
    'الملاريا',
    'حمى الضنك',
    'التهاب الكبد الوبائي',
    'سوء التغذية',
    'نقص فيتامين د',
    'التهاب المسالك البولية',
    'الإسهال المزمن',
    'التهاب الأمعاء',
    'أمراض الجهاز التنفسي',
    'الحساسية الموسمية'
];

// الأدوية والوصفات (متوفرة في اليمن)
$medications = [
    'بندول 500 مجم',
    'أسبرين 100 مجم',
    'أموكسيسيلين 500 مجم',
    'إيبوبروفين 400 مجم',
    'أوميبرازول 20 مجم',
    'ميتفورمين 500 مجم',
    'أتورفاستاتين 20 مجم',
    'ليسينوبريل 10 مجم',
    'فيتامين د 1000 وحدة',
    'حديد 65 مجم',
    'كالسيوم 600 مجم',
    'فيتامين ب12',
    'كلوروكين 250 مجم',
    'دوكسيسايكلين 100 مجم',
    'سيبروفلوكساسين 500 مجم',
    'فلاجيل 500 مجم',
    'أوجمنتين 625 مجم',
    'فولتارين 50 مجم',
    'زنك 20 مجم',
    'فيتامين أ 5000 وحدة',
    'محلول الجفاف ORS',
    'كريم مضاد للفطريات',
    'مرهم مضاد حيوي',
    'شراب السعال',
    'نقط العين المضادة للالتهاب'
];

echo "<h3>📊 إحصائيات قاعدة البيانات الحالية:</h3>";

// عرض الإحصائيات الحالية
$stats = [];
$tables = ['doctors', 'users', 'tblpatient', 'appointment', 'tblmedicalhistory'];
foreach ($tables as $table) {
    $result = mysqli_query($con, "SELECT COUNT(*) as count FROM $table");
    $count = mysqli_fetch_assoc($result)['count'];
    $stats[$table] = $count;
    echo "<p>📋 $table: $count سجل</p>";
}

echo "<hr>";
echo "<h3>🔄 توليد البيانات الوهمية...</h3>";

// 1. إضافة أطباء جدد
echo "<h4>👨‍⚕️ إضافة أطباء جدد...</h4>";
$doctorsAdded = 0;
for ($i = 0; $i < 25; $i++) {
    $doctorName = $yemeniDoctorNames[array_rand($yemeniDoctorNames)];
    $specialization = $specializations[array_rand($specializations)];
    $address = $faker->address . ', ' . $arabicCities[array_rand($arabicCities)];
    $fees = rand(5000, 25000); // رسوم بالريال اليمني
    // أرقام هواتف يمنية (شبكات سبأفون، يمن موبايل، واي)
    $yemeniPrefixes = ['77', '73', '70', '71', '78'];
    $prefix = $yemeniPrefixes[array_rand($yemeniPrefixes)];
    $contactno = $prefix . rand(1000000, 9999999);
    $email = 'doctor' . ($i + 20) . '@yemen-hospital.ye';
    $password = generateHashedPassword();

    $stmt = mysqli_prepare($con, "INSERT INTO doctors (specilization, doctorName, address, docFees, contactno, docEmail, password) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'sssssss', $specialization, $doctorName, $address, $fees, $contactno, $email, $password);
        if (mysqli_stmt_execute($stmt)) {
            $doctorsAdded++;
            echo "<p>✅ تم إضافة الطبيب: $doctorName - $specialization</p>";
        }
        mysqli_stmt_close($stmt);
    }
}
echo "<p><strong>📈 تم إضافة $doctorsAdded طبيب جديد</strong></p>";

// 2. إضافة مستخدمين (مرضى) جدد
echo "<h4>👥 إضافة مرضى جدد...</h4>";
$usersAdded = 0;
for ($i = 0; $i < 30; $i++) {
    $fullName = $yemeniPatientNames[array_rand($yemeniPatientNames)];
    $address = $faker->address . ', ' . $arabicCities[array_rand($arabicCities)];
    $city = $arabicCities[array_rand($arabicCities)];
    $gender = rand(0, 1) ? 'ذكر' : 'أنثى';
    $email = 'patient' . ($i + 50) . '@yemen.ye';
    $password = generateHashedPassword();

    $stmt = mysqli_prepare($con, "INSERT INTO users (fullName, address, city, gender, email, password) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'ssssss', $fullName, $address, $city, $gender, $email, $password);
        if (mysqli_stmt_execute($stmt)) {
            $userId = mysqli_insert_id($con);
            $usersAdded++;

            // إضافة سجل مريض مقابل في tblpatient
            $age = rand(18, 80);
            // أرقام هواتف يمنية للمرضى
            $yemeniPrefixes = ['77', '73', '70', '71', '78'];
            $prefix = $yemeniPrefixes[array_rand($yemeniPrefixes)];
            $contactno = $prefix . rand(1000000, 9999999);
            $medHistory = $medicalConditions[array_rand($medicalConditions)];
            if (rand(0, 1)) {
                $medHistory .= ', ' . $medicalConditions[array_rand($medicalConditions)];
            }

            $stmtPatient = mysqli_prepare($con, "INSERT INTO tblpatient (Docid, PatientName, PatientContno, PatientEmail, PatientGender, PatientAdd, PatientAge, PatientMedhis) VALUES (0, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmtPatient) {
                mysqli_stmt_bind_param($stmtPatient, 'sssssss', $fullName, $contactno, $email, $gender, $address, $age, $medHistory);
                mysqli_stmt_execute($stmtPatient);
                mysqli_stmt_close($stmtPatient);
            }

            echo "<p>✅ تم إضافة المريض: $fullName - العمر: $age</p>";
        }
        mysqli_stmt_close($stmt);
    }
}
echo "<p><strong>📈 تم إضافة $usersAdded مريض جديد</strong></p>";

// 3. إضافة مواعيد
echo "<h4>📅 إضافة مواعيد جديدة...</h4>";
$appointmentsAdded = 0;

// جلب قائمة الأطباء والمرضى المتاحين
$doctors = [];
$result = mysqli_query($con, "SELECT id, specilization, docFees FROM doctors");
while ($row = mysqli_fetch_assoc($result)) {
    $doctors[] = $row;
}

$users = [];
$result = mysqli_query($con, "SELECT id FROM users");
while ($row = mysqli_fetch_assoc($result)) {
    $users[] = $row['id'];
}

for ($i = 0; $i < 30; $i++) {
    if (empty($doctors) || empty($users)) break;

    $doctor = $doctors[array_rand($doctors)];
    $userId = $users[array_rand($users)];
    $appointmentDate = generateFutureDate(60);
    $appointmentTime = generateAppointmentTime();
    $userStatus = rand(0, 1);
    $doctorStatus = rand(0, 1);

    $stmt = mysqli_prepare($con, "INSERT INTO appointment (doctorSpecialization, doctorId, userId, consultancyFees, appointmentDate, appointmentTime, userStatus, doctorStatus) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'siisssii', $doctor['specilization'], $doctor['id'], $userId, $doctor['docFees'], $appointmentDate, $appointmentTime, $userStatus, $doctorStatus);
        if (mysqli_stmt_execute($stmt)) {
            $appointmentsAdded++;
            $statusText = ($userStatus && $doctorStatus) ? 'مؤكد' : 'في الانتظار';
            echo "<p>✅ موعد جديد: $appointmentDate في $appointmentTime - الحالة: $statusText</p>";
        }
        mysqli_stmt_close($stmt);
    }
}
echo "<p><strong>📈 تم إضافة $appointmentsAdded موعد جديد</strong></p>";

// 4. إضافة سجلات طبية
echo "<h4>🩺 إضافة سجلات طبية...</h4>";
$medicalRecordsAdded = 0;

// جلب قائمة المرضى من tblpatient
$patients = [];
$result = mysqli_query($con, "SELECT ID FROM tblpatient");
while ($row = mysqli_fetch_assoc($result)) {
    $patients[] = $row['ID'];
}

for ($i = 0; $i < 50; $i++) {
    if (empty($patients)) break;

    $patientId = $patients[array_rand($patients)];
    $bloodPressure = rand(110, 140) . '/' . rand(70, 90);
    $bloodSugar = rand(80, 200);
    $weight = rand(50, 120);
    $temperature = rand(36, 39) . '.' . rand(0, 9);

    // توليد وصفة طبية
    $prescription = $medications[array_rand($medications)];
    if (rand(0, 1)) {
        $prescription .= "\n" . $medications[array_rand($medications)];
    }
    $prescription .= "\nملاحظات: " . $faker->sentence();

    $stmt = mysqli_prepare($con, "INSERT INTO tblmedicalhistory (PatientID, BloodPressure, BloodSugar, Weight, Temperature, MedicalPres) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'isssss', $patientId, $bloodPressure, $bloodSugar, $weight, $temperature, $prescription);
        if (mysqli_stmt_execute($stmt)) {
            $medicalRecordsAdded++;
            echo "<p>✅ سجل طبي جديد للمريض رقم: $patientId - ضغط الدم: $bloodPressure</p>";
        }
        mysqli_stmt_close($stmt);
    }
}
echo "<p><strong>📈 تم إضافة $medicalRecordsAdded سجل طبي جديد</strong></p>";

// 5. إضافة تخصصات جديدة إذا لم تكن موجودة
echo "<h4>🏥 التحقق من التخصصات الطبية...</h4>";
$specializationsAdded = 0;
foreach ($specializations as $spec) {
    $result = mysqli_query($con, "SELECT id FROM doctorspecilization WHERE specilization = '$spec'");
    if (mysqli_num_rows($result) == 0) {
        $stmt = mysqli_prepare($con, "INSERT INTO doctorspecilization (specilization) VALUES (?)");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $spec);
            if (mysqli_stmt_execute($stmt)) {
                $specializationsAdded++;
                echo "<p>✅ تم إضافة تخصص: $spec</p>";
            }
            mysqli_stmt_close($stmt);
        }
    }
}
echo "<p><strong>📈 تم إضافة $specializationsAdded تخصص جديد</strong></p>";

echo "<hr>";
echo "<h3>📊 الإحصائيات النهائية:</h3>";

// عرض الإحصائيات النهائية
foreach ($tables as $table) {
    $result = mysqli_query($con, "SELECT COUNT(*) as count FROM $table");
    $newCount = mysqli_fetch_assoc($result)['count'];
    $added = $newCount - $stats[$table];
    echo "<p>📋 $table: $newCount سجل (+$added جديد)</p>";
}

echo "<hr>";
echo "<h3>✅ تم الانتهاء من توليد البيانات الوهمية بنجاح!</h3>";
echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h4>📝 ملخص العملية:</h4>";
echo "<ul>";
echo "<li>✅ تم إضافة $doctorsAdded طبيب جديد</li>";
echo "<li>✅ تم إضافة $usersAdded مريض جديد</li>";
echo "<li>✅ تم إضافة $appointmentsAdded موعد جديد</li>";
echo "<li>✅ تم إضافة $medicalRecordsAdded سجل طبي جديد</li>";
echo "<li>✅ تم إضافة $specializationsAdded تخصص طبي جديد</li>";
echo "</ul>";
echo "</div>";

echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h4>🔐 معلومات تسجيل الدخول (البيانات اليمنية):</h4>";
echo "<p><strong>للأطباء:</strong> البريد الإلكتروني: doctor[20-44]@yemen-hospital.ye | كلمة المرور: 123456</p>";
echo "<p><strong>للمرضى:</strong> البريد الإلكتروني: patient[100-129]@yemen.ye | كلمة المرور: 123456</p>";
echo "<p><strong>للإدارة:</strong> اسم المستخدم: admin | كلمة المرور: 123456</p>";
echo "<p><strong>أرقام الهواتف:</strong> تبدأ بـ 77، 73، 70، 71، 78 (شبكات يمنية)</p>";
echo "<p><strong>الرسوم:</strong> بالريال اليمني (5,000 - 25,000 ريال)</p>";
echo "<p><strong>الأسماء:</strong> أسماء يمنية أصيلة مع الألقاب القبلية</p>";
echo "</div>";

echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h4>🗑️ تنظيف البيانات:</h4>";
echo "<p>لحذف البيانات الوهمية والبدء من جديد:</p>";
echo "<a href='?clear=true' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>حذف البيانات الوهمية</a>";
echo "</div>";

echo "<div style='background: #d1ecf1; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h4>🎯 الخطوات التالية:</h4>";
echo "<ul>";
echo "<li>🔍 تصفح قائمة الأطباء والتخصصات</li>";
echo "<li>📅 جرب حجز المواعيد</li>";
echo "<li>📋 استعرض السجلات الطبية</li>";
echo "<li>📊 تحقق من لوحة التحكم</li>";
echo "</ul>";
echo "</div>";

echo "</div>";
