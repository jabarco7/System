<?php
session_start();
error_reporting(0);
include('include/config.php');
include('include/hardening.php');

if (empty($_SESSION['id'])) {
    header('location:logout.php');
    exit;
}

/* =========================
   AJAX داخل نفس الصفحة
   ========================= */
if (isset($_POST['ajax'])) {
    header('Content-Type: application/json; charset=UTF-8');

    // جلب بيانات مستخدم
    if ($_POST['ajax'] === 'get_user') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['ok' => false, 'msg' => 'معرّف غير صالح']);
            exit;
        }

        $stmt = mysqli_prepare($con, "SELECT id, fullName, address, city, gender, email FROM users WHERE id=? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);

        if (!$user) {
            echo json_encode(['ok' => false, 'msg' => 'المستخدم غير موجود']);
            exit;
        }
        echo json_encode(['ok' => true, 'user' => $user], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // حفظ التعديل
    if ($_POST['ajax'] === 'update_user') {
        $id       = (int)($_POST['id'] ?? 0);
        $fullName = trim($_POST['fullName'] ?? '');
        $address  = trim($_POST['address'] ?? '');
        $city     = trim($_POST['city'] ?? '');
        $gender   = trim($_POST['gender'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $pwNew    = trim($_POST['passwordNew'] ?? '');
        $pwConf   = trim($_POST['passwordConfirm'] ?? '');

        if ($id <= 0 || $fullName === '' || $address === '' || $city === '' || $gender === '' || $email === '') {
            echo json_encode(['ok' => false, 'msg' => 'يرجى تعبئة جميع الحقول.']);
            exit;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['ok' => false, 'msg' => 'البريد الإلكتروني غير صالح.']);
            exit;
        }

        // تأكد أن البريد غير مستخدم لمستخدم آخر
        $du = mysqli_prepare($con, "SELECT id FROM users WHERE email=? AND id<>? LIMIT 1");
        mysqli_stmt_bind_param($du, 'si', $email, $id);
        mysqli_stmt_execute($du);
        $duRes = mysqli_stmt_get_result($du);
        if (mysqli_fetch_assoc($duRes)) {
            echo json_encode(['ok' => false, 'msg' => 'البريد مستخدم بالفعل لمستخدم آخر.']);
            exit;
        }
        mysqli_stmt_close($du);

        // بناء استعلام التحديث
        if ($pwNew !== '' || $pwConf !== '') {
            if ($pwNew !== $pwConf) {
                echo json_encode(['ok' => false, 'msg' => 'كلمتا المرور غير متطابقتين.']);
                exit;
            }
            if (strlen($pwNew) < 6) {
                echo json_encode(['ok' => false, 'msg' => 'كلمة المرور يجب أن تكون 6 أحرف/أرقام على الأقل.']);
                exit;
            }
            $pwHash = md5($pwNew); // ملاحظة: يُفضّل ترحيل النظام لاحقًا إلى password_hash
            $up = mysqli_prepare($con, "UPDATE users SET fullName=?, address=?, city=?, gender=?, email=?, password=?, updationDate=NOW() WHERE id=?");
            mysqli_stmt_bind_param($up, 'ssssssi', $fullName, $address, $city, $gender, $email, $pwHash, $id);
        } else {
            $up = mysqli_prepare($con, "UPDATE users SET fullName=?, address=?, city=?, gender=?, email=?, updationDate=NOW() WHERE id=?");
            mysqli_stmt_bind_param($up, 'sssssi', $fullName, $address, $city, $gender, $email, $id);
        }

        if (mysqli_stmt_execute($up)) {
            echo json_encode(['ok' => true, 'msg' => 'تم الحفظ بنجاح']);
            exit;
        } else {
            echo json_encode(['ok' => false, 'msg' => 'تعذّر الحفظ.']);
            exit;
        }
    }

    echo json_encode(['ok' => false, 'msg' => 'طلب غير معروف']);
    exit;
}

/* حذف مستخدم */
if (isset($_GET['del'], $_GET['id'])) {
    $uid = (int)$_GET['id'];
    if ($uid > 0) {
        $stmt = mysqli_prepare($con, "DELETE FROM users WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $uid);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $_SESSION['msg'] = "تم حذف المستخدم بنجاح!";
        header("Location: manage-users.php");
        exit;
    }
}

/* ترقيم الصفحات */
$limit = 10;
$page  = isset($_GET['page']) ? max((int)$_GET['page'], 1) : 1;
$start = ($page - 1) * $limit;

/* إجمالي المستخدمين */
$total_users = 0;
$total_query  = mysqli_query($con, "SELECT COUNT(*) AS total FROM users");
if ($total_query && $total_row = mysqli_fetch_assoc($total_query)) {
    $total_users = (int)$total_row['total'];
}
$pages = max(1, (int)ceil($total_users / $limit));

/* جلب البيانات */
$start = (int)$start;
$limit = (int)$limit;
$sql = mysqli_query(
    $con,
    "SELECT id, fullName, address, city, gender, email, regDate, updationDate
     FROM users
     ORDER BY id ASC
     LIMIT $start, $limit"
);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>المسؤول | إدارة المستخدمين</title>

    <!-- Bootstrap 5 (CDN) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            background: #f0f5f9;

            padding-top: var(--header-h);

            margin-top: 80px;
            padding-top: 80px;
        }

        .navbar-fixed-top,
        .app-header {
            z-index: 1030;
            /* أعلى من المحتوى */
        }

        .main-content {
            margin-right: 20px;
            padding: 20px;
            position: relative;
            z-index: 1;
        }

        .sidebar,
        #sidebar,
        .app-sidebar {
            position: sticky;
            top: calc(var(--header-h) + 10px);
            /* مسافة بسيطة تحت الهيدر */
        }

        .page-header {
            margin-top: 6px;
        }

        .page-header {
            background: linear-gradient(90deg, #3498db, #4aa8e0);
            color: #fff;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
            background: #fff;
        }

        /* === أحجام موحّدة للهيدر والسايدبار === */
        :root {
            --header-h: 72px;
            /* عدّلها حسب ارتفاع الهيدر الحقيقي عندك */
            --sidebar-w: 260px;
            /* إن كان لك سايدبار ثابت */
        }


        .card-header {
            background: #fff;
            padding: 15px 20px;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-container {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: separate;
            min-width: 900px;
        }

        .table th {
            background: #3498db;
            color: #fff;
            font-weight: 600;
            padding: 15px;
            text-align: right;
        }

        .table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            text-align: right;
            vertical-align: top;
        }

        .table tr:nth-child(even) {
            background: #f8fafc;
        }

        .action-btn {
            padding: 6px 12px;
            border-radius: 5px;
            margin-left: 5px;
            font-size: .85rem;
            text-decoration: none;
            display: inline-block
        }

        .btn-delete {
            background: rgba(231, 76, 60, .15);
            color: #e74c3c;
            border: 1px solid rgba(231, 76, 60, .3);
        }

        .btn-delete:hover {
            background: rgba(231, 76, 60, .3);
            color: #fff;
        }

        .btn-edit {
            background: rgba(52, 152, 219, .15);
            color: #3498db;
            border: 1px solid rgba(52, 152, 219, .3);
        }

        .btn-edit:hover {
            background: rgba(52, 152, 219, .3);
            color: #fff;
        }

        .alert-message {
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 600;
            margin: 0 0 20px;
            background: rgba(39, 174, 96, .15);
            color: #27ae60;
        }

        .search-container {
            max-width: 300px;
            position: relative;
        }

        .search-container input {
            padding-right: 40px;
            border-radius: 30px;
            border: 1px solid #ddd;
            height: 36px;
            width: 100%;
        }

        /* منطقة زر المسؤول والقائمة */
        #adminArea {
            position: relative;
            z-index: 2001;
        }

        /* أعلى من الهيدر */
        #adminMenu {
            position: absolute;
            top: calc(100% + 8px);
            inset-inline-end: 0;
            /* يدعم RTL/LTR */
            min-width: 220px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 12px 30px rgba(0, 0, 0, .15);
            border: 1px solid #e9ecef;
            display: none;
        }

        #adminMenu.show {
            display: block;
        }

        #adminMenu a {
            display: block;
            padding: 10px 12px;
            text-decoration: none;
            color: #22313f;
        }

        #adminMenu a:hover {
            background: #f6f9fc;
        }


        .search-container i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }

        .pagination {
            margin-top: 20px;
            text-align: center;
        }

        .pagination a {
            display: inline-block;
            padding: 8px 12px;
            margin: 0 5px;
            border: 1px solid #ccc;
            border-radius: 5px;
            background: #fff;
            color: #3498db;
            text-decoration: none;
        }

        .pagination a.active {
            background: #3498db;
            color: #fff;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: .8rem
        }

        .badge-male {
            background: #e8f4ff;
            color: #0d6efd;
        }

        .badge-female {
            background: #ffe8f3;
            color: #d63384;
        }

        @media (max-width:768px) {
            .card-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px
            }

            .search-container {
                max-width: 100%;
                width: 100%;
            }
        }
    </style>
</head>

<body>

    <?php include('include/header.php'); ?>
    <?php include('include/sidebar.php'); ?>

    <div class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-users me-3"></i>إدارة المستخدمين</h1>
            <p>عرض وتعديل وحذف بيانات المستخدمين</p>
        </div>

        <div class="card">
            <div class="card-header">
                <span><i class="fas fa-table me-2"></i>قائمة المستخدمين</span>
                <div class="search-container">
                    <input type="text" id="search" class="form-control" placeholder="ابحث عن مستخدم...">
                    <i class="fas fa-search"></i>
                </div>
            </div>
            <div class="card-body">
                <?php if (isset($_SESSION['msg'])): ?>
                    <div class="alert-message"><?php echo htmlspecialchars($_SESSION['msg']); ?></div>
                    <?php unset($_SESSION['msg']); ?>
                <?php endif; ?>

                <div class="table-container">
                    <table class="table" id="users-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>المستخدم</th>
                                <th>المعلومات</th>
                                <th>البريد الإلكتروني</th>
                                <th>تاريخ الإنشاء</th>
                                <th>تاريخ التحديث</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $cnt = $start + 1;
                            if ($sql && mysqli_num_rows($sql) > 0):
                                while ($row = mysqli_fetch_assoc($sql)):
                                    $id      = (int)$row['id'];
                                    $name    = htmlspecialchars($row['fullName'] ?? '—', ENT_QUOTES, 'UTF-8');
                                    $city    = htmlspecialchars($row['city'] ?? '', ENT_QUOTES, 'UTF-8');
                                    $address = htmlspecialchars($row['address'] ?? '', ENT_QUOTES, 'UTF-8');
                                    $gender  = trim((string)($row['gender'] ?? ''));
                                    $email   = htmlspecialchars($row['email'] ?? '', ENT_QUOTES, 'UTF-8');
                                    $reg     = htmlspecialchars($row['regDate'] ?? '', ENT_QUOTES, 'UTF-8');
                                    $upd     = htmlspecialchars($row['updationDate'] ?? '', ENT_QUOTES, 'UTF-8');
                                    $g = mb_strtolower($gender, 'UTF-8');
                                    $badgeClass = ($g === 'ذكر' || $g === 'male') ? 'badge-male' : (($g === 'انثى' || $g === 'female') ? 'badge-female' : '');
                            ?>
                                    <tr data-row-id="<?php echo $id; ?>">
                                        <td><?php echo $cnt; ?></td>
                                        <td>
                                            <div class="cell-name"><?php echo $name; ?></div>
                                            <?php if ($city): ?><small class="cell-city"><?php echo $city; ?></small><?php else: ?><small class="cell-city"></small><?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="cell-address"><?php echo $address; ?></div>
                                            <?php if ($gender): ?>
                                                <span class="badge cell-gender <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($gender, ENT_QUOTES, 'UTF-8'); ?></span>
                                            <?php else: ?>
                                                <span class="badge cell-gender"></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="cell-email"><?php echo $email; ?></td>
                                        <td><?php echo $reg; ?></td>
                                        <td class="cell-upd"><?php echo $upd ?: '—'; ?></td>
                                        <td>
                                            <a href="#" class="action-btn btn-edit js-edit-user" data-id="<?php echo $id; ?>">
                                                <i class="fas fa-edit me-1"></i> تعديل
                                            </a>
                                            <a href="?id=<?php echo $id; ?>&del=1"
                                                onclick="return confirm('هل أنت متأكد أنك تريد حذف هذا المستخدم؟')"
                                                class="action-btn btn-delete">
                                                <i class="fas fa-trash-alt me-1"></i> حذف
                                            </a>
                                        </td>
                                    </tr>
                                <?php
                                    $cnt++;
                                endwhile;
                            else:
                                ?>
                                <tr>
                                    <td colspan="7" class="text-center">لا توجد سجلات.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- ترقيم الصفحات -->
                <div class="pagination">
                    <?php for ($i = 1; $i <= $pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>" class="<?php echo ($i == $page) ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include('include/setting.php'); ?>

    <!-- Bootstrap Modal: Edit User -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true" dir="rtl">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content" style="border-radius:12px;overflow:hidden">
                <div class="modal-header" style="background:#3498db;color:#fff">
                    <h5 class="modal-title">تعديل بيانات المستخدم</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <div class="modal-body">
                    <div id="editAlert" class="alert d-none"></div>
                    <form id="editUserForm">
                        <input type="hidden" name="id" id="eu_id">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">الاسم الكامل</label>
                                <input type="text" name="fullName" id="eu_fullName" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">المدينة</label>
                                <input type="text" name="city" id="eu_city" class="form-control" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">العنوان</label>
                                <input type="text" name="address" id="eu_address" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">الجنس</label>
                                <select name="gender" id="eu_gender" class="form-control" required>
                                    <option value="ذكر">ذكر</option>
                                    <option value="انثى">أنثى</option>
                                </select>
                            </div>

                            <!-- كلمة مرور جديدة (اختياري) -->
                            <div class="col-md-6">
                                <label class="form-label">كلمة مرور جديدة (اختياري)</label>
                                <input type="password" name="passwordNew" id="eu_passwordNew" class="form-control" placeholder="اتركها فارغة لعدم التغيير">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">تأكيد كلمة المرور</label>
                                <input type="password" name="passwordConfirm" id="eu_passwordConfirm" class="form-control" placeholder="أعد كتابة كلمة المرور">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">البريد الإلكتروني</label>
                                <input type="email" name="email" id="eu_email" class="form-control" required>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer" style="gap:8px">
                    <button type="button" class="action-btn btn-edit" id="saveUserBtn">
                        <i class="fas fa-save me-1"></i> حفظ
                    </button>
                    <button type="button" class="action-btn btn-delete" data-bs-dismiss="modal">إلغاء</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle (JS) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // افتح/سكر قائمة المسؤول
        (function() {
            const toggle = document.getElementById('adminToggle');
            const menu = document.getElementById('adminMenu');
            const area = document.getElementById('adminArea');
            if (!toggle || !menu || !area) return;

            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                menu.classList.toggle('show');
            });

            // اغلاق عند الضغط خارج القائمة
            document.addEventListener('click', function(e) {
                if (!area.contains(e.target)) menu.classList.remove('show');
            });

            // اغلاق بـ Esc
            window.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') menu.classList.remove('show');
            });
        })();


        // إخفاء تنبيه الصفحة بعد 4 ثوان
        setTimeout(() => {
            const alertEl = document.querySelector('.alert-message');
            if (alertEl) {
                alertEl.style.opacity = '0';
                setTimeout(() => alertEl.remove(), 400);
            }
        }, 4000);

        // بحث فوري
        document.getElementById('search').addEventListener('input', function() {
            const term = this.value.toLowerCase();
            document.querySelectorAll('#users-table tbody tr').forEach(tr => {
                tr.style.display = tr.innerText.toLowerCase().includes(term) ? '' : 'none';
            });
        });

        // Bootstrap modal instance
        let editModal;
        document.addEventListener('DOMContentLoaded', () => {
            const modalEl = document.getElementById('editUserModal');
            editModal = new bootstrap.Modal(modalEl, {
                backdrop: 'static'
            });

            // زر تعديل: افتح المودال وجلب البيانات
            document.querySelectorAll('.js-edit-user').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const id = this.dataset.id;
                    if (!id) return;
                    showEditAlert('', '');
                    fetch('manage-users.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: new URLSearchParams({
                                ajax: 'get_user',
                                id
                            })
                        })
                        .then(r => r.json())
                        .then(data => {
                            if (!data || !data.ok) {
                                showEditAlert(data?.msg || 'تعذّر جلب البيانات.', 'danger');
                                return;
                            }
                            const u = data.user;
                            eu_id.value = u.id;
                            eu_fullName.value = u.fullName || '';
                            eu_address.value = u.address || '';
                            eu_city.value = u.city || '';
                            eu_gender.value = u.gender || 'ذكر';
                            eu_email.value = u.email || '';
                            eu_passwordNew.value = '';
                            eu_passwordConfirm.value = '';
                            editModal.show();
                        })
                        .catch(() => showEditAlert('خطأ غير متوقع أثناء التحميل.', 'danger'));
                });
            });

            // حفظ
            document.getElementById('saveUserBtn').addEventListener('click', function() {
                const form = document.getElementById('editUserForm');
                // تحقق كلمة المرور (اختياري)
                const pw = form.passwordNew.value.trim();
                const pw2 = form.passwordConfirm.value.trim();
                if (pw || pw2) {
                    if (pw.length < 6) {
                        showEditAlert('كلمة المرور يجب أن تكون 6 أحرف/أرقام على الأقل.', 'danger');
                        return;
                    }
                    if (pw !== pw2) {
                        showEditAlert('كلمتا المرور غير متطابقتين.', 'danger');
                        return;
                    }
                }

                const fd = new FormData(form);
                fd.append('ajax', 'update_user');

                fetch('manage-users.php', {
                        method: 'POST',
                        body: fd
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (!data || !data.ok) {
                            showEditAlert(data?.msg || 'تعذّر حفظ التعديلات.', 'danger');
                            return;
                        }
                        showEditAlert('تم حفظ التعديلات بنجاح.', 'success');

                        // حدّث الصف بصريًا
                        const id = form.id.value;
                        const row = document.querySelector(`tr[data-row-id="${id}"]`);
                        if (row) {
                            row.querySelector('.cell-name').textContent = form.fullName.value || '—';
                            row.querySelector('.cell-city').textContent = form.city.value || '';
                            row.querySelector('.cell-address').textContent = form.address.value || '';
                            row.querySelector('.cell-email').textContent = form.email.value || '';
                            const badge = row.querySelector('.cell-gender');
                            if (badge) {
                                const g = (form.gender.value || '').toLowerCase();
                                badge.textContent = form.gender.value || '';
                                badge.className = 'badge cell-gender ' + ((g === 'ذكر' || g === 'male') ? 'badge-male' : (g === 'انثى' || g === 'female') ? 'badge-female' : '');
                            }
                            // بإمكانك هنا تحديث تاريخ التحديث لو رجّعته من السيرفر
                        }
                        setTimeout(() => editModal.hide(), 700);
                    })
                    .catch(() => showEditAlert('خطأ غير متوقع أثناء الحفظ.', 'danger'));
            });
        });

        function showEditAlert(msg, type) {
            const el = document.getElementById('editAlert');
            if (!msg) {
                el.className = 'alert d-none';
                el.textContent = '';
                return;
            }
            el.className = 'alert alert-' + (type || 'info');
            el.textContent = msg;
            el.classList.remove('d-none');
        }
    </script>
</body>

</html>