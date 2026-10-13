<?php
ob_start();
session_start();
require_once '../../config/db_connexion.php';

ob_clean();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'غير مصرح لك.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $offering_id = filter_input(INPUT_POST, 'offering_id', FILTER_VALIDATE_INT);
    $status = $_POST['status'] === 'active' ? 'active' : 'inactive';

    if (!$offering_id) {
        echo json_encode(['success' => false, 'message' => 'معرف المقرر غير صالح.']);
        exit();
    }

    try {
        $stmt = $pdo->prepare("UPDATE course_offerings SET status = ? WHERE offering_id = ?");
        $stmt->execute([$status, $offering_id]);

        echo json_encode(['success' => true, 'message' => 'تم تحديث الحالة بنجاح.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()]);
    }
    exit();
}