<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start(); // ابدأ الجلسة
header('Content-Type: application/json'); // إرسال استجابة JSON

// المسار إلى ملف users.json (مهم جداً: تأكد من المسار الصحيح)
$users_file = '../data/users.json';

// استقبال البيانات المرسلة من JavaScript كـ JSON
$input_data = file_get_contents('php://input');
$data = json_decode($input_data, true);

// التحقق من وجود البيانات المطلوبة
if (!isset($data['username']) || !isset($data['email']) || !isset($data['password'])) {
    echo json_encode(['success' => false, 'message' => 'الرجاء توفير جميع البيانات المطلوبة.']);
    exit;
}

$username = $data['username'];
$email = $data['email'];
$password = $data['password'];

// قراءة بيانات المستخدمين الحاليين من ملف JSON
// التحقق مما إذا كان الملف موجوداً وغير فارغ
if (file_exists($users_file) && filesize($users_file) > 0) {
    $users_json = file_get_contents($users_file);
    $users = json_decode($users_json, true);
    if ($users === null) { // في حال كان ملف JSON تالفاً
        $users = [];
    }
} else {
    $users = []; // إذا كان الملف غير موجود أو فارغ، نبدأ بمصفوفة فارغة
}

// التحقق مما إذا كان اسم المستخدم أو البريد الإلكتروني موجوداً بالفعل
foreach ($users as $user) {
    if ($user['username'] === $username) {
        echo json_encode(['success' => false, 'message' => 'اسم المستخدم هذا موجود بالفعل. الرجاء اختيار اسم آخر.']);
        exit;
    }
    if ($user['email'] === $email) {
        echo json_encode(['success' => false, 'message' => 'هذا البريد الإلكتروني مسجل بالفعل.']);
        exit;
    }
}

// تشفير كلمة المرور (مهم جداً للأمان)
// PASSWORD_BCRYPT هو خوارزمية تشفير آمنة وموصى بها
$hashed_password = password_hash($password, PASSWORD_BCRYPT);

// توليد ID فريد للمستخدم الجديد
// يمكن استخدام uniqid() أو دالة لإنشاء UUID
$new_user_id = uniqid('user_');

// بناء بيانات المستخدم الجديد
$new_user = [
    'id' => $new_user_id,
    'username' => $username,
    'email' => $email,
    'password_hash' => $hashed_password,
    'is_admin' => false // المستخدم العادي لا يكون مديراً افتراضياً
];

// إضافة المستخدم الجديد إلى مصفوفة المستخدمين
$users[] = $new_user;

// حفظ البيانات المحدثة مرة أخرى إلى ملف JSON
if (file_put_contents($users_file, json_encode($users, JSON_PRETTY_PRINT))) {
    echo json_encode(['success' => true, 'message' => 'تم التسجيل بنجاح! سيتم توجيهك لصفحة تسجيل الدخول.']);
} else {
    echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء حفظ بيانات المستخدم. الرجاء المحاولة مرة أخرى.']);
}
?>