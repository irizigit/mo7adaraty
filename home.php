<?php
session_start();
require_once 'config/db_connexion.php';

$base_url = '/mo7adaraty/';
$is_logged_in = isset($_SESSION['user_id']);
$user_id = $is_logged_in ? $_SESSION['user_id'] : null;

// إعداد نظام اللغات
$allowed_langs = ['ar', 'en', 'fr'];
$lang_code = (isset($_SESSION['lang']) && in_array($_SESSION['lang'], $allowed_langs)) ? $_SESSION['lang'] : 'fr';
$lang = require "lang/{$lang_code}.php";
$dir = ($lang_code === 'ar') ? 'rtl' : 'ltr';

// دالة تحويل الأرقام إلى أيقونات
function renderNumberAsIcons($number, $base_url) {
    $numStr = (string)$number;
    $html = '<div class="stats-icons-wrapper" style="display: flex; align-items: center; justify-content: center; direction: ltr;">';
    for ($i = 0; $i < strlen($numStr); $i++) {
        $digit = $numStr[$i];
        if (is_numeric($digit)) {
            $iconPath = $base_url . 'admin/assets/icons/num_' . $digit . '.png';
            $html .= '<img src="' . htmlspecialchars($iconPath) . '" alt="'.$digit.'" style="height: 38px; width: auto; object-fit: contain; margin: 0 1px;">';
        }
    }
    $html .= '<span style="font-size: 32px; font-weight: 900; color: var(--brand-primary); margin-left: 5px; line-height: 1;">+</span>';
    $html .= '</div>';
    return $html;
}

try {
    $db = isset($pdo) ? $pdo : (isset($conn) ? $conn : null);
    $design_settings = [];
    
    if ($db) {
        $stmt_design = $db->prepare("SELECT asset_key, file_path FROM design_assets");
        $stmt_design->execute();
        while ($row = $stmt_design->fetch(PDO::FETCH_ASSOC)) {
            $design_settings[$row['asset_key']] = $row['file_path'];
        }
    }

    function getDesignIcon($key, $default_path) {
        global $design_settings, $base_url;
        return !empty($design_settings[$key]) ? $base_url . $design_settings[$key] : $base_url . $default_path;
    }

    // الإحصائيات (Stats)
    $stats = [
        'users' => $db ? $db->query("SELECT COUNT(*) FROM users")->fetchColumn() : 0,
        'lectures' => $db ? $db->query("SELECT COUNT(*) FROM lectures WHERE status = 'approved'")->fetchColumn() : 0,
        'posts' => $db ? $db->query("SELECT COUNT(*) FROM posts WHERE status = 'approved'")->fetchColumn() : 0,
        'divisions' => $db ? $db->query("SELECT COUNT(*) FROM divisions")->fetchColumn() : 0
    ];

    // الآراء (Reviews)
    $reviews = [];
    if ($db) {
        $stmt_reviews = $db->query("
            SELECT r.*, u.username, u.profile_picture,
            (SELECT d.division_name FROM student_enrollments se JOIN course_offerings co ON se.offering_id = co.offering_id JOIN divisions d ON co.division_id = d.division_id WHERE se.user_id = u.user_id LIMIT 1) as user_division
            FROM reviews r
            LEFT JOIN users u ON r.user_id = u.user_id
            WHERE r.status = 'approved'
            ORDER BY r.created_at DESC LIMIT 6
        ");
        $reviews = $stmt_reviews->fetchAll(PDO::FETCH_ASSOC);
    }

    $logged_user_data = [];
    if ($is_logged_in && $db) {
        $stmt_user = $db->prepare("SELECT username FROM users WHERE user_id = ?");
        $stmt_user->execute([$user_id]);
        $logged_user_data = $stmt_user->fetch(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    $stats = ['users' => 0, 'lectures' => 0, 'posts' => 0, 'divisions' => 0];
    $reviews = [];
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang_code; ?>" dir="<?php echo $dir; ?>">
<head>
    <meta name="viewport" content="width=1024">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($lang['site_name_full']) ? $lang['site_name_full'] : 'Mo7adaraty'; ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= $base_url ?>assets/images/favicon.svg">
    
   <link rel="stylesheet" href="<?= $base_url ?>assets/font/sf-soltan.ttf">
    <link rel="stylesheet" href="<?= $base_url ?>assets/css/global.css">
    <link rel="stylesheet" href="<?= $base_url ?>assets/css/pages/home.css">

    <style>
        .sneak-peek-slider { position: relative; width: 100%; padding-top: 56.25%; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1); background: var(--bg-surface, #fff); border: 1px solid var(--border-color, #eaeaea); }
        .sneak-peek-slider img { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; opacity: 0; transform: scale(1.05); transition: opacity 1.2s ease-in-out, transform 6s linear; }
        .sneak-peek-slider img.active { opacity: 1; transform: scale(1); }
        .slider-badge { position: absolute; bottom: 15px; right: 15px; background: rgba(0, 0, 0, 0.65); color: #fff; padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; z-index: 10; backdrop-filter: blur(5px); letter-spacing: 1px; pointer-events: none; }
        [dir="ltr"] .slider-badge { right: auto; left: 15px; }
        .btn-hero i { display: none; }
        .faq-question i { display: none; }
        .faq-question { display: flex; justify-content: space-between; align-items: center; }
        .arrow-icon { width: 20px; height: 20px; transition: transform 0.3s; }
        .faq-item.active .arrow-icon { transform: rotate(180deg); }
    </style>
</head>
<body class="home-wrapper">

    <script>
        (function() {
            const savedTheme = localStorage.getItem('mo7adaraty_theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>

    <?php require_once 'templates/header.php'; ?>
<section class="hero-section relative overflow-hidden min-h-screen flex items-center justify-center">
        
        <!-- طبقة تظليل شفافة فوق الفيديو لضمان وضوح النص -->
        <div class="absolute top-0 left-0 w-full h-full bg-black/50 z-0"></div>
        
        <!-- المحتوى (النص والأزرار) -->
        <div class="hero-content relative z-10 container mx-auto px-4 text-center">
            <h1 class="hero-title text-white drop-shadow-lg"><?php echo $lang['home_hero_title']; ?></h1>
            <p class="hero-subtitle text-white/90 drop-shadow-md max-w-2xl mx-auto"><?php echo $lang['home_hero_subtitle']; ?></p>
            
            <div class="hero-buttons mt-10 flex flex-wrap justify-center gap-4">
                <?php if ($is_logged_in): ?>
                    <a href="<?= $base_url ?>student/profil.php" class="btn-hero btn-hero-primary inline-flex items-center">
                        <img src="<?php echo getDesignIcon('icon_profile', 'includesimg/header/icon_profile.png'); ?>" alt="Profile" style="width: 20px; margin-inline-end: 8px; vertical-align: middle;">
                        <?php echo $lang['home_btn_profile']; ?>
                    </a>
                <?php else: ?>
                    <a href="<?= $base_url ?>signup.php" class="btn-hero btn-hero-primary inline-flex items-center">
                        <img src="<?php echo getDesignIcon('icon_signup', 'includesimg/header/icon_signup.png'); ?>" alt="Signup" style="width: 20px; margin-inline-end: 8px; vertical-align: middle;">
                        <?php echo $lang['home_btn_signup']; ?>
                    </a>
                    <a href="<?= $base_url ?>login.php" class="btn-hero btn-hero-secondary inline-flex items-center">
                        <img src="<?php echo getDesignIcon('icon_login', 'includesimg/header/icon_login.png'); ?>" alt="Login" style="width: 20px; margin-inline-end: 8px; vertical-align: middle;">
                        <?php echo $lang['home_btn_login']; ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>
    
    <section class="video-section">
        <div class="section-header">
            <h2><?php echo $lang['home_video_title']; ?></h2>
            <p><?php echo $lang['home_video_desc']; ?></p>
        </div>
        <div class="video-container">
<iframe width="560" height="315" src="https://www.youtube.com/embed/XGMThcMc3io?si=z2WiSspg6pKB7uNr&amp;start=79&amp;autoplay=1&amp;mute=1&amp;loop=1&amp;playlist=XGMThcMc3io" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>    </section>

    <section class="sneak-peek-section">
        <div class="section-header">
            <h2><?php echo $lang['home_sneak_title']; ?></h2>
            <p><?php echo $lang['home_sneak_desc']; ?></p>
        </div>
        <div class="sneak-peek-container">
            
            <div class="sneak-peek-item">
                <div class="sneak-peek-text">
                    <h3><?php echo $lang['home_sneak_1_title']; ?></h3>
                    <p><?php echo $lang['home_sneak_1_desc']; ?></p>
                </div>
                <div class="sneak-peek-img">
                    <div class="sneak-peek-slider">
                        <span class="slider-badge lang-indicator"></span>
                        <img src="<?php echo getDesignIcon('home_sneak_profile_ar', 'includesimg/home/home_sneak_profile_ar.jpg'); ?>" class="active" data-lang="" alt="Profile AR">
                        <img src="<?php echo getDesignIcon('home_sneak_profile_fr', 'includesimg/home/home_sneak_profile_fr.jpg'); ?>" data-lang="" alt="Profile ">
                        <img src="<?php echo getDesignIcon('home_sneak_profile_en', 'includesimg/home/home_sneak_profile_en.jpg'); ?>" data-lang="" alt="Profile ">
                    </div>
                </div>
            </div>

            <div class="sneak-peek-item">
                <div class="sneak-peek-text">
                    <h3><?php echo $lang['home_sneak_2_title']; ?></h3>
                    <p><?php echo $lang['home_sneak_2_desc']; ?></p>
                </div>
                <div class="sneak-peek-img">
                    <div class="sneak-peek-slider">
                        <span class="slider-badge lang-indicator"></span>
                        <img src="<?php echo getDesignIcon('home_sneak_settings_ar', 'includesimg/home/home_sneak_settings_ar.jpg'); ?>" class="active" data-lang="AR" alt="Settings ">
                        <img src="<?php echo getDesignIcon('home_sneak_settings_fr', 'includesimg/home/home_sneak_settings_fr.jpg'); ?>" data-lang="" alt="Settings ">
                        <img src="<?php echo getDesignIcon('home_sneak_settings_en', 'includesimg/home/home_sneak_settings_en.jpg'); ?>" data-lang="" alt="Settings ">
                    </div>
                </div>
            </div>

            <div class="sneak-peek-item sneak-peek-hidden">
                <div class="sneak-peek-text">
                    <h3><?php echo $lang['home_sneak_3_title']; ?></h3>
                    <p><?php echo $lang['home_sneak_3_desc']; ?></p>
                </div>
                <div class="sneak-peek-img">
                    <div class="sneak-peek-slider">
                        <span class="slider-badge lang-indicator"></span>
                        <img src="<?php echo getDesignIcon('home_sneak_community_ar', 'includesimg/home/home_sneak_community_ar.jpg'); ?>" class="active" data-lang="" alt="Community AR">
                        <img src="<?php echo getDesignIcon('home_sneak_community_fr', 'includesimg/home/home_sneak_community_fr.jpg'); ?>" data-lang="" alt="Community ">
                        <img src="<?php echo getDesignIcon('home_sneak_community_en', 'includesimg/home/home_sneak_community_en.jpg'); ?>" data-lang="" alt="Community ">
                    </div>
                </div>
            </div>

        </div>
        <div class="show-more-container">
            <button id="showMoreSneakPeek" class="btn-show-more">
                <?php echo $lang['home_show_more']; ?> 
                <img src="<?php echo getDesignIcon('home_icon_arrow', 'admin/assets/icons/arrow_down.png'); ?>" alt="Down" style="width:14px; margin-inline-start:8px;">
            </button>
        </div>
    </section>

    <section class="features-section">
        <div class="section-header">
            <h2><?php echo $lang['home_feat_title']; ?></h2>
            <p><?php echo $lang['home_feat_desc']; ?></p>
        </div>
        <div class="features-grid">
            <div class="feature-card">
                <img src="<?php echo getDesignIcon('home_feat_1', 'includesimg/student/icon_bell.png'); ?>" alt="Feature" class="feature-icon">
                <h3><?php echo $lang['home_feat_1_title']; ?></h3>
                <p><?php echo $lang['home_feat_1_desc']; ?></p>
            </div>
            <div class="feature-card">
                <img src="<?php echo getDesignIcon('home_feat_2', 'includesimg/class_lectures/cl_icon_upload.png'); ?>" alt="Feature" class="feature-icon">
                <h3><?php echo $lang['home_feat_2_title']; ?></h3>
                <p><?php echo $lang['home_feat_2_desc']; ?></p>
            </div>
            <div class="feature-card">
                <img src="<?php echo getDesignIcon('home_feat_3', 'includesimg/header/icon_whatsapp.png'); ?>" alt="Feature" class="feature-icon">
                <h3><?php echo $lang['home_feat_3_title']; ?></h3>
                <p><?php echo $lang['home_feat_3_desc']; ?></p>
            </div>
        </div>
    </section>

    <section class="stats-section">
        <div class="stats-grid">
            <div class="stat-item">
                <img src="<?php echo getDesignIcon('home_stat_1', 'includesimg/class_lectures/cl_icon_school.svg'); ?>" alt="Divisions" class="stat-icon">
                <div class="stat-number-wrapper">
                    <?php echo renderNumberAsIcons($stats['divisions'], $base_url); ?>
                </div>
                <div class="stat-label"><?php echo $lang['home_stat_divisions']; ?></div>
            </div>
            <div class="stat-item">
                <img src="<?php echo getDesignIcon('home_stat_2', 'includesimg/header/icon_profile.png'); ?>" alt="Users" class="stat-icon">
                <div class="stat-number-wrapper">
                    <?php echo renderNumberAsIcons($stats['users'], $base_url); ?>
                </div>
                <div class="stat-label"><?php echo $lang['home_stat_users']; ?></div>
            </div>
            <div class="stat-item">
                <img src="<?php echo getDesignIcon('home_stat_3', 'includesimg/class_lectures/cl_icon_pdf.png'); ?>" alt="Lectures" class="stat-icon">
                <div class="stat-number-wrapper">
                    <?php echo renderNumberAsIcons($stats['lectures'], $base_url); ?>
                </div>
                <div class="stat-label"><?php echo $lang['home_stat_lectures']; ?></div>
            </div>
            <div class="stat-item">
                <img src="<?php echo getDesignIcon('home_stat_4', 'includesimg/class_lectures/cl_icon_comments.png'); ?>" alt="Posts" class="stat-icon">
                <div class="stat-number-wrapper">
                    <?php echo renderNumberAsIcons($stats['posts'], $base_url); ?>
                </div>
                <div class="stat-label"><?php echo $lang['home_stat_posts']; ?></div>
            </div>
        </div>
    </section>

    <section class="reviews-section">
        <div class="section-header">
            <h2><?php echo $lang['home_review_title']; ?></h2>
            <p><?php echo $lang['home_review_desc']; ?></p>
        </div>
        
        <div class="reviews-grid" id="reviewsGrid">
            <?php if (empty($reviews)): ?>
                <div style="grid-column: 1 / -1; text-align: center; color: var(--text-secondary);"><?php echo $lang['home_review_empty']; ?></div>
            <?php else: ?>
                <?php foreach ($reviews as $review): ?>
                    <?php 
                        $display_name = $review['user_id'] ? $review['username'] : $review['guest_name'];
                        $display_division = $review['user_id'] ? ($review['user_division'] ?: 'طالب بالمنصة') : $review['guest_division'];
                        $avatar = $review['user_id'] && $review['profile_picture'] ? $base_url . 'uploads/profile_pictures/' . $review['profile_picture'] : $base_url . 'uploads/profile_pictures/default_profile.png';
                    ?>
                    <div class="review-card">
                        <div class="review-text">"<?php echo nl2br(htmlspecialchars($review['review_text'])); ?>"</div>
                        <div class="review-author">
                            <img src="<?php echo htmlspecialchars($avatar); ?>" alt="User" class="review-avatar" onerror="this.src='<?= $base_url ?>uploads/profile_pictures/default_profile.png'">
                            <div class="review-author-info">
                                <h4><?php echo htmlspecialchars($display_name); ?></h4>
                                <span><?php echo htmlspecialchars($display_division); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="leave-review-container">
            <h3><?php echo $lang['home_review_leave']; ?></h3>
            <form id="reviewForm">
                <?php if (!$is_logged_in): ?>
                    <div class="form-group">
                        <input type="text" name="guest_name" class="form-control" placeholder="<?php echo $lang['home_review_name']; ?>" required>
                    </div>
                    <div class="form-group">
                        <input type="text" name="guest_division" class="form-control" placeholder="<?php echo $lang['home_review_div']; ?>" required>
                    </div>
                <?php else: ?>
                    <div style="margin-bottom: 15px; color: var(--text-secondary); text-align: center;">
                        <img src="<?php echo getDesignIcon('home_icon_check', 'includesimg/student/icon_check.png'); ?>" alt="Check" style="width: 16px; vertical-align: middle;">
                        <?php echo $lang['home_review_welcome']; ?> <strong><?php echo htmlspecialchars($logged_user_data['username']); ?></strong>، <?php echo $lang['home_review_notice']; ?>
                    </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <textarea name="review_text" class="form-control" placeholder="<?php echo $lang['home_review_placeholder']; ?>" style="min-height: 100px; resize: vertical;" required></textarea>
                </div>
                <button type="submit" class="btn-submit-review"><?php echo $lang['home_review_submit']; ?></button>
                <div id="reviewStatus"></div>
            </form>
        </div>
    </section>

    <section class="faq-section">
        <div class="section-header">
            <h2><?php echo $lang['home_faq_title']; ?></h2>
            <p><?php echo $lang['home_faq_desc']; ?></p>
        </div>
        <div class="faq-container">
            <div class="faq-item">
                <button class="faq-question">
                    <?php echo $lang['home_faq_q1']; ?> 
                    <img src="<?php echo getDesignIcon('home_icon_arrow', 'admin/assets/icons/arrow_down.png'); ?>" class="arrow-icon" alt="Arrow">
                </button>
                <div class="faq-answer"><?php echo $lang['home_faq_a1']; ?></div>
            </div>
            <div class="faq-item">
                <button class="faq-question">
                    <?php echo $lang['home_faq_q2']; ?> 
                    <img src="<?php echo getDesignIcon('home_icon_arrow', 'admin/assets/icons/arrow_down.png'); ?>" class="arrow-icon" alt="Arrow">
                </button>
                <div class="faq-answer"><?php echo $lang['home_faq_a2']; ?></div>
            </div>
            <div class="faq-item">
                <button class="faq-question">
                    <?php echo $lang['home_faq_q3']; ?> 
                    <img src="<?php echo getDesignIcon('home_icon_arrow', 'admin/assets/icons/arrow_down.png'); ?>" class="arrow-icon" alt="Arrow">
                </button>
                <div class="faq-answer"><?php echo $lang['home_faq_a3']; ?></div>
            </div>
        </div>
    </section>

    <?php require_once 'templates/footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sliders = document.querySelectorAll('.sneak-peek-slider');
            sliders.forEach(slider => {
                const images = slider.querySelectorAll('img');
                const badge = slider.querySelector('.lang-indicator');
                let currentIndex = 0;
                if(images.length > 1) {
                    setInterval(() => {
                        images[currentIndex].classList.remove('active');
                        currentIndex = (currentIndex + 1) % images.length;
                        images[currentIndex].classList.add('active');
                        if (badge) {
                            badge.textContent = images[currentIndex].getAttribute('data-lang');
                        }
                    }, 4000);
                }
            });
        });
    </script>
    
    <script>
        const langData = {
            sending: "<?php echo isset($lang['js_sending']) ? $lang['js_sending'] : 'جاري الإرسال...'; ?>",
            serverError: "<?php echo isset($lang['js_server_error']) ? $lang['js_server_error'] : 'حدث خطأ في الاتصال بالخادم.'; ?>"
        };
        let viewportTag = document.querySelector('meta[name="viewport"]');
if (viewportTag) {
    viewportTag.setAttribute('content', 'width=1024');
} else {
    let newViewport = document.createElement('meta');
    newViewport.name = 'viewport';
    newViewport.content = 'width=1024';
    document.head.appendChild(newViewport);
}
    </script>
    
    <script src="<?= $base_url ?>assets/js/home.js"></script>

</body>
</html>