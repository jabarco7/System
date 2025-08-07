<?php
session_start();
error_reporting(0);
include('include/config.php');
mysqli_set_charset($con, "utf8");
if (strlen($_SESSION['id']) == 0) {
	header('location:logout.php');
	exit();
}

// إحصائيات
$total = $active = $cancelByPatient = $cancelByDoctor = 0;
$sqlStats = mysqli_query($con, "SELECT userStatus, doctorStatus FROM appointment");
while ($row = mysqli_fetch_array($sqlStats)) {
	$total++;
	if ($row['userStatus'] == 1 && $row['doctorStatus'] == 1) $active++;
	if ($row['userStatus'] == 0 && $row['doctorStatus'] == 1) $cancelByPatient++;
	if ($row['userStatus'] == 1 && $row['doctorStatus'] == 0) $cancelByDoctor++;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
	<meta charset="UTF-8">
	<title>تاريخ المواعيد</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
	<style>
		body {
			background-color: #f5f7fa;
			font-family: 'Cairo', sans-serif;
		}
		.sidebar {
			position: fixed;
			width: 250px;
			height: 100vh;
			background-color: #1e293b;
			color: white;
			padding-top: 20px;
		}
		.sidebar a {
			color: #fff;
			display: block;
			padding: 12px 20px;
			text-decoration: none;
			font-size: 15px;
		}
		.sidebar a:hover {
			background-color: #334155;
		}
		.main {
			margin-right: 250px;
			padding: 20px;
		}
		.header {
			background: linear-gradient(90deg, #3b82f6, #60a5fa);
			padding: 20px;
			color: white;
			border-radius: 10px;
			margin-bottom: 30px;
		}
		.stats-box {
			background: #fff;
			border-radius: 8px;
			box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
			padding: 20px;
			text-align: center;
			margin-bottom: 20px;
		}
		.stats-box i {
			font-size: 30px;
			margin-bottom: 10px;
			color: #3b82f6;
		}
		.search-bar input {
			width: 100%;
			padding: 10px;
			border-radius: 8px;
			border: 1px solid #ccc;
		}
	</style>
</head>

<body>
	<div class="sidebar">
		<h4 class="text-center mb-4">المسؤول</h4>
		<a href="#"><i class="fas fa-home"></i> لوحة التحكم</a>
		<a href="#"><i class="fas fa-user-md"></i> الأطباء</a>
		<a href="#"><i class="fas fa-users"></i> إدارة المرضى</a>
		<a href="#"><i class="fas fa-calendar"></i> تاريخ المواعيد</a>
	</div>
	<div class="main">
		<div class="header">
			<h2>إدارة المواعيد</h2>
		</div>

		<div class="row text-center">
			<div class="col-md-3">
				<div class="stats-box">
					<i class="fas fa-calendar-check"></i>
					<h6>إجمالي المواعيد</h6>
					<p><?php echo $total; ?></p>
				</div>
			</div>
			<div class="col-md-3">
				<div class="stats-box">
					<i class="fas fa-user-check"></i>
					<h6>مواعيد نشطة</h6>
					<p><?php echo $active; ?></p>
				</div>
			</div>
			<div class="col-md-3">
				<div class="stats-box">
					<i class="fas fa-user-slash"></i>
					<h6>أُلغي من المريض</h6>
					<p><?php echo $cancelByPatient; ?></p>
				</div>
			</div>
			<div class="col-md-3">
				<div class="stats-box">
					<i class="fas fa-user-md-slash"></i>
					<h6>أُلغي من الطبيب</h6>
					<p><?php echo $cancelByDoctor; ?></p>
				</div>
			</div>
		</div>

		<div class="card mt-4">
			<div class="card-body">
				<div class="mb-3 search-bar">
					<input type="text" placeholder="ابحث باسم المريض أو رقم الهاتف...">
				</div>
				<table class="table table-hover align-middle">
					<thead class="table-light">
						<tr>
							<th>#</th>
							<th>الطبيب</th>
							<th>المريض</th>
							<th>التخصص</th>
							<th>الرسوم</th>
							<th>التاريخ / الوقت</th>
							<th>تاريخ الإنشاء</th>
							<th>الحالة</th>
							<th>الإجراء</th>
						</tr>
					</thead>
					<tbody>
						<?php
						$cnt = 1;
						$sql = mysqli_query($con, "SELECT doctors.doctorName AS docname, users.fullName AS pname, appointment.* FROM appointment JOIN doctors ON doctors.id=appointment.doctorId JOIN users ON users.id=appointment.userId");
						while ($row = mysqli_fetch_array($sql)) {
						?>
							<tr>
								<td><?php echo $cnt++; ?></td>
								<td><?php echo $row['docname - appointment-history.php:156']; ?></td>
								<td><?php echo $row['pname - appointment-history.php:157']; ?></td>
								<td><?php echo $row['doctorSpecialization - appointment-history.php:158']; ?></td>
								<td><?php echo $row['consultancyFees - appointment-history.php:159']; ?></td>
								<td><?php echo $row['appointmentDate - appointment-history.php:160'] . ' / ' . $row['appointmentTime']; ?></td>
								<td><?php echo $row['postingDate - appointment-history.php:161']; ?></td>
								<td>
									<?php
									if ($row['userStatus - appointment-history.php:164'] == 1 && $row['doctorStatus'] == 1) echo "نشط";
									elseif ($row['userStatus - appointment-history.php:165'] == 0 && $row['doctorStatus'] == 1) echo "أُلغي من المريض";
									elseif ($row['userStatus - appointment-history.php:166'] == 1 && $row['doctorStatus'] == 0) echo "أُلغي من الطبيب";
									?>
								</td>
								<td><a class="btn btn-sm btn-outline-primary" href="#"><i class="fas fa-eye"></i> عرض</a></td>
							</tr>
						<?php } ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</body>

</html>
