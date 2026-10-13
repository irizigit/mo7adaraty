<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../../config/db_connexion.php';

// التحقق من صلاحيات المدير
if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') { 
    header("Location: ../../../login.php"); 
    exit(); 
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // مسار حفظ الأيقونات
    $upload_dir = '../../assets/icons/';
    if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }

    $success_count = 0;

    try {
        $pdo->beginTransaction();

        foreach ($_FILES as $asset_key => $file) {
            if ($file['error'] === UPLOAD_ERR_OK && !empty($file['name'])) {
                $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                
                // قبول الصور وتحويل المسار المسجل ليكون .png دائماً (الحل الجذري)
                if (in_array($file_extension, ['png', 'jpg', 'jpeg', 'svg', 'webp'])) {
                    
                    // فرض الامتداد png في اسم الملف والمسار
                    $new_file_name = $asset_key . '.png';
                    $destination = $upload_dir . $new_file_name;

                    if (move_uploaded_file($file['tmp_name'], $destination)) {
                        $db_path = 'admin/assets/icons/' . $new_file_name;
                        
                        // تحديث المسار + نوع الأصل + القسم الصحيح
                        $stmt = $pdo->prepare("
                            UPDATE design_assets 
                            SET file_path = ?, 
                                asset_type = 'image' 
                            WHERE asset_key = ? AND page_section = 'class_lectures'
                        ");
                        
                        if ($stmt->execute([$db_path, $asset_key])) { 
                            $success_count++; 
                        }
                    }
                } else {
                    throw new Exception("تنسيق الملف غير مدعوم للأصل: " . $asset_key);
                }
            }
        }
        $pdo->commit();

        if ($success_count > 0) {
            $_SESSION['msg'] = "تم تحديث ($success_count) أصل بنجاح بصيغة PNG.";
        } else {
            $_SESSION['error'] = "لم يتم اختيار أي ملفات صالحة للرفع.";
        }

    } catch (Exception $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        $_SESSION['error'] = $e->getMessage();
    }

    header("Location: index.php");
    exit();
} else {
    header("Location: index.php");
    exit();
}