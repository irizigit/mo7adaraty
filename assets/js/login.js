/* =========================================
   Login Page Scripts (login.js)
   ========================================= */
document.addEventListener('DOMContentLoaded', function() {
    // دالة إظهار وإخفاء كلمة المرور
    window.togglePasswordCustom = function() {
        const passwordInput = document.getElementById('password');
        const icon = document.getElementById('toggleEyeIcon');
        
        if (passwordInput && icon) {
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                // التعامل مع أيقونات FontAwesome
                if (icon.tagName.toLowerCase() === 'i') {
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                }
            } else {
                passwordInput.type = 'password';
                if (icon.tagName.toLowerCase() === 'i') {
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            }
        }
    };
});