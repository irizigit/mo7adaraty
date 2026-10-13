<?php
session_start();
require_once '../../config/db_connexion.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    if ($id) {
        try {
            // حماية صارمة: منع حذف العام "الحالي" لضمان عدم انهيار النظام الزمني للمنصة
            $stmt_check = $pdo->prepare("SELECT is_current FROM academic_years WHERE id = ?");
            $stmt_check->execute([$id]);
            $is_current = $stmt_check->fetchColumn();

            if ($is_current == 1) {
                $_SESSION['error'] = "إجراء مرفوض: لا يمكن حذف العام الدراسي الحالي. يرجى تعيين عام آخر كعام حالي أولاً.";
            } else {
                $stmt = $pdo->prepare("DELETE FROM academic_years WHERE id = ?");
                $stmt->execute([$id]);
                $_SESSION['msg'] = "تم حذف العام الدراسي بنجاح.";
            }
            
        } catch (PDOException $e) {
            $_SESSION['error'] = "حدث خطأ أثناء الحذف: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "معرف غير صالح.";
    }
}
header("Location: index.php");
exit();