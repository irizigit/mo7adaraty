<?php
// تأمين الـ session لمنع خطأ Already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../config/db_connexion.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

$breadcrumbs = [
    "التواصل" => "",
    "مجموعات الواتساب الشاملة" => ""
];

try {
    $stmt_design = $pdo->query("SELECT asset_key, file_path FROM design_assets WHERE page_section = 'global_icons'");
    $design_settings = [];
    while ($row = $stmt_design->fetch(PDO::FETCH_ASSOC)) {
        $design_settings[$row['asset_key']] = $row['file_path'];
    }

    // تأمين الدالة لمنع خطأ Cannot redeclare
    if (!function_exists('getDesignIcon')) {
        function getDesignIcon($key, $default_path) {
            global $design_settings;
            return !empty($design_settings[$key]) ? '../../' . $design_settings[$key] : '../../' . $default_path;
        }
    }

    // جلب المواسم والشعب لنماذج الإضافة
    $years = $pdo->query("SELECT * FROM academic_years ORDER BY year_label DESC")->fetchAll(PDO::FETCH_ASSOC);
    $divisions = $pdo->query("SELECT * FROM divisions ORDER BY division_name ASC")->fetchAll(PDO::FETCH_ASSOC);

    // 1. جلب جميع المقررات المتاحة (Offerings) وربطها بروابط الواتساب إن وجدت
    $offerings_stmt = $pdo->query("
        SELECT co.offering_id, ay.year_label, d.division_name, c.class_name, t.track_name, g.group_name,
               wg.link_id, wg.group_link, wg.group_name as wa_name
        FROM course_offerings co
        JOIN academic_years ay ON co.academic_year_id = ay.id
        JOIN divisions d ON co.division_id = d.division_id
        JOIN classes c ON co.class_id = c.class_id
        LEFT JOIN tracks t ON co.track_id = t.track_id
        LEFT JOIN `groups` g ON co.group_id = g.group_id
        LEFT JOIN whatsapp_groups wg ON wg.offering_id = co.offering_id
        ORDER BY ay.year_label DESC, d.division_name, c.class_name
    ");
    $offerings = $offerings_stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. جلب المجموعات المستقلة والعامة (التي لا ترتبط بمقرر offering_id IS NULL)
    $indep_stmt = $pdo->query("
        SELECT wg.*, ay.year_label, d.division_name
        FROM whatsapp_groups wg
        LEFT JOIN academic_years ay ON wg.academic_year_id = ay.id
        LEFT JOIN divisions d ON wg.division_id = d.division_id
        WHERE wg.offering_id IS NULL
        ORDER BY wg.link_id DESC
    ");
    $indep_groups = $indep_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("خطأ في قاعدة البيانات: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة مجموعات الواتساب - Mo7adaraty</title>
    <link rel="icon" type="image/svg+xml" href="/mo7adaraty/assets/images/favicon.svg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/admin_global.css">
    <link rel="stylesheet" href="../assets/css/admin_academic.css">
    <style>
        .wa-section-title { font-size: 20px; font-weight: 800; color: var(--text-primary); margin: 30px 0 20px 0; padding-bottom: 10px; border-bottom: 2px solid var(--border-color); display: flex; align-items: center; gap: 10px; }
        .wa-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; }
        .wa-card { background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 20px; display: flex; flex-direction: column; gap: 15px; box-shadow: var(--shadow-sm); transition: 0.3s; position: relative; overflow: hidden; }
        .wa-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-md); border-color: #25D366; }
        .wa-card::before { content: ''; position: absolute; top: 0; right: 0; width: 100%; height: 4px; background: #25D366; }
        
        .wa-header { display: flex; align-items: flex-start; gap: 15px; }
        .wa-icon { width: 50px; height: 50px; object-fit: contain; }
        .wa-title { font-size: 16px; font-weight: 800; color: var(--text-primary); margin: 0 0 8px 0; line-height: 1.4; }
        
        .wa-badges { display: flex; flex-wrap: wrap; gap: 6px; }
        .wa-badge { display: inline-flex; align-items: center; gap: 5px; font-size: 11px; font-weight: bold; padding: 4px 10px; border-radius: 20px; }
        .badge-year { background: rgba(26, 115, 232, 0.1); color: var(--brand-primary); }
        .badge-div { background: rgba(255, 193, 7, 0.1); color: #d39e00; }
        .badge-success { background: rgba(40, 167, 69, 0.1); color: var(--success-color); }
        .badge-danger { background: rgba(220, 53, 69, 0.1); color: var(--danger-color); }
        
        .wa-actions { display: flex; gap: 8px; margin-top: auto; border-top: 1px dashed var(--border-color); padding-top: 15px; }
        .wa-btn { flex: 1; padding: 8px; border-radius: 6px; text-align: center; text-decoration: none; font-size: 13px; font-weight: bold; transition: 0.2s; cursor: pointer; border: none; font-family: var(--font-family); display: flex; align-items: center; justify-content: center; gap: 5px; }
        .btn-add { background: #25D366; color: #fff; }
        .btn-add:hover { background: #128C7E; }
        .btn-edit { background: rgba(255, 193, 7, 0.1); color: var(--warning-color); }
        .btn-edit:hover { background: var(--warning-color); color: #000; }
        .btn-del { background: rgba(220, 53, 69, 0.1); color: var(--danger-color); }
        .btn-del:hover { background: var(--danger-color); color: #fff; }
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
                    <img src="../dashboard_design/images/admin_icon_whatsapp.png" alt="WhatsApp" style="width: 40px; height: 40px; object-fit: contain;">
                    مجموعات الواتساب الشاملة
                </h2>
                <button class="btn-add-new" onclick="openIndepModal()" style="background: #25D366; color: white;">
                    <i class="fa-solid fa-plus"></i> إضافة مجموعة مستقلة / عامة
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

            <h3 class="wa-section-title skeleton"><i class="fa-solid fa-layer-group text-[var(--brand-primary)]"></i> روابط المقررات الأكاديمية المتاحة في المنصة</h3>
            <div class="wa-grid skeleton">
                <?php foreach ($offerings as $off): ?>
                    <?php 
                        $has_link = !empty($off['link_id']); 
                        $track_txt = $off['track_name'] ? "مسار: {$off['track_name']}" : "";
                        $group_txt = $off['group_name'] ? "فوج: {$off['group_name']}" : "";
                        $offering_title = "{$off['division_name']} - {$off['class_name']} " . ($track_txt ? " - $track_txt" : "") . ($group_txt ? " - $group_txt" : "");
                    ?>
                    <div class="wa-card" style="<?php echo !$has_link ? 'border-color: var(--danger-color);' : ''; ?>">
                        <?php if(!$has_link): ?>
                            <style>#card-<?php echo $off['offering_id']; ?>::before { background: var(--danger-color); }</style>
                        <?php endif; ?>
                        
                        <div class="wa-header" id="card-<?php echo $off['offering_id']; ?>">
                            <img src="../dashboard_design/images/admin_icon_whatsapp.png" class="wa-icon" alt="WA" style="<?php echo !$has_link ? 'filter: grayscale(100%); opacity: 0.5;' : ''; ?>">
                            <div>
                                <h3 class="wa-title"><?php echo htmlspecialchars($offering_title); ?></h3>
                                <div class="wa-badges">
                                    <span class="wa-badge badge-year"><i class="fa-regular fa-calendar"></i> <?php echo htmlspecialchars($off['year_label']); ?></span>
                                    <?php if ($has_link): ?>
                                        <span class="wa-badge badge-success"><i class="fa-solid fa-check"></i> مربوطة</span>
                                    <?php else: ?>
                                        <span class="wa-badge badge-danger"><i class="fa-solid fa-xmark"></i> لا يوجد رابط</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="wa-actions">
                            <?php if ($has_link): ?>
                                <a href="<?php echo htmlspecialchars($off['group_link']); ?>" target="_blank" class="wa-btn btn-join" style="background: var(--info-color);"><i class="fa-solid fa-eye"></i> معاينة</a>
                                <button onclick="openOfferingModal(<?php echo $off['offering_id']; ?>, '<?php echo htmlspecialchars(addslashes($offering_title)); ?>', <?php echo $off['link_id']; ?>, '<?php echo htmlspecialchars(addslashes($off['wa_name'])); ?>', '<?php echo htmlspecialchars(addslashes($off['group_link'])); ?>')" class="wa-btn btn-edit"><i class="fa-solid fa-pen"></i> تعديل</button>
                                <form action="process.php" method="POST" style="margin:0; flex:1;" onsubmit="return confirm('تأكيد حذف الرابط من هذا المقرر؟');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="link_id" value="<?php echo $off['link_id']; ?>">
                                    <button type="submit" class="wa-btn btn-del" style="width:100%;"><i class="fa-solid fa-trash"></i> حذف</button>
                                </form>
                            <?php else: ?>
                                <button onclick="openOfferingModal(<?php echo $off['offering_id']; ?>, '<?php echo htmlspecialchars(addslashes($offering_title)); ?>')" class="wa-btn btn-add" style="flex: 100%;">
                                    <i class="fa-brands fa-whatsapp"></i> إضافة رابط لهذا المقرر
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <h3 class="wa-section-title skeleton" style="margin-top: 50px;"><i class="fa-solid fa-globe text-[#25D366]"></i> المجموعات المستقلة (للشعب) والعامة (للجميع)</h3>
            <div class="wa-grid skeleton">
                <?php if (empty($indep_groups)): ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 30px; background: var(--bg-surface); border-radius: var(--radius-md); border: 1px dashed var(--border-color); color: var(--text-secondary);">
                        <p>لا توجد مجموعات مستقلة أو عامة حالياً.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($indep_groups as $wg): ?>
                        <div class="wa-card">
                            <div class="wa-header">
                                <img src="../dashboard_design/images/admin_icon_whatsapp.png" class="wa-icon" alt="WA">
                                <div>
                                    <h3 class="wa-title"><?php echo htmlspecialchars($wg['group_name']); ?></h3>
                                    <div class="wa-badges">
                                        <span class="wa-badge badge-year"><i class="fa-regular fa-calendar"></i> <?php echo htmlspecialchars($wg['year_label'] ?? 'موسم غير محدد'); ?></span>
                                        <?php if (empty($wg['division_id'])): ?>
                                            <span class="wa-badge badge-success"><i class="fa-solid fa-globe"></i> مجموعة عامة للجميع</span>
                                        <?php else: ?>
                                            <span class="wa-badge badge-div"><i class="fa-solid fa-bullseye"></i> خاصة بـ: <?php echo htmlspecialchars($wg['division_name']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="wa-actions">
                                <a href="<?php echo htmlspecialchars($wg['group_link']); ?>" target="_blank" class="wa-btn btn-join"><i class="fa-solid fa-eye"></i> معاينة</a>
                                <form action="process.php" method="POST" style="margin: 0; flex: 1;" onsubmit="return confirm('تأكيد حذف هذه المجموعة؟');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="link_id" value="<?php echo $wg['link_id']; ?>">
                                    <button type="submit" class="wa-btn btn-del" style="width:100%;"><i class="fa-solid fa-trash"></i> حذف</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <div id="offeringModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h2>إدارة رابط المقرر</h2>
                <button class="close-modal" onclick="document.getElementById('offeringModal').style.display='none'">&times;</button>
            </div>
            <div class="modal-body">
                <div style="background: var(--bg-light); padding: 15px; border-radius: var(--radius-sm); border: 1px dashed var(--border-color); margin-bottom: 20px; font-weight: bold; color: var(--brand-primary); font-size: 14px;">
                    <i class="fa-solid fa-layer-group"></i> المقرر: <span id="offering_title_display"></span>
                </div>
                <form action="process.php" method="POST">
                    <input type="hidden" name="action" value="save_offering">
                    <input type="hidden" name="offering_id" id="modal_offering_id">
                    <input type="hidden" name="link_id" id="modal_link_id">
                    
                    <div class="form-group">
                        <label class="form-label">اسم المجموعة (كما سيظهر للطلاب): <span style="color:var(--danger-color)">*</span></label>
                        <input type="text" name="group_name" id="modal_group_name" class="form-control" required placeholder="مثال: مجموعة الفقه - الفصل الأول">
                    </div>
                    <div class="form-group">
                        <label class="form-label">رابط الدعوة (Invite Link): <span style="color:var(--danger-color)">*</span></label>
                        <input type="url" name="group_link" id="modal_group_link" class="form-control" required dir="ltr" style="text-align:left;" placeholder="https://chat.whatsapp.com/...">
                    </div>
                    <button type="submit" class="btn-add-new" style="width: 100%; justify-content: center; background: #25D366; color: white;">
                        <i class="fa-solid fa-save"></i> حفظ الرابط للمقرر
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div id="indepModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h2>إضافة مجموعة مستقلة / عامة</h2>
                <button class="close-modal" onclick="document.getElementById('indepModal').style.display='none'">&times;</button>
            </div>
            <div class="modal-body">
                <form action="process.php" method="POST">
                    <input type="hidden" name="action" value="save_independent">
                    
                    <div class="form-group">
                        <label class="form-label">اسم المجموعة: <span style="color:var(--danger-color)">*</span></label>
                        <input type="text" name="group_name" class="form-control" required placeholder="مثال: مجموعة إعلانات الكلية">
                    </div>
                    <div class="form-group">
                        <label class="form-label">رابط الدعوة (Invite Link): <span style="color:var(--danger-color)">*</span></label>
                        <input type="url" name="group_link" class="form-control" required dir="ltr" style="text-align:left;" placeholder="https://chat.whatsapp.com/...">
                    </div>
                    <div class="form-group" style="border-top: 1px dashed var(--border-color); padding-top: 15px; margin-top: 15px;">
                        <label class="form-label">الموسم الجامعي: <span style="color:var(--danger-color)">*</span></label>
                        <select name="academic_year_id" class="form-control" required>
                            <?php foreach ($years as $y): ?>
                                <option value="<?php echo $y['id']; ?>" <?php echo $y['is_current'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($y['year_label']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">تخصيص الشعبة (اختياري - مجموعة مستقلة):</label>
                        <select name="division_id" class="form-control">
                            <option value="">-- عامة لجميع الشعب في هذا الموسم --</option>
                            <?php foreach ($divisions as $d): ?>
                                <option value="<?php echo $d['division_id']; ?>"><?php echo htmlspecialchars($d['division_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small style="color: var(--text-secondary); font-weight: bold; margin-top: 5px; display: block;">إذا لم تحدد شعبة، سيظهر الرابط لجميع الطلاب كإعلان عام.</small>
                    </div>
                    <button type="submit" class="btn-add-new" style="width: 100%; justify-content: center; background: #25D366; color: white; margin-top: 15px;">
                        <i class="fa-solid fa-save"></i> حفظ ونشر المجموعة
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="../assets/js/admin_global.js"></script>
    <script>
        window.onclick = function(event) {
            if (event.target == document.getElementById('offeringModal')) { document.getElementById('offeringModal').style.display = "none"; }
            if (event.target == document.getElementById('indepModal')) { document.getElementById('indepModal').style.display = "none"; }
        }

        function openOfferingModal(offeringId, offeringTitle, linkId = '', groupName = '', groupLink = '') {
            document.getElementById('offering_title_display').textContent = offeringTitle;
            document.getElementById('modal_offering_id').value = offeringId;
            document.getElementById('modal_link_id').value = linkId;
            document.getElementById('modal_group_name').value = groupName;
            document.getElementById('modal_group_link').value = groupLink;
            document.getElementById('offeringModal').style.display = 'block';
        }

        function openIndepModal() {
            document.getElementById('indepModal').style.display = 'block';
        }
    </script>
</body>
</html>