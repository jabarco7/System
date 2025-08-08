<?php
session_start();
error_reporting(0);
include('include/config.php');

if (strlen($_SESSION['id'] == 0)) {
    header('location:logout.php');
    exit;
}

// اسم المريض القادم من صفحة التسجيل
$patientName = isset($_SESSION['patient_name']) && $_SESSION['patient_name'] !== ''
    ? $_SESSION['patient_name']
    : 'المريض';

/**
 * تحكم بسيط: هذه الصفحة للمريض فقط، لذلك نعطّل إدارة التخصصات هنا
 * إن أردت إظهارها للمسؤول فقط:
 * $canManageSpecs = (($_SESSION['role'] ?? '') === 'admin');
 */
$canManageSpecs = false;

// (اختياري) لو أردت منع أي محاولات وصول لعمليات الإضافة/الحذف من العنوان:
if (!$canManageSpecs) {
    unset($_POST['submit'], $_GET['del'], $_GET['id']);
}

// معالجة إضافة تخصص (لن تعمل لأن $canManageSpecs=false)
if ($canManageSpecs && isset($_POST['submit'])) {
    $doctorspecilization = trim($_POST['doctorspecilization']);
    if ($doctorspecilization !== '') {
        mysqli_query($con, "INSERT INTO doctorSpecilization(specilization) VALUES ('" . mysqli_real_escape_string($con, $doctorspecilization) . "')");
        $_SESSION['msg'] = "تمت إضافة تخصص الطبيب بنجاح!";
    }
}

// حذف تخصص (لن يعمل لأن $canManageSpecs=false)
if ($canManageSpecs && isset($_GET['del']) && isset($_GET['id'])) {
    $sid = intval($_GET['id']);
    mysqli_query($con, "DELETE FROM doctorSpecilization WHERE id = '$sid'");
    $_SESSION['msg'] = "تم حذف البيانات!";
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <title>تسجيل دخول مريض</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root{
            --primary:#3498db;--primary-dark:#2c3e50;--secondary:#1a2530;
            --success:#27ae60;--info:#2980b9;--warning:#f39c12;--danger:#e74c3c;
            --light:#f8f9fa;--dark:#343a40;--gray:#6c757d
        }
        body{font-family:'Tajawal',sans-serif;background:#f0f5f9;color:#333;padding-top:60px}
        .layout{display:flex;gap:20px}
        /* Sidebar */
        .sidebar{
            width:260px;background:#fff;border-radius:15px;box-shadow:0 5px 15px rgba(0,0,0,.05);
            padding:20px;height:100%
        }
        .patient-card{
            display:flex;align-items:center;gap:12px;padding:14px;border:1px dashed #e5eef6;border-radius:12px;
            background:#fbfdff;margin-bottom:16px
        }
        .patient-avatar{
            width:44px;height:44px;border-radius:50%;display:flex;align-items:center;justify-content:center;
            background:linear-gradient(90deg,var(--primary),#4aa8e0);color:#fff;font-weight:700
        }
        .menu{list-style:none;padding:0;margin:0}
        .menu li{margin-bottom:8px}
        .menu a{
            display:flex;align-items:center;gap:10px;text-decoration:none;color:#22313f;padding:10px 12px;border-radius:10px
        }
        .menu a:hover,.menu a.active{background:#eef7ff;color:#0d6efd}
        .badge-soft{background:#eef7ff;color:#0d6efd;border-radius:30px;padding:2px 10px;font-size:.8rem}
        /* Header */
        .app-header{
            background:linear-gradient(90deg,var(--primary),#4aa8e0);color:#fff;padding:22px 26px;border-radius:10px;margin-bottom:20px;
            box-shadow:0 5px 15px rgba(0,0,0,.1)
        }
        .app-header h1{margin:0;font-weight:700;font-size:1.4rem}
        .main{flex:1}
        .card-clean{background:#fff;border-radius:15px;padding:22px;box-shadow:0 5px 15px rgba(0,0,0,.05)}
        /* بطاقات اختيار نوع المرض */
        .disease-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:14px}
        .disease-card{
            background:#fff;border:1px solid #e9f1f7;border-radius:14px;padding:16px;text-align:center;cursor:pointer;
            transition:.2s;box-shadow:0 2px 8px rgba(0,0,0,.02)
        }
        .disease-card:hover{transform:translateY(-4px);box-shadow:0 8px 18px rgba(0,0,0,.08)}
        .disease-card i{font-size:1.8rem;color:var(--primary);margin-bottom:8px}
        /* Form & Table (تخصصات الأطباء) */
        .modern-form{background:#fff;border-radius:15px;padding:22px;box-shadow:0 5px 15px rgba(0,0,0,.05)}
        .form-label{font-weight:600;margin-bottom:6px;color:var(--secondary)}
        .form-control{border:2px solid #e1e5eb;border-radius:10px;padding:12px 15px;height:auto}
        .form-control:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(52,152,219,.2)}
        .btn-primary{background:linear-gradient(90deg,var(--primary),#4aa8e0);border:none;padding:10px 20px;border-radius:30px}
        .table-container{background:#fff;border-radius:15px;overflow:hidden;box-shadow:0 5px 15px rgba(0,0,0,.05);margin-top:20px}
        .table th{background:var(--primary);color:#fff}
        .btn-action{width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center}
        .btn-edit{background:rgba(39,174,96,.15);color:#27ae60}
        .btn-edit:hover{background:#27ae60;color:#fff}
        .btn-delete{background:rgba(231,76,60,.15);color:#e74c3c}
        .btn-delete:hover{background:#e74c3c;color:#fff}
        @media (max-width: 992px){.sidebar{display:none}}
    </style>
</head>
<body>
    <div class="container-xxl">
        <div class="layout">
            <!-- Sidebar -->
            <aside class="sidebar">
                <div class="patient-card">
                    <div class="patient-avatar"><?php echo mb_substr($patientName,0,1,'UTF-8'); ?></div>
                    <div>
                        <div style="font-weight:700"><?php echo htmlspecialchars($patientName); ?></div>
                        <div class="text-muted" style="font-size:.9rem">مرحباً بك</div>
                    </div>
                </div>
                <ul class="menu">
                    <li><a href="#choose-disease" class="active"><i class="fa-solid fa-notes-medical"></i><span>اختيار نوع المرض</span><span class="ms-auto badge-soft">الآن</span></a></li>
                    <!-- حذفت رابط "تخصصات الأطباء" من القائمة للمريض -->
                    <li><a href="my-appointments.php"><i class="fa-regular fa-calendar-check"></i><span>حجوزاتي</span></a></li>
                    <li><a href="profile.php"><i class="fa-regular fa-id-badge"></i><span>ملفي الطبي</span></a></li>
                    <li><a href="labs-results.php"><i class="fa-solid fa-vial"></i><span>نتائج الفحوصات</span></a></li>
                    <li><a href="payments.php"><i class="fa-regular fa-credit-card"></i><span>المدفوعات</span></a></li>
                    <li><a href="support.php"><i class="fa-regular fa-life-ring"></i><span>الدعم</span></a></li>
                    <li><a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i><span>تسجيل الخروج</span></a></li>
                </ul>
            </aside>

            <main class="main">
                <!-- Header -->
                <div class="app-header">
                    <h1>تسجيل دخول مريض</h1>
                </div>

                <!-- اختيار نوع المرض -->
                <section id="choose-disease" class="card-clean mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="m-0" style="font-weight:700;color:#1a2530">اختر نوع المرض الذي تعاني منه</h5>
                        <small class="text-muted">سيساعدنا هذا على توجيهك للتخصص المناسب</small>
                    </div>
                    <div class="disease-grid">
                      <a class="disease-card" href="doctors.php?spec=أمراض القلب"><i class="fa-solid fa-heart-pulse"></i><div>أمراض القلب</div></a>
<a class="disease-card" href="doctors.php?spec=الباطنية"><i class="fa-solid fa-staff-snake"></i><div>الباطنية</div></a>
<a class="disease-card" href="doctors.php?spec=العظام"><i class="fa-solid fa-bone"></i><div>العظام</div></a>
<a class="disease-card" href="doctors.php?spec=الأسنان"><i class="fa-solid fa-tooth"></i><div>الأسنان</div></a>
<a class="disease-card" href="doctors.php?spec=العيون"><i class="fa-regular fa-eye"></i><div>العيون</div></a>
<a class="disease-card" href="doctors.php?spec=الجلدية"><i class="fa-regular fa-face-smile"></i><div>الجلدية</div></a>
<a class="disease-card" href="doctors.php?spec=أطفال"><i class="fa-solid fa-baby"></i><div>أطفال</div></a>
<a class="disease-card" href="doctors.php?spec=نساء وولادة"><i class="fa-solid fa-person-pregnant"></i><div>نساء وولادة</div></a>


                    </div>
                </section>

                <!-- رسائل النظام -->
                <?php if (isset($_SESSION['msg'])): ?>
                    <div class="alert alert-success rounded-3">
                        <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($_SESSION['msg']); ?>
                    </div>
                    <?php unset($_SESSION['msg']); ?>
                <?php endif; ?>

                <!-- تخصصات الأطباء (مخفي للمريض) -->
                <?php if ($canManageSpecs): ?>
                <section id="specs" class="mb-4">
                    <div class="row g-3">
                        <div class="col-lg-5">
                            <div class="modern-form">
                                <div class="d-flex align-items-center mb-3" style="border-bottom:2px solid var(--primary);padding-bottom:10px">
                                    <i class="fas fa-plus-circle me-2" style="color:var(--primary)"></i>
                                    <h5 class="m-0" style="font-weight:700">إضافة تخصص جديد</h5>
                                </div>
                                <form role="form" name="dcotorspcl" method="post">
                                    <div class="mb-3">
                                        <label class="form-label">إضافة تخصص</label>
                                        <input type="text" name="doctorspecilization" class="form-control" placeholder="ادخل اسم التخصص" required>
                                    </div>
                                    <button type="submit" name="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>حفظ
                                    </button>
                                </form>
                            </div>
                        </div>
                        <div class="col-lg-7">
                            <div class="table-container">
                                <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                                    <h5 class="m-0">قائمة التخصصات الطبية</h5>
                                    <div class="input-group" style="max-width:280px">
                                        <input id="searchBox" type="text" class="form-control" placeholder="بحث في التخصصات...">
                                        <button class="btn btn-primary" type="button" onclick="filterSpecs()"><i class="fas fa-search"></i></button>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table m-0" id="specsTable">
                                        <thead>
                                            <tr>
                                                <th class="center">#</th>
                                                <th>التخصص</th>
                                                <th>تاريخ الإنشاء</th>
                                                <th>تاريخ التعديل</th>
                                                <th>الإجراءات</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $sql = mysqli_query($con, "SELECT * FROM doctorSpecilization ORDER BY id DESC");
                                            $cnt = 1;
                                            while ($row = mysqli_fetch_assoc($sql)) { ?>
                                                <tr>
                                                    <td class="center"><?php echo $cnt; ?>.</td>
                                                    <td class="fw-bold"><?php echo htmlspecialchars($row['specilization']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['creationDate']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['updationDate']); ?></td>
                                                    <td>
                                                        <div class="d-flex gap-2">
                                                            <a href="editdoctorspecialization.php?id=<?php echo (int)$row['id']; ?>" class="btn-action btn-edit" title="تعديل">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <a href="doctorspecilization.php?id=<?php echo (int)$row['id']; ?>&del=delete"
                                                               onclick="return confirm('هل أنت متأكد أنك تريد الحذف؟')"
                                                               class="btn-action btn-delete" title="حذف">
                                                                <i class="fas fa-trash-alt"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php $cnt++; } ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="d-flex justify-content-between align-items-center p-3 border-top">
                                    <div>عرض 1 إلى <?php echo $cnt - 1; ?> من <?php echo $cnt - 1; ?> إدخالات</div>
                                    <nav>
                                        <ul class="pagination m-0">
                                            <li class="page-item disabled"><a class="page-link" href="#">السابق</a></li>
                                            <li class="page-item active"><a class="page-link" href="#">1</a></li>
                                            <li class="page-item"><a class="page-link" href="#">2</a></li>
                                            <li class="page-item"><a class="page-link" href="#">التالي</a></li>
                                        </ul>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                <?php endif; ?>

                <?php include('include/footer.php'); ?>
            </main>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php if ($canManageSpecs): ?>
    <script>
        function filterSpecs(){
            const q = document.getElementById('searchBox').value.trim();
            const rows = document.querySelectorAll('#specsTable tbody tr');
            rows.forEach(r=>{
                const name = r.children[1].innerText;
                r.style.display = name.includes(q) ? '' : 'none';
            });
        }
        $(function(){
            $('form[name="dcotorspcl"]').on('submit', function(e){
                const v = $('input[name="doctorspecilization"]').val().trim();
                if(!v){ e.preventDefault(); alert('يرجى إدخال اسم التخصص'); }
            });
        });
    </script>
    <?php endif; ?>
</body>
</html>
