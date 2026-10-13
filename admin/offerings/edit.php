<?php
session_start();
require_once '../../config/db_connexion.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

$offering_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$offering_id) {
    header("Location: index.php");
    exit();
}

$breadcrumbs = [
    "الهيكلة الأكاديمية" => "",
    "إدارة المقررات" => "index.php",
    "تعديل ارتباطات المقرر" => ""
];

try {
    $stmt = $pdo->prepare("SELECT * FROM course_offerings WHERE offering_id = ?");
    $stmt->execute([$offering_id]);
    $offering = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$offering) {
        $_SESSION['error'] = "المقرر غير موجود.";
        header("Location: index.php");
        exit();
    }

    $years = $pdo->query("SELECT * FROM academic_years ORDER BY year_label DESC")->fetchAll(PDO::FETCH_ASSOC);
    $divisions = $pdo->query("SELECT * FROM divisions ORDER BY division_name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $classes = $pdo->query("SELECT * FROM classes ORDER BY class_name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $tracks = $pdo->query("SELECT * FROM tracks ORDER BY track_name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $groups = $pdo->query("SELECT * FROM `groups` ORDER BY group_name ASC")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("خطأ: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل المقرر - Mo7adaraty</title>
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
                    <i class="fa-solid fa-pen-to-square text-[var(--warning-color)]"></i> تعديل ارتباطات المقرر
                </h2>

                <form action="update_process.php" method="POST">
                    <input type="hidden" name="offering_id" value="<?php echo $offering['offering_id']; ?>">
                    
                    <div class="form-group">
                        <label class="form-label" for="academic_year_id">العام الدراسي: <span style="color:var(--danger-color)">*</span></label>
                        <select name="academic_year_id" id="academic_year_id" class="form-control" required>
                            <?php foreach ($years as $y): ?>
                                <option value="<?php echo $y['id']; ?>" <?php echo ($offering['academic_year_id'] == $y['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($y['year_label']) . ($y['is_current'] ? ' (الحالي)' : ''); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label class="form-label" for="division_id">الشعبة: <span style="color:var(--danger-color)">*</span></label>
                            <select name="division_id" id="division_id" class="form-control" required>
                                <?php foreach ($divisions as $d): ?>
                                    <option value="<?php echo $d['division_id']; ?>" <?php echo ($offering['division_id'] == $d['division_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($d['division_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="class_id">الفصل: <span style="color:var(--danger-color)">*</span></label>
                            <select name="class_id" id="class_id" class="form-control" required>
                                <?php foreach ($classes as $c): ?>
                                    <option value="<?php echo $c['class_id']; ?>" <?php echo ($offering['class_id'] == $c['class_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($c['class_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label class="form-label" for="track_id">المسار:</label>
                            <select name="track_id" id="track_id" class="form-control">
                                <option value="">-- عام (بدون مسار) --</option>
                                <?php foreach ($tracks as $t): ?>
                                    <option value="<?php echo $t['track_id']; ?>" <?php echo ($offering['track_id'] == $t['track_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($t['track_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="group_id">الفوج:</label>
                            <select name="group_id" id="group_id" class="form-control">
                                <option value="">-- عام (بدون فوج) --</option>
                                <?php foreach ($groups as $g): ?>
                                    <option value="<?php echo $g['group_id']; ?>" <?php echo ($offering['group_id'] == $g['group_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($g['group_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 10px;">
                        <label class="form-label" for="status">حالة المقرر:</label>
                        <select name="status" id="status" class="form-control">
                            <option value="active" <?php echo ($offering['status'] === 'active') ? 'selected' : ''; ?>>نشط (يظهر للطلاب)</option>
                            <option value="inactive" <?php echo ($offering['status'] === 'inactive') ? 'selected' : ''; ?>>غير نشط (مخفي)</option>
                        </select>
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