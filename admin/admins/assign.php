<?php
session_start();
require_once '../../config/db_connexion.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'assign') {
    $assign_user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    if ($assign_user_id) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET role = 'admin' WHERE user_id = ?");
            $stmt->execute([$assign_user_id]);
            $_SESSION['msg'] = "تم تعيين المستخدم كمشرف بنجاح.";
        } catch (PDOException $e) {
            $_SESSION['error'] = "حدث خطأ أثناء التعيين: " . $e->getMessage();
        }
    }
    header("Location: index.php");
    exit();
}

$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$search_results = [];

if ($search_query) {
    try {
        $stmt = $pdo->prepare("
            SELECT user_id, username, email, profile_picture, created_at 
            FROM users 
            WHERE role = 'student' AND (username LIKE ? OR email LIKE ?)
            LIMIT 20
        ");
        $like_query = "%$search_query%";
        $stmt->execute([$like_query, $like_query]);
        $search_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("خطأ في جلب البيانات: " . $e->getMessage());
    }
}

$breadcrumbs = ["شؤون المستخدمين" => "", "إدارة المشرفين" => "index.php", "تعيين مشرف جديد" => ""];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعيين مشرف جديد - Mo7adaraty</title>
    <link rel="icon" type="image/svg+xml" href="../../assets/images/favicon.svg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/admin_global.css">
    <style>
        .search-box { background: var(--bg-surface); padding: 25px; border-radius: var(--radius-lg); border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); margin-bottom: 30px; }
        .search-form { display: flex; gap: 15px; }
        .search-input { flex-grow: 1; padding: 12px 20px; border: 1px solid var(--border-color); border-radius: 8px; font-family: inherit; font-size: 15px; background: var(--bg-light); color: var(--text-primary); }
        .btn-search { background: var(--brand-primary); color: #fff; border: none; padding: 0 25px; border-radius: 8px; font-weight: bold; cursor: pointer; display: flex; align-items: center; gap: 8px; font-family: inherit; font-size: 15px; }
        
        .results-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .result-card { background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 20px; display: flex; align-items: center; gap: 15px; box-shadow: var(--shadow-sm); transition: 0.3s; }
        .result-card:hover { transform: translateY(-3px); border-color: var(--success-color); }
        .result-avatar { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 2px solid var(--bg-light); }
        .result-info { flex-grow: 1; }
        .result-info h3 { margin: 0 0 5px 0; font-size: 15px; font-weight: 800; color: var(--text-primary); }
        .result-info p { margin: 0; font-size: 12px; color: var(--text-secondary); font-weight: bold; }
        .btn-assign { background: rgba(40, 167, 69, 0.1); color: var(--success-color); border: none; padding: 8px 15px; border-radius: 6px; font-size: 13px; font-weight: bold; cursor: pointer; transition: 0.2s; display: flex; align-items: center; gap: 5px; font-family: inherit; }
        .btn-assign:hover { background: var(--success-color); color: #fff; }
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
                    <i class="fa-solid fa-user-plus" style="font-size: 30px; color: var(--brand-primary);"></i>
                    البحث وتعيين مشرف
                </h2>
                <a href="index.php" class="btn-add-new" style="background: var(--bg-surface); color: var(--text-primary); border: 1px solid var(--border-color);">
                    <i class="fa-solid fa-arrow-right"></i> العودة للمشرفين
                </a>
            </div>

            <div class="search-box skeleton">
                <form method="GET" class="search-form">
                    <input type="text" name="q" class="search-input" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="ابحث عن طريق الاسم (Username) أو البريد الإلكتروني..." required>
                    <button type="submit" class="btn-search"><i class="fa-solid fa-magnifying-glass"></i> بحث عن طالب</button>
                </form>
            </div>

            <?php if ($search_query): ?>
                <h3 style="color: var(--text-primary); margin-bottom: 20px;" class="skeleton">نتائج البحث عن "<?php echo htmlspecialchars($search_query); ?>"</h3>
                <div class="results-grid skeleton">
                    <?php if (empty($search_results)): ?>
                        <div style="grid-column: 1 / -1; text-align: center; padding: 30px; background: var(--bg-surface); border-radius: var(--radius-md); border: 1px dashed var(--border-color); color: var(--text-secondary);">
                            لا توجد نتائج مطابقة، أو أن المستخدم مشرف بالفعل.
                        </div>
                    <?php else: ?>
                        <?php foreach ($search_results as $user): ?>
                            <?php $avatar = !empty($user['profile_picture']) && $user['profile_picture'] !== 'default.png' ? '../../uploads/profile_pictures/' . htmlspecialchars($user['profile_picture']) : '../../uploads/profile_pictures/default_profile.png'; ?>
                            <div class="result-card">
                                <img src="<?php echo $avatar; ?>" alt="Avatar" class="result-avatar" onerror="this.src='../../uploads/profile_pictures/default_profile.png'">
                                <div class="result-info">
                                    <h3><?php echo htmlspecialchars($user['username']); ?></h3>
                                    <p dir="ltr"><?php echo htmlspecialchars($user['email']); ?></p>
                                </div>
                                <form action="assign.php" method="POST" style="margin: 0;" onsubmit="return confirm('تأكيد ترقية هذا المستخدم ليصبح مشرفاً (Admin) بصلاحيات كاملة؟');">
                                    <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                    <input type="hidden" name="action" value="assign">
                                    <button type="submit" class="btn-assign">
                                        <i class="fa-solid fa-level-up-alt"></i> ترقية
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
    <script src="../assets/js/admin_global.js"></script>
</body>
</html>