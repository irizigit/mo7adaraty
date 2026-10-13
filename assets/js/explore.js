/* =========================================
   Explore Platform Scripts (explore.js)
   ========================================= */
document.addEventListener('DOMContentLoaded', function() {
    
    // 1. نظام التبويبات (Tabs Logic)
    const tabButtons = document.querySelectorAll('.explore-tab-btn');
    const tabPanes = document.querySelectorAll('.explore-tab-pane');

    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            // إزالة التفعيل من جميع الأزرار والمحتوى
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabPanes.forEach(pane => pane.classList.remove('active'));

            // تفعيل الزر الحالي والمحتوى المرتبط به
            button.classList.add('active');
            const targetId = button.getAttribute('data-target');
            document.getElementById(targetId).classList.add('active');
        });
    });

    // 2. نظام المحتوى المغلق (Gated Content Modal)
    const authModal = document.getElementById('authModal');
    const closeBtn = document.getElementById('closeModal');
    const requiresAuthElements = document.querySelectorAll('.requires-auth');
    
    // متغير يتم تمريره من PHP لمعرفة حالة المستخدم
    const isGuest = window.ExploreConfig ? window.ExploreConfig.isGuest : false;

    // اعتراض النقرات على العناصر المحمية
    requiresAuthElements.forEach(element => {
        element.addEventListener('click', function(e) {
            if (isGuest) {
                e.preventDefault(); // إيقاف التحميل أو الانتقال
                showModal();
            }
            // إذا لم يكن زائراً، سيستمر الحدث الطبيعي للعنصر
        });
    });

    function showModal() {
        if (authModal) {
            authModal.classList.add('show');
            document.body.style.overflow = 'hidden'; // منع التمرير في الخلفية
        }
    }

    function closeModal() {
        if (authModal) {
            authModal.classList.remove('show');
            document.body.style.overflow = ''; // استعادة التمرير
        }
    }

    // إغلاق النافذة عند النقر على زر الإغلاق أو خارج النافذة
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (authModal) {
        authModal.addEventListener('click', function(e) {
            if (e.target === authModal) {
                closeModal();
            }
        });
    }
});