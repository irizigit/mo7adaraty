<?php
session_start();
require_once '../../config/db_connexion.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $track_id = filter_input(INPUT_POST, 'track_id', FILTER_VALIDATE_INT);
    $division_id = filter_input(INPUT_POST, 'division_id', FILTER_VALIDATE_INT);
    $track_name = trim($_POST['track_name'] ?? '');

    if (!$track_id || !$division_id || empty($track_name)) {
        $_SESSION['error'] = "بيانات غير صالحة.";
        header("Location: index.php");
        exit();
    }

    try {
        $stmt = $pdo->prepare("UPDATE tracks SET track_name = ?, division_id = ? WHERE track_id = ?");
        $stmt->execute([$track_name, $division_id, $track_id]);

        $_SESSION['msg'] = "تم تحديث المسار بنجاح.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "خطأ في قاعدة البيانات: " . $e->getMessage();
    }
    header("Location: index.php");
    exit();
}