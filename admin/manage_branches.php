<?php
// تفعيل عرض الأخطاء للمساعدة في التصحيح
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../server/session_check.php';

// التأكد من أن المستخدم مسؤول قبل الوصول إلى هذه الصفحة
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Location: ../public/login.php');
    exit;
}

// جلب بيانات المحاضرات والتصنيفات والأساتذة والشعب والفصول والأفواج
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

// دوال مساعدة لجلب الأسماء بناءً على ID
function getNameById($id, $data_array) {
    foreach ($data_array as $item) {
        if ($item['id'] === $id) {
            return $item['name'];
        }
    }
    return 'غير معروف';
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المحاضرات</title>
    <link rel="stylesheet" href="../public/css/style.css">
    <style>
        .manage-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: #f9f9f9;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .manage-table th, .manage-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: right;
        }
        .manage-table th {
            background-color: #007bff;
            color: white;
            font-weight: bold;
        }
        .manage-table tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .manage-table .actions button {
            padding: 6px 10px;
            margin: 0 3px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9em;
            transition: background-color 0.3s ease;
        }
        .manage-table .actions .edit-btn {
            background-color: #ffc107;
            color: #333;
        }
        .manage-table .actions .edit-btn:hover {
            background-color: #e0a800;
        }
        .manage-table .actions .delete-btn {
            background-color: #dc3545;
            color: white;
        }
        .manage-table .actions .delete-btn:hover {
            background-color: #c82333;
        }
        .search-filter-bar {
            margin-bottom: 20px;
            text-align: center;
        }
        .search-filter-bar input[type="text"],
        .search-filter-bar select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>إدارة المحاضرات</h2>
        <p><a href="dashboard.php">العودة للوحة التحكم</a></p>
        <p><a href="add_lecture.php">إضافة محاضرة جديدة</a></p>

        <div class="search-filter-bar">
            <input type="text" id="lectureSearchBar" placeholder="البحث بالعنوان أو الأستاذ...">
            <select id="branchFilter"> <option value="">جميع الشعب</option>
                <?php foreach ($branches_data as $branch): ?>
                    <option value="<?php echo htmlspecialchars($branch['id']); ?>"><?php echo htmlspecialchars($branch['name']); ?></option>
                <?php endforeach; ?>
            </select>
            <select id="semesterFilter"> <option value="">جميع الفصول</option>
                <?php foreach ($semesters_data as $semester): ?>
                    <option value="<?php echo htmlspecialchars($semester['id']); ?>"><?php echo htmlspecialchars($semester['name']); ?></option>
                <?php endforeach; ?>
            </select>
            <select id="groupFilter"> <option value="">جميع الأفواج</option>
                <?php foreach ($groups_data as $group): ?>
                    <option value="<?php echo htmlspecialchars($group['id']); ?>"><?php echo htmlspecialchars($group['name']); ?></option>
                <?php endforeach; ?>
            </select>
            <select id="lectureCategoryFilter">
                <option value="">جميع التصنيفات</option>
                <?php foreach ($categories_data as $category): ?>
                    <option value="<?php echo htmlspecialchars($category['id']); ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                <?php endforeach; ?>
            </select>
            <select id="lecturerFilter"> <option value="">جميع الأساتذة</option>
                <?php foreach ($lecturers_data as $lecturer): ?>
                    <option value="<?php echo htmlspecialchars($lecturer['id']); ?>"><?php echo htmlspecialchars($lecturer['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <table class="manage-table">
            <thead>
                <tr>
                    <th>العنوان</th>
                    <th>الأستاذ</th> <th>الشعبة</th> <th>الفصل</th> <th>الفوج</th> <th>التصنيف</th>
                    <th>تاريخ النشر</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody id="lecturesTableBody">
                <?php if (empty($lectures_data)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center;">لا توجد محاضرات لعرضها بعد.</td> </tr>
                <?php else: ?>
                    <?php foreach ($lectures_data as $lecture): ?>
                        <tr data-lecture-id="<?php echo htmlspecialchars($lecture['id']); ?>">
                            <td><?php echo htmlspecialchars($lecture['title']); ?></td>
                            <td><?php echo htmlspecialchars(getNameById($lecture['lecturer_id'], $lecturers_data)); ?></td> <td><?php echo htmlspecialchars(getNameById($lecture['branch_id'], $branches_data)); ?></td>       <td><?php echo htmlspecialchars(getNameById($lecture['semester_id'], $semesters_data)); ?></td>   <td><?php echo htmlspecialchars(getNameById($lecture['group_id'], $groups_data)); ?></td>           <td><?php echo htmlspecialchars(getNameById($lecture['category_id'], $categories_data)); ?></td>
                            <td><?php echo htmlspecialchars($lecture['publish_date']); ?></td>
                            <td class="actions">
                                <button class="edit-btn" data-id="<?php echo htmlspecialchars($lecture['id']); ?>">تعديل</button>
                                <button class="delete-btn" data-id="<?php echo htmlspecialchars($lecture['id']); ?>">حذف</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const lectureSearchBar = document.getElementById('lectureSearchBar');
            const lectureCategoryFilter = document.getElementById('lectureCategoryFilter');
            const branchFilter = document.getElementById('branchFilter');     // جديد
            const semesterFilter = document.getElementById('semesterFilter'); // جديد
            const groupFilter = document.getElementById('groupFilter');       // جديد
            const lecturerFilter = document.getElementById('lecturerFilter'); // جديد
            const lecturesTableBody = document.getElementById('lecturesTableBody');
            
            // جعل بيانات JSON متاحة لـ JavaScript
            const allLectures = <?php echo json_encode($lectures_data, JSON_UNESCAPED_UNICODE); ?>;
            const allCategories = <?php echo json_encode($categories_data, JSON_UNESCAPED_UNICODE); ?>;
            const allLecturers = <?php echo json_encode($lecturers_data, JSON_UNESCAPED_UNICODE); ?>;
            const allBranches = <?php echo json_encode($branches_data, JSON_UNESCAPED_UNICODE); ?>;
            const allSemesters = <?php echo json_encode($semesters_data, JSON_UNESCAPED_UNICODE); ?>;
            const allGroups = <?php echo json_encode($groups_data, JSON_UNESCAPED_UNICODE); ?>;


            // دوال مساعدة لجلب الأسماء من المعرفات
            function getNameById(id, dataArray) {
                const item = dataArray.find(item => item.id === id);
                return item ? item.name : 'غير معروف';
            }

            function displayLectures(lecturesToDisplay) {
                lecturesTableBody.innerHTML = '';
                if (lecturesToDisplay.length === 0) {
                    lecturesTableBody.innerHTML = '<tr><td colspan="8" style="text-align: center;">لا توجد محاضرات مطابقة.</td></tr>'; // عدّل عدد الأعمدة
                    return;
                }

                lecturesToDisplay.forEach(lecture => {
                    const row = document.createElement('tr');
                    row.setAttribute('data-lecture-id', lecture.id);
                    row.innerHTML = `
                        <td>${lecture.title}</td>
                        <td>${getNameById(lecture.lecturer_id, allLecturers)}</td>
                        <td>${getNameById(lecture.branch_id, allBranches)}</td>
                        <td>${getNameById(lecture.semester_id, allSemesters)}</td>
                        <td>${getNameById(lecture.group_id, allGroups)}</td>
                        <td>${getNameById(lecture.category_id, allCategories)}</td>
                        <td>${lecture.publish_date}</td>
                        <td class="actions">
                            <button class="edit-btn" data-id="${lecture.id}">تعديل</button>
                            <button class="delete-btn" data-id="${lecture.id}">حذف</button>
                        </td>
                    `;
                    lecturesTableBody.appendChild(row);
                });
            }

            function filterAndSearchLectures() {
                const searchTerm = lectureSearchBar.value.toLowerCase();
                const selectedCategory = lectureCategoryFilter.value;
                const selectedBranch = branchFilter.value;
                const selectedSemester = semesterFilter.value;
                const selectedGroup = groupFilter.value;
                const selectedLecturer = lecturerFilter.value;

                let filteredLectures = allLectures.filter(lecture => {
                    const lecturerName = getNameById(lecture.lecturer_id, allLecturers).toLowerCase();
                    const matchesSearch = lecture.title.toLowerCase().includes(searchTerm) ||
                                          lecturerName.includes(searchTerm);

                    const matchesCategory = selectedCategory === '' || lecture.category_id === selectedCategory;
                    const matchesBranch = selectedBranch === '' || lecture.branch_id === selectedBranch;
                    const matchesSemester = selectedSemester === '' || lecture.semester_id === selectedSemester;
                    const matchesGroup = selectedGroup === '' || lecture.group_id === selectedGroup;
                    const matchesLecturer = selectedLecturer === '' || lecture.lecturer_id === selectedLecturer;

                    return matchesSearch && matchesCategory && matchesBranch && matchesSemester && matchesGroup && matchesLecturer;
                });
                displayLectures(filteredLectures);
            }

            // الاستماع لأحداث البحث والتصفية
            lectureSearchBar.addEventListener('input', filterAndSearchLectures);
            lectureCategoryFilter.addEventListener('change', filterAndSearchLectures);
            branchFilter.addEventListener('change', filterAndSearchLectures);
            semesterFilter.addEventListener('change', filterAndSearchLectures);
            groupFilter.addEventListener('change', filterAndSearchLectures);
            lecturerFilter.addEventListener('change', filterAndSearchLectures);

            // معالج أحداث التعديل والحذف (سيتم معالجته بواسطة AJAX)
            lecturesTableBody.addEventListener('click', async (e) => {
                if (e.target.classList.contains('edit-btn')) {
                    const lectureId = e.target.dataset.id;
                    window.location.href = `edit_lecture.php?id=${lectureId}`; // توجيه لصفحة التعديل
                } else if (e.target.classList.contains('delete-btn')) {
                    const lectureId = e.target.dataset.id;
                    if (confirm('هل أنت متأكد أنك تريد حذف هذه المحاضرة؟')) {
                        try {
                            const response = await fetch('../server/delete_lecture_handler.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({ id: lectureId })
                            });
                            const data = await response.json();
                            if (data.success) {
                                alert(data.message);
                                // إعادة تحميل المحاضرات بعد الحذف
                                window.location.reload(); // طريقة بسيطة لإعادة التحميل
                            } else {
                                alert('فشل الحذف: ' + data.message);
                            }
                        } catch (error) {
                            console.error('Error:', error);
                            alert('حدث خطأ أثناء الاتصال بالخادم للحذف.');
                        }
                    }
                }
            });

            // عرض المحاضرات عند تحميل الصفحة لأول مرة، مع الأخذ في الاعتبار أي تصفية مسبقة من الـ URL (إذا وجدت)
            const urlParams = new URLSearchParams(window.location.search);
            const initialCategoryId = urlParams.get('category_id');
            const initialBranchId = urlParams.get('branch_id');
            const initialSemesterId = urlParams.get('semester_id');
            const initialGroupId = urlParams.get('group_id');
            const initialLecturerId = urlParams.get('lecturer_id');

            if (initialCategoryId) categoryFilter.value = initialCategoryId;
            if (initialBranchId) branchFilter.value = initialBranchId;
            if (initialSemesterId) semesterFilter.value = initialSemesterId;
            if (initialGroupId) groupFilter.value = initialGroupId;
            if (initialLecturerId) lecturerFilter.value = initialLecturerId;


            filterAndSearchLectures(); // تطبيق الفلترة الأولية
        });
    </script>
</body>
</html>