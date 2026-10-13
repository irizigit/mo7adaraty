<?php
session_start();
require_once '../../config/db_connexion.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['schedule_id'], $_POST['action'])) {
    $schedule_id = filter_input(INPUT_POST, 'schedule_id', FILTER_VALIDATE_INT);
    $action = $_POST['action'];

    if ($schedule_id && in_array($action, ['approve', 'reject'])) {
        $status = ($action === 'approve') ? 'approved' : 'rejected';
        
        try {
            // 1. جلب بيانات الطلب المرفوع
            $stmt_fetch = $pdo->prepare("SELECT * FROM schedules WHERE id = :id");
            $stmt_fetch->execute([':id' => $schedule_id]);
            $schedule_data = $stmt_fetch->fetch(PDO::FETCH_ASSOC);

            if ($schedule_data) {
                $student_id = $schedule_data['student_id'];
                
                if ($status === 'approved') {
                    $division_id = $schedule_data['division_id'];
                    $class_id = $schedule_data['class_id'];
                    $group_id = $schedule_data['group_id'];
                    
                    // المسارات
                    $source_file = '../../' . $schedule_data['file_path'];
                    $filename_only = basename($schedule_data['file_path']);
                    $dest_dir = '../../uploads/timetables/';
                    $dest_file = $dest_dir . $filename_only;
                    
                    if (!is_dir($dest_dir)) {
                        mkdir($dest_dir, 0755, true);
                    }

                    // البحث عن استعمال الزمن القديم لنفس الفوج وحذفه
                    $check_stmt = $pdo->prepare("SELECT file_path FROM timetables WHERE division_id = ? AND class_id = ? AND group_id = ?");
                    $check_stmt->execute([$division_id, $class_id, $group_id]);
                    $existing = $check_stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($existing) {
                        $old_file = $dest_dir . $existing['file_path'];
                        if (file_exists($old_file) && is_file($old_file)) {
                            unlink($old_file);
                        }
                    }

                    // نسخ الملف الجديد ليكون ضمن الملفات الرسمية
                    if (file_exists($source_file)) {
                        copy($source_file, $dest_file);
                    }

                    // إدراج أو تحديث استعمال الزمن في الجدول الرسمي ليظهر لجميع طلبة الفوج
                    $stmt_insert = $pdo->prepare("
                        INSERT INTO timetables (division_id, class_id, group_id, file_path) 
                        VALUES (?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE 
                        file_path = VALUES(file_path), updated_at = CURRENT_TIMESTAMP
                    ");
                    $stmt_insert->execute([$division_id, $class_id, $group_id, $filename_only]);
                }

                // 2. تحديث حالة الطلب في جدول schedules
                $stmt_update = $pdo->prepare("UPDATE schedules SET status = :status WHERE id = :id");
                $stmt_update->execute([':status' => $status, ':id' => $schedule_id]);
                
                // 3. إرسال الإشعار للطالب
                $notification_message = ($status === 'approved') 
                    ? "تهانينا! تمت الموافقة على استعمال الزمن الذي قمت برفعه وتم تعميمه على فوجك." 
                    : "نأسف، تم رفض استعمال الزمن الذي قمت برفعه. يرجى التأكد من صحة الملف وإعادة الرفع.";
                
                $notification_link = "student/schedule/my_schedules.php"; 

                $notif_stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (:user_id, :message, :link)");
                $notif_stmt->execute([
                    ':user_id' => $student_id,
                    ':message' => $notification_message,
                    ':link'    => $notification_link
                ]);

                $_SESSION['msg'] = "تمت العملية بنجاح وإشعار الطالب.";
            } else {
                $_SESSION['msg'] = "السجل غير موجود.";
            }
        } catch (PDOException $e) {
            $_SESSION['msg'] = "خطأ في قاعدة البيانات: " . $e->getMessage();
        }
    }
}

header("Location: admin_schedules.php");
exit();