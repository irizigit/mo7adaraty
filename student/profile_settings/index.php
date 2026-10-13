<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية الصفحة
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

try {
    // === جلب الأيقونات الموحدة من الإدارة ===
    $stmt_design = $pdo->prepare("SELECT asset_key, file_path FROM design_assets WHERE page_section IN ('header', 'student_profile', 'class_lectures')");
    $stmt_design->execute();
    $design_settings = [];
    while ($row = $stmt_design->fetch(PDO::FETCH_ASSOC)) {
        $design_settings[$row['asset_key']] = $row['file_path'];
    }

    function getDesignIcon($key, $default_path) {
        global $design_settings;
        return !empty($design_settings[$key]) ? '../../' . $design_settings[$key] : '../../' . $default_path;
    }

    // جلب بيانات المستخدم (مع حقل الجنس الجديد)
    $stmt_user = $pdo->prepare("SELECT username, email, gender FROM users WHERE user_id = ?");
    $stmt_user->execute([$user_id]);
    $user = $stmt_user->fetch(PDO::FETCH_ASSOC);

    // جلب الشعب لنموذج طلب التغيير
    $divisions = $pdo->query("SELECT * FROM divisions ORDER BY division_name")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("خطأ في جلب البيانات: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>إعدادات الملف الشخصي - Mo7adaraty</title>
    <link rel="icon" type="image/svg+xml" href="../../assets/images/favicon.svg">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="../../assets/css/global.css">
    <link rel="stylesheet" href="../../assets/css/pages/profile_settings.css">
</head>
<body>

    <script>
        (function() {
            const savedTheme = localStorage.getItem('mo7adaraty_theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>

    <?php require_once '../../templates/header.php'; ?>

    <main class="settings-wrapper">
        <div class="settings-container">
            
            <div class="settings-header">
                <h1 class="settings-title">
                    <img src="<?php echo getDesignIcon('profile_icon_settings', 'includesimg/student/icon_settings.png'); ?>" alt="settings" class="settings-title-icon">
                    إعدادات الملف الشخصي
                </h1>
            </div>

            <?php
            if (isset($_SESSION['settings_msg'])) {
                $msg_type = $_SESSION['settings_msg']['type'];
                $message = $_SESSION['settings_msg']['message'];
                $icon = $msg_type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation';
                echo "<div class='status-message $msg_type'><i class='fa-solid $icon'></i> $message</div>";
                unset($_SESSION['settings_msg']);
            }
            ?>

            <div class="settings-card">
                <h3 class="card-title">
                    <i class="fa-solid fa-user-edit text-[var(--brand-primary)]"></i> تعديل المعلومات الأساسية
                </h3>
                
                <form action="update_profile_process.php" method="POST" enctype="multipart/form-data">
                    <div class="grid-2">
                        <div class="form-group">
                            <label class="form-label" for="username">اسم المستخدم:</label>
                            <input type="text" id="username" name="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="email">البريد الإلكتروني:</label>
                            <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label class="form-label" for="gender">الجنس:</label>
                            <select id="gender" name="gender" class="form-control" required>
                                <option value="male" <?php echo ($user['gender'] == 'male') ? 'selected' : ''; ?>>طالب</option>
                                <option value="female" <?php echo ($user['gender'] == 'female') ? 'selected' : ''; ?>>طالبة</option>
                                <option value="unspecified" <?php echo ($user['gender'] == 'unspecified' || empty($user['gender'])) ? 'selected' : ''; ?>>غير محدد</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">الصورة الشخصية (اختياري):</label>
                            <input type="file" name="profile_picture" class="form-control" accept="image/*" style="padding: 10px;">
                        </div>
                    </div>

                    <hr class="divider">

                    <h4 style="margin-top: 0; margin-bottom: 20px; color: var(--text-primary);">تغيير كلمة المرور</h4>
                    <div class="grid-2">
                        <div class="form-group">
                            <label class="form-label">كلمة المرور الحالية (مطلوبة للتغيير):</label>
                            <input type="password" name="current_password" class="form-control" placeholder="أدخل كلمة المرور الحالية...">
                        </div>
                        <div class="form-group">
                            <label class="form-label">كلمة المرور الجديدة:</label>
                            <input type="password" name="new_password" class="form-control" placeholder="أدخل كلمة المرور الجديدة...">
                        </div>
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label class="form-label">تأكيد كلمة المرور الجديدة:</label>
                            <input type="password" name="confirm_password" class="form-control" placeholder="أعد إدخال كلمة المرور الجديدة...">
                        </div>
                    </div>

                    <button type="submit" name="update_info" class="btn-submit">
                        <i class="fa-solid fa-save"></i> حفظ التعديلات
                    </button>
                </form>
            </div>

            <div class="settings-card">
                <h3 class="card-title">
                    <img src="<?php echo getDesignIcon('cl_icon_school', 'includesimg/class_lectures/icon_school.svg'); ?>" alt="school" class="card-title-icon"> 
                    طلب تغيير المقرر الدراسي
                </h3>
                
                <form id="enrollmentChangeForm" action="request_enrollment_change_process.php" method="POST">
                    <div class="form-group">
                        <label class="form-label" for="division">اختر الشعبة الجديدة:</label>
                        <select id="division" name="division_id" class="form-control" required>
                            <option value="" selected disabled>-- اختر الشعبة لعرض الفصول --</option>
                            <?php foreach ($divisions as $division): ?>
                                <option value="<?php echo $division['division_id']; ?>"><?php echo htmlspecialchars($division['division_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div id="enrollment-container">
                        </div>
                    
                    <button type="button" id="add-enrollment-btn" class="btn-add" style="display:none;">
                        <i class="fa-solid fa-plus"></i> إضافة مقرر آخر
                    </button>
                    
                    <hr class="divider">
                    
                    <div class="form-group">
                        <label class="form-label" for="reason">سبب الطلب (اختياري):</label>
                        <textarea id="reason" name="reason" class="form-control" placeholder="يرجى توضيح سبب طلب التغيير لمساعدة الإدارة في مراجعة طلبك..."></textarea>
                    </div>
                    
                    <button type="submit" name="request_change" class="btn-submit">
                        <img src="<?php echo getDesignIcon('cl_icon_send', 'includesimg/class_lectures/icon_send.png'); ?>" alt="send" class="btn-icon"> إرسال الطلب
                    </button>
                </form>
            </div>

            <a href="../profil.php" class="back-link">
                <i class="fa-solid fa-arrow-right"></i> العودة إلى ملفي الشخصي
            </a>

        </div>
    </main>

    <?php require_once '../../templates/footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../../assets/js/profile_settings.js"></script>

</body>
</html>