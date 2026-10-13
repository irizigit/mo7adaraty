<?php
session_start();
require_once '../../config/db_connexion.php';

// تعيين نوع المحتوى ليكون JSON
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'غير مصرح لك أو طلب غير صالح.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$comment_id = filter_input(INPUT_POST, 'comment_id', FILTER_VALIDATE_INT);
$comment_text = trim($_POST['comment_text'] ?? '');

if (!$comment_id || empty($comment_text)) {
    echo json_encode(['success' => false, 'message' => 'البيانات غير مكتملة.']);
    exit();
}

try {
    $stmt = $pdo->prepare("UPDATE comments SET comment_text = ?, updated_at = NOW() WHERE comment_id = ? AND user_id = ?");
    $stmt->execute([$comment_text, $comment_id, $user_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'لا تملك صلاحية تعديل هذا التعليق أو لم تقم بأي تغيير.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'خطأ داخلي في الخادم.']);
}
?>