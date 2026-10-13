<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../config/db_connexion.php';

// حماية صفحة الإدارة
if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

$breadcrumbs = ["التواصل" => "", "صندوق بريد الزوار" => ""];

try {
    // جلب جميع الرسائل (الأحدث أولاً)
    $stmt = $pdo->query("SELECT * FROM visitor_messages ORDER BY created_at DESC");
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // حساب عدد الرسائل غير المقروءة للإحصائيات
    $stmt_unread = $pdo->query("SELECT COUNT(*) FROM visitor_messages WHERE is_read = 0");
    $unread_count = $stmt_unread->fetchColumn();

} catch (PDOException $e) {
    die("خطأ في جلب الرسائل: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة رسائل الزوار - Mo7adaraty</title>
    <link rel="icon" type="image/svg+xml" href="../../assets/images/favicon.svg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/admin_global.css">
    
    <style>
        /* إحصائيات البريد */
        .stats-header { display: flex; gap: 20px; margin-bottom: 30px; flex-wrap: wrap; }
        .stat-box { background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 20px; flex: 1; min-width: 250px; display: flex; align-items: center; gap: 20px; box-shadow: var(--shadow-sm); transition: 0.3s; }
        .stat-box:hover { transform: translateY(-3px); box-shadow: var(--shadow-md); border-color: var(--brand-primary); }
        .stat-icon { width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; }
        .icon-total { background: rgba(26, 115, 232, 0.1); color: var(--brand-primary); }
        .icon-unread { background: rgba(220, 53, 69, 0.1); color: var(--danger-color); }
        .stat-info h4 { margin: 0 0 8px 0; color: var(--text-secondary); font-size: 15px; }
        .stat-info p { margin: 0; font-size: 28px; font-weight: 900; color: var(--text-primary); }

        /* تصميم صندوق الوارد (Inbox Style) */
        .inbox-container { background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); overflow: hidden; }
        .inbox-row { display: flex; align-items: center; padding: 15px 20px; border-bottom: 1px solid var(--border-color); transition: 0.2s; text-decoration: none; color: inherit; gap: 15px; position: relative; }
        .inbox-row:last-child { border-bottom: none; }
        .inbox-row:hover { background: var(--bg-light); }
        
        .msg-unread { background: rgba(26, 115, 232, 0.03); border-right: 4px solid var(--brand-primary); }
        .msg-unread .sender-name, .msg-unread .msg-subject { font-weight: 900; color: var(--text-primary); }
        .msg-read { border-right: 4px solid transparent; opacity: 0.85; }
        .msg-read .sender-name, .msg-read .msg-subject { font-weight: 500; color: var(--text-secondary); }

        .sender-avatar { width: 45px; height: 45px; border-radius: 50%; background: var(--brand-light); color: var(--brand-primary); display: flex; align-items: center; justify-content: center; font-size: 18px; font-weight: 900; flex-shrink: 0; border: 1px solid rgba(26,115,232,0.2); }
        
        .msg-content { flex-grow: 1; min-width: 0; display: flex; flex-direction: column; gap: 5px; }
        .msg-top { display: flex; justify-content: space-between; align-items: center; gap: 10px; }
        .sender-name { font-size: 16px; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .msg-date { font-size: 12px; color: var(--text-secondary); white-space: nowrap; font-weight: bold; }
        
        .msg-bottom { display: flex; align-items: center; gap: 10px; }
        .msg-subject { font-size: 14px; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 70%; }
        
        .msg-actions { opacity: 0; transition: 0.2s; display: flex; gap: 10px; margin-right: auto; }
        .inbox-row:hover .msg-actions { opacity: 1; }
        
        .action-btn { background: transparent; border: none; padding: 8px; border-radius: 6px; cursor: pointer; transition: 0.2s; display: flex; align-items: center; justify-content: center; text-decoration: none; }
        .btn-view { color: var(--info-color); background: rgba(23, 162, 184, 0.1); }
        .btn-view:hover { background: var(--info-color); color: #fff; }
        .btn-del { color: var(--danger-color); background: rgba(220, 53, 69, 0.1); }
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
                    <img src="../dashboard_design/images/admin_icon_messages.png" alt="Messages" style="width: 40px; height: 40px; object-fit: contain;" onerror="this.src='../dashboard_design/images/admin_icon_dashboard.png'">
                    صندوق بريد الزوار
                </h2>
            </div>

            <?php if (isset($_SESSION['msg'])): ?>
                <div style="padding: 15px; background: rgba(40, 167, 69, 0.1); color: var(--success-color); border: 1px solid var(--success-color); border-radius: var(--radius-md); margin-bottom: 20px; font-weight: bold;">
                    <i class="fa-solid fa-circle-check"></i> <?php echo $_SESSION['msg']; unset($_SESSION['msg']); ?>
                </div>
            <?php endif; ?>

            <div class="stats-header skeleton">
                <div class="stat-box">
                    <div class="stat-icon icon-total"><i class="fa-solid fa-inbox"></i></div>
                    <div class="stat-info">
                        <h4>إجمالي الرسائل الواردة</h4>
                        <p><?php echo count($messages); ?></p>
                    </div>
                </div>
                <div class="stat-box">
                    <div class="stat-icon icon-unread"><i class="fa-solid fa-envelope"></i></div>
                    <div class="stat-info">
                        <h4>رسائل جديدة (غير مقروءة)</h4>
                        <p><?php echo $unread_count; ?></p>
                    </div>
                </div>
            </div>

            <div class="inbox-container skeleton">
                <?php if (empty($messages)): ?>
                    <div style="text-align: center; padding: 60px; color: var(--text-secondary);">
                        <i class="fa-solid fa-box-open" style="font-size: 60px; opacity: 0.5; margin-bottom: 15px;"></i>
                        <h3 style="margin: 0;">صندوق الوارد فارغ</h3>
                        <p style="margin-top: 5px; font-weight: bold;">لا توجد أي رسائل من الزوار حتى الآن.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($messages as $msg): ?>
                        <?php 
                            $is_unread = ($msg['is_read'] == 0); 
                            $row_class = $is_unread ? 'msg-unread' : 'msg-read';
                            $first_char = mb_substr($msg['name'], 0, 1, "UTF-8");
                        ?>
                        <div class="inbox-row <?php echo $row_class; ?>">
                            <div class="sender-avatar">
                                <?php echo htmlspecialchars($first_char); ?>
                            </div>
                            
                            <div class="msg-content">
                                <div class="msg-top">
                                    <h4 class="sender-name"><?php echo htmlspecialchars($msg['name']); ?></h4>
                                    <span class="msg-date" dir="ltr"><?php echo date('M d, H:i', strtotime($msg['created_at'])); ?></span>
                                </div>
                                <div class="msg-bottom">
                                    <?php if($is_unread): ?>
                                        <span style="background: var(--danger-color); width: 8px; height: 8px; border-radius: 50%; display: inline-block;"></span>
                                    <?php endif; ?>
                                    <p class="msg-subject"><?php echo htmlspecialchars($msg['subject'] ?: 'بدون موضوع'); ?></p>
                                    
                                    <div class="msg-actions">
                                        <a href="view.php?id=<?php echo $msg['id']; ?>" class="action-btn btn-view" title="قراءة الرسالة">
                                            <i class="fa-solid fa-eye"></i> قراءة
                                        </a>
                                        <form action="delete.php" method="POST" style="margin: 0;" onsubmit="return confirm('تأكيد حذف الرسالة نهائياً؟');">
                                            <input type="hidden" name="id" value="<?php echo $msg['id']; ?>">
                                            <button type="submit" class="action-btn btn-del" title="حذف الرسالة">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
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