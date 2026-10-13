<?php
session_start();
require_once '../../config/db_connexion.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject_id = filter_input(INPUT_POST, 'subject_id', FILTER_VALIDATE_INT);
    $subject_name = trim($_POST['subject_name'] ?? '');
    $offering_ids = $_POST['offering_ids'] ?? [];

    if (!$subject_id || empty($subject_name)) {
        $_SESSION['error'] = "بيانات غير صالحة.";
        header("Location: index.php");
        exit();
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("UPDATE subjects SET subject_name = ? WHERE subject_id = ?");
        $stmt->execute([$subject_name, $subject_id]);

        // تجديد الربط: مسح الروابط القديمة وإدخال الجديدة
        $stmt_del_links = $pdo->prepare("DELETE FROM offering_subjects WHERE subject_id = ?");
        $stmt_del_links->execute([$subject_id]);

        if (!empty($offering_ids) && is_array($offering_ids)) {
            $stmt_link = $pdo->prepare("INSERT INTO offering_subjects (offering_id, subject_id) VALUES (?, ?)");
            foreach ($offering_ids as $offering_id) {
                $clean_off_id = filter_var($offering_id, FILTER_VALIDATE_INT);
                if ($clean_off_id) {
                    $stmt_link->execute([$clean_off_id, $subject_id]);
                }
            }
        }

        $pdo->commit();
        $_SESSION['msg'] = "تم تحديث المادة وارتباطاتها بنجاح.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "خطأ في النظام: " . $e->getMessage();
    }
    header("Location: index.php");
    exit();
}