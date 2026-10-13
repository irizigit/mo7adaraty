<?php
session_start();
$base_url = '/mo7adaraty/'; 
require_once '../config/db_connexion.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

$curr_yr_id = 1;
$current_year_label = '';
$needs_reenrollment = false;
$last_enroll = null;
$divisions = [];
$structured_data = [];
$published_lectures_by_class = [];

// دوال مساعدة لروابط Google Drive
if (!function_exists('getDriveEmbedUrl')) {
    function getDriveEmbedUrl($url) {
        if (empty($url)) return '';
        if (preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $url, $matches) || preg_match('/[?&]id=([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return "https://drive.google.com/file/d/" . $matches[1] . "/preview";
        }
        return $url;
    }
}
if (!function_exists('getDriveDownloadUrl')) {
    function getDriveDownloadUrl($url) {
        if (empty($url)) return '';
        if (preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $url, $matches) || preg_match('/[?&]id=([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return "https://drive.google.com/uc?export=download&id=" . $matches[1];
        }
        return $url;
    }
}

// دالة جلب رابط صورة الغلاف سواء من Google Drive أو مسار محلي أو استخراج مصغر تلقائي
if (!function_exists('getLectureCoverSrc')) {
    function getLectureCoverSrc($lecture, $base_url = '/mo7adaraty/') {
        if (!empty($lecture['cover_image'])) {
            $cov = trim($lecture['cover_image']);
            if (strpos($cov, 'http://') === 0 || strpos($cov, 'https://') === 0) {
                if (preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $cov, $m) || preg_match('/[?&]id=([a-zA-Z0-9_-]+)/', $cov, $m)) {
                    return "https://drive.google.com/thumbnail?id=" . $m[1] . "&sz=w800";
                }
                return $cov;
            }
            return rtrim($base_url, '/') . '/' . ltrim($cov, '/');
        }
        
        if (!empty($lecture['file_path'])) {
            $fp = $lecture['file_path'];
            if (preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $fp, $m) || preg_match('/[?&]id=([a-zA-Z0-9_-]+)/', $fp, $m)) {
                return "https://drive.google.com/thumbnail?id=" . $m[1] . "&sz=w800";
            }
        }
        
        return null;
    }
}

$profile_assets = [];
try {
    $stmt_assets = $pdo->prepare("SELECT asset_key, file_path FROM design_assets WHERE page_section = 'student_profile'");
    $stmt_assets->execute();
    while ($row = $stmt_assets->fetch(PDO::FETCH_ASSOC)) {
        $profile_assets[$row['asset_key']] = $row['file_path'];
    }
} catch (PDOException $e) {}

function getProfileAsset($key, $is_bg = false, $style = 'width: 50px !important; height: 50px !important; margin-left: 12px !important; vertical-align: middle !important;') {
    global $profile_assets;
    $base_url = '/mo7adaraty/'; 

    $ext = strpos($key, 'bg') !== false ? 'webp' : 'svg';
    $path = !empty($profile_assets[$key]) ? ltrim($profile_assets[$key], '/') : "includesimg/student/{$key}.{$ext}";
    
    $full_path = __DIR__ . '/../' . $path;
    $version = file_exists($full_path) ? filemtime($full_path) : time();
    
    $url = htmlspecialchars($base_url . $path . '?v=' . $version);
    
    if ($is_bg) return $url;
    
    return '<img src="' . $url . '" alt="" style="' . $style . ' object-fit: contain !important; pointer-events: none !important;">';
}

$line_h_path = '../admin/assets/icons/line_h.png';
$line_v_path = '../admin/assets/icons/line_v.png';
$th_bg_path  = '../admin/assets/icons/table_header_bg.png';

$line_h = file_exists($line_h_path) ? $base_url . 'admin/assets/icons/line_h.png?v='.time() : '';
$line_v = file_exists($line_v_path) ? $base_url . 'admin/assets/icons/line_v.png?v='.time() : '';
$th_bg  = file_exists($th_bg_path)  ? $base_url . 'admin/assets/icons/table_header_bg.png?v='.time() : '';

try {
    $stmt_current_yr = $pdo->query("SELECT id, year_label FROM academic_years WHERE is_current = 1 LIMIT 1");
    $curr_yr = $stmt_current_yr->fetch(PDO::FETCH_ASSOC);
    if ($curr_yr) {
        $curr_yr_id = $curr_yr['id'];
        $current_year_label = $curr_yr['year_label'];
    }

    $stmt_user = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt_user->execute([$user_id]);
    $user_info = $stmt_user->fetch();

    if (!$user_info) {
        session_destroy();
        header("Location: ../login.php");
        exit();
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_gender'])) {
        $new_gender = $_POST['gender'];
        if (in_array($new_gender, ['male', 'female', 'unspecified'])) {
            
            $current_pic = $user_info['profile_picture'];
            $new_pic = $current_pic;
            $default_pics = ['default.png', 'default_profile.png', 'default_unspecified.png', 'default_male.png', 'default_female.png', ''];
            
            if (in_array($current_pic, $default_pics)) {
                if ($new_gender === 'male') $new_pic = 'default_male.png';
                elseif ($new_gender === 'female') $new_pic = 'default_female.png';
                else $new_pic = 'default_unspecified.png';
            }

            $stmt_update = $pdo->prepare("UPDATE users SET gender = ?, profile_picture = ? WHERE user_id = ?");
            $stmt_update->execute([$new_gender, $new_pic, $user_id]);

            if ($new_gender === 'unspecified') $_SESSION['gender_prompt_dismissed'] = true;

            header("Location: profil.php?status=gender_updated");
            exit();
        }
    }

    $profile_picture = $user_info['profile_picture'] ?: 'default_profile.png';

    $stmt_check_enroll = $pdo->prepare("SELECT COUNT(*) FROM student_enrollments WHERE user_id = ? AND academic_year_id = ?");
    $stmt_check_enroll->execute([$user_id, $curr_yr_id]);
    
    if ($stmt_check_enroll->fetchColumn() == 0) {
        $needs_reenrollment = true;
        $stmt_last_enroll = $pdo->prepare("
            SELECT d.division_id, d.division_name, d.division_image
            FROM student_enrollments se
            JOIN course_offerings co ON se.offering_id = co.offering_id
            JOIN divisions d ON co.division_id = d.division_id
            WHERE se.user_id = ?
            ORDER BY se.enrollment_id DESC LIMIT 1
        ");
        $stmt_last_enroll->execute([$user_id]);
        $last_enroll = $stmt_last_enroll->fetch(PDO::FETCH_ASSOC);

        if (!$last_enroll) {
            $divisions = $pdo->query("
                SELECT DISTINCT d.division_id, d.division_name 
                FROM divisions d
                INNER JOIN course_offerings co ON d.division_id = co.division_id
                WHERE co.academic_year_id = $curr_yr_id AND (co.status = 1 OR co.status = 'active')
                ORDER BY d.division_name ASC
            ")->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    if (!$needs_reenrollment) {
        $stmt_enrollments = $pdo->prepare("
            SELECT d.division_id, d.division_name, d.division_image, 
                   c.class_id, c.class_name, c.class_image, 
                   g.group_id, g.group_name, g.group_image, 
                   t.track_name,
                   co.offering_id, co.timetable_url, s.subject_id, s.subject_name, p.professor_name,
                   tm.file_path as timetable_file,
                   se.selected_subjects
            FROM student_enrollments se
            JOIN course_offerings co ON se.offering_id = co.offering_id
            LEFT JOIN divisions d ON co.division_id = d.division_id
            LEFT JOIN classes c ON co.class_id = c.class_id
            LEFT JOIN `groups` g ON co.group_id = g.group_id
            LEFT JOIN tracks t ON co.track_id = t.track_id
            LEFT JOIN offering_subjects os ON co.offering_id = os.offering_id
            LEFT JOIN subjects s ON os.subject_id = s.subject_id
            LEFT JOIN professors p ON os.professor_id = p.professor_id
            LEFT JOIN timetables tm ON (tm.division_id = co.division_id AND tm.class_id = co.class_id AND tm.group_id = co.group_id)
            WHERE se.user_id = ? AND se.academic_year_id = ? AND (co.status = 1 OR co.status = 'active')
            ORDER BY d.division_name, c.class_id, g.group_name, s.subject_name
        ");
        $stmt_enrollments->execute([$user_id, $curr_yr_id]);
        $all_enrollments = $stmt_enrollments->fetchAll(PDO::FETCH_ASSOC);

        foreach ($all_enrollments as $enrollment) {
            $division = $enrollment['division_name'] ?: 'شعبة غير محددة';
            $division_img = $enrollment['division_image'] ?: 'default_division.png';
            $class_img = $enrollment['class_image'] ?: 'default_class.png'; 
            $group_img = $enrollment['group_image'] ?: 'default_group.png'; 
            
            $offering_id = $enrollment['offering_id'];
            
            $offering_key = ($enrollment['class_name'] ?: 'فصل غير محدد') . ' - ' . ($enrollment['group_name'] ?: 'عام') . ($enrollment['track_name'] ? ' - ' . $enrollment['track_name'] : '');
            
            $selected_subjects_arr = $enrollment['selected_subjects'] ? json_decode($enrollment['selected_subjects'], true) : null;

            if (!isset($structured_data[$division])) {
                $structured_data[$division] = ['image' => $division_img, 'offerings' => []];
            }
            if (!isset($structured_data[$division]['offerings'][$offering_id])) {
                $structured_data[$division]['offerings'][$offering_id] = [
                    'description'   => $offering_key, 
                    'class_image'   => $class_img,   
                    'group_image'   => $group_img,
                    'timetable_url' => $enrollment['timetable_url'] ?? null,
                    'timetable'     => $enrollment['timetable_file'] ?? null,
                    'subjects'      => []
                ];
            }
            if ($enrollment['subject_name']) {
                $subj_id = $enrollment['subject_id'];
                $show_subject = true;
                
                if (is_array($selected_subjects_arr) && !empty($selected_subjects_arr)) {
                    if (!in_array($subj_id, $selected_subjects_arr)) {
                        $show_subject = false;
                    }
                }
                
                if ($show_subject) {
                    $subject_exists = false;
                    foreach ($structured_data[$division]['offerings'][$offering_id]['subjects'] as $existing_sub) {
                        if ($existing_sub['subject_name'] === $enrollment['subject_name']) {
                            $subject_exists = true;
                            break;
                        }
                    }
                    
                    if (!$subject_exists) {
                        $structured_data[$division]['offerings'][$offering_id]['subjects'][] = [
                            'subject_name'   => $enrollment['subject_name'],
                            'professor_name' => $enrollment['professor_name'] ?? 'لم يحدد'
                        ];
                    }
                }
            }
        }

        // جلب المحاضرات المنشورة مع منع التكرار
        $stmt_published = $pdo->prepare("
            SELECT 
                l.lecture_id, l.title, l.description, l.upload_date, l.file_path, l.file_type, l.cover_image, 
                s.subject_name, 
                c.class_id, c.class_name, c.class_image 
            FROM lectures l
            LEFT JOIN offering_subjects os ON l.offering_subject_id = os.offering_subject_id
            LEFT JOIN subjects s ON os.subject_id = s.subject_id
            LEFT JOIN course_offerings co ON l.offering_id = co.offering_id
            LEFT JOIN classes c ON co.class_id = c.class_id
            WHERE l.uploader_user_id = ? 
              AND l.status = 'approved' 
              AND (l.academic_year_id = ? OR l.academic_year_id IS NULL OR l.academic_year_id = 0)
            ORDER BY c.class_id ASC, l.upload_date DESC
        ");
        $stmt_published->execute([$user_id, $curr_yr_id]);
        $raw_published_lectures = $stmt_published->fetchAll(PDO::FETCH_ASSOC);

        $seen_lectures = [];
        foreach ($raw_published_lectures as $lec) {
            $class_key = $lec['class_id'] ? (int)$lec['class_id'] : 0;
            $unique_key = !empty($lec['file_path']) ? trim($lec['file_path']) : (trim($lec['title']) . '_' . ($lec['subject_name'] ?? ''));

            if (isset($seen_lectures[$class_key][$unique_key])) {
                continue;
            }
            $seen_lectures[$class_key][$unique_key] = true;

            if (!isset($published_lectures_by_class[$class_key])) {
                $published_lectures_by_class[$class_key] = [
                    'class_image' => !empty($lec['class_image']) ? $lec['class_image'] : 'default_class.png',
                    'class_name'  => $lec['class_name'] ?? 'فصل دراسي',
                    'lectures'    => []
                ];
            }
            $published_lectures_by_class[$class_key]['lectures'][] = $lec;
        }
    }

} catch (PDOException $e) {
    header("Location: ../login.php?error=خطأ في جلب البيانات");
    exit();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ملفي الشخصي - Mo7adaraty</title>
  <link rel="icon" type="image/svg+xml" href="<?= $base_url ?>assets/images/favicon.svg">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;900&display=swap" rel="stylesheet">
  
  <link rel="stylesheet" href="<?= $base_url ?>assets/css/global.css">
  <link rel="stylesheet" href="<?= $base_url ?>assets/css/student.css">
  <link rel="stylesheet" href="<?= $base_url ?>assets/css/pages/profil.css">
  <style>
    /* بطاقة استعمال الزمن الخفيفة */
    .timetable-live-card-container {
        margin-top: 15px;
        background: var(--bg-body);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        overflow: hidden;
    }
    .timetable-live-header {
        padding: 12px 14px;
        background: rgba(16, 185, 129, 0.1);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .timetable-live-header-title {
        font-size: 13px;
        font-weight: 700;
        color: #059669;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .timetable-placeholder-box {
        padding: 24px 16px;
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 8px;
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.04) 0%, rgba(15, 23, 42, 0.02) 100%);
        border-top: 1px solid var(--border-color);
        border-bottom: 1px solid var(--border-color);
    }
    .timetable-live-buttons-bar {
        display: flex;
        gap: 8px;
        padding: 10px;
        background: var(--bg-surface);
    }
    .btn-tt-live-action {
        flex: 1;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 8px 12px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 700;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.2s ease;
        border: none;
    }
    .btn-tt-live-dl {
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        color: #fff !important;
    }
    .btn-tt-live-fs {
        background: var(--bg-body);
        color: var(--text-primary);
        border: 1px solid var(--border-color);
    }
    .btn-tt-live-fs:hover {
        border-color: var(--brand-primary);
        color: var(--brand-primary);
    }

    /* صندوق غلاف المحاضرة مع دعم الصور السحابية */
    .lecture-cover-container-card {
        width: 100%;
        height: 180px;
        overflow: hidden;
        background: #0f172a;
        position: relative;
        cursor: pointer;
    }
    .lecture-cover-container-card img.actual-lecture-cover {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        transition: transform 0.3s ease;
    }
    .lecture-cover-container-card:hover img.actual-lecture-cover {
        transform: scale(1.03);
    }
    .pdf-badge-tag {
        position: absolute;
        top: 10px;
        right: 10px;
        background: #ef4444;
        color: #fff;
        padding: 2px 8px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.5px;
        z-index: 2;
    }

    /* صندوق المعاينة البديل في حال تعذر تحميل الغلاف */
    .lecture-preview-placeholder-card {
        width: 100%;
        height: 180px;
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        border-bottom: 1px solid var(--border-color);
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 8px;
        color: #ffffff;
        cursor: pointer;
    }
    .lecture-preview-placeholder-card:hover {
        background: linear-gradient(135deg, #25334a 0%, #121c32 100%);
    }
    .lecture-preview-placeholder-card .preview-hint-btn {
        background: rgba(255, 255, 255, 0.15);
        color: #fff;
        border: 1px solid rgba(255, 255, 255, 0.25);
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 11.5px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        backdrop-filter: blur(4px);
    }

    /* نافذة المعاينة السريعة المنبثقة On-Demand */
    .preview-modal-overlay-custom {
        position: fixed;
        inset: 0;
        z-index: 999999;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 16px;
        background: rgba(0, 0, 0, 0.85);
        backdrop-filter: blur(4px);
    }
    .preview-modal-dialog-custom {
        position: relative;
        background: var(--bg-surface);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        width: 100%;
        max-width: 1050px;
        height: 90vh;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    }
    .preview-modal-top-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 18px;
        border-bottom: 1px solid var(--border-color);
        background: var(--bg-body);
    }
    .preview-modal-content-area {
        flex: 1;
        width: 100%;
        height: 100%;
        background: #0f172a;
    }
    .preview-modal-content-area iframe {
        width: 100%;
        height: 100%;
        border: none;
        display: block;
    }

    .published-class-banner-wrap {
        width: 100%;
        overflow: hidden;
        background: var(--bg-surface);
        border-bottom: 1px solid var(--border-color);
    }
    .published-class-banner-img {
        width: 100%;
        max-height: 200px;
        object-fit: cover;
        display: block;
    }
  </style>
</head>
<body class="student-profile-page">
    
    <?php require_once '../templates/header.php'; ?>

<div class="container" style="max-width: 1200px; margin: 0 auto; padding: 2rem 1rem;">
  
  <?php if(isset($_GET['status']) && $_GET['status'] == 're_enrolled'): ?>
      <div class="status-message-custom">
          <?= getProfileAsset('profile_icon_check') ?> <span>تم تسجيلك في الموسم الجديد بنجاح!</span>
      </div>
  <?php endif; ?>

  <?php if(isset($_GET['status']) && $_GET['status'] == 'gender_updated'): ?>
      <div class="status-message-custom">
          <?= getProfileAsset('profile_icon_check') ?> <span>تم تحديث بيانات الجنس وتخصيص صورتك الشخصية بنجاح!</span>
      </div>
  <?php endif; ?>

  <?php if(isset($_GET['error'])): ?>
      <div class="status-message-custom" style="background: rgba(220, 53, 69, 0.1); color: var(--danger-color); border: 1px solid var(--danger-color); margin-bottom: 20px;">
          <i class="fa-solid fa-circle-exclamation" style="font-size: 24px; margin-left: 10px;"></i> 
          <span><?= htmlspecialchars(urldecode($_GET['error'])) ?></span>
      </div>
  <?php endif; ?>

  <?php if(isset($_GET['status']) && $_GET['status'] == 'multiple_timetables'): ?>
      <div class="status-message-custom" style="background: rgba(26, 115, 232, 0.1); color: var(--brand-primary); border: 1px solid var(--brand-primary);">
          <i class="fa-solid fa-layer-group" style="font-size: 24px; margin-left: 10px;"></i> 
          <span>أنت مسجل في أكثر من مقرر. يرجى التمرير للأسفل والاطلاع على استعمال الزمن الخاص بكل مقرر من البطاقة الخاصة به.</span>
      </div>
  <?php endif; ?>

  <?php if(isset($_GET['status']) && $_GET['status'] == 'no_timetable'): ?>
      <div class="status-message-custom" style="background: rgba(220, 53, 69, 0.1); color: var(--danger-color); border: 1px solid var(--danger-color);">
          <i class="fa-solid fa-calendar-xmark" style="font-size: 24px; margin-left: 10px;"></i> 
          <span>عذراً، لم يتم رفع استعمال الزمن الخاص بمجموعتك بعد. يرجى المحاولة لاحقاً.</span>
      </div>
  <?php endif; ?>

  <?php if ( (empty($user_info['gender']) || $user_info['gender'] === 'unspecified') && !isset($_SESSION['gender_prompt_dismissed']) ): ?>
    <div class="gender-prompt-box">
        <h3 class="gender-header">
            <?= getProfileAsset('profile_icon_gender') ?> <span style="color: var(--brand-primary);">استكمال بيانات الملف الشخصي</span>
        </h3>
        <p>مرحباً بك! يرجى تحديد فئتك.</p>
        <form action="" method="POST" class="gender-form-container">
            <select name="gender" required class="form-input gender-select">
                <option value="" selected disabled>-- يرجى اختيار الفئة --</option>
                <option value="male">طالب</option>
                <option value="female">طالبة</option>
                <option value="unspecified">عدم الإفصاح (تخطي)</option>
            </select>
            <button type="submit" name="update_gender" class="btn-custom gender-submit-btn" style="border-radius: 12px; padding: 0 20px; font-weight: bold; display: flex; align-items: center; gap: 8px;">
                <?= getProfileAsset('profile_icon_check') ?> <span>حفظ الإعدادات</span>
            </button>
        </form>
    </div>
  <?php endif; ?>
<div class="custom-profile-header">
    <div class="profile-sidebar-buttons">
        <a href="archive.php" class="sidebar-btn">
            <span class="btn-text">أرشيف المواسم</span>
            <?= getProfileAsset('profile_icon_archive', false, 'height: 40px; margin-right: 10px;') ?>
        </a>
        <a href="my_lectures/index.php" class="sidebar-btn">
            <span class="btn-text">محاضراتي</span>
            <?= getProfileAsset('profile_icon_lectures', false, 'height: 40px; margin-right: 10px;') ?>
        </a>
        <a href="profile_settings/index.php" class="sidebar-btn">
            <span class="btn-text">إعدادات الحساب</span>
            <?= getProfileAsset('profile_icon_settings', false, 'height: 40px; margin-right: 10px;') ?>
        </a>
    </div>

    <div class="year-graphic">
        <div style="position: relative; display: inline-block;">
            <i class="fa-solid fa-graduation-cap" style="color: #d4af37; font-size: 50px; position: absolute; top: -20px; left: 50%; transform: translateX(-50%); z-index: 2; filter: drop-shadow(2px 2px 4px rgba(0,0,0,0.5));"></i>
            <h1 class="golden-year"><?= htmlspecialchars($current_year_label ? explode('-', $current_year_label)[1] ?? '2026' : '2026'); ?></h1>
        </div>
    </div>
<div class="user-details-section">
    <div class="user-info-list">
        <div class="info-item">
            <span class="info-text"><?= htmlspecialchars($user_info['username']); ?></span>
            <div class="info-icon-wrapper"><i class="fa-solid fa-id-card" style="color: #555;"></i></div>
        </div>
        <div class="info-item">
            <span class="info-text"><?= htmlspecialchars($user_info['email']); ?></span>
            <div class="info-icon-wrapper"><i class="fa-solid fa-envelope" style="color: #ffc107;"></i></div>
        </div>
        <?php 
            $gender_text = 'غير محدد';
            if($user_info['gender'] == 'male') $gender_text = 'طالب';
            elseif($user_info['gender'] == 'female') $gender_text = 'طالبة';
        ?>
        <div class="info-item">
            <span class="info-text"><?= $gender_text ?></span>
            <div class="info-icon-wrapper"><i class="fa-solid fa-venus-mars" style="color: #e83e8c;"></i></div>
        </div>
        
        <?php if(!empty($structured_data)): ?>
                <?php 
                    $first_div = array_key_first($structured_data);
                    $classes = [];
                    $groups = [];
                    
                    if($first_div && !empty($structured_data[$first_div]['offerings'])) {
                        foreach($structured_data[$first_div]['offerings'] as $offering) {
                            $desc_parts = explode(' - ', $offering['description']);
                            if(isset($desc_parts[0]) && trim($desc_parts[0]) !== '') {
                                $classes[] = trim($desc_parts[0]);
                            }
                            if(isset($desc_parts[1]) && trim($desc_parts[1]) !== '') {
                                $groups[] = trim($desc_parts[1]);
                            }
                        }
                    }
                    
                    $classes_text = implode(' و ', array_unique($classes));
                    $groups_text = implode(' و ', array_unique($groups));
                ?>
                
                <div class="info-item">
                    <span class="info-text"><?= htmlspecialchars($first_div) ?></span>
                    <div class="info-icon-wrapper"><i class="fa-solid fa-graduation-cap" style="color: #343a40;"></i></div>
                </div>
                
                <?php if(!empty($classes_text)): ?>
                <div class="info-item">
                    <span class="info-text"><?= htmlspecialchars($classes_text) ?></span>
                    <div class="info-icon-wrapper"><i class="fa-solid fa-book" style="color: #17a2b8;"></i></div>
                </div>
                <?php endif; ?>
                
                <?php if(!empty($groups_text)): ?>
                <div class="info-item">
                    <span class="info-text">فوج: <?= htmlspecialchars($groups_text) ?></span>
                    <div class="info-icon-wrapper"><i class="fa-solid fa-users" style="color: #28a745;"></i></div>
                </div>
                <?php endif; ?>
                
            <?php endif; ?>

    </div>
    
    <div class="profile-pic-container">
        <img src="../uploads/profile_pictures/<?= htmlspecialchars($profile_picture); ?>" alt="الصورة الشخصية" class="profile-img" onerror="this.onerror=null;this.src='../uploads/profile_pictures/default_profile.png';">
    </div>
</div>
  </div>  
  <?php if ($needs_reenrollment): ?>
      <div class="reenroll-box" style="padding: 25px; border-radius: 12px; margin-bottom: 30px; border: 2px solid var(--brand-primary); background: var(--bg-surface);">
          <h2 style="margin-bottom: 20px; font-size: 24px; color: var(--text-primary); border-bottom: 1px solid var(--border-color); padding-bottom: 15px;">
              <?= getProfileAsset('profile_icon_enter') ?> <span>مرحباً بك في موسم <?= htmlspecialchars($current_year_label); ?>!</span>
          </h2>
          <p style="color: var(--text-secondary); margin-bottom: 20px;">قم بتحديث مقرراتك للموسم الجديد للاستمرار في تصفح المنصة. (يمكنك اختيار مقررين كحد أقصى)</p>
          
          <form action="re_enroll_process.php" method="POST" class="reenroll-form" id="reEnrollForm">
              <input type="hidden" name="academic_year_id" value="<?= $curr_yr_id; ?>">
              
              <?php if ($last_enroll): ?>
                  <div class="division-banner-container" style="margin-top: 15px; margin-bottom: 20px;">
                      <?php $img = !empty($last_enroll['division_image']) ? $last_enroll['division_image'] : 'default_division.png'; ?>
                      <img src="<?= $base_url ?>uploads/divisions/<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($last_enroll['division_name']); ?>" class="division-banner-img" onerror="this.src='<?= $base_url ?>assets/images/favicon.png';">
                  </div>
                  <input type="hidden" id="division" name="division_id" value="<?= $last_enroll['division_id']; ?>">
              <?php else: ?>
                  <div style="margin-bottom: 20px;">
                      <label style="display: block; margin-bottom: 8px; color: var(--text-primary); font-weight: bold;">اختر الشعبة لعرض الفصول المتاحة:</label>
                      <select id="division" name="division_id" required class="form-input" style="width: 100%; padding: 12px; border-radius: 8px; background: var(--bg-color); color: var(--text-primary); border: 1px solid var(--border-color);">
                          <option value="">-- اختر الشعبة --</option>
                          <?php foreach($divisions as $div): ?>
                              <option value="<?= $div['division_id']; ?>"><?= htmlspecialchars($div['division_name']); ?></option>
                          <?php endforeach; ?>
                      </select>
                  </div>
              <?php endif; ?>

              <div id="enrollment-container" style="margin-bottom: 20px;"></div>
              
              <button type="button" id="add-enrollment-btn" style="display: none; width: 100%; padding: 12px; background: #10b981; color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; margin-bottom: 20px; align-items: center; justify-content: center; gap: 8px; transition: 0.3s;">
                  <i class="fa-solid fa-plus"></i> إضافة مقرر آخر
              </button>

              <button type="submit" class="btn-submit-enroll" style="background: var(--brand-primary); color: white; width: 100%; border: none; padding: 15px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 16px; transition: 0.3s;">
                  <i class="fa-solid fa-paper-plane" style="margin-left: 10px;"></i> تأكيد تسجيل مقررات الموسم الجديد
              </button>
          </form>
      </div>
  <?php else: ?>
      <?php if (empty($structured_data)): ?>
        <div class="no-data" style="padding: 30px; text-align: center; background: var(--bg-surface); border-radius: 12px; color: var(--text-secondary); border: 1px solid var(--border-color);">
          <i class="fa-solid fa-folder-open" style="font-size: 40px; margin-bottom: 15px; opacity: 0.5;"></i>
          <p style="font-size: 18px; font-weight: bold; margin-bottom: 15px;">أنت غير مسجل في أي مقرر دراسي لهذا الموسم.</p>
          <a href="profile_settings/index.php" style="display: inline-flex; align-items: center; gap: 8px; background: var(--brand-primary); color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: bold; transition: 0.3s;">
              <i class="fa-solid fa-pen-to-square"></i> لتسجيل المقررات، انتقل إلى إعدادات الحساب
          </a>
        </div>
      <?php else: ?>
        <?php foreach ($structured_data as $division_name => $div_data): ?>
          <div class="division-group" style="background: var(--bg-surface); border-radius: 12px; margin-bottom: 30px; border: 1px solid var(--border-color); overflow: hidden;">
            
            <div class="division-banner-container">
                <img src="<?= $base_url ?>uploads/divisions/<?= htmlspecialchars($div_data['image']) ?>" alt="<?= htmlspecialchars($division_name); ?>" class="division-banner-img" onerror="this.src='<?= $base_url ?>assets/images/favicon.png';">
            </div>

            <div class="offerings-grid" style="padding: 20px; display: grid; gap: 20px;">
                <?php foreach ($div_data['offerings'] as $offering_id => $offering_data): 
                    $drive_url = $offering_data['timetable_url'] ?? '';
                    $embed_url = getDriveEmbedUrl($drive_url);
                    $download_url = getDriveDownloadUrl($drive_url);
                    $has_drive = !empty($embed_url);
                    $has_legacy = !$has_drive && !empty($offering_data['timetable']);
                    $legacy_url = $has_legacy ? $base_url . 'uploads/timetables/' . $offering_data['timetable'] : '';
                    $timetable_preview_url = $has_drive ? $embed_url : $legacy_url;
                    $timetable_download_url = $has_drive ? $download_url : $legacy_url;
                ?>
                    <div class="class-card">   
                        <div class="class-banner-container">
                            <img src="<?= $base_url ?>uploads/classes/<?= htmlspecialchars($offering_data['class_image']) ?>" alt="صورة الفصل" class="class-banner-img" onerror="this.src='<?= $base_url ?>assets/images/favicon.png';">
                        </div>

                        <div class="group-banner-container">
                            <img src="<?= $base_url ?>uploads/groups/<?= htmlspecialchars($offering_data['group_image']) ?>" alt="صورة الفوج" class="group-banner-img" onerror="this.src='<?= $base_url ?>assets/images/favicon.png';">
                        </div>

                        <div class="class-card-header" style="padding: 15px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                            <div class="class-info"><h4 style="font-size: 14px; margin:0;"><?= htmlspecialchars($offering_data['description']); ?></h4></div>
                            <a href="class_lectures.php?id=<?= $offering_id; ?>" class="btn-enter" style="margin-top: 0; background: var(--brand-primary); color: white; text-decoration: none; padding: 8px 15px; border-radius: 8px; font-size: 14px; min-height: auto;">
                                <?= getProfileAsset('profile_icon_enter', false, 'width: 20px !important; height: 20px !important; margin-left: 8px !important;') ?> <span>الدخول</span>
                            </a>
                        </div>
                        
                        <div class="class-card-body" style="padding: 15px;">
                            <ul class="subjects-list" style="list-style: none; padding: 0; margin: 0;">
                                <?php if (!empty($offering_data['subjects'])): ?>
                                    <?php foreach ($offering_data['subjects'] as $subject): ?>
                                        <li style="padding: 10px 0; border-bottom: 1px solid var(--border-color); color: var(--text-primary); font-size: 14px;">
                                            <span class="subject-name" style="font-weight: bold;"><?= htmlspecialchars($subject['subject_name']); ?></span> 
                                            <span class="professor-name" style="color: var(--text-secondary); margin-right: 10px;">الأستاذ: <?= htmlspecialchars($subject['professor_name']); ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li style="color: var(--text-secondary); font-size: 14px;">لا توجد مواد مسندة لهذا المقرر بعد.</li>
                                <?php endif; ?>
                            </ul>

                            <?php if ($has_drive || $has_legacy): ?>
                                <div class="timetable-live-card-container">
                                    <div class="timetable-live-header">
                                        <div class="timetable-live-header-title">
                                            <i class="fa-solid fa-calendar-check"></i> 
                                            <span>استعمال الزمن المعتمد لمجموعتي</span>
                                        </div>
                                    </div>
                                    
                                    <div class="timetable-placeholder-box">
                                        <i class="fa-solid fa-calendar-days" style="font-size: 34px; color: #10b981; opacity: 0.85;"></i>
                                        <span style="font-size: 12px; color: var(--text-secondary); font-weight: bold;">الملف متاح للمعاينة والتحميل الفوري</span>
                                    </div>
                                    
                                    <div class="timetable-live-buttons-bar">
                                        <a href="<?= htmlspecialchars($timetable_download_url) ?>" target="_blank" download class="btn-tt-live-action btn-tt-live-dl">
                                            <i class="fa-solid fa-download"></i> 
                                            <span>تحميل الملف</span>
                                        </a>
                                        <button type="button" class="btn-tt-live-action btn-tt-live-fs" onclick="openPreviewModal('<?= htmlspecialchars($timetable_preview_url) ?>', 'استعمال الزمن - <?= htmlspecialchars(addslashes($offering_data['description'])) ?>')">
                                            <i class="fa-solid fa-eye"></i> 
                                            <span>معاينة الملف</span>
                                        </button>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div style="margin-top: 15px; padding: 12px; background: var(--bg-body); border-radius: 8px; text-align: center; font-size: 13px; color: var(--text-secondary); border: 1px dashed var(--border-color);">
                                    <i class="fa-solid fa-clock-rotate-left"></i> استعمال الزمن لهذا الفوج قيد التحيين والإعداد.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>

      <div class="section-title" style="margin-top: 3rem; margin-bottom: 20px; font-weight: bold; font-size: 20px;">محاضراتي المنشورة (هذا الموسم)</div>
      <?php if (empty($published_lectures_by_class)): ?>
        <div class="no-data" style="padding: 20px; text-align: center; background: var(--bg-surface); border-radius: 12px; color: var(--text-secondary); border: 1px solid var(--border-color);"><p>لم تقم بنشر أي محاضرات مقبولة في هذا الموسم بعد.</p></div>
      <?php else: ?>
        <?php foreach ($published_lectures_by_class as $class_info): ?>
          <div class="division-group" style="background: var(--bg-surface); border-radius: 12px; margin-bottom: 30px; border: 1px solid var(--border-color); overflow: hidden;">
            
            <div class="published-class-banner-wrap">
                <img src="<?= $base_url ?>uploads/classes/<?= htmlspecialchars($class_info['class_image']) ?>" alt="<?= htmlspecialchars($class_info['class_name']) ?>" class="published-class-banner-img" onerror="this.src='<?= $base_url ?>assets/images/favicon.png';">
            </div>

            <div class="offerings-grid" style="padding: 20px; display: grid; gap: 20px;">
              <?php foreach ($class_info['lectures'] as $lecture): 
                  $lec_is_drive = (strpos($lecture['file_path'], 'http://') === 0 || strpos($lecture['file_path'], 'https://') === 0);
                  $lec_view_url = $lec_is_drive ? getDriveEmbedUrl($lecture['file_path']) : "../" . $lecture['file_path'];
                  $lec_download_url = $lec_is_drive ? getDriveDownloadUrl($lecture['file_path']) : "../download.php?type=lecture&id=" . $lecture['lecture_id'];$cover_src = getLectureCoverSrc($lecture,$base_url);
              ?>
                <div class="class-card">
                  <?php if (!empty($cover_src)): ?>
                    <div class="lecture-cover-container-card" onclick="openPreviewModal('<?= htmlspecialchars($lec_view_url) ?>', '<?= htmlspecialchars(addslashes($lecture['title'])) ?>')">
                        <img src="<?= htmlspecialchars($cover_src) ?>" alt="<?= htmlspecialchars($lecture['title']) ?>" class="actual-lecture-cover" loading="lazy" onerror="this.parentElement.style.display='none'; this.parentElement.nextElementSibling.style.display='flex';">
                        <span class="pdf-badge-tag"><?= strtoupper(htmlspecialchars($lecture['file_type'] ?: 'PDF')) ?></span>
                    </div>
                    <div class="lecture-preview-placeholder-card" style="display: none;" onclick="openPreviewModal('<?= htmlspecialchars($lec_view_url) ?>', '<?= htmlspecialchars(addslashes($lecture['title'])) ?>')">
                        <span class="pdf-badge-tag"><?= strtoupper(htmlspecialchars($lecture['file_type'] ?: 'PDF')) ?></span>
                        <i class="fa-solid fa-file-pdf" style="font-size: 42px; color: #ef4444;"></i>
                        <span class="preview-hint-btn">
                            <i class="fa-solid fa-eye"></i> انقر للمعاينة المباشرة
                        </span>
                    </div>
                  <?php else: ?>
                    <div class="lecture-preview-placeholder-card" onclick="openPreviewModal('<?= htmlspecialchars($lec_view_url) ?>', '<?= htmlspecialchars(addslashes($lecture['title'])) ?>')">
                        <span class="pdf-badge-tag"><?= strtoupper(htmlspecialchars($lecture['file_type'] ?: 'PDF')) ?></span>
                        <i class="fa-solid fa-file-pdf" style="font-size: 42px; color: #ef4444;"></i>
                        <span class="preview-hint-btn">
                            <i class="fa-solid fa-eye"></i> انقر للمعاينة المباشرة
                        </span>
                    </div>
                  <?php endif; ?>

                  <div class="class-card-header" style="padding: 15px; border-bottom: 1px solid var(--border-color);">
                      <div class="class-info"><h4 style="margin: 0; color: var(--text-primary);"><?= htmlspecialchars($lecture['title']); ?></h4></div>
                  </div>
                  <div class="class-card-body" style="padding: 15px;">
                    <ul class="subjects-list" style="list-style: none; padding: 0; margin: 0; margin-bottom: 15px;">
                      <li style="padding: 8px 0; border-bottom: 1px solid var(--border-color); color: var(--text-primary); font-size: 14px;"><span class="subject-name" style="font-weight: bold;">المادة:</span> <span class="professor-name" style="color: var(--text-secondary);"><?= htmlspecialchars($lecture['subject_name'] ?? 'غير محدد'); ?></span></li>
                      
                      <li style="padding: 8px 0; color: var(--text-primary); font-size: 14px;"><span class="subject-name" style="font-weight: bold;">تاريخ النشر:</span> <span class="professor-name" style="color: var(--text-secondary);"><?= date('Y-m-d', strtotime($lecture['upload_date'])); ?></span></li>
                    </ul>
                    <div style="display: flex; gap: 8px;">
                        <button type="button" onclick="openPreviewModal('<?= htmlspecialchars($lec_view_url) ?>', '<?= htmlspecialchars(addslashes($lecture['title'])) ?>')" class="btn-enter" style="flex: 1; margin-top: 0; background: var(--brand-primary); color: white; text-align: center; padding: 10px; border-radius: 8px; font-weight: bold; min-height: auto; justify-content: center; border: none; cursor: pointer;">
                            <?= getProfileAsset('profile_icon_pdf', false, 'width: 18px !important; height: 18px !important; margin-left: 6px !important;') ?> <span>معاينة المحاضرة</span>
                        </button>
                        <a href="<?= htmlspecialchars($lec_download_url); ?>" <?= $lec_is_drive ? 'target="_blank" download' : '' ?> class="btn-enter" style="flex: 1; margin-top: 0; background: #2563eb; color: white; text-align: center; padding: 10px; border-radius: 8px; text-decoration: none; font-weight: bold; min-height: auto; justify-content: center;">
                            <i class="fa-solid fa-download" style="margin-left: 6px;"></i> <span>تحميل</span>
                        </a>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
  <?php endif; ?>

</div>

<div id="previewModalCustom" class="preview-modal-overlay-custom" style="display: none;">
    <div class="preview-modal-dialog-custom">
        <div class="preview-modal-top-bar">
            <h3 id="previewModalHeading" style="margin: 0; font-size: 15px; font-weight: 700; color: var(--text-primary);">
                <i class="fa-solid fa-eye" style="color: var(--brand-primary); margin-left: 6px;"></i> <span id="previewModalTitle">معاينة الملف</span>
            </h3>
            <button type="button" onclick="closePreviewModal()" style="background: none; border: none; font-size: 24px; color: var(--text-secondary); cursor: pointer; line-height: 1;">&times;</button>
        </div>
        <div class="preview-modal-content-area">
            <iframe id="previewModalIframe" src="" allow="autoplay"></iframe>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="<?= $base_url ?>assets/js/student.js" defer></script>
<script>
    function openPreviewModal(url, title) {
        if (!url) return;
        document.getElementById('previewModalTitle').innerText = title;
        document.getElementById('previewModalIframe').src = url;
        document.getElementById('previewModalCustom').style.display = 'flex';
    }

    function closePreviewModal() {
        document.getElementById('previewModalCustom').style.display = 'none';
        document.getElementById('previewModalIframe').src = 'about:blank';
    }

    window.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closePreviewModal();
        }
    });
</script>
    
<?php include '../templates/footer.php'; ?>
</body>
</html>