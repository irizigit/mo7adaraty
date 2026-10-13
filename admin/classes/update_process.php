<?php
session_start();
require_once '../../config/db_connexion.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // تم استخدام INPUT_POST كما اتفقنا
    $class_id = filter_input(INPUT_POST, 'class_id', FILTER_VALIDATE_INT);
    $class_name = trim($_POST['class_name'] ?? '');

    if (!$class_id || empty($class_name)) {
        $_SESSION['error'] = "بيانات غير صالحة.";
        header("Location: index.php");
        exit();
    }

    try {
        $update_query = "UPDATE classes SET class_name = ?";
        $params = [$class_name];

        if (isset($_FILES['class_image']) && $_FILES['class_image']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml', 'image/webp'];
            $file_type = mime_content_type($_FILES['class_image']['tmp_name']);
            
            if (in_array($file_type, $allowed_types)) {
                $stmt_old = $pdo->prepare("SELECT class_image FROM classes WHERE class_id = ?");
                $stmt_old->execute([$class_id]);
                $old_image = $stmt_old->fetchColumn();

                if ($old_image && file_exists('../../uploads/classes/' . $old_image) && $old_image !== 'default_class.png') {
                    unlink('../../uploads/classes/' . $old_image);
                }

                $upload_dir = '../../uploads/classes/';
                if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }
                
                $extension = pathinfo($_FILES['class_image']['name'], PATHINFO_EXTENSION);
                $file_name = 'cls_' . uniqid() . '.' . $extension;
                
                if (move_uploaded_file($_FILES['class_image']['tmp_name'], $upload_dir . $file_name)) {
                    $update_query .= ", class_image = ?";
                    $params[] = $file_name;
                }
            } else {
                $_SESSION['error'] = "نوع الصورة غير مدعوم.";
                header("Location: edit.php?id=" . $class_id);
                exit();
            }
        }

        $update_query .= " WHERE class_id = ?";
        $params[] = $class_id;

        $stmt = $pdo->prepare($update_query);
        $stmt->execute($params);

        $_SESSION['msg'] = "تم تحديث الفصل بنجاح.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "خطأ في قاعدة البيانات: " . $e->getMessage();
    }

    header("Location: index.php");
    exit();
} else {
    header("Location: index.php");
    exit();
}