<?php
session_start();

// استدعاء ملف الاتصال بقاعدة البيانات
require_once 'config/db_connexion.php'; 

// تجهيز مسارات الأيقونات الافتراضية
$loader_assets = [
    'loader_logo' => 'assets/images/favicon.svg',
    'loader_step_1' => 'admin/assets/icons/num_1.png',
    'loader_step_2' => 'admin/assets/icons/num_2.png',
    'loader_step_3' => 'admin/assets/icons/num_3.png',
    'loader_step_4' => 'admin/assets/icons/num_4.png'
];

// جلب الأيقونات المحدثة من قاعدة البيانات
try {
    $db = isset($pdo) ? $pdo : (isset($conn) ? $conn : null);
    
    if ($db) {
        $stmt = $db->prepare("SELECT asset_key, file_path FROM design_assets WHERE page_section = 'loader'");
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (!empty($row['file_path'])) {
                $loader_assets[$row['asset_key']] = $row['file_path'];
            }
        }
    }
} catch (PDOException $e) {
    // الاعتماد على الأيقونات الافتراضية في حالة وجود خطأ
}

// نظام اللغات
$allowed_langs = ['ar', 'en', 'fr'];
// تم التعديل هنا: جعل الفرنسية 'fr' هي اللغة الافتراضية إذا لم يتم اختيار لغة مسبقاً
$lang_code = (isset($_SESSION['lang']) && in_array($_SESSION['lang'], $allowed_langs)) ? $_SESSION['lang'] : 'fr';

$lang = require "lang/{$lang_code}.php";
$dir = ($lang_code === 'ar') ? 'rtl' : 'ltr';
?>
<!DOCTYPE html>
<html lang="<?php echo $lang_code; ?>" dir="<?php echo $dir; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($lang['loader_title']) ? $lang['loader_title'] : 'Mo7adaraty'; ?></title>
        <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($loader_assets['loader_logo']); ?>">

    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/loader.css">
</head>
<body class="loader-page">
    <div class="loader-wrapper" id="loader">
        <h1 class="site-title"><?php echo isset($lang['site_name_full']) ? $lang['site_name_full'] : 'Mo7adaraty'; ?></h1>
        
        <div class="logo-box">
            <div class="circle-bg"></div>
            <img src="<?php echo htmlspecialchars($loader_assets['loader_logo']); ?>" alt="Logo" class="loader-logo">
        </div>

        <div class="loading-status">
            <span id="status-text"><?php echo isset($lang['loader_preparing_status']) ? $lang['loader_preparing_status'] : 'جاري التجهيز...'; ?></span>
            <span id="percentage">0%</span>
        </div>

        <div class="progress-bar-bg">
            <div class="progress-bar" id="progress"></div>
        </div>
        
        <p class="loader-text" id="loader-text"><?php echo isset($lang['loader_wait']) ? $lang['loader_wait'] : 'يرجى الانتظار قليلاً'; ?></p>

        <div class="loader-steps">
            <div class="step-line">
                <div class="step-line-progress" id="step-progress"></div>
            </div>
            
            <div class="loader-step">
                <div class="step-icon" id="step1-icon">
                    <img src="<?php echo htmlspecialchars($loader_assets['loader_step_1']); ?>" alt="1" style="width: 100%; height: 100%; object-fit: contain;">
                </div>
                <div class="step-text"><?php echo isset($lang['loader_step1']) ? $lang['loader_step1'] : 'التحضير'; ?></div>
            </div>
            
            <div class="loader-step">
                <div class="step-icon" id="step2-icon">
                    <img src="<?php echo htmlspecialchars($loader_assets['loader_step_2']); ?>" alt="2" style="width: 100%; height: 100%; object-fit: contain;">
                </div>
                <div class="step-text"><?php echo isset($lang['loader_step2']) ? $lang['loader_step2'] : 'الاتصال'; ?></div>
            </div>
            
            <div class="loader-step">
                <div class="step-icon" id="step3-icon">
                    <img src="<?php echo htmlspecialchars($loader_assets['loader_step_3']); ?>" alt="3" style="width: 100%; height: 100%; object-fit: contain;">
                </div>
                <div class="step-text"><?php echo isset($lang['loader_step3']) ? $lang['loader_step3'] : 'التحميل'; ?></div>
            </div>
            
            <div class="loader-step">
                <div class="step-icon" id="step4-icon">
                    <img src="<?php echo htmlspecialchars($loader_assets['loader_step_4']); ?>" alt="4" style="width: 100%; height: 100%; object-fit: contain;">
                </div>
                <div class="step-text"><?php echo isset($lang['loader_step4']) ? $lang['loader_step4'] : 'الاكتمال'; ?></div>
            </div>
            
        </div>

        <button class="retry-button" id="retry-button"><?php echo isset($lang['loader_retry']) ? $lang['loader_retry'] : 'إعادة المحاولة'; ?></button>
    </div>

    <script>
        window.loaderTranslations = {
            step1: "<?php echo addslashes($lang['loader_js_step1'] ?? 'جاري تهيئة بيئة العمل...'); ?>",
            step2: "<?php echo addslashes($lang['loader_js_step2'] ?? 'الاتصال بقاعدة البيانات...'); ?>",
            step3: "<?php echo addslashes($lang['loader_js_step3'] ?? 'تحميل ملفات النظام...'); ?>",
            step4: "<?php echo addslashes($lang['loader_js_step4'] ?? 'تجهيز واجهة المستخدم...'); ?>",
            complete: "<?php echo addslashes($lang['loader_js_complete'] ?? 'اكتمل التحميل، جاري التوجيه...'); ?>",
            success: "<?php echo addslashes($lang['loader_js_success'] ?? 'مكتمل بنجاح'); ?>"
        };
    </script>
    <script src="assets/js/loader_script.js"></script>

</body>
</html>