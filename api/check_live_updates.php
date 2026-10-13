<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$user_id = $_SESSION['user_id'] ?? null;
session_write_close();

header('Content-Type: application/json');

if (!$user_id) {
    echo json_encode(['success' => false, 'logged_in' => false]);
    exit();
}

require_once __DIR__ . '/../config/db_connexion.php';

$content_type = $_GET['content_type'] ?? 'post';

try {
    $counters = [];

    if ($content_type === 'lecture') {
        $active_ids = $_GET['active_lecture_ids'] ?? ($_GET['active_ids'] ?? []);
        if (!empty($active_ids) && is_array($active_ids)) {
            $clean_ids = array_filter(array_map('intval', $active_ids));
            if (!empty($clean_ids)) {
                $in_query = implode(',', $clean_ids);
                $stmt_counters = $pdo->query("
                    SELECT 
                        l.lecture_id,
                        (SELECT COUNT(*) FROM content_reactions WHERE content_id = l.lecture_id AND content_type = 'lecture' AND reaction_type = 'like') AS likes,
                        (SELECT COUNT(*) FROM content_reactions WHERE content_id = l.lecture_id AND content_type = 'lecture' AND reaction_type = 'dislike') AS dislikes,
                        (SELECT COUNT(*) FROM comments WHERE content_id = l.lecture_id AND content_type = 'lecture') AS comments,
                        (SELECT COALESCE(MAX(comment_id), 0) FROM comments WHERE content_id = l.lecture_id AND content_type = 'lecture') AS max_comment_id
                    FROM lectures l
                    WHERE l.lecture_id IN ($in_query)
                ");
                $counters = $stmt_counters->fetchAll(PDO::FETCH_ASSOC);
            }
        }
    } else {
        $last_post_id = filter_input(INPUT_GET, 'last_post_id', FILTER_VALIDATE_INT) ?: 0;
        $stmt_posts = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE post_id > ? AND status = 'approved'");
        $stmt_posts->execute([$last_post_id]);
        $new_posts_count = (int)$stmt_posts->fetchColumn();

        $active_ids = $_GET['active_post_ids'] ?? ($_GET['active_ids'] ?? []);
        if (!empty($active_ids) && is_array($active_ids)) {
            $clean_ids = array_filter(array_map('intval', $active_ids));
            if (!empty($clean_ids)) {
                $in_query = implode(',', $clean_ids);
                $stmt_counters = $pdo->query("
                    SELECT 
                        p.post_id,
                        (SELECT COUNT(*) FROM content_reactions WHERE content_id = p.post_id AND content_type = 'post' AND reaction_type = 'like') AS likes,
                        (SELECT COUNT(*) FROM comments WHERE content_id = p.post_id AND content_type = 'post') AS comments,
                        (SELECT COALESCE(MAX(comment_id), 0) FROM comments WHERE content_id = p.post_id AND content_type = 'post') AS max_comment_id
                    FROM posts p
                    WHERE p.post_id IN ($in_query)
                ");
                $counters = $stmt_counters->fetchAll(PDO::FETCH_ASSOC);
            }
        }
    }

    echo json_encode([
        'success'  => true,
        'counters' => $counters
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}