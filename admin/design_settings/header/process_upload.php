<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../../config/db_connexion.php';

// التحقق من الصلاحيات
if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') { 
    header("Location: ../../../login.php"); 
    exit(); 
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $upload_dir = '../../../includesimg/header/';
    $section = 'header';
    
    if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }

    $success_count = 0;
    $allowed_extensions = ['png', 'jpg', 'jpeg', 'webp', 'svg'];

    try {
        $db = isset($pdo) ? $pdo : (isset($conn) ? $conn : null);
        if (!$db) throw new Exception("فشل الاتصال بقاعدة البيانات.");

        $db->beginTransaction();

        foreach ($_FILES as $asset_key => $file) {
            if ($file['error'] === UPLOAD_ERR_OK && !empty($file['name'])) {
                $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                
                if (in_array($file_extension, $allowed_extensions)) {
                    $new_file_name = $asset_key . '.' . $file_extension;
                    $destination = $upload_dir . $new_file_name;

                    // مسح الملف القديم
                    $stmt_old = $db->prepare("SELECT file_path FROM design_assets WHERE asset_key = ? AND page_section = ?");
                    $stmt_old->execute([$asset_key, $section]);
                    $old_file = $stmt_old->fetchColumn();
                    
                    if ($old_file) {
                        $old_file_physical = '../../../' . ltrim($old_file, '/');
                        if (file_exists($old_file_physical) && basename($old_file_physical) !== $new_file_name) {
                            unlink($old_file_physical);
                        }
                    }

                    if (move_uploaded_file($file['tmp_name'], $destination)) {
                        $db_path = 'includesimg/header/' . $new_file_name;
                        
                        // التعديل: اعتبار جميع مفاتيح الشعارات كصورة (image)
                        $asset_type = (strpos($asset_key, 'header_logo') !== false) ? 'image' : 'icon';
                        
                        $stmt_check = $db->prepare("SELECT COUNT(*) FROM design_assets WHERE asset_key = ? AND page_section = ?");
                        $stmt_check->execute([$asset_key, $section]);
                        
                        if ($stmt_check->fetchColumn() > 0) {
                            $stmt = $db->prepare("UPDATE design_assets SET file_path = ?, asset_type = ? WHERE asset_key = ? AND page_section = ?");
                            if ($stmt->execute([$db_path, $asset_type, $asset_key, $section])) { $success_count++; }
                        } else {
                            $stmt = $db->prepare("INSERT INTO design_assets (page_section, asset_key, asset_type, file_path, description) VALUES (?, ?, ?, ?, ?)");
                            if ($stmt->execute([$section, $asset_key, $asset_type, $db_path, 'مورد: ' . $asset_key])) { $success_count++; }
                        }
                    }
                } else {
                    throw new Exception("إحدى الملفات المرفوعة ليست بالصيغة المطلوبة (JPG, PNG, WEBP, SVG).");
                }
            }
        }

        $db->commit();

        if ($success_count > 0) {
            $_SESSION['msg'] = "تم رفع وتحديث ($success_count) مورد للهيدر بنجاح.";
        } else {
            $_SESSION['error'] = "لم يتم تحديد أي ملفات جديدة للرفع.";
        }

    } catch (Exception $e) {
        if(isset($db) && $db->inTransaction()) { $db->rollBack(); }
        $_SESSION['error'] = "حدث خطأ: " . $e->getMessage();
    }

    header("Location: index.php");
    exit();

} else {
    header("Location: index.php");
    exit();
}