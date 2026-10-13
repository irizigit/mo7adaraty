<?php
session_start();
require_once '../../config/db_connexion.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $track_name = trim($_POST['track_name'] ?? '');
    $division_id = filter_input(INPUT_POST, 'division_id', FILTER_VALIDATE_INT);

    if (empty($track_name) || !$division_id) {
        $_SESSION['error'] = "الرجاء إدخال اسم المسار واختيار الشعبة.";
        header("Location: index.php");
        exit();
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO tracks (track_name, division_id) VALUES (?, ?)");
        $stmt->execute([$track_name, $division_id]);

        $_SESSION['msg'] = "تم إضافة المسار بنجاح.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "خطأ في قاعدة البيانات: " . $e->getMessage();
    }
    header("Location: index.php");
    exit();
}