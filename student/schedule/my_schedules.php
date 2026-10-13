<?php
session_start();
require_once '../../config/db_connexion.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$student_id = $_SESSION['user_id'];

// جلب الجداول الخاصة بالطالب
try {
    $stmt = $pdo->prepare("SELECT id, file_path, status, created_at FROM schedules WHERE student_id = :student_id ORDER BY created_at DESC");
    $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
    $stmt->execute();
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("خطأ في جلب البيانات.");
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>جداولي - محاضراتي</title>
    <style>
        body { font-family: sans-serif; padding: 20px; background: #f4f7f6; }
        table { width: 100%; border-collapse: collapse; background: #fff; margin-top: 20px;}
        th, td { border: 1px solid #ddd; padding: 12px; text-align: center; }
        th { background-color: #007bff; color: white; }
        .status-pending { color: #856404; background-color: #fff3cd; padding: 5px 10px; border-radius: 4px;}
        .status-approved { color: #155724; background-color: #d4edda; padding: 5px 10px; border-radius: 4px;}
        .status-rejected { color: #721c24; background-color: #f8d7da; padding: 5px 10px; border-radius: 4px;}
        .btn-view { text-decoration: none; background: #17a2b8; color: white; padding: 5px 10px; border-radius: 3px; }
    </style>
</head>
<body>
    <h2>استعمالات الزمن الخاصة بي</h2>
    <a href="index.php">رفع جدول جديد</a>
    <table>
        <tr>
            <th>تاريخ الرفع</th>
            <th>الملف</th>
            <th>الحالة</th>
        </tr>
        <?php if(empty($schedules)): ?>
            <tr><td colspan="3">لا توجد جداول مرفوعة حالياً.</td></tr>
        <?php else: ?>
            <?php foreach($schedules as $sch): ?>
            <tr>
                <td><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($sch['created_at']))); ?></td>
                <td><a href="../../<?php echo htmlspecialchars($sch['file_path']); ?>" target="_blank" class="btn-view">عرض الملف</a></td>
                <td>
                    <span class="status-<?php echo $sch['status']; ?>">
                        <?php 
                            if($sch['status'] == 'pending') echo 'قيد الانتظار';
                            elseif($sch['status'] == 'approved') echo 'مقبول';
                            else echo 'مرفوض';
                        ?>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </table>
</body>
</html>