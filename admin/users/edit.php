<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../config/db_connexion.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

$user_id_to_edit = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$user_id_to_edit) {
    header("Location: index.php?error=معرف غير صحيح");
    exit();
}

$breadcrumbs = ["الطلاب والمستخدمون" => "", "إدارة الحسابات" => "index.php", "تعديل بيانات المستخدم" => ""];

try {
    // تم تصحيح اسم العمود إلى password_hash
    $stmt = $pdo->prepare("SELECT user_id, username, email, password_hash, role, gender, profile_picture FROM users WHERE user_id = ?");
    $stmt->execute([$user_id_to_edit]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        header("Location: index.php?error=لم يتم العثور على المستخدم");
        exit();
    }

    $stmt_enrollments = $pdo->prepare("
        SELECT se.enrollment_id, d.division_name, c.class_name, g.group_name, t.track_name
        FROM student_enrollments se
        JOIN course_offerings co ON se.offering_id = co.offering_id
        JOIN divisions d ON co.division_id = d.division_id
        JOIN classes c ON co.class_id = c.class_id
        JOIN `groups` g ON co.group_id = g.group_id
        LEFT JOIN tracks t ON co.track_id = t.track_id
        WHERE se.user_id = ?
    ");
    $stmt_enrollments->execute([$user_id_to_edit]);
    $enrollments = $stmt_enrollments->fetchAll(PDO::FETCH_ASSOC);
    
    $divisions = $pdo->query("SELECT * FROM divisions ORDER BY division_name")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("خطأ في جلب البيانات: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل مستخدم - <?php echo htmlspecialchars($user['username']); ?></title>
    <link rel="icon" type="image/svg+xml" href="/mo7adaraty/assets/images/favicon.svg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/admin_global.css">
    <link rel="stylesheet" href="../assets/css/admin_academic.css">
    <style>
        .password-wrapper { position: relative; }
        .password-wrapper input { padding-left: 45px; }
        .toggle-password { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-secondary); cursor: pointer; font-size: 18px; transition: 0.2s; }
        .toggle-password:hover { color: var(--brand-primary); }
        .track-group { display: none; }
    </style>
</head>
<body class="admin-body">

    <script>
        (function() {
            const savedTheme = localStorage.getItem('mo7adaraty_theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>

    <?php include '../templates/sidebar.php'; ?>

    <main class="admin-main">
        <?php include '../templates/header.php'; ?>

        <div class="admin-content-wrapper">
            
            <?php if (isset($_SESSION['user_update_msg'])): ?>
                <div style="padding: 15px; background: rgba(40, 167, 69, 0.1); color: var(--success-color); border: 1px solid var(--success-color); border-radius: var(--radius-md); margin-bottom: 20px; font-weight: bold;">
                    <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($_SESSION['user_update_msg']); unset($_SESSION['user_update_msg']); ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div style="padding: 15px; background: rgba(220, 53, 69, 0.1); color: var(--danger-color); border: 1px solid var(--danger-color); border-radius: var(--radius-md); margin-bottom: 20px; font-weight: bold;">
                    <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <div class="academic-form-container skeleton" style="max-width: 850px; margin-bottom: 30px;">
                <h2 style="margin-top: 0; color: var(--text-primary); border-bottom: 1px solid var(--border-color); padding-bottom: 15px; margin-bottom: 25px;">
                    <i class="fa-solid fa-user-pen text-[var(--warning-color)]"></i> تعديل بيانات المستخدم
                </h2>

                <form action="update_process.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                    
                    <div style="display: flex; gap: 25px; flex-wrap: wrap;">
                        <div style="flex: 1; min-width: 300px;">
                            <div class="form-group">
                                <label class="form-label">الاسم الكامل: <span style="color:var(--danger-color)">*</span></label>
                                <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">البريد الإلكتروني: <span style="color:var(--danger-color)">*</span></label>
                                <input type="email" name="email" class="form-control" dir="ltr" style="text-align: left;" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">إعادة تعيين كلمة المرور:</label>
                                <div class="password-wrapper">
                                    <input type="password" name="password" id="password" class="form-control" dir="ltr" style="text-align: left; font-family: monospace; font-size: 16px;" placeholder="اكتب كلمة مرور جديدة لتغييرها...">
                                    <button type="button" class="toggle-password" onclick="togglePasswordVisibility()" title="إظهار/إخفاء كلمة المرور">
                                        <i class="fa-solid fa-eye" id="eye-icon"></i>
                                    </button>
                                </div>
                                <small style="color: var(--brand-primary); font-weight: bold; margin-top: 5px; display: block;"><i class="fa-solid fa-circle-info"></i> اترك الحقل فارغاً إذا كنت لا تريد تغيير كلمة المرور القديمة.</small>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                <div class="form-group">
                                    <label class="form-label">الصلاحية:</label>
                                    <select name="role" class="form-control" required>
                                        <option value="student" <?php echo ($user['role'] === 'student') ? 'selected' : ''; ?>>طالب</option>
                                        <option value="admin" <?php echo ($user['role'] === 'admin') ? 'selected' : ''; ?>>مدير (Admin)</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">الجنس:</label>
                                    <select name="gender" class="form-control">
                                        <option value="unspecified" <?php echo ($user['gender'] === 'unspecified' || empty($user['gender'])) ? 'selected' : ''; ?>>غير محدد</option>
                                        <option value="male" <?php echo ($user['gender'] === 'male') ? 'selected' : ''; ?>>ذكر</option>
                                        <option value="female" <?php echo ($user['gender'] === 'female') ? 'selected' : ''; ?>>أنثى</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div style="flex: 0 0 250px; background: var(--bg-light); border: 1px dashed var(--border-color); border-radius: var(--radius-md); padding: 25px; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                            <label class="form-label" style="width: 100%; color: var(--text-primary);">الصورة الشخصية:</label>
                            <?php $current_avatar = !empty($user['profile_picture']) && $user['profile_picture'] !== 'default.png' ? '../../uploads/profile_pictures/' . htmlspecialchars($user['profile_picture']) : '../../uploads/profile_pictures/default_profile.png'; ?>
                            
                            <img id="avatar_preview" src="<?php echo $current_avatar; ?>" alt="Avatar" style="width: 130px; height: 130px; border-radius: 50%; object-fit: cover; border: 4px solid var(--bg-surface); box-shadow: var(--shadow-sm); margin-bottom: 20px;" onerror="this.src='../../uploads/profile_pictures/default_profile.png'">
                            
                            <input type="file" name="profile_picture" id="profile_picture" class="form-control" accept="image/jpeg,image/png,image/webp" style="font-size: 13px; padding: 6px;">
                        </div>
                    </div>

                    <div style="display: flex; gap: 10px; margin-top: 30px; border-top: 1px solid var(--border-color); padding-top: 20px;">
                        <button type="submit" class="btn-add-new" style="flex: 1; justify-content: center; background: var(--warning-color); color: #000 !important; font-size: 16px;">
                            <i class="fa-solid fa-save"></i> حفظ جميع التعديلات
                        </button>
                        <a href="index.php" class="btn-add-new" style="flex: 1; justify-content: center; background: var(--bg-surface); color: var(--text-primary) !important; border: 1px solid var(--border-color); font-size: 16px;">
                            إلغاء وعودة
                        </a>
                    </div>
                </form>
            </div>

            <?php if ($user['role'] === 'student'): ?>
                <div class="academic-form-container skeleton" style="max-width: 850px; border-top: 4px solid var(--brand-primary);">
                    <h3 style="margin-top: 0; color: var(--brand-primary); margin-bottom: 20px;"><i class="fa-solid fa-graduation-cap"></i> إدارة التسجيلات الدراسية (المسار الأكاديمي)</h3>
                    <h4 style="color: var(--text-primary); font-size: 15px; margin-bottom: 10px;">التسجيلات الحالية:</h4>
                    <?php if (empty($enrollments)): ?>
                        <div style="padding: 15px; background: var(--bg-light); border: 1px dashed var(--border-color); border-radius: var(--radius-sm); text-align: center; color: var(--danger-color); font-weight: bold; margin-bottom: 20px;">
                            هذا الطالب غير مسجل في أي مقرر أكاديمي حالياً.
                        </div>
                    <?php else: ?>
                        <div style="background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-md); overflow: hidden; margin-bottom: 25px;">
                            <table style="width: 100%; border-collapse: collapse; text-align: right;">
                                <thead>
                                    <tr style="background: var(--bg-light); border-bottom: 2px solid var(--border-color);">
                                        <th style="padding: 12px 15px; color: var(--text-secondary);">المسار الأكاديمي (شعبة > فصل > مسار > فوج)</th>
                                        <th style="padding: 12px 15px; width: 100px; text-align: center; color: var(--text-secondary);">إجراء</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($enrollments as $enroll): ?>
                                    <tr style="border-bottom: 1px solid var(--border-color);">
                                        <td style="padding: 15px; color: var(--text-primary); font-weight: bold;">
                                            <i class="fa-solid fa-check text-[var(--success-color)]"></i> 
                                            <?php echo htmlspecialchars($enroll['division_name'] . ' > ' . $enroll['class_name'] . ' / ' . $enroll['group_name'] . ($enroll['track_name'] ? ' / ' . $enroll['track_name'] : '')); ?>
                                        </td>
                                        <td style="padding: 15px; text-align: center;">
                                            <form action="delete_enrollment.php" method="POST" style="margin: 0;" onsubmit="return confirm('تأكيد حذف هذا المسار من حساب الطالب؟');">
                                                <input type="hidden" name="enrollment_id" value="<?php echo $enroll['enrollment_id']; ?>">
                                                <input type="hidden" name="user_id" value="<?php echo $user_id_to_edit; ?>">
                                                <button type="submit" class="btn-add-new" style="background: rgba(220,53,69,0.1); color: var(--danger-color); padding: 6px 12px; width: 100%; justify-content: center; border: none;">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <h4 style="color: var(--text-primary); font-size: 15px; margin-bottom: 15px; border-top: 1px dashed var(--border-color); padding-top: 20px;"><i class="fa-solid fa-link text-[var(--brand-primary)]"></i> إضافة تسجيل لمقرر جديد:</h4>
                    <form id="add-enrollment-form" action="add_enrollment.php" method="POST" style="background: var(--bg-light); padding: 20px; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                        <input type="hidden" name="user_id" value="<?php echo $user_id_to_edit; ?>">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: end;">
                            <div class="form-group" style="margin: 0;">
                                <label class="form-label">الشعبة:</label>
                                <select id="division" class="form-control" required>
                                    <option value="">-- اختر --</option>
                                    <?php foreach ($divisions as $div): ?>
                                        <option value="<?php echo $div['division_id']; ?>"><?php echo htmlspecialchars($div['division_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group" style="margin: 0;">
                                <label class="form-label">الفصل:</label>
                                <select id="class" class="form-control class-select" required disabled></select>
                            </div>
                            <div class="form-group track-group" style="margin: 0;">
                                <label class="form-label">المسار:</label>
                                <select id="track" class="form-control track-select" disabled></select>
                            </div>
                            <div class="form-group" style="margin: 0;">
                                <label class="form-label">الفوج:</label>
                                <select id="group" name="offering_id" class="form-control group-select" required disabled></select>
                            </div>
                            <button type="submit" class="btn-add-new" style="background: var(--brand-primary); color: white; height: 42px; justify-content: center;">
                                <i class="fa-solid fa-plus"></i> ربط
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

        </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/admin_global.js"></script>
    <script>
        function togglePasswordVisibility() {
            var passwordInput = document.getElementById("password");
            var eyeIcon = document.getElementById("eye-icon");
            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                eyeIcon.classList.remove("fa-eye");
                eyeIcon.classList.add("fa-eye-slash");
            } else {
                passwordInput.type = "password";
                eyeIcon.classList.remove("fa-eye-slash");
                eyeIcon.classList.add("fa-eye");
            }
        }

        document.getElementById('profile_picture').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    document.getElementById('avatar_preview').src = event.target.result;
                }
                reader.readAsDataURL(file);
            }
        });

        // AJAX لربط التسجيلات
        $(document).ready(function() {
            const ADVANCED_CLASSES_IDS = [5, 6];
            const $form = $('#add-enrollment-form');
            const $divisionSelect = $form.find('#division');
            const $classSelect = $form.find('.class-select');
            const $trackGroup = $form.find('.track-group');
            const $trackSelect = $form.find('.track-select');
            const $groupSelect = $form.find('.group-select');

            $divisionSelect.on('change', function() {
                const divisionID = $(this).val();
                $classSelect.html('<option>...</option>').prop('disabled', true);
                $trackGroup.hide();
                $trackSelect.html('').prop('disabled', true);
                $groupSelect.html('').prop('disabled', true);

                if (divisionID) {
                    $.post('../../ajax/get_data_for_settings.php', { action: 'get_classes', division_id: divisionID }, function(classes) {
                        $classSelect.html('<option value="">-- اختر الفصل --</option>');
                        classes.forEach(cls => { $classSelect.append(`<option value="${cls.class_id}">${cls.class_name}</option>`); });
                        $classSelect.prop('disabled', false);
                    }, 'json');
                }
            });

            $classSelect.on('change', function() {
                const classID = parseInt($(this).val());
                const divisionID = $divisionSelect.val();
                $trackGroup.hide();
                $trackSelect.html('').prop('required', false).prop('disabled', true);
                $groupSelect.html('<option>...</option>').prop('disabled', true);

                if (ADVANCED_CLASSES_IDS.includes(classID)) {
                    $trackGroup.show();
                    $trackSelect.prop('required', true).prop('disabled', false);
                    $.post('../../ajax/get_data_for_settings.php', { action: 'get_tracks', division_id: divisionID }, function(tracks) {
                        $trackSelect.html('<option value="">-- اختر المسار --</option>');
                        tracks.forEach(track => { $trackSelect.append(`<option value="${track.track_id}">${track.track_name}</option>`); });
                    }, 'json');
                } else {
                    $.post('../../ajax/get_data_for_settings.php', { action: 'get_groups', class_id: classID }, function(groups) {
                        $groupSelect.html('<option value="">-- اختر الفوج --</option>');
                        groups.forEach(group => { $groupSelect.append(`<option value="${group.offering_id}">${group.group_name}</option>`); });
                        $groupSelect.prop('disabled', false);
                    }, 'json');
                }
            });

            $trackSelect.on('change', function() {
                const classID = $classSelect.val();
                const trackID = $(this).val();
                $groupSelect.html('<option>...</option>').prop('disabled', true);
                if (trackID) {
                     $.post('../../ajax/get_data_for_settings.php', { action: 'get_groups', class_id: classID, track_id: trackID }, function(groups) {
                        $groupSelect.html('<option value="">-- اختر الفوج --</option>');
                        groups.forEach(group => { $groupSelect.append(`<option value="${group.offering_id}">${group.group_name}</option>`); });
                        $groupSelect.prop('disabled', false);
                    }, 'json');
                }
            });
        });
    </script>
</body>
</html>