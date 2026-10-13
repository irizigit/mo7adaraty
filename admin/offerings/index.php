<?php
session_start();
require_once '../../config/db_connexion.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

$breadcrumbs = [
    "الهيكلة الأكاديمية" => "",
    "إدارة المقررات المتاحة" => ""
];

try {
    // التأكد من وجود حقل status في جدول course_offerings لتشغيل ميزة التفعيل/التعطيل
    try {
        $pdo->query("SELECT status FROM course_offerings LIMIT 1");
    } catch (PDOException $e) {
        $pdo->exec("ALTER TABLE course_offerings ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active'");
    }

    $stmt_design = $pdo->query("SELECT asset_key, file_path FROM design_assets WHERE page_section = 'global_icons'");
    $design_settings = [];
    while ($row = $stmt_design->fetch(PDO::FETCH_ASSOC)) {
        $design_settings[$row['asset_key']] = $row['file_path'];
    }

    function getDesignIcon($key, $default_path) {
        global $design_settings;
        return !empty($design_settings[$key]) ? '../../' . $design_settings[$key] : '../../' . $default_path;
    }

    // جلب القوائم للفلترة ونموذج الإضافة
    $years = $pdo->query("SELECT * FROM academic_years ORDER BY year_label DESC")->fetchAll(PDO::FETCH_ASSOC);
    $divisions = $pdo->query("SELECT * FROM divisions ORDER BY division_name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $classes = $pdo->query("SELECT * FROM classes ORDER BY class_name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $tracks = $pdo->query("SELECT * FROM tracks ORDER BY track_name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $groups = $pdo->query("SELECT * FROM `groups` ORDER BY group_name ASC")->fetchAll(PDO::FETCH_ASSOC);

    // تحديد الفلاتر الحالية (الافتراضي هو العام الحالي)
    $stmt_curr_year = $pdo->query("SELECT id FROM academic_years WHERE is_current = 1 LIMIT 1");
    $current_year_id = $stmt_curr_year->fetchColumn();

    $filter_year = filter_input(INPUT_GET, 'year_id', FILTER_VALIDATE_INT) ?: $current_year_id;
    $filter_division = filter_input(INPUT_GET, 'division_id', FILTER_VALIDATE_INT) ?: '';

    // بناء الاستعلام الهرمي للمقررات بناءً على الفلاتر
    $query = "
        SELECT 
            co.offering_id, co.status,
            y.id as year_id, y.year_label, y.is_current,
            d.division_id, d.division_name, d.division_image,
            c.class_name, c.class_image,
            t.track_name,
            g.group_name
        FROM course_offerings co
        JOIN academic_years y ON co.academic_year_id = y.id
        JOIN divisions d ON co.division_id = d.division_id
        JOIN classes c ON co.class_id = c.class_id
        LEFT JOIN tracks t ON co.track_id = t.track_id
        LEFT JOIN `groups` g ON co.group_id = g.group_id
        WHERE 1=1
    ";
    $params = [];
    
    if ($filter_year) {
        $query .= " AND co.academic_year_id = ?";
        $params[] = $filter_year;
    }
    if ($filter_division) {
        $query .= " AND co.division_id = ?";
        $params[] = $filter_division;
    }
    
    $query .= " ORDER BY y.year_label DESC, d.division_name ASC, c.class_name ASC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $raw_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // تجميع البيانات هرمياً: العام -> الشعبة -> المقررات
    $hierarchy = [];
    foreach ($raw_data as $row) {
        $y_id = $row['year_id'];
        $d_id = $row['division_id'];

        if (!isset($hierarchy[$y_id])) {
            $hierarchy[$y_id] = [
                'label' => $row['year_label'],
                'is_current' => $row['is_current'],
                'divisions' => []
            ];
        }

        if (!isset($hierarchy[$y_id]['divisions'][$d_id])) {
            $hierarchy[$y_id]['divisions'][$d_id] = [
                'name' => $row['division_name'],
                'image' => $row['division_image'],
                'offerings' => []
            ];
        }

        $hierarchy[$y_id]['divisions'][$d_id]['offerings'][] = [
            'id' => $row['offering_id'],
            'class_name' => $row['class_name'],
            'class_image' => $row['class_image'],
            'track_name' => $row['track_name'],
            'group_name' => $row['group_name'],
            'status' => $row['status']
        ];
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
    <title>إدارة المقررات المتاحة - Mo7adaraty</title>
    <link rel="icon" type="image/svg+xml" href="/mo7adaraty/assets/images/favicon.svg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/admin_global.css">
    <link rel="stylesheet" href="../assets/css/admin_academic.css">
    
    <style>
        .hierarchical-wrapper { display: flex; flex-direction: column; gap: 20px; }
        
        /* مستوى الشعبة */
        .h-div-card { background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); overflow: hidden; transition: all 0.3s; }
        .h-div-header { display: flex; align-items: center; gap: 20px; padding: 20px; cursor: pointer; background: var(--bg-light); transition: background 0.3s; user-select: none; }
        .h-div-header:hover { background: var(--border-color); }
        .h-div-img { width: 90px; height: 90px; object-fit: contain; background: #ffffff; padding: 5px; border-radius: 12px; border: 1px solid var(--border-color); box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .h-div-title { font-size: 22px; font-weight: 900; color: var(--brand-primary); margin: 0; }
        .h-div-body { display: none; padding: 25px; border-top: 2px solid var(--brand-primary); background: var(--bg-color); }
        
        /* مستوى المقررات (البطاقات) */
        .h-offering-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .h-offering-card { background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 20px; display: flex; flex-direction: column; gap: 15px; transition: transform 0.2s, box-shadow 0.2s; position: relative; }
        .h-offering-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-sm); border-color: var(--brand-primary); }
        
        .h-offering-header { display: flex; align-items: center; gap: 15px; }
        .h-offering-img { width: 60px; height: 60px; object-fit: contain; }
        .h-offering-info h4 { margin: 0 0 5px 0; font-size: 18px; font-weight: 800; color: var(--text-primary); }
        
        .h-badges-container { display: flex; flex-wrap: wrap; gap: 8px; }
        .h-badge { font-size: 12px; font-weight: bold; padding: 4px 10px; border-radius: 6px; display: inline-flex; align-items: center; gap: 5px; }
        .badge-track { background: rgba(26, 115, 232, 0.1); color: var(--brand-primary); border: 1px solid rgba(26, 115, 232, 0.2); }
        .badge-group { background: rgba(255, 193, 7, 0.1); color: #d39e00; border: 1px solid rgba(255, 193, 7, 0.2); }
        
        .h-sub-actions { display: flex; gap: 8px; margin-top: auto; padding-top: 15px; border-top: 1px solid var(--border-color); align-items: center; }
        
        /* Toggle Switch (AJAX Status) */
        .status-toggle { display: flex; align-items: center; gap: 10px; margin-right: auto; }
        .switch { position: relative; display: inline-block; width: 46px; height: 24px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: var(--border-color); transition: .4s; border-radius: 24px; }
        .slider:before { position: absolute; content: ""; height: 16px; width: 16px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: var(--success-color); }
        input:checked + .slider:before { transform: translateX(22px); }
        
        /* الفلتر */
        .filter-bar { background: var(--bg-surface); padding: 20px; border-radius: var(--radius-lg); border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); margin-bottom: 25px; display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap; }
        
        .acc-icon { margin-left: auto; transition: transform 0.3s; font-size: 20px; color: var(--text-secondary); }
        .h-expanded .acc-icon { transform: rotate(180deg); color: var(--brand-primary); }
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
                    <img src="../dashboard_design/images/admin_icon_offerings.png" alt="Offerings" style="width: 40px; height: 40px; object-fit: contain;">
                    إدارة المقررات المتاحة (Offerings)
                </h2>
                <button class="btn-add-new" onclick="document.getElementById('addModal').style.display='block'">
                    <i class="fa-solid fa-plus"></i> إنشاء مقرر جديد
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

            <div class="filter-bar skeleton">
                <form action="" method="GET" style="display: flex; gap: 15px; flex-wrap: wrap; width: 100%;">
                    <div style="flex: 1; min-width: 200px;">
                        <label class="form-label">تصفية حسب العام الدراسي:</label>
                        <select name="year_id" class="form-control" onchange="this.form.submit()">
                            <option value="">-- كل الأعوام --</option>
                            <?php foreach ($years as $y): ?>
                                <option value="<?php echo $y['id']; ?>" <?php echo ($filter_year == $y['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($y['year_label']) . ($y['is_current'] ? ' (الحالي)' : ''); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="flex: 1; min-width: 200px;">
                        <label class="form-label">تصفية حسب الشعبة:</label>
                        <select name="division_id" class="form-control" onchange="this.form.submit()">
                            <option value="">-- كل الشعب --</option>
                            <?php foreach ($divisions as $d): ?>
                                <option value="<?php echo $d['division_id']; ?>" <?php echo ($filter_division == $d['division_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($d['division_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>

            <div class="hierarchical-wrapper skeleton">
                
                <?php if (empty($hierarchy)): ?>
                    <div style="text-align: center; padding: 40px; background: var(--bg-surface); border-radius: var(--radius-md); border: 1px dashed var(--border-color); color: var(--text-secondary);">
                        <i class="fa-solid fa-folder-open" style="font-size: 40px; margin-bottom: 15px; opacity: 0.5;"></i>
                        <p>لا توجد مقررات متاحة تطابق خيارات البحث.</p>
                    </div>
                <?php endif; ?>

                <?php foreach ($hierarchy as $y_id => $year_data): ?>
                    <div style="margin-bottom: 30px;">
                        <h2 style="color: var(--text-primary); border-right: 4px solid var(--brand-primary); padding-right: 15px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                            <i class="fa-regular fa-calendar-check"></i> عام: <?php echo htmlspecialchars($year_data['label']); ?>
                            <?php if ($year_data['is_current']): ?>
                                <span style="font-size: 12px; background: var(--success-color); color: white; padding: 4px 10px; border-radius: 20px;">حالي</span>
                            <?php endif; ?>
                        </h2>

                        <?php foreach ($year_data['divisions'] as $d_id => $div_data): ?>
                            <div class="h-div-card" style="margin-bottom: 15px;">
                                <div class="h-div-header acc-trigger">
                                    <?php $div_img = !empty($div_data['image']) ? '../../uploads/divisions/' . htmlspecialchars($div_data['image']) : '../dashboard_design/images/admin_icon_divisions.png'; ?>
                                    <img src="<?= $div_img ?>" alt="Division" class="h-div-img" onerror="this.src='../dashboard_design/images/admin_icon_divisions.png'">
                                    <h3 class="h-div-title"><?= htmlspecialchars($div_data['name']) ?></h3>
                                    <i class="fa-solid fa-chevron-down acc-icon"></i>
                                </div>
                                
                                <div class="h-div-body">
                                    <div class="h-offering-grid">
                                        <?php foreach ($div_data['offerings'] as $off): ?>
                                            <div class="h-offering-card">
                                                <div class="h-offering-header">
                                                    <?php $cls_img = !empty($off['class_image']) ? '../../uploads/classes/' . htmlspecialchars($off['class_image']) : '../dashboard_design/images/admin_icon_classes.svg'; ?>
                                                    <img src="<?= $cls_img ?>" alt="Class" class="h-offering-img" onerror="this.src='../dashboard_design/images/admin_icon_classes.svg'">
                                                    <div class="h-offering-info">
                                                        <h4><?= htmlspecialchars($off['class_name']) ?></h4>
                                                        <div class="h-badges-container">
                                                            <?php if ($off['track_name']): ?>
                                                                <span class="h-badge badge-track"><i class="fa-solid fa-code-branch"></i> <?= htmlspecialchars($off['track_name']) ?></span>
                                                            <?php endif; ?>
                                                            <?php if ($off['group_name']): ?>
                                                                <span class="h-badge badge-group"><i class="fa-solid fa-users"></i> <?= htmlspecialchars($off['group_name']) ?></span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="h-sub-actions">
                                                    <a href="edit.php?id=<?= $off['id'] ?>" class="btn-card-action btn-edit" style="border: 1px solid var(--border-color); border-radius: 6px; padding: 6px 12px; font-size: 13px;">
                                                        <i class="fa-solid fa-pen"></i> تعديل
                                                    </a>
                                                    
                                                    <form action="delete_process.php" method="POST" style="margin: 0;" onsubmit="return confirm('تحذير: سيتم حذف المقرر وجميع المحاضرات المرتبطة به نهائياً! هل أنت متأكد؟');">
                                                        <input type="hidden" name="offering_id" value="<?= $off['id'] ?>">
                                                        <button type="submit" class="btn-card-action btn-delete" style="border: 1px solid var(--border-color); border-radius: 6px; padding: 6px 12px; font-size: 13px;">
                                                            <i class="fa-solid fa-trash"></i> حذف
                                                        </button>
                                                    </form>
                                                    
                                                    <div class="status-toggle">
                                                        <label class="switch" title="تفعيل / تعطيل المقرر">
                                                            <input type="checkbox" class="toggle-status-btn" data-id="<?= $off['id'] ?>" <?= ($off['status'] === 'active') ? 'checked' : '' ?>>
                                                            <span class="slider"></span>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <div id="addModal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h2>إنشاء وربط مقرر جديد</h2>
                <button class="close-modal" onclick="document.getElementById('addModal').style.display='none'">&times;</button>
            </div>
            <div class="modal-body">
                <form action="add_process.php" method="POST">
                    <div class="form-group">
                        <label class="form-label" for="academic_year_id">العام الدراسي: <span style="color:var(--danger-color)">*</span></label>
                        <select name="academic_year_id" id="academic_year_id" class="form-control" required>
                            <?php foreach ($years as $y): ?>
                                <option value="<?php echo $y['id']; ?>" <?php echo ($y['is_current']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($y['year_label']) . ($y['is_current'] ? ' (الحالي)' : ''); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label class="form-label" for="division_id">الشعبة: <span style="color:var(--danger-color)">*</span></label>
                            <select name="division_id" id="division_id" class="form-control" required>
                                <option value="" disabled selected>-- اختر الشعبة --</option>
                                <?php foreach ($divisions as $d): ?>
                                    <option value="<?php echo $d['division_id']; ?>"><?php echo htmlspecialchars($d['division_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="class_id">الفصل: <span style="color:var(--danger-color)">*</span></label>
                            <select name="class_id" id="class_id" class="form-control" required>
                                <option value="" disabled selected>-- اختر الفصل --</option>
                                <?php foreach ($classes as $c): ?>
                                    <option value="<?php echo $c['class_id']; ?>"><?php echo htmlspecialchars($c['class_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label class="form-label" for="track_id">المسار (اختياري):</label>
                            <select name="track_id" id="track_id" class="form-control">
                                <option value="">-- عام (بدون مسار) --</option>
                                <?php foreach ($tracks as $t): ?>
                                    <option value="<?php echo $t['track_id']; ?>"><?php echo htmlspecialchars($t['track_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="group_id">الفوج (اختياري):</label>
                            <select name="group_id" id="group_id" class="form-control">
                                <option value="">-- عام (بدون فوج) --</option>
                                <?php foreach ($groups as $g): ?>
                                    <option value="<?php echo $g['group_id']; ?>"><?php echo htmlspecialchars($g['group_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 10px;">
                        <label class="form-label" for="status">حالة المقرر:</label>
                        <select name="status" id="status" class="form-control">
                            <option value="active" selected>نشط (يظهر للطلاب)</option>
                            <option value="inactive">غير نشط (مخفي)</option>
                        </select>
                    </div>

                    <button type="submit" class="btn-add-new" style="width: 100%; justify-content: center; margin-top: 15px;">
                        <i class="fa-solid fa-save"></i> إنشاء وربط المقرر
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/admin_global.js"></script>
    <script>
        // إغلاق المودال
        window.onclick = function(event) {
            var modal = document.getElementById('addModal');
            if (event.target == modal) { modal.style.display = "none"; }
        }

        // الأكورديون لفتح الشعب
        document.querySelectorAll('.acc-trigger').forEach(trigger => {
            trigger.addEventListener('click', function() {
                const body = this.nextElementSibling;
                if (body.style.display === 'block') {
                    body.style.display = 'none';
                    this.parentElement.classList.remove('h-expanded');
                } else {
                    body.style.display = 'block';
                    this.parentElement.classList.add('h-expanded');
                }
            });
        });

        // AJAX لتبديل حالة المقرر (نشط / غير نشط)
        $(document).ready(function() {
            $('.toggle-status-btn').on('change', function() {
                var isChecked = $(this).is(':checked');
                var offeringId = $(this).data('id');
                var newStatus = isChecked ? 'active' : 'inactive';

                $.ajax({
                    url: 'toggle_status.php',
                    type: 'POST',
                    data: { offering_id: offeringId, status: newStatus },
                    dataType: 'json',
                    success: function(response) {
                        if (!response.success) {
                            alert(response.message);
                            // إعادة الزر لحالته السابقة إذا فشل التحديث
                            $(this).prop('checked', !isChecked);
                        }
                    }.bind(this),
                    error: function() {
                        alert('فشل الاتصال بالخادم.');
                        $(this).prop('checked', !isChecked);
                    }.bind(this)
                });
            });
        });
    </script>
</body>
</html>