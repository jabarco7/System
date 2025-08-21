<?php
// doctor/appointment-history.php
session_start();
error_reporting(0);
include('include/config.php');

// التحقق من تسجيل الدخول
if (!isset($_SESSION['id']) || strlen($_SESSION['id']) == 0) {
  header('location:logout.php');
  exit();
}

/* تحديد هوية الطبيب */
$doctorId = 0;
$doctor   = null;

$sessionId    = (int)($_SESSION['id'] ?? 0);
$sessionEmail = trim($_SESSION['dlogin'] ?? '');

if ($sessionId > 0) {
  if ($st = $con->prepare("SELECT id, doctorName, specilization FROM doctors WHERE id=? LIMIT 1")) {
    $st->bind_param("i", $sessionId);
    $st->execute();
    $res = $st->get_result();
    $doctor = $res ? $res->fetch_assoc() : null;
    $st->close();
  }
}
if (!$doctor && $sessionEmail !== '') {
  if ($st = $con->prepare("SELECT id, doctorName, specilization FROM doctors WHERE docEmail=? LIMIT 1")) {
    $st->bind_param("s", $sessionEmail);
    $st->execute();
    $res = $st->get_result();
    $doctor = $res ? $res->fetch_assoc() : null;
    $st->close();
  }
}
if ($doctor) {
  $doctorId = (int)$doctor['id'];
}
if ($doctorId <= 0) {
  header('location:logout.php');
  exit;
}

$doctorName     = $doctor['doctorName'];
$specialization = $doctor['specilization'];

/* إلغاء موعد (مع التحقق من المِلْكية) */
if (isset($_GET['cancel']) && isset($_GET['id'])) {
  $aid = (int)$_GET['id'];
  if ($aid > 0) {
    if ($st = $con->prepare("UPDATE appointment SET doctorStatus=0 WHERE id=? AND doctorId=? AND doctorStatus=1")) {
      $st->bind_param("ii", $aid, $doctorId);
      $st->execute();
      $_SESSION['msg'] = ($st->affected_rows > 0) ? "تم إلغاء الموعد بنجاح" : "لا يمكن إلغاء هذا الموعد.";
      $_SESSION['msg_type'] = ($st->affected_rows > 0) ? "success" : "danger";
      $st->close();
    } else {
      $_SESSION['msg'] = "تعذّر تنفيذ طلب الإلغاء حالياً.";
      $_SESSION['msg_type'] = "danger";
    }
  }
  header('Location: ' . $_SERVER['PHP_SELF']);
  exit();
}

/* معالج AJAX للتحقق من المواعيد الجديدة */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'check_new') {
  header('Content-Type: application/json');
  $countStmt = mysqli_prepare($con, "SELECT COUNT(*) as count FROM appointment WHERE doctorId = ?");
  mysqli_stmt_bind_param($countStmt, 'i', $doctorId);
  mysqli_stmt_execute($countStmt);
  $countResult = mysqli_stmt_get_result($countStmt);
  $newCount = mysqli_fetch_assoc($countResult)['count'];
  mysqli_stmt_close($countStmt);

  echo json_encode(['newCount' => $newCount]);
  exit;
}

/* جلب المواعيد + رقم المريض (من tblpatient) */
$appts = [];
if ($st = $con->prepare("
    SELECT
        a.id,
        u.fullName                     AS patientName,
        a.consultancyFees,
        a.appointmentDate,             -- YYYY-MM-DD
        a.appointmentTime,             -- HH:MM[:SS]
        a.postingDate,
        a.userStatus,
        a.doctorStatus,
        COALESCE(p.PatientContno, p2.PatientContno, 'غير محدد') AS patientNumber
    FROM appointment a
    JOIN users u
      ON u.id = a.userId
    LEFT JOIN tblpatient p
      ON p.PatientEmail = u.email AND p.Docid = a.doctorId
    LEFT JOIN tblpatient p2
      ON p2.PatientEmail = u.email AND p2.Docid != a.doctorId
    WHERE a.doctorId = ?
    ORDER BY a.appointmentDate DESC, STR_TO_DATE(a.appointmentTime, '%H:%i') DESC, a.id DESC
")) {
  $st->bind_param("i", $doctorId);
  $st->execute();
  $res = $st->get_result();
  while ($row = $res->fetch_assoc()) {
    $appts[] = $row;
  }
  $st->close();
  echo "<!-- Debug: Doctor ID = $doctorId, Found " . count($appts) . " appointments -->";
} else {
  echo "<!-- Debug: SQL prepare failed: " . $con->error . " -->";
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
  <meta charset="UTF-8">
  <title>الطبيب | سجل المواعيد</title>
  <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="assets/css/hms-unified.css?v=1.0">

  <!-- تنسيق زرّي الفلترة فقط -->
  <style>
    .header-box {
      margin: 20px;
    }
    @keyframes slideIn {
      from {
        transform: translateX(100%);
        opacity: 0;
      }

      to {
        transform: translateX(0);
        opacity: 1;
      }
    }

    .new-appointment-indicator {
      background: #28a745 !important;
      animation: pulse 2s infinite;
    }

    @keyframes pulse {
      0% {
        box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7);
      }

      70% {
        box-shadow: 0 0 0 10px rgba(40, 167, 69, 0);
      }

      100% {
        box-shadow: 0 0 0 0 rgba(40, 167, 69, 0);
      }
    }

    .filters {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      flex-wrap: wrap;
    }

    .seg {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: #fff;
      border: 1px solid #e0ecfb;
      border-radius: 999px;
      padding: 4px;
    }

    .seg-btn {
      border: 0;
      background: transparent;
      padding: 8px 14px;
      border-radius: 999px;
      cursor: pointer;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: .15s ease;
    }

    .seg-btn:hover {
      background: #f3f8ff;
    }

    .seg-btn.active {
      background: #0d6efd;
      color: #fff;
      box-shadow: 0 4px 12px rgba(13, 110, 253, .25);
    }

    .date-pill {
      display: flex;
      align-items: center;
      gap: 8px;
      background: #fff;
      border: 1px solid #e0ecfb;
      border-radius: 999px;
      padding: 6px 10px;
    }

    .date-pill i {
      color: #6e86a5;
    }

    .date-pill input[type="date"] {
      border: 0;
      outline: none;
      height: 34px;
      min-width: 175px;
      background: transparent;
    }

    .date-pill .clear-date {
      border: 0;
      background: transparent;
      padding: 6px 10px;
      border-radius: 999px;
      color: #6c7a92;
      cursor: pointer;
    }

    .date-pill .clear-date:hover {
      background: #f2f6ff;
    }
  </style>
</head>

<body>

  <!-- الهيدر والسايدبار كما هم -->
  <?php include('include/header.php'); ?>
  <?php include('include/sidebar.php'); ?>

  <div class="main-content">
    <div class="container-section">
      <div class="page-header">
        <h1><i class="fa-regular fa-calendar-check me-2"></i>سجل المواعيد</h1>
        <p class="mb-0">جميع مواعيدك مرتبة من الأحدث للأقدم</p>
      </div>
    </div>

    <!-- فلاش -->
    <?php if (!empty($_SESSION['msg'])): ?>
      <div class="alert <?php echo (($_SESSION['msg_type'] ?? '') === 'danger') ? 'alert-danger' : 'alert-success'; ?>">
        <i class="fa-solid <?php echo (($_SESSION['msg_type'] ?? '') === 'danger') ? 'fa-circle-exclamation' : 'fa-circle-check'; ?>"></i>
        <?php echo htmlspecialchars($_SESSION['msg']); ?>
      </div>
      <?php $_SESSION['msg'] = '';
      $_SESSION['msg_type'] = ''; ?>
    <?php endif; ?>

    <div class="container-section">
      <!-- هيدر داخلي (اسم الطبيب/التخصص) -->
      <div class="header-box">
        <h2 class="header-title"><i class="fa-solid fa-list"></i> سجل المواعيد</h2>
        <div class="user-info">
          <div class="user-avatar">د</div>
          <div>
            <div><strong>د. <?php echo htmlspecialchars($doctorName); ?></strong></div>
            <div class="muted"><?php echo htmlspecialchars($specialization); ?></div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <div class="filters">
            <!-- فلترة الحالة (أزرار مجزأة) -->
            <div class="seg" id="statusSeg">
              <button class="seg-btn active" data-value="all">
                <i class="fa fa-layer-group"></i> <span>كل الحالات</span>
              </button>
              <button class="seg-btn" data-value="active">نشطة</button>
              <button class="seg-btn" data-value="pending">قيد الانتظار</button>
              <button class="seg-btn" data-value="cancelled">ملغية</button>
            </div>

            <!-- فلترة التاريخ (كبسولة مع زر مسح) -->
            <div class="date-pill">
              <i class="fa-regular fa-calendar"></i>
              <input type="date" id="dateFilter" />
              <button class="clear-date" id="clearDate" title="مسح التاريخ">
                <i class="fa fa-xmark"></i>
              </button>
            </div>
          </div>
        </div>

        <?php if (count($appts)): ?>
          <div class="table-container">
            <table class="table" id="apptTable">
              <thead>
                <tr>
                  <th>#</th>
                  <th>اسم المريض</th>
                  <th>رقم المريض</th>
                  <!-- <th>التخصص</th> -->
                  <th>الرسوم</th>
                  <th>التاريخ/الوقت</th>
                  <th>أنشئ</th>
                  <th>الحالة</th>
                  <th>إجراء</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $i = 1;
                foreach ($appts as $row):
                  $isActive   = ((int)$row['userStatus'] === 1 && (int)$row['doctorStatus'] === 1);
                  $isUserCan  = ((int)$row['userStatus'] === 0);
                  $isDocCan   = ((int)$row['doctorStatus'] === 0);

                  if ($isActive) {
                    $stTxt = 'نشط';
                    $stCls = 'b-ok';
                    $flt   = 'active';
                  } elseif ($isUserCan) {
                    $stTxt = 'ملغي من المريض';
                    $stCls = 'b-bad';
                    $flt   = 'cancelled';
                  } elseif ($isDocCan) {
                    $stTxt = 'ملغي منك';
                    $stCls = 'b-bad';
                    $flt   = 'cancelled';
                  } else {
                    $stTxt = 'قيد الانتظار';
                    $stCls = 'b-warn';
                    $flt   = 'pending';
                  }

                  $tm   = substr($row['appointmentTime'], 0, 5);
                  $date = $row['appointmentDate']; // YYYY-MM-DD
                  $pnum = trim($row['patientNumber'] ?? '');
                ?>
                  <tr data-status="<?php echo $flt; ?>" data-date="<?php echo htmlspecialchars($date); ?>">
                    <td><?php echo $i++; ?>.</td>
                    <td><?php echo htmlspecialchars($row['patientName']); ?></td>
                    <td><?php echo htmlspecialchars($pnum !== '' ? $pnum : '—'); ?></td>
                    <td><?php echo htmlspecialchars($row['consultancyFees']); ?> ر.س</td>
                    <td><?php echo htmlspecialchars($date . ' - ' . $tm); ?></td>
                    <td><?php echo htmlspecialchars($row['postingDate']); ?></td>
                    <td><span class="badge <?php echo $stCls; ?>"><?php echo $stTxt; ?></span></td>
                    <td>
                      <?php if ($isActive): ?>
                        <a class="btn btn-cancel"
                          href="<?php echo $_SERVER['PHP_SELF'] . '?cancel=1&id=' . (int)$row['id']; ?>"
                          onclick="return confirm('هل أنت متأكد من إلغاء هذا الموعد؟');">
                          <i class="fa-solid fa-xmark"></i> إلغاء
                        </a>
                      <?php else: ?>
                        <span class="muted">—</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="p-4 text-center muted">
            <i class="fa-regular fa-calendar-xmark" style="font-size:38px;"></i>
            <div class="mt-2">لا توجد مواعيد مسجلة حتى الآن.</div>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <script>
      // حالة وتاريخ حاليين
      let currentStatus = 'all';
      let currentDate = '';

      const rows = Array.from(document.querySelectorAll('#apptTable tbody tr'));
      const statusSeg = document.getElementById('statusSeg');
      const dateInput = document.getElementById('dateFilter');
      const clearDate = document.getElementById('clearDate');

      // تحديث تلقائي كل 30 ثانية للتحقق من المواعيد الجديدة
      let lastAppointmentCount = rows.length;

      function checkForNewAppointments() {
        fetch(window.location.href + '&ajax=check_new')
          .then(response => response.json())
          .then(data => {
            if (data.newCount > lastAppointmentCount) {
              // إظهار إشعار بوجود مواعيد جديدة
              showNewAppointmentNotification(data.newCount - lastAppointmentCount);
            }
          })
          .catch(error => console.log('تحقق من المواعيد الجديدة:', error));
      }

      function showNewAppointmentNotification(count) {
        const notification = document.createElement('div');
        notification.style.cssText = `
          position: fixed; top: 20px; right: 20px; z-index: 1000;
          background: #28a745; color: white; padding: 15px 20px;
          border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.3);
          animation: slideIn 0.3s ease-out;
        `;
        notification.innerHTML = `
          <i class="fa fa-bell"></i>
          <strong>موعد جديد!</strong>
          تم حجز ${count} موعد جديد.
          <button onclick="location.reload()" style="background: white; color: #28a745; border: none; padding: 5px 10px; border-radius: 4px; margin-left: 10px; cursor: pointer;">
            تحديث الصفحة
          </button>
          <button onclick="this.parentElement.remove()" style="background: transparent; color: white; border: none; padding: 5px; cursor: pointer;">×</button>
        `;
        document.body.appendChild(notification);

        // إزالة الإشعار تلقائياً بعد 10 ثوان
        setTimeout(() => {
          if (notification.parentElement) {
            notification.remove();
          }
        }, 10000);
      }

      // تشغيل التحقق كل 30 ثانية
      setInterval(checkForNewAppointments, 30000);

      function applyFilters() {
        rows.forEach(tr => {
          const s = tr.dataset.status; // active | pending | cancelled
          const d = tr.dataset.date; // YYYY-MM-DD
          const okStatus = (currentStatus === 'all') || (s === currentStatus);
          const okDate = (!currentDate) || (d === currentDate);
          tr.style.display = (okStatus && okDate) ? '' : 'none';
        });
      }

      // أزرار الحالة
      statusSeg.querySelectorAll('.seg-btn').forEach(btn => {
        btn.addEventListener('click', function() {
          statusSeg.querySelectorAll('.seg-btn').forEach(b => b.classList.remove('active'));
          this.classList.add('active');
          currentStatus = this.dataset.value;
          applyFilters();
        });
      });

      // اختيار التاريخ
      dateInput.addEventListener('change', function() {
        currentDate = this.value || '';
        applyFilters();
      });

      // مسح التاريخ
      clearDate.addEventListener('click', function() {
        dateInput.value = '';
        currentDate = '';
        applyFilters();
      });

      // تهيئة
      applyFilters();
    </script>

  </div> <!-- /.main-content -->

</body>

</html>