<?php
session_start();
require_once '../../config/db_connexion.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $group_name = trim($_POST['group_name'] ?? '');
    $group_image = 'default_group.png';

    if (empty($group_name)) {
        $_SESSION['error'] = "اسم الفوج مطلوب.";
        header("Location: index.php");
        exit();
    }

    try {
        if (isset($_FILES['group_image']) && $_FILES['group_image']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml', 'image/webp'];
            $file_type = mime_content_type($_FILES['group_image']['tmp_name']);
            
            if (in_array($file_type, $allowed_types)) {
                $upload_dir = '../../uploads/groups/';
                if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }
                
                $extension = pathinfo($_FILES['group_image']['name'], PATHINFO_EXTENSION);
                $file_name = 'grp_' . uniqid() . '.' . $extension;
                
                if (move_uploaded_file($_FILES['group_image']['tmp_name'], $upload_dir . $file_name)) {
                    $group_image = $file_name;
                }
            } else {
                $_SESSION['error'] = "نوع الصورة غير مدعوم.";
                header("Location: index.php");
                exit();
            }
        }

        $stmt = $pdo->prepare("INSERT INTO `groups` (group_name, group_image) VALUES (?, ?)");
        $stmt->execute([$group_name, $group_image]);

        $_SESSION['msg'] = "تم إضافة الفوج بنجاح.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "خطأ في قاعدة البيانات: " . $e->getMessage();
    }
    header("Location: index.php");
    exit();
}