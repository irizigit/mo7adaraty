<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json');

// التأكد من أن المستخدم مسؤول
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    echo json_encode(['success' => false, 'message' => 'غير مصرح لك بالوصول.']);
    exit;
}

$categories_file = '../data/categories.json';
$lectures_file = '../data/lectures.json'; // نحتاجه عند حذف تصنيف للتحقق

$input_data = file_get_contents('php://input');
$data = json_decode($input_data, true);

$action = $data['action'] ?? ''; // add, edit, delete
$category_id = $data['id'] ?? '';
$category_name = $data['name'] ?? '';

// قراءة التصنيفات الحالية
$categories = [];
if (file_exists($categories_file) && filesize($categories_file) > 0) {
    $categories_json = file_get_contents($categories_file);
    $categories = json_decode($categories_json, true);
    if ($categories === null) $categories = [];
}

switch ($action) {
    case 'add':
        if (empty($category_name)) {
            echo json_encode(['success' => false, 'message' => 'اسم التصنيف لا يمكن أن يكون فارغاً.']);
            exit;
        }
        // التحقق من تكرار اسم التصنيف
        foreach ($categories as $cat) {
            if ($cat['name'] === $category_name) {
                echo json_encode(['success' => false, 'message' => 'هذا التصنيف موجود بالفعل.']);
                exit;
            }
        }
        $new_id = uniqid('cat_');
        $categories[] = ['id' => $new_id, 'name' => $category_name];
        break;

    case 'edit':
        if (empty($category_id) || empty($category_name)) {
            echo json_encode(['success' => false, 'message' => 'بيانات التعديل غير مكتملة.']);
            exit;
        }
        $found = false;
        foreach ($categories as $index => $cat) {
            if ($cat['id'] === $category_id) {
                // التحقق من تكرار الاسم بعد التعديل (مع استثناء التصنيف الحالي)
                foreach ($categories as $other_cat) {
                    if ($other_cat['id'] !== $category_id && $other_cat['name'] === $category_name) {
                        echo json_encode(['success' => false, 'message' => 'اسم التصنيف هذا موجود بالفعل لتصنيف آخر.']);
                        exit;
                    }
                }
                $categories[$index]['name'] = $category_name;
                $found = true;
                break;
            }
        }
        if (!$found) {
            echo json_encode(['success' => false, 'message' => 'التصنيف المراد تعديله لم يتم العثور عليه.']);
            exit;
        }
        break;

    case 'delete':
        if (empty($category_id)) {
            echo json_encode(['success' => false, 'message' => 'معرف التصنيف مفقود.']);
            exit;
        }
        // التحقق مما إذا كان هناك أي محاضرات تستخدم هذا التصنيف
        $lectures_data = [];
        if (file_exists($lectures_file) && filesize($lectures_file) > 0) {
            $lectures_json = file_get_contents($lectures_file);
            $lectures_data = json_decode($lectures_json, true);
            if ($lectures_data === null) $lectures_data = [];
        }
        foreach ($lectures_data as $lecture) {
            if ($lecture['category_id'] === $category_id) {
                echo json_encode(['success' => false, 'message' => 'لا يمكن حذف هذا التصنيف لأنه يحتوي على محاضرات مرتبطة به.']);
                exit;
            }
        }

        $categories = array_filter($categories, function($cat) use ($category_id) {
            return $cat['id'] !== $category_id;
        });
        // إعادة ترتيب الفهارس بعد الحذف
        $categories = array_values($categories);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'إجراء غير صالح.']);
        exit;
}

// حفظ التصنيفات المحدثة
if (file_put_contents($categories_file, json_encode($categories, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    echo json_encode(['success' => true, 'message' => 'تمت العملية بنجاح!']);
} else {
    echo json_encode(['success' => false, 'message' => 'فشل حفظ التصنيفات.']);
}
?>