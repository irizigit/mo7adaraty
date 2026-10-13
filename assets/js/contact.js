/* =========================================
   Contact Us Page Scripts (contact.js)
   ========================================= */
document.addEventListener('DOMContentLoaded', function() {
    
    // 1. تهيئة مكتبة AOS للحركات
    if (typeof AOS !== 'undefined') {
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true,
            offset: 50
        });
    }

    // 2. التحقق من صحة النموذج قبل الإرسال
    const contactForm = document.getElementById('contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', function(event) {
            const name = document.getElementById('name').value.trim();
            const email = document.getElementById('email').value.trim();
            const message = document.getElementById('message').value.trim();
            
            let isValid = true;
            
            // جلب الترجمات من الكائن العام الذي تم تمريره عبر PHP
            const t = window.ContactTranslations || {
                name_err: "الرجاء إدخال الاسم",
                email_err_empty: "الرجاء إدخال البريد الإلكتروني",
                email_err_invalid: "الرجاء إدخال بريد إلكتروني صحيح",
                msg_err: "الرجاء كتابة رسالتك"
            };
            
            // التحقق من الاسم
            if (name === '') {
                isValid = false;
                showFieldError('name', t.name_err);
            } else {
                clearFieldError('name');
            }
            
            // التحقق من البريد الإلكتروني
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (email === '') {
                isValid = false;
                showFieldError('email', t.email_err_empty);
            } else if (!emailRegex.test(email)) {
                isValid = false;
                showFieldError('email', t.email_err_invalid);
            } else {
                clearFieldError('email');
            }
            
            // التحقق من الرسالة
            if (message === '') {
                isValid = false;
                showFieldError('message', t.msg_err);
            } else {
                clearFieldError('message');
            }
            
            if (!isValid) {
                event.preventDefault();
            }
        });
    }
    
    // وظائف مساعدة للتحقق من الحقول
    function showFieldError(fieldId, message) {
        const field = document.getElementById(fieldId);
        field.style.borderColor = 'var(--danger-color)';
        
        let errorElement = field.parentNode.querySelector('.field-error-text');
        if (!errorElement) {
            errorElement = document.createElement('div');
            errorElement.className = 'field-error-text';
            field.parentNode.appendChild(errorElement);
        }
        errorElement.textContent = message;
    }
    
    function clearFieldError(fieldId) {
        const field = document.getElementById(fieldId);
        field.style.borderColor = '';
        
        const errorElement = field.parentNode.querySelector('.field-error-text');
        if (errorElement) {
            errorElement.remove();
        }
    }
});