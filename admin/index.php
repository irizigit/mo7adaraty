<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once '../config/db_connexion.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$breadcrumbs = [
    "الرئيسية" => ""
];

function getCountSafe($pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() ?: 0;
    } catch (Throwable $e) {
        return 0;
    }
}

// دالة مساعدة لجلب الأيقونات[cite: 2]
$base_admin_url = '/mo7adaraty/admin/';
$img_path = $base_admin_url . 'dashboard_design/images/';

try {
    $stats = [
        'students' => getCountSafe($pdo, "SELECT COUNT(*) FROM users WHERE role = 'student' OR role IS NOT NULL"),
        'professors' => getCountSafe($pdo, "SELECT COUNT(*) FROM professors"),
        'lectures' => getCountSafe($pdo, "SELECT COUNT(*) FROM lectures"),
        'exams' => getCountSafe($pdo, "SELECT COUNT(*) FROM exams"),
        'whatsapp' => getCountSafe($pdo, "SELECT COUNT(*) FROM whatsapp_groups"),
        'reports' => getCountSafe($pdo, "SELECT COUNT(*) FROM reports WHERE status = 'pending'")
    ];

    $pending_alerts = [
        'reset_requests' => getCountSafe($pdo, "SELECT COUNT(*) FROM password_reset_requests WHERE status = 'pending'"),
        'enrollment_requests' => getCountSafe($pdo, "SELECT COUNT(*) FROM enrollment_change_requests WHERE status = 'pending'"),
        'student_messages' => getCountSafe($pdo, "SELECT COUNT(*) FROM admin_messages WHERE status = 'pending'"),
        'student_uploads' => getCountSafe($pdo, "SELECT (SELECT COUNT(*) FROM lectures WHERE status = 'pending') + (SELECT COUNT(*) FROM exams WHERE status = 'pending')"),
        'post_reviews' => getCountSafe($pdo, "SELECT COUNT(*) FROM post_reviews WHERE status = 'pending'")
    ];
} catch (PDOException $e) {
    die("خطأ حرج في قاعدة البيانات: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم التنفيذية - محاضراتي</title>
    <link rel="icon" type="image/svg+xml" href="../assets/images/favicon.svg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="assets/css/admin_global.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        .executive-container { display: flex; flex-direction: column; gap: 25px; }
        .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; }
        .kpi-card { background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 16px; padding: 20px; display: flex; align-items: center; justify-content: space-between; transition: all 0.3s ease; box-shadow: 0 4px 12px rgba(0,0,0,0.02); }
        .kpi-card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0,0,0,0.06); border-color: var(--brand-primary); }
        .kpi-details h3 { margin: 0; font-size: 0.95rem; color: var(--text-secondary); font-weight: 500; }
        .kpi-details .counter { font-size: 1.8rem; font-weight: 900; color: var(--text-primary); margin: 5px 0 0 0; }
        .kpi-icon { width: 55px; height: 55px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
        .kpi-icon img { width: 35px; height: 35px; object-fit: contain; } /* لضبط حجم الصور */
        
        .charts-section { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }
        @media (max-width: 992px) { .charts-section { grid-template-columns: 1fr; } }
        .chart-card { background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 16px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.02); }
        .chart-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid var(--border-color); }
        .chart-header h3 { margin: 0; font-size: 1.1rem; color: var(--text-primary); display: flex; align-items: center; gap: 8px; }
        
        .quick-actions-panel { display: flex; flex-direction: column; gap: 12px; }
        .action-item-btn { display: flex; align-items: center; justify-content: space-between; padding: 12px 15px; background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 10px; text-decoration: none; color: var(--text-primary); transition: 0.2s; font-weight: 500; font-size: 0.9rem; }
        .action-item-btn:hover { background: var(--brand-primary); color: white; border-color: var(--brand-primary); }
        .action-item-btn i { font-size: 1rem; }

        .urgent-alerts-bar { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 15px; margin-bottom: 5px; }
        .alert-widget { text-decoration: none; display: flex; align-items: center; justify-content: space-between; padding: 15px 20px; border-radius: 12px; background: var(--bg-primary); border: 1px solid var(--border-color); transition: 0.2s; }
        .alert-widget.active { border-color: var(--danger-color); background: rgba(220, 53, 69, 0.02); }
        .alert-widget:hover { transform: scale(1.01); }
        .alert-widget img { width: 20px; height: 20px; object-fit: contain; margin-left: 8px; vertical-align: middle; } /* ضبط صور التنبيهات */
        
        [data-theme="dark"] .kpi-card, [data-theme="dark"] .chart-card, [data-theme="dark"] .alert-widget { background: var(--bg-secondary); }
        [data-theme="dark"] .action-item-btn { background: var(--bg-primary); }
    </style>
</head>
<body class="admin-body">

    <script>
        (function() {
            const savedTheme = localStorage.getItem('mo7adaraty_theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>

    <?php include 'templates/sidebar.php'; ?>

    <main class="admin-main">
        <?php include 'templates/header.php'; ?>

        <div class="admin-content-wrapper">
            <div class="executive-container">
                
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <h1 style="margin: 0; color: var(--text-primary); display: flex; align-items: center; gap: 12px; font-size: 1.6rem;">
                        <img src="<?php echo $img_path; ?>admin_icon_dashboard.png" alt="Dashboard" style="width: 35px; height: 35px;">
                        اللوحة التنفيذية الموحدة
                    </h1>
                    <a href="search_engine.php" class="action-item-btn" style="background: var(--brand-primary); color: white; padding: 8px 16px; border-radius: 8px;">
                        <i class="fa-solid fa-magnifying-glass" style="margin-left: 5px;"></i> المحرك الشامل
                    </a>
                </div>

                <div class="urgent-alerts-bar">
                    <a href="enrollment_requests/index.php" class="alert-widget <?php echo $pending_alerts['enrollment_requests'] > 0 ? 'active' : ''; ?>">
                        <span style="color: var(--text-primary); font-weight: 500;"><img src="<?php echo $img_path; ?>admin_icon_enrollment_requests.png" alt="Icon"> طلبات تغيير المقرر</span>
                        <span class="badge" style="background: <?php echo $pending_alerts['enrollment_requests'] > 0 ? 'var(--danger-color)' : 'var(--success-color)'; ?>; color: white; padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: bold;"><?php echo $pending_alerts['enrollment_requests']; ?> معلق</span>
                    </a>
                    <a href="reset_requests/index.php" class="alert-widget <?php echo $pending_alerts['reset_requests'] > 0 ? 'active' : ''; ?>">
                        <span style="color: var(--text-primary); font-weight: 500;"><img src="<?php echo $img_path; ?>admin_icon_reset_requests.png" alt="Icon"> تعيين كلمة السر</span>
                        <span class="badge" style="background: <?php echo $pending_alerts['reset_requests'] > 0 ? 'var(--danger-color)' : 'var(--success-color)'; ?>; color: white; padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: bold;"><?php echo $pending_alerts['reset_requests']; ?> معلق</span>
                    </a>
                    <a href="messages/index.php" class="alert-widget <?php echo $pending_alerts['student_messages'] > 0 ? 'active' : ''; ?>">
                        <span style="color: var(--text-primary); font-weight: 500;"><img src="<?php echo $img_path; ?>admin_icon_messages.png" alt="Icon"> رسائل الدعم الفني</span>
                        <span class="badge" style="background: <?php echo $pending_alerts['student_messages'] > 0 ? 'var(--danger-color)' : 'var(--success-color)'; ?>; color: white; padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: bold;"><?php echo $pending_alerts['student_messages']; ?> جديدة</span>
                    </a>
                    <a href="student_uploads/index.php" class="alert-widget <?php echo $pending_alerts['student_uploads'] > 0 ? 'active' : ''; ?>">
                        <span style="color: var(--text-primary); font-weight: 500;"><img src="<?php echo $img_path; ?>admin_icon_uploads.png" alt="Icon"> مرفوعات الطلاب</span>
                        <span class="badge" style="background: <?php echo $pending_alerts['student_uploads'] > 0 ? 'var(--danger-color)' : 'var(--success-color)'; ?>; color: white; padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: bold;"><?php echo $pending_alerts['student_uploads']; ?> طلب</span>
                    </a>
                    <a href="post_reviews/index.php" class="alert-widget <?php echo $pending_alerts['post_reviews'] > 0 ? 'active' : ''; ?>">
                        <span style="color: var(--text-primary); font-weight: 500;"><img src="<?php echo $img_path; ?>admin_icon_post_reviews.png" alt="Icon"> مراجعة المنشورات</span>
                        <span class="badge" style="background: <?php echo $pending_alerts['post_reviews'] > 0 ? 'var(--danger-color)' : 'var(--success-color)'; ?>; color: white; padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: bold;"><?php echo $pending_alerts['post_reviews']; ?> معلق</span>
                    </a>
                </div>

                <div class="kpi-grid">
                    <div class="kpi-card">
                        <div class="kpi-details">
                            <h3>إجمالي الطلاب</h3>
                            <div class="counter"><?php echo $stats['students']; ?></div>
                        </div>
                        <div class="kpi-icon" style="background: rgba(23, 162, 184, 0.1); color: #17a2b8;">
                            <img src="<?php echo $img_path; ?>admin_icon_users.png" alt="Users">
                        </div>
                    </div>

                    <div class="kpi-card">
                        <div class="kpi-details">
                            <h3>هيئة التدريس</h3>
                            <div class="counter"><?php echo $stats['professors']; ?></div>
                        </div>
                        <div class="kpi-icon" style="background: rgba(111, 66, 193, 0.1); color: #6f42c1;">
                            <img src="<?php echo $img_path; ?>admin_icon_professors.png" alt="Professors">
                        </div>
                    </div>

                    <div class="kpi-card">
                        <div class="kpi-details">
                            <h3>المحاضرات الرقمية</h3>
                            <div class="counter"><?php echo $stats['lectures']; ?></div>
                        </div>
                        <div class="kpi-icon" style="background: rgba(40, 167, 69, 0.1); color: #28a745;">
                            <img src="<?php echo $img_path; ?>admin_icon_lectures.png" alt="Lectures">
                        </div>
                    </div>

                    <div class="kpi-card">
                        <div class="kpi-details">
                            <h3>نماذج الامتحانات</h3>
                            <div class="counter"><?php echo $stats['exams']; ?></div>
                        </div>
                        <div class="kpi-icon" style="background: rgba(253, 126, 20, 0.1); color: #fd7e14;">
                            <img src="<?php echo $img_path; ?>admin_icon_exams.png" alt="Exams">
                        </div>
                    </div>

                    <div class="kpi-card">
                        <div class="kpi-details">
                            <h3>مجموعات التواصل</h3>
                            <div class="counter"><?php echo $stats['whatsapp']; ?></div>
                        </div>
                        <div class="kpi-icon" style="background: rgba(37, 211, 102, 0.1); color: #25d366;">
                            <img src="<?php echo $img_path; ?>admin_icon_whatsapp.png" alt="WhatsApp">
                        </div>
                    </div>

                    <div class="kpi-card">
                        <div class="kpi-details">
                            <h3>بلاغات معلقة</h3>
                            <div class="counter"><?php echo $stats['reports']; ?></div>
                        </div>
                        <div class="kpi-icon" style="background: rgba(220, 53, 69, 0.1); color: #dc3545;">
                            <img src="<?php echo $img_path; ?>admin_icon_reports.png" alt="Reports">
                        </div>
                    </div>
                </div>

                <div class="charts-section">
                    <div class="chart-card">
                        <div class="chart-header">
                            <h3><i class="fa-solid fa-chart-bar" style="color: var(--brand-primary);"></i> توزيع المحتوى التعليمي عبر الأقطاب</h3>
                        </div>
                        <div style="position: relative; height: 260px;">
                            <canvas id="contentDistributionChart"></canvas>
                        </div>
                    </div>

                    <div class="chart-card">
                        <div class="chart-header">
                            <h3><i class="fa-solid fa-bolt" style="color: #ffc107;"></i> إجراءات إدارية سريعة</h3>
                        </div>
                        <div class="quick-actions-panel">
                            <a href="lectures/add.php" class="action-item-btn">
                                <span><img src="<?php echo $img_path; ?>admin_icon_lectures.png" alt="Icon" style="width: 16px; height: 16px; margin-left: 8px; vertical-align: middle;"> إضافة محاضرة جديدة</span>
                                <i class="fa-solid fa-chevron-left"></i>
                            </a>
                            <a href="exams/add.php" class="action-item-btn">
                                <span><img src="<?php echo $img_path; ?>admin_icon_exams.png" alt="Icon" style="width: 16px; height: 16px; margin-left: 8px; vertical-align: middle;"> رفع نموذج امتحان</span>
                                <i class="fa-solid fa-chevron-left"></i>
                            </a>
                            <a href="publish_announcement.php" class="action-item-btn">
                                <span><img src="<?php echo $img_path; ?>admin_icon_announcement.png" alt="Icon" style="width: 16px; height: 16px; margin-left: 8px; vertical-align: middle;"> إذاعة إعلان عام للطلاب</span>
                                <i class="fa-solid fa-chevron-left"></i>
                            </a>
                            <a href="post_reviews/index.php" class="action-item-btn">
                                <span><img src="<?php echo $img_path; ?>admin_icon_post_reviews.png" alt="Icon" style="width: 16px; height: 16px; margin-left: 8px; vertical-align: middle;"> مراجعة منشورات الطلاب</span>
                                <i class="fa-solid fa-chevron-left"></i>
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="assets/js/admin_global.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const ctx = document.getElementById('contentDistributionChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['المحاضرات', 'الامتحانات', 'المجموعات', 'البلاغات'],
                    datasets: [{
                        label: 'إحصائيات المنصة الحالية',
                        data: [<?php echo $stats['lectures']; ?>, <?php echo $stats['exams']; ?>, <?php echo $stats['whatsapp']; ?>, <?php echo $stats['reports']; ?>],
                        backgroundColor: [
                            'rgba(40, 167, 69, 0.7)',
                            'rgba(253, 126, 20, 0.7)',
                            'rgba(37, 211, 102, 0.7)',
                            'rgba(220, 53, 69, 0.7)'
                        ],
                        borderColor: [
                            '#28a745',
                            '#fd7e14',
                            '#25d366',
                            '#dc3545'
                        ],
                        borderWidth: 1,
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } },
                        x: { grid: { display: false } }
                    }
                }
            });
        });
    </script>
</body>
</html>