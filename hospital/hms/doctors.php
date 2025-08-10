<?php
// System/hospital/hms/doctors.php
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
include('include/config.php');

// تأكيد تسجيل الدخول
if (empty($_SESSION['id'])) {
    header('Location: user-login.php');
    exit;
}

// بيانات العرض/البحث
$patientName = $_SESSION['patient_name'] ?? 'المريض';
$spec = trim($_GET['spec'] ?? '');
$q    = trim($_GET['q'] ?? '');

// الاستعلام عن الأطباء مع فلاتر
$sql  = "SELECT id, doctorName, specilization, address, docFees, contactno, docEmail FROM doctors";
$cond = [];
$params = [];
$types  = "";

if ($spec !== "") {
    $cond[] = "specilization = ?";
    $params[] = $spec;
    $types   .= "s";
}
if ($q !== "") {
    $cond[] = "doctorName LIKE ?";
    $params[] = "%$q%";
    $types   .= "s";
}
if ($cond) { $sql .= " WHERE " . implode(" AND ", $cond); }
$sql .= " ORDER BY id DESC";

$stmt = mysqli_prepare($con, $sql);
if ($params) { mysqli_stmt_bind_param($stmt, $types, ...$params); }
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>الأطباء<?php echo $spec ? " - " . htmlspecialchars($spec) : ""; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root{--primary:#3498db}
        body{font-family:'Tajawal',sans-serif;background:#f0f5f9}
        .page{max-width:1150px;margin:30px auto}
        .head{background:linear-gradient(90deg,#3498db,#4aa8e0);color:#fff;border-radius:14px;padding:18px 20px;margin-bottom:16px}
        .searchbar{background:#fff;border-radius:14px;padding:14px 16px;box-shadow:0 6px 18px rgba(0,0,0,.05);margin-bottom:18px}

        /* الكروت */
        .card.doc{border:0;border-radius:16px;box-shadow:0 10px 26px rgba(0,0,0,.06);padding:22px}
        .doc-head{display:flex;gap:18px;align-items:flex-start}
        .avatar{width:78px;height:78px;min-width:78px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:#eef7ff}
        .avatar i{font-size:30px;color:#0d6efd}
        .doc-name{font-weight:800;font-size:1.15rem;margin-bottom:2px}
        .doc-spec{display:inline-block;background:#eef7ff;color:#0d6efd;border-radius:999px;padding:4px 12px;font-size:.9rem}
        .doc-meta{color:#5b6775;font-size:.92rem}
        .meta-sep{margin:0 6px;color:#cbd5e1}
        .fee{font-weight:800;color:#0f172a}
        .doc-actions{margin-top:12px}
        .btn-outline-secondary{border-radius:10px}
        .tag{display:inline-flex;align-items:center;gap:6px;background:#f6f9ff;border:1px solid #e7efff;border-radius:10px;padding:6px 10px;font-size:.86rem;color:#2b3b4f}
        .tag i{color:#0d6efd}
    </style>
</head>
<body>
<div class="page">
    <div class="head d-flex justify-content-between align-items-center">
        <h4 class="m-0">الأطباء <?php echo $spec ? " - " . htmlspecialchars($spec) : ""; ?></h4>
        <div>مرحباً، <?php echo htmlspecialchars($patientName); ?></div>
    </div>

    <div class="searchbar">
        <form class="row g-2" method="get">
            <div class="col-md-6">
                <input type="text" name="q" value="<?php echo htmlspecialchars($q);?>" class="form-control" placeholder="ابحث باسم الطبيب...">
            </div>
            <div class="col-md-4">
                <input type="text" name="spec" value="<?php echo htmlspecialchars($spec);?>" class="form-control" placeholder="التخصص (اختياري)">
            </div>
            <div class="col-md-2 d-grid">
                <button class="btn btn-primary"><i class="fa fa-search me-1"></i> بحث</button>
            </div>
        </form>
    </div>

    <?php if ($res && mysqli_num_rows($res) > 0): ?>
        <div class="row g-3">
            <?php while($d = mysqli_fetch_assoc($res)): ?>
                <div class="col-md-6">
                    <div class="card doc h-100">
                        <div class="doc-head">
                            <div class="avatar"><i class="fa-solid fa-user-doctor"></i></div>
                            <div class="flex-grow-1">
                                <div class="doc-name">
                                    <?php echo htmlspecialchars($d['doctorName']); ?>
                                </div>
                                <div class="mb-2">
                                    <span class="doc-spec"><?php echo htmlspecialchars($d['specilization']); ?></span>
                                </div>

                                <div class="doc-meta">
                                    <i class="fa-regular fa-envelope"></i>
                                    <?php echo htmlspecialchars($d['docEmail']); ?>
                                    <span class="meta-sep">•</span>
                                    <i class="fa-solid fa-location-dot"></i>
                                    <?php echo htmlspecialchars($d['address']); ?>
                                </div>

                                <div class="mt-2 d-flex flex-wrap gap-2">
                                    <span class="tag"><i class="fa-solid fa-sack-dollar"></i> <span class="fee"><?php echo htmlspecialchars($d['docFees']); ?></span> ر.س</span>
                                    <?php if (!empty($d['contactno'])): ?>
                                        <span class="tag"><i class="fa-solid fa-phone"></i> <?php echo htmlspecialchars($d['contactno']); ?></span>
                                    <?php endif; ?>
                                </div>

                                <div class="doc-actions">
                                    <!-- أزلنا زر حجز الموعد كما طلبت -->
                                    <a class="btn btn-sm btn-outline-secondary" href="doctor-profile.php?id=<?php echo (int)$d['id']; ?>">
                                        <i class="fa-regular fa-id-card me-1"></i> الملف
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info">لا يوجد أطباء مطابقون للبحث.</div>
    <?php endif; mysqli_stmt_close($stmt); ?>
</div>

<!-- لا يوجد أي مودال أو زر حجز بعد الآن -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
