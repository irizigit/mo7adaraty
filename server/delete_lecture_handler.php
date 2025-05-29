<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json');

// التأكد من أن المستخدم مسؤول
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    echo json_encode(['success' => false, 'message' => 'غير مصرح لك بالوصول.']);
    exit;
}

$input_data = file_get_contents('php://input');
$data = json_decode($input_data, true);

if (!isset($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'معرف المحاضرة مفقود.']);
    exit;
}

$lecture_id_to_delete = $data['id'];
$lectures_file = '../data/lectures.json';

$lectures = [];
if (file_exists($lectures_file) && filesize($lectures_file) > 0) {
    $lectures_json = file_get_contents($lectures_file);
    $lectures = json_decode($lectures_json, true);
    if ($lectures === null) {
        $lectures = [];
    }
}

$found_and_deleted = false;
$updated_lectures = [];
$deleted_files_paths = []; // لتخزين مسارات الملفات التي يجب حذفها فعلياً

foreach ($lectures as $lecture) {
    if ($lecture['id'] === $lecture_id_to_delete) {
        $found_and_deleted = true;
        // أضف مسارات الملفات المرتبطة بالمحاضرة المحذوفة
        if ($lecture['video_url']) {
            $deleted_files_paths[] = '../public/' . $lecture['video_url'];
        }
        if ($lecture['audio_url']) {
            $deleted_files_paths[] = '../public/' . $lecture['audio_url'];
        }
        foreach ($lecture['download_files'] as $file) {
            $deleted_files_paths[] = '../public/' . $file['path'];
        }
    } else {
        $updated_lectures[] = $lecture; // احتفظ بالمحاضرات التي لن يتم حذفها
    }
}

if ($found_and_deleted) {
    if (file_put_contents($lectures_file, json_encode($updated_lectures, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
        // حذف الملفات الفعلية من الخادم
        foreach ($deleted_files_paths as $file_path) {
            if (file_exists($file_path)) {
                unlink($file_path); // حذف الملف
            }
        }
        echo json_encode(['success' => true, 'message' => 'تم حذف المحاضرة بنجاح.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'فشل حفظ التغييرات في ملف JSON.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'المحاضرة المطلوبة للحذف لم يتم العثور عليها.']);
}
?>