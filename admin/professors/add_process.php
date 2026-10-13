<?php
session_start();
require_once '../../config/db_connexion.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $professor_name = trim($_POST['professor_name'] ?? '');
    $offering_subject_ids = $_POST['offering_subject_ids'] ?? [];

    if (empty($professor_name)) {
        $_SESSION['error'] = "اسم الأستاذ مطلوب.";
        header("Location: index.php");
        exit();
    }

    try {
        $pdo->beginTransaction();

        // 1. إضافة الأستاذ في الجدول الرئيسي (يحتوي فقط على ID و Name و Date)
        $stmt = $pdo->prepare("INSERT INTO professors (professor_name) VALUES (?)");
        $stmt->execute([$professor_name]);
        $professor_id = $pdo->lastInsertId();

        // 2. ربط الأستاذ بالمواد المحددة في المقررات (UPDATE بدلاً من INSERT)
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
        $_SESSION['msg'] = "تم إضافة الأستاذ وتعيينه بنجاح.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "حدث خطأ: " . $e->getMessage();
    }
    header("Location: index.php");
    exit();
}