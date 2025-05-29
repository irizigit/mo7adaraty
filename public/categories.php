<?php
require_once '../server/session_check.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$categories_file = '../data/categories.json';
$lectures_file = '../data/lectures.json';

$categories_data = [];
if (file_exists($categories_file) && filesize($categories_file) > 0) {
    $categories_json = file_get_contents($categories_file);
    $categories_data = json_decode($categories_json, true);
    if ($categories_data === null) $categories_data = [];
}

$lectures_data = [];
if (file_exists($lectures_file) && filesize($lectures_file) > 0) {
    $lectures_json = file_get_contents($lectures_file);
    $lectures_data = json_decode($lectures_json, true);
    if ($lectures_data === null) $lectures_data = [];
}

// حساب عدد المحاضرات لكل تصنيف
$category_counts = [];
foreach ($lectures_data as $lecture) {
    $categoryId = $lecture['category_id'];
    if (isset($category_counts[$categoryId])) {
        $category_counts[$categoryId]++;
    } else {
        $category_counts[$categoryId] = 1;
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تصنيفات المحاضرات</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .category-list {
            list-style: none;
            padding: 0;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }
        .category-item {
            background-color: #e9f5ff;
            border: 1px solid #cce5ff;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .category-item h3 {
            margin-top: 0;
            margin-bottom: 10px;
            color: #007bff;
        }
        .category-item p {
            color: #555;
            font-size: 0.9em;
        }
        .category-item a {
            display: block;
            background-color: #007bff;
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            text-decoration: none;
            margin-top: 15px;
            transition: background-color 0.3s ease;
        }
        .category-item a:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>تصنيفات المحاضرات</h2>
        <p><a href="main_user_homepage.php">العودة للصفحة الرئيسية</a></p>

        <ul class="category-list">
            <?php if (empty($categories_data)): ?>
                <p style="text-align: center;">لا توجد تصنيفات لعرضها بعد.</p>
            <?php else: ?>
                <?php foreach ($categories_data as $category): ?>
                    <li class="category-item">
                        <h3><?php echo htmlspecialchars($category['name']); ?></h3>
                        <p>عدد المحاضرات: <?php echo $category_counts[$category['id']] ?? 0; ?></p>
                        <a href="browse_lectures.php?category_id=<?php echo htmlspecialchars($category['id']); ?>">تصفح المحاضرات في هذا التصنيف</a>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </div>
</body>
</html>