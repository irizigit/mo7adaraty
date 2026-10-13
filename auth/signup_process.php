<?php
// auth/signup_process.php
session_start();
require_once '../config/db_connexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("لا يمكن الوصول إلى هذه الصفحة مباشرة.");
}

$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

$gender = $_POST['gender'] ?? 'unspecified';
if (!in_array($gender, ['male', 'female', 'unspecified'])) {
    $gender = 'unspecified';
}

$raw_offering_ids = [];
if (isset($_POST['offering_id']) && is_array($_POST['offering_id'])) {
    $raw_offering_ids = array_filter(array_map('intval', $_POST['offering_id']));
}

$offering_ids = array_unique($raw_offering_ids);
$subjects = $_POST['subjects'] ?? [];

if (count($raw_offering_ids) > count($offering_ids)) {
    header('Location: ../signup.php?error=' . urlencode('خطأ: لا يمكنك التسجيل في نفس المقرر (الفوج) أكثر من مرة.'));
    exit();
}

if (empty($username) || empty($email) || empty($password) || empty($offering_ids) || empty($gender)) {
    header('Location: ../signup.php?error=' . urlencode('يرجى ملء جميع الحقول واختيار مقرر واحد على الأقل.'));
    exit();
}

if (strlen($password) < 8) {
    header('Location: ../signup.php?error=' . urlencode('كلمة المرور يجب أن تتكون من 8 أحرف على الأقل.'));
    exit();
}

if (count($offering_ids) > 2) {
    header('Location: ../signup.php?error=' . urlencode('لا يمكنك التسجيل في أكثر من مقررين.'));
    exit();
}

$stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
$stmt->execute([$username, $email]);
if ($stmt->fetch()) {
    header('Location: ../signup.php?error=' . urlencode('اسم المستخدم أو البريد الإلكتروني مسجل بالفعل.'));
    exit();
}

$profile_picture_name = 'default_unspecified.png';
if ($gender === 'male') {
    $profile_picture_name = 'default_male.png';
} elseif ($gender === 'female') {
    $profile_picture_name = 'default_female.png';
}

if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
    $tmp_name = $_FILES['profile_picture']['tmp_name'];
    
    $allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $file_mime = mime_content_type($tmp_name);
    
    if (!in_array($file_mime, $allowed_mime_types)) {
         header('Location: ../signup.php?error=' . urlencode('نوع الملف غير مدعوم، يرجى رفع صورة صالحة (PNG, JPG, WEBP).'));
         exit();
    }

    $extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
    $profile_picture_name = uniqid('user_', true) . '.' . $extension;
    $upload_dir = __DIR__ . '/../uploads/profile_pictures/';
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    move_uploaded_file($tmp_name, $upload_dir . $profile_picture_name);
}

$password_hash = password_hash($password, PASSWORD_DEFAULT);

try {
    $pdo->beginTransaction();

    $stmt_year = $pdo->query("SELECT id FROM academic_years WHERE is_current = 1 LIMIT 1");
    $current_year_id = $stmt_year->fetchColumn();

    if (!$current_year_id) {
        throw new Exception("لا يمكن التسجيل حالياً، لا توجد سنة جامعية مفعلة في النظام.");
    }

    $placeholders = implode(',', array_fill(0, count($offering_ids), '?'));
    $check_sql = "SELECT COUNT(*) FROM course_offerings WHERE academic_year_id = ? AND status = 1 AND offering_id IN ($placeholders)";
    
    $check_stmt = $pdo->prepare($check_sql);
    $check_params = array_merge([$current_year_id], $offering_ids);
    $check_stmt->execute($check_params);
    
    $valid_count = $check_stmt->fetchColumn();

    if ($valid_count != count($offering_ids)) {
        throw new Exception("خطأ أمني: تم اكتشاف محاولة تسجيل في مقررات غير متاحة أو تم إيقافها مؤخراً.");
    }

    $stmt_user = $pdo->prepare(
        "INSERT INTO users (username, email, password_hash, profile_picture, role, gender) VALUES (?, ?, ?, ?, 'student', ?)"
    );
    $stmt_user->execute([$username, $email, $password_hash, $profile_picture_name, $gender]);
    $user_id = $pdo->lastInsertId();

    $stmt_enroll = $pdo->prepare("INSERT INTO student_enrollments (user_id, offering_id, academic_year_id, selected_subjects) VALUES (?, ?, ?, ?)");
    foreach ($offering_ids as $off_id) {
        $selected_subs = isset($subjects[$off_id]) ? json_encode(array_map('intval', $subjects[$off_id])) : null;
        $stmt_enroll->execute([$user_id, $off_id, $current_year_id, $selected_subs]);
    }

    $pdo->commit();

    header('Location: ../login.php?status=signup_success');
    exit();

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Signup Process Error: " . $e->getMessage()); 
    $error_msg = urlencode($e->getMessage());
    header("Location: ../signup.php?error={$error_msg}");
    exit();
}
?>