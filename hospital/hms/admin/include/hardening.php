<?php
// يحذف أي "xxxx.php:123" مع اختيارية الشرطة والمسافة قبلها
function strip_annot($s) {
    if (!is_string($s)) return $s;
    // امسح " - editdoctorspecialization.php:109" وأشباهها
    $s = preg_replace('/\s*-\s*[\w\-]+\.php:\d+\b/u', '', $s);
    // امسح أي "file.php:123" بقيت
    $s = preg_replace('/\b[\w\-]+\.php:\d+\b/u', '', $s);
    return trim($s);
}

// تنظيف مصفوفة (POST/GET/SESSION) بشكل递归
function deep_clean(&$arr) {
    foreach ($arr as $k => &$v) {
        $cleanK = strip_annot($k);
        if ($cleanK !== $k) { unset($arr[$k]); $k = $cleanK; }
        if (is_array($v)) deep_clean($v);
        else $v = strip_annot($v);
    }
}

// فعّل تنظيف المدخلات
deep_clean($_GET);
deep_clean($_POST);
deep_clean($_REQUEST);
if (isset($_SESSION)) deep_clean($_SESSION);

// فلتر مخرجات الصفحة كلها (لو تسرّب شيء)
ob_start(function($html){
    $html = preg_replace('/\s*-\s*[\w\-]+\.php:\d+\b/u', '', $html);
    $html = preg_replace('/\b[\w\-]+\.php:\d+\b/u', '', $html);
    return $html;
});
