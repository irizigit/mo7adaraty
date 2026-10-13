<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    return;
}

$user_id = $_SESSION['user_id'];$base_url = '/mo7adaraty/';

require_once __DIR__ . '/../config/db_connexion.php';
$db = isset($pdo) ?$pdo : (isset($conn) ?$conn : null);

if (!$db) {
    return;
}

$enrolled_classes = [];

try {
    $stmt_year =$db->query("SELECT id FROM academic_years WHERE is_current = 1 LIMIT 1");
    $current_yr_id = $stmt_year ? $stmt_year->fetchColumn() : null;

    if ($current_yr_id) {
        $stmt_enroll =$db->prepare("
            SELECT DISTINCT 
                co.offering_id,
                c.class_id,
                c.class_name,
                c.class_image,
                g.group_name
            FROM student_enrollments se
            JOIN course_offerings co ON se.offering_id = co.offering_id
            JOIN classes c ON co.class_id = c.class_id
            LEFT JOIN `groups` g ON co.group_id = g.group_id
            WHERE se.user_id = ? 
              AND se.academic_year_id = ? 
              AND (co.status = 1 OR co.status = 'active')
            ORDER BY c.class_id ASC
        ");
        $stmt_enroll->execute([$user_id,$current_yr_id]);
        $enrolled_classes =$stmt_enroll->fetchAll(PDO::FETCH_ASSOC);
    }

    if (empty($enrolled_classes)) {
        $stmt_fallback =$db->prepare("
            SELECT DISTINCT 
                co.offering_id,
                c.class_id,
                c.class_name,
                c.class_image,
                g.group_name
            FROM student_enrollments se
            JOIN course_offerings co ON se.offering_id = co.offering_id
            JOIN classes c ON co.class_id = c.class_id
            LEFT JOIN `groups` g ON co.group_id = g.group_id
            WHERE se.user_id = ? 
              AND (co.status = 1 OR co.status = 'active')
            ORDER BY se.enrollment_id DESC 
            LIMIT 2
        ");
        $stmt_fallback->execute([$user_id]);
        $enrolled_classes =$stmt_fallback->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {$enrolled_classes = [];
}

if (empty($enrolled_classes)) {
    return;
}
?>

<!-- أنماط الزر العائم والقائمة المنبثقة -->
<style>
.floating-classes-wrapper {
    position: fixed;
    bottom: 30px;
    left: 30px;
    z-index: 99998;
    direction: rtl;
    font-family: 'Tajawal', sans-serif;
}

[dir="ltr"] .floating-classes-wrapper {
    left: auto;
    right: 30px;
    direction: ltr;
}

.floating-classes-btn {
    width: 58px;
    height: 58px;
    border-radius: 50%;
    background: linear-gradient(135deg, #1a73e8 0%, #1557b0 100%);
    color: #ffffff;
    border: none;
    outline: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 24px rgba(26, 115, 232, 0.4);
    transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.25s ease;
    position: relative;
}

.floating-classes-btn:hover {
    transform: scale(1.08) translateY(-3px);
    box-shadow: 0 12px 28px rgba(26, 115, 232, 0.5);
}

.floating-classes-btn i {
    font-size: 24px;
    transition: transform 0.3s ease;
}

.floating-classes-btn.is-active i {
    transform: rotate(90deg);
}

.floating-classes-badge {
    position: absolute;
    top: -2px;
    right: -2px;
    background: #10b981;
    color: #ffffff;
    font-size: 11px;
    font-weight: 800;
    min-width: 20px;
    height: 20px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid #ffffff;
    box-shadow: 0 2px 6px rgba(0,0,0,0.2);
}

.floating-classes-panel {
    position: absolute;
    bottom: 74px;
    left: 0;
    width: 320px;
    max-width: calc(100vw - 40px);
    background: var(--bg-surface, #ffffff);
    border: 1px solid var(--border-color, #e2e8f0);
    border-radius: 20px;
    box-shadow: 0 16px 36px rgba(0, 0, 0, 0.18);
    padding: 16px;
    box-sizing: border-box;
    display: none;
    transform-origin: bottom left;
    animation: floatingPanelIn 0.22s ease-out forwards;
}

[dir="ltr"] .floating-classes-panel {
    left: auto;
    right: 0;
    transform-origin: bottom right;
}

@keyframes floatingPanelIn {
    from {
        opacity: 0;
        transform: scale(0.9) translateY(10px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

.floating-panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-bottom: 12px;
    margin-bottom: 12px;
    border-bottom: 1px solid var(--border-color, #e2e8f0);
}

.floating-panel-title {
    margin: 0;
    font-size: 14px;
    font-weight: 800;
    color: var(--text-primary, #0f172a);
    display: flex;
    align-items: center;
    gap: 8px;
}

.floating-classes-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
    max-height: 380px;
    overflow-y: auto;
    padding-inline-end: 2px;
}

.floating-class-item {
    background: var(--bg-body, #f8fafc);
    border: 1px solid var(--border-color, #e2e8f0);
    border-radius: 14px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    transition: transform 0.2s ease, border-color 0.2s ease;
}

.floating-class-item:hover {
    border-color: var(--brand-primary, #1a73e8);
    transform: translateY(-2px);
}

.floating-class-banner-wrap {
    width: 100%;
    height: 75px;
    background: #0f172a;
    position: relative;
    overflow: hidden;
}

.floating-class-banner-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.floating-class-info-bar {
    padding: 10px 12px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
}

.floating-class-name {
    margin: 0;
    font-size: 13px;
    font-weight: 800;
    color: var(--text-primary, #0f172a);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 170px;
}

.btn-floating-enter {
    background: var(--brand-primary, #1a73e8);
    color: #ffffff !important;
    text-decoration: none;
    font-size: 12px;
    font-weight: 700;
    padding: 7px 14px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: background 0.2s ease;
    flex-shrink: 0;
}

.btn-floating-enter:hover {
    background: #1557b0;
}

@media (max-width: 600px) {
    .floating-classes-wrapper {
        bottom: 20px;
        left: 20px;
    }
    [dir="ltr"] .floating-classes-wrapper {
        right: 20px;
    }
    .floating-classes-panel {
        width: 290px;
        bottom: 68px;
    }
}
</style>

<!-- هيكل الزر والقائمة -->
<div class="floating-classes-wrapper" id="floatingClassesWrapper">
    <button type="button" class="floating-classes-btn" id="floatingClassesToggle" aria-label="الوصول السريع للفصول">
        <i class="fa-solid fa-graduation-cap"></i>
        <span class="floating-classes-badge"><?= count($enrolled_classes) ?></span>
    </button>

    <div class="floating-classes-panel" id="floatingClassesPanel">
        <div class="floating-panel-header">
            <h4 class="floating-panel-title">
                <i class="fa-solid fa-book-bookmark" style="color: var(--brand-primary, #1a73e8);"></i>
                <span>فصولي الدراسية (الموسم الحالي)</span>
            </h4>
        </div>
        
        <div class="floating-classes-list">
            <?php foreach ($enrolled_classes as $class_row):$c_img = !empty($class_row['class_image']) ?$class_row['class_image'] : 'default_class.png';
                $c_img_url =$base_url . 'uploads/classes/' . htmlspecialchars($c_img);$g_label = !empty($class_row['group_name']) ? ' - فوج ' . htmlspecialchars($class_row['group_name']) : '';
            ?>
                <div class="floating-class-item">
                    <div class="floating-class-banner-wrap">
                        <img src="<?= $c_img_url ?>" alt="<?= htmlspecialchars($class_row['class_name']) ?>" class="floating-class-banner-img" onerror="this.onerror=null; this.src='<?= $base_url ?>assets/images/favicon.png';">
                    </div>
                    <div class="floating-class-info-bar">
                        <h5 class="floating-class-name"><?= htmlspecialchars($class_row['class_name']) .$g_label ?></h5>
                        <a href="<?= $base_url ?>student/class_lectures.php?id=<?= $class_row['offering_id'] ?>" class="btn-floating-enter">
                            <i class="fa-solid fa-arrow-left"></i>
                            <span>دخول</span>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.getElementById('floatingClassesToggle');
    const panel = document.getElementById('floatingClassesPanel');
    const wrapper = document.getElementById('floatingClassesWrapper');

    if (toggleBtn && panel) {
        toggleBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            const isOpen = panel.style.display === 'block';
            panel.style.display = isOpen ? 'none' : 'block';
            toggleBtn.classList.toggle('is-active', !isOpen);
        });

        document.addEventListener('click', function(e) {
            if (wrapper && !wrapper.contains(e.target)) {
                panel.style.display = 'none';
                toggleBtn.classList.remove('is-active');
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                panel.style.display = 'none';
                toggleBtn.classList.remove('is-active');
            }
        });
    }
});
</script>