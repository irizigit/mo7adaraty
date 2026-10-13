<?php
session_start();
require_once '../../../config/db_connexion.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../../login.php"); 
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // المسار الصحيح: نرجع خطوتين للوراء (من global_icons ثم design_settings) لنصل إلى مجلد admin
    $upload_dir = '../../assets/icons/';
    
    // إنشاء المجلد إذا لم يكن موجوداً
    if (!is_dir($upload_dir)) { 
        mkdir($upload_dir, 0777, true); 
    }

    $success_count = 0;
    // تحديد أن الإمتداد المسموح به هو png فقط
    $allowed_extensions = ['png'];

    foreach ($_FILES as $asset_key => $file) {
        if ($file['error'] === UPLOAD_ERR_OK && !empty($file['name'])) {
            $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (in_array($file_extension, $allowed_extensions)) {
                $new_file_name = $asset_key . '.png';
                $destination = $upload_dir . $new_file_name;

                // رفع الملف إلى المسار الصحيح
                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    
                    // مسار قاعدة البيانات يجب أن يبدأ بـ admin/
                    $db_path = 'admin/assets/icons/' . $new_file_name;
                    
                    $stmt = $pdo->prepare("UPDATE design_assets SET file_path = ? WHERE asset_key = ? AND page_section = 'global_icons'");
                    if ($stmt->execute([$db_path, $asset_key])) { 
                        $success_count++; 
                    }
                }
            } else {
                header("Location: index.php?error=يرجى رفع صور بصيغة PNG فقط لضمان توحيد التصميم.");
                exit();
            }
        }
    }
    header("Location: index.php?msg=success&count=" . $success_count);
    exit();
} else {
    header("Location: index.php");
    exit();
}