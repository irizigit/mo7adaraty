<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    echo json_encode(['success' => false, 'message' => 'غير مصرح لك بالوصول.']);
    exit;
}

$input_data = file_get_contents('php://input');
$data = json_decode($input_data, true);

if (!isset($data['lecture_id']) || !isset($data['file_path'])) {
    echo json_encode(['success' => false, 'message' => 'معرف المحاضرة أو مسار الملف مفقود.']);
    exit;
}

$lecture_id = $data['lecture_id'];
$file_path_to_delete = $data['file_path']; // المسار كما هو في JSON (مثلاً downloads/abc.pdf)

$lectures_file = '../data/lectures.json';
$lectures = [];
if (file_exists($lectures_file) && filesize($lectures_file) > 0) {
    $lectures_json = file_get_contents($lectures_file);
    $lectures = json_decode($lectures_json, true);
    if ($lectures === null) $lectures = [];
}

$found_lecture_index = -1;
foreach ($lectures as $index => $lecture) {
    if ($lecture['id'] === $lecture_id) {
        $found_lecture_index = $index;
        break;
    }
}

if ($found_lecture_index === -1) {
    echo json_encode(['success' => false, 'message' => 'المحاضرة لم يتم العثور عليها.']);
    exit;
}

$updated_download_files = [];
$file_was_deleted_from_array = false;
$full_server_file_path = '../public/' . $file_path_to_delete; // المسار الكامل على الخادم

foreach ($lectures[$found_lecture_index]['download_files'] as $file) {
    if ($file['path'] === $file_path_to_delete) {
        $file_was_deleted_from_array = true;
        // لا تضف هذا الملف إلى القائمة المحدثة
    } else {
        $updated_download_files[] = $file;
    }
}

// تحديث قائمة ملفات التحميل في المحاضرة
$lectures[$found_lecture_index]['download_files'] = $updated_download_files;

if ($file_was_deleted_from_array) {
    if (file_put_contents($lectures_file, json_encode($lectures, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
        // حذف الملف الفعلي من الخادم
        if (file_exists($full_server_file_path)) {
            unlink($full_server_file_path);
        }
        echo json_encode(['success' => true, 'message' => 'تم حذف الملف الإضافي بنجاح.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'فشل حفظ التغييرات في ملف JSON.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'الملف المطلوب لم يتم العثور عليه في المحاضرة.']);
}
?>