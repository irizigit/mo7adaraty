<?php
session_start();
require_once '../../config/db_connexion.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

$professor_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$professor_id) {
    header("Location: index.php");
    exit();
}

$breadcrumbs = [
    "الهيكلة الأكاديمية" => "",
    "هيئة التدريس" => "index.php",
    "تعديل بيانات الأستاذ" => ""
];

try {
    $stmt = $pdo->prepare("SELECT * FROM professors WHERE professor_id = ?");
    $stmt->execute([$professor_id]);
    $prof_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$prof_data) {
        $_SESSION['error'] = "الأستاذ غير موجود.";
        header("Location: index.php");
        exit();
    }

    // جلب المواد التي يدرسها حالياً لتعليمها في القائمة المنسدلة
    $stmt_linked = $pdo->prepare("SELECT offering_subject_id FROM offering_subjects WHERE professor_id = ?");
    $stmt_linked->execute([$professor_id]);
    $linked_os = $stmt_linked->fetchAll(PDO::FETCH_COLUMN);

    // جلب جميع (المواد داخل المقررات) المتاحة
    $stmt_all_os = $pdo->query("
        SELECT os.offering_subject_id, d.division_name, c.class_name, g.group_name, s.subject_name
        FROM offering_subjects os
        JOIN course_offerings co ON os.offering_id = co.offering_id
        JOIN divisions d ON co.division_id = d.division_id
        JOIN classes c ON co.class_id = c.class_id
        LEFT JOIN `groups` g ON co.group_id = g.group_id
        JOIN subjects s ON os.subject_id = s.subject_id
        ORDER BY d.division_name, c.class_name, s.subject_name
    ");
    $all_offering_subjects = $stmt_all_os->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("خطأ: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل الأستاذ - Mo7adaraty</title>
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
                    <i class="fa-solid fa-pen-to-square text-[var(--warning-color)]"></i> تعديل الأستاذ وارتباطاته
                </h2>

                <form action="update_process.php" method="POST">
                    <input type="hidden" name="professor_id" value="<?php echo $prof_data['professor_id']; ?>">
                    
                    <div class="form-group">
                        <label class="form-label" for="professor_name">اسم الأستاذ: <span style="color:var(--danger-color)">*</span></label>
                        <input type="text" name="professor_name" id="professor_name" class="form-control" value="<?php echo htmlspecialchars($prof_data['professor_name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">الأيقونة (تلقائية للأساتذة):</label>
                        <div style="background: var(--bg-light); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 20px; text-align: center; margin-bottom: 15px;">
                            <img src="../dashboard_design/images/admin_icon_professors.png" alt="صورة الأستاذ" style="width: 100px; height: 100px; object-fit: contain; border-radius: 12px; border: 2px solid var(--border-color); background: var(--bg-surface);">
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 20px; padding-top: 20px; border-top: 1px dashed var(--border-color);">
                        <label class="form-label" for="offering_subject_ids">تعديل التعيين الأكاديمي (اختيار متعدد):</label>
                        <select name="offering_subject_ids[]" id="offering_subject_ids" class="form-control" multiple style="height: 180px; padding: 10px;">
                            <?php foreach ($all_offering_subjects as $os): ?>
                                <?php $is_selected = in_array($os['offering_subject_id'], $linked_os) ? 'selected' : ''; ?>
                                <option value="<?php echo $os['offering_subject_id']; ?>" <?php echo $is_selected; ?> style="padding: 6px; border-bottom: 1px solid var(--border-color);">
                                    <?php echo htmlspecialchars($os['division_name'] . ' - ' . $os['class_name'] . ($os['group_name'] ? ' (' . $os['group_name'] . ')' : '') . ' ⬅️ ' . $os['subject_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small style="color: var(--brand-primary); display: block; margin-top: 8px; font-weight: bold;">
                            <i class="fa-solid fa-circle-info"></i> استخدم مفتاح Ctrl لإلغاء التحديد أو إضافة مواد جديدة.
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