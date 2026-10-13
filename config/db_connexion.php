<?php
// بدء الجلسة
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// === تحسين كود تغيير اللغة ===
if (isset($_GET['lang'])) {
    $allowed_langs = ['ar', 'en', 'fr'];
    if (in_array($_GET['lang'], $allowed_langs)) {
        $_SESSION['lang'] = $_GET['lang'];
    }
    
    // التقاط الرابط الحالي
    $current_url = $_SERVER['REQUEST_URI'];
    $parsed_url = parse_url($current_url);
    
    // استخراج المعاملات الموجودة في الرابط (إن وجدت)
    $query_params = [];
    if (isset($parsed_url['query'])) {
        parse_str($parsed_url['query'], $query_params);
    }
    
    // إزالة معامل اللغة من الرابط لتنظيفه
    unset($query_params['lang']);
    
    // إعادة بناء الرابط بدون معامل اللغة ولكن مع الاحتفاظ بالمعاملات الأخرى
    $new_query_string = http_build_query($query_params);
    
    // تجهيز مسار إعادة التوجيه
    $redirect_url = $parsed_url['path'] . (!empty($new_query_string) ? '?' . $new_query_string : '');
    
    // إعادة التوجيه
    header("Location: " . $redirect_url);
    exit();
}

// تعيين اللغة الافتراضية إذا لم تكن موجودة
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'ar'; // اللغة الافتراضية
}

// تحميل ملف الترجمة بناءً على اللغة الحالية
$current_lang = $_SESSION['lang'];
$lang_file = __DIR__ . '/../lang/' . $current_lang . '.php';

if (file_exists($lang_file)) {
    $translations = include $lang_file;
} else {
    // في حال عدم وجود الملف، تحميل العربية كاحتياطي
    $translations = include __DIR__ . '/../lang/ar.php';
}


if (!function_exists('__')) {
    function __($key) {
        global $translations;
        return isset($translations[$key]) ? $translations[$key] : $key;
    }
}



// إعدادات قاعدة البيانات
$db_host = "localhost";
$db_name = "mo7adaraty";
$db_user = "admin";
$db_pass = "password123";

try {
    // محاولة الاتصال بقاعدة البيانات
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); 
    $pdo->exec("SET NAMES 'utf8'");

} catch (PDOException $e) {
    // تسجيل الخطأ الفعلي في ملف السجلات الخاص بالسيرفر
    error_log("فشل الاتصال بقاعدة البيانات: " . $e->getMessage());
    
    // التحقق مما إذا كان الطلب من نوع AJAX
    $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'عذراً، حدثت مشكلة في الاتصال بقاعدة البيانات.']);
    } else {
        // عرض رسالة خطأ أنيقة ومتجاوبة تناسب صفحات HTML بدلاً من JSON
        echo '<div style="font-family: Arial, sans-serif; text-align: center; margin-top: 50px; padding: 20px; border: 1px solid #ffcccc; background-color: #ffe6e6; color: #cc0000; border-radius: 8px; max-width: 90%; width: 600px; margin-left: auto; margin-right: auto; box-sizing: border-box;">';
        echo '<h2 style="margin-top: 0; font-size: 1.5rem;">عذراً، الموقع تحت الصيانة حالياً</h2>';
        echo '<p style="font-size: 1rem;">نواجه صعوبة مؤقتة في الاتصال بخوادمنا. يرجى المحاولة لاحقاً.</p>';
        echo '</div>';
    }
    exit;
}
global $pdo;
    $base_url = '/mo7adaraty/';

?>