<?php
session_start();
require_once '../../config/db_connexion.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $professor_id = filter_input(INPUT_POST, 'professor_id', FILTER_VALIDATE_INT);

    if ($professor_id) {
        try {
            $pdo->beginTransaction();

            // إزالة الأستاذ من المواد التي كان يدرسها بوضع الحقل NULL (الآمان التام)
            $stmt_links = $pdo->prepare("UPDATE offering_subjects SET professor_id = NULL WHERE professor_id = ?");
            $stmt_links->execute([$professor_id]);

            // حذف الأستاذ من الجدول الرئيسي
            $stmt = $pdo->prepare("DELETE FROM professors WHERE professor_id = ?");
            $stmt->execute([$professor_id]);

            $pdo->commit();
            $_SESSION['msg'] = "تم حذف الأستاذ بنجاح وفك ارتباطاته بالمواد.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "حدث خطأ أثناء الحذف: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "معرف الأستاذ غير صالح.";
    }
}
header("Location: index.php");
exit();