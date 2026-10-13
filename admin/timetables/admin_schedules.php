<?php
session_start();
require_once '../../config/db_connexion.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

try {
    $stmt = $pdo->query("
        SELECT s.id, s.file_path, s.status, s.created_at, u.username 
        FROM schedules s
        JOIN users u ON s.student_id = u.user_id
        ORDER BY s.created_at DESC
    ");
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("خطأ في جلب البيانات: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مراجعة استعمالات الزمن المرفوعة - Mo7adaraty</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/admin_global.css">
    <link rel="stylesheet" href="../assets/css/admin_academic.css">
    <style>
        .status-pending { color: #856404; background-color: #fff3cd; padding: 5px 10px; border-radius: 4px; font-weight: bold;}
        .status-approved { color: #155724; background-color: #d4edda; padding: 5px 10px; border-radius: 4px; font-weight: bold;}
        .status-rejected { color: #721c24; background-color: #f8d7da; padding: 5px 10px; border-radius: 4px; font-weight: bold;}
        .table-container { background: var(--bg-surface); padding: 20px; border-radius: var(--radius-md); box-shadow: 0 2px 4px rgba(0,0,0,0.05); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px 15px; text-align: right; border-bottom: 1px solid var(--border-color); }
        th { background-color: var(--brand-light); color: var(--brand-primary); font-weight: bold; }
        .btn-action { border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; color: #fff; font-family: 'Tajawal', sans-serif; font-weight: bold; display: inline-flex; align-items: center; gap: 5px; font-size: 14px; }
        .btn-approve { background-color: #28a745; }
        .btn-approve:hover { background-color: #218838; }
        .btn-reject { background-color: #dc3545; }
        .btn-reject:hover { background-color: #c82333; }
        .btn-view { background-color: #17a2b8; text-decoration: none; color: white; padding: 8px 12px; border-radius: 4px; display: inline-flex; align-items: center; gap: 5px; font-size: 14px; font-weight: bold; }
        .btn-view:hover { background-color: #138496; }
        .actions-form { display: inline-block; margin: 0 2px; }
    </style>
</head>
<body class="admin-body">
    <?php include '../templates/sidebar.php'; ?>
    <main class="admin-main">
        <?php include '../templates/header.php'; ?>
        
        <div class="admin-content-wrapper">
            <div class="academic-header-actions">
                <h2 style="margin: 0; color: var(--text-primary); display: flex; align-items: center; gap: 10px;">
                    <i class="fa-solid fa-file-circle-check" style="font-size: 30px; color: var(--brand-primary);"></i>
                    مراجعة استعمالات الزمن
                </h2>
            </div>

            <?php if (isset($_SESSION['msg'])): ?>
                <div style="padding: 15px; background: rgba(40, 167, 69, 0.1); color: var(--success-color); border: 1px solid var(--success-color); border-radius: var(--radius-md); margin-bottom: 20px; font-weight: bold;">
                    <i class="fa-solid fa-circle-check"></i> <?php echo $_SESSION['msg']; unset($_SESSION['msg']); ?>
                </div>
            <?php endif; ?>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>الطالب</th>
                            <th>تاريخ الرفع</th>
                            <th>الملف</th>
                            <th>الحالة الحالية</th>
                            <th>إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($schedules)): ?>
                            <tr><td colspan="5" style="text-align: center; padding: 30px; color: var(--text-secondary);">لا توجد استعمالات زمن مرفوعة حالياً.</td></tr>
                        <?php else: ?>
                            <?php foreach($schedules as $sch): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($sch['username']); ?></td>
                                    <td dir="ltr" style="text-align: right;"><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($sch['created_at']))); ?></td>
                                    <td>
                                        <a href="../../<?php echo htmlspecialchars($sch['file_path']); ?>" target="_blank" class="btn-view">
                                            <i class="fa-solid fa-eye"></i> عرض الملف
                                        </a>
                                    </td>
                                    <td>
                                        <span class="status-<?php echo $sch['status']; ?>">
                                            <?php 
                                                if($sch['status'] == 'pending') echo 'قيد الانتظار';
                                                elseif($sch['status'] == 'approved') echo 'مقبول';
                                                else echo 'مرفوض';
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if($sch['status'] == 'pending'): ?>
                                            <form action="schedule_action.php" method="POST" class="actions-form">
                                                <input type="hidden" name="schedule_id" value="<?php echo $sch['id']; ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <button type="submit" class="btn-action btn-approve"><i class="fa-solid fa-check"></i> قبول</button>
                                            </form>
                                            <form action="schedule_action.php" method="POST" class="actions-form" onsubmit="return confirm('هل أنت متأكد من رفض هذا الملف؟');">
                                                <input type="hidden" name="schedule_id" value="<?php echo $sch['id']; ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <button type="submit" class="btn-action btn-reject"><i class="fa-solid fa-xmark"></i> رفض</button>
                                            </form>
                                        <?php else: ?>
                                            <span style="color: var(--text-secondary); font-size: 14px; font-weight: bold;"><i class="fa-solid fa-lock"></i> تمت المعالجة</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    <script src="../assets/js/admin_global.js"></script>
</body>
</html>