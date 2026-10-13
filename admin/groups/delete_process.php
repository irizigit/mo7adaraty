<?php
session_start();
require_once '../../config/db_connexion.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $group_id = filter_input(INPUT_POST, 'group_id', FILTER_VALIDATE_INT);

    if ($group_id) {
        try {
            $stmt_img = $pdo->prepare("SELECT group_image FROM `groups` WHERE group_id = ?");
            $stmt_img->execute([$group_id]);
            $image_name = $stmt_img->fetchColumn();

            $stmt = $pdo->prepare("DELETE FROM `groups` WHERE group_id = ?");
            $stmt->execute([$group_id]);

            if ($image_name && file_exists('../../uploads/groups/' . $image_name) && $image_name !== 'default_group.png') {
                unlink('../../uploads/groups/' . $image_name);
            }

            $_SESSION['msg'] = "تم حذف الفوج بنجاح.";
        } catch (PDOException $e) {
            $_SESSION['error'] = "حدث خطأ أثناء الحذف: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "معرف الفوج غير صالح.";
    }
}
header("Location: index.php");
exit();