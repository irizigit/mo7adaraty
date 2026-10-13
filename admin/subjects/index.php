<?php
session_start();
require_once '../../config/db_connexion.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

$breadcrumbs = [
    "الهيكلة الأكاديمية" => "",
    "المواد الدراسية (العرض الهرمي)" => ""
];

try {
    $stmt_design = $pdo->query("SELECT asset_key, file_path FROM design_assets WHERE page_section = 'global_icons'");
    $design_settings = [];
    while ($row = $stmt_design->fetch(PDO::FETCH_ASSOC)) {
        $design_settings[$row['asset_key']] = $row['file_path'];
    }

    function getDesignIcon($key, $default_path) {
        global $design_settings;
        return !empty($design_settings[$key]) ? '../../' . $design_settings[$key] : '../../' . $default_path;
    }

    $stmt = $pdo->query("
        SELECT 
            d.division_id, d.division_name, d.division_image,
            c.class_id, c.class_name, c.class_image,
            t.track_id, t.track_name,
            g.group_name,
            co.offering_id,
            s.subject_id, s.subject_name,
            p.professor_name
        FROM divisions d
        JOIN course_offerings co ON d.division_id = co.division_id
        JOIN classes c ON co.class_id = c.class_id
        LEFT JOIN tracks t ON co.track_id = t.track_id
        LEFT JOIN `groups` g ON co.group_id = g.group_id
        JOIN offering_subjects os ON co.offering_id = os.offering_id
        JOIN subjects s ON os.subject_id = s.subject_id
        LEFT JOIN professors p ON os.professor_id = p.professor_id
        ORDER BY d.division_name, c.class_name, t.track_name, g.group_name, s.subject_name
    ");
    
    $raw_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $hierarchy = [];
    foreach ($raw_data as $row) {
        $div_id = $row['division_id'];
        $cls_id = $row['class_id'];
        $track_id = $row['track_id']; 
        $off_id = $row['offering_id'];
        
        if (!isset($hierarchy[$div_id])) {
            $hierarchy[$div_id] = [
                'name' => $row['division_name'],
                'image' => $row['division_image'],
                'classes' => []
            ];
        }
        
        if (!isset($hierarchy[$div_id]['classes'][$cls_id])) {
            $hierarchy[$div_id]['classes'][$cls_id] = [
                'name' => $row['class_name'],
                'image' => $row['class_image'],
                'tracks' => [],
                'offerings' => [] 
            ];
        }
        
        $group_label = $row['group_name'] ? "فوج: " . $row['group_name'] : "فوج عام";
        $subject_data = [
            'id' => $row['subject_id'],
            'name' => $row['subject_name'],
            'professor' => $row['professor_name'] ?: 'أستاذ غير محدد'
        ];

        if (!empty($track_id)) {
            if (!isset($hierarchy[$div_id]['classes'][$cls_id]['tracks'][$track_id])) {
                $hierarchy[$div_id]['classes'][$cls_id]['tracks'][$track_id] = [
                    'name' => $row['track_name'],
                    'offerings' => []
                ];
            }
            if (!isset($hierarchy[$div_id]['classes'][$cls_id]['tracks'][$track_id]['offerings'][$off_id])) {
                $hierarchy[$div_id]['classes'][$cls_id]['tracks'][$track_id]['offerings'][$off_id] = [
                    'label' => $group_label,
                    'subjects' => []
                ];
            }
            $hierarchy[$div_id]['classes'][$cls_id]['tracks'][$track_id]['offerings'][$off_id]['subjects'][] = $subject_data;
        } else {
            if (!isset($hierarchy[$div_id]['classes'][$cls_id]['offerings'][$off_id])) {
                $hierarchy[$div_id]['classes'][$cls_id]['offerings'][$off_id] = [
                    'label' => $group_label,
                    'subjects' => []
                ];
            }
            $hierarchy[$div_id]['classes'][$cls_id]['offerings'][$off_id]['subjects'][] = $subject_data;
        }
    }

    $stmt_orphans = $pdo->query("
        SELECT subject_id, subject_name 
        FROM subjects 
        WHERE subject_id NOT IN (SELECT subject_id FROM offering_subjects)
    ");
    $orphaned_subjects = $stmt_orphans->fetchAll(PDO::FETCH_ASSOC);

    $divisions = $pdo->query("SELECT division_id, division_name FROM divisions ORDER BY division_name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $classes = $pdo->query("SELECT class_id, class_name FROM classes ORDER BY class_name ASC")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("خطأ في قاعدة البيانات: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المواد الدراسية (هرمياً) - Mo7adaraty</title>
    <link rel="icon" type="image/svg+xml" href="/mo7adaraty/assets/images/favicon.svg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/admin_global.css">
    <link rel="stylesheet" href="../assets/css/admin_academic.css">
    
    <style>
        .hierarchical-wrapper { display: flex; flex-direction: column; gap: 20px; }
        
        .h-div-card { background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); overflow: hidden; transition: all 0.3s; }
        .h-div-header { display: flex; align-items: center; gap: 20px; padding: 20px; cursor: pointer; background: var(--bg-light); transition: background 0.3s; user-select: none; }
        .h-div-header:hover { background: var(--border-color); }
        .h-div-img { width: 90px; height: 90px; object-fit: contain; background: #ffffff; padding: 5px; border-radius: 12px; border: 1px solid var(--border-color); box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .h-div-title { font-size: 22px; font-weight: 900; color: var(--brand-primary); margin: 0; }
        .h-div-body { display: none; padding: 25px; border-top: 2px solid var(--brand-primary); background: var(--bg-color); }
        
        .h-cls-card { background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-md); margin-bottom: 20px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
        .h-cls-header { display: flex; align-items: center; gap: 15px; padding: 15px 20px; cursor: pointer; background: var(--bg-surface); transition: background 0.3s; user-select: none; }
        .h-cls-header:hover { background: var(--bg-light); }
        .h-cls-img { width: 50px; height: 50px; object-fit: contain; }
        .h-cls-title { font-size: 18px; font-weight: 700; color: var(--text-primary); margin: 0; }
        .h-cls-body { display: none; padding: 20px; border-top: 1px dashed var(--border-color); background: var(--bg-light); }
        
        .h-track-card { background: var(--bg-color); border: 1px solid var(--border-color); border-radius: var(--radius-sm); margin-bottom: 15px; overflow: hidden; }
        .h-track-header { display: flex; align-items: center; gap: 10px; padding: 12px 15px; cursor: pointer; background: var(--bg-surface); border-bottom: 1px solid transparent; user-select: none; justify-content: space-between; }
        .h-track-header:hover { background: var(--bg-light); }
        .h-track-title { font-size: 16px; font-weight: 700; color: var(--text-primary); margin: 0; display: flex; align-items: center; gap: 8px;}
        .h-track-body { display: none; padding: 15px; border-top: 1px solid var(--border-color); }

        .h-off-section { margin-bottom: 15px; background: var(--bg-surface); border-radius: var(--radius-sm); border: 1px dashed var(--border-color); overflow: hidden; }
        .h-off-title { font-size: 15px; font-weight: 800; color: var(--brand-primary); display: flex; align-items: center; justify-content: space-between; cursor: pointer; user-select: none; padding: 15px; background: rgba(14, 165, 233, 0.05); }
        .h-off-title:hover { background: rgba(14, 165, 233, 0.1); }
        .h-off-title-left { display: flex; align-items: center; gap: 8px; }
        .h-off-body { display: none; padding: 20px; border-top: 1px solid var(--border-color); }
        
        .h-sub-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 15px; }
        .h-sub-card { background: var(--bg-color); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 15px; display: flex; flex-direction: column; gap: 12px; transition: transform 0.2s, box-shadow 0.2s; }
        .h-sub-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-sm); border-color: var(--brand-primary); }
        .h-sub-header { display: flex; align-items: center; gap: 12px; font-weight: 800; font-size: 16px; color: var(--text-primary); }
        .h-sub-icon { width: 30px; height: 30px; object-fit: contain; }
        .h-sub-prof { font-size: 13px; color: var(--text-secondary); display: flex; align-items: center; gap: 6px; font-weight: 600; background: var(--bg-light); padding: 6px 10px; border-radius: 6px; }
        .h-sub-actions { display: flex; gap: 8px; margin-top: auto; padding-top: 12px; border-top: 1px solid var(--border-color); }
        .h-btn { flex: 1; padding: 8px; font-size: 13px; border-radius: 6px; text-align: center; cursor: pointer; text-decoration: none; border: none; font-family: var(--font-family); font-weight: bold; display: flex; align-items: center; justify-content: center; gap: 5px; transition: 0.2s; }
        .h-btn-edit { background: rgba(255, 193, 7, 0.1); color: var(--warning-color); }
        .h-btn-edit:hover { background: var(--warning-color); color: #000; }
        .h-btn-delete { background: rgba(220, 53, 69, 0.1); color: var(--danger-color); }
        .h-btn-delete:hover { background: var(--danger-color); color: #fff; }
        
        .acc-icon { margin-left: auto; transition: transform 0.3s; font-size: 18px; color: var(--text-secondary); }
        .h-expanded > .acc-trigger > .acc-icon { transform: rotate(180deg); color: var(--brand-primary); }
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
                    <img src="../dashboard_design/images/admin_icon_subjects.png" alt="Subjects" style="width: 40px; height: 40px; object-fit: contain;">
                    المواد الدراسية (نظام العرض الهرمي)
                </h2>
                <button class="btn-add-new" onclick="document.getElementById('addModal').style.display='block'">
                    <i class="fa-solid fa-plus"></i> إضافة مادة جديدة
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

            <div class="hierarchical-wrapper skeleton">
                
                <?php if (empty($hierarchy) && empty($orphaned_subjects)): ?>
                    <div style="text-align: center; padding: 40px; background: var(--bg-surface); border-radius: var(--radius-md); border: 1px dashed var(--border-color); color: var(--text-secondary);">
                        <i class="fa-solid fa-sitemap" style="font-size: 40px; margin-bottom: 15px; opacity: 0.5;"></i>
                        <p>لا توجد مواد دراسية مرتبطة بالهيكلة حتى الآن.</p>
                    </div>
                <?php endif; ?>

                <?php foreach ($hierarchy as $div_id => $div_data): ?>
                    <div class="h-div-card">
                        <div class="h-div-header acc-trigger">
                            <?php $div_img = !empty($div_data['image']) ? '../../uploads/divisions/' . htmlspecialchars($div_data['image']) : '../dashboard_design/images/admin_icon_divisions.png'; ?>
                            <img src="<?= $div_img ?>" alt="Division" class="h-div-img" onerror="this.src='../dashboard_design/images/admin_icon_divisions.png'">
                            <h2 class="h-div-title"><?= htmlspecialchars($div_data['name']) ?></h2>
                            <i class="fa-solid fa-chevron-down acc-icon"></i>
                        </div>
                        
                        <div class="h-div-body">
                            <?php foreach ($div_data['classes'] as $cls_id => $cls_data): ?>
                                <div class="h-cls-card">
                                    <div class="h-cls-header acc-trigger">
                                        <?php $cls_img = !empty($cls_data['image']) ? '../../uploads/classes/' . htmlspecialchars($cls_data['image']) : '../dashboard_design/images/admin_icon_classes.svg'; ?>
                                        <img src="<?= $cls_img ?>" alt="Class" class="h-cls-img" onerror="this.src='../dashboard_design/images/admin_icon_classes.svg'">
                                        <h3 class="h-cls-title"><?= htmlspecialchars($cls_data['name']) ?></h3>
                                        <i class="fa-solid fa-chevron-down acc-icon"></i>
                                    </div>
                                    
                                    <div class="h-cls-body">
                                        <?php if (!empty($cls_data['offerings'])): ?>
                                            <?php foreach ($cls_data['offerings'] as $off_id => $off_data): ?>
                                                <div class="h-off-section">
                                                    <div class="h-off-title acc-trigger">
                                                        <div class="h-off-title-left">
                                                            <i class="fa-solid fa-users"></i> 
                                                            <?= htmlspecialchars($off_data['label']) ?>
                                                        </div>
                                                        <i class="fa-solid fa-chevron-down acc-icon"></i>
                                                    </div>
                                                    <div class="h-off-body">
                                                        <div class="h-sub-grid">
                                                            <?php foreach ($off_data['subjects'] as $subject): ?>
                                                                <div class="h-sub-card">
                                                                    <div class="h-sub-header">
                                                                        <img src="../dashboard_design/images/admin_icon_subjects.png" alt="Sub" class="h-sub-icon">
                                                                        <span><?= htmlspecialchars($subject['name']) ?></span>
                                                                    </div>
                                                                    <div class="h-sub-prof">
                                                                        <i class="fa-solid fa-user-tie"></i> <?= htmlspecialchars($subject['professor']) ?>
                                                                    </div>
                                                                    <div class="h-sub-actions">
                                                                        <a href="edit.php?id=<?= $subject['id'] ?>" class="h-btn h-btn-edit"><i class="fa-solid fa-pen"></i> تعديل</a>
                                                                        <form action="delete_process.php" method="POST" style="flex:1; display:flex;" onsubmit="return confirm('تأكيد الحذف؟');">
                                                                            <input type="hidden" name="subject_id" value="<?= $subject['id'] ?>">
                                                                            <button type="submit" class="h-btn h-btn-delete"><i class="fa-solid fa-trash"></i> حذف</button>
                                                                        </form>
                                                                    </div>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>

                                        <?php if (!empty($cls_data['tracks'])): ?>
                                            <?php foreach ($cls_data['tracks'] as $track_id => $track_data): ?>
                                                <div class="h-track-card">
                                                    <div class="h-track-header acc-trigger">
                                                        <div class="h-track-title">
                                                            <i class="fa-solid fa-code-branch text-[var(--text-secondary)]"></i>
                                                            <?= htmlspecialchars($track_data['name']) ?>
                                                        </div>
                                                        <i class="fa-solid fa-chevron-down acc-icon"></i>
                                                    </div>

                                                    <div class="h-track-body">
                                                        <?php foreach ($track_data['offerings'] as $off_id => $off_data): ?>
                                                            <div class="h-off-section">
                                                                <div class="h-off-title acc-trigger">
                                                                    <div class="h-off-title-left">
                                                                        <i class="fa-solid fa-users"></i> 
                                                                        <?= htmlspecialchars($off_data['label']) ?>
                                                                    </div>
                                                                    <i class="fa-solid fa-chevron-down acc-icon"></i>
                                                                </div>
                                                                
                                                                <div class="h-off-body">
                                                                    <div class="h-sub-grid">
                                                                        <?php foreach ($off_data['subjects'] as $subject): ?>
                                                                            <div class="h-sub-card">
                                                                                <div class="h-sub-header">
                                                                                    <img src="../dashboard_design/images/admin_icon_subjects.png" alt="Sub" class="h-sub-icon">
                                                                                    <span><?= htmlspecialchars($subject['name']) ?></span>
                                                                                </div>
                                                                                <div class="h-sub-prof">
                                                                                    <i class="fa-solid fa-user-tie"></i> <?= htmlspecialchars($subject['professor']) ?>
                                                                                </div>
                                                                                <div class="h-sub-actions">
                                                                                    <a href="edit.php?id=<?= $subject['id'] ?>" class="h-btn h-btn-edit"><i class="fa-solid fa-pen"></i> تعديل</a>
                                                                                    <form action="delete_process.php" method="POST" style="flex:1; display:flex;" onsubmit="return confirm('تأكيد الحذف؟');">
                                                                                        <input type="hidden" name="subject_id" value="<?= $subject['id'] ?>">
                                                                                        <button type="submit" class="h-btn h-btn-delete"><i class="fa-solid fa-trash"></i> حذف</button>
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
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if (!empty($orphaned_subjects)): ?>
                    <div class="h-div-card" style="border-color: var(--danger-color);">
                        <div class="h-div-header acc-trigger" style="background: rgba(220,53,69,0.05);">
                            <div class="h-div-img" style="border-color: var(--danger-color); display:flex; align-items:center; justify-content:center;">
                                <i class="fa-solid fa-triangle-exclamation" style="font-size: 40px; color: var(--danger-color);"></i>
                            </div>
                            <h2 class="h-div-title" style="color: var(--danger-color);">مواد غير مرتبطة (تحتاج مراجعة)</h2>
                            <i class="fa-solid fa-chevron-down acc-icon"></i>
                        </div>
                        <div class="h-div-body">
                            <div class="h-sub-grid">
                                <?php foreach ($orphaned_subjects as $orphan): ?>
                                    <div class="h-sub-card" style="border-color: var(--danger-color);">
                                        <div class="h-sub-header">
                                            <img src="../dashboard_design/images/admin_icon_subjects.png" alt="Sub" class="h-sub-icon">
                                            <span><?= htmlspecialchars($orphan['subject_name']) ?></span>
                                        </div>
                                        <div class="h-sub-actions">
                                            <a href="edit.php?id=<?= $orphan['subject_id'] ?>" class="h-btn h-btn-edit"><i class="fa-solid fa-link"></i> ربط الآن</a>
                                            <form action="delete_process.php" method="POST" style="flex:1; display:flex;" onsubmit="return confirm('تأكيد الحذف؟');">
                                                <input type="hidden" name="subject_id" value="<?= $orphan['subject_id'] ?>">
                                                <button type="submit" class="h-btn h-btn-delete"><i class="fa-solid fa-trash"></i> حذف</button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </main>

    <div id="addModal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h2>إضافة مادة دراسية وتحديد مسارها</h2>
                <button class="close-modal" onclick="document.getElementById('addModal').style.display='none'">&times;</button>
            </div>
            <div class="modal-body">
                <form action="add_process.php" method="POST">
                    <div class="form-group">
                        <label class="form-label" for="subject_name">اسم المادة: <span style="color:var(--danger-color)">*</span></label>
                        <input type="text" name="subject_name" id="subject_name" class="form-control" required>
                    </div>

                    <div class="form-group" style="margin-top: 15px;">
                        <label class="form-label" for="division_id">الشعبة: <span style="color:var(--danger-color)">*</span></label>
                        <select name="division_id" id="division_id" class="form-control" required>
                            <option value="">-- اختر الشعبة --</option>
                            <?php foreach($divisions as $div): ?>
                                <option value="<?= $div['division_id'] ?>"><?= htmlspecialchars($div['division_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="class_id">الفصل: <span style="color:var(--danger-color)">*</span></label>
                        <select name="class_id" id="class_id" class="form-control" required disabled>
                            <option value="">-- اختر الشعبة أولاً --</option>
                            <?php foreach($classes as $cls): ?>
                                <option value="<?= $cls['class_id'] ?>"><?= htmlspecialchars($cls['class_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" id="track_group" style="display: none;">
                        <label class="form-label" for="track_id">المسار (إن وجد): <span style="color:var(--danger-color)">*</span></label>
                        <select name="track_id" id="track_id" class="form-control">
                            <option value="">-- اختر المسار --</option>
                        </select>
                    </div>
                    
                    <div class="form-group" style="margin-top: 20px; padding-top: 20px; border-top: 1px dashed var(--border-color);">
                        <label class="form-label" for="offering_ids">الأفواج (المقررات) المتاحة للربط: <span style="color:var(--danger-color)">*</span></label>
                        <select name="offering_ids[]" id="offering_ids" class="form-control" multiple required disabled style="height: 120px; padding: 10px;">
                            <option value="">-- يرجى اختيار الفصل/المسار أولاً --</option>
                        </select>
                        <small style="color: var(--brand-primary); display: block; margin-top: 8px; font-weight: bold;">
                            <i class="fa-solid fa-circle-info"></i> استخدم مفتاح Ctrl (أو Cmd في الماك) لاختيار أكثر من فوج.
                        </small>
                    </div>

                    <button type="submit" class="btn-add-new" style="width: 100%; justify-content: center; margin-top: 15px;">
                        <i class="fa-solid fa-save"></i> حفظ المادة وإنشاء الارتباط
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="../assets/js/admin_global.js"></script>
    <script>
        window.onclick = function(event) {
            var modal = document.getElementById('addModal');
            if (event.target == modal) { modal.style.display = "none"; }
        }

        document.querySelectorAll('.acc-trigger').forEach(trigger => {
            trigger.addEventListener('click', function(e) {
                e.stopPropagation();
                
                const body = this.nextElementSibling;
                const parent = this.parentElement;
                
                if (body.style.display === 'block') {
                    body.style.display = 'none';
                    parent.classList.remove('h-expanded');
                } else {
                    body.style.display = 'block';
                    parent.classList.add('h-expanded');
                }
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const divisionSelect = document.getElementById('division_id');
            const classSelect = document.getElementById('class_id');
            const trackGroup = document.getElementById('track_group');
            const trackSelect = document.getElementById('track_id');
            const offeringSelect = document.getElementById('offering_ids');

            function resetSelect(element, defaultText) {
                element.innerHTML = `<option value="">-- ${defaultText} --</option>`;
                element.disabled = true;
            }

            divisionSelect.addEventListener('change', function() {
                if(this.value) {
                    classSelect.disabled = false;
                    classSelect.value = "";
                } else {
                    classSelect.disabled = true;
                }
                trackGroup.style.display = 'none';
                trackSelect.required = false;
                resetSelect(trackSelect, 'اختر المسار');
                resetSelect(offeringSelect, 'اختر الفصل/المسار أولاً');
            });

            classSelect.addEventListener('change', function() {
                const classId = this.value;
                const divisionId = divisionSelect.value;
                
                resetSelect(trackSelect, 'اختر المسار');
                resetSelect(offeringSelect, 'جاري التحميل...');

                if (!classId) return;

                if (classId === '5' || classId === '6') {
                    trackGroup.style.display = 'block';
                    trackSelect.required = true;
                    trackSelect.disabled = false;
                    
                    fetch('get_tracks.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `division_id=${divisionId}`
                    })
                    .then(res => res.json())
                    .then(data => {
                        trackSelect.innerHTML = `<option value="">-- اختر المسار --</option>`;
                        if (!data.error) {
                            data.forEach(t => trackSelect.innerHTML += `<option value="${t.track_id}">${t.track_name}</option>`);
                        }
                    });
                    
                    resetSelect(offeringSelect, 'الرجاء اختيار المسار أولاً');
                } else {
                    trackGroup.style.display = 'none';
                    trackSelect.required = false;
                    fetchOfferings(divisionId, classId, null);
                }
            });

            trackSelect.addEventListener('change', function() {
                const trackId = this.value;
                const classId = classSelect.value;
                const divisionId = divisionSelect.value;
                
                if (trackId) {
                    fetchOfferings(divisionId, classId, trackId);
                } else {
                    resetSelect(offeringSelect, 'الرجاء اختيار المسار أولاً');
                }
            });

            function fetchOfferings(divisionId, classId, trackId) {
                offeringSelect.disabled = false;
                offeringSelect.innerHTML = `<option value="">جاري التحميل...</option>`;
                
                let bodyData = `division_id=${divisionId}&class_id=${classId}`;
                if (trackId) {
                    bodyData += `&track_id=${trackId}`;
                }

                fetch('get_offerings_ajax.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: bodyData
                })
                .then(res => res.json())
                .then(data => {
                    offeringSelect.innerHTML = '';
                    if (data.error) {
                        offeringSelect.innerHTML = `<option value="" disabled>${data.error}</option>`;
                    } else {
                        data.forEach(o => {
                            let groupName = o.group_name ? `فوج: ${o.group_name}` : 'مقرر عام (بدون أفواج)';
                            offeringSelect.innerHTML += `<option value="${o.offering_id}" style="padding: 8px; border-bottom: 1px solid var(--border-color);">${groupName}</option>`;
                        });
                    }
                })
                .catch(err => {
                    offeringSelect.innerHTML = `<option value="" disabled>حدث خطأ في الاتصال بالخادم</option>`;
                });
            }
        });
    </script>
</body>
</html>