<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية مسار المعالجة
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // مسار مجلد الرفع - سيتم إنشاؤه تلقائياً إن لم يكن موجوداً
    $upload_dir = '../../uploads/timetables/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    if ($action === 'upload') {
        $division_id = intval($_POST['division_id']);
        $class_id = intval($_POST['class_id']);
        $group_id = intval($_POST['group_id']);
        
        if (isset($_FILES['timetable_file']) && $_FILES['timetable_file']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['timetable_file']['tmp_name'];
            $file_name = $_FILES['timetable_file']['name'];
            
            // استخراج وتأمين امتداد الملف
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_extensions = ['pdf', 'png', 'jpg', 'jpeg'];
            
            if (in_array($file_ext, $allowed_extensions)) {
                
                // هندسة اسم الملف: نجعله فريداً لتجنب التضارب واستغلال الثغرات
                $new_file_name = 'timetable_div' . $division_id . '_cls' . $class_id . '_grp' . $group_id . '_' . time() . '.' . $file_ext;
                $destination = $upload_dir . $new_file_name;
                $db_path = 'uploads/timetables/' . $new_file_name;

                if (move_uploaded_file($file_tmp, $destination)) {
                    
                    // التفكير كـ Tech Lead: هل لهذه المجموعة ملف قديم؟ إذا نعم، يجب حذفه لتوفير مساحة السيرفر.
                    $check_stmt = $conn->prepare("SELECT file_path FROM timetables WHERE division_id = ? AND class_id = ? AND group_id = ?");
                    $check_stmt->execute([$division_id, $class_id, $group_id]);
                    $existing = $check_stmt->fetch(PDO::FETCH_ASSOC);

                    if ($existing) {
                        // تنظيف السيرفر من الملف القديم
                        $old_file = '../../' . $existing['file_path'];
                        if (file_exists($old_file) && is_file($old_file)) {
                            unlink($old_file);
                        }
                        
                        // تحديث مسار الملف الجديد في القاعدة
                        $update_stmt = $conn->prepare("UPDATE timetables SET file_path = ?, updated_at = NOW() WHERE division_id = ? AND class_id = ? AND group_id = ?");
                        $update_stmt->execute([$db_path, $division_id, $class_id, $group_id]);
                        $_SESSION['message'] = "تم تحديث استعمال الزمن القديم بنجاح بالملف الجديد.";
                    } else {
                        // لا يوجد ملف قديم، إضافة سجل جديد
                        $insert_stmt = $conn->prepare("INSERT INTO timetables (division_id, class_id, group_id, file_path) VALUES (?, ?, ?, ?)");
                        $insert_stmt->execute([$division_id, $class_id, $group_id, $db_path]);
                        $_SESSION['message'] = "تم رفع واعتماد استعمال الزمن بنجاح.";
                    }
                } else {
                    $_SESSION['error'] = "عذراً، فشل النظام في نقل الملف المرفوع.";
                }
            } else {
                $_SESSION['error'] = "خطأ أمني: نوع الملف غير مسموح به. يرجى رفع ملفات بصيغة PDF أو صور فقط.";
            }
        } else {
            $_SESSION['error'] = "حدث خطأ أثناء الرفع، تأكد من اختيار ملف سليم.";
        }
    } 
    
    elseif ($action === 'delete') {
        $id = intval($_POST['id']);
        
        // جلب مسار الملف لحذفه من السيرفر قبل حذف السجل
        $get_stmt = $conn->prepare("SELECT file_path FROM timetables WHERE id = ?");
        $get_stmt->execute([$id]);
        $timetable = $get_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($timetable) {
            $file_to_delete = '../../' . $timetable['file_path'];
            if (file_exists($file_to_delete) && is_file($file_to_delete)) {
                unlink($file_to_delete);
            }
            
            $del_stmt = $conn->prepare("DELETE FROM timetables WHERE id = ?");
            $del_stmt->execute([$id]);
            $_SESSION['message'] = "تم حذف استعمال الزمن نهائياً من السيرفر وقاعدة البيانات.";
        } else {
            $_SESSION['error'] = "هذا السجل غير موجود أو تم حذفه مسبقاً.";
        }
    }

    // إعادة التوجيه لصفحة العرض بعد التنفيذ
    header('Location: index.php');
    exit();
} else {
    // منع الوصول المباشر للملف
    header('Location: index.php');
    exit();
}