<?php
session_start();
require_once '../../config/db_connexion.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../login.php");
    exit();
}

$request_id = filter_input(INPUT_POST, 'request_id', FILTER_VALIDATE_INT);
$user_id_to_update = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
$action = $_POST['action']; // 'completed' or 'rejected'

if ($request_id && $user_id_to_update && in_array($action, ['completed', 'rejected'])) {
    try {
        $pdo->beginTransaction();

        if ($action === 'completed') {
            // --- منطق الموافقة ---

            // 1. جلب كل المقررات الجديدة والمواد التي طلبها الطالب
            $stmt_new = $pdo->prepare("SELECT requested_offering_id, requested_subjects FROM enrollment_change_requests WHERE user_id = ? AND status = 'pending'");
            $stmt_new->execute([$user_id_to_update]);
            $new_requests = $stmt_new->fetchAll(PDO::FETCH_ASSOC);

            if ($new_requests) {
                // جلب معرف السنة الجامعية الحالية
                $stmt_year = $pdo->query("SELECT id FROM academic_years WHERE is_current = 1 LIMIT 1");
                $current_year_id = $stmt_year->fetchColumn() ?: 1;

                // 2. حذف جميع التسجيلات الدراسية القديمة للطالب في هذا الموسم فقط (للحفاظ على الأرشيف القديم)
                $stmt_delete_old = $pdo->prepare("DELETE FROM student_enrollments WHERE user_id = ? AND academic_year_id = ?");
                $stmt_delete_old->execute([$user_id_to_update, $current_year_id]);

                // 3. إضافة التسجيلات الجديدة للطالب مع المواد المحددة (selected_subjects)
                $stmt_insert_new = $pdo->prepare("INSERT INTO student_enrollments (user_id, offering_id, academic_year_id, selected_subjects) VALUES (?, ?, ?, ?)");
                foreach ($new_requests as $req) {
                    $stmt_insert_new->execute([
                        $user_id_to_update, 
                        $req['requested_offering_id'], 
                        $current_year_id, 
                        $req['requested_subjects']
                    ]);
                }
            }
            
            // 4. تحديث حالة جميع الطلبات المعلقة لهذا الطالب إلى "مكتمل"
            $stmt_update = $pdo->prepare("UPDATE enrollment_change_requests SET status = 'completed' WHERE user_id = ? AND status = 'pending'");
            $stmt_update->execute([$user_id_to_update]);
            
            // 5. إرسال إشعار الموافقة
            $message = "تهانينا! تمت الموافقة على طلبك لتغيير المقرر الدراسي واعتماد المواد التي اخترتها.";
            $_SESSION['request_status_msg'] = "تمت الموافقة على الطلب وتحديث تسجيل الطالب بنجاح.";

        } else { // 'rejected'
            // --- منطق الرفض ---
            $stmt_update = $pdo->prepare("UPDATE enrollment_change_requests SET status = 'rejected' WHERE user_id = ? AND status = 'pending'");
            $stmt_update->execute([$user_id_to_update]);
            $message = "نأسف، تم رفض طلبك لتغيير المقرر الدراسي.";
            $_SESSION['request_status_msg'] = "تم رفض الطلب بنجاح.";
        }

        // إدراج الإشعار في قاعدة البيانات للطالب
        $stmt_notify = $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
        $stmt_notify->execute([$user_id_to_update, $message, 'student/profil.php']);
        
        $pdo->commit();

    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['request_status_msg'] = "حدث خطأ: " . $e->getMessage();
    }
}

header("Location: index.php");
exit();
?>