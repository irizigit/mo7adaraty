<?php
// تفعيل عرض الأخطاء لغرض الاختبار
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../../config/db_connexion.php';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>اختبار مسارات الأيقونات</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background-color: #f9f9f9; }
        h2 { color: #333; border-bottom: 2px solid #ccc; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: right; }
        th { background-color: #007bff; color: white; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        img { max-width: 60px; max-height: 60px; background: #e0e0e0; padding: 5px; border-radius: 5px; }
        code { background: #f4f4f4; padding: 2px 5px; border-radius: 3px; color: #d63384; direction: ltr; display: inline-block; }
    </style>
</head>
<body>

    <h2>🔍 أداة فحص وتشخيص أيقونات صفحة "من نحن"</h2>

    <?php
    $page_section = 'AboutUs';
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM design_assets WHERE page_section = ?");
        $stmt->execute([$page_section]);
        $assets = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($assets) == 0) {
            echo "<p class='error'>❌ لا توجد أي بيانات في قاعدة البيانات لقسم AboutUs. عملية الإدخال (INSERT) فشلت!</p>";
        } else {
            echo "<p class='success'>✅ تم العثور على " . count($assets) . " أيقونات مسجلة في قاعدة البيانات.</p>";
            
            echo "<table>";
            echo "<tr>
                    <th>مفتاح الأيقونة</th>
                    <th>المسار في القاعدة (DB)</th>
                    <th>فحص وجود الملف (Server)</th>
                    <th>عرض (Relative Path)</th>
                    <th>عرض (Absolute Path)</th>
                  </tr>";

            foreach ($assets as $asset) {
                // المسار المخزن في قاعدة البيانات
                $db_path = $asset['file_path']; 
                
                // المسار الفعلي في السيرفر انطلاقاً من هذا الملف (للتأكد من وجود الملف)
                $physical_path = '../../../' . $db_path; 
                
                // المسار المطلق للمتصفح (يبدأ من مجلد المشروع الرئيسي)
                // بافتراض أن اسم مجلد المشروع في السيرفر المحلي هو mo7adaraty
                $absolute_web_path = '/mo7adaraty/' . $db_path; 

                // فحص هل الملف موجود فعلياً في مجلد uploads أم لا
                if (file_exists($physical_path)) {
                    $file_status = "<span class='success'>✅ موجود</span><br><code style='font-size:11px;'>$physical_path</code>";
                } else {
                    $file_status = "<span class='error'>❌ غير موجود</span><br><code style='font-size:11px;'>$physical_path</code>";
                }

                echo "<tr>";
                echo "<td><strong>{$asset['asset_key']}</strong></td>";
                echo "<td><code>{$db_path}</code></td>";
                echo "<td>{$file_status}</td>";
                
                // محاولة عرض الصورة بالمسار النسبي (مثل المستخدم في لوحة التحكم)
                echo "<td>
                        <img src='{$physical_path}?v=".time()."' alt='Relative'>
                        <br><code style='font-size:10px;'>../../../{$db_path}</code>
                      </td>";
                      
                // محاولة عرض الصورة بالمسار المطلق (الطريقة الأضمن لحل مشاكل المسارات)
                echo "<td>
                        <img src='{$absolute_web_path}?v=".time()."' alt='Absolute'>
                        <br><code style='font-size:10px;'>/mo7adaraty/{$db_path}</code>
                      </td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } catch (PDOException $e) {
        echo "<p class='error'>❌ خطأ في قاعدة البيانات: " . $e->getMessage() . "</p>";
    }
    ?>

</body>
</html>