<?php
require 'include/config.php';
header('Content-Type: text/html; charset=UTF-8');

// تعبئة قائمة الأطباء حسب التخصص
if (isset($_POST['specilizationid'])) {
    $spec = trim($_POST['specilizationid']);

    $stmt = mysqli_prepare($con, "SELECT id, doctorName FROM doctors WHERE specilization = ?");
    mysqli_stmt_bind_param($stmt, 's', $spec);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    echo '<option value="">اختر الطبيب</option>';
    while ($r = mysqli_fetch_assoc($res)) {
        $id   = (int)$r['id'];
        $name = htmlspecialchars($r['doctorName'], ENT_QUOTES, 'UTF-8');
        echo '<option value="'.$id.'">'.$name.'</option>';
    }
    exit;
}

// تعبئة الرسوم حسب الطبيب
if (isset($_POST['doctor'])) {
    $docId = (int)$_POST['doctor'];

    $stmt = mysqli_prepare($con, "SELECT docFees FROM doctors WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $docId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    if ($r = mysqli_fetch_assoc($res)) {
        $fees = htmlspecialchars($r['docFees'], ENT_QUOTES, 'UTF-8');
        echo '<option value="'.$fees.'">'.$fees.'</option>';
    } else {
        echo '<option value="">—</option>';
    }
    exit;
}

// لا مدخلات صالحة
http_response_code(400);
echo '<option value="">—</option>';
