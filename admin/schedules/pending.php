<?php
session_start();
require_once '../../config/db_connexion.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

// جلب الجداول التي حالتها pending
try {
    // استخدمنا JOIN إذا أردت إظهار اسم الطالب من جدول users
    // جلب الجداول التي حالتها pending مع اسم الطالب
    $stmt = $pdo->prepare("SELECT s.id, s.file_path, s.created_at, u.username AS student_name 
                       FROM schedules s 
                       JOIN users u ON s.student_id = u.user_id 
                       WHERE s.status = 'pending' 
                       ORDER BY s.created_at ASC");
    $stmt->execute();
    $pending_schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("خطأ في قاعدة البيانات: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إدارة استعمالات الزمن - الإدارة</title>
    <style>
        body { font-family: sans-serif; padding: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: center; }
        th { background-color: #343a40; color: #fff; }
        .btn { padding: 6px 12px; border: none; cursor: pointer; color: white; text-decoration: none; border-radius: 3px; }
        .btn-view { background-color: #17a2b8; }
        .btn-approve { background-color: #28a745; }
        .btn-reject { background-color: #dc3545; }
        .alert { padding: 10px; margin-bottom: 15px; border-radius: 4px; background: #d4edda; color: #155724; }
    </style>
</head>
<body>
        <?php include '../templates/sidebar.php'; ?>
        <?php include '../templates/header.php'; ?>

    <h2>استعمالات الزمن قيد الانتظار</h2>
    
    <?php if(isset($_SESSION['msg'])): ?>
        <div class="alert"><?php echo $_SESSION['msg']; unset($_SESSION['msg']); ?></div>
    <?php endif; ?>

    <table>
        <tr>
            <th>الطالب</th>
            <th>تاريخ الرفع</th>
            <th>الملف</th>
            <th>إجراءات</th>
        </tr>
        <?php if(empty($pending_schedules)): ?>
            <tr><td colspan="4">لا توجد طلبات معلقة حالياً.</td></tr>
        <?php else: ?>
            <?php foreach($pending_schedules as $row): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                <td><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($row['created_at']))); ?></td>
                <td><a href="../../<?php echo htmlspecialchars($row['file_path']); ?>" target="_blank" class="btn btn-view">عرض</a></td>
                <td>
                    <form action="action.php" method="POST" style="display:inline;">
                        <input type="hidden" name="schedule_id" value="<?php echo $row['id']; ?>">
                        <input type="hidden" name="action" value="approve">
                        <button type="submit" class="btn btn-approve">موافقة</button>
                    </form>
                    <form action="action.php" method="POST" style="display:inline;">
                        <input type="hidden" name="schedule_id" value="<?php echo $row['id']; ?>">
                        <input type="hidden" name="action" value="reject">
                        <button type="submit" class="btn btn-reject">رفض</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </table>
</body>
</html>