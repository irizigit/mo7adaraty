/* =========================================
   Contact Admin Page Scripts (contact_admin.js)
   ========================================= */
document.addEventListener('DOMContentLoaded', function() {
    
    // تهيئة حركات AOS إذا كانت المكتبة موجودة
    if (typeof AOS !== 'undefined') {
        AOS.init({
            duration: 600,
            easing: 'ease-in-out',
            once: true,
            offset: 30
        });
    }

    // تفاعل زر الإرسال
    const contactForm = document.getElementById('contactAdminForm');
    if (contactForm) {
        contactForm.addEventListener('submit', function() {
            const submitBtn = document.getElementById('submitTicketBtn');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> جاري الإرسال...';
            submitBtn.style.pointerEvents = 'none';
            submitBtn.style.opacity = '0.8';
        });
    }
});