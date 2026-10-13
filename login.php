<?php
// 1. بدء الجلسة
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. حماية الصفحة: إذا كان مسجلاً للدخول بالفعل، نوجهه لملفه فوراً
if (isset($_SESSION['user_id'])) {
    $redirect = ($_SESSION['role'] === 'admin') ? 'admin/index.php' : 'student/profil.php';
    header("Location: $redirect");
    exit();
}

require_once 'config/db_connexion.php';

// 3. إعداد نظام اللغات (جعل الفرنسية هي الافتراضية كما فعلنا سابقاً أو حسب الجلسة)
$allowed_langs = ['ar', 'en', 'fr'];
$lang_code = (isset($_SESSION['lang']) && in_array($_SESSION['lang'], $allowed_langs)) ? $_SESSION['lang'] : 'fr';

// استخدام require لجلب المصفوفة في كل مرة
$lang_file = __DIR__ . "/lang/{$lang_code}.php";
if (file_exists($lang_file)) {
    $translations = require $lang_file; 
} else {
    $translations = [];
}

$dir = ($lang_code === 'ar') ? 'rtl' : 'ltr';

if (!function_exists('__')) {
    function __($key) {
        global $translations;
        return $translations[$key] ?? $key;
    }
}

// 4. جلب بيانات "تذكرني" من الكوكيز
$saved_login = $_COOKIE['saved_login'] ?? '';

// ========================================================================
// 5. جلب بيانات التصميم الخاصة بصفحة الدخول
// ========================================================================
$login_assets = [];
try {
    $db = isset($pdo) ? $pdo : (isset($conn) ? $conn : null);
    if ($db) {
        $stmt_login = $db->prepare("SELECT asset_key, file_path FROM design_assets WHERE page_section = 'login'");
        $stmt_login->execute();
        while ($asset = $stmt_login->fetch(PDO::FETCH_ASSOC)) {
            $login_assets[$asset['asset_key']] = $asset['file_path'];
        }
    }
} catch (PDOException $e) {}

// تحديد الكلاسات الديناميكية للـ Body والـ Wrapper
$body_class = "login-page";
if (!empty($login_assets['login_bg_body'])) {
    $body_class .= " has-custom-bg";
}

$wrapper_class = "login-wrapper fade-in";
if (!empty($login_assets['login_bg_card'])) {
    $wrapper_class .= " has-custom-card-bg";
}

// ========================================================================
// 6. دالة مساعدة لعرض الأيقونات (تمت إزالة الفلاتر ودعم الأداء العالي)
// ========================================================================
if (!function_exists('getLoginIcon')) {
    function getLoginIcon($key, $default_path, $size = '50px', $custom_style = '', $id = '', $class = '', $onclick = '') {
        global $login_assets;
        $base_url = '/mo7adaraty/';
        
        $path = $login_assets[$key] ?? $default_path;
        $image_url = rtrim($base_url, '/') . '/' . ltrim($path, '/');
        
        $attr_id = $id ? ' id="' . htmlspecialchars($id) . '"' : '';
        $attr_class = $class ? ' class="' . htmlspecialchars($class) . '"' : '';
        $attr_onclick = $onclick ? ' onclick="' . htmlspecialchars($onclick) . '"' : '';
        
        // الأيقونات بدون أي فلاتر لتظهر بشكلها الأصلي
        return '<img src="' . htmlspecialchars($image_url) . '" alt="" style="width: ' . $size . '; height: ' . $size . '; object-fit: contain; z-index: 2; ' . $custom_style . '"' . $attr_id . $attr_class . $attr_onclick . ' onerror="this.style.display=\'none\'">';
    }
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang_code) ?>" dir="<?= $dir ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
    <title><?= __('login_title') ?? 'تسجيل الدخول' ?></title>
    
    
    <link rel="stylesheet" href="assets/css/global.css"> 
    <link rel="stylesheet" href="assets/css/auth.css">
    <link rel="stylesheet" href="assets/css/pages/login.css">
    
    <style>
        :root {
            <?php if (!empty($login_assets['login_bg_body'])): ?>
            --login-bg-body: url('<?= '/mo7adaraty/' . ltrim($login_assets['login_bg_body'], '/') ?>');
            <?php endif; ?>
            
            <?php if (!empty($login_assets['login_bg_card'])): ?>
            --login-bg-card: url('<?= '/mo7adaraty/' . ltrim($login_assets['login_bg_card'], '/') ?>');
            <?php endif; ?>
        }
        
        /* تنسيقات إضافية لضمان احتواء الأيقونات بحجم 50 بيكسل داخل الحقول بشكل جميل */
        .auth-form .input-group input {
            height: 60px; /* رفع ارتفاع الحقل ليتسع للأيقونة */
            font-size: 16px;
        }
    </style>
</head>
<body class="<?= $body_class ?>">

    <script>
        (function() {
            const savedTheme = localStorage.getItem('mo7adaraty_theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>

    <div class="header-wrapper">
        <?php 
            $header_path = 'templates/header.php';
            if (file_exists($header_path)) require_once $header_path;
        ?>
    </div>
    
    <div class="bg-animation"></div>
    <div class="shape shape-1"></div>
    <div class="shape shape-2"></div>
    <div class="shape shape-3"></div>
    <video autoplay muted loop id="bg-video"><source src="https://streamable.com/de8pkj" type="video/mp4"></video>
    
    <main class="login-main-container">
        <div class="<?= $wrapper_class ?>" style="margin: 0;">
            <div class="image-container">
                <div class="image-content">
                    <div class="logo-svg" style="display: flex; justify-content: center; margin-bottom: 15px;">
                        <?= getLoginIcon('login_logo', 'includesimg/login/login_logo.png', '100px') ?>
                    </div>
                    <h3><?= __('welcome_heading') ?? 'مرحباً بك مجدداً' ?></h3>
                    <p><?= __('welcome_desc') ?? 'سعداء برؤيتك مرة أخرى في منصتنا.' ?></p>
                </div>
            </div>
            
            <div class="form-container slide-in-right">
                <div class="logo" style="display: flex; justify-content: center; margin-bottom: 20px;">
                    <?= getLoginIcon('login_logo', 'includesimg/login/login_logo.png', '100px') ?>
                </div>
                
                <h2><?= __('login_heading') ?? 'تسجيل الدخول' ?></h2>
                
                <?php if (isset($_GET['error'])): ?>
                    <div class="status-message error slide-in-right">
                        <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none" style="margin-inline-end:8px; vertical-align:middle;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                        <?= htmlspecialchars($_GET['error']) ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['status']) && $_GET['status'] == 'signup_success'): ?>
                    <div class="status-message success slide-in-right">
                        <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none" style="margin-inline-end:8px; vertical-align:middle;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                        <?= __('signup_success_msg') ?? 'تم التسجيل بنجاح، يمكنك الدخول الآن' ?>
                    </div>
                <?php endif; ?>

                <form action="auth/login_process.php" method="POST" class="auth-form">
                    <div class="form-group">
                        <label for="login_identifier"><?= __('email_or_username') ?? 'اسم المستخدم أو البريد الإلكتروني' ?></label>
                        <div class="input-group" style="position: relative;">
                            <?= getLoginIcon('login_icon_user', 'includesimg/login/login_icon_user.png', '50px', 'position: absolute; top: 50%; transform: translateY(-50%); inset-inline-start: 5px;') ?>
                            <input type="text" id="login_identifier" name="login_identifier" value="<?= htmlspecialchars($saved_login) ?>" style="padding-inline-start: 65px; padding-inline-end: 15px;" required>
                        </div>
                    </div>

                    <div class="form-group password-group">
                        <label for="password"><?= __('password') ?? 'كلمة المرور' ?></label>
                        <div class="input-group" style="position: relative;">
                            <?= getLoginIcon('login_icon_password', 'includesimg/login/login_icon_password.png', '50px', 'position: absolute; top: 50%; transform: translateY(-50%); inset-inline-start: 5px;') ?>
                            <input type="password" id="password" name="password" style="padding-inline-start: 65px; padding-inline-end: 65px;" required>
                            
                            <?= getLoginIcon('login_icon_eye', 'includesimg/login/login_icon_eye.png', '50px', 'position: absolute; top: 50%; transform: translateY(-50%); inset-inline-end: 5px; cursor: pointer;', 'toggleEyeIcon', 'toggle-password', 'togglePasswordCustom()') ?>
                        </div>
                    </div>
                    
                    <div class="remember-me-container">
                        <input type="checkbox" id="remember_me" name="remember_me" <?= !empty($saved_login) ? 'checked' : '' ?>>
                        <label for="remember_me"><?= __('remember_me') ?? 'تذكر بيانات الدخول' ?></label>
                    </div>
                    
                    <button type="submit" style="display:flex; align-items:center; justify-content:center; gap:12px; height: 60px; font-size: 18px;">
                        <span><?= __('login_btn') ?? 'دخول' ?></span> 
                        <?= getLoginIcon('login_icon_btn', 'includesimg/login/login_icon_btn.png', '50px') ?>
                    </button>
                </form>

                <div class="switch-form">
                    <p><?= __('no_account') ?? 'ليس لديك حساب؟' ?> <a href="signup.php"><?= __('create_account') ?? 'أنشئ حساباً جديداً' ?></a>.</p>
                    <p><a href="forgot_password.php"><?= __('forgot_password') ?? 'هل نسيت كلمة المرور؟' ?></a></p>
                </div>
            </div>
        </div>
    </main>

    <script>
        function togglePasswordCustom() {
            var passwordInput = document.getElementById('password');
            // يمكنك تغيير مصدر أيقونة العين إذا كانت لديك أيقونة "عين مغلقة"
            // var eyeIcon = document.getElementById('toggleEyeIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                // eyeIcon.style.opacity = '0.5'; // تأثير بصري بسيط دلالة على الفتح
            } else {
                passwordInput.type = 'password';
                // eyeIcon.style.opacity = '1';
            }
        }
    </script>
    <script src="assets/js/login.js"></script>
</body>
</html>