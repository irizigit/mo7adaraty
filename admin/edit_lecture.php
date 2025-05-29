<?php
require_once '../server/session_check.php';

// التأكد من أن المستخدم مسؤول قبل الوصول إلى هذه الصفحة
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Location: ../public/login.php');
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

// إذا لم يتم العثور على المحاضرة، إعادة التوجيه
if (!$current_lecture) {
    header('Location: manage_lectures.php');
    exit;
}

// جلب التصنيفات لعرضها في قائمة منسدلة
$categories_file = '../data/categories.json';
$categories_data = [];
if (file_exists($categories_file) && filesize($categories_file) > 0) {
    $categories_json = file_get_contents($categories_file);
    $categories_data = json_decode($categories_json, true);
    if ($categories_data === null) $categories_data = [];
}
?>
<?php
require_once '../server/session_check.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Location: ../public/login.php');
    exit;
}

$lecture_id = $_GET['id'] ?? null;

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

if (!$current_lecture) {
    header('Location: manage_lectures.php');
    exit;
}

// جلب التصنيفات
$categories_file = '../data/categories.json';
$categories_data = [];
if (file_exists($categories_file) && filesize($categories_file) > 0) {
    $categories_json = file_get_contents($categories_file);
    $categories_data = json_decode($categories_json, true);
    if ($categories_data === null) $categories_data = [];
}

// جلب الأساتذة
$lecturers_file = '../data/lecturers.json';
$lecturers_data = [];
if (file_exists($lecturers_file) && filesize($lecturers_file) > 0) {
    $lecturers_json = file_get_contents($lecturers_file);
    $lecturers_data = json_decode($lecturers_json, true);
    if ($lecturers_data === null) $lecturers_data = [];
}

// جلب الشعب والفصول والأفواج
$branches_file = '../data/branches.json';
$semesters_file = '../data/semesters.json';
$groups_file = '../data/groups.json';

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
?>


<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل المحاضرة: <?php echo htmlspecialchars($current_lecture['title']); ?></title>
    <link rel="stylesheet" href="../public/css/style.css">
    <style>
        .form-edit {
            margin-top: 20px;
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .form-edit label {
            font-weight: bold;
            margin-bottom: 5px;
            display: block;
        }
        .form-edit input[type="text"],
        .form-edit input[type="file"],
        .form-edit textarea,
        .form-edit select {
            width: calc(100% - 22px);
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .form-edit textarea {
            resize: vertical;
            min-height: 80px;
        }
        .file-upload-section {
            margin-top: 20px;
            padding: 15px;
            border: 1px dashed #ccc;
            border-radius: 8px;
            background-color: #f2f2f2;
        }
        .file-upload-section small {
            display: block;
            margin-top: -10px;
            margin-bottom: 10px;
            color: #777;
        }
        .current-file-info {
            margin-top: 10px;
            font-size: 0.9em;
            color: #555;
        }
        .add-file-btn, .remove-file-btn, .remove-existing-file-btn {
            background-color: #6c757d;
            color: white;
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9em;
            margin-top: 5px;
            margin-left: 5px;
            transition: background-color 0.3s ease;
        }
        .add-file-btn:hover, .remove-file-btn:hover, .remove-existing-file-btn:hover {
            background-color: #5a6268;
        }
        .remove-file-btn, .remove-existing-file-btn {
            background-color: #dc3545;
        }
        .remove-file-btn:hover, .remove-existing-file-btn:hover {
            background-color: #c82333;
        }
        .form-edit button[type="submit"] {
            margin-top: 20px;
            background-color: #007bff;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.1em;
        }
        .form-edit button[type="submit"]:hover {
            background-color: #0056b3;
        }
        #message {
            margin-top: 15px;
            padding: 10px;
            border-radius: 5px;
            font-weight: bold;
            display: none;
        }
        #message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        #message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <div class="container">
        <h2>تعديل المحاضرة: <?php echo htmlspecialchars($current_lecture['title']); ?></h2>
        <p><a href="manage_lectures.php">العودة لإدارة المحاضرات</a></p>

        <div id="message" class="message"></div>

        <form id="editLectureForm" class="form-edit" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($current_lecture['id']); ?>">

            <div class="form-group">
                <label for="title">عنوان المحاضرة:</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($current_lecture['title']); ?>" required>
            </div>

          <div class="form-group">
    <label for="lecturer_id">الأستاذ المحاضر:</label>
    <select id="lecturer_id" name="lecturer_id" required>
        <option value="">اختر أستاذاً</option>
        <?php foreach ($lecturers_data as $lecturer): ?>
            <option value="<?php echo htmlspecialchars($lecturer['id']); ?>"
                <?php echo ($current_lecture['lecturer_id'] === $lecturer['id']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($lecturer['name']); ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>



<div class="form-group">
    <label for="branch_id">الشعبة:</label>
    <select id="branch_id" name="branch_id" required>
        <option value="">اختر شعبة</option>
        <?php foreach ($branches_data as $branch): ?>
            <option value="<?php echo htmlspecialchars($branch['id']); ?>"
                <?php echo ($current_lecture['branch_id'] === $branch['id']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($branch['name']); ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>

<div class="form-group">
    <label for="semester_id">الفصل:</label>
    <select id="semester_id" name="semester_id" required>
        <option value="">اختر فصلاً</option>
        <?php foreach ($semesters_data as $semester): ?>
            <option value="<?php echo htmlspecialchars($semester['id']); ?>"
                <?php echo ($current_lecture['semester_id'] === $semester['id']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($semester['name']); ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>

<div class="form-group">
    <label for="group_id">الفوج:</label>
    <select id="group_id" name="group_id" required>
        <option value="">اختر فوجاً</option>
        <?php foreach ($groups_data as $group): ?>
            <option value="<?php echo htmlspecialchars($group['id']); ?>"
                <?php echo ($current_lecture['group_id'] === $group['id']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($group['name']); ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>





            <div class="form-group">
                <label for="category_id">التصنيف:</label>
                <select id="category_id" name="category_id" required>
                    <option value="">اختر تصنيفاً</option>
                    <?php foreach ($categories_data as $category): ?>
                        <option value="<?php echo htmlspecialchars($category['id']); ?>"
                            <?php echo ($current_lecture['category_id'] === $category['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="description">وصف المحاضرة:</label>
                <textarea id="description" name="description" rows="5" required><?php echo htmlspecialchars($current_lecture['description']); ?></textarea>
            </div>

            <div class="form-group file-upload-section">
                <label>ملف المحاضرة الحالي (فيديو أو صوت):</label>
                <?php if ($current_lecture['video_url']): ?>
                    <p class="current-file-info">ملف الفيديو الحالي: <a href="../public/<?php echo htmlspecialchars($current_lecture['video_url']); ?>" target="_blank"><?php echo basename($current_lecture['video_url']); ?></a></p>
                    <input type="hidden" name="existing_video_url" value="<?php echo htmlspecialchars($current_lecture['video_url']); ?>">
                <?php elseif ($current_lecture['audio_url']): ?>
                    <p class="current-file-info">ملف الصوت الحالي: <a href="../public/<?php echo htmlspecialchars($current_lecture['audio_url']); ?>" target="_blank"><?php echo basename($current_lecture['audio_url']); ?></a></p>
                    <input type="hidden" name="existing_audio_url" value="<?php echo htmlspecialchars($current_lecture['audio_url']); ?>">
                <?php else: ?>
                    <p class="current-file-info">لا يوجد ملف محاضرة حالي.</p>
                <?php endif; ?>
                <label for="lecture_file">استبدال ملف المحاضرة (اختياري):</label>
                <input type="file" id="lecture_file" name="lecture_file" accept="video/*,audio/*">
                <small>اختر ملف جديد لاستبدال الملف الحالي (فيديو أو صوت).</small>
            </div>

            <div class="form-group file-upload-section">
                <label>ملفات إضافية قابلة للتحميل:</label>
                <div id="existingDownloadFilesContainer">
                    <?php if (!empty($current_lecture['download_files'])): ?>
                        <?php foreach ($current_lecture['download_files'] as $index => $file): ?>
                            <div class="download-file-item">
                                <p class="current-file-info">
                                    ملف: <a href="../public/<?php echo htmlspecialchars($file['path']); ?>" target="_blank"><?php echo htmlspecialchars($file['name']); ?></a>
                                </p>
                                <input type="hidden" name="existing_download_files[<?php echo $index; ?>][name]" value="<?php echo htmlspecialchars($file['name']); ?>">
                                <input type="hidden" name="existing_download_files[<?php echo $index; ?>][path]" value="<?php echo htmlspecialchars($file['path']); ?>">
                                <button type="button" class="remove-existing-file-btn" data-path="<?php echo htmlspecialchars($file['path']); ?>">إزالة هذا الملف</button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div id="newDownloadFilesContainer">
                    </div>
                <button type="button" id="addDownloadFileBtn" class="add-file-btn">إضافة ملف تحميل جديد</button>
            </div>

            <button type="submit">حفظ التعديلات</button>
        </form>
    </div>

    <script src="../public/js/edit_lecture.js"></script>
</body>
</html>