<?php
session_start();
require_once '../../config/db_connexion.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../login.php");
    exit();
}

$user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

// منع المدير من حذف حسابه الشخصي!
if ($user_id == $_SESSION['user_id']) {
    $_SESSION['error'] = "إجراء مرفوض: لا يمكنك حذف الحساب الذي تستخدمه حالياً.";
    header("Location: index.php");
    exit();
}

if ($user_id) {
    try {
        $pdo->beginTransaction();

        // 1. جلب الصورة لحذفها من السيرفر
        $stmt_pic = $pdo->prepare("SELECT profile_picture FROM users WHERE user_id = ?");
        $stmt_pic->execute([$user_id]);
        $profile_pic = $stmt_pic->fetchColumn();

        // 2. حذف المستخدم (قاعدة البيانات ستحذف الارتباطات عبر CASCADE إذا كانت معدة كذلك)
        $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);

        // 3. مسح الصورة الفعلية
        if ($profile_pic && $profile_pic !== 'default_profile.png') {
            $pic_path = '../../uploads/profile_pictures/' . $profile_pic;
            if (file_exists($pic_path)) {
                unlink($pic_path);
            }
        }

        $pdo->commit();
        $_SESSION['msg'] = "تم حذف المستخدم بنجاح.";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "حدث خطأ أثناء الحذف: " . $e->getMessage();
    }
} else {
    $_SESSION['error'] = "معرف مستخدم غير صالح.";
}

header("Location: index.php");
exit();