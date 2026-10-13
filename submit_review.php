<?php
session_start();
require_once 'config/db_connexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $review_text = trim($_POST['review_text'] ?? '');
    $user_id = $_SESSION['user_id'] ?? null;
    $guest_name = trim($_POST['guest_name'] ?? '');
    $guest_division = trim($_POST['guest_division'] ?? '');

    // التحقق من المدخلات
    if (empty($review_text)) {
        $_SESSION['error'] = "نص الرأي مطلوب.";
        header("Location: home.php#reviews-section");
        exit;
    }

    if (!$user_id && empty($guest_name)) {
        $_SESSION['error'] = "يرجى إدخال اسمك.";
        header("Location: home.php#reviews-section");
        exit;
    }

    try {
        // إدخال الرأي بحالة قيد المراجعة افتراضياً
        $stmt = $pdo->prepare("INSERT INTO reviews (user_id, guest_name, guest_division, review_text, status) VALUES (?, ?, ?, ?, 'pending')");
        $stmt->execute([
            $user_id, 
            $user_id ? null : $guest_name, 
            $user_id ? null : $guest_division, 
            $review_text
        ]);
        
        $_SESSION['msg'] = "شكراً لك! تم إرسال رأيك بنجاح وبانتظار موافقة الإدارة لظهوره.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "حدث خطأ أثناء إرسال رأيك، يرجى المحاولة لاحقاً.";
    }
    
    header("Location: home.php#reviews-section");
    exit;
}