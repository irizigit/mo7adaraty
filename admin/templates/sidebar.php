<?php
// الحصول على مسار الملف الحالي لتحديد الـ Active State
$current_script = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

// دالة مساعدة لتحديد الكلاس النشط
function isActive($dir_name, $current_dir, $current_script) {
    if ($dir_name === 'admin' && $current_dir === 'admin' && $current_script === 'index.php') return 'active';
    if ($dir_name === $current_dir) return 'active';
    return '';
}

$base_admin_url = '/mo7adaraty/admin/';
$img_path = $base_admin_url . 'dashboard_design/images/';

// === نظام الإشعارات الذكي في السايد بار ===
$unread_messages_count = 0; // رسائل الزوار
$pending_reviews_count = 0; // مراجعة الآراء
$pending_enrollments_count = 0; // طلبات التسجيل
$unread_admin_msgs_count = 0; // الرسائل (messages)
$pending_resets_count = 0; // طلبات إعادة تعيين كلمة المرور

if (isset($pdo)) {
    // 1. رسائل الزوار
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM visitor_messages WHERE is_read = 0");
        if ($stmt) $unread_messages_count = $stmt->fetchColumn();
    } catch (PDOException $e) {}

    // 2. الآراء
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM reviews WHERE status = 'pending'");
        if ($stmt) $pending_reviews_count = $stmt->fetchColumn();
    } catch (PDOException $e) {}

    // 3. طلبات التسجيل
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM enrollment_change_requests WHERE status = 'pending'");
        if ($stmt) $pending_enrollments_count = $stmt->fetchColumn();
    } catch (PDOException $e) {}

    // 4. صندوق الرسائل (admin/messages)
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM messages WHERE is_read = 0");
        if ($stmt) $unread_admin_msgs_count = $stmt->fetchColumn();
    } catch (PDOException $e) {}

    // 5. طلبات إعادة تعيين كلمة المرور
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM reset_requests WHERE status = 'pending'");
        if ($stmt) $pending_resets_count = $stmt->fetchColumn();
    } catch (PDOException $e) {}
}
?>

<aside class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-header" style="display: flex; align-items: center; justify-content: space-between; padding: 15px 20px; transition: all 0.3s;">
        <img src="<?php echo $img_path; ?>admin_logo.png" alt="Logo" class="sidebar-logo sidebar-text" onerror="this.src='/mo7adaraty/assets/images/favicon.png'; this.style.height='40px';">
        <button id="toggleSidebarBtn" class="toggle-btn" title="طي/توسيع القائمة">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
        </button>
    </div>
    
    <ul class="sidebar-menu">
        <li class="sidebar-item <?php echo isActive('admin', $current_dir, $current_script); ?>">
            <a href="<?php echo $base_admin_url; ?>index.php" class="sidebar-link">
                <img src="<?php echo $img_path; ?>admin_icon_dashboard.png" class="sidebar-icon" alt="Dashboard">
                <span class="sidebar-text">لوحة التحكم</span>
            </a>
        </li>

        <li class="menu-header"><span class="sidebar-text">الإدارة الأكاديمية</span></li>
        <li class="sidebar-item <?php echo isActive('academic_years', $current_dir, $current_script); ?>">
            <a href="<?php echo $base_admin_url; ?>academic_years/index.php" class="sidebar-link">
                <img src="<?php echo $img_path; ?>admin_icon_academic_years.png" class="sidebar-icon" alt="Years">
                <span class="sidebar-text">السنة الجامعية</span>
            </a>
        </li>
        <li class="sidebar-item <?php echo isActive('divisions', $current_dir, $current_script); ?>">
            <a href="<?php echo $base_admin_url; ?>divisions/index.php" class="sidebar-link">
                <img src="<?php echo $img_path; ?>admin_icon_divisions.png" class="sidebar-icon" alt="Divisions">
                <span class="sidebar-text">الشعب</span>
            </a>
        </li>
        <li class="sidebar-item <?php echo isActive('tracks', $current_dir, $current_script); ?>">
            <a href="<?php echo $base_admin_url; ?>tracks/index.php" class="sidebar-link">
                <img src="<?php echo $img_path; ?>admin_icon_tracks.png" class="sidebar-icon" alt="Tracks">
                <span class="sidebar-text">المسارات</span>
            </a>
        </li>
        <li class="sidebar-item <?php echo isActive('classes', $current_dir, $current_script); ?>">
            <a href="<?php echo $base_admin_url; ?>classes/index.php" class="sidebar-link">
                <img src="<?php echo $img_path; ?>admin_icon_classes.svg" class="sidebar-icon" alt="Classes">
                <span class="sidebar-text">الفصول</span>
            </a>
        </li>
        <li class="sidebar-item <?php echo isActive('groups', $current_dir, $current_script); ?>">
            <a href="<?php echo $base_admin_url; ?>groups/index.php" class="sidebar-link">
                <img src="<?php echo $img_path; ?>admin_icon_groups.png" class="sidebar-icon" alt="Groups">
                <span class="sidebar-text">الأفواج</span>
            </a>
        </li>
        <li class="sidebar-item <?php echo isActive('subjects', $current_dir, $current_script); ?>">
            <a href="<?php echo $base_admin_url; ?>subjects/index.php" class="sidebar-link">
                <img src="<?php echo $img_path; ?>admin_icon_subjects.png" class="sidebar-icon" alt="Subjects">
                <span class="sidebar-text">المواد</span>
            </a>
        </li>
        <li class="sidebar-item <?php echo isActive('professors', $current_dir, $current_script); ?>">
            <a href="<?php echo $base_admin_url; ?>professors/index.php" class="sidebar-link">
                <img src="<?php echo $img_path; ?>admin_icon_professors.png" class="sidebar-icon" alt="Professors">
                <span class="sidebar-text">الأساتذة</span>
            </a>
        </li>
<li class="menu-header"><span class="sidebar-text">شؤون المستخدمين</span></li>
<li class="sidebar-item <?php echo isActive('admins', $current_dir, $current_script); ?>">
    <a href="<?php echo $base_admin_url; ?>admins/index.php" class="sidebar-link" style="color: var(--danger-color);">
        <img src="<?php echo $img_path; ?>admin_icon_admins.png" class="sidebar-icon" alt="Admins">
        <span class="sidebar-text">إدارة المشرفين</span>
    </a>
</li>
        <li class="sidebar-item <?php echo isActive('users', $current_dir, $current_script); ?>">
            <a href="<?php echo $base_admin_url; ?>users/index.php" class="sidebar-link">
                <img src="<?php echo $img_path; ?>admin_icon_users.png" class="sidebar-icon" alt="Users">
                <span class="sidebar-text">الطلاب والمستخدمين</span>
            </a>
        </li>
        <li class="sidebar-item <?php echo isActive('enrollment_requests', $current_dir, $current_script); ?>">
            <a href="<?php echo $base_admin_url; ?>enrollment_requests/index.php" class="sidebar-link notification-link">
                <div class="link-content">
                    <img src="<?php echo $img_path; ?>admin_icon_enrollment_requests.png" class="sidebar-icon" alt="Enrollment Requests">
                    <span class="sidebar-text">طلبات التسجيل</span>
                </div>
                <?php if ($pending_enrollments_count > 0): ?>
                    <span class="sidebar-badge pulse-badge"><?php echo $pending_enrollments_count > 99 ? '+99' : $pending_enrollments_count; ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li class="sidebar-item <?php echo isActive('reset_requests', $current_dir, $current_script); ?>">
            <a href="<?php echo $base_admin_url; ?>reset_requests/index.php" class="sidebar-link notification-link">
                <div class="link-content">
                    <img src="<?php echo $img_path; ?>admin_icon_reset_requests.png" class="sidebar-icon" alt="Reset Requests" onerror="this.src='../assets/icons/circle-exclamation.png'">
                    <span class="sidebar-text">إعادة تعيين كلمة المرور</span>
                </div>
                <?php if ($pending_resets_count > 0): ?>
                    <span class="sidebar-badge pulse-badge"><?php echo $pending_resets_count > 99 ? '+99' : $pending_resets_count; ?></span>
                <?php endif; ?>
            </a>
        </li>

        <li class="menu-header"><span class="sidebar-text">الإدارة التعليمية</span></li>
        <li class="sidebar-item <?php echo isActive('offerings', $current_dir, $current_script); ?>">
            <a href="<?php echo $base_admin_url; ?>offerings/index.php" class="sidebar-link">
                <img src="<?php echo $img_path; ?>admin_icon_offerings.png" class="sidebar-icon" alt="Offerings" onerror="this.src='../assets/icons/book-open.png'">
                <span class="sidebar-text">إدارة المقررات المتاحة</span>
            </a>
        </li>
        <li class="sidebar-item <?php echo isActive('lectures', $current_dir, $current_script); ?>">
            <a href="<?php echo $base_admin_url; ?>lectures/index.php" class="sidebar-link">
                <img src="<?php echo $img_path; ?>admin_icon_lectures.png" class="sidebar-icon" alt="Lectures">
                <span class="sidebar-text">إدارة المحاضرات</span>
            </a>
        </li>
        <li class="sidebar-item <?php echo isActive('exams', $current_dir, $current_script); ?>">
            <a href="<?php echo $base_admin_url; ?>exams/index.php" class="sidebar-link">
                <img src="<?php echo $img_path; ?>admin_icon_exams.png" class="sidebar-icon" alt="Exams">
                <span class="sidebar-text">إدارة الامتحانات</span>
            </a>
        </li>
        <li class="sidebar-item <?php echo isActive('student_uploads', $current_dir, $current_script); ?>">
    <a href="<?php echo $base_admin_url; ?>student_uploads/index.php" class="sidebar-link">
        <img src="<?php echo $img_path; ?>admin_icon_uploads.png" class="sidebar-icon" alt="Student Uploads">
        <span class="sidebar-text">مرفوعات الطلاب</span>
    </a>
</li>
        <li class="sidebar-item <?php echo isActive('timetables', $current_dir, $current_script); ?>">
            <a href="<?php echo $base_admin_url; ?>timetables/index.php" class="sidebar-link">
                <img src="<?php echo $img_path; ?>admin_icon_dashboard.png" class="sidebar-icon" alt="Timetables">
                <span class="sidebar-text">جداول الحصص</span>
            </a>
        </li>
        <li class="sidebar-item <?php echo isActive('whatsapp_groups', $current_dir, $current_script); ?>">
            <a href="<?php echo $base_admin_url; ?>whatsapp_groups/index.php" class="sidebar-link">
                <img src="<?php echo $img_path; ?>admin_icon_whatsapp.png" class="sidebar-icon" alt="WhatsApp">
                <span class="sidebar-text">مجموعات الواتساب</span>
            </a>
        </li>

        <li class="menu-header"><span class="sidebar-text">التواصل والرقابة</span></li>
        <li class="sidebar-item <?php echo isActive('messages', $current_dir, $current_script); ?>">
            <a href="<?php echo $base_admin_url; ?>messages/index.php" class="sidebar-link notification-link">
                <div class="link-content">
                    <img src="<?php echo $img_path; ?>admin_icon_messages.png" class="sidebar-icon" alt="Messages" onerror="this.src='../assets/icons/inbox.png'">
                    <span class="sidebar-text">صندوق الرسائل</span>
                </div>
                <?php if ($unread_admin_msgs_count > 0): ?>
                    <span class="sidebar-badge pulse-badge"><?php echo $unread_admin_msgs_count > 99 ? '+99' : $unread_admin_msgs_count; ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li class="sidebar-item <?php echo isActive('visitor_messages', $current_dir, $current_script); ?>">
            <a href="<?php echo $base_admin_url; ?>visitor_messages/index.php" class="sidebar-link notification-link">
                <div class="link-content">
                    <img src="<?php echo $img_path; ?>admin_icon_messages.png" class="sidebar-icon" alt="Visitor Messages">
                    <span class="sidebar-text">رسائل الزوار</span>
                </div>
                <?php if ($unread_messages_count > 0): ?>
                    <span class="sidebar-badge pulse-badge"><?php echo $unread_messages_count > 99 ? '+99' : $unread_messages_count; ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li class="sidebar-item <?php echo isActive('post_reviews', $current_dir, $current_script); ?>">
            <a href="<?php echo $base_admin_url; ?>post_reviews/index.php" class="sidebar-link">
                <img src="<?php echo $img_path; ?>admin_icon_post_reviews.png" class="sidebar-icon" alt="Post Reviews">
                <span class="sidebar-text">مراجعة المنشورات</span>
            </a>
        </li>
        <li class="sidebar-item <?php echo isActive('reviews', $current_dir, $current_script); ?>">
            <a href="<?php echo $base_admin_url; ?>reviews/index.php" class="sidebar-link notification-link">
                <div class="link-content">
                    <img src="<?php echo $img_path; ?>admin_icon_reviews.png" class="sidebar-icon" alt="Reviews">
                    <span class="sidebar-text">مراجعة الآراء</span>
                </div>
                <?php if ($pending_reviews_count > 0): ?>
                    <span class="sidebar-badge pulse-badge"><?php echo $pending_reviews_count > 99 ? '+99' : $pending_reviews_count; ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li class="sidebar-item <?php echo isActive('reports', $current_dir, $current_script); ?>">
            <a href="<?php echo $base_admin_url; ?>reports/index.php" class="sidebar-link">
                <img src="<?php echo $img_path; ?>admin_icon_reports.png" class="sidebar-icon" alt="Reports">
                <span class="sidebar-text">البلاغات</span>
            </a>
        </li>
        <li class="sidebar-item <?php echo ($current_script == 'publish_announcement.php') ? 'active' : ''; ?>">
            <a href="<?php echo $base_admin_url; ?>publish_announcement.php" class="sidebar-link">
                <img src="<?php echo $img_path; ?>admin_icon_announcement.png" class="sidebar-icon" alt="Announcement">
                <span class="sidebar-text">نشر إعلان عام</span>
            </a>
        </li>

        <li class="menu-header"><span class="sidebar-text">إعدادات النظام</span></li>
        <li class="sidebar-item <?php echo isActive('design_settings', $current_dir, $current_script); ?>">
            <a href="<?php echo $base_admin_url; ?>design_settings/index.php" class="sidebar-link">
                <img src="<?php echo $img_path; ?>admin_icon_design.png" class="sidebar-icon" alt="Design">
                <span class="sidebar-text">إعدادات التصميم</span>
            </a>
        </li>
        <li class="sidebar-item" style="margin-top: 10px;">
            <a href="/mo7adaraty/logout.php" class="sidebar-link" style="color: var(--danger-color);">
                <img src="<?php echo $img_path; ?>admin_icon_logout.png" class="sidebar-icon" alt="Logout" onerror="this.src='../assets/icons/delete.png'">
                <span class="sidebar-text">تسجيل الخروج</span>
            </a>
        </li>
    </ul>
</aside>

<style>
    /* عناوين المجموعات */
    .menu-header {
        padding: 15px 20px 5px 20px;
        font-size: 11px;
        font-weight: bold;
        text-transform: uppercase;
        color: #888;
        letter-spacing: 0.5px;
        list-style: none;
        white-space: nowrap;
        transition: all 0.2s ease;
    }
    
    #adminSidebar.collapsed .menu-header {
        padding: 0;
        height: 0;
        margin: 0;
        opacity: 0;
        overflow: hidden;
    }
    
    #adminSidebar.collapsed:hover .menu-header {
        height: auto;
        padding: 15px 20px 5px 20px;
        opacity: 1;
        text-align: right;
    }

    #adminSidebar.collapsed .sidebar-header {
        justify-content: center !important;
        padding: 15px 0;
    }

    .sidebar-link, .notification-link .link-content {
        display: flex;
        align-items: center;
        gap: 12px;
        text-decoration: none;
        white-space: nowrap;
    }
    
    .notification-link {
        display: flex !important;
        align-items: center;
        justify-content: space-between;
        position: relative;
    }

    #adminSidebar {
        width: 260px;
        transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1), margin 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        overflow-x: hidden;
        overflow-y: auto;
        z-index: 1000;
        background: var(--bg-primary, #fff);
        border-left: 1px solid var(--border-color, #eee);
    }
    
    #adminSidebar::-webkit-scrollbar {
        width: 5px;
    }
    #adminSidebar::-webkit-scrollbar-thumb {
        background-color: rgba(0,0,0,0.1);
        border-radius: 5px;
    }

    #adminSidebar.collapsed {
        width: 80px;
    }

    #adminSidebar.collapsed:hover {
        width: 260px;
        margin-left: -180px;
        box-shadow: -5px 0 25px rgba(0,0,0,0.1);
    }

    .sidebar-text {
        transition: opacity 0.2s ease, visibility 0.2s ease;
        opacity: 1;
        visibility: visible;
    }

    #adminSidebar.collapsed .sidebar-text {
        opacity: 0;
        visibility: hidden;
    }

    #adminSidebar.collapsed:hover .sidebar-text {
        opacity: 1;
        visibility: visible;
    }

    .toggle-btn {
        background: transparent;
        border: none;
        color: var(--text-primary);
        cursor: pointer;
        padding: 8px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background-color 0.2s ease;
    }
    .toggle-btn:hover {
        background-color: rgba(0, 0, 0, 0.05);
    }

    .sidebar-badge {
        background-color: #dc3545;
        color: white;
        font-family: Arial, sans-serif;
        font-size: 11px;
        font-weight: bold;
        padding: 2px 6px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 20px;
        height: 20px;
        transition: all 0.3s ease;
    }

    #adminSidebar.collapsed .sidebar-badge {
        position: absolute;
        left: 15px;
        transform: scale(0.5) translateY(-10px);
        font-size: 0;
        min-width: 15px;
        height: 15px;
    }

    #adminSidebar.collapsed:hover .sidebar-badge {
        position: static;
        transform: scale(1) translateY(0);
        font-size: 11px;
    }

    .pulse-badge {
        box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7);
        animation: pulse-red 2s infinite;
    }
    @keyframes pulse-red {
        0% { transform: scale(0.95); }
        70% { transform: scale(1.05); box-shadow: 0 0 0 6px rgba(220, 53, 69, 0); }
        100% { transform: scale(0.95); }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('adminSidebar');
    const toggleBtn = document.getElementById('toggleSidebarBtn'); // زر السايدبار الداخلي
    const mobileBtn = document.getElementById('mobileToggleBtn'); // زر الهيدر الجديد للهاتف
    const mainContent = document.querySelector('.admin-main');
    
    // استرجاع حالة السايدبار
    const isCollapsed = localStorage.getItem('mo7adaraty_sidebar_collapsed') === 'true';
    
    // تطبيق الطي فقط على الشاشات الكبيرة لتفادي الإخفاء المزدوج
    if (isCollapsed && window.innerWidth > 992) {
        sidebar.classList.add('collapsed');
        if (mainContent) mainContent.classList.add('expanded');
    }

    // دالة التحكم المرنة للإغلاق والفتح
    function toggleSidebar() {
        sidebar.classList.toggle('collapsed');
        const collapsedNow = sidebar.classList.contains('collapsed');
        
        if (window.innerWidth > 992 && mainContent) {
            mainContent.classList.toggle('expanded', collapsedNow);
        }
        
        localStorage.setItem('mo7adaraty_sidebar_collapsed', collapsedNow);
    }

    // ربط الأزرار بالدالة
    if (toggleBtn) toggleBtn.addEventListener('click', toggleSidebar);
    if (mobileBtn) mobileBtn.addEventListener('click', toggleSidebar);
});
</script>