<?php
session_start();
require_once '../config/db_connexion.php';
require_once '../config/GoogleDriveService.php';

// 1. حماية الصفحة والتحقق من الصلاحيات
if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// 2. استقبال البيانات من النموذج
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$exam_date = !empty($_POST['exam_date']) ? $_POST['exam_date'] : null;
$offering_id = filter_input(INPUT_POST, 'offering_id', FILTER_VALIDATE_INT)
            ?: filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$offering_subject_id = filter_input(INPUT_POST, 'subject_id', FILTER_VALIDATE_INT);

// فحص تجاوز حجم بيانات POST
if (empty($_POST) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
    $redirect_url = $offering_id ? "upload_exam.php?id={$offering_id}&error=" : "profil.php?error=";
    header("Location: " . $redirect_url . urlencode("حجم الملف يتجاوز الحد الأقصى المسموح به في الخادم."));
    exit();
}

// التحقق من صحة الملف المدخل
if (!isset($_FILES['exam_file']) || $_FILES['exam_file']['error'] !== UPLOAD_ERR_OK) {
    $err_code = $_FILES['exam_file']['error'] ?? -1;
    $msg = "فشل استلام الملف في السيرفر.";
    if ($err_code === UPLOAD_ERR_INI_SIZE || $err_code === UPLOAD_ERR_FORM_SIZE) {
        $msg = "حجم الملف كبير جداً ويتجاوز الحد المسموح به في إعدادات الخادم.";
    }
    header("Location: upload_exam.php?id={$offering_id}&error=" . urlencode($msg));
    exit();
}

if (empty($title) || !$offering_id || !$offering_subject_id) {
    header("Location: upload_exam.php?id={$offering_id}&error=" . urlencode("يرجى إدخال عنوان الامتحان وتحديد المادة الدراسية."));
    exit();
}

// 3. التحقق من صلاحية الطالب في المقرر وجلب بيانات المسار الأكاديمي
try {
    $stmt_check = $pdo->prepare("
        SELECT 
            co.class_id, 
            c.class_name, 
            d.division_name, 
            s.subject_name, 
            p.professor_name
        FROM student_enrollments se 
        JOIN course_offerings co ON se.offering_id = co.offering_id 
        JOIN classes c ON co.class_id = c.class_id
        JOIN divisions d ON co.division_id = d.division_id
        LEFT JOIN offering_subjects os ON (os.offering_id = co.offering_id AND os.offering_subject_id = ?)
        LEFT JOIN subjects s ON os.subject_id = s.subject_id
        LEFT JOIN professors p ON os.professor_id = p.professor_id
        WHERE se.user_id = ? AND se.offering_id = ?
    ");
    $stmt_check->execute([$offering_subject_id, $user_id, $offering_id]);
    $course_info = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if (!$course_info && (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin')) {
        header("Location: upload_exam.php?id={$offering_id}&error=" . urlencode("ليس لديك صلاحية النشر في هذا المقرر."));
        exit();
    }

    // في حال كان المشرف هو من يرفع، جلب البيانات بدون اشتراط تسجيله كطالب
    if (!$course_info) {
        $stmt_admin_info = $pdo->prepare("
            SELECT 
                co.class_id, 
                c.class_name, 
                d.division_name, 
                s.subject_name, 
                p.professor_name
            FROM course_offerings co
            JOIN classes c ON co.class_id = c.class_id
            JOIN divisions d ON co.division_id = d.division_id
            LEFT JOIN offering_subjects os ON (os.offering_id = co.offering_id AND os.offering_subject_id = ?)
            LEFT JOIN subjects s ON os.subject_id = s.subject_id
            LEFT JOIN professors p ON os.professor_id = p.professor_id
            WHERE co.offering_id = ?
        ");
        $stmt_admin_info->execute([$offering_subject_id, $offering_id]);
        $course_info = $stmt_admin_info->fetch(PDO::FETCH_ASSOC);
    }

    $class_id = $course_info['class_id'];
    $division_folder  = !empty($course_info['division_name']) ? trim($course_info['division_name']) : 'الشعبة العامة';
    $class_folder     = !empty($course_info['class_name']) ? trim($course_info['class_name']) : 'الفصل العام';
    $subject_folder   = !empty($course_info['subject_name']) ? trim($course_info['subject_name']) : 'مادة عامة';
    $professor_folder = !empty($course_info['professor_name']) ? trim($course_info['professor_name']) : 'الامتحانات';

} catch (PDOException $e) {
    header("Location: upload_exam.php?id={$offering_id}&error=" . urlencode("خطأ في التحقق من الصلاحيات والمسار الأكاديمي."));
    exit();
}

// 4. جلب معرف الموسم الجامعي الحالي
try {
    $stmt_year = $pdo->query("SELECT id FROM academic_years WHERE is_current = 1 LIMIT 1");
    $academic_year_id = $stmt_year->fetchColumn() ?: 1;
} catch (PDOException $e) {
    $academic_year_id = 1;
}

// 5. التحقق من صيغة وحجم الملف المرفوع
$file_extension = strtolower(pathinfo($_FILES['exam_file']['name'], PATHINFO_EXTENSION));
$allowed_types = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'webp'];
$max_size = 100 * 1024 * 1024; // 100MB

if (!in_array($file_extension, $allowed_types) || $_FILES['exam_file']['size'] > $max_size) {
    header("Location: upload_exam.php?id={$offering_id}&error=" . urlencode("صيغة الملف غير مدعومة أو الحجم يتجاوز 100MB."));
    exit();
}

// 6. حفظ الملف مؤقتاً في السيرفر لرفعه سحابياً
$temp_dir = '../uploads/temp/';
if (!is_dir($temp_dir)) {
    mkdir($temp_dir, 0777, true);
}

$temp_storage_name = "temp_exam_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $file_extension;
$temp_file_path = $temp_dir . $temp_storage_name;

if (!move_uploaded_file($_FILES['exam_file']['tmp_name'], $temp_file_path)) {
    header("Location: upload_exam.php?id={$offering_id}&error=" . urlencode("تعذر نقل الملف المؤقت على الخادم."));
    exit();
}

// 7. رفع ملف الامتحان والغلاف إلى مجلد Google Drive المنظم
$drive_file_url = null;
$cover_image_drive_url = null;

try {
    $driveService = new GoogleDriveService();

    // مسار الشجرة في Google Drive: شعبة > فصل > مادة > أستاذ (أو قسم الامتحانات)
    $hierarchyPath = [
        $division_folder,
        $class_folder,
        $subject_folder,
        $professor_folder
    ];

    $targetFolderId = $driveService->getOrCreateHierarchy($hierarchyPath);

    // أ. رفع ملف الامتحان الأساسي
    $driveFileName = preg_match('/\.'.preg_quote($file_extension, '/').'$/i', $title) ? $title : ($title . '.' . $file_extension);
    $mime_type = $_FILES['exam_file']['type'] ?: 'application/octet-stream';
    $driveUploadResult = $driveService->uploadFile($temp_file_path, $driveFileName, $mime_type, $targetFolderId);
    $drive_file_url = $driveUploadResult['view_url'];

    if (file_exists($temp_file_path)) {
        unlink($temp_file_path);
    }

    // ب. رفع صورة الغلاف إلى Google Drive (المولدة تلقائياً عبر PDF.js أو المختارة)
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
        $cover_ext = strtolower(pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION));
        if (in_array($cover_ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $temp_cover_name = "temp_cover_exam_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $cover_ext;
            $temp_cover_path = $temp_dir . $temp_cover_name;

            if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $temp_cover_path)) {
                $driveCoverTitle = "غلاف_امتحان - " . $title . "." . $cover_ext;
                $cover_mime = $_FILES['cover_image']['type'] ?: 'image/webp';
                
                $coverDriveResult = $driveService->uploadFile($temp_cover_path, $driveCoverTitle, $cover_mime, $targetFolderId);

                $cover_file_id = $coverDriveResult['id'] ?? null;
                if (!$cover_file_id && !empty($coverDriveResult['view_url']) && preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $coverDriveResult['view_url'], $m)) {
                    $cover_file_id = $m[1];
                }

                if ($cover_file_id) {
                    $cover_image_drive_url = "https://drive.google.com/thumbnail?id=" . $cover_file_id . "&sz=w1000";
                } else {
                    $cover_image_drive_url = $coverDriveResult['view_url'] ?? null;
                }

                if (file_exists($temp_cover_path)) {
                    unlink($temp_cover_path);
                }
            }
        }
    }

} catch (Exception $e) {
    if (file_exists($temp_file_path)) {
        unlink($temp_file_path);
    }
    if (isset($temp_cover_path) && file_exists($temp_cover_path)) {
        unlink($temp_cover_path);
    }
    header("Location: upload_exam.php?id={$offering_id}&error=" . urlencode("فشل الرفع السحابي إلى Google Drive: " . $e->getMessage()));
    exit();
}

// 8. حفظ الامتحان في قاعدة البيانات مع دعم الأعمدة الديناميكي
try {
    $pdo->beginTransaction();

    // التحقق من وجود الأعمدة اللازمة وإضافتها تلقائياً إن لم تكن متوفرة
    $columns_stmt = $pdo->query("SHOW COLUMNS FROM exams");
    $existing_columns = $columns_stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('cover_image', $existing_columns)) {
        try {
            $pdo->exec("ALTER TABLE exams ADD COLUMN cover_image VARCHAR(500) NULL AFTER file_type");
        } catch (Exception $e) {}
    }

    if (!in_array('exam_date', $existing_columns)) {
        try {
            $pdo->exec("ALTER TABLE exams ADD COLUMN exam_date DATE NULL AFTER description");
        } catch (Exception $e) {}
    }

    // إدراج سجل الامتحان برابط Google Drive والمادة والموسم
    $stmt_insert = $pdo->prepare("
        INSERT INTO exams (title, description, exam_date, file_path, file_type, cover_image, class_id, offering_subject_id, uploader_user_id, academic_year_id) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt_insert->execute([
        $title, 
        $description, 
        $exam_date, 
        $drive_file_url, 
        $file_extension, 
        $cover_image_drive_url, 
        $class_id, 
        $offering_subject_id, 
        $user_id, 
        $academic_year_id
    ]);

    // إرسال إشعارات لجميع طلاب الفصل المسجلين في هذا المقرر
    $stmt_students = $pdo->prepare("
        SELECT DISTINCT se.user_id, se.offering_id
        FROM student_enrollments se
        JOIN course_offerings co ON se.offering_id = co.offering_id
        WHERE co.class_id = ? AND se.user_id != ?
    ");
    $stmt_students->execute([$class_id, $user_id]);
    $students_to_notify = $stmt_students->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($students_to_notify) > 0) {
        $uploader_name = $_SESSION['username'] ?? 'طالب';
        $notification_message = "قام " . $uploader_name . " بإضافة نموذج امتحان جديد: " . $title;
        $stmt_notify = $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
        
        foreach ($students_to_notify as $student) {
            $notification_link = "student/class_exams.php?id=" . $student['offering_id'];
            $stmt_notify->execute([$student['user_id'], $notification_message, $notification_link]);
        }
    }

    $pdo->commit();
    header("Location: class_exams.php?id={$offering_id}&status=upload_success");
    exit();

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    header("Location: upload_exam.php?id={$offering_id}&error=" . urlencode("حدث خطأ أثناء حفظ الامتحان: " . $e->getMessage()));
    exit();
}