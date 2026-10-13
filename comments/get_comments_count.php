<?php
session_start();
require_once '../config/db_connexion.php';

if (!isset($_GET['content_id']) || !isset($_GET['content_type'])) {
    echo json_encode(['success' => false, 'count' => 0]);
    exit();
}

$content_id = filter_var($_GET['content_id'], FILTER_VALIDATE_INT);
$content_type = preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['content_type']);

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE content_id = ? AND content_type = ?");
    $stmt->execute([$content_id, $content_type]);
    $count = $stmt->fetchColumn();

    echo json_encode(['success' => true, 'count' => $count]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'count' => 0]);
}
?>