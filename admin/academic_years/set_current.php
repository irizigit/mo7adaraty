<?php
ob_start();
session_start();
require_once '../../config/db_connexion.php';

ob_clean();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'غير مصرح لك.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $year_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    if (!$year_id) {
        echo json_encode(['success' => false, 'message' => 'معرف العام غير صالح.']);
        exit();
    }

    try {
        $pdo->beginTransaction();

        // 1. جلب معرف السنة الحالية قبل التغيير لنسخ المقررات منها
        $stmt_old_year = $pdo->query("SELECT id FROM academic_years WHERE is_current = 1 LIMIT 1");
        $old_year_id = $stmt_old_year->fetchColumn();

        // 2. التحقق مما إذا كانت السنة الجديدة تحتوي أصلاً على مقررات (لتفادي التكرار)
        $stmt_check_offerings = $pdo->prepare("SELECT COUNT(*) FROM course_offerings WHERE academic_year_id = ?");
        $stmt_check_offerings->execute([$year_id]);
        $has_offerings = $stmt_check_offerings->fetchColumn();

        // 3. تصفير جميع الأعوام لضمان وجود عام حالي واحد فقط
        $stmt_reset = $pdo->prepare("UPDATE academic_years SET is_current = 0");
        $stmt_reset->execute();

        // 4. تعيين العام المختار كعام حالي
        $stmt_set = $pdo->prepare("UPDATE academic_years SET is_current = 1 WHERE id = ?");
        $stmt_set->execute([$year_id]);

        // 5. الاستنساخ التلقائي للمقررات والمواد المرتبطة بها إلى السنة الجديدة
        if ($old_year_id && $old_year_id != $year_id && $has_offerings == 0) {
            // جلب جميع مقررات السنة السابقة
            $stmt_get_offerings = $pdo->prepare("SELECT * FROM course_offerings WHERE academic_year_id = ?");
            $stmt_get_offerings->execute([$old_year_id]);
            $old_offerings = $stmt_get_offerings->fetchAll(PDO::FETCH_ASSOC);

            $stmt_insert_offering = $pdo->prepare("INSERT INTO course_offerings (academic_year_id, division_id, class_id, group_id, track_id, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_insert_subject = $pdo->prepare("INSERT INTO offering_subjects (offering_id, subject_id, professor_id) VALUES (?, ?, ?)");

            foreach ($old_offerings as $offering) {
                // إدراج المقرر (الفوج/الفصل/المسار) في السنة الجديدة
                $stmt_insert_offering->execute([
                    $year_id,
                    $offering['division_id'],
                    $offering['class_id'],
                    $offering['group_id'],
                    $offering['track_id'],
                    $offering['status']
                ]);
                $new_offering_id = $pdo->lastInsertId();

                // جلب المواد والأساتذة المرتبطين بهذا المقرر من السنة السابقة
                $stmt_get_subjects = $pdo->prepare("SELECT * FROM offering_subjects WHERE offering_id = ?");
                $stmt_get_subjects->execute([$offering['offering_id']]);
                $old_subjects = $stmt_get_subjects->fetchAll(PDO::FETCH_ASSOC);

                // إدراج المواد والأساتذة للمقرر الجديد
                foreach ($old_subjects as $subject) {
                    $stmt_insert_subject->execute([
                        $new_offering_id,
                        $subject['subject_id'],
                        $subject['professor_id']
                    ]);
                }
            }
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'تم تعيين العام الحالي وتهيئة المقررات بنجاح.']);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()]);
    }
    exit();
}
?>