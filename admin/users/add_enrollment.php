<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية الصفحة
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../login.php");
    exit();
}

$user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
$offering_id = filter_input(INPUT_POST, 'offering_id', FILTER_VALIDATE_INT);

if (!$user_id || !$offering_id) {
    $_SESSION['user_update_msg'] = "خطأ: البيانات غير كاملة.";
    header("Location: edit.php?id=" . $user_id);
    exit();
}

try {
    // 1. جلب السنة الجامعية المفعلة حالياً (نفس منطق التسجيل)
    $stmt_year = $pdo->query("SELECT id FROM academic_years WHERE is_current = 1 LIMIT 1");
    $current_year_id = $stmt_year->fetchColumn();

    if (!$current_year_id) {
        $_SESSION['user_update_msg'] = "خطأ: لا توجد سنة جامعية مفعلة حالياً في النظام لتسجيل الطالب.";
        header("Location: edit.php?id=" . $user_id);
        exit();
    }

    // 2. التحقق من أن الطالب غير مسجل بالفعل في هذا المقرر لنفس السنة
    $stmt_check = $pdo->prepare("SELECT enrollment_id FROM student_enrollments WHERE user_id = ? AND offering_id = ? AND academic_year_id = ?");
    $stmt_check->execute([$user_id, $offering_id, $current_year_id]);

    if ($stmt_check->fetch()) {
        $_SESSION['user_update_msg'] = "هذا الطالب مسجل بالفعل في المقرر المحدد خلال هذه السنة الجامعية.";
    } else {
        // 3. إضافة التسجيل الجديد مع ربطه بالسنة الحالية (إصلاح الثغرة)
        $stmt_insert = $pdo->prepare("INSERT INTO student_enrollments (user_id, offering_id, academic_year_id) VALUES (?, ?, ?)");
        $stmt_insert->execute([$user_id, $offering_id, $current_year_id]);
        $_SESSION['user_update_msg'] = "تمت إضافة التسجيل بنجاح في السنة الجامعية الحالية.";
    }

} catch (PDOException $e) {
    error_log("Admin Add Enrollment Error: " . $e->getMessage());
    $_SESSION['user_update_msg'] = "حدث خطأ في قاعدة البيانات.";
}

header("Location: edit.php?id=" . $user_id);
exit();
?>