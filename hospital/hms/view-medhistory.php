<?php
session_start();
include('include/config.php');
include('include/checklogin.php');
check_login();

// اسم المريض (إن كنت تحفظه بالجلسة)
$patientName = isset($_SESSION['patient_name']) && $_SESSION['patient_name'] !== ''
    ? $_SESSION['patient_name'] : 'المريض';

// نفترض أن PatientID في tblmedicalhistory يساوي مُعرف المستخدم الحالي
$pid = (int)($_SESSION['id'] ?? 0);

// جلب الفحوصات
$stmt = mysqli_prepare($con, "
    SELECT ID, BloodPressure, Weight, BloodSugar, Temperature, MedicalPres, CreationDate
    FROM tblmedicalhistory
    WHERE PatientID = ?
    ORDER BY ID DESC
");
mysqli_stmt_bind_param($stmt, 'i', $pid);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$rows = [];
while ($r = mysqli_fetch_assoc($res)) {
    $rows[] = $r;
}
mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8" />
    <title>نتائج الفحوصات</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #3498db;
            --secondary: #1a2530;
        }

        body {
            font-family: 'Tajawal', sans-serif;
            background: #f0f5f9;
            color: #333;
            padding-top: 24px
        }

        .layout {
            display: flex;
            gap: 20px
        }

        .sidebar {
            width: 260px;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, .05);
            padding: 20px;
            height: 100%
        }

        .patient-card {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px;
            border: 1px dashed #e5eef6;
            border-radius: 12px;
            background: #fbfdff;
            margin-bottom: 16px
        }

        .patient-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(90deg, var(--primary), #4aa8e0);
            color: #fff;
            font-weight: 700
        }

        .menu {
            list-style: none;
            padding: 0;
            margin: 0
        }

        .menu a {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: #22313f;
            padding: 10px 12px;
            border-radius: 10px
        }

        .menu a:hover,
        .menu a.active {
            background: #eef7ff;
            color: #0d6efd
        }

        .badge-soft {
            background: #eef7ff;
            color: #0d6efd;
            border-radius: 30px;
            padding: 2px 10px;
            font-size: .8rem
        }

        .main {
            flex: 1
        }

        .app-header {
            background: linear-gradient(90deg, var(--primary), #4aa8e0);
            color: #fff;
            padding: 22px 26px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, .1)
        }

        .app-header h1 {
            margin: 0;
            font-weight: 700;
            font-size: 1.2rem
        }

        .card-clean {
            background: #fff;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, .06)
        }

        .table thead th {
            background: #f5f8fc
        }

        @media(max-width:992px) {
            .sidebar {
                display: none
            }
        }
    </style>
</head>

<body>
    <div class="container-xxl">
        <div class="layout">
            <!-- السايدبار (على نفس ستايل الداشبورد) -->
            <aside class="sidebar">
                <div class="patient-card">
                    <div class="patient-avatar"><?php echo mb_substr($patientName, 0, 1, 'UTF-8'); ?></div>
                    <div>
                        <div style="font-weight:700"><?php echo htmlspecialchars($patientName); ?></div>
                        <div class="text-muted" style="font-size:.9rem">مرحباً بك</div>
                    </div>
                </div>
                <ul class="menu">
                    <li><a href="dashboard.php"><i class="fa-solid fa-house"></i><span>الصفحة الرئيسية</span></a></li>
                    <li><a href="book-appointment.php"><i class="fa-regular fa-calendar-plus"></i><span>حجز موعد</span></a></li>
                    <li><a href="appointment-history.php"><i class="fa-regular fa-calendar-check"></i><span>حجوزاتي</span></a></li>
                    <li><a href="edit-profile.php"><i class="fa-regular fa-id-card"></i><span>ملفي الطبي</span></a></li>
                    <li><a href="view-medhistory.php" class="active"><i class="fa-solid fa-vial"></i><span>نتائج الفحوصات</span><span class="ms-auto badge-soft"><?php echo count($rows); ?></span></a></li>
                    <li><a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i><span>تسجيل الخروج</span></a></li>
                </ul>
            </aside>

            <main class="main">
                <div class="app-header">
                    <h1>نتائج الفحوصات</h1>
                </div>

                <div class="card-clean">
                    <?php if (count($rows) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle">
                                <thead>
                                    <tr class="text-center">
                                        <th>#</th>
                                        <th>ضغط الدم</th>
                                        <th>الوزن</th>
                                        <th>سكر الدم</th>
                                        <th>درجة الحرارة</th>
                                        <th>الوصفــة / الملاحظات</th>
                                        <th>تاريخ الفحص</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $i = 1;
                                    foreach ($rows as $row): ?>
                                        <tr>
                                            <td class="text-center"><?php echo $i++; ?></td>
                                            <td><?php echo htmlspecialchars($row['BloodPressure']); ?></td>
                                            <td><?php echo htmlspecialchars($row['Weight']); ?></td>
                                            <td><?php echo htmlspecialchars($row['BloodSugar']); ?></td>
                                            <td><?php echo htmlspecialchars($row['Temperature']); ?></td>
                                            <td><?php echo nl2br(htmlspecialchars($row['MedicalPres'])); ?></td>
                                            <td><?php echo htmlspecialchars($row['CreationDate']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <img src="https://cdn-icons-png.flaticon.com/512/4076/4076500.png" alt="" style="width:80px;opacity:.7">
                            <h5 class="mt-3">لا يوجد لديك فحوصات حتى الآن</h5>
                            <p class="text-muted m-0">عند إضافة فحوصات من قِبل الطبيب ستظهر هنا تلقائيًا.</p>


                        </div>
                        <!-- لا نعرض أي رسالة إذا ما في فحوصات؛ فقط تظل البطاقة فارغة -->
                        <!-- بإمكانك لاحقًا إضافة ودجت تحفيزية هنا بدون تنبيه -->
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>