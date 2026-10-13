<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية الصفحة
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'طالب';

$design_settings = [];
try {
    $stmt_design = $pdo->prepare("SELECT asset_key, file_path FROM design_assets WHERE page_section = 'class_lectures'");
    $stmt_design->execute();
    while ($row = $stmt_design->fetch(PDO::FETCH_ASSOC)) {
        $design_settings[$row['asset_key']] = $row['file_path'];
    }
} catch (PDOException $e) {}

function getDesignIcon($key, $default_path) {
    global $design_settings;
    return !empty($design_settings[$key]) ? '../../' . $design_settings[$key] : '../../' . $default_path;
}

// 1. حساب عدد المنشورات المعلقة الخاصة بالمستخدم لإظهار شارة العداد
$pending_count = 0;
try {
    $stmt_pending = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ? AND status = 'pending'");
    $stmt_pending->execute([$user_id]);
    $pending_count = (int)$stmt_pending->fetchColumn();
} catch (PDOException $e) {}

// 2. فحص ما إذا كان هناك طلب لتعديل منشور معلق
$edit_post = null;
if (isset($_GET['edit_post'])) {
    $edit_id = filter_input(INPUT_GET, 'edit_post', FILTER_VALIDATE_INT);
    if ($edit_id) {
        try {
            $stmt_edit = $pdo->prepare("SELECT * FROM posts WHERE post_id = ? AND user_id = ? AND status = 'pending'");
            $stmt_edit->execute([$edit_id, $user_id]);
            $edit_post = $stmt_edit->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {}
    }
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            p.post_id, p.post_text, p.image_path, p.created_at, p.background_style,
            u.username, u.profile_picture,
            (SELECT COUNT(*) FROM content_reactions WHERE content_id = p.post_id AND content_type = 'post' AND reaction_type = 'like') as likes_count,
            (SELECT COUNT(*) FROM comments WHERE content_id = p.post_id AND content_type = 'post') as comments_count,
            (SELECT reaction_type FROM content_reactions WHERE content_id = p.post_id AND content_type = 'post' AND user_id = :user_id) as user_reaction
        FROM posts p
        JOIN users u ON p.user_id = u.user_id
        WHERE p.status = 'approved'
        ORDER BY p.created_at DESC
    ");
    $stmt->execute(['user_id' => $user_id]);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("خطأ في جلب المنشورات: " . $e->getMessage());
}

$allowed_langs = ['ar', 'en', 'fr'];
$lang_code = (isset($_SESSION['lang']) && in_array($_SESSION['lang'], $allowed_langs)) ? $_SESSION['lang'] : 'fr';
$lang = require "../lang/{$lang_code}.php";
$dir = ($lang_code === 'ar') ? 'rtl' : 'ltr';

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang['community']; ?></title>
    <link rel="icon" type="image/svg+xml" href="../../assets/images/favicon.svg">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="../../assets/css/global.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../assets/css/pages/community.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="/mo7adaraty/comments/assets/css/comments.css?v=<?php echo time(); ?>">
    <style>
        .pending-posts-link {
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        .pending-badge {
            background: #ef4444;
            color: #ffffff;
            font-size: 11px;
            font-weight: bold;
            min-width: 20px;
            height: 20px;
            line-height: 20px;
            border-radius: 10px;
            text-align: center;
            padding: 0 6px;
            display: inline-block;
            box-shadow: 0 2px 6px rgba(239, 68, 68, 0.4);
            animation: pulse-badge 2s infinite;
        }
        @keyframes pulse-badge {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        .edit-notice-bar {
            background: rgba(217, 119, 6, 0.1);
            border: 1px solid #d97706;
            color: #d97706;
            padding: 8px 14px;
            border-radius: 8px;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    
    <script>
        (function() {
            const savedTheme = localStorage.getItem('mo7adaraty_theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>

    <?php require_once '../../templates/header.php'; ?>

    <main class="community-wrapper">
        <div class="community-container">

            <header class="community-header">
                <h1 class="community-title">
                    <img src="<?php echo getDesignIcon('cl_icon_nav_community', 'includesimg/class_lectures/icon_nav_community.svg'); ?>" alt="community" class="action-icon" style="width: 35px; height: 35px;">
                    <?php echo $lang['community']; ?>
                </h1>
                <a href="my_pending_posts.php" class="pending-posts-link">
                    <i class="fa-solid fa-hourglass-half"></i> <?php echo $lang['pending_posts']; ?>
                    <?php if ($pending_count > 0): ?>
                        <span class="pending-badge"><?php echo $pending_count; ?></span>
                    <?php endif; ?>
                </a>
            </header>

            <?php if (isset($_GET['error'])): ?>
                <div class="msg-alert msg-error">
                    <i class="fa-solid fa-circle-exclamation"></i> 
                    <?php
                    $errors = [
                        'empty_post' => $lang['empty_post'],
                        'background_with_image_not_allowed' => $lang['background_with_image_not_allowed'],
                        'invalid_image' => $lang['invalid_image'],
                        'upload_failed' => $lang['upload_failed'],
                        'background_without_text' => $lang['background_without_text'],
                        'db_error' => $lang['db_error'],
                    ];
                    echo $errors[$_GET['error']] ?? $lang['unknown_error'];
                    ?>
                </div>
            <?php elseif (isset($_GET['status']) && $_GET['status'] === 'post_submitted'): ?>
                <div class="msg-alert msg-success">
                    <i class="fa-solid fa-circle-check"></i> <?php echo $lang['post_submitted_successfully']; ?>
                </div>
            <?php endif; ?>

            <div class="create-post-card" id="create-post-card">
                <?php if ($edit_post): ?>
                    <div class="edit-notice-bar">
                        <span><i class="fa-solid fa-pen"></i> جاري الآن تعديل منشورك المعلق</span>
                        <a href="index.php" style="color: #ef4444; text-decoration: none;">إلغاء التعديل &times;</a>
                    </div>
                <?php endif; ?>

                <div class="create-post-header">
                    <?php 
                        $stmt_pic = $pdo->prepare("SELECT profile_picture FROM users WHERE user_id = ?");
                        $stmt_pic->execute([$user_id]);
                        $my_pic = $stmt_pic->fetchColumn() ?: 'default_profile.png';
                    ?>
                    <img src="../../uploads/profile_pictures/<?php echo htmlspecialchars($my_pic); ?>" alt="صورتي" class="create-post-avatar" onerror="this.src='../../uploads/profile_pictures/default_profile.png';">
                    <span style="font-weight: 600; color: var(--text-primary);">
                        <?php echo $edit_post ? 'تعديل المنشور' : $lang['share_your_thoughts']; ?>
                    </span>
                </div>
                
                <form action="process_post.php" method="POST" enctype="multipart/form-data">
                    <?php if ($edit_post): ?>
                        <input type="hidden" name="post_id" value="<?php echo $edit_post['post_id']; ?>">
                        <input type="hidden" name="action" value="update">
                    <?php endif; ?>

                    <?php 
                        $current_bg = $edit_post['background_style'] ?? 'none';
                        $has_bg_class = (!empty($current_bg) && $current_bg !== 'none') ? 'has-bg bg-style-' . htmlspecialchars($current_bg) : '';
                    ?>
                    <textarea name="post_text" id="post_text" class="create-post-textarea auto-resize <?php echo $has_bg_class; ?>" placeholder="بماذا تفكر يا <?php echo htmlspecialchars($username); ?>؟"><?php echo htmlspecialchars($edit_post['post_text'] ?? ''); ?></textarea>
                    
                    <div id="image-preview">
                        <?php if (!empty($edit_post['image_path'])): ?>
                            <div style="position: relative; display: inline-block; margin-top: 10px;">
                                <img src="../../<?php echo htmlspecialchars($edit_post['image_path']); ?>" style="max-width: 100%; max-height: 250px; border-radius: 8px; border: 1px solid var(--border-color); display: block;">
                                <button type="button" id="remove-image-btn" style="position: absolute; top: 5px; right: 5px; background: rgba(0,0,0,0.6); color: #fff; border: none; border-radius: 50%; width: 26px; height: 26px; cursor: pointer; display: flex; align-items: center; justify-content: center;">&times;</button>
                                <input type="hidden" name="existing_image" id="existing_image" value="<?php echo htmlspecialchars($edit_post['image_path']); ?>">
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="color-selector">
                        <span>  <?php echo $lang['background_style'] ?>:</span>
                        <div class="color-option <?php echo $current_bg === 'none' ? 'selected' : ''; ?>" data-style="none" onclick="selectStyle('none')"></div>
                        <div class="color-option bg-style-blue <?php echo $current_bg === 'blue' ? 'selected' : ''; ?>" data-style="blue" onclick="selectStyle('blue')"></div>
                        <div class="color-option bg-style-red <?php echo $current_bg === 'red' ? 'selected' : ''; ?>" data-style="red" onclick="selectStyle('red')"></div>
                        <div class="color-option bg-style-green <?php echo $current_bg === 'green' ? 'selected' : ''; ?>" data-style="green" onclick="selectStyle('green')"></div>
                        <div class="color-option bg-style-yellow <?php echo $current_bg === 'yellow' ? 'selected' : ''; ?>" data-style="yellow" onclick="selectStyle('yellow')"></div>
                        <div class="color-option bg-style-purple <?php echo $current_bg === 'purple' ? 'selected' : ''; ?>" data-style="purple" onclick="selectStyle('purple')"></div>
                        <input type="hidden" name="background_style" id="background_style" value="<?php echo htmlspecialchars($current_bg); ?>">
                    </div>
                    
                    <div class="form-actions">
                        <div class="file-upload-wrapper">
                            <label for="post_image" class="file-label">
                                <i class="fa-solid fa-image"></i> <?php echo $lang['add_image']; ?> 
                                <input type="file" id="post_image" name="post_image" accept="image/*">
                            </label>
                        </div>
                        <?php if ($edit_post): ?>
                            <div style="display: flex; gap: 8px;">
                                <a href="index.php" style="padding: 10px 16px; border-radius: 20px; background: var(--bg-body); border: 1px solid var(--border-color); color: var(--text-secondary); text-decoration: none; font-weight: bold; font-size: 14px;">إلغاء</a>
                                <button type="submit" class="submit-post-btn" style="background: #d97706;">
                                    <i class="fa-solid fa-check"></i> حفظ التعديلات
                                </button>
                            </div>
                        <?php else: ?>
                            <button type="submit" class="submit-post-btn">
                                <img src="<?php echo getDesignIcon('cl_icon_send', 'includesimg/class_lectures/icon_send.png'); ?>" alt="send" class="action-icon">
                                <?php echo $lang['submit']; ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="posts-feed">
                <?php if (empty($posts)): ?>
                    <div class="empty-feed">
                        <i class="fa-solid fa-comments" style="font-size: 40px; color: var(--border-color); margin-bottom: 15px;"></i>
                        <p><?php echo $lang['no_posts_to_display']; ?></p>
                    </div>
                <?php else: ?>
                    <?php foreach ($posts as $post): ?>
                        <div class="post-card" id="post-<?php echo $post['post_id']; ?>">
                            
                            <div class="post-header">
                                <div class="post-author-wrapper">
                                    <img src="../../uploads/profile_pictures/<?php echo htmlspecialchars($post['profile_picture'] ?: 'default_profile.png'); ?>" alt="User" class="post-avatar" onerror="this.src='../../uploads/profile_pictures/default_profile.png';">
                                    <div>
                                        <span class="author-name"><?php echo htmlspecialchars($post['username']); ?></span>
                                        <div class="post-time"><?php echo date('d M Y, H:i', strtotime($post['created_at'])); ?></div>
                                    </div>
                                </div>
                                <button class="post-options-btn report-btn" data-content-id="<?php echo $post['post_id']; ?>" title="<?php echo $lang['report_post']; ?>">
                                    <i class="fa-solid fa-ellipsis-v"></i>
                                </button>
                            </div>
                            
                            <div class="post-body">
                                <?php if (!empty($post['post_text'])): ?>
                                    <div class="post-text <?php echo (!empty($post['background_style']) && $post['background_style'] !== 'none') ? 'has-bg bg-style-' . htmlspecialchars($post['background_style']) : ''; ?>">
                                        <?php echo nl2br(htmlspecialchars($post['post_text'])); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($post['image_path'])): ?>
                                    <div class="post-image">
                                        <img src="../../<?php echo htmlspecialchars($post['image_path']); ?>" alt="<?php echo $lang['post_image']; ?>">
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="post-footer">
                                <div class="footer-action like-btn <?php echo $post['user_reaction'] === 'like' ? 'liked' : ''; ?>" data-content-id="<?php echo $post['post_id']; ?>">
                                    <img src="<?php echo getDesignIcon('cl_icon_like', 'includesimg/class_lectures/icon_like.png'); ?>" alt="like" class="action-icon"> 
                                    <?php echo $lang['like']; ?> (<span class="likes-count"><?php echo $post['likes_count']; ?></span>)
                                </div>
                                
                                <div class="footer-action comment-toggle-btn">
                                    <img src="<?php echo getDesignIcon('cl_icon_comments', 'includesimg/class_lectures/icon_comments.png'); ?>" alt="comment" class="action-icon"> 
                                    <?php echo $lang['comment']; ?> (<span class="comments-count"><?php echo $post['comments_count']; ?></span>)
                                </div>
                            </div>
                            
                            <div class="comments-section" style="display: none;">
                                <div class="comments-container" data-content-id="<?php echo $post['post_id']; ?>" data-content-type="post">
                                </div>
                                <form class="comment-form" action="/mo7adaraty/comments/add_comment.php" method="POST">
                                    <input type="hidden" name="content_id" value="<?php echo $post['post_id']; ?>">
                                    <input type="hidden" name="content_type" value="post">
                                    <textarea name="comment_text" class="auto-resize" placeholder="<?php echo $lang['write_your_comment']; ?>" required></textarea>
                                    <button type="submit">
                                        <img src="<?php echo getDesignIcon('cl_icon_send', 'includesimg/class_lectures/icon_send.png'); ?>" alt="send" class="action-icon">
                                    </button>
                                </form>
                            </div>
                            
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <div id="reportModal" class="modal">
        <div class="modal-content" style="background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-md);">
            <div class="modal-header" style="border-bottom: 1px solid var(--border-color); padding-bottom: 15px; margin-bottom: 20px;">
                <h2 style="color: var(--text-primary); font-size: 20px; font-weight: bold; display: flex; align-items: center; gap: 8px;">
                    <img src="<?php echo getDesignIcon('cl_icon_report', 'includesimg/class_lectures/icon_report.png'); ?>" alt="report" style="width: 30px;">
                    <?php echo $lang['report_post']; ?>
                </h2>
                <button class="close-btn" style="background: none; border: none; font-size: 24px; color: var(--text-secondary); cursor: pointer;">&times;</button>
            </div>
            <div class="modal-body">
                <form id="reportForm" action="submit_report.php" method="POST">
                    <input type="hidden" name="content_id" id="report_content_id">
                    <input type="hidden" name="content_type" id="report_content_type" value="post">
                    <div style="margin-bottom: 15px;">
                        <label for="reason" style="display: block; margin-bottom: 10px; font-weight: bold; color: var(--text-primary);"><?php echo $lang['what_is_the_issue_with_this_post']; ?></label>
                        <textarea id="reason" name="reason" placeholder="<?php echo $lang['please_explain_the_report_reason']; ?>" required style="width: 100%; min-height: 100px; padding: 15px; border-radius: var(--radius-sm); border: 1px solid var(--border-color); background: var(--bg-color); color: var(--text-primary); font-family: var(--font-family); resize: vertical;"></textarea>
                    </div>
                    <button type="submit" style="width: 100%; background: var(--brand-primary); color: white; border: none; padding: 12px; border-radius: var(--radius-pill); font-weight: bold; cursor: pointer; display: flex; justify-content: center; align-items: center; gap: 8px;">
                        <img src="<?php echo getDesignIcon('cl_icon_send', 'includesimg/class_lectures/icon_send.png'); ?>" alt="send" style="width: 20px;"> <?php echo $lang['submit_report']; ?>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        window.commentsLang = <?php echo json_encode([
            'edit_prompt'   => $lang['edit_prompt'] ?? 'تعديل التعليق:',
            'error_update'  => $lang['error_update'] ?? 'حدث خطأ أثناء التعديل',
            'server_error'  => $lang['server_error'] ?? 'خطأ في الاتصال بالخادم',
            'confirm_delete'=> $lang['confirm_delete'] ?? 'هل أنت متأكد من حذف هذا التعليق نهائياً؟',
            'error_delete'  => $lang['error_delete'] ?? 'تعذر حذف التعليق',
            'server_error_delete' => $lang['server_error_delete'] ?? 'خطأ في الخادم'
        ]); ?>;

        <?php if ($edit_post): ?>
        // التمرير التلقائي لصندوق التعديل عند فتح الرابط
        document.addEventListener('DOMContentLoaded', function() {
            var card = document.getElementById('create-post-card');
            if (card) {
                card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                document.getElementById('post_text').focus();
            }
        });
        <?php endif; ?>
    </script>
    <script src="community_script.js?v=<?php echo time(); ?>"></script>
    <script src="/mo7adaraty/comments/assets/js/comments.js?v=<?php echo time(); ?>"></script>

    <?php require_once '../../templates/footer.php'; ?>

</body>
</html>