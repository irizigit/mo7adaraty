<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية الصفحة
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

$breadcrumbs = [
    "إدارة المحتوى" => "",
    "المحاضرات المرفوعة" => ""
];

if (!function_exists('getDriveEmbedUrl')) {
    function getDriveEmbedUrl($url) {
        if (empty($url)) return '';
        if (preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $url, $matches) || preg_match('/[?&]id=([a-zA-Z0-9_-]+)/',$matches)) {
            return "https://drive.google.com/file/d/" . $matches[1] . "/preview";
        }
        return $url;
    }
}

if (!function_exists('getDriveDownloadUrl')) {
    function getDriveDownloadUrl($url) {
        if (empty($url)) return '';
        if (preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $url, $matches) || preg_match('/[?&]id=([a-zA-Z0-9_-]+)/',$matches)) {
            return "https://drive.google.com/uc?export=download&id=" . $matches[1];
        }
        return $url;
    }
}

try {
    $stmt =$pdo->query("
        SELECT 
            l.lecture_id, l.title, l.description, l.cover_image, l.file_path, l.file_type, l.upload_date, l.status,
            s.subject_name,
            p.professor_name,
            u.username as uploader_name,
            d.division_name, d.division_image,
            c.class_name, c.class_image,
            g.group_name,
            t.track_name,
            ay.year_label, ay.is_current
        FROM lectures l
        LEFT JOIN academic_years ay ON l.academic_year_id = ay.id
        LEFT JOIN users u ON l.uploader_user_id = u.user_id
        LEFT JOIN offering_subjects os ON l.offering_subject_id = os.offering_subject_id
        LEFT JOIN subjects s ON os.subject_id = s.subject_id
        LEFT JOIN professors p ON os.professor_id = p.professor_id
        LEFT JOIN course_offerings co ON l.offering_id = co.offering_id
        LEFT JOIN divisions d ON co.division_id = d.division_id
        LEFT JOIN classes c ON co.class_id = c.class_id
        LEFT JOIN `groups` g ON co.group_id = g.group_id
        LEFT JOIN tracks t ON co.track_id = t.track_id
        ORDER BY ay.year_label DESC, d.division_name, c.class_name, t.track_name, g.group_name, p.professor_name, s.subject_name
    ");
    $lectures =$stmt->fetchAll(PDO::FETCH_ASSOC);

    $tree = [];
    foreach ($lectures as$lec) {
        $year =$lec['year_label'] ?: 'غير محدد';
        $div  =$lec['division_name'] ?: 'غير محدد';
        $cls  =$lec['class_name'] ?: 'غير محدد';
        $trk  =$lec['track_name'] ?: 'عام';
        $grp  =$lec['group_name'] ?: 'عام';
        $prof =$lec['professor_name'] ?: 'أستاذ غير محدد';
        $sub  =$lec['subject_name'] ?: 'مادة غير محددة';

        $tree[$year][$div][$cls][$trk][$grp][$prof][$sub][] =$lec;
    }

} catch (PDOException $e) {
    die("خطأ في قاعدة البيانات: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المحاضرات - Mo7adaraty</title>
    <link rel="icon" type="image/svg+xml" href="/mo7adaraty/assets/images/favicon.svg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/admin_global.css">
    <link rel="stylesheet" href="../assets/css/admin_academic.css">
    <style>
        .acc-header { cursor: pointer; padding: 15px; background: var(--bg-surface); border: 1px solid var(--border-color); margin-bottom: 5px; border-radius: var(--radius-sm); font-weight: bold; display: flex; justify-content: space-between; align-items: center; user-select: none; transition: 0.2s; }
        .acc-header:hover { background: var(--bg-light); border-color: var(--brand-primary); }
        .acc-body { display: none; padding: 15px; margin-bottom: 15px; border-right: 3px solid var(--brand-primary); background: var(--bg-color); }
        
        .lvl-1 { font-size: 20px; background: var(--brand-light); color: var(--brand-primary); border-color: var(--brand-primary); }
        .lvl-2 { font-size: 18px; margin-right: 15px; }
        .lvl-3 { font-size: 16px; margin-right: 30px; }
        .lvl-4 { font-size: 15px; margin-right: 45px; background: var(--bg-light); }
        .lvl-5 { font-size: 14px; margin-right: 60px; }
        .lvl-6 { font-size: 14px; margin-right: 75px; color: var(--info-color); border-left: 3px solid var(--info-color); }
        
        .lecture-card { background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-md); overflow: hidden; display: flex; flex-direction: column; transition: 0.3s; box-shadow: var(--shadow-sm); }
        .lecture-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-md); border-color: var(--brand-primary); }
        .lec-cover { height: 180px; background: #0f172a; position: relative; display: flex; align-items: center; justify-content: center; overflow: hidden; border-bottom: 1px solid var(--border-color); cursor: pointer; }
        .lec-cover img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .lec-type-tag { position: absolute; bottom: 8px; right: 8px; background: rgba(15, 23, 42, 0.85); color: #fff; font-size: 11px; font-weight: bold; padding: 2px 7px; border-radius: 4px; z-index: 2; }
        .lec-drive-btn { position: absolute; top: 8px; left: 8px; background: rgba(15, 23, 42, 0.75); color: #fff; border: 1px solid rgba(255,255,255,0.2); padding: 4px 8px; border-radius: 4px; font-size: 11px; text-decoration: none; display: flex; align-items: center; gap: 4px; z-index: 2; transition: 0.2s; }
        .lec-drive-btn:hover { background: var(--brand-primary); color: #fff; }
        
        .lec-info { padding: 15px; flex-grow: 1; }
        .lec-title { margin: 0 0 10px 0; font-size: 16px; color: var(--text-primary); }
        .lec-meta { font-size: 12px; color: var(--text-secondary); margin-bottom: 5px; }
        .lec-actions { display: flex; border-top: 1px solid var(--border-color); }
        .lec-btn { flex: 1; text-align: center; padding: 10px; font-size: 13px; font-weight: bold; text-decoration: none; cursor: pointer; border: none; background: transparent; font-family: var(--font-family); transition: 0.2s; display: flex; align-items: center; justify-content: center; gap: 5px; }
        .btn-view { color: var(--brand-primary); border-left: 1px solid var(--border-color); }
        .btn-view:hover { background: rgba(26,115,232,0.1); }
        .btn-edit { color: var(--warning-color); border-left: 1px solid var(--border-color); }
        .btn-edit:hover { background: rgba(255,193,7,0.1); }
        .btn-del { color: var(--danger-color); }
        .btn-del:hover { background: rgba(220,53,69,0.1); }

        .preview-modal-custom { position: fixed; inset: 0; z-index: 999999; display: none; align-items: center; justify-content: center; padding: 16px; background: rgba(0, 0, 0, 0.85); backdrop-filter: blur(4px); }
        .preview-dialog-custom { position: relative; background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: 16px; width: 100%; max-width: 1050px; height: 90vh; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); }
        .preview-dialog-bar { display: flex; align-items: center; justify-content: space-between; padding: 12px 18px; border-bottom: 1px solid var(--border-color); background: var(--bg-body); }
        .preview-dialog-body { flex: 1; width: 100%; height: 100%; background: #0f172a; }
        .preview-dialog-body iframe { width: 100%; height: 100%; border: none; display: block; }
    </style>
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
                    <img src="../dashboard_design/images/admin_icon_lectures.png" alt="Lectures" style="width: 40px; height: 40px; object-fit: contain;">
                    إدارة المحاضرات
                </h2>
                <button class="btn-add-new" onclick="window.location.href='add.php'">
                    <i class="fa-solid fa-plus"></i> إضافة محاضرة جديدة
                </button>
            </div>

            <?php if (isset($_GET['status']) &&$_GET['status'] == 'added_successfully'): ?>
                <div style="padding: 15px; background: rgba(40, 167, 69, 0.1); color: var(--success-color); border: 1px solid var(--success-color); border-radius: var(--radius-md); margin-bottom: 20px; font-weight: bold;">
                    <i class="fa-solid fa-circle-check"></i> تم إضافة المحاضرة ونشرها بنجاح عبر Google Drive.
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['status']) &&$_GET['status'] == 'deleted'): ?>
                <div style="padding: 15px; background: rgba(220, 53, 69, 0.1); color: var(--danger-color); border: 1px solid var(--danger-color); border-radius: var(--radius-md); margin-bottom: 20px; font-weight: bold;">
                    <i class="fa-solid fa-trash"></i> تم حذف المحاضرة وملفها بنجاح.
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['status']) &&$_GET['status'] == 'updated'): ?>
                <div style="padding: 15px; background: rgba(40, 167, 69, 0.1); color: var(--success-color); border: 1px solid var(--success-color); border-radius: var(--radius-md); margin-bottom: 20px; font-weight: bold;">
                    <i class="fa-solid fa-circle-check"></i> تم تحديث بيانات المحاضرة بنجاح.
                </div>
            <?php endif; ?>

            <div class="skeleton">
                <?php if (empty($tree)): ?>
                    <div style="text-align: center; padding: 40px; background: var(--bg-surface); border: 1px dashed var(--border-color); border-radius: var(--radius-md); color: var(--text-secondary);">
                        <i class="fa-solid fa-folder-open" style="font-size: 40px; margin-bottom: 10px;"></i>
                        <p>لا توجد محاضرات في النظام.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($tree as $year =>$divisions): ?>
                        <div class="acc-header lvl-1"><i class="fa-regular fa-calendar-check"></i> موسم: <?= htmlspecialchars($year) ?> <i class="fa-solid fa-chevron-down"></i></div>
                        <div class="acc-body">
                            <?php foreach ($divisions as $div =>$classes): ?>
                                <div class="acc-header lvl-2"><i class="fa-solid fa-building-columns"></i> <?= htmlspecialchars($div) ?> <i class="fa-solid fa-chevron-down"></i></div>
                                <div class="acc-body">
                                    <?php foreach ($classes as $cls =>$tracks): ?>
                                        <div class="acc-header lvl-3"><i class="fa-solid fa-chalkboard"></i> <?= htmlspecialchars($cls) ?> <i class="fa-solid fa-chevron-down"></i></div>
                                        <div class="acc-body">
                                            <?php foreach ($tracks as $trk =>$groups): ?>
                                                <div class="acc-header lvl-4"><i class="fa-solid fa-code-branch"></i> مسار: <?= htmlspecialchars($trk) ?> <i class="fa-solid fa-chevron-down"></i></div>
                                                <div class="acc-body">
                                                    <?php foreach ($groups as $grp =>$profs): ?>
                                                        <div class="acc-header lvl-5"><i class="fa-solid fa-users"></i> فوج: <?= htmlspecialchars($grp) ?> <i class="fa-solid fa-chevron-down"></i></div>
                                                        <div class="acc-body">
                                                            <?php foreach ($profs as $prof =>$subs): ?>
                                                                <div class="acc-header lvl-6" style="border-right: none; border-left: 3px solid var(--warning-color);"><i class="fa-solid fa-user-tie"></i> د. <?= htmlspecialchars($prof) ?> <i class="fa-solid fa-chevron-down"></i></div>
                                                                <div class="acc-body">
                                                                    <?php foreach ($subs as $sub =>$lecs): ?>
                                                                        <div class="acc-header lvl-6"><i class="fa-solid fa-book"></i> مادة: <?= htmlspecialchars($sub) ?> <i class="fa-solid fa-chevron-down"></i></div>
                                                                        <div class="acc-body" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 18px; background: transparent; border: none; padding: 0;">
                                                                            <?php foreach ($lecs as$lec): 
                                                                                $is_drive = (strpos($lec['file_path'], 'http://') === 0 || strpos($lec['file_path'], 'https://') === 0);$view_url = $is_drive ? getDriveEmbedUrl($lec['file_path']) : '../../' . $lec['file_path'];$has_cover = !empty($lec['cover_image']);$is_cover_remote = $has_cover && (strpos($lec['cover_image'], 'http://') === 0 || strpos($lec['cover_image'], 'https://') === 0);$cover_src = $has_cover ? ($is_cover_remote ? $lec['cover_image'] : '../../' . ltrim($lec['cover_image'], '/')) : '';
                                                                            ?>
                                                                                <div class="lecture-card">
                                                                                    <div class="lec-cover" onclick="openPreviewModal('<?= htmlspecialchars($view_url) ?>', '<?= htmlspecialchars(addslashes($lec['title'])) ?>')">
                                                                                        <?php if ($has_cover): ?>
                                                                                            <img src="<?= htmlspecialchars($cover_src) ?>" alt="Cover" loading="lazy" onerror="this.src='../dashboard_design/images/admin_icon_lectures.png'">
                                                                                        <?php else: ?>
                                                                                            <img src="../dashboard_design/images/admin_icon_lectures.png" alt="Cover" style="width: 70px; height: 70px; object-fit: contain;">
                                                                                        <?php endif; ?>
                                                                                        
                                                                                        <span class="lec-type-tag"><?= strtoupper($lec['file_type'] ?: 'FILE') ?></span>
                                                                                        
                                                                                        <a href="javascript:void(0)" onclick="event.stopPropagation(); openPreviewModal('<?= htmlspecialchars($view_url) ?>', '<?= htmlspecialchars(addslashes($lec['title'])) ?>')" class="lec-drive-btn" title="معاينة الملف">
                                                                                            <i class="fa-solid fa-expand"></i> معاينة
                                                                                        </a>
                                                                                    </div>
                                                                                    <div class="lec-info">
                                                                                        <h4 class="lec-title"><?= htmlspecialchars($lec['title']) ?></h4>
                                                                                        <div class="lec-meta"><i class="fa-solid fa-user-pen"></i> <?= htmlspecialchars($lec['uploader_name']) ?></div>
                                                                                        <div class="lec-meta"><i class="fa-regular fa-clock"></i> <?= date('Y-m-d', strtotime($lec['upload_date'])) ?></div>
                                                                                        <?php if ($is_drive): ?>
                                                                                            <div class="lec-meta" style="color: #10b981; font-weight: bold;"><i class="fa-brands fa-google-drive"></i> مرفوع على Google Drive</div>
                                                                                        <?php endif; ?>
                                                                                    </div>
                                                                                    <div class="lec-actions">
                                                                                        <a href="<?= htmlspecialchars($view_url) ?>" target="_blank" rel="noopener noreferrer" class="lec-btn btn-view" title="فتح الملف"><i class="fa-solid fa-arrow-up-right-from-square"></i> عرض</a>
                                                                                        <a href="edit.php?id=<?= $lec['lecture_id'] ?>" class="lec-btn btn-edit"><i class="fa-solid fa-pen"></i> تعديل</a>
                                                                                        <form action="delete_process.php" method="POST" style="flex:1; margin:0;" onsubmit="return confirm('تأكيد الحذف؟ سيتم حذف الملف من قاعدة البيانات ومحرك Google Drive إن وُجد.');">
                                                                                            <input type="hidden" name="id" value="<?= $lec['lecture_id'] ?>">
                                                                                            <button type="submit" class="lec-btn btn-del" style="width:100%;"><i class="fa-solid fa-trash"></i> حذف</button>
                                                                                        </form>
                                                                                    </div>
                                                                                </div>
                                                                            <?php endforeach; ?>
                                                                        </div>
                                                                    <?php endforeach; ?>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <div id="adminModalPreview" class="preview-modal-custom">
        <div class="preview-dialog-custom">
            <div class="preview-dialog-bar">
                <h3 id="adminModalTitle" style="margin: 0; font-size: 15px; font-weight: 700; color: var(--text-primary);">
                    <i class="fa-solid fa-file-pdf" style="color: #ef4444; margin-left: 6px;"></i> معاينة المحاضرة
                </h3>
                <button type="button" onclick="closePreviewModal()" style="background: none; border: none; font-size: 24px; color: var(--text-secondary); cursor: pointer; line-height: 1;">&times;</button>
            </div>
            <div class="preview-dialog-body">
                <iframe id="adminModalIframe" src="" allow="autoplay"></iframe>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/admin_global.js"></script>
    <script src="lectures_script.js"></script>
    <script>
        function openPreviewModal(url, title) {
            if (!url) return;
            document.getElementById('adminModalTitle').innerText = title;
            document.getElementById('adminModalIframe').src = url;
            document.getElementById('adminModalPreview').style.display = 'flex';
        }

        function closePreviewModal() {
            document.getElementById('adminModalPreview').style.display = 'none';
            document.getElementById('adminModalIframe').src = 'about:blank';
        }

        window.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closePreviewModal();
            }
        });
    </script>
</body>
</html>