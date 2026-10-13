<?php
session_start();
require_once '../config/db_connexion.php';

// --- جلب الأيقونات الديناميكية الخاصة بصفحة سياسة الخصوصية ---
$privacy_assets = [];
try {
    $assets_query = "SELECT asset_key, file_path FROM design_assets WHERE page_section = 'PrivacyPolicy'";
    $stmt_assets = $pdo->query($assets_query);
    while ($row = $stmt_assets->fetch(PDO::FETCH_ASSOC)) {
        $privacy_assets[$row['asset_key']] = $row['file_path'];
    }
} catch (PDOException $e) { }

function get_privacy_icon($key, $default = '../assets/images/favicon.png') {
    global $privacy_assets;
    // إضافة ../ للرجوع للمجلد الرئيسي حيث يوجد مجلد uploads
    return isset($privacy_assets[$key]) ? '../' . htmlspecialchars($privacy_assets[$key]) : $default;
}
// ----------------------------------------------------

// نظام الترجمة المصغر لضمان عمل الدوال إذا لم يتم تعريفها مسبقاً
$lang = $_SESSION['lang'] ?? 'ar';
$lang_file = __DIR__ . "/../lang/{$lang}.php";
if (file_exists($lang_file)) {
    $translations = require_once $lang_file; 
} else {
    $translations = [];
}

if (!function_exists('__')) {
    function __($key) {
        global $translations;
        return $translations[$key] ?? $key;
    }
}
?>
<!DOCTYPE html>
<html lang="<?= __('lang_code') ?? 'ar' ?>" dir="<?= __('lang_dir') ?? 'rtl' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('privacy_page_title') ?></title>
    <meta name="description" content="<?= __('privacy_meta_desc') ?>">
    <link rel="icon" type="image/svg+xml" href="../assets/images/favicon.svg">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/pages/legal.css">
</head>
<body class="font-tajawal overflow-x-hidden">

    <script>
        (function() {
            const savedTheme = localStorage.getItem('mo7adaraty_theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>

    <?php 
    $base_url = '/mo7adaraty/';
    require_once '../templates/header.php'; 
    ?>

    <main class="legal-page-wrapper">
        <!-- بداية قسم الـ Hero المعدل لصفحة سياسة الخصوصية لدعم فيديو الانترو -->
        <section class="legal-hero py-20 md:py-32 relative overflow-hidden">
            
            <!-- عنصر الفيديو بكامله وبدون أي قص ليمط ويأخذ حجم السيكشن بالكامل -->
            <video autoplay muted loop playsinline class="absolute top-0 left-0 w-full h-full object-fill z-0 pointer-events-none opacity-25">
                <source src="../assets/videos/PrivacyPolicy.mp4" type="video/mp4">
                <?= __('برجاء تحديث المتصفح لدعم تشغيل الفيديو') ?>
            </video>
            
            <!-- طبقة فلتر خفيف للمساعدة في دمج ألوان الفيديو مع هوية المنصة البصرية -->
            <div class="absolute top-0 left-0 w-full h-full bg-black/5 z-0"></div>
            
            <!-- محتوى النصوص الرئيسي مع رفعه للطبقة z-10 لضمان وضوحه التام -->
            <div class="container mx-auto px-4 relative z-10">
                <div class="text-center" data-aos="fade-up">
                    <h1 class="text-4xl md:text-6xl font-bold mb-6"><?= __('privacy_heading') ?></h1>
                    <p class="text-xl md:text-2xl max-w-3xl mx-auto leading-relaxed opacity-90">
                        <?= __('privacy_hero_desc') ?>
                    </p>
                    <div class="mt-8 inline-block bg-[var(--bg-surface)] text-[var(--text-primary)] rounded-full px-6 py-3 shadow-md border border-[var(--border-color)]">
                        <span class="text-sm md:text-base font-bold"><?= __('privacy_last_updated') ?> <?php echo date('Y/m/d'); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- حاوية الموجة السفلي مع الحفاظ على تماسك اتصاله بباقي كتل الصفحة z-10 -->
            
        </section>
        <!-- نهاية قسم الـ Hero المعدل -->

        <div class="container mx-auto px-4 py-16 md:py-24 relative z-10">
            
            <div class="legal-card p-8 md:p-10" data-aos="fade-up">
                <div class="flex flex-col md:flex-row items-start">
                    <div class="legal-icon-wrapper flex items-center justify-center flex-shrink-0 ml-0 md:ml-6">
                        <img src="<?= get_privacy_icon('icon_shield') ?>" class="w-30 h-30 object-contain" alt="shield">
                    </div>
                    <div>
                        <h2 class="legal-title"><?= __('privacy_sec1_title') ?></h2>
                        <p class="legal-text">
                            <?= sprintf(__('privacy_sec1_p1'), $base_url) ?>
                        </p>
                        <p class="legal-text">
                            <?= __('privacy_sec1_p2') ?>
                        </p>
                    </div>
                </div>
            </div>

            <div class="legal-card p-8 md:p-10" data-aos="fade-up">
                <div class="flex flex-col md:flex-row items-start">
                    <div class="legal-icon-wrapper flex items-center justify-center flex-shrink-0 ml-0 md:ml-6">
                        <img src="<?= get_privacy_icon('icon_database') ?>" class="w-35 h-35 object-contain" alt="database">
                    </div>
                    <div class="w-full">
                        <h2 class="legal-title"><?= __('privacy_sec2_title') ?></h2>
                        
                        <h3 class="text-xl font-bold mt-6 mb-4 text-[var(--brand-primary)]"><?= __('privacy_sec2_subtitle1') ?></h3>
                        <ul class="legal-list">
                            <li><strong><?= __('privacy_info_reg_title') ?></strong> <?= __('privacy_info_reg_desc') ?></li>
                            <li><strong><?= __('privacy_info_profile_title') ?></strong> <?= __('privacy_info_profile_desc') ?></li>
                            <li><strong><?= __('privacy_info_content_title') ?></strong> <?= __('privacy_info_content_desc') ?></li>
                            <li><strong><?= __('privacy_info_contact_title') ?></strong> <?= __('privacy_info_contact_desc') ?></li>
                        </ul>
                        
                        <h3 class="text-xl font-bold mt-8 mb-4 text-[var(--brand-primary)]"><?= __('privacy_sec2_subtitle2') ?></h3>
                        <ul class="legal-list">
                            <li><strong><?= __('privacy_auto_log_title') ?></strong> <?= __('privacy_auto_log_desc') ?></li>
                            <li><strong><?= __('privacy_auto_cookie_title') ?></strong> <?= __('privacy_auto_cookie_desc') ?></li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="legal-card p-8 md:p-10" data-aos="fade-up">
                <div class="flex flex-col md:flex-row items-start">
                    <div class="legal-icon-wrapper flex items-center justify-center flex-shrink-0 ml-0 md:ml-6">
                        <img src="<?= get_privacy_icon('icon_cookie') ?>" class="w-20 h-20 object-contain" alt="cookie">
                    </div>
                    <div class="w-full">
                        <h2 class="legal-title"><?= __('privacy_sec3_title') ?></h2>
                        <p class="legal-text">
                            <?= __('privacy_sec3_p1') ?>
                        </p>
                        <div class="legal-highlight">
                            <p class="mb-2 font-bold flex items-center">
                                <img src="<?= get_privacy_icon('icon_info') ?>" class="w-20 h-20 object-contain ml-2" alt="info"> 
                                <?= __('privacy_cookie_note_title') ?>
                            </p>
                            <p class="legal-text mb-0">
                                <?= __('privacy_cookie_note_desc') ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                <div class="legal-card p-8" data-aos="fade-up">
                    <div class="legal-icon-wrapper flex items-center justify-center mb-4">
                        <img src="<?= get_privacy_icon('icon_cogs') ?>" class="w-20 h-20 object-contain" alt="settings">
                    </div>
                    <h2 class="legal-title"><?= __('privacy_sec4_title') ?></h2>
                    <ul class="legal-list">
                        <li><?= __('privacy_use_1') ?></li>
                        <li><?= __('privacy_use_2') ?></li>
                        <li><?= __('privacy_use_3') ?></li>
                        <li><?= __('privacy_use_4') ?></li>
                        <li><?= __('privacy_use_5') ?></li>
                    </ul>
                </div>
                
                <div class="legal-card p-8" data-aos="fade-up" data-aos-delay="100">
                    <div class="legal-icon-wrapper flex items-center justify-center mb-4">
                        <img src="<?= get_privacy_icon('icon_share') ?>" class="w-20 h-20 object-contain" alt="share">
                    </div>
                    <h2 class="legal-title"><?= __('privacy_sec5_title') ?></h2>
                    <ul class="legal-list">
                        <li><strong><?= __('privacy_share_1_title') ?></strong> <?= __('privacy_share_1_desc') ?></li>
                        <li><strong><?= __('privacy_share_2_title') ?></strong> <?= __('privacy_share_2_desc') ?></li>
                        <li><strong><?= __('privacy_share_3_title') ?></strong> <?= __('privacy_share_3_desc') ?></li>
                    </ul>
                </div>
            </div>

            <div class="legal-card p-8 md:p-10" data-aos="fade-up">
                <div class="flex flex-col md:flex-row items-start">
                    <div class="legal-icon-wrapper flex items-center justify-center flex-shrink-0 ml-0 md:ml-6">
                        <img src="<?= get_privacy_icon('icon_lock') ?>" class="w-20 h-20 object-contain" alt="lock">
                    </div>
                    <div class="w-full">
                        <h2 class="legal-title"><?= __('privacy_sec6_title') ?></h2>
                        <p class="legal-text">
                            <?= __('privacy_sec6_p1') ?>
                        </p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                            <div class="flex items-center p-4 border border-[var(--border-color)] rounded-lg bg-[var(--bg-light)]">
                                <img src="<?= get_privacy_icon('icon_user_shield') ?>" class="w-20 h-20 object-contain ml-4" alt="user shield">
                                <div>
                                    <h4 class="font-bold text-[var(--text-primary)]"><?= __('privacy_protect_acc_title') ?></h4>
                                    <p class="text-sm text-[var(--text-secondary)] m-0"><?= __('privacy_protect_acc_desc') ?></p>
                                </div>
                            </div>
                            <div class="flex items-center p-4 border border-[var(--border-color)] rounded-lg bg-[var(--bg-light)]">
                                <img src="<?= get_privacy_icon('icon_server') ?>" class="w-20 h-20 object-contain ml-4" alt="server">
                                <div>
                                    <h4 class="font-bold text-[var(--text-primary)]"><?= __('privacy_protect_servers_title') ?></h4>
                                    <p class="text-sm text-[var(--text-secondary)] m-0"><?= __('privacy_protect_servers_desc') ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-20" data-aos="fade-up">
                <div class="text-center mb-10">
                    <h2 class="text-3xl font-bold mb-4 text-[var(--text-primary)]"><?= __('privacy_priority_title') ?></h2>
                    <p class="text-lg text-[var(--text-secondary)] max-w-2xl mx-auto"><?= __('privacy_priority_subtitle') ?></p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
                    <div class="legal-stat-card">
                        <div class="legal-stat-num">100%</div>
                        <div class="text-[var(--text-secondary)] font-bold"><?= __('privacy_stat_encrypted') ?></div>
                    </div>
                    <div class="legal-stat-card">
                        <div class="legal-stat-num">24/7</div>
                        <div class="text-[var(--text-secondary)] font-bold"><?= __('privacy_stat_monitoring') ?></div>
                    </div>
                    <div class="legal-stat-card">
                        <div class="legal-stat-num">0%</div>
                        <div class="text-[var(--text-secondary)] font-bold"><?= __('privacy_stat_no_sell') ?></div>
                    </div>
                    <div class="legal-stat-card">
                        <div class="legal-stat-num flex items-center justify-center">
                            <img src="<?= get_privacy_icon('icon_check') ?>" class="w-20 h-20 object-contain" alt="check">
                        </div>
                        <div class="text-[var(--text-secondary)] font-bold"><?= __('privacy_stat_transparency') ?></div>
                    </div>
                </div>
            </div>

            <div class="mt-16 text-center" data-aos="zoom-in">
                <div class="inline-block bg-[var(--bg-surface)] border border-[var(--border-color)] rounded-2xl p-10 max-w-3xl shadow-lg">
                    <h3 class="text-2xl font-bold mb-4 text-[var(--text-primary)]"><?= __('privacy_faq_title') ?></h3>
                    <p class="text-lg text-[var(--text-secondary)] mb-8"><?= __('privacy_faq_desc') ?></p>
                    <a href="ContactUs.php" class="inline-flex items-center gap-2 bg-[var(--brand-primary)] text-white px-8 py-3 rounded-full font-bold hover:bg-[var(--brand-hover)] transition-colors shadow-md">
                        <img src="<?= get_privacy_icon('icon_envelope') ?>" class="w-20 h-20 object-contain" alt="contact"> 
                        <?= __('privacy_contact_btn') ?>
                    </a>
                </div>
            </div>

        </div>
    </main>

    <?php require_once '../templates/footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script src="../assets/js/legal.js"></script>
</body>
</html>