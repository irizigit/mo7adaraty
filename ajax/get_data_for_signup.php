<?php
// نقطة نهاية عامة (Public Endpoint) مخصصة لصفحة إنشاء الحساب
// تم تأمينها لتجلب فقط البيانات المرتبطة بالسنة الجامعية المفعلة والمقررات النشطة
require_once '../config/db_connexion.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? '';

try {
    // 1. تحديد "مصدر الحقيقة" (السنة المفعلة)
    $stmt_year = $pdo->query("SELECT id FROM academic_years WHERE is_current = 1 LIMIT 1");
    $current_year_id = $stmt_year->fetchColumn();

    if (!$current_year_id) {
        echo json_encode(['error' => 'لا توجد سنة جامعية مفعلة حالياً في النظام.']);
        exit;
    }

    // 2. جلب الفصول المتاحة فقط (المرتبطة بمقررات مفعلة هذا العام)
    if ($action === 'get_classes') {
        $division_id = filter_input(INPUT_POST, 'division_id', FILTER_VALIDATE_INT);
        
        $sql = "SELECT DISTINCT c.class_id, c.class_name 
                FROM classes c
                INNER JOIN course_offerings co ON c.class_id = co.class_id
                WHERE co.academic_year_id = ? AND co.status = 1"; 
        $params = [$current_year_id];

        if ($division_id) {
            $sql .= " AND co.division_id = ?";
            $params[] = $division_id;
        }

        $sql .= " ORDER BY c.class_id ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // 3. جلب المسارات المتاحة فقط (المرتبطة بمقررات مفعلة هذا العام في نفس الفصل)
    if ($action === 'get_tracks') {
        $division_id = filter_input(INPUT_POST, 'division_id', FILTER_VALIDATE_INT);
        $class_id = filter_input(INPUT_POST, 'class_id', FILTER_VALIDATE_INT);

        if ($division_id && $class_id) {
            // استعلام ذكي: نجلب المسار فقط إذا كان يمتلك مقررات مفعلة في هذا الفصل وهذه الشعبة لهذه السنة
            $stmt = $pdo->prepare("
                SELECT DISTINCT t.track_id, t.track_name 
                FROM tracks t
                INNER JOIN course_offerings co ON t.track_id = co.track_id
                WHERE t.division_id = ? AND co.class_id = ? AND co.academic_year_id = ? AND co.status = 1
                ORDER BY t.track_name ASC
            ");
            $stmt->execute([$division_id, $class_id, $current_year_id]);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        } else {
            echo json_encode(['error' => 'معرف الشعبة أو الفصل غير صالح']);
        }
        exit;
    }

    // 4. جلب المقررات المتاحة بدقة (المفعلة فقط وفي نفس السنة)
    if ($action === 'get_offerings') {
        $division_id = filter_input(INPUT_POST, 'division_id', FILTER_VALIDATE_INT);
        $class_id = filter_input(INPUT_POST, 'class_id', FILTER_VALIDATE_INT);
        $track_id = filter_input(INPUT_POST, 'track_id', FILTER_VALIDATE_INT);

        if ($division_id && $class_id) {
            $sql = "SELECT co.offering_id, g.group_name 
                    FROM course_offerings co
                    LEFT JOIN `groups` g ON co.group_id = g.group_id
                    WHERE co.division_id = ? AND co.class_id = ? AND co.academic_year_id = ? AND co.status = 1";
            $params = [$division_id, $class_id, $current_year_id];

            if ($track_id) {
                $sql .= " AND co.track_id = ?";
                $params[] = $track_id;
            } else {
                $sql .= " AND (co.track_id IS NULL OR co.track_id = 0)";
            }

            $sql .= " ORDER BY g.group_name ASC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $offerings = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($offerings)) {
                echo json_encode(['error' => 'لا توجد مقررات متاحة لهذا الاختيار في الموسم الحالي.']);
            } else {
                echo json_encode($offerings);
            }
        } else {
            echo json_encode(['error' => 'بيانات ناقصة']);
        }
        exit;
    }

    echo json_encode(['error' => 'إجراء غير معروف']);

} catch (PDOException $e) {
    // إخفاء تفاصيل الخطأ عن الزوار لأسباب أمنية وتسجيله في السيرفر
    error_log("Signup AJAX Error: " . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'حدث خطأ في قاعدة البيانات.']);
}
?>