<?php
session_start();
require_once '../../config/db_connexion.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $division_id = filter_input(INPUT_POST, 'division_id', FILTER_VALIDATE_INT);

    if ($division_id) {
        try {
            // جلب اسم الصورة قبل الحذف لحذفها من السيرفر
            $stmt_img = $pdo->prepare("SELECT division_image FROM divisions WHERE division_id = ?");
            $stmt_img->execute([$division_id]);
            $image_name = $stmt_img->fetchColumn();

            // تنفيذ الحذف (بفضل ON DELETE CASCADE في قاعدة البيانات سيتم حذف الفصول المرتبطة)
            $stmt = $pdo->prepare("DELETE FROM divisions WHERE division_id = ?");
            $stmt->execute([$division_id]);

            // حذف ملف الصورة من السيرفر إذا وجد
            if ($image_name && file_exists('../../uploads/divisions/' . $image_name)) {
                unlink('../../uploads/divisions/' . $image_name);
            }

            $_SESSION['msg'] = "تم حذف الشعبة وجميع البيانات المرتبطة بها بنجاح.";
        } catch (PDOException $e) {
            $_SESSION['error'] = "حدث خطأ أثناء الحذف: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "معرف الشعبة غير صالح.";
    }
}

header("Location: index.php");
exit();