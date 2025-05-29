
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


session_start();
header('Content-Type: application/json'); // إرسال استجابة JSON

// المسار إلى ملف users.json
$users_file = '../data/users.json';

// استقبال البيانات المرسلة من JavaScript كـ JSON
$input_data = file_get_contents('php://input');
$data = json_decode($input_data, true);

// التحقق من وجود البيانات المطلوبة
if (!isset($data['username']) || !isset($data['password'])) {
    echo json_encode(['success' => false, 'message' => 'الرجاء توفير اسم المستخدم وكلمة المرور.']);
    exit;
}

$username = $data['username'];
$password = $data['password'];

// قراءة بيانات المستخدمين الحاليين من ملف JSON
if (file_exists($users_file) && filesize($users_file) > 0) {
    $users_json = file_get_contents($users_file);
    $users = json_decode($users_json, true);
    if ($users === null) {
        $users = []; // في حال كان ملف JSON تالفاً
    }
} else {
    echo json_encode(['success' => false, 'message' => 'لا توجد حسابات مسجلة بعد.']);
    exit;
}

$authenticated_user = null;
foreach ($users as $user) {
    if ($user['username'] === $username) {
        // التحقق من كلمة المرور المشفرة
        if (password_verify($password, $user['password_hash'])) {
            $authenticated_user = $user;
            break; // تم العثور على المستخدم وتطابقت كلمة المرور
        }
    }
}

if ($authenticated_user) {
    // تسجيل بيانات المستخدم في الجلسة
    $_SESSION['user_id'] = $authenticated_user['id'];
    $_SESSION['username'] = $authenticated_user['username'];
    $_SESSION['is_admin'] = $authenticated_user['is_admin'];

    echo json_encode([
        'success' => true,
        'message' => 'تم تسجيل الدخول بنجاح!',
        'is_admin' => $authenticated_user['is_admin'] // إرسال حالة الإدارة لتوجيه الواجهة الأمامية
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'اسم المستخدم أو كلمة المرور غير صحيحة.']);
}
?>