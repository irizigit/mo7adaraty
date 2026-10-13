<?php
session_start();
require_once '../../config/db_connexion.php';
require_once '../../config/GoogleDriveService.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../login.php");
    exit();
}

$lecture_id = filter_input(INPUT_POST, 'lecture_id', FILTER_VALIDATE_INT);
$action = $_POST['action'] ?? '';
$reason = trim($_POST['reason'] ?? '');

if (!$lecture_id) {
    header("Location: lectures.php");
    exit();
}

try {
    $pdo->beginTransaction();

    if ($action === 'approve') {
        $stmt_lec = $pdo->prepare("SELECT * FROM lectures WHERE lecture_id = ? AND status = 'pending'");
        $stmt_lec->execute([$lecture_id]);
        $lecture = $stmt_lec->fetch(PDO::FETCH_ASSOC);

        if (!$lecture) {
            throw new Exception("المحاضرة غير موجودة أو تم البت فيها مسبقاً.");
        }

        $student_uploader_id = $lecture['uploader_user_id'];

        $stmt_active_year = $pdo->query("SELECT id FROM academic_years WHERE is_current = 1 LIMIT 1");
        $current_academic_year_id = $stmt_active_year->fetchColumn();

        if (!$current_academic_year_id) {
            throw new Exception("لا يوجد موسم جامعي مفعل حالياً في النظام.");
        }

        $stmt_info = $pdo->prepare("
            SELECT 
                co.division_id, d.division_name, 
                co.class_id, c.class_name, 
                co.track_id, 
                os.subject_id, s.subject_name, 
                os.professor_id, p.professor_name
            FROM offering_subjects os
            JOIN course_offerings co ON os.offering_id = co.offering_id
            JOIN divisions d ON co.division_id = d.division_id
            JOIN classes c ON co.class_id = c.class_id
            JOIN subjects s ON os.subject_id = s.subject_id
            LEFT JOIN professors p ON os.professor_id = p.professor_id
            WHERE os.offering_subject_id = ? AND co.offering_id = ?
        ");
        $stmt_info->execute([$lecture['offering_subject_id'], $lecture['offering_id']]);
        $info = $stmt_info->fetch(PDO::FETCH_ASSOC);

        if (!$info) {
            throw new Exception("تعذر العثور على المسار الأكاديمي للمحاضرة.");
        }

        $file_path = $lecture['file_path'];
        $file_extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        $is_drive = (strpos($file_path, 'http://') === 0 || strpos($file_path, 'https://') === 0);
        $final_file_path = $file_path;

        if (!$is_drive) {
            $local_full_path = '../../' . ltrim($file_path, '/');
            if (file_exists($local_full_path)) {
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

                $driveFileName = preg_match('/\.'.preg_quote($file_extension, '/').'$/i', $lecture['title']) ? $lecture['title'] : ($lecture['title'] . '.' . $file_extension);
                $mime_type = mime_content_type($local_full_path) ?: 'application/octet-stream';
                $driveResult = $driveService->uploadFile($local_full_path, $driveFileName, $mime_type, $targetFolderId);
                $final_file_path = $driveResult['view_url'];

                unlink($local_full_path);
            }
        }

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

        $stmt_update = $pdo->prepare("
            UPDATE lectures 
            SET status = 'approved',
                academic_year_id = ?,
                file_path = ?
            WHERE lecture_id = ?
        ");
        $stmt_update->execute([
            $current_academic_year_id,
            $final_file_path,
            $lecture_id
        ]);

        $stmt_insert = $pdo->prepare("
            INSERT INTO lectures (title, description, file_path, file_type, cover_image, offering_id, offering_subject_id, uploader_user_id, academic_year_id, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'approved')
        ");
        $stmt_notify = $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
        $stmt_students = $pdo->prepare("SELECT user_id FROM student_enrollments WHERE offering_id = ? AND academic_year_id = ?");

        foreach ($targets as $target) {
            if ($target['offering_id'] != $lecture['offering_id'] || $target['offering_subject_id'] != $lecture['offering_subject_id']) {
                $stmt_insert->execute([
                    $lecture['title'],
                    $lecture['description'],
                    $final_file_path,
                    $lecture['file_type'],
                    $lecture['cover_image'],
                    $target['offering_id'],
                    $target['offering_subject_id'],
                    $student_uploader_id,
                    $current_academic_year_id
                ]);
            }

            $stmt_students->execute([$target['offering_id'], $current_academic_year_id]);
            $students = $stmt_students->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($students)) {
                $notification_message = "تم نشر محاضرة جديدة: " . $lecture['title'];
                $notification_link = "student/class_lectures.php?id=" . $target['offering_id'];
                foreach ($students as $stu) {
                    $stmt_notify->execute([$stu['user_id'], $notification_message, $notification_link]);
                }
            }
        }

        if (!empty($student_uploader_id)) {
            $msg_uploader = "تمت الموافقة على نشر محاضرتك بنجاح وتعميمها على جميع الأفواج المطابقة: " . $lecture['title'];
            $link_uploader = "student/class_lectures.php?id=" . $lecture['offering_id'];
            $stmt_notify->execute([$student_uploader_id, $msg_uploader, $link_uploader]);
        }

        $pdo->commit();
        $_SESSION['msg'] = "تم قبول المحاضرة وتعميمها بنجاح على جميع الأفواج المطابقة في الموسم الحالي باسم الطالب الناشر.";
        header("Location: lectures.php");
        exit();

    } elseif ($action === 'reject') {
        $stmt_lec = $pdo->prepare("SELECT * FROM lectures WHERE lecture_id = ? AND status = 'pending'");
        $stmt_lec->execute([$lecture_id]);
        $lecture = $stmt_lec->fetch(PDO::FETCH_ASSOC);

        if ($lecture) {
            $driveService = new GoogleDriveService();

            // حذف ملف المحاضرة من Google Drive أو محلياً
            if (preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $lecture['file_path'], $m) || preg_match('/[?&]id=([a-zA-Z0-9_-]+)/', $lecture['file_path'], $m)) {
                try {
                    $driveService->deleteFile($m[1]);
                } catch (Exception $ex) {}
            } elseif (file_exists('../../' . $lecture['file_path'])) {
                unlink('../../' . $lecture['file_path']);
            }

            // حذف صورة الغلاف من Google Drive أو محلياً
            if (!empty($lecture['cover_image'])) {
                if (preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $lecture['cover_image'], $cm) || preg_match('/[?&]id=([a-zA-Z0-9_-]+)/', $lecture['cover_image'], $cm)) {
                    try {
                        $driveService->deleteFile($cm[1]);
                    } catch (Exception $ex) {}
                } elseif (file_exists('../../' . $lecture['cover_image'])) {
                    unlink('../../' . $lecture['cover_image']);
                }
            }

            $stmt_del = $pdo->prepare("DELETE FROM lectures WHERE lecture_id = ?");
            $stmt_del->execute([$lecture_id]);

            if (!empty($lecture['uploader_user_id'])) {
                $reject_msg = "تم رفض المحاضرة المرفوعة (" . $lecture['title'] . "): " . ($reason ?: 'لم يتم تحديد سبب.');
                $stmt_notify = $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, '#')");
                $stmt_notify->execute([$lecture['uploader_user_id'], $reject_msg]);
            }
        }

        $pdo->commit();
        $_SESSION['msg'] = "تم رفض المحاضرة وإشعار الطالب بالسبب بنجاح.";
        header("Location: lectures.php");
        exit();
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['msg'] = "خطأ: " . $e->getMessage();
    header("Location: lectures.php");
    exit();
}