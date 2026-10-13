<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../../config/db_connexion.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../../login.php");
    exit();
}

$breadcrumbs = [
    "الإعدادات" => "",
    "إعدادات التصميم المركزية" => "../index.php",
    "تصميم صفحة الدخول" => ""
];

try {

    $db = isset($pdo) ? $pdo : (isset($conn) ? $conn : null);

    // 2. فحص الحماية: إذا كان $db لا يزال null، توقف واشرح السبب بدل الانهيار
    if ($db === null) {
        throw new Exception("خطأ: لم يتم العثور على اتصال قاعدة البيانات (PDO أو Conn). تأكد من تعريف المتغير.");
    }

    $stmt = $db->prepare("SELECT * FROM design_assets WHERE page_section = 'login'");
    $stmt->execute();
    $assets = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    echo "فشل العمل: " . $e->getMessage();

    // تقسيم المجموعات لتسهيل الإدارة
    $groups = [
        'الشعار والخلفيات' => ['login_logo', 'login_bg_body', 'login_bg_card'],
        'أيقونات الحقول' => ['login_icon_user', 'login_icon_password', 'login_icon_eye'],
        'أيقونة الزر' => ['login_icon_btn']
    ];
    
    $assets_keyed = [];
    foreach ($assets as $asset) {
        $assets_keyed[$asset['asset_key']] = $asset;
    }
} catch (PDOException $e) { die("خطأ: " . $e->getMessage()); }
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تصميم صفحة الدخول - Mo7adaraty</title>
    <link rel="icon" type="image/svg+xml" href="../../../assets/images/favicon.svg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../assets/css/global.css">
    <link rel="stylesheet" href="../../assets/css/admin_global.css">
    <style>
        .design-section { background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 25px; margin-bottom: 30px; box-shadow: var(--shadow-sm); }
        .design-section h3 { margin-top: 0; margin-bottom: 20px; color: #f59e0b; border-bottom: 2px solid var(--border-color); padding-bottom: 10px; font-size: 20px; font-weight: 900;}
        .grid-container { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; }
        .asset-card { background: var(--bg-color); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 20px; text-align: center; transition: all 0.3s; display: flex; flex-direction: column; }
        .asset-card:hover { border-color: #f59e0b; box-shadow: var(--shadow-md); transform: translateY(-3px); }
        .asset-preview { width: 100%; height: 120px; background: var(--bg-light); border-radius: var(--radius-sm); margin-bottom: 15px; display: flex; align-items: center; justify-content: center; overflow: hidden; position: relative; border: 1px solid var(--border-color); }
        .asset-preview img { max-width: 90%; max-height: 90%; object-fit: contain; } 
        .no-image-fallback { display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100%; height: 100%; color: var(--text-secondary); background: var(--bg-light); font-weight: bold; font-size: 14px; }
        .no-image-fallback i { font-size: 28px; margin-bottom: 8px; color: #cbd5e1; }
        .asset-info h4 { margin: 0 0 8px 0; font-size: 15px; color: var(--text-primary); font-weight: 800; }
        .asset-info p { margin: 0 0 15px 0; font-size: 12px; color: var(--text-secondary); font-weight: bold; background: var(--bg-light); display: inline-block; padding: 2px 8px; border-radius: 4px; border: 1px solid var(--border-color); }
        .file-input-wrapper { position: relative; overflow: hidden; display: inline-block; width: 100%; margin-top: auto; }
        .file-input-wrapper input[type=file] { font-size: 100px; position: absolute; left: 0; top: 0; opacity: 0; cursor: pointer; height: 100%; }
        .btn-upload-custom { background: rgba(245, 158, 11, 0.05); color: #f59e0b; border: 1px dashed #f59e0b; padding: 10px 15px; border-radius: var(--radius-sm); font-weight: 800; width: 100%; display: block; cursor: pointer; transition: all 0.3s; font-size: 13px; }
        .file-input-wrapper:hover .btn-upload-custom { background: #f59e0b; color: white; }
        .submit-container { text-align: left; margin-top: 30px; position: sticky; bottom: 20px; z-index: 100; background: var(--bg-surface); padding: 15px 25px; border-radius: var(--radius-md); box-shadow: 0 -4px 20px rgba(0,0,0,0.1); border: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; }
        .submit-info { color: var(--text-secondary); font-weight: bold; font-size: 13px; }
        .btn-save { background: var(--success-color); color: white; border: none; padding: 12px 30px; border-radius: var(--radius-sm); font-size: 16px; font-weight: 900; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: 0.3s; }
        .btn-save:hover { background: #218838; transform: translateY(-2px); box-shadow: var(--shadow-sm); }
    </style>
</head>
<body class="admin-body">

    <script>
        (function() {
            const savedTheme = localStorage.getItem('mo7adaraty_theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>

    <?php include '../../templates/sidebar.php'; ?>

    <main class="admin-main">
        <?php include '../../templates/header.php'; ?>

        <div class="admin-content-wrapper">
            <div class="academic-header-actions skeleton" style="border-bottom: 1px dashed var(--border-color); padding-bottom: 20px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div style="width: 50px; height: 50px; background: rgba(245, 158, 11, 0.1); color: #f59e0b; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px;">
                        <i class="fa-solid fa-right-to-bracket"></i>
                    </div>
                    <div>
                        <h2 style="margin: 0; color: var(--text-primary); font-weight: 900;">تصميم صفحة الدخول (Login)</h2>
                        <p style="margin: 5px 0 0 0; color: var(--text-secondary); font-size: 14px; font-weight: bold;">تخصيص الشعار، الخلفيات، وأيقونات الحقول في واجهة تسجيل الدخول.</p>
                    </div>
                </div>
                <a href="../index.php" class="btn-add-new" style="background: var(--bg-surface); color: var(--text-primary); border: 1px solid var(--border-color);">
                    العودة للمركز <i class="fa-solid fa-arrow-left"></i>
                </a>
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

            <form action="process_upload.php" method="POST" enctype="multipart/form-data" class="skeleton">
                <?php foreach ($groups as $group_name => $keys): ?>
                    <div class="design-section">
                        <h3><?php echo $group_name; ?></h3>
                        <div class="grid-container">
                            <?php foreach ($keys as $key): ?>
                                <?php 
                                    $asset = $assets_keyed[$key] ?? null; 
                                    $has_img = !empty($asset['file_path']);
                                    $is_bg = strpos($key, 'bg') !== false;
                                ?>
                                <div class="asset-card">
                                    <div class="asset-preview">
                                        <img src="../../../<?php echo htmlspecialchars($asset['file_path'] ?? ''); ?>?v=<?php echo time(); ?>" 
                                             id="preview_<?php echo $key; ?>" 
                                             style="display: <?php echo $has_img ? 'block' : 'none'; ?>; <?php echo $is_bg ? 'width: 100%; height: 100%; object-fit: cover;' : ''; ?>"
                                             onerror="this.onerror=null; this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                        
                                        <div class="no-image-fallback" style="display: <?php echo $has_img ? 'none' : 'flex'; ?>;">
                                            <i class="fa-solid fa-image"></i>
                                            <span>لا توجد صورة</span>
                                        </div>
                                    </div>
                                    <div class="asset-info">
                                        <h4><?php echo htmlspecialchars($asset['description'] ?? 'بدون وصف'); ?></h4>
                                        <p><code><?php echo $key; ?></code></p>
                                        <div class="file-input-wrapper">
                                            <span class="btn-upload-custom"><i class="fa-solid fa-upload"></i> اختر ملف جديد</span>
                                            <input type="file" name="<?php echo $key; ?>" id="<?php echo $key; ?>" accept=".png, .svg, .jpg, .jpeg, .webp" onchange="previewImage(this, 'preview_<?php echo $key; ?>', <?php echo $is_bg ? 'true' : 'false'; ?>)">
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="submit-container">
                    <div class="submit-info">
                        <i class="fa-solid fa-circle-info" style="color:#f59e0b;"></i> سيتم حفظ الملفات في المجلد <code>includesimg/login/</code>
                    </div>
                    <button type="submit" class="btn-save"><i class="fa-solid fa-floppy-disk"></i> حفظ التغييرات الآن</button>
                </div>
            </form>
        </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../../assets/js/admin_global.js"></script>
    <script>
        function previewImage(input, previewId, isBg) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    var img = document.getElementById(previewId);
                    img.src = e.target.result;
                    img.style.display = 'block';
                    if(isBg) {
                        img.style.width = '100%';
                        img.style.height = '100%';
                        img.style.objectFit = 'cover';
                    }
                    
                    var fallback = img.nextElementSibling;
                    if (fallback && fallback.classList.contains('no-image-fallback')) {
                        fallback.style.display = 'none';
                    }
                    
                    input.previousElementSibling.style.backgroundColor = 'rgba(40, 167, 69, 0.1)';
                    input.previousElementSibling.style.borderColor = 'var(--success-color)';
                    input.previousElementSibling.style.color = 'var(--success-color)';
                    input.previousElementSibling.innerHTML = '<i class="fa-solid fa-check-double"></i> تم الاختيار للرفع';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>