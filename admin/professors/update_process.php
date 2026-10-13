<?php
session_start();
require_once '../../config/db_connexion.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $professor_id = filter_input(INPUT_POST, 'professor_id', FILTER_VALIDATE_INT);
    $professor_name = trim($_POST['professor_name'] ?? '');
    $offering_subject_ids = $_POST['offering_subject_ids'] ?? [];

    if (!$professor_id || empty($professor_name)) {
        $_SESSION['error'] = "بيانات غير صالحة.";
        header("Location: index.php");
        exit();
    }

    try {
        $pdo->beginTransaction();

        // 1. تحديث اسم الأستاذ
        $stmt = $pdo->prepare("UPDATE professors SET professor_name = ? WHERE professor_id = ?");
        $stmt->execute([$professor_name, $professor_id]);

        // 2. تجديد الربط: إزالة تعيين الأستاذ من المواد السابقة (وليس حذف المواد)
        $stmt_del_links = $pdo->prepare("UPDATE offering_subjects SET professor_id = NULL WHERE professor_id = ?");
        $stmt_del_links->execute([$professor_id]);

        // 3. إضافة التعيينات الجديدة
        if (!empty($offering_subject_ids) && is_array($offering_subject_ids)) {
            $stmt_link = $pdo->prepare("UPDATE offering_subjects SET professor_id = ? WHERE offering_subject_id = ?");
            foreach ($offering_subject_ids as $os_id) {
                $clean_os_id = filter_var($os_id, FILTER_VALIDATE_INT);
                if ($clean_os_id) {
                    $stmt_link->execute([$professor_id, $clean_os_id]);
                }
            }
        }

        $pdo->commit();
        $_SESSION['msg'] = "تم تحديث بيانات الأستاذ وتعييناته بنجاح.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "خطأ في النظام: " . $e->getMessage();
    }
    header("Location: index.php");
    exit();
}