<?php
require_once __DIR__ . '/../vendor/autoload.php';

class GoogleDriveService
{
    private $service;
    // ضع هنا معرف المجلد الجديد الذي نجح معك في الاختبار
    private $folderId = '1SP2rwe7TxqHAPP3SpMxrXmMljagZ7yf-';

    public function __construct()
    {
        $oauthConfigPath = __DIR__ . '/drive_oauth.json';

        if (!file_exists($oauthConfigPath)) {
            throw new Exception("ملف إعدادات التوثيق غير موجود: config/drive_oauth.json. يرجى إتمام المصادقة أولاً.");
        }

        $config = json_decode(file_get_contents($oauthConfigPath), true);
        if (!$config || empty($config['client_id']) || empty($config['client_secret']) || empty($config['refresh_token'])) {
            throw new Exception("بيانات التوثيق داخل config/drive_oauth.json غير صالحة.");
        }

        $client = new Google\Client();
        $client->setClientId($config['client_id']);
        $client->setClientSecret($config['client_secret']);
        $client->addScope(Google\Service\Drive::DRIVE);
        $client->setAccessType('offline');

        $accessToken = $client->fetchAccessTokenWithRefreshToken($config['refresh_token']);
        if (isset($accessToken['error'])) {
            throw new Exception("فشل تجديد رمز الوصول: " . ($accessToken['error_description'] ?? $accessToken['error']));
        }

        $client->setAccessToken($accessToken);
        $this->service = new Google\Service\Drive($client);
    }

    /**
     * البحث عن مجلد بالاسم داخل مجلد أب معين، وإنشاؤه إن لم يكن موجوداً
     */
    public function getOrCreateFolder($folderName, $parentId)
    {
        $sanitizedName = str_replace("'", "\\'", $folderName);
        $query = "mimeType = 'application/vnd.google-apps.folder' and name = '{$sanitizedName}' and '{$parentId}' in parents and trashed = false";

        $response = $this->service->files->listFiles([
            'q' => $query,
            'spaces' => 'drive',
            'fields' => 'files(id, name)'
        ]);

        $files = $response->getFiles();
        if (!empty($files)) {
            return $files[0]->getId();
        }

        $folderMetadata = new Google\Service\Drive\DriveFile([
            'name' => $folderName,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents' => [$parentId]
        ]);

        $folder = $this->service->files->create($folderMetadata, [
            'fields' => 'id'
        ]);

        return $folder->getId();
    }

    /**
     * إنشاء أو جلب المسار الهرمي للمجلدات بالترتيب: شعبة > فصل > مادة > أستاذ
     */
    public function getOrCreateHierarchy(array $folderNames, $rootId = null)
    {
        $currentParentId = $rootId ?: $this->folderId;

        foreach ($folderNames as $name) {
            $name = trim($name);
            if ($name === '') {
                $name = 'عام';
            }
            $currentParentId = $this->getOrCreateFolder($name, $currentParentId);
        }

        return $currentParentId;
    }

    /**
     * رفع الملف إلى مجلد محدد وتسميته بالاسم المطلوب مع منحه صلاحية القراءة للجميع
     */
    public function uploadFile($localFilePath, $fileName, $mimeType = 'application/pdf', $targetFolderId = null)
    {
        if (!file_exists($localFilePath)) {
            throw new Exception("الملف المحلي غير موجود: " . $localFilePath);
        }

        $destinationFolderId = $targetFolderId ?: $this->folderId;

        $fileMetadata = new Google\Service\Drive\DriveFile([
            'name' => $fileName,
            'parents' => [$destinationFolderId]
        ]);

        $content = file_get_contents($localFilePath);

        $uploadedFile = $this->service->files->create($fileMetadata, [
            'data' => $content,
            'mimeType' => $mimeType,
            'uploadType' => 'multipart',
            'fields' => 'id, webViewLink, webContentLink'
        ]);

        $fileId = $uploadedFile->id;

        $permission = new Google\Service\Drive\Permission([
            'type' => 'anyone',
            'role' => 'reader'
        ]);
        $this->service->permissions->create($fileId, $permission);

        return [
            'file_id'      => $fileId,
            'view_url'     => $uploadedFile->webViewLink,
            'preview_url'  => "https://drive.google.com/file/d/{$fileId}/preview",
            'download_url' => "https://drive.google.com/uc?export=download&id={$fileId}"
        ];
    }

    public function deleteFile($fileId)
    {
        try {
            $this->service->files->delete($fileId);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}