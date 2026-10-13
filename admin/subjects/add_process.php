<?php
session_start();
require_once '../../config/db_connexion.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject_name = trim($_POST['subject_name'] ?? '');
    $offering_ids = $_POST['offering_ids'] ?? [];

    if (empty($subject_name)) {
        $_SESSION['error'] = "اسم المادة مطلوب.";
        header("Location: index.php");
        exit();
    }

    try {
        $pdo->beginTransaction(); 

        // إدخال المادة فقط (بدون أيقونة لأن الجدول لا يدعمها)
        $stmt = $pdo->prepare("INSERT INTO subjects (subject_name) VALUES (?)");
        $stmt->execute([$subject_name]);
        $subject_id = $pdo->lastInsertId();

        // إدخال الروابط الهرمية مع المقررات
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
        $_SESSION['msg'] = "تم إضافة المادة وربطها بالهيكلة بنجاح.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "حدث خطأ: " . $e->getMessage();
    }
    header("Location: index.php");
    exit();
}