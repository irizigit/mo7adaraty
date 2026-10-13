<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../config/db_connexion.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../login.php");
    exit();
}

$user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$new_password = $_POST['password'] ?? ''; // الحقل الجديد
$role = ($_POST['role'] === 'admin') ? 'admin' : 'student';
$gender = in_array($_POST['gender'], ['male', 'female', 'unspecified']) ? $_POST['gender'] : 'unspecified';

if (!$user_id || empty($username) || empty($email)) {
    $_SESSION['error'] = "الاسم والبريد الإلكتروني مطلوبان.";
    header("Location: edit.php?id=" . $user_id);
    exit();
}

try {
    $pdo->beginTransaction();

    // 1. تحديث البيانات الأساسية (باستثناء الباسوورد)
    $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, role = ?, gender = ? WHERE user_id = ?");
    $stmt->execute([$username, $email, $role, $gender, $user_id]);

    // 2. تحديث كلمة المرور فقط إذا تم إدخال كلمة مرور جديدة (وتشفيرها فوراً)
    if (!empty($new_password)) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt_pass = $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
        $stmt_pass->execute([$hashed_password, $user_id]);
    }

    // 3. معالجة رفع الصورة الشخصية
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $file_extension = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
        
        if (in_array($file_extension, $allowed_extensions)) {
            $stmt_old_pic = $pdo->prepare("SELECT profile_picture FROM users WHERE user_id = ?");
            $stmt_old_pic->execute([$user_id]);
            $old_picture = $stmt_old_pic->fetchColumn();

            $upload_dir = '../../uploads/profile_pictures/';
            if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }
            
            $new_file_name = 'user_' . $user_id . '_' . uniqid() . '.' . $file_extension;
            $target_file = $upload_dir . $new_file_name;

            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_file)) {
                $stmt_update_pic = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE user_id = ?");
                $stmt_update_pic->execute([$new_file_name, $user_id]);

                // حذف القديمة عدا الافتراضية
                if (!empty($old_picture) && $old_picture !== 'default.png' && $old_picture !== 'default_profile.png' && file_exists($upload_dir . $old_picture)) {
                    unlink($upload_dir . $old_picture);
                }
            }
        }
    }

    $pdo->commit();
    $_SESSION['user_update_msg'] = "تم تحديث بيانات المستخدم بنجاح!";
    header("Location: edit.php?id=" . $user_id);
    exit();

} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = "حدث خطأ أثناء حفظ التعديلات: " . $e->getMessage();
    header("Location: edit.php?id=" . $user_id);
    exit();
}