<?php
// تفعيل عرض الأخطاء للمساعدة في التصحيح
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

// المسارات لملفات JSON ومجلدات التحميل
$lectures_file = '../data/lectures.json';
$videos_dir = '../public/videos/';
$audios_dir = '../public/audios/';
$downloads_dir = '../public/downloads/';
$covers_dir = '../public/covers/'; // مسار جديد لصور الغلاف

// التأكد من وجود مجلدات التحميل وصلاحيات الكتابة
foreach ([$videos_dir, $audios_dir, $downloads_dir, $covers_dir] as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true); // 0777 permissions (مهم للتجربة على اللوكال هوست)
    }
}

// استقبال البيانات النصية من النموذج (method: POST)
$title = $_POST['title'] ?? '';
$description = $_POST['description'] ?? '';
$lecturer_id = $_POST['lecturer_id'] ?? '';
$category_id = $_POST['category_id'] ?? '';
$branch_id = $_POST['branch_id'] ?? '';
$semester_id = $_POST['semester_id'] ?? '';
$group_id = $_POST['group_id'] ?? '';
$publish_date = date('Y-m-d'); // تاريخ النشر اليوم

// التحقق من البيانات الأساسية
if (empty($title) || empty($description) || empty($lecturer_id) || empty($category_id) || empty($branch_id) || empty($semester_id) || empty($group_id)) {
    echo json_encode(['success' => false, 'message' => 'الرجاء ملء جميع الحقول الإلزامية.']);
    exit;
}

// توليد ID فريد للمحاضرة
$new_lecture_id = uniqid('lec_');

$video_url = null;
$audio_url = null;
$download_files_data = [];
$cover_image_url = null; // تهيئة لـ cover_image_url

// 1. معالجة ملف المحاضرة الرئيسي (فيديو أو صوت)
if (isset($_FILES['lecture_file']) && $_FILES['lecture_file']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['lecture_file'];
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $new_file_name = $new_lecture_id . '.' . $file_ext;

    if (str_starts_with($file['type'], 'video/')) {
        $upload_path = $videos_dir . $new_file_name;
        $video_url = 'videos/' . $new_file_name;
    } elseif (str_starts_with($file['type'], 'audio/')) {
        $upload_path = $audios_dir . $new_file_name;
        $audio_url = 'audios/' . $new_file_name;
    } else {
        echo json_encode(['success' => false, 'message' => 'نوع ملف المحاضرة غير مدعوم.']);
        exit;
    }

    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        echo json_encode(['success' => false, 'message' => 'فشل رفع ملف المحاضرة.']);
        exit;
    }
}

// 2. معالجة الملفات الإضافية القابلة للتحميل
if (isset($_FILES['download_files'])) {
    foreach ($_FILES['download_files']['tmp_name'] as $index => $tmp_name) {
        if ($_FILES['download_files']['error'][$index] === UPLOAD_ERR_OK) {
            $file_name_original = $_FILES['download_files']['name'][$index];
            $file_ext = strtolower(pathinfo($file_name_original, PATHINFO_EXTENSION));
            $new_download_file_name = $new_lecture_id . '_download_' . uniqid() . '.' . $file_ext;
            $upload_path = $downloads_dir . $new_download_file_name;

            if (move_uploaded_file($tmp_name, $upload_path)) {
                $download_files_data[] = [
                    'name' => $_POST['download_names'][$index] ?? $file_name_original,
                    'path' => 'downloads/' . $new_download_file_name
                ];
            } else {
                error_log("Failed to upload download file: " . $file_name_original);
            }
        }
    }
}

// 3. معالجة ملف صورة الغلاف
if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['cover_image'];
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $new_cover_name = $new_lecture_id . '_cover.' . $file_ext;
    $upload_path = $covers_dir . $new_cover_name;

    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        $cover_image_url = 'covers/' . $new_cover_name;
    } else {
        error_log("Failed to upload cover image: " . $file['name']);
        // لا توقف العملية، فقط سجل الخطأ واستمر بدون صورة غلاف
    }
}

// قراءة بيانات المحاضرات الحالية من ملف JSON
$lectures = [];
if (file_exists($lectures_file) && filesize($lectures_file) > 0) {
    $lectures_json = file_get_contents($lectures_file);
    $lectures = json_decode($lectures_json, true);
    if ($lectures === null) {
        $lectures = [];
    }
}

// بناء بيانات المحاضرة الجديدة
$new_lecture = [
    'id' => $new_lecture_id,
    'title' => $title,
    'description' => $description,
    'lecturer_id' => $lecturer_id,
    'category_id' => $category_id,
    'branch_id' => $branch_id,
    'semester_id' => $semester_id,
    'group_id' => $group_id,
    'publish_date' => $publish_date,
    'video_url' => $video_url,
    'audio_url' => $audio_url,
    'download_files' => $download_files_data,
    'cover_image_url' => $cover_image_url,         // جديد
    'download_count' => 0,                         // جديد (قيمة مبدئية)
    'view_count' => 0,                             // جديد (قيمة مبدئية)
    'interactions' => ['likes' => 0, 'dislikes' => 0] // جديد (قيم مبدئية)
];

// إضافة المحاضرة الجديدة إلى المصفوفة
$lectures[] = $new_lecture;

// حفظ البيانات المحدثة مرة أخرى إلى ملف JSON
if (file_put_contents($lectures_file, json_encode($lectures, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    echo json_encode(['success' => true, 'message' => 'تمت إضافة المحاضرة بنجاح!']);
} else {
    echo json_encode(['success' => false, 'message' => 'فشل حفظ بيانات المحاضرة في ملف JSON.']);
}
?>