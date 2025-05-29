<?php
require_once '../server/session_check.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// يمكننا هنا جلب المحاضرات والتصنيفات باستخدام PHP لقراءتها وتهيئتها لـ JavaScript
$lectures_file = '../data/lectures.json';
$categories_file = '../data/categories.json';

$lecturers_file = '../data/lecturers.json'; // جديد
$branches_file = '../data/branches.json';   // جديد
$semesters_file = '../data/semesters.json'; // جديد
$groups_file = '../data/groups.json';     // جديد





















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






$lecturers_data = []; // جديد
if (file_exists($lecturers_file) && filesize($lecturers_file) > 0) {
    $lecturers_json = file_get_contents($lecturers_file);
    $lecturers_data = json_decode($lecturers_json, true);
    if ($lecturers_data === null) $lecturers_data = [];
}

$branches_data = []; // جديد
if (file_exists($branches_file) && filesize($branches_file) > 0) {
    $branches_json = file_get_contents($branches_file);
    $branches_data = json_decode($branches_json, true);
    if ($branches_data === null) $branches_data = [];
}

$semesters_data = []; // جديد
if (file_exists($semesters_file) && filesize($semesters_file) > 0) {
    $semesters_json = file_get_contents($semesters_file);
    $semesters_data = json_decode($semesters_json, true);
    if ($semesters_data === null) $semesters_data = [];
}

$groups_data = []; // جديد
if (file_exists($groups_file) && filesize($groups_file) > 0) {
    $groups_json = file_get_contents($groups_file);
    $groups_data = json_decode($groups_json, true);
    if ($groups_data === null) $groups_data = [];
}









?>









<?php
// ... (كود جلب البيانات في الأعلى) ...

// دوال مساعدة لجلب الأسماء بناءً على ID
function getNameById($id, $data_array) {
    foreach ($data_array as $item) {
        if ($item['id'] === $id) {
            return $item['name'];
        }
    }
    return 'غير معروف';
}

// ... (بقية كود PHP) ...
?>







<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تصفح المحاضرات</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .filter-options {
            margin-bottom: 20px;
            text-align: center;
        }
        .filter-options select, .filter-options input {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin: 5px;
        }
        .lecture-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }
        .lecture-card {
            background-color: #f9f9f9;
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            text-align: right;
        }
        .lecture-card h3 {
            color: #333;
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 1.2em;
        }
        .lecture-card p {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 5px;
        }
        .lecture-card .category {
            font-weight: bold;
            color: #007bff;
        }
        .lecture-card .lecturer {
            font-style: italic;
            color: #555;
        }
        .lecture-card a {
            display: inline-block;
            background-color: #28a745;
            color: white;
            padding: 8px 12px;
            border-radius: 4px;
            text-decoration: none;
            margin-top: 15px;
            transition: background-color 0.3s ease;
        }
        .lecture-card a:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>تصفح المحاضرات</h2>
        <p><a href="main_user_homepage.php">العودة للصفحة الرئيسية</a></p>

        <div class="filter-options">
            <input type="text" id="searchBar" placeholder="البحث بالعنوان أو المحاضر...">
            <select id="categoryFilter">
                <option value="">جميع التصنيفات</option>
                <?php
                foreach ($categories_data as $category) {
                    echo '<option value="' . htmlspecialchars($category['id']) . '">' . htmlspecialchars($category['name']) . '</option>';
                }
                ?>
            </select>
        </div>

        <div id="lectureList" class="lecture-list">
            <p id="noLecturesMessage" style="text-align: center; display: none;">لا توجد محاضرات مطابقة.</p>
        </div>
    </div>

    <script>
        // جعل بيانات المحاضرات والتصنيفات متاحة لـ JavaScript
        const allLectures = <?php echo json_encode($lectures_data, JSON_UNESCAPED_UNICODE); ?>;
        const allCategories = <?php echo json_encode($categories_data, JSON_UNESCAPED_UNICODE); ?>;

        document.addEventListener('DOMContentLoaded', () => {
            const lectureListDiv = document.getElementById('lectureList');
            const searchBar = document.getElementById('searchBar');
            const categoryFilter = document.getElementById('categoryFilter');
            const noLecturesMessage = document.getElementById('noLecturesMessage');

            // دالة لجلب اسم التصنيف من معرف التصنيف
            function getCategoryName(categoryId) {
                const category = allCategories.find(cat => cat.id === categoryId);
                return category ? category.name : 'غير مصنف';
            }

            // دالة لعرض المحاضرات
            function displayLectures(lecturesToDisplay) {
                lectureListDiv.innerHTML = ''; // مسح المحاضرات الحالية
                if (lecturesToDisplay.length === 0) {
                    noLecturesMessage.style.display = 'block';
                    return;
                }
                noLecturesMessage.style.display = 'none';

                lecturesToDisplay.forEach(lecture => {
                    const lectureCard = document.createElement('div');
                    lectureCard.className = 'lecture-card';
                    lectureCard.innerHTML = `
                        <h3>${lecture.title}</h3>
                        <p class="lecturer">المحاضر: ${lecture.lecturer}</p>
                        <p class="category">التصنيف: ${getCategoryName(lecture.category_id)}</p>
                        <p>تاريخ النشر: ${lecture.publish_date}</p>
                        <a href="lecture_details.php?id=${lecture.id}">مشاهدة التفاصيل</a>
                    `;
                    lectureListDiv.appendChild(lectureCard);
                });
            }

            // دالة لتطبيق الفرز والبحث
            function filterAndSearchLectures() {
                const searchTerm = searchBar.value.toLowerCase();
                const selectedCategory = categoryFilter.value;

                let filteredLectures = allLectures.filter(lecture => {
                    const matchesSearch = lecture.title.toLowerCase().includes(searchTerm) ||
                                          lecture.lecturer.toLowerCase().includes(searchTerm);
                    const matchesCategory = selectedCategory === '' || lecture.category_id === selectedCategory;
                    return matchesSearch && matchesCategory;
                });

                displayLectures(filteredLectures);
            }

            // الاستماع لأحداث البحث والتصفية
            searchBar.addEventListener('input', filterAndSearchLectures);
            categoryFilter.addEventListener('change', filterAndSearchLectures);

            // عرض جميع المحاضرات عند تحميل الصفحة لأول مرة
            displayLectures(allLectures);
        });
    </script>
</body>
</html>