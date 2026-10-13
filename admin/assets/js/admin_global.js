/* =========================================
   Admin Global Scripts (admin_global.js)
   ========================================= */
document.addEventListener('DOMContentLoaded', function() {

    // 1. نظام إزالة التحميل الهيكلي (Skeleton Loading)
    // يحاكي تحميل البيانات، نزيل الكلاس بمجرد اكتمال تحميل الـ DOM
    setTimeout(() => {
        document.querySelectorAll('.skeleton').forEach(el => {
            el.classList.remove('skeleton');
        });
    }, 500); // تأخير بسيط لإظهار التأثير الجمالي

    // 2. نظام التجاوب للسايدبار (Mobile Sidebar Toggle)
    const toggleBtn = document.getElementById('toggleSidebar');
    const sidebar = document.getElementById('adminSidebar');
    
    // إنشاء طبقة التظليل للخلفية في الموبايل
    const overlay = document.createElement('div');
    overlay.className = 'sidebar-overlay';
    document.body.appendChild(overlay);

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
        });

        overlay.addEventListener('click', function() {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });
    }

    // 3. نظام الوضع المظلم (Dark Mode Toggle)
    const themeToggleBtn = document.getElementById('adminThemeToggle');
    if (themeToggleBtn) {
        // تحديث الأيقونة بناءً على الوضع الحالي
        const currentTheme = document.documentElement.getAttribute('data-theme');
        themeToggleBtn.innerHTML = currentTheme === 'dark' ? '<i class="fa-solid fa-sun"></i>' : '<i class="fa-solid fa-moon"></i>';

        themeToggleBtn.addEventListener('click', function() {
            let theme = document.documentElement.getAttribute('data-theme');
            let newTheme = theme === 'dark' ? 'light' : 'dark';
            
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('mo7adaraty_theme', newTheme);
            
            this.innerHTML = newTheme === 'dark' ? '<i class="fa-solid fa-sun"></i>' : '<i class="fa-solid fa-moon"></i>';
        });
    }

    // 4. تأثير الاستجابة الذكية لفلتر الإحصائيات (السنوات الجامعية)
    const yearFilterSelect = document.getElementById('year_filter');
    if (yearFilterSelect) {
        yearFilterSelect.addEventListener('change', function() {
            // إضافة كلاس skeleton لبطاقات الإحصائيات فور تغيير القائمة 
            // لإعطاء إحساس بصري سلس بالتحميل ريثما تتحدث الصفحة لجلب الإحصائيات الجديدة
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach(card => {
                card.classList.add('skeleton');
                // يمكننا إخفاء المحتوى مؤقتاً لزيادة جمالية التأثير
                const statInfoHtml = card.querySelector('.stat-info').innerHTML;
                card.querySelector('.stat-info').style.opacity = '0.3';
            });
            
            // إضافة تأثير للجدول أيضاً
            const tableContainer = document.querySelector('.admin-table-container');
            if(tableContainer) tableContainer.classList.add('skeleton');
        });
    }

});