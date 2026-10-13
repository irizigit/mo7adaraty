/* =========================================
   Global Scripts (main.js)
   ========================================= */
document.addEventListener('DOMContentLoaded', function() {
    // 1. نظام قائمة الموبايل (Mobile Menu)
    const menuToggle = document.getElementById('menuToggle');
    const nav = document.getElementById('mainNav');
    const notificationsMenu = document.querySelector('.notifications-menu');

    if(menuToggle) {
        menuToggle.addEventListener('click', function(event) {
            event.stopPropagation();
            nav.classList.toggle('open');
        });
    }
    
    // إخفاء القائمة عند الضغط في أي مكان خارجها
    document.addEventListener('click', function(event) {
        if (nav && !nav.contains(event.target) && !menuToggle.contains(event.target)) {
            nav.classList.remove('open');
        }
    });
    
    // تحسين تجربة الإشعارات على الجوال
    if (window.innerWidth <= 768 && notificationsMenu) {
        const notificationsLink = notificationsMenu.querySelector('a');
        
        notificationsLink.addEventListener('click', function(e) {
            e.preventDefault();
            notificationsMenu.classList.toggle('active');
        });
    }

    // 2. تفعيل الأنيميشن عند التمرير (AOS) إذا كانت المكتبة موجودة
    if (typeof AOS !== 'undefined') {
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true
        });
    }
});