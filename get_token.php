<?php
require_once __DIR__ . '/vendor/autoload.php';

// ضع بياناتك المستخرجة هنا
$clientId = '401764445894-kvo0tst06ab9v5pgfel7jq2likd2icgd.apps.googleusercontent.com';
$clientSecret = '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';
$redirectUri = 'http://localhost/mo7adaraty/get_token.php';

$client = new Google\Client();
$client->setClientId($clientId);
$client->setClientSecret($clientSecret);
$client->setRedirectUri($redirectUri);
$client->addScope(Google\Service\Drive::DRIVE);
$client->setAccessType('offline');
$client->setPrompt('select_account consent');

if (!isset($_GET['code'])) {
    $authUrl = $client->createAuthUrl();
    echo "<div style='font-family: sans-serif; text-align: center; margin-top: 50px;'>";
    echo "<h2>المصادقة مع Google Drive</h2>";
    echo "<a href='$authUrl' style='display: inline-block; padding: 12px 24px; background: #2563eb; color: #fff; text-decoration: none; border-radius: 8px; font-weight: bold;'>تسجيل الدخول وربط الحساب</a>";
    echo "</div>";
    exit();
} else {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    
    if (isset($token['error'])) {
        die("حدث خطأ أثناء جلب الرمز: " . htmlspecialchars($token['error_description'] ?? $token['error']));
    }

    $refreshToken = $token['refresh_token'] ?? null;

    if ($refreshToken) {
        $credentialsData = [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'refresh_token' => $refreshToken
        ];
        file_put_contents(__DIR__ . '/config/drive_oauth.json', json_encode($credentialsData, JSON_PRETTY_PRINT));
        chmod(__DIR__ . '/config/drive_oauth.json', 0644);

        echo "<div style='font-family: sans-serif; text-align: center; margin-top: 50px; color: green;'>";
        echo "<h2>تم استخراج Refresh Token وحفظه بنجاح!</h2>";
        echo "<p>تم إنشاء الملف <code>config/drive_oauth.json</code> وجاهز للاستخدام.</p>";
        echo "<p>يمكنك الآن حذف هذا الملف (get_token.php) وإعادة تجربة <code>test_drive.php</code>.</p>";
        echo "</div>";
    } else {
        echo "<div style='color: red;'>لم يتم العثور على Refresh Token. تأكد من إزالة صلاحية التطبيق من حساب Google وإعادة المحاولة لتوليد رمز جديد.</div>";
    }
}