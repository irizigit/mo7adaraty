<?php
session_start();
require_once '../../config/db_connexion.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $group_id = filter_input(INPUT_POST, 'group_id', FILTER_VALIDATE_INT);
    $group_name = trim($_POST['group_name'] ?? '');

    if (!$group_id || empty($group_name)) {
        $_SESSION['error'] = "بيانات غير صالحة.";
        header("Location: index.php");
        exit();
    }

    try {
        $update_query = "UPDATE `groups` SET group_name = ?";
        $params = [$group_name];

        if (isset($_FILES['group_image']) && $_FILES['group_image']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml', 'image/webp'];
            $file_type = mime_content_type($_FILES['group_image']['tmp_name']);
            
            if (in_array($file_type, $allowed_types)) {
                $stmt_old = $pdo->prepare("SELECT group_image FROM `groups` WHERE group_id = ?");
                $stmt_old->execute([$group_id]);
                $old_image = $stmt_old->fetchColumn();

                if ($old_image && file_exists('../../uploads/groups/' . $old_image) && $old_image !== 'default_group.png') {
                    unlink('../../uploads/groups/' . $old_image);
                }

                $upload_dir = '../../uploads/groups/';
                if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }
                
                $extension = pathinfo($_FILES['group_image']['name'], PATHINFO_EXTENSION);
                $file_name = 'grp_' . uniqid() . '.' . $extension;
                
                if (move_uploaded_file($_FILES['group_image']['tmp_name'], $upload_dir . $file_name)) {
                    $update_query .= ", group_image = ?";
                    $params[] = $file_name;
                }
            } else {
                $_SESSION['error'] = "نوع الصورة غير مدعوم.";
                header("Location: edit.php?id=" . $group_id);
                exit();
            }
        }

        $update_query .= " WHERE group_id = ?";
        $params[] = $group_id;

        $stmt = $pdo->prepare($update_query);
        $stmt->execute($params);

        $_SESSION['msg'] = "تم تحديث الفوج بنجاح.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "خطأ في قاعدة البيانات: " . $e->getMessage();
    }
    header("Location: index.php");
    exit();
}