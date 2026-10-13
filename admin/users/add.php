<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../config/db_connexion.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

$breadcrumbs = ["الطلاب والمستخدمون" => "", "إدارة الحسابات" => "index.php", "إضافة مستخدم جديد" => ""];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إضافة مستخدم جديد - Mo7adaraty</title>
    <link rel="icon" type="image/svg+xml" href="/mo7adaraty/assets/images/favicon.svg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/admin_global.css">
    <style>
        .password-wrapper { position: relative; }
        .password-wrapper input { padding-left: 45px; }
        .toggle-password { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-secondary); cursor: pointer; font-size: 18px; transition: 0.2s; }
        .toggle-password:hover { color: var(--brand-primary); }
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
            
            <?php if (isset($_SESSION['error'])): ?>
                <div style="padding: 15px; background: rgba(220, 53, 69, 0.1); color: var(--danger-color); border: 1px solid var(--danger-color); border-radius: var(--radius-md); margin-bottom: 20px; font-weight: bold;">
                    <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <div class="academic-form-container skeleton" style="max-width: 850px; margin-bottom: 30px;">
                <h2 style="margin-top: 0; color: var(--text-primary); border-bottom: 1px solid var(--border-color); padding-bottom: 15px; margin-bottom: 25px;">
                    <i class="fa-solid fa-user-plus text-[var(--success-color)]"></i> إضافة مستخدم جديد
                </h2>

                <form action="add_process.php" method="POST" enctype="multipart/form-data">
                    
                    <div style="display: flex; gap: 25px; flex-wrap: wrap;">
                        <div style="flex: 1; min-width: 300px;">
                            <div class="form-group">
                                <label class="form-label">الاسم الكامل: <span style="color:var(--danger-color)">*</span></label>
                                <input type="text" name="username" class="form-control" required placeholder="مثال: أحمد محمود">
                            </div>

                            <div class="form-group">
                                <label class="form-label">البريد الإلكتروني: <span style="color:var(--danger-color)">*</span></label>
                                <input type="email" name="email" class="form-control" dir="ltr" style="text-align: left;" required placeholder="student@example.com">
                            </div>

                            <div class="form-group">
                                <label class="form-label">كلمة المرور: <span style="color:var(--danger-color)">*</span></label>
                                <div class="password-wrapper">
                                    <input type="password" name="password" id="password" class="form-control" dir="ltr" style="text-align: left; font-family: monospace; font-size: 16px;" required placeholder="اكتب كلمة مرور قوية...">
                                    <button type="button" class="toggle-password" onclick="togglePasswordVisibility()" title="إظهار/إخفاء كلمة المرور">
                                        <i class="fa-solid fa-eye" id="eye-icon"></i>
                                    </button>
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                <div class="form-group">
                                    <label class="form-label">الصلاحية:</label>
                                    <select name="role" class="form-control" required>
                                        <option value="student" selected>طالب</option>
                                        <option value="admin">مدير (Admin)</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">الجنس:</label>
                                    <select name="gender" class="form-control">
                                        <option value="unspecified" selected>غير محدد</option>
                                        <option value="male">ذكر</option>
                                        <option value="female">أنثى</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div style="flex: 0 0 250px; background: var(--bg-light); border: 1px dashed var(--border-color); border-radius: var(--radius-md); padding: 25px; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                            <label class="form-label" style="width: 100%; color: var(--text-primary);">الصورة الشخصية (اختياري):</label>
                            <img id="avatar_preview" src="../../uploads/profile_pictures/default_profile.png" alt="Avatar" style="width: 130px; height: 130px; border-radius: 50%; object-fit: cover; border: 4px solid var(--bg-surface); box-shadow: var(--shadow-sm); margin-bottom: 20px;" onerror="this.src='../../uploads/profile_pictures/default_profile.png'">
                            <input type="file" name="profile_picture" id="profile_picture" class="form-control" accept="image/jpeg,image/png,image/webp" style="font-size: 13px; padding: 6px;">
                        </div>
                    </div>

                    <div style="display: flex; gap: 10px; margin-top: 30px; border-top: 1px solid var(--border-color); padding-top: 20px;">
                        <button type="submit" class="btn-add-new" style="flex: 1; justify-content: center; background: var(--success-color); color: #fff !important; font-size: 16px;">
                            <i class="fa-solid fa-save"></i> حفظ وإضافة المستخدم
                        </button>
                        <a href="index.php" class="btn-add-new" style="flex: 1; justify-content: center; background: var(--bg-surface); color: var(--text-primary) !important; border: 1px solid var(--border-color); font-size: 16px;">
                            إلغاء وعودة
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script src="../assets/js/admin_global.js"></script>
    <script>
        function togglePasswordVisibility() {
            var passwordInput = document.getElementById("password");
            var eyeIcon = document.getElementById("eye-icon");
            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                eyeIcon.classList.remove("fa-eye");
                eyeIcon.classList.add("fa-eye-slash");
            } else {
                passwordInput.type = "password";
                eyeIcon.classList.remove("fa-eye-slash");
                eyeIcon.classList.add("fa-eye");
            }
        }

        document.getElementById('profile_picture').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    document.getElementById('avatar_preview').src = event.target.result;
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>