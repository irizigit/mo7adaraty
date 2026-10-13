/* =========================================
   WhatsApp Groups Page Scripts (whatsapp.js)
   ========================================= */
document.addEventListener('DOMContentLoaded', function() {
    
    // تهيئة حركات AOS للبطاقات
    if (typeof AOS !== 'undefined') {
        AOS.init({
            duration: 600,
            easing: 'ease-in-out',
            once: true,
            offset: 30
        });
    }
});