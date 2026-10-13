<?php
// student/submit_report.php
session_start();
require_once '../config/db_connexion.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'وصول غير مصرح به.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$content_id = filter_input(INPUT_POST, 'content_id', FILTER_VALIDATE_INT);
$content_type = $_POST['content_type'] ?? '';
$reason = trim($_POST['reason'] ?? '');

$allowed_types = ['lecture', 'exam', 'comment', 'post', 'service_ad'];

if (!$content_id || !in_array($content_type, $allowed_types) || empty($reason)) {
    echo json_encode(['success' => false, 'message' => 'بيانات غير صالحة.']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO reports (reporter_user_id, content_type, content_id, reason, status) VALUES (?, ?, ?, ?, 'pending')");
    $stmt->execute([$user_id, $content_type, $content_id, $reason]);

    echo json_encode(['success' => true, 'message' => 'تم إرسال البلاغ بنجاح.']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء إرسال البلاغ.']);
}
?>