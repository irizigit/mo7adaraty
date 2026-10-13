<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../config/db_connexion.php';

// حماية الصفحة
if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

// تأمين الحذف باستخدام POST فقط لمنع ثغرات الـ CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $msg_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    if ($msg_id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM visitor_messages WHERE id = ?");
            $stmt->execute([$msg_id]);
            
            $_SESSION['msg'] = "تم حذف الرسالة بنجاح.";
        } catch (PDOException $e) {
            $_SESSION['error'] = "حدث خطأ أثناء محاولة الحذف.";
        }
    }
}

// إعادة التوجيه إلى صندوق الوارد
header("Location: index.php");
exit();