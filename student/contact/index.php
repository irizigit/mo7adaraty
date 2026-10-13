<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية الصفحة
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

try {
    // === جلب الأيقونات الموحدة من الإدارة لتطبيق مبدأ DRY ===
    $stmt_design = $pdo->prepare("SELECT asset_key, file_path FROM design_assets WHERE page_section IN ('header', 'class_lectures', 'global_icons')");
    $stmt_design->execute();
    $design_settings = [];
    while ($row = $stmt_design->fetch(PDO::FETCH_ASSOC)) {
        $design_settings[$row['asset_key']] = $row['file_path'];
    }

    // دالة للحصول على مسار الأيقونة (تحافظ على التصميم الأصلي بدون فلاتر)
    function getDesignIcon($key, $default_path) {
        global $design_settings;
        return !empty($design_settings[$key]) ? '../../' . $design_settings[$key] : '../../' . $default_path;
    }

    // جلب سجل الرسائل
    $stmt = $pdo->prepare("SELECT * FROM admin_messages WHERE student_user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("خطأ في جلب الرسائل: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التواصل مع الإدارة - Mo7adaraty</title>
    <link rel="icon" type="image/svg+xml" href="../../assets/images/favicon.svg">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="../../assets/css/global.css">
    <link rel="stylesheet" href="../../assets/css/pages/contact_admin.css">
</head>
<body>

    <script>
        (function() {
            const savedTheme = localStorage.getItem('mo7adaraty_theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>

    <?php require_once '../../templates/header.php'; ?>

    <main class="contact-admin-wrapper">
        <div class="contact-admin-container">
            
            <div class="admin-page-header" data-aos="fade-down">
                <a href="../profil.php" class="back-link">
                    <img src="<?php echo getDesignIcon('cl_icon_back', 'includesimg/class_lectures/icon_back.svg'); ?>" alt="back" style="width: 20px; height: 20px; object-fit: contain;">
                    العودة للملف الشخصي
                </a>
                
                <h1 class="admin-title">
                    <img src="<?php echo getDesignIcon('icon_contact', 'includesimg/header/icon_contact.png'); ?>" alt="contact" class="admin-title-icon">
                    التواصل مع الإدارة
                </h1>
                <p style="color: var(--text-secondary); margin: 0; font-size: 16px;">أرسل استفساراتك أو مشاكلك لفريق الدعم، وسنقوم بالرد عليك في أقرب وقت.</p>
            </div>

            <?php if (isset($_SESSION['contact_msg'])): ?>
                <div class="msg-alert msg-<?php echo $_SESSION['contact_msg']['type']; ?>" data-aos="fade-in">
                    <?php if ($_SESSION['contact_msg']['type'] == 'success'): ?>
                        <i class="fa-solid fa-circle-check"></i>
                    <?php else: ?>
                        <i class="fa-solid fa-circle-exclamation"></i>
                    <?php endif; ?>
                    <?php echo $_SESSION['contact_msg']['message']; ?>
                </div>
                <?php unset($_SESSION['contact_msg']); ?>
            <?php endif; ?>

            <div class="ticket-card" data-aos="fade-up">
                <div class="ticket-card-header">
                    <h3><i class="fa-solid fa-pen-to-square" style="color: var(--brand-primary);"></i> إنشاء تذكرة جديدة</h3>
                </div>
                <div class="ticket-card-body">
                    <form id="contactAdminForm" action="process.php" method="POST">
                        <div class="ticket-form-group">
                            <label for="message_subject">موضوع الرسالة <span style="color: var(--danger-color);">*</span></label>
                            <input type="text" id="message_subject" name="message_subject" class="ticket-input" placeholder="اكتب موضوعاً مختصراً (مثال: مشكلة في تحميل محاضرة)" required>
                        </div>
                        <div class="ticket-form-group">
                            <label for="message_content">تفاصيل الرسالة <span style="color: var(--danger-color);">*</span></label>
                            <textarea id="message_content" name="message_content" class="ticket-textarea" placeholder="اشرح مشكلتك أو استفسارك بالتفصيل لمساعدتنا في الرد عليك بدقة..." required></textarea>
                        </div>
                        <button type="submit" id="submitTicketBtn" class="ticket-submit-btn">
                            إرسال الرسالة
                            <img src="<?php echo getDesignIcon('cl_icon_send', 'includesimg/class_lectures/icon_send.png'); ?>" alt="send">
                        </button>
                    </form>
                </div>
            </div>

            <div class="ticket-card" data-aos="fade-up" data-aos-delay="100">
                <div class="ticket-card-header">
                    <h3><i class="fa-solid fa-clock-rotate-left" style="color: var(--brand-primary);"></i> سجل رسائلك السابقة</h3>
                </div>
                <div class="ticket-card-body">
                    <?php if (empty($messages)): ?>
                        <div class="empty-state">
                            <img src="<?php echo getDesignIcon('cl_icon_empty', 'includesimg/class_lectures/cl_icon_empty.png'); ?>" alt="empty">
                            <p>لا توجد رسائل سابقة. صندوق الوارد الخاص بك فارغ.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($messages as $msg): ?>
                            <div class="message-thread">
                                <div class="thread-header">
                                    <h4><?php echo htmlspecialchars($msg['message_subject']); ?></h4>
                                    <?php if ($msg['status'] === 'replied'): ?>
                                        <span class="status-badge status-replied">
                                            <i class="fa-solid fa-check-circle"></i> تم الرد
                                        </span>
                                    <?php else: ?>
                                        <span class="status-badge status-pending">
                                            <i class="fa-solid fa-hourglass-half"></i> بانتظار الرد
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <div class="message-bubble student-message">
                                    <strong><i class="fa-solid fa-user"></i> رسالتك:</strong>
                                    <p><?php echo nl2br(htmlspecialchars($msg['message_content'])); ?></p>
                                    <span class="message-time"><?php echo date('Y-m-d | H:i', strtotime($msg['created_at'])); ?></span>
                                </div>

                                <?php if (!empty($msg['admin_reply'])): ?>
                                    <div class="message-bubble admin-reply">
                                        <strong><i class="fa-solid fa-user-shield"></i> رد الإدارة:</strong>
                                        <p><?php echo nl2br(htmlspecialchars($msg['admin_reply'])); ?></p>
                                        <span class="message-time"><?php echo date('Y-m-d | H:i', strtotime($msg['replied_at'])); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </main>

    <?php require_once '../../templates/footer.php'; ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script src="../../assets/js/contact_admin.js"></script>
</body>
</html>