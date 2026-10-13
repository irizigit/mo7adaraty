<?php
session_start();
require_once '../../config/db_connexion.php';
require_once '../../config/GoogleDriveService.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $exam_id = filter_input(INPUT_POST, 'exam_id', FILTER_VALIDATE_INT);

    if ($exam_id) {
        try {
            $pdo->beginTransaction();

            $stmt_path = $pdo->prepare("SELECT file_path FROM exams WHERE exam_id = ?");
            $stmt_path->execute([$exam_id]);
            $file_path = $stmt_path->fetchColumn();

            $stmt = $pdo->prepare("DELETE FROM exams WHERE exam_id = ?");
            $stmt->execute([$exam_id]);

            // حذف الملف من Google Drive إذا كان رابطاً سحابياً أو من السيرفر إذا كان محلياً
            if ($file_path) {
                if (preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $file_path, $m) || preg_match('/[?&]id=([a-zA-Z0-9_-]+)/', $file_path, $m)) {
                    try {
                        $driveService = new GoogleDriveService();
                        $driveService->deleteFile($m[1]);
                    } catch (Exception $ex) {}
                } elseif (file_exists('../../' . $file_path)) {
                    unlink('../../' . $file_path);
                }
            }

            $pdo->commit();
            $_SESSION['msg'] = "تم حذف الامتحان وملفه نهائياً بنجاح.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "حدث خطأ أثناء الحذف: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "معرف الامتحان غير صالح.";
    }
}
header("Location: index.php");
exit();