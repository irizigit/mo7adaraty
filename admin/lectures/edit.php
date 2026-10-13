<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية الصفحة
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

$lecture_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$lecture_id) {
    header("Location: index.php?error=" . urlencode("معرف غير صحيح"));
    exit();
}

$breadcrumbs = [
    "إدارة المحتوى" => "",
    "المحاضرات" => "index.php",
    "تعديل محاضرة" => ""
];

if (!function_exists('getDriveEmbedUrl')) {
    function getDriveEmbedUrl($url) {
        if (empty($url)) return '';
        if (preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $url, $matches) || preg_match('/[?&]id=([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return "https://drive.google.com/file/d/" . $matches[1] . "/preview";
        }
        return $url;
    }
}

try {
    $stmt = $pdo->prepare("
        SELECT l.*, os.offering_id, s.subject_id, s.subject_name, 
               c.class_name, g.group_name, t.track_name, d.division_name
        FROM lectures l
        JOIN offering_subjects os ON l.offering_subject_id = os.offering_subject_id
        JOIN subjects s ON os.subject_id = s.subject_id
        JOIN course_offerings co ON os.offering_id = co.offering_id
        JOIN classes c ON co.class_id = c.class_id
        LEFT JOIN `groups` g ON co.group_id = g.group_id
        LEFT JOIN tracks t ON co.track_id = t.track_id
        JOIN divisions d ON co.division_id = d.division_id
        WHERE l.lecture_id = ?
    ");
    $stmt->execute([$lecture_id]);
    $lecture = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$lecture) {
        header("Location: index.php?error=" . urlencode("المحاضرة غير موجودة"));
        exit();
    }
} catch (PDOException $e) {
    die("خطأ: " . $e->getMessage());
}

$is_drive = (strpos($lecture['file_path'], 'http://') === 0 || strpos($lecture['file_path'], 'https://') === 0);
$embed_url = $is_drive ? getDriveEmbedUrl($lecture['file_path']) : '../../' . $lecture['file_path'];

$current_cover = '../dashboard_design/images/admin_icon_lectures.png';
if (!empty($lecture['cover_image'])) {
    if (strpos($lecture['cover_image'], 'http://') === 0 || strpos($lecture['cover_image'], 'https://') === 0) {
        $current_cover = $lecture['cover_image'];
    } else {
        $current_cover = '../../' . ltrim($lecture['cover_image'], '/');
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل المحاضرة - Mo7adaraty</title>
    <link rel="icon" type="image/svg+xml" href="/mo7adaraty/assets/images/favicon.svg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/admin_global.css">
    <link rel="stylesheet" href="../assets/css/admin_academic.css">
</head>
<body class="admin-body">
    <script>
        (function() {
            const savedTheme = localStorage.getItem('mo7adaraty_theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>
    
    <?php include '../templates/sidebar.php'; ?>
    
    <main class="admin-main">
        <?php include '../templates/header.php'; ?>
        
        <div class="admin-content-wrapper">
            <div class="academic-form-container skeleton" style="max-width: 800px;">
                <h2 style="margin-top: 0; color: var(--text-primary); border-bottom: 1px solid var(--border-color); padding-bottom: 15px; margin-bottom: 25px;">
                    <i class="fa-solid fa-pen-to-square text-[var(--warning-color)]"></i> تعديل بيانات المحاضرة
                </h2>
                
                <div style="background: var(--bg-light); border: 1px dashed var(--border-color); padding: 15px; border-radius: var(--radius-sm); margin-bottom: 25px; color: var(--text-secondary); font-size: 14px; font-weight: bold; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                    <div>
                        <i class="fa-solid fa-sitemap"></i> المسار: 
                        <?= htmlspecialchars($lecture['division_name']) ?> / 
                        <?= htmlspecialchars($lecture['class_name']) ?> / 
                        <?= htmlspecialchars($lecture['subject_name']) ?>
                    </div>
                    <?php if ($is_drive): ?>
                        <a href="<?= htmlspecialchars($embed_url) ?>" target="_blank" rel="noopener noreferrer" style="background: #10b981; color: #fff; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 12px; display: inline-flex; align-items: center; gap: 6px;">
                            <i class="fa-brands fa-google-drive"></i> فتح الملف في Google Drive
                        </a>
                    <?php else: ?>
                        <a href="<?= htmlspecialchars($embed_url) ?>" target="_blank" style="background: var(--brand-primary); color: #fff; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 12px; display: inline-flex; align-items: center; gap: 6px;">
                            <i class="fa-solid fa-file"></i> عرض الملف المحلي
                        </a>
                    <?php endif; ?>
                </div>

                <form action="update_process.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="lecture_id" value="<?= $lecture['lecture_id'] ?>">
                    
                    <div class="form-group">
                        <label class="form-label" for="title">عنوان المحاضرة: <span style="color:var(--danger-color)">*</span></label>
                        <input type="text" name="title" id="title" class="form-control" value="<?= htmlspecialchars($lecture['title']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="description">الوصف (اختياري):</label>
                        <textarea name="description" id="description" class="form-control" style="min-height: 100px; resize: vertical;"><?= htmlspecialchars($lecture['description']) ?></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="status">حالة المحاضرة:</label>
                        <select name="status" id="status" class="form-control">
                            <option value="pending" <?= ($lecture['status'] === 'pending') ? 'selected' : '' ?>>معلقة (تحتاج مراجعة)</option>
                            <option value="approved" <?= ($lecture['status'] === 'approved') ? 'selected' : '' ?>>مقبولة (تظهر للطلاب)</option>
                            <option value="rejected" <?= ($lecture['status'] === 'rejected') ? 'selected' : '' ?>>مرفوضة (مخفية)</option>
                        </select>
                    </div>

                    <div class="form-group" style="margin-top: 20px; border-top: 1px dashed var(--border-color); padding-top: 20px;">
                        <label class="form-label" for="cover_image">تغيير صورة الغلاف (اختياري):</label>
                        <input type="file" name="cover_image" id="cover_image" class="form-control" accept="image/*">
                        
                        <div id="cover_preview" style="margin-top: 15px; text-align: center; background: var(--bg-light); padding: 15px; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                            <p style="margin: 0 0 10px 0; font-weight: bold; color: var(--text-secondary);">الغلاف الحالي:</p>
                            <img id="preview_img" src="<?= htmlspecialchars($current_cover) ?>" alt="Cover Preview" style="max-height: 150px; object-fit: contain; border-radius: 8px;">
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 10px; margin-top: 30px;">
                        <button type="submit" class="btn-add-new" style="flex: 1; justify-content: center; background: var(--warning-color); color: #000 !important;">
                            <i class="fa-solid fa-save"></i> حفظ التحديثات
                        </button>
                        <a href="index.php" class="btn-add-new" style="flex: 1; justify-content: center; background: var(--bg-surface); color: var(--text-primary) !important; border: 1px solid var(--border-color);">
                            إلغاء وعودة
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/admin_global.js"></script>
    <script>
        document.getElementById('cover_image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    document.getElementById('preview_img').src = event.target.result;
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>