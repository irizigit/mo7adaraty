<?php
session_start();
require_once '../../config/db_connexion.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $review_id = filter_input(INPUT_POST, 'review_id', FILTER_VALIDATE_INT);

    if ($review_id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = ?");
            $stmt->execute([$review_id]);
            $_SESSION['msg'] = "تم حذف الرأي بنجاح.";
        } catch (PDOException $e) {
            $_SESSION['error'] = "حدث خطأ أثناء الحذف: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "معرف الرأي غير صالح.";
    }
}
header("Location: index.php");
exit();