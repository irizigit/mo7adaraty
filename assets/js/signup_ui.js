/* =========================================
   Signup Page UI Scripts (signup_ui.js)
   ========================================= */
document.addEventListener('DOMContentLoaded', function() {
    // دالة إظهار وإخفاء كلمة المرور المستخرجة من الصفحة
    window.togglePasswordCustom = function() {
        const passwordInput = document.getElementById('password');
        if (passwordInput) {
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
            } else {
                passwordInput.type = 'password';
            }
        }
    };
});