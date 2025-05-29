<?php
// تفعيل عرض الأخطاء للمساعدة في التصحيح
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../server/session_check.php';

// التأكد من أن المستخدم مسؤول قبل الوصول إلى هذه الصفحة
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Location: ../public/login.php'); // توجيه لصفحة تسجيل الدخول إذا لم يكن مسؤولاً
    exit;
}

// جلب بيانات التصنيفات، الأساتذة، الشعب، الفصول، والأفواج لعرضها في القوائم المنسدلة
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
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إضافة محاضرة جديدة</title>
    <link rel="stylesheet" href="../public/css/style.css">
    <style>
        .form-upload {
            margin-top: 20px;
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .form-upload label {
            font-weight: bold;
            margin-bottom: 5px;
            display: block;
        }
        .form-upload input[type="text"],
        .form-upload input[type="file"],
        .form-upload textarea,
        .form-upload select {
            width: calc(100% - 22px); /* Adjust for padding and border */
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .form-upload textarea {
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
        .file-upload-section label {
            color: #444;
        }
        .add-file-btn, .remove-file-btn {
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
        .add-file-btn:hover, .remove-file-btn:hover {
            background-color: #5a6268;
        }
        .remove-file-btn {
            background-color: #dc3545;
        }
        .remove-file-btn:hover {
            background-color: #c82333;
        }
        .form-upload button[type="submit"] {
            margin-top: 20px;
            background-color: #007bff;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.1em;
        }
        .form-upload button[type="submit"]:hover {
            background-color: #0056b3;
        }
        #message {
            margin-top: 15px;
            padding: 10px;
            border-radius: 5px;
            font-weight: bold;
            display: none;
        }
        #message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        #message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>إضافة محاضرة جديدة</h2>
        <p><a href="dashboard.php">العودة للوحة التحكم</a></p>

        <div id="message" class="message"></div>

        <form id="addLectureForm" class="form-upload" enctype="multipart/form-data">
            <div class="form-group">
                <label for="title">عنوان المحاضرة:</label>
                <input type="text" id="title" name="title" required>
            </div>

            <div class="form-group">
                <label for="lecturer_id">الأستاذ المحاضر:</label>
                <select id="lecturer_id" name="lecturer_id" required>
                    <option value="">اختر أستاذاً</option>
                    <?php foreach ($lecturers_data as $lecturer): ?>
                        <option value="<?php echo htmlspecialchars($lecturer['id']); ?>">
                            <?php echo htmlspecialchars($lecturer['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="category_id">التصنيف:</label>
                <select id="category_id" name="category_id" required>
                    <option value="">اختر تصنيفاً</option>
                    <?php foreach ($categories_data as $category): ?>
                        <option value="<?php echo htmlspecialchars($category['id']); ?>">
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="branch_id">الشعبة:</label>
                <select id="branch_id" name="branch_id" required>
                    <option value="">اختر شعبة</option>
                    <?php foreach ($branches_data as $branch): ?>
                        <option value="<?php echo htmlspecialchars($branch['id']); ?>">
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
                        <option value="<?php echo htmlspecialchars($semester['id']); ?>">
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
                        <option value="<?php echo htmlspecialchars($group['id']); ?>">
                            <?php echo htmlspecialchars($group['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="description">وصف المحاضرة:</label>
                <textarea id="description" name="description" rows="5" required></textarea>
            </div>

            <div class="form-group file-upload-section">
                <label>ملف المحاضرة (فيديو أو صوت):</label>
                <input type="file" id="lecture_file" name="lecture_file" accept="video/*,audio/*">
                <small>اختر ملف فيديو (mp4, webm) أو ملف صوت (mp3, wav).</small>
            </div>

            <div class="form-group file-upload-section">
                <label>ملفات إضافية قابلة للتحميل (PDF, PPTX, ZIP...):</label>
                <div id="downloadFilesContainer">
                    </div>
                <button type="button" id="addDownloadFileBtn" class="add-file-btn">إضافة ملف تحميل آخر</button>
            </div>

            <button type="submit">إضافة المحاضرة</button>
        </form>
    </div>

    <script src="../public/js/add_lecture.js"></script>
</body>
</html>