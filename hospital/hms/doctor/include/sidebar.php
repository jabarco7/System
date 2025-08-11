<?php
// include/sidebar.php  (Doctor Sidebar)
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
if (!isset($con)) { @include_once __DIR__ . '/config.php'; }

$docName = 'الطبيب';
$docSpec = '—';
$avatarInitial = 'د';

$sessionId    = (int)($_SESSION['id'] ?? 0);
$sessionEmail = trim($_SESSION['dlogin'] ?? '');

if (isset($con) && $con instanceof mysqli) {
    if ($sessionId > 0) {
        if ($st = $con->prepare("SELECT id, doctorName, specilization FROM doctors WHERE id=? LIMIT 1")) {
            $st->bind_param("i", $sessionId);
            $st->execute();
            $res = $st->get_result();
            if ($row = $res->fetch_assoc()) {
                $docName = $row['doctorName'] ?: $docName;
                $docSpec = $row['specilization'] ?: $docSpec;
            }
            $st->close();
        }
    }
    if ($docSpec === '—' && $sessionEmail !== '') {
        if ($st = $con->prepare("SELECT id, doctorName, specilization FROM doctors WHERE docEmail=? LIMIT 1")) {
            $st->bind_param("s", $sessionEmail);
            $st->execute();
            $res = $st->get_result();
            if ($row = $res->fetch_assoc()) {
                $docName = $row['doctorName'] ?: $docName;
                $docSpec = $row['specilization'] ?: $docSpec;
            }
            $st->close();
        }
    }
}

if (function_exists('mb_substr')) {
    $avatarInitial = mb_substr($docName, 0, 1, 'UTF-8') ?: $avatarInitial;
} else {
    $avatarInitial = substr($docName, 0, 1) ?: $avatarInitial;
}
?>



<nav id="sidebar" aria-label="Doctor sidebar">
  <div class="user-profile">
    <div class="user-avatar"><?php echo htmlspecialchars($avatarInitial); ?></div>
    <div class="user-name"><?php echo htmlspecialchars($docName); ?></div>
    <div class="user-role"><?php echo htmlspecialchars($docSpec ?: '—'); ?></div>
  </div>

  <div class="navbar-title">التنقل الرئيسي</div>

  <ul class="nav">
    <li class="nav-item">
      <a href="dashboard.php" class="nav-link<?php echo (basename($_SERVER['PHP_SELF'])==='dashboard.php'?' active':''); ?>">
        <i class="fas fa-home"></i> لوحة التحكم
      </a>
    </li>
    <li class="nav-item">
      <a href="manage-patient.php" class="nav-link<?php echo (basename($_SERVER['PHP_SELF'])==='manage-patient.php'?' active':''); ?>">
        <i class="fas fa-user-injured"></i> إدارة المرضى
      </a>
    </li>
    <li class="nav-item">
      <a href="appointment-history.php" class="nav-link<?php echo (basename($_SERVER['PHP_SELF'])==='appointment-history.php'?' active':''); ?>">
        <i class="fas fa-history"></i> مواعيدي
      </a>
    </li>
    <li class="nav-item">
      <a href="logout.php" class="nav-link">
        <i class="fas fa-sign-out-alt"></i> تسجيل الخروج
      </a>
    </li>
  </ul>
</nav>

<script>
  // زر إظهار السايدبار على الموبايل (لو موجود في الهيدر)
  (function(){
    var toggle  = document.getElementById('sidebarToggle');
    var sidebar = document.getElementById('sidebar');
    if(toggle && sidebar){
      toggle.addEventListener('click', function(e){
        e.preventDefault();
        sidebar.classList.toggle('active');
      });
      document.addEventListener('click', function(e){
        if (window.innerWidth <= 992){
          if (!sidebar.contains(e.target) && e.target !== toggle && !toggle.contains(e.target)){
            sidebar.classList.remove('active');
          }
        }
      });
    }
  })();
</script>
