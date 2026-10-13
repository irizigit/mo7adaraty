<?php
session_start();
require_once '../../config/db_connexion.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

$breadcrumbs = [
    "إدارة المرفوعات" => ""
];

try {
    $stmt_lec = $pdo->query("SELECT COUNT(*) FROM lectures WHERE status = 'pending'");
    $pending_lectures_count = $stmt_lec->fetchColumn();

    $stmt_exam = $pdo->query("SELECT COUNT(*) FROM exams WHERE status = 'pending'");
    $pending_exams_count = $stmt_exam->fetchColumn();
} catch (PDOException $e) {
    die("خطأ في قاعدة البيانات: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة مرفوعات الطلاب - Mo7adaraty</title>
    <link rel="icon" type="image/svg+xml" href="/mo7adaraty/assets/images/favicon.svg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/admin_global.css">
    <style>
        .uploads-hub-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }
        .hub-card {
            background: var(--bg-surface);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 30px;
            text-align: center;
            box-shadow: var(--shadow-sm);
            transition: all var(--transition-normal);
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        .hub-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
            border-color: var(--brand-primary);
        }
        .hub-icon-wrapper {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: rgba(26, 115, 232, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
        }
        .hub-icon-wrapper i {
            font-size: 35px;
            color: var(--brand-primary);
        }
        .hub-card h3 {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
            color: var(--text-primary);
        }
        .hub-card p {
            margin: 0;
            font-size: 14px;
            color: var(--text-secondary);
        }
        .badge-count {
            position: absolute;
            top: 20px;
            left: 20px;
            background: var(--danger-color);
            color: #fff;
            font-size: 12px;
            font-weight: bold;
            padding: 4px 10px;
            border-radius: var(--radius-pill);
        }
        .badge-count.zero {
            background: var(--success-color);
        }
        .hub-btn {
            margin-top: 10px;
            padding: 10px 24px;
            background: var(--brand-primary);
            color: #fff;
            border-radius: var(--radius-md);
            text-decoration: none;
            font-weight: bold;
            font-size: 14px;
            transition: opacity 0.2s;
        }
        .hub-btn:hover {
            opacity: 0.9;
        }
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
            <div class="academic-header-actions" style="margin-bottom: 25px;">
                <h2 style="margin: 0; color: var(--text-primary); display: flex; align-items: center; gap: 10px;">
                    <img src="../assets/icons/hourglass-half.png" alt="Uploads" style="width: 40px; height: 40px; object-fit: contain;">
                    طلبات مراجعة مرفوعات الطلاب
                </h2>
                <p style="color: var(--text-secondary); margin: 5px 0 0 0; font-size: 14px;">من هنا يمكنك مراجعة، قبول أو رفض المحاضرات والامتحانات المرفوعة على Google Drive من قبل الطلاب قبل نشرها للعموم.</p>
            </div>

            <div class="uploads-hub-grid">
                
                <div class="hub-card">
                    <span class="badge-count <?php echo ($pending_lectures_count == 0) ? 'zero' : ''; ?>">
                        <?php echo $pending_lectures_count; ?> معلقة
                    </span>
                    <div class="hub-icon-wrapper">
                        <i class="fa-solid fa-file-video"></i>
                    </div>
                    <h3>محاضرات الطلاب</h3>
                    <p>طلبات نشر ملفات المحاضرات والدروس والملخصات المرفوعة سحابياً.</p>
                    <a href="lectures.php" class="hub-btn">عرض الطلبات</a>
                </div>

                <div class="hub-card">
                    <span class="badge-count <?php echo ($pending_exams_count == 0) ? 'zero' : ''; ?>">
                        <?php echo $pending_exams_count; ?> معلقة
                    </span>
                    <div class="hub-icon-wrapper" style="background: rgba(253, 126, 20, 0.1);">
                        <i class="fa-solid fa-file-signature" style="color: #fd7e14;"></i>
                    </div>
                    <h3>امتحانات الطلاب</h3>
                    <p>طلبات نشر الامتحانات السابقة والنماذج الاختبارية المصححة.</p>
                    <a href="exams.php" class="hub-btn" style="background: #fd7e14;">عرض الطلبات</a>
                </div>

            </div>
        </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/admin_global.js"></script>
</body>
</html>