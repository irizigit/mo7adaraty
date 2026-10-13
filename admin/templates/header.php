<style>
/* تنسيقات زر الواجهة الطلابية المدمجة لضمان سرعة التطبيق */
.student-view-btn {
    background-color: var(--brand-light);
    color: var(--brand-primary);
    border: 1px solid var(--brand-primary);
    padding: 8px 16px;
    border-radius: 20px;
    text-decoration: none;
    font-size: 14px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
}
.student-view-btn:hover {
    background-color: var(--brand-primary);
    color: #ffffff;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(26, 115, 232, 0.2);
}

/* زر قائمة الموبايل الجديد */
.mobile-menu-btn {
    display: none;
    background: transparent;
    border: none;
    font-size: 24px;
    color: var(--text-primary);
    cursor: pointer;
    padding: 5px;
    margin-left: 15px;
}

/* إخفاء النص في الموبايل والاكتفاء بالأيقونة وإظهار زر القائمة */
@media (max-width: 992px) {
    .student-view-btn span.btn-text { display: none; }
    .student-view-btn { 
        padding: 8px; 
        border-radius: 50%; 
        width: 40px; 
        height: 40px; 
        justify-content: center; 
    }
    .mobile-menu-btn { display: block; }
}
</style>

<header class="admin-header">
    <div class="header-left">
        <button id="mobileToggleBtn" class="mobile-menu-btn" title="فتح القائمة">
            <i class="fa-solid fa-bars"></i>
        </button>
        
        <div class="breadcrumbs">
            <a href="/mo7adaraty/admin/index.php"><i class="fa-solid fa-home"></i> الإدارة</a>
            
            <?php if (isset($breadcrumbs) && is_array($breadcrumbs)): ?>
                <?php foreach ($breadcrumbs as $title => $link): ?>
                    <span class="breadcrumbs-separator"><i class="fa-solid fa-chevron-left"></i></span>
                    <?php if ($link): ?>
                        <a href="<?php echo htmlspecialchars($link); ?>"><?php echo htmlspecialchars($title); ?></a>
                    <?php else: ?>
                        <span style="color: var(--text-primary);"><?php echo htmlspecialchars($title); ?></span>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="header-right">
        <a href="/mo7adaraty/student/profil.php" target="_blank" class="student-view-btn" title="معاينة الواجهة الطلابية">
            <i class="fa-solid fa-graduation-cap"></i>
            <span class="btn-text">الواجهة الطلابية</span>
        </a>

        <button id="adminThemeToggle" class="theme-toggle-admin" title="تبديل المظهر"></button>
        
        <div style="display: flex; align-items: center; gap: 10px; font-weight: bold; color: var(--text-primary);">
            <img src="/mo7adaraty/admin/assets/icons/user-tie.png" alt="Admin" style="width: 35px; height: 35px; border-radius: 50%; border: 1px solid var(--border-color);" onerror="this.src='/mo7adaraty/assets/images/favicon.png'">

            <?php echo htmlspecialchars($_SESSION['username'] ?? 'المدير'); ?>
        </div>
    </div>
</header>