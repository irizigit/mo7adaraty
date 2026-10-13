<?php
session_start();
require_once '../../config/db_connexion.php';

$content_id = filter_input(INPUT_GET, 'content_id', FILTER_VALIDATE_INT);
$content_type = $_GET['content_type'] ?? '';
$allowed_langs = ['ar', 'en', 'fr'];
$lang_code = (isset($_SESSION['lang']) && in_array($_SESSION['lang'], $allowed_langs)) ? $_SESSION['lang'] : 'fr';
$lang = require "../lang/{$lang_code}.php";
$dir = ($lang_code === 'ar') ? 'rtl' : 'ltr';

if (!$content_id || !in_array($content_type, ['lecture', 'exam', 'post'])) {
    exit();
}

try {
    // جلب التعليقات والردود مرتبة زمنياً
    $stmt = $pdo->prepare("
        SELECT c.comment_id, c.comment_text, c.created_at, c.user_id, c.parent_id,
               u.username, u.profile_picture 
        FROM comments c 
        JOIN users u ON c.user_id = u.user_id
        WHERE c.content_id = ? AND c.content_type = ?
        ORDER BY c.created_at ASC
    ");
    
    $stmt->execute([$content_id, $content_type]);
    $all_comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // فرز التعليقات الأساسية والردود
    $parents = [];
    $replies = [];

    foreach ($all_comments as $row) {
        if (empty($row['parent_id'])) {
            $parents[] = $row;
        } else {
            $replies[$row['parent_id']][] = $row;
        }
    }

    if (count($parents) > 0) {
        foreach ($parents as $comment) {
            $profile_picture = !empty($comment['profile_picture']) ? htmlspecialchars($comment['profile_picture']) : 'default_profile.png';
            $image_path = '../../uploads/profile_pictures/' . $profile_picture;
            $comment_date = date('Y-m-d H:i', strtotime($comment['created_at']));
            $comment_id = $comment['comment_id'];
            ?>
            
            <div class="comment-item" id="comment-<?= $comment_id ?>">
                <div class="comment-avatar">
                    <img src="<?= $image_path ?>" alt="<?= htmlspecialchars($comment['username']) ?>" onerror="this.onerror=null;this.src='../../uploads/profile_pictures/default_profile.png';">
                </div>
                
                <div class="comment-body">
                    <div class="comment-header">
                        <strong><?= htmlspecialchars($comment['username']) ?></strong>
                        <span class="comment-date"><i class="fa-regular fa-clock"></i> <?= $comment_date ?></span>
                    </div>
                    
                    <p class="comment-text"><?= nl2br(htmlspecialchars($comment['comment_text'])) ?></p>
                    
                    <div class="comment-actions">
                        <button type="button" class="reply-toggle-btn" data-comment-id="<?= $comment_id ?>">
                            <i class="fa-solid fa-reply"></i> <?= $lang['reply'] ?? 'رد' ?>
                        </button>

                        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $comment['user_id']): ?>
                            <button type="button" class="edit-comment-btn" data-comment-id="<?= $comment_id ?>">
                                <i class="fa-solid fa-pen"></i> <?= $lang['edit'] ?>
                            </button>
                            <button type="button" class="delete-comment-btn" data-comment-id="<?= $comment_id ?>">
                                <i class="fa-solid fa-trash-can"></i> <?= $lang['delete'] ?>
                            </button>
                        <?php endif; ?>
                    </div>

                    <!-- نموذج إضافة رد -->
                    <form class="nested-reply-form" style="display: none;" data-parent-id="<?= $comment_id ?>">
                        <input type="hidden" name="content_id" value="<?= $content_id ?>">
                        <input type="hidden" name="content_type" value="<?= $content_type ?>">
                        <input type="hidden" name="parent_id" value="<?= $comment_id ?>">
                        <div class="reply-input-group">
                            <textarea name="comment_text" rows="1" placeholder="<?= $lang['write_a_reply'] ?? 'اكتب رداً...' ?>" required></textarea>
                            <button type="submit"><i class="fa-solid fa-paper-plane"></i></button>
                        </div>
                    </form>

                    <!-- حاوية الردود الفرعية -->
                    <div class="replies-container">
                        <?php if (!empty($replies[$comment_id])): ?>
                            <?php foreach ($replies[$comment_id] as $reply): 
                                $r_pic = !empty($reply['profile_picture']) ? htmlspecialchars($reply['profile_picture']) : 'default_profile.png';
                                $r_path = '../../uploads/profile_pictures/' . $r_pic;
                                $r_date = date('Y-m-d H:i', strtotime($reply['created_at']));
                            ?>
                                <div class="comment-item reply-item" id="comment-<?= $reply['comment_id'] ?>">
                                    <div class="comment-avatar">
                                        <img src="<?= $r_path ?>" alt="<?= htmlspecialchars($reply['username']) ?>" onerror="this.onerror=null;this.src='../../uploads/profile_pictures/default_profile.png';">
                                    </div>
                                    <div class="comment-body">
                                        <div class="comment-header">
                                            <strong><?= htmlspecialchars($reply['username']) ?></strong>
                                            <span class="comment-date"><i class="fa-regular fa-clock"></i> <?= $r_date ?></span>
                                        </div>
                                        <p class="comment-text"><?= nl2br(htmlspecialchars($reply['comment_text'])) ?></p>
                                        
                                        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $reply['user_id']): ?>
                                            <div class="comment-actions">
                                                <button type="button" class="edit-comment-btn" data-comment-id="<?= $reply['comment_id'] ?>">
                                                    <i class="fa-solid fa-pen"></i> <?= $lang['edit'] ?>
                                                </button>
                                                <button type="button" class="delete-comment-btn" data-comment-id="<?= $reply['comment_id'] ?>">
                                                    <i class="fa-solid fa-trash-can"></i> <?= $lang['delete'] ?>
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php
        }
    } else {
        ?>
        <div class="no-comments-box">
            <i class="fa-regular fa-comments"></i>
            <p><?= $lang['no_comments_yet'] ?></p>
            <span><?= $lang['be_the_first_to_comment'] ?></span>
        </div>
        <?php
    }

} catch (PDOException $e) {
    echo '<div class="comment-error-alert"><i class="fa-solid fa-triangle-exclamation"></i> ' . $lang['error_loading_comments'] . '</div>';
}
?>