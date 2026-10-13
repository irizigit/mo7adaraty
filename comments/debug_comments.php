<?php
session_start();
require_once '../config/db_connexion.php';

// أداة فحص نظام التعليقات (Diagnostic Tool)
$logs = [];

// 1. فحص الجلسة
if (!isset($_SESSION['user_id'])) {
    $logs[] = "❌ خطأ: أنت لست مسجل الدخول. الجلسة مفقودة.";
} else {
    $logs[] = "✅ الجلسة نشطة. معرّف المستخدم: " . $_SESSION['user_id'];
}

// 2. فحص اتصال قاعدة البيانات
if ($pdo) {
    $logs[] = "✅ الاتصال بقاعدة البيانات يعمل بنجاح.";
} else {
    $logs[] = "❌ خطأ: فشل الاتصال بقاعدة البيانات.";
}

// 3. فحص جدول التعليقات
try {
    $stmt = $pdo->query("DESCRIBE comments");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $logs[] = "✅ جدول 'comments' موجود. الأعمدة: " . implode(', ', $columns);
} catch (PDOException $e) {
    $logs[] = "❌ خطأ: جدول 'comments' غير موجود أو تالف. التفاصيل: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>أداة فحص التعليقات</title>
    <style>
        body { font-family: Tahoma, sans-serif; background: #121212; color: #e0e0e0; padding: 20px; }
        .box { background: #1e1e1e; padding: 20px; border-radius: 8px; border: 1px solid #333; margin-bottom: 20px; }
        .log { padding: 10px; background: #2a2a2a; border-left: 4px solid #4facfe; margin-bottom: 10px; font-family: monospace; }
        button { background: #4facfe; color: #000; padding: 10px 20px; border: none; font-weight: bold; cursor: pointer; border-radius: 4px; }
        #ajax_result { margin-top: 15px; padding: 15px; background: #000; color: #0f0; font-family: monospace; white-space: pre-wrap; border-radius: 4px;}
    </style>
</head>
<body>
    <h1>أداة فحص نظام التعليقات (Debug Mode)</h1>
    
    <div class="box">
        <h3>1. تقرير النظام الأساسي:</h3>
        <?php foreach($logs as $log): ?>
            <div class="log"><?php echo htmlspecialchars($log); ?></div>
        <?php endforeach; ?>
    </div>

    <div class="box">
        <h3>2. محاكاة طلب إضافة تعليق (AJAX Simulation):</h3>
        <p>استخدم هذا الزر لإرسال طلب حقيقي للسيرفر وعرض الرد الخام (Raw Response) لنتأكد من عدم وجود شوائب.</p>
        <form id="debugForm">
            <input type="hidden" name="content_id" value="1">
            <input type="hidden" name="content_type" value="lecture">
            <textarea name="comment_text" style="width:100%; height:60px; margin-bottom:10px; background:#333; color:#fff;" placeholder="اكتب تعليق تجريبي..."></textarea>
            <button type="submit">إرسال واختبار</button>
        </form>
        <div id="ajax_result">في انتظار الاختبار...</div>
    </div>

    <script>
        document.getElementById('debugForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const resultBox = document.getElementById('ajax_result');
            resultBox.innerHTML = "جاري الإرسال...";
            
            const formData = new FormData(this);
            fetch('add_comment.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text()) // نقرأ كنص خام لنرى الشوائب إن وجدت
            .then(text => {
                resultBox.innerHTML = "الرد الخام من السيرفر:\n\n" + text;
                try {
                    const json = JSON.parse(text);
                    resultBox.innerHTML += "\n\n✅ JSON صالح ومطابق! الرسالة: " + json.message;
                } catch (err) {
                    resultBox.innerHTML += "\n\n❌ خطأ: الرد ليس JSON صالح! السبب: " + err.message;
                }
            })
            .catch(err => {
                resultBox.innerHTML = "فشل الاتصال: " + err.message;
            });
        });
    </script>
</body>
</html>