<?php
session_start();
require_once '../../config/db_connexion.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['error' => 'غير مصرح لك بالوصول']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['division_id']) || !isset($_POST['class_id'])) {
    echo json_encode(['error' => 'طلب غير صالح']);
    exit;
}

$division_id = filter_input(INPUT_POST, 'division_id', FILTER_VALIDATE_INT);
$class_id = filter_input(INPUT_POST, 'class_id', FILTER_VALIDATE_INT);
$track_id = isset($_POST['track_id']) ? filter_input(INPUT_POST, 'track_id', FILTER_VALIDATE_INT) : null;

try {
    $sql = "SELECT co.offering_id, g.group_name 
            FROM course_offerings co
            LEFT JOIN `groups` g ON co.group_id = g.group_id
            WHERE co.division_id = ? AND co.class_id = ?";
    $params = [$division_id, $class_id];

    if ($track_id) {
        $sql .= " AND co.track_id = ?";
        $params[] = $track_id;
    } else {
        $sql .= " AND (co.track_id IS NULL OR co.track_id = 0)";
    }

    $sql .= " ORDER BY g.group_name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $offerings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($offerings)) {
        echo json_encode(['error' => 'لا توجد أفواج/مقررات مسجلة لهذا المسار حتى الآن']);
    } else {
        echo json_encode($offerings);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'حدث خطأ في قاعدة البيانات أثناء جلب الأفواج']);
}
?>