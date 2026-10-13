<?php
session_start();
require_once '../../config/db_connexion.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../login.php");
    exit();
}

$exam_id = filter_input(INPUT_POST, 'exam_id', FILTER_VALIDATE_INT);
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$status = $_POST['status'] ?? 'pending';

if (!in_array($status, ['approved', 'pending', 'rejected'])) { $status = 'pending'; }

if (!$exam_id || empty($title)) {
    $_SESSION['error'] = "البيانات غير صالحة.";
    header("Location: edit.php?id=" . $exam_id);
    exit();
}

try {
    $stmt = $pdo->prepare("UPDATE exams SET title = ?, description = ?, status = ? WHERE exam_id = ?");
    $stmt->execute([$title, $description, $status, $exam_id]);

    $_SESSION['msg'] = "تم تحديث بيانات الامتحان بنجاح.";
} catch (PDOException $e) {
    $_SESSION['error'] = "خطأ في قاعدة البيانات: " . $e->getMessage();
}

header("Location: index.php");
exit();