<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية الصفحة
if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

try {
    // 1. جلب قائمة المواد لترجمة المعرفات (IDs) إلى أسماء
    $stmt_subs = $pdo->query("SELECT subject_id, subject_name FROM subjects");
    $subjects_map = [];
    while ($row = $stmt_subs->fetch(PDO::FETCH_ASSOC)) {
        $subjects_map[$row['subject_id']] = $row['subject_name'];
    }

    // 2. استعلام لجلب الطلبات مع حقل المواد (requested_subjects)
    $stmt = $pdo->query("
        SELECT 
            req.request_id, req.user_id, req.request_date, req.reason,
            req.current_enrollment_info, req.requested_subjects, u.username,
            d.division_name as requested_division_name,
            c.class_name as requested_class_name,
            g.group_name as requested_group_name,
            t.track_name as requested_track_name
        FROM enrollment_change_requests req
        JOIN users u ON req.user_id = u.user_id
        JOIN course_offerings co ON req.requested_offering_id = co.offering_id
        JOIN divisions d ON co.division_id = d.division_id
        JOIN classes c ON co.class_id = c.class_id
        JOIN `groups` g ON co.group_id = g.group_id
        LEFT JOIN tracks t ON co.track_id = t.track_id
        WHERE req.status = 'pending'
        ORDER BY req.request_date ASC
    ");
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("خطأ في جلب البيانات: ". $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>طلبات تغيير المقرر - لوحة التحكم</title>
    <link rel="icon" type="image/svg+xml" href="/mo7adaraty/assets/images/favicon.svg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;900&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="../../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/admin_global.css">
    
    <style>
        .enrollment-info {
            white-space: pre-wrap;
            font-size: 0.85em;
            color: var(--text-secondary);
            background: var(--bg-secondary);
            padding: 8px 12px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            line-height: 1.6;
        }
        .action-buttons {
            display: flex;
            gap: 8px;
            justify-content: center;
        }
        .btn-approve {
            background-color: rgba(40, 167, 69, 0.1);
            color: #28a745;
            border: 1px solid #28a745;
            padding: 6px 12px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: inherit;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .btn-approve:hover {
            background-color: #28a745;
            color: white;
            transform: translateY(-2px);
        }
        .btn-reject {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            border: 1px solid #dc3545;
            padding: 6px 12px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: inherit;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .btn-reject:hover {
            background-color: #dc3545;
            color: white;
            transform: translateY(-2px);
        }
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: var(--text-secondary);
        }
        .success-msg {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
            border: 1px solid #28a745;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: bold;
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
                        <img src="../dashboard_design/images/admin_icon_enrollment_requests.png" alt="Enrollment Requests" style="width: 40px;" onerror="this.src='../../assets/icons/exchange-alt.png'">
                        طلبات تغيير المقرر الدراسي
                    </h1>
                    <p style="color: var(--text-secondary); margin-top: 5px;">مراجعة وإدارة طلبات الطلاب لتغيير مساراتهم أو أفواجهم.</p>
                </div>
            </div>

            <?php if (isset($_SESSION['request_status_msg'])): ?>
                <div class="success-msg skeleton">
                    <i class="fa-solid fa-circle-check" style="font-size: 1.2rem;"></i>
                    <?php echo htmlspecialchars($_SESSION['request_status_msg']); ?>
                </div>
                <?php unset($_SESSION['request_status_msg']); ?>
            <?php endif; ?>

            <div class="admin-table-container skeleton">
                <div style="padding: 20px; border-bottom: 1px solid var(--border-color);">
                    <h3 style="margin: 0; color: var(--text-primary); display: flex; align-items: center; gap: 10px;">
                        <i class="fa-solid fa-list-check text-[var(--brand-primary)]"></i> 
                        الطلبات المعلقة 
                        <span style="background: var(--brand-primary); color: white; padding: 2px 8px; border-radius: 20px; font-size: 14px;"><?php echo count($requests); ?></span>
                    </h3>
                </div>
                
                <?php if (empty($requests)): ?>
                    <div class="empty-state">
                        <img src="../../assets/icons/check-to-slot.png" alt="Empty" style="width: 80px; opacity: 0.3; margin-bottom: 15px;" onerror="this.src='../../assets/images/favicon.png'">
                        <h3 style="color: var(--text-primary); margin-bottom: 5px;">لا توجد طلبات معلقة</h3>
                        <p>جميع طلبات تغيير المقررات تمت معالجتها. أحسنت!</p>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>الطالب</th>
                                    <th>التسجيل الحالي</th>
                                    <th>المقرر والمواد المطلوبة</th>
                                    <th>السبب</th>
                                    <th style="text-align: center;">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($requests as $request): ?>
                                <tr>
                                    <td style="font-weight: bold; color: var(--text-primary);">
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <img src="../../assets/icons/student.png" alt="Student" style="width: 35px; height: 35px;" onerror="this.src='../../assets/images/favicon.png'">
                                            <?php echo htmlspecialchars($request['username']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="enrollment-info">
                                            <?php echo htmlspecialchars($request['current_enrollment_info']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span style="color: var(--brand-primary); font-weight: bold; display: block;">
                                            <i class="fa-solid fa-arrow-turn-down fa-rotate-90"></i>
                                            <?php echo htmlspecialchars($request['requested_division_name'] . ' > ' . $request['requested_class_name'] . ' / ' . $request['requested_group_name'] . ($request['requested_track_name'] ? ' / ' . $request['requested_track_name'] : '')); ?>
                                        </span>
                                        <?php 
                                            // عرض المواد التي اختارها الطالب
                                            $req_subs = $request['requested_subjects'] ? json_decode($request['requested_subjects'], true) : null;
                                            if (is_array($req_subs) && !empty($req_subs)) {
                                                echo '<div style="margin-top: 8px; font-size: 12px; color: var(--text-secondary); background: var(--bg-surface); padding: 8px; border-radius: 6px; border: 1px dashed var(--border-color);">';
                                                echo '<strong style="color:var(--text-primary);"><i class="fa-solid fa-book-open"></i> المواد المختارة:</strong><br>';
                                                $sub_names = [];
                                                foreach ($req_subs as $sid) {
                                                    if (isset($subjects_map[$sid])) {
                                                        $sub_names[] = htmlspecialchars($subjects_map[$sid]);
                                                    }
                                                }
                                                echo implode('، ', $sub_names);
                                                echo '</div>';
                                            } else {
                                                echo '<div style="margin-top: 8px; font-size: 12px; color: #dc3545;">* لم يقم باختيار مواد محددة (يدرس الجميع)</div>';
                                            }
                                        ?>
                                    </td>
                                    <td>
                                        <span style="color: var(--text-secondary);">
                                            <?php echo htmlspecialchars($request['reason'] ?: 'لم يذكر سبب'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <form action="process_request.php" method="POST" onsubmit="return confirm('هل أنت متأكد من الموافقة؟ سيتم تغيير تسجيل الطالب ومواده فوراً.');" style="margin: 0;">
                                                <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                                                <input type="hidden" name="user_id" value="<?php echo $request['user_id']; ?>">
                                                <button type="submit" name="action" value="completed" class="btn-approve" title="موافقة وتحديث التسجيل">
                                                    <i class="fa-solid fa-check"></i> موافقة
                                                </button>
                                            </form>
                                            <form action="process_request.php" method="POST" onsubmit="return confirm('هل أنت متأكد من رفض هذا الطلب؟');" style="margin: 0;">
                                                 <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                                                 <input type="hidden" name="user_id" value="<?php echo $request['user_id']; ?>">
                                                <button type="submit" name="action" value="rejected" class="btn-reject" title="رفض الطلب">
                                                    <i class="fa-solid fa-times"></i> رفض
                                                </button>
                                            </form>
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