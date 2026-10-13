<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../config/db_connexion.php';

// حماية الصفحة
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

$breadcrumbs = ["الطلاب والمستخدمون" => "", "إدارة الحسابات" => ""];

// الحصول على معاملات التصفية
$division_id = filter_input(INPUT_GET, 'division_id', FILTER_VALIDATE_INT);
$class_id = filter_input(INPUT_GET, 'class_id', FILTER_VALIDATE_INT);
$track_id = filter_input(INPUT_GET, 'track_id', FILTER_VALIDATE_INT);
$group_id = filter_input(INPUT_GET, 'group_id', FILTER_VALIDATE_INT);

try {
    // --- 1. جلب الموسم الجامعي الحالي ---
    $stmt_year = $pdo->query("SELECT id, year_label FROM academic_years WHERE is_current = 1 LIMIT 1");
    $current_year = $stmt_year->fetch(PDO::FETCH_ASSOC);
    $current_year_id = $current_year ? $current_year['id'] : 1;
    $current_year_label = $current_year ? $current_year['year_label'] : 'غير محدد';

    // جلب الشعب للفلتر
    $divisions = $pdo->query("SELECT * FROM divisions ORDER BY division_name")->fetchAll(PDO::FETCH_ASSOC);
    
    // بناء استعلام المستخدمين مع الفلاتر والموسم الحالي
    $query = "
        SELECT 
            u.user_id, u.username, u.email, u.role, u.gender, u.profile_picture, u.created_at,
            GROUP_CONCAT(
                DISTINCT CONCAT(d.division_name, ' > ', c.class_name, ' / ', g.group_name, IF(t.track_name IS NOT NULL, CONCAT(' / ', t.track_name), '')) 
                SEPARATOR '<br>'
            ) as enrolled_offerings
        FROM users u
        LEFT JOIN student_enrollments se ON u.user_id = se.user_id AND se.academic_year_id = ?
        LEFT JOIN course_offerings co ON se.offering_id = co.offering_id
        LEFT JOIN divisions d ON co.division_id = d.division_id
        LEFT JOIN classes c ON co.class_id = c.class_id
        LEFT JOIN `groups` g ON co.group_id = g.group_id
        LEFT JOIN tracks t ON co.track_id = t.track_id
        WHERE 1=1
    ";
    
    $params = [$current_year_id];
    
    if ($division_id) { $query .= " AND co.division_id = ?"; $params[] = $division_id; }
    if ($class_id) { $query .= " AND co.class_id = ?"; $params[] = $class_id; }
    if ($track_id) { $query .= " AND co.track_id = ?"; $params[] = $track_id; }
    if ($group_id) { $query .= " AND co.group_id = ?"; $params[] = $group_id; }
    
    $query .= " GROUP BY u.user_id ORDER BY u.role ASC, u.user_id DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // جلب الإحصائيات للموسم الحالي فقط
    $stats_query = "
        SELECT 
            d.division_name, c.class_name, g.group_name, t.track_name,
            COUNT(DISTINCT se.user_id) as user_count
        FROM student_enrollments se
        JOIN course_offerings co ON se.offering_id = co.offering_id
        JOIN divisions d ON co.division_id = d.division_id
        JOIN classes c ON co.class_id = c.class_id
        JOIN `groups` g ON co.group_id = g.group_id
        LEFT JOIN tracks t ON co.track_id = t.track_id
        WHERE se.academic_year_id = ?
    ";
    
    $stats_params = [$current_year_id];
    
    if ($division_id) { $stats_query .= " AND co.division_id = ?"; $stats_params[] = $division_id; }
    if ($class_id) { $stats_query .= " AND co.class_id = ?"; $stats_params[] = $class_id; }
    if ($track_id) { $stats_query .= " AND co.track_id = ?"; $stats_params[] = $track_id; }
    
    $stats_query .= " GROUP BY d.division_name, c.class_id, c.class_name, g.group_id, g.group_name, t.track_name ORDER BY d.division_name, c.class_id, g.group_id";
    
    $stats_stmt = $pdo->prepare($stats_query);
    $stats_stmt->execute($stats_params);
    $stats = $stats_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $division_stats = []; $class_stats = []; $group_stats = [];
    
    foreach ($stats as $stat) {
        if (!isset($division_stats[$stat['division_name']])) { $division_stats[$stat['division_name']] = 0; }
        $division_stats[$stat['division_name']] += $stat['user_count'];
        
        $class_key = $stat['division_name'] . ' > ' . $stat['class_name'];
        if (!isset($class_stats[$class_key])) { $class_stats[$class_key] = 0; }
        $class_stats[$class_key] += $stat['user_count'];
        
        $group_key = $stat['division_name'] . ' > ' . $stat['class_name'] . ' / ' . $stat['group_name'];
        if ($stat['track_name']) { $group_key .= ' / ' . $stat['track_name']; }
        $group_stats[$group_key] = $stat['user_count'];
    }

} catch (PDOException $e) {
    die("خطأ في جلب البيانات: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المستخدمين - Mo7adaraty</title>
    <link rel="icon" type="image/svg+xml" href="/mo7adaraty/assets/images/favicon.svg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/admin_global.css">
    <link rel="stylesheet" href="../assets/css/admin_academic.css">
    <style>
        /* ستايل الفلاتر والإحصائيات الحديث */
        .stats-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stats-card { background: var(--bg-surface); border-radius: var(--radius-md); padding: 20px; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); border-right: 4px solid var(--brand-primary); }
        .stats-card h3 { margin-top: 0; color: var(--brand-primary); font-size: 16px; font-weight: 800; display: flex; align-items: center; gap: 8px; }
        .stats-card .count { font-size: 2.5em; font-weight: 900; margin: 10px 0; color: var(--text-primary); }
        .stats-card ul { list-style: none; padding: 0; margin: 0; }
        .stats-card li { padding: 8px 0; border-bottom: 1px dashed var(--border-color); display: flex; justify-content: space-between; font-size: 13px; font-weight: bold; color: var(--text-secondary); }
        .stats-card li:last-child { border-bottom: none; }
        .stats-card .stat-value { font-weight: 900; color: var(--brand-primary); background: var(--bg-light); padding: 2px 8px; border-radius: 12px; }
        
        .filter-section { background: var(--bg-surface); padding: 20px; border-radius: var(--radius-md); border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); margin-bottom: 30px; }
        .filter-form { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: end; }
        .track-group { display: none; }
        .active-filters { margin-top: 15px; padding-top: 15px; border-top: 1px dashed var(--border-color); font-size: 13px; }
        .filter-tag { display: inline-block; background: var(--bg-light); border: 1px solid var(--border-color); color: var(--text-primary); padding: 5px 12px; border-radius: 20px; margin: 5px 5px 5px 0; font-weight: bold; }
        .filter-tag .remove-filter { color: var(--danger-color); margin-right: 8px; cursor: pointer; text-decoration: none; }

        /* ستايل بطاقات المستخدمين (Luxury UI) */
        .users-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .user-card { background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 20px; display: flex; flex-direction: column; align-items: center; text-align: center; box-shadow: var(--shadow-sm); transition: 0.3s; position: relative; overflow: hidden; }
        .user-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-md); border-color: var(--brand-primary); }
        .user-card.role-admin { border-top: 4px solid var(--danger-color); }
        .user-card.role-student { border-top: 4px solid var(--brand-primary); }
        
        .user-avatar-wrapper { position: relative; margin-bottom: 15px; }
        .user-avatar { width: 90px; height: 90px; border-radius: 50%; object-fit: cover; border: 3px solid var(--bg-light); box-shadow: var(--shadow-sm); }
        .user-gender-icon { position: absolute; bottom: 0; right: 0; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 13px; border: 2px solid var(--bg-surface); }
        .gender-male { background: var(--brand-primary); }
        .gender-female { background: #e83e8c; }
        .gender-none { background: var(--text-secondary); }

        .user-name { font-size: 18px; font-weight: 900; color: var(--text-primary); margin: 0 0 5px 0; }
        .user-email { font-size: 13px; color: var(--text-secondary); margin-bottom: 10px; font-weight: bold; }
        
        .user-role-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; margin-bottom: 15px; }
        .badge-admin { background: rgba(220, 53, 69, 0.1); color: var(--danger-color); border: 1px solid rgba(220,53,69,0.2); }
        .badge-student { background: rgba(26, 115, 232, 0.1); color: var(--brand-primary); border: 1px solid rgba(26,115,232,0.2); }

        .user-enrollments { width: 100%; text-align: right; background: var(--bg-light); padding: 12px; border-radius: var(--radius-sm); font-size: 12px; color: var(--text-secondary); margin-bottom: 15px; border-right: 3px solid var(--brand-primary); font-weight: 600; line-height: 1.6; }

        .user-actions { display: flex; gap: 10px; width: 100%; border-top: 1px dashed var(--border-color); padding-top: 15px; }
        .btn-user-action { flex: 1; padding: 8px; border-radius: 6px; font-size: 13px; font-weight: bold; text-decoration: none; border: none; cursor: pointer; transition: 0.2s; display: flex; align-items: center; justify-content: center; gap: 5px; font-family: var(--font-family); }
        .btn-edit { background: rgba(255, 193, 7, 0.1); color: var(--warning-color); }
        .btn-edit:hover { background: var(--warning-color); color: #000; }
        .btn-delete { background: rgba(220, 53, 69, 0.1); color: var(--danger-color); }
        .btn-delete:hover { background: var(--danger-color); color: #fff; }
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
                    <img src="../dashboard_design/images/admin_icon_users.png" alt="Users" style="width: 40px; height: 40px; object-fit: contain;" onerror="this.src='../dashboard_design/images/admin_icon_dashboard.png'">
                    إدارة حسابات المستخدمين
                    <span style="font-size: 12px; background: var(--brand-light); padding: 4px 10px; border-radius: 12px; color: var(--brand-primary); border: 1px solid var(--brand-primary);">
                        (موسم <?php echo htmlspecialchars($current_year_label); ?>)
                    </span>
                </h2>
            </div>

            <?php if (isset($_GET['status']) && $_GET['status'] == 'updated'): ?>
                <div style="padding: 15px; background: rgba(40, 167, 69, 0.1); color: var(--success-color); border: 1px solid var(--success-color); border-radius: var(--radius-md); margin-bottom: 20px; font-weight: bold;">
                    <i class="fa-solid fa-circle-check"></i> تم تحديث بيانات المستخدم بنجاح.
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['error'])): ?>
                <div style="padding: 15px; background: rgba(220, 53, 69, 0.1); color: var(--danger-color); border: 1px solid var(--danger-color); border-radius: var(--radius-md); margin-bottom: 20px; font-weight: bold;">
                    <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <div class="filter-section skeleton">
                <h3 style="margin-top: 0; font-size: 16px; color: var(--text-primary);"><i class="fa-solid fa-filter text-[var(--brand-primary)]"></i> فلترة المستخدمين</h3>
                <form id="filter-form" class="filter-form" method="GET">
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label">الشعبة:</label>
                        <select id="division-filter" name="division_id" class="form-control">
                            <option value="">-- جميع الشعب --</option>
                            <?php foreach ($divisions as $div): ?>
                                <option value="<?php echo $div['division_id']; ?>" <?php if ($division_id == $div['division_id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($div['division_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label">الفصل:</label>
                        <select id="class-filter" name="class_id" class="form-control" <?php if (!$division_id) echo 'disabled'; ?>>
                            <option value="">-- جميع الفصول --</option>
                        </select>
                    </div>
                    
                    <div class="form-group track-group" id="track-filter-group" style="margin: 0;">
                        <label class="form-label">المسار:</label>
                        <select id="track-filter" name="track_id" class="form-control">
                            <option value="">-- جميع المسارات --</option>
                        </select>
                    </div>
                    
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label">الفوج:</label>
                        <select id="group-filter" name="group_id" class="form-control" <?php if (!$class_id) echo 'disabled'; ?>>
                            <option value="">-- جميع الفئات --</option>
                        </select>
                    </div>
                    
                    <div style="display: flex; gap: 10px; align-items: flex-end;">
                        <button type="submit" class="btn-add-new" style="background: var(--brand-primary); color: white; justify-content: center; height: 42px;">
                            <i class="fa-solid fa-search"></i> بحث
                        </button>
                        <a href="index.php" class="btn-add-new" style="background: var(--bg-light); color: var(--text-primary); border: 1px solid var(--border-color); justify-content: center; height: 42px;">
                            <i class="fa-solid fa-eraser"></i>
                        </a>
                    </div>
                </form>
                
                <?php if ($division_id || $class_id || $track_id || $group_id): ?>
                    <div class="active-filters">
                        <strong style="color: var(--text-primary);">الفلاتر النشطة: </strong>
                        <?php if ($division_id): ?>
                            <span class="filter-tag">
                                الشعبة: <?php $dkey = array_search($division_id, array_column($divisions, 'division_id')); echo htmlspecialchars($dkey !== false ? $divisions[$dkey]['division_name'] : 'محدد'); ?>
                                <a href="?<?php echo http_build_query(array_diff($_GET, ['division_id' => $division_id])); ?>" class="remove-filter"><i class="fas fa-times"></i></a>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="stats-container skeleton">
                <div class="stats-card">
                    <h3><i class="fa-solid fa-users"></i> إجمالي النتائج</h3>
                    <div class="count"><?php echo count($users); ?></div>
                    <p style="margin:0; font-size:12px; color:var(--text-secondary); font-weight:bold;">مستخدم مسجل بالنظام</p>
                </div>
                <div class="stats-card">
                    <h3><i class="fa-solid fa-sitemap"></i> حسب الشعبة</h3>
                    <ul>
                        <?php if (empty($division_stats)): ?><li>لا توجد تسجيلات.</li>
                        <?php else: foreach ($division_stats as $division => $count): ?>
                            <li><span><?php echo htmlspecialchars($division); ?></span><span class="stat-value"><?php echo $count; ?></span></li>
                        <?php endforeach; endif; ?>
                    </ul>
                </div>
                <div class="stats-card">
                    <h3><i class="fa-solid fa-book"></i> حسب الفصل</h3>
                    <ul>
                        <?php if (empty($class_stats)): ?><li>لا توجد تسجيلات.</li>
                        <?php else: foreach ($class_stats as $class => $count): ?>
                            <li><span><?php echo htmlspecialchars($class); ?></span><span class="stat-value"><?php echo $count; ?></span></li>
                        <?php endforeach; endif; ?>
                    </ul>
                </div>
                <div class="stats-card">
                    <h3><i class="fa-solid fa-users-line"></i> حسب الفوج</h3>
                    <ul style="max-height: 120px; overflow-y: auto;">
                        <?php if (empty($group_stats)): ?><li>لا توجد تسجيلات.</li>
                        <?php else: foreach ($group_stats as $group => $count): ?>
                            <li><span><?php echo htmlspecialchars($group); ?></span><span class="stat-value"><?php echo $count; ?></span></li>
                        <?php endforeach; endif; ?>
                    </ul>
                </div>
            </div>

            <h3 style="margin-top: 0; margin-bottom: 20px; color: var(--text-primary); font-size: 18px;"><i class="fa-solid fa-id-card text-[var(--brand-primary)]"></i> قائمة المستخدمين (<?php echo count($users); ?>)</h3>
            <hr style="margin: 0 0 20px 0; border: none; border-top: 1px solid var(--border-color);">
            <a href="add.php" class="btn-add-new" style="margin-bottom: 20px; background: var(--brand-primary); color: white;">
                <i class="fa-solid fa-user-plus"></i> إضافة مستخدم جديد
            </a>
            <div class="users-grid skeleton">
                <?php if (empty($users)): ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 40px; background: var(--bg-surface); border-radius: var(--radius-md); border: 1px dashed var(--border-color); color: var(--text-secondary);">
                        <i class="fa-solid fa-users-slash" style="font-size: 40px; margin-bottom: 15px; opacity: 0.5;"></i>
                        <p>لا يوجد مستخدمون يطابقون الفلتر.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                        <?php 
                            $avatar = !empty($user['profile_picture']) ? '../../uploads/profile_pictures/' . htmlspecialchars($user['profile_picture']) : '../../uploads/profile_pictures/default_profile.png';
                            $gender_class = 'gender-none'; $gender_icon = 'fa-user';
                            if ($user['gender'] === 'male') { $gender_class = 'gender-male'; $gender_icon = 'fa-mars'; }
                            elseif ($user['gender'] === 'female') { $gender_class = 'gender-female'; $gender_icon = 'fa-venus'; }
                            $role_class = $user['role'] === 'admin' ? 'badge-admin' : 'badge-student';
                            $role_text = $user['role'] === 'admin' ? 'مدير نظام' : 'طالب';
                            $card_class = $user['role'] === 'admin' ? 'role-admin' : 'role-student';
                        ?>
                        <div class="user-card <?php echo $card_class; ?>">
                            <div class="user-avatar-wrapper">
                                <img src="<?php echo $avatar; ?>" alt="Avatar" class="user-avatar" onerror="this.src='../../uploads/profile_pictures/default_profile.png'">
                                <div class="user-gender-icon <?php echo $gender_class; ?>" title="<?php echo $user['gender'] === 'male' ? 'ذكر' : ($user['gender'] === 'female' ? 'أنثى' : 'غير محدد'); ?>">
                                    <i class="fa-solid <?php echo $gender_icon; ?>"></i>
                                </div>
                            </div>
                            
                            <h3 class="user-name"><?php echo htmlspecialchars($user['username']); ?></h3>
                            <div class="user-email" dir="ltr"><?php echo htmlspecialchars($user['email']); ?></div>
                            
                            <div class="user-role-badge <?php echo $role_class; ?>">
                                <i class="fa-solid <?php echo $user['role'] === 'admin' ? 'fa-crown' : 'fa-graduation-cap'; ?>"></i> <?php echo $role_text; ?>
                            </div>

                            <div class="user-enrollments">
                                <span style="color: var(--brand-primary);"><i class="fa-solid fa-bookmark"></i> مسجل في:</span><br>
                                <?php echo $user['enrolled_offerings'] ?? '<span style="color: var(--danger-color); font-size: 11px;">غير مسجل في أي مقرر للموسم الحالي</span>'; ?>
                            </div>

                            <div class="user-actions">
                                <a href="edit.php?id=<?php echo $user['user_id']; ?>" class="btn-user-action btn-edit">
                                    <i class="fa-solid fa-user-pen"></i> تعديل
                                </a>
                                <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                    <form action="delete_process.php" method="POST" style="margin: 0; flex: 1;" onsubmit="return confirm('تأكيد حذف الحساب نهائياً؟');">
                                        <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                        <button type="submit" class="btn-user-action btn-delete" style="width: 100%;">
                                            <i class="fa-solid fa-trash"></i> حذف
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button class="btn-user-action btn-delete" style="opacity: 0.5; cursor: not-allowed;" title="لا يمكنك حذف حسابك الحالي" disabled>
                                        <i class="fa-solid fa-ban"></i> حسابك
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/admin_global.js"></script>
    <script>
        $(document).ready(function() {
            const ADVANCED_CLASSES_IDS = [5, 6];
            
            // --- فلتر الشعبة ---
            $('#division-filter').on('change', function() {
                const divisionID = $(this).val();
                const $classFilter = $('#class-filter');
                const $trackFilterGroup = $('#track-filter-group');
                const $groupFilter = $('#group-filter');
                
                $classFilter.html('<option value="">-- جميع الفصول --</option>').prop('disabled', !divisionID);
                $trackFilterGroup.hide();
                $groupFilter.html('<option value="">-- جميع الفئات --</option>').prop('disabled', true);
                
                if (divisionID) {
                    $.post('../../ajax/get_data_for_settings.php', { action: 'get_classes', division_id: divisionID }, function(classes) {
                        classes.forEach(cls => { $classFilter.append(`<option value="${cls.class_id}">${cls.class_name}</option>`); });
                    }, 'json');
                }
            });
            
            // --- فلتر الفصل ---
            $('#class-filter').on('change', function() {
                const classID = parseInt($(this).val());
                const divisionID = $('#division-filter').val();
                const $trackFilterGroup = $('#track-filter-group');
                const $trackFilter = $('#track-filter');
                const $groupFilter = $('#group-filter');
                
                $trackFilterGroup.hide();
                $trackFilter.html('<option value="">-- جميع المسارات --</option>');
                $groupFilter.html('<option value="">-- جميع الفئات --</option>').prop('disabled', !classID);
                
                if (classID) {
                    if (ADVANCED_CLASSES_IDS.includes(classID)) {
                        $trackFilterGroup.show();
                        $.post('../../ajax/get_data_for_settings.php', { action: 'get_tracks', division_id: divisionID }, function(tracks) {
                            tracks.forEach(track => { $trackFilter.append(`<option value="${track.track_id}">${track.track_name}</option>`); });
                        }, 'json');
                    } else {
                        $.post('../../ajax/get_data_for_settings.php', { action: 'get_groups', class_id: classID }, function(groups) {
                            groups.forEach(group => {
                                let val_id = group.group_id ? group.group_id : (group.id ? group.id : group.offering_id);
                                $groupFilter.append(`<option value="${val_id}">${group.group_name}</option>`);
                            });
                            $groupFilter.prop('disabled', false);
                        }, 'json');
                    }
                }
            });
            
            // --- فلتر المسار ---
            $('#track-filter').on('change', function() {
                const classID = $('#class-filter').val();
                const trackID = $(this).val();
                const $groupFilter = $('#group-filter');
                $groupFilter.html('<option value="">-- جميع الفئات --</option>').prop('disabled', !trackID);
                if (trackID) {
                     $.post('../../ajax/get_data_for_settings.php', { action: 'get_groups', class_id: classID, track_id: trackID }, function(groups) {
                        groups.forEach(group => {
                            let val_id = group.group_id ? group.group_id : (group.id ? group.id : group.offering_id);
                            $groupFilter.append(`<option value="${val_id}">${group.group_name}</option>`);
                        });
                        $groupFilter.prop('disabled', false);
                    }, 'json');
                }
            });
            
            // --- تحميل الفلاتر عند بدء الصفحة ---
            <?php if ($division_id): ?>
                $('#division-filter').trigger('change');
                <?php if ($class_id): ?>
                    setTimeout(() => { $('#class-filter').val('<?php echo $class_id; ?>').trigger('change'); }, 500);
                    <?php if ($track_id): ?>
                        setTimeout(() => { $('#track-filter').val('<?php echo $track_id; ?>').trigger('change'); }, 1000);
                        <?php if ($group_id): ?> setTimeout(() => { $('#group-filter').val('<?php echo $group_id; ?>'); }, 1500); <?php endif; ?>
                    <?php endif; ?>
                    <?php if (!$track_id && $group_id): ?> setTimeout(() => { $('#group-filter').val('<?php echo $group_id; ?>'); }, 1000); <?php endif; ?>
                <?php endif; ?>
            <?php endif; ?>
        });
    </script>
</body>
</html>