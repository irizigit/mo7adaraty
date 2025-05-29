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

$users_file = '../data/users.json';

$input_data = file_get_contents('php://input');
$data = json_decode($input_data, true);

$action = $data['action'] ?? '';
$user_id_to_process = $data['id'] ?? '';

// قراءة المستخدمين الحاليين
$users = [];
if (file_exists($users_file) && filesize($users_file) > 0) {
    $users_json = file_get_contents($users_file);
    $users = json_decode($users_json, true);
    if ($users === null) $users = [];
}

switch ($action) {
    case 'delete':
        if (empty($user_id_to_process)) {
            echo json_encode(['success' => false, 'message' => 'معرف المستخدم مفقود.']);
            exit;
        }

        // منع المسؤول من حذف حسابه الخاص
        if ($user_id_to_process === $_SESSION['user_id']) {
            echo json_encode(['success' => false, 'message' => 'لا يمكنك حذف حسابك الخاص.']);
            exit;
        }

        $users = array_filter($users, function($user) use ($user_id_to_process) {
            return $user['id'] !== $user_id_to_process;
        });
        // إعادة ترتيب الفهارس بعد الحذف
        $users = array_values($users);
        break;

    // يمكنك إضافة 'edit_permissions' هنا لاحقاً
    // case 'edit_permissions':
    //     // منطق تعديل الصلاحيات
    //     break;

    default:
        echo json_encode(['success' => false, 'message' => 'إجراء غير صالح.']);
        exit;
}

// حفظ المستخدمين المحدثين
if (file_put_contents($users_file, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    echo json_encode(['success' => true, 'message' => 'تمت العملية بنجاح!']);
} else {
    echo json_encode(['success' => false, 'message' => 'فشل حفظ المستخدمين.']);
}
?>