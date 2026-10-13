<?php
session_start();
require_once '../config/db_connexion.php';

header('Content-Type: text/html; charset=utf-8');

$user_id =$_SESSION['user_id'] ?? null;
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>أداة تشخيص فحص رفع المحاضرات - Mo7adaraty</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Tajawal', sans-serif; background: #0f172a; color: #f8fafc; padding: 30px; margin: 0; }
        .box { max-width: 900px; margin: 0 auto; background: #1e293b; border-radius: 14px; padding: 25px; border: 1px solid #334155; }
        h1 { margin-top: 0; color: #38bdf8; font-size: 22px; border-bottom: 1px solid #334155; padding-bottom: 15px; }
        .item { padding: 12px 16px; margin-bottom: 10px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; font-size: 14px; }
        .ok { background: rgba(16, 185, 129, 0.15); border: 1px solid #10b981; color: #34d399; }
        .warn { background: rgba(245, 158, 11, 0.15); border: 1px solid #f59e0b; color: #fbbf24; }
        .fail { background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #f87171; }
        .badge { font-weight: bold; padding: 4px 10px; border-radius: 6px; font-size: 12px; }
        .badge-ok { background: #10b981; color: #fff; }
        .badge-fail { background: #ef4444; color: #fff; }
        .badge-warn { background: #f59e0b; color: #000; }
        .section-title { margin: 20px 0 10px 0; font-size: 16px; font-weight: bold; color: #94a3b8; }
        pre { background: #0f172a; padding: 12px; border-radius: 8px; font-size: 12px; color: #e2e8f0; overflow-x: auto; direction: ltr; text-align: left; }
    </style>
</head>
<body>

<div class="box">
    <h1><i class="fa-solid fa-stethoscope"></i> فحص وتشخيص أسباب تعثر رفع المحاضرات</h1>

    <!-- 1. فحص الجلسة -->
    <div class="section-title">1. جلسة المستخدم الحالي (Session)</div>
    <?php if ($user_id): ?>
        <div class="item ok">
            <span>مسجل الدخول بمعرف المستخدم: <strong>#<?= htmlspecialchars($user_id) ?></strong></span>
            <span class="badge badge-ok">ناجح</span>
        </div>
    <?php else: ?>
        <div class="item fail">
            <span>غير مسجل الدخول! لا يمكن رفع المحاضرات بدون جلسة صالحة.</span>
            <span class="badge badge-fail">فشل</span>
        </div>
    <?php endif; ?>

    <!-- 2. فحص إعدادات PHP -->
    <div class="section-title">2. إعدادات خادم PHP لأحجام الملفات</div>
    <?php
    $upload_max = ini_get('upload_max_filesize');
    $post_max = ini_get('post_max_size');$file_uploads = ini_get('file_uploads');
    
    function parse_size($size) {
        $unit = preg_replace('/[^bkmgtpezy]/i', '',$size);
        $size = preg_replace('/[^0-9\.]/', '',$size);
        if ($unit) {
            return round($size * pow(1024, stripos('bkmgtpezy',$unit[0])));
        }
        return round($size);
    }
    
    $upload_max_bytes = parse_size($upload_max);
    ?>
    <div class="item <?= $file_uploads ? 'ok' : 'fail' ?>">
        <span>خاصية رفع الملفات (file_uploads): <strong><?= $file_uploads ? 'مفعلة (On)' : 'معطلة (Off)' ?></strong></span>
        <span class="badge <?= $file_uploads ? 'badge-ok' : 'badge-fail' ?>"><?= $file_uploads ? 'ناجح' : 'فشل' ?></span>
    </div>
    
    <div class="item <?= ($upload_max_bytes >= 10 * 1024 * 1024) ? 'ok' : 'warn' ?>">
        <span>الحد الأقصى لرفع الملف (upload_max_filesize): <strong><?= $upload_max ?></strong> (إذا كان 2M سيفشل رفع أي ملف أكبر من 2MB)</span>
        <span class="badge <?= ($upload_max_bytes >= 10 * 1024 * 1024) ? 'badge-ok' : 'badge-warn' ?>">
            <?= ($upload_max_bytes >= 10 * 1024 * 1024) ? 'جيد' : 'منخفض جداً' ?>
        </span>
    </div>

    <div class="item ok">
        <span>الحد الأقصى لبيانات POST (post_max_size): <strong><?= $post_max ?></strong></span>
        <span class="badge badge-ok">معلومة</span>
    </div>

    <!-- 3. فحص مجلدات الرفع وصلاحيات الكتابة -->
    <div class="section-title">3. مسار ومجلدات الرفع على الخادم (Upload Directories)</div>
    <?php
    $base_upload = '../uploads/';
    $lectures_dir = '../uploads/lectures/';$covers_dir = '../uploads/covers/';

    function test_dir($dir,$name) {
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        if (!is_dir($dir)) {
            echo "<div class='item fail'><span>المجلد <strong>{$name}</strong> غير موجود وفشل إنشاؤه: <code>{$dir}</code></span><span class='badge badge-fail'>غير موجود</span></div>";
            return false;
        }
        if (!is_writable($dir)) {
            echo "<div class='item fail'><span>المجلد <strong>{$name}</strong> موجود لكنه غير قابل للكتابة (Permissions issue).</span><span class='badge badge-fail'>صلاحيات</span></div>";
            return false;
        }
        // تجربة كتابة ملف فعلي وحذفه
        $test_file =$dir . 'test_write_' . time() . '.txt';
        if (@file_put_contents($test_file, 'test')) {
            @unlink($test_file);
            echo "<div class='item ok'><span>المجلد <strong>{$name}</strong> جاهز وصالح للكتابة: <code>{$dir}</code></span><span class='badge badge-ok'>ناجح</span></div>";
            return true;
        } else {
            echo "<div class='item fail'><span>فشل إنشاء ملف تجريبي داخل مجلد <strong>{$name}</strong>.</span><span class='badge badge-fail'>فشل الكتابة</span></div>";
            return false;
        }
    }

    test_dir($lectures_dir, 'uploads/lectures/');
    test_dir($covers_dir, 'uploads/covers/');
    ?>

    <!-- 4. فحص جدول المحاضرات lectures وبنيته -->
    <div class="section-title">4. بنية جدول المحاضرات (lectures table schema)</div>
    <?php
    try {
        $stmt_cols =$pdo->query("SHOW COLUMNS FROM lectures");
        $cols =$stmt_cols->fetchAll(PDO::FETCH_ASSOC);
        $col_names = array_column($cols, 'Field');

        $required_cols = ['title', 'file_path', 'offering_id', 'offering_subject_id', 'uploader_user_id', 'status', 'academic_year_id'];$missing_cols = array_diff($required_cols,$col_names);

        if (empty($missing_cols)) {
            echo "<div class='item ok'><span>جميع الأعمدة المطلوبة موجودة في جدول lectures.</span><span class='badge badge-ok'>ناجح</span></div>";
        } else {
            echo "<div class='item fail'><span>أعمدة مفقودة في جدول lectures: <strong>" . implode(', ', $missing_cols) . "</strong></span><span class='badge badge-fail'>حقل ناقص</span></div>";
        }

        // فحص هل offering_subject_id يقبل NULL
        foreach ($cols as$col) {
            if ($col['Field'] === 'offering_subject_id') {
                if ($col['Null'] === 'NO') {
                    echo "<div class='item fail'><span>تحذير حرج: الحقل <code>offering_subject_id</code> لا يقبل NULL! إذا لم يختر الطالب مادة سيفشل الرفع فوراً.</span><span class='badge badge-fail'>خطأ في التصميم</span></div>";
                } else {
                    echo "<div class='item ok'><span>الحقل <code>offering_subject_id</code> يقبل NULL بنجاح عند رفع ملف عام.</span><span class='badge badge-ok'>سليم</span></div>";
                }
            }
        }
    } catch (PDOException $e) {
        echo "<div class='item fail'><span>خطأ في فحص جدول lectures: " . htmlspecialchars($e->getMessage()) . "</span><span class='badge badge-fail'>خطأ SQL</span></div>";
    }
    ?>

    <!-- 5. فحص المحاضرات المعلقة pending للمستخدم -->
    <div class="section-title">5. فحص المحاضرات المرفوعة حالياً في قاعدة البيانات للمستخدم</div>
    <?php
    if ($user_id) {
        try {
            $stmt_user_lecs =$pdo->prepare("SELECT lecture_id, title, status, upload_date FROM lectures WHERE uploader_user_id = ? ORDER BY upload_date DESC");
            $stmt_user_lecs->execute([$user_id]);
            $user_lecs =$stmt_user_lecs->fetchAll(PDO::FETCH_ASSOC);

            if (empty($user_lecs)) {
                echo "<div class='item warn'><span>لم يتم العثور على أي محاضرات مسجلة باسمك في جدول lectures.</span><span class='badge badge-warn'>0 محاضرات</span></div>";
            } else {
                foreach ($user_lecs as$ul) {
                    $is_pending = ($ul['status'] === 'pending');
                    echo "<div class='item " . ($is_pending ? 'warn' : 'ok') . "'>";
                    echo "<span>المحاضرة: <strong>" . htmlspecialchars($ul['title']) . "</strong> (تاريخ: " . $ul['upload_date'] . ")</span>";
                    echo "<span class='badge " . ($is_pending ? 'badge-warn' : 'badge-ok') . "'>الحالة: " . htmlspecialchars($ul['status']) . ($is_pending ? ' (تنتظر موافقة الأدمن)' : ' (منشورة)') . "</span>";
                    echo "</div>";
                }
            }
        } catch (PDOException $e) {
            echo "<div class='item fail'><span>خطأ: " . htmlspecialchars($e->getMessage()) . "</span></div>";
        }
    }
    ?>

    <!-- 6. فحص تسجيل الطالب في المقررات والأفواج -->
    <div class="section-title">6. فحص المقررات المسجل بها الطالب حالياً (Enrollments)</div>
    <?php
    if ($user_id) {
        try {
            $stmt_enr =$pdo->prepare("
                SELECT se.offering_id, d.division_name, c.class_name, g.group_name 
                FROM student_enrollments se
                JOIN course_offerings co ON se.offering_id = co.offering_id
                JOIN divisions d ON co.division_id = d.division_id
                JOIN classes c ON co.class_id = c.class_id
                JOIN `groups` g ON co.group_id = g.group_id
                WHERE se.user_id = ?
            ");
            $stmt_enr->execute([$user_id]);
            $enrs =$stmt_enr->fetchAll(PDO::FETCH_ASSOC);

            if (empty($enrs)) {
                echo "<div class='item fail'><span>الطالب غير مسجل في أي مقرر في جدول student_enrollments! لن يتمكن من الرفع.</span><span class='badge badge-fail'>غير مسجل</span></div>";
            } else {
                foreach ($enrs as$en) {
                    echo "<div class='item ok'>";
                    echo "<span>مقرر متاح للرفع: <strong>" . htmlspecialchars($en['division_name'] . ' - ' .$en['class_name'] . ' - ' . $en['group_name']) . "</strong> (offering_id: " . $en['offering_id'] . ")</span>";
                    echo "<span class='badge badge-ok'>صالح للرفع</span>";
                    echo "</div>";
                }
            }
        } catch (PDOException $e) {
            echo "<div class='item fail'><span>خطأ في فحص التسجيل: " . htmlspecialchars($e->getMessage()) . "</span></div>";
        }
    }
    ?>
</div>

</body>
</html>