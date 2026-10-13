<?php
session_start();
require_once '../../config/db_connexion.php';
require_once '../../config/GoogleDriveService.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../login.php");
    exit();
}

$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$academic_year_id = filter_input(INPUT_POST, 'academic_year_id', FILTER_VALIDATE_INT);
$division_id = filter_input(INPUT_POST, 'division_id', FILTER_VALIDATE_INT);
$class_id = filter_input(INPUT_POST, 'class_id', FILTER_VALIDATE_INT);
$offering_id = filter_input(INPUT_POST, 'offering_id', FILTER_VALIDATE_INT) ?: null;
$offering_subject_id = filter_input(INPUT_POST, 'offering_subject_id', FILTER_VALIDATE_INT) ?: null;
$uploader_user_id = $_SESSION['user_id'];
$send_notification = isset($_POST['send_notification']) ? 1 : 0;

if (empty($title) || !$division_id || !$class_id || !isset($_FILES['exam_file']) || $_FILES['exam_file']['error'] != 0) {
    header("Location: add.php?error=" . urlencode("البيانات الأساسية أو الملف مفقودة."));
    exit();
}

try {
    $pdo->beginTransaction();

    // 1. التحقق من الموسم الجامعي
    if (!$academic_year_id) {
        $stmt_year = $pdo->query("SELECT id FROM academic_years WHERE is_current = 1 LIMIT 1");
        $academic_year_id = $stmt_year->fetchColumn() ?: 1;
    }

    // 2. جلب أسماء الشعبة والفصل والمادة لتنظيم المجلدات في Google Drive
    $stmt_meta = $pdo->prepare("
        SELECT 
            d.division_name, 
            c.class_name,
            s.subject_name
        FROM divisions d
        JOIN classes c ON c.class_id = ?
        LEFT JOIN offering_subjects os ON os.offering_subject_id = ?
        LEFT JOIN subjects s ON os.subject_id = s.subject_id
        WHERE d.division_id = ?
    ");
    $stmt_meta->execute([$class_id, $offering_subject_id, $division_id]);
    $meta = $stmt_meta->fetch(PDO::FETCH_ASSOC);

    $division_folder = !empty($meta['division_name']) ? trim($meta['division_name']) : 'الشعبة العامة';
    $class_folder    = !empty($meta['class_name']) ? trim($meta['class_name']) : 'الفصل العام';
    $subject_folder  = !empty($meta['subject_name']) ? trim($meta['subject_name']) : 'مادة عامة';

    // 3. حفظ الملف مؤقتاً في السيرفر ورفعه إلى Google Drive
    $temp_dir = '../../uploads/temp/';
    if (!is_dir($temp_dir)) { 
        mkdir($temp_dir, 0777, true); 
    }
    
    $file_extension = strtolower(pathinfo($_FILES['exam_file']['name'], PATHINFO_EXTENSION));
    $temp_name = "temp_exam_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $file_extension;
    $temp_file = $temp_dir . $temp_name;

    if (!move_uploaded_file($_FILES['exam_file']['tmp_name'], $temp_file)) {
        throw new Exception("فشل في استلام الملف على الخادم.");
    }

    $driveService = new GoogleDriveService();
    $targetFolderId = $driveService->getOrCreateHierarchy([
        $division_folder,
        $class_folder,
        $subject_folder,
        'نماذج الامتحانات'
    ]);

    $driveFileName = preg_match('/\.'.preg_quote($file_extension, '/').'$/i', $title) ? $title : ($title . '.' . $file_extension);
    $mime_type = $_FILES['exam_file']['type'] ?: 'application/octet-stream';
    $driveResult = $driveService->uploadFile($temp_file, $driveFileName, $mime_type, $targetFolderId);
    $final_file_path = $driveResult['view_url'];

    if (file_exists($temp_file)) { 
        unlink($temp_file); 
    }

    // 4. حفظ بيانات الامتحان ورابط Google Drive في قاعدة البيانات
    $stmt = $pdo->prepare("
        INSERT INTO exams (title, description, file_path, file_type, academic_year_id, division_id, class_id, offering_id, offering_subject_id, uploader_user_id, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'approved')
    ");
    $stmt->execute([
        $title, 
        $description, 
        $final_file_path, 
        $file_extension, 
        $academic_year_id, 
        $division_id, 
        $class_id, 
        $offering_id, 
        $offering_subject_id, 
        $uploader_user_id
    ]);

    // 5. إرسال الإشعارات للطلاب المسجلين
    if ($send_notification) {
        $query_students = "
            SELECT DISTINCT se.user_id, se.offering_id 
            FROM student_enrollments se 
            JOIN course_offerings co ON se.offering_id = co.offering_id 
            WHERE co.academic_year_id = ? AND co.division_id = ? AND co.class_id = ?
        ";
        $params_students = [$academic_year_id, $division_id, $class_id];
        
        if ($offering_id) {
            $query_students .= " AND co.offering_id = ?";
            $params_students[] = $offering_id;
        }

        $stmt_students = $pdo->prepare($query_students);
        $stmt_students->execute($params_students);
        $students = $stmt_students->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($students)) {
            $notification_message = "تم نشر نموذج امتحان جديد: " . $title;
            $stmt_notify = $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
            foreach ($students as $stu) {
                $notification_link = "student/class_exams.php?id=" . $stu['offering_id'];
                $stmt_notify->execute([$stu['user_id'], $notification_message, $notification_link]);
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
    header("Location: add.php?error=" . urlencode($e->getMessage()));
    exit();
}