<?php
session_start();
require_once '../../config/db_connexion.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $year_label = trim($_POST['year_label'] ?? '');

    if (!$id || empty($year_label)) {
        $_SESSION['error'] = "بيانات غير صالحة.";
        header("Location: index.php");
        exit();
    }

    try {
        $stmt = $pdo->prepare("UPDATE academic_years SET year_label = ? WHERE id = ?");
        $stmt->execute([$year_label, $id]);

        $_SESSION['msg'] = "تم تحديث مسمى العام بنجاح.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "خطأ في النظام: " . $e->getMessage();
    }
    header("Location: index.php");
    exit();
}