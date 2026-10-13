<?php
session_start();
require_once '../../config/db_connexion.php';
header('Content-Type: application/json; charset=utf-8');

// حماية نقطة النهاية
if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['error' => 'غير مصرح لك بالوصول']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['division_id'])) {
    $division_id = filter_input(INPUT_POST, 'division_id', FILTER_VALIDATE_INT);
    
    if ($division_id === false || $division_id <= 0) {
        echo json_encode(['error' => 'معرف الشعبة غير صالح']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT track_id, track_name FROM tracks WHERE division_id = ? ORDER BY track_name ASC");
        $stmt->execute([$division_id]);
        $tracks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($tracks)) {
            echo json_encode(['error' => 'لا توجد مسارات متاحة لهذه الشعبة']);
        } else {
            echo json_encode($tracks);
        }
    } catch (PDOException $e) {
        error_log("خطأ في get_tracks.php (Subjects): " . $e->getMessage());
        echo json_encode(['error' => 'حدث خطأ أثناء جلب المسارات']);
    }
} else {
    echo json_encode(['error' => 'طلب غير صالح']);
}
?>