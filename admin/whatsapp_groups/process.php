<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../config/db_connexion.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'save_offering') {
            // معالجة روابط المقررات المتاحة
            $offering_id = filter_input(INPUT_POST, 'offering_id', FILTER_VALIDATE_INT);
            $link_id = filter_input(INPUT_POST, 'link_id', FILTER_VALIDATE_INT) ?: null;
            $group_name = trim($_POST['group_name'] ?? '');
            $group_link = trim($_POST['group_link'] ?? '');

            if (!$offering_id || empty($group_name) || empty($group_link)) {
                throw new Exception("البيانات غير مكتملة.");
            }

            if ($link_id) {
                // تحديث رابط موجود
                $stmt = $pdo->prepare("UPDATE whatsapp_groups SET group_name = ?, group_link = ? WHERE link_id = ?");
                $stmt->execute([$group_name, $group_link, $link_id]);
                $_SESSION['msg'] = "تم تحديث رابط المقرر بنجاح.";
            } else {
                // إدخال رابط جديد للمقرر
                $stmt = $pdo->prepare("INSERT INTO whatsapp_groups (group_name, group_link, offering_id) VALUES (?, ?, ?)");
                $stmt->execute([$group_name, $group_link, $offering_id]);
                $_SESSION['msg'] = "تم إضافة رابط المقرر بنجاح.";
            }

        } elseif ($action === 'save_independent') {
            // معالجة المجموعات المستقلة والعامة
            $group_name = trim($_POST['group_name'] ?? '');
            $group_link = trim($_POST['group_link'] ?? '');
            $academic_year_id = filter_input(INPUT_POST, 'academic_year_id', FILTER_VALIDATE_INT);
            $division_id = filter_input(INPUT_POST, 'division_id', FILTER_VALIDATE_INT) ?: null;

            if (empty($group_name) || empty($group_link) || !$academic_year_id) {
                throw new Exception("الرجاء إدخال اسم المجموعة والرابط والموسم.");
            }

            $stmt = $pdo->prepare("INSERT INTO whatsapp_groups (group_name, group_link, academic_year_id, division_id, offering_id) VALUES (?, ?, ?, ?, NULL)");
            $stmt->execute([$group_name, $group_link, $academic_year_id, $division_id]);
            $_SESSION['msg'] = "تمت إضافة المجموعة بنجاح.";

        } elseif ($action === 'delete') {
            // حذف أي رابط بناءً على link_id
            $link_id = filter_input(INPUT_POST, 'link_id', FILTER_VALIDATE_INT);
            if ($link_id) {
                $stmt = $pdo->prepare("DELETE FROM whatsapp_groups WHERE link_id = ?");
                $stmt->execute([$link_id]);
                $_SESSION['msg'] = "تم حذف الرابط بنجاح.";
            } else {
                throw new Exception("معرف المجموعة غير صالح.");
            }
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "خطأ: " . $e->getMessage();
    }

    header("Location: index.php");
    exit();
}