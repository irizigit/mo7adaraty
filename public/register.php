<!DOCTYPE html>
<html lang="ar" dir="rtl"> <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل مستخدم جديد</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h2>تسجيل مستخدم جديد</h2>
        <div id="message" class="message" style="display:none;"></div>
        <form id="registerForm">
            <div class="form-group">
                <label for="username">اسم المستخدم:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="email">البريد الإلكتروني:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">كلمة المرور:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">تأكيد كلمة المرور:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit">تسجيل</button>
        </form>
        <p class="login-link">
            هل لديك حساب بالفعل؟ <a href="login.php">تسجيل الدخول</a>
        </p>
    </div>

    <script src="js/register.js"></script>
</body>
</html>