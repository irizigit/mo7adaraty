<?php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'mo7adaraty.official@gmail.com'); // بريد المنصة المخصص
define('SMTP_PASS', 'pctj fzws nrtu ksfe');        // كلمة مرور التطبيقات المكونة من 16 حرفاً
define('SMTP_FROM_NAME', 'Mo7adaraty Platform');

if (!function_exists('isValidTrustedEmail')) {
    function isValidTrustedEmail($email) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return false;
        $domain = strtolower(substr(strrchr($email, "@"), 1));
        $trusted = [
            'gmail.com', 'googlemail.com',
            'outlook.com', 'outlook.fr', 'hotmail.com', 'hotmail.fr', 'live.com', 'live.fr',
            'yahoo.com', 'yahoo.fr',
            'icloud.com',
            'yandex.com', 'yandex.ru'
        ];
        return in_array($domain, $trusted, true);
    }
}

if (!function_exists('maskEmail')) {
    function maskEmail($email) {
        $parts = explode("@", $email);
        $name = $parts[0];
        $domain = $parts[1];
        $visible = substr($name, 0, 2);
        return $visible . str_repeat("*", max(strlen($name) - 2, 3)) . "@" . $domain;
    }
}