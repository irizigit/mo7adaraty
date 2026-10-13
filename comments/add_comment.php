<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$user_id = $_SESSION['user_id'] ?? null;
session_write_close();

header('Content-Type: application/json; charset=utf-8');

if (!$user_id || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'وصول غير مصرح به أو انتهت الجلسة.']);
    exit();
}

require_once __DIR__ . '/../config/db_connexion.php';

$content_id = isset($_POST['content_id']) ? (int)$_POST['content_id'] : 0;
$content_type = trim($_POST['content_type'] ?? '');
$comment_text = trim($_POST['comment_text'] ?? '');

$parent_id = (!empty($_POST['parent_id']) && is_numeric($_POST['parent_id']) && (int)$_POST['parent_id'] > 0) 
             ? (int)$_POST['parent_id'] 
             : null;

if (empty($comment_text) || !$content_id || !in_array($content_type, ['lecture', 'exam', 'post'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'بيانات غير صالحة أو نص التعليق فارغ.']);
    exit();
}

if ($parent_id !== null) {
    $check_parent = $pdo->prepare("SELECT comment_id FROM comments WHERE comment_id = ?");
    $check_parent->execute([$parent_id]);
    if (!$check_parent->fetchColumn()) {
        $parent_id = null;
    }
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO comments (content_type, content_id, user_id, parent_id, comment_text) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$content_type, $content_id, $user_id, $parent_id, $comment_text]);

    echo json_encode(['success' => true]);
    exit();

} catch (PDOException $e) {
    if ($e->getCode() == '23000') {
        try {
            $stmt_fallback = $pdo->prepare("
                INSERT INTO comments (content_type, content_id, user_id, parent_id, comment_text) 
                VALUES (?, ?, ?, NULL, ?)
            ");
            $stmt_fallback->execute([$content_type, $content_id, $user_id, $comment_text]);

            echo json_encode(['success' => true]);
            exit();
        } catch (PDOException $e2) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'خطأ قاعدة البيانات: ' . $e2->getMessage()]);
            exit();
        }
    }

    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'خطأ قاعدة البيانات: ' . $e->getMessage()]);
    exit();
}