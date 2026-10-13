/* =========================================
   Academic Structure Scripts (الشعب، الفصول، المواد)
   ========================================= */
document.addEventListener('DOMContentLoaded', function() {
    
    // 1. إزالة التحميل الهيكلي (Skeleton)
    setTimeout(() => {
        document.querySelectorAll('.skeleton').forEach(el => {
            el.classList.remove('skeleton');
        });
    }, 400);

    // 2. معاينة الصورة المرفوعة قبل إرسال النموذج (UX مميز)
    const imageInputs = document.querySelectorAll('.image-upload-input');
    
    imageInputs.forEach(input => {
        input.addEventListener('change', function() {
            const previewContainer = document.getElementById(this.dataset.previewTarget);
            
            if (this.files && this.files[0] && previewContainer) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    // إذا كان هناك صورة سابقة، استبدلها، وإلا أنشئ عنصراً جديداً
                    let img = previewContainer.querySelector('img');
                    if (!img) {
                        img = document.createElement('img');
                        previewContainer.appendChild(img);
                    }
                    img.src = e.target.result;
                    previewContainer.style.display = 'block';
                }
                
                reader.readAsDataURL(this.files[0]);
            } else if (previewContainer) {
                previewContainer.style.display = 'none';
                previewContainer.innerHTML = '';
            }
        });
    });

    // 3. تأكيد الحذف
    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (!confirm('تنبيه: هل أنت متأكد من رغبتك في الحذف؟ هذا الإجراء قد يحذف البيانات المرتبطة بهذا العنصر نهائياً.')) {
                e.preventDefault();
            }
        });
    });
});