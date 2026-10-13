<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/db_connexion.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['student', 'admin'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$already_read_ids = [];

try {
    // 1. التأكد من وجود جدول قراءة الإعلانات
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS announcement_reads (
            user_id INT NOT NULL,
            announcement_id INT NOT NULL,
            read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (user_id, announcement_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // 2. جلب معرفات الإعلانات المقروءة مسبقاً لتحديد الإعلان الجديد قبل التحديث
    $stmt_reads = $pdo->prepare("SELECT announcement_id FROM announcement_reads WHERE user_id = ?");
    $stmt_reads->execute([$user_id]);
    $already_read_ids = $stmt_reads->fetchAll(PDO::FETCH_COLUMN) ?: [];

    // 3. استعلام جلب الإعلانات الخاصة بالطالب
    $query = "
        SELECT DISTINCT a.* FROM announcements a
        WHERE a.target_type = 'all'
        OR (
            a.target_type = 'specific'
            AND EXISTS (
                SELECT 1 
                FROM student_enrollments se
                JOIN course_offerings co ON se.offering_id = co.offering_id
                WHERE se.user_id = :user_id 
                AND co.academic_year_id = a.academic_year_id
                AND (a.division_id IS NULL OR co.division_id = a.division_id)
            )
        )
        ORDER BY a.created_at DESC
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute(['user_id' => $user_id]);
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. تسجيل جميع الإعلانات المعروضة كمقروءة لهذا الطالب فور فتح الصفحة
    if (!empty($announcements)) {
        $stmt_mark = $pdo->prepare("
            INSERT IGNORE INTO announcement_reads (user_id, announcement_id) 
            VALUES (?, ?)
        ");
        foreach ($announcements as $ann) {
            $ann_id = $ann['id'] ?? $ann['announcement_id'] ?? null;
            if ($ann_id) {
                $stmt_mark->execute([$user_id, $ann_id]);
            }
        }
    }

} catch (PDOException $e) {
    $error_msg = "خطأ في جلب الإعلانات: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الإعلانات الإدارية - محاضراتي</title>
    <link rel="icon" type="image/svg+xml" href="../assets/images/favicon.svg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/pages/announcements.css">
    <style>
        .ann-container { max-width: 900px; margin: 30px auto; padding: 0 20px; }
        .ann-card { 
            background: var(--bg-surface); 
            border: 1px solid var(--border-color); 
            border-radius: var(--radius-lg); 
            padding: 25px; 
            margin-bottom: 20px; 
            box-shadow: var(--shadow-sm);
            border-right: 5px solid var(--brand-primary);
            transition: transform 0.3s ease;
        }
        .ann-card:hover { transform: translateY(-3px); }
        .ann-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px; }
        .ann-title { font-size: 1.2rem; font-weight: 800; color: var(--text-primary); margin: 0; }
        .ann-date { font-size: 0.85rem; color: var(--text-secondary); display: flex; align-items: center; gap: 5px; }
        .ann-body { color: var(--text-primary); line-height: 1.7; font-size: 1rem; }
        .ann-link { 
            display: inline-flex; 
            align-items: center; 
            gap: 8px; 
            margin-top: 15px; 
            padding: 8px 16px; 
            background: var(--brand-light); 
            color: var(--brand-primary); 
            border-radius: var(--radius-sm); 
            text-decoration: none; 
            font-weight: bold; 
            font-size: 0.9rem;
        }
        .no-ann { text-align: center; padding: 50px; color: var(--text-secondary); }
        .badge-new { background: var(--danger-color); color: #fff; padding: 2px 8px; border-radius: 10px; font-size: 0.7rem; }
    </style>
</head>
<body class="student-body">
    <script>
        (function() {
            const savedTheme = localStorage.getItem('mo7adaraty_theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>

    <?php require_once '../templates/header.php'; ?>

    <main class="ann-container">
        <div class="page-header" style="margin-bottom: 30px;">
            <h1 style="display: flex; align-items: center; gap: 12px; font-weight: 900;">
                <i class="fa-solid fa-bullhorn" style="color: var(--brand-primary);"></i>
                الإعلانات والتبليغات
            </h1>
            <p style="color: var(--text-secondary);">تابع آخر المستجدات والإعلانات الصادرة عن إدارة المؤسسة.</p>
        </div>

        <?php if (isset($error_msg)): ?>
            <div class="alert alert-danger" style="background: rgba(220,53,69,0.1); color: var(--danger-color); padding: 15px; border-radius: 10px; border: 1px solid var(--danger-color);">
                <i class="fa-solid fa-triangle-exclamation"></i> <?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($announcements)): ?>
            <div class="no-ann">
                <i class="fa-solid fa-inbox fa-3x" style="margin-bottom: 15px; opacity: 0.3;"></i>
                <p>لا توجد إعلانات منشورة حالياً.</p>
            </div>
        <?php else: ?>
            <?php foreach ($announcements as $ann): 
                $ann_id = $ann['id'] ?? $ann['announcement_id'] ?? null;
                $is_unread = !in_array($ann_id, $already_read_ids);
                $is_recent = (strtotime($ann['created_at']) > strtotime('-2 days'));
            ?>
                <div class="ann-card">
                    <div class="ann-header">
                        <div>
                            <h2 class="ann-title">
                                <?php echo htmlspecialchars($ann['title']); ?>
                                <?php if ($is_unread): ?> 
                                    <span class="badge-new">جديد</span> 
                                <?php elseif ($is_recent): ?>
                                    <span class="badge-new" style="background: var(--brand-primary);">مؤخراً</span>
                                <?php endif; ?>
                            </h2>
                            <div class="ann-date">
                                <i class="fa-regular fa-clock"></i>
                                <?php echo date('Y-m-d H:i', strtotime($ann['created_at'])); ?>
                            </div>
                        </div>
                        <i class="fa-solid fa-info-circle" style="color: var(--brand-primary); font-size: 1.5rem; opacity: 0.2;"></i>
                    </div>
                    <div class="ann-body">
                        <?php echo nl2br(htmlspecialchars($ann['message'])); ?>
                    </div>
                    <?php if (!empty($ann['link'])): ?>
                        <a href="<?php echo htmlspecialchars($ann['link']); ?>" target="_blank" class="ann-link">
                            <i class="fa-solid fa-link"></i>
                            رابط المرفق / المزيد من المعلومات
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>

    <?php include '../templates/footer.php'; ?>
    <script src="../assets/js/main.js"></script>
</body>
</html>