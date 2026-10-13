<?php
session_start();
require_once '../../config/db_connexion.php';
require_once '../../config/GoogleDriveService.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../login.php");
    exit();
}

$exam_id = filter_input(INPUT_POST, 'exam_id', FILTER_VALIDATE_INT);
$action = $_POST['action'] ?? '';
$reason = trim($_POST['reason'] ?? '');

if (!$exam_id || !in_array($action, ['approve', 'reject'])) {
    $_SESSION['msg'] = "طلب غير صالح.";
    header("Location: exams.php");
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT title, uploader_user_id, file_path, cover_image, class_id FROM exams WHERE exam_id = ?");
    $stmt->execute([$exam_id]);
    $exam = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$exam) {
        throw new Exception("الامتحان غير موجود.");
    }

    if ($action === 'approve') {
        $pdo->prepare("UPDATE exams SET status = 'approved' WHERE exam_id = ?")->execute([$exam_id]);

        $stmt_offering = $pdo->prepare("
            SELECT se.offering_id 
            FROM student_enrollments se 
            JOIN course_offerings co ON se.offering_id = co.offering_id 
            WHERE se.user_id = ? AND co.class_id = ? 
            LIMIT 1
        ");
        $stmt_offering->execute([$exam['uploader_user_id'], $exam['class_id']]);
        $offering_id = $stmt_offering->fetchColumn();
        $target_link = $offering_id ? "student/class_exams.php?id=" . $offering_id : "student/profil.php";

        $msg = "تم قبول ونشر نموذج الامتحان الخاص بك: " . $exam['title'];
        $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)")->execute([$exam['uploader_user_id'], $msg, $target_link]);

        $_SESSION['msg'] = "تم قبول نموذج الامتحان بنجاح.";
    } elseif ($action === 'reject') {
        if (empty($reason)) {
            $_SESSION['msg'] = "يجب تحديد سبب الرفض.";
            header("Location: exams.php"); 
            exit();
        }

        $msg = "تم رفض نموذج الامتحان '" . $exam['title'] . "' للسبب التالي: " . $reason;
        $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, '#')")->execute([$exam['uploader_user_id'], $msg]);

        $driveService = new GoogleDriveService();

        // حذف ملف الامتحان من Google Drive أو محلياً
        if (preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $exam['file_path'], $m) || preg_match('/[?&]id=([a-zA-Z0-9_-]+)/', $exam['file_path'], $m)) {
            try {
                $driveService->deleteFile($m[1]);
            } catch (Exception $ex) {}
        } elseif (file_exists('../../' . $exam['file_path'])) {
            unlink('../../' . $exam['file_path']);
        }

        // حذف صورة الغلاف من Google Drive أو محلياً
        if (!empty($exam['cover_image'])) {
            if (preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $exam['cover_image'], $cm) || preg_match('/[?&]id=([a-zA-Z0-9_-]+)/', $exam['cover_image'], $cm)) {
                try {
                    $driveService->deleteFile($cm[1]);
                } catch (Exception $ex) {}
            } elseif (file_exists('../../' . $exam['cover_image'])) {
                unlink('../../' . $exam['cover_image']);
            }
        }

        $pdo->prepare("DELETE FROM exams WHERE exam_id = ?")->execute([$exam_id]);

        $_SESSION['msg'] = "تم رفض وحذف نموذج الامتحان بنجاح.";
    }
} catch (Exception $e) {
    $_SESSION['msg'] = "خطأ: " . $e->getMessage();
}

header("Location: exams.php");
exit();