/**
 * Mo7adaraty - Auth System JavaScript (Multilingual Support)
 * الالتزام بقواعد البساطة والتعديل الجراحي
 */

document.addEventListener('DOMContentLoaded', function() {
    const authForms = document.querySelectorAll('.auth-form');

    authForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            
            // [تعديل جراحي]: التحقق من صحة النموذج قبل فعل أي شيء
            // إذا كان النموذج غير صالح (مثلا حقل مطلوب فارغ)، نوقف تنفيذ الكود لتجنب تعليق الزر
            if (!form.checkValidity()) {
                return; 
            }

            const submitBtn = form.querySelector('button[type="submit"]');
            if (!submitBtn) return;

            // منع النقر المزدوج
            if (submitBtn.classList.contains('is-loading')) {
                e.preventDefault();
                return;
            }

            // جلب نص التحميل من الخاصية أو المتغير العام أو قيمة افتراضية
            const loadingText = submitBtn.getAttribute('data-loading-text') || 
                                (window.AppTranslations && window.AppTranslations.sending) || 
                                '...';

            // تطبيق حالة التحميل
            submitBtn.classList.add('is-loading');
            setTimeout(() => { submitBtn.disabled = true; }, 10);
            // تحديث النص داخل الـ span مع الحفاظ على الأيقونة إذا كانت موجودة
            const btnSpan = submitBtn.querySelector('span');
            if (btnSpan) {
                btnSpan.textContent = loadingText;
            } else {
                submitBtn.textContent = loadingText;
            }

            // ملاحظة: إذا كان النموذج Ajax، ستحتاج لاستعادة originalContent في ملف المعالجة
            // هنا نفترض الإرسال التقليدي Standard POST كما في forgot_password.php
        });
    });

    // تحسين تجربة المستخدم: إخفاء رسائل الخطأ عند البدء بالكتابة
    const inputs = document.querySelectorAll('.form-group input');
    inputs.forEach(input => {
        input.addEventListener('input', () => {
            const errorMsg = document.querySelector('.status-message.error');
            if (errorMsg) {
                errorMsg.style.opacity = '0';
                setTimeout(() => errorMsg.remove(), 300);
            }
        });
    });
});