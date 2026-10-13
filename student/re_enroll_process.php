<?php
session_start();
require_once '../config/db_connexion.php';

// حماية الصفحة
if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: profil.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// ----------------------------------------------------
// تنظيف وتأمين مصفوفة المقررات مع منع التكرار
// ----------------------------------------------------
$raw_offering_ids = [];
if (isset($_POST['offering_id']) && is_array($_POST['offering_id'])) {
    // جلب القيم الخام وتحويلها لأرقام صحيحة وإزالة القيم الفارغة
    $raw_offering_ids = array_filter(array_map('intval', $_POST['offering_id']));
}

$offering_ids = array_unique($raw_offering_ids);

// التحقق من الحقول الأساسية
if (empty($offering_ids)) {
    header('Location: profil.php?error=' . urlencode('يرجى اختيار مقرر واحد على الأقل.'));
    exit();
}

// التحقق الصارم: منع إرسال نفس المقرر مرتين
if (count($raw_offering_ids) > count($offering_ids)) {
    header('Location: profil.php?error=' . urlencode('خطأ منطقي: لا يمكنك التسجيل في نفس المقرر أكثر من مرة.'));
    exit();
}

// الحد الأقصى للمقررات المسموح بها
if (count($offering_ids) > 2) {
    header('Location: profil.php?error=' . urlencode('لا يمكنك التسجيل في أكثر من مقررين كحد أقصى.'));
    exit();
}

try {
    $pdo->beginTransaction();

    // 1. الأمان: جلب السنة الجامعية المفعلة حالياً من السيرفر
    $stmt_year = $pdo->query("SELECT id FROM academic_years WHERE is_current = 1 LIMIT 1");
    $current_year_id = $stmt_year->fetchColumn();

    if (!$current_year_id) {
        throw new Exception("خطأ: لا توجد سنة جامعية مفعلة حالياً في النظام.");
    }

    // 2. التأكد من أن الطالب غير مسجل مسبقاً في هذا الموسم لتفادي التكرار المزدوج
    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM student_enrollments WHERE user_id = ? AND academic_year_id = ?");
    $stmt_check->execute([$user_id, $current_year_id]);
    
    if ($stmt_check->fetchColumn() > 0) {
        throw new Exception("أنت مسجل بالفعل في مقررات هذا الموسم. لا يمكنك التسجيل مرة أخرى.");
    }

    // 3. التحقق الأمني من أن جميع المقررات المختارة تنتمي للسنة الحالية ومفعلة
    $placeholders = implode(',', array_fill(0, count($offering_ids), '?'));
    $check_sql = "SELECT COUNT(*) FROM course_offerings WHERE academic_year_id = ? AND status = 1 AND offering_id IN ($placeholders)";
    
    $check_stmt = $pdo->prepare($check_sql);
    $check_params = array_merge([$current_year_id], $offering_ids);
    $check_stmt->execute($check_params);
    
    if ($check_stmt->fetchColumn() != count($offering_ids)) {
        throw new Exception("خطأ أمني: أحد المقررات المختارة غير متاح في الموسم الحالي أو تم إيقافه مؤخراً.");
    }

    // 4. إدخال التسجيلات الجديدة في الموسم الجديد
    $stmt_enroll = $pdo->prepare("INSERT INTO student_enrollments (user_id, offering_id, academic_year_id) VALUES (?, ?, ?)");
    foreach ($offering_ids as $off_id) {
        $stmt_enroll->execute([$user_id, $off_id, $current_year_id]);
    }
    
    $pdo->commit();
    
    // التوجيه للبروفايل مع إظهار رسالة النجاح
    header("Location: profil.php?status=re_enrolled");
    exit();

} catch (Exception $e) {
    // التراجع عن أي تعديلات في قاعدة البيانات إذا حدث خطأ
    $pdo->rollBack();
    error_log("Re-enroll Process Error: " . $e->getMessage());
    $error_msg = urlencode($e->getMessage());
    header("Location: profil.php?error={$error_msg}");
    exit();
}
?>