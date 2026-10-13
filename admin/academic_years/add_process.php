<?php
session_start();
require_once '../../config/db_connexion.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $year_label = trim($_POST['year_label'] ?? '');

    if (empty($year_label)) {
        $_SESSION['error'] = "اسم العام الدراسي مطلوب.";
        header("Location: index.php");
        exit();
    }

    try {
        // عند إضافة عام جديد، نجعله افتراضياً ليس العام الحالي (is_current = 0)
        $stmt = $pdo->prepare("INSERT INTO academic_years (year_label, is_current) VALUES (?, 0)");
        $stmt->execute([$year_label]);

        $_SESSION['msg'] = "تم إضافة العام الدراسي بنجاح.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "خطأ في قاعدة البيانات: " . $e->getMessage();
    }
    header("Location: index.php");
    exit();
}