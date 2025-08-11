<?php
// include/header.php  (موحّد للطبيب/المدير)
error_reporting(0);
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

// اجلب اسم الطبيب وتخصصه (لو الصفحة للطبيب)
$docName = 'الحساب';
$docSpec = '';
if (!empty($_SESSION['id']) && isset($con) && $con instanceof mysqli) {
    if ($st = $con->prepare("SELECT doctorName, specilization FROM doctors WHERE id=? LIMIT 1")) {
        $st->bind_param("i", $_SESSION['id']);
        $st->execute();
        $res = $st->get_result();
        if ($row = $res->fetch_assoc()) {
            $docName = $row['doctorName'] ?: $docName;
            $docSpec = $row['specilization'] ?: $docSpec;
        }
        $st->close();
    }
}
$initial = function_exists('mb_substr') ? mb_substr($docName, 0, 1, 'UTF-8') : substr($docName, 0, 1);
?>
<header class="navbar navbar-default navbar-static-top">
  <div class="container-fluid" style="display:flex;align-items:center;gap:16px;">
    <!-- زر فتح السايدبار على الشاشات الصغيرة -->
    <button id="sidebarToggle" class="btn btn-link text-white d-lg-none" type="button" aria-label="Toggle sidebar">
      <i class="fa fa-bars"></i>
    </button>

    <!-- الشعار -->
    <a class="navbar-brand" href="dashboard.php">
      <h2 style="margin:0;color:#fff;">HMS</h2>
    </a>

    <!-- عنوان الهيدر في المنتصف -->
    <div class="flex-grow-1 text-center" style="color:#fff;font-weight:600;">
      نظام إدارة المستشفيات
    </div>

    <!-- أيقونة المستخدم وقائمة منسدلة مخصّصة -->
    <div class="hms-userbox">
      <a href="#" class="user-btn" id="userMenuBtn" aria-haspopup="true" aria-expanded="false">
        <span class="user-avatar" style="width:36px;height:36px;border-radius:50%;background:#0d6efd;color:#fff;display:inline-flex;align-items:center;justify-content:center;font-weight:700;">
          <?php echo htmlspecialchars($initial); ?>
        </span>
        <span class="d-none d-sm-inline" style="color:#e9eef7;"><?php echo htmlspecialchars($docName); ?></span>
        <i class="fa fa-chevron-down"></i>
      </a>

      <div class="user-menu" id="userMenu">
        <div style="padding:10px 12px;border-bottom:1px solid #eef2f7;">
          <div style="font-weight:700;"><?php echo htmlspecialchars($docName); ?></div>
          <?php if($docSpec): ?><small style="color:#6c757d;"><?php echo htmlspecialchars($docSpec); ?></small><?php endif; ?>
        </div>

        <!-- روابط الحساب مخفية افتراضياً -->
        <a href="edit-profile.php" class="d-none" id="profileLink">ملفي الشخصي</a>
        <a href="change-password.php" class="d-none" id="changePassLink">تغيير كلمة المرور</a>
        <a href="logout.php">تسجيل الخروج</a>

        <div style="padding:8px 12px;border-top:1px solid #eef2f7;">
          <button id="toggleAccountLinks" class="btn btn-sm btn-light w-100" type="button">
            إظهار خيارات الحساب
          </button>
        </div>
      </div>
    </div>
  </div>
</header>

<script>
(function(){
  const btn   = document.getElementById('userMenuBtn');
  const menu  = document.getElementById('userMenu');
  const tog   = document.getElementById('toggleAccountLinks');
  const pro   = document.getElementById('profileLink');
  const pass  = document.getElementById('changePassLink');

  function closeMenu(){ menu.classList.remove('show'); btn && btn.setAttribute('aria-expanded','false'); }
  btn && btn.addEventListener('click', function(e){
    e.preventDefault();
    menu.classList.toggle('show');
    btn.setAttribute('aria-expanded', menu.classList.contains('show') ? 'true' : 'false');
  });
  document.addEventListener('click', function(e){
    if (menu && !menu.contains(e.target) && btn && !btn.contains(e.target)) closeMenu();
  });
  window.addEventListener('keydown', function(e){ if (e.key === 'Escape') closeMenu(); });

  // إظهار/إخفاء روابط الحساب
  if (tog) {
    tog.addEventListener('click', function(){
      const hidden = pro.classList.contains('d-none');
      [pro, pass].forEach(el => el.classList.toggle('d-none'));
      this.textContent = hidden ? 'إخفاء خيارات الحساب' : 'إظهار خيارات الحساب';
    });
  }

  // تفعيل زر السايدبار في الشاشات الصغيرة
  const sidebarToggle = document.getElementById('sidebarToggle');
  const sidebar = document.getElementById('sidebar');
  if (sidebarToggle && sidebar) {
    sidebarToggle.addEventListener('click', function(e){
      e.preventDefault();
      sidebar.classList.toggle('active');
    });
  }
})();
</script>
