<?php
session_start();
require_once '../../config/db_connexion.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $division_name = trim($_POST['division_name'] ?? '');
    $division_image = null;

    if (empty($division_name)) {
        $_SESSION['error'] = "اسم الشعبة مطلوب.";
        header("Location: index.php");
        exit();
    }

    try {
        // معالجة رفع الصورة
        if (isset($_FILES['division_image']) && $_FILES['division_image']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml', 'image/webp'];
            $file_type = mime_content_type($_FILES['division_image']['tmp_name']);
            
            if (in_array($file_type, $allowed_types)) {
                $upload_dir = '../../uploads/divisions/';
                if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }
                
                $extension = pathinfo($_FILES['division_image']['name'], PATHINFO_EXTENSION);
                $file_name = 'div_' . uniqid() . '.' . $extension;
                $target_path = $upload_dir . $file_name;

                if (move_uploaded_file($_FILES['division_image']['tmp_name'], $target_path)) {
                    $division_image = $file_name;
                }
            } else {
                $_SESSION['error'] = "نوع الصورة غير مدعوم.";
                header("Location: index.php");
                exit();
            }
        }

        $stmt = $pdo->prepare("INSERT INTO divisions (division_name, division_image) VALUES (?, ?)");
        $stmt->execute([$division_name, $division_image]);

        $_SESSION['msg'] = "تم إضافة الشعبة بنجاح.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "خطأ في قاعدة البيانات: " . $e->getMessage();
    }

    header("Location: index.php");
    exit();
} else {
    header("Location: index.php");
    exit();
}