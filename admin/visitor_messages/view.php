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

$msg_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$msg_id) {
    header("Location: index.php");
    exit();
}

$breadcrumbs = ["التواصل" => "", "رسائل الزوار" => "index.php", "قراءة الرسالة" => ""];

try {
    $stmt = $pdo->prepare("SELECT * FROM visitor_messages WHERE id = ?");
    $stmt->execute([$msg_id]);
    $message = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$message) {
        $_SESSION['error'] = "الرسالة غير موجودة أو تم حذفها.";
        header("Location: index.php");
        exit();
    }

    // تحديث حالة الرسالة إلى "مقروءة" بمجرد فتحها
    if ($message['is_read'] == 0) {
        $update_stmt = $pdo->prepare("UPDATE visitor_messages SET is_read = 1 WHERE id = ?");
        $update_stmt->execute([$msg_id]);
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
    <title>قراءة الرسالة - Mo7adaraty</title>
    <link rel="icon" type="image/svg+xml" href="../../assets/images/favicon.svg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/admin_global.css">
    <style>
        .ticket-card { background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); overflow: hidden; max-width: 850px; margin: 0 auto; }
        
        .ticket-header { background: var(--bg-light); padding: 25px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 20px; }
        .sender-box { display: flex; align-items: center; gap: 15px; }
        .sender-avatar { width: 55px; height: 55px; background: var(--brand-primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 22px; font-weight: 900; box-shadow: var(--shadow-sm); border: 2px solid rgba(26,115,232,0.2); }
        .sender-info h3 { margin: 0 0 5px 0; color: var(--text-primary); font-size: 18px; font-weight: 900; }
        .sender-info p { margin: 0; color: var(--text-secondary); font-size: 14px; font-weight: bold; }
        
        .ticket-meta { text-align: left; }
        .ticket-date { display: inline-flex; align-items: center; gap: 8px; background: var(--bg-surface); padding: 8px 15px; border-radius: 20px; border: 1px solid var(--border-color); font-size: 13px; color: var(--text-secondary); font-weight: bold; }
        
        .ticket-body { padding: 30px; }
        .ticket-subject { font-size: 20px; font-weight: 900; color: var(--text-primary); margin-top: 0; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px dashed var(--border-color); display: flex; align-items: center; gap: 10px; }
        .ticket-content { color: var(--text-primary); line-height: 1.9; font-size: 16px; white-space: pre-wrap; background: var(--bg-light); padding: 25px; border-radius: var(--radius-md); border: 1px solid var(--border-color); font-weight: 500; }
        
        .ticket-footer { padding: 20px 30px; background: var(--bg-surface); border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
        
        .btn-action { padding: 12px 20px; border-radius: var(--radius-sm); text-decoration: none; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; border: none; font-family: var(--font-family); cursor: pointer; font-size: 14px; }
        .btn-back { background: var(--bg-light); color: var(--text-primary); border: 1px solid var(--border-color); }
        .btn-back:hover { background: var(--border-color); }
        .btn-reply { background: var(--brand-primary); color: white; }
        .btn-reply:hover { background: #0d47a1; transform: translateY(-2px); box-shadow: var(--shadow-sm); }
        .btn-delete { background: rgba(220, 53, 69, 0.1); color: var(--danger-color); }
        .btn-delete:hover { background: var(--danger-color); color: white; }
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
            
            <div class="ticket-card skeleton">
                
                <div class="ticket-header">
                    <div class="sender-box">
                        <div class="sender-avatar">
                            <?php echo htmlspecialchars(mb_substr($message['name'], 0, 1, "UTF-8")); ?>
                        </div>
                        <div class="sender-info">
                            <h3><?php echo htmlspecialchars($message['name']); ?></h3>
                            <p><i class="fa-solid fa-envelope"></i> <?php echo htmlspecialchars($message['email']); ?></p>
                        </div>
                    </div>
                    <div class="ticket-meta">
                        <div class="ticket-date" dir="ltr">
                            <i class="fa-regular fa-clock text-[var(--brand-primary)]"></i> <?php echo date('Y-m-d | H:i', strtotime($message['created_at'])); ?> 
                        </div>
                    </div>
                </div>

                <div class="ticket-body">
                    <h2 class="ticket-subject">
                        <i class="fa-solid fa-quote-right text-[var(--border-color)]"></i> 
                        <?php echo htmlspecialchars($message['subject'] ?: 'رسالة بدون عنوان'); ?>
                    </h2>
                    <div class="ticket-content"><?php echo htmlspecialchars($message['message']); ?></div>
                </div>

                <div class="ticket-footer">
                    <a href="index.php" class="btn-action btn-back"><i class="fa-solid fa-arrow-right"></i> العودة للصندوق</a>
                    
                    <div style="display: flex; gap: 10px;">
                        <form action="delete.php" method="POST" style="margin: 0;" onsubmit="return confirm('تأكيد حذف الرسالة نهائياً؟');">
                            <input type="hidden" name="id" value="<?php echo $message['id']; ?>">
                            <button type="submit" class="btn-action btn-delete">
                                <i class="fa-solid fa-trash"></i> حذف
                            </button>
                        </form>
                        
                        <a href="mailto:<?php echo htmlspecialchars($message['email']); ?>?subject=رد من الإدارة: <?php echo rawurlencode($message['subject']); ?>" class="btn-action btn-reply">
                            <i class="fa-solid fa-reply"></i> الرد عبر البريد
                        </a>
                    </div>
                </div>

            </div>

        </div>
    </main>

    <script src="../assets/js/admin_global.js"></script>
</body>
</html>