<?php
session_start();
require_once '../../config/db_connexion.php';
require_once '../../config/GoogleDriveService.php';

// 1. حماية الصفحة والتحقق من الصلاحيات
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../login.php");
    exit();
}

// 2. استقبال البيانات من النموذج
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$offering_id = filter_input(INPUT_POST, 'offering_id', FILTER_VALIDATE_INT);
$offering_subject_id = filter_input(INPUT_POST, 'offering_subject_id', FILTER_VALIDATE_INT);
$uploader_user_id = $_SESSION['user_id'];

if (empty($title) || !$offering_id || !$offering_subject_id || !isset($_FILES['lecture_file']) || $_FILES['lecture_file']['error'] != 0) {
    header("Location: add.php?error=" . urlencode("البيانات غير كاملة أو الملف غير صالح."));
    exit();
}

try {
    $pdo->beginTransaction();

    // 3. التحقق من الموسم الجامعي المفعل حالياً
    $stmt_active_year = $pdo->query("SELECT id FROM academic_years WHERE is_current = 1 LIMIT 1");
    $current_academic_year_id = $stmt_active_year->fetchColumn();

    if (!$current_academic_year_id) {
        throw new Exception("لا يوجد موسم جامعي مفعل حالياً في النظام.");
    }

    // 4. جلب بيانات المقرر والتأكد من انتمائه للموسم المفعل
    $stmt_info = $pdo->prepare("
        SELECT 
            co.division_id, d.division_name, 
            co.class_id, c.class_name, 
            co.track_id, 
            os.subject_id, s.subject_name, 
            os.professor_id, p.professor_name, 
            co.academic_year_id
        FROM offering_subjects os
        JOIN course_offerings co ON os.offering_id = co.offering_id
        JOIN divisions d ON co.division_id = d.division_id
        JOIN classes c ON co.class_id = c.class_id
        JOIN subjects s ON os.subject_id = s.subject_id
        LEFT JOIN professors p ON os.professor_id = p.professor_id
        WHERE os.offering_subject_id = ? AND co.offering_id = ? AND co.academic_year_id = ?
    ");
    $stmt_info->execute([$offering_subject_id, $offering_id, $current_academic_year_id]);
    $info = $stmt_info->fetch(PDO::FETCH_ASSOC);

    if (!$info) {
        throw new Exception("بيانات المقرر أو المادة لا تنتمي للموسم الجامعي المفعل حالياً.");
    }

    // 5. البحث عن جميع الأفواج الشبيهة ضمن نفس الموسم الجامعي المفعل حصراً
    $sql_targets = "
        SELECT os.offering_subject_id, co.offering_id
        FROM offering_subjects os
        JOIN course_offerings co ON os.offering_id = co.offering_id
        WHERE co.academic_year_id = ?
          AND co.division_id = ?
          AND co.class_id = ?
          AND (co.track_id <=> ?)
          AND os.subject_id = ?
          AND (os.professor_id <=> ?)
          AND (co.status = 1 OR co.status = 'active')
    ";
    $stmt_targets = $pdo->prepare($sql_targets);
    $stmt_targets->execute([
        $current_academic_year_id,
        $info['division_id'], 
        $info['class_id'], 
        $info['track_id'], 
        $info['subject_id'], 
        $info['professor_id']
    ]);
    $targets = $stmt_targets->fetchAll(PDO::FETCH_ASSOC);

    // 6. حفظ الملف المؤقت ورفعه إلى Google Drive داخل هيكل المجلدات المنظم
    $temp_dir = '../../uploads/temp/';
    if (!is_dir($temp_dir)) { 
        mkdir($temp_dir, 0777, true); 
    }
    
    $file_extension = strtolower(pathinfo($_FILES['lecture_file']['name'], PATHINFO_EXTENSION));
    $temp_name = "temp_lec_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $file_extension;
    $temp_file = $temp_dir . $temp_name;

    if (!move_uploaded_file($_FILES['lecture_file']['tmp_name'], $temp_file)) {
        throw new Exception("فشل في استلام الملف على الخادم.");
    }

    $division_folder  = !empty($info['division_name']) ? trim($info['division_name']) : 'الشعبة العامة';
    $class_folder     = !empty($info['class_name']) ? trim($info['class_name']) : 'الفصل العام';
    $subject_folder   = !empty($info['subject_name']) ? trim($info['subject_name']) : 'مادة عامة';
    $professor_folder = !empty($info['professor_name']) ? trim($info['professor_name']) : 'بدون أستاذ';

    $driveService = new GoogleDriveService();
    $targetFolderId = $driveService->getOrCreateHierarchy([
        $division_folder,
        $class_folder,
        $subject_folder,
        $professor_folder
    ]);

    $driveFileName = preg_match('/\.'.preg_quote($file_extension, '/').'$/i', $title) ? $title : ($title . '.' . $file_extension);
    $mime_type = $_FILES['lecture_file']['type'] ?: 'application/octet-stream';
    $driveResult = $driveService->uploadFile($temp_file, $driveFileName, $mime_type, $targetFolderId);
    $final_file_path = $driveResult['view_url'];

    if (file_exists($temp_file)) { 
        unlink($temp_file); 
    }

    // 7. رفع صورة الغلاف إلى Google Drive والحصول على رابطها المباشر
    $cover_image_path = null;
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
        $cover_extension = strtolower(pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION));
        $allowed_img = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($cover_extension, $allowed_img)) {
            $temp_cover_name = "temp_cover_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $cover_extension;
            $temp_cover_file = $temp_dir . $temp_cover_name;

            if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $temp_cover_file)) {
                $driveCoverTitle = "غلاف - " . $title . "." . $cover_extension;
                $cover_mime = $_FILES['cover_image']['type'] ?: 'image/webp';
                
                $coverDriveResult = $driveService->uploadFile($temp_cover_file, $driveCoverTitle, $cover_mime, $targetFolderId);
                
                $cover_file_id = $coverDriveResult['id'] ?? null;
                if (!$cover_file_id && !empty($coverDriveResult['view_url']) && preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $coverDriveResult['view_url'], $m)) {
                    $cover_file_id = $m[1];
                }

                if ($cover_file_id) {
                    $cover_image_path = "https://drive.google.com/thumbnail?id=" . $cover_file_id . "&sz=w1000";
                } else {
                    $cover_image_path = $coverDriveResult['view_url'] ?? null;
                }

                if (file_exists($temp_cover_file)) {
                    unlink($temp_cover_file);
                }
            }
        }
    }

    // 8. حفظ السجلات وربطها بالموسم الجامعي المفعل الحالي حصراً
    $stmt_insert = $pdo->prepare("
        INSERT INTO lectures (title, description, file_path, file_type, cover_image, offering_id, offering_subject_id, uploader_user_id, academic_year_id, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'approved')
    ");
    $stmt_notify = $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
    $stmt_students = $pdo->prepare("SELECT user_id FROM student_enrollments WHERE offering_id = ? AND academic_year_id = ?");

    foreach ($targets as $target) {
        $stmt_insert->execute([
            $title, 
            $description, 
            $final_file_path, 
            $file_extension, 
            $cover_image_path, 
            $target['offering_id'], 
            $target['offering_subject_id'], 
            $uploader_user_id, 
            $current_academic_year_id
        ]);

        $stmt_students->execute([$target['offering_id'], $current_academic_year_id]);
        $students_to_notify = $stmt_students->fetchAll(PDO::FETCH_ASSOC);

        if (count($students_to_notify) > 0) {
            $notification_message = "تم نشر محاضرة جديدة: " . $title;
            $notification_link = "student/class_lectures.php?id=" . $target['offering_id'];
            foreach ($students_to_notify as $student) {
                $stmt_notify->execute([$student['user_id'], $notification_message, $notification_link]);
            }
        }
    }

    $pdo->commit();
    header("Location: index.php?status=added_successfully");
    exit();

} catch (Exception $e) {
    if ($pdo->inTransaction()) { 
        $pdo->rollBack(); 
    }
    if (isset($temp_file) && file_exists($temp_file)) { 
        unlink($temp_file); 
    }
    if (isset($temp_cover_file) && file_exists($temp_cover_file)) { 
        unlink($temp_cover_file); 
    }
    header("Location: add.php?error=" . urlencode($e->getMessage()));
    exit();
}