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
$action_type = $data['type'] ?? null; // 'view' or 'download'

if (!$lecture_id || !in_array($action_type, ['view', 'download'])) {
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
$new_count = 0;
$file_url = null; // لمسار ملف التحميل

if ($action_type === 'view') {
    $current_lecture['view_count'] = ($current_lecture['view_count'] ?? 0) + 1;
    $new_count = $current_lecture['view_count'];
} elseif ($action_type === 'download') {
    $current_lecture['download_count'] = ($current_lecture['download_count'] ?? 0) + 1;
    $new_count = $current_lecture['download_count'];
    // جلب رابط التحميل الفعلي للمحاضرة إذا كان موجوداً
    if (!empty($current_lecture['download_files']) && isset($current_lecture['download_files'][0]['path'])) {
        $file_url = '/islamique/public/' . $current_lecture['download_files'][0]['path'];
    } elseif ($current_lecture['video_url']) {
        $file_url = '/islamique/public/' . $current_lecture['video_url'];
    } elseif ($current_lecture['audio_url']) {
        $file_url = '/islamique/public/' . $current_lecture['audio_url'];
    }
}

$lectures[$found_index] = $current_lecture;

if (file_put_contents($lectures_file, json_encode($lectures, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    echo json_encode(['success' => true, 'new_count' => $new_count, 'file_url' => $file_url]);
} else {
    echo json_encode(['success' => false, 'message' => 'فشل تحديث العداد.']);
}
?>