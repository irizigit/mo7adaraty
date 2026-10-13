<?php
session_start();
require_once '../../config/db_connexion.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // تم تصحيح الثابت هنا إلى INPUT_POST
    $division_id = filter_input(INPUT_POST, 'division_id', FILTER_VALIDATE_INT);
    $division_name = trim($_POST['division_name'] ?? '');

    if (!$division_id || empty($division_name)) {
        $_SESSION['error'] = "بيانات غير صالحة.";
        header("Location: index.php");
        exit();
    }

    try {
        $update_query = "UPDATE divisions SET division_name = ?";
        $params = [$division_name];

        // معالجة رفع الصورة إذا تم إرفاق صورة جديدة
        if (isset($_FILES['division_image']) && $_FILES['division_image']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml', 'image/webp'];
            $file_type = mime_content_type($_FILES['division_image']['tmp_name']);
            
            if (in_array($file_type, $allowed_types)) {
                // جلب الصورة القديمة لحذفها
                $stmt_old = $pdo->prepare("SELECT division_image FROM divisions WHERE division_id = ?");
                $stmt_old->execute([$division_id]);
                $old_image = $stmt_old->fetchColumn();

                if ($old_image && file_exists('../../uploads/divisions/' . $old_image)) {
                    unlink('../../uploads/divisions/' . $old_image);
                }

                $upload_dir = '../../uploads/divisions/';
                if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }
                
                $extension = pathinfo($_FILES['division_image']['name'], PATHINFO_EXTENSION);
                $file_name = 'div_' . uniqid() . '.' . $extension;
                
                if (move_uploaded_file($_FILES['division_image']['tmp_name'], $upload_dir . $file_name)) {
                    $update_query .= ", division_image = ?";
                    $params[] = $file_name;
                }
            } else {
                $_SESSION['error'] = "نوع الصورة غير مدعوم.";
                header("Location: edit.php?id=" . $division_id);
                exit();
            }
        }

        $update_query .= " WHERE division_id = ?";
        $params[] = $division_id;

        $stmt = $pdo->prepare($update_query);
        $stmt->execute($params);

        $_SESSION['msg'] = "تم تحديث الشعبة بنجاح.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "خطأ في قاعدة البيانات: " . $e->getMessage();
    }

    header("Location: index.php");
    exit();
} else {
    header("Location: index.php");
    exit();
}