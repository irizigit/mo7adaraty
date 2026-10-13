<?php
// الخطوة 1: بدء الجلسة (Session) لتخزين معلومات المستخدم وصلاحياته
session_start();

// الخطوة 2: استدعاء ملف الاتصال بقاعدة البيانات
require_once '../config/db_connexion.php';

// إعداد نظام تعدد اللغات (i18n)
$lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'ar';
$valid_langs = ['ar', 'en', 'fr'];
if (!in_array($lang, $valid_langs)) { $lang = 'ar'; }
$translations = require_once "../lang/{$lang}.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $login_identifier = trim($_POST['login_identifier']); 
    $password = $_POST['password'];

    if (empty($login_identifier) || empty($password)) {
        $error_msg = urlencode($translations['err_required_fields']);
        header("Location: ../login.php?error=" . $error_msg);
        exit();
    }

    try {
        // الخطوة 3: جلب السنة الجامعية الحالية (الارتباط الموسمي)
        $stmt_year = $pdo->query("SELECT id, year_label FROM academic_years WHERE is_current = 1 LIMIT 1");
        $current_year = $stmt_year->fetch();
        $current_year_id = $current_year ? $current_year['id'] : null;

        // الخطوة 4: البحث عن المستخدم وجلب بيانات الإشراف (Managed Scopes)
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$login_identifier, $login_identifier]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            
            // أ. تخزين البيانات الأساسية في الجلسة
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role']; // سوبر أدمن أو طالب
            $_SESSION['current_year_id'] = $current_year_id;

            // ب. نظام "ازدواجية الدور": جلب صلاحيات النطاق والصفحات إذا كان مشرفاً
            // نتحقق من وجود نطاق إشراف مخصص
            if ($user['managed_scope_type'] !== null) {
                $_SESSION['is_sub_admin'] = true;
                $_SESSION['managed_scope'] = [
                    'type'        => $user['managed_scope_type'],
                    'division_id' => $user['managed_division_id'],
                    'class_id'    => $user['managed_class_id'],
                    'track_id'    => $user['managed_track_id'],
                    'group_id'    => $user['managed_group_id']
                ];

                // جلب قائمة الصفحات المسموحة (Individual Checkboxes) للسنة الحالية فقط
                $stmt_perms = $pdo->prepare("SELECT page_slug FROM admin_permissions WHERE user_id = ? AND academic_year_id = ?");
                $stmt_perms->execute([$user['user_id'], $current_year_id]);
                $_SESSION['admin_permissions'] = $stmt_perms->fetchAll(PDO::FETCH_COLUMN);
            } else {
                $_SESSION['is_sub_admin'] = false;
                $_SESSION['admin_permissions'] = [];
            }

            // ج. تسجيل نشاط الدخول (Activity Logs)
            $log_stmt = $pdo->prepare("INSERT INTO system_logs (user_id, action_type, details, ip_address) VALUES (?, 'login', ?, ?)");
            $details = "Logged in from " . ($user['managed_scope_type'] ? "Admin Panel" : "Student View");
            $log_stmt->execute([$user['user_id'], $details, $_SERVER['REMOTE_ADDR']]);

            // د. تفعيل ميزة "تذكرني"
            if (isset($_POST['remember_me'])) {
                setcookie('saved_login', $login_identifier, time() + (86400 * 30), "/");
            }

            // هـ. التوجيه الذكي (نقطة الهبوط): الأولوية للوحة التحكم للمشرفين
            // إذا كان سوبر أدمن أو مشرفاً فرعياً لديه صلاحيات للسنة الحالية
            if ($user['role'] === 'admin' || ($_SESSION['is_sub_admin'] && !empty($_SESSION['admin_permissions']))) {
                header("Location: ../admin/index.php");
            } else {
                header("Location: ../student/profil.php");
            }
            exit();

        } else {
            $error_msg = urlencode($translations['err_invalid_credentials']);
            header("Location: ../login.php?error=" . $error_msg);
            exit();
        }

    } catch (PDOException $e) {
        $error_msg = urlencode($translations['err_database_error']);
        header("Location: ../login.php?error=" . $error_msg);
        exit();
    }

} else {
    header("Location: ../index.php");
    exit();
}