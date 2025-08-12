<?php
// include/header.php  (هيدر موحّد، وسط ثابت)
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
<header class="hms-topbar">
  <!-- يمين: زر القائمة -->
  <div class="hms-slot hms-slot-right">
    <button id="sidebarToggle" class="btn btn-link text-white d-lg-none" type="button" aria-label="Toggle sidebar">
      <i class="fa fa-bars"></i>
    </button>
  </div>

  <!-- الوسط: العنوان -->
  <div class="hms-center">
    <span class="hms-title">نظام إدارة المستشفيات</span>
  </div>

  <!-- يسار: قائمة المستخدم -->
  <div class="hms-slot hms-slot-left">
    <div class="hms-userbox">
      <a href="#" class="user-btn" id="userMenuBtn" aria-haspopup="true" aria-expanded="false">
        <span class="user-avatar">
          <?php echo htmlspecialchars($initial); ?>
        </span>
        <span class="d-none d-sm-inline user-name"><?php echo htmlspecialchars($docName); ?></span>
        <i class="fa fa-chevron-down"></i>
      </a>

      <div class="user-menu" id="userMenu">
        <div class="user-menu-head">
          <div class="user-menu-name"><?php echo htmlspecialchars($docName); ?></div>
          <?php if($docSpec): ?><small class="user-menu-spec"><?php echo htmlspecialchars($docSpec); ?></small><?php endif; ?>
        </div>

        <a href="edit-profile.php" class="d-none" id="profileLink">ملفي الشخصي</a>
        <a href="change-password.php" class="d-none" id="changePassLink">تغيير كلمة المرور</a>
        <a href="logout.php">تسجيل الخروج</a>

        <div class="user-menu-foot">
          <button id="toggleAccountLinks" class="btn btn-sm btn-light w-100" type="button">
            إظهار خيارات الحساب
          </button>
        </div>
      </div>
    </div>
  </div>
</header>

<style>
/* ===== هيدر بوسط ثابت ===== */
.hms-topbar{
  position:relative;
  display:flex; align-items:center; justify-content:center;
  min-height:64px;
  background:linear-gradient(90deg,#0b1f3b,#0c2a55);
  color:#fff; z-index:1000;
  padding:0 16px;
}
/* الفتحات الجانبية مثبتة */
.hms-slot{position:absolute; top:50%; transform:translateY(-50%); display:flex; align-items:center; gap:8px;}
.hms-slot-right{right:16px; left:auto;}
.hms-slot-left{left:16px; right:auto;}
/* الوسط */
.hms-center{pointer-events:none; text-align:center;}
.hms-center > *{pointer-events:auto;}
.hms-title{font-weight:700; letter-spacing:.3px;}

/* المستخدم */
.hms-userbox .user-btn{display:flex; align-items:center; gap:8px; color:#e9eef7; text-decoration:none;}
.hms-userbox .user-btn:hover{color:#fff;}
.user-avatar{
  width:36px;height:36px;border-radius:50%;
  background:#0d6efd;color:#fff;display:inline-flex;align-items:center;justify-content:center;font-weight:700;
}
.user-menu{
  position:absolute; left:0; right:auto; top:calc(100% + 10px);
  background:#fff; color:#333; border:1px solid #e7ecf3; border-radius:10px;
  box-shadow:0 10px 30px rgba(0,0,0,.08);
  min-width:220px; padding:6px 0; display:none;
}
.user-menu.show{display:block;}
.user-menu a{display:block; padding:8px 12px; color:#333; text-decoration:none;}
.user-menu a:hover{background:#f5f8fd;}
.user-menu-head{padding:10px 12px; border-bottom:1px solid #eef2f7;}
.user-menu-name{font-weight:700;}
.user-menu-spec{color:#6c757d;}
.user-menu-foot{padding:8px 12px; border-top:1px solid #eef2f7;}

/* موبایل: لو ضاق العرض خليه space-between */
@media (max-width: 992px){
  .hms-topbar{justify-content:space-between;}
  .hms-center{position:absolute; right:50%; transform:translateX(50%);}
}
</style>

<script>
(function(){
  const btn   = document.getElementById('userMenuBtn');
  const menu  = document.getElementById('userMenu');
  const tog   = document.getElementById('toggleAccountLinks');
  const pro   = document.getElementById('profileLink');
  const pass  = document.getElementById('changePassLink');

  function closeMenu(){ menu && menu.classList.remove('show'); btn && btn.setAttribute('aria-expanded','false'); }
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
