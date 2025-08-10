<?php
session_start();
error_reporting(0);
include('include/config.php');

if (strlen($_SESSION['id']) == 0) {
    header('location:logout.php');
    exit();
} else {

    // ====== معالج AJAX لإرجاع بيانات المريض بشكل JSON ======
    if (isset($_GET['ajax']) && $_GET['ajax'] === 'patient' && isset($_GET['id'])) {
        header('Content-Type: application/json; charset=utf-8');
        $id = (int) $_GET['id'];

        $stmt = mysqli_prepare($con, "SELECT ID, PatientName, PatientContno, PatientEmail, PatientGender, PatientAdd, CreationDate, UpdationDate FROM tblpatient WHERE ID = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;

        if ($row) {
            echo json_encode(['ok' => true, 'data' => $row], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(404);
            echo json_encode(['ok' => false, 'msg' => 'المريض غير موجود'], JSON_UNESCAPED_UNICODE);
        }
        exit();
    }
    // ====== نهاية معالج AJAX ======

    // Pagination variables
    $records_per_page = 10;
    $page   = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $page   = max($page, 1);
    $offset = ($page - 1) * $records_per_page;

    // Get total number of patients
    $total_records_query  = mysqli_query($con, "SELECT COUNT(*) AS total FROM tblpatient");
    $total_records_result = mysqli_fetch_assoc($total_records_query);
    $total_records        = (int)$total_records_result['total'];
    $total_pages          = ($total_records > 0) ? ceil($total_records / $records_per_page) : 1;

    // Fetch patients for current page
    $cnt = ($page - 1) * $records_per_page + 1;
    $sql = mysqli_query($con, "SELECT * FROM tblpatient ORDER BY ID DESC LIMIT $records_per_page OFFSET $offset");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>المسؤول | عرض المرضى</title>

    <!-- Bootstrap 5 RTL -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">

    <style>
        :root { --primary:#3498db; --secondary:#2c3e50; --success:#27ae60; --info:#2980b9; --warning:#f39c12; --light:#f8f9fa; --dark:#343a40 }
        body{font-family:'Tajawal',sans-serif;background:linear-gradient(135deg,#f5f7fa,#e4edf9);min-height:100vh;padding-top:65px}
        .app-content{margin-right:20px;transition:margin-right .3s ease}
        .main-content{padding-top:40px}
        .page-header{background:linear-gradient(90deg,var(--primary),#4aa8e0);color:#fff;padding:25px 30px;border-radius:10px;margin-bottom:25px;box-shadow:0 5px 15px rgba(0,0,0,.1)}
        .page-header h1{font-weight:700;margin-bottom:0}
        .stats-container{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px;margin-bottom:30px}
        .stat-card{position:relative;overflow:hidden;padding:25px;border-radius:10px;text-align:center;box-shadow:0 5px 15px rgba(0,0,0,.05);transition:transform .3s;background:#fff}
        .stat-card:hover{transform:translateY(-5px);box-shadow:0 8px 25px rgba(0,0,0,.1)}
        .stat-card i{font-size:2.5rem;margin-bottom:15px}
        .stat-card h3{font-weight:700;margin-bottom:5px;font-size:1.8rem}
        .stat-card p{color:#6c757d;margin-bottom:0;font-size:1.05rem}
        .card{border:none;border-radius:10px;box-shadow:0 5px 15px rgba(0,0,0,.05);margin-bottom:25px;overflow:hidden}
        .card-header{background:#fff;border-bottom:1px solid rgba(0,0,0,.05);padding:15px 20px;font-weight:600;font-size:1.1rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:15px}
        .table-container{overflow-x:auto}
        .table{width:100%;border-collapse:separate;border-spacing:0}
        .table th{background:var(--primary);color:#fff;font-weight:600;padding:15px}
        .table td{padding:12px 15px;border-bottom:1px solid rgba(0,0,0,.05)}
        .table tr:nth-child(even){background:#f8fafc}
        .table tr:hover{background:rgba(52,152,219,.05)}
        .action-btn{padding:6px 12px;border-radius:5px;margin-left:5px;font-size:.85rem;transition:all .3s;display:inline-flex;align-items:center;gap:5px;border:1px solid rgba(52,152,219,.3);background:rgba(52,152,219,.15);color:var(--primary)}
        .action-btn:hover{background:rgba(52,152,219,.3)}
        .search-box{display:flex !important;gap:10px;flex-wrap:wrap}
        .search-box input{width:300px;max-width:100%;border-radius:30px;padding:8px 20px}
        .search-box .btn{border-radius:30px;padding:8px 20px}
        .no-records{text-align:center;padding:40px;color:#6c757d}
        .no-records i{font-size:3rem;margin-bottom:15px;color:#d1d8e0}
        .pagination-container{display:flex;justify-content:space-between;align-items:center;margin-top:30px;flex-wrap:wrap;background:#fff;padding:15px;border-radius:10px;box-shadow:0 3px 10px rgba(0,0,0,.08)}
        .pagination{display:flex;justify-content:center;flex-wrap:wrap;direction:rtl;margin:0;padding:0}
        .page-item{margin:3px}
        .page-item .page-link{color:var(--primary);border:1px solid #dee2e6;border-radius:50px;padding:8px 16px;background:#fff;font-weight:600;transition:all .3s;min-width:40px;text-align:center;display:flex;align-items:center;justify-content:center}
        .page-item.active .page-link{background:linear-gradient(90deg,var(--primary),#4aa8e0);border-color:var(--primary);color:#fff;box-shadow:0 4px 10px rgba(52,152,219,.3)}
        .page-item.disabled .page-link{background:#f1f1f1;color:#ccc;cursor:not-allowed;border-color:#e0e0e0}
        .page-item .page-link:hover:not(.active):not(.disabled){background:rgba(52,152,219,.1);color:var(--primary);transform:translateY(-1px);box-shadow:0 2px 6px rgba(0,0,0,.08)}
        .page-info{padding:8px 15px;font-weight:600;color:var(--secondary)}
        .page-jump{display:flex;align-items:center;gap:10px}
        .page-jump select{padding:8px 15px;border-radius:25px;border:1px solid #ccc;font-weight:600;color:var(--primary);background:#fff;cursor:pointer;transition:all .3s}
        .page-jump select:hover{border-color:var(--primary);box-shadow:0 0 5px rgba(52,152,219,.3)}
        @media (max-width:992px){.app-content{margin-right:0}.search-box{width:100%;margin-top:15px}.search-box input{min-width:100%}}
        @media (max-width:768px){.stat-card{padding:15px}.stat-card i{font-size:2rem}.stat-card h3{font-size:1.5rem}.pagination-container{flex-direction:column;gap:15px}}
    </style>
</head>
<body>
<?php include('include/header.php'); ?>
<?php include('include/sidebar.php'); ?>

<div class="app-content">
  <div class="main-content">
    <div class="container-fluid">
      <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
          <div><h1><i class="fas fa-users me-3"></i>إدارة المرضى</h1></div>
          <div class="d-flex align-items-center"><span class="badge bg-light text-dark fs-6">المسؤول</span></div>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="stats-container">
        <?php
          $total_patients = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) AS total FROM tblpatient"))['total'];
          $male_count     = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) AS total FROM tblpatient WHERE PatientGender='ذكر'"))['total'];
          $female_count   = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) AS total FROM tblpatient WHERE PatientGender='أنثى'"))['total'];
          $recent_count   = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) AS total FROM tblpatient WHERE CreationDate >= NOW() - INTERVAL 7 DAY"))['total'];
        ?>
        <div class="stat-card"><i class="fas fa-users text-primary"></i><h3><?php echo $total_patients; ?></h3><p>إجمالي المرضى</p></div>
        <div class="stat-card"><i class="fas fa-male text-info"></i><h3><?php echo $male_count; ?></h3><p>مرضى ذكور</p></div>
        <div class="stat-card"><i class="fas fa-female text-warning"></i><h3><?php echo $female_count; ?></h3><p>مرضى إناث</p></div>
        <div class="stat-card"><i class="fas fa-clock text-success"></i><h3><?php echo $recent_count; ?></h3><p>مرضى جدد هذا الأسبوع</p></div>
      </div>

      <!-- Patients Table -->
      <div class="card">
        <div class="card-header">
          <span><i class="fas fa-table me-2"></i>قائمة المرضى</span>
          <div class="search-box">
            <input type="text" class="form-control" placeholder="ابحث باسم المريض أو رقم الهاتف..." id="searchInput">
            <button class="btn btn-primary" onclick="filterTable()"><i class="fas fa-search me-1"></i> بحث</button>
          </div>
        </div>
        <div class="card-body">
          <div class="table-container">
            <table class="table" id="patientsTable">
              <thead>
                <tr>
                  <th class="text-center">#</th>
                  <th>اسم المريض</th>
                  <th>رقم الاتصال</th>
                  <th>الجنس</th>
                  <th>تاريخ الإنشاء</th>
                  <th>تاريخ التحديث</th>
                  <th class="text-center">الإجراءات</th>
                </tr>
              </thead>
              <tbody>
                <?php
                if ($sql && mysqli_num_rows($sql) > 0) {
                    while ($row = mysqli_fetch_assoc($sql)) {
                ?>
                  <tr>
                    <td class="text-center"><?php echo $cnt; ?></td>
                    <td><?php echo htmlspecialchars($row['PatientName']); ?></td>
                    <td><?php echo htmlspecialchars($row['PatientContno']); ?></td>
                    <td>
                      <?php if ($row['PatientGender'] === 'ذكر'): ?>
                        <span class="badge bg-info">ذكر</span>
                      <?php elseif ($row['PatientGender'] === 'أنثى'): ?>
                        <span class="badge bg-warning">أنثى</span>
                      <?php else: ?>
                        <span class="badge bg-secondary">غير محدد</span>
                      <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($row['CreationDate']); ?></td>
                    <td><?php echo htmlspecialchars($row['UpdationDate']); ?></td>
                    <td class="text-center">
                      <button type="button"
                              class="action-btn view-patient-btn"
                              data-id="<?php echo (int)$row['ID']; ?>"
                              data-fullhref="view-patient.php?viewid=<?php echo (int)$row['ID']; ?>">
                        <i class="fas fa-eye me-1"></i>عرض
                      </button>
                    </td>
                  </tr>
                <?php
                        $cnt++;
                    }
                } else {
                    echo '<tr><td colspan="7" class="text-center py-5">
                      <div class="no-records">
                        <i class="fas fa-user-injured"></i>
                        <h4>لا توجد سجلات متاحة</h4>
                        <p class="text-muted">لم يتم إضافة أي مرضى حتى الآن</p>
                      </div>
                    </td></tr>';
                }
                ?>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <?php if ($total_pages > 1): ?>
          <div class="pagination-container">
            <div class="page-info">عرض <?php echo min($cnt - 1, $records_per_page); ?> من أصل <?php echo $total_records; ?> مريض</div>
            <nav>
              <ul class="pagination">
                <?php if ($page > 1): ?>
                  <li class="page-item"><a class="page-link" href="?page=<?php echo $page - 1; ?>" aria-label="السابق"><span aria-hidden="true">&laquo;</span></a></li>
                <?php endif; ?>
                <?php
                  $start = max(1, $page - 2);
                  $end   = min($total_pages, $page + 2);
                  for ($i = $start; $i <= $end; $i++) {
                    $active = ($i == $page) ? 'active' : '';
                    echo '<li class="page-item '.$active.'"><a class="page-link" href="?page='.$i.'">'.$i.'</a></li>';
                  }
                ?>
                <?php if ($page < $total_pages): ?>
                  <li class="page-item"><a class="page-link" href="?page=<?php echo $page + 1; ?>" aria-label="التالي"><span aria-hidden="true">&raquo;</span></a></li>
                <?php endif; ?>
              </ul>
            </nav>
            <div class="page-jump">
              <span>اذهب إلى الصفحة:</span>
              <select class="form-select" onchange="window.location.href='?page='+this.value">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                  <option value="<?php echo $i; ?>" <?php if ($i == $page) echo 'selected'; ?>><?php echo $i; ?></option>
                <?php endfor; ?>
              </select>
            </div>
          </div>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </div>
</div>

<?php include('include/setting.php'); ?>

<!-- Modal: بيانات المريض -->
<div class="modal fade" id="patientModal" tabindex="-1" aria-hidden="true" dir="rtl">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="fas fa-id-card-clip me-2"></i>بيانات المريض</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="إغلاق"></button>
      </div>
      <div class="modal-body">
        <div id="patientLoading" class="text-center py-4">
          <div class="spinner-border" role="status"><span class="visually-hidden">تحميل...</span></div>
          <div class="mt-2">جاري التحميل...</div>
        </div>

        <div id="patientError" class="alert alert-danger d-none"></div>

        <div id="patientContent" class="d-none">
          <div class="row g-3">
            <div class="col-md-6"><strong>الاسم:</strong> <span id="pName"></span></div>
            <div class="col-md-6"><strong>الجنس:</strong> <span id="pGender" class="badge"></span></div>
            <div class="col-md-6"><strong>رقم الاتصال:</strong> <span id="pPhone"></span></div>
            <div class="col-md-6"><strong>البريد الإلكتروني:</strong> <span id="pEmail"></span></div>
            <div class="col-12"><strong>العنوان:</strong> <span id="pAddress"></span></div>
            <div class="col-md-6"><strong>تاريخ الإنشاء:</strong> <span id="pCreated"></span></div>
            <div class="col-md-6"><strong>آخر تحديث:</strong> <span id="pUpdated"></span></div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <a id="openFullPage" class="btn btn-outline-secondary" target="_blank">
          <i class="fas fa-up-right-from-square me-1"></i>فتح الصفحة الكاملة
        </a>
        <button type="button" class="btn btn-primary" onclick="window.print()">
          <i class="fas fa-print me-1"></i>طباعة
        </button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
      </div>
    </div>
  </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  function filterTable() {
    const input = document.getElementById('searchInput');
    const filter = input.value.toUpperCase();
    const table = document.getElementById('patientsTable');
    const tr = table.getElementsByTagName('tr');

    for (let i = 1; i < tr.length; i++) {
      const td = tr[i].getElementsByTagName('td');
      let show = false;
      // search in: name (1) + contact (2)
      for (let j = 1; j <= 2; j++) {
        if (td[j]) {
          const txt = (td[j].textContent || td[j].innerText).toUpperCase();
          if (txt.indexOf(filter) > -1) { show = true; break; }
        }
      }
      tr[i].style.display = show ? '' : 'none';
    }
  }

  document.getElementById('searchInput').addEventListener('keyup', function(e) {
    if (e.key === 'Enter') filterTable();
  });

  document.querySelectorAll('.action-btn').forEach(btn => {
    btn.addEventListener('mouseover', () => btn.style.transform = 'scale(1.05)');
    btn.addEventListener('mouseout',  () => btn.style.transform = 'scale(1)');
  });

  // ====== عرض المودال وجلب بيانات المريض ======
  const modalEl = document.getElementById('patientModal');
  const patientModal = new bootstrap.Modal(modalEl);

  const elLoading = document.getElementById('patientLoading');
  const elError   = document.getElementById('patientError');
  const elContent = document.getElementById('patientContent');

  const pName    = document.getElementById('pName');
  const pGender  = document.getElementById('pGender');
  const pPhone   = document.getElementById('pPhone');
  const pEmail   = document.getElementById('pEmail');
  const pAddress = document.getElementById('pAddress');
  const pCreated = document.getElementById('pCreated');
  const pUpdated = document.getElementById('pUpdated');
  const openFull = document.getElementById('openFullPage');

  function resetModal() {
    elLoading.classList.remove('d-none');
    elError.classList.add('d-none');
    elContent.classList.add('d-none');
    elError.textContent = '';
    [pName, pPhone, pEmail, pAddress, pCreated, pUpdated].forEach(el => el.textContent = '');
    pGender.textContent = ''; pGender.className = 'badge';
    openFull.removeAttribute('href');
  }

  function fillGenderBadge(g) {
    const val = (g || '').trim();
    pGender.textContent = val || 'غير محدد';
    if (val === 'ذكر') {
      pGender.classList.add('bg-info');
    } else if (val === 'أنثى') {
      pGender.classList.add('bg-warning','text-dark');
    } else {
      pGender.classList.add('bg-secondary');
    }
  }

  function showPatient(id, fullHref) {
    resetModal();
    patientModal.show();

    fetch(`${location.pathname}?ajax=patient&id=${encodeURIComponent(id)}&_=${Date.now()}`)
      .then(async (r) => {
        const json = await r.json().catch(() => ({}));
        if (!r.ok || !json.ok) {
          const msg = (json && json.msg) ? json.msg : 'تعذر جلب البيانات';
          throw new Error(msg);
        }
        return json;
      })
      .then(json => {
        const d = json.data || {};
        pName.textContent    = d.PatientName || '';
        pPhone.textContent   = d.PatientContno || '';
        pEmail.textContent   = d.PatientEmail || '—';
        pAddress.textContent = d.PatientAdd || '—';
        pCreated.textContent = d.CreationDate || '';
        pUpdated.textContent = d.UpdationDate || '';
        fillGenderBadge(d.PatientGender);

        if (fullHref) openFull.setAttribute('href', fullHref);

        elLoading.classList.add('d-none');
        elContent.classList.remove('d-none');
      })
      .catch(err => {
        elLoading.classList.add('d-none');
        elError.textContent = err.message || 'حدث خطأ غير متوقع';
        elError.classList.remove('d-none');
      });
  }

  document.querySelectorAll('.view-patient-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      const id = btn.dataset.id;
      const fullHref = btn.dataset.fullhref;
      showPatient(id, fullHref);
    });
  });
  // ====== نهاية سكربت المودال ======
</script>
</body>
</html>
<?php } ?>
