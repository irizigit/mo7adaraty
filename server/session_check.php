<?php
// 1. ابدأ الجلسة أولاً وقبل كل شيء.
session_start();

// 2. ثم قم بإعداد تقارير الأخطاء (هذه الأسطر مفيدة جداً للتصحيح)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 3. تهيئة بيانات الجلسة الافتراضية
// هذه القيم تمثل الحالة عندما لا يكون المستخدم مسجلاً للدخول بعد.
$session_data = [
    'isLoggedIn' => false, // القيمة الافتراضية يجب أن تكون false
    'username' => null,
    'isAdmin' => false,    // القيمة الافتراضية يجب أن تكون false
    'userId' => null
];

// 4. التحقق مما إذا كان المستخدم مسجلاً للدخول (أي، هل توجد بيانات في الجلسة؟)
if (isset($_SESSION['user_id']) && isset($_SESSION['username'])) {
    // إذا كان المستخدم مسجلاً للدخول، قم بتحديث بيانات الجلسة
    $session_data['isLoggedIn'] = true;
    $session_data['username'] = $_SESSION['username'];
    $session_data['userId'] = $_SESSION['user_id'];

    // التحقق من صلاحية المسؤول المخزنة في الجلسة
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
        $session_data['isAdmin'] = true;
    }
}

// 5. طباعة بيانات الجلسة ككود JavaScript.
// هذا الكود سيُنفّذ في متصفح العميل ويجعل `window.sessionData` متاحاً.
echo '<script>';
echo 'window.sessionData = ' . json_encode($session_data) . ';';
echo '</script>';
?>