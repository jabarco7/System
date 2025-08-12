<?php
session_start();
include("include/config.php");

/* أمان: CSRF */
if (empty($_SESSION['csrf'])) {
  $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$CSRF = $_SESSION['csrf'];

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
  // تحقق CSRF
  if (!hash_equals($CSRF, $_POST['csrf'] ?? '')) {
    $err = 'انتهت صلاحية الجلسة. حدّث الصفحة وحاول مجددًا.';
  } else {
    $contactno = trim($_POST['contactno'] ?? '');
    $email     = trim($_POST['email'] ?? '');

    // تنظيف/تحقق مبدئي
    $contactno = preg_replace('/\s+/', '', $contactno); // إزالة المسافات
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $err = 'صيغة البريد الإلكتروني غير صحيحة.';
    } elseif (!preg_match('/^[0-9+\-]{6,20}$/', $contactno)) {
      $err = 'رقم الاتصال غير صالح.';
    } else {
      // استعلام مُحضّر
      if ($st = $con->prepare("SELECT id FROM doctors WHERE contactno = ? AND docEmail = ? LIMIT 1")) {
        $st->bind_param('ss', $contactno, $email);
        $st->execute();
        $st->store_result();
        if ($st->num_rows > 0) {
          // نجاح: خزّن الحد الأدنى للمتابعة
          $_SESSION['cnumber'] = $contactno;
          $_SESSION['email']   = $email;

          // منع تثبيت الجلسة
          session_regenerate_id(true);

          header('Location: reset-password.php');
          exit;
        } else {
          $err = 'بيانات غير صحيحة. يُرجى المحاولة باستخدام بيانات صحيحة.';
        }
        $st->close();
      } else {
        $err = 'حدث خطأ داخلي. حاول لاحقًا.';
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8" />
  <title>استعادة كلمة المرور | الأطباء</title>

  <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="vendor/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="vendor/fontawesome/css/font-awesome.min.css">

  <style>
    html, body { height:100%; background:#f0f5f9; font-family:'Tajawal',sans-serif; }
    .auth-wrap{
      min-height:100%;
      display:flex; align-items:center; justify-content:center;
      padding:24px;
    }
    .auth-card{
      width:100%; max-width:460px;
      background:#fff; border-radius:16px;
      box-shadow:0 14px 40px rgba(0,0,0,.08);
      overflow:hidden;
    }
    .auth-head{
      background:linear-gradient(90deg,#0c2a55,#0b1f3b);
      color:#fff; padding:18px 20px;
      display:flex; align-items:center; justify-content:center;
      font-weight:700;
    }
    .auth-body{ padding:20px; }
    .form-control{ border-radius:10px; height:44px; }
    .btn-primary{
      background:#0d6efd; border:none; border-radius:10px; padding:10px 14px;
    }
    .btn-primary:focus{ outline:none; box-shadow:0 0 0 0.2rem rgba(13,110,253,.25); }
    .muted{ color:#6c757d; }
    .brand-link{ text-decoration:none; color:#fff; }
    .brand-link:hover{ color:#e8f0ff; text-decoration:none; }
    .small-link{ text-decoration:none; }
    .small-link:hover{ text-decoration:underline; }
  </style>
</head>
<body>

<div class="auth-wrap">
  <div class="auth-card">
    <div class="auth-head">
      <a class="brand-link" href="../../index.php">
        مستشفى عمران — استعادة كلمة مرور الطبيب
      </a>
    </div>

    <div class="auth-body">
      <h4 class="mb-2">استعادة كلمة المرور</h4>
      <p class="muted mb-3">يرجى إدخال <strong>رقم الاتصال</strong> و<strong>البريد الإلكتروني</strong> المسجلين لمتابعة إعادة التعيين.</p>

      <?php if (!empty($err)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($err); ?></div>
      <?php endif; ?>
      <?php if (!empty($msg)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div>
      <?php endif; ?>

      <form method="post" autocomplete="off" novalidate>
        <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($CSRF); ?>">

        <div class="form-group mb-3">
          <label class="mb-1">رقم الاتصال المسجل</label>
          <input type="text"
                 class="form-control"
                 name="contactno"
                 inputmode="numeric"
                 pattern="[0-9+\-]{6,20}"
                 placeholder="مثال: 771234567"
                 value="<?php echo htmlspecialchars($_POST['contactno'] ?? ''); ?>"
                 required>
        </div>

        <div class="form-group mb-3">
          <label class="mb-1">البريد الإلكتروني المسجل</label>
          <input type="email"
                 class="form-control"
                 name="email"
                 placeholder="your@email.com"
                 value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                 required>
        </div>

        <div class="d-flex align-items-center justify-content-between mt-3">
          <a href="index.php" class="small-link"><i class="fa fa-arrow-right"></i> العودة لتسجيل الدخول</a>
          <button type="submit" class="btn btn-primary" name="submit">
            متابعة <i class="fa fa-arrow-left"></i>
          </button>
        </div>
      </form>

      <hr class="mt-4 mb-2" />
      <div class="text-center muted" style="font-size:.9rem;">
        نظام إدارة المستشفيات
      </div>
    </div>
  </div>
</div>

<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.min.js"></script>
</body>
</html>
