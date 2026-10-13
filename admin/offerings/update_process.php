<?php
session_start();
require_once '../../config/db_connexion.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $offering_id = filter_input(INPUT_POST, 'offering_id', FILTER_VALIDATE_INT);
    $academic_year_id = filter_input(INPUT_POST, 'academic_year_id', FILTER_VALIDATE_INT);
    $division_id = filter_input(INPUT_POST, 'division_id', FILTER_VALIDATE_INT);
    $class_id = filter_input(INPUT_POST, 'class_id', FILTER_VALIDATE_INT);
    $track_id = filter_input(INPUT_POST, 'track_id', FILTER_VALIDATE_INT) ?: null;
    $group_id = filter_input(INPUT_POST, 'group_id', FILTER_VALIDATE_INT) ?: null;
    $status = $_POST['status'] === 'inactive' ? 'inactive' : 'active';

    if (!$offering_id || !$academic_year_id || !$division_id || !$class_id) {
        $_SESSION['error'] = "البيانات الأساسية مطلوبة.";
        header("Location: index.php");
        exit();
    }

    try {
        // التحقق من عدم التكرار مع مقرر آخر موجود
        $check_query = "SELECT offering_id FROM course_offerings WHERE academic_year_id = ? AND division_id = ? AND class_id = ? AND offering_id != ?";
        $check_params = [$academic_year_id, $division_id, $class_id, $offering_id];
        
        if ($track_id) { $check_query .= " AND track_id = ?"; $check_params[] = $track_id; } 
        else { $check_query .= " AND track_id IS NULL"; }
        
        if ($group_id) { $check_query .= " AND group_id = ?"; $check_params[] = $group_id; } 
        else { $check_query .= " AND group_id IS NULL"; }

        $stmt_check = $pdo->prepare($check_query);
        $stmt_check->execute($check_params);

        if ($stmt_check->fetch()) {
            $_SESSION['error'] = "هذا التعديل يطابق مقرراً آخر موجوداً بالفعل.";
            header("Location: edit.php?id=" . $offering_id);
            exit();
        }

        // التحديث
        $stmt = $pdo->prepare("UPDATE course_offerings SET academic_year_id=?, division_id=?, class_id=?, track_id=?, group_id=?, status=? WHERE offering_id=?");
        $stmt->execute([$academic_year_id, $division_id, $class_id, $track_id, $group_id, $status, $offering_id]);

        $_SESSION['msg'] = "تم تحديث ارتباطات المقرر بنجاح.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "خطأ في قاعدة البيانات: " . $e->getMessage();
    }
    header("Location: index.php");
    exit();
}