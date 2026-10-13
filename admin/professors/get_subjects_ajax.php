<?php
session_start();
require_once '../../config/db_connexion.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['error' => 'غير مصرح لك بالوصول']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['offering_id'])) {
    echo json_encode(['error' => 'طلب غير صالح']);
    exit;
}

$offering_id = filter_input(INPUT_POST, 'offering_id', FILTER_VALIDATE_INT);

try {
    // نجلب المواد المرتبطة بالمقرر المحدد فقط، مع جلب اسم الأستاذ المعين إن وجد
    $stmt = $pdo->prepare("
        SELECT os.offering_subject_id, s.subject_name, p.professor_name
        FROM offering_subjects os
        JOIN subjects s ON os.subject_id = s.subject_id
        LEFT JOIN professors p ON os.professor_id = p.professor_id
        WHERE os.offering_id = ?
        ORDER BY s.subject_name ASC
    ");
    $stmt->execute([$offering_id]);
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($subjects)) {
        echo json_encode(['error' => 'لا توجد مواد مسجلة لهذا الفوج، يرجى إضافة مواد أولاً']);
    } else {
        echo json_encode($subjects);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'حدث خطأ في قاعدة البيانات أثناء جلب المواد']);
}
?>