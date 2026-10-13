<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية الصفحة
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// إعدادات محرك الترجمة والاتجاهات
$allowed_langs = ['ar', 'en', 'fr'];
$lang_code = (isset($_SESSION['lang']) && in_array($_SESSION['lang'], $allowed_langs)) ? $_SESSION['lang'] : 'fr';
$lang = require "../lang/{$lang_code}.php";
$dir = ($lang_code === 'ar') ? 'rtl' : 'ltr';

try {
    // جلب منشورات المستخدم الحالي التي هي فقط قيد المراجعة
    $stmt = $pdo->prepare("
        SELECT *
        FROM posts
        WHERE user_id = ? AND status = 'pending'
        ORDER BY created_at DESC
    ");
    $stmt->execute([$user_id]);
    $pending_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("خطأ في جلب البيانات: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang_code; ?>" dir="<?php echo $dir; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang['my_pending_posts_page_title'] ?? 'مشاركاتي قيد المراجعة'; ?></title>
    <link rel="icon" type="image/svg+xml" href="/mo7adaraty/assets/images/favicon.svg">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;900&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="../../assets/css/global.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../assets/css/student.css?v=<?php echo time(); ?>">

    <style>
        .pending-posts-page {
            max-width: 760px;
            margin: 30px auto;
            padding: 0 15px;
        }
        .pending-header-card {
            background: var(--bg-surface, #ffffff);
            border: 1px solid var(--border-color, #e2e8f0);
            border-radius: 16px;
            padding: 18px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
        }
        .pending-title {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--text-primary, #1e293b);
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
        }
        .pending-title i {
            color: #d97706;
        }
        .back-to-feed-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 20px;
            background: rgba(2, 132, 199, 0.08);
            color: var(--brand-primary, #0284c7);
            text-decoration: none;
            font-weight: 700;
            font-size: 0.9rem;
            transition: all 0.2s ease;
        }
        .back-to-feed-btn:hover {
            background: var(--brand-primary, #0284c7);
            color: #ffffff;
        }
        .pending-feed-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .pending-card {
            background: var(--bg-surface, #ffffff);
            border: 1px solid var(--border-color, #e2e8f0);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .pending-card:hover {
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
        }
        .pending-card-top {
            padding: 14px 20px;
            background: var(--bg-body, #f8f9fa);
            border-bottom: 1px solid var(--border-color, #e2e8f0);
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.85rem;
            color: var(--text-secondary, #64748b);
        }
        .status-badge-pending {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(217, 119, 6, 0.12);
            color: #d97706;
            font-weight: 700;
            font-size: 0.8rem;
            padding: 4px 10px;
            border-radius: 12px;
        }
        .pending-card-content {
            padding: 20px;
        }
        .pending-post-text {
            font-size: 1rem;
            line-height: 1.7;
            color: var(--text-primary, #1e293b);
            margin: 0;
            word-break: break-word;
        }
        .pending-post-text.has-bg {
            padding: 30px 20px;
            border-radius: 12px;
            color: #ffffff !important;
            font-size: 1.2rem;
            font-weight: bold;
            text-align: center;
            margin-bottom: 10px;
        }
        .bg-style-blue { background: linear-gradient(135deg, #1e3a8a, #3b82f6); }
        .bg-style-red { background: linear-gradient(135deg, #881337, #ef4444); }
        .bg-style-green { background: linear-gradient(135deg, #064e3b, #10b981); }
        .bg-style-yellow { background: linear-gradient(135deg, #78350f, #f59e0b); }
        .bg-style-purple { background: linear-gradient(135deg, #581c87, #a855f7); }
        .pending-post-media {
            margin-top: 15px;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid var(--border-color, #e2e8f0);
        }
        .pending-post-media img {
            width: 100%;
            max-height: 380px;
            object-fit: cover;
            display: block;
        }
        .pending-card-actions {
            padding: 12px 20px;
            border-top: 1px solid var(--border-color, #e2e8f0);
            background: var(--bg-surface, #ffffff);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        .action-btn-edit {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 8px;
            background: rgba(217, 119, 6, 0.1);
            color: #d97706;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.88rem;
            border: 1px solid rgba(217, 119, 6, 0.3);
            transition: all 0.2s ease;
        }
        .action-btn-edit:hover {
            background: #d97706;
            color: #ffffff;
        }
        .action-btn-delete {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 8px;
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            font-weight: 700;
            font-size: 0.88rem;
            border: 1px solid rgba(239, 68, 68, 0.3);
            cursor: pointer;
            font-family: inherit;
            transition: all 0.2s ease;
        }
        .action-btn-delete:hover {
            background: #ef4444;
            color: #ffffff;
        }
        .empty-pending-state {
            text-align: center;
            padding: 50px 20px;
            background: var(--bg-surface, #ffffff);
            border-radius: 16px;
            border: 1px dashed var(--border-color, #cbd5e1);
        }
        .empty-pending-state i {
            font-size: 3rem;
            color: #10b981;
            margin-bottom: 15px;
        }
    </style>
</head>
<body class="student-body">

    <script>
        (function() {
            const savedTheme = localStorage.getItem('mo7adaraty_theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>

    <?php require_once '../../templates/header.php'; ?>

    <main class="pending-posts-page">
        <div class="pending-header-card">
            <h1 class="pending-title">
                <i class="fa-solid fa-hourglass-half"></i> 
                <?php echo $lang['my_pending_posts'] ?? 'مشاركاتي قيد المراجعة'; ?>
            </h1>
            <a href="index.php" class="back-to-feed-btn">
                <i class="fa-solid fa-arrow-right"></i> <?php echo $lang['back_to_feed'] ?? 'العودة إلى الساحة'; ?>
            </a>
        </div>

        <?php if (isset($_SESSION['post_action_status'])): ?>
            <div style="margin-bottom: 20px; padding: 12px 18px; border-radius: 10px; font-weight: bold; display: flex; align-items: center; gap: 8px; <?php echo $_SESSION['post_action_status']['type'] === 'success' ? 'background: rgba(16, 185, 129, 0.1); color: #059669; border: 1px solid #10b981;' : 'background: rgba(239, 68, 68, 0.1); color: #dc2626; border: 1px solid #ef4444;'; ?>">
                <i class="fa-solid <?php echo $_SESSION['post_action_status']['type'] === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation'; ?>"></i>
                <?php echo htmlspecialchars($_SESSION['post_action_status']['message']); ?>
            </div>
            <?php unset($_SESSION['post_action_status']); ?>
        <?php endif; ?>

        <?php if (empty($pending_posts)): ?>
            <div class="empty-pending-state">
                <i class="fa-regular fa-circle-check"></i>
                <h3 style="color: var(--text-primary); margin-bottom: 6px;"><?php echo $lang['no_pending_posts'] ?? 'لا توجد مشاركات معلقة'; ?></h3>
                <p style="color: var(--text-secondary); margin: 0; font-size: 0.95rem;"><?php echo $lang['no_pending_posts_desc'] ?? 'جميع منشوراتك تم اعتمادها ومشاركتها مع الطلاب.'; ?></p>
            </div>
        <?php else: ?>
            <div class="pending-feed-list">
                <?php foreach ($pending_posts as $post): ?>
                    <div class="pending-card">
                        <div class="pending-card-top">
                            <span>
                                <i class="fa-regular fa-clock"></i> 
                                <?php echo date('Y-m-d H:i', strtotime($post['created_at'])); ?>
                            </span>
                            <span class="status-badge-pending">
                                <i class="fa-solid fa-hourglass-half"></i> قيد المراجعة
                            </span>
                        </div>

                        <div class="pending-card-content">
                            <?php if (!empty($post['post_text'])): ?>
                                <p class="pending-post-text <?php echo (!empty($post['background_style']) && $post['background_style'] !== 'none') ? 'has-bg bg-style-' . htmlspecialchars($post['background_style']) : ''; ?>">
                                    <?php echo nl2br(htmlspecialchars($post['post_text'])); ?>
                                </p>
                            <?php endif; ?>
                            
                            <?php if (!empty($post['image_path'])): ?>
                                <div class="pending-post-media">
                                    <img src="../../<?php echo htmlspecialchars($post['image_path']); ?>" alt="صورة المنشور" loading="lazy">
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="pending-card-actions">
                            <a href="index.php?edit_post=<?php echo $post['post_id']; ?>#create-post-card" class="action-btn-edit">
                                <i class="fa-solid fa-pen"></i> <?php echo $lang['edit'] ?? 'تعديل'; ?>
                            </a>

                            <form action="delete_post.php" method="POST" class="delete-post-form" style="margin: 0;">
                                <input type="hidden" name="post_id" value="<?php echo $post['post_id']; ?>">
                                <button type="button" class="action-btn-delete btn-delete-post">
                                    <i class="fa-solid fa-trash-can"></i> <?php echo $lang['delete'] ?? 'حذف'; ?>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            $('.btn-delete-post').on('click', function(e) {
                e.preventDefault();
                var form = $(this).closest('form');

                Swal.fire({
                    title: 'حذف المنشور',
                    text: 'هل أنت متأكد من رغبتك في حذف هذا المنشور المعلق نهائياً؟',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: '<i class="fa-solid fa-trash-can"></i> نعم، حذف',
                    cancelButtonText: 'إلغاء',
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#64748b',
                    reverseButtons: true,
                    focusCancel: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    </script>

    <?php require_once '../../templates/footer.php'; ?>

</body>
</html>