<?php
session_start();
require_once '../../config/db_connexion.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {    header("Location: ../../login.php");
    exit();
}

$breadcrumbs = [
    "إدارة المحتوى" => "",
    "الامتحانات المرفوعة" => ""
];

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
        if (preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $url,$matches) || preg_match('/[?&]id=([a-zA-Z0-9_-]+)/', $url,$matches)) {
            return "https://drive.google.com/uc?export=download&id=" . $matches[1];
        }
        return $url;
    }
}

try {
    $stmt =$pdo->query("
        SELECT 
            e.exam_id, e.title, e.description, e.file_path, e.file_type, e.upload_date, e.status,
            ay.year_label, ay.is_current,
            d.division_name, d.division_image,
            c.class_name, c.class_image,
            s.subject_name,
            co.group_id, g.group_name,
            u.username as uploader_name
        FROM exams e
        LEFT JOIN academic_years ay ON e.academic_year_id = ay.id
        LEFT JOIN divisions d ON e.division_id = d.division_id
        LEFT JOIN classes c ON e.class_id = c.class_id
        LEFT JOIN offering_subjects os ON e.offering_subject_id = os.offering_subject_id
        LEFT JOIN subjects s ON os.subject_id = s.subject_id
        LEFT JOIN course_offerings co ON e.offering_id = co.offering_id
        LEFT JOIN `groups` g ON co.group_id = g.group_id
        LEFT JOIN users u ON e.uploader_user_id = u.user_id
        ORDER BY ay.year_label DESC, d.division_name, c.class_name, e.upload_date DESC
    ");
    $exams =$stmt->fetchAll(PDO::FETCH_ASSOC);

    // بناء الهيكل الشجري: العام > الشعبة > الفصل > الامتحانات
    $tree = [];
    foreach ($exams as$ex) {
        $year =$ex['year_label'] ?: 'عام غير محدد';
        $is_curr =$ex['is_current'];
        $div =$ex['division_name'] ?: 'شعبة غير محددة';
        $div_img =$ex['division_image'];
        $cls =$ex['class_name'] ?: 'فصل غير محدد';
        $cls_img =$ex['class_image'];

        if (!isset($tree[$year])) {$tree[$year] = ['is_current' =>$is_curr, 'divisions' => []];
        }
        if (!isset($tree[$year]['divisions'][$div])) {
            $tree[$year]['divisions'][$div] = ['image' =>$div_img, 'classes' => []];
        }
        if (!isset($tree[$year]['divisions'][$div]['classes'][$cls])) {$tree[$year]['divisions'][$div]['classes'][$cls] = ['image' =>$cls_img, 'exams' => []];
        }
        $tree[$year]['divisions'][$div]['classes'][$cls]['exams'][] =$ex;
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
    <title>إدارة الامتحانات - Mo7adaraty</title>
    <link rel="icon" type="image/svg+xml" href="/mo7adaraty/assets/images/favicon.svg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/admin_global.css">
    <link rel="stylesheet" href="../assets/css/admin_academic.css">
    <style>
        .gh-wrapper { display: flex; flex-direction: column; gap: 30px; }
        .gh-year-header { font-size: 24px; font-weight: 900; color: var(--text-primary); border-right: 5px solid var(--brand-primary); padding-right: 15px; margin-bottom: 20px; display: flex; align-items: center; gap: 15px; }
        .gh-badge-current { background: var(--success-color); color: #fff; padding: 4px 12px; border-radius: 20px; font-size: 13px; font-weight: bold; }
        
        .gh-div-card { background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); overflow: hidden; margin-bottom: 15px; transition: 0.3s; }
        .gh-div-header { display: flex; align-items: center; gap: 20px; padding: 20px; cursor: pointer; background: var(--bg-light); transition: 0.3s; user-select: none; }
        .gh-div-header:hover { background: var(--border-color); }
        .gh-div-img { width: 70px; height: 70px; object-fit: contain; background: var(--bg-color); padding: 5px; border-radius: 12px; border: 1px solid var(--border-color); }
        .gh-div-title { font-size: 20px; font-weight: 800; color: var(--brand-primary); margin: 0; }
        .gh-div-body { display: none; padding: 20px; border-top: 2px solid var(--brand-primary); background: var(--bg-color); }
        
        .gh-cls-card { background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-md); margin-bottom: 15px; overflow: hidden; }
        .gh-cls-header { display: flex; align-items: center; gap: 15px; padding: 15px; cursor: pointer; background: var(--bg-surface); transition: 0.3s; user-select: none; }
        .gh-cls-header:hover { background: var(--bg-light); }
        .gh-cls-img { width: 40px; height: 40px; object-fit: contain; }
        .gh-cls-title { font-size: 18px; font-weight: 700; color: var(--text-primary); margin: 0; }
        .gh-cls-body { display: none; padding: 20px; border-top: 1px dashed var(--border-color); background: var(--bg-light); }

        .acc-icon { margin-left: auto; transition: transform 0.3s; font-size: 20px; color: var(--text-secondary); }
        .gh-expanded > .acc-icon { transform: rotate(180deg); color: var(--brand-primary); }

        .cinematic-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .cine-card { background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-md); overflow: hidden; box-shadow: var(--shadow-sm); transition: 0.3s; position: relative; display: flex; flex-direction: column;}
        .cine-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); border-color: var(--brand-primary); }
        .cine-status { position: absolute; top: 10px; left: 10px; padding: 4px 10px; border-radius: var(--radius-pill); font-size: 11px; font-weight: 800; color: #fff; z-index: 10; }
        .st-approved { background: rgba(40, 167, 69, 0.9); } .st-pending { background: rgba(255, 193, 7, 0.9); color: #000; } .st-rejected { background: rgba(220, 53, 69, 0.9); }
        
        .cine-preview-box { height: 180px; background: #0f172a; position: relative; display: flex; align-items: center; justify-content: center; overflow: hidden; border-bottom: 1px solid var(--border-color); }
        .cine-preview-box iframe { width: 100%; height: 100%; border: none; display: block; }
        .cine-preview-box img.cover-img { width: 100%; height: 100%; object-fit: cover; }
        
        .cine-body { padding: 15px; flex-grow: 1; }
        .cine-title { font-size: 16px; font-weight: 800; color: var(--text-primary); margin: 0 0 10px 0; }
        .cine-meta { font-size: 12px; color: var(--text-secondary); display: flex; gap: 10px; font-weight: 600; margin-bottom: 10px; }
        .cine-badge { display: inline-block; background: var(--brand-light); color: var(--brand-primary); padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: bold; border: 1px solid rgba(26, 115, 232, 0.2); margin-top: 5px; }
        .cine-actions { display: flex; border-top: 1px solid var(--border-color); }
        .cine-btn { flex: 1; padding: 10px; text-align: center; font-size: 13px; font-weight: bold; text-decoration: none; border: none; background: var(--bg-surface); cursor: pointer; transition: 0.2s; display: flex; align-items: center; justify-content: center; gap: 5px; }
        .cine-btn-view { color: var(--brand-primary); }
        .cine-btn-view:hover { background: rgba(26, 115, 232, 0.1); }
        .cine-btn-edit { color: var(--warning-color); border-left: 1px solid var(--border-color); border-right: 1px solid var(--border-color); }
        .cine-btn-edit:hover { background: rgba(255, 193, 7, 0.1); }
        .cine-btn-del { color: var(--danger-color); }
        .cine-btn-del:hover { background: rgba(220, 53, 69, 0.1); }

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
                    <img src="../dashboard_design/images/admin_icon_exams.png" alt="Exams" style="width: 40px; height: 40px; object-fit: contain;">
                    إدارة الامتحانات
                </h2>
                <a href="add.php" class="btn-add-new">
                    <i class="fa-solid fa-cloud-arrow-up"></i> رفع امتحان جديد
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

            <div class="gh-wrapper skeleton">
                <?php if (empty($tree)): ?>
                    <div style="text-align: center; padding: 50px; background: var(--bg-surface); border-radius: var(--radius-lg); border: 1px dashed var(--border-color); color: var(--text-secondary);">
                        <i class="fa-solid fa-file-signature" style="font-size: 50px; margin-bottom: 15px; opacity: 0.5;"></i>
                        <p style="font-size: 18px; font-weight: bold;">لا توجد امتحانات في النظام حالياً.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($tree as $year =>$year_data): ?>
                        <div class="gh-year-section">
                            <div class="gh-year-header">
                                <i class="fa-regular fa-calendar-check"></i> موسم <?php echo htmlspecialchars($year); ?>
                                <?php if ($year_data['is_current']): ?>
                                    <span class="gh-badge-current">العام الحالي</span>
                                <?php endif; ?>
                            </div>
                            
                            <?php foreach ($year_data['divisions'] as $div =>$div_data): ?>
                                <div class="gh-div-card">
                                    <div class="gh-div-header acc-trigger">
                                        <?php $div_img = !empty($div_data['image']) ? '../../uploads/divisions/' . htmlspecialchars($div_data['image']) : '../dashboard_design/images/admin_icon_divisions.png'; ?>
                                        <img src="<?= $div_img ?>" alt="Div" class="gh-div-img" onerror="this.src='../dashboard_design/images/admin_icon_divisions.png'">
                                        <h2 class="gh-div-title"><?= htmlspecialchars($div) ?></h2>
                                        <i class="fa-solid fa-chevron-down acc-icon"></i>
                                    </div>
                                    
                                    <div class="gh-div-body">
                                        <?php foreach ($div_data['classes'] as $cls =>$cls_data): ?>
                                            <div class="gh-cls-card">
                                                <div class="gh-cls-header acc-trigger">
                                                    <?php $cls_img = !empty($cls_data['image']) ? '../../uploads/classes/' . htmlspecialchars($cls_data['image']) : '../dashboard_design/images/admin_icon_classes.svg'; ?>
                                                    <img src="<?= $cls_img ?>" alt="Cls" class="gh-cls-img" onerror="this.src='../dashboard_design/images/admin_icon_classes.svg'">
                                                    <h3 class="gh-cls-title"><?= htmlspecialchars($cls) ?></h3>
                                                    <i class="fa-solid fa-chevron-down acc-icon"></i>
                                                </div>
                                                
                                                <div class="gh-cls-body">
                                                    <div class="cinematic-grid">
                                                        <?php foreach ($cls_data['exams'] as$ex): ?>
                                                            <?php 
$is_drive = (strpos($ex['file_path'], 'http://') === 0 || strpos($ex['file_path'], 'https://') === 0);
$view_url = $is_drive ? getDriveEmbedUrl($ex['file_path']) : '../../' . $ex['file_path'];
$is_pdf = (strtolower($ex['file_type'] ?? '') === 'pdf' || strpos(strtolower($ex['file_path']), '.pdf') !== false);                                                                $is_image = in_array(strtolower($ex['file_type'] ?? ''), ['jpg', 'jpeg', 'png', 'webp']);

                                                                $status_class = '';$status_text = '';
                                                                if ($ex['status'] === 'approved') { $status_class = 'st-approved';$status_text = 'مقبول'; }
                                                                elseif ($ex['status'] === 'pending') { $status_class = 'st-pending';$status_text = 'مراجعة'; }
                                                                else { $status_class = 'st-rejected';$status_text = 'مرفوض'; }
                                                            ?>
                                                            <div class="cine-card">
                                                                <div class="cine-status <?= $status_class ?>"><?= $status_text ?></div>
                                                                
                                                                <div class="cine-preview-box">
                                                                    <?php if ($is_pdf): ?>
                                                                        <iframe src="<?= htmlspecialchars($view_url) ?>" loading="lazy"></iframe>
                                                                    <?php elseif ($is_image): ?>
                                                                        <img src="<?= htmlspecialchars($view_url) ?>" alt="Cover" class="cover-img">
                                                                    <?php else: ?>
                                                                        <img src="../dashboard_design/images/admin_icon_exams.png" alt="Cover" style="width: 70px; height: 70px; object-fit: contain;">
                                                                    <?php endif; ?>

                                                                    <a href="javascript:void(0)" onclick="openExamPreviewModal('<?= htmlspecialchars($view_url) ?>', '<?= htmlspecialchars(addslashes($ex['title'])) ?>')" style="position: absolute; top: 10px; right: 10px; background: rgba(15, 23, 42, 0.75); color: #fff; padding: 4px 8px; border-radius: 4px; font-size: 11px; text-decoration: none; z-index: 5;">
                                                                        <i class="fa-solid fa-expand"></i> معاينة
                                                                    </a>
                                                                </div>

                                                                <div class="cine-body">
                                                                    <h4 class="cine-title"><?= htmlspecialchars($ex['title']) ?></h4>
                                                                    <div class="cine-meta">
                                                                        <span><i class="fa-solid fa-user-pen"></i> <?= htmlspecialchars($ex['uploader_name'] ?? 'المدير') ?></span>
                                                                        <span><i class="fa-regular fa-clock"></i> <?= date('Y-m-d', strtotime($ex['upload_date'])) ?></span>
                                                                    </div>
                                                                    <?php if (!empty($ex['subject_name'])): ?>
                                                                        <span class="cine-badge">مادة: <?= htmlspecialchars($ex['subject_name']) ?></span>
                                                                    <?php endif; ?>
                                                                    <?php if (!empty($ex['group_name'])): ?>
                                                                        <span class="cine-badge" style="color:#d39e00; background:rgba(255,193,7,0.1); border-color:rgba(255,193,7,0.2);">فوج: <?= htmlspecialchars($ex['group_name']) ?></span>
                                                                    <?php endif; ?>
                                                                    <?php if ($is_drive): ?>
                                                                        <div style="font-size: 11px; color: #10b981; font-weight: bold; margin-top: 6px;"><i class="fa-brands fa-google-drive"></i> Google Drive</div>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <div class="cine-actions">
                                                                    <a href="<?= htmlspecialchars($view_url) ?>" target="_blank" rel="noopener noreferrer" class="cine-btn cine-btn-view"><i class="fa-solid fa-eye"></i> عرض</a>
                                                                    <a href="edit.php?id=<?= $ex['exam_id'] ?>" class="cine-btn cine-btn-edit"><i class="fa-solid fa-pen"></i> تعديل</a>
                                                                    <form action="delete_process.php" method="POST" style="flex:1; display:flex; margin:0;" onsubmit="return confirm('تأكيد حذف الامتحان وملفه نهائياً؟');">
                                                                        <input type="hidden" name="exam_id" value="<?= $ex['exam_id'] ?>">
                                                                        <button type="submit" class="cine-btn cine-btn-del"><i class="fa-solid fa-trash"></i> حذف</button>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <div id="examModalPreview" class="preview-modal-custom">
        <div class="preview-dialog-custom">
            <div class="preview-dialog-bar">
                <h3 id="examModalTitle" style="margin: 0; font-size: 15px; font-weight: 700; color: var(--text-primary);">
                    <i class="fa-solid fa-file-pdf" style="color: #ef4444; margin-left: 6px;"></i> معاينة الامتحان
                </h3>
                <button type="button" onclick="closeExamPreviewModal()" style="background: none; border: none; font-size: 24px; color: var(--text-secondary); cursor: pointer; line-height: 1;">&times;</button>
            </div>
            <div class="preview-dialog-body">
                <iframe id="examModalIframe" src="" allow="autoplay"></iframe>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/admin_global.js"></script>
    <script>
        $(document).ready(function() {$('.acc-trigger').on('click', function(e) {
                if($(e.target).closest('button, a').length) return;
                const body = $(this).next('.gh-div-body, .gh-cls-body');
                const icon = $(this).find('.acc-icon');
                if (body.is(':visible')) {
                    body.slideUp(200);
                    $(this).removeClass('gh-expanded');
                } else {
                    body.slideDown(200);
                    $(this).addClass('gh-expanded');
                }
            });
        });

        function openExamPreviewModal(url, title) {
            if (!url) return;
            document.getElementById('examModalTitle').innerText = title;
            document.getElementById('examModalIframe').src = url;
            document.getElementById('examModalPreview').style.display = 'flex';
        }

        function closeExamPreviewModal() {
            document.getElementById('examModalPreview').style.display = 'none';
            document.getElementById('examModalIframe').src = '';
        }

        window.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeExamPreviewModal();
            }
        });
    </script>
</body>
</html>