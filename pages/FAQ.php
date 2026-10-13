<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/db_connexion.php';

// --- جلب الأيقونات الديناميكية الخاصة بصفحة الأسئلة الشائعة ---
$faq_assets = [];
try {
    $assets_query = "SELECT asset_key, file_path FROM design_assets WHERE page_section = 'FAQ'";
    $stmt_assets = $pdo->query($assets_query);
    while ($row = $stmt_assets->fetch(PDO::FETCH_ASSOC)) {
        $faq_assets[$row['asset_key']] = $row['file_path'];
    }
} catch (PDOException $e) { }

function get_faq_icon($key, $default = '../assets/images/favicon.png') {
    global $faq_assets;
    // إضافة ../ للرجوع للمجلد الرئيسي حيث يوجد مجلد uploads
    return isset($faq_assets[$key]) ? '../' . htmlspecialchars($faq_assets[$key]) : $default;
}
// ----------------------------------------------------

// تحديد المسار الأساسي للمشروع
$base_url = '/mo7adaraty/';

// جلب اللغة الحالية لتحديد الاتجاه
$current_lang = $_SESSION['lang'] ?? 'ar';
$text_dir = ($current_lang === 'ar') ? 'rtl' : 'ltr';

// تحميل ملف الترجمة المناسب
$lang_file = __DIR__ . '/../lang/' . $current_lang . '.php';
$translations = file_exists($lang_file) ? include $lang_file : include __DIR__ . '/../lang/ar.php';

// دالة الترجمة البرمجية مع النص الافتراضي (Fallback)
if (!function_exists('__')) {
    function __($key, $default = '') {
        global $translations;
        return $translations[$key] ?? ($default ?: $key);
    }
}

// مصفوفة الأسئلة الشائعة (تم تعديل الأيقونات لتستخدم مفاتيح PNG الديناميكية)
$faq_data = [
    'general' => [
        'title' => __('faq_cat_general_title', 'أسئلة عامة'),
        'icon' => 'icon_general',
        'items' => [
            [
                'q' => __('faq_q_what_is', 'ما هي منصة محاضراتي؟'),
                'a' => __('faq_a_what_is', 'محاضراتي هي منصة تعليمية تفاعلية تهدف إلى جمع الطلاب والأساتذة في بيئة رقمية واحدة لتسهيل مشاركة المحاضرات، الامتحانات، وتبادل المعرفة الأكاديمية.')
            ],
            [
                'q' => __('faq_q_free', 'هل التسجيل في المنصة مجاني؟'),
                'a' => __('faq_a_free', 'نعم، التسجيل واستخدام كافة الميزات الأساسية في منصة محاضراتي هو مجاني تماماً لجميع الطلاب.')
            ],
            [
                'q' => __('faq_q_reliable', 'هل المحتوى المتوفر موثوق؟'),
                'a' => __('faq_a_reliable', 'يتم رفع المحتوى من قبل زملائك الطلاب. لدينا فريق من المشرفين يقومون بمراجعة المحتوى، ولكننا ننصح دائماً بالتأكد ومراجعة مصادرك الأكاديمية الأساسية.')
            ]
        ]
    ],
    'account' => [
        'title' => __('faq_cat_account_title', 'الحساب والتسجيل'),
        'icon' => 'icon_account',
        'items' => [
            [
                'q' => __('faq_q_password', 'كيف يمكنني استعادة كلمة المرور إذا نسيتها؟'),
                'a' => __('faq_a_password', 'يمكنك التوجه إلى صفحة "تسجيل الدخول" والضغط على "هل نسيت كلمة المرور؟". سيُطلب منك إدخال اسم المستخدم الخاص بك وتأكيد هويتك لاستعادتها.')
            ],
            [
                'q' => __('faq_q_change_major', 'هل يمكنني تغيير تخصصي أو شعبي بعد التسجيل؟'),
                'a' => __('faq_a_change_major', 'نعم، يمكنك تعديل تفضيلاتك الأكاديمية (الشعبة، الفصل، المسار) من خلال الدخول إلى "إعدادات الحساب" في ملفك الشخصي.')
            ],
            [
                'q' => __('faq_q_guest', 'لماذا لا يمكنني تحميل المحاضرات وأنا زائر؟'),
                'a' => __('faq_a_guest', 'للحفاظ على جودة المحتوى وحماية حقوق الطلاب، نطلب من المستخدمين إنشاء حساب للتمكن من تحميل المحاضرات والامتحانات والتفاعل مع المنشورات.')
            ]
        ]
    ],
    'content' => [
        'title' => __('faq_cat_content_title', 'المحاضرات والامتحانات'),
        'icon' => 'icon_content',
        'items' => [
            [
                'q' => __('faq_q_upload', 'كيف أقوم برفع محاضرة أو امتحان؟'),
                'a' => __('faq_a_upload', 'بعد تسجيل الدخول، اذهب إلى صفحة الفصل الخاص بك، وستجد زراً واضحاً باسم "رفع محاضرة" أو "رفع امتحان". قم بتعبئة البيانات وإرفاق الملف.')
            ],
            [
                'q' => __('faq_q_upload_delay', 'لماذا لا تظهر المحاضرة التي رفعتها مباشرة؟'),
                'a' => __('faq_a_upload_delay', 'جميع الملفات المرفوعة تخضع لعملية مراجعة (Review) من قبل الإدارة للتأكد من جودتها وملاءمتها للمادة العلمية. ستظهر بمجرد الموافقة عليها (عادة خلال 24 ساعة).')
            ],
            [
                'q' => __('faq_q_file_types', 'ما هي صيغ الملفات المسموح برفعها؟'),
                'a' => __('faq_a_file_types', 'ندعم حالياً ملفات PDF والمستندات النصية (Word) بالإضافة إلى الصور (JPG, PNG) ذات الجودة العالية للامتحانات.')
            ]
        ]
    ],
    'community' => [
        'title' => __('faq_cat_community_title', 'ساحة المشاركات (المجتمع)'),
        'icon' => 'icon_community',
        'items' => [
            [
                'q' => __('faq_q_community', 'ما هي ساحة المشاركات؟'),
                'a' => __('faq_a_community', 'هي مساحة تفاعلية تشبه وسائل التواصل الاجتماعي، مخصصة للطلاب لطرح الأسئلة، تبادل الآراء، أو طلب مساعدة في فهم جزء معين من المقرر.')
            ],
            [
                'q' => __('faq_q_report', 'كيف أبلغ عن تعليق أو منشور مسيء؟'),
                'a' => __('faq_a_report', 'يوجد زر خيارات (ثلاث نقاط) بجانب كل منشور أو تعليق، يمكنك الضغط عليه واختيار "إبلاغ". سيقوم الفريق الإداري بمراجعة البلاغ واتخاذ الإجراء المناسب فوراً.')
            ]
        ]
    ]
];
?>
<!DOCTYPE html>
<html lang="<?= $current_lang ?>" dir="<?= $text_dir ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('faq_page_title', 'الأسئلة الشائعة') ?> - Mo7adaraty</title>
    <meta name="description" content="<?= __('faq_meta_desc', 'إجابات على الأسئلة الشائعة حول منصة محاضراتي، كيفية التسجيل، رفع المحاضرات، واستخدام ساحة المشاركات.') ?>">
    <link rel="icon" type="image/svg+xml" href="../assets/images/favicon.svg">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
    
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/pages/faq.css">

    <style>
        /* تعديلات الديناميكية لاتجاه اللغة */
        body { text-align: <?= $text_dir === 'rtl' ? 'right' : 'left' ?>; }
        
        .faq-search-icon {
            <?= $text_dir === 'rtl' ? 'left: 1.5rem; right: auto;' : 'right: 1.5rem; left: auto;' ?>
        }
        
        .faq-category-icon {
            <?= $text_dir === 'rtl' ? 'margin-left: 0.75rem; margin-right: 0;' : 'margin-right: 0.75rem; margin-left: 0;' ?>
        }
        
        .faq-question img.faq-toggle-icon {
            <?= $text_dir === 'rtl' ? 'margin-right: auto; margin-left: 0;' : 'margin-left: auto; margin-right: 0;' ?>
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

    <main class="faq-page-wrapper">
        
        <!-- بداية قسم الـ Hero المعدل لصفحة الأسئلة الشائعة لدعم فيديو الانترو -->
        <section class="faq-hero py-20 md:py-32 relative overflow-hidden">
            
            <!-- عنصر الفيديو بكامله وبدون أي قص ليمط ويأخذ حجم السيكشن بالكامل -->
            <video autoplay muted loop playsinline class="absolute top-0 left-0 w-full h-full object-fill z-0 pointer-events-none opacity-25">
                <source src="../assets/videos/FAQ.mp4" type="video/mp4">
                <?= __('برجاء تحديث المتصفح لدعم تشغيل الفيديو') ?>
            </video>
            
            <!-- طبقة تظليل شفافة ناعمة لتعزيز حدة الحروف والقراءة المريحة -->
            <div class="absolute top-0 left-0 w-full h-full bg-black/5 z-0"></div>

            <!-- محتوى النصوص والترحيب مع رفعه فوق طبقة الفيديو z-10 -->
            <div class="faq-hero-content container mx-auto px-4 relative z-10" data-aos="fade-up">
                <h1 class="text-4xl md:text-5xl font-black mb-4 text-white"><?= __('faq_hero_title', 'كيف يمكننا مساعدتك؟') ?></h1>
                <p class="text-lg md:text-xl opacity-90 max-w-2xl mx-auto text-white/90">
                    <?= __('faq_hero_desc', 'جمعنا لك هنا إجابات لأكثر الأسئلة شيوعاً حول المنصة لتسهيل تجربتك.') ?>
                </p>
            </div>
        </section>
        <!-- نهاية قسم الـ Hero المعدل -->
        <div class="faq-search-container" data-aos="fade-up" data-aos-delay="100">
            <div class="faq-search-wrapper relative flex items-center">
                <input type="text" id="faqSearchInput" class="faq-search-input w-full" placeholder="<?= __('faq_search_placeholder', 'ابحث عن سؤالك هنا (مثال: كلمة المرور، رفع محاضرة)...') ?>">
                <img src="<?= get_faq_icon('search_icon') ?>" class="faq-search-icon absolute w-12 h-12 object-contain" alt="search">
            </div>
        </div>

        <div class="container mx-auto px-4 max-w-4xl relative z-10">
            
            <div id="faqNoResults" class="faq-no-results text-center">
                <img src="<?= get_faq_icon('no_results_icon') ?>" class="w-20 h-20 object-contain mx-auto mb-4 opacity-70" alt="no results">
                <h3 class="text-xl font-bold mb-2"><?= __('faq_no_results_title', 'لم نتمكن من العثور على إجابة') ?></h3>
                <p><?= __('faq_no_results_desc', 'جرب استخدام كلمات مفتاحية أخرى، أو تواصل مع الدعم الفني مباشرة.') ?></p>
            </div>

            <?php foreach ($faq_data as $category_id => $category): ?>
                <div class="faq-category-section mb-10" data-aos="fade-up">
                    <h2 class="faq-category-title flex items-center">
                        <div class="faq-category-icon flex items-center justify-center">
                            <img src="<?= get_faq_icon($category['icon']) ?>" class="w-100 h-100 object-contain" alt="icon">
                        </div>
                        <?= htmlspecialchars($category['title']) ?>
                    </h2>
                    
                    <div class="faq-list">
                        <?php foreach ($category['items'] as $item): ?>
                            <div class="faq-item">
                                <button class="faq-question flex w-full items-center justify-between">
                                    <span class="<?= $text_dir === 'ltr' ? 'text-left' : 'text-right' ?>"><?= htmlspecialchars($item['q']) ?></span>
                                    <img src="<?= get_faq_icon('toggle_icon') ?>" class="faq-toggle-icon w-14 h-14 object-contain transition-transform duration-300" alt="toggle">
                                </button>
                                <div class="faq-answer-wrapper">
                                    <div class="faq-answer <?= $text_dir === 'ltr' ? 'text-left' : 'text-right' ?>">
                                        <?= htmlspecialchars($item['a']) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="faq-cta-box" data-aos="zoom-in">
                <h3 class="faq-cta-title"><?= __('faq_cta_title', 'لم تجد إجابة لسؤالك؟') ?></h3>
                <p class="faq-cta-text"><?= __('faq_cta_text', 'فريق الدعم الخاص بنا متواجد دائماً للرد على كافة استفساراتك وحل مشاكلك.') ?></p>
                <a href="ContactUs.php" class="faq-cta-btn flex items-center justify-center">
                    <img src="<?= get_faq_icon('cta_support_icon') ?>" class="<?= $text_dir === 'rtl' ? 'ml-2' : 'mr-2' ?> w-20 h-20 object-contain" alt="support"> 
                    <?= __('contact_us', 'تواصل مع الدعم الفني') ?>
                </a>
            </div>

        </div>
    </main>

    <?php require_once '../templates/footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script src="../assets/js/faq.js"></script>
    <script>
        if (typeof AOS !== 'undefined') {
            AOS.init({ duration: 800, once: true, offset: 50 });
        }
    </script>
</body>
</html>