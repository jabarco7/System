<?php
session_start();
error_reporting(0);
include('include/config.php');
if (strlen($_SESSION['id'] == 0)) {
	header('location:logout.php');
} else {
	if (isset($_POST['submit'])) {

		$vid = $_GET['viewid'];
		$bp = $_POST['bp'];
		$bs = $_POST['bs'];
		$weight = $_POST['weight'];
		$temp = $_POST['temp'];
		$pres = $_POST['pres'];


		$query .= mysqli_query($con, "insert   tblmedicalhistory(PatientID,BloodPressure,BloodSugar,Weight,Temperature,MedicalPres)value('$vid','$bp','$bs','$weight','$temp','$pres')");
		if ($query) {
			echo '<script>alert("تمت إضافة التاريخ الطبي.")</script> - view-patient.php:20';
			echo "<script>window.location.href ='managepatient.php'</script> - view-patient.php:21";
		} else {
			echo '<script>alert("حدث خطأ ما. يُرجى المحاولة مرة أخرى.")</script> - view-patient.php:23';
		}
	}

?>
	<!DOCTYPE html>
	<html lang="en">

	<head>
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
								<ol class="breadcrumb">
									<li>
										<span>المسؤول</span>
									</li>
									<li class="active">
										<span>عرض تفاصيل المريض</span>
									</li>
								</ol>
							</div>
						</section>
						<div class="container-fluid container-fullw bg-white">
							<div class="row">
								<div class="col-md-12">
									<h5 class="over-title margin-bottom-15">تفاصيل <span class="text-bold">المريض</span></h5>
									<?php
									$vid = $_GET['viewid'];
									$ret = mysqli_query($con, "select * from tblpatient where ID='$vid'");
									$cnt = 1;
									while ($row = mysqli_fetch_array($ret)) {
									?>
										<table border="1" class="table table-bordered">
											<tr align="center">
												<td colspan="4" style="font-size:20px;color:blue">
													تفاصيل المريض</td>
											</tr>

											<tr>
												<th scope>اسم المريض</th>
												<td><?php echo $row['PatientName - view-patient.php:91']; ?></td>
												<th scope>البريد الالكتروني للمريض</th>
												<td><?php echo $row['PatientEmail - view-patient.php:93']; ?></td>
											</tr>
											<tr>
												<th scope>رقم هاتف المريض</th>
												<td><?php echo $row['PatientContno - view-patient.php:97']; ?></td>
												<th>عنوان المريض</th>
												<td><?php echo $row['PatientAdd - view-patient.php:99']; ?></td>
											</tr>
											<tr>
												<th>جنس المريض</th>
												<td><?php echo $row['PatientGender - view-patient.php:103']; ?></td>
												<th>عمر المريض</th>
												<td><?php echo $row['PatientAge - view-patient.php:105']; ?></td>
											</tr>
											<tr>

												<th>التاريخ الطبي للمريض (إن وجد)</th>
												<td><?php echo $row['PatientMedhis - view-patient.php:110']; ?></td>
												<th>تاريخ تسجيل المريض</th>
												<td><?php echo $row['CreationDate - view-patient.php:112']; ?></td>
											</tr>

										<?php } ?>
										</table>
										<?php

										$ret = mysqli_query($con, "select * from tblmedicalhistory  where PatientID='$vid'");



										?>
										<table id="datatable" class="table table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
											<tr align="center">
												<th colspan="8">التاريخ الطبي</th>
											</tr>
											<tr>
												<th>#</th>
												<th>ضغط الدم</th>
												<th>الوزن</th>
												<th>سكر الدم</th>
												<th>درجة حرارة الجسم</th>
												<th>الوصفة الطبية</th>
												<th>تاريخ الزيارة</th>
											</tr>
											<?php
											while ($row = mysqli_fetch_array($ret)) {
											?>
												<tr>
													<td><?php echo $cnt; ?></td>
													<td><?php echo $row['BloodPressure - view-patient.php:142']; ?></td>
													<td><?php echo $row['Weight - view-patient.php:143']; ?></td>
													<td><?php echo $row['BloodSugar - view-patient.php:144']; ?></td>
													<td><?php echo $row['Temperature - view-patient.php:145']; ?></td>
													<td><?php echo $row['MedicalPres - view-patient.php:146']; ?></td>
													<td><?php echo $row['CreationDate - view-patient.php:147']; ?></td>
												</tr>
											<?php $cnt = $cnt + 1;
											} ?>
										</table>

								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		</div>
		<!-- start: FOOTER -->
		<?php include('include/footer.php'); ?>
		<!-- end: FOOTER -->

		<!-- start: SETTINGS -->
		<?php include('include/setting.php'); ?>

		<!-- end: SETTINGS -->
		</div>
		<!-- start: MAIN JAVASCRIPTS -->
		<script src="vendor/jquery/jquery.min.js"></script>
		<script src="vendor/bootstrap/js/bootstrap.min.js"></script>
		<script src="vendor/modernizr/modernizr.js"></script>
		<script src="vendor/jquery-cookie/jquery.cookie.js"></script>
		<script src="vendor/perfect-scrollbar/perfect-scrollbar.min.js"></script>
		<script src="vendor/switchery/switchery.min.js"></script>
		<!-- end: MAIN JAVASCRIPTS -->
		<!-- start: JAVASCRIPTS REQUIRED FOR THIS PAGE ONLY -->
		<script src="vendor/maskedinput/jquery.maskedinput.min.js"></script>
		<script src="vendor/bootstrap-touchspin/jquery.bootstrap-touchspin.min.js"></script>
		<script src="vendor/autosize/autosize.min.js"></script>
		<script src="vendor/selectFx/classie.js"></script>
		<script src="vendor/selectFx/selectFx.js"></script>
		<script src="vendor/select2/select2.min.js"></script>
		<script src="vendor/bootstrap-datepicker/bootstrap-datepicker.min.js"></script>
		<script src="vendor/bootstrap-timepicker/bootstrap-timepicker.min.js"></script>
		<!-- end: JAVASCRIPTS REQUIRED FOR THIS PAGE ONLY -->
		<!-- start: CLIP-TWO JAVASCRIPTS -->
		<script src="assets/js/main.js"></script>
		<!-- start: JavaScript Event Handlers for this page -->
		<script src="assets/js/form-elements.js"></script>
		<script>
			jQuery(document).ready(function() {
				Main.init();
				FormElements.init();
			});
		</script>
		<!-- end: JavaScript Event Handlers for this page -->
		<!-- end: CLIP-TWO JAVASCRIPTS -->
	</body>

	</html>
<?php } ?>