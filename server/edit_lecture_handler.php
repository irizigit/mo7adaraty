<?php
// تفعيل عرض الأخطاء للمساعدة في التصحيح
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    echo json_encode(['success' => false, 'message' => 'غير مصرح لك بالوصول.']);
    exit;
}

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

// استقبال البيانات
$lecture_id = $_POST['id'] ?? '';
$title = $_POST['title'] ?? '';
$description = $_POST['description'] ?? '';
$lecturer_id = $_POST['lecturer_id'] ?? '';
$category_id = $_POST['category_id'] ?? '';
$branch_id = $_POST['branch_id'] ?? '';
$semester_id = $_POST['semester_id'] ?? '';
$group_id = $_POST['group_id'] ?? '';

$existing_video_url = $_POST['existing_video_url'] ?? null;
$existing_audio_url = $_POST['existing_audio_url'] ?? null;
$existing_cover_image_url = $_POST['existing_cover_image_url'] ?? null; // جديد

// ملفات التحميل الموجودة التي لم يتم حذفها (يتم إرسالها كـ hidden inputs)
$existing_download_files_data = [];
if (isset($_POST['existing_download_files']) && is_array($_POST['existing_download_files'])) {
    foreach ($_POST['existing_download_files'] as $file) {
        if (isset($file['name']) && isset($file['path'])) {
            $existing_download_files_data[] = ['name' => $file['name'], 'path' => $file['path']];
        }
    }
}


// التحقق من البيانات الأساسية
if (empty($lecture_id) || empty($title) || empty($description) || empty($lecturer_id) || empty($category_id) || empty($branch_id) || empty($semester_id) || empty($group_id)) {
    echo json_encode(['success' => false, 'message' => 'الرجاء ملء جميع الحقول الإلزامية.']);
    exit;
}

$lectures = [];
if (file_exists($lectures_file) && filesize($lectures_file) > 0) {
    $lectures_json = file_get_contents($lectures_file);
    $lectures = json_decode($lectures_json, true);
    if ($lectures === null) $lectures = [];
}

$updated_lecture = null;
$original_lecture_index = -1;

// البحث عن المحاضرة المراد تعديلها
foreach ($lectures as $index => $lecture) {
    if ($lecture['id'] === $lecture_id) {
        $original_lecture_index = $index;
        $updated_lecture = $lecture; // ابدأ بنسخة من المحاضرة الأصلية
        break;
    }
}

if ($updated_lecture === null) {
    echo json_encode(['success' => false, 'message' => 'المحاضرة المراد تعديلها لم يتم العثور عليها.']);
    exit;
}

// تحديث البيانات الأساسية
$updated_lecture['title'] = $title;
$updated_lecture['description'] = $description;
$updated_lecture['lecturer_id'] = $lecturer_id;
$updated_lecture['category_id'] = $category_id;
$updated_lecture['branch_id'] = $branch_id;
$updated_lecture['semester_id'] = $semester_id;
$updated_lecture['group_id'] = $group_id;

// المحافظة على العدادات والتفاعلات كما هي من المحاضرة الأصلية
// لا يتم تحديثها من النموذج مباشرة، بل من handlers منفصلة
$updated_lecture['download_count'] = $updated_lecture['download_count'] ?? 0;
$updated_lecture['view_count'] = $updated_lecture['view_count'] ?? 0;
$updated_lecture['interactions'] = $updated_lecture['interactions'] ?? ['likes' => 0, 'dislikes' => 0];


// معالجة ملف المحاضرة الرئيسي الجديد (فيديو أو صوت)
if (isset($_FILES['lecture_file']) && $_FILES['lecture_file']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['lecture_file'];
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $new_file_name = $lecture_id . '_main.' . $file_ext; // اسم فريد للملف الجديد

    $upload_path = null;
    $new_video_url = null;
    $new_audio_url = null;

    if (str_starts_with($file['type'], 'video/')) {
        $upload_path = $videos_dir . $new_file_name;
        $new_video_url = 'videos/' . $new_file_name;
    } elseif (str_starts_with($file['type'], 'audio/')) {
        $upload_path = $audios_dir . $new_file_name;
        $new_audio_url = 'audios/' . $new_file_name;
    } else {
        echo json_encode(['success' => false, 'message' => 'نوع ملف المحاضرة الجديد غير مدعوم.']);
        exit;
    }

    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        // حذف الملف القديم إذا كان موجوداً ومختلفاً عن النوع الجديد
        if ($updated_lecture['video_url'] && $updated_lecture['video_url'] !== $new_video_url && file_exists('../public/' . $updated_lecture['video_url'])) {
            unlink('../public/' . $updated_lecture['video_url']);
        }
        if ($updated_lecture['audio_url'] && $updated_lecture['audio_url'] !== $new_audio_url && file_exists('../public/' . $updated_lecture['audio_url'])) {
            unlink('../public/' . $updated_lecture['audio_url']);
        }

        $updated_lecture['video_url'] = $new_video_url;
        $updated_lecture['audio_url'] = $new_audio_url;
    } else {
        echo json_encode(['success' => false, 'message' => 'فشل رفع ملف المحاضرة الجديد.']);
        exit;
    }
} else {
    // إذا لم يتم رفع ملف جديد، احتفظ بالملف القديم ما لم يكن هناك طلب لحذفه
    // أو إذا كان المستخدم قد قام بتغيير نوع الملف (مثلاً من فيديو إلى صوتي أو العكس)
    // حالياً، إذا لم يرفع ملف جديد، يبقى old_video_url/audio_url كما هو.
}

// معالجة ملف صورة الغلاف الجديد
if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['cover_image'];
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $new_cover_name = $lecture_id . '_cover_updated.' . $file_ext; // اسم جديد لتجنب التضارب
    $upload_path = $covers_dir . $new_cover_name;

    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        // حذف الصورة القديمة إذا كانت موجودة
        if ($updated_lecture['cover_image_url'] && file_exists('../public/' . $updated_lecture['cover_image_url'])) {
            unlink('../public/' . $updated_lecture['cover_image_url']);
        }
        $updated_lecture['cover_image_url'] = 'covers/' . $new_cover_name;
    } else {
        error_log("Failed to upload new cover image: " . $file['name']);
    }
} else {
    // إذا لم يتم رفع صورة غلاف جديدة، احتفظ بالصورة القديمة
    $updated_lecture['cover_image_url'] = $existing_cover_image_url;
}


// معالجة الملفات الإضافية القابلة للتحميل
$new_download_files_list = [];
// 1. إضافة الملفات الموجودة التي لم يتم إزالتها (يتم إرسالها من النموذج)
if (!empty($existing_download_files_data)) {
    foreach ($existing_download_files_data as $file) {
        $new_download_files_list[] = ['name' => $file['name'], 'path' => $file['path']];
    }
}

// 2. معالجة الملفات الجديدة المرفوعة
if (isset($_FILES['new_download_files'])) {
    foreach ($_FILES['new_download_files']['tmp_name'] as $index => $tmp_name) {
        if ($_FILES['new_download_files']['error'][$index] === UPLOAD_ERR_OK) {
            $file_name_original = $_FILES['new_download_files']['name'][$index];
            $file_ext = strtolower(pathinfo($file_name_original, PATHINFO_EXTENSION));
            $new_download_file_name = $lecture_id . '_download_' . uniqid() . '.' . $file_ext;
            $upload_path = $downloads_dir . $new_download_file_name;

            if (move_uploaded_file($tmp_name, $upload_path)) {
                $display_name = $_POST['new_download_names'][$index] ?? $file_name_original;
                $new_download_files_list[] = [
                    'name' => $display_name,
                    'path' => 'downloads/' . $new_download_file_name
                ];
            } else {
                error_log("Failed to upload new download file: " . $file_name_original);
            }
        }
    }
}
$updated_lecture['download_files'] = $new_download_files_list; // تحديث قائمة ملفات التحميل

// استبدال المحاضرة المحدثة في المصفوفة الأصلية
$lectures[$original_lecture_index] = $updated_lecture;

// حفظ البيانات المحدثة مرة أخرى إلى ملف JSON
if (file_put_contents($lectures_file, json_encode($lectures, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    echo json_encode(['success' => true, 'message' => 'تم تعديل المحاضرة بنجاح!']);
} else {
    echo json_encode(['success' => false, 'message' => 'فشل حفظ بيانات المحاضرة في ملف JSON.']);
}
?>