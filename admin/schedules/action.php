<?php
session_start();
require_once '../../config/db_connexion.php';

// تحقق من صلاحيات الأدمن هنا

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $schedule_id = filter_input(INPUT_POST, 'schedule_id', FILTER_VALIDATE_INT);
    $action = $_POST['action'] ?? '';

    if ($schedule_id && in_array($action, ['approve', 'reject'])) {
        $new_status = ($action === 'approve') ? 'approved' : 'rejected';

        try {
            // تحديث الحالة باستخدام Prepared Statements
            $stmt = $pdo->prepare("UPDATE schedules SET status = :status WHERE id = :id");
            $stmt->bindParam(':status', $new_status, PDO::PARAM_STR);
            $stmt->bindParam(':id', $schedule_id, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                $_SESSION['msg'] = "تم " . ($action === 'approve' ? 'الموافقة على' : 'رفض') . " استعمال الزمن بنجاح.";
            } else {
                $_SESSION['msg'] = "حدث خطأ أثناء التحديث.";
            }
        } catch (PDOException $e) {
            $_SESSION['msg'] = "خطأ في قاعدة البيانات.";
        }
    } else {
        $_SESSION['msg'] = "بيانات غير صالحة.";
    }
}

header("Location: pending.php");
exit();