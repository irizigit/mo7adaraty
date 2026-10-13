<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../config/db_connexion.php';

// 1. السماح للطالب والمدير (بصفته يتفقد الموقع) برؤية الصفحة
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['student', 'admin'])) {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // 2. جلب بيانات تسجيل الطالب إن وجدت
    $stmt_enrollment = $pdo->prepare("
        SELECT co.offering_id, co.academic_year_id, co.division_id, ay.year_label, d.division_name, c.class_name
        FROM student_enrollments se
        JOIN course_offerings co ON se.offering_id = co.offering_id
        JOIN academic_years ay ON co.academic_year_id = ay.id
        JOIN divisions d ON co.division_id = d.division_id
        JOIN classes c ON co.class_id = c.class_id
        WHERE se.user_id = ?
        LIMIT 1
    ");
    $stmt_enrollment->execute([$user_id]);
    $enrollment = $stmt_enrollment->fetch(PDO::FETCH_ASSOC);

    // تحديد العام الدراسي
    if ($enrollment) {
        $academic_year_id = $enrollment['academic_year_id'];
    } else {
        $stmt_curr = $pdo->query("SELECT id FROM academic_years WHERE is_current = 1 LIMIT 1");
        $academic_year_id = $stmt_curr->fetchColumn();
    }

    $offering_group = null;
    $division_groups = [];
    $general_groups = [];
    
    // 3. جلب المجموعات المخصصة للمقرر والشعبة (للمسجلين فقط)
    if ($enrollment) {
        $stmt_offering = $pdo->prepare("SELECT * FROM whatsapp_groups WHERE offering_id = ? LIMIT 1");
        $stmt_offering->execute([$enrollment['offering_id']]);
        $offering_group = $stmt_offering->fetch(PDO::FETCH_ASSOC);

        $stmt_div = $pdo->prepare("SELECT * FROM whatsapp_groups WHERE offering_id IS NULL AND division_id = ? AND academic_year_id = ? ORDER BY link_id DESC");
        $stmt_div->execute([$enrollment['division_id'], $academic_year_id]);
        $division_groups = $stmt_div->fetchAll(PDO::FETCH_ASSOC);
    }

    // 4. جلب المجموعات العامة (تظهر للجميع)
    if ($academic_year_id) {
        $stmt_gen = $pdo->prepare("SELECT * FROM whatsapp_groups WHERE offering_id IS NULL AND division_id IS NULL AND academic_year_id = ? ORDER BY link_id DESC");
        $stmt_gen->execute([$academic_year_id]);
        $general_groups = $stmt_gen->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    die("خطأ: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مجموعات الواتساب - Mo7adaraty</title>
    <link rel="icon" type="image/svg+xml" href="../../assets/images/favicon.svg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/global.css">
    <style>
        body { background: var(--bg-color); font-family: 'Tajawal', sans-serif; color: var(--text-primary); margin: 0; }
        .student-wrapper { max-width: 1200px; margin: 0 auto; padding: 20px; min-height: 70vh; }
        
        .back-link { display: inline-flex; align-items: center; gap: 8px; color: var(--text-secondary); font-weight: bold; text-decoration: none; margin-bottom: 20px; transition: color 0.3s; }
        .back-link:hover { color: var(--brand-primary); }

        .page-header { background: linear-gradient(135deg, #128C7E, #25D366); color: #fff; padding: 30px; border-radius: var(--radius-lg); margin-bottom: 30px; display: flex; align-items: center; gap: 20px; box-shadow: 0 4px 15px rgba(37, 211, 102, 0.3); }
        .page-header i { font-size: 40px; }
        .page-header h1 { margin: 0; font-size: 24px; font-weight: 900; }
        .page-header p { margin: 5px 0 0 0; opacity: 0.9; }

        .groups-section-title { font-size: 18px; font-weight: 800; color: var(--text-primary); margin: 30px 0 20px 0; padding-bottom: 10px; border-bottom: 2px solid var(--border-color); display: flex; align-items: center; gap: 10px; }
        .groups-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        
        .group-card { background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 25px; text-align: center; transition: 0.3s; box-shadow: var(--shadow-sm); position: relative; overflow: hidden; }
        .group-card:hover { transform: translateY(-5px); border-color: #25D366; box-shadow: var(--shadow-md); }
        .group-card::before { content: ''; position: absolute; top: 0; right: 0; width: 100%; height: 4px; background: #25D366; }
        
        .group-icon { width: 70px; height: 70px; background: rgba(37, 211, 102, 0.1); color: #25D366; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 35px; margin: 0 auto 15px auto; }
        .group-title { font-size: 18px; font-weight: 800; margin: 0 0 10px 0; color: var(--text-primary); line-height: 1.5; }
        .group-badge { display: inline-block; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; margin-bottom: 20px; border: 1px solid var(--border-color); }
        
        .badge-main { background: rgba(37,211,102,0.1); color: #128C7E; border-color: rgba(37,211,102,0.3); }
        .badge-div { background: rgba(255,193,7,0.1); color: #d39e00; border-color: rgba(255,193,7,0.3); }
        .badge-general { background: var(--bg-light); color: var(--brand-primary); }

        .btn-join { display: flex; align-items: center; justify-content: center; gap: 10px; width: 100%; background: #25D366; color: #fff; padding: 12px; border-radius: var(--radius-md); text-decoration: none; font-weight: 800; font-size: 15px; transition: 0.2s; }
        .btn-join:hover { background: #128C7E; color: #fff; transform: translateY(-2px); }

        .empty-state { text-align: center; padding: 40px; background: var(--bg-surface); border: 1px dashed var(--border-color); border-radius: var(--radius-lg); }
    </style>
</head>
<body>

    <?php require_once '../../templates/header.php'; ?>

    <div class="student-wrapper">
        <a href="../profil.php" class="back-link">
            <i class="fa-solid fa-arrow-right"></i> العودة للوحة التحكم
        </a>

        <div class="page-header">
            <i class="fa-brands fa-whatsapp"></i>
            <div>
                <h1>مجموعات الواتساب الرسمية</h1>
                <p>تواصل مع زملائك، اطرح استفساراتك، وتابع المستجدات</p>
            </div>
        </div>

        <?php if (!$enrollment): ?>
            <div class="empty-state" style="margin-bottom: 30px; border-color: var(--warning-color);">
                <i class="fa-solid fa-triangle-exclamation" style="font-size: 40px; color: var(--warning-color); margin-bottom: 15px;"></i>
                <h3 style="color: var(--text-primary);">أنت غير مسجل في أي مسار أكاديمي</h3>
                <p style="color: var(--text-secondary);">لذلك لا تظهر لك المجموعات الخاصة بالفصول والشعب. يمكنك رؤية المجموعات العامة فقط.</p>
                <?php if($_SESSION['role'] === 'student'): ?>
                    <a href="../settings.php" class="btn-join" style="width: auto; display: inline-flex; margin-top: 15px; background: var(--brand-primary);">الذهاب لتسجيل مسارك</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div style="margin-bottom: 20px; font-weight: bold; color: var(--text-secondary); background: var(--bg-surface); padding: 15px; border-radius: var(--radius-md); border: 1px solid var(--border-color); display: flex; align-items: center; gap: 10px;">
                <i class="fa-solid fa-graduation-cap" style="color: var(--brand-primary); font-size: 20px;"></i> 
                أنت تتصفح مجموعات: <span style="color: var(--brand-primary);"><?php echo htmlspecialchars($enrollment['division_name'] . ' - ' . $enrollment['class_name'] . ' (' . $enrollment['year_label'] . ')'); ?></span>
            </div>

            <h3 class="groups-section-title"><i class="fa-solid fa-layer-group text-[#25D366]"></i> المجموعة الرسمية لمقررك</h3>
            <?php if ($offering_group): ?>
                <div class="groups-grid" style="margin-bottom: 40px;">
                    <div class="group-card" style="border-color: #25D366; box-shadow: 0 4px 15px rgba(37, 211, 102, 0.1);">
                        <div class="group-icon" style="background: #25D366; color: white;"><i class="fa-brands fa-whatsapp"></i></div>
                        <h3 class="group-title"><?php echo htmlspecialchars($offering_group['group_name']); ?></h3>
                        <span class="group-badge badge-main">المجموعة الأساسية لفصلك</span>
                        <a href="<?php echo htmlspecialchars($offering_group['group_link']); ?>" target="_blank" class="btn-join">
                            الانضمام للمجموعة <i class="fa-solid fa-arrow-up-right-from-square"></i>
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="empty-state" style="padding: 20px; margin-bottom: 40px;">
                    <p style="margin:0; color: var(--text-secondary); font-weight: bold;">لم يتم تعيين مجموعة واتساب لهذا المقرر حتى الآن.</p>
                </div>
            <?php endif; ?>

            <?php if (!empty($division_groups)): ?>
                <h3 class="groups-section-title"><i class="fa-solid fa-bullseye text-[#d39e00]"></i> مجموعات إضافية مخصصة لشعبتك</h3>
                <div class="groups-grid" style="margin-bottom: 40px;">
                    <?php foreach ($division_groups as $group): ?>
                        <div class="group-card">
                            <div class="group-icon"><i class="fa-solid fa-users"></i></div>
                            <h3 class="group-title"><?php echo htmlspecialchars($group['group_name']); ?></h3>
                            <span class="group-badge badge-div">خاصة بشعبة: <?php echo htmlspecialchars($enrollment['division_name']); ?></span>
                            <a href="<?php echo htmlspecialchars($group['group_link']); ?>" target="_blank" class="btn-join" style="background: #d39e00;">
                                الانضمام <i class="fa-solid fa-arrow-up-right-from-square"></i>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <h3 class="groups-section-title"><i class="fa-solid fa-globe text-[var(--brand-primary)]"></i> المجموعات العامة والإعلانات</h3>
        <div class="groups-grid">
            <?php if (empty($general_groups)): ?>
                <div class="empty-state" style="grid-column: 1/-1; padding: 30px;">
                    <p style="margin:0; color: var(--text-secondary); font-weight: bold;">لا توجد مجموعات عامة مسجلة في هذا الموسم.</p>
                </div>
            <?php else: ?>
                <?php foreach ($general_groups as $group): ?>
                    <div class="group-card">
                        <div class="group-icon" style="background: rgba(26, 115, 232, 0.1); color: var(--brand-primary);"><i class="fa-solid fa-bullhorn"></i></div>
                        <h3 class="group-title"><?php echo htmlspecialchars($group['group_name']); ?></h3>
                        <span class="group-badge badge-general">مجموعة عامة للجميع</span>
                        <a href="<?php echo htmlspecialchars($group['group_link']); ?>" target="_blank" class="btn-join" style="background: var(--brand-primary);">
                            الانضمام <i class="fa-solid fa-arrow-up-right-from-square"></i>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>

    <?php require_once '../../templates/footer.php'; ?>

</body>
</html>