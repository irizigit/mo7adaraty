<?php
session_start();
require_once '../../config/db_connexion.php';

// 1. حماية الصفحة والتحقق من صلاحيات المشرف
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {    header("Location: ../../login.php");
    exit();
}

$breadcrumbs = [
    "إدارة المرفوعات" => "index.php",  
    "محاضرات الطلاب قيد المراجعة" => ""
];

// دوال استخراج الروابط المباشرة لـ Google Drive
if (!function_exists('getDriveEmbedUrl')) {
    function getDriveEmbedUrl($url) {
        if (empty($url)) return '';
        if (preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $url,$matches) || preg_match('/[?&]id=([a-zA-Z0-9_-]+)/', $url,$matches)) {
            return "https://drive.google.com/file/d/" . $matches[1] . "/preview";
        }
        return $url;
    }
}

if (!function_exists('getDriveDownloadUrl')) {
    function getDriveDownloadUrl($url) {
        if (empty($url)) return '';
        if (preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $url, $matches) || preg_match('/[?&]id=([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return "https://drive.google.com/uc?export=download&id=" . $matches[1];
        }
        return $url;
    }
}

// 2. استعلام جلب المحاضرات المعلقة
try {
    $stmt =$pdo->query("
        SELECT 
            l.*, 
            u.username, 
            u.profile_picture, 
            c.class_name, 
            s.subject_name, 
            d.division_name, 
            g.group_name 
        FROM lectures l
        JOIN users u ON l.uploader_user_id = u.user_id
        LEFT JOIN course_offerings co ON l.offering_id = co.offering_id
        LEFT JOIN classes c ON co.class_id = c.class_id
        LEFT JOIN divisions d ON co.division_id = d.division_id
        LEFT JOIN `groups` g ON co.group_id = g.group_id
        LEFT JOIN offering_subjects os ON l.offering_subject_id = os.offering_subject_id
        LEFT JOIN subjects s ON os.subject_id = s.subject_id
        WHERE l.status = 'pending'
        ORDER BY l.upload_date DESC
    ");
    $pending_lectures =$stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("خطأ في قاعدة البيانات: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مراجعة محاضرات الطلاب - الإدارة</title>
    <link rel="icon" type="image/svg+xml" href="/mo7adaraty/assets/images/favicon.svg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="../../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/admin_global.css">

    <style>
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
            gap: 24px;
            margin-top: 15px;
        }
        .review-card {
            background: var(--bg-surface);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }
        .review-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
            border-color: var(--brand-primary);
        }
        .card-top-meta {
            padding: 12px 16px;
            background: var(--bg-body);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .uploader-badge {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .uploader-avatar-container {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid var(--brand-primary);
            background: var(--bg-surface);
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .uploader-avatar-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .uploader-name {
            font-weight: 800;
            font-size: 14px;
            color: var(--text-primary);
        }
        .upload-time {
            font-size: 11px;
            color: var(--text-secondary);
            margin-top: 2px;
        }
        .preview-viewer-box {
            position: relative;
            width: 100%;
            height: 240px;
            background: #0f172a;
            overflow: hidden;
            border-bottom: 1px solid var(--border-color);
        }
        .preview-viewer-box iframe {
            width: 100%;
            height: 100%;
            border: none;
            display: block;
        }
        .preview-viewer-box img.cover-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .preview-placeholder-box {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background: linear-gradient(135deg, rgba(26, 115, 232, 0.08) 0%, rgba(15, 23, 42, 0.03) 100%);
            color: var(--text-secondary);
        }
        .preview-toolbar {
            position: absolute;
            top: 10px;
            left: 10px;
            display: flex;
            gap: 6px;
            z-index: 5;
        }
        .btn-toolbar-tool {
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(4px);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s;
        }
        .btn-toolbar-tool:hover {
            background: var(--brand-primary);
            color: #fff;
        }
        .file-type-pill {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: rgba(15, 23, 42, 0.85);
            color: #fff;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 0.5px;
            z-index: 5;
        }
        .card-main-body {
            padding: 16px 18px;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .lecture-heading {
            font-size: 16px;
            font-weight: 800;
            color: var(--text-primary);
            margin: 0;
            line-height: 1.4;
        }
        .course-tags-row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .meta-tag {
            background: var(--bg-body);
            border: 1px solid var(--border-color);
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            color: var(--text-secondary);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-weight: 500;
        }
        .lecture-desc-text {
            font-size: 13px;
            color: var(--text-secondary);
            margin: 0;
            line-height: 1.6;
            background: var(--bg-body);
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px dashed var(--border-color);
            max-height: 75px;
            overflow-y: auto;
        }
        .card-actions-footer {
            padding: 14px 18px;
            background: var(--bg-surface);
            border-top: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .action-btns-row {
            display: flex;
            gap: 10px;
        }
        .action-btns-row form {
            flex: 1;
            margin: 0;
        }
        .btn-decision {
            width: 100%;
            padding: 9px 14px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }
        .btn-decision-approve {
            background: #10b981;
            color: #fff;
        }
        .btn-decision-approve:hover {
            background: #059669;
        }
        .btn-decision-reject {
            background: rgba(239, 68, 68, 0.12);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.3);
            flex: 1;
        }
        .btn-decision-reject:hover {
            background: #ef4444;
            color: #fff;
        }
        .reject-reason-box {
            display: none;
            background: var(--bg-body);
            padding: 12px;
            border-radius: 8px;
            border: 1px dashed var(--danger-color);
            animation: fadeIn 0.2s ease-in-out;
        }
        .reject-reason-input {
            width: 100%;
            padding: 8px 12px;
            margin-bottom: 8px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            background: var(--bg-surface);
            color: var(--text-primary);
            font-family: inherit;
            font-size: 13px;
            box-sizing: border-box;
        }
        .reject-reason-input:focus {
            outline: none;
            border-color: var(--danger-color);
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-4px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .preview-modal-custom {
            position: fixed;
            inset: 0;
            z-index: 999999;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 16px;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(4px);
        }
        .preview-dialog-custom {
            position: relative;
            background: var(--bg-surface);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            width: 100%;
            max-width: 1050px;
            height: 90vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        .preview-dialog-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 18px;
            border-bottom: 1px solid var(--border-color);
            background: var(--bg-body);
        }
        .preview-dialog-body {
            flex: 1;
            width: 100%;
            height: 100%;
            background: #0f172a;
        }
        .preview-dialog-body iframe {
            width: 100%;
            height: 100%;
            border: none;
            display: block;
        }
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
            
            <div class="academic-header-actions" style="margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                <h2 style="margin: 0; color: var(--text-primary); display: flex; align-items: center; gap: 10px;">
                    <i class="fa-solid fa-file-video" style="color: var(--brand-primary); font-size: 28px;"></i>
                    محاضرات الطلاب قيد المراجعة
                    <span style="font-size: 14px; background: rgba(26, 115, 232, 0.12); color: var(--brand-primary); padding: 3px 10px; border-radius: 20px;">
                        <?= count($pending_lectures) ?> طلب معلق
                    </span>
                </h2>
                <a href="index.php" class="btn" style="background: var(--bg-surface); color: var(--text-primary); border: 1px solid var(--border-color); box-shadow: var(--shadow-sm);">
                    <i class="fa-solid fa-arrow-right"></i> عودة للمركز
                </a>
            </div>
            
            <?php if (isset($_SESSION['msg'])): ?>
                <div style="padding: 15px; background: rgba(40, 167, 69, 0.1); color: var(--success-color); border: 1px solid var(--success-color); border-radius: var(--radius-md); margin-bottom: 20px; font-weight: bold;">
                    <i class="fa-solid fa-circle-check"></i> <?= $_SESSION['msg']; unset($_SESSION['msg']); ?>
                </div>
            <?php endif; ?>

            <?php if (empty($pending_lectures)): ?>
                <div style="background: var(--bg-surface); border-radius: var(--radius-lg); border: 1px solid var(--border-color); padding: 50px 20px; text-align: center; color: var(--text-secondary); box-shadow: var(--shadow-sm);">
                    <i class="fa-solid fa-inbox" style="font-size: 45px; margin-bottom: 15px; display: block; opacity: 0.4;"></i>
                    <h3 style="margin: 0 0 8px 0; color: var(--text-primary);">لا توجد أي محاضرات قيد المراجعة</h3>
                    <p style="margin: 0; font-size: 14px;">تم فحص واعتماد جميع مرفوعات الطلبة الحالية بنجاح.</p>
                </div>
            <?php else: ?>
                <div class="cards-grid">
          <?php foreach ($pending_lectures as $lec): 
    $is_drive = (strpos($lec['file_path'], 'http://') === 0 || strpos($lec['file_path'], 'https://') === 0);
    $embed_url = $is_drive ? getDriveEmbedUrl($lec['file_path']) : '../../' . $lec['file_path'];
    $download_url = $is_drive ? getDriveDownloadUrl($lec['file_path']) : '../../' . $lec['file_path'];
    $is_pdf = ($lec['file_type'] === 'pdf' || strpos(strtolower($lec['file_path']), '.pdf') !== false);
    $has_cover = !empty($lec['cover_image']);
    $is_cover_remote = $has_cover && (strpos($lec['cover_image'], 'http://') === 0 || strpos($lec['cover_image'], 'https://') === 0);
    $cover_src = $has_cover ? ($is_cover_remote ? $lec['cover_image'] : '../../' . ltrim($lec['cover_image'], '/')) : '';
    $avatar_file = !empty($lec['profile_picture']) ? $lec['profile_picture'] : 'default_profile.png';
    $avatar_path = '../../uploads/profile_pictures/' . $avatar_file;
?>
                        <div class="review-card" id="card-lec-<?= $lec['lecture_id'] ?>">
                            
                            <div class="card-top-meta">
                                <div class="uploader-badge">
                                    <div class="uploader-avatar-container">
                                        <img src="<?= htmlspecialchars($avatar_path) ?>" alt="<?= htmlspecialchars($lec['username']) ?>" class="uploader-avatar-img" onerror="this.onerror=null;this.src='../../uploads/profile_pictures/default_profile.png';">
                                    </div>
                                    <div>
                                        <div class="uploader-name"><?= htmlspecialchars($lec['username']) ?></div>
                                        <div class="upload-time" dir="ltr"><i class="fa-regular fa-clock"></i> <?= date('Y-m-d H:i', strtotime($lec['upload_date'])) ?></div>
                                    </div>
                                </div>
                                <span class="file-type-pill" style="position: static;"><?= strtoupper($lec['file_type']) ?></span>
                            </div>

                            <div class="preview-viewer-box">
                                <div class="preview-toolbar">
                                    <button type="button" class="btn-toolbar-tool" onclick="openAdminModal('<?= htmlspecialchars($embed_url) ?>', '<?= htmlspecialchars(addslashes($lec['title'])) ?>')">
                                        <i class="fa-solid fa-expand"></i> معاينة كاملة
                                    </button>
                                    <a href="<?= htmlspecialchars($embed_url) ?>" target="_blank" rel="noopener noreferrer" class="btn-toolbar-tool">
                                        <?php if ($is_drive): ?>
                                            <i class="fa-brands fa-google-drive"></i> درايف
                                        <?php else: ?>
                                            <i class="fa-solid fa-arrow-up-right-from-square"></i> فتح
                                        <?php endif; ?>
                                    </a>
                                </div>

                                <?php if ($has_cover): ?>
                                    <img src="<?= htmlspecialchars($cover_src) ?>" alt="Cover" class="cover-img" loading="lazy">
                                <?php elseif ($is_pdf): ?>
                                    <iframe src="<?= htmlspecialchars($embed_url) ?>" loading="lazy"></iframe>
                                <?php else: ?>
                                    <div class="preview-placeholder-box">
                                        <i class="fa-regular fa-file-lines" style="font-size: 42px; opacity: 0.6;"></i>
                                        <span style="font-size: 13px; font-weight: 600;">ملف غير مدعوم للمعاينة التلقائية</span>
                                        <a href="<?= htmlspecialchars($download_url) ?>" target="_blank" download class="btn-toolbar-tool" style="position: static; margin-top: 5px;">
                                            <i class="fa-solid fa-download"></i> تنزيل وفحص
                                        </a>
                                    </div>
                                <?php endif; ?>

                                <span class="file-type-pill"><?= strtoupper($lec['file_type']) ?></span>
                            </div>

                            <div class="card-main-body">
                                <h3 class="lecture-heading"><?= htmlspecialchars($lec['title']) ?></h3>

                                <div class="course-tags-row">
                                    <span class="meta-tag" title="الشعبة">
                                        <i class="fa-solid fa-graduation-cap" style="color: #6366f1;"></i>
                                        <?= htmlspecialchars($lec['division_name'] ?? 'شعبة عامة') ?>
                                    </span>
                                    <span class="meta-tag" title="الفصل">
                                        <i class="fa-solid fa-layer-group" style="color: var(--brand-primary);"></i>
                                        <?= htmlspecialchars($lec['class_name'] ?? 'مقرر عام') ?>
                                    </span>
                                    <span class="meta-tag" title="الفوج">
                                        <i class="fa-solid fa-users" style="color: #10b981;"></i>
                                        <?= htmlspecialchars($lec['group_name'] ?? 'عام') ?>
                                    </span>
                                    <span class="meta-tag" title="المادة">
                                        <i class="fa-solid fa-book-open" style="color: #f59e0b;"></i>
                                        <?= htmlspecialchars($lec['subject_name'] ?? 'مادة عامة') ?>
                                    </span>
                                </div>

                                <?php if (!empty($lec['description'])): ?>
                                    <p class="lecture-desc-text" title="الوصف المرفق">
                                        <?= nl2br(htmlspecialchars($lec['description'])) ?>
                                    </p>
                                <?php endif; ?>
                            </div>

                            <div class="card-actions-footer">
                                <div class="action-btns-row">
                                    <form action="process_lecture.php" method="POST">
                                        <input type="hidden" name="lecture_id" value="<?= $lec['lecture_id'] ?>">
                                        <button type="submit" name="action" value="approve" class="btn-decision btn-decision-approve" onclick="return confirm('هل أنت متأكد من قبول هذه المحاضرة ونشرها لجميع الطلبة؟');">
                                            <i class="fa-solid fa-check"></i> قبول ونشر
                                        </button>
                                    </form>
                                    
                                    <button type="button" class="btn-decision btn-decision-reject" onclick="toggleRejectBox(<?= $lec['lecture_id'] ?>)">
                                        <i class="fa-solid fa-times"></i> رفض الطلب
                                    </button>
                                </div>

                                <div id="reject-box-<?= $lec['lecture_id'] ?>" class="reject-reason-box">
                                    <form action="process_lecture.php" method="POST" style="margin: 0;">
                                        <input type="hidden" name="lecture_id" value="<?= $lec['lecture_id'] ?>">
                                        <input type="text" name="reason" class="reject-reason-input" placeholder="اكتب سبب الرفض هنا (إلزامي ليصل للطالب)..." required>
                                        <div style="display: flex; gap: 8px;">
                                            <button type="submit" name="action" value="reject" class="btn-decision btn-decision-reject" style="flex: 1; padding: 7px 10px; font-size: 12px; background: #ef4444; color: #fff;">
                                                تأكيد الرفض والحذف
                                            </button>
                                            <button type="button" class="btn-decision" style="flex: 0 0 auto; background: var(--bg-surface); border: 1px solid var(--border-color); color: var(--text-primary); padding: 7px 12px; font-size: 12px;" onclick="toggleRejectBox(<?= $lec['lecture_id'] ?>)">
                                                إلغاء
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>
    </main>

    <div id="adminModalPreview" class="preview-modal-custom">
        <div class="preview-dialog-custom">
            <div class="preview-dialog-bar">
                <h3 id="adminModalTitle" style="margin: 0; font-size: 15px; font-weight: 700; color: var(--text-primary);">
                    <i class="fa-solid fa-file-pdf" style="color: #ef4444; margin-left: 6px;"></i> معاينة المستند
                </h3>
                <button type="button" onclick="closeAdminModal()" style="background: none; border: none; font-size: 24px; color: var(--text-secondary); cursor: pointer; line-height: 1;">&times;</button>
            </div>
            <div class="preview-dialog-body">
                <iframe id="adminModalIframe" src="" allow="autoplay"></iframe>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/admin_global.js"></script>
    <script>
        function toggleRejectBox(id) {
            var box = document.getElementById('reject-box-' + id);
            if (box.style.display === 'block') {
                box.style.display = 'none';
            } else {
                document.querySelectorAll('.reject-reason-box').forEach(function(el) {
                    el.style.display = 'none';
                });
                box.style.display = 'block';
            }
        }

        function openAdminModal(url, title) {
            if (!url) return;
            document.getElementById('adminModalTitle').innerText = title;
            document.getElementById('adminModalIframe').src = url;
            document.getElementById('adminModalPreview').style.display = 'flex';
        }

        function closeAdminModal() {
            document.getElementById('adminModalPreview').style.display = 'none';
            document.getElementById('adminModalIframe').src = '';
        }

        window.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeAdminModal();
            }
        });
    </script>
</body>
</html>