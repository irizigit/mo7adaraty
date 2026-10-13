<?php
session_start();
require_once '../../config/db_connexion.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'revoke') {
    $revoke_user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    if ($revoke_user_id && $revoke_user_id != $_SESSION['user_id']) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET role = 'student' WHERE user_id = ?");
            $stmt->execute([$revoke_user_id]);
            $_SESSION['msg'] = "تم سحب صلاحية الإشراف وإعادة المستخدم كطالب بنجاح.";
        } catch (PDOException $e) {
            $_SESSION['error'] = "حدث خطأ أثناء سحب الإشراف: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "إجراء مرفوض: لا يمكنك سحب الإشراف من حسابك الشخصي.";
    }
    header("Location: index.php");
    exit();
}

$breadcrumbs = ["شؤون المستخدمين" => "", "إدارة المشرفين" => ""];

try {
    $stmt = $pdo->query("
        SELECT 
            u.user_id, u.username, u.email, u.profile_picture, u.created_at,
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
                WHERE se.user_id = u.user_id
            ) as enrollment_info
        FROM users u
        WHERE u.role = 'admin'
        ORDER BY u.created_at DESC
    ");
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("خطأ في جلب البيانات: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المشرفين - Mo7adaraty</title>
    <link rel="icon" type="image/svg+xml" href="../../assets/images/favicon.svg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/admin_global.css">
    <style>
        .admins-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; }
        .admin-card { background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 20px; display: flex; flex-direction: column; box-shadow: var(--shadow-sm); transition: 0.3s; position: relative; overflow: hidden; border-top: 4px solid var(--danger-color); }
        .admin-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-md); }
        .admin-header { display: flex; gap: 15px; align-items: center; border-bottom: 1px dashed var(--border-color); padding-bottom: 15px; margin-bottom: 15px; }
        .admin-avatar { width: 60px; height: 60px; border-radius: 50%; object-fit: cover; border: 2px solid var(--bg-light); box-shadow: var(--shadow-sm); }
        .admin-info h3 { margin: 0 0 5px 0; font-size: 16px; font-weight: 900; color: var(--text-primary); }
        .admin-info p { margin: 0; font-size: 13px; color: var(--text-secondary); font-weight: bold; }
        .admin-badge { display: inline-flex; align-items: center; gap: 5px; font-size: 11px; font-weight: bold; padding: 4px 10px; border-radius: 20px; margin-top: 5px; }
        .badge-normal { background: rgba(220, 53, 69, 0.1); color: var(--danger-color); }
        .admin-enrollment { background: var(--bg-light); padding: 12px; border-radius: var(--radius-sm); font-size: 13px; color: var(--text-secondary); margin-bottom: 15px; border-right: 3px solid var(--brand-primary); font-weight: 600; line-height: 1.6; flex-grow: 1; }
        .admin-actions { display: flex; gap: 10px; border-top: 1px solid var(--border-color); padding-top: 15px; }
        .btn-revoke { background: rgba(220, 53, 69, 0.1); color: var(--danger-color); border: none; padding: 10px; border-radius: 6px; font-size: 14px; font-weight: bold; cursor: pointer; transition: 0.2s; flex: 1; display: flex; align-items: center; justify-content: center; gap: 8px; font-family: var(--font-family); }
        .btn-revoke:hover { background: var(--danger-color); color: #fff; }
        .btn-revoke:disabled { opacity: 0.5; cursor: not-allowed; }
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
                    <i class="fa-solid fa-user-shield" style="font-size: 30px; color: var(--danger-color);"></i>
                    إدارة المشرفين
                </h2>
                <a href="assign.php" class="btn-add-new" style="background: var(--danger-color); color: white;">
                    <i class="fa-solid fa-user-plus"></i> تعيين مشرف جديد
                </a>
            </div>

            <?php if (isset($_SESSION['msg'])): ?>
                <div style="padding: 15px; background: rgba(40, 167, 69, 0.1); color: var(--success-color); border: 1px solid var(--success-color); border-radius: var(--radius-md); margin-bottom: 20px; font-weight: bold;">
                    <i class="fa-solid fa-circle-check"></i> <?php echo $_SESSION['msg']; unset($_SESSION['msg']); ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div style="padding: 15px; background: rgba(220, 53, 69, 0.1); color: var(--danger-color); border: 1px solid var(--danger-color); border-radius: var(--radius-md); margin-bottom: 20px; font-weight: bold;">
                    <i class="fa-solid fa-circle-exclamation"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <div class="admins-grid skeleton">
                <?php foreach ($admins as $admin): ?>
                    <?php 
                        $avatar = !empty($admin['profile_picture']) && $admin['profile_picture'] !== 'default.png' ? '../../uploads/profile_pictures/' . htmlspecialchars($admin['profile_picture']) : '../../uploads/profile_pictures/default_profile.png';
                        $is_current_user = $admin['user_id'] == $_SESSION['user_id'];
                    ?>
                    <div class="admin-card">
                        <div class="admin-header">
                            <img src="<?php echo $avatar; ?>" alt="Avatar" class="admin-avatar" onerror="this.src='../../uploads/profile_pictures/default_profile.png'">
                            <div class="admin-info">
                                <h3><?php echo htmlspecialchars($admin['username']); ?></h3>
                                <p dir="ltr"><?php echo htmlspecialchars($admin['email']); ?></p>
                                <span class="admin-badge badge-normal"><i class="fa-solid fa-user-gear"></i> مشرف</span>
                            </div>
                        </div>
                        <div class="admin-enrollment">
                            <span style="color: var(--brand-primary);"><i class="fa-solid fa-graduation-cap"></i> التسجيل الأكاديمي للمشرف:</span><br>
                            <div style="margin-top: 5px;">
                                <?php echo $admin['enrollment_info'] ?: '<span style="color: var(--danger-color);">غير مسجل في أي مسار أكاديمي</span>'; ?>
                            </div>
                        </div>
                        <div class="admin-actions">
                            <form action="index.php" method="POST" style="margin: 0; width: 100%;" onsubmit="return confirm('تأكيد سحب صلاحية الإشراف وإعادة هذا المستخدم إلى طالب عادي؟');">
                                <input type="hidden" name="user_id" value="<?php echo $admin['user_id']; ?>">
                                <input type="hidden" name="action" value="revoke">
                                <button type="submit" class="btn-revoke" <?php echo $is_current_user ? 'disabled title="لا يمكن سحب صلاحيات هذا الحساب"' : ''; ?>>
                                    <i class="fa-solid fa-user-minus"></i> سحب الإشراف
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>
    <script src="../assets/js/admin_global.js"></script>
</body>
</html>