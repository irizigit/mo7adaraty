<?php
require_once '../../config/db_connexion.php';

header('Content-Type: application/json; charset=utf-8');

$action =$_POST['action'] ?? '';

try {
    // جلب معرف الموسم المفعل حالياً
    $stmt_year =$pdo->query("SELECT id FROM academic_years WHERE is_current = 1 LIMIT 1");
    $active_year_id =$stmt_year->fetchColumn() ?: 1;

    // 1. جلب الفصول المتاحة للشعبة المحددة
    if ($action === 'get_classes' && !empty($_POST['division_id'])) {$division_id = filter_input(INPUT_POST, 'division_id', FILTER_VALIDATE_INT);

        $stmt =$pdo->prepare("
            SELECT DISTINCT c.class_id, c.class_name
            FROM classes c
            JOIN course_offerings co ON c.class_id = co.class_id
            WHERE co.division_id = ? 
              AND co.academic_year_id = ? 
              AND (co.status = 1 OR co.status = 'active')
            ORDER BY c.class_id ASC
        ");
        $stmt->execute([$division_id,$active_year_id]);
        $classes =$stmt->fetchAll(PDO::FETCH_ASSOC);

        // خطة بديلة: إذا لم تكن هناك فصول مسجلة في جدول المقررات يتم جلب الفصول العامة
        if (empty($classes)) {
            $stmt_fallback =$pdo->query("SELECT class_id, class_name FROM classes ORDER BY class_id ASC");
            $classes =$stmt_fallback->fetchAll(PDO::FETCH_ASSOC);
        }

        echo json_encode($classes);
        exit();

    // 2. جلب الأفواج والمقررات التابعة للشعبة والفصل
    } elseif ($action === 'get_offerings' && !empty($_POST['division_id']) && !empty($_POST['class_id'])) {
        $division_id = filter_input(INPUT_POST, 'division_id', FILTER_VALIDATE_INT);$class_id = filter_input(INPUT_POST, 'class_id', FILTER_VALIDATE_INT);
        
        $stmt =$pdo->prepare("
            SELECT co.offering_id, c.class_name, g.group_name, t.track_name
            FROM course_offerings co
            JOIN classes c ON co.class_id = c.class_id
            LEFT JOIN `groups` g ON co.group_id = g.group_id
            LEFT JOIN tracks t ON co.track_id = t.track_id
            WHERE co.division_id = ? 
              AND co.class_id = ? 
              AND co.academic_year_id = ? 
              AND (co.status = 1 OR co.status = 'active')
            ORDER BY c.class_id, g.group_id
        ");
        $stmt->execute([$division_id, $class_id,$active_year_id]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit();

    // 3. جلب المواد المسندة للفوج
    } elseif ($action === 'get_subjects' && !empty($_POST['offering_id'])) {$offering_id = filter_input(INPUT_POST, 'offering_id', FILTER_VALIDATE_INT);
        
        $stmt =$pdo->prepare("
            SELECT os.offering_subject_id, s.subject_name
            FROM offering_subjects os
            JOIN subjects s ON os.subject_id = s.subject_id
            WHERE os.offering_id = ?
            ORDER BY s.subject_name
        ");
        $stmt->execute([$offering_id]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit();

    } else {
        echo json_encode([]);
        exit();
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
    exit();
}