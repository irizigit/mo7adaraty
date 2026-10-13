<?php
session_start();
require_once '../config/db_connexion.php';

// 1. حماية الصفحة
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// ========================================================================
// 2. معالج الـ AJAX المصغر (لتحديث حالة الإشعارات بدون إعادة تحميل الصفحة)
// ========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['ajax_action'] === 'mark_all') {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
        $success = $stmt->execute([$user_id]);
        echo json_encode(['success' => $success]);
        exit();
    }
    
    if ($_POST['ajax_action'] === 'mark_one' && !empty($_POST['notif_id'])) {
        $notif_id = filter_var($_POST['notif_id'], FILTER_VALIDATE_INT);
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $success = $stmt->execute([$notif_id, $user_id]);
        echo json_encode(['success' => $success]);
        exit();
    }
}
// ========================================================================

try {
    // === جلب الأيقونات الموحدة من الإدارة ===
    $stmt_design = $pdo->prepare("SELECT asset_key, file_path FROM design_assets WHERE page_section IN ('header', 'class_lectures', 'global_icons')");
    $stmt_design->execute();
    $design_settings = [];
    while ($row = $stmt_design->fetch(PDO::FETCH_ASSOC)) {
        $design_settings[$row['asset_key']] = $row['file_path'];
    }

    // دالة مساعدة للحصول على مسار الأيقونة
    function getDesignIcon($key, $default_path) {
        global $design_settings;
        return !empty($design_settings[$key]) ? '../' . $design_settings[$key] : '../' . $default_path;
    }

    // 3. جلب جميع الإشعارات للمستخدم
    $stmt_notifs = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
    $stmt_notifs->execute([$user_id]);
    $notifications = $stmt_notifs->fetchAll(PDO::FETCH_ASSOC);
    
    // التحقق مما إذا كان هناك إشعارات غير مقروءة لعرض زر "تحديد الكل"
    $has_unread = false;
    foreach ($notifications as $n) {
        if ($n['is_read'] == 0) {
            $has_unread = true;
            break;
        }
    }

} catch (PDOException $e) {
    die("خطأ في جلب الإشعارات: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الإشعارات - Mo7adaraty</title>
    <link rel="icon" type="image/svg+xml" href="../assets/images/favicon.svg">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/pages/notifications.css">
</head>
<body>

    <script>
        (function() {
            const savedTheme = localStorage.getItem('mo7adaraty_theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>

    <?php require_once '../templates/header.php'; ?>

    <main class="notifications-wrapper">
        <div class="notifications-container">
            
            <div class="notif-page-header" data-aos="fade-down">
                <h1 class="notif-title">
                    <img src="<?php echo getDesignIcon('icon_notifications', 'includesimg/header/icon_notifications.png'); ?>" alt="notifications" class="notif-title-icon">
                    الإشعارات
                </h1>
                
                <?php if ($has_unread): ?>
                    <button id="markAllReadBtn" class="mark-all-read-btn">
                        <i class="fa-solid fa-check-double"></i> تحديد الكل كمقروء
                    </button>
                <?php endif; ?>
            </div>

            <div class="notif-list">
                <?php if (empty($notifications)): ?>
                    <div class="empty-state" data-aos="fade-up">
                        <img src="<?php echo getDesignIcon('cl_icon_empty', 'includesimg/class_lectures/cl_icon_empty.png'); ?>" alt="empty" class="empty-state-icon">
                        <p>لا توجد إشعارات حالياً. أنت مطلع على كل شيء!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($notifications as $index => $notif): ?>
                        <?php 
                            $is_unread = ($notif['is_read'] == 0); 
                            $link = !empty($notif['link']) && $notif['link'] !== '#' ? htmlspecialchars('../' . ltrim($notif['link'], '/')) : 'javascript:void(0)';
                            $notif_id = $notif['id'] ?? $notif['notification_id'] ?? 0;
                            
                            $msg = mb_strtolower($notif['message'], 'UTF-8');
                            $icon_key = 'icon_bell';
                            $default_icon = 'includesimg/student/icon_bell.png';
                            
                            if (strpos($msg, 'محاضرة') !== false) {
                                $icon_key = 'cl_icon_pdf'; 
                                $default_icon = 'includesimg/class_lectures/icon_pdf.png';
                            } elseif (strpos($msg, 'امتحان') !== false) {
                                $icon_key = 'cl_icon_nav_exams'; 
                                $default_icon = 'includesimg/class_lectures/icon_nav_exams.svg';
                            } elseif (strpos($msg, 'تعليق') !== false || strpos($msg, 'منشور') !== false) {
                                $icon_key = 'cl_icon_comments'; 
                                $default_icon = 'includesimg/class_lectures/icon_comments.png';
                            } elseif (strpos($msg, 'إعجاب') !== false) {
                                $icon_key = 'cl_icon_like'; 
                                $default_icon = 'includesimg/class_lectures/icon_like.png';
                            } elseif (strpos($msg, 'إعلان') !== false || strpos($msg, 'تنبيه') !== false) {
                                $icon_key = 'icon_announcements'; 
                                $default_icon = 'includesimg/header/icon_announcements.png';
                            }
                        ?>
                        
                        <a href="<?php echo $link; ?>" class="notif-card <?php echo $is_unread ? 'unread' : ''; ?>" data-id="<?php echo $notif_id; ?>" data-aos="fade-up" data-aos-delay="<?php echo min($index * 50, 400); ?>">
                            
                            <div class="notif-icon-box">
                                <img src="<?php echo getDesignIcon($icon_key, $default_icon); ?>" alt="icon">
                            </div>
                            
                            <div class="notif-content">
                                <h3 class="notif-text"><?php echo htmlspecialchars($notif['message']); ?></h3>
                                <span class="notif-time">
                                    <i class="far fa-clock"></i> 
                                    <?php echo date('d-m-Y | H:i', strtotime($notif['created_at'])); ?>
                                </span>
                            </div>
                            
                            <?php if ($is_unread): ?>
                                <div class="unread-dot" title="غير مقروء"></div>
                            <?php endif; ?>
                            
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div>
    </main>

    <?php require_once '../templates/footer.php'; ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script src="../assets/js/notifications.js"></script>

</body>
</html>