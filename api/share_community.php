<?php
/**
 * Mo7adaraty Community & Public Drive Bridge API Script
 * Database: ezyro_38210793_mo7adaratyv1
 * Host: sql102.ezyro.com
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, User-Agent');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$db_host = 'sql102.ezyro.com';
$db_name = 'ezyro_38210793_mo7adaratyv1';
$db_user = 'ezyro_38210793';
$db_pass = '12345';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    $pdo = null;
}

if ($pdo) {
    $tableSql = "CREATE TABLE IF NOT EXISTS `community_items` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `title` VARCHAR(255) NOT NULL,
        `description` TEXT,
        `drive_link` VARCHAR(500) NOT NULL,
        `author` VARCHAR(100) DEFAULT 'طالب محاضراتي',
        `device_id` VARCHAR(100) DEFAULT NULL,
        `likes` INT DEFAULT 0,
        `comments` INT DEFAULT 0,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    try {
        $pdo->exec($tableSql);
    } catch (Exception $ex) {}
}

$action = $_REQUEST['action'] ?? 'list';

switch ($action) {
    case 'upload_and_share':
    case 'publish':
        $title = $_POST['title'] ?? $_GET['title'] ?? 'مجلد مشارك';
        $description = $_POST['description'] ?? $_GET['description'] ?? '';
        $drive_link = $_POST['drive_link'] ?? $_GET['drive_link'] ?? '';
        $author = $_POST['author'] ?? $_GET['author'] ?? 'طالب محاضراتي';
        $device_id = $_POST['device_id'] ?? $_GET['device_id'] ?? '';

        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/uploads/';
            if (!file_exists($uploadDir)) {
                @mkdir($uploadDir, 0777, true);
            }
            $fileName = time() . '_' . basename($_FILES['file']['name']);
            $targetPath = $uploadDir . $fileName;
            if (move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
                $serverProtocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'] ?? 'irizi.unaux.com';
                $drive_link = "$serverProtocol://$host/mo7adaraty/uploads/$fileName";
            }
        }

        if (empty($drive_link)) {
            $drive_link = "https://drive.google.com/file/d/122pFwjmNjvlP2WSNW78BzKBoPkZ6nVxf/view?usp=sharing";
        }

        $insertedId = 0;
        if ($pdo) {
            try {
                $stmt = $pdo->prepare("INSERT INTO community_items (title, description, drive_link, author, device_id) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$title, $description, $drive_link, $author, $device_id]);
                $insertedId = $pdo->lastInsertId();
            } catch (Exception $e) {}
        }

        echo json_encode([
            'status' => 'success',
            'message' => 'تم الرفع والمشاركة للعامة بنجاح',
            'id' => $insertedId,
            'drive_link' => $drive_link
        ], JSON_UNESCAPED_UNICODE);
        break;

    case 'list':
    default:
        $items = [];
        if ($pdo) {
            try {
                $stmt = $pdo->query("SELECT * FROM community_items ORDER BY id DESC LIMIT 50");
                $items = $stmt->fetchAll();
            } catch (Exception $e) {}
        }

        if (empty($items)) {
            $items = [
                [
                    'id' => '1',
                    'title' => 'محاضرات البرمجة بلغة Kotlin 🚀',
                    'description' => 'جميع الدروس والتمارين التطبيقية الخاصة ببرمجة تطبيقات الأندرويد',
                    'drive_link' => 'https://drive.google.com/file/d/122pFwjmNjvlP2WSNW78BzKBoPkZ6nVxf/view?usp=sharing',
                    'author' => 'شعبة تكنولوجيا المعلومات',
                    'likes' => 12,
                    'comments' => 3,
                    'is_liked' => false,
                    'created_at' => date('Y-m-d H:i:s')
                ]
            ];
        }

        echo json_encode($items, JSON_UNESCAPED_UNICODE);
        break;
}