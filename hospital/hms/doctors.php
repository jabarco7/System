<?php
// System/hospital/hms/doctors.php
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
include('include/config.php');

// (اختياري) تأكيد تسجيل الدخول
if (empty($_SESSION['id'])) {
    header('Location: user-login.php');
    exit;
}

$patientName = $_SESSION['patient_name'] ?? 'المريض';
$spec = trim($_GET['spec'] ?? '');
$q    = trim($_GET['q'] ?? '');

// حضّر الاستعلام بمرونة
$sql  = "SELECT id, doctorName, specilization, address, docFees, contactno, docEmail
         FROM doctors";
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root{--primary:#3498db}
        body{font-family:'Tajawal',sans-serif;background:#f0f5f9}
        .page{max-width:1100px;margin:30px auto}
        .head{background:linear-gradient(90deg,#3498db,#4aa8e0);color:#fff;border-radius:12px;padding:18px 20px;margin-bottom:16px}
        .card{border:0;border-radius:14px;box-shadow:0 6px 18px rgba(0,0,0,.06)}
        .doc-card{display:flex;gap:16px;align-items:center}
        .avatar{width:58px;height:58px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:#eef7ff;font-weight:700}
        .pill{background:#eef7ff;color:#0d6efd;border-radius:30px;padding:3px 10px;font-size:.85rem}
        .searchbar{background:#fff;border-radius:12px;padding:12px 14px;box-shadow:0 4px 12px rgba(0,0,0,.04)}
        .fee{font-weight:700}
    </style>
</head>
<body>
<div class="page">
    <div class="head d-flex justify-content-between align-items-center">
        <h4 class="m-0">الأطباء <?php echo $spec ? " - " . htmlspecialchars($spec) : ""; ?></h4>
        <div>مرحباً، <?php echo htmlspecialchars($patientName); ?></div>
    </div>

    <div class="searchbar mb-3">
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
                    <div class="card p-3">
                        <div class="doc-card">
                            <div class="avatar"><i class="fa-solid fa-user-doctor"></i></div>
                            <div>
                                <div class="fw-bold"><?php echo htmlspecialchars($d['doctorName']); ?></div>
                                <div class="text-muted small">
                                    <?php echo htmlspecialchars($d['address']); ?>
                                    • <?php echo htmlspecialchars($d['docEmail']); ?>
                                    • <span class="fee"><?php echo htmlspecialchars($d['docFees']); ?> ر.س</span>
                                </div>
                                <span class="pill mt-1 d-inline-block"><?php echo htmlspecialchars($d['specilization']); ?></span>
                            </div>
                        </div>
                        <div class="mt-3 d-flex gap-2">
                            <a class="btn btn-sm btn-outline-primary" href="book-appointment.php?doctor=<?php echo (int)$d['id']; ?>">
                                <i class="fa-regular fa-calendar-check me-1"></i> حجز موعد
                            </a>
                            <a class="btn btn-sm btn-outline-secondary" href="doctor-profile.php?id=<?php echo (int)$d['id']; ?>">
                                <i class="fa-regular fa-id-card me-1"></i> الملف
                            </a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info">لا يوجد أطباء مطابقون للبحث.</div>
    <?php endif; mysqli_stmt_close($stmt); ?>
</div>
</body>
</html>
