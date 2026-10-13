<?php
session_start();
require_once '../config/db_connexion.php';

// حماية الصفحة
if (!isset($_SESSION['user_id']) || $_SESSION['role'] === 'admin') {
    header("Location: profil.php");
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // 1. جلب الموسم الحالي
    $stmt_year = $pdo->query("SELECT id, year_label FROM academic_years WHERE is_current = 1 LIMIT 1");
    $current_year = $stmt_year->fetch(PDO::FETCH_ASSOC);
    $current_year_id = $current_year ? $current_year['id'] : 1;
    $current_year_label = $current_year ? $current_year['year_label'] : '';

    // 2. التحقق من أن الطالب غير مسجل في هذا الموسم (لمنع الدخول اليدوي للصفحة)
    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM student_enrollments WHERE user_id = ? AND academic_year_id = ?");
    $stmt_check->execute([$user_id, $current_year_id]);
    if ($stmt_check->fetchColumn() > 0) {
        header("Location: profil.php");
        exit();
    }

    // 3. جلب معلومات الطالب (الاسم والبريد)
    $stmt_user = $pdo->prepare("SELECT username, email FROM users WHERE user_id = ?");
    $stmt_user->execute([$user_id]);
    $user_info = $stmt_user->fetch(PDO::FETCH_ASSOC);

    // 4. جلب آخر شعبة كان مسجل فيها الطالب بذكاء (للاحتفاظ بها)
    $stmt_last_enroll = $pdo->prepare("
        SELECT d.division_id, d.division_name
        FROM student_enrollments se
        JOIN course_offerings co ON se.offering_id = co.offering_id
        JOIN divisions d ON co.division_id = d.division_id
        WHERE se.user_id = ?
        ORDER BY se.enrollment_id DESC LIMIT 1
    ");
    $stmt_last_enroll->execute([$user_id]);
    $last_enroll = $stmt_last_enroll->fetch(PDO::FETCH_ASSOC);

    $divisions = [];
    $classes = [];
    
    if ($last_enroll) {
        // جلب الفصول المتاحة لهاد الشعبة فقط باش ميتسناش AJAX
        $stmt_classes = $pdo->prepare("
            SELECT DISTINCT c.class_id, c.class_name 
            FROM course_offerings co 
            JOIN classes c ON co.class_id = c.class_id 
            WHERE co.division_id = ?
        ");
        $stmt_classes->execute([$last_enroll['division_id']]);
        $classes = $stmt_classes->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // حالة نادرة: إذا ماكان عندو حتى تسجيل قديم، نجبدو ليه الـ Divisions كاملين
        $divisions = $pdo->query("SELECT * FROM divisions ORDER BY division_name")->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    die("خطأ: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="/mo7adaraty/assets/images/favicon.svg">
    <title>تجديد التسجيل - محاضراتي</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body { background: #f4f8fc; font-family: 'Tajawal', sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px; box-sizing: border-box;}
        .re-enroll-card { background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); max-width: 550px; width: 100%; border-top: 5px solid #007bff; }
        .text-center { text-align: center; }
        .re-enroll-card i.main-icon { font-size: 60px; color: #007bff; margin-bottom: 15px; display: block;}
        .re-enroll-card h2 { color: #333; margin-bottom: 10px; font-size: 24px; }
        .re-enroll-card p.intro { color: #666; margin-bottom: 20px; line-height: 1.6; font-size: 15px; }
        
        .user-info-box { background: #f8f9fa; border: 1px solid #e9ecef; padding: 15px; border-radius: 10px; margin-bottom: 20px; text-align: right;}
        .user-info-box p { margin: 5px 0; color: #495057; font-size: 15px;}
        .user-info-box p i { color: #007bff; width: 20px; text-align: center;}
        
        .division-info-box { background: #e3f2fd; border: 1px solid #b8daff; color: #0056b3; padding: 15px; border-radius: 10px; margin-bottom: 25px; text-align: center; font-weight: bold; font-size: 16px;}
        .division-info-box i { margin-left: 8px; font-size: 20px;}

        .form-group { text-align: right; margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: bold; color: #444; }
        .form-group select { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-family: 'Tajawal', sans-serif; box-sizing: border-box; background-color: #fff;}
        .form-group select:focus { border-color: #007bff; outline: none; box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25); }
        
        .btn-submit { background: #28a745; color: white; border: none; padding: 14px 20px; width: 100%; border-radius: 8px; font-size: 16px; font-weight: bold; cursor: pointer; transition: 0.3s; margin-top: 10px; }
        .btn-submit:hover { background: #218838; transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.1);}
        .track-group { display: none; }
        .logout-link { display: block; text-align: center; margin-top: 20px; color: #dc3545; text-decoration: none; font-weight: bold; transition: 0.3s; }
        .logout-link:hover { color: #a71d2a; }
    </style>
</head>
<body>
    <div class="re-enroll-card">
        <div class="text-center">
            <i class="fa-solid fa-door-open main-icon"></i>
            <h2>مرحباً بك يا <?php echo htmlspecialchars($user_info['username']); ?>!</h2>
            <p class="intro">لقد بدأ الموسم الجامعي <strong><?php echo htmlspecialchars($current_year_label); ?></strong>، لتجديد تسجيلك المرجو اختيار الفصل الجديد.</p>
        </div>

        <div class="user-info-box">
            <p><i class="fa-solid fa-user"></i> الطالب: <strong><?php echo htmlspecialchars($user_info['username']); ?></strong></p>
            <p><i class="fa-solid fa-envelope"></i> البريد: <strong><?php echo htmlspecialchars($user_info['email']); ?></strong></p>
        </div>

        <form action="re_enroll_process.php" method="POST">
            <input type="hidden" name="academic_year_id" value="<?php echo $current_year_id; ?>">
            
            <?php if ($last_enroll): ?>
                <div class="division-info-box">
                    <i class="fa-solid fa-graduation-cap"></i> شعبتك الحالية: <?php echo htmlspecialchars($last_enroll['division_name']); ?>
                </div>
                <input type="hidden" id="division" name="division_id" value="<?php echo $last_enroll['division_id']; ?>">
                
                <div class="form-group">
                    <label>اختر الفصل الجديد:</label>
                    <select id="class" name="class_id" required>
                        <option value="">-- اختر الفصل --</option>
                        <?php foreach($classes as $cls): ?>
                            <option value="<?php echo $cls['class_id']; ?>"><?php echo htmlspecialchars($cls['class_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php else: ?>
                <div class="form-group">
                    <label>اختر الشعبة:</label>
                    <select id="division" name="division_id" required>
                        <option value="">-- اختر الشعبة --</option>
                        <?php foreach($divisions as $div): ?>
                            <option value="<?php echo $div['division_id']; ?>"><?php echo htmlspecialchars($div['division_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>اختر الفصل:</label>
                    <select id="class" name="class_id" required disabled>
                        <option value="">-- اختر الشعبة أولاً --</option>
                    </select>
                </div>
            <?php endif; ?>

            <div class="form-group track-group" id="track-group">
                <label>اختر المسار:</label>
                <select id="track" name="track_id">
                    <option value="">-- اختر المسار --</option>
                </select>
            </div>

            <div class="form-group">
                <label>اختر الفوج (أو المسار):</label>
                <select id="group" name="offering_id" required disabled>
                    <option value="">-- اختر الفصل/المسار أولاً --</option>
                </select>
            </div>

            <button type="submit" class="btn-submit"><i class="fa-solid fa-paper-plane"></i> تأكيد وإرسال الطلب</button>
        </form>
        
        <a href="../logout.php" class="logout-link"><i class="fa-solid fa-right-from-bracket"></i> تسجيل الخروج مؤقتاً</a>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            const ADVANCED_CLASSES_IDS = [5, 6];
            
            // في حالة لم تكن لديه شعبة (استرجاع الفصول عبر أجاكس)
            $('#division').on('change', function() {
                if($(this).is('select')) { // تأكد أنه حقل اختيار وليس input مخفي
                    const divisionID = $(this).val();
                    $('#class').html('<option value="">-- اختر --</option>').prop('disabled', !divisionID);
                    $('#track-group').hide();
                    $('#group').html('<option value="">-- اختر --</option>').prop('disabled', true);

                    if (divisionID) {
                        $.post('../ajax/get_data_for_signup.php', { action: 'get_classes', division_id: divisionID }, function(data) {
                            try {
                                const classes = typeof data === 'string' ? JSON.parse(data) : data;
                                classes.forEach(cls => {
                                    $('#class').append(`<option value="${cls.class_id}">${cls.class_name}</option>`);
                                });
                            } catch(e) {}
                        });
                    }
                }
            });

            // جلب الأفواج/المسارات بناء على الفصل
            $('#class').on('change', function() {
                const classID = parseInt($(this).val());
                const divisionID = $('#division').val();
                $('#track-group').hide();
                $('#track').html('<option value="">-- اختر --</option>');
                $('#group').html('<option value="">-- اختر --</option>').prop('disabled', !classID);

                if (classID) {
                    if (ADVANCED_CLASSES_IDS.includes(classID)) {
                        $('#track-group').show();
                        $.post('../ajax/get_data_for_signup.php', { action: 'get_tracks', division_id: divisionID }, function(data) {
                            try {
                                const tracks = typeof data === 'string' ? JSON.parse(data) : data;
                                tracks.forEach(track => {
                                    $('#track').append(`<option value="${track.track_id}">${track.track_name}</option>`);
                                });
                            } catch(e) {}
                        });
                    } else {
                        $.post('../ajax/get_data_for_signup.php', { action: 'get_groups', class_id: classID }, function(data) {
                            try {
                                const groups = typeof data === 'string' ? JSON.parse(data) : data;
                                groups.forEach(group => {
                                    $('#group').append(`<option value="${group.offering_id}">${group.group_name}</option>`);
                                });
                                $('#group').prop('disabled', false);
                            } catch(e) {}
                        });
                    }
                }
            });

            // جلب الأفواج بناء على المسار
            $('#track').on('change', function() {
                const classID = $('#class').val();
                const trackID = $(this).val();
                $('#group').html('<option value="">-- اختر --</option>').prop('disabled', !trackID);

                if (trackID) {
                    $.post('../ajax/get_data_for_signup.php', { action: 'get_groups', class_id: classID, track_id: trackID }, function(data) {
                        try {
                            const groups = typeof data === 'string' ? JSON.parse(data) : data;
                            groups.forEach(group => {
                                $('#group').append(`<option value="${group.offering_id}">${group.group_name}</option>`);
                            });
                            $('#group').prop('disabled', false);
                        } catch(e) {}
                    });
                }
            });
        });
    </script>
</body>
</html>