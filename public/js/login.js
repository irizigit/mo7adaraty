// في public/js/login.js

document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('loginForm');
    const messageDiv = document.getElementById('message');

    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value;

        messageDiv.style.display = 'none';
        messageDiv.className = 'message';
        messageDiv.textContent = '';

        if (!username || !password) {
            showMessage('الرجاء إدخال اسم المستخدم وكلمة المرور.', 'error');
            return;
        }

        try {
            // التعديل هنا: استخدام المسار المطلق من جذر المشروع /islamique/
            const response = await fetch('/islamique/server/login_handler.php', { // <--- التعديل الصحيح
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ username, password })
            });

            const data = await response.json();

            if (data.success) {
                showMessage(data.message, 'success');
                loginForm.reset();
                if (data.is_admin) {
                    // أيضاً المسار المطلق من جذر المشروع
                    window.location.href = '/islamique/admin/dashboard.php'; // <--- التعديل الصحيح
                } else {
                    // أيضاً المسار المطلق من جذر المشروع
                    window.location.href = '/islamique/public/index.php'; // <--- التعديل الصحيح
                }
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