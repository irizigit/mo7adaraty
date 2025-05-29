<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h2>تسجيل الدخول</h2>
        <div id="message" class="message" style="display:none;"></div>
        <form id="loginForm">
            <div class="form-group">
                <label for="username">اسم المستخدم:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">كلمة المرور:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">تسجيل الدخول</button>
        </form>
        <p class="register-link">
            ليس لديك حساب؟ <a href="register.php">سجل الآن</a>
        </p>
    </div>

    <script src="js/login.js"></script>
</body>
</html>