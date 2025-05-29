<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json');

// هذا السكربت لا يتطلب صلاحيات مسؤول

$lectures_file = '../data/lectures.json';

$input_data = file_get_contents('php://input');
$data = json_decode($input_data, true);

$lecture_id = $data['id'] ?? null;
$interaction_type = $data['type'] ?? null; // 'like' or 'dislike'

if (!$lecture_id || !in_array($interaction_type, ['like', 'dislike'])) {
    echo json_encode(['success' => false, 'message' => 'بيانات غير صالحة.']);
    exit;
}

$lectures = [];
if (file_exists($lectures_file) && filesize($lectures_file) > 0) {
    $lectures_json = file_get_contents($lectures_file);
    $lectures = json_decode($lectures_json, true);
    if ($lectures === null) $lectures = [];
}

$found_index = -1;
foreach ($lectures as $index => $lecture) {
    if ($lecture['id'] === $lecture_id) {
        $found_index = $index;
        break;
    }
}

if ($found_index === -1) {
    echo json_encode(['success' => false, 'message' => 'المحاضرة لم يتم العثور عليها.']);
    exit;
}

$current_lecture = $lectures[$found_index];

// تهيئة interactions إذا لم تكن موجودة
if (!isset($current_lecture['interactions']) || !is_array($current_lecture['interactions'])) {
    $current_lecture['interactions'] = ['likes' => 0, 'dislikes' => 0];
}

if ($interaction_type === 'like') {
    $current_lecture['interactions']['likes'] = ($current_lecture['interactions']['likes'] ?? 0) + 1;
} elseif ($interaction_type === 'dislike') {
    $current_lecture['interactions']['dislikes'] = ($current_lecture['interactions']['dislikes'] ?? 0) + 1;
}

$lectures[$found_index] = $current_lecture;

if (file_put_contents($lectures_file, json_encode($lectures, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    echo json_encode([
        'success' => true,
        'new_likes' => $current_lecture['interactions']['likes'],
        'new_dislikes' => $current_lecture['interactions']['dislikes']
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'فشل تحديث التفاعل.']);
}
?>