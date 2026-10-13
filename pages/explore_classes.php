<?php
session_start();
require_once '../config/db_connexion.php';

// --- جلب الأيقونات الديناميكية الخاصة بصفحة الاستكشاف ---
$explore_assets = [];
try {
    $assets_query = "SELECT asset_key, file_path FROM design_assets WHERE page_section = 'Explore'";
    $stmt_assets = $pdo->query($assets_query);
    while ($row = $stmt_assets->fetch(PDO::FETCH_ASSOC)) {
        $explore_assets[$row['asset_key']] = $row['file_path'];
    }
} catch (PDOException $e) { }

function get_explore_icon($key, $default = '../assets/images/favicon.png') {
    global $explore_assets;
    return isset($explore_assets[$key]) ? '../' . htmlspecialchars($explore_assets[$key]) : $default;
}
// ----------------------------------------------------

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

$is_guest = !isset($_SESSION['user_id']);

$divisions = [];
$lectures = [];
$exams = [];
$posts = [];
$academic_tree = [];
$academic_tree_error = null;

try {
    $stmt_div = $pdo->query("SELECT division_name, division_image FROM divisions WHERE is_deleted = 0 ORDER BY division_name");
    if($stmt_div) $divisions = $stmt_div->fetchAll(PDO::FETCH_ASSOC);

    $tree_query = "
        SELECT 
            d.division_name, 
            c.class_name, 
            COALESCE(t.track_name, 'مسار عام') AS track_name, 
            COALESCE(g.group_name, 'فوج عام') AS group_name, 
            s.subject_name, 
            COALESCE(p.professor_name, 'غير محدد') AS professor_name, 
            l.title AS lecture_title, 
            l.file_path AS lecture_file
        FROM course_offerings co
        INNER JOIN divisions d ON co.division_id = d.division_id
        INNER JOIN classes c ON co.class_id = c.class_id
        INNER JOIN `groups` g ON co.group_id = g.group_id
        LEFT JOIN tracks t ON co.track_id = t.track_id
        LEFT JOIN offering_subjects os ON co.offering_id = os.offering_id
        LEFT JOIN subjects s ON os.subject_id = s.subject_id
        LEFT JOIN professors p ON os.professor_id = p.professor_id
        LEFT JOIN lectures l ON l.offering_subject_id = os.offering_subject_id AND l.status = 'approved' AND l.is_deleted = 0
        WHERE co.status = 'active' AND d.is_deleted = 0 AND c.is_deleted = 0
        ORDER BY d.division_id, c.class_id, t.track_id, g.group_id, s.subject_name
    ";
    
    $stmt_tree = $pdo->query($tree_query);
    if($stmt_tree) {
        $results = $stmt_tree->fetchAll(PDO::FETCH_ASSOC);
        if(empty($results)) {
            $academic_tree_error = "لا توجد بيانات مسجلة في الهيكلة الأكاديمية (جداول المقررات فارغة).";
        } else {
            foreach ($results as $row) {
                $div = !empty($row['division_name']) ? $row['division_name'] : 'غير محدد';
                $cls = !empty($row['class_name']) ? $row['class_name'] : 'غير محدد';
                $trk = !empty($row['track_name']) ? $row['track_name'] : 'مسار عام';
                $grp = !empty($row['group_name']) ? $row['group_name'] : 'فوج عام';
                $sbj = !empty($row['subject_name']) ? $row['subject_name'] : 'غير محدد';
                $prf = !empty($row['professor_name']) ? $row['professor_name'] : 'غير محدد';
                
                $academic_tree[$div][$cls][$trk][$grp][$sbj][$prf][] = [
                    'title' => $row['lecture_title'],
                    'file' => $row['lecture_file']
                ];
            }
        }
    }
} catch (PDOException $e) {
    $academic_tree_error = "خطأ برمجي في قاعدة البيانات: " . $e->getMessage();
}

try {
    $query_lectures = "
        SELECT 
            l.title, l.description, l.cover_image, l.upload_date, l.file_path, 
            s.subject_name, p.professor_name,
            d.division_name, c.class_name, t.track_name, g.group_name
        FROM lectures l
        LEFT JOIN offering_subjects os ON l.offering_subject_id = os.offering_subject_id
        LEFT JOIN course_offerings co ON os.offering_id = co.offering_id
        LEFT JOIN divisions d ON co.division_id = d.division_id
        LEFT JOIN classes c ON co.class_id = c.class_id
        LEFT JOIN tracks t ON co.track_id = t.track_id
        LEFT JOIN `groups` g ON co.group_id = g.group_id
        LEFT JOIN subjects s ON os.subject_id = s.subject_id
        LEFT JOIN professors p ON os.professor_id = p.professor_id
        WHERE l.status = 'approved' AND l.is_deleted = 0
        ORDER BY l.upload_date DESC LIMIT 6
    ";
    $stmt_lec = $pdo->query($query_lectures);
    if($stmt_lec) $lectures = $stmt_lec->fetchAll(PDO::FETCH_ASSOC);

    $query_exams = "
        SELECT e.exam_title, e.exam_date, e.file_path, s.subject_name 
        FROM exams e
        LEFT JOIN offering_subjects os ON e.offering_subject_id = os.offering_subject_id
        LEFT JOIN subjects s ON os.subject_id = s.subject_id
        WHERE e.status = 'approved' AND e.is_deleted = 0
        ORDER BY e.exam_date DESC LIMIT 6
    ";
    $stmt_ex = $pdo->query($query_exams);
    if($stmt_ex) $exams = $stmt_ex->fetchAll(PDO::FETCH_ASSOC);

    $query_posts = "
        SELECT p.post_text AS content, p.created_at, u.username, u.profile_picture 
        FROM posts p
        JOIN users u ON p.user_id = u.user_id
        WHERE p.status = 'approved'
        ORDER BY p.created_at DESC LIMIT 6
    ";
    $stmt_posts = $pdo->query($query_posts);
    if($stmt_posts) $posts = $stmt_posts->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {}

$base_url = '/mo7adaraty/';
?>
<!DOCTYPE html>
<html lang="<?= __('lang_code') ?? 'ar' ?>" dir="<?= __('lang_dir') ?? 'rtl' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('explore_page_title') ?></title>
    <meta name="description" content="<?= __('explore_meta_desc') ?>">
    <link rel="icon" type="image/svg+xml" href="../assets/images/favicon.svg">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/pages/explore.css">
    
    <style>
        .academic-tree details > summary { list-style: none; outline: none; }
        .academic-tree details > summary::-webkit-details-marker { display: none; }
        .academic-tree details > summary::before { 
            content: '\f0da'; 
            font-family: 'Font Awesome 6 Free'; 
            font-weight: 900; 
            display: inline-block; 
            width: 20px; 
            transition: transform 0.2s; 
            margin-left: 5px; 
            color: var(--brand-primary, #007bff);
        }
        .academic-tree details[open] > summary::before { transform: rotate(90deg); }
    </style>

    <script>
        window.ExploreConfig = {
            isGuest: <?= $is_guest ? 'true' : 'false' ?>
        };
    </script>
</head>
<body class="font-tajawal overflow-x-hidden">

    <script>
        (function() {
            const savedTheme = localStorage.getItem('mo7adaraty_theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>

    <?php require_once '../templates/header.php'; ?>

    <main class="explore-page-wrapper">
        
        <section class="explore-hero py-20 md:py-32 relative overflow-hidden">
            
            <video autoplay muted loop playsinline class="absolute top-0 left-0 w-full h-full object-fill z-0 pointer-events-none opacity-30">
                <source src="../assets/videos/explore_classes.mp4" type="video/mp4">
                <?= __('برجاء تحديث المتصفح لدعم تشغيل الفيديو') ?>
            </video>
            
            <div class="absolute top-0 left-0 w-full h-full bg-black/10 z-0"></div>

            <div class="explore-hero-content container mx-auto px-4 relative z-10 text-center">
                <h1 class="text-4xl md:text-5xl font-black mb-4 text-white"><?= __('explore_hero_title') ?></h1>
                <p class="text-lg md:text-xl opacity-90 max-w-2xl mx-auto text-white/90">
                    <?= __('explore_hero_subtitle') ?>
                </p>
            </div>
        </section>
        

        <div class="explore-tabs-container">
            <button class="explore-tab-btn active" data-target="tab-academic">
                <img src="<?= get_explore_icon('tab_academic') ?>" class="w-5 h-5 object-contain inline-block ml-1" alt="icon"> <?= __('explore_tab_academic') ?>
            </button>
            <button class="explore-tab-btn" data-target="tab-lectures">
                <img src="<?= get_explore_icon('tab_lectures') ?>" class="w-5 h-5 object-contain inline-block ml-1" alt="icon"> <?= __('explore_tab_lectures') ?>
            </button>
            <button class="explore-tab-btn" data-target="tab-exams">
                <img src="<?= get_explore_icon('tab_exams') ?>" class="w-5 h-5 object-contain inline-block ml-1" alt="icon"> <?= __('explore_tab_exams') ?>
            </button>
            <button class="explore-tab-btn" data-target="tab-community">
                <img src="<?= get_explore_icon('tab_community') ?>" class="w-5 h-5 object-contain inline-block ml-1" alt="icon"> <?= __('explore_tab_community') ?>
            </button>
        </div>

        <div class="container mx-auto px-4 py-8 mb-20 max-w-6xl">
            
            <div id="tab-academic" class="explore-tab-pane active">
                <div class="mb-12 academic-tree">
                    <h2 class="text-2xl font-bold mb-6 text-[var(--text-primary)] border-b border-[var(--border-color)] pb-2">الهيكلة الأكاديمية</h2>
                    
                    <?php if($academic_tree_error): ?>
                        <div class="text-[var(--danger-color)] text-center p-8 bg-red-50 rounded-xl border border-red-200" dir="ltr">
                            <strong>حدث خطأ يمنع تحميل الهيكلة:</strong><br>
                            <?= htmlspecialchars($academic_tree_error) ?>
                        </div>
                    <?php elseif(empty($academic_tree)): ?>
                        <p class="text-[var(--text-secondary)] text-center p-8 bg-[var(--bg-surface)] rounded-xl border border-[var(--border-color)]">جاري تحميل الهيكلة الأكاديمية...</p>
                    <?php else: ?>
                        <div class="bg-[var(--bg-surface)] p-4 md:p-6 rounded-xl border border-[var(--border-color)]">
                            <?php foreach($academic_tree as $div_name => $classes): ?>
                                <?php if($div_name === 'غير محدد') continue; ?>
                                <details class="mb-3 border border-[var(--border-color)] rounded-lg overflow-hidden bg-[var(--bg-body)]">
                                    <summary class="p-4 font-bold cursor-pointer hover:bg-[var(--brand-light)] transition-colors text-[var(--text-primary)] text-lg">
                                        <?= htmlspecialchars($div_name) ?>
                                    </summary>
                                    <div class="p-4 pr-6 border-t border-[var(--border-color)] bg-[var(--bg-surface)]">
                                        
                                        <?php foreach($classes as $cls_name => $tracks): ?>
                                            <?php if($cls_name === 'غير محدد') continue; ?>
                                            <details class="mb-3">
                                                <summary class="p-2 font-semibold cursor-pointer text-[var(--brand-primary)] hover:opacity-80 text-md">
                                                    <?= htmlspecialchars($cls_name) ?>
                                                </summary>
                                                <div class="p-2 pr-6 border-r-2 border-[var(--brand-light)] mr-2">
                                                    
                                                    <?php foreach($tracks as $track_name => $groups): ?>
                                                        <?php $has_track = ($track_name !== 'مسار عام' && $track_name !== ''); ?>
                                                        <?php if($has_track): ?>
                                                        <details class="mb-2">
                                                            <summary class="p-2 font-semibold cursor-pointer text-[var(--text-primary)] hover:text-[var(--brand-primary)]">
                                                                <i class="fas fa-route text-[var(--text-secondary)] ml-1 text-xs"></i> <?= htmlspecialchars($track_name) ?>
                                                            </summary>
                                                            <div class="p-2 pr-6 border-r border-[var(--border-color)] mr-2">
                                                        <?php endif; ?>

                                                        <?php foreach($groups as $group_name => $subjects): ?>
                                                            <?php $has_group = ($group_name !== 'فوج عام' && $group_name !== ''); ?>
                                                            <details class="mb-2">
                                                                <summary class="p-1 text-sm font-semibold cursor-pointer text-[var(--text-secondary)] hover:text-[var(--brand-primary)]">
                                                                    <i class="fas fa-users text-[var(--text-secondary)] ml-1 text-xs"></i> <?= htmlspecialchars($group_name) ?>
                                                                </summary>
                                                                <div class="p-2 pr-4 mr-2 border-r border-[var(--border-color)]">

                                                                    <?php foreach($subjects as $sub_name => $professors_tree): ?>
                                                                        <?php if($sub_name === 'غير محدد') continue; ?>
                                                                        <details class="mb-2 bg-[var(--bg-body)] rounded p-3 border border-[var(--border-color)]">
                                                                            <summary class="text-sm font-bold cursor-pointer text-[var(--text-primary)] hover:text-[var(--brand-primary)]">
                                                                                <i class="fas fa-book text-[var(--brand-primary)] ml-1 text-xs"></i> <?= htmlspecialchars($sub_name) ?>
                                                                            </summary>
                                                                            <div class="p-2 pr-4 mt-2">
                                                                                
                                                                                <?php foreach($professors_tree as $prof_name => $lectures_tree): ?>
                                                                                    <details class="mb-1">
                                                                                        <summary class="text-sm font-semibold cursor-pointer text-[var(--text-secondary)] hover:text-[var(--text-primary)]">
                                                                                            <i class="fas fa-user-tie ml-1 text-xs"></i> <?= htmlspecialchars($prof_name) ?>
                                                                                        </summary>
                                                                                        <ul class="list-none pr-6 mt-2 space-y-2 border-r-2 border-[var(--border-color)] mr-2">
                                                                                            <?php $has_lectures = false; ?>
                                                                                            <?php foreach($lectures_tree as $lec): ?>
                                                                                                <?php if(!empty($lec['title'])): ?>
                                                                                                    <?php $has_lectures = true; ?>
                                                                                                    <li>
                                                                                                        <a href="<?= $base_url . ltrim($lec['file'], '/') ?>" class="text-xs font-bold text-[var(--brand-primary)] hover:underline flex items-center requires-auth" <?= $is_guest ? 'target="_self"' : 'target="_blank"' ?>>
                                                                                                            <i class="fas fa-file-pdf ml-2 text-[var(--danger-color)]"></i> <?= htmlspecialchars($lec['title']) ?>
                                                                                                        </a>
                                                                                                    </li>
                                                                                                <?php endif; ?>
                                                                                            <?php endforeach; ?>
                                                                                            <?php if(!$has_lectures): ?>
                                                                                                <li class="text-xs text-[var(--text-secondary)]"><i class="fas fa-info-circle ml-1"></i> لا توجد محاضرات حالياً</li>
                                                                                            <?php endif; ?>
                                                                                        </ul>
                                                                                    </details>
                                                                                <?php endforeach; ?>

                                                                            </div>
                                                                        </details>
                                                                    <?php endforeach; ?>

                                                                </div>
                                                            </details>
                                                        <?php endforeach; ?>

                                                        <?php if($has_track): ?>
                                                            </div>
                                                        </details>
                                                        <?php endif; ?>

                                                    <?php endforeach; ?>

                                                </div>
                                            </details>
                                        <?php endforeach; ?>

                                    </div>
                                </details>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div id="tab-lectures" class="explore-tab-pane">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php if(empty($lectures)): ?>
                        <div class="col-span-full text-center p-10 bg-[var(--bg-surface)] rounded-xl border border-[var(--border-color)]">
                            <img src="<?= get_explore_icon('empty_lectures') ?>" class="w-16 h-16 object-contain mx-auto mb-4 opacity-70" alt="empty">
                            <p class="text-[var(--text-secondary)]"><?= __('explore_no_lectures') ?></p>
                        </div>
                    <?php else: ?>
                        <?php foreach($lectures as $lec): ?>
                        <div class="explore-card">
                            <?php if(!empty($lec['cover_image'])): ?>
                                <div class="w-full h-32 mb-3 bg-[var(--bg-body)] rounded-lg overflow-hidden border border-[var(--border-color)]">
                                    <img src="<?= $base_url ?>uploads/lectures/<?= htmlspecialchars($lec['cover_image']) ?>" alt="Cover" class="w-full h-full object-cover" onerror="this.style.display='none';">
                                </div>
                            <?php endif; ?>
                            
                            <span class="explore-badge"><?= __('explore_badge_lecture') ?></span>
                            <h3 class="explore-card-title"><?= htmlspecialchars($lec['title']) ?></h3>
                            
                            <?php if(!empty($lec['description'])): ?>
                                <p class="text-xs text-[var(--text-secondary)] mb-3 line-clamp-2"><?= nl2br(htmlspecialchars($lec['description'])) ?></p>
                            <?php endif; ?>
                            
                            <div class="explore-card-meta text-xs">
                                <p class="flex items-center"><i class="fas fa-university w-4 text-center ml-2 opacity-70"></i> <?= htmlspecialchars($lec['division_name'] ?? 'غير محدد') ?></p>
                                <p class="mt-1 flex items-center"><i class="fas fa-layer-group w-4 text-center ml-2 opacity-70"></i> <?= htmlspecialchars($lec['class_name'] ?? 'غير محدد') ?></p>
                                
                                <?php if(!empty($lec['track_name']) && $lec['track_name'] !== 'مسار عام'): ?>
                                    <p class="mt-1 flex items-center"><i class="fas fa-route w-4 text-center ml-2 opacity-70"></i> <?= htmlspecialchars($lec['track_name']) ?></p>
                                <?php endif; ?>
                                
                                <p class="mt-1 flex items-center"><i class="fas fa-users w-4 text-center ml-2 opacity-70"></i> <?= htmlspecialchars($lec['group_name'] ?? 'غير محدد') ?></p>
                                <p class="mt-1 flex items-center"><img src="<?= get_explore_icon('meta_subject') ?>" class="w-4 h-4 object-contain ml-2 opacity-70" alt="subject"> <?= htmlspecialchars($lec['subject_name'] ?? __('explore_general_subject')) ?></p>
                                <p class="mt-1 flex items-center"><img src="<?= get_explore_icon('meta_professor') ?>" class="w-4 h-4 object-contain ml-2 opacity-70" alt="professor"> <?= htmlspecialchars($lec['professor_name'] ?? __('explore_unspecified')) ?></p>
                            </div>
                            
                            <a href="<?= $base_url . ltrim($lec['file_path'], '/') ?>" class="explore-action-btn requires-auth mt-3" <?= $is_guest ? 'target="_self"' : 'target="_blank"' ?>>
                                <img src="<?= get_explore_icon('btn_download') ?>" class="w-4 h-4 object-contain ml-2" alt="download"> <?= __('explore_download_lecture') ?>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div id="tab-exams" class="explore-tab-pane">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php if(empty($exams)): ?>
                        <div class="col-span-full text-center p-10 bg-[var(--bg-surface)] rounded-xl border border-[var(--border-color)]">
                            <img src="<?= get_explore_icon('empty_exams') ?>" class="w-16 h-16 object-contain mx-auto mb-4 opacity-70" alt="empty">
                            <p class="text-[var(--text-secondary)]"><?= __('explore_no_exams') ?></p>
                        </div>
                    <?php else: ?>
                        <?php foreach($exams as $ex): ?>
                        <div class="explore-card">
                            <span class="explore-badge bg-[var(--danger-color)] text-white"><?= __('explore_badge_exam') ?></span>
                            <h3 class="explore-card-title"><?= htmlspecialchars($ex['exam_title']) ?></h3>
                            <div class="explore-card-meta">
                                <p class="flex items-center"><img src="<?= get_explore_icon('meta_subject') ?>" class="w-4 h-4 object-contain ml-2 opacity-70" alt="subject"> <?= htmlspecialchars($ex['subject_name'] ?? __('explore_general_subject')) ?></p>
                                <p class="mt-1 flex items-center"><img src="<?= get_explore_icon('meta_date') ?>" class="w-4 h-4 object-contain ml-2 opacity-70" alt="date"> <?= htmlspecialchars($ex['exam_date'] ?? __('explore_unspecified')) ?></p>
                            </div>
                            <a href="<?= $base_url . ltrim($ex['file_path'], '/') ?>" class="explore-action-btn requires-auth" <?= $is_guest ? 'target="_self"' : 'target="_blank"' ?>>
                                <img src="<?= get_explore_icon('btn_download') ?>" class="w-4 h-4 object-contain ml-2" alt="download"> <?= __('explore_download_exam') ?>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div id="tab-community" class="explore-tab-pane">
                <div class="max-w-3xl mx-auto">
                    <?php if(empty($posts)): ?>
                        <div class="text-center p-10 bg-[var(--bg-surface)] rounded-xl border border-[var(--border-color)]">
                            <img src="<?= get_explore_icon('empty_community') ?>" class="w-16 h-16 object-contain mx-auto mb-4 opacity-70" alt="empty">
                            <p class="text-[var(--text-secondary)]"><?= __('explore_no_posts') ?></p>
                        </div>
                    <?php else: ?>
                        <?php foreach($posts as $post): ?>
                        <div class="explore-post-card">
                            <div class="explore-post-header">
                                <?php $pic = $post['profile_picture'] ?: 'default_profile.png'; ?>
                                <img src="<?= $base_url ?>uploads/profile_pictures/<?= htmlspecialchars($pic) ?>" alt="User" class="explore-post-avatar" onerror="this.src='<?= $base_url ?>uploads/profile_pictures/default_profile.png';">
                                <div>
                                    <div class="explore-post-author"><?= htmlspecialchars($post['username']) ?></div>
                                    <div class="explore-post-time"><?= date('Y-m-d H:i', strtotime($post['created_at'])) ?></div>
                                </div>
                            </div>
                            <div class="explore-post-content">
                                <?= nl2br(htmlspecialchars(mb_strimwidth($post['content'], 0, 200, "..."))) ?>
                            </div>
                            <div class="flex gap-4 border-t border-[var(--border-color)] pt-3">
                                <button class="text-[var(--text-secondary)] hover:text-[var(--brand-primary)] transition-colors text-sm font-bold flex items-center gap-2 requires-auth">
                                    <img src="<?= get_explore_icon('btn_like') ?>" class="w-4 h-4 object-contain" alt="like"> <?= __('explore_btn_like') ?>
                                </button>
                                <button class="text-[var(--text-secondary)] hover:text-[var(--brand-primary)] transition-colors text-sm font-bold flex items-center gap-2 requires-auth">
                                    <img src="<?= get_explore_icon('btn_comment') ?>" class="w-4 h-4 object-contain" alt="comment"> <?= __('explore_btn_comment') ?>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </main>

    <div id="authModal" class="auth-modal-overlay">
        <div class="auth-modal-content">
            <button id="closeModal" class="auth-modal-close"><i class="fas fa-times"></i></button>
            <div class="auth-modal-icon"><img src="<?= get_explore_icon('modal_lock') ?>" class="w-10 h-10 object-contain mx-auto" alt="lock"></div>
            <h3 class="auth-modal-title"><?= __('explore_modal_title') ?></h3>
            <p class="auth-modal-text">
                <?= __('explore_modal_desc') ?>
            </p>
            <div class="auth-modal-actions">
                <a href="../signup.php" class="auth-modal-btn btn-primary-auth flex items-center justify-center">
                    <img src="<?= get_explore_icon('modal_signup') ?>" class="w-5 h-5 ml-2 object-contain" alt="signup"> <?= __('explore_modal_signup') ?>
                </a>
                <a href="../login.php" class="auth-modal-btn btn-secondary-auth flex items-center justify-center">
                    <img src="<?= get_explore_icon('modal_login') ?>" class="w-5 h-5 ml-2 object-contain" alt="login"> <?= __('explore_modal_login') ?>
                </a>
            </div>
        </div>
    </div>

    <?php require_once '../templates/footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/explore.js"></script>
</body>
</html>