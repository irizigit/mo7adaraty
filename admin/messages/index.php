<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../config/db_connexion.php';

// حماية الصفحة
if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

$breadcrumbs = ["التواصل" => "", "رسائل الطلاب (الدعم الفني)" => ""];

try {
    // جلب الأيقونات الذكية
    $stmt_design = $pdo->query("SELECT asset_key, file_path FROM design_assets WHERE page_section = 'global_icons'");
    $design_settings = [];
    while ($row = $stmt_design->fetch(PDO::FETCH_ASSOC)) {
        $design_settings[$row['asset_key']] = $row['file_path'];
    }

    // الاستعلام الأصلي المعتمد لجلب الرسائل مع معلومات تسجيل الطالب الصحيحة
    $stmt = $pdo->query("
        SELECT 
            m.message_id, m.student_user_id, m.message_subject, m.message_content, 
            m.admin_reply, m.status, m.created_at, m.replied_at,
            u.username,
            (
                SELECT GROUP_CONCAT(
                    DISTINCT CONCAT(d.division_name, ' > ', c.class_name, ' / ', g.group_name, IF(t.track_name IS NOT NULL, CONCAT(' / ', t.track_name), '')) 
                    SEPARATOR '<br>'
                )
                FROM student_enrollments se
                JOIN course_offerings co ON se.offering_id = co.offering_id
                JOIN divisions d ON co.division_id = d.division_id
                JOIN classes c ON co.class_id = c.class_id
                JOIN `groups` g ON co.group_id = g.group_id
                LEFT JOIN tracks t ON co.track_id = t.track_id
                WHERE se.user_id = m.student_user_id
            ) as enrollment_info
        FROM admin_messages m
        JOIN users u ON m.student_user_id = u.user_id
        ORDER BY m.status ASC, m.created_at DESC
    ");
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // إحصائيات سريعة
    $pending_count = 0;
    foreach ($messages as $m) { if ($m['status'] !== 'replied') $pending_count++; }

} catch (PDOException $e) {
    die("خطأ في جلب الرسائل: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الرسائل الواردة - Mo7adaraty</title>
    <link rel="icon" type="image/svg+xml" href="/mo7adaraty/assets/images/favicon.svg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/admin_global.css">
    <link rel="stylesheet" href="../assets/css/admin_academic.css">
    <style>
        .messages-grid { display: flex; flex-direction: column; gap: 25px; max-width: 900px; margin: 0 auto; }
        
        .msg-card { background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); overflow: hidden; transition: 0.3s; position: relative; }
        .msg-card:hover { box-shadow: var(--shadow-md); transform: translateY(-3px); }
        
        .msg-pending { border-right: 5px solid var(--warning-color); }
        .msg-replied { border-right: 5px solid var(--success-color); opacity: 0.9; }
        
        .msg-header { background: var(--bg-light); padding: 20px 25px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 15px; }
        
        .sender-box { display: flex; align-items: center; gap: 15px; }
        .sender-avatar { width: 50px; height: 50px; background: var(--brand-primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: 900; box-shadow: var(--shadow-sm); }
        .sender-info h3 { margin: 0 0 5px 0; color: var(--text-primary); font-size: 18px; font-weight: 900; }
        .sender-info .date { margin: 0; color: var(--text-secondary); font-size: 12px; font-weight: bold; }
        
        .status-badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .badge-pending { background: rgba(255, 193, 7, 0.1); color: #d39e00; border: 1px solid rgba(255, 193, 7, 0.3); }
        .badge-replied { background: rgba(40, 167, 69, 0.1); color: var(--success-color); border: 1px solid rgba(40, 167, 69, 0.3); }
        
        .msg-body { padding: 25px; }
        .msg-subject { font-size: 18px; font-weight: 800; color: var(--brand-primary); margin-top: 0; margin-bottom: 15px; }
        .msg-content { color: var(--text-primary); line-height: 1.8; font-size: 15px; white-space: pre-wrap; background: var(--bg-color); padding: 20px; border-radius: var(--radius-sm); border: 1px solid var(--border-color); border-right: 3px solid var(--brand-light); }
        
        .student-enrollment { margin-top: 20px; padding: 15px; background: rgba(26, 115, 232, 0.05); border: 1px dashed var(--brand-primary); border-radius: var(--radius-sm); font-size: 13px; color: var(--text-secondary); font-weight: bold; line-height: 1.6; }
        
        .reply-section { padding: 25px; background: var(--bg-light); border-top: 1px solid var(--border-color); }
        
        .admin-reply-box { background: rgba(40, 167, 69, 0.05); border: 1px solid rgba(40, 167, 69, 0.2); border-right: 4px solid var(--success-color); padding: 20px; border-radius: var(--radius-sm); }
        .admin-reply-box h4 { margin: 0 0 10px 0; color: var(--success-color); font-size: 15px; display: flex; align-items: center; gap: 8px; }
        .admin-reply-box p { margin: 0; color: var(--text-primary); line-height: 1.7; font-size: 14px; white-space: pre-wrap; }
        
        .reply-form { display: flex; flex-direction: column; gap: 15px; }
        .reply-form textarea { min-height: 100px; resize: vertical; border-color: var(--warning-color); }
        .btn-send-reply { background: var(--brand-primary); color: white; border: none; padding: 12px 20px; border-radius: var(--radius-sm); font-weight: 800; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; justify-content: center; gap: 8px; font-size: 15px; width: fit-content; }
        .btn-send-reply:hover { background: #0d47a1; transform: translateY(-2px); box-shadow: var(--shadow-sm); }
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
            
            <div class="academic-header-actions skeleton" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; max-width: 900px; margin: 0 auto 30px auto;">
                <h2 style="margin: 0; color: var(--text-primary); display: flex; align-items: center; gap: 10px;">
                    <img src="../dashboard_design/images/admin_icon_messages.png" alt="Messages" style="width: 40px; height: 40px; object-fit: contain;" onerror="this.src='../dashboard_design/images/admin_icon_dashboard.png'">
                    رسائل الطلاب (الدعم الفني)
                </h2>
                
                <?php if($pending_count > 0): ?>
                    <div style="background: var(--bg-surface); border: 1px solid var(--warning-color); padding: 8px 15px; border-radius: var(--radius-pill); font-weight: bold; font-size: 14px; color: var(--warning-color); box-shadow: var(--shadow-sm);">
                        رسائل بانتظار الرد: <span><?php echo $pending_count; ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (isset($_SESSION['msg'])): ?>
                <div style="padding: 15px; background: rgba(40, 167, 69, 0.1); color: var(--success-color); border: 1px solid var(--success-color); border-radius: var(--radius-md); margin-bottom: 20px; font-weight: bold; max-width: 900px; margin-left: auto; margin-right: auto;">
                    <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($_SESSION['msg']); unset($_SESSION['msg']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div style="padding: 15px; background: rgba(220, 53, 69, 0.1); color: var(--danger-color); border: 1px solid var(--danger-color); border-radius: var(--radius-md); margin-bottom: 20px; font-weight: bold; max-width: 900px; margin-left: auto; margin-right: auto;">
                    <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <div class="messages-grid skeleton">
                <?php if (empty($messages)): ?>
                    <div style="text-align: center; padding: 60px; background: var(--bg-surface); border-radius: var(--radius-lg); border: 1px dashed var(--border-color); color: var(--text-secondary);">
                        <i class="fa-solid fa-inbox" style="font-size: 60px; margin-bottom: 15px; color: var(--success-color); opacity: 0.8;"></i>
                        <p style="font-size: 18px; font-weight: bold;">صندوق الوارد فارغ. تم الرد على جميع استفسارات الطلاب!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($messages as $msg): ?>
                        <?php 
                            $is_replied = ($msg['status'] === 'replied');
                            $card_class = $is_replied ? 'msg-replied' : 'msg-pending';
                            $avatar_char = mb_substr($msg['username'], 0, 1, "UTF-8");
                        ?>
                        <div class="msg-card <?php echo $card_class; ?>">
                            <div class="msg-header">
                                <div class="sender-box">
                                    <div class="sender-avatar"><?php echo htmlspecialchars($avatar_char); ?></div>
                                    <div class="sender-info">
                                        <h3><?php echo htmlspecialchars($msg['username']); ?></h3>
                                        <p class="date" dir="ltr"><i class="fa-regular fa-clock"></i> <?php echo date('Y-m-d H:i', strtotime($msg['created_at'])); ?></p>
                                    </div>
                                </div>
                                <div>
                                    <?php if ($is_replied): ?>
                                        <span class="status-badge badge-replied"><i class="fa-solid fa-check-double"></i> تمت الإجابة</span>
                                    <?php else: ?>
                                        <span class="status-badge badge-pending"><i class="fa-solid fa-hourglass-half"></i> بانتظار ردك</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="msg-body">
                                <h4 class="msg-subject"><i class="fa-regular fa-comment-dots text-[var(--text-secondary)]"></i> <?php echo htmlspecialchars($msg['message_subject']); ?></h4>
                                <div class="msg-content"><?php echo nl2br(htmlspecialchars($msg['message_content'])); ?></div>
                                
                                <div class="student-enrollment">
                                    <span style="color: var(--brand-primary);"><i class="fa-solid fa-layer-group"></i> المسار الأكاديمي للطالب:</span><br>
                                    <div style="margin-top: 8px;">
                                        <?php echo $msg['enrollment_info'] ?: '<span style="color: var(--danger-color);">الطالب غير مسجل في أي مقرر أكاديمي حالياً.</span>'; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="reply-section">
                                <?php if ($is_replied): ?>
                                    <div class="admin-reply-box">
                                        <h4><i class="fa-solid fa-user-shield"></i> رد الإدارة (أنت):</h4>
                                        <p><?php echo nl2br(htmlspecialchars($msg['admin_reply'])); ?></p>
                                        <small style="display: block; margin-top: 10px; color: var(--success-color); font-weight: bold;" dir="ltr">
                                            <i class="fa-solid fa-reply"></i> <?php echo date('Y-m-d H:i', strtotime($msg['replied_at'])); ?>
                                        </small>
                                    </div>
                                <?php else: ?>
                                    <form action="process.php" method="POST" class="reply-form">
                                        <input type="hidden" name="message_id" value="<?php echo $msg['message_id']; ?>">
                                        <input type="hidden" name="student_user_id" value="<?php echo $msg['student_user_id']; ?>">
                                        
                                        <div class="form-group" style="margin: 0;">
                                            <label class="form-label" style="color: var(--warning-color);"><i class="fa-solid fa-pen"></i> اكتب ردك للطالب هنا:</label>
                                            <textarea name="admin_reply" class="form-control" required placeholder="مرحباً، بخصوص استفسارك..."></textarea>
                                        </div>
                                        
                                        <button type="submit" class="btn-send-reply">
                                            <i class="fa-solid fa-paper-plane"></i> إرسال الرد
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div>
    </main>

    <script src="../assets/js/admin_global.js"></script>
</body>
</html>