/* =========================================
   Archive Page Scripts (archive.js)
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

    // إرسال النموذج تلقائياً عند تغيير الموسم لتوفير خطوة على الطالب (UX)
    const yearSelect = document.getElementById('year_id');
    const archiveForm = document.getElementById('archiveForm');

    if (yearSelect && archiveForm) {
        yearSelect.addEventListener('change', function() {
            // إضافة تأثير تحميل بصري بسيط
            this.style.opacity = '0.5';
            archiveForm.submit();
        });
    }
});