<?php
session_start();
require_once '../../config/db_connexion.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

$breadcrumbs = [
    "الهيكلة الأكاديمية" => "",
    "المسارات والتخصصات" => ""
];

try {
    // جلب المسارات مع تجميعها حسب الشعبة
    $stmt = $pdo->query("
        SELECT t.*, d.division_name, d.division_image,
        (SELECT COUNT(*) FROM course_offerings WHERE track_id = t.track_id) as offerings_count 
        FROM tracks t 
        JOIN divisions d ON t.division_id = d.division_id
        ORDER BY d.division_name ASC, t.track_name ASC
    ");
    $allTracks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // تنظيم البيانات: مصفوفة الشعب تحتوي على مساراتها
    $divisionsWithTracks = [];
    foreach ($allTracks as $track) {
        $divId = $track['division_id'];
        if (!isset($divisionsWithTracks[$divId])) {
            $divisionsWithTracks[$divId] = [
                'division_name' => $track['division_name'],
                'division_image' => $track['division_image'] ?? 'default_division.png',
                'tracks' => []
            ];
        }
        $divisionsWithTracks[$divId]['tracks'][] = $track;
    }

    // جلب الشعب لنموذج الإضافة
    $divisions = $pdo->query("SELECT * FROM divisions ORDER BY division_name")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("خطأ في قاعدة البيانات: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المسارات - Mo7adaraty</title>
    <link rel="icon" type="image/svg+xml" href="/mo7adaraty/assets/images/favicon.svg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/admin_global.css">
    <link rel="stylesheet" href="../assets/css/admin_academic.css">
    <style>
        .division-section {
            background: var(--bg-surface);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-color);
            margin-bottom: 20px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }
        .division-header {
            padding: 15px 20px;
            background: rgba(14, 165, 233, 0.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            user-select: none;
            border-bottom: 1px solid transparent;
            transition: var(--transition-base);
        }
        .division-header:hover {
            background: rgba(14, 165, 233, 0.1);
        }
        .division-header.active {
            border-bottom-color: var(--border-color);
        }
        .division-title-area {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .division-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--brand-primary);
            background: white;
            padding: 2px;
        }
        .division-name {
            margin: 0;
            font-size: 1.1rem;
            color: var(--text-primary);
            font-weight: bold;
        }
        .division-badge {
            background: var(--brand-primary);
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: normal;
            margin-right: 10px;
        }
        .toggle-icon {
            color: var(--text-secondary);
            transition: transform 0.3s ease;
        }
        .division-header.active .toggle-icon {
            transform: rotate(180deg);
        }
        .tracks-container {
            display: none;
            padding: 20px;
            background: var(--bg-body);
        }
        .tracks-container.active {
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
            
            <div class="academic-header-actions skeleton">
                <h2 style="margin: 0; color: var(--text-primary); display: flex; align-items: center; gap: 10px;">
                    <img src="../dashboard_design/images/admin_icon_tracks.png" alt="Tracks" style="width: 40px; height: 40px; object-fit: contain;">
                    المسارات والتخصصات
                </h2>
                <button class="btn-add-new" onclick="document.getElementById('addModal').style.display='block'">
                    <i class="fa-solid fa-plus"></i> إضافة مسار جديد
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

            <div class="skeleton">
                <?php if (empty($divisionsWithTracks)): ?>
                    <div style="text-align: center; padding: 40px; background: var(--bg-surface); border-radius: var(--radius-md); border: 1px dashed var(--border-color); color: var(--text-secondary);">
                        <i class="fa-solid fa-code-branch" style="font-size: 40px; margin-bottom: 15px; opacity: 0.5;"></i>
                        <p>لا توجد مسارات مضافة حتى الآن.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($divisionsWithTracks as $divId => $data): ?>
                        <div class="division-section">
                            <div class="division-header" onclick="toggleTracks('div_<?php echo $divId; ?>')">
                                <div class="division-title-area">
                                    <img src="../../uploads/divisions/<?php echo htmlspecialchars($data['division_image']); ?>" onerror="this.src='../dashboard_design/images/admin_icon_divisions.png'" alt="Division" class="division-icon">
                                    <h3 class="division-name">
                                        <?php echo htmlspecialchars($data['division_name']); ?>
                                        <span class="division-badge"><?php echo count($data['tracks']); ?> مسارات</span>
                                    </h3>
                                </div>
                                <i class="fa-solid fa-chevron-down toggle-icon"></i>
                            </div>
                            
                            <div id="div_<?php echo $divId; ?>" class="tracks-container">
                                <div class="academic-grid" style="grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));">
                                    <?php foreach ($data['tracks'] as $track): ?>
                                        <div class="academic-card" style="box-shadow: none; border: 1px solid var(--border-color);">
                                            <div class="card-img-wrapper" style="height: 100px; background: rgba(14, 165, 233, 0.05);">
                                                <img src="../dashboard_design/images/admin_icon_tracks.png" alt="Tracks Icon" style="width: 50px;">
                                            </div>
                                            <div class="card-content">
                                                <h3 class="card-title" style="font-size: 1rem;"><?php echo htmlspecialchars($track['track_name']); ?></h3>
                                                <div class="card-stats" style="justify-content: center; margin-top: 10px;">
                                                    <span class="stat-badge" title="عدد المقررات">
                                                        <i class="fa-solid fa-book-open" style="color: var(--brand-primary);"></i>
                                                        <?php echo $track['offerings_count']; ?> مقررات
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="card-actions">
                                                <a href="edit.php?id=<?php echo $track['track_id']; ?>" class="btn-card-action btn-edit">
                                                    <i class="fa-solid fa-pen"></i>
                                                </a>
                                                <form action="delete_process.php" method="POST" style="flex: 1; display: flex;" onsubmit="return confirm('هل أنت متأكد من حذف هذا المسار؟');">
                                                    <input type="hidden" name="track_id" value="<?php echo $track['track_id']; ?>">
                                                    <button type="submit" class="btn-card-action btn-delete" style="width: 100%;">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
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
                <h2>إضافة مسار جديد</h2>
                <button class="close-modal" onclick="document.getElementById('addModal').style.display='none'">&times;</button>
            </div>
            <div class="modal-body">
                <form action="add_process.php" method="POST">
                    <div class="form-group">
                        <label class="form-label" for="track_name">اسم المسار (مثال: مسار العقيدة): <span style="color:var(--danger-color)">*</span></label>
                        <input type="text" name="track_name" id="track_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="division_id">الشعبة التابع لها: <span style="color:var(--danger-color)">*</span></label>
                        <select name="division_id" id="division_id" class="form-control" required>
                            <option value="" disabled selected>-- اختر الشعبة --</option>
                            <?php foreach ($divisions as $div): ?>
                                <option value="<?php echo $div['division_id']; ?>"><?php echo htmlspecialchars($div['division_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn-add-new" style="width: 100%; justify-content: center;">
                        <i class="fa-solid fa-save"></i> حفظ المسار
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="../assets/js/admin_global.js"></script>
    <script>
        window.onclick = function(event) {
            var modal = document.getElementById('addModal');
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }

        function toggleTracks(containerId) {
            const container = document.getElementById(containerId);
            const header = container.previousElementSibling;
            
            // إغلاق جميع الأقسام المفتوحة الأخرى (اختياري، إذا كنت تريد فتح قسم واحد فقط في كل مرة)
            
            document.querySelectorAll('.tracks-container').forEach(c => {
                if (c.id !== containerId && c.classList.contains('active')) {
                    c.classList.remove('active');
                    c.previousElementSibling.classList.remove('active');
                }
            });
            

            container.classList.toggle('active');
            header.classList.toggle('active');
        }
    </script>
</body>
</html>