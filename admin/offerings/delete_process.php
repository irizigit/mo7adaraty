<?php
session_start();
require_once '../../config/db_connexion.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $offering_id = filter_input(INPUT_POST, 'offering_id', FILTER_VALIDATE_INT);

    if ($offering_id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM course_offerings WHERE offering_id = ?");
            $stmt->execute([$offering_id]);
            $_SESSION['msg'] = "تم حذف المقرر وجميع البيانات المرتبطة به بنجاح.";
        } catch (PDOException $e) {
            $_SESSION['error'] = "حدث خطأ أثناء الحذف: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "معرف المقرر غير صالح.";
    }
}
header("Location: index.php");
exit();