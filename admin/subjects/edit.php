<?php
session_start();
require_once '../../config/db_connexion.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

$subject_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$subject_id) {
    header("Location: index.php");
    exit();
}

$breadcrumbs = [
    "الهيكلة الأكاديمية" => "",
    "المواد الدراسية" => "index.php",
    "تعديل المادة" => ""
];

try {
    $stmt = $pdo->prepare("SELECT * FROM subjects WHERE subject_id = ?");
    $stmt->execute([$subject_id]);
    $subject_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$subject_data) {
        $_SESSION['error'] = "المادة غير موجودة.";
        header("Location: index.php");
        exit();
    }

    $stmt_linked = $pdo->prepare("SELECT offering_id FROM offering_subjects WHERE subject_id = ?");
    $stmt_linked->execute([$subject_id]);
    $linked_offerings = $stmt_linked->fetchAll(PDO::FETCH_COLUMN);

    $stmt_off = $pdo->query("
        SELECT co.offering_id, d.division_name, c.class_name, g.group_name, t.track_name
        FROM course_offerings co
        JOIN divisions d ON co.division_id = d.division_id
        JOIN classes c ON co.class_id = c.class_id
        LEFT JOIN `groups` g ON co.group_id = g.group_id
        LEFT JOIN tracks t ON co.track_id = t.track_id
        ORDER BY d.division_name, c.class_name
    ");
    $all_offerings = $stmt_off->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("خطأ: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل المادة - Mo7adaraty</title>
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
            
            <div class="academic-form-container skeleton">
                <h2 style="margin-top: 0; color: var(--text-primary); border-bottom: 1px solid var(--border-color); padding-bottom: 15px; margin-bottom: 25px;">
                    <i class="fa-solid fa-pen-to-square text-[var(--warning-color)]"></i> تعديل المادة وارتباطاتها
                </h2>

                <form action="update_process.php" method="POST">
                    <input type="hidden" name="subject_id" value="<?php echo $subject_data['subject_id']; ?>">
                    
                    <div class="form-group">
                        <label class="form-label" for="subject_name">اسم المادة: <span style="color:var(--danger-color)">*</span></label>
                        <input type="text" name="subject_name" id="subject_name" class="form-control" value="<?php echo htmlspecialchars($subject_data['subject_name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">الأيقونة الموحدة:</label>
                        <div style="background: var(--bg-light); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 20px; text-align: center; margin-bottom: 15px;">
                            <img src="../dashboard_design/images/admin_icon_subjects.png" alt="أيقونة المادة" style="max-width: 100%; height: 80px; object-fit: contain;">
                            <p style="font-size: 13px; color: var(--text-secondary); margin-top: 10px;">الأيقونة تستدعى تلقائياً من إعدادات التصميم</p>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 20px; padding-top: 20px; border-top: 1px dashed var(--border-color);">
                        <label class="form-label" for="offering_ids">تعديل التوزيع الأكاديمي (اختيار متعدد):</label>
                        <select name="offering_ids[]" id="offering_ids" class="form-control" multiple style="height: 200px; padding: 10px;">
                            <?php foreach ($all_offerings as $off): ?>
                                <?php $is_selected = in_array($off['offering_id'], $linked_offerings) ? 'selected' : ''; ?>
                                <option value="<?php echo $off['offering_id']; ?>" <?php echo $is_selected; ?> style="padding: 6px; border-bottom: 1px solid var(--border-color);">
                                    <?php echo htmlspecialchars($off['division_name'] . ' - ' . $off['class_name'] . ($off['track_name'] ? ' (' . $off['track_name'] . ')' : '') . ($off['group_name'] ? ' (' . $off['group_name'] . ')' : '')); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small style="color: var(--brand-primary); display: block; margin-top: 8px; font-weight: bold;">
                            <i class="fa-solid fa-circle-info"></i> استخدم مفتاح Ctrl (أو Cmd) لتحديد المقررات.
                        </small>
                    </div>

                    <div style="display: flex; gap: 10px; margin-top: 30px;">
                        <button type="submit" class="btn-add-new" style="flex: 1; justify-content: center; background: var(--warning-color); color: #000 !important;">
                            <i class="fa-solid fa-save"></i> حفظ التعديلات
                        </button>
                        <a href="index.php" class="btn-add-new" style="flex: 1; justify-content: center; background: var(--bg-surface); color: var(--text-primary) !important; border: 1px solid var(--border-color);">
                            إلغاء
                        </a>
                    </div>
                </form>
            </div>

        </div>
    </main>

    <script src="../assets/js/admin_global.js"></script>
</body>
</html>