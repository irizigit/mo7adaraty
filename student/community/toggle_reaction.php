<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'غير مصرح']);
    exit();
}

require_once __DIR__ . '/../../config/db_connexion.php';

$user_id = $_SESSION['user_id'];
$content_id = filter_input(INPUT_POST, 'content_id', FILTER_VALIDATE_INT);
$content_type = $_POST['content_type'] ?? 'post';
$reaction_type = $_POST['reaction_type'] ?? 'like';

if (!$content_id) {
    echo json_encode(['success' => false, 'message' => 'معرف غير صالح']);
    exit();
}

try {
    // التحقق مما إذا كان المستخدم قد تفاعل مسبقاً
    $check_stmt = $pdo->prepare("
        SELECT reaction_id FROM content_reactions 
        WHERE content_id = ? AND content_type = ? AND user_id = ?
    ");
    $check_stmt->execute([$content_id, $content_type, $user_id]);
    $existing = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        // حذف التفاعل (Unlike)
        $del_stmt = $pdo->prepare("DELETE FROM content_reactions WHERE reaction_id = ?");
        $del_stmt->execute([$existing['reaction_id']]);
        $action = 'removed';
    } else {
        // إضافة التفاعل (Like)
        $add_stmt = $pdo->prepare("
            INSERT INTO content_reactions (content_id, content_type, user_id, reaction_type) 
            VALUES (?, ?, ?, ?)
        ");
        $add_stmt->execute([$content_id, $content_type, $user_id, $reaction_type]);
        $action = 'added';
    }

    // جلب العداد المحدث
    $count_stmt = $pdo->prepare("
        SELECT COUNT(*) FROM content_reactions 
        WHERE content_id = ? AND content_type = ? AND reaction_type = 'like'
    ");
    $count_stmt->execute([$content_id, $content_type]);
    $likes_count = (int)$count_stmt->fetchColumn();

    echo json_encode([
        'success'     => true,
        'action'      => $action,
        'likes_count' => $likes_count
    ]);
    exit();

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit();
}