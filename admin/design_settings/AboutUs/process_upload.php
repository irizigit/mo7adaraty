<?php
session_start();
require_once '../../../config/db_connexion.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $page_section = 'AboutUs';
    $icons_list = [
        'mission_check' => 'أيقونة الصح في المهام',
        'feature_search' => 'أيقونة ميزة البحث',
        'feature_upload' => 'أيقونة ميزة الرفع',
        'feature_users' => 'أيقونة ميزة المستخدمين',
        'feature_comments' => 'أيقونة ميزة التعليقات',
        'story_quote' => 'أيقونة الاقتباس في القصة',
        'team1_linkedin' => 'عضو الفريق 1 - لينكد إن',
        'team1_github' => 'عضو الفريق 1 - جيت هاب',
        'team2_linkedin' => 'عضو الفريق 2 - لينكد إن',
        'team2_twitter' => 'عضو الفريق 2 - تويتر',
        'team3_linkedin' => 'عضو الفريق 3 - لينكد إن',
        'team3_facebook' => 'عضو الفريق 3 - فيسبوك',
        'cta_signup' => 'أيقونة زر إنشاء حساب',
        'cta_contact' => 'أيقونة زر اتصل بنا'
    ];
    
    $uploadFolder = '../../../uploads/design/AboutUs/';
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
                    $dbPath = 'uploads/design/AboutUs/' . $newFileName;
                    
                    try {
                        // التعديل هنا: استخدام 'image' بدلاً من 'image/png' لتتطابق مع قاعدة بياناتك
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