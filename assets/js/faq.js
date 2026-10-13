/* =========================================
   FAQ Page Scripts (faq.js)
   ========================================= */
document.addEventListener('DOMContentLoaded', function() {
    
    // 1. Accordion Logic (فتح وإغلاق الأسئلة)
    const faqItems = document.querySelectorAll('.faq-item');
    
    faqItems.forEach(item => {
        const questionBtn = item.querySelector('.faq-question');
        const answerWrapper = item.querySelector('.faq-answer-wrapper');
        
        questionBtn.addEventListener('click', () => {
            const isActive = item.classList.contains('active');
            
            // إغلاق جميع الأسئلة المفتوحة (اختياري، يجعله أكثر ترتيباً)
            faqItems.forEach(otherItem => {
                otherItem.classList.remove('active');
                otherItem.querySelector('.faq-answer-wrapper').style.maxHeight = null;
            });
            
            // إذا لم يكن السؤال الحالي مفتوحاً، افتحه
            if (!isActive) {
                item.classList.add('active');
                answerWrapper.style.maxHeight = answerWrapper.scrollHeight + "px";
            }
        });
    });

    // 2. Live Search Logic (شريط البحث الفوري)
    const searchInput = document.getElementById('faqSearchInput');
    const faqCategories = document.querySelectorAll('.faq-category-section');
    const noResultsBox = document.getElementById('faqNoResults');

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            let totalVisibleItems = 0;

            faqCategories.forEach(category => {
                const items = category.querySelectorAll('.faq-item');
                let categoryHasVisibleItems = false;

                items.forEach(item => {
                    const questionText = item.querySelector('.faq-question').textContent.toLowerCase();
                    const answerText = item.querySelector('.faq-answer').textContent.toLowerCase();

                    // البحث في السؤال والجواب
                    if (questionText.includes(searchTerm) || answerText.includes(searchTerm)) {
                        item.style.display = 'block';
                        categoryHasVisibleItems = true;
                        totalVisibleItems++;
                        
                        // تظليل النص المطابق (اختياري ومتقدم، نكتفي بالفلترة حالياً لضمان السرعة)
                    } else {
                        item.style.display = 'none';
                        // إغلاق العنصر المخفي إذا كان مفتوحاً
                        item.classList.remove('active');
                        item.querySelector('.faq-answer-wrapper').style.maxHeight = null;
                    }
                });

                // إخفاء التصنيف بأكمله إذا لم يكن هناك أي سؤال يطابق البحث فيه
                if (categoryHasVisibleItems) {
                    category.style.display = 'block';
                } else {
                    category.style.display = 'none';
                }
            });

            // إظهار رسالة "لا توجد نتائج" إذا لم يتم العثور على شيء
            if (totalVisibleItems === 0 && searchTerm !== '') {
                noResultsBox.classList.add('show');
            } else {
                noResultsBox.classList.remove('show');
            }
        });
    }
});