<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية صارمة للمسار
if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $division_id = $_POST['division_id'] ?? null;
    $class_id = $_POST['class_id'] ?? null;
    $group_id = $_POST['group_id'] ?? null;
    
    if (!$division_id || !$class_id || !$group_id || empty($_FILES['timetable_file']['name'])) {
        $_SESSION['error'] = "الرجاء تعبئة جميع الحقول واختيار ملف.";
        header("Location: index.php");
        exit();
    }

    $file = $_FILES['timetable_file'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg', 'application/pdf'];
    
    if (!in_array($file['type'], $allowed_types)) {
        $_SESSION['error'] = "نوع الملف غير مدعوم. المرجو رفع صورة أو PDF.";
        header("Location: index.php");
        exit();
    }

    // إنشاء مجلد الرفع في حال لم يكن موجوداً
    $upload_dir = '../../uploads/timetables/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_filename = 'timetable_' . $division_id . '_' . $class_id . '_' . $group_id . '_' . time() . '.' . $file_ext;
    $destination = $upload_dir . $new_filename;

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        try {
            // التحقق وحذف الملف القديم من السيرفر إن وجد لتوفير المساحة
            $check_stmt = $pdo->prepare("SELECT file_path FROM timetables WHERE division_id = ? AND class_id = ? AND group_id = ?");
            $check_stmt->execute([$division_id, $class_id, $group_id]);
            $existing = $check_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing && file_exists($upload_dir . $existing['file_path'])) {
                unlink($upload_dir . $existing['file_path']);
            }

            // الإدخال أو التحديث بفضل تقنية ON DUPLICATE KEY
            $stmt = $pdo->prepare("
                INSERT INTO timetables (division_id, class_id, group_id, file_path) 
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                file_path = VALUES(file_path), updated_at = CURRENT_TIMESTAMP
            ");
            $stmt->execute([$division_id, $class_id, $group_id, $new_filename]);
            
            $_SESSION['msg'] = "تم رفع استعمال الزمن بنجاح.";
        } catch (PDOException $e) {
            $_SESSION['error'] = "خطأ في قاعدة البيانات: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "حدث خطأ غير متوقع أثناء نقل الملف إلى السيرفر.";
    }
}
header("Location: index.php");
exit();