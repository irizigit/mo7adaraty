<?php
session_start();
require_once '../../config/db_connexion.php';
require_once '../../config/GoogleDriveService.php';

// حماية الصفحة والتحقق من الصلاحيات
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../login.php");
    exit();
}

$lecture_id = filter_input(INPUT_POST, 'lecture_id', FILTER_VALIDATE_INT);
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$status = $_POST['status'] ?? 'pending';

if (empty($title) || !$lecture_id) {
    header("Location: edit.php?id=$lecture_id&error=" . urlencode("البيانات غير كاملة."));
    exit();
}

try {
    // تحديث البيانات الأساسية
    $stmt = $pdo->prepare("UPDATE lectures SET title = ?, description = ?, status = ? WHERE lecture_id = ?");
    $stmt->execute([$title, $description, $status, $lecture_id]);

    // معالجة تحديث صورة الغلاف إن وُجدت
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
        $stmt_old = $pdo->prepare("SELECT cover_image, offering_id, offering_subject_id FROM lectures WHERE lecture_id = ?");
        $stmt_old->execute([$lecture_id]);
        $lec_data = $stmt_old->fetch(PDO::FETCH_ASSOC);
        $old_cover_path = $lec_data['cover_image'] ?? null;

        $cover_extension = strtolower(pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($cover_extension, $allowed_extensions)) {
            $temp_dir = '../../uploads/temp/';
            if (!is_dir($temp_dir)) { mkdir($temp_dir, 0777, true); }
            $temp_cover_name = "temp_cover_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $cover_extension;
            $temp_cover_file = $temp_dir . $temp_cover_name;

            if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $temp_cover_file)) {
                // جلب المسار الأكاديمي لرفع الغلاف إلى Google Drive
                $stmt_hierarchy = $pdo->prepare("
                    SELECT d.division_name, c.class_name, s.subject_name, p.professor_name
                    FROM course_offerings co
                    JOIN divisions d ON co.division_id = d.division_id
                    JOIN classes c ON co.class_id = c.class_id
                    LEFT JOIN offering_subjects os ON (os.offering_id = co.offering_id AND os.offering_subject_id = ?)
                    LEFT JOIN subjects s ON os.subject_id = s.subject_id
                    LEFT JOIN professors p ON os.professor_id = p.professor_id
                    WHERE co.offering_id = ?
                ");
                $stmt_hierarchy->execute([$lec_data['offering_subject_id'], $lec_data['offering_id']]);
                $course_info = $stmt_hierarchy->fetch(PDO::FETCH_ASSOC);

                $division_folder  = !empty($course_info['division_name']) ? trim($course_info['division_name']) : 'الشعبة العامة';
                $class_folder     = !empty($course_info['class_name']) ? trim($course_info['class_name']) : 'الفصل العام';
                $subject_folder   = !empty($course_info['subject_name']) ? trim($course_info['subject_name']) : 'مادة عامة';
                $professor_folder = !empty($course_info['professor_name']) ? trim($course_info['professor_name']) : 'بدون أستاذ';

                $driveService = new GoogleDriveService();
                $targetFolderId = $driveService->getOrCreateHierarchy([
                    $division_folder,
                    $class_folder,
                    $subject_folder,
                    $professor_folder
                ]);

                $driveCoverTitle = "غلاف - " . $title . "." . $cover_extension;
                $cover_mime = $_FILES['cover_image']['type'] ?: 'image/webp';
                $coverDriveResult = $driveService->uploadFile($temp_cover_file, $driveCoverTitle, $cover_mime, $targetFolderId);

                $cover_file_id = $coverDriveResult['id'] ?? null;
                if (!$cover_file_id && !empty($coverDriveResult['view_url']) && preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $coverDriveResult['view_url'], $m)) {
                    $cover_file_id = $m[1];
                }

                $new_cover_path = $cover_file_id 
                    ? "https://drive.google.com/thumbnail?id=" . $cover_file_id . "&sz=w1000" 
                    : ($coverDriveResult['view_url'] ?? null);

                if (file_exists($temp_cover_file)) {
                    unlink($temp_cover_file);
                }

                if ($new_cover_path) {
                    $stmt_cover = $pdo->prepare("UPDATE lectures SET cover_image = ? WHERE lecture_id = ?");
                    $stmt_cover->execute([$new_cover_path, $lecture_id]);
                    
                    // حذف الغلاف القديم إن لم يكن مستخدماً في مكان آخر
                    if ($old_cover_path) {
                        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM lectures WHERE cover_image = ? AND lecture_id != ?");
                        $stmt_check->execute([$old_cover_path, $lecture_id]);
                        if ($stmt_check->fetchColumn() == 0) {
                            if (preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $old_cover_path, $om) || preg_match('/[?&]id=([a-zA-Z0-9_-]+)/', $old_cover_path, $om)) {
                                try { $driveService->deleteFile($om[1]); } catch (Exception $ex) {}
                            } elseif (file_exists('../../' . $old_cover_path)) {
                                unlink('../../' . $old_cover_path);
                            }
                        }
                    }
                }
            }
        }
    }

    header("Location: index.php?status=updated");
    exit();

} catch (PDOException $e) {
    header("Location: edit.php?id=$lecture_id&error=" . urlencode("خطأ في النظام: " . $e->getMessage()));
    exit();
}