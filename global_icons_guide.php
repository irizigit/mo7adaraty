<?php
// هذا الملف هو مجرد دليل مرجعي (Documentation) للمطور.
// يمكنك فتحه في المتصفح لقراءة الخطوات ونسخ الأكواد بسهولة.
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>دليل استخدام الأيقونات العامة - Mo7adaraty</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f4f7f6;
            color: #333;
            line-height: 1.8;
            margin: 0;
            padding: 40px 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: #fff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        }
        h1 {
            color: #6f42c1;
            text-align: center;
            border-bottom: 2px solid #eee;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        h2 {
            color: #007bff;
            margin-top: 40px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .step-number {
            background: #007bff;
            color: #fff;
            width: 30px;
            height: 30px;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            border-radius: 50%;
            font-size: 16px;
        }
        p {
            font-size: 16px;
            color: #555;
        }
        pre {
            background: #282c34;
            color: #abb2bf;
            padding: 20px;
            border-radius: 8px;
            overflow-x: auto;
            direction: ltr;
            text-align: left;
            font-family: Consolas, Monaco, monospace;
            font-size: 15px;
            border-right: 4px solid #6f42c1;
        }
        code {
            font-family: Consolas, Monaco, monospace;
        }
        .highlight {
            background: #e7f1ff;
            color: #007bff;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: bold;
        }
        .note {
            background: #fff3cd;
            border: 1px solid #ffe69c;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>📚 دليل مطور Mo7adaraty: نظام الأيقونات العامة (PNG)</h1>
    <p>هذا الدليل يشرح لك دورة حياة إضافة أيقونة جديدة إلى النظام من الصفر وحتى استخدامها في الصفحات كبديل احترافي وسريع لمكتبة FontAwesome.</p>
    
    <div class="note">
        💡 <strong>مثال تطبيقي:</strong> في هذا الدليل، سنقوم بإضافة أيقونة "طباعة" (Print) جديدة إلى النظام.
    </div>

    <h2><span class="step-number">1</span> قاعدة البيانات (إخبار النظام بالأيقونة)</h2>
    <p>أول خطوة هي إخبار قاعدة البيانات بوجود أيقونة جديدة يجب إدارتها. قم بفتح <span class="highlight">phpMyAdmin</span> وقم بتنفيذ كود الـ SQL التالي:</p>
    <pre><code>INSERT INTO `design_assets` (`page_section`, `asset_key`, `asset_type`, `file_path`, `description`) 
VALUES ('global_icons', 'print', 'icon', 'admin/assets/icons/print.png', 'أيقونة الطباعة (Print)');</code></pre>
    <p><strong>تنبيه:</strong> تأكد أن <code>asset_key</code> (وهو <code>print</code> هنا) هو نفس اسم الصورة التي سترفعها لاحقاً.</p>

    <h2><span class="step-number">2</span> إضافة الأيقونة للوحة التحكم (ليتمكن الأدمن من رفعها)</h2>
    <p>اذهب إلى ملف التحكم الخاص برفع الأيقونات العامة في هذا المسار:<br>
    <span class="highlight">mo7adaraty/admin/design_settings/global_icons/index.php</span></p>
    <p>ابحث عن المصفوفة <code>$groups</code> في أعلى الملف، وأضف المعرف <code>print</code> إلى القائمة، لتصبح هكذا:</p>
    <pre><code>$groups = [
    'أيقونات الإجراءات (الأزرار)' => ['add', 'edit', 'delete', 'print'], // أضفناها هنا
    'أيقونات النظام' => ['sitemap', 'menu', 'student']
];</code></pre>

    <h2><span class="step-number">3</span> رفع صورة الأيقونة من لوحة التحكم</h2>
    <p>الآن، واجهة النظام جاهزة لاستقبال الصورة:</p>
    <ul>
        <li>ادخل إلى لوحة تحكم الأدمن في متصفحك.</li>
        <li>اذهب إلى: <strong>إعدادات التصميم</strong> ➔ <strong>الأيقونات العامة (Global)</strong>.</li>
        <li>ستجد أن مربع "أيقونة الطباعة" قد ظهر تلقائياً!</li>
        <li>قم برفع صورة بصيغة <strong>PNG حصراً</strong> (مثلاً <code>print.png</code>) واضغط "حفظ التغييرات".</li>
    </ul>

    <h2><span class="step-number">4</span> كيف يستدعي النظام الأيقونة؟ (ملف الهيدر)</h2>
    <p>لقد قمنا مسبقاً ببرمجة دالة ذكية اسمها <code>getUiIcon()</code> داخل ملف:<br>
    <span class="highlight">mo7adaraty/admin/templates/header.php</span></p>
    <p>هذه الدالة تقوم بالبحث عن الأيقونة تلقائياً في مجلد <code>admin/assets/icons/</code>. <strong>أنت لست بحاجة لتعديل هذا الملف أبداً</strong> عند إضافة أيقونات جديدة، فهو يعمل بشكل أوتوماتيكي.</p>

    <h2><span class="step-number">5</span> طريقة استخدام الأيقونة في أي صفحة HTML/PHP</h2>
    <p>الآن الخطوة الأمتع! لتعويض كود FontAwesome القديم بأيقونتك الجديدة، افتح أي صفحة (مثلاً صفحة إدارة المستخدمين).</p>
    <p><strong>الكود القديم (FontAwesome):</strong></p>
    <pre><code>&lt;button class="btn-print"&gt;&lt;i class="fa-solid fa-print"&gt;&lt;/i&gt; طباعة التقرير&lt;/button&gt;</code></pre>
    
    <p><strong>الكود الجديد (نظامك الخاص المطور):</strong></p>
    <pre><code>&lt;button class="btn-print"&gt;&lt;?= getUiIcon('print', '18px') ?&gt; طباعة التقرير&lt;/button&gt;</code></pre>

    <div class="note" style="background: #e2e3e5; border-color: #d6d8db; color: #383d41;">
        <strong>⚙️ شرح معطيات الدالة getUiIcon:</strong><br><br>
        <code>getUiIcon($icon_name, $size, $margin)</code><br><br>
        1. <strong>$icon_name</strong>: (إجباري) المعرف الذي وضعته في قاعدة البيانات (مثل 'print' أو 'edit').<br>
        2. <strong>$size</strong>: (اختياري) مقاس الأيقونة، الافتراضي هو '18px'.<br>
        3. <strong>$margin</strong>: (اختياري) الهوامش لإبعاد النص عن الصورة، الافتراضي هو 'margin-left: 8px;'.
    </div>

    <p style="text-align: center; margin-top: 50px; font-weight: bold; color: #28a745;">
        🎉 بهذا النظام، أصبح موقعك أسرع بكثير، ولديك تحكم 100% في تصميم كل زر وأيقونة!
    </p>
</div>

</body>
</html>