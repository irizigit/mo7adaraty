<?php
session_start();
require_once '../../config/db_connexion.php';

// 1. التحقق من تسجيل الدخول وأن الطلب من نوع POST
if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? 'create';
$post_id = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
$is_update = ($action === 'update' && !empty($post_id));

// دالة مساعدة لتوجيه الأخطاء وإعادة المستخدم إلى المكان الصحيح
$redirect_error = function($error_code) use ($is_update, $post_id) {
    if ($is_update) {
        header("Location: index.php?edit_post=" . $post_id . "&error=" . $error_code);
    } else {
        header("Location: index.php?error=" . $error_code);
    }
    exit();
};

// 2. استقبال وتنقية المدخلات
$post_text = trim($_POST['post_text'] ?? '');
$background_style = trim($_POST['background_style'] ?? 'none');
$post_image = $_FILES['post_image'] ?? null;
$existing_image = trim($_POST['existing_image'] ?? '');

// التحقق من صلاحية نمط الخلفية
$valid_styles = ['none', 'blue', 'red', 'green', 'yellow', 'purple'];
if (!in_array($background_style, $valid_styles)) {
    $background_style = 'none';
}

// 3. التحقق من المنشور الحالي في حالة التعديل
$current_post = null;
if ($is_update) {
    $stmt_check = $pdo->prepare("SELECT * FROM posts WHERE post_id = ? AND user_id = ? AND status = 'pending'");
    $stmt_check->execute([$post_id, $user_id]);
    $current_post = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if (!$current_post) {
        $_SESSION['post_action_status'] = [
            'type' => 'error',
            'message' => 'المنشور غير موجود أو تمت مراجعته بالفعل.'
        ];
        header("Location: my_pending_posts.php");
        exit();
    }
}

// 4. معالجة وتدقيق الصورة
$image_path = null;
$has_new_image = isset($post_image) && $post_image['error'] === UPLOAD_ERR_OK;

if ($has_new_image) {
    if ($background_style !== 'none') {
        $redirect_error('background_with_image_not_allowed');
    }

    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5 ميغابايت
    $upload_dir = '../../uploads/posts/';

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    if (!in_array($post_image['type'], $allowed_types) || $post_image['size'] > $max_size) {
        $redirect_error('invalid_image');
    }

    $file_extension = strtolower(pathinfo($post_image['name'], PATHINFO_EXTENSION));
    $image_name = 'post_' . uniqid() . '.' . $file_extension;
    $relative_path = 'uploads/posts/' . $image_name;

    if (!move_uploaded_file($post_image['tmp_name'], '../../' . $relative_path)) {
        $redirect_error('upload_failed');
    }

    $image_path = $relative_path;

    // حذف الصورة القديمة عند رفع صورة جديدة أثناء التعديل
    if ($is_update && !empty($current_post['image_path']) && file_exists('../../' . $current_post['image_path'])) {
        unlink('../../' . $current_post['image_path']);
    }

} elseif ($is_update) {
    // إذا لم يرفع صورة جديدة واحتفظ بالصورة السابقة
    if (!empty($existing_image) && $existing_image === $current_post['image_path']) {
        $image_path = $current_post['image_path'];
    } else {
        // قام المستخدم بحذف الصورة السابقة من واجهة التعديل
        if (!empty($current_post['image_path']) && file_exists('../../' . $current_post['image_path'])) {
            unlink('../../' . $current_post['image_path']);
        }
        $image_path = null;
    }
}

// 5. التحقق من الشروط المنطقية للنشر
if (empty($post_text) && empty($image_path)) {
    $redirect_error('empty_post');
}

if (!empty($image_path) && $background_style !== 'none') {
    $redirect_error('background_with_image_not_allowed');
}

if (empty($post_text) && $background_style !== 'none') {
    $redirect_error('background_without_text');
}

// 6. تنفيذ الحفظ أو التعديل في قاعدة البيانات
try {
    if ($is_update) {
        // تحديث المنشور المعلق
        $stmt_update = $pdo->prepare("
            UPDATE posts 
            SET post_text = ?, image_path = ?, background_style = ? 
            WHERE post_id = ? AND user_id = ? AND status = 'pending'
        ");
        $stmt_update->execute([
            $post_text ?: null,
            $image_path,
            $background_style,
            $post_id,
            $user_id
        ]);

        $_SESSION['post_action_status'] = [
            'type' => 'success',
            'message' => 'تم حفظ تعديلات المنشور بنجاح، ويبقى قيد المراجعة.'
        ];
        header("Location: my_pending_posts.php");
        exit();

    } else {
        // إضافة منشور جديد قيد المراجعة
        $stmt_insert = $pdo->prepare("
            INSERT INTO posts (user_id, post_text, image_path, background_style, status)
            VALUES (?, ?, ?, ?, 'pending')
        ");
        $stmt_insert->execute([$user_id, $post_text ?: null, $image_path, $background_style]);

        header("Location: index.php?status=post_submitted");
        exit();
    }

} catch (PDOException $e) {
    if ($is_update) {
        $_SESSION['post_action_status'] = [
            'type' => 'error',
            'message' => 'حدث خطأ في قاعدة البيانات: ' . $e->getMessage()
        ];
        header("Location: my_pending_posts.php");
    } else {
        header("Location: index.php?error=db_error&message=" . urlencode($e->getMessage()));
    }
    exit();
}