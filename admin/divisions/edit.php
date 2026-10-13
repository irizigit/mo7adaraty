<?php
session_start();
require_once '../../config/db_connexion.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

$division_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$division_id) {
    header("Location: index.php");
    exit();
}

$breadcrumbs = [
    "الهيكلة الأكاديمية" => "",
    "الشعب الأكاديمية" => "index.php",
    "تعديل شعبة" => ""
];

try {
    $stmt = $pdo->prepare("SELECT * FROM divisions WHERE division_id = ?");
    $stmt->execute([$division_id]);
    $division = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$division) {
        $_SESSION['error'] = "الشعبة غير موجودة.";
        header("Location: index.php");
        exit();
    }
} catch (PDOException $e) {
    die("خطأ: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل الشعبة - Mo7adaraty</title>
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
                    <i class="fa-solid fa-pen-to-square text-[var(--warning-color)]"></i> تعديل الشعبة
                </h2>

                <form action="update_process.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="division_id" value="<?php echo $division['division_id']; ?>">
                    
                    <div class="form-group">
                        <label class="form-label" for="division_name">اسم الشعبة: <span style="color:var(--danger-color)">*</span></label>
                        <input type="text" name="division_name" id="division_name" class="form-control" value="<?php echo htmlspecialchars($division['division_name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">الصورة الحالية:</label>
                        <div style="background: var(--bg-light); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 20px; text-align: center; margin-bottom: 15px;">
                            <?php 
                                $img_src = !empty($division['division_image']) ? '../../uploads/divisions/' . htmlspecialchars($division['division_image']) : '../dashboard_design/images/admin_icon_divisions.png';
                            ?>
                            <img src="<?php echo $img_src; ?>" alt="صورة الشعبة" style="max-width: 100%; height: 120px; object-fit: contain;">
                        </div>
                        
                        <label class="form-label" for="division_image">تغيير الصورة (اختياري):</label>
                        <input type="file" name="division_image" id="division_image" class="form-control" accept="image/*">
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