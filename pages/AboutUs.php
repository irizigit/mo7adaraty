<?php
session_start();
require_once '../config/db_connexion.php';

// --- جلب الأيقونات الديناميكية الخاصة بصفحة من نحن ---
$about_assets = [];
try {
    $assets_query = "SELECT asset_key, file_path FROM design_assets WHERE page_section = 'AboutUs'";
    $stmt = $pdo->query($assets_query);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $about_assets[$row['asset_key']] = $row['file_path'];
    }
} catch (PDOException $e) { }

// تصحيح الدالة لإضافة ../ للمسار القادم من قاعدة البيانات
function get_about_icon($key) {
    global $about_assets;
    $default = '../assets/images/favicon.png'; 
    // المسار في القاعدة يبدأ بـ uploads/ لذا نحتاج للخروج خطوة للمجلد الرئيسي
    return isset($about_assets[$key]) ? '../' . htmlspecialchars($about_assets[$key]) : $default;
}
// ----------------------------------------------------
?>
<!DOCTYPE html>
<html lang="<?= __('lang_code') ?? 'ar' ?>" dir="<?= __('lang_dir') ?? 'rtl' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('about_us_title') ?></title>
    <meta name="description" content="<?= __('about_us_meta_desc') ?>">
    <link rel="icon" type="image/svg+xml" href="../assets/images/favicon.svg">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/pages/about.css">
</head>
<body class="font-tajawal overflow-x-hidden">

    <script>
        (function() {
            const savedTheme = localStorage.getItem('mo7adaraty_theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>

    <?php 
    // ضبط المسار الأساسي ليعمل الهيدر بشكل صحيح من داخل مجلد pages
    $base_url = '/mo7adaraty/'; 
    require_once '../templates/header.php'; 
    ?>

<main class="about-page">
        
        <!-- بداية قسم الـ Hero المطور والمستقل لدعم فيديو الانترو -->
        <section class="about-hero py-16 md:py-24 relative overflow-hidden mb-12">
            
            <!-- عنصر الفيديو بكامله وبدون أي قص مطابقاً تماماً لأبعاد السيكشن -->
            <video autoplay muted loop playsinline class="absolute top-0 left-0 w-full h-full object-fill z-0 pointer-events-none opacity-30">
                <source src="../assets/videos/AboutUs.mp4" type="video/mp4">
                <?= __('برجاء تحديث المتصفح لدعم تشغيل الفيديو') ?>
            </video>
            
            <!-- طبقة تظليل شفافة متناسقة لتعزيز تباين الخطوط -->
            <div class="absolute top-0 left-0 w-full h-full bg-black/5 z-0"></div>
            
            <!-- الحفاظ على نمط الخلفية الأصلي مدمجاً بشكل خفيف خلف نصوص الهيرو z-0 -->
            <div class="about-bg-pattern absolute top-0 left-0 w-full h-full z-0 opacity-40 pointer-events-none"></div>
            
            <!-- محتوى النصوص المرفوع للطبقة z-10 ليكون ظاهراً بوضوح تام فوق المقطع -->
            <div class="container mx-auto px-4 relative z-10">
                <div class="text-center" data-aos="fade-up">
                    <h1 class="text-4xl md:text-5xl font-bold about-title"><?= __('about_us_heading') ?></h1>
                    <p class="text-lg md:text-xl about-subtitle max-w-3xl mx-auto mt-6">
                       <?= __('AboutUs_hero_subtitle') ?>
                    </p>
                </div>
            </div>
        </section>
        <!-- نهاية قسم الـ Hero المطور -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center mb-24">
                <div data-aos="fade-right">
                    <div class="about-hero-image h-[400px]">
                        <img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?q=80&w=800&auto=format&fit=crop" alt="<?= __('about_us_hero_img_alt') ?>">
                    </div>
                </div>
                <div data-aos="fade-left">
                    <h2 class="text-3xl font-bold mb-6 text-[var(--text-primary)]"><?= __('about_us_mission_title') ?></h2>
                    <p class="mb-5 about-subtitle text-lg">
                        <?= __('about_us_mission_p1') ?>
                    </p>
                    <p class="mb-8 about-subtitle text-lg">
                        <?= __('about_us_mission_p2') ?>
                    </p>
                    <div class="flex flex-col gap-3">
                        <div class="about-check-item">
                            <div class="about-check-icon"><img src="<?= get_about_icon('mission_check') ?>" alt="icon" style="width: 100%; height: 100%; object-fit: contain;"></div>
                            <span><?= __('about_us_mission_point1') ?></span>
                        </div>
                        <div class="about-check-item">
                            <div class="about-check-icon"><img src="<?= get_about_icon('mission_check') ?>" alt="icon" style="width: 100%; height: 100%; object-fit: contain;"></div>
                            <span><?= __('about_us_mission_point2') ?></span>
                        </div>
                        <div class="about-check-item">
                            <div class="about-check-icon"><img src="<?= get_about_icon('mission_check') ?>" alt="icon" style="width: 100%; height: 100%; object-fit: contain;"></div>
                            <span><?= __('about_us_mission_point3') ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mb-24" data-aos="fade-up">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-bold mb-4 text-[var(--text-primary)]"><?= __('about_us_what_we_offer') ?></h2>
                    <p class="about-subtitle max-w-2xl mx-auto"><?= __('about_us_what_we_offer_subtitle') ?></p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="about-feature-card">
                        <div class="about-icon-wrapper"><img src="<?= get_about_icon('feature_search') ?>" alt="icon" style="width: 7rem; height: 7rem; object-fit: contain;"></div>
                        <h3 class="text-xl font-bold mb-3 text-[var(--text-primary)]"><?= __('about_us_feature1_title') ?></h3>
                        <p class="about-subtitle text-sm"><?= __('about_us_feature1_desc') ?></p>
                    </div>
                    
                    <div class="about-feature-card">
                        <div class="about-icon-wrapper"><img src="<?= get_about_icon('feature_upload') ?>" alt="icon" style="width: 7rem; height: 7rem; object-fit: contain;"></div>
                        <h3 class="text-xl font-bold mb-3 text-[var(--text-primary)]"><?= __('about_us_feature2_title') ?></h3>
                        <p class="about-subtitle text-sm"><?= __('about_us_feature2_desc') ?></p>
                    </div>
                    
                    <div class="about-feature-card">
                        <div class="about-icon-wrapper"><img src="<?= get_about_icon('feature_users') ?>" alt="icon" style="width: 7rem; height: 7rem; object-fit: contain;"></div>
                        <h3 class="text-xl font-bold mb-3 text-[var(--text-primary)]"><?= __('about_us_feature3_title') ?></h3>
                        <p class="about-subtitle text-sm"><?= __('about_us_feature3_desc') ?></p>
                    </div>
                    
                    <div class="about-feature-card">
                        <div class="about-icon-wrapper"><img src="<?= get_about_icon('feature_comments') ?>" alt="icon" style="width: 7rem; height: 7rem; object-fit: contain;"></div>
                        <h3 class="text-xl font-bold mb-3 text-[var(--text-primary)]"><?= __('about_us_feature4_title') ?></h3>
                        <p class="about-subtitle text-sm"><?= __('about_us_feature4_desc') ?></p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center mb-24">
                <div class="order-2 lg:order-1" data-aos="fade-right">
                    <h2 class="text-3xl font-bold mb-6 text-[var(--text-primary)]"><?= __('about_us_story_title') ?></h2>
                    <p class="mb-4 about-subtitle text-lg">
                        <?= __('about_us_story_p1') ?>
                    </p>
                    <p class="mb-4 about-subtitle text-lg">
                        <?= __('about_us_story_p2') ?>
                    </p>
                    <p class="mb-6 about-subtitle text-lg">
                        <?= __('about_us_story_p3') ?>
                    </p>
                    <div class="flex items-start mt-8 p-6 bg-[var(--bg-light)] border border-[var(--border-color)] rounded-2xl">
                        <img src="<?= get_about_icon('story_quote') ?>" alt="quote" class="w-8 h-8 ml-4 mt-2 object-contain">
                        <p class="text-xl font-medium italic text-[var(--text-primary)] leading-relaxed">
                            "<?= __('about_us_story_quote') ?>"
                        </p>
                    </div>
                </div>
                <div class="order-1 lg:order-2" data-aos="fade-left">
                    <div class="about-hero-image h-[450px]">
                        <img src="https://images.unsplash.com/photo-1581276879432-15e50529f34b?q=80&w=800&auto=format&fit=crop" alt="<?= __('about_us_story_img_alt') ?>">
                    </div>
                </div>
            </div>

            <div class="mb-24" data-aos="fade-up">
                <div class="about-stats-container">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                        <div>
                            <h3 class="text-3xl font-bold mb-6 text-[var(--text-primary)]"><?= __('about_us_community_title') ?></h3>
                            <p class="mb-4 about-subtitle text-lg">
                                <?= __('about_us_community_p1') ?>
                            </p>
                            <p class="mb-8 about-subtitle text-lg">
                                <?= __('about_us_community_p2') ?>
                            </p>
                        </div>
                        <div>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="about-stat-item">
                                    <div class="about-stat-number">+1000</div>
                                    <div class="text-[var(--text-secondary)] font-bold"><?= __('about_us_stat_students') ?></div>
                                </div>
                                <div class="about-stat-item">
                                    <div class="about-stat-number">+5000</div>
                                    <div class="text-[var(--text-secondary)] font-bold"><?= __('about_us_stat_content') ?></div>
                                </div>
                                <div class="about-stat-item">
                                    <div class="about-stat-number">+50</div>
                                    <div class="text-[var(--text-secondary)] font-bold"><?= __('about_us_stat_tracks') ?></div>
                                </div>
                                <div class="about-stat-item">
                                    <div class="about-stat-number">24/7</div>
                                    <div class="text-[var(--text-secondary)] font-bold"><?= __('about_us_stat_access') ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mb-10" data-aos="fade-up">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-bold mb-4 text-[var(--text-primary)]"><?= __('about_us_team_title') ?></h2>
                    <p class="about-subtitle max-w-2xl mx-auto"><?= __('about_us_team_subtitle') ?></p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-5xl mx-auto">
                    <div class="about-team-card">
                        <div class="about-team-avatar">
                            <img src="https://images.unsplash.com/photo-1568602471122-7832951cc4c5?q=80&w=300&auto=format&fit=crop" alt="<?= __('about_us_team_founder_alt') ?>">
                        </div>
                        <h3 class="text-xl font-bold mb-1 text-[var(--text-primary)]"><?= __('about_us_team_member1_name') ?></h3>
                        <p class="text-[var(--brand-primary)] font-medium mb-3"><?= __('about_us_team_member1_role') ?></p>
                        <p class="about-subtitle text-sm mb-5"><?= __('about_us_team_member1_desc') ?></p>
                        <div class="flex justify-center gap-3">
                            <a href="#" class="about-social-icon flex items-center justify-center"><img src="<?= get_about_icon('team1_linkedin') ?>" alt="icon" class="w-800 h-800"></a>
                            <a href="#" class="about-social-icon flex items-center justify-center"><img src="<?= get_about_icon('team1_github') ?>" alt="icon" class="w-800 h-800"></a>
                        </div>
                    </div>
                    
                    <div class="about-team-card">
                        <div class="about-team-avatar">
                            <img src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?q=80&w=300&auto=format&fit=crop" alt="<?= __('about_us_team_manager_alt') ?>">
                        </div>
                        <h3 class="text-xl font-bold mb-1 text-[var(--text-primary)]"><?= __('about_us_team_member2_name') ?></h3>
                        <p class="text-[var(--brand-primary)] font-medium mb-3"><?= __('about_us_team_member2_role') ?></p>
                        <p class="about-subtitle text-sm mb-5"><?= __('about_us_team_member2_desc') ?></p>
                        <div class="flex justify-center gap-3">
                            <a href="#" class="about-social-icon flex items-center justify-center"><img src="<?= get_about_icon('team2_linkedin') ?>" alt="icon" class="w-800 h-800"></a>
                            <a href="#" class="about-social-icon flex items-center justify-center"><img src="<?= get_about_icon('team2_twitter') ?>" alt="icon" class="w-800 h-800"></a>
                        </div>
                    </div>
                    
                    <div class="about-team-card">
                        <div class="about-team-avatar">
                            <img src="https://images.unsplash.com/photo-1494790108755-2616b612b786?q=80&w=300&auto=format&fit=crop" alt="<?= __('about_us_team_support_alt') ?>">
                        </div>
                        <h3 class="text-xl font-bold mb-1 text-[var(--text-primary)]"><?= __('about_us_team_member3_name') ?></h3>
                        <p class="text-[var(--brand-primary)] font-medium mb-3"><?= __('about_us_team_member3_role') ?></p>
                        <p class="about-subtitle text-sm mb-5"><?= __('about_us_team_member3_desc') ?></p>
                        <div class="flex justify-center gap-3">
                            <a href="#" class="about-social-icon flex items-center justify-center"><img src="<?= get_about_icon('team3_linkedin') ?>" alt="icon" class="w-800 h-800"></a>
                            <a href="#" class="about-social-icon flex items-center justify-center"><img src="<?= get_about_icon('team3_facebook') ?>" alt="icon" class="w-800 h-800"></a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="about-cta-section" data-aos="zoom-in">
                <h2 class="text-3xl font-bold mb-4 text-[var(--text-primary)]"><?= __('about_us_cta_title') ?></h2>
                <p class="about-subtitle text-lg mb-8 max-w-2xl mx-auto">
                    <?= __('about_us_cta_desc') ?>
                </p>
                <div class="flex flex-wrap justify-center gap-4">
                    <a href="../signup.php" class="about-btn-primary flex items-center"><img src="<?= get_about_icon('cta_signup') ?>" class="w-10 h-10 ml-2 object-contain" alt="icon"> <?= __('about_us_cta_signup') ?></a>
                    <a href="ContactUs.php" class="about-btn-outline flex items-center"><img src="<?= get_about_icon('cta_contact') ?>" class="w-10 h-10 ml-2 object-contain" alt="icon"> <?= __('about_us_cta_contact') ?></a>
                </div>
            </div>

        </div>
    </main>

    <?php require_once '../templates/footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // تهيئة مكتبة AOS للحركات
            AOS.init({
                duration: 800,
                easing: 'ease-in-out',
                once: true,
                offset: 50
            });
        });
    </script>
</body>
</html>