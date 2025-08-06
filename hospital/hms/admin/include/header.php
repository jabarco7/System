<?php
error_reporting(0);
?>

<header class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top" style="height: 70px; z-index: 1030;" dir="rtl">
  <div class="container-fluid d-flex align-items-center justify-content-between px-3">
    
    <!-- زر إظهار الشريط الجانبي للجوال -->
    <button class="d-block d-lg-none btn btn-outline-light" onclick="toggleSidebar()" title="إظهار القائمة">
      ☰
    </button>

    <!-- شعار النظام -->
    <a href="#" class="navbar-brand mb-0 h1 text-white">HMS</a>

    <!-- عنوان النظام -->
    <h5 class="text-white mb-0 d-none d-md-block">نظام إدارة المستشفيات</h5>

    <!-- قائمة المستخدم -->
    <div class="dropdown">
      <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
        <img src="assets/images/images.jpg" alt="المستخدم" width="32" height="32" class="rounded-circle ms-2">
        <strong>المسؤول</strong>
      </a>
      <ul class="dropdown-menu dropdown-menu-end text-end">
        <li><a class="dropdown-item" href="change-password.php">تغيير كلمة المرور</a></li>
        <li><a class="dropdown-item" href="logout.php">تسجيل الخروج</a></li>
      </ul>
    </div>

  </div>
</header>

<script>
  function toggleSidebar() {
    document.getElementById("sidebar").classList.toggle("active");
  }
</script>
