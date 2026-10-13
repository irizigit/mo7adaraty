<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/db_connexion.php';

// --- جلب الأيقونات الديناميكية الخاصة بصفحة شروط الخدمة ---
$tos_assets = [];
try {
    $assets_query = "SELECT asset_key, file_path FROM design_assets WHERE page_section = 'TermsOfService'";
    $stmt_assets = $pdo->query($assets_query);
    while ($row = $stmt_assets->fetch(PDO::FETCH_ASSOC)) {
        $tos_assets[$row['asset_key']] = $row['file_path'];
    }
} catch (PDOException $e) { }

function get_tos_icon($key, $default = '../assets/images/favicon.png') {
    global $tos_assets;
    // إضافة ../ للرجوع للمجلد الرئيسي حيث يوجد مجلد uploads
    return isset($tos_assets[$key]) ? '../' . htmlspecialchars($tos_assets[$key]) : $default;
}
// ----------------------------------------------------

// تحديد المسار الأساسي للمشروع
$base_url = '/mo7adaraty/';

// جلب اللغة الحالية لتحديد الاتجاه
$current_lang = $_SESSION['lang'] ?? 'ar';
$text_dir = ($current_lang === 'ar') ? 'rtl' : 'ltr';

// تحميل ملف الترجمة
$lang_file = __DIR__ . '/../lang/' . $current_lang . '.php';
$translations = file_exists($lang_file) ? include $lang_file : include __DIR__ . '/../lang/ar.php';

// دالة الترجمة مع النص الافتراضي كبديل
if (!function_exists('__')) {
    function __($key, $default = '') {
        global $translations;
        return $translations[$key] ?? ($default ?: $key);
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $current_lang ?>" dir="<?= $text_dir ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('terms_of_service', 'شروط الخدمة') ?> - Mo7adaraty</title>
    <meta name="description" content="<?= __('tos_desc', 'شروط وأحكام استخدام منصة محاضراتي التعليمية') ?>">
    <link rel="icon" type="image/svg+xml" href="../assets/images/favicon.svg">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/pages/legal.css">
    
    <style>
        /* توافق النصوص وهوامش الأيقونات بناءً على اتجاه اللغة */
        body { text-align: <?= $text_dir === 'rtl' ? 'right' : 'left' ?>; }
        .legal-icon-wrapper { 
            <?= $text_dir === 'rtl' ? 'margin-left: 1.5rem; margin-right: 0;' : 'margin-right: 1.5rem; margin-left: 0;' ?> 
        }
        .legal-table {
            text-align: <?= $text_dir === 'rtl' ? 'right' : 'left' ?>;
        }
        .legal-highlight img.inline-icon {
            <?= $text_dir === 'rtl' ? 'margin-left: 0.5rem; margin-right: 0;' : 'margin-right: 0.5rem; margin-left: 0;' ?>
        }
    </style>
</head>
<body class="font-tajawal overflow-x-hidden">

    <script>
        
        (function() {
            const savedTheme = localStorage.getItem('mo7adaraty_theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>

    <?php require_once '../templates/header.php'; ?>

    <main class="legal-page-wrapper">
<!-- بداية قسم الـ Hero المعدل لصفحة شروط الخدمة -->
        <section class="legal-hero py-20 md:py-32 relative overflow-hidden">
            
            <!-- عنصر الفيديو كخلفية حية متحركة للمقدمة -->
            <video autoplay muted loop playsinline class="absolute top-0 left-0 w-full h-full object-fill z-0 pointer-events-none opacity-25">
    <source src="../assets/videos/TermsOfService.mp4" type="video/mp4">
    <?= __('برجاء تحديث المتصفح لدعم تشغيل الفيديو') ?>
</video>
            <!-- طبقة فلتر خفيف للمساعدة في دمج ألوان الفيديو مع هوية المنصة البصرية -->
            <div class="absolute top-0 left-0 w-full h-full bg-black/5 z-0"></div>
            <!-- محتوى النصوص الرئيسي مع رفعه للطبقة z-10 لضمان وضوحه التام -->
            <div class="container mx-auto px-4 relative z-10">
                <div class="text-center" data-aos="fade-up">
                    <h1 class="text-4xl md:text-6xl font-bold mb-6"><?= __('terms_of_service', 'شروط الخدمة') ?></h1>
                    <p class="text-xl md:text-2xl max-w-3xl mx-auto leading-relaxed opacity-90">
                        <?= __('tos_hero_desc', 'توضح هذه الشروط والأحكام القواعد التي تحكم استخدامك لمنصة وخدمات "محاضراتي". يرجى قراءتها بعناية.') ?>
                    </p>
                    <div class="mt-8 inline-block bg-[var(--bg-surface)] text-[var(--text-primary)] rounded-full px-6 py-3 shadow-md border border-[var(--border-color)]">
                        <span class="text-sm md:text-base font-bold"><?= __('tos_last_update', 'آخر تحديث:') ?> <?php echo date('Y/m/d'); ?></span>
                    </div>
                </div>
            </div>
            
          
        </section>
        <!-- نهاية قسم الـ Hero المعدل -->        <div class="container mx-auto px-4 py-16 md:py-24 relative z-10">
            
            <div class="legal-card p-8 md:p-10" data-aos="fade-up">
                <div class="flex flex-col md:flex-row items-start">
                    <div class="legal-icon-wrapper flex items-center justify-center flex-shrink-0 ml-0 md:ml-0">
                        <img src="<?= get_tos_icon('icon_handshake') ?>" class="w-100 h-100 object-contain" alt="handshake">
                    </div>
                    <div class="w-full">
                        <h2 class="legal-title"><?= __('tos_sec1_title', 'المقدمة وقبول الشروط') ?></h2>
                        <p class="legal-text">
                            <?= __('tos_sec1_p1', 'مرحبًا بك في منصة المحاضرات. توضح هذه الشروط والأحكام ("الشروط") القواعد التي تحكم استخدامك لمنصة وخدمات الموقع.') ?>
                        </p>
                        <p class="legal-text">
                            <?= __('tos_sec1_p2', 'من خلال الوصول إلى الموقع، أو التسجيل للحصول على حساب، أو تصفح المحتوى، فإنك تقر بأنك قرأت وفهمت وتوافق على الالتزام الكامل بهذه الشروط. إذا كنت لا توافق، يجب التوقف عن الاستخدام فوراً.') ?>
                        </p>
                        <div class="legal-highlight">
                            <strong><?= __('important_note', 'ملاحظة هامة:') ?></strong> <?= __('tos_sec1_note', 'استخدامك للموقع يعتبر قبولاً صريحاً لهذه الشروط والأحكام بكامل بنودها.') ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="legal-card p-8 md:p-10" data-aos="fade-up">
                <div class="flex flex-col md:flex-row items-start">
                    <div class="legal-icon-wrapper flex items-center justify-center flex-shrink-0 ml-0 md:ml-0">
                        <img src="<?= get_tos_icon('icon_user_circle') ?>" class="w-100 h-100 object-contain" alt="user account">
                    </div>
                    <div class="w-full">
                        <h2 class="legal-title"><?= __('tos_sec2_title', 'الحسابات والتسجيل') ?></h2>
                        <ul class="legal-list">
                            <li><strong><?= __('tos_sec2_li1_title', 'أهلية المستخدم:') ?></strong> <?= __('tos_sec2_li1_desc', 'يجب أن تكون طالباً جامعياً أو مهتماً بالمحتوى الأكاديمي المتاح.') ?></li>
                            <li><strong><?= __('tos_sec2_li2_title', 'دقة المعلومات:') ?></strong> <?= __('tos_sec2_li2_desc', 'أنت توافق على تقديم معلومات دقيقة وكاملة أثناء عملية التسجيل.') ?></li>
                            <li><strong><?= __('tos_sec2_li3_title', 'أمان الحساب:') ?></strong> <?= __('tos_sec2_li3_desc', 'أنت مسؤول مسؤولية كاملة عن الحفاظ على سرية كلمة المرور وعن جميع الأنشطة في حسابك.') ?></li>
                            <li><strong><?= __('tos_sec2_li4_title', 'ملكية الحساب:') ?></strong> <?= __('tos_sec2_li4_desc', 'الحسابات شخصية ولا يجوز نقلها أو مشاركتها مع أشخاص آخرين.') ?></li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="legal-card p-8 md:p-10" data-aos="fade-up">
                <div class="flex flex-col md:flex-row items-start">
                    <div class="legal-icon-wrapper flex items-center justify-center flex-shrink-0 ml-0 md:ml-0">
                        <img src="<?= get_tos_icon('icon_file_upload') ?>" class="w-100 h-100 object-contain" alt="upload content">
                    </div>
                    <div class="w-full">
                        <h2 class="legal-title"><?= __('tos_sec3_title', 'المحتوى الذي ينشئه المستخدم') ?></h2>
                        <p class="legal-text">
                            <?= __('tos_sec3_p1', 'يشمل "محتوى المستخدم" أي ملفات (محاضرات، امتحانات، ملخصات)، منشورات، تعليقات، أو رسائل تقوم برفعها.') ?>
                        </p>
                        <p class="legal-text font-bold">
                            <?= __('tos_sec3_p2', 'أنت وحدك المسؤول عن محتوى المستخدم الذي تنشره وتقر بأنك تمتلك الحقوق اللازمة لمشاركته.') ?>
                        </p>
                        <div class="legal-highlight border-[var(--danger-color)] flex items-start">
                            <img src="<?= get_tos_icon('icon_ban') ?>" class="inline-icon w-10 h-10 object-contain mt-1" alt="ban">
                            <div>
                                <strong class="text-[var(--danger-color)]"><?= __('tos_sec3_ban_title', 'المحتوى المحظور:') ?></strong> 
                                <?= __('tos_sec3_ban_desc', 'يُمنع منعاً باتاً نشر أي محتوى ينتهك حقوق الطبع والنشر، أو محتوى مسيء، غير لائق، أو يحض على الكراهية. سيتم حذف المحتوى المخالف وحظر الحساب فوراً.') ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="legal-card p-8 md:p-10" data-aos="fade-up">
                <div class="flex flex-col md:flex-row items-start">
                    <div class="legal-icon-wrapper flex items-center justify-center flex-shrink-0 ml-0 md:ml-0">
                        <img src="<?= get_tos_icon('icon_gavel') ?>" class="w-100 h-100 object-contain" alt="law">
                    </div>
                    <div class="w-full">
                        <h2 class="legal-title"><?= __('tos_sec4_title', 'القانون الحاكم وحل النزاعات') ?></h2>
                        <p class="legal-text">
                            <?= __('tos_sec4_p1', 'تخضع هذه الشروط وتُفسر وفقًا للقوانين المعمول بها. أي نزاع سيتم حله حصريًا من قبل المحاكم المختصة.') ?>
                        </p>
                        
                        <table class="legal-table mt-6 w-full">
                            <thead>
                                <tr>
                                    <th><?= __('tos_table_h1', 'البند') ?></th>
                                    <th><?= __('tos_table_h2', 'التفصيل') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><?= __('tos_table_r1_c1', 'القانون المطبق') ?></td>
                                    <td><?= __('tos_table_r1_c2', 'القوانين والتشريعات المحلية المعمول بها') ?></td>
                                </tr>
                                <tr>
                                    <td><?= __('tos_table_r2_c1', 'إخلاء المسؤولية') ?></td>
                                    <td><?= __('tos_table_r2_c2', 'المنصة غير مسؤولة عن دقة المحتوى المرفوع من قبل الطلاب') ?></td>
                                </tr>
                                <tr>
                                    <td><?= __('tos_table_r3_c1', 'لغة العقد') ?></td>
                                    <td><?= __('tos_table_r3_c2', 'اللغة العربية هي اللغة المعتمدة') ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="mt-16 text-center" data-aos="zoom-in">
                <div class="inline-block bg-[var(--bg-surface)] border border-[var(--border-color)] rounded-2xl p-10 max-w-3xl shadow-lg">
                    <h3 class="text-2xl font-bold mb-4 text-[var(--text-primary)]"><?= __('tos_contact_title', 'هل لديك استفسار حول هذه الشروط؟') ?></h3>
                    <p class="text-lg text-[var(--text-secondary)] mb-8"><?= __('tos_contact_desc', 'نحن هنا لمساعدتك في فهم كافة التفاصيل بوضوح وشفافية.') ?></p>
                    <a href="ContactUs.php" class="inline-flex items-center gap-2 bg-[var(--brand-primary)] text-white px-8 py-3 rounded-full font-bold hover:bg-[var(--brand-hover)] transition-colors shadow-md">
                        <img src="<?= get_tos_icon('icon_headset') ?>" class="<?= $text_dir === 'rtl' ? 'ml-7' : 'mr-7' ?> w-10 h-10 object-contain" alt="contact">
                        <?= __('contact_us', 'التواصل معنا') ?>
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