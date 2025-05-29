<?php
require_once '../server/session_check.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Location: ../public/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم المسؤول</title>
    <link rel="stylesheet" href="../public/css/style.css">
    <style>
        .admin-options ul {
            list-style: none;
            padding: 0;
        }
        .admin-options li {
            margin-bottom: 10px;
        }
        .admin-options a {
            display: block;
            background-color: #28a745;
            color: white;
            padding: 12px 20px;
            border-radius: 5px;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }
        .admin-options a:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>لوحة تحكم المسؤول</h2>
        <p id="adminWelcomeMessage"></p>
        <p>هنا يمكنك إدارة المحاضرات، التصنيفات، والمستخدمين.</p>
        <div class="admin-options">
            <ul>
                <li><a href="add_lecture.php">إضافة محاضرة جديدة</a></li>
                <li><a href="manage_lectures.php">إدارة المحاضرات</a></li>
                <li><a href="manage_categories.php">إدارة التصنيفات</a></li>
                <li><a href="manage_lecturers.php">إدارة الأساتذة</a></li> <li><a href="manage_users.php">إدارة المستخدمين</a></li>
            </ul>
        </div>
        <p><a href="#" id="adminLogoutLink">تسجيل الخروج</a></p>
    </div>

    <script src="../server/session_check.php"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const adminWelcomeMessage = document.getElementById('adminWelcomeMessage');
            const adminLogoutLink = document.getElementById('adminLogoutLink');

            if (window.sessionData && window.sessionData.isLoggedIn && window.sessionData.isAdmin) {
                adminWelcomeMessage.textContent = `مرحباً أيها المسؤول، ${window.sessionData.username}!`;
            } else {
                window.location.href = '../public/login.php';
            }

            adminLogoutLink.addEventListener('click', async (e) => {
                e.preventDefault();
                try {
                    const response = await fetch('../server/logout_handler.php');
                    const data = await response.json();
                    if (data.success) {
                        window.location.href = '../public/login.php';
                    } else {
                        alert('حدث خطأ أثناء تسجيل الخروج.');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('تعذر الاتصال بالخادم للخروج.');
                }
            });
        });
    </script>
</body>
</html>