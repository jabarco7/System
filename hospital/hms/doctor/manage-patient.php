<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('include/config.php');

if (!isset($_SESSION['id']) || strlen($_SESSION['id']) == 0) {
  header('location:logout.php');
  exit;
}

$docid = (int)$_SESSION['id'];

/* ===================== جلب المرضى من tblpatient ===================== */
/* نجلب كل السجلات التابعة لهذا الطبيب مباشرةً من tblpatient */
$patients = [];
$sql = "
  SELECT
    ID                AS pid,           -- رقم المريض في الجدول
    Docid,
    PatientName,
    PatientContno,
    PatientEmail,
    PatientGender,
    PatientAdd,
    PatientAge,
    PatientMedhis,
    CreationDate,
    UpdationDate
  FROM tblpatient
  WHERE Docid = ?
  ORDER BY COALESCE(UpdationDate, CreationDate) DESC, ID DESC
";
if ($st = $con->prepare($sql)) {
  $st->bind_param('i', $docid);
  $st->execute();
  $res = $st->get_result();
  while ($row = $res->fetch_assoc()) {
    // توحيد مسمى الجنس (male/female -> ذكر/أنثى)
    $g = trim((string)($row['PatientGender'] ?? ''));
    $g_l = mb_strtolower($g, 'UTF-8');
    if ($g_l === 'male')   $row['PatientGender'] = 'ذكر';
    elseif ($g_l === 'female') $row['PatientGender'] = 'انثى';
    // تنظيف الهاتف لأغراض البحث
    $row['_phone_digits'] = preg_replace('/\D+/', '', (string)($row['PatientContno'] ?? ''));
    // خزن
    $patients[] = $row;
  }
  $st->close();
}

$total = count($patients);

/* اقتراحات لـ datalist (أسماء/إيميلات فريدة) */
$nameSet  = [];
$emailSet = [];
foreach ($patients as $r) {
  $n = trim($r['PatientName'] ?? '');
  $e = trim($r['PatientEmail'] ?? '');
  if ($n !== '') $nameSet[$n] = true;
  if ($e !== '') $emailSet[$e] = true;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>طبيب | إدارة المرضى</title>

  <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="assets/css/hms-unified.css?v=1.0">

  <style>
    body{font-family:'Tajawal',sans-serif}

    /* الشريط الأزرق الكبير */
    .hero-bar{
      background: linear-gradient(90deg,#3fa7da,#63b2e4);
      color:#fff; border-radius:12px;
      padding:28px 20px; min-height:92px;
      margin:20px 20px 16px; position:relative;
      display:flex; align-items:center; justify-content:center;
      box-shadow:0 6px 16px rgba(40,120,180,.15);
    }
    .hero-title{display:inline-flex; align-items:center; gap:10px; font-weight:700; text-align:center}
    .hero-title i{opacity:.95}

    /* شريط الأدوات */
    .pt-toolbar{display:flex;flex-wrap:wrap;align-items:center;gap:12px;margin-bottom:12px}
    .pt-stat{background:#f8fbff;border:1px solid #e7eef9;border-radius:10px;padding:6px 10px;display:inline-flex;align-items:center;gap:6px}

    /* فلترة الجنس (segmented) */
    .seg{display:inline-flex; align-items:center; gap:6px; background:#fff; border:1px solid #e0ecfb; border-radius:999px; padding:4px}
    .seg-btn{
      border:0; background:transparent; padding:7px 14px; border-radius:999px; cursor:pointer;
      transition:all .15s ease; font-weight:600; display:inline-flex; align-items:center; gap:6px;
    }
    .seg-btn:hover{background:#f3f8ff}
    .seg-btn.active{background:#0d6efd; color:#fff; box-shadow:0 4px 12px rgba(13,110,253,.25)}
    .seg-badge{background:#eef5ff;color:#0d6efd;border-radius:10px;padding:1px 6px;font-size:.8em}

    /* البحث */
    .pt-tools{display:flex;flex-wrap:wrap;gap:10px;margin-right:auto}
    .searchbox{position:relative}
    .searchbox i{position:absolute; right:12px; top:50%; transform:translateY(-50%); color:#8aa2c0}
    .searchbox input{
      padding-right:36px; border-radius:999px;
      min-width:360px; height:44px; font-size:.98rem;
    }
    .hint{font-size:.85em;color:#6c829f}

    /* الجدول */
    .pt-table-wrap{border-radius:12px;box-shadow:0 8px 20px rgba(0,0,0,.06);padding:10px}
    .table thead th{white-space:nowrap;position:sticky;top:0;background:#f7f9fc;z-index:1}
    .table tbody tr:hover{background:#fcfdff}
    .truncate{max-width:320px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:inline-block;vertical-align:middle}
    .muted{color:#6c757d}

    .badge-soft{display:inline-block;border:1px solid #e7eef9;background:#f7fbff;color:#0d6efd;border-radius:14px;padding:2px 8px;font-size:.85em}
    .gender-m{background:#eff6ff;color:#0b66c3;border:1px solid #d7e7ff}
    .gender-f{background:#fff0f6;color:#c2185b;border:1px solid #ffd6e7}
    .gender-o{background:#f5f5f5;color:#555;border:1px solid #e6e6e6}

    .btn-icon{display:inline-flex;align-items:center;gap:6px}
  </style>
</head>
<body>
  <div id="app">

    <?php include('include/header.php'); ?>
    <?php include('include/sidebar.php'); ?>

    <div class="main-content">
      <div class="wrap-content container" id="container">

        <!-- الشريط الأزرق -->
        <div class="hero-bar">
          <div class="hero-title"><i class="fa fa-users"></i> مرضاي</div>
        </div>

        <div class="container-fluid container-fullw bg-white">
          <div class="row">
            <div class="col-md-12">
              <h5 class="over-title margin-bottom-15">قائمة <span class="text-bold">المرضى</span></h5>

              <!-- أدوات -->
              <div class="pt-toolbar">
                <div class="pt-stat"><i class="fa fa-users"></i> إجمالي: <strong id="stTotal"><?php echo $total; ?></strong></div>

                <div class="pt-tools">

                  <!-- الجنس -->
                  <div class="seg" id="segGender">
                    <button class="seg-btn active" data-value="all">
                      <i class="fa fa-venus-mars"></i> الكل
                      <span class="seg-badge" id="gAll">0</span>
                    </button>
                    <button class="seg-btn" data-value="ذكر">
                      ذكر <span class="seg-badge" id="gM">0</span>
                    </button>
                    <button class="seg-btn" data-value="انثى">
                      أنثى <span class="seg-badge" id="gF">0</span>
                    </button>
                  </div>

                  <!-- البحث: اسم / بريد / هاتف / رقم المريض -->
                  <div>
                    <div class="searchbox">
                      <i class="fa fa-search"></i>
                      <input id="q" class="form-control" list="patientsHints"
                             placeholder="ابحث بالاسم / البريد / الهاتف / رقم المريض (ID)">
                    </div>
                    <div class="hint">إذا احتوى الاستعلام على <strong>@</strong> سيُعامل كبحث بريد. وإن كان أرقامًا فسوف يطابق رقم الهاتف أو رقم المريض.</div>
                    <datalist id="patientsHints">
                      <?php foreach(array_keys($nameSet) as $n): ?>
                        <option value="<?php echo htmlspecialchars($n); ?>"></option>
                      <?php endforeach; foreach(array_keys($emailSet) as $e): ?>
                        <option value="<?php echo htmlspecialchars($e); ?>"></option>
                      <?php endforeach; ?>
                    </datalist>
                  </div>

                </div>
              </div>

              <div class="pt-table-wrap">
                <div class="table-responsive">
                  <table class="table table-hover" id="patients-table">
                    <thead>
                      <tr>
                        <th>رقم المريض</th>
                        <th>اسم المريض</th>
                        <th>رقم الاتصال</th>
                        <th>البريد الإلكتروني</th>
                        <th>الجنس</th>
                        <th>العنوان</th>
                        <th>العمر</th>
                        <th>التاريخ المرضي</th>
                        <th>تاريخ الإنشاء</th>
                        <th>آخر تحديث</th>
                        <th>الإجراء</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if ($patients): ?>
                        <?php foreach ($patients as $row):
                          $gender = trim($row['PatientGender'] ?? '');
                          $genderClass = ($gender==='ذكر') ? 'gender-m' : (($gender==='انثى') ? 'gender-f' : 'gender-o');
                          $name   = trim($row['PatientName'] ?? '');
                          $email  = trim($row['PatientEmail'] ?? '');
                          $phone  = $row['_phone_digits'];
                        ?>
                        <tr
                          data-gender="<?php echo $gender ?: 'other'; ?>"
                          data-name="<?php echo htmlspecialchars(mb_strtolower($name, 'UTF-8')); ?>"
                          data-email="<?php echo htmlspecialchars(mb_strtolower($email, 'UTF-8')); ?>"
                          data-phone="<?php echo htmlspecialchars($phone); ?>"
                          data-pno="<?php echo (int)$row['pid']; ?>"
                        >
                          <td><?php echo (int)$row['pid']; ?></td>
                          <td><?php echo htmlspecialchars($name ?: '—'); ?></td>
                          <td><?php echo htmlspecialchars($row['PatientContno'] ?: '—'); ?></td>
                          <td><?php echo htmlspecialchars($email ?: '—'); ?></td>
                          <td><span class="badge-soft <?php echo $genderClass; ?>"><?php echo $gender ?: '—'; ?></span></td>
                          <td><span class="truncate" title="<?php echo htmlspecialchars($row['PatientAdd'] ?: '—'); ?>"><?php echo htmlspecialchars($row['PatientAdd'] ?: '—'); ?></span></td>
                          <td><?php echo htmlspecialchars($row['PatientAge'] ?: '—'); ?></td>
                          <td><span class="truncate" title="<?php echo htmlspecialchars($row['PatientMedhis'] ?: '—'); ?>"><?php echo htmlspecialchars($row['PatientMedhis'] ?: '—'); ?></span></td>
                          <td><?php echo htmlspecialchars($row['CreationDate'] ?: '—'); ?></td>
                          <td><?php echo htmlspecialchars($row['UpdationDate'] ?: '—'); ?></td>
                          <td>
                            <a href="view-patient.php?viewid=<?php echo (int)$row['pid']; ?>" class="btn btn-warning btn-sm btn-icon" title="عرض التفاصيل">
                              <i class="fa fa-eye"></i> عرض
                            </a>
                          </td>
                        </tr>
                        <?php endforeach; ?>
                      <?php else: ?>
                        <tr><td colspan="11" class="text-center">لا يوجد مرضى مسجلون لديك حالياً.</td></tr>
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

      const q = document.getElementById('q');
      const rows = Array.from(document.querySelectorAll('#patients-table tbody tr'));
      const stTotal = document.getElementById('stTotal');

      const seg = document.getElementById('segGender');
      const gAll = document.getElementById('gAll');
      const gM   = document.getElementById('gM');
      const gF   = document.getElementById('gF');
      const gO   = document.getElementById('gO'); // قد لا يوجد

      let genFilter = 'all';   // all | ذكر | انثى | other

      function normArabic(s){
        return (s||'')
          .replace(/[أإآٱ]/g,'ا')
          .replace(/ى/g,'ي')
          .replace(/ة/g,'ه')
          .replace(/ؤ/g,'و')
          .replace(/ئ/g,'ي');
      }
      const onlyDigits = s => (s||'').replace(/\D+/g,'');

      function applyFilters(){
        const raw  = (q.value || '').trim().toLowerCase();
        const norm = normArabic(raw);
        const hasAt    = raw.indexOf('@') !== -1;
        const hasDigit = /\d/.test(raw);
        const qDigits  = onlyDigits(raw);

        let total=0, m=0, f=0, o=0;

        rows.forEach(tr=>{
          const g      = tr.dataset.gender || 'other';
          const name   = tr.dataset.name  || '';
          const email  = tr.dataset.email || '';
          const phone  = tr.dataset.phone || '';
          const pno    = tr.dataset.pno   || '';

          let match = true;
          if (raw){
            if (hasAt) {
              match = email.indexOf(raw) !== -1;            // بريد
            } else {
              match = (normArabic(name).indexOf(norm) !== -1) || (email.indexOf(raw) !== -1); // اسم أو بريد
              if (hasDigit) match = match || phone.indexOf(qDigits) !== -1 || pno.indexOf(qDigits) !== -1; // هاتف أو رقم مريض
            }
          }

          if (genFilter!=='all'){
            if (genFilter==='other') { match = match && (g!=='ذكر' && g!=='انثى'); }
            else { match = match && (g===genFilter); }
          }

          tr.style.display = match ? '' : 'none';
          if (match){
            total++;
            if (g==='ذكر') m++; else if (g==='انثى') f++; else o++;
          }
        });

        stTotal.textContent = total;
        gAll.textContent = total; gM.textContent = m; gF.textContent = f; if (gO) gO.textContent = o;
      }

      q.addEventListener('input', applyFilters);

      seg.querySelectorAll('.seg-btn').forEach(btn=>{
        btn.addEventListener('click', function(){
          seg.querySelectorAll('.seg-btn').forEach(b=>b.classList.remove('active'));
          this.classList.add('active');
          genFilter = this.dataset.value;
          applyFilters();
        });
      });

      applyFilters();
    });
  </script>
</body>
</html>
