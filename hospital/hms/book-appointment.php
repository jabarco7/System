<?php
session_start();
include('include/config.php');
include('include/checklogin.php');
check_login();

// دالة بسيطة لجلب معلومات الطبيب (الاسم والرسوم) عند التأكيد
function getDoctorInfo($con, $doctorId)
{
	$doctorId = (int)$doctorId;
	$res = mysqli_query($con, "SELECT doctorName, docFees FROM doctors WHERE id = $doctorId LIMIT 1");
	return $res ? mysqli_fetch_assoc($res) : null;
}

$step = 'form'; // form | confirm | done
$msg  = '';

// تنفيذ الإدخال النهائي بعد التأكيد
if (isset($_POST['confirm'])) {
	$specilization = $_POST['Doctorspecialization'] ?? '';
	$doctorid      = $_POST['doctor'] ?? '';
	$userid        = $_SESSION['id'];
	$fees          = $_POST['fees'] ?? '';
	$appdate       = $_POST['appdate'] ?? '';
	$time          = $_POST['apptime'] ?? '';
	$userstatus    = 1;
	$docstatus     = 1;

	$q = mysqli_query($con, "INSERT INTO appointment(doctorSpecialization,doctorId,userId,consultancyFees,appointmentDate,appointmentTime,userStatus,doctorStatus)
                             VALUES(
                                '" . mysqli_real_escape_string($con, $specilization) . "',
                                '" . (int)$doctorid . "',
                                '" . (int)$userid . "',
                                '" . mysqli_real_escape_string($con, $fees) . "',
                                '" . mysqli_real_escape_string($con, $appdate) . "',
                                '" . mysqli_real_escape_string($con, $time) . "',
                                '$userstatus','$docstatus'
                             )");
	if ($q) {
		$step = 'done';
		$msg  = '✅ تم حجز موعدك بنجاح';
	} else {
		$step = 'form';
		$msg  = '❌ حدث خطأ أثناء الحجز، حاول لاحقًا';
	}
}
// عرض صفحة التأكيد قبل الحفظ
elseif (isset($_POST['preview'])) {
	// تأكد من المدخلات
	$specilization = trim($_POST['Doctorspecialization'] ?? '');
	$doctorid      = trim($_POST['doctor'] ?? '');
	$fees          = trim($_POST['fees'] ?? '');
	$appdate       = trim($_POST['appdate'] ?? '');
	$time          = trim($_POST['apptime'] ?? '');

	if ($specilization !== '' && $doctorid !== '' && $fees !== '' && $appdate !== '' && $time !== '') {
		$doctorInfo = getDoctorInfo($con, $doctorid);
		$doctorName = $doctorInfo['doctorName'] ?? '—';
		$docFees    = $doctorInfo['docFees']    ?? $fees;

		$step = 'confirm';
	} else {
		$step = 'form';
		$msg  = '⚠️ الرجاء تعبئة جميع الحقول أولاً';
	}
} else {
	$step = 'form';
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
	<meta charset="utf-8" />
	<title>حجز موعد</title>

	<link rel="stylesheet" href="vendor/bootstrap/css/bootstrap.min.css">
	<link rel="stylesheet" href="vendor/fontawesome/css/font-awesome.min.css">
	<link href="vendor/bootstrap-datepicker/bootstrap-datepicker3.standalone.min.css" rel="stylesheet">
	<link href="vendor/bootstrap-timepicker/bootstrap-timepicker.min.css" rel="stylesheet">

	<style>
		body {
			background: #f0f5f9;
			margin-top: 30px;
		}

		.page-head {
			background: linear-gradient(90deg, #3498db, #4aa8e0);
			color: #fff;
			border-radius: 12px;
			padding: 18px 20px;
			margin: 20px auto;
			max-width: 900px;
			text-align: center
			
		}

		.container-narrow {
			max-width: 900px;
			margin: 0 auto 40px;
		}

		.card-clean {
			background: #fff;
			border-radius: 14px;
			box-shadow: 0 6px 20px rgba(0, 0, 0, .08);
			padding: 24px
		}

		.form-label {
			font-weight: 600
		}

		.btn-confirm {
			background: linear-gradient(90deg, #27ae60, #2ecc71);
			border: none;
			color: #fff
		}

		.btn-confirm:hover {
			opacity: .95
		}

		.btn-edit {
			background: #eef2f7;
			border: 1px solid #dfe7f3
		}

		.summary-item {
			display: flex;
			justify-content: space-between;
			border-bottom: 1px dashed #e9eef5;
			padding: 10px 0
		}

		.summary-item:last-child {
			border-bottom: 0
		}

		.pill {
			background: #eef7ff;
			color: #0d6efd;
			border-radius: 30px;
			padding: 2px 10px
		}
	</style>

	<script>
		// AJAX
		function getdoctor(val) {
			$.ajax({
				type: "POST",
				url: "get_doctor.php",
				data: 'specilizationid=' + encodeURIComponent(val),
				success: function(data) {
					$("#doctor").html(data);
					$("#fees").html('<option value="">—</option>');
				}
			});
		}

		function getfee(val) {
			$.ajax({
				type: "POST",
				url: "get_doctor.php",
				data: 'doctor=' + encodeURIComponent(val),
				success: function(data) {
					$("#fees").html(data);
				}
			});
		}
	</script>
</head>

<body>
	<div id="app">

		<div class="page-head">
			<h4 class="m-0">حجز موعد جديد</h4>
			<small class="opacity-75">
				<?php
				echo ($step === 'form') ? 'الخطوة 1 من 2: أدخل البيانات' : (($step === 'confirm') ? 'الخطوة 2 من 2: تأكيد الموعد' : 'تم الحجز');
				?>
			</small>
		</div>

		<div class="container-narrow">
			<?php if (!empty($msg)): ?>
				<div class="alert <?php echo (strpos($msg, '✅') !== false) ? 'alert-success' : 'alert-warning'; ?>">
					<?php echo htmlentities($msg); ?>
				</div>
			<?php endif; ?>

			<?php if ($step === 'form'): ?>
				<div class="card-clean">
					<form method="post" autocomplete="off">
						<div class="row g-3">
							<div class="col-md-6">
								<label class="form-label">تخصص الدكتور</label>
								<select name="Doctorspecialization" class="form-control" onchange="getdoctor(this.value);" required>
									<option value="">حدد التخصص</option>
									<?php
									$ret = mysqli_query($con, "SELECT * FROM doctorspecilization ORDER BY specilization ASC");
									while ($row = mysqli_fetch_array($ret)) { ?>
										<option value="<?php echo htmlentities($row['specilization']); ?>">
											<?php echo htmlentities($row['specilization']); ?>
										</option>
									<?php } ?>
								</select>
							</div>

							<div class="col-md-6">
								<label class="form-label">الأطباء</label>
								<select name="doctor" id="doctor" class="form-control" onchange="getfee(this.value);" required>
									<option value="">اختر الطبيب</option>
								</select>
							</div>

							<div class="col-md-4">
								<label class="form-label">رسوم الاستشارة</label>
								<select name="fees" id="fees" class="form-control" required>
									<option value="">—</option>
								</select>
							</div>

							<div class="col-md-4">
								<label class="form-label">التاريخ</label>
								<input class="form-control datepicker" name="appdate" required data-date-format="yyyy-mm-dd" placeholder="yyyy-mm-dd">
							</div>

							<div class="col-md-4">
								<label class="form-label">الوقت</label>
								<input class="form-control" name="apptime" id="timepicker1" required placeholder="مثال: 10:00 PM">
							</div>
						</div>

						<div class="mt-4 d-flex justify-content-center gap-2">
							<button type="submit" name="preview" class="btn btn-primary">
								<i class="fa fa-eye"></i> مراجعة البيانات
							</button>
						</div>
					</form>
				</div>

			<?php elseif ($step === 'confirm'):
				// نعيد قراءة المدخلات للعرض
				$specilization = htmlspecialchars($_POST['Doctorspecialization'], ENT_QUOTES, 'UTF-8');
				$doctorid      = (int)$_POST['doctor'];
				$fees          = htmlspecialchars($_POST['fees'], ENT_QUOTES, 'UTF-8');
				$appdate       = htmlspecialchars($_POST['appdate'], ENT_QUOTES, 'UTF-8');
				$time          = htmlspecialchars($_POST['apptime'], ENT_QUOTES, 'UTF-8');

				$doctorInfo = getDoctorInfo($con, $doctorid);
				$doctorName = htmlspecialchars($doctorInfo['doctorName'] ?? '—', ENT_QUOTES, 'UTF-8');
				$docFees    = htmlspecialchars($doctorInfo['docFees'] ?? $fees, ENT_QUOTES, 'UTF-8');
			?>
				<div class="card-clean">
					<h5 class="mb-3"><i class="fa fa-clipboard-check"></i> تأكيد الموعد</h5>

					<div class="summary-item">
						<div>التخصص</div>
						<div class="pill"><?php echo $specilization; ?></div>
					</div>
					<div class="summary-item">
						<div>الطبيب</div>
						<div><?php echo $doctorName; ?></div>
					</div>
					<div class="summary-item">
						<div>الرسوم</div>
						<div><?php echo $docFees; ?> ر.س</div>
					</div>
					<div class="summary-item">
						<div>التاريخ</div>
						<div><?php echo $appdate; ?></div>
					</div>
					<div class="summary-item">
						<div>الوقت</div>
						<div><?php echo $time; ?></div>
					</div>

					<form method="post" class="mt-4 d-flex justify-content-between">
						<!-- قيم مخفية لإعادة الإرسال -->
						<input type="hidden" name="Doctorspecialization" value="<?php echo $specilization; ?>">
						<input type="hidden" name="doctor" value="<?php echo $doctorid; ?>">
						<input type="hidden" name="fees" value="<?php echo $docFees; ?>">
						<input type="hidden" name="appdate" value="<?php echo $appdate; ?>">
						<input type="hidden" name="apptime" value="<?php echo $time; ?>">

						<button type="submit" name="edit" value="1" class="btn btn-edit">
							<i class="fa fa-pen"></i> تعديل البيانات
						</button>
						<button type="submit" name="confirm" value="1" class="btn btn-confirm">
							<i class="fa fa-check"></i> تأكيد نهائي
						</button>
					</form>
				</div>

			<?php elseif ($step === 'done'): ?>
				<div class="card-clean text-center">
					<h5 class="mb-3">تم الحجز 🎉</h5>
					<p>تم حفظ موعدك بنجاح </p>
					<div class="mt-3 d-flex justify-content-center gap-2">
						<a href="book-appointment.php" class="btn btn-outline-primary"><i class="fa fa-plus"></i> حجز موعد جديد</a>
						<a href="appointment-history.php" class="btn btn-primary"><i class="fa fa-list-ul"></i> الذهاب إلى حجوزاتي</a>
					</div>
				</div>
			<?php endif; ?>
		

	<script src="vendor/jquery/jquery.min.js"></script>
	<script src="vendor/bootstrap/js/bootstrap.min.js"></script>
	<script src="vendor/bootstrap-datepicker/bootstrap-datepicker.min.js"></script>
	<script src="vendor/bootstrap-timepicker/bootstrap-timepicker.min.js"></script>
	<script>
		jQuery(function() {
			// لو رجعت من زر "تعديل البيانات"، رجّع القيم للحقول (fallback بسيط)
			<?php if (isset($_POST['edit'])): ?>
				const prev = <?php
								// نبني كائن JS بالقيم السابقة
								$data = [
									'Doctorspecialization' => $_POST['Doctorspecialization'] ?? '',
									'doctor'               => $_POST['doctor'] ?? '',
									'fees'                 => $_POST['fees'] ?? '',
									'appdate'              => $_POST['appdate'] ?? '',
									'apptime'              => $_POST['apptime'] ?? '',
								];
								echo json_encode($data, JSON_UNESCAPED_UNICODE);
								?>;
				// نحاول تعبئة الحقول بعد ما تُبنى الصفحة (ممكن ما تنجح 100% لأن الأطباء يُجلبون AJAX)
				setTimeout(function() {
					$('select[name="Doctorspecialization"]').val(prev.Doctorspecialization).trigger('change');
					// بعد ما يتعبأ الأطباء عبر AJAX، نختار الطبيب والرسوم
					setTimeout(function() {
						$('#doctor').val(prev.doctor).trigger('change');
						setTimeout(function() {
							$('#fees').val(prev.fees);
						}, 300);
					}, 400);
					$('input[name="appdate"]').val(prev.appdate);
					$('input[name="apptime"]').val(prev.apptime);
				}, 300);
			<?php endif; ?>

			$('.datepicker').datepicker({
				format: 'yyyy-mm-dd',
				startDate: new Date(),
				autoclose: true
			});
			$('#timepicker1').timepicker({
				minuteStep: 5,
				showMeridian: true,
				defaultTime: false
			});
		});
	</script>
</body>

</html>