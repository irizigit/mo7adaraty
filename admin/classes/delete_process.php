<?php
session_start();
require_once '../../config/db_connexion.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class_id = filter_input(INPUT_POST, 'class_id', FILTER_VALIDATE_INT);

    if ($class_id) {
        try {
            $stmt_img = $pdo->prepare("SELECT class_image FROM classes WHERE class_id = ?");
            $stmt_img->execute([$class_id]);
            $image_name = $stmt_img->fetchColumn();

            $stmt = $pdo->prepare("DELETE FROM classes WHERE class_id = ?");
            $stmt->execute([$class_id]);

            if ($image_name && file_exists('../../uploads/classes/' . $image_name) && $image_name !== 'default_class.png') {
                unlink('../../uploads/classes/' . $image_name);
            }

            $_SESSION['msg'] = "تم حذف الفصل وجميع البيانات المرتبطة به بنجاح.";
        } catch (PDOException $e) {
            $_SESSION['error'] = "حدث خطأ أثناء الحذف: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "معرف الفصل غير صالح.";
    }
}

header("Location: index.php");
exit();