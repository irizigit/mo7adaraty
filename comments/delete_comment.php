<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

$current_user_id = $_SESSION['user_id'] ?? null;
$session_role = $_SESSION['role'] ?? null;
session_write_close();

if (!$current_user_id || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'وصول غير مصرح به.']);
    exit();
}

require_once __DIR__ . '/../config/db_connexion.php';

$comment_id = filter_input(INPUT_POST, 'comment_id', FILTER_VALIDATE_INT);

if (!$comment_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'معرف التعليق غير صالح.']);
    exit();
}

try {
    // 1. جلب بيانات التعليق للتأكد من وجوده ومعرفة صاحبه
    $stmt = $pdo->prepare("SELECT user_id FROM comments WHERE comment_id = ?");
    $stmt->execute([$comment_id]);
    $comment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$comment) {
        echo json_encode(['success' => false, 'message' => 'التعليق غير موجود أو تم حذفه مسبقاً.']);
        exit();
    }

    // 2. التحقق من صلاحية المشرف
    $is_moderator = false;
    if (in_array($session_role, ['admin', 'moderator', 'supervisor'])) {
        $is_moderator = true;
    } else {
        $role_stmt = $pdo->prepare("SELECT role FROM users WHERE user_id = ?");
        $role_stmt->execute([$current_user_id]);
        $db_role = $role_stmt->fetchColumn();
        if (in_array($db_role, ['admin', 'moderator', 'supervisor'])) {
            $is_moderator = true;
        }
    }

    // 3. التحقق من ملكية التعليق أو امتلاك صلاحية الإشراف
    if ($comment['user_id'] != $current_user_id && !$is_moderator) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'لا تملك الصلاحية لحذف هذا التعليق.']);
        exit();
    }

    // 4. حذف التعليق (يتم حذف الردود المتفرعة تلقائياً بفضل الربط المتسلسل في قاعدة البيانات)
    $delete_stmt = $pdo->prepare("DELETE FROM comments WHERE comment_id = ?");
    $delete_stmt->execute([$comment_id]);

    echo json_encode(['success' => true]);
    exit();

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء حذف التعليق: ' . $e->getMessage()]);
    exit();
}