<?php
require_once '../server/session_check.php';

// التأكد من أن المستخدم مسؤول قبل الوصول إلى هذه الصفحة
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Location: ../public/login.php'); // توجيه لصفحة تسجيل الدخول إذا لم يكن مسؤولاً
    exit;
}

$categories_file = '../data/categories.json';

$categories_data = [];
if (file_exists($categories_file) && filesize($categories_file) > 0) {
    $categories_json = file_get_contents($categories_file);
    $categories_data = json_decode($categories_json, true);
    if ($categories_data === null) $categories_data = [];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة التصنيفات</title>
    <link rel="stylesheet" href="../public/css/style.css">
    <style>
        .category-form, .manage-table {
            margin-top: 20px;
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .category-form label {
            font-weight: bold;
            margin-bottom: 5px;
            display: block;
        }
        .category-form input[type="text"] {
            width: calc(100% - 22px);
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .category-form button {
            background-color: #007bff;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.3s ease;
        }
        .category-form button:hover {
            background-color: #0056b3;
        }
        .manage-table {
            width: 100%;
            border-collapse: collapse;
        }
        .manage-table th, .manage-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: right;
        }
        .manage-table th {
            background-color: #007bff;
            color: white;
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
        #message {
            margin-top: 15px;
            padding: 10px;
            border-radius: 5px;
            font-weight: bold;
            display: none;
        }
        #message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        #message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <div class="container">
        <h2>إدارة التصنيفات</h2>
        <p><a href="dashboard.php">العودة للوحة التحكم</a></p>

        <div id="message" class="message"></div>

        <div class="category-form">
            <h3>إضافة/تعديل تصنيف</h3>
            <form id="categoryForm">
                <input type="hidden" id="categoryId" name="id">
                <div class="form-group">
                    <label for="categoryName">اسم التصنيف:</label>
                    <input type="text" id="categoryName" name="name" required>
                </div>
                <button type="submit" id="submitCategoryBtn">إضافة تصنيف</button>
                <button type="button" id="cancelEditBtn" style="display:none; background-color: #6c757d;">إلغاء التعديل</button>
            </form>
        </div>

        <h3 style="margin-top: 30px;">التصنيفات الحالية</h3>
        <table class="manage-table">
            <thead>
                <tr>
                    <th>اسم التصنيف</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody id="categoriesTableBody">
                <?php if (empty($categories_data)): ?>
                    <tr>
                        <td colspan="2" style="text-align: center;">لا توجد تصنيفات لعرضها بعد.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($categories_data as $category): ?>
                        <tr data-category-id="<?php echo htmlspecialchars($category['id']); ?>">
                            <td><?php echo htmlspecialchars($category['name']); ?></td>
                            <td class="actions">
                                <button class="edit-btn" data-id="<?php echo htmlspecialchars($category['id']); ?>" data-name="<?php echo htmlspecialchars($category['name']); ?>">تعديل</button>
                                <button class="delete-btn" data-id="<?php echo htmlspecialchars($category['id']); ?>">حذف</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script src="../public/js/manage_categories.js"></script>
</body>
</html>