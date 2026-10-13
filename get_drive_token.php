<?php
require_once __DIR__ . '/vendor/autoload.php';

$client_id     = '439933195154-k87imajrrv8k0de029hphnqkc8dqiak9.apps.googleusercontent.com';
$client_secret = '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';
$redirect_uri  = 'http://localhost/mo7adaraty/get_drive_token.php';

$client = new Google\Client();
$client->setClientId($client_id);
$client->setClientSecret($client_secret);
$client->setRedirectUri($redirect_uri);
$client->addScope(Google\Service\Drive::DRIVE);
$client->setAccessType('offline');
$client->setPrompt('consent');

if (!isset($_GET['code'])) {
    $auth_url = $client->createAuthUrl();
    echo "<div style='font-family: Tahoma, Arial; text-align: center; padding: 50px;'>";
    echo "<h2>ربط حساب منصة محاضراتي مع Google Drive</h2>";
    echo "<p>اضغط على الزر وسجل الدخول بحساب <strong>المنصة الرسمي (المالك للمجلد)</strong>:</p>";
    echo "<a href='{$auth_url}' style='background: #1a73e8; color: #fff; padding: 12px 24px; text-decoration: none; border-radius: 8px; font-weight: bold;'>تسجيل الدخول وربط الحساب</a>";
    echo "</div>";
    exit();
} else {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    
    if (isset($token['error'])) {
        die("حدث خطأ أثناء جلب الرمز: " . ($token['error_description'] ?? $token['error']));
    }

    $new_refresh_token = $token['refresh_token'] ?? '';
    if (empty($new_refresh_token)) {
        die("تنبيه: لم يقم Google بإرسال refresh_token جديد لأن التطبيق مربوط مسبقاً. يرجى إلغاء إذن التطبيق من إعدادات حساب Google ثم إعادة فتح الرابط.");
    }

    $oauth_data = [
        'client_id'     => $client_id,
        'client_secret' => $client_secret,
        'refresh_token' => $new_refresh_token
    ];

    file_put_contents(__DIR__ . '/config/drive_oauth.json', json_encode($oauth_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    echo "<div style='font-family: Tahoma, Arial; text-align: center; padding: 50px; color: #10b981;'>";
    echo "<h2>✅ تم الربط وتوليد الرمز بنجاح تام!</h2>";
    echo "<p>تم تحديث ملف <code>config/drive_oauth.json</code> بالـ Refresh Token الجديد الخاص بالحساب الصحيح.</p>";
    echo "<p>يمكنك الآن تجربة الرفع في <code>test_drive_upload.php</code> ورفع المحاضرات مباشرة.</p>";
    echo "</div>";
}