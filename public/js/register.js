document.addEventListener('DOMContentLoaded', () => {
    const registerForm = document.getElementById('registerForm');
    const messageDiv = document.getElementById('message');

    registerForm.addEventListener('submit', async (e) => {
        e.preventDefault(); // منع الإرسال الافتراضي للنموذج

        const username = document.getElementById('username').value.trim();
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;

        // إخفاء أي رسائل سابقة
        messageDiv.style.display = 'none';
        messageDiv.className = 'message';
        messageDiv.textContent = '';

        // التحقق من أن جميع الحقول مملوءة
        if (!username || !email || !password || !confirmPassword) {
            showMessage('الرجاء ملء جميع الحقول.', 'error');
            return;
        }

        // التحقق من تطابق كلمات المرور
        if (password !== confirmPassword) {
            showMessage('كلمتا المرور غير متطابقتين.', 'error');
            return;
        }

        // التحقق من طول كلمة المرور (اختياري، يمكنك تعديلها)
        if (password.length < 6) {
            showMessage('يجب أن تكون كلمة المرور 6 أحرف على الأقل.', 'error');
            return;
        }

        // إرسال البيانات إلى السكربت الخلفي (PHP)
        try {
            const response = await fetch('../server/register_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json' // نرسل البيانات كـ JSON
                },
                body: JSON.stringify({ username, email, password })
            });

            const data = await response.json(); // توقع استجابة JSON من السيرفر

            if (data.success) {
                showMessage(data.message, 'success');
                registerForm.reset(); // مسح النموذج بعد التسجيل الناجح
                // يمكن توجيه المستخدم لصفحة تسجيل الدخول هنا بعد فترة
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 2000); // التوجيه بعد ثانيتين
            } else {
                showMessage(data.message, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showMessage('حدث خطأ أثناء الاتصال بالخادم. الرجاء المحاولة مرة أخرى.', 'error');
        }
    });

    function showMessage(msg, type) {
        messageDiv.textContent = msg;
        messageDiv.className = `message ${type}`;
        messageDiv.style.display = 'block';
    }
});