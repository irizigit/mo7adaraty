<?php
session_start();
require_once '../../config/db_connexion.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $track_id = filter_input(INPUT_POST, 'track_id', FILTER_VALIDATE_INT);

    if ($track_id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM tracks WHERE track_id = ?");
            $stmt->execute([$track_id]);
            $_SESSION['msg'] = "تم حذف المسار بنجاح.";
        } catch (PDOException $e) {
            $_SESSION['error'] = "حدث خطأ أثناء الحذف: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "معرف المسار غير صالح.";
    }
}
header("Location: index.php");
exit();