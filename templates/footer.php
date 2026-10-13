<?php
// التأكد من أن المسار الأساسي موجود 
if (!isset($base_url)) {
    $base_url = '/mo7adaraty/';
}

// تعريف دالة الترجمة للفوتر لتقرأ من ملفات اللغات الحالية
if (!function_exists('__')) {
    function __($key, $default = '') { 
        global $lang;
        // إذا كان المفتاح موجوداً في ملف اللغة، قم بعرضه، وإلا اعرض النص الافتراضي
        return isset($lang[$key]) ? $lang[$key] : ($default ?: $key); 
    }
}
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<footer class="footer">
    <div class="footer__container">
        <div class="footer__section" data-aos="fade-up">
  <div class="header__logo">
            <a href="<?= $base_url ?>home.php" class="header__logo-link">
                <?php 
                // جلب الشعار بناءً على اللغة الحالية، وإلا استخدام العربي كبديل
                $logo_key = 'header_logo_' . $current_lang;
                $logo_path = $header_icons[$logo_key] ?? ($header_icons['header_logo_ar'] ?? '');
                
                if (!empty($logo_path)) {
                    $logo_url = rtrim($base_url, '/') . '/' . ltrim($logo_path, '/');
echo '<img src="' . htmlspecialchars($logo_url) . '" alt="' . __('site_name', 'محاضراتي') . '" class="header__logo-img" style="width: 100px; height: 100px; object-fit: contain;" onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'block\';">';                    echo '<span style="display:none;">' . __('site_name', 'محاضراتي') . '</span>';
                    echo '<span style="display:none;">' . __('site_name', 'محاضراتي') . '</span>';
                } else {
                    echo __('site_name', 'محاضراتي');
                }
                ?>
            </a>
        </div>            <p class="footer__text"><?= __('welcome_desc', 'منصتك التعليمية الأولى لتبادل المحاضرات والامتحانات والتواصل الفعال بين الطلاب لبيئة دراسية أفضل.') ?></p>
            <p class="footer__text"><?= __('contact_us', 'تواصل معنا') ?>: <a href="mailto:support@mo7adaraty.com" class="footer__link" style="display:inline; transform:none;">support@mo7adaraty.com</a></p>
        </div>
        
        <div class="footer__section" data-aos="fade-up" data-aos-delay="100">
            <h4 class="footer__title"><?= __('quick_links', 'روابط سريعة') ?></h4>
            <ul class="footer__list">
                <li><a href="<?= $base_url ?>home.php" class="footer__link"><?= __('home', 'الرئيسية') ?></a></li>
                <li><a href="<?= $base_url ?>student/profil.php" class="footer__link"><?= __('my_profile', 'ملفي الشخصي') ?></a></li>
                <li><a href="<?= $base_url ?>student/community/index.php" class="footer__link"><?= __('community', 'ساحة المشاركات') ?></a></li>
                <li><a href="<?= $base_url ?>pages/ContactUs.php" class="footer__link"><?= __('contact_us', 'تواصل معنا') ?></a></li>
                <li><a href="<?= $base_url ?>pages/AboutUs.php" class="footer__link"><?= __('about_us', 'عن الموقع') ?></a></li>
                <li><a href="<?= $base_url ?>pages/FAQ.php" class="footer__link"><?= __('FAQ', 'الأسئلة الشائعة') ?></a></li>
            </ul>
        </div>
        
        <div class="footer__section" data-aos="fade-up" data-aos-delay="200">
            <h4 class="footer__title"><?= __('follow_us_social', 'تابعنا') ?></h4>
            <div class="footer__social-list">
                <a href="https://facebook.com/mo7adaraty" target="_blank" aria-label="Facebook" class="footer__social-link"><i class="fab fa-facebook-f"></i></a>
                <a href="https://twitter.com/mo7adaraty" target="_blank" aria-label="Twitter" class="footer__social-link"><i class="fab fa-twitter"></i></a>
                <a href="https://linkedin.com/company/mo7adaraty" target="_blank" aria-label="LinkedIn" class="footer__social-link"><i class="fab fa-linkedin-in"></i></a>
                <a href="https://instagram.com/mo7adaraty" target="_blank" aria-label="Instagram" class="footer__social-link"><i class="fab fa-instagram"></i></a>
                <a href="https://github.com/mo7adaraty" target="_blank" aria-label="GitHub" class="footer__social-link"><i class="fab fa-github"></i></a>
            </div>
        </div>
    </div>
    
    <div class="footer__bottom">
        <p><?= __('created_by', 'تم الإنشاء بواسطة') ?> <strong>MOHAMED IRIZI & ILHAM AAMOUM</strong> | <?= __('all_rights_reserved', 'جميع الحقوق محفوظة') ?> &copy; <?php echo date("Y"); ?></p>
    </div>
</footer>

<script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
<script>
    // التأكد من تهيئة مكتبة AOS لتعمل الحركات في الفوتر بسلاسة
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof AOS !== 'undefined') {
            AOS.init({
                once: true,
                offset: 50,
                duration: 600
            });
        }
    });
</script>