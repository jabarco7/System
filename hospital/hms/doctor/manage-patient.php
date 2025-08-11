<?php
session_start();

error_reporting(E_ALL);           // <-- مؤقتًا لعرض الأخطاء
ini_set('display_errors', 1);     // <-- مؤقتًا لعرض الأخطاء
include('include/config.php');

if (!isset($_SESSION['id']) || strlen($_SESSION['id']) == 0) {
  header('location:logout.php');
  exit;
}

$docid = (int)$_SESSION['id'];

/** نجلب المرضى الذين لديهم حجوزات لدى هذا الطبيب (من appointment) ونظهر بياناتهم من users */
$patients = [];
if ($st = $con->prepare("
    SELECT 
      u.id            AS uid,
      u.fullName      AS fullName,
      u.gender        AS gender,
      u.regDate       AS regDate,
      u.updationDate  AS updationDate,
      MAX(CONCAT(a.appointmentDate,' ',a.appointmentTime)) AS last_visit,
      COUNT(*) AS total_visits
    FROM appointment a
    JOIN users u ON u.id = a.userId
    WHERE a.doctorId = ?
    GROUP BY u.id, u.fullName, u.gender, u.regDate, u.updationDate
    ORDER BY last_visit DESC
")) {
  $st->bind_param('i', $docid);
  $st->execute();
  $res = $st->get_result();
  while ($row = $res->fetch_assoc()) { $patients[] = $row; }
  $st->close();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>طبيب | إدارة المرضى</title>

  <!-- خط Tajawal -->
  <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">

  <!-- ملفاتك الحالية -->
  <link rel="stylesheet" href="vendor/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="vendor/fontawesome/css/font-awesome.min.css">
  <link rel="stylesheet" href="vendor/themify-icons/themify-icons.min.css">
  <link href="vendor/animate.css/animate.min.css" rel="stylesheet" media="screen">
  <link href="vendor/perfect-scrollbar/perfect-scrollbar.min.css" rel="stylesheet" media="screen">
  <link href="vendor/switchery/switchery.min.css" rel="stylesheet" media="screen">
  <link href="vendor/bootstrap-touchspin/jquery.bootstrap-touchspin.min.css" rel="stylesheet" media="screen">
  <link href="vendor/select2/select2.min.css" rel="stylesheet" media="screen">
  <link href="vendor/bootstrap-datepicker/bootstrap-datepicker3.standalone.min.css" rel="stylesheet" media="screen">
  <link href="vendor/bootstrap-timepicker/bootstrap-timepicker.min.css" rel="stylesheet" media="screen">
  <link rel="stylesheet" href="assets/css/styles.css">
  <link rel="stylesheet" href="assets/css/plugins.css">
  <link rel="stylesheet" href="assets/css/themes/theme-1.css" id="skin_color" />
  <link rel="stylesheet" href="assets/css/hms-unified.css?v=1.1">
</head>

<body>
  <div id="app">

   

  <?php include('include/header.php'); ?>
	   <?php include('include/sidebar.php'); ?>

      <div class="main-content">
        <div class="wrap-content container" id="container">

          <section id="page-title" style="margin-bottom: 16px;">
            <div class="row">
              <div class="col-sm-8">
                <h1 class="mainTitle">طبيب | إدارة المرضى</h1>
              </div>
              <ol class="breadcrumb">
                <li><span>طبيب</span></li>
                <li class="active"><span>إدارة المرضى</span></li>
              </ol>
            </div>
          </section>

          <div class="container-fluid container-fullw bg-white">
            <div class="row">
              <div class="col-md-12">
                <h5 class="over-title margin-bottom-15">إدارة <span class="text-bold">المرضى</span></h5>

                <div class="table-responsive">
                  <table class="table table-hover" id="patients-table">
                    <thead>
                      <tr>
                        <th class="center">#</th>
                        <th>اسم المريض</th>
                        <th>الجنس</th>
                        <th>تاريخ الإنشاء</th>
                        <th>تاريخ التحديث</th>
                        <th>آخر زيارة</th>
                        <th>عدد الزيارات</th>
                        <th>الإجراء</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (count($patients)): ?>
                        <?php $cnt = 1; foreach ($patients as $row): ?>
                          <tr>
                            <td class="center"><?php echo $cnt++; ?>.</td>
                            <td class="hidden-xs"><?php echo htmlspecialchars($row['fullName'] ?: '—'); ?></td>
                            <td><?php echo htmlspecialchars($row['gender'] ?: '—'); ?></td>
                            <td><?php echo htmlspecialchars($row['regDate'] ?: '—'); ?></td>
                            <td><?php echo htmlspecialchars($row['updationDate'] ?: '—'); ?></td>
                            <td><?php echo htmlspecialchars($row['last_visit'] ?: '—'); ?></td>
                            <td><?php echo (int)$row['total_visits']; ?></td>
                            <td>
                              <!-- عدّل صفحات العرض/التعديل لتقبل uid إن لزم -->
                              <a href="edit-patient.php?uid=<?php echo (int)$row['uid']; ?>" class="btn btn-primary btn-sm">تعديل</a>
<a href="view-patient.php?viewid=<?php echo (int)$row['uid']; ?>" class="btn btn-warning btn-sm">عرض التفاصيل</a>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      <?php else: ?>
                        <tr>
                          <td colspan="8" class="text-center">لا يوجد مرضى لديهم حجوزات لديك حالياً.</td>
                        </tr>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>

              </div>
            </div>
          </div>

        </div>
      </div>
    </div>


  </div>

  <script src="vendor/jquery/jquery.min.js"></script>
  <script src="vendor/bootstrap/js/bootstrap.min.js"></script>
  <script src="vendor/modernizr/modernizr.js"></script>
  <script src="vendor/jquery-cookie/jquery.cookie.js"></script>
  <script src="vendor/perfect-scrollbar/perfect-scrollbar.min.js"></script>
  <script src="vendor/switchery/switchery.min.js"></script>
  <script src="vendor/maskedinput/jquery.maskedinput.min.js"></script>
  <script src="vendor/bootstrap-touchspin/jquery.bootstrap-touchspin.min.js"></script>
  <script src="vendor/autosize/autosize.min.js"></script>
  <script src="vendor/selectFx/classie.js"></script>
  <script src="vendor/selectFx/selectFx.js"></script>
  <script src="vendor/select2/select2.min.js"></script>
  <script src="vendor/bootstrap-datepicker/bootstrap-datepicker.min.js"></script>
  <script src="vendor/bootstrap-timepicker/bootstrap-timepicker.min.js"></script>
  <script src="assets/js/main.js"></script>
  <script src="assets/js/form-elements.js"></script>
  <script>
    jQuery(function(){
      if (window.Main && Main.init) Main.init();
      if (window.FormElements && FormElements.init) FormElements.init();
    });
  </script>
</body>
</html>
