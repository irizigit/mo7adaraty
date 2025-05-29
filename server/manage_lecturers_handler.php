<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    echo json_encode(['success' => false, 'message' => 'غير مصرح لك بالوصول.']);
    exit;
}

$lecturers_file = '../data/lecturers.json';
$lectures_file = '../data/lectures.json'; // للتحقق عند الحذف

$input_data = file_get_contents('php://input');
$data = json_decode($input_data, true);

$action = $data['action'] ?? ''; // add, edit, delete
$lecturer_id = $data['id'] ?? '';
$lecturer_name = $data['name'] ?? '';
$lecturer_email = $data['email'] ?? '';
$lecturer_bio = $data['bio'] ?? '';
$taught_semesters = $data['taught_semesters'] ?? []; // مصفوفة من ID الفصول

// قراءة الأساتذة الحاليين
$lecturers = [];
if (file_exists($lecturers_file) && filesize($lecturers_file) > 0) {
    $lecturers_json = file_get_contents($lecturers_file);
    $lecturers = json_decode($lecturers_json, true);
    if ($lecturers === null) $lecturers = [];
}

switch ($action) {
    case 'add':
        if (empty($lecturer_name)) {
            echo json_encode(['success' => false, 'message' => 'اسم الأستاذ لا يمكن أن يكون فارغاً.']);
            exit;
        }
        // التحقق من تكرار اسم الأستاذ
        foreach ($lecturers as $lec) {
            if ($lec['name'] === $lecturer_name) {
                echo json_encode(['success' => false, 'message' => 'هذا الأستاذ موجود بالفعل.']);
                exit;
            }
        }
        $new_id = uniqid('lecturer_');
        $lecturers[] = [
            'id' => $new_id,
            'name' => $lecturer_name,
            'email' => $lecturer_email,
            'bio' => $lecturer_bio,
            'taught_semesters' => $taught_semesters
        ];
        break;

    case 'edit':
        if (empty($lecturer_id) || empty($lecturer_name)) {
            echo json_encode(['success' => false, 'message' => 'بيانات التعديل غير مكتملة.']);
            exit;
        }
        $found = false;
        foreach ($lecturers as $index => $lec) {
            if ($lec['id'] === $lecturer_id) {
                // التحقق من تكرار الاسم بعد التعديل (مع استثناء الأستاذ الحالي)
                foreach ($lecturers as $other_lec) {
                    if ($other_lec['id'] !== $lecturer_id && $other_lec['name'] === $lecturer_name) {
                        echo json_encode(['success' => false, 'message' => 'اسم الأستاذ هذا موجود بالفعل لأستاذ آخر.']);
                        exit;
                    }
                }
                $lecturers[$index]['name'] = $lecturer_name;
                $lecturers[$index]['email'] = $lecturer_email;
                $lecturers[$index]['bio'] = $lecturer_bio;
                $lecturers[$index]['taught_semesters'] = $taught_semesters;
                $found = true;
                break;
            }
        }
        if (!$found) {
            echo json_encode(['success' => false, 'message' => 'الأستاذ المراد تعديله لم يتم العثور عليه.']);
            exit;
        }
        break;

    case 'delete':
        if (empty($lecturer_id)) {
            echo json_encode(['success' => false, 'message' => 'معرف الأستاذ مفقود.']);
            exit;
        }
        // التحقق مما إذا كان هناك أي محاضرات مرتبطة بهذا الأستاذ
        $lectures_data = [];
        if (file_exists($lectures_file) && filesize($lectures_file) > 0) {
            $lectures_json = file_get_contents($lectures_file);
            $lectures_data = json_decode($lectures_json, true);
            if ($lectures_data === null) $lectures_data = [];
        }
        foreach ($lectures_data as $lecture) {
            if ($lecture['lecturer_id'] === $lecturer_id) {
                echo json_encode(['success' => false, 'message' => 'لا يمكن حذف هذا الأستاذ لأنه مرتبط بمحاضرات موجودة.']);
                exit;
            }
        }

        $lecturers = array_filter($lecturers, function($lec) use ($lecturer_id) {
            return $lec['id'] !== $lecturer_id;
        });
        $lecturers = array_values($lecturers); // إعادة ترتيب الفهارس
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'إجراء غير صالح.']);
        exit;
}

// حفظ الأساتذة المحدثين
if (file_put_contents($lecturers_file, json_encode($lecturers, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    echo json_encode(['success' => true, 'message' => 'تمت العملية بنجاح!']);
} else {
    echo json_encode(['success' => false, 'message' => 'فشل حفظ بيانات الأساتذة.']);
}
?>