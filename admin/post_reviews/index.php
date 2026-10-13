<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية الصفحة
if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

try {
    $stmt = $pdo->query("
        SELECT p.*, u.username, u.profile_picture
        FROM posts p
        JOIN users u ON p.user_id = u.user_id
        WHERE p.status = 'pending'
        ORDER BY p.created_at ASC
    ");
    $pending_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("خطأ في جلب المنشورات: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مراجعة المشاركات - لوحة التحكم</title>
    <link rel="icon" type="image/svg+xml" href="/mo7adaraty/assets/images/favicon.svg">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;900&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="../../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/admin_global.css">
    
    <style>
        .posts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .post-card {
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .post-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        .post-header {
            padding: 15px 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 12px;
            background: var(--bg-secondary);
        }

        .post-header img {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--brand-primary);
        }

        .post-header-info {
            display: flex;
            flex-direction: column;
        }

        .post-header-info strong {
            color: var(--text-primary);
            font-size: 1.05rem;
        }

        .post-header-info span {
            color: var(--text-secondary);
            font-size: 0.85rem;
        }

        .post-body {
            padding: 20px;
            color: var(--text-primary);
            flex-grow: 1;
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .post-body img {
            width: 100%;
            max-height: 250px;
            object-fit: cover;
            border-radius: 8px;
            margin-top: 15px;
            border: 1px solid var(--border-color);
        }

        .post-actions {
            padding: 15px 20px;
            background-color: var(--bg-secondary);
            border-top: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .action-inputs {
            display: flex;
            gap: 10px;
            width: 100%;
        }

        .rejection-input {
            flex-grow: 1;
            padding: 10px 15px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background: var(--bg-primary);
            color: var(--text-primary);
            font-family: inherit;
            font-size: 0.9rem;
            outline: none;
            transition: border-color 0.3s;
        }

        .rejection-input:focus {
            border-color: #dc3545;
        }

        .btn-approve {
            background-color: rgba(40, 167, 69, 0.1);
            color: #28a745;
            border: 1px solid #28a745;
            padding: 10px 15px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: inherit;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            width: 100%;
        }
        .btn-approve:hover { background-color: #28a745; color: white; }
        
        .btn-reject {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            border: 1px solid #dc3545;
            padding: 10px 15px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: inherit;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            white-space: nowrap;
        }
        .btn-reject:hover { background-color: #dc3545; color: white; }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-secondary);
            background: var(--bg-primary);
            border-radius: 12px;
            border: 1px dashed var(--border-color);
        }

        .alert-msg {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: bold;
        }
        .alert-success { background: rgba(40, 167, 69, 0.1); color: #28a745; border: 1px solid #28a745; }
        .alert-error { background: rgba(220, 53, 69, 0.1); color: #dc3545; border: 1px solid #dc3545; }
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
            
            <div style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;" class="skeleton">
                <div>
                    <h1 style="margin: 0; color: var(--text-primary); display: flex; align-items: center; gap: 10px;">
                        <img src="../dashboard_design/images/admin_icon_post_reviews.png" alt="Post Reviews" style="width: 40px;" onerror="this.src='../../assets/icons/check-to-slot.png'">
                        مراجعة المنشورات
                    </h1>
                    <p style="color: var(--text-secondary); margin-top: 5px;">مراجعة منشورات الطلاب المعلقة قبل نشرها في ساحة المجتمع.</p>
                </div>
            </div>

            <?php if (isset($_SESSION['review_status'])): ?>
                <div class="alert-msg <?php echo $_SESSION['review_status']['type'] === 'success' ? 'alert-success' : 'alert-error'; ?> skeleton">
                    <i class="fa-solid <?php echo $_SESSION['review_status']['type'] === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation'; ?>"></i>
                    <?php echo htmlspecialchars($_SESSION['review_status']['message']); ?>
                </div>
                <?php unset($_SESSION['review_status']); ?>
            <?php endif; ?>

            <?php if (empty($pending_posts)): ?>
                <div class="empty-state skeleton">
                    <img src="../../assets/icons/check-to-slot.png" alt="Empty" style="width: 80px; opacity: 0.3; margin-bottom: 15px;" onerror="this.src='../../assets/images/favicon.png'">
                    <h3 style="color: var(--text-primary); margin-bottom: 5px;">لا توجد مشاركات معلقة</h3>
                    <p>جميع المشاركات تمت مراجعتها، ساحة المجتمع في أفضل حالاتها!</p>
                </div>
            <?php else: ?>
                <div class="posts-grid skeleton">
                    <?php foreach ($pending_posts as $post): ?>
                        <div class="post-card">
                            <div class="post-header">
                                <img src="../../uploads/profile_pictures/<?php echo htmlspecialchars($post['profile_picture'] ?: 'default_profile.png'); ?>" alt="صورة المستخدم" onerror="this.src='../../assets/icons/student.png'">
                                <div class="post-header-info">
                                    <strong><?php echo htmlspecialchars($post['username']); ?></strong>
                                    <span><i class="fa-regular fa-clock"></i> <?php echo date('Y-m-d H:i', strtotime($post['created_at'])); ?></span>
                                </div>
                            </div>
                            
                            <div class="post-body">
                                <?php if(!empty($post['post_text'])): ?>
                                    <div style="white-space: pre-wrap; word-break: break-word;"><?php echo htmlspecialchars($post['post_text']); ?></div>
                                <?php endif; ?>
                                
                                <?php if(!empty($post['image_path'])): ?>
                                    <img src="../../<?php echo htmlspecialchars($post['image_path']); ?>" alt="صورة المنشور" loading="lazy">
                                <?php endif; ?>
                            </div>
                            
                            <div class="post-actions">
                                <form action="process_post.php" method="POST" style="margin: 0;">
                                    <input type="hidden" name="post_id" value="<?php echo $post['post_id']; ?>">
                                    <button type="submit" name="action" value="approve" class="btn-approve" title="نشر في ساحة المجتمع">
                                        <i class="fa-solid fa-check"></i> قبول ونشر
                                    </button>
                                </form>
                                
                                <form action="process_post.php" method="POST" style="margin: 0; display: flex; gap: 10px;">
                                    <input type="hidden" name="post_id" value="<?php echo $post['post_id']; ?>">
                                    <input type="text" name="rejection_reason" class="rejection-input" placeholder="سبب الرفض (إلزامي للرفض)..." required>
                                    <button type="submit" name="action" value="reject" class="btn-reject" title="رفض وحذف المنشور">
                                        <i class="fa-solid fa-times"></i> رفض
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>
    </main>

    <script src="../assets/js/admin_global.js"></script>
</body>
</html>