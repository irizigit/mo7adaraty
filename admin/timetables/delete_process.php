<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية صارمة
if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['timetable_id'])) {
    $id = $_POST['timetable_id'];
    
    try {
        // جلب مسار الملف لحذفه من السيرفر أولاً
        $stmt = $pdo->prepare("SELECT file_path FROM timetables WHERE id = ?");
        $stmt->execute([$id]);
        $timetable = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($timetable) {
            $file_to_delete = '../../uploads/timetables/' . $timetable['file_path'];
            if (file_exists($file_to_delete)) {
                unlink($file_to_delete); // حذف الملف فيزيائياً
            }
            
            // حذف السجل من قاعدة البيانات
            $del_stmt = $pdo->prepare("DELETE FROM timetables WHERE id = ?");
            $del_stmt->execute([$id]);
            
            $_SESSION['msg'] = "تم حذف استعمال الزمن بنجاح.";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "خطأ أثناء الحذف: " . $e->getMessage();
    }
}
header("Location: index.php");
exit();