/* =========================================
   Legal Pages Scripts (legal.js)
   ========================================= */
document.addEventListener('DOMContentLoaded', function() {
    // تهيئة مكتبة AOS للحركات
    if (typeof AOS !== 'undefined') {
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true,
            offset: 50
        });
    }

    // إضافة تأثير parallax لخلفية الهيرو
    window.addEventListener('scroll', () => {
        const scrolled = window.pageYOffset;
        const heroSection = document.querySelector('.legal-hero');
        if (heroSection) {
            // تحريك الخلفية بشكل أبطأ من التمرير
            heroSection.style.backgroundPositionY = `${scrolled * 0.4}px`;
        }
    });
});