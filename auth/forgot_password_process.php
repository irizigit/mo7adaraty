<?php
session_start();
require_once '../config/db_connexion.php';

// 1. إعداد نظام اللغات
$allowed_langs = ['ar', 'en', 'fr'];
$lang_code = (isset($_SESSION['lang']) && in_array($_SESSION['lang'], $allowed_langs)) ? $_SESSION['lang'] : 'fr';
$lang = require "../lang/{$lang_code}.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 2. استقبال البيانات وتطهيرها
    $user_id = filter_input(INPUT_POST, 'user_id', FILTER_SANITIZE_NUMBER_INT);
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['registered_email'] ?? '');
    $contact = trim($_POST['contact_info'] ?? '');

    // 3. التحقق من ملء الحقول
    if (empty($email) || empty($contact) || empty($user_id) || empty($username)) {
        $_SESSION['reset_error'] = $lang['err_required_fields'] ?? 'يرجى ملء جميع الحقول المطلوبة.';
        header("Location: ../forgot_password.php");
        exit();
    }

    // 4. التحقق من صحة البريد الإلكتروني
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['reset_error'] = $lang['err_invalid_email'] ?? 'يرجى إدخال بريد إلكتروني صالح.';
        header("Location: ../forgot_password.php");
        exit();
    }

    try {
        $db = isset($pdo) ? $pdo : (isset($conn) ? $conn : null);
        
        if ($db) {
            // 5. التحقق من مطابقة البريد الإلكتروني للمستخدم في قاعدة البيانات
            $stmt = $db->prepare("SELECT email FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();

            if (!$user || $user['email'] !== $email) {
                $_SESSION['reset_error'] = $lang['err_email_mismatch'] ?? 'البريد الإلكتروني المدخل لا يطابق البريد المسجل لهذا الحساب.';
                header("Location: ../forgot_password.php");
                exit();
            }

            // 6. التحقق من وجود طلب سابق قيد الانتظار (لمنع التكرار)
            $stmt = $db->prepare("SELECT request_id FROM password_reset_requests WHERE user_id = ? AND status = 'pending'");
            $stmt->execute([$user_id]);
            if ($stmt->fetch()) {
                $_SESSION['reset_error'] = $lang['err_pending_request'] ?? 'لديك طلب سابق قيد الانتظار، يرجى انتظار تواصل الإدارة.';
                header("Location: ../forgot_password.php");
                exit();
            }

            // 7. [التعديل الجراحي]: تسجيل طلب الاسترجاع باسم المستخدم (username) بدلاً من الإيميل ليتوافق مع قاعدة البيانات
            $stmt = $db->prepare("INSERT INTO password_reset_requests (user_id, username, contact_info, request_date, status) VALUES (?, ?, ?, NOW(), 'pending')");
            $stmt->execute([$user_id, $username, $contact]);

            // 8. نجاح العملية
            $_SESSION['reset_success'] = $lang['success_request_sent'] ?? 'تم إرسال طلبك بنجاح! ستتواصل معك الإدارة قريباً.';
            header("Location: ../forgot_password.php");
            exit();

        } else {
            throw new Exception("Database connection failed");
        }
    } catch (Exception $e) {
        // يمكنك تفعيل السطر التالي مؤقتاً لمعرفة الخطأ بدقة إذا استمرت المشكلة
        // die($e->getMessage()); 
        $_SESSION['reset_error'] = $lang['err_database_error'] ?? 'حدث خطأ في النظام، يرجى المحاولة لاحقاً.';
        header("Location: ../forgot_password.php");
        exit();
    }
} else {
    header("Location: ../forgot_password.php");
    exit();
}