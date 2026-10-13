<?php
session_start();
require_once '../../config/db_connexion.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject_id = filter_input(INPUT_POST, 'subject_id', FILTER_VALIDATE_INT);

    if ($subject_id) {
        try {
            $pdo->beginTransaction();

            $stmt_links = $pdo->prepare("DELETE FROM offering_subjects WHERE subject_id = ?");
            $stmt_links->execute([$subject_id]);

            $stmt = $pdo->prepare("DELETE FROM subjects WHERE subject_id = ?");
            $stmt->execute([$subject_id]);

            $pdo->commit();
            $_SESSION['msg'] = "تم حذف المادة بنجاح.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "حدث خطأ أثناء الحذف: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "معرف المادة غير صالح.";
    }
}
header("Location: index.php");
exit();