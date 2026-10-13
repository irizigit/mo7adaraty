<?php
require_once '../../config/db_connexion.php';
header('Content-Type: application/json; charset=utf-8');

// التحقق من وصول البيانات المطلوبة
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['class_id']) || !isset($_POST['division_id'])) {
    echo json_encode(['error' => 'طلب غير صالح']);
    exit;
}

$class_id = filter_input(INPUT_POST, 'class_id', FILTER_VALIDATE_INT);
$division_id = filter_input(INPUT_POST, 'division_id', FILTER_VALIDATE_INT);
$track_id = isset($_POST['track_id']) ? filter_input(INPUT_POST, 'track_id', FILTER_VALIDATE_INT) : null;

if ($class_id === false || $division_id === false) {
    echo json_encode(['error' => 'معرفات غير صالحة']);
    exit;
}

try {
    if ($track_id) {
        // جلب الأفواج المرتبطة بالشعبة، الفصل، والمسار
        $stmt = $pdo->prepare("
            SELECT DISTINCT g.group_id, g.group_name 
            FROM `groups` g
            JOIN course_offerings co ON g.group_id = co.group_id
            WHERE co.division_id = ? AND co.class_id = ? AND co.track_id = ? AND co.status = 'active'
            ORDER BY g.group_name ASC
        ");
        $stmt->execute([$division_id, $class_id, $track_id]);
    } else {
        // جلب الأفواج المرتبطة بالشعبة والفصل (بدون مسار)
        $stmt = $pdo->prepare("
            SELECT DISTINCT g.group_id, g.group_name 
            FROM `groups` g
            JOIN course_offerings co ON g.group_id = co.group_id
            WHERE co.division_id = ? AND co.class_id = ? AND (co.track_id IS NULL OR co.track_id = 0) AND co.status = 'active'
            ORDER BY g.group_name ASC
        ");
        $stmt->execute([$division_id, $class_id]);
    }
    
    $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($groups)) {
        echo json_encode(['error' => 'لا توجد أفواج مفعلة لهذا الفصل']);
    } else {
        echo json_encode($groups);
    }
} catch (PDOException $e) {
    error_log("خطأ في get_groups.php (Timetables): " . $e->getMessage());
    echo json_encode(['error' => 'حدث خطأ في قاعدة البيانات أثناء جلب الأفواج']);
}
?>