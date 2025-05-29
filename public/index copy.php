<?php
// تفعيل عرض الأخطاء للمساعدة في التصحيح
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// تضمين ملف التحقق من الجلسة
require_once '../server/session_check.php';

// توجيه المستخدم إذا لم يكن مسجلاً للدخول
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
// إذا كان المستخدم مسؤولاً، يمكن توجيهه إلى لوحة التحكم
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
    header('Location: ../admin/dashboard.php');
    exit;
}

// جلب جميع بيانات المحاضرات والتصنيفات والأساتذة والشعب والفصول والأفواج
$lectures_file = '../data/lectures.json';
$categories_file = '../data/categories.json';
$lecturers_file = '../data/lecturers.json';
$branches_file = '../data/branches.json';
$semesters_file = '../data/semesters.json';
$groups_file = '../data/groups.json';

$lectures_data = [];
if (file_exists($lectures_file) && filesize($lectures_file) > 0) {
    $lectures_json = file_get_contents($lectures_file);
    $lectures_data = json_decode($lectures_json, true);
    if ($lectures_data === null) $lectures_data = [];
}

$categories_data = [];
if (file_exists($categories_file) && filesize($categories_file) > 0) {
    $categories_json = file_get_contents($categories_file);
    $categories_data = json_decode($categories_json, true);
    if ($categories_data === null) $categories_data = [];
}

$lecturers_data = [];
if (file_exists($lecturers_file) && filesize($lecturers_file) > 0) {
    $lecturers_json = file_get_contents($lecturers_file);
    $lecturers_data = json_decode($lecturers_json, true);
    if ($lecturers_data === null) $lecturers_data = [];
}

$branches_data = [];
if (file_exists($branches_file) && filesize($branches_file) > 0) {
    $branches_json = file_get_contents($branches_file);
    $branches_data = json_decode($branches_json, true);
    if ($branches_data === null) $branches_data = [];
}

$semesters_data = [];
if (file_exists($semesters_file) && filesize($semesters_file) > 0) {
    $semesters_json = file_get_contents($semesters_file);
    $semesters_data = json_decode($semesters_json, true);
    if ($semesters_data === null) $semesters_data = [];
}

$groups_data = [];
if (file_exists($groups_file) && filesize($groups_file) > 0) {
    $groups_json = file_get_contents($groups_file);
    $groups_data = json_decode($groups_json, true);
    if ($groups_data === null) $groups_data = [];
}

// دالة مساعدة لجلب الأسماء بناءً على ID (تم تحسينها لتجنب الأخطاء)
function getNameById($id, $data_array) {
    if ($id === null) return 'غير محدد'; // التعامل مع القيم الفارغة/الـ null
    foreach ($data_array as $item) {
        if (isset($item['id']) && $item['id'] === $id) {
            return $item['name'];
        }
    }
    return 'غير معروف';
}

// دالة لتنظيف المعرفات (IDs) لتكون آمنة لـ HTML
function sanitizeId($string) {
    $string = str_replace(' ', '_', $string);
    $string = preg_replace('/[^A-Za-z0-9\-_]/', '', $string);
    return $string;
}

// تجميع المحاضرات حسب الشعب والفصول (للشريط الأفقي والأقسام)
$lectures_by_branch_semester = [];
foreach ($lectures_data as $lecture) {
    $branch_id = $lecture['branch_id'] ?? null; // استخدام ?? null للتعامل مع المفاتيح غير الموجودة
    $semester_id = $lecture['semester_id'] ?? null;
    
    if ($branch_id && $semester_id) { // تأكد من وجود الشعبة والفصل
        if (!isset($lectures_by_branch_semester[$branch_id])) {
            $lectures_by_branch_semester[$branch_id] = [];
        }
        if (!isset($lectures_by_branch_semester[$branch_id][$semester_id])) {
            $lectures_by_branch_semester[$branch_id][$semester_id] = [];
        }
        $lectures_by_branch_semester[$branch_id][$semester_id][] = $lecture;
    }
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مكتبة المحاضرات الإسلامية</title>
    <link href="bootstrap-5.3.6-dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css"> <style>
        /* أنماط عامة للصفحة */
        body {
            background: linear-gradient(45deg, #a2ff9a, #a9a7bd, #fad0c4, #d4e024);
            background-size: 400% 400%;
            animation: gradientAnimation 9s ease infinite;
            min-height: 100vh;
            margin: 0;
            padding: 0;
            font-family: 'Arial', sans-serif;
            direction: rtl;
        }

        @keyframes gradientAnimation {
            0% { background-position: 0% 0%; }
            50% { background-position: 100% 100%; }
            100% { background-position: 0% 0%; }
        }

        /* لضمان أن كل المحتوى يظهر بشكل صحيح، ولتجنب التداخل */
        .page-wrapper {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .main-content {
            flex-grow: 1; /* لجعل المحتوى الرئيسي يتمدد */
            padding: 20px;
        }

        .container {
            max-width: 1200px; /* لتقييد عرض المحتوى */
            margin: 0 auto;
            padding: 20px;
        }

        /* شريط التنقل العلوي (Navbar) */
        .navbar-custom {
            background: linear-gradient(90deg, #007bff, #00c4ff);
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
            padding: 15px 0;
            position: sticky; /* لجعل الشريط ثابتاً عند التمرير */
            top: 0;
            z-index: 1000; /* لضمان ظهوره فوق العناصر الأخرى */
            width: 100%;
        }
        .navbar-brand, .nav-link {
            color: white !important;
            font-weight: bold;
        }
        .navbar-brand {
            font-size: 1.8rem;
        }
        .navbar-toggler {
            border-color: rgba(255,255,255,0.5);
        }
        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 0.5%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }
        .search-container {
            display: flex;
            align-items: center;
            margin-right: 20px;
            margin-left: auto;
            flex-grow: 1;
        }
        .search-container input {
            border-radius: 20px;
            border: none;
            padding: 8px 15px;
            margin-left: 10px;
            width: 200px;
        }
        .search-container button {
            background-color: #f8f9fa;
            color: #007bff;
            border: none;
            border-radius: 20px;
            padding: 8px 15px;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }
        .search-container button:hover {
            background-color: #e2e6ea;
        }
        .dropdown-menu-custom {
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .dropdown-item-custom {
            padding: 10px 15px;
            transition: background-color 0.2s ease;
        }
        .dropdown-item-custom:hover {
            background-color: #e9ecef;
            color: #007bff;
        }

        /* شريط الشعب الأفقي */
        .branches-horizontal-nav {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 15px;
            padding: 30px 20px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            margin-bottom: 40px;
        }
        .branch-nav-item {
            background: linear-gradient(90deg, #28a745, #34d058);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 30px;
            font-weight: bold;
            font-size: 1.1rem;
            text-decoration: none;
            transition: background 0.3s ease, transform 0.2s ease;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 120px;
        }
        .branch-nav-item:hover {
            background: linear-gradient(90deg, #218838, #2cb548);
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
        }

        /* أنماط أقسام الشعب والفصول والمحاضرات */
        .section-title {
            font-size: 2.5rem;
            font-weight: bold;
            color: #ffffff;
            text-align: center;
            margin: 60px 0 40px 0;
            padding: 20px;
            background: rgba(0, 123, 255, 0.9);
            border-radius: 10px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
            text-shadow: 1px 1px 4px rgba(0, 0, 0, 0.4);
        }
        .branch-main-section {
            margin-bottom: 60px;
        }
        .branch-section-title {
            font-size: 2rem;
            font-weight: bold;
            color: #333;
            text-align: center;
            margin: 40px 0 30px 0;
            padding: 15px;
            background: rgba(255, 255, 255, 0.8);
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        .semester-main-section {
            margin-bottom: 40px;
        }
        .semester-section-title {
            font-size: 1.75rem;
            font-weight: bold;
            color: #28a745;
            text-align: center;
            margin: 30px 0 20px 0;
            padding: 12px;
            background: rgba(255, 255, 255, 0.85);
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
        }

        /* تصميم بطاقة المحاضرة */
        .lecture-card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-top: 20px;
            padding-bottom: 40px;
        }
        .lecture-card {
            background-color: #fff;
            border-radius: 15px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            height: 100%;
            border: 1px solid #eee;
        }
        .lecture-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        .lecture-card .card-img {
            width: 100%;
            height: 220px;
            object-fit: cover;
            border-bottom: 3px solid #007bff;
        }
        .lecture-card .card-body {
            padding: 20px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
            text-align: center;
        }
        .lecture-card h5.card-title {
            font-size: 1.4em;
            color: #333;
            margin-bottom: 10px;
            font-weight: bold;
        }
        .lecture-card .card-title a {
            color: #007bff;
            text-decoration: none;
        }
        .lecture-card .card-title a:hover {
            color: #0056b3;
        }
        .lecture-card p.card-text {
            font-size: 0.95em;
            color: #555;
            margin-bottom: 5px;
        }
        .lecture-card .meta-info {
            font-size: 0.85em;
            color: #888;
            margin-top: 10px;
            margin-bottom: 20px;
        }

        /* قسم الأزرار والعدادات والتفاعلات */
        .card-actions-row {
            display: flex;
            justify-content: space-around;
            align-items: center;
            margin-top: auto;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        .action-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            cursor: pointer;
            color: #666;
            transition: color 0.2s ease;
        }
        .action-item:hover {
            color: #007bff;
        }
        .action-item .icon {
            font-size: 1.5em;
            margin-bottom: 5px;
        }
        .action-item .count {
            font-size: 0.9em;
            font-weight: bold;
        }

        .interaction-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 10px;
            margin-bottom: 5px;
        }
        .interaction-buttons button {
            background: none;
            border: none;
            font-size: 1.8em;
            cursor: pointer;
            transition: transform 0.1s ease;
            padding: 0 5px;
        }
        .interaction-buttons button:hover {
            transform: scale(1.1);
        }
        .interaction-buttons .like-btn { color: #28a745; }
        .interaction-buttons .dislike-btn { color: #dc3545; }
        .interaction-counts {
            display: flex;
            justify-content: center;
            gap: 15px;
            font-size: 0.9em;
            color: #555;
            font-weight: bold;
        }
        .interaction-counts span {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .interaction-counts .like-count { color: #28a745; }
        .interaction-counts .dislike-count { color: #dc3545; }

        /* الأنماط الخاصة بالقائمة المتداخلة */
        .nested-menu {
            margin: 20px auto;
            max-width: 800px;
            padding: 20px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }
        .nested-menu .menu-item {
            cursor: pointer;
            font-size: 1.2rem;
            font-weight: bold;
            color: #007bff;
            padding: 12px 15px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background-color 0.2s ease;
        }
        .nested-menu .menu-item:hover {
            background-color: #f8f9fa;
        }
        .nested-menu .menu-item:last-child {
            border-bottom: none;
        }
        .nested-menu .sub-list {
            display: none;
            padding-right: 25px;
            background-color: #f2f2f2;
            border-radius: 0 0 10px 10px;
        }
        .nested-menu .sub-item {
            cursor: pointer;
            font-size: 1.05rem;
            color: #343a40;
            padding: 10px 15px;
            border-bottom: 1px dotted #dee2e6;
            transition: background-color 0.2s ease;
        }
        .nested-menu .sub-item:hover {
            background-color: #e9ecef;
            color: #007bff;
        }
        .nested-menu .sub-item:last-child {
            border-bottom: none;
        }
        .nested-menu .sub-item a {
            color: inherit;
            text-decoration: none;
            display: block;
        }
        .nested-menu .sub-item a:hover {
            text-decoration: underline;
        }
        .toggle-icon {
            font-size: 1.5rem;
            font-weight: normal;
            transition: transform 0.3s ease;
        }
        .menu-item.active .toggle-icon {
            transform: rotate(90deg);
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
    <div class="container-fluid">
        <a class="navbar-brand" href="/islamique/public/index.php">مكتبة المحاضرات الإسلامية</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link active" aria-current="page" href="/islamique/public/index.php">الرئيسية</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/islamique/public/browse_lectures.php">تصفح كل المحاضرات</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/islamique/public/categories.php">التصنيفات</a>
                </li>
                 <li class="nav-item">
                    <a class="nav-link" href="/islamique/public/lecturers.php">الأساتذة</a>
                </li>
                <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                <li class="nav-item">
                    <a class="nav-link" href="/islamique/admin/dashboard.php">لوحة التحكم</a>
                </li>
                <?php endif; ?>
            </ul>
            <div class="search-container">
                <input type="text" id="globalSearchBar" placeholder="البحث في الموقع..." aria-label="Search">
                <button type="button">بحث</button>
            </div>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="#" id="logoutLink">تسجيل الخروج (<?php echo htmlspecialchars($_SESSION['username'] ?? 'Guest'); ?>)</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="page-wrapper">

<section class="branches-horizontal-nav">
    <?php if (empty($branches_data)): ?>
        <p>لا توجد شعب لعرضها.</p>
    <?php else: ?>
        <?php foreach ($branches_data as $branch): ?>
            <a href="#branch_<?php echo sanitizeId($branch['id']); ?>" class="branch-nav-item scroll-to-section">
                <?php echo htmlspecialchars($branch['name']); ?>
            </a>
        <?php endforeach; ?>
    <?php endif; ?>














                            <div>



    <h2 class="section-title">المحاضرات حسب الشعب والفصول</h2>
    <?php if (empty($lectures_by_branch_semester)): ?>
        <p class="text-center">لا توجد محاضرات لعرضها بعد.</p>
    <?php else: ?>
        <?php foreach ($branches_data as $branch): ?>
            <?php if (isset($lectures_by_branch_semester[$branch['id']])): ?>
                <div class="branch-main-section" id="branch_<?php echo sanitizeId($branch['id']); ?>">
                    <h3 class="branch-section-title"><?php echo htmlspecialchars($branch['name']); ?></h3>
                    <?php foreach ($semesters_data as $semester): ?>
                        <?php if (isset($lectures_by_branch_semester[$branch['id']][$semester['id']])): ?>
                            <div class="semester-main-section" id="semester_<?php echo sanitizeId($semester['id']); ?>_branch_<?php echo sanitizeId($branch['id']); ?>">
                                <h4 class="semester-section-title"><?php echo htmlspecialchars($semester['name']); ?></h4>
                                <div class="row lecture-card-grid">
                                    <?php
                                    // فرز المحاضرات حسب التصنيف لتجميعها تحت نفس العنوان الفرعي
                                    $lectures_in_semester_branch = $lectures_by_branch_semester[$branch['id']][$semester['id']];
                                    $lectures_grouped_by_category = [];
                                    foreach ($lectures_in_semester_branch as $lecture) {
                                        $category_id = $lecture['category_id'] ?? null;
                                        if ($category_id) { // تأكد من وجود معرف التصنيف
                                            if (!isset($lectures_grouped_by_category[$category_id])) {
                                                $lectures_grouped_by_category[$category_id] = [];
                                            }
                                            $lectures_grouped_by_category[$category_id][] = $lecture;
                                        }
                                    }

                                    foreach ($lectures_grouped_by_category as $category_id => $lectures_in_category) {
                                        $category_name = getNameById($category_id, $categories_data);
                                        ?>
                                        <div class="col-12">
                                            <h5 class="category-title" style="font-size:1.4rem; background: rgba(255, 255, 255, 0.7); padding: 8px; border-radius: 5px; margin-bottom: 15px;">
                                                الفئة: <?php echo htmlspecialchars($category_name); ?>
                                            </h5>
                                        </div>
                                        <?php foreach ($lectures_in_category as $lecture):
                                            $lecturer_name = getNameById($lecture['lecturer_id'], $lecturers_data);
                                            $group_name = getNameById($lecture['group_id'], $groups_data);
                                            ?>
                                            <div class="col-md-6 col-lg-4 d-flex">
                                                <div class="lecture-card">
                                                    <img src="/islamique/public/<?php echo htmlspecialchars($lecture['cover_image_url'] ?? 'covers/default_cover.jpg'); ?>"
                                                         onerror="this.onerror=null;this.src='/islamique/public/covers/default_cover.jpg';"
                                                         alt="Lecture Cover" class="card-img">
                                                    <div class="card-body">
                                                        <h5 class="card-title"><a href="/islamique/public/lecture_details.php?id=<?php echo htmlspecialchars($lecture['id']); ?>"><?php echo htmlspecialchars($lecture['title']); ?></a></h5>
                                                        <p class="card-text">المحاضر: <?php echo htmlspecialchars($lecturer_name); ?></p>
                                                        <p class="card-text">الفوج: <?php echo htmlspecialchars($group_name); ?></p>
                                                        <div class="meta-info">
                                                            <span>تاريخ النشر: <?php echo htmlspecialchars($lecture['publish_date']); ?></span>
                                                        </div>

                                                        <div class="card-actions-row">
                                                            <div class="action-item download-action" data-lecture-id="<?php echo htmlspecialchars($lecture['id']); ?>">
                                                                <span class="icon">📚⬇️</span>
                                                                <span class="count download-count"><?php echo htmlspecialchars($lecture['download_count'] ?? 0); ?></span>
                                                                <small>تحميل</small>
                                                            </div>
                                                            <div class="action-item view-action" data-lecture-id="<?php echo htmlspecialchars($lecture['id']); ?>">
                                                                <span class="icon">👀</span>
                                                                <span class="count view-count"><?php echo htmlspecialchars($lecture['view_count'] ?? 0); ?></span>
                                                                <small>مشاهدة</small>
                                                            </div>
                                                        </div>
                                                        <div class="interaction-buttons">
                                                            <button class="like-btn" data-lecture-id="<?php echo htmlspecialchars($lecture['id']); ?>">👍</button>
                                                            <button class="dislike-btn" data-lecture-id="<?php echo htmlspecialchars($lecture['id']); ?>">👎</button>
                                                        </div>
                                                        <div class="interaction-counts">
                                                            <span class="like-count">👍 <?php echo htmlspecialchars($lecture['interactions']['likes'] ?? 0); ?></span>
                                                            <span class="dislike-count">👎 <?php echo htmlspecialchars($lecture['interactions']['dislikes'] ?? 0); ?></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php } // End foreach lectures_grouped_by_category ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>






                            </div>


















<section class="nested-menu">
    <h3 class="text-center mb-4">تصفح المحاضرات حسب الهيكل التنظيمي</h3>
    <?php
    if (!empty($branches_data)) {
        foreach ($branches_data as $branch) {
            $safeBranchId = sanitizeId($branch['id']);
            ?>
            <div class="menu-item" data-toggle-id="semesters-list-<?php echo $safeBranchId; ?>">
                <?php echo htmlspecialchars($branch['name']); ?>
                <span class="toggle-icon">+</span>
            </div>
            <div class="sub-list" id="semesters-list-<?php echo $safeBranchId; ?>">
                <?php
                if (!empty($semesters_data)) {
                    foreach ($semesters_data as $semester) {
                        $safeSemesterId = sanitizeId($semester['id']);
                        ?>
                        <div class="menu-item" data-toggle-id="categories-list-<?php echo $safeBranchId; ?>-<?php echo $safeSemesterId; ?>">
                            <?php echo htmlspecialchars($semester['name']); ?>
                            <span class="toggle-icon">+</span>
                        </div>
                        <div class="sub-list" id="categories-list-<?php echo $safeBranchId; ?>-<?php echo $safeSemesterId; ?>">
                            <?php
                            $unique_categories_for_semester_branch = [];
                            foreach($lectures_data as $lecture) {
                                if ((isset($lecture['branch_id']) && $lecture['branch_id'] === $branch['id']) && (isset($lecture['semester_id']) && $lecture['semester_id'] === $semester['id'])) {
                                    $category_name = getNameById($lecture['category_id'], $categories_data);
                                    if (!in_array($category_name, $unique_categories_for_semester_branch)) {
                                        $unique_categories_for_semester_branch[] = $category_name;
                                    }
                                }
                            }
                            sort($unique_categories_for_semester_branch); // فرز أبجدي
                            if (!empty($unique_categories_for_semester_branch)) {
                                foreach ($unique_categories_for_semester_branch as $cat_name) {
                                    // البحث عن الـ ID الفعلي للتصنيف باستخدام اسمه
                                    $original_category_id = null;
                                    foreach($categories_data as $cat_obj) {
                                        if ($cat_obj['name'] === $cat_name) {
                                            $original_category_id = $cat_obj['id'];
                                            break;
                                        }
                                    }

                                    if ($original_category_id) { // تأكد من العثور على الـ ID
                                        ?>
                                        <div class="sub-item">
                                            <a href="/islamique/public/browse_lectures.php?branch_id=<?php echo htmlspecialchars($branch['id']); ?>&semester_id=<?php echo htmlspecialchars($semester['id']); ?>&category_id=<?php echo htmlspecialchars($original_category_id); ?>">
                                                <?php echo htmlspecialchars($cat_name); ?>
                                            </a>
                                        </div>
                                        <?php
                                    }
                                }
                            } else {
                                echo '<div class="sub-item text-center">لا توجد تصنيفات متاحة لهذا الفصل في هذه الشعبة.</div>';
                            }
                            ?>
                        </div>
                        <?php
                    }
                } else {
                    echo '<div class="sub-item text-center">لا توجد فصول دراسية متاحة.</div>';
                }
                ?>
            </div>
            <?php
        }
    } else {
        echo '<div class="text-center">لا توجد شعب متاحة.</div>';
    }
    ?>
</section>







</div> <script src="bootstrap-5.3.6-dist/js/popper.min.js"></script>
<script src="bootstrap-5.3.6-dist/js/bootstrap.min.js"></script>

<script src="/islamique/public/js/index.js"></script>

</body>
</html>