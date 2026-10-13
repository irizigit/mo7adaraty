<?php
require_once '../../config/db_connexion.php';

/*
 * نقطة النهاية (Endpoint) لطلبات AJAX لنموذج إضافة وتعديل المحاضرات
 * تعتمد حصراً على الموسم الدراسي المفعل حالياً
 */

header('Content-Type: application/json; charset=utf-8');

$action =$_POST['action'] ?? '';

try {
    // 1. جلب المقررات المتاحة للشعبة المحددة ضمن الموسم المفعل حالياً
    if ($action === 'get_offerings' && !empty($_POST['division_id'])) {$division_id = filter_input(INPUT_POST, 'division_id', FILTER_VALIDATE_INT);
        
        $stmt_year =$pdo->query("SELECT id FROM academic_years WHERE is_current = 1 LIMIT 1");
        $active_year_id =$stmt_year->fetchColumn() ?: 1;

        $stmt =$pdo->prepare("
            SELECT
                co.offering_id, c.class_name, g.group_name, t.track_name
            FROM course_offerings co
            JOIN classes c ON co.class_id = c.class_id
            LEFT JOIN `groups` g ON co.group_id = g.group_id
            LEFT JOIN tracks t ON co.track_id = t.track_id
            WHERE co.division_id = ? 
              AND co.academic_year_id = ? 
              AND (co.status = 1 OR co.status = 'active')
            ORDER BY c.class_id, g.group_id
        ");
        $stmt->execute([$division_id,$active_year_id]);
        $offerings =$stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($offerings);

    // 2. جلب المواد المتاحة للمقرر المختار
    } elseif ($action === 'get_subjects' && !empty($_POST['offering_id'])) {$offering_id = filter_input(INPUT_POST, 'offering_id', FILTER_VALIDATE_INT);
        $stmt =$pdo->prepare("
            SELECT os.offering_subject_id, s.subject_name, p.professor_name
            FROM offering_subjects os
            JOIN subjects s ON os.subject_id = s.subject_id
            LEFT JOIN professors p ON os.professor_id = p.professor_id
            WHERE os.offering_id = ?
            ORDER BY s.subject_name
        ");
        $stmt->execute([$offering_id]);
        $subjects =$stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($subjects);

    } else {
        echo json_encode([]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}