<?php
session_start();
$base_url = '/mo7adaraty/'; 
require_once '../config/db_connexion.php';

// --- جلب الأيقونات الديناميكية الخاصة بصفحة اتصل بنا ---
$contact_assets = [];
try {
    $assets_query = "SELECT asset_key, file_path FROM design_assets WHERE page_section = 'ContactUs'";
    $stmt = $pdo->query($assets_query);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $contact_assets[$row['asset_key']] = $row['file_path'];
    }
} catch (PDOException $e) { }

function get_contact_icon($key, $default = '../assets/images/favicon.png') {
    global $contact_assets;
    // إضافة ../ للرجوع للمجلد الرئيسي حيث يوجد مجلد uploads
    return isset($contact_assets[$key]) ? '../' . htmlspecialchars($contact_assets[$key]) : $default;
}
// ----------------------------------------------------

// نظام الترجمة المصغر
$lang = $_SESSION['lang'] ?? 'ar';
$lang_file = __DIR__ . "/../lang/{$lang}.php";
if (file_exists($lang_file)) {
    $translations = require_once $lang_file; 
} else {
    $translations = [];
}

if (!function_exists('__')) {
    function __($key) {
        global $translations;
        return $translations[$key] ?? $key;
    }
}

// معالجة إرسال النموذج
$message_sent = false;
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $subject = isset($_POST['subject']) ? trim($_POST['subject']) : '';
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';
    
    if (empty($name) || empty($email) || empty($message)) {
        $error_message = __('contact_error_required_fields');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = __('contact_error_invalid_email');
    } else {
        try {
            // إدراج الرسالة في قاعدة البيانات في جدول visitor_messages
            $stmt = $pdo->prepare("INSERT INTO visitor_messages (name, email, subject, message, is_read, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
            $stmt->execute([$name, $email, $subject, $message]);
            
            $message_sent = true;
            
            // تفريغ الحقول بعد الإرسال الناجح لمنع التكرار
            unset($_POST);
            $name = $email = $subject = $message = '';
            
        } catch (PDOException $e) {
            // رسالة خطأ في حال فشل الاتصال بقاعدة البيانات
            $error_message = __('contact_error_system');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>" dir="<?= __('lang_dir') ?? ($lang === 'ar' ? 'rtl' : 'ltr') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('contact_page_title') ?></title>
    <meta name="description" content="<?= __('contact_meta_desc') ?>">
    <link rel="icon" type="image/svg+xml" href="../assets/images/favicon.svg">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/pages/contact.css">
</head>
<body class="font-tajawal overflow-x-hidden">

    <script>
        (function() {
            const savedTheme = localStorage.getItem('mo7adaraty_theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>

    <?php require_once '../templates/header.php'; ?>

    <main class="contact-page-wrapper">
        
      <!-- بداية قسم الـ Hero المعدل ليدعم فيديو الانترو -->
        <section class="contact-hero py-20 md:py-32 relative overflow-hidden">
            
            <!-- عنصر الفيديو كخلفية متحركة للمقدمة -->
           <video autoplay muted loop playsinline class="absolute top-0 left-0 w-full h-full object-fill z-0 pointer-events-none opacity-40">
    <source src="../assets/videos/ContactUs.mp4" type="video/mp4">
    <?= __('برجاء تحديث المتصفح لدعم تشغيل الفيديو') ?>
</video> 
            <!-- طبقة تظليل داكنة لضمان تباين ووضوح النصوص الفاتحة فوق الفيديو -->
            <div class="absolute top-0 left-0 w-full h-full bg-black/20 z-0"></div>

            <!-- محتوى النصوص الرئيسي مع الحفاظ على التموضع العلوي z-10 -->
            <div class="container mx-auto px-4 relative z-10">
                <div class="text-center text-white" data-aos="fade-up">
                    <h1 class="text-4xl md:text-6xl font-bold mb-6"><?= __('contact_us_heading') ?></h1>
                    <p class="text-lg md:text-xl max-w-3xl mx-auto leading-relaxed opacity-90">
                        <?= __('contact_hero_subtitle') ?>
                    </p>
                </div>
            </div>
            
            <!-- الحفاظ على حاوية الموجة السفلي في مكانها الصحيح فوق الفيديو z-10 -->
            
        </section>
        <!-- نهاية قسم الـ Hero المعدل -->
        <div class="container mx-auto px-4 py-16 md:py-24 relative z-10">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
                
                <div class="lg:col-span-2" data-aos="fade-right">
                    <div class="contact-card p-8 md:p-10">
                        <h2 class="text-2xl font-bold mb-6 text-[var(--text-primary)] border-b border-[var(--border-color)] pb-4">
                            <i class="fas fa-envelope-open-text text-[var(--brand-primary)] ml-2"></i> <?= __('contact_send_message_title') ?>
                        </h2>
                        
                        <?php if ($message_sent): ?>
                            <div class="contact-status-msg contact-status-success">
                                <i class="fas fa-check-circle text-2xl"></i>
                                <div>
                                    <strong><?= __('contact_thank_you') ?></strong> <?= __('contact_message_sent_success') ?>
                                </div>
                            </div>
                        <?php elseif (!empty($error_message)): ?>
                            <div class="contact-status-msg contact-status-error">
                                <i class="fas fa-exclamation-circle text-2xl"></i>
                                <div>
                                    <strong><?= __('contact_error') ?></strong> <?php echo $error_message; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <form id="contactForm" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="contact-input-group">
                                    <label for="name" class="block mb-2 font-semibold text-[var(--text-primary)]"><?= __('contact_name_label') ?> <span class="text-red-500">*</span></label>
                                    <i class="fas fa-user contact-input-icon"></i>
                                    <input type="text" id="name" name="name" class="contact-input" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>">
                                </div>
                                <div class="contact-input-group">
                                    <label for="email" class="block mb-2 font-semibold text-[var(--text-primary)]"><?= __('contact_email_label') ?> <span class="text-red-500">*</span></label>
                                    <i class="fas fa-envelope contact-input-icon"></i>
                                    <input type="email" id="email" name="email" class="contact-input" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="contact-input-group">
                                <label for="subject" class="block mb-2 font-semibold text-[var(--text-primary)]"><?= __('contact_subject_label') ?></label>
                                <i class="fas fa-tag contact-input-icon"></i>
                                <select id="subject" name="subject" class="contact-input">
                                    <option value=""><?= __('contact_select_subject') ?></option>
                                    <option value="استفسار عام" <?php echo (isset($subject) && $subject === 'استفسار عام') ? 'selected' : ''; ?>><?= __('contact_subject_general') ?></option>
                                    <option value="الإبلاغ عن مشكلة تقنية" <?php echo (isset($subject) && $subject === 'الإبلاغ عن مشكلة تقنية') ? 'selected' : ''; ?>><?= __('contact_subject_tech') ?></option>
                                    <option value="اقتراح" <?php echo (isset($subject) && $subject === 'اقتراح') ? 'selected' : ''; ?>><?= __('contact_subject_suggestion') ?></option>
                                </select>
                            </div>
                            
                            <div class="contact-input-group">
                                <label for="message" class="block mb-2 font-semibold text-[var(--text-primary)]"><?= __('contact_message_label') ?> <span class="text-red-500">*</span></label>
                                <i class="fas fa-comment-alt contact-input-icon"></i>
                                <textarea id="message" name="message" class="contact-input contact-textarea"><?php echo isset($message) ? htmlspecialchars($message) : ''; ?></textarea>
                            </div>
                            
                            <div class="mt-8">
                                <button type="submit" class="contact-submit-btn">
                                    <?= __('contact_send_btn') ?> <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div data-aos="fade-left">
                    <div class="working-hours-card">
                        <div class="flex items-center mb-6">
                            <img src="<?= get_contact_icon('icon_working_hours') ?>" class="w-8 h-8 ml-3 object-contain" alt="clock">
                            <h3 class="text-xl font-bold"><?= __('contact_working_hours') ?></h3>
                        </div>
                        <div class="space-y-4 text-sm font-medium">
                            <div class="flex justify-between border-b border-white/20 pb-2">
                                <span><?= __('contact_sat_thu') ?></span>
                                <span><?= __('contact_time_9_6') ?></span>
                            </div>
                            <div class="flex justify-between border-b border-white/20 pb-2">
                                <span><?= __('contact_friday') ?></span>
                                <span><?= __('contact_closed') ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span><?= __('contact_tech_support') ?></span>
                                <span class="bg-white text-[var(--brand-primary)] px-2 py-1 rounded text-xs font-bold"><?= __('contact_24_7') ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="contact-card p-6 mb-8">
                        <div class="contact-icon-wrapper flex items-center justify-center"><img src="<?= get_contact_icon('icon_email_card') ?>" class="w-6 h-6 object-contain" alt="email"></div>
                        <h3 class="text-xl font-bold mb-3 text-[var(--text-primary)]"><?= __('contact_email_card_title') ?></h3>
                        <p class="text-[var(--text-secondary)] text-sm mb-4"><?= __('contact_email_card_text') ?></p>
                        <a href="mailto:support@mo7adaraty.com" class="text-[var(--brand-primary)] font-bold hover:underline">support@mo7adaraty.com</a>
                    </div>

                    <div class="contact-card p-6">
                        <div class="contact-icon-wrapper flex items-center justify-center"><img src="<?= get_contact_icon('icon_social_card') ?>" class="w-6 h-6 object-contain" alt="social"></div>
                        <h3 class="text-xl font-bold mb-5 text-[var(--text-primary)]"><?= __('contact_social_title') ?></h3>
                        <div class="flex gap-3">
                            <a href="#" class="w-10 h-10 rounded-full bg-[var(--bg-light)] text-[var(--text-secondary)] flex items-center justify-center hover:bg-[var(--brand-primary)] hover:text-white transition-colors border border-[var(--border-color)]"><i class="fab fa-facebook-f"></i></a>
                            <a href="#" class="w-10 h-10 rounded-full bg-[var(--bg-light)] text-[var(--text-secondary)] flex items-center justify-center hover:bg-[var(--brand-primary)] hover:text-white transition-colors border border-[var(--border-color)]"><i class="fab fa-twitter"></i></a>
                            <a href="#" class="w-10 h-10 rounded-full bg-[var(--bg-light)] text-[var(--text-secondary)] flex items-center justify-center hover:bg-[var(--brand-primary)] hover:text-white transition-colors border border-[var(--border-color)]"><i class="fab fa-instagram"></i></a>
                            <a href="#" class="w-10 h-10 rounded-full bg-[var(--bg-light)] text-[var(--text-secondary)] flex items-center justify-center hover:bg-[var(--brand-primary)] hover:text-white transition-colors border border-[var(--border-color)]"><i class="fab fa-linkedin-in"></i></a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-24" data-aos="fade-up">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-bold mb-4 text-[var(--text-primary)]"><?= __('contact_support_team') ?></h2>
                    <p class="text-lg text-[var(--text-secondary)] max-w-2xl mx-auto"><?= __('contact_support_team_text') ?></p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-5xl mx-auto">
                    <div class="contact-card p-8 text-center">
                        <div class="contact-team-avatar">
                            <img src="<?= get_contact_icon('team1_avatar') ?>" alt="Manager" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                        <h3 class="text-xl font-bold mb-1 text-[var(--text-primary)]">MOHAMED IRIZI</h3>
                        <p class="text-[var(--brand-primary)] font-medium mb-5"><?= __('contact_role_manager') ?></p>
                        <div class="flex justify-center gap-3">
                            <a href="#" class="text-[var(--text-secondary)] hover:text-[var(--brand-primary)] text-lg"><i class="fab fa-linkedin"></i></a>
                            <a href="#" class="text-[var(--text-secondary)] hover:text-[var(--brand-primary)] text-lg"><i class="fab fa-github"></i></a>
                        </div>
                    </div>
                    
                    <div class="contact-card p-8 text-center">
                        <div class="contact-team-avatar">
                            <img src="<?= get_contact_icon('team2_avatar') ?>" alt="Support" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                        <h3 class="text-xl font-bold mb-1 text-[var(--text-primary)]"><?= __('contact_team_sara') ?></h3>
                        <p class="text-[var(--brand-primary)] font-medium mb-5"><?= __('contact_role_customer_service') ?></p>
                        <div class="flex justify-center gap-3">
                            <a href="#" class="text-[var(--text-secondary)] hover:text-[var(--brand-primary)] text-lg"><i class="fab fa-linkedin"></i></a>
                            <a href="#" class="text-[var(--text-secondary)] hover:text-[var(--brand-primary)] text-lg"><i class="fab fa-twitter"></i></a>
                        </div>
                    </div>
                    
                    <div class="contact-card p-8 text-center">
                        <div class="contact-team-avatar">
                            <img src="<?= get_contact_icon('team3_avatar') ?>" alt="Tech Support" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                        <h3 class="text-xl font-bold mb-1 text-[var(--text-primary)]"><?= __('contact_team_ali') ?></h3>
                        <p class="text-[var(--brand-primary)] font-medium mb-5"><?= __('contact_role_tech_support') ?></p>
                        <div class="flex justify-center gap-3">
                            <a href="#" class="text-[var(--text-secondary)] hover:text-[var(--brand-primary)] text-lg"><i class="fab fa-github"></i></a>
                            <a href="#" class="text-[var(--text-secondary)] hover:text-[var(--brand-primary)] text-lg"><i class="fas fa-envelope"></i></a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-24" data-aos="fade-up">
                <div class="text-center mb-10">
                    <h2 class="text-3xl font-bold mb-4 text-[var(--text-primary)]"><?= __('contact_our_location') ?></h2>
                </div>
                <div class="contact-map-container">
                    <iframe 
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3323.8464584992523!2d-7.6321287!3d33.583311!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0xda7d2967dfc66c3%3A0xc31620a2ce8ba3e6!2sCasablanca%2C%20Morocco!5e0!3m2!1sen!2s!4v1699999999999!5m2!1sen!2s" 
                        width="100%" 
                        height="100%" 
                        style="border:0; filter: grayscale(20%) contrast(90%);" 
                        allowfullscreen="" 
                        loading="lazy" 
                        referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                </div>
            </div>

        </div>
    </main>

    <?php require_once '../templates/footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    
    <script>
        window.ContactTranslations = {
            name_err: "<?= __('contact_js_enter_name') ?>",
            email_err_empty: "<?= __('contact_js_enter_email') ?>",
            email_err_invalid: "<?= __('contact_js_valid_email') ?>",
            msg_err: "<?= __('contact_js_enter_message') ?>"
        };
    </script>
    <script src="../assets/js/contact.js"></script>
    
</body>
</html>