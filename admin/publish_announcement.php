<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/db_connexion.php';

// تصحيح مسار التوجيه (من ../../login.php إلى ../login.php)
if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$breadcrumbs = ["التواصل" => "", "نشر وإدارة الإعلانات" => ""];

// ==========================================
// 1. معالجة إرسال الإعلان الجديد
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'publish') {
    $title = trim($_POST['title'] ?? '');
    $message_body = trim($_POST['message'] ?? '');
    $target_type = $_POST['target_type'] ?? 'all';
    $link = trim($_POST['link'] ?? '');
    
    $academic_year_id = filter_input(INPUT_POST, 'academic_year_id', FILTER_VALIDATE_INT) ?: null;
    $division_id = filter_input(INPUT_POST, 'division_id', FILTER_VALIDATE_INT) ?: null;

    if (empty($title) || empty($message_body)) {
        $_SESSION['error'] = "يرجى كتابة عنوان ونص الإعلان.";
        header("Location: publish_announcement.php");
        exit();
    }

    try {
        $pdo->beginTransaction();

        // أ. تخزين الإعلان في أرشيف الإعلانات الجديد (announcements)
        $stmt_ann = $pdo->prepare("INSERT INTO announcements (title, message, link, target_type, academic_year_id, division_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_ann->execute([$title, $message_body, $link, $target_type, $academic_year_id, $division_id]);

        // ب. تجهيز نص الإشعار بدون رموز تعبيرية لتجنب خطأ 1366
        $final_notification_message = "إعلان إداري: " . $title;
        $students_to_notify = [];

        // ج. تحديد الطلاب المستهدفين
        if ($target_type === 'all') {
            $stmt = $pdo->query("SELECT user_id FROM users WHERE role = 'student'");
            $students_to_notify = $stmt->fetchAll(PDO::FETCH_COLUMN);
        } elseif ($target_type === 'specific') {
            if (!$academic_year_id) { throw new Exception("يجب تحديد الموسم الجامعي للإعلان المخصص."); }
            $query = "
                SELECT DISTINCT se.user_id 
                FROM student_enrollments se 
                JOIN course_offerings co ON se.offering_id = co.offering_id 
                WHERE co.academic_year_id = ?
            ";
            $params = [$academic_year_id];
            if ($division_id) {
                $query .= " AND co.division_id = ?";
                $params[] = $division_id;
            }
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $students_to_notify = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }

        // د. إرسال الإشعارات
        if (!empty($students_to_notify)) {
            $stmt_notify = $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
            $target_link = "student/announcements.php"; 
            foreach ($students_to_notify as $uid) {
                $stmt_notify->execute([$uid, $final_notification_message, $target_link]);
            }
            $_SESSION['msg'] = "تم حفظ الإعلان بنجاح وإرسال الإشعار لـ (" . count($students_to_notify) . ") طالب.";
        } else {
            $_SESSION['error'] = "تم حفظ الإعلان في الأرشيف، لكن لم يتم العثور على طلاب في النطاق المحدد لإرسال إشعار لهم.";
        }

        $pdo->commit();
        header("Location: publish_announcement.php");
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "حدث خطأ أثناء النشر: " . $e->getMessage();
        header("Location: publish_announcement.php");
        exit();
    }
}

// ==========================================
// 2. معالجة حذف إعلان
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $del_id = filter_input(INPUT_POST, 'announcement_id', FILTER_VALIDATE_INT);
    if ($del_id) {
        $pdo->prepare("DELETE FROM announcements WHERE id = ?")->execute([$del_id]);
        $_SESSION['msg'] = "تم حذف الإعلان بنجاح.";
    }
    header("Location: publish_announcement.php");
    exit();
}

// ==========================================
// 3. جلب البيانات (السنوات، الشعب، والأرشيف)
// ==========================================
try {
    $years = $pdo->query("SELECT * FROM academic_years ORDER BY year_label DESC")->fetchAll(PDO::FETCH_ASSOC);
    $divisions = $pdo->query("SELECT * FROM divisions ORDER BY division_name ASC")->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt_history = $pdo->query("
        SELECT a.*, ay.year_label, d.division_name 
        FROM announcements a
        LEFT JOIN academic_years ay ON a.academic_year_id = ay.id
        LEFT JOIN divisions d ON a.division_id = d.division_id
        ORDER BY a.created_at DESC
    ");
    $announcements_history = $stmt_history->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("خطأ: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نشر وإدارة الإعلانات - Mo7adaraty</title>
    <link rel="icon" type="image/svg+xml" href="../assets/images/favicon.svg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="assets/css/admin_global.css">
    <link rel="stylesheet" href="assets/css/admin_academic.css">
    <style>
        .broadcast-container { background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 30px; box-shadow: var(--shadow-sm); max-width: 800px; margin: 0 auto 40px auto; }
        .broadcast-header { display: flex; align-items: center; gap: 15px; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid var(--border-color); }
        .broadcast-icon { width: 60px; height: 60px; background: rgba(26, 115, 232, 0.1); color: var(--brand-primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 28px; }
        .target-box { background: var(--bg-light); border: 1px dashed var(--border-color); padding: 20px; border-radius: var(--radius-md); margin-bottom: 20px; transition: all 0.3s ease; }
        .target-box.active { border-color: var(--brand-primary); background: rgba(26, 115, 232, 0.02); }
        .radio-group { display: flex; gap: 20px; margin-bottom: 15px; }
        .radio-option { flex: 1; background: var(--bg-surface); border: 1px solid var(--border-color); padding: 15px; border-radius: var(--radius-sm); cursor: pointer; display: flex; align-items: center; gap: 10px; font-weight: bold; transition: 0.2s; }
        .radio-option:hover { border-color: var(--brand-primary); }
        .radio-option input[type="radio"] { width: 18px; height: 18px; accent-color: var(--brand-primary); }
        .btn-broadcast { background: linear-gradient(135deg, var(--brand-primary), #0d47a1); color: #fff; border: none; padding: 15px; border-radius: var(--radius-sm); font-size: 16px; font-weight: 900; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; width: 100%; transition: 0.3s; margin-top: 20px; }
        .btn-broadcast:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(26, 115, 232, 0.3); }

        /* ستايل الأرشيف */
        .history-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; }
        .ann-card { background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 20px; display: flex; flex-direction: column; gap: 15px; box-shadow: var(--shadow-sm); position: relative; border-right: 4px solid var(--brand-primary); }
        .ann-title { margin: 0; font-size: 16px; font-weight: 800; color: var(--text-primary); }
        .ann-meta { display: flex; gap: 10px; font-size: 12px; color: var(--text-secondary); font-weight: bold; }
        .ann-badge { display: inline-block; padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; }
        .badge-all { background: rgba(40,167,69,0.1); color: var(--success-color); }
        .badge-specific { background: rgba(255,193,7,0.1); color: #d39e00; }
        .ann-body { font-size: 13px; line-height: 1.6; color: var(--text-primary); background: var(--bg-light); padding: 12px; border-radius: var(--radius-sm); }
    </style>
</head>
<body class="admin-body">
    <script>
        (function() {
            const savedTheme = localStorage.getItem('mo7adaraty_theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>
    
    <?php include 'templates/sidebar.php'; ?>
    
    <main class="admin-main">
        <?php include 'templates/header.php'; ?>

        <div class="admin-content-wrapper">
            
            <?php if (isset($_SESSION['msg'])): ?>
                <div style="padding: 15px; background: rgba(40, 167, 69, 0.1); color: var(--success-color); border: 1px solid var(--success-color); border-radius: var(--radius-md); margin-bottom: 20px; font-weight: bold; max-width: 800px; margin-left: auto; margin-right: auto;">
                    <i class="fa-solid fa-circle-check"></i> <?php echo $_SESSION['msg']; unset($_SESSION['msg']); ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div style="padding: 15px; background: rgba(220, 53, 69, 0.1); color: var(--danger-color); border: 1px solid var(--danger-color); border-radius: var(--radius-md); margin-bottom: 20px; font-weight: bold; max-width: 800px; margin-left: auto; margin-right: auto;">
                    <i class="fa-solid fa-circle-exclamation"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <div class="broadcast-container skeleton">
                <div class="broadcast-header">
                    <div class="broadcast-icon"><i class="fa-solid fa-bullhorn"></i></div>
                    <div>
                        <h2 style="margin: 0; color: var(--text-primary); font-weight: 900;">نشر إعلان إداري جديد</h2>
                        <p style="margin: 5px 0 0 0; color: var(--text-secondary); font-size: 14px;">سيتم أرشفة الإعلان وإرسال إشعار فوري للطلاب المستهدفين.</p>
                    </div>
                </div>

                <form action="publish_announcement.php" method="POST" id="broadcastForm">
                    <input type="hidden" name="action" value="publish">
                    <div class="form-group">
                        <label class="form-label">عنوان الإعلان: <span style="color:var(--danger-color)">*</span></label>
                        <input type="text" name="title" class="form-control" required placeholder="مثال: تغيير موعد امتحانات الدورة الخريفية">
                    </div>
                    <div class="form-group">
                        <label class="form-label">نص الإعلان (التفاصيل): <span style="color:var(--danger-color)">*</span></label>
                        <textarea name="message" class="form-control" required style="min-height: 120px; resize: vertical;" placeholder="اكتب تفاصيل الإعلان هنا..."></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">إرفاق رابط (اختياري):</label>
                        <input type="url" name="link" class="form-control" dir="ltr" style="text-align: left;" placeholder="https://...">
                    </div>

                    <h3 style="font-size: 16px; font-weight: 800; color: var(--text-primary); margin-top: 30px; margin-bottom: 15px;"><i class="fa-solid fa-crosshairs text-[var(--brand-primary)]"></i> الجمهور المستهدف</h3>
                    <div class="target-box active" id="targetWrapper">
                        <div class="radio-group">
                            <label class="radio-option">
                                <input type="radio" name="target_type" value="all" checked onchange="toggleTargeting()">
                                إعلان عام (لجميع الطلاب)
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="target_type" value="specific" onchange="toggleTargeting()">
                                تخصيص (حسب الموسم / الشعبة)
                            </label>
                        </div>
                        <div id="specificFields" style="display: none; padding-top: 15px; border-top: 1px dashed var(--border-color); margin-top: 10px;">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                <div class="form-group" style="margin: 0;">
                                    <label class="form-label">الموسم الجامعي: <span style="color:var(--danger-color)">*</span></label>
                                    <select name="academic_year_id" id="academic_year_id" class="form-control">
                                        <?php foreach ($years as $y): ?>
                                            <option value="<?php echo $y['id']; ?>" <?php echo $y['is_current'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($y['year_label']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group" style="margin: 0;">
                                    <label class="form-label">تخصيص الشعبة (اختياري):</label>
                                    <select name="division_id" id="division_id" class="form-control">
                                        <option value="">-- جميع الشعب في هذا الموسم --</option>
                                        <?php foreach ($divisions as $d): ?>
                                            <option value="<?php echo $d['division_id']; ?>"><?php echo htmlspecialchars($d['division_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn-broadcast" onclick="return confirm('تأكيد نشر الإعلان وإرسال الإشعارات؟');">
                        <i class="fa-regular fa-paper-plane"></i> نشر وإرسال الإشعار
                    </button>
                </form>
            </div>

            <h3 style="font-size: 20px; font-weight: 800; color: var(--text-primary); margin-bottom: 20px; border-bottom: 2px solid var(--border-color); padding-bottom: 10px;">
                <i class="fa-solid fa-clock-rotate-left text-[var(--brand-primary)]"></i> أرشيف الإعلانات المنشورة
            </h3>
            <div class="history-grid skeleton">
                <?php if (empty($announcements_history)): ?>
                    <div style="grid-column: 1/-1; text-align: center; padding: 40px; background: var(--bg-surface); border: 1px dashed var(--border-color); border-radius: var(--radius-md); color: var(--text-secondary);">
                        <p>لا توجد إعلانات منشورة مسبقاً.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($announcements_history as $ann): ?>
                        <div class="ann-card">
                            <h4 class="ann-title"><?php echo htmlspecialchars($ann['title']); ?></h4>
                            <div class="ann-meta">
                                <span dir="ltr"><i class="fa-regular fa-clock"></i> <?php echo date('Y-m-d H:i', strtotime($ann['created_at'])); ?></span>
                                <?php if ($ann['target_type'] === 'all'): ?>
                                    <span class="ann-badge badge-all">إعلان عام</span>
                                <?php else: ?>
                                    <span class="ann-badge badge-specific">مخصص: <?php echo htmlspecialchars($ann['division_name'] ?? 'لجميع الشعب'); ?> (<?php echo htmlspecialchars($ann['year_label']); ?>)</span>
                                <?php endif; ?>
                            </div>
                            <div class="ann-body">
                                <?php echo nl2br(htmlspecialchars($ann['message'])); ?>
                                <?php if (!empty($ann['link'])): ?>
                                    <br><a href="<?php echo htmlspecialchars($ann['link']); ?>" target="_blank" style="color: var(--brand-primary); font-weight: bold; font-size: 12px; display: inline-block; margin-top: 10px;"><i class="fa-solid fa-link"></i> رابط مرفق</a>
                                <?php endif; ?>
                            </div>
                            <form action="publish_announcement.php" method="POST" style="position: absolute; top: 15px; left: 15px;" onsubmit="return confirm('حذف الإعلان من الأرشيف؟ (ملاحظة: الإشعارات المرسلة للطلاب لن تحذف)');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="announcement_id" value="<?php echo $ann['id']; ?>">
                                <button type="submit" style="background: rgba(220,53,69,0.1); color: var(--danger-color); border: none; padding: 5px 8px; border-radius: 6px; cursor: pointer; transition: 0.2s;"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="assets/js/admin_global.js"></script>
    <script>
        function toggleTargeting() {
            const isSpecific = document.querySelector('input[name="target_type"]:checked').value === 'specific';
            const specificFields = document.getElementById('specificFields');
            const targetWrapper = document.getElementById('targetWrapper');
            if (isSpecific) {
                specificFields.style.display = 'block';
                targetWrapper.classList.add('active');
                document.getElementById('academic_year_id').setAttribute('required', 'required');
            } else {
                specificFields.style.display = 'none';
                targetWrapper.classList.remove('active');
                document.getElementById('academic_year_id').removeAttribute('required');
            }
        }
    </script>
</body>
</html>