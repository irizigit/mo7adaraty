<?php
session_start();
require_once '../../config/db_connexion.php';

if (!isset($_SESSION['user_id'])) {
    die("غير مصرح لك بالدخول.");
}

$student_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['schedule_file'])) {
    
    $division_id = filter_input(INPUT_POST, 'division_id', FILTER_VALIDATE_INT);
    $class_id = filter_input(INPUT_POST, 'class_id', FILTER_VALIDATE_INT);
    $group_id = filter_input(INPUT_POST, 'group_id', FILTER_VALIDATE_INT);
    $update_reason = trim($_POST['update_reason'] ?? '');
    
    if (!$division_id || !$class_id || !$group_id || empty($update_reason)) {
        $_SESSION['error'] = "يرجى تعبئة جميع الحقول المطلوبة بما فيها سبب الرفع.";
        header("Location: index.php");
        exit();
    }

    $file = $_FILES['schedule_file'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['error'] = "حدث خطأ أثناء رفع الملف.";
        header("Location: index.php");
        exit();
    }

    $max_size = 5 * 1024 * 1024;
    if ($file['size'] > $max_size) {
        $_SESSION['error'] = "حجم الملف يتجاوز الحد المسموح به (5 ميجابايت).";
        header("Location: index.php");
        exit();
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg', 'application/pdf'];
    if (!in_array($mime_type, $allowed_mimes)) {
        $_SESSION['error'] = "نوع الملف غير مدعوم. المرجو رفع صورة أو PDF فقط.";
        header("Location: index.php");
        exit();
    }

    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $unique_filename = 'schedule_' . $student_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $file_ext;
    
    $upload_dir = '../../uploads/schedules/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $destination = $upload_dir . $unique_filename;

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO schedules (student_id, division_id, class_id, group_id, file_path, file_type, update_reason, status) VALUES (:student_id, :division_id, :class_id, :group_id, :file_path, :file_type, :update_reason, 'pending')");
            
            $relative_path = 'uploads/schedules/' . $unique_filename; 
            
            $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
            $stmt->bindParam(':division_id', $division_id, PDO::PARAM_INT);
            $stmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
            $stmt->bindParam(':group_id', $group_id, PDO::PARAM_INT);
            $stmt->bindParam(':file_path', $relative_path, PDO::PARAM_STR);
            $stmt->bindParam(':file_type', $mime_type, PDO::PARAM_STR);
            $stmt->bindParam(':update_reason', $update_reason, PDO::PARAM_STR);
            
            $stmt->execute();
            
            $_SESSION['success'] = "تم رفع استعمال الزمن بنجاح وهو الآن قيد المراجعة من طرف الإدارة.";
        } catch (PDOException $e) {
            unlink($destination);
            $_SESSION['error'] = "حدث خطأ في قاعدة البيانات: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "فشل في نقل الملف إلى الخادم.";
    }
} else {
    $_SESSION['error'] = "طلب غير صالح.";
}

header("Location: index.php");
exit();