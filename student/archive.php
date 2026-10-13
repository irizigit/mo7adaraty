<?php
session_start();
require_once '../config/db_connexion.php';

// 1. حماية الصفحة
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

$selected_year_id = filter_input(INPUT_GET, 'year_id', FILTER_VALIDATE_INT);
$selected_offering_id = filter_input(INPUT_GET, 'offering_id', FILTER_VALIDATE_INT);
$selected_class_id = null;

try {
    // === جلب الأيقونات الموحدة من الإدارة لتطبيق مبدأ DRY ===
    $stmt_design = $pdo->prepare("SELECT asset_key, file_path FROM design_assets WHERE page_section IN ('header', 'class_lectures', 'student_profile')");
    $stmt_design->execute();
    $design_settings = [];
    while ($row = $stmt_design->fetch(PDO::FETCH_ASSOC)) {
        $design_settings[$row['asset_key']] = $row['file_path'];
    }

    // دالة مساعدة للحصول على مسار الأيقونة
    function getDesignIcon($key, $default_path) {
        global $design_settings;
        return !empty($design_settings[$key]) ? '../' . $design_settings[$key] : '../' . $default_path;
    }

    // 2. القفل الأمني 1: جلب المواسم المؤرشفة اللي الطالب كان مسجل فيها فقط!
    $stmt_years = $pdo->prepare("
        SELECT DISTINCT ay.id, ay.year_label 
        FROM academic_years ay
        JOIN student_enrollments se ON ay.id = se.academic_year_id
        WHERE se.user_id = :user_id AND ay.is_current = 0
        ORDER BY ay.id DESC
    ");
    $stmt_years->execute(['user_id' => $user_id]);
    $archived_years = $stmt_years->fetchAll(PDO::FETCH_ASSOC);

    $enrolled_classes = [];
    $lectures = [];
    $exams = [];

    // 3. القفل الأمني 2: إذا اختار موسم، نجلب الفصول اللي كان مسجل فيها فداك الموسم بالضبط
    if ($selected_year_id) {
        $stmt_classes = $pdo->prepare("
            SELECT co.offering_id, c.class_id, c.class_name, g.group_name 
            FROM student_enrollments se
            JOIN course_offerings co ON se.offering_id = co.offering_id
            JOIN classes c ON co.class_id = c.class_id
            LEFT JOIN `groups` g ON co.group_id = g.group_id
            WHERE se.user_id = :user_id AND se.academic_year_id = :year_id
        ");
        $stmt_classes->execute(['user_id' => $user_id, 'year_id' => $selected_year_id]);
        $enrolled_classes = $stmt_classes->fetchAll(PDO::FETCH_ASSOC);

        // 4. جلب المحتوى إذا اختار الفصل
        if ($selected_offering_id) {
            $is_valid = false;
            foreach ($enrolled_classes as $ec) {
                if ($ec['offering_id'] == $selected_offering_id) {
                    $is_valid = true;
                    $selected_class_id = $ec['class_id'];
                    break;
                }
            }

            if ($is_valid) {
                // جلب المحاضرات (أرشيف)
                $stmt_lec = $pdo->prepare("
                    SELECT l.*, s.subject_name, p.professor_name, u.username as uploader_name
                    FROM lectures l
                    LEFT JOIN offering_subjects os ON l.offering_subject_id = os.offering_subject_id
                    LEFT JOIN subjects s ON os.subject_id = s.subject_id
                    LEFT JOIN professors p ON os.professor_id = p.professor_id
                    LEFT JOIN users u ON l.uploader_user_id = u.user_id
                    WHERE l.offering_id = :offering_id AND l.academic_year_id = :year_id AND l.status = 'approved'
                    ORDER BY l.upload_date DESC
                ");
                $stmt_lec->execute(['offering_id' => $selected_offering_id, 'year_id' => $selected_year_id]);
                $lectures = $stmt_lec->fetchAll(PDO::FETCH_ASSOC);

                // جلب الامتحانات (أرشيف)
                $stmt_exam = $pdo->prepare("
                    SELECT e.*, s.subject_name, p.professor_name, u.username as uploader_name
                    FROM exams e
                    LEFT JOIN offering_subjects os ON e.offering_subject_id = os.offering_subject_id
                    LEFT JOIN subjects s ON os.subject_id = s.subject_id
                    LEFT JOIN professors p ON os.professor_id = p.professor_id
                    LEFT JOIN users u ON e.uploader_user_id = u.user_id
                    WHERE e.class_id = :class_id AND e.academic_year_id = :year_id
                    ORDER BY e.upload_date DESC
                ");
                $stmt_exam->execute(['class_id' => $selected_class_id, 'year_id' => $selected_year_id]);
                $exams = $stmt_exam->fetchAll(PDO::FETCH_ASSOC);
            }
        }
    }

} catch (PDOException $e) {
    die("خطأ في جلب الأرشيف: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الأرشيف - Mo7adaraty</title>
    <link rel="icon" type="image/svg+xml" href="../assets/images/favicon.svg">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/pages/archive.css">
</head>
<body>

    <script>
        (function() {
            const savedTheme = localStorage.getItem('mo7adaraty_theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>

    <?php require_once '../templates/header.php'; ?>

    <main class="archive-wrapper">
        <div class="archive-container">
            
            <div class="archive-header" data-aos="fade-down">
                <h1 class="archive-title">
                    <img src="<?php echo getDesignIcon('profile_icon_archive', 'includesimg/student/profile_icon_archive.png'); ?>" alt="archive" class="archive-icon"> 
                    أرشيف المواسم السابقة
                </h1>
                <a href="profil.php" class="back-link">
                    العودة للملف الشخصي <i class="fa-solid fa-arrow-left"></i>
                </a>
            </div>

            <div class="filter-card" data-aos="fade-up" data-aos-delay="100">
                <form action="" method="GET" class="filter-form" id="archiveForm">
                    <div class="form-group">
                        <label class="form-label" for="year_id">اختر الموسم الجامعي:</label>
                        <select name="year_id" id="year_id" class="form-control" required>
                            <option value="">-- اختر الموسم --</option>
                            <?php foreach($archived_years as $year): ?>
                                <option value="<?php echo $year['id']; ?>" <?php echo ($selected_year_id == $year['id']) ? 'selected' : ''; ?>>
                                    موسم <?php echo htmlspecialchars($year['year_label']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <?php if($selected_year_id): ?>
                    <div class="form-group" data-aos="fade-up">
                        <label class="form-label" for="offering_id">اختر الفصل الدراسي:</label>
                        <select name="offering_id" id="offering_id" class="form-control" required>
                            <option value="">-- اختر الفصل --</option>
                            <?php foreach($enrolled_classes as $class): ?>
                                <option value="<?php echo $class['offering_id']; ?>" <?php echo ($selected_offering_id == $class['offering_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($class['class_name'] . ' - ' . ($class['group_name'] ?? 'عام')); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="flex: 0;" data-aos="fade-up">
                        <button type="submit" class="btn-filter">
                            <i class="fa-solid fa-search"></i> عرض الأرشيف
                        </button>
                    </div>
                    <?php endif; ?>
                </form>
            </div>

            <?php if($selected_offering_id): ?>
                
                <div class="content-section" data-aos="fade-up">
                    <h2 class="section-title">
                        <img src="<?php echo getDesignIcon('cl_icon_nav_lectures', 'includesimg/class_lectures/icon_nav_lectures.svg'); ?>" alt="lectures" class="section-icon"> 
                        المحاضرات المؤرشفة
                    </h2>
                    
                    <?php if(empty($lectures)): ?>
                        <div class="empty-state">
                            <img src="<?php echo getDesignIcon('cl_icon_empty', 'includesimg/class_lectures/cl_icon_empty.png'); ?>" alt="empty" class="empty-state-icon">
                            <p>لا توجد محاضرات في هذا الفصل لهذا الموسم.</p>
                        </div>
                    <?php else: ?>
                        <div class="item-list">
                            <?php foreach($lectures as $lec): ?>
                                <div class="item-card">
                                    <div class="item-info">
                                        <h3><?php echo htmlspecialchars($lec['title']); ?></h3>
                                        <p>المادة: <strong><?php echo htmlspecialchars($lec['subject_name'] ?? 'غير محدد'); ?></strong> <span style="margin:0 5px; color:var(--border-color);">|</span> الأستاذ: <strong><?php echo htmlspecialchars($lec['professor_name'] ?? 'غير محدد'); ?></strong></p>
                                    </div>
                                    <a href="../download.php?type=lecture&id=<?php echo $lec['lecture_id']; ?>" class="btn-download">
                                        <img src="<?php echo getDesignIcon('cl_icon_download', 'includesimg/class_lectures/icon_download.png'); ?>" alt="download" class="btn-download-icon"> تحميل
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="content-section" data-aos="fade-up" data-aos-delay="100">
                    <h2 class="section-title">
                        <img src="<?php echo getDesignIcon('cl_icon_nav_exams', 'includesimg/class_lectures/icon_nav_exams.svg'); ?>" alt="exams" class="section-icon"> 
                        الامتحانات المؤرشفة
                    </h2>
                    
                    <?php if(empty($exams)): ?>
                        <div class="empty-state">
                            <img src="<?php echo getDesignIcon('cl_icon_empty', 'includesimg/class_lectures/cl_icon_empty.png'); ?>" alt="empty" class="empty-state-icon">
                            <p>لا توجد امتحانات في هذا الفصل لهذا الموسم.</p>
                        </div>
                    <?php else: ?>
                        <div class="item-list">
                            <?php foreach($exams as $exam): ?>
                                <div class="item-card">
                                    <div class="item-info">
                                        <h3><?php echo htmlspecialchars($exam['title']); ?></h3>
                                        <p>المادة: <strong><?php echo htmlspecialchars($exam['subject_name'] ?? 'غير محدد'); ?></strong> <span style="margin:0 5px; color:var(--border-color);">|</span> الأستاذ: <strong><?php echo htmlspecialchars($exam['professor_name'] ?? 'غير محدد'); ?></strong></p>
                                    </div>
                                    <a href="../download.php?type=exam&id=<?php echo $exam['exam_id']; ?>" class="btn-download">
                                        <img src="<?php echo getDesignIcon('cl_icon_download', 'includesimg/class_lectures/icon_download.png'); ?>" alt="download" class="btn-download-icon"> تحميل
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

            <?php elseif(empty($archived_years)): ?>
                <div class="empty-state large" data-aos="fade-up">
                    <img src="<?php echo getDesignIcon('cl_icon_empty', 'includesimg/class_lectures/cl_icon_empty.png'); ?>" alt="empty" class="empty-state-icon" style="width: 100px; height: 100px;">
                    <h3>أرشيفك فارغ</h3>
                    <p>لا توجد لديك أي مواسم جامعية سابقة مؤرشفة حالياً.</p>
                </div>
            <?php endif; ?>

        </div>
    </main>

    <?php require_once '../templates/footer.php'; ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script src="../assets/js/archive.js"></script>

</body>
</html>