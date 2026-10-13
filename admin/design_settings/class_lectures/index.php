<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../../config/db_connexion.php';

// حماية الصفحة والتحقق من الصلاحيات
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../../login.php");
    exit();
}

$breadcrumbs = [
    "الإعدادات" => "",
    "إعدادات التصميم المركزية" => "../index.php",
    "تصميم صفحة المحاضرات" => ""
];

try {
    // جلب جميع إعدادات التصميم الخاصة بصفحة المحاضرات
    $stmt = $pdo->prepare("SELECT * FROM design_assets WHERE page_section = 'class_lectures'");
    $stmt->execute();
    $assets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // تقسيم الأصول إلى مجموعات لتنظيم العرض
    $groups = [
        'الخلفيات الأساسية' => ['cl_bg_header'],
        'أيقونات التصفح والقوائم' => ['cl_icon_school', 'cl_icon_back', 'cl_icon_nav_lectures', 'cl_icon_nav_exams', 'cl_icon_nav_community', 'cl_icon_sidebar', 'cl_icon_subject'],
        'أيقونات الإجراءات والتفاعل' => ['cl_icon_upload', 'cl_icon_grid', 'cl_icon_list', 'cl_icon_view', 'cl_icon_download', 'cl_icon_report', 'cl_icon_like', 'cl_icon_dislike', 'cl_icon_comments', 'cl_icon_send'],
        'أيقونات أنواع الملفات' => ['cl_icon_empty', 'cl_icon_default', 'cl_icon_pdf', 'cl_icon_image']
    ];

    $assets_keyed = [];
    foreach ($assets as $asset) {
        $assets_keyed[$asset['asset_key']] = $asset;
    }

} catch (PDOException $e) {
    die("خطأ في جلب البيانات: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إعدادات تصميم صفحة المحاضرات - Mo7adaraty</title>
    <link rel="icon" type="image/svg+xml" href="../../../assets/images/favicon.svg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../assets/css/global.css">
    <link rel="stylesheet" href="../../assets/css/admin_global.css">
    <link rel="stylesheet" href="../../assets/css/admin_academic.css">
    <style>
        .design-section { background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 25px; margin-bottom: 30px; box-shadow: var(--shadow-sm); }
        .design-section h3 { margin-top: 0; margin-bottom: 20px; color: var(--brand-primary); border-bottom: 2px solid var(--border-color); padding-bottom: 10px; font-size: 20px; font-weight: 900;}
        .grid-container { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
        .asset-card { background: var(--bg-color); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 20px; text-align: center; transition: all 0.3s; display: flex; flex-direction: column; }
        .asset-card:hover { border-color: var(--brand-primary); box-shadow: var(--shadow-md); transform: translateY(-3px); }
        .asset-preview { width: 100%; height: 120px; background: var(--bg-light); border-radius: var(--radius-sm); margin-bottom: 15px; display: flex; align-items: center; justify-content: center; overflow: hidden; position: relative; border: 1px solid var(--border-color); }
        .asset-preview.transparent-bg { 
            background-image: linear-gradient(45deg, rgba(128,128,128,0.1) 25%, transparent 25%), 
                              linear-gradient(-45deg, rgba(128,128,128,0.1) 25%, transparent 25%); 
            background-size: 20px 20px; 
        }
        .asset-preview img { max-width: 90%; max-height: 90%; object-fit: contain; }
        .asset-preview img.bg-preview { width: 100%; height: 100%; object-fit: cover; }
        .asset-info h4 { margin: 0 0 8px 0; font-size: 15px; color: var(--text-primary); font-weight: 800; }
        .asset-info p { margin: 0 0 15px 0; font-size: 12px; color: var(--text-secondary); background: var(--bg-light); display: inline-block; padding: 2px 8px; border-radius: 4px; border: 1px solid var(--border-color); }
        .file-input-wrapper { position: relative; overflow: hidden; display: inline-block; width: 100%; margin-top: auto; }
        .file-input-wrapper input[type=file] { position: absolute; left: 0; top: 0; opacity: 0; cursor: pointer; height: 100%; width: 100%; }
        .btn-upload-custom { background: rgba(26, 115, 232, 0.05); color: var(--brand-primary); border: 1px dashed var(--brand-primary); padding: 10px 15px; border-radius: var(--radius-sm); font-weight: 800; width: 100%; display: block; font-size: 13px; }
        .submit-container { text-align: left; margin-top: 30px; position: sticky; bottom: 20px; z-index: 100; background: var(--bg-surface); padding: 15px 25px; border-radius: var(--radius-md); box-shadow: 0 -4px 20px rgba(0,0,0,0.1); border: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; }
        .btn-save { background: var(--success-color); color: white; border: none; padding: 12px 30px; border-radius: var(--radius-sm); font-size: 16px; font-weight: 900; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
    </style>
</head>
<body class="admin-body">
    <?php include '../../templates/sidebar.php'; ?>
    <main class="admin-main">
        <?php include '../../templates/header.php'; ?>
        <div class="admin-content-wrapper">
            <div class="academic-header-actions" style="border-bottom: 1px dashed var(--border-color); padding-bottom: 20px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div style="width: 50px; height: 50px; background: rgba(26, 115, 232, 0.1); color: var(--brand-primary); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px;">
                        <i class="fa-solid fa-chalkboard-user"></i>
                    </div>
                    <div>
                        <h2 style="margin: 0; color: var(--text-primary); font-weight: 900;">تصميم صفحة المحاضرات</h2>
                    </div>
                </div>
            </div>

            <?php if (isset($_SESSION['msg'])): ?>
                <div style="padding: 15px; background: rgba(40, 167, 69, 0.1); color: var(--success-color); border: 1px solid var(--success-color); border-radius: var(--radius-md); margin-bottom: 20px; font-weight: bold;">
                    <i class="fa-solid fa-circle-check"></i> <?php echo $_SESSION['msg']; unset($_SESSION['msg']); ?>
                </div>
            <?php endif; ?>

            <form action="process_upload.php" method="POST" enctype="multipart/form-data">
                <?php foreach ($groups as $group_name => $keys): ?>
                    <div class="design-section">
                        <h3><?php echo $group_name; ?></h3>
                        <div class="grid-container">
                            <?php foreach ($keys as $key): ?>
                                <?php 
                                    $asset = $assets_keyed[$key] ?? null; 
                                    if (!$asset) continue;
                                    $is_bg = ($asset['asset_type'] === 'image');
                                ?>
                                <div class="asset-card">
                                    <div class="asset-preview <?php echo $is_bg ? '' : 'transparent-bg'; ?>">
                                        <img src="../../../<?php echo htmlspecialchars($asset['file_path']); ?>?v=<?php echo time(); ?>" id="preview_<?php echo $key; ?>" onerror="this.src='../../../assets/images/placeholder.png'">
                                    </div>
                                    <div class="asset-info">
                                        <h4><?php echo htmlspecialchars($asset['description']); ?></h4>
                                        <p><code><?php echo $key; ?></code></p>
                                        <div class="file-input-wrapper">
                                            <span class="btn-upload-custom"><i class="fa-solid fa-upload"></i> اختر ملف جديد</span>
                                            <input type="file" name="<?php echo $key; ?>" onchange="previewImage(this, 'preview_<?php echo $key; ?>')">
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="submit-container">
                    <button type="submit" class="btn-save">
                        <i class="fa-solid fa-floppy-disk"></i> حفظ التغييرات الآن
                    </button>
                </div>
            </form>
        </div>
    </main>

    <script>
        function previewImage(input, previewId) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById(previewId).src = e.target.result;
                    input.previousElementSibling.style.color = 'var(--success-color)';
                    input.previousElementSibling.innerHTML = '<i class="fa-solid fa-check"></i> جاهز للرفع';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>