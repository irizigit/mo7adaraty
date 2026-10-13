<?php
session_start();
require_once '../../config/db_connexion.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

$track_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$track_id) {
    header("Location: index.php");
    exit();
}

$breadcrumbs = [
    "الهيكلة الأكاديمية" => "",
    "المسارات" => "index.php",
    "تعديل المسار" => ""
];

try {
    $stmt = $pdo->prepare("SELECT * FROM tracks WHERE track_id = ?");
    $stmt->execute([$track_id]);
    $track_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$track_data) {
        $_SESSION['error'] = "المسار غير موجود.";
        header("Location: index.php");
        exit();
    }

    // جلب الشعب للقائمة
    $divisions = $pdo->query("SELECT * FROM divisions ORDER BY division_name")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("خطأ: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل المسار - Mo7adaraty</title>
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
                    <i class="fa-solid fa-pen-to-square text-[var(--warning-color)]"></i> تعديل المسار
                </h2>

                <form action="update_process.php" method="POST">
                    <input type="hidden" name="track_id" value="<?php echo $track_data['track_id']; ?>">
                    
                    <div class="form-group">
                        <label class="form-label" for="track_name">اسم المسار: <span style="color:var(--danger-color)">*</span></label>
                        <input type="text" name="track_name" id="track_name" class="form-control" value="<?php echo htmlspecialchars($track_data['track_name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="division_id">تغيير الشعبة: <span style="color:var(--danger-color)">*</span></label>
                        <select name="division_id" id="division_id" class="form-control" required>
                            <?php foreach ($divisions as $div): ?>
                                <option value="<?php echo $div['division_id']; ?>" <?php echo ($track_data['division_id'] == $div['division_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($div['division_name']); ?>
                                </option>
                            <?php endforeach; ?>
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