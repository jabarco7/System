<?php
session_start();
error_reporting(0);
include('include/config.php');

if (strlen($_SESSION['id']) == 0) {
    header('location:logout.php');
    exit;
} else {

    // إضافة التاريخ الطبي
    if (isset($_POST['submit'])) {
        $vid    = isset($_GET['viewid']) ? intval($_GET['viewid']) : 0;
        $bp     = trim($_POST['bp'] ?? '');
        $bs     = trim($_POST['bs'] ?? '');
        $weight = trim($_POST['weight'] ?? '');
        $temp   = trim($_POST['temp'] ?? '');
        $pres   = trim($_POST['pres'] ?? '');

        if ($vid > 0) {
            $query = mysqli_query($con, "INSERT INTO tblmedicalhistory
                (PatientID,BloodPressure,BloodSugar,Weight,Temperature,MedicalPres)
                VALUES ('$vid','$bp','$bs','$weight','$temp','$pres')");
            if ($query) {
                echo '<script>alert("تمت إضافة التاريخ الطبي.")</script>';
                echo "<script>window.location.href='manage-patient.php'</script>";
                exit;
            } else {
                echo '<script>alert("حدث خطأ ما. يُرجى المحاولة مرة أخرى.")</script>';
            }
        }
    }
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>المسؤول | عرض تفاصيل المريض</title>

    <link href="http://fonts.googleapis.com/css?family=Lato:300,400,400italic,600,700|Raleway:300,400,500,600,700|Crete+Round:400italic" rel="stylesheet" type="text/css" />
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

    <!-- تعديلات RTL خفيفة للسلايدبار والجداول فقط -->
    <style>
      /* اجعل السلايدبار على اليمين والمحتوى يأخذ مسافة من اليمين */
      body, html { direction: rtl; }
      .sidebar { right: 0 !important; left: auto !important; }
      .sidebar ~ .app-content { margin-right: 260px; margin-left: 0; } /* عدّل الرقم لو عرض سلايدبارك مختلف */
      @media (max-width: 991.98px){
        .sidebar ~ .app-content { margin-right: 0; }
      }

      /* الجداول RTL: العناوين والخلايا يمين، والأرقام وسط */
      .rtl-table { direction: rtl; }
      .rtl-table th, .rtl-table td { text-align: right; vertical-align: middle; }
      .rtl-table td.num, .rtl-table th.num { text-align: center; }
      /* صف العنوان الأزرق */
      .rtl-table thead th { text-align: right; }
      /* عنوان الجدول المدمج */
      .table-title-row td { text-align:center !important; font-size:19px; color:#2d6cdf; font-weight:700; }
      		:root{
			/* عدّل الرقمين حسب ارتفاع الهيدر لديك */
			--header-h: 64px;       /* ارتفاع شريط الهيدر */
			--sidebar-lift: 10px;   /* مقدار رفع السايدبار للأعلى */
		}

		html, body { margin:0; padding:0; }

		/* ألغِ أي هامش/حشو علوي من حاويات المحتوى القياسية */
		.app-content,
		.main-content,
		.wrap-content,
		#container,
		.container,
		.container-fluid{
			margin-top: 0 !important;
			padding-top: 0 !important;
		}

		/* عنوان الصفحة (الشريط الأزرق) بدون فراغ علوي */
		#page-title{
			margin-top: 0 !important;
			padding-top: 8px;
			padding-bottom: 8px;
		}

		/* أنزل الكارد الأبيض قليلاً لإحساس توازن */
		.container-fullw.bg-white{
			margin-top: 8px;
		}

		/* === رفع/تثبيت السايدبار بدون لمس ملفه === */
		/* بعض القوالب تستخدم أحد هذه المحددات للسايدبار، فغطّيناها كلها */
		aside#sidebar.app-sidebar,
		#sidebar.app-sidebar,
		#sidebar{
			position: fixed !important;
			right: 0;
			top: calc(var(--header-h) - var(--sidebar-lift)) !important;
			height: calc(100vh - (var(--header-h) - var(--sidebar-lift))) !important;
			overflow-y: auto;
			/* نضمن عدم وجود حشوة علوية داخلية قد تعمل فراغ */
			padding-top: 0 !important;
		}
		#sidebar .user-profile{ margin-top: 0 !important; }

		/* في الشاشات الصغيرة عادة السايدبار يكون أوف-كانفاس؛ لا نرفع */
		@media (max-width: 991.98px){
			aside#sidebar.app-sidebar,
			#sidebar.app-sidebar,
			#sidebar{
				top: var(--header-h) !important;
				height: calc(100vh - var(--header-h)) !important;
			}
		}
    </style>
</head>

<body>
<div id="app">
    <?php include('include/sidebar.php'); ?>
    <div class="app-content">
        <?php include('include/header.php'); ?>

        <div class="main-content">
            <div class="wrap-content container" id="container">
                <!-- start: PAGE TITLE -->
                <section id="page-title">
                    <div class="row">
                        <div class="col-sm-8">
                            <h1 class="mainTitle">المسؤول | عرض تفاصيل المريض</h1>
                        </div>
                       
                    </div>
                </section>

                <div class="container-fluid container-fullw bg-white">
                    <div class="row">
                        <div class="col-md-12">
                            <?php
                            $vid = isset($_GET['viewid']) ? intval($_GET['viewid']) : 0;
                            $ret = mysqli_query($con, "SELECT * FROM tblpatient WHERE ID='$vid'");
                            if ($ret && mysqli_num_rows($ret) > 0):
                                while ($row = mysqli_fetch_assoc($ret)):
                            ?>
                            <table class="table table-bordered rtl-table">
                                <tr class="table-title-row">
                                    <td colspan="4">تفاصيل المريض</td>
                                </tr>
                                <tr>
                                    <th>اسم المريض</th>
                                    <td><?php echo htmlspecialchars($row['PatientName']); ?></td>
                                    <th>البريد الالكتروني للمريض</th>
                                    <td><?php echo htmlspecialchars($row['PatientEmail']); ?></td>
                                </tr>
                                <tr>
                                    <th>رقم هاتف المريض</th>
                                    <td><?php echo htmlspecialchars($row['PatientContno']); ?></td>
                                    <th>عنوان المريض</th>
                                    <td><?php echo htmlspecialchars($row['PatientAdd']); ?></td>
                                </tr>
                                <tr>
                                    <th>جنس المريض</th>
                                    <td><?php echo htmlspecialchars($row['PatientGender']); ?></td>
                                    <th>عمر المريض</th>
                                    <td><?php echo htmlspecialchars($row['PatientAge']); ?></td>
                                </tr>
                                <tr>
                                    <th>التاريخ الطبي للمريض (إن وجد)</th>
                                    <td><?php echo htmlspecialchars($row['PatientMedhis']); ?></td>
                                    <th>تاريخ تسجيل المريض</th>
                                    <td><?php echo htmlspecialchars($row['CreationDate']); ?></td>
                                </tr>
                            </table>
                            <?php
                                endwhile;
                            else:
                                echo '<div class="alert alert-warning">لا توجد بيانات لهذا المريض.</div>';
                            endif;
                            ?>

                            <?php
                            $hist = mysqli_query($con, "SELECT * FROM tblmedicalhistory WHERE PatientID='$vid' ORDER BY CreationDate DESC");
                            ?>
                            <table class="table table-bordered rtl-table">
                                <tr class="table-title-row">
                                    <td colspan="7">التاريخ الطبي</td>
                                </tr>
                                <thead>
                                  <tr>
                                      <th class="num">#</th>
                                      <th>ضغط الدم</th>
                                      <th>الوزن</th>
                                      <th>سكر الدم</th>
                                      <th>درجة حرارة الجسم</th>
                                      <th>الوصفة الطبية</th>
                                      <th>تاريخ الزيارة</th>
                                  </tr>
                                </thead>
                                <tbody>
                                <?php
                                $cnt = 1;
                                if ($hist && mysqli_num_rows($hist) > 0):
                                    while ($row = mysqli_fetch_assoc($hist)):
                                ?>
                                  <tr>
                                      <td class="num"><?php echo $cnt; ?></td>
                                      <td><?php echo htmlspecialchars($row['BloodPressure']); ?></td>
                                      <td><?php echo htmlspecialchars($row['Weight']); ?></td>
                                      <td><?php echo htmlspecialchars($row['BloodSugar']); ?></td>
                                      <td><?php echo htmlspecialchars($row['Temperature']); ?></td>
                                      <td><?php echo htmlspecialchars($row['MedicalPres']); ?></td>
                                      <td><?php echo htmlspecialchars($row['CreationDate']); ?></td>
                                  </tr>
                                <?php
                                    $cnt++;
                                    endwhile;
                                else:
                                    echo '<tr><td colspan="7" class="text-center text-muted">لا توجد سجلات تاريخ طبي بعد.</td></tr>';
                                endif;
                                ?>
                                </tbody>
                            </table>

                        </div>
                    </div>
                </div><!-- /.container-fluid -->
            </div>
        </div>
    </div><!-- /.app-content -->
</div><!-- /#app -->

<?php include('include/footer.php'); ?>
<?php include('include/setting.php'); ?>

<!-- Scripts -->
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
jQuery(function() {
    if (window.Main) Main.init();
    if (window.FormElements) FormElements.init();
});
</script>
</body>
</html>
<?php } ?>
