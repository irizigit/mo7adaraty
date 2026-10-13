<?php
session_start();
require_once '../../config/db_connexion.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

$breadcrumbs = [
    "الهيكلة الأكاديمية" => "",
    "الشعب الأكاديمية" => ""
];

try {
    // جلب الأيقونات الذكية
    $stmt_design = $pdo->query("SELECT asset_key, file_path FROM design_assets WHERE page_section = 'global_icons'");
    $design_settings = [];
    while ($row = $stmt_design->fetch(PDO::FETCH_ASSOC)) {
        $design_settings[$row['asset_key']] = $row['file_path'];
    }

    function getDesignIcon($key, $default_path) {
        global $design_settings;
        return !empty($design_settings[$key]) ? '../../' . $design_settings[$key] : '../../' . $default_path;
    }

    // جلب الشعب مع عدد الفصول لكل شعبة كإحصائية ذكية
    $stmt = $pdo->query("
        SELECT d.*, 
        (SELECT COUNT(*) FROM classes WHERE division_id = d.division_id) as classes_count 
        FROM divisions d 
        ORDER BY d.division_name ASC
    ");
    $divisions = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("خطأ في قاعدة البيانات: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الشعب الأكاديمية - Mo7adaraty</title>
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
            
            <div class="academic-header-actions skeleton">
                <h2 style="margin: 0; color: var(--text-primary); display: flex; align-items: center; gap: 10px;">
                    <img src="../dashboard_design/images/admin_icon_divisions.png" alt="Divisions" style="width: 35px; height: 35px; object-fit: contain;">
                    الشعب الأكاديمية
                </h2>
                <button class="btn-add-new" onclick="document.getElementById('addModal').style.display='block'">
                    <i class="fa-solid fa-plus"></i> إضافة شعبة جديدة
                </button>
            </div>

            <?php if (isset($_SESSION['msg'])): ?>
                <div style="padding: 15px; background: rgba(40, 167, 69, 0.1); color: var(--success-color); border: 1px solid var(--success-color); border-radius: var(--radius-md); margin-bottom: 20px; font-weight: bold;">
                    <i class="fa-solid fa-circle-check"></i> <?php echo $_SESSION['msg']; unset($_SESSION['msg']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div style="padding: 15px; background: rgba(220, 53, 69, 0.1); color: var(--danger-color); border: 1px solid var(--danger-color); border-radius: var(--radius-md); margin-bottom: 20px; font-weight: bold;">
                    <i class="fa-solid fa-circle-exclamation"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <div class="academic-grid skeleton">
                <?php if (empty($divisions)): ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 40px; background: var(--bg-surface); border-radius: var(--radius-md); border: 1px dashed var(--border-color); color: var(--text-secondary);">
                        <i class="fa-solid fa-folder-open" style="font-size: 40px; margin-bottom: 15px; opacity: 0.5;"></i>
                        <p>لا توجد شعب أكاديمية مضافة حتى الآن.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($divisions as $div): ?>
                        <div class="academic-card">
                            <div class="card-img-wrapper">
                                <?php 
                                    $img_src = !empty($div['division_image']) ? '../../uploads/divisions/' . htmlspecialchars($div['division_image']) : '../dashboard_design/images/admin_icon_divisions.png';
                                ?>
                                <img src="<?php echo $img_src; ?>" alt="<?php echo htmlspecialchars($div['division_name']); ?>" onerror="this.src='../dashboard_design/images/admin_icon_divisions.png'">
                            </div>
                            <div class="card-content">
                                <h3 class="card-title"><?php echo htmlspecialchars($div['division_name']); ?></h3>
                                <div class="card-stats">
                                    <span class="stat-badge" title="عدد الفصول">
                                        <img src="../dashboard_design/images/admin_icon_classes.svg" alt="Classes" style="width: 16px; height: 16px;">
                                        <?php echo $div['classes_count']; ?> فصول
                                    </span>
                                </div>
                            </div>
                            <div class="card-actions">
                                <a href="edit.php?id=<?php echo $div['division_id']; ?>" class="btn-card-action btn-edit">
                                    <i class="fa-solid fa-pen"></i> تعديل
                                </a>
                                <form action="delete_process.php" method="POST" style="flex: 1; display: flex;" onsubmit="return confirm('تحذير: سيتم حذف الشعبة وجميع الفصول والمقررات المرتبطة بها! هل أنت متأكد؟');">
                                    <input type="hidden" name="division_id" value="<?php echo $div['division_id']; ?>">
                                    <button type="submit" class="btn-card-action btn-delete" style="width: 100%;">
                                        <i class="fa-solid fa-trash"></i> حذف
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div>
    </main>

    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>إضافة شعبة أكاديمية جديدة</h2>
                <button class="close-modal" onclick="document.getElementById('addModal').style.display='none'">&times;</button>
            </div>
            <div class="modal-body">
                <form action="add_process.php" method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label class="form-label" for="division_name">اسم الشعبة (مثال: الدراسات الإسلامية): <span style="color:var(--danger-color)">*</span></label>
                        <input type="text" name="division_name" id="division_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="division_image">صورة أو أيقونة الشعبة (اختياري):</label>
                        <input type="file" name="division_image" id="division_image" class="form-control" accept="image/*">
                        <small style="color: var(--text-secondary); display: block; margin-top: 5px;">يفضل استخدام صور شفافة (PNG/SVG) لجمالية العرض.</small>
                    </div>
                    <button type="submit" class="btn-add-new" style="width: 100%; justify-content: center;">
                        <i class="fa-solid fa-save"></i> حفظ الشعبة
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="../assets/js/admin_global.js"></script>
    <script>
        // إغلاق المودال عند النقر خارجه
        window.onclick = function(event) {
            var modal = document.getElementById('addModal');
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>