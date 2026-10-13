/* =========================================
   Home Page Scripts (home.js)
   ========================================= */
document.addEventListener('DOMContentLoaded', function() {
    
    // 1. نظام إظهار المزيد في اللقطات (Sneak Peek)
    const showMoreBtn = document.getElementById('showMoreSneakPeek');
    if (showMoreBtn) {
        showMoreBtn.addEventListener('click', function() {
            const hiddenItems = document.querySelectorAll('.sneak-peek-hidden');
            hiddenItems.forEach(item => {
                item.classList.remove('sneak-peek-hidden');
                item.style.animation = 'fadeIn 0.5s ease';
            });
            this.style.display = 'none'; // إخفاء الزر بعد الضغط
        });
    }

    // 2. نظام الأسئلة الشائعة (FAQ Accordion)
    const faqQuestions = document.querySelectorAll('.faq-question');
    faqQuestions.forEach(question => {
        question.addEventListener('click', function() {
            const item = this.parentElement;
            const isActive = item.classList.contains('active');
            
            // إغلاق الجميع
            document.querySelectorAll('.faq-item').forEach(faq => {
                faq.classList.remove('active');
            });
            
            // فتح العنصر المضغوط إذا لم يكن مفتوحاً
            if (!isActive) {
                item.classList.add('active');
            }
        });
    });

    // 3. نظام إرسال الآراء بالـ AJAX (Review System)
    const reviewForm = document.getElementById('reviewForm');
    if (reviewForm) {
        
        // --- تعريب/ترجمة رسالة المتصفح الافتراضية للحقول الإجبارية ---
        const requiredInputs = reviewForm.querySelectorAll('[required]');
        requiredInputs.forEach(input => {
            input.addEventListener('invalid', function() {
                // تعيين الرسالة المترجمة حسب لغة الموقع بناءً على المتغير المرر من PHP
                this.setCustomValidity(langData.requiredField);
            });
            input.addEventListener('input', function() {
                // إخفاء الرسالة فور بدء المستخدم بالكتابة
                this.setCustomValidity('');
            });
        });
        // --------------------------------------------------------

        reviewForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;
            const statusDiv = document.getElementById('reviewStatus');
            
            // استخدام رسالة "جاري الإرسال" المترجمة
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> ' + langData.sending;
            submitBtn.disabled = true;
            statusDiv.style.display = 'none';

            const formData = new FormData(this);

            fetch('ajax/submit_review.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                submitBtn.innerHTML = originalBtnText;
                submitBtn.disabled = false;
                
                statusDiv.style.display = 'block';
                
                if (data.success) {
                    statusDiv.style.color = 'var(--success-color)';
                    statusDiv.innerHTML = '<i class="fa-solid fa-check-circle"></i> ' + data.message;
                    this.reset();
                    
                    // إضافة الرأي الجديد إلى الواجهة فوراً
                    const reviewsGrid = document.getElementById('reviewsGrid');
                    if (reviewsGrid && data.html) {
                        reviewsGrid.insertAdjacentHTML('afterbegin', data.html);
                    }
                } else {
                    statusDiv.style.color = 'var(--danger-color)';
                    statusDiv.innerHTML = '<i class="fa-solid fa-exclamation-circle"></i> ' + data.message;
                }
            })
            .catch(error => {
                submitBtn.innerHTML = originalBtnText;
                submitBtn.disabled = false;
                statusDiv.style.display = 'block';
                statusDiv.style.color = 'var(--danger-color)';
                // استخدام رسالة الخطأ المترجمة
                statusDiv.innerHTML = langData.serverError;
            });
        });
    }
});