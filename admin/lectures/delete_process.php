<?php
session_start();
require_once '../../config/db_connexion.php';
require_once '../../config/GoogleDriveService.php';

// حماية الصفحة والتحقق من الصلاحيات
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php?error=" . urlencode("وصول غير مصرح به"));
    exit();
}

$lecture_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$lecture_id) {
    header("Location: index.php?error=" . urlencode("معرف غير صحيح"));
    exit();
}

try {
    $pdo->beginTransaction();

    // أ. جلب مسارات الملفات
    $stmt_paths = $pdo->prepare("SELECT file_path, cover_image FROM lectures WHERE lecture_id = ?");
    $stmt_paths->execute([$lecture_id]);
    $paths = $stmt_paths->fetch(PDO::FETCH_ASSOC);
    
    $filepath_to_delete = $paths['file_path'] ?? null;
    $coverpath_to_delete = $paths['cover_image'] ?? null;

    // ب. التحقق من عدد المحاضرات التي تستخدم نفس الملف الأساسي
    $file_usage_count = 0;
    if ($filepath_to_delete) {
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM lectures WHERE file_path = ? AND lecture_id != ?");
        $stmt_check->execute([$filepath_to_delete, $lecture_id]);
        $file_usage_count = $stmt_check->fetchColumn();
    }

    // ج. التحقق من عدد المحاضرات التي تستخدم نفس الغلاف
    $cover_usage_count = 0;
    if ($coverpath_to_delete) {
        $stmt_check_cover = $pdo->prepare("SELECT COUNT(*) FROM lectures WHERE cover_image = ? AND lecture_id != ?");
        $stmt_check_cover->execute([$coverpath_to_delete, $lecture_id]);
        $cover_usage_count = $stmt_check_cover->fetchColumn();
    }

    // د. حذف سجل المحاضرة
    $stmt_delete = $pdo->prepare("DELETE FROM lectures WHERE lecture_id = ?");
    $stmt_delete->execute([$lecture_id]);

    $driveService = new GoogleDriveService();

    // هـ. حذف الملف الفعلي من Google Drive أو الخادم
    if ($file_usage_count == 0 && $filepath_to_delete) {
        if (preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $filepath_to_delete, $m) || preg_match('/[?&]id=([a-zA-Z0-9_-]+)/', $filepath_to_delete, $m)) {
            try {
                $driveService->deleteFile($m[1]);
            } catch (Exception $ex) {}
        } elseif (file_exists('../../' . $filepath_to_delete)) {
            unlink('../../' . $filepath_to_delete);
        }
    }
    
    // و. حذف صورة الغلاف من Google Drive أو الخادم
    if ($cover_usage_count == 0 && $coverpath_to_delete) {
        if (preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $coverpath_to_delete, $cm) || preg_match('/[?&]id=([a-zA-Z0-9_-]+)/', $coverpath_to_delete, $cm)) {
            try {
                $driveService->deleteFile($cm[1]);
            } catch (Exception $ex) {}
        } elseif (file_exists('../../' . $coverpath_to_delete)) {
            unlink('../../' . $coverpath_to_delete);
        }
    }
    
    $pdo->commit();
    header("Location: index.php?status=deleted");
    exit();

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    header("Location: index.php?error=" . urlencode("خطأ أثناء الحذف: " . $e->getMessage()));
    exit();
}