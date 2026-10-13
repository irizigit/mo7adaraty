<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../../config/db_connexion.php';

// حماية الصفحة والتحقق من الصلاحيات
if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') { 
    header("Location: ../../../login.php"); 
    exit(); 
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // مسار حفظ أيقونات وصور التحميل (نوحدها مع بنية includesimg)
    $upload_dir = '../../../includesimg/loader/';
    $section = 'loader';
    
    // إنشاء المجلد إذا لم يكن موجوداً
    if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }

    $success_count = 0;
    $allowed_extensions = ['png', 'jpg', 'jpeg', 'svg', 'webp'];

    try {
        $pdo->beginTransaction();

        foreach ($_FILES as $asset_key => $file) {
            if ($file['error'] === UPLOAD_ERR_OK && !empty($file['name'])) {
                $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                
                if (in_array($file_extension, $allowed_extensions)) {
                    $new_file_name = $asset_key . '.' . $file_extension;
                    $destination = $upload_dir . $new_file_name;

                    // مسح الملف القديم لعدم استهلاك مساحة السيرفر
                    $stmt_old = $pdo->prepare("SELECT file_path FROM design_assets WHERE asset_key = ? AND page_section = ?");
                    $stmt_old->execute([$asset_key, $section]);
                    $old_file = $stmt_old->fetchColumn();
                    
                    if ($old_file) {
                        $old_file_physical = '../../../' . ltrim($old_file, '/');
                        if (file_exists($old_file_physical) && basename($old_file_physical) !== $new_file_name) {
                            unlink($old_file_physical);
                        }
                    }

                    // رفع الملف الجديد وتحديث قاعدة البيانات
                    if (move_uploaded_file($file['tmp_name'], $destination)) {
                        $db_path = 'includesimg/loader/' . $new_file_name;
                        $stmt = $pdo->prepare("UPDATE design_assets SET file_path = ? WHERE asset_key = ? AND page_section = ?");
                        if ($stmt->execute([$db_path, $asset_key, $section])) { 
                            $success_count++; 
                        }
                    }
                } else {
                    throw new Exception("إحدى الملفات المرفوعة ليست بالصيغة المطلوبة (SVG, PNG, JPG, WEBP).");
                }
            }
        }

        $pdo->commit();

        if ($success_count > 0) {
            $_SESSION['msg'] = "تم تحديث ($success_count) ملف بنجاح.";
        } else {
            $_SESSION['error'] = "لم يتم تحديد أي ملفات جديدة للرفع.";
        }

    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "حدث خطأ: " . $e->getMessage();
    }

    header("Location: index.php");
    exit();

} else {
    header("Location: index.php");
    exit();
}