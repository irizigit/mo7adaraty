<?php
session_start();
require_once 'config/db_connexion.php';
require_once 'config/mail.php';
require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// معلومات التواصل المباشر مع المسؤول
$admin_whatsapp_num = '0715104027';
$admin_whatsapp_link = 'https://wa.me/212715104027?text=' . urlencode('السلام عليكم، واجهت مشكلة في استعادة حسابي على منصة محاضراتي.');
$admin_support_email = 'mo7adaraty.noreply@gmail.com';

// التأكد من تهيئة الاتصال بقاعدة البيانات
$db = isset($pdo) ? $pdo : (isset($conn) ? $conn : null);

// إعداد نظام اللغات
$allowed_langs = ['ar', 'en', 'fr'];
$lang_code = (isset($_SESSION['lang']) && in_array($_SESSION['lang'], $allowed_langs)) ? $_SESSION['lang'] : 'ar';
$lang = file_exists("lang/{$lang_code}.php") ? require "lang/{$lang_code}.php" : [];
$dir = ($lang_code === 'ar') ? 'rtl' : 'ltr';

// جلب الأيقونات من قاعدة البيانات
$design_assets = [];
try {
    if ($db) {
        $stmt = $db->query("SELECT asset_key, file_path FROM design_assets WHERE page_section IN ('forgot_password', 'loader', 'global')");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $design_assets[$row['asset_key']] = $row['file_path'];
        }
    }
} catch (PDOException $e) {}

function getIcon($key, $default) {
    global $design_assets;
    return !empty($design_assets[$key]) ? $design_assets[$key] : $default;
}

if (!isset($_SESSION['reset_step'])) {
    $_SESSION['reset_step'] = 1;
}

$error = $_SESSION['reset_error'] ?? null;
$success = $_SESSION['reset_success'] ?? null;
unset($_SESSION['reset_error'], $_SESSION['reset_success']);

// خيار العودة لتعديل البريد
if (isset($_GET['action']) && $_GET['action'] === 'edit_email') {
    $_SESSION['reset_step'] = 2;
    header("Location: forgot_password.php");
    exit();
}

// إعادة تعيين العملية والبدء من جديد
if (isset($_GET['action']) && $_GET['action'] === 'restart') {
    unset(
        $_SESSION['reset_step'],
        $_SESSION['reset_user_id'],
        $_SESSION['reset_username'],
        $_SESSION['reset_emails_list'],
        $_SESSION['reset_target_email'],
        $_SESSION['reset_otp'],
        $_SESSION['reset_otp_expiry']
    );
    header("Location: forgot_password.php");
    exit();
}

// =========================================================================
// إرسال طلب المساعدة إلى لوحة التحكم (password_reset_requests)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_admin_request'])) {
    $help_username = trim($_POST['help_username'] ?? '');
    $help_phone    = trim($_POST['help_phone'] ?? '');

    if (empty($help_username) || empty($help_phone)) {
        $error = 'يرجى كتابة اسم المستخدم ورقم هاتفك (الواتساب) للتواصل معك.';
    } else {
        if ($db) {
            try {
                $user_stmt = $db->prepare("SELECT user_id FROM users WHERE username = ? LIMIT 1");
                $user_stmt->execute([$help_username]);
                $found_uid = $user_stmt->fetchColumn() ?: null;

                $ins_stmt = $db->prepare("
                    INSERT INTO password_reset_requests (user_id, username, contact_info, status, request_date) 
                    VALUES (?, ?, ?, 'pending', NOW())
                ");
                $ins_stmt->execute([$found_uid, $help_username, $help_phone]);

                unset(
                    $_SESSION['reset_step'],
                    $_SESSION['reset_user_id'],
                    $_SESSION['reset_username'],
                    $_SESSION['reset_emails_list'],
                    $_SESSION['reset_target_email'],
                    $_SESSION['reset_otp'],
                    $_SESSION['reset_otp_expiry']
                );

                $success = 'تم إرسال طلبك بنجاح إلى إدارة المنصة! سيتم مراجعة بياناتك والتواصل معك مباشرة عبر الواتساب لتزويدك بكلمة المرور.';
            } catch (PDOException $e) {
                $error = 'حدث خطأ أثناء إرسال الطلب: ' . $e->getMessage();
            }
        }
    }
}

// =========================================================================
// الخطوة 1: التحقق الذاتي من الهوية
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_identity'])) {
    $username     = trim($_POST['username'] ?? '');
    $email_prefix = trim($_POST['initial_email_prefix'] ?? '');
    $email_domain = trim($_POST['initial_email_domain'] ?? '@gmail.com');

    if (empty($username) || empty($email_prefix)) {
        $error = 'يرجى إدخال اسم المستخدم والبريد الإلكتروني.';
    } else {
        $full_initial_email = (strpos($email_prefix, '@') !== false) ? $email_prefix : ($email_prefix . $email_domain);

        if ($db) {
            $stmt = $db->prepare("SELECT user_id, username, email, recovery_emails FROM users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || strtolower(trim($user['email'])) !== strtolower(trim($full_initial_email))) {
                $error = 'البيانات المدخلة غير متطابقة مع الحساب المسجل. إذا تعذر عليك التذكر يمكنك التواصل المباشر مع الإدارة بالأسفل.';
            } else {
                $_SESSION['reset_user_id'] = $user['user_id'];
                $_SESSION['reset_username'] = $user['username'];

                $saved_emails = !empty($user['recovery_emails']) ? json_decode($user['recovery_emails'], true) : [];
                if (!is_array($saved_emails)) {
                    $saved_emails = [];
                }
                if (!in_array($user['email'], $saved_emails, true)) {
                    array_unshift($saved_emails, $user['email']);
                }

                $_SESSION['reset_emails_list'] = $saved_emails;
                $_SESSION['reset_step'] = 2;
                header("Location: forgot_password.php");
                exit();
            }
        }
    }
}

// =========================================================================
// الخطوة 2: اختيار البريد وإرسال الرمز
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['choose_email'])) {
    $choice       = $_POST['email_choice'] ?? '';
    $target_email = '';

    if ($choice === 'new') {
        $new_prefix   = trim($_POST['new_email_prefix'] ?? '');
        $new_domain   = trim($_POST['new_email_domain'] ?? '@gmail.com');
        $target_email = (strpos($new_prefix, '@') !== false) ? $new_prefix : ($new_prefix . $new_domain);

        if (!isValidTrustedEmail($target_email)) {
            $error = 'يرجى إدخال بريد إلكتروني رسمي وموثوق (Gmail, Outlook, Yahoo, iCloud).';
        }
    } else {
        $idx = (int)$choice;
        if (isset($_SESSION['reset_emails_list'][$idx])) {
            $target_email = $_SESSION['reset_emails_list'][$idx];
        } else {
            $error = 'يرجى اختيار بريد إلكتروني صالح.';
        }
    }

    if (empty($error) && !empty($target_email)) {
        if (isset($_SESSION['last_otp_sent_time']) && (time() - $_SESSION['last_otp_sent_time'] < 60)) {
            $wait_seconds = 60 - (time() - $_SESSION['last_otp_sent_time']);
            $error = "يرجى الانتظار {$wait_seconds} ثانية قبل إعادة طلب رمز جديد.";
        } else {
            $otp    = (string)random_int(100000, 999999);
            $expiry = time() + (10 * 60);

            $_SESSION['reset_target_email'] = $target_email;
            $_SESSION['reset_otp']          = $otp;
            $_SESSION['reset_otp_expiry']   = $expiry;
            $_SESSION['last_otp_sent_time'] = time();

            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = SMTP_HOST;
                $mail->SMTPAuth   = true;
                $mail->Username   = SMTP_USER;
                $mail->Password   = trim(str_replace(' ', '', SMTP_PASS));
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = SMTP_PORT;
                $mail->CharSet    = 'UTF-8';
                $mail->Encoding   = 'base64';

                $mail->setFrom(SMTP_USER, SMTP_FROM_NAME);
                $mail->addReplyTo(SMTP_USER, SMTP_FROM_NAME);
                $mail->addAddress($target_email);
                $mail->addCustomHeader('X-Auto-Response-Suppress', 'OOF, AutoReply');

                $user_display_name = htmlspecialchars($_SESSION['reset_username'] ?? 'عزيزي الطالب');
                $timestamp_now     = date('H:i');

                $mail->isHTML(true);
                $mail->Subject = "رمز التحقق [{$otp}] - منصة محاضراتي ({$timestamp_now})";
                $mail->Body = "
                <!DOCTYPE html>
                <html lang='ar' dir='rtl'>
                <head><meta charset='UTF-8'></head>
                <body style='margin:0; padding:20px; background-color:#f1f5f9; font-family:Tahoma, Arial, sans-serif; direction:rtl; text-align:right;'>
                    <div style='max-width:520px; margin:auto; background:#ffffff; border-radius:14px; padding:25px; border:1px solid #e2e8f0;'>
                        <h2 style='color:#1a73e8; margin-top:0;'>منصة محاضراتي | استعادة كلمة المرور</h2>
                        <p style='color:#334155; font-size:15px;'>مرحباً بك، <strong>{$user_display_name}</strong></p>
                        <p style='color:#475569; font-size:14px;'>رمز الأمان الخاص بك لاستعادة الحساب هو:</p>
                        <div style='background:#f8fafc; border:2px dashed #1a73e8; font-size:32px; font-weight:bold; letter-spacing:8px; color:#1a73e8; text-align:center; padding:15px; border-radius:10px; margin:20px 0;'>
                            {$otp}
                        </div>
                        <p style='color:#ef4444; font-size:12px;'>⏱ صلاحية هذا الرمز تنتهي بعد 10 دقائق فقط.</p>
                    </div>
                </body>
                </html>";
                $mail->AltBody = "رمز التحقق الخاص بك في منصة محاضراتي هو: {$otp} (صالح لمدة 10 دقائق).";

                $mail->send();
                $_SESSION['reset_step'] = 3;
                header("Location: forgot_password.php");
                exit();

            } catch (Exception $e) {
                $error = "تعذر إرسال رسالة التحقق عبر البريد حالياً. يمكنك استخدام نموذج المساعدة بالأسفل أو التواصل عبر الواتساب مباشرة.";
            }
        }
    }
}

// =========================================================================
// الخطوة 3: التحقق من كود OTP
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_otp'])) {
    $entered_otp = trim($_POST['otp'] ?? '');

    if (empty($entered_otp)) {
        $error = 'يرجى إدخال رمز التحقق.';
    } elseif (time() > ($_SESSION['reset_otp_expiry'] ?? 0)) {
        $error = 'انتهت صلاحية الرمز، يرجى طلب رمز جديد أو مراسلة الإدارة.';
    } elseif ($entered_otp !== ($_SESSION['reset_otp'] ?? '')) {
        $error = 'رمز التحقق غير صحيح.';
    } else {
        $_SESSION['reset_step'] = 4;
        header("Location: forgot_password.php");
        exit();
    }
}

// =========================================================================
// الخطوة 4: حفظ كلمة المرور الجديدة
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_password'])) {
    $password         = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if (strlen($password) < 6) {
        $error = 'يجب ألا تقل كلمة المرور عن 6 أحرف.';
    } elseif ($password !== $password_confirm) {
        $error = 'كلمتا المرور غير متطابقتين.';
    } else {
        if ($db) {
            $user_id      = $_SESSION['reset_user_id'];
            $target_email = $_SESSION['reset_target_email'];
            $hashed       = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $db->prepare("SELECT email, recovery_emails FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $cur = $stmt->fetch(PDO::FETCH_ASSOC);

            $emails_array = !empty($cur['recovery_emails']) ? json_decode($cur['recovery_emails'], true) : [];
            if (!is_array($emails_array)) {
                $emails_array = [];
            }

            if (!in_array($target_email, $emails_array, true)) {
                $emails_array[] = $target_email;
            }

            $stmt_up = $db->prepare("UPDATE users SET password = ?, email = ?, recovery_emails = ? WHERE user_id = ?");
            $stmt_up->execute([$hashed, $target_email, json_encode(array_values($emails_array)), $user_id]);

            unset(
                $_SESSION['reset_step'],
                $_SESSION['reset_user_id'],
                $_SESSION['reset_username'],
                $_SESSION['reset_emails_list'],
                $_SESSION['reset_target_email'],
                $_SESSION['reset_otp'],
                $_SESSION['reset_otp_expiry']
            );

            $_SESSION['reset_success'] = 'تم تحديث كلمة المرور وتعيين بريدك بنجاح! يمكنك الآن تسجيل الدخول مباشرة.';
            header("Location: forgot_password.php");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang_code; ?>" dir="<?php echo $dir; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>استعادة الحساب - Mo7adaraty</title>
    
    <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800;900&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/auth.css">
    <link rel="stylesheet" href="assets/css/pages/forgot_password.css">
    <style>
        * { box-sizing: border-box; }
        body.forgot-pwd-page { font-family: 'Tajawal', sans-serif; }
        
        .stepper-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            margin-bottom: 25px;
            padding: 0 5px;
        }
        .stepper-line {
            position: absolute;
            top: 20px;
            left: 20px;
            right: 20px;
            height: 3px;
            background: var(--border-color, #e2e8f0);
            z-index: 1;
        }
        .stepper-progress {
            position: absolute;
            top: 20px;
            right: 20px;
            height: 3px;
            background: var(--brand-primary, #1a73e8);
            z-index: 1;
            transition: width 0.3s ease;
        }
        .step-item {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
        }
        .step-circle {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: var(--bg-surface, #ffffff);
            border: 2px solid var(--border-color, #cbd5e1);
            color: var(--text-secondary, #64748b);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 13px;
        }
        .step-item.active .step-circle {
            border-color: var(--brand-primary, #1a73e8);
            background: var(--brand-primary, #1a73e8);
            color: #ffffff;
            box-shadow: 0 0 0 4px rgba(26, 115, 232, 0.2);
        }
        .step-item.completed .step-circle {
            border-color: #10b981;
            background: #10b981;
            color: #ffffff;
        }
        .step-label {
            font-size: 11px;
            font-weight: 700;
            color: var(--text-secondary, #64748b);
        }
        .step-item.active .step-label {
            color: var(--brand-primary, #1a73e8);
            font-weight: 800;
        }

        .email-input-group {
            display: flex;
            align-items: stretch;
            direction: ltr !important;
            border: 1px solid var(--border-color, #cbd5e1);
            border-radius: 12px;
            overflow: hidden;
            background: var(--bg-surface, #ffffff);
        }
        .email-input-group:focus-within {
            border-color: var(--brand-primary, #1a73e8);
            box-shadow: 0 0 0 3px rgba(26, 115, 232, 0.15);
        }
        .email-prefix-field {
            flex: 1;
            border: none !important;
            padding: 12px 14px !important;
            font-size: 14px !important;
            outline: none !important;
            background: transparent !important;
            color: var(--text-primary) !important;
            direction: ltr !important;
            text-align: left !important;
        }
        .email-domain-select {
            border: none !important;
            border-left: 1px solid var(--border-color, #e2e8f0) !important;
            background: var(--bg-body, #f8fafc) !important;
            color: var(--text-primary) !important;
            padding: 0 12px !important;
            font-size: 13.5px !important;
            font-weight: 700 !important;
            cursor: pointer !important;
            outline: none !important;
            direction: ltr !important;
        }

        .full-email-badge {
            background: rgba(26, 115, 232, 0.08);
            border: 1px solid rgba(26, 115, 232, 0.25);
            color: var(--brand-primary, #1a73e8);
            padding: 12px 16px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
        }
        .full-email-text {
            font-weight: 800;
            font-size: 14.5px;
            direction: ltr;
            text-align: left;
        }
        .edit-email-btn {
            background: var(--bg-surface, #ffffff);
            border: 1px solid var(--brand-primary, #1a73e8);
            color: var(--brand-primary, #1a73e8);
            padding: 5px 10px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .spam-notice-card {
            background: rgba(245, 158, 11, 0.08);
            border-right: 4px solid #f59e0b;
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 18px;
            text-align: right;
            font-size: 12.5px;
            color: var(--text-primary);
            line-height: 1.6;
        }

        .radio-option-card {
            display: flex;
            align-items: center;
            gap: 12px;
            background: var(--bg-body, #f8fafc);
            padding: 12px 14px;
            border-radius: 10px;
            border: 1px solid var(--border-color, #e2e8f0);
            margin-bottom: 10px;
            cursor: pointer;
        }
        .radio-option-card.selected {
            border-color: var(--brand-primary, #1a73e8);
            background: rgba(26, 115, 232, 0.05);
        }

        .fallback-support-box {
            margin-top: 25px;
            background: var(--bg-body, #f8fafc);
            border: 1px solid var(--border-color, #e2e8f0);
            border-radius: 14px;
            padding: 18px;
            text-align: right;
        }
        .fallback-support-header {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #0f172a;
            font-weight: 800;
            font-size: 14px;
            margin-bottom: 6px;
        }
        .direct-contact-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 14px;
            margin-bottom: 14px;
        }
        .btn-direct-contact {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 12px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 800;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .btn-whatsapp-direct {
            background: #25D366;
            color: #ffffff;
        }
        .btn-whatsapp-direct:hover {
            background: #1eb956;
            color: #ffffff;
        }
        .btn-email-direct {
            background: #0284c7;
            color: #ffffff;
        }
        .btn-email-direct:hover {
            background: #0369a1;
            color: #ffffff;
        }
        .fallback-btn-submit {
            background: var(--bg-surface) !important;
            color: var(--text-primary) !important;
            border: 1px solid var(--border-color) !important;
            padding: 10px !important;
            font-weight: 700 !important;
            border-radius: 8px !important;
            width: 100% !important;
            cursor: pointer !important;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 10px;
            font-size: 13px !important;
        }
        .fallback-btn-submit:hover {
            background: var(--border-color) !important;
        }

        .otp-input {
            text-align: center !important;
            font-size: 26px !important;
            letter-spacing: 8px !important;
            font-weight: 900 !important;
            direction: ltr !important;
        }
    </style>
</head>
<body class="forgot-pwd-page">

<script>
    (function() {
        const savedTheme = localStorage.getItem('mo7adaraty_theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
    })();
</script>

<div class="form-container fade-in" style="max-width: 500px;">
    
    <!-- مؤشر الخطوات -->
    <div class="stepper-container">
        <?php
            $current_step = (int)($_SESSION['reset_step'] ?? 1);
            $progress_width = match($current_step) {
                1 => '0%',
                2 => '33.3%',
                3 => '66.6%',
                4 => '100%',
                default => '0%'
            };
        ?>
        <div class="stepper-line"></div>
        <div class="stepper-progress" style="width: <?= $progress_width ?>;"></div>

        <div class="step-item <?= $current_step === 1 ? 'active' : ($current_step > 1 ? 'completed' : '') ?>">
            <div class="step-circle"><?= $current_step > 1 ? '<i class="fa-solid fa-check"></i>' : '1' ?></div>
            <span class="step-label">الهوية</span>
        </div>

        <div class="step-item <?= $current_step === 2 ? 'active' : ($current_step > 2 ? 'completed' : '') ?>">
            <div class="step-circle"><?= $current_step > 2 ? '<i class="fa-solid fa-check"></i>' : '2' ?></div>
            <span class="step-label">البريد</span>
        </div>

        <div class="step-item <?= $current_step === 3 ? 'active' : ($current_step > 3 ? 'completed' : '') ?>">
            <div class="step-circle"><?= $current_step > 3 ? '<i class="fa-solid fa-check"></i>' : '3' ?></div>
            <span class="step-label">الرمز</span>
        </div>

        <div class="step-item <?= $current_step === 4 ? 'active' : '' ?>">
            <div class="step-circle">4</div>
            <span class="step-label">كلمة السر</span>
        </div>
    </div>

    <div class="icon-header" style="text-align: center;">
        <img src="<?php echo htmlspecialchars(getIcon('forgot_icon_shield', 'includesimg/forgot_password/icon_shield.svg')); ?>" alt="Shield" style="width: 50px; height: 50px;">
    </div>
    <h2 style="text-align: center; margin-top: 5px; font-weight: 800;">استعادة كلمة المرور</h2>
    
    <?php if ($error): ?>
        <div class="status-message error slide-in-right" style="margin-bottom: 18px;">
            <i class="fa-solid fa-circle-exclamation" style="margin-inline-end: 8px;"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="status-message success slide-in-right" style="margin-bottom: 18px;">
            <i class="fa-solid fa-circle-check" style="margin-inline-end: 8px;"></i>
            <?php echo htmlspecialchars($success); ?>
        </div>
        <div class="switch-form" style="text-align: center; margin-top: 15px;">
            <a href="login.php" class="back-btn" style="display: inline-flex; align-items: center; gap: 8px; font-weight: 700; text-decoration: none;">
                <i class="fa-solid fa-arrow-right"></i> العودة لتسجيل الدخول
            </a>
        </div>
    <?php else: ?>

        <!-- ======================= الخطوة 1: التحقق من الهوية ======================= -->
        <?php if ($_SESSION['reset_step'] === 1): ?>
            <p class="desc-text" style="text-align: center; color: var(--text-secondary); margin-bottom: 20px;">
                أدخل اسم المستخدم والبريد الأولي المسجل به الحساب:
            </p>
            
            <form action="forgot_password.php" method="POST" class="auth-form" id="step1Form">
                <input type="hidden" name="verify_identity" value="1">
                
                <div class="form-group">
                    <label for="username" style="font-weight: 700; display: block; margin-bottom: 6px;">
                        <i class="fa-solid fa-user" style="color: var(--brand-primary); margin-inline-end: 6px;"></i> اسم المستخدم:
                    </label>
                    <input type="text" id="username" name="username" placeholder="اسم المستخدم الخاص بك" required autofocus style="width: 100%; border-radius: 10px; padding: 12px;">
                </div>

                <div class="form-group" style="margin-top: 14px;">
                    <label style="font-weight: 700; display: block; margin-bottom: 6px;">
                        <i class="fa-solid fa-envelope" style="color: var(--brand-primary); margin-inline-end: 6px;"></i> البريد المسجل به أولاً:
                    </label>
                    
                    <div class="email-input-group">
                        <input type="text" name="initial_email_prefix" id="initial_email_prefix" class="email-prefix-field" placeholder="username" required>
                        <select name="initial_email_domain" id="initial_email_domain" class="email-domain-select">
                            <option value="@gmail.com" selected>@gmail.com</option>
                            <option value="@outlook.com">@outlook.com</option>
                            <option value="@yahoo.com">@yahoo.com</option>
                            <option value="@icloud.com">@icloud.com</option>
                            <option value="@hotmail.com">@hotmail.com</option>
                            <option value="@yandex.com">@yandex.com</option>
                        </select>
                    </div>
                </div>
                
                <button type="submit" id="submitBtn1" style="width: 100%; border-radius: 10px; padding: 12px; font-weight: 800; margin-top: 20px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <span>متابعة التحقق</span> 
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
            </form>

        <!-- ======================= الخطوة 2: اختيار أو إضافة البريد ======================= -->
        <?php elseif ($_SESSION['reset_step'] === 2): ?>
            <div style="background: rgba(26, 115, 232, 0.08); padding: 12px 14px; border-radius: 10px; margin-bottom: 18px;">
                <span style="font-size: 14px; font-weight: 800; color: var(--text-primary); display: block;">
                    أهلاً بك <strong><?php echo htmlspecialchars($_SESSION['reset_username'] ?? ''); ?></strong> 👋
                </span>
                <span style="font-size: 12px; color: var(--text-secondary); display: block; margin-top: 3px;">
                    اختر البريد لاستلام رمز الأمان أو أضف بريدك الشخصي الجديد:
                </span>
            </div>
            
            <form action="forgot_password.php" method="POST" class="auth-form" id="step2Form">
                <input type="hidden" name="choose_email" value="1">

                <?php foreach ($_SESSION['reset_emails_list'] as $idx => $saved_em): ?>
                    <label class="radio-option-card <?= $idx === 0 ? 'selected' : '' ?>">
                        <input type="radio" name="email_choice" value="<?php echo $idx; ?>" <?php echo $idx === 0 ? 'checked' : ''; ?> onchange="handleEmailChoiceChange(this)">
                        <span style="direction: ltr; font-weight: 700; flex: 1;"><?php echo htmlspecialchars($saved_em); ?></span>
                        <i class="fa-solid fa-envelope-circle-check" style="color: #10b981;"></i>
                    </label>
                <?php endforeach; ?>

                <label class="radio-option-card" id="card_choice_new">
                    <input type="radio" name="email_choice" value="new" id="choice_new" onchange="handleEmailChoiceChange(this)">
                    <span style="font-weight: 800; color: var(--brand-primary); flex: 1;">
                        <i class="fa-solid fa-plus-circle" style="margin-inline-end: 6px;"></i> إضافة بريد إلكتروني جديد
                    </span>
                </label>

                <div class="form-group" id="new_email_box" style="display: none; margin-top: 14px; background: var(--bg-body); padding: 12px; border-radius: 10px; border: 1px dashed var(--border-color);">
                    <label style="font-weight: 700; display: block; margin-bottom: 6px;">البريد الإلكتروني الجديد:</label>
                    <div class="email-input-group">
                        <input type="text" name="new_email_prefix" id="new_email_prefix" class="email-prefix-field" placeholder="username">
                        <select name="new_email_domain" id="new_email_domain" class="email-domain-select">
                            <option value="@gmail.com" selected>@gmail.com</option>
                            <option value="@outlook.com">@outlook.com</option>
                            <option value="@yahoo.com">@yahoo.com</option>
                            <option value="@icloud.com">@icloud.com</option>
                            <option value="@hotmail.com">@hotmail.com</option>
                            <option value="@yandex.com">@yandex.com</option>
                        </select>
                    </div>
                </div>
                
                <button type="submit" id="submitBtn2" style="width: 100%; border-radius: 10px; padding: 12px; font-weight: 800; margin-top: 18px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <span>إرسال رمز التحقق</span> 
                    <i class="fa-solid fa-paper-plane"></i>
                </button>

                <div style="text-align: center; margin-top: 14px;">
                    <a href="forgot_password.php?action=restart" style="color: var(--text-secondary); font-size: 13px; text-decoration: none;">
                        <i class="fa-solid fa-arrow-rotate-left"></i> إلغاء والعودة للبداية
                    </a>
                </div>
            </form>

        <!-- ======================= الخطوة 3: إدخال كود OTP ======================= -->
        <?php elseif ($_SESSION['reset_step'] === 3): ?>
            
            <div class="full-email-badge">
                <div>
                    <small style="display: block; font-size: 11px; opacity: 0.8;">تم إرسال الرمز إلى:</small>
                    <div class="full-email-text"><?php echo htmlspecialchars($_SESSION['reset_target_email'] ?? ''); ?></div>
                </div>
                <a href="forgot_password.php?action=edit_email" class="edit-email-btn">
                    <i class="fa-solid fa-pen-to-square"></i> <span>تعديل البريد</span>
                </a>
            </div>

            <div class="spam-notice-card">
                <i class="fa-solid fa-triangle-exclamation" style="color: #d97706; margin-inline-end: 4px;"></i>
                <strong>لم يصلك الرمز في الوارد؟</strong> تفقد قسم <strong>الرسائل غير المرغوب فيها (Spam / Indésirable)</strong> أو استخدم وسائل التواصل المباشر بالأسفل.
            </div>
            
            <form action="forgot_password.php" method="POST" class="auth-form" id="step3Form">
                <input type="hidden" name="verify_otp" value="1">
                
                <div class="form-group">
                    <label for="otp" style="font-weight: 700; display: block; margin-bottom: 6px; text-align: center;">
                        أدخل رمز التحقق (6 أرقام):
                    </label>
                    <input type="text" id="otp" name="otp" class="otp-input" maxlength="6" placeholder="000000" required autofocus style="width: 100%; border-radius: 10px; padding: 12px;">
                </div>
                
                <button type="submit" id="submitBtn3" style="width: 100%; border-radius: 10px; padding: 12px; font-weight: 800; margin-top: 16px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <span>تأكيد الرمز والمتابعة</span> 
                    <i class="fa-solid fa-shield-check"></i>
                </button>
            </form>

        <!-- ======================= الخطوة 4: كلمة المرور الجديدة ======================= -->
        <?php elseif ($_SESSION['reset_step'] === 4): ?>
            <p class="desc-text" style="text-align: center; color: var(--text-secondary); margin-bottom: 20px;">
                تم إثبات هويتك بنجاح! أدخل كلمة المرور الجديدة:
            </p>
            
            <form action="forgot_password.php" method="POST" class="auth-form" id="step4Form">
                <input type="hidden" name="save_password" value="1">
                
                <div class="form-group">
                    <label for="password" style="font-weight: 700; display: block; margin-bottom: 6px;">
                        <i class="fa-solid fa-lock" style="color: var(--brand-primary); margin-inline-end: 6px;"></i> كلمة المرور الجديدة:
                    </label>
                    <input type="password" id="password" name="password" placeholder="••••••••" required autofocus style="width: 100%; border-radius: 10px; padding: 12px;">
                </div>

                <div class="form-group" style="margin-top: 14px;">
                    <label for="password_confirm" style="font-weight: 700; display: block; margin-bottom: 6px;">
                        <i class="fa-solid fa-lock-check" style="color: var(--brand-primary); margin-inline-end: 6px;"></i> تأكيد كلمة المرور:
                    </label>
                    <input type="password" id="password_confirm" name="password_confirm" placeholder="••••••••" required style="width: 100%; border-radius: 10px; padding: 12px;">
                </div>
                
                <button type="submit" id="submitBtn4" style="width: 100%; border-radius: 10px; padding: 12px; font-weight: 800; margin-top: 20px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <span>حفظ كلمة المرور الجديدة</span> 
                    <i class="fa-solid fa-circle-check"></i>
                </button>
            </form>
        <?php endif; ?>
        
        <!-- ========================================================================= -->
        <!-- قسم التواصل المباشر وطلب مساعدة الإدارة عند تعذر الاسترجاع -->
        <!-- ========================================================================= -->
        <div class="fallback-support-box">
            <div class="fallback-support-header">
                <i class="fa-solid fa-headset" style="font-size: 18px; color: var(--brand-primary);"></i>
                <span>تعذر عليك استرجاع الحساب؟ تواصل مع الإدارة مباشرة:</span>
            </div>
            
            <!-- أزرار التواصل المباشر عبر واتساب والبريد -->
            <div class="direct-contact-grid">
                <a href="<?php echo $admin_whatsapp_link; ?>" target="_blank" rel="noopener noreferrer" class="btn-direct-contact btn-whatsapp-direct">
                    <i class="fa-brands fa-whatsapp" style="font-size: 17px;"></i>
                    <span>واتساب: <?php echo htmlspecialchars($admin_whatsapp_num); ?></span>
                </a>
                <a href="mailto:<?php echo htmlspecialchars($admin_support_email); ?>?subject=<?php echo urlencode('طلب مساعدة في استعادة حساب منصة محاضراتي'); ?>" class="btn-direct-contact btn-email-direct">
                    <i class="fa-solid fa-envelope" style="font-size: 16px;"></i>
                    <span>مراسلة عبر البريد</span>
                </a>
            </div>

            <p style="margin: 0 0 10px 0; font-size: 12px; color: var(--text-secondary); line-height: 1.5;">
                أو اترك اسمك ورقم هاتفك وسنقوم بالتواصل معك لحل المشكلة:
            </p>
            <form action="forgot_password.php" method="POST">
                <input type="hidden" name="submit_admin_request" value="1">
                
                <div style="margin-bottom: 8px;">
                    <input type="text" name="help_username" placeholder="اسم المستخدم (Username)" value="<?php echo htmlspecialchars($_SESSION['reset_username'] ?? ''); ?>" required style="width: 100%; border-radius: 8px; padding: 9px 12px; border: 1px solid var(--border-color); background: var(--bg-surface); color: var(--text-primary); font-size: 13px;">
                </div>

                <div style="margin-bottom: 8px;">
                    <input type="tel" name="help_phone" placeholder="رقم هاتفك / الواتساب (مثال: 0612345678)" required style="width: 100%; border-radius: 8px; padding: 9px 12px; border: 1px solid var(--border-color); background: var(--bg-surface); color: var(--text-primary); direction: ltr; text-align: left; font-size: 13px;">
                </div>

                <button type="submit" class="fallback-btn-submit">
                    <i class="fa-solid fa-paper-plane"></i> إرسال طلب تواصل إلى لوحة التحكم
                </button>
            </form>
        </div>

        <div class="switch-form" style="text-align: center; margin-top: 18px;">
            <p style="margin: 0; font-size: 13.5px; color: var(--text-secondary);">
                تذكرت كلمة المرور؟ <a href="login.php" style="color: var(--brand-primary); font-weight: 800; text-decoration: none;">تسجيل الدخول</a>
            </p>
        </div>
    <?php endif; ?>
</div>

<script>
    function handleEmailChoiceChange(radio) {
        document.querySelectorAll('.radio-option-card').forEach(card => card.classList.remove('selected'));
        radio.closest('.radio-option-card').classList.add('selected');
        
        const newBox = document.getElementById('new_email_box');
        const newPrefix = document.getElementById('new_email_prefix');
        if (radio.value === 'new') {
            newBox.style.display = 'block';
            newPrefix.required = true;
            newPrefix.focus();
        } else {
            newBox.style.display = 'none';
            newPrefix.required = false;
        }
    }

    function attachAutoDomainPicker(prefixInputId, domainSelectId) {
        const input = document.getElementById(prefixInputId);
        const select = document.getElementById(domainSelectId);
        if (!input || !select) return;

        input.addEventListener('input', function() {
            const val = this.value.trim();
            if (val.includes('@')) {
                const parts = val.split('@');
                this.value = parts[0];
                const domainSuffix = '@' + parts[1].toLowerCase();
                
                let matched = false;
                for (let i = 0; i < select.options.length; i++) {
                    if (select.options[i].value.toLowerCase() === domainSuffix) {
                        select.selectedIndex = i;
                        matched = true;
                        break;
                    }
                }
                if (!matched && parts[1]) {
                    const opt = new Option('@' + parts[1], '@' + parts[1]);
                    select.add(opt);
                    select.value = opt.value;
                }
            }
        });
    }

    attachAutoDomainPicker('initial_email_prefix', 'initial_email_domain');
    attachAutoDomainPicker('new_email_prefix', 'new_email_domain');
</script>
<script src="assets/js/auth.js"></script>
</body>
</html>