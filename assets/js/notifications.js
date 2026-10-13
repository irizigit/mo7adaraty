/* =========================================
   Notifications Page Scripts (notifications.js)
   ========================================= */
document.addEventListener('DOMContentLoaded', function() {
    
    // تهيئة حركات AOS
    if (typeof AOS !== 'undefined') {
        AOS.init({ duration: 600, once: true });
    }

    // 1. معالجة النقر على إشعار غير مقروء (لتحديده كمقروء قبل الانتقال)
    const notifCards = document.querySelectorAll('.notif-card');
    
    notifCards.forEach(card => {
        card.addEventListener('click', function(e) {
            const isUnread = this.classList.contains('unread');
            
            if (isUnread) {
                e.preventDefault(); // إيقاف الانتقال الفوري
                const notifId = this.dataset.id;
                const targetUrl = this.getAttribute('href');

                // إرسال طلب AJAX لتحديث حالة الإشعار
                const formData = new FormData();
                formData.append('ajax_action', 'mark_one');
                formData.append('notif_id', notifId);

                fetch('notifications.php', {
                    method: 'POST',
                    body: formData
                }).then(() => {
                    // الانتقال للرابط بعد نجاح التحديث
                    window.location.href = targetUrl;
                }).catch(() => {
                    // في حال فشل الاتصال، ننتقل للرابط على أي حال كإجراء احتياطي
                    window.location.href = targetUrl;
                });
            }
        });
    });

    // 2. معالجة زر "تحديد الكل كمقروء"
    const markAllBtn = document.getElementById('markAllReadBtn');
    
    if (markAllBtn) {
        markAllBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            const originalHtml = this.innerHTML;
            this.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> جاري التحديث...';
            this.style.pointerEvents = 'none';

            const formData = new FormData();
            formData.append('ajax_action', 'mark_all');

            fetch('notifications.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // تحديث الواجهة البصرية فوراً دون إعادة تحميل الصفحة
                    document.querySelectorAll('.notif-card.unread').forEach(card => {
                        card.classList.remove('unread');
                        const dot = card.querySelector('.unread-dot');
                        if (dot) dot.remove(); // إزالة النقطة الزرقاء النابضة
                    });

                    // تحديث الزر وتغيير حالته
                    this.innerHTML = '<i class="fa-solid fa-check-double"></i> تم تحديد الكل كمقروء';
                    this.style.backgroundColor = 'var(--success-color)';
                    this.style.color = '#ffffff';
                    this.style.borderColor = 'var(--success-color)';
                    
                    // إخفاء الزر بعد ثانيتين
                    setTimeout(() => {
                        this.style.opacity = '0';
                        setTimeout(() => this.remove(), 300);
                    }, 2000);
                    
                    // تحديث العداد في الهيدر الرئيسي إذا كان موجوداً
                    const headerBadge = document.querySelector('.nav__badge');
                    if (headerBadge) headerBadge.remove();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                this.innerHTML = originalHtml;
                this.style.pointerEvents = 'auto';
                alert('حدث خطأ أثناء محاولة التحديث. يرجى المحاولة لاحقاً.');
            });
        });
    }
});