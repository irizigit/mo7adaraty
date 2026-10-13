<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية الصفحة: التحقق من الصلاحيات بدقة
if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

$breadcrumbs = [
    "الهيكلة الأكاديمية" => "../index.php",
    "استعمالات الزمن" => ""
];

// معالجة الحفظ والتحديث عبر AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_drive_url') {
    header('Content-Type: application/json');
    $offering_id = filter_input(INPUT_POST, 'offering_id', FILTER_VALIDATE_INT);
    $timetable_url = trim($_POST['timetable_url'] ?? '');

    if (!$offering_id) {
        echo json_encode(['success' => false, 'message' => 'معرف الفوج غير صالح.']);
        exit();
    }

    try {
        $stmt = $pdo->prepare("UPDATE course_offerings SET timetable_url = ? WHERE offering_id = ?");
        $stmt->execute([!empty($timetable_url) ? $timetable_url : null, $offering_id]);
        echo json_encode(['success' => true, 'message' => 'تم حفظ رابط استعمال الزمن بنجاح.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()]);
    }
    exit();
}

// معالجة الحفظ عبر النموذج التقليدي من المودال
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_from_modal'])) {
    $division_id = filter_input(INPUT_POST, 'division_id', FILTER_VALIDATE_INT);
    $class_id = filter_input(INPUT_POST, 'class_id', FILTER_VALIDATE_INT);
    $track_id = !empty($_POST['track_id']) ? filter_input(INPUT_POST, 'track_id', FILTER_VALIDATE_INT) : null;
    $group_id = filter_input(INPUT_POST, 'group_id', FILTER_VALIDATE_INT);
    $timetable_url = trim($_POST['timetable_url'] ?? '');

    if ($division_id && $class_id && $group_id) {
        try {
            $sql_find = "SELECT offering_id FROM course_offerings WHERE division_id = ? AND class_id = ? AND group_id = ?";
            $params = [$division_id, $class_id, $group_id];
            if ($track_id) {
                $sql_find .= " AND track_id = ?";
                $params[] = $track_id;
            } else {
                $sql_find .= " AND track_id IS NULL";
            }
            $stmt_find = $pdo->prepare($sql_find);
            $stmt_find->execute($params);
            $offering_id = $stmt_find->fetchColumn();

            if ($offering_id) {
                $stmt_up = $pdo->prepare("UPDATE course_offerings SET timetable_url = ? WHERE offering_id = ?");
                $stmt_up->execute([!empty($timetable_url) ? $timetable_url : null, $offering_id]);
                $_SESSION['msg'] = "تم تحديث رابط استعمال الزمن للفوج بنجاح.";
            } else {
                $_SESSION['error'] = "لم يتم العثور على مقرر مطابق لهذا التحديد.";
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "خطأ في قاعدة البيانات: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "يرجى تعبئة جميع الحقول الإلزامية.";
    }
    header("Location: index.php");
    exit();
}

// دالة مساعدة لاستخراج رابط المعاينة المباشرة لـ Google Drive
function getDriveEmbedUrl($url) {
    if (empty($url)) return '';
    if (preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $url, $matches) || preg_match('/[?&]id=([a-zA-Z0-9_-]+)/', $url, $matches)) {
        return "https://drive.google.com/file/d/" . $matches[1] . "/preview";
    }
    return '';
}

try {
    // جلب جميع عروض المقررات والأفواج وحالة روابط استعمال الزمن الخاصة بها
    $stmt = $pdo->query("
        SELECT 
            co.offering_id,
            co.timetable_url,
            d.division_id,
            d.division_name,
            c.class_id,
            c.class_name,
            g.group_id,
            g.group_name,
            t.track_id,
            t.track_name
        FROM course_offerings co
        JOIN divisions d ON co.division_id = d.division_id
        JOIN classes c ON co.class_id = c.class_id
        JOIN `groups` g ON co.group_id = g.group_id
        LEFT JOIN tracks t ON co.track_id = t.track_id
        WHERE co.status = 'active'
        ORDER BY d.division_id ASC, c.class_id ASC, co.track_id ASC, g.group_id ASC
    ");
    $offerings =$stmt->fetchAll(PDO::FETCH_ASSOC);

    // إحصائيات الروابط
    $total_count = count($offerings);$linked_count = 0;
    foreach ($offerings as$item) {
        if (!empty($item['timetable_url'])) {$linked_count++;
        }
    }

    // جلب الشعب والفصول للقوائم المنسدلة
    $divisions =$pdo->query("SELECT division_id, division_name FROM divisions ORDER BY division_name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $classes =$pdo->query("SELECT class_id, class_name FROM classes ORDER BY class_name ASC")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("خطأ في قاعدة البيانات: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة استعمالات الزمن - Mo7adaraty</title>
    <link rel="icon" type="image/svg+xml" href="/mo7adaraty/assets/images/favicon.svg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/admin_global.css">
    <link rel="stylesheet" href="../assets/css/admin_academic.css">
    <style>
        .filter-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            background: var(--bg-surface);
            padding: 16px;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
            margin-bottom: 20px;
            align-items: center;
        }
        .filter-input {
            flex: 1;
            min-width: 200px;
            height: 40px;
            padding: 0 14px;
            background: var(--bg-body);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            color: var(--text-primary);
            font-family: inherit;
            font-size: 13px;
        }
        .filter-select {
            height: 40px;
            padding: 0 14px;
            background: var(--bg-body);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            color: var(--text-primary);
            font-family: inherit;
            font-size: 13px;
            min-width: 160px;
        }
        .stats-badge-container {
            display: flex;
            gap: 10px;
            font-size: 12px;
            font-weight: 700;
        }
        .stat-pill {
            padding: 4px 10px;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .stat-pill-success {
            background: rgba(40, 167, 69, 0.15);
            color: #28a745;
            border: 1px solid rgba(40, 167, 69, 0.3);
        }
        .stat-pill-warning {
            background: rgba(255, 193, 7, 0.15);
            color: #d39e00;
            border: 1px solid rgba(255, 193, 7, 0.3);
        }
        .url-box-wrapper {
            margin-top: 12px;
            display: flex;
            flex-direction: column;
            gap: 6px;
            width: 100%;
        }
        .drive-input {
            width: 100%;
            height: 36px;
            padding: 0 10px;
            font-size: 12px;
            direction: ltr;
            text-align: left;
            background: var(--bg-body);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            color: var(--text-primary);
            font-family: monospace;
        }
        .drive-input:focus {
            border-color: var(--brand-primary);
            outline: none;
        }
        .btn-preview-link {
            background: #17a2b8;
            color: #fff;
            border: none;
            padding: 6px 12px;
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        .btn-preview-link:hover {
            background: #138496;
        }
        .btn-save-inline {
            background: var(--brand-primary);
            color: #fff;
            border: none;
            padding: 6px 14px;
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }
        .btn-save-inline:hover {
            opacity: 0.9;
        }
        .btn-open-drive {
            background: transparent;
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
            padding: 6px 10px;
            border-radius: var(--radius-sm);
            text-decoration: none;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-open-drive:hover {
            color: var(--brand-primary);
            border-color: var(--brand-primary);
        }
        .modal-preview-container {
            width: 100%;
            height: 75vh;
            background: #000;
            border-radius: var(--radius-md);
            overflow: hidden;
            border: 1px solid var(--border-color);
            position: relative;
        }
        .modal-preview-container iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
        .preview-spinner {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: #fff;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
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
                <div>
                    <h2 style="margin: 0; color: var(--text-primary); display: flex; align-items: center; gap: 10px;">
                        <i class="fa-solid fa-calendar-days" style="font-size: 30px; color: var(--brand-primary);"></i>
                        استعمالات الزمن الرسمية (Google Drive)
                    </h2>
                    <div class="stats-badge-container" style="margin-top: 8px;">
                        <span class="stat-pill stat-pill-success">
                            <i class="fa-solid fa-circle-check"></i> مربوط: <?= $linked_count ?> فوج
                        </span>
                        <span class="stat-pill stat-pill-warning">
                            <i class="fa-solid fa-clock"></i> قيد الإعداد: <?= $total_count -$linked_count ?> فوج
                        </span>
                    </div>
                </div>
                <div style="display: flex; gap: 10px;">
                    <a href="admin_schedules.php" class="btn-add-new" style="background-color: #17a2b8; text-decoration: none;">
                        <i class="fa-solid fa-list-check"></i> مراجعة جداول الطلبة
                    </a>
                    <button class="btn-add-new" onclick="document.getElementById('addModal').style.display='block'">
                        <i class="fa-brands fa-google-drive"></i> ربط استعمال زمن
                    </button>
                </div>
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

            <!-- شريط التصفية والبحث السريع -->
            <div class="filter-bar">
                <input type="text" id="searchInput" class="filter-input" placeholder="بحث باسم الشعبة، الفصل، المسار، الفوج..." onkeyup="filterCards()">
                
                <select id="divisionFilter" class="filter-select" onchange="filterCards()">
                    <option value="">-- جميع الشعب --</option>
                    <?php foreach($divisions as$div): ?>
                        <option value="<?= htmlspecialchars($div['division_name']) ?>"><?= htmlspecialchars($div['division_name']) ?></option>
                    <?php endforeach; ?>
                </select>

                <select id="classFilter" class="filter-select" onchange="filterCards()">
                    <option value="">-- جميع الفصول --</option>
                    <?php foreach($classes as$cls): ?>
                        <option value="<?= htmlspecialchars($cls['class_name']) ?>"><?= htmlspecialchars($cls['class_name']) ?></option>
                    <?php endforeach; ?>
                </select>

                <select id="statusFilter" class="filter-select" onchange="filterCards()">
                    <option value="">-- حالة الرابط --</option>
                    <option value="linked">المربوطة فقط</option>
                    <option value="missing">غير المربوطة</option>
                </select>
            </div>

            <!-- شبكة بطاقات استعمالات الزمن للأفواج -->
            <div class="academic-grid skeleton" id="offeringsGrid">
                <?php if (empty($offerings)): ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 40px; background: var(--bg-surface); border-radius: var(--radius-md); border: 1px dashed var(--border-color); color: var(--text-secondary);">
                        <i class="fa-solid fa-calendar-xmark" style="font-size: 40px; margin-bottom: 15px; opacity: 0.5;"></i>
                        <p>لا توجد أفواج دراسية مفتوحة حالياً.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($offerings as$row): 
                        $has_url = !empty($row['timetable_url']);
                        $embed_url = getDriveEmbedUrl($row['timetable_url']);
                    ?>
                        <div class="academic-card offering-card" 
                             data-division="<?= htmlspecialchars($row['division_name']) ?>"
                             data-class="<?= htmlspecialchars($row['class_name']) ?>"
                             data-status="<?= $has_url ? 'linked' : 'missing' ?>"
                             data-search="<?= htmlspecialchars(strtolower($row['division_name'] . ' ' .$row['class_name'] . ' ' . ($row['track_name'] ?? '') . ' ' .$row['group_name'])) ?>">
                            
                            <div class="card-img-wrapper" style="background: var(--bg-body); display: flex; flex-direction: column; align-items: center; justify-content: center; position: relative;">
                                <i class="fa-brands fa-google-drive" style="font-size: 48px; color: <?= $has_url ? '#28a745' : '#6c757d' ?>;"></i>
                                <span style="font-size: 11px; font-weight: 700; margin-top: 8px; color: <?= $has_url ? '#28a745' : '#e0a800' ?>;">
                                    <?= $has_url ? 'رابط مفعل' : 'الرابط غير محدد' ?>
                                </span>
                            </div>

                            <div class="card-content">
                                <h3 class="card-title" style="font-size: 15px; margin-bottom: 6px;">
                                    <?= htmlspecialchars($row['division_name']) ?>
                                </h3>

                                <div class="card-stats" style="flex-direction: column; gap: 5px; align-items: start;">
                                    <span class="stat-badge"><i class="fa-solid fa-layer-group"></i> <?= htmlspecialchars($row['class_name']) ?></span>
                                    <?php if (!empty($row['track_name'])): ?>
                                        <span class="stat-badge" style="background: rgba(23, 162, 184, 0.15); color: #17a2b8;">
                                            <i class="fa-solid fa-route"></i> <?= htmlspecialchars($row['track_name']) ?>
                                        </span>
                                    <?php endif; ?>
                                    <span class="stat-badge"><i class="fa-solid fa-users"></i> <?= htmlspecialchars($row['group_name']) ?></span>
                                </div>

                                <div class="url-box-wrapper">
                                    <label style="font-size: 11px; font-weight: 600; color: var(--text-secondary);">رابط ملف Drive:</label>
                                    <input type="url" 
                                           id="input-<?= $row['offering_id'] ?>" 
                                           class="drive-input" 
                                           value="<?= htmlspecialchars($row['timetable_url'] ?? '') ?>" 
                                           placeholder="https://drive.google.com/file/d/.../view">
                                </div>
                            </div>

                            <div class="card-actions" style="flex-wrap: wrap; gap: 6px;">
                                <button type="button" 
                                        class="btn-save-inline" 
                                        id="btn-save-<?= $row['offering_id'] ?>" 
                                        onclick="saveOfferingUrl(<?= $row['offering_id'] ?>)" 
                                        style="flex: 1;">
                                    <i class="fa-solid fa-floppy-disk"></i> حفظ
                                </button>

                                <button type="button" 
                                        class="btn-preview-link" 
                                        onclick="openPreviewModal(<?= $row['offering_id'] ?>, '<?= htmlspecialchars(addslashes($row['division_name'] . ' - ' . $row['class_name'] . ' (' .$row['group_name'] . ')')) ?>')" 
                                        style="flex: 1;">
                                    <i class="fa-solid fa-eye"></i> معاينة فورية
                                </button>

                                <?php if ($has_url): ?>
                                    <a href="<?= htmlspecialchars($row['timetable_url']) ?>" target="_blank" class="btn-open-drive" title="فتح في نافذة جديدة">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- مودال المعاينة الفورية المباشرة لملف Google Drive -->
    <div id="previewModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 950px; width: 95%;">
            <div class="modal-header">
                <h2 id="modalPreviewTitle" style="display:flex; align-items:center; gap:8px;">
                    <i class="fa-brands fa-google-drive" style="color: #28a745;"></i> معاينة استعمال الزمن
                </h2>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <a id="modalExternalLink" href="#" target="_blank" class="btn-open-drive" style="color: var(--text-primary);">
                        <i class="fa-solid fa-up-right-from-square"></i> فتح بنافذة مستقلة
                    </a>
                    <button class="close-modal" onclick="closePreviewModal()">&times;</button>
                </div>
            </div>
            <div class="modal-body" style="padding: 10px;">
                <div class="modal-preview-container">
                    <div id="previewLoader" class="preview-spinner">
                        <i class="fa-solid fa-spinner fa-spin"></i> جاري تحميل الملف من Google Drive...
                    </div>
                    <iframe id="previewIframe" src="" onload="document.getElementById('previewLoader').style.display='none';"></iframe>
                </div>
            </div>
        </div>
    </div>

    <!-- مودال ربط وتعيين رابط Google Drive جديد -->
    <div id="addModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h2>ربط استعمال زمن جديد (Google Drive)</h2>
                <button class="close-modal" onclick="document.getElementById('addModal').style.display='none'">&times;</button>
            </div>
            <div class="modal-body">
                <form action="index.php" method="POST">
                    <input type="hidden" name="save_from_modal" value="1">
                    
                    <div class="form-group">
                        <label class="form-label" for="division_id">الشعبة: <span style="color:var(--danger-color)">*</span></label>
                        <select name="division_id" id="division_id" class="form-control" required>
                            <option value="">-- اختر الشعبة أولاً --</option>
                            <?php foreach($divisions as$div): ?>
                                <option value="<?php echo $div['division_id']; ?>"><?php echo htmlspecialchars($div['division_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="class_id">الفصل: <span style="color:var(--danger-color)">*</span></label>
                        <select name="class_id" id="class_id" class="form-control" required disabled>
                            <option value="">-- يرجى اختيار الشعبة أولاً --</option>
                            <?php foreach($classes as$cls): ?>
                                <option value="<?php echo $cls['class_id']; ?>"><?php echo htmlspecialchars($cls['class_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" id="track_group" style="display: none;">
                        <label class="form-label" for="track_id">المسار (Track): <span style="color:var(--danger-color)">*</span></label>
                        <select name="track_id" id="track_id" class="form-control">
                            <option value="">-- اختر المسار --</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="group_id">المجموعة / الفوج: <span style="color:var(--danger-color)">*</span></label>
                        <select name="group_id" id="group_id" class="form-control" required disabled>
                            <option value="">-- اختر الفصل/المسار أولاً --</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="modal_timetable_url">رابط ملف Google Drive: <span style="color:var(--danger-color)">*</span></label>
                        <input type="url" name="timetable_url" id="modal_timetable_url" class="form-control" placeholder="https://drive.google.com/file/d/.../view" required style="direction: ltr; font-family: monospace;">
                        <small style="color: var(--text-secondary); display: block; margin-top: 4px;">
                            تأكد أن إذن المشاركة في Google Drive مفعّل على (Anyone with the link can view).
                        </small>
                    </div>

                    <div style="margin-bottom: 16px; display: flex; justify-content: flex-end;">
                        <button type="button" class="btn-preview-link" onclick="previewModalDirectInput()">
                            <i class="fa-solid fa-eye"></i> فحص ومعاينة الرابط فوراً
                        </button>
                    </div>

                    <button type="submit" class="btn-add-new" style="width: 100%; justify-content: center;">
                        <i class="fa-solid fa-floppy-disk"></i> حفظ الرابط للفوج
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="../assets/js/admin_global.js"></script>
    <script>
        // دالة استخراج معرف الملف من أي صيغة رابط Google Drive
        function extractDriveId(url) {
            if (!url) return null;
            const match = url.match(/\/d\/([a-zA-Z0-9_-]+)/) || url.match(/[?&]id=([a-zA-Z0-9_-]+)/);
            return match ? match[1] : null;
        }

        // فتح مودال المعاينة لبطاقة محددة
        function openPreviewModal(offeringId, title) {
            const input = document.getElementById('input-' + offeringId);
            const url = input ? input.value.trim() : '';
            const fileId = extractDriveId(url);

            if (!fileId) {
                alert('يرجى إدخال رابط Google Drive صحيح لهذا الفوج لعرض المعاينة.');
                return;
            }

            const previewUrl = `https://drive.google.com/file/d/${fileId}/preview`;
            document.getElementById('modalPreviewTitle').innerHTML = `<i class="fa-brands fa-google-drive" style="color: #28a745;"></i> معاينة: ${title}`;
            document.getElementById('modalExternalLink').href = url;
            document.getElementById('previewLoader').style.display = 'flex';
            document.getElementById('previewIframe').src = previewUrl;
            document.getElementById('previewModal').style.display = 'block';
        }

        // معاينة الرابط المدخل داخل مودال الإضافة مباشرة
        function previewModalDirectInput() {
            const url = document.getElementById('modal_timetable_url').value.trim();
            const fileId = extractDriveId(url);

            if (!fileId) {
                alert('يرجى إدخال رابط Google Drive صحيح للمعاينة.');
                return;
            }

            const previewUrl = `https://drive.google.com/file/d/${fileId}/preview`;
            document.getElementById('modalPreviewTitle').innerHTML = `<i class="fa-brands fa-google-drive" style="color: #28a745;"></i> معاينة الرابط الجديد`;
            document.getElementById('modalExternalLink').href = url;
            document.getElementById('previewLoader').style.display = 'flex';
            document.getElementById('previewIframe').src = previewUrl;
            document.getElementById('previewModal').style.display = 'block';
        }

        // إغلاق مودال المعاينة وتفريغ الـ iframe
        function closePreviewModal() {
            document.getElementById('previewModal').style.display = 'none';
            document.getElementById('previewIframe').src = '';
        }

        // حفظ الرابط الفردي لكل فوج عبر AJAX
        function saveOfferingUrl(offeringId) {
            const input = document.getElementById('input-' + offeringId);
            const btn = document.getElementById('btn-save-' + offeringId);
            const url = input.value.trim();

            btn.disabled = true;
            btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> جاري...`;

            fetch('index.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'save_drive_url',
                    offering_id: offeringId,
                    timetable_url: url
                })
            })
            .then(res => res.json())
            .then(data => {
                btn.disabled = false;
                if (data.success) {
                    btn.innerHTML = `<i class="fa-solid fa-check"></i> تم الحفظ`;
                    btn.style.backgroundColor = '#28a745';
                    setTimeout(() => {
                        btn.innerHTML = `<i class="fa-solid fa-floppy-disk"></i> حفظ`;
                        btn.style.backgroundColor = '';
                    }, 1500);

                    // تحديث الحالة في البطاقة
                    const card = input.closest('.offering-card');
                    if (card) {
                        card.setAttribute('data-status', url ? 'linked' : 'missing');
                    }
                } else {
                    btn.innerHTML = `<i class="fa-solid fa-floppy-disk"></i> حفظ`;
                    alert(data.message || 'حدث خطأ أثناء الحفظ.');
                }
            })
            .catch(() => {
                btn.disabled = false;
                btn.innerHTML = `<i class="fa-solid fa-floppy-disk"></i> حفظ`;
                alert('فشل الاتصال بالخادم.');
            });
        }

        // فلترة البطاقات لحظياً
        function filterCards() {
            const searchVal = document.getElementById('searchInput').value.toLowerCase().trim();
            const divVal = document.getElementById('divisionFilter').value.trim();
            const clsVal = document.getElementById('classFilter').value.trim();
            const statusVal = document.getElementById('statusFilter').value.trim();

            const cards = document.querySelectorAll('.offering-card');

            cards.forEach(card => {
                const searchData = card.getAttribute('data-search') || '';
                const cardDiv = card.getAttribute('data-division') || '';
                const cardCls = card.getAttribute('data-class') || '';
                const cardStatus = card.getAttribute('data-status') || '';

                const matchesSearch = !searchVal || searchData.includes(searchVal);
                const matchesDiv = !divVal || cardDiv === divVal;
                const matchesCls = !clsVal || cardCls === clsVal;
                const matchesStatus = !statusVal || cardStatus === statusVal;

                if (matchesSearch && matchesDiv && matchesCls && matchesStatus) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        // إدارة القوائم المنسدلة في مودال الإضافة
        document.addEventListener('DOMContentLoaded', function() {
            const divisionSelect = document.getElementById('division_id');
            const classSelect = document.getElementById('class_id');
            const trackGroup = document.getElementById('track_group');
            const trackSelect = document.getElementById('track_id');
            const groupSelect = document.getElementById('group_id');

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
                resetSelect(groupSelect, 'اختر الفصل/المسار أولاً');
            });

            classSelect.addEventListener('change', function() {
                const classId = this.value;
                const divisionId = divisionSelect.value;
                
                resetSelect(trackSelect, 'اختر المسار');
                resetSelect(groupSelect, 'جاري التحميل...');

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
                        if (!data.error && Array.isArray(data)) {
                            data.forEach(t => trackSelect.innerHTML += `<option value="${t.track_id}">${t.track_name}</option>`);
                        }
                    });
                    
                    resetSelect(groupSelect, 'الرجاء اختيار المسار أولاً');
                } else {
                    trackGroup.style.display = 'none';
                    trackSelect.required = false;
                    fetchGroups(classId, null);
                }
            });

            trackSelect.addEventListener('change', function() {
                const trackId = this.value;
                const classId = classSelect.value;
                if (trackId) {
                    fetchGroups(classId, trackId);
                } else {
                    resetSelect(groupSelect, 'الرجاء اختيار المسار أولاً');
                }
            });

            function fetchGroups(classId, trackId) {
                const divisionId = divisionSelect.value;
                groupSelect.disabled = false;
                groupSelect.innerHTML = `<option value="">جاري التحميل...</option>`;
                
                let bodyData = `class_id=${classId}&division_id=${divisionId}`;
                if (trackId) {
                    bodyData += `&track_id=${trackId}`;
                }

                fetch('get_groups.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: bodyData
                })
                .then(res => res.json())
                .then(data => {
                    groupSelect.innerHTML = `<option value="">-- اختر المجموعة --</option>`;
                    if (data.error) {
                        groupSelect.innerHTML = `<option value="">${data.error}</option>`;
                    } else if (Array.isArray(data)) {
                        data.forEach(g => groupSelect.innerHTML += `<option value="${g.group_id}">${g.group_name}</option>`);
                    }
                });
            }
        });

        // إغلاق المودالات عند النقر خارجها
        window.onclick = function(event) {
            const addModal = document.getElementById('addModal');
            const previewModal = document.getElementById('previewModal');
            if (event.target === addModal) {
                addModal.style.display = "none";
            }
            if (event.target === previewModal) {
                closePreviewModal();
            }
        }
    </script>
</body>
</html>