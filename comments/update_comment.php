<?php
ob_start();
session_start();
require_once '../config/db_connexion.php';

ob_clean();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'طلب غير صالح.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$comment_id = filter_var($_POST['comment_id'] ?? 0, FILTER_VALIDATE_INT);
$new_text = trim($_POST['comment_text'] ?? '');

if (!$comment_id || empty($new_text)) {
    echo json_encode(['success' => false, 'message' => 'البيانات غير مكتملة.']);
    exit();
}

try {
    // التحقق من ملكية التعليق
    $stmt_check = $pdo->prepare("SELECT user_id FROM comments WHERE comment_id = ?");
    $stmt_check->execute([$comment_id]);
    $comment = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if (!$comment) {
        echo json_encode(['success' => false, 'message' => 'التعليق غير موجود.']);
        exit();
    }

    if ($comment['user_id'] != $user_id) {
        echo json_encode(['success' => false, 'message' => 'ليس لديك صلاحية لتعديل هذا التعليق.']);
        exit();
    }

    $stmt_update = $pdo->prepare("UPDATE comments SET comment_text = ? WHERE comment_id = ?");
    $stmt_update->execute([$new_text, $comment_id]);
    
    echo json_encode(['success' => true, 'message' => 'تم تعديل التعليق بنجاح.']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()]);
}
exit();
?>