<?php
// تفعيل عرض الأخطاء للمساعدة في التصحيح
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../server/session_check.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$lecture_id = $_GET['id'] ?? null; // الحصول على ID المحاضرة من الـ URL

$lectures_file = '../data/lectures.json';
$lectures_data = [];
if (file_exists($lectures_file) && filesize($lectures_file) > 0) {
    $lectures_json = file_get_contents($lectures_file);
    $lectures_data = json_decode($lectures_json, true);
    if ($lectures_data === null) $lectures_data = [];
}

$current_lecture = null;
if ($lecture_id) {
    foreach ($lectures_data as $lecture) {
        if ($lecture['id'] === $lecture_id) {
            $current_lecture = $lecture;
            break;
        }
    }
}

// إذا لم يتم العثور على المحاضرة، يمكن إعادة التوجيه أو عرض رسالة خطأ
if (!$current_lecture) {
    header('Location: browse_lectures.php'); // إعادة التوجيه لصفحة التصفح
    exit;
}

// جلب بيانات التصنيفات، الأساتذة، الشعب، الفصول، الأفواج
$categories_file = '../data/categories.json';
$lecturers_file = '../data/lecturers.json';
$branches_file = '../data/branches.json';
$semesters_file = '../data/semesters.json';
$groups_file = '../data/groups.json';

$categories_data = [];
if (file_exists($categories_file) && filesize($categories_file) > 0) {
    $categories_json = file_get_contents($categories_file);
    $categories_data = json_decode($categories_json, true);
    if ($categories_data === null) $categories_data = [];
}

$lecturers_data = [];
if (file_exists($lecturers_file) && filesize($lecturers_file) > 0) {
    $lecturers_json = file_get_contents($lecturers_file);
    $lecturers_data = json_decode($lecturers_json, true);
    if ($lecturers_data === null) $lecturers_data = [];
}

$branches_data = [];
if (file_exists($branches_file) && filesize($branches_file) > 0) {
    $branches_json = file_get_contents($branches_file);
    $branches_data = json_decode($branches_json, true);
    if ($branches_data === null) $branches_data = [];
}

$semesters_data = [];
if (file_exists($semesters_file) && filesize($semesters_file) > 0) {
    $semesters_json = file_get_contents($semesters_file);
    $semesters_data = json_decode($semesters_json, true);
    if ($semesters_data === null) $semesters_data = [];
}

$groups_data = [];
if (file_exists($groups_file) && filesize($groups_file) > 0) {
    $groups_json = file_get_contents($groups_file);
    $groups_data = json_decode($groups_json, true);
    if ($groups_data === null) $groups_data = [];
}

// دوال مساعدة لجلب الأسماء
function getNameById($id, $data_array) {
    foreach ($data_array as $item) {
        if (isset($item['id']) && $item['id'] === $id) { // تأكد من وجود المفتاح 'id'
            return $item['name'];
        }
    }
    return 'غير معروف'; // قيمة افتراضية إذا لم يتم العثور على المعرف
}

// استخدام الدالة الجديدة لجلب أسماء المحاضرين والتصنيفات والشعب والفصول والأفواج
$category_name = getNameById($current_lecture['category_id'], $categories_data);
$lecturer_name = getNameById($current_lecture['lecturer_id'], $lecturers_data); // هذا هو السطر الذي سيتغير
$branch_name = getNameById($current_lecture['branch_id'] ?? null, $branches_data);     // استخدام null coalescing operator للتعامل مع المفاتيح غير الموجودة
$semester_name = getNameById($current_lecture['semester_id'] ?? null, $semesters_data); // استخدام null coalescing operator
$group_name = getNameById($current_lecture['group_id'] ?? null, $groups_data);         // استخدام null coalescing operator

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($current_lecture['title']); ?> - تفاصيل المحاضرة</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .lecture-content {
            margin-top: 20px;
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .lecture-content h3 {
            color: #333;
            margin-top: 0;
            font-size: 1.5em;
        }
        .lecture-content .meta-info {
            font-size: 0.9em;
            color: #555;
            margin-bottom: 15px;
        }
        .lecture-content .description {
            margin-bottom: 20px;
            line-height: 1.6;
        }
        .lecture-media {
            margin-bottom: 20px;
        }
        .lecture-media video, .lecture-media audio {
            width: 100%;
            max-width: 700px; /* لتحديد عرض أقصى */
            height: auto;
            border-radius: 8px;
            background-color: #000;
        }
        .download-links {
            margin-top: 20px;
            border-top: 1px solid #eee;
            padding-top: 15px;
        }
        .download-links h4 {
            color: #333;
            margin-bottom: 10px;
        }
        .download-links ul {
            list-style: none;
            padding: 0;
        }
        .download-links li {
            margin-bottom: 8px;
        }
        .download-links a {
            color: #007bff;
            text-decoration: none;
            font-weight: bold;
        }
        .download-links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>تفاصيل المحاضرة</h2>
        <p><a href="browse_lectures.php">العودة لتصفح المحاضرات</a></p>

        <div class="lecture-content">
            <h3><?php echo htmlspecialchars($current_lecture['title']); ?></h3>
            <div class="meta-info">
                <p>المحاضر: <?php echo htmlspecialchars($lecturer_name); ?></p>
                <p>الشعبة: <?php echo htmlspecialchars($branch_name); ?></p>
                <p>الفصل: <?php echo htmlspecialchars($semester_name); ?></p>
                <p>الفوج: <?php echo htmlspecialchars($group_name); ?></p>
                <p>التصنيف: <?php echo htmlspecialchars($category_name); ?></p>
                <p>تاريخ النشر: <?php echo htmlspecialchars($current_lecture['publish_date']); ?></p>
            </div>
            <div class="description">
                <p><?php echo nl2br(htmlspecialchars($current_lecture['description'])); ?></p>
            </div>

            <?php if (isset($current_lecture['video_url']) && $current_lecture['video_url']): ?>
            <div class="lecture-media">
                <video controls>
                    <source src="<?php echo htmlspecialchars('../public/' . $current_lecture['video_url']); ?>" type="video/mp4">
                    متصفحك لا يدعم وسم الفيديو.
                </video>
            </div>
            <?php elseif (isset($current_lecture['audio_url']) && $current_lecture['audio_url']): ?>
            <div class="lecture-media">
                <audio controls>
                    <source src="<?php echo htmlspecialchars('../public/' . $current_lecture['audio_url']); ?>" type="audio/mpeg">
                    متصفحك لا يدعم وسم الصوت.
                </audio>
            </div>
            <?php endif; ?>

            <?php if (isset($current_lecture['download_files']) && !empty($current_lecture['download_files'])): ?>
            <div class="download-links">
                <h4>ملفات قابلة للتحميل:</h4>
                <ul>
                    <?php foreach ($current_lecture['download_files'] as $file): ?>
                        <li><a href="<?php echo htmlspecialchars('../public/' . $file['path']); ?>" download><?php echo htmlspecialchars($file['name']); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>