<?php
session_start();
require_once '../../../config/db_connexion.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $page_section = 'PrivacyPolicy';
    $icons_list = [
        'icon_shield' => 'أيقونة جمع المعلومات (درع)',
        'icon_database' => 'أيقونة نوع البيانات (قاعدة بيانات)',
        'icon_cookie' => 'أيقونة ملفات الارتباط (Cookie)',
        'icon_cogs' => 'أيقونة استخدام المعلومات (إعدادات)',
        'icon_share' => 'أيقونة مشاركة المعلومات',
        'icon_lock' => 'أيقونة حماية البيانات (قفل)',
        'icon_user_shield' => 'أيقونة حماية الحسابات',
        'icon_server' => 'أيقونة حماية الخوادم',
        'icon_info' => 'أيقونة ملاحظة (Info)',
        'icon_check' => 'أيقونة علامة الصح (في الإحصائيات)',
        'icon_envelope' => 'أيقونة البريد (زر المراسلة)'
    ];
    
    $uploadFolder = '../../../uploads/design/PrivacyPolicy/';
    if (!is_dir($uploadFolder)) {
        mkdir($uploadFolder, 0777, true);
    }
    
    foreach ($icons_list as $key => $description) {
        if (isset($_FILES[$key]) && $_FILES[$key]['error'] === UPLOAD_ERR_OK) {
            
            $file = $_FILES[$key];
            $fileName = $file['name'];
            $fileTmpName = $file['tmp_name'];
            
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $mimeType = mime_content_type($fileTmpName);
            
            if ($fileExt === 'png' && $mimeType === 'image/png') {
                $newFileName = $key . '.png';
                $destination = $uploadFolder . $newFileName;
                
                if (move_uploaded_file($fileTmpName, $destination)) {
                    $dbPath = 'uploads/design/PrivacyPolicy/' . $newFileName;
                    
                    try {
                        $sql = "INSERT INTO design_assets (page_section, asset_key, asset_type, file_path, description, updated_at) 
                                VALUES (?, ?, 'image', ?, ?, NOW()) 
                                ON DUPLICATE KEY UPDATE file_path = ?, description = ?, updated_at = NOW()";
                                
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$page_section, $key, $dbPath, $description, $dbPath, $description]);
                        
                    } catch (PDOException $e) {
                        die("<div style='background:#ffdddd; color:red; padding:20px; font-family:tahoma; direction:rtl; text-align:right; border:2px solid darkred; border-radius:8px; max-width:800px; margin:50px auto;'>
                                <h3 style='margin-top:0;'>❌ فشل الحفظ في قاعدة البيانات</h3>
                                <p>الخطأ التقني:</p>
                                <code style='direction:ltr; text-align:left; display:block; background:#fff; padding:15px; border:1px solid #ccc; font-size:16px;'>".$e->getMessage()."</code>
                             </div>");
                    }
                }
            }
        }
    }
    
    header("Location: index.php?msg=success");
    exit();
    
} else {
    header("Location: index.php");
    exit();
}