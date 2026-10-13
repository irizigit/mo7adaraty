<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../config/db_connexion.php';

// حماية الصفحة والتأكد من أن المستخدم هو أدمن
if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../login.php");
    exit();
}

// استقبال البيانات من نموذج الرد
$message_id = filter_input(INPUT_POST, 'message_id', FILTER_VALIDATE_INT);
$student_user_id = filter_input(INPUT_POST, 'student_user_id', FILTER_VALIDATE_INT);
$admin_reply = trim($_POST['admin_reply'] ?? '');

// التحقق من صحة البيانات
if (!$message_id || !$student_user_id || empty($admin_reply)) {
    $_SESSION['error'] = "خطأ: يرجى كتابة نص الرد.";
    header("Location: index.php");
    exit();
}

try {
    // استخدام معاملة آمنة (Transaction) لضمان تنفيذ العمليتين معاً
    $pdo->beginTransaction();

    // 1. تحديث الرسالة الأصلية بالرد وتغيير حالتها
    // ملاحظة: الكود القديم كان يعتمد على pending_reply، سأدعمها وأدعم pending العادية لضمان التوافق
    $stmt_update = $pdo->prepare(
        "UPDATE admin_messages 
         SET admin_reply = ?, status = 'replied', replied_at = CURRENT_TIMESTAMP 
         WHERE message_id = ? AND (status = 'pending_reply' OR status = 'pending')"
    );
    $stmt_update->execute([$admin_reply, $message_id]);

    if ($stmt_update->rowCount() === 0) {
         throw new Exception("الرسالة غير موجودة أو تم الرد عليها مسبقاً.");
    }

    // 2. إرسال إشعار جديد للطالب بالرد
    $notification_message = "لقد تلقيت رداً من الإدارة على رسالتك.";
    $stmt_notify = $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
    // الرابط يوجه الطالب إلى صفحة التواصل ليرى الرد
    $stmt_notify->execute([$student_user_id, $notification_message, 'student/contact/index.php']);

    // تأكيد العمليات
    $pdo->commit();

    // استخدام المعيار الموحد الجديد للتنبيهات
    $_SESSION['msg'] = "تم إرسال الرد للطالب بنجاح.";

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Admin reply error: " . $e->getMessage());
    $_SESSION['error'] = "حدث خطأ أثناء إرسال الرد: " . $e->getMessage();
}

header("Location: index.php");
exit();