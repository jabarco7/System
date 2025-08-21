<?php
// include/sidebar.php  (Doctor Sidebar)
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}
if (!isset($con)) {
  @include_once __DIR__ . '/config.php';
}

$docName = 'الطبيب';
$docSpec = '—';
$avatarInitial = 'د';

$sessionId    = (int)($_SESSION['id'] ?? 0);
$sessionEmail = trim($_SESSION['dlogin'] ?? '');

if (isset($con) && $con instanceof mysqli) {
  if ($sessionId > 0 && ($st = $con->prepare("SELECT doctorName, specilization FROM doctors WHERE id=? LIMIT 1"))) {
    $st->bind_param("i", $sessionId);
    $st->execute();
    $res = $st->get_result();
    if ($row = $res->fetch_assoc()) {
      $docName = $row['doctorName'] ?: $docName;
      $docSpec = $row['specilization'] ?: $docSpec;
    }
    $st->close();
  }
  if ($docSpec === '—' && $sessionEmail !== '' && ($st = $con->prepare("SELECT doctorName, specilization FROM doctors WHERE docEmail=? LIMIT 1"))) {
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

$avatarInitial = (function_exists('mb_substr') ? mb_substr($docName, 0, 1, 'UTF-8') : substr($docName, 0, 1)) ?: $avatarInitial;

// Helper لتمييز الرابط الحالي
$active = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
?>
<!-- استخدم aside وليس nav كحاوية رئيسية -->
<aside id="sidebar" class="app-sidebar" aria-label="Doctor sidebar">
  <div class="sidebar-header">
    <div class="user-mini">
      <span class="avatar"><?php echo htmlspecialchars($avatarInitial); ?></span>
      <div>
        <div class="name" style="font-weight:700;"><?php echo htmlspecialchars($docName); ?></div>
        <div class="role" style="color:#9fb2cf;"><?php echo htmlspecialchars($docSpec ?: '—'); ?></div>
      </div>
    </div>
  </div>

  <nav aria-label="Main navigation">
    <ul>
      <li>
        <a href="dashboard.php" class="<?php echo ($active === 'dashboard.php' ? 'is-active' : ''); ?>">
          <i class="fa-solid fa-house"></i> لوحة التحكم
        </a>
      </li>
      <li>
        <a href="manage-patient.php" class="<?php echo ($active === 'manage-patient.php' ? 'is-active' : ''); ?>">
          <i class="fa-solid fa-user-injured"></i> إدارة المرضى
        </a>
      </li>
      <li>
        <a href="appointment-history.php" class="<?php echo ($active === 'appointment-history.php' ? 'is-active' : ''); ?>">
          <i class="fa-solid fa-calendar-check"></i> مواعيدي
        </a>
      </li>
      <li>
        <a href="logout.php">
          <i class="fa-solid fa-right-from-bracket"></i> تسجيل الخروج
        </a>
      </li>
    </ul>
  </nav>
</aside>

<script>
  // زر السايدبار للموبايل (متكامل مع الهيدر)
  (function() {
    var toggle = document.getElementById('sidebarToggle');
    var sidebar = document.getElementById('sidebar');
    if (toggle && sidebar) {
      toggle.addEventListener('click', function(e) {
        e.preventDefault();
        sidebar.classList.toggle('active');
      });
      document.addEventListener('click', function(e) {
        if (window.innerWidth <= 992) {
          if (!sidebar.contains(e.target) && e.target !== toggle && !toggle.contains(e.target)) {
            sidebar.classList.remove('active');
          }
        }
      });
    }
  })();
</script>