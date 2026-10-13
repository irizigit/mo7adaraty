<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../config/db_connexion.php';

// حماية الصفحة والتحقق من الصلاحيات
if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

$breadcrumbs = [
    "الإعدادات" => "",
    "إعدادات التصميم المركزية" => ""
];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إعدادات التصميم - Mo7adaraty</title>
    <link rel="icon" type="image/svg+xml" href="../../assets/images/favicon.svg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/admin_global.css">
    <link rel="stylesheet" href="../assets/css/admin_academic.css">
    
    <style>
        .design-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px; margin-top: 10px; }
        .design-card { background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 35px 20px; text-align: center; box-shadow: var(--shadow-sm); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); display: flex; flex-direction: column; align-items: center; gap: 15px; text-decoration: none; position: relative; overflow: hidden; z-index: 1; }
        .design-card::before { content: ''; position: absolute; top: 0; right: 0; width: 100%; height: 4px; background: var(--brand-primary); opacity: 0; transition: 0.3s; z-index: 0; }
        .design-card:hover { transform: translateY(-8px); box-shadow: var(--shadow-md); border-color: var(--brand-primary); }
        .design-card:hover::before { opacity: 1; }
        .design-icon { font-size: 35px; color: var(--brand-primary); background: var(--brand-light); width: 80px; height: 80px; display: flex; align-items: center; justify-content: center; border-radius: 50%; transition: 0.3s; position: relative; z-index: 2; }
        .design-card:hover .design-icon { background: var(--brand-primary); color: #fff; transform: scale(1.1) rotate(-5deg); }
        .design-title { font-size: 18px; font-weight: 900; color: var(--text-primary); margin: 5px 0 0 0; position: relative; z-index: 2; }
        .design-desc { font-size: 13px; color: var(--text-secondary); margin: 0; line-height: 1.6; font-weight: bold; position: relative; z-index: 2; }

        /* --- تخصيص ألوان الأقسام --- */
        .card-global:hover { border-color: #8b5cf6; } .card-global:hover::before { background: #8b5cf6; } .card-global .design-icon { color: #8b5cf6; background: rgba(139, 92, 246, 0.1); } .card-global:hover .design-icon { background: #8b5cf6; color: #fff; }
        .card-dash:hover { border-color: #10b981; } .card-dash:hover::before { background: #10b981; } .card-dash .design-icon { color: #10b981; background: rgba(16, 185, 129, 0.1); } .card-dash:hover .design-icon { background: #10b981; color: #fff; }
        .card-auth:hover { border-color: #f59e0b; } .card-auth:hover::before { background: #f59e0b; } .card-auth .design-icon { color: #f59e0b; background: rgba(245, 158, 11, 0.1); } .card-auth:hover .design-icon { background: #f59e0b; color: #fff; }
        .card-academic:hover { border-color: #1d4ed8; } .card-academic:hover::before { background: #1d4ed8; } .card-academic .design-icon { color: #1d4ed8; background: rgba(29, 78, 216, 0.1); } .card-academic:hover .design-icon { background: #1d4ed8; color: #fff; }
        .card-loader:hover { border-color: #ef4444; } .card-loader:hover::before { background: #ef4444; } .card-loader .design-icon { color: #ef4444; background: rgba(239, 68, 68, 0.1); } .card-loader:hover .design-icon { background: #ef4444; color: #fff; }
        .card-home:hover { border-color: #0ea5e9; } .card-home:hover::before { background: #0ea5e9; } .card-home .design-icon { color: #0ea5e9; background: rgba(14, 165, 233, 0.1); } .card-home:hover .design-icon { background: #0ea5e9; color: #fff; }
    </style>
</head>
<body class="admin-body">

    <script>
        (function() {
            const savedTheme = localStorage.getItem('mo7adaraty_theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>

    <?php include '../templates/sidebar.php'; ?>

    <main class="admin-main">
        <?php include '../templates/header.php'; ?>

        <div class="admin-content-wrapper">
            
            <div class="academic-header-actions skeleton" style="border-bottom: 1px dashed var(--border-color); padding-bottom: 20px; margin-bottom: 30px;">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div style="width: 50px; height: 50px; background: rgba(255, 193, 7, 0.1); color: var(--warning-color); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px;">
                        <i class="fa-solid fa-wand-magic-sparkles"></i>
                    </div>
                    <div>
                        <h2 style="margin: 0; color: var(--text-primary); font-weight: 900;">مركز إعدادات التصميم</h2>
                        <p style="margin: 5px 0 0 0; color: var(--text-secondary); font-size: 14px; font-weight: bold;">تحكم كامل في الأيقونات، الصور، والهوية البصرية للمنصة.</p>
                    </div>
                </div>
            </div>

            <div class="design-grid skeleton">
                
                <a href="global_icons/index.php" class="design-card card-global">
                    <div class="design-icon"><i class="fa-solid fa-icons"></i></div>
                    <h3 class="design-title">الأيقونات العامة (Global)</h3>
                    <p class="design-desc">إدارة الأزرار والأيقونات المتكررة بصيغة PNG أو SVG لضمان توحيد الهوية.</p>
                </a>

                <a href="dashboard/index.php" class="design-card card-dash">
                    <div class="design-icon"><i class="fa-solid fa-gauge-high"></i></div>
                    <h3 class="design-title">تصميم لوحة التحكم</h3>
                    <p class="design-desc">تخصيص الشعار وأيقونات القائمة الجانبية (Sidebar) الخاصة بالإدارة.</p>
                </a>
                
                <a href="header/index.php" class="design-card">
                    <div class="design-icon"><i class="fa-solid fa-heading"></i></div>
                    <h3 class="design-title">تصميم الهيدر العام</h3>
                    <p class="design-desc">إدارة الشعار والأيقونات العلوية (الإشعارات، الوضع المظلم، وغيرها).</p>
                </a>

                <a href="home/index.php" class="design-card card-home">
                    <div class="design-icon"><i class="fa-solid fa-house"></i></div>
                    <h3 class="design-title">تصميم الصفحة الرئيسية</h3>
                    <p class="design-desc">إدارة صور العرض التوضيحية (Sneak Peek) بمختلف اللغات والخلفيات.</p>
                </a>

                <a href="loader/index.php" class="design-card card-loader">
                    <div class="design-icon"><i class="fa-solid fa-spinner"></i></div>
                    <h3 class="design-title">شاشة التحميل (Loader)</h3>
                    <p class="design-desc">تخصيص شعار وأيقونات خطوات شاشة التحميل قبل دخول المنصة.</p>
                </a>

                <a href="login/index.php" class="design-card card-auth">
                    <div class="design-icon"><i class="fa-solid fa-right-to-bracket"></i></div>
                    <h3 class="design-title">صفحة الدخول (Login)</h3>
                    <p class="design-desc">إدارة خلفيات الشاشة والبطاقة وأيقونات الحقول في صفحة تسجيل الدخول.</p>
                </a>

                <a href="signup/index.php" class="design-card card-auth">
                    <div class="design-icon"><i class="fa-solid fa-user-plus"></i></div>
                    <h3 class="design-title">صفحة التسجيل (Signup)</h3>
                    <p class="design-desc">تخصيص أيقونات الحقول وأيقونات اللوحة الجانبية والمميزات.</p>
                </a>

                <a href="forgot_password/index.php" class="design-card card-auth">
                    <div class="design-icon"><i class="fa-solid fa-shield-halved"></i></div>
                    <h3 class="design-title">صفحة استرجاع المرور</h3>
                    <p class="design-desc">إدارة أيقونة الدرع، وأيقونات الحقول لصفحة استعادة كلمة المرور.</p>
                </a>

                <a href="student_profile/index.php" class="design-card card-academic">
                    <div class="design-icon"><i class="fa-solid fa-id-card-clip"></i></div>
                    <h3 class="design-title">الملف الشخصي للطالب</h3>
                    <p class="design-desc">إدارة الخلفيات والصور وأيقونات الإحصائيات الخاصة بصفحة الملف الشخصي.</p>
                </a>

                <a href="class_lectures/index.php" class="design-card card-academic">
                    <div class="design-icon"><i class="fa-solid fa-chalkboard-user"></i></div>
                    <h3 class="design-title">صفحة المحاضرات والملفات</h3>
                    <p class="design-desc">تخصيص أيقونات أنواع الملفات (PDF, Video...)، وأزرار التفاعل الأكاديمي.</p>
                </a>
                <a href="AboutUs/index.php" class="design-card card-academic">
                    <div class="design-icon"><i class="fa-solid fa-users"></i></div>
                    <h3 class="design-title">صفحة من نحن (About Us)</h3>
                    <p class="design-desc">إدارة أيقونات أعضاء الفريق، وأيقونات التواصل الاجتماعي لصفحة من نحن.</p>
                </a>
                <a href="ContactUs/index.php" class="design-card card-academic">
                    <div class="design-icon"><i class="fa-solid fa-address-book"></i></div>
                    <h3 class="design-title">صفحة اتصل بنا (Contact Us)</h3>
                    <p class="design-desc">تخصيص أيقونات بطاقات التواصل، وأيقونات أعضاء الفريق لصفحة اتصل بنا.</p>
                </a>
                <a href="Explore/index.php" class="design-card card-academic">
                 <div class="design-icon"><i class="fa-solid fa-compass"></i></div>
                <h3 class="design-title">تصميم صفحة الاستكشاف</h3>
                 <p class="design-desc">إدارة أيقونات التبويبات، الحالات الفارغة للبيانات، وأزرار التفاعل (التحميل، التعليق...).</p>
                </a>
                <a href="FAQ/index.php" class="design-card card-academic">
                    <div class="design-icon"><i class="fa-solid fa-question"></i></div>
                    <h3 class="design-title">صفحة الأسئلة الشائعة (FAQ)</h3>
                    <p class="design-desc">تخصيص أيقونات أقسام الأسئلة، وأيقونات حالة البحث، وأيقونات التفاعل مع الأسئلة.</p>
                </a>
                <a href="Policy/index.php" class="design-card card-academic">
                    <div class="design-icon"><i class="fa-solid fa-file-shield"></i></div>
                    <h3 class="design-title">صفحة سياسة الخصوصية</h3>
                    <p class="design-desc">إدارة أيقونات الأقسام، وأيقونات الحماية، وأيقونات التفاعل لصفحة سياسة الخصوصية.</p>
                </a>
                <a href="TermsOfService/index.php" class="design-card card-auth">
                 <div class="design-icon"><i class="fa-solid fa-file-contract"></i></div>
                 <h3 class="design-title">تصميم شروط الخدمة</h3>
                <p class="design-desc">إدارة أيقونات بنود وقواعد شروط الخدمة في المنصة.</p>
                </a>
            </div>

        </div>
    </main>

    <script src="../assets/js/admin_global.js"></script>
</body>
</html>