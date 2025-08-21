<?php
session_start();
error_reporting(0);
include('include/config.php');

if (empty($_SESSION['id'])) {
    header('location:logout.php');
    exit;
}

$docId = (int)$_SESSION['id'];

// جلب معلومات الطبيب
$doctorInfo = null;
if ($st = $con->prepare("SELECT doctorName, specilization FROM doctors WHERE id=? LIMIT 1")) {
    $st->bind_param('i', $docId);
    $st->execute();
    $res = $st->get_result();
    $doctorInfo = $res->fetch_assoc();
    $st->close();
}

// إحصائيات المرضى
$stats = [
    'total_patients' => 0,
    'new_this_month' => 0,
    'appointments_today' => 0,
    'pending_appointments' => 0
];

// إجمالي المرضى
if ($st = $con->prepare("SELECT COUNT(DISTINCT p.ID) FROM tblpatient p WHERE p.Docid=?")) {
    $st->bind_param('i', $docId);
    $st->execute();
    $st->bind_result($stats['total_patients']);
    $st->fetch();
    $st->close();
}

// المرضى الجدد هذا الشهر
if ($st = $con->prepare("SELECT COUNT(*) FROM tblpatient WHERE Docid=? AND MONTH(CreationDate)=MONTH(NOW()) AND YEAR(CreationDate)=YEAR(NOW())")) {
    $st->bind_param('i', $docId);
    $st->execute();
    $st->bind_result($stats['new_this_month']);
    $st->fetch();
    $st->close();
}

// مواعيد اليوم
if ($st = $con->prepare("SELECT COUNT(*) FROM appointment WHERE doctorId=? AND appointmentDate=CURDATE()")) {
    $st->bind_param('i', $docId);
    $st->execute();
    $st->bind_result($stats['appointments_today']);
    $st->fetch();
    $st->close();
}

// المواعيد المعلقة
if ($st = $con->prepare("SELECT COUNT(*) FROM appointment WHERE doctorId=? AND (userStatus=0 OR doctorStatus=0)")) {
    $st->bind_param('i', $docId);
    $st->execute();
    $st->bind_result($stats['pending_appointments']);
    $st->fetch();
    $st->close();
}

// جلب قائمة المرضى مع آخر زيارة
$patients = [];
if ($st = $con->prepare("
    SELECT 
        p.ID,
        p.PatientName,
        p.PatientContno,
        p.PatientEmail,
        p.PatientGender,
        p.PatientAdd,
        p.PatientAge,
        p.PatientMedhis,
        p.CreationDate,
        COUNT(m.ID) as medical_records_count,
        MAX(m.CreationDate) as last_medical_record,
        COUNT(a.id) as total_appointments,
        MAX(CONCAT(a.appointmentDate, ' ', a.appointmentTime)) as last_appointment
    FROM tblpatient p
    LEFT JOIN tblmedicalhistory m ON m.PatientID = p.ID
    LEFT JOIN appointment a ON a.userId = (SELECT u.id FROM users u WHERE u.email = p.PatientEmail LIMIT 1) AND a.doctorId = ?
    WHERE p.Docid = ?
    GROUP BY p.ID
    ORDER BY p.CreationDate DESC
")) {
    $st->bind_param('ii', $docId, $docId);
    $st->execute();
    $res = $st->get_result();
    while ($row = $res->fetch_assoc()) {
        $patients[] = $row;
    }
    $st->close();
}

// فلترة البحث
$searchTerm = trim($_GET['search'] ?? '');
$genderFilter = trim($_GET['gender'] ?? '');
$ageFilter = trim($_GET['age'] ?? '');

if ($searchTerm || $genderFilter || $ageFilter) {
    $patients = array_filter($patients, function($patient) use ($searchTerm, $genderFilter, $ageFilter) {
        $matchSearch = empty($searchTerm) || 
                      stripos($patient['PatientName'], $searchTerm) !== false ||
                      stripos($patient['PatientEmail'], $searchTerm) !== false;
        
        $matchGender = empty($genderFilter) || $patient['PatientGender'] === $genderFilter;
        
        $matchAge = empty($ageFilter) || 
                   ($ageFilter === 'young' && $patient['PatientAge'] < 30) ||
                   ($ageFilter === 'middle' && $patient['PatientAge'] >= 30 && $patient['PatientAge'] < 60) ||
                   ($ageFilter === 'senior' && $patient['PatientAge'] >= 60);
        
        return $matchSearch && $matchGender && $matchAge;
    });
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم المرضى - <?php echo htmlspecialchars($doctorInfo['doctorName'] ?? 'الطبيب'); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            background: #f0f5f9;
            color: #333;
        }
        .header-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
        }
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .stats-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #667eea;
        }
        .patient-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .patient-card:hover {
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            transform: translateY(-2px);
        }
        .patient-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: bold;
        }
        .badge-custom {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .badge-male { background: #e3f2fd; color: #1976d2; }
        .badge-female { background: #fce4ec; color: #c2185b; }
        .search-section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        .btn-action {
            border-radius: 8px;
            padding: 8px 16px;
            font-size: 0.9rem;
            margin: 2px;
        }
    </style>
</head>
<body>
    <?php include('include/header.php'); ?>
    <?php include('include/sidebar.php'); ?>

    <div class="header-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-users-medical"></i> لوحة تحكم المرضى</h1>
                    <p class="mb-0">د. <?php echo htmlspecialchars($doctorInfo['doctorName'] ?? 'الطبيب'); ?> - <?php echo htmlspecialchars($doctorInfo['specilization'] ?? ''); ?></p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="add-patient.php" class="btn btn-light btn-lg">
                        <i class="fas fa-user-plus"></i> إضافة مريض جديد
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- إحصائيات سريعة -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $stats['total_patients']; ?></div>
                    <div>إجمالي المرضى</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $stats['new_this_month']; ?></div>
                    <div>مرضى جدد هذا الشهر</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $stats['appointments_today']; ?></div>
                    <div>مواعيد اليوم</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $stats['pending_appointments']; ?></div>
                    <div>مواعيد معلقة</div>
                </div>
            </div>
        </div>

        <!-- قسم البحث والفلترة -->
        <div class="search-section">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">البحث بالاسم أو البريد الإلكتروني</label>
                    <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($searchTerm); ?>" placeholder="ابحث عن مريض...">
                </div>
                <div class="col-md-3">
                    <label class="form-label">الجنس</label>
                    <select class="form-select" name="gender">
                        <option value="">جميع الأجناس</option>
                        <option value="ذكر" <?php echo $genderFilter === 'ذكر' ? 'selected' : ''; ?>>ذكر</option>
                        <option value="أنثى" <?php echo $genderFilter === 'أنثى' ? 'selected' : ''; ?>>أنثى</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">الفئة العمرية</label>
                    <select class="form-select" name="age">
                        <option value="">جميع الأعمار</option>
                        <option value="young" <?php echo $ageFilter === 'young' ? 'selected' : ''; ?>>أقل من 30</option>
                        <option value="middle" <?php echo $ageFilter === 'middle' ? 'selected' : ''; ?>>30-60</option>
                        <option value="senior" <?php echo $ageFilter === 'senior' ? 'selected' : ''; ?>>أكبر من 60</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> بحث
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- قائمة المرضى -->
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3>📋 قائمة المرضى (<?php echo count($patients); ?>)</h3>
                    <div>
                        <a href="manage-patient.php" class="btn btn-outline-primary">
                            <i class="fas fa-list"></i> العرض التقليدي
                        </a>
                    </div>
                </div>

                <?php if (empty($patients)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <h4>لا توجد مرضى</h4>
                        <p class="text-muted">لم يتم العثور على أي مرضى مطابقين للبحث</p>
                        <a href="add-patient.php" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> إضافة مريض جديد
                        </a>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($patients as $patient): ?>
                            <div class="col-lg-6 col-xl-4">
                                <div class="patient-card">
                                    <div class="d-flex align-items-start">
                                        <div class="patient-avatar">
                                            <?php echo mb_substr($patient['PatientName'], 0, 1, 'UTF-8'); ?>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h5 class="mb-1"><?php echo htmlspecialchars($patient['PatientName']); ?></h5>
                                            <p class="text-muted mb-2">
                                                <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($patient['PatientEmail']); ?>
                                            </p>
                                            <p class="text-muted mb-2">
                                                <i class="fas fa-phone"></i> <?php echo htmlspecialchars($patient['PatientContno']); ?>
                                            </p>
                                            
                                            <div class="d-flex gap-2 mb-3">
                                                <span class="badge-custom <?php echo $patient['PatientGender'] === 'ذكر' ? 'badge-male' : 'badge-female'; ?>">
                                                    <?php echo htmlspecialchars($patient['PatientGender']); ?>
                                                </span>
                                                <span class="badge-custom" style="background: #e8f5e8; color: #2e7d32;">
                                                    <?php echo $patient['PatientAge']; ?> سنة
                                                </span>
                                                <span class="badge-custom" style="background: #fff3e0; color: #f57c00;">
                                                    <?php echo $patient['medical_records_count']; ?> سجل طبي
                                                </span>
                                            </div>

                                            <div class="d-flex gap-1">
                                                <a href="view-patient.php?viewid=<?php echo $patient['ID']; ?>" class="btn btn-primary btn-action">
                                                    <i class="fas fa-eye"></i> عرض
                                                </a>
                                                <a href="edit-patient.php?editid=<?php echo $patient['ID']; ?>" class="btn btn-outline-primary btn-action">
                                                    <i class="fas fa-edit"></i> تعديل
                                                </a>
                                                <a href="../view-medhistory.php?pid=<?php echo $patient['ID']; ?>" class="btn btn-outline-success btn-action">
                                                    <i class="fas fa-notes-medical"></i> السجل
                                                </a>
                                            </div>

                                            <?php if ($patient['last_appointment']): ?>
                                                <div class="mt-2">
                                                    <small class="text-muted">
                                                        <i class="fas fa-calendar"></i> آخر موعد: <?php echo date('Y-m-d H:i', strtotime($patient['last_appointment'])); ?>
                                                    </small>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include('include/footer.php'); ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
