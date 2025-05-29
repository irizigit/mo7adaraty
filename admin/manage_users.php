<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../server/session_check.php';

// التأكد من أن المستخدم مسؤول قبل الوصول إلى هذه الصفحة
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Location: ../public/login.php'); // توجيه لصفحة تسجيل الدخول إذا لم يكن مسؤولاً
    exit;
}

$users_file = '../data/users.json';

$users_data = [];
if (file_exists($users_file) && filesize($users_file) > 0) {
    $users_json = file_get_contents($users_file);
    $users_data = json_decode($users_json, true);
    if ($users_data === null) $users_data = [];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المستخدمين</title>
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
        .manage-table .actions .delete-btn {
            background-color: #dc3545;
            color: white;
        }
        .manage-table .actions .delete-btn:hover {
            background-color: #c82333;
        }
        .manage-table .admin-status {
            font-weight: bold;
            color: #28a745;
        }
        .manage-table .user-status {
            color: #6c757d;
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
        <h2>إدارة المستخدمين</h2>
        <p><a href="dashboard.php">العودة للوحة التحكم</a></p>

        <div id="message" class="message"></div>

        <table class="manage-table">
            <thead>
                <tr>
                    <th>اسم المستخدم</th>
                    <th>البريد الإلكتروني</th>
                    <th>الصلاحية</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody id="usersTableBody">
                <?php if (empty($users_data)): ?>
                    <tr>
                        <td colspan="4" style="text-align: center;">لا توجد مستخدمين لعرضهم بعد.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users_data as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <?php if ($user['is_admin']): ?>
                                    <span class="admin-status">مسؤول</span>
                                <?php else: ?>
                                    <span class="user-status">مستخدم عادي</span>
                                <?php endif; ?>
                            </td>
                            <td class="actions">
                                <?php if ($_SESSION['user_id'] !== $user['id']): // لا تسمح للمسؤول بحذف حسابه ?>
                                    <button class="delete-btn" data-id="<?php echo htmlspecialchars($user['id']); ?>">حذف</button>
                                <?php else: ?>
                                    <button disabled style="background-color: #ccc; cursor: not-allowed;">حذف (غير متاح)</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const usersTableBody = document.getElementById('usersTableBody');
            const messageDiv = document.getElementById('message');

            function showMessage(msg, type) {
                messageDiv.textContent = msg;
                messageDiv.className = `message ${type}`;
                messageDiv.style.display = 'block';
            }

            usersTableBody.addEventListener('click', async (e) => {
                if (e.target.classList.contains('delete-btn')) {
                    const userId = e.target.dataset.id;
                    if (confirm('هل أنت متأكد أنك تريد حذف هذا المستخدم؟')) {
                        try {
                            const response = await fetch('../server/manage_users_handler.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({ action: 'delete', id: userId })
                            });
                            const data = await response.json();
                            if (data.success) {
                                showMessage(data.message, 'success');
                                // إعادة تحميل المستخدمين بعد الحذف
                                window.location.reload();
                            } else {
                                showMessage('فشل الحذف: ' + data.message, 'error');
                            }
                        } catch (error) {
                            console.error('Error:', error);
                            showMessage('حدث خطأ أثناء الاتصال بالخادم للحذف.', 'error');
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>