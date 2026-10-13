<?php
session_start();
require_once '../../config/db_connexion.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

$exam_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$exam_id) {
    header("Location: index.php");
    exit();
}

$breadcrumbs = ["إدارة المحتوى" => "", "الامتحانات" => "index.php", "تعديل امتحان" => ""];

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
        SELECT e.*, d.division_name, c.class_name, s.subject_name
        FROM exams e
        LEFT JOIN divisions d ON e.division_id = d.division_id
        LEFT JOIN classes c ON e.class_id = c.class_id
        LEFT JOIN offering_subjects os ON e.offering_subject_id = os.offering_subject_id
        LEFT JOIN subjects s ON os.subject_id = s.subject_id
        WHERE e.exam_id = ?
    ");
    $stmt->execute([$exam_id]);
    $exam = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$exam) {
        header("Location: index.php?error=الامتحان غير موجود.");
        exit();
    }
} catch (PDOException $e) {
    die("خطأ: " . $e->getMessage());
}

$is_drive = (strpos($exam['file_path'], 'http://') === 0 || strpos($exam['file_path'], 'https://') === 0);
$embed_url = $is_drive ? getDriveEmbedUrl($exam['file_path']) : '../../' . $exam['file_path'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل الامتحان - Mo7adaraty</title>
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
            <div class="academic-form-container skeleton" style="max-width: 700px;">
                <h2 style="margin-top: 0; color: var(--text-primary); border-bottom: 1px solid var(--border-color); padding-bottom: 15px; margin-bottom: 25px;">
                    <i class="fa-solid fa-pen-to-square text-[var(--warning-color)]"></i> تعديل بيانات الامتحان
                </h2>

                <div style="background: var(--bg-light); border: 1px dashed var(--border-color); padding: 15px; border-radius: var(--radius-sm); margin-bottom: 25px; color: var(--text-secondary); font-size: 14px; font-weight: bold; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                    <div>
                        <i class="fa-solid fa-sitemap"></i> المسار: 
                        <?= htmlspecialchars($exam['division_name'] ?? 'شعبة عامة') ?> / 
                        <?= htmlspecialchars($exam['class_name'] ?? 'فصل عام') ?> / 
                        <?= htmlspecialchars($exam['subject_name'] ?? 'مادة عامة') ?>
                    </div>
                    <?php if ($is_drive): ?>
                        <a href="<?= htmlspecialchars($embed_url) ?>" target="_blank" rel="noopener noreferrer" style="background: #10b981; color: #fff; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 12px; display: inline-flex; align-items: center; gap: 6px;">
                            <i class="fa-brands fa-google-drive"></i> فتح في Google Drive
                        </a>
                    <?php endif; ?>
                </div>

                <form action="update_process.php" method="POST">
                    <input type="hidden" name="exam_id" value="<?php echo $exam['exam_id']; ?>">
                    
                    <div class="form-group">
                        <label class="form-label" for="title">عنوان الامتحان: <span style="color:var(--danger-color)">*</span></label>
                        <input type="text" name="title" id="title" class="form-control" value="<?php echo htmlspecialchars($exam['title']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="description">ملاحظات إضافية:</label>
                        <textarea name="description" id="description" class="form-control" style="min-height: 80px; resize: vertical;"><?php echo htmlspecialchars($exam['description']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="status">حالة الامتحان (ظاهر/مخفي):</label>
                        <select name="status" id="status" class="form-control">
                            <option value="approved" <?php echo ($exam['status'] === 'approved') ? 'selected' : ''; ?>>مقبول (يظهر للطلاب)</option>
                            <option value="pending" <?php echo ($exam['status'] === 'pending') ? 'selected' : ''; ?>>معلق (مخفي للمراجعة)</option>
                            <option value="rejected" <?php echo ($exam['status'] === 'rejected') ? 'selected' : ''; ?>>مرفوض (مخفي)</option>
                        </select>
                    </div>

                    <div style="background: var(--bg-light); border: 1px solid var(--border-color); padding: 15px; border-radius: var(--radius-sm); margin-top: 20px;">
                        <h4 style="margin: 0 0 10px 0; color: var(--brand-primary);"><i class="fa-solid fa-file"></i> معاينة الملف المرفق</h4>
                        <a href="<?= htmlspecialchars($embed_url) ?>" target="_blank" rel="noopener noreferrer" style="color: var(--info-color); font-weight: bold; text-decoration: none;"><i class="fa-solid fa-external-link-alt"></i> استعراض ملف الامتحان الكامل</a>
                        <p style="margin: 5px 0 0 0; font-size: 12px; color: var(--text-secondary);">* لتغيير الملف الأساسي أو النطاق الأكاديمي، يرجى حذف هذا الامتحان ورفع واحد جديد.</p>
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
    <script src="../assets/js/admin_global.js"></script>
</body>
</html>