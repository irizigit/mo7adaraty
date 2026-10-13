<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/vendor/autoload.php';

use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;

$target_folder_id = '1NLO_vkHwFprdlgENyRxi0PuAdj1iZCt1-'; // استبدل هذا بالمعرف الصحيح للمجلد الجديد الذي قمت بإنشائه
$oauth_path = __DIR__ . '/config/drive_oauth.json';

$result_message = '';
$error_message = '';

if (!file_exists($oauth_path)) {
    die("خطأ: لم يتم العثور على ملف config/drive_oauth.json. يرجى تشغيل get_drive_token.php أولاً.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_upload'])) {
    try {
        $config = json_decode(file_get_contents($oauth_path), true);

        $client = new Client();
        $client->setClientId($config['client_id']);
        $client->setClientSecret($config['client_secret']);
        $client->addScope(Drive::DRIVE);
        $client->setAccessType('offline');

        $accessToken = $client->fetchAccessTokenWithRefreshToken($config['refresh_token']);
        if (isset($accessToken['error'])) {
            throw new Exception("فشل تجديد رمز الوصول: " . ($accessToken['error_description'] ?? $accessToken['error']));
        }
        $client->setAccessToken($accessToken);

        $service = new Drive($client);

        $fileMetadata = new DriveFile([
            'name'     => 'test_connection_' . date('Y-m-d_H-i-s') . '.txt',
            'parents'  => [$target_folder_id]
        ]);

        $content = "تم الرفع بنجاح عبر حساب المنصة الرسمي بتاريخ: " . date('Y-m-d H:i:s');

        $file = $service->files->create($fileMetadata, [
            'data'       => $content,
            'mimeType'   => 'text/plain',
            'uploadType' => 'multipart',
            'fields'     => 'id, name, webViewLink'
        ]);

        $result_message = "تم رفع الملف بنجاح في الحساب الجديد! معرّف الملف: " . $file->id;

    } catch (Exception $e) {
        $error_message = "فشل الرفع: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>اختبار الرفع على Google Drive</title>
    <style>
        body { font-family: Tahoma, Arial, sans-serif; background: #0f172a; color: #fff; padding: 30px; }
        .box { max-width: 600px; margin: auto; background: #1e293b; padding: 25px; border-radius: 12px; border: 1px solid #334155; }
        .success { background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #34d399; padding: 12px; border-radius: 8px; margin-bottom: 15px; }
        .error { background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #f87171; padding: 12px; border-radius: 8px; margin-bottom: 15px; }
        button { background: #0284c7; color: #fff; border: none; padding: 12px 20px; border-radius: 8px; font-weight: bold; cursor: pointer; width: 100%; font-size: 15px; }
    </style>
</head>
<body>

<div class="box">
    <h2>اختبار الرفع عبر حساب المنصة (OAuth 2.0)</h2>

    <?php if ($result_message): ?>
        <div class="success"><?php echo htmlspecialchars($result_message); ?></div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="error"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <p>المجلد المستهدف: <code><?php echo htmlspecialchars($target_folder_id); ?></code></p>

    <form method="POST">
        <button type="submit" name="test_upload">رفع ملف تجريبي الآن</button>
    </form>
</div>

</body>
</html>