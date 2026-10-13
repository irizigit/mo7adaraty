<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية الصفحة
if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

$status_message = '';
$error_message = '';

// معالجة تغيير حالة البلاغ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['report_id'])) {
    $report_id = $_POST['report_id'];
    $action = $_POST['action'];
    $new_status = in_array($action, ['resolve', 'dismiss']) ? ($action === 'resolve' ? 'resolved' : 'dismissed') : '';

    if ($new_status) {
        try {
            $stmt = $pdo->prepare("UPDATE reports SET status = ? WHERE report_id = ?");
            $stmt->execute([$new_status, $report_id]);
            $status_message = "تم تحديث حالة البلاغ بنجاح!";
        } catch (PDOException $e) { $error_message = "حدث خطأ أثناء تحديث البلاغ."; }
    }
}

// استعلام شامل لدعم بلاغات المنشورات والمحتوى الأكاديمي
try {
    $stmt = $pdo->query("
        SELECT 
            r.*, 
            reporter.username as reporter_username,
            COALESCE(l.title, e.title, SUBSTRING(p.post_text, 1, 50), SUBSTRING(c.comment_text, 1, 50)) as content_summary,
            author.username as author_username,
            CASE
                WHEN r.content_type = 'lecture' THEN CONCAT('../../student/class_lectures.php?id=', l.offering_id, '#lecture-', r.content_id)
                WHEN r.content_type = 'exam' THEN CONCAT('../../student/class_exams.php?id=', os_e.offering_id, '#exam-', r.content_id)
                WHEN r.content_type = 'post' THEN CONCAT('../../student/community/index.php#post-', r.content_id)
                WHEN r.content_type = 'comment' AND c.content_type = 'lecture' THEN CONCAT('../../student/class_lectures.php?id=', cl.offering_id, '#lecture-', c.content_id)
                WHEN r.content_type = 'comment' AND c.content_type = 'exam' THEN CONCAT('../../student/class_exams.php?id=', os_ce.offering_id, '#exam-', c.content_id)
                WHEN r.content_type = 'comment' AND c.content_type = 'post' THEN CONCAT('../../student/community/index.php#post-', c.content_id)
                ELSE '#'
            END as content_link
        FROM reports r
        JOIN users reporter ON r.reporter_user_id = reporter.user_id
        LEFT JOIN lectures l ON r.content_type = 'lecture' AND r.content_id = l.lecture_id
        LEFT JOIN exams e ON r.content_type = 'exam' AND r.content_id = e.exam_id
        LEFT JOIN posts p ON r.content_type = 'post' AND r.content_id = p.post_id
        LEFT JOIN comments c ON r.content_type = 'comment' AND r.content_id = c.comment_id
        LEFT JOIN users author ON author.user_id = COALESCE(l.uploader_user_id, e.uploader_user_id, p.user_id, c.user_id)
        
        LEFT JOIN offering_subjects os_e ON e.offering_subject_id = os_e.offering_subject_id
        LEFT JOIN lectures cl ON c.content_type = 'lecture' AND c.content_id = cl.lecture_id
        LEFT JOIN exams ce ON c.content_type = 'exam' AND c.content_id = ce.exam_id
        LEFT JOIN offering_subjects os_ce ON ce.offering_subject_id = os_ce.offering_subject_id

        ORDER BY FIELD(r.status, 'pending', 'resolved', 'dismissed'), r.created_at DESC
    ");
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { die("خطأ في جلب البلاغات: " . $e->getMessage()); }

// دالة لتحويل حالة البلاغ إلى نص عربي منسق
function getStatusBadge($status) {
    switch ($status) {
        case 'pending': return '<span class="status-badge status-pending"><i class="fa-solid fa-clock"></i> قيد المراجعة</span>';
        case 'resolved': return '<span class="status-badge status-resolved"><i class="fa-solid fa-check-circle"></i> تم الحل</span>';
        case 'dismissed': return '<span class="status-badge status-dismissed"><i class="fa-solid fa-times-circle"></i> مرفوض</span>';
        default: return $status;
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة البلاغات - لوحة التحكم</title>
    <link rel="icon" type="image/svg+xml" href="/mo7adaraty/assets/images/favicon.svg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;900&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="../../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/admin_global.css">
    
    <style>
        .action-buttons {
            display: flex;
            gap: 8px;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
        }
        .btn-approve {
            background-color: rgba(40, 167, 69, 0.1);
            color: #28a745;
            border: 1px solid #3e4d41;
            padding: 6px 12px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: inherit;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 0.9rem;
        }
        .btn-approve:hover { background-color: #28a745; color: white; transform: translateY(-2px); }
        
        .btn-reject {
            background-color: rgba(108, 117, 125, 0.1);
            color: #6c757d;
            border: 1px solid #6c757d;
            padding: 6px 12px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: inherit;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 0.9rem;
        }
        .btn-reject:hover { background-color: #6c757d; color: white; transform: translateY(-2px); }
        
        .btn-delete {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            border: 1px solid #dc3545;
            padding: 6px 12px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 0.9rem;
        }
        .btn-delete:hover { background-color: #dc3545; color: white; transform: translateY(-2px); }
        
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            white-space: nowrap;
        }
        .status-pending { background-color: rgba(255, 193, 7, 0.15); color: #d39e00; border: 1px solid #ffc107; }
        .status-resolved { background-color: rgba(40, 167, 69, 0.15); color: #28a745; border: 1px solid #28a745; }
        .status-dismissed { background-color: rgba(108, 117, 125, 0.15); color: #6c757d; border: 1px solid #6c757d; }
        
        .empty-state { text-align: center; padding: 50px 20px; color: var(--text-secondary); }
        .alert-msg { padding: 15px; border-radius: 10px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-weight: bold; }
        .alert-success { background: rgba(40, 167, 69, 0.1); color: #28a745; border: 1px solid #28a745; }
        .alert-error { background: rgba(220, 53, 69, 0.1); color: #dc3545; border: 1px solid #dc3545; }
        
        .report-reason {
            white-space: pre-wrap;
            font-size: 0.9em;
            color: var(--text-secondary);
            background: var(--bg-secondary);
            padding: 8px;
            border-radius: 6px;
            border: 1px solid var(--border-color);
            max-width: 250px;
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
            
            <div style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;" class="skeleton">
                <div>
                    <h1 style="margin: 0; color: var(--text-primary); display: flex; align-items: center; gap: 10px;">
                        <img src="../dashboard_design/images/admin_icon_reports.png" alt="Reports" style="width: 40px;" onerror="this.src='../../assets/icons/flag.png'">
                        إدارة البلاغات
                    </h1>
                    <p style="color: var(--text-secondary); margin-top: 5px;">مراجعة بلاغات المستخدمين حول المحتوى واتخاذ الإجراءات اللازمة للحفاظ على جودة المنصة.</p>
                </div>
            </div>

            <?php if ($status_message): ?>
                <div class="alert-msg alert-success skeleton"><i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($status_message); ?></div>
            <?php endif; ?>
            <?php if (isset($_GET['status']) && $_GET['status'] == 'deleted'): ?>
                <div class="alert-msg alert-success skeleton"><i class="fa-solid fa-trash-can"></i> تم حذف البلاغ بنجاح!</div>
            <?php endif; ?>
            <?php if ($error_message): ?>
                <div class="alert-msg alert-error skeleton"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <div class="admin-table-container skeleton">
                <div style="padding: 20px; border-bottom: 1px solid var(--border-color);">
                    <h3 style="margin: 0; color: var(--text-primary); display: flex; align-items: center; gap: 10px;">
                        <i class="fa-solid fa-flag text-[var(--brand-primary)]"></i> 
                        قائمة البلاغات 
                        <span style="background: var(--brand-primary); color: white; padding: 2px 8px; border-radius: 20px; font-size: 14px;"><?php echo count($reports); ?></span>
                    </h3>
                </div>
                
                <?php if (empty($reports)): ?>
                    <div class="empty-state">
                        <img src="../../assets/icons/check-to-slot.png" alt="Empty" style="width: 80px; opacity: 0.3; margin-bottom: 15px;" onerror="this.src='../../assets/images/favicon.png'">
                        <h3 style="color: var(--text-primary); margin-bottom: 5px;">لا توجد بلاغات حالياً</h3>
                        <p>المنصة نظيفة ولا توجد أي بلاغات تحتاج لمراجعة.</p>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>المُبلّغ</th>
                                    <th>المحتوى المُبلغ عنه</th>
                                    <th>صاحب المحتوى</th>
                                    <th>سبب البلاغ</th>
                                    <th>الحالة</th>
                                    <th style="text-align: center;">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reports as $report): ?>
                                <tr>
                                    <td style="font-weight: bold; color: var(--text-primary);">
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <img src="../../assets/icons/student.png" alt="User" style="width: 30px; height: 30px;" onerror="this.src='../../assets/images/favicon.png'">
                                            <?php echo htmlspecialchars($report['reporter_username']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="<?php echo htmlspecialchars($report['content_link']); ?>" target="_blank" title="عرض المحتوى" style="color: var(--brand-primary); font-weight: bold; text-decoration: none; display: flex; align-items: center; gap: 5px;">
                                            <i class="fa-solid fa-link"></i>
                                            <?php echo htmlspecialchars(mb_substr($report['content_summary'], 0, 40)) . (mb_strlen($report['content_summary']) > 40 ? '...' : ''); ?>
                                        </a>
                                    </td>
                                    <td><span style="color: var(--text-secondary);"><i class="fa-solid fa-user-pen"></i> <?php echo htmlspecialchars($report['author_username'] ?? 'غير معروف'); ?></span></td>
                                    <td>
                                        <div class="report-reason"><?php echo htmlspecialchars($report['reason']); ?></div>
                                    </td>
                                    <td>
                                        <?php echo getStatusBadge($report['status']); ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <?php if ($report['status'] === 'pending'): ?>
                                                <form action="index.php" method="POST" style="margin: 0;">
                                                    <input type="hidden" name="report_id" value="<?php echo $report['report_id']; ?>">
                                                    <button type="submit" name="action" value="resolve" class="btn-approve" title="حل البلاغ">
                                                        <i class="fa-solid fa-check"></i> حل
                                                    </button>
                                                </form>
                                                <form action="index.php" method="POST" style="margin: 0;">
                                                    <input type="hidden" name="report_id" value="<?php echo $report['report_id']; ?>">
                                                    <button type="submit" name="action" value="dismiss" class="btn-reject" title="رفض البلاغ">
                                                        <i class="fa-solid fa-times"></i> رفض
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            <a href="delete_process.php?id=<?php echo $report['report_id']; ?>" class="btn-delete" onclick="return confirm('هل أنت متأكد من حذف هذا البلاغ نهائياً؟');" title="حذف البلاغ">
                                                <i class="fa-solid fa-trash-can"></i> حذف
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </main>

    <script src="../assets/js/admin_global.js"></script>
</body>
</html>