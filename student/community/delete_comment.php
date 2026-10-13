<?php
session_start();
require_once '../../config/db_connexion.php';

// تعيين نوع المحتوى ليكون JSON
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) { 
    echo json_encode(['success' => false, 'message' => 'غير مصرح لك.']);
    exit(); 
}

// استقبال الـ ID عبر POST وليس GET كما كان سابقاً
$comment_id = filter_input(INPUT_POST, 'comment_id', FILTER_VALIDATE_INT);

if ($comment_id) {
    try {
        // استخدام Prepared Statements للحماية من SQL Injection
        $stmt = $pdo->prepare("DELETE FROM comments WHERE comment_id = ? AND user_id = ?");
        $stmt->execute([$comment_id, $_SESSION['user_id']]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'لا تملك صلاحية حذف هذا التعليق.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'خطأ في قاعدة البيانات.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'معرف التعليق غير صالح.']);
}
exit();
?>