<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_user_id = $_SESSION['user_id'] ?? null;
$lang_code = (isset($_SESSION['lang']) && in_array($_SESSION['lang'], ['ar', 'en', 'fr'])) ? $_SESSION['lang'] : 'fr';
session_write_close();

require_once __DIR__ . '/../config/db_connexion.php';

$content_id = filter_input(INPUT_GET, 'content_id', FILTER_VALIDATE_INT);
$content_type = $_GET['content_type'] ?? '';

$lang_file = __DIR__ . "/../lang/{$lang_code}.php";
$lang = file_exists($lang_file) ? require $lang_file : [];
$dir = ($lang_code === 'ar') ? 'rtl' : 'ltr';

if (!$content_id || !in_array($content_type, ['lecture', 'exam', 'post'])) {
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT c.comment_id, c.comment_text, c.created_at, c.user_id, c.parent_id,
               u.username, u.profile_picture,
               pu.username AS replied_to_username
        FROM comments c 
        JOIN users u ON c.user_id = u.user_id
        LEFT JOIN comments pc ON c.parent_id = pc.comment_id
        LEFT JOIN users pu ON pc.user_id = pu.user_id
        WHERE c.content_id = ? AND c.content_type = ?
        ORDER BY c.created_at ASC
    ");
    
    $stmt->execute([$content_id, $content_type]);
    $all_comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $comments_map = [];
    foreach ($all_comments as $row) {
        $comments_map[$row['comment_id']] = $row;
    }

    function findRootCommentId($id, &$map) {
        $visited = [];
        $curr = $id;
        while (!empty($map[$curr]['parent_id']) && isset($map[$map[$curr]['parent_id']])) {
            if (in_array($curr, $visited)) break;
            $visited[] = $curr;
            $curr = $map[$curr]['parent_id'];
        }
        return $curr;
    }

    $root_comments = [];
    $replies_grouped = [];

    foreach ($all_comments as $row) {
        $cid = $row['comment_id'];
        if (empty($row['parent_id']) || !isset($comments_map[$row['parent_id']])) {
            $root_comments[] = $row;
        } else {
            $root_id = findRootCommentId($cid, $comments_map);
            $replies_grouped[$root_id][] = $row;
        }
    }

    if (count($root_comments) > 0) {
        foreach ($root_comments as $comment) {
            $raw_pic = trim($comment['profile_picture'] ?? '');
            if (empty($raw_pic) || in_array($raw_pic, ['default.png', 'default_male.png'])) {
                $pic_name = 'default_profile.png';
            } else {
                $pic_name = $raw_pic;
            }

            $image_path = '/mo7adaraty/uploads/profile_pictures/' . htmlspecialchars($pic_name);
            $comment_date = date('Y-m-d H:i', strtotime($comment['created_at']));
            $comment_id = $comment['comment_id'];
            ?>
            
            <div class="comment-item root-comment" id="comment-<?= $comment_id ?>">
                <div class="comment-avatar">
                    <img src="<?= $image_path ?>" alt="<?= htmlspecialchars($comment['username']) ?>" onerror="this.onerror=null;this.src='/mo7adaraty/uploads/profile_pictures/default_profile.png';">
                </div>
                
                <div class="comment-body">
                    <div class="comment-header">
                        <strong class="author-name"><?= htmlspecialchars($comment['username']) ?></strong>
                        <span class="comment-date"><i class="fa-regular fa-clock"></i> <?= $comment_date ?></span>
                    </div>
                    
                    <p class="comment-text"><?= nl2br(htmlspecialchars($comment['comment_text'])) ?></p>
                    
                    <div class="comment-actions">
                        <button type="button" class="reply-toggle-btn" data-comment-id="<?= $comment_id ?>">
                            <i class="fa-solid fa-reply"></i> <?= $lang['reply'] ?? 'رد' ?>
                        </button>

                        <?php if ($current_user_id && $current_user_id == $comment['user_id']): ?>
                            <button type="button" class="edit-comment-btn" data-comment-id="<?= $comment_id ?>">
                                <i class="fa-solid fa-pen"></i> <?= $lang['edit'] ?? 'تعديل' ?>
                            </button>
                            <button type="button" class="delete-comment-btn" data-comment-id="<?= $comment_id ?>">
                                <i class="fa-solid fa-trash-can"></i> <?= $lang['delete'] ?? 'حذف' ?>
                            </button>
                        <?php endif; ?>
                    </div>

                    <form class="nested-reply-form" style="display: none;" data-parent-id="<?= $comment_id ?>">
                        <input type="hidden" name="content_id" value="<?= $content_id ?>">
                        <input type="hidden" name="content_type" value="<?= $content_type ?>">
                        <input type="hidden" name="parent_id" value="<?= $comment_id ?>">
                        <div class="reply-input-group">
                            <textarea name="comment_text" rows="1" placeholder="<?= ($lang['write_a_reply'] ?? 'اكتب رداً على') . ' ' . htmlspecialchars($comment['username']) ?>..." required></textarea>
                            <button type="submit"><i class="fa-solid fa-paper-plane"></i></button>
                        </div>
                    </form>

                    <div class="replies-container">
                        <?php if (!empty($replies_grouped[$comment_id])): ?>
                            <?php foreach ($replies_grouped[$comment_id] as $reply): 
                                $r_raw_pic = trim($reply['profile_picture'] ?? '');
                                if (empty($r_raw_pic) || in_array($r_raw_pic, ['default.png', 'default_male.png'])) {
                                    $r_pic_name = 'default_profile.png';
                                } else {
                                    $r_pic_name = $r_raw_pic;
                                }
                                $r_path = '/mo7adaraty/uploads/profile_pictures/' . htmlspecialchars($r_pic_name);
                                $r_date = date('Y-m-d H:i', strtotime($reply['created_at']));
                                $r_id = $reply['comment_id'];
                            ?>
                                <div class="comment-item reply-item" id="comment-<?= $r_id ?>">
                                    <div class="comment-avatar">
                                        <img src="<?= $r_path ?>" alt="<?= htmlspecialchars($reply['username']) ?>" onerror="this.onerror=null;this.src='/mo7adaraty/uploads/profile_pictures/default_profile.png';">
                                    </div>
                                    <div class="comment-body">
                                        <div class="comment-header">
                                            <div class="user-meta">
                                                <strong class="author-name"><?= htmlspecialchars($reply['username']) ?></strong>
                                                <?php if (!empty($reply['replied_to_username'])): ?>
                                                    <span class="replying-to-badge">
                                                        <i class="fa-solid fa-share fa-flip-vertical"></i> <?= $lang['replying_to'] ?? 'رد على' ?> <strong>@<?= htmlspecialchars($reply['replied_to_username']) ?></strong>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <span class="comment-date"><i class="fa-regular fa-clock"></i> <?= $r_date ?></span>
                                        </div>
                                        
                                        <p class="comment-text"><?= nl2br(htmlspecialchars($reply['comment_text'])) ?></p>
                                        
                                        <div class="comment-actions">
                                            <button type="button" class="reply-toggle-btn" data-comment-id="<?= $r_id ?>">
                                                <i class="fa-solid fa-reply"></i> <?= $lang['reply'] ?? 'رد' ?>
                                            </button>

                                            <?php if ($current_user_id && $current_user_id == $reply['user_id']): ?>
                                                <button type="button" class="edit-comment-btn" data-comment-id="<?= $r_id ?>">
                                                    <i class="fa-solid fa-pen"></i> <?= $lang['edit'] ?? 'تعديل' ?>
                                                </button>
                                                <button type="button" class="delete-comment-btn" data-comment-id="<?= $r_id ?>">
                                                    <i class="fa-solid fa-trash-can"></i> <?= $lang['delete'] ?? 'حذف' ?>
                                                </button>
                                            <?php endif; ?>
                                        </div>

                                        <form class="nested-reply-form" style="display: none;" data-parent-id="<?= $r_id ?>">
                                            <input type="hidden" name="content_id" value="<?= $content_id ?>">
                                            <input type="hidden" name="content_type" value="<?= $content_type ?>">
                                            <input type="hidden" name="parent_id" value="<?= $r_id ?>">
                                            <div class="reply-input-group">
                                                <textarea name="comment_text" rows="1" placeholder="<?= ($lang['write_a_reply'] ?? 'اكتب رداً على') . ' ' . htmlspecialchars($reply['username']) ?>..." required></textarea>
                                                <button type="submit"><i class="fa-solid fa-paper-plane"></i></button>
                                            </div>
                                        </form>
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
            <p><?= $lang['no_comments_yet'] ?? 'لا توجد تعليقات بعد' ?></p>
            <span><?= $lang['be_the_first_to_comment'] ?? 'كن أول من يعلق' ?></span>
        </div>
        <?php
    }

} catch (PDOException $e) {
    echo '<div class="comment-error-alert"><i class="fa-solid fa-triangle-exclamation"></i> ' . ($lang['error_loading_comments'] ?? 'خطأ في تحميل التعليقات') . '</div>';
}