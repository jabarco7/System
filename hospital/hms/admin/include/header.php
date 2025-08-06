<?php
error_reporting(0);
?>

<header class="navbar navbar-default navbar-static-top">
  <!-- بداية: رأس الشريط -->
  <div class="navbar-header">
    
    <!-- زر إظهار الشريط الجانبي للجوال -->
    <a href="#" class="sidebar-mobile-toggler pull-left hidden-md hidden-lg btn btn-navbar sidebar-toggle"
       data-toggle-class="app-slide-off" data-toggle-target="#app" data-toggle-click-outside="#sidebar">
    </a>

    <!-- شعار النظام -->
    <a class="navbar-brand" href="#">
      <h2 style="padding-top: 20%; color:white">HMS</h2>
    </a>

    <!-- زر إظهار الشريط الجانبي للحواسيب -->
    <a href="#" class="sidebar-toggler pull-right visible-md visible-lg"
       data-toggle-class="app-sidebar-closed" data-toggle-target="#app">
    </a>

    <!-- زر قائمة الجوال -->
    <a class="pull-right menu-toggler visible-xs-block" id="menu-toggler" data-toggle="collapse" href=".navbar-collapse">
      <span class="sr-only">تبديل التنقل</span>
      <i class="ti-view-grid"></i>
    </a>
  </div>
  <!-- نهاية: رأس الشريط -->

  <!-- بداية: عناصر الشريط -->
  <div class="navbar-collapse collapse">
    <ul class="nav navbar-right">
      
      <!-- عنوان النظام -->
      <li style="padding-top: 2%;">
        <h2 style="color: white;">نظام إدارة المستشفيات</h2>
      </li>

      <!-- حساب المستخدم -->
      <li class="dropdown current-user">
        <a href="#" class="dropdown-toggle" data-toggle="dropdown">
          <img src="assets/images/images.jpg" alt="المستخدم">
          <span class="username">
            المسؤول
            <i class="ti-angle-down"></i>
          </span>
        </a>
        <ul class="dropdown-menu dropdown-dark">
          <li><a href="change-password.php">تغيير كلمة المرور</a></li>
          <li><a href="logout.php">تسجيل الخروج</a></li>
        </ul>
      </li>
    </ul>

    <!-- زر إغلاق القائمة على الجوال -->
    <div class="close-handle visible-xs-block menu-toggler" data-toggle="collapse" href=".navbar-collapse">
      <div class="arrow-left"></div>
      <div class="arrow-right"></div>
    </div>
  </div>
  <!-- نهاية: عناصر الشريط -->
</header>
