<?php
// signup.php
if (isset($_POST['action']) && $_POST['action'] === 'get_subjects') {
    require_once 'config/db_connexion.php';
    $offering_id = intval($_POST['offering_id']);
    $stmt = $pdo->prepare("
        SELECT os.subject_id, s.subject_name, p.professor_name 
        FROM offering_subjects os
        JOIN subjects s ON os.subject_id = s.subject_id
        LEFT JOIN professors p ON os.professor_id = p.professor_id
        WHERE os.offering_id = ?
    ");
    $stmt->execute([$offering_id]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit();
}

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    $redirect = ($_SESSION['role'] === 'admin') ? 'admin/index.php' : 'student/profil.php';
    header("Location: $redirect");
    exit();
}

require_once 'config/db_connexion.php';

$lang = $_SESSION['lang'] ?? 'ar';
$lang_file = __DIR__ . "/lang/{$lang}.php";
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

$divisions = [];
try {
    $stmt_year = $pdo->query("SELECT id FROM academic_years WHERE is_current = 1 LIMIT 1");
    $current_year_id = $stmt_year->fetchColumn();

    if ($current_year_id) {
        $sql_divisions = "
            SELECT DISTINCT d.division_id, d.division_name 
            FROM divisions d
            INNER JOIN course_offerings co ON d.division_id = co.division_id
            WHERE co.academic_year_id = ? AND co.status = 1
            ORDER BY d.division_name ASC
        ";
        $stmt_divs = $pdo->prepare($sql_divisions);
        $stmt_divs->execute([$current_year_id]);
        $divisions = $stmt_divs->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    die(__('signup_db_error') ?? "خطأ في الاتصال بقاعدة البيانات.");
}

$dynamic_assets = [];
try {
    if (isset($pdo)) {
        $stmt_assets = $pdo->query("SELECT asset_key, file_path, page_section FROM design_assets");
        $assets_fetched = $stmt_assets->fetchAll(PDO::FETCH_ASSOC);
        foreach ($assets_fetched as $asset) {
            $dynamic_assets[$asset['page_section']][$asset['asset_key']] = $asset['file_path'];
        }
    }
} catch (PDOException $e) {}

$body_class = "tail-container signup-page-body min-h-screen flex flex-col";
if (!empty($dynamic_assets['login']['login_bg_body'])) {
    $body_class .= " has-custom-bg";
}

$card_class = "main-card w-full max-w-7xl signup-card rounded-3xl shadow-2xl overflow-hidden flex flex-col lg:flex-row";
if (!empty($dynamic_assets['login']['login_bg_card'])) {
    $card_class .= " has-custom-card-bg";
}

function getDynamicIcon($key, $target_folder = 'signup', $style = 'width: 50px; height: 50px;', $id = '', $class = '') {
    global $dynamic_assets;
    $base_url = '/mo7adaraty/';
    
    $attr_id = $id ? ' id="' . $id . '"' : '';
    $attr_class = $class ? ' class="' . $class . '"' : '';
    
    $path = '';
    
    if (!empty($dynamic_assets[$target_folder][$key])) {
        $path = ltrim($dynamic_assets[$target_folder][$key], '/');
    } else {
        $path = "includesimg/{$target_folder}/{$key}.png";
    }
    
    $path = str_ireplace('.svg', '.png', trim($path));
    
    return '<img src="' . htmlspecialchars($base_url . $path) . '" alt="" style="' . $style . ' object-fit: contain; z-index: 2; pointer-events: none;"' . $attr_id . $attr_class . '>';
}
?>
<!DOCTYPE html>
<html lang="<?= __('lang_code') ?? 'ar' ?>" dir="<?= __('lang_dir') ?? 'rtl' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('signup_page_title') ?></title>
    <link rel="icon" type="image/svg+xml" href="/mo7adaraty/assets/images/favicon.svg">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
    
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/style.css"> 
    <link rel="stylesheet" href="assets/css/auth.css">
    <link rel="stylesheet" href="assets/css/pages/signup.css">

    <style>
        :root {
            <?php if (!empty($dynamic_assets['login']['login_bg_body'])): ?>
            --signup-bg-body: url('<?= '/mo7adaraty/' . ltrim($dynamic_assets['login']['login_bg_body'], '/') ?>');
            <?php endif; ?>
            
            <?php if (!empty($dynamic_assets['login']['login_bg_card'])): ?>
            --signup-bg-card: url('<?= '/mo7adaraty/' . ltrim($dynamic_assets['login']['login_bg_card'], '/') ?>');
            <?php endif; ?>
        }
    </style>
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/shepherd.js@13.0.0/dist/css/shepherd.css"/>
<script src="https://cdn.jsdelivr.net/npm/shepherd.js@13.0.0/dist/js/shepherd.min.js"></script>
</head>

<body class="<?= $body_class ?>">

    <?php require_once 'templates/header.php'; ?>

    <main class="flex-1 flex items-center justify-center p-4 py-8 w-full">
        <div class="<?= $card_class ?>">
            
            <div class="hidden lg:flex lg:w-5/12 bg-gradient-to-br from-[#1a73e8] to-[#1557b0] text-white p-12 flex-col justify-between relative overflow-hidden">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_30%_20%,rgba(255,255,255,0.15)_0%,transparent_70%)]"></div>
                
                <div class="flex items-center gap-x-4 relative z-10">
                    <div class="w-12 h-12 bg-white rounded-2xl flex items-center justify-center shadow-inner">
                        <?php 
                            $logo_url = '/mo7adaraty/assets/images/favicon.svg';
                            if (!empty($dynamic_assets['login']['login_logo'])) {
                                $logo_url = '/mo7adaraty/' . ltrim($dynamic_assets['login']['login_logo'], '/');
                            }
                        ?>
                        <img src="<?= htmlspecialchars($logo_url) ?>" alt="Mo7adaraty" class="w-8 h-8 object-contain">
                    </div>
                    <span class="text-4xl font-bold tracking-tight">Mo7adaraty</span>
                </div>
                
                <div class="text-center relative z-10 py-12">
                    <div class="inline-flex items-center justify-center w-32 h-32 bg-white/10 backdrop-blur-3xl rounded-3xl mb-10 shadow-2xl border border-white/20">
                        <?= getDynamicIcon('signup_feat_grad', 'signup', 'width: 80px; height: 80px;') ?>
                    </div>
                    <h1 class="text-5xl font-bold leading-none mb-4"><?= __('signup_hero_title') ?></h1>
                    <p class="text-2xl text-white/90 max-w-xs mx-auto"><?= __('signup_hero_subtitle') ?></p>
                </div>
                
                <div class="space-y-8 relative z-10">
                    <div class="flex items-start gap-5">
                        <div class="w-14 h-14 bg-white/20 rounded-2xl flex items-center justify-center flex-shrink-0 mt-1">
                            <?= getDynamicIcon('signup_feat_book', 'signup', 'width: 35px; height: 35px;') ?>
                        </div>
                        <div>
                            <div class="font-semibold text-lg"><?= __('signup_feat_book_title') ?></div>
                            <div class="text-white/75 text-base"><?= __('signup_feat_book_desc') ?></div>
                        </div>
                    </div>
                    <div class="flex items-start gap-5">
                        <div class="w-14 h-14 bg-white/20 rounded-2xl flex items-center justify-center flex-shrink-0 mt-1">
                            <?= getDynamicIcon('signup_feat_users', 'signup', 'width: 35px; height: 35px;') ?>
                        </div>
                        <div>
                            <div class="font-semibold text-lg"><?= __('signup_feat_users_title') ?></div>
                            <div class="text-white/75 text-base"><?= __('signup_feat_users_desc') ?></div>
                        </div>
                    </div>
                    <div class="flex items-start gap-5">
                        <div class="w-14 h-14 bg-white/20 rounded-2xl flex items-center justify-center flex-shrink-0 mt-1">
                            <?= getDynamicIcon('signup_feat_mobile', 'signup', 'width: 35px; height: 35px;') ?>
                        </div>
                        <div>
                            <div class="font-semibold text-lg"><?= __('signup_feat_mobile_title') ?></div>
                            <div class="text-white/75 text-base"><?= __('signup_feat_mobile_desc') ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="text-center text-xs text-white/60 tracking-widest font-medium relative z-10">
                    <?= __('signup_footer_rights') ?> <?php echo date('Y'); ?>
                </div>
            </div>
            
            <div class="flex-1 lg:w-7/12 p-8 lg:p-14 flex flex-col">
                <div class="max-w-xl mx-auto w-full">
                    
                    <div class="flex items-center justify-between mb-10 lg:hidden">
                        <div class="flex items-center gap-x-3">
                            <img src="<?= htmlspecialchars($logo_url) ?>" alt="Mo7adaraty" class="w-12 h-12 object-contain">
                            <span class="text-3xl font-bold text-brand">Mo7adaraty</span>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-4 mb-4 lg:justify-start justify-center" data-aos="fade-up">
                        <?= getDynamicIcon('icon_signup', 'signup', 'width: 80px; height: 80px;') ?>
                        <h2 class="text-4xl font-bold text-primary"><?= __('signup_main_heading') ?></h2>
                    </div>
                    <p class="text-secondary text-center lg:text-start mb-8" data-aos="fade-up" data-aos-delay="100">
                        <?= __('signup_main_subheading') ?>
                    </p>
                    
                    <?php if (isset($_GET['error'])): ?>
                        <div class="signup-error-box px-6 py-4 rounded-3xl mb-8 flex items-center gap-3 text-sm">
                            <span style="font-weight:bold;"><?= __('signup_error_prefix') ?></span>
                            <span><?php echo htmlspecialchars($_GET['error']); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <form id="signupForm" action="auth/signup_process.php" method="POST" enctype="multipart/form-data" class="space-y-10">
                        
                        <div data-aos="fade-up" data-aos-delay="150">
                            <div class="flex items-center gap-3 mb-6">
                                <div class="h-px signup-divider-bg flex-1"></div>
                                <span class="text-xs font-medium uppercase tracking-widest text-secondary"><?= __('signup_section_personal') ?></span>
                                <div class="h-px signup-divider-bg flex-1"></div>
                            </div>
                            
                            <div class="space-y-6">
                                <div>
                                    <label class="block text-sm font-semibold text-primary mb-2" for="username"><?= __('signup_username_label') ?></label>
                                    <div class="form-input-container">
                                        <?= getDynamicIcon('signup_icon_user', 'signup', 'width: 50px; height: 50px; position: absolute; right: 10px;') ?>
                                        <input type="text" id="username" name="username" required class="signup-input w-full">
                                    </div>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-primary mb-2" for="email"><?= __('signup_email_label') ?></label>
                                    <div class="form-input-container">
                                        <?= getDynamicIcon('signup_icon_email', 'signup', 'width: 50px; height: 50px; position: absolute; right: 10px;') ?>
                                        <input type="email" id="email" name="email" required class="signup-input w-full">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-primary mb-2" for="gender"><?= __('signup_gender_label') ?></label>
                                    <div class="form-input-container">
                                        <?= getDynamicIcon('signup_icon_gender', 'signup', 'width: 50px; height: 50px; position: absolute; right: 10px;') ?>
                                        <select id="gender" name="gender" required class="signup-input w-full">
                                            <option value="" selected disabled><?= __('signup_gender_placeholder') ?></option>
                                            <option value="male"><?= __('signup_gender_male') ?></option>
                                            <option value="female"><?= __('signup_gender_female') ?></option>
                                            <option value="unspecified"><?= __('signup_gender_unspecified') ?></option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-primary mb-2" for="password"><?= __('signup_password_label') ?></label>
                                    <div class="form-input-container">
                                        <?= getDynamicIcon('signup_icon_password', 'signup', 'width: 50px; height: 50px; position: absolute; right: 10px;') ?>
                                        
                                        <input type="password" id="password" name="password" minlength="8" required class="signup-input password-input w-full">
                                               
                                        <div style="position: absolute; left: 10px; cursor: pointer; z-index: 10;" id="togglePasswordBtn" onclick="togglePasswordCustom()">
                                            <?= getDynamicIcon('signup_icon_eye', 'signup', 'width: 40px; height: 40px;', 'toggleEyeIcon') ?>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-4">
                                        <div class="h-2 bg-gray-100 rounded-3xl overflow-hidden">
                                            <div id="strength-bar" class="strength-bar w-0 bg-gradient-to-r from-red-500 via-yellow-400 to-emerald-500"></div>
                                        </div>
                                        <div id="strength-text" class="text-xs font-medium mt-2 text-secondary"></div>
                                    </div>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-primary mb-2" for="profile_picture"><?= __('signup_avatar_label') ?></label>
                                    <div class="flex items-center gap-6">
                                        <div class="form-input-container w-full">
                                            <?= getDynamicIcon('signup_icon_upload', 'signup', 'width: 50px; height: 50px; position: absolute; right: 10px;') ?>
                                            <input type="file" id="profile_picture" name="profile_picture" accept="image/*" class="signup-input w-full" style="padding-top: 20px;">
                                        </div>
                                        <div id="profile-preview-container" class="hidden flex-shrink-0">
                                            <img id="profile-preview" class="w-20 h-20 object-cover rounded-3xl border-4 border-white shadow-md" alt="معاينة الصورة">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div data-aos="fade-up" data-aos-delay="250">
                            <div class="flex items-center gap-3 mb-6">
                                <div class="h-px signup-divider-bg flex-1"></div>
                                <span class="text-xs font-medium uppercase tracking-widest text-secondary"><?= __('signup_section_courses') ?></span>
                                <div class="h-px signup-divider-bg flex-1"></div>
                            </div>
                            
                            <div class="space-y-6">
                                <div>
                                    <label class="block text-sm font-semibold text-primary mb-2" for="division"><?= __('signup_division_label') ?></label>
                                    <div class="form-input-container">
                                        <?= getDynamicIcon('signup_icon_division', 'signup', 'width: 50px; height: 50px; position: absolute; right: 10px;') ?>
                                        <select id="division" name="division_id" required class="signup-input w-full">
                                            <option value="" selected disabled><?= __('signup_division_placeholder') ?></option>
                                            <?php foreach ($divisions as $division): ?>
                                                <option value="<?php echo $division['division_id']; ?>">
                                                    <?php echo htmlspecialchars($division['division_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div id="enrollment-container" class="space-y-6"></div>
                                
                                <button type="button" id="add-enrollment-btn" class="hidden items-center justify-center gap-3 w-full py-4 bg-emerald-500 hover:bg-emerald-600 text-white font-semibold rounded-3xl transition-all active:scale-95 btn-custom">
                                    <?= getDynamicIcon('signup_icon_add', 'signup', 'width: 40px; height: 40px;') ?>
                                    <?= __('signup_btn_add_course') ?>
                                </button>
                            </div>
                        </div>
                        
                        <button type="submit" class="w-full btn-primary-custom font-bold rounded-3xl shadow-lg shadow-[#1a73e8]/30 transition-all active:scale-[0.98] flex items-center justify-center gap-3 btn-custom">
                            <?= __('signup_btn_create_account') ?>
                            <?= getDynamicIcon('icon_signup', 'signup', 'width: 50px; height: 50px;') ?>
                        </button>
                    </form>
                    
                    <div class="flex items-center justify-center gap-3 mt-10">
                        <p class="text-secondary mb-0"><?= __('signup_already_have_account') ?></p>
                        <a href="login.php" class="text-brand font-semibold hover:underline flex items-center gap-2">
                            <?= __('signup_login_link') ?>
                            <?= getDynamicIcon('signup_icon_login', 'signup', 'width: 40px; height: 40px;') ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script>
        window.SignupTranslations = {
            loading: "<?= __('signup_js_loading') ?>",
            select_class: "<?= __('signup_js_select_class') ?>",
            no_classes: "<?= __('signup_js_no_classes') ?>",
            conn_error: "<?= __('signup_js_conn_error') ?>",
            selected: "<?= __('signup_js_selected') ?>",
            select_group: "<?= __('signup_js_select_group') ?>",
            select_track: "<?= __('signup_js_select_track') ?>",
            select_track_first: "<?= __('signup_js_select_track_first') ?>",
            select_course: "<?= __('signup_js_select_course') ?>",
            group_prefix: "<?= __('signup_js_group_prefix') ?>",
            general_course: "<?= __('signup_js_general_course') ?>",
            no_courses: "<?= __('signup_js_no_courses') ?>",
            err_duplicate: "<?= __('signup_js_err_duplicate') ?>",
            lbl_class: "<?= __('signup_js_lbl_class') ?>",
            lbl_track: "<?= __('signup_js_lbl_track') ?>",
            lbl_course: "<?= __('signup_js_lbl_course') ?>"
        };
    </script>
    <script src="assets/js/signup.js"></script>
    <script src="assets/js/signup_ui.js"></script>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/driver.js@1.0.1/dist/driver.css"/>
<script src="https://cdn.jsdelivr.net/npm/driver.js@1.0.1/dist/driver.js.iife.js"></script>

    <script>
        window.appLang = "<?php echo isset($_SESSION['lang']) ? $_SESSION['lang'] : 'ar'; ?>";
    </script>

<script src="assets/js/driver/driver-tour.js"></script></body>
</html>