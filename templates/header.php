<?php
// 1. بدء الجلسة بشكل آمن
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// تحديد المسار الأساسي للمشروع
$base_url = '/mo7adaraty/';

// 2. التقاط تغيير اللغة
if (isset($_GET['lang']) && in_array($_GET['lang'], ['ar', 'en', 'fr'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
$current_lang = $_SESSION['lang'] ?? 'ar';

// ========================================================================
// 3. تحميل محرك الترجمة 
// ========================================================================
$lang_file = __DIR__ . '/../lang/' . $current_lang . '.php';
if (file_exists($lang_file)) {
    $translations = include $lang_file;
} else {
    $fallback_file = __DIR__ . '/../lang/ar.php';
    $translations = file_exists($fallback_file) ? include $fallback_file : [];
}

if (!function_exists('__')) {
    function __($key, $default = '') {
        global $translations;
        return $translations[$key] ?? ($default ?: $key);
    }
}

// 4. تحديد اتجاه الصفحة بناءً على اللغة
$text_dir = ($current_lang === 'ar') ? 'rtl' : 'ltr';

$unread_notifications_count = 0;
$unread_announcements_count = 0;
$notifications = [];

if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../config/db_connexion.php';
    
    $db = isset($pdo) ? $pdo : (isset($conn) ? $conn : null);
    
    if ($db) {
        // أ. جلب عدد وقائمة الإشعارات
        $stmt_count = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt_count->execute([$_SESSION['user_id']]);
        $unread_notifications_count = (int)$stmt_count->fetchColumn();

        $stmt = $db->prepare("
            SELECT message, link, is_read, created_at
            FROM notifications
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT 5
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ب. حساب عدد الإعلانات غير المقروءة للطالب
        try {
            $db->exec("
                CREATE TABLE IF NOT EXISTS announcement_reads (
                    user_id INT NOT NULL,
                    announcement_id INT NOT NULL,
                    read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (user_id, announcement_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");

            $ann_pk = 'id';
            $col_check = $db->query("SHOW COLUMNS FROM announcements LIKE 'id'")->fetch();
            if (!$col_check) {
                $ann_pk = 'announcement_id';
            }

            $stmt_ann_count = $db->prepare("
                SELECT COUNT(DISTINCT a.{$ann_pk})
                FROM announcements a
                WHERE (
                    a.target_type = 'all'
                    OR (
                        a.target_type = 'specific'
                        AND EXISTS (
                            SELECT 1 
                            FROM student_enrollments se
                            JOIN course_offerings co ON se.offering_id = co.offering_id
                            WHERE se.user_id = :user_id 
                            AND co.academic_year_id = a.academic_year_id
                            AND (a.division_id IS NULL OR co.division_id = a.division_id)
                        )
                    )
                )
                AND NOT EXISTS (
                    SELECT 1 
                    FROM announcement_reads ar 
                    WHERE ar.user_id = :user_id 
                    AND ar.announcement_id = a.{$ann_pk}
                )
            ");
            $stmt_ann_count->execute(['user_id' => $_SESSION['user_id']]);
            $unread_announcements_count = (int)$stmt_ann_count->fetchColumn();
        } catch (Exception $e) {
            $unread_announcements_count = 0;
        }
    }
}

// ========================================================================
// 5. جلب بيانات التصميم (الملف الشخصي + الهيدر) من قاعدة البيانات
// ========================================================================
$header_icons = [];
try {
    $db = isset($pdo) ? $pdo : (isset($conn) ? $conn : null);
    if ($db) {
        $stmt_assets = $db->query("SELECT asset_key, file_path, page_section, asset_type FROM design_assets WHERE page_section IN ('student_profile', 'header')");
        $dynamic_assets = $stmt_assets->fetchAll(PDO::FETCH_ASSOC);
        
        if ($dynamic_assets) {
            echo "\n<style>\n:root {\n";
            foreach ($dynamic_assets as $asset) {
                if ($asset['page_section'] === 'student_profile') {
                    $css_var = '--' . str_replace('_', '-', $asset['asset_key']);
                    $file_url = rtrim($base_url, '/') . '/' . ltrim($asset['file_path'], '/');
                    echo "    {$css_var}: url('{$file_url}') !important;\n";
                } elseif ($asset['page_section'] === 'header') {
                    $header_icons[$asset['asset_key']] = $asset['file_path'];
                    if ($asset['asset_type'] === 'image' && strpos($asset['asset_key'], 'bg') !== false) {
                        $css_var = '--' . str_replace('_', '-', $asset['asset_key']);
                        $file_url = rtrim($base_url, '/') . '/' . ltrim($asset['file_path'], '/');
                        echo "    {$css_var}: url('{$file_url}') !important;\n";
                    }
                }
            }
            echo "}\n</style>\n";
        }
    }
} catch (PDOException $e) {}

// ========================================================================
// 6. دالة عرض أيقونات القائمة 
// ========================================================================
if (!function_exists('renderNavIcon')) {
    function renderNavIcon($asset_key, $header_icons, $base_url, $custom_style = '') {
        $file_path = $header_icons[$asset_key] ?? 'assets/images/placeholder.png';
        $image_url = rtrim($base_url, '/') . '/' . ltrim($file_path, '/');
        return '<img src="' . htmlspecialchars($image_url) . '" class="nav__icon-img" alt="" style="'.$custom_style.'" onerror="this.style.display=\'none\'">';
    }
}
?>

<script>
    (function() {
        const savedTheme = localStorage.getItem('mo7adaraty_theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
    })();
</script>

<style>
    body { direction: <?= $text_dir ?> !important; text-align: <?= $text_dir === 'rtl' ? 'right' : 'left' ?>; }
    .nav__dropdown-menu { <?= $text_dir === 'ltr' ? 'left: 0; transform: translateY(10px);' : 'left: 50%; transform: translateX(-50%) translateY(10px);' ?> }
    .nav__item--dropdown:hover .nav__dropdown-menu { <?= $text_dir === 'ltr' ? 'transform: translateY(5px);' : 'transform: translateX(-50%) translateY(5px);' ?> }
    .theme-icon-container { display: flex; align-items: center; justify-content: center; width: 24px; height: 24px; transition: transform 0.5s ease; }
    .theme-icon-container img { width: 100%; height: 100%; object-fit: contain; }
    
    .nav__item-timetable {
        border-radius: 8px;
        transition: all 0.3s ease;
    }
    .nav__item-timetable:hover {
        background: rgba(26, 115, 232, 0.05);
    }
</style>

<link rel="stylesheet" href="<?= $base_url ?>assets/css/global.css">
<script src="<?= $base_url ?>assets/js/main.js" defer></script>

<header class="header" style="background-image: var(--header-bg, none); background-size: cover; background-position: center;">
    <div class="header__container">
        
        <div class="header__logo">
            <a href="<?= $base_url ?>home.php" class="header__logo-link">
                <?php 
                $logo_key = 'header_logo_' . $current_lang;
                $logo_path = $header_icons[$logo_key] ?? ($header_icons['header_logo_ar'] ?? '');
                
                if (!empty($logo_path)) {
                    $logo_url = rtrim($base_url, '/') . '/' . ltrim($logo_path, '/');
                    echo '<img src="' . htmlspecialchars($logo_url) . '" alt="' . __('site_name', 'محاضراتي') . '" class="header__logo-img" style="width: 100px; height: 100px; object-fit: contain;" onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'block\';">';   
                    echo '<span style="display:none;">' . __('site_name', 'محاضراتي') . '</span>';
                } else {
                    echo __('site_name', 'محاضراتي');
                }
                ?>
            </a>
        </div>

        <nav class="header__nav" id="mainNav">
            <ul class="nav__list">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <li class="nav__item">
                            <a href="<?= $base_url ?>admin/index.php" class="nav__link">
                                <?= renderNavIcon('icon_dashboard', $header_icons, $base_url) ?> 
                                <span class="nav__text"><?= __('dashboard', 'لوحة التحكم') ?></span>
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <li class="nav__item">
                        <a href="<?= $base_url ?>student/profil.php" class="nav__link">
                            <?= renderNavIcon('icon_profile', $header_icons, $base_url) ?> 
                            <span class="nav__text"><?= __('my_profile', 'ملفي الشخصي') ?></span>
                        </a>
                    </li>
                    
                    <li class="nav__item">
                        <a href="<?= $base_url ?>student/community/index.php" class="nav__link">
                            <?= renderNavIcon('icon_community', $header_icons, $base_url) ?> 
                            <span class="nav__text"><?= __('community', 'ساحة المشاركات') ?></span>
                        </a>
                    </li>
                    
                    <li class="nav__item">
                        <a href="<?= $base_url ?>student/announcements.php" class="nav__link">
                            <?= renderNavIcon('icon_announcements', $header_icons, $base_url) ?> 
                            <span class="nav__text"><?= __('announcements', 'الإعلانات') ?></span>
                            <?php if ($unread_announcements_count > 0): ?>
                                <span class="nav__badge"><?php echo $unread_announcements_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    
                    <li class="nav__item">
                        <a href="<?= $base_url ?>student/archive.php" class="nav__link nav__link--btn">
                            <?= renderNavIcon('icon_archive', $header_icons, $base_url) ?> 
                            <span class="nav__text"><?= __('archive', 'أرشيف المواسم') ?></span>
                        </a>
                    </li>
                    
                    <li class="nav__item">
                        <a href="<?= $base_url ?>student/whatsapp_groups/index.php" class="nav__link">
                            <?= renderNavIcon('icon_whatsapp', $header_icons, $base_url) ?> 
                            <span class="nav__text"><?= __('whatsapp_groups', 'مجموعاتنا') ?></span>
                        </a>
                    </li>
                    
                    <li class="nav__item">
                        <a href="<?= $base_url ?>student/contact/index.php" class="nav__link">
                            <?= renderNavIcon('icon_contact', $header_icons, $base_url) ?> 
                            <span class="nav__text"><?= __('contact_us', 'التواصل معنا') ?></span>
                        </a>
                    </li>
                    
                    <li class="nav__item nav__item--dropdown">
                        <a href="<?= $base_url ?>student/notifications.php" class="nav__link">
                            <?= renderNavIcon('icon_notifications', $header_icons, $base_url) ?>
                            <span class="nav__text"><?= __('notifications', 'الإشعارات') ?></span>
                            <?php if ($unread_notifications_count > 0): ?>
                                <span class="nav__badge"><?php echo $unread_notifications_count; ?></span>
                            <?php endif; ?>
                        </a>
                        <div class="nav__dropdown-menu nav__dropdown-menu--notifications">
                            <?php if (!empty($notifications)): ?>
                                <?php foreach ($notifications as $n): ?>
                                    <a class="nav__dropdown-item" href="<?= $base_url . htmlspecialchars(ltrim($n['link'], '/')) ?>">
                                        <span class="nav__text"><?php echo htmlspecialchars($n['message']); ?></span>
                                        <small class="nav__dropdown-time"><?php echo date("d/m H:i", strtotime($n['created_at'])); ?></small>
                                    </a>
                                <?php endforeach; ?>
                                <a class="nav__dropdown-item" style="justify-content: center; font-weight: bold; color: var(--brand-primary);" href="<?= $base_url ?>student/notifications.php"><?= __('view_all_notifications', 'عرض كل الإشعارات') ?></a>
                            <?php else: ?>
                                <p style="padding: 15px; text-align: center; color: var(--text-secondary); margin: 0;"><?= __('no_new_notifications', 'لا توجد إشعارات جديدة.') ?></p>
                            <?php endif; ?>
                        </div>
                    </li>
                    
                    <li class="nav__item">
                        <a href="<?= $base_url ?>auth/logout.php" class="nav__link">
                            <?= renderNavIcon('icon_logout', $header_icons, $base_url) ?> 
                            <span class="nav__text"><?= __('logout', 'خروج') ?></span>
                        </a>
                    </li>
                
                <?php else: ?>
                    <li class="nav__item"><a href="<?= $base_url ?>home.php" class="nav__link"><?= renderNavIcon('icon_home', $header_icons, $base_url) ?> <span class="nav__text"><?= __('home', 'الرئيسية') ?></span></a></li>
                    <li class="nav__item"><a href="<?= $base_url ?>login.php" class="nav__link"><?= renderNavIcon('icon_login', $header_icons, $base_url) ?> <span class="nav__text"><?= __('login', 'دخول') ?></span></a></li>
                    <li class="nav__item"><a href="<?= $base_url ?>signup.php" class="nav__link"><?= renderNavIcon('icon_signup', $header_icons, $base_url) ?> <span class="nav__text"><?= __('signup', 'تسجيل') ?></span></a></li>
                    <li class="nav__item"><a href="<?= $base_url ?>pages/TermsOfService.php" class="nav__link"><?= renderNavIcon('icon_terms', $header_icons, $base_url) ?> <span class="nav__text"><?= __('terms_of_service', 'شروط الخدمة') ?></span></a></li>
                    <li class="nav__item"><a href="<?= $base_url ?>pages/ContactUs.php" class="nav__link"><?= renderNavIcon('icon_contact_us', $header_icons, $base_url) ?> <span class="nav__text"><?= __('contact_us', 'تواصل معنا') ?></span></a></li>
                    <li class="nav__item"><a href="<?= $base_url ?>pages/AboutUs.php" class="nav__link"><?= renderNavIcon('icon_about_us', $header_icons, $base_url) ?> <span class="nav__text"><?= __('about_us', 'عن الموقع') ?></span></a></li>
                    <li class="nav__item"><a href="<?= $base_url ?>pages/FAQ.php" class="nav__link"><?= renderNavIcon('icon_faq', $header_icons, $base_url) ?> <span class="nav__text"><?= __('FAQ', 'الأسئلة الشائعة') ?></span></a></li>
                    <li class="nav__item"><a href="<?= $base_url ?>pages/PrivacyPolicy.php" class="nav__link"><?= renderNavIcon('icon_privacy', $header_icons, $base_url) ?> <span class="nav__text"><?= __('privacy_policy', 'سياسة الخصوصية') ?></span></a></li>
                    <li class="nav__item"><a href="<?= $base_url ?>pages/explore_classes.php" class="nav__link"><?= renderNavIcon('icon_explore_classes', $header_icons, $base_url) ?> <span class="nav__text"><?= __('explore_classes', 'استكشاف الفصول') ?></span></a></li>
                <?php endif; ?>

                <li class="nav__item nav__item--dropdown">
                    <a href="javascript:void(0)" class="nav__link" style="background-color: var(--brand-light); color: var(--brand-primary);">
                        <?php 
                            if($current_lang == 'en') {
                                echo renderNavIcon('icon_lang_en', $header_icons, $base_url) . '<span class="nav__text">English</span>';
                            } elseif($current_lang == 'fr') {
                                echo renderNavIcon('icon_lang_fr', $header_icons, $base_url) . '<span class="nav__text">Français</span>';
                            } else {
                                echo renderNavIcon('icon_lang_ar', $header_icons, $base_url) . '<span class="nav__text">عربي</span>';
                            }
                        ?>
                    </a>
                    <div class="nav__dropdown-menu">
                        <a href="?lang=ar" class="nav__dropdown-item"><?= renderNavIcon('icon_lang_ar', $header_icons, $base_url) ?> <span class="nav__text" style="margin: <?= $text_dir === 'ltr' ? '0 0 0 8px' : '0 8px 0 0' ?>;">عربي</span></a>
                        <a href="?lang=en" class="nav__dropdown-item"><?= renderNavIcon('icon_lang_en', $header_icons, $base_url) ?> <span class="nav__text" style="margin: <?= $text_dir === 'ltr' ? '0 0 0 8px' : '0 8px 0 0' ?>;">English</span></a>
                        <a href="?lang=fr" class="nav__dropdown-item"><?= renderNavIcon('icon_lang_fr', $header_icons, $base_url) ?> <span class="nav__text" style="margin: <?= $text_dir === 'ltr' ? '0 0 0 8px' : '0 8px 0 0' ?>;">Français</span></a>
                    </div>
                </li>
            </ul>
        </nav>

        <div class="header__actions">
            <button id="themeToggleBtn" class="theme-toggle-btn" aria-label="تبديل الوضع المظلم">
                <div class="theme-icon-container" id="themeIconContainer">
                    <span id="themeLightIcon" style="display:none;"><?= renderNavIcon('icon_light_mode', $header_icons, $base_url, 'width:100%; height:100%;') ?></span>
                    <span id="themeDarkIcon" style="display:none;"><?= renderNavIcon('icon_dark_mode', $header_icons, $base_url, 'width:100%; height:100%;') ?></span>
                </div>
            </button>
            
            <button class="header__menu-btn" id="menuToggle">
                <?= renderNavIcon('icon_burger', $header_icons, $base_url, 'width:24px;') ?>
            </button>
        </div>

    </div>
</header>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.getElementById('menuToggle');
    const mainNav = document.getElementById('mainNav');
    
    if (menuToggle && mainNav) {
        menuToggle.addEventListener('click', function(e) {
            e.preventDefault();
            mainNav.classList.toggle('is-active');
        });
    }

    const themeToggleBtn = document.getElementById('themeToggleBtn');
    const themeIconContainer = document.getElementById('themeIconContainer');
    const themeLightIcon = document.getElementById('themeLightIcon');
    const themeDarkIcon = document.getElementById('themeDarkIcon');
    const htmlElement = document.documentElement;

    function updateThemeIcon(theme) {
        if (theme === 'dark') {
            themeLightIcon.style.display = 'block';
            themeDarkIcon.style.display = 'none';
        } else {
            themeLightIcon.style.display = 'none';
            themeDarkIcon.style.display = 'block';
        }
    }

    const currentTheme = htmlElement.getAttribute('data-theme') || 'light';
    updateThemeIcon(currentTheme);

    if (themeToggleBtn) {
        themeToggleBtn.addEventListener('click', function() {
            const currentTheme = htmlElement.getAttribute('data-theme') || 'light';
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            htmlElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('mo7adaraty_theme', newTheme);
            updateThemeIcon(newTheme);
            
            if(themeIconContainer) {
                themeIconContainer.style.transform = 'rotate(360deg)';
                setTimeout(() => themeIconContainer.style.transform = 'none', 500);
            }
        });
    }
});
</script>

<?php 
if (isset($_SESSION['user_id'])) {
    include __DIR__ . '/floating_classes.php';
}

include 'scroll_nav.php'; 
?>
<?php 
if (!isset($_SESSION['user_id'])) {
    include __DIR__ . '/floating_guest_social.php';
}
?>