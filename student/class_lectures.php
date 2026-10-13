<?php
session_start();
require_once '../config/db_connexion.php';

// 1. حماية الصفحة
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// 2. استقبال معرّف المقرر
$offering_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$offering_id) {
    header("Location: profil.php?error=" . urlencode("رابط المقرر غير صحيح"));
    exit();
}

// دوال استخراج روابط Google Drive للمعاينة والتحميل
if (!function_exists('getDriveEmbedUrl')) {
    function getDriveEmbedUrl($url) {
        if (empty($url)) return '';
        if (preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $url, $matches) || preg_match('/[?&]id=([a-zA-Z0-9_-]+)/', $matches)) {
            return "https://drive.google.com/file/d/" . $matches[1] . "/preview";
        }
        return $url;
    }
}
if (!function_exists('getDriveDownloadUrl')) {
    function getDriveDownloadUrl($url) {
        if (empty($url)) return '';
        if (preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $url, $matches) || preg_match('/[?&]id=([a-zA-Z0-9_-]+)/', $matches)) {
            return "https://drive.google.com/uc?export=download&id=" . $matches[1];
        }
        return $url;
    }
}

if (!function_exists('getLectureCoverSrc')) {
    function getLectureCoverSrc($lecture, $base_url = '../') {
        if (!empty($lecture['cover_image'])) {
            $cov = trim($lecture['cover_image']);
            if (strpos($cov, 'http://') === 0 || strpos($cov, 'https://') === 0) {
                if (preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $cov, $m) || preg_match('/[?&]id=([a-zA-Z0-9_-]+)/', $m)) {
                    return "https://drive.google.com/thumbnail?id=" . $m[1] . "&sz=w800";
                }
                return $cov;
            }
            return rtrim($base_url, '/') . '/' . ltrim($cov, '/');
        }
        
        if (!empty($lecture['file_path'])) {
            $fp = trim($lecture['file_path']);
            if (preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $fp, $m) || preg_match('/[?&]id=([a-zA-Z0-9_-]+)/', $m)) {
                return "https://drive.google.com/thumbnail?id=" . $m[1] . "&sz=w800";
            }
        }
        
        return null;
    }
}

try {
    try {
        $pdo->exec("
            UPDATE comments c
            JOIN lectures l ON c.content_id = l.lecture_id AND c.content_type = 'lecture'
            JOIN (
                SELECT file_path, MIN(lecture_id) as min_id
                FROM lectures
                WHERE file_path IS NOT NULL AND file_path != ''
                GROUP BY file_path
            ) m ON l.file_path = m.file_path
            SET c.content_id = m.min_id
            WHERE c.content_id != m.min_id
        ");

        $pdo->exec("
            UPDATE content_reactions r
            JOIN lectures l ON r.content_id = l.lecture_id AND r.content_type = 'lecture'
            JOIN (
                SELECT file_path, MIN(lecture_id) as min_id
                FROM lectures
                WHERE file_path IS NOT NULL AND file_path != ''
                GROUP BY file_path
            ) m ON l.file_path = m.file_path
            SET r.content_id = m.min_id
            WHERE r.content_id != m.min_id
        ");
    } catch (Exception $e) {}

    $stmt_design = $pdo->prepare("SELECT asset_key, file_path FROM design_assets WHERE page_section = 'class_lectures'");
    $stmt_design->execute();
    $design_settings = [];
    while ($row = $stmt_design->fetch(PDO::FETCH_ASSOC)) {
        $design_settings[$row['asset_key']] = $row['file_path'];
    }

    function getDesignIcon($key, $default_path) {
        global $design_settings;
        return !empty($design_settings[$key]) ? '../' . $design_settings[$key] : '../' . $default_path;
    }

    $stmt_year = $pdo->query("SELECT id, year_label FROM academic_years WHERE is_current = 1 LIMIT 1");
    $current_year = $stmt_year->fetch(PDO::FETCH_ASSOC);
    $current_year_id = $current_year ? $current_year['id'] : 1;
    $current_year_label = $current_year ? $current_year['year_label'] : '';

    $stmt_check = $pdo->prepare("SELECT selected_subjects FROM student_enrollments WHERE user_id = ? AND offering_id = ?");
    $stmt_check->execute([$user_id, $offering_id]);
    $enrollment_data = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if ($enrollment_data === false && (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin')) {
        header("Location: profil.php?error=" . urlencode("ليس لديك صلاحية الوصول لهذا المقرر"));
        exit();
    }
    
    $selected_subjects_arr = ($enrollment_data && $enrollment_data['selected_subjects']) ? json_decode($enrollment_data['selected_subjects'], true) : null;
    
    $stmt_offering_info = $pdo->prepare("
        SELECT c.class_name 
        FROM course_offerings co
        JOIN classes c ON co.class_id = c.class_id
        WHERE co.offering_id = ?
    ");
    $stmt_offering_info->execute([$offering_id]);
    $class_info = $stmt_offering_info->fetch();
    if (!$class_info) {
        die("خطأ: لم يتم العثور على المقرر.");
    }

    $stmt_subjects = $pdo->prepare("
        SELECT s.subject_id, s.subject_name, p.professor_name, os.offering_subject_id
        FROM offering_subjects os
        JOIN subjects s ON os.subject_id = s.subject_id
        LEFT JOIN professors p ON os.professor_id = p.professor_id
        WHERE os.offering_id = ?
        ORDER BY s.subject_name
    ");
    $stmt_subjects->execute([$offering_id]);
    $raw_subjects = $stmt_subjects->fetchAll(PDO::FETCH_ASSOC);

    $all_subjects = [];
    foreach ($raw_subjects as $subject) {
        if (is_array($selected_subjects_arr) && !empty($selected_subjects_arr)) {
            if (!in_array($subject['subject_id'], $selected_subjects_arr)) {
                continue; 
            }
        }
        $all_subjects[] = $subject;
    }

    $stmt_direct_lectures = $pdo->prepare("
        SELECT 
            NULL as offering_subject_id,
            l.lecture_id,
            COALESCE((SELECT MIN(l_c.lecture_id) FROM lectures l_c WHERE l_c.file_path = l.file_path), l.lecture_id) AS canonical_lecture_id,
            l.title, l.description, l.file_path, l.file_type, l.cover_image, l.upload_date,
            u.username as uploader_name,
            (SELECT COUNT(*) FROM content_reactions WHERE content_id = COALESCE((SELECT MIN(l_c.lecture_id) FROM lectures l_c WHERE l_c.file_path = l.file_path), l.lecture_id) AND content_type = 'lecture' AND reaction_type = 'like') as likes_count,
            (SELECT COUNT(*) FROM content_reactions WHERE content_id = COALESCE((SELECT MIN(l_c.lecture_id) FROM lectures l_c WHERE l_c.file_path = l.file_path), l.lecture_id) AND content_type = 'lecture' AND reaction_type = 'dislike') as dislikes_count,
            (SELECT COUNT(*) FROM comments WHERE content_id = COALESCE((SELECT MIN(l_c.lecture_id) FROM lectures l_c WHERE l_c.file_path = l.file_path), l.lecture_id) AND content_type = 'lecture') as comments_count,
            (SELECT reaction_type FROM content_reactions WHERE content_id = COALESCE((SELECT MIN(l_c.lecture_id) FROM lectures l_c WHERE l_c.file_path = l.file_path), l.lecture_id) AND content_type = 'lecture' AND user_id = :user_id LIMIT 1) as user_reaction
        FROM lectures l
        LEFT JOIN users u ON l.uploader_user_id = u.user_id
        WHERE l.offering_id = :offering_id 
          AND l.offering_subject_id IS NULL 
          AND l.status = 'approved' 
          AND (l.academic_year_id = :year_id OR l.academic_year_id IS NULL OR l.academic_year_id = 0)
        ORDER BY l.upload_date DESC
    ");
    $stmt_direct_lectures->execute(['user_id' => $user_id, 'offering_id' => $offering_id, 'year_id' => $current_year_id]);
    $direct_lectures = $stmt_direct_lectures->fetchAll(PDO::FETCH_ASSOC);

    $stmt_subject_lectures = $pdo->prepare("
        SELECT 
            os.offering_subject_id,
            l.lecture_id,
            COALESCE((SELECT MIN(l_c.lecture_id) FROM lectures l_c WHERE l_c.file_path = l.file_path), l.lecture_id) AS canonical_lecture_id,
            l.title, l.description, l.file_path, l.file_type, l.cover_image, l.upload_date,
            u.username as uploader_name,
            (SELECT COUNT(*) FROM content_reactions WHERE content_id = COALESCE((SELECT MIN(l_c.lecture_id) FROM lectures l_c WHERE l_c.file_path = l.file_path), l.lecture_id) AND content_type = 'lecture' AND reaction_type = 'like') as likes_count,
            (SELECT COUNT(*) FROM content_reactions WHERE content_id = COALESCE((SELECT MIN(l_c.lecture_id) FROM lectures l_c WHERE l_c.file_path = l.file_path), l.lecture_id) AND content_type = 'lecture' AND reaction_type = 'dislike') as dislikes_count,
            (SELECT COUNT(*) FROM comments WHERE content_id = COALESCE((SELECT MIN(l_c.lecture_id) FROM lectures l_c WHERE l_c.file_path = l.file_path), l.lecture_id) AND content_type = 'lecture') as comments_count,
            (SELECT reaction_type FROM content_reactions WHERE content_id = COALESCE((SELECT MIN(l_c.lecture_id) FROM lectures l_c WHERE l_c.file_path = l.file_path), l.lecture_id) AND content_type = 'lecture' AND user_id = :user_id LIMIT 1) as user_reaction
        FROM lectures l
        LEFT JOIN users u ON l.uploader_user_id = u.user_id
        JOIN offering_subjects os ON l.offering_subject_id = os.offering_subject_id
        WHERE os.offering_id = :offering_id 
          AND l.status = 'approved' 
          AND (l.academic_year_id = :year_id OR l.academic_year_id IS NULL OR l.academic_year_id = 0)
        ORDER BY l.upload_date DESC
    ");
    $stmt_subject_lectures->execute(['user_id' => $user_id, 'offering_id' => $offering_id, 'year_id' => $current_year_id]);
    $subject_lectures = $stmt_subject_lectures->fetchAll(PDO::FETCH_ASSOC);

    $subjects_with_lectures = [];

    foreach ($all_subjects as $subject) {
        $subject_key = $subject['subject_name'] . ' - ' . ($subject['professor_name'] ?? 'غير محدد');
        $subjects_with_lectures[$subject_key] = [
            'subject_info' => $subject,
            'lectures' => []
        ];
    }

    foreach ($subject_lectures as $lecture) {
        foreach ($all_subjects as $subject) {
            if ($subject['offering_subject_id'] == $lecture['offering_subject_id']) {
                $subject_key = $subject['subject_name'] . ' - ' . ($subject['professor_name'] ?? 'غير محدد');
                $subjects_with_lectures[$subject_key]['lectures'][] = $lecture;
                break;
            }
        }
    }

    if (!empty($direct_lectures)) {
        $subject_key = 'مواد أخرى - غير محدد';
        if (!isset($subjects_with_lectures[$subject_key])) {
            $subjects_with_lectures[$subject_key] = [
                'subject_info' => [
                    'subject_name' => 'مواد أخرى',
                    'professor_name' => 'غير محدد',
                    'offering_subject_id' => null
                ],
                'lectures' => []
            ];
        }
        $subjects_with_lectures[$subject_key]['lectures'] = array_merge($subjects_with_lectures[$subject_key]['lectures'], $direct_lectures);
    }

    $first_subject_title = !empty($subjects_with_lectures) ? array_key_first($subjects_with_lectures) : 'المواد الدراسية';

} catch (PDOException $e) {
    die("خطأ في جلب البيانات: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="image/svg+xml" href="/mo7adaraty/assets/images/favicon.svg">
  <title>محاضرات <?php echo htmlspecialchars($class_info['class_name']); ?> - Mo7adaraty</title>
  <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  
  <link rel="stylesheet" href="../assets/css/global.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="../assets/css/pages/class_lectures.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="/mo7adaraty/comments/assets/css/comments.css?v=<?php echo time(); ?>">

  <style>
    *, *::before, *::after {
        box-sizing: border-box !important;
    }

    body {
        overflow-x: hidden !important;
        width: 100% !important;
        -webkit-font-smoothing: antialiased !important;
    }

    .cust-icon-sm {
        width: 16px !important;
        height: 16px !important;
        max-width: 16px !important;
        max-height: 16px !important;
        object-fit: contain !important;
        flex-shrink: 0 !important;
        display: inline-block !important;
    }

    .cust-icon-md {
        width: 22px !important;
        height: 22px !important;
        max-width: 22px !important;
        max-height: 22px !important;
        object-fit: contain !important;
        flex-shrink: 0 !important;
        display: inline-block !important;
    }

    .container {
        width: 100% !important;
        max-width: 1320px !important;
        margin: 0 auto !important;
        padding: 16px 20px !important;
        box-sizing: border-box !important;
    }

    .page-header {
        position: relative !important;
        width: 100% !important;
        padding: 24px 26px !important;
        border-radius: 20px !important;
        margin-bottom: 22px !important;
        overflow: hidden !important;
        background: var(--bg-surface, #ffffff) !important;
        border: 1px solid var(--border-color, #e2e8f0) !important;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.03) !important;
    }

    .header-top {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        flex-wrap: wrap !important;
        gap: 16px !important;
        width: 100% !important;
    }

    .page-header-title h1 {
        margin: 0 !important;
        font-size: clamp(19px, 2.8vw, 25px) !important;
        font-weight: 800 !important;
        display: flex !important;
        align-items: center !important;
        gap: 10px !important;
        color: var(--text-primary) !important;
    }

    .year-badge {
        font-size: 12px !important;
        font-weight: 700 !important;
        padding: 4px 12px !important;
        border-radius: 20px !important;
        background: rgba(26, 115, 232, 0.12) !important;
        color: var(--brand-primary, #1a73e8) !important;
        border: 1px solid rgba(26, 115, 232, 0.2) !important;
    }

    .header-actions {
        display: flex !important;
        align-items: center !important;
        gap: 10px !important;
        flex-wrap: wrap !important;
    }

    .back-link, .upload-btn {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 8px !important;
        padding: 10px 18px !important;
        border-radius: 12px !important;
        font-size: 13.5px !important;
        font-weight: 700 !important;
        text-decoration: none !important;
        min-height: 42px !important;
        cursor: pointer !important;
    }

    .back-link {
        background: var(--bg-surface, #ffffff) !important;
        color: var(--text-primary) !important;
        border: 1px solid var(--border-color, #e2e8f0) !important;
    }

    .upload-btn {
        background: #10b981 !important;
        color: #ffffff !important;
        border: none !important;
    }

    .class-nav {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 8px !important;
        background: var(--bg-surface, #ffffff) !important;
        padding: 8px 12px !important;
        border-radius: 14px !important;
        border: 1px solid var(--border-color, #e2e8f0) !important;
        margin-top: 18px !important;
        width: 100% !important;
    }

    .class-nav a {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 8px !important;
        padding: 9px 18px !important;
        border-radius: 10px !important;
        font-size: 13.5px !important;
        font-weight: 700 !important;
        color: var(--text-secondary, #64748b) !important;
        text-decoration: none !important;
        min-height: 40px !important;
        cursor: pointer !important;
    }

    .class-nav a.active {
        background: var(--brand-primary, #1a73e8) !important;
        color: #ffffff !important;
    }

    .class-nav a img {
        width: 20px !important;
        height: 20px !important;
        max-width: 20px !important;
        max-height: 20px !important;
        object-fit: contain !important;
    }

    .content-wrapper {
        display: flex !important;
        gap: 22px !important;
        align-items: flex-start !important;
        width: 100% !important;
    }

    .mobile-subjects-toggle-btn {
        display: none;
        width: 100%;
        background: var(--brand-primary, #1a73e8) !important;
        color: #ffffff !important;
        border: none !important;
        border-radius: 14px !important;
        padding: 13px 18px !important;
        font-size: 14.5px !important;
        font-weight: 800 !important;
        cursor: pointer !important;
        align-items: center !important;
        justify-content: space-between !important;
        margin-bottom: 16px !important;
    }

    .subjects-sidebar {
        flex: 0 0 280px !important;
        width: 280px !important;
        background: var(--bg-surface, #ffffff) !important;
        border: 1px solid var(--border-color, #e2e8f0) !important;
        border-radius: 18px !important;
        padding: 16px !important;
        display: flex !important;
        flex-direction: column !important;
        gap: 8px !important;
        position: sticky !important;
        top: 20px !important;
        max-height: calc(100vh - 40px) !important;
        overflow-y: auto !important;
    }

    .sidebar-header {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        padding-bottom: 12px !important;
        border-bottom: 1px solid var(--border-color, #e2e8f0) !important;
        margin-bottom: 4px !important;
    }

    .sidebar-title {
        display: flex !important;
        align-items: center !important;
        gap: 10px !important;
        font-size: 14.5px !important;
        font-weight: 800 !important;
        color: var(--text-primary) !important;
    }

    .sidebar-title img {
        width: 24px !important;
        height: 24px !important;
        max-width: 24px !important;
        max-height: 24px !important;
        object-fit: contain !important;
    }

    .subject-link {
        display: flex !important;
        align-items: center !important;
        gap: 10px !important;
        padding: 11px 13px !important;
        border-radius: 10px !important;
        font-size: 13px !important;
        font-weight: 600 !important;
        color: var(--text-secondary, #64748b) !important;
        text-decoration: none !important;
        background: var(--bg-body, #f8fafc) !important;
        border: 1px solid transparent !important;
        min-height: 42px !important;
        cursor: pointer !important;
    }

    .subject-link:hover {
        color: var(--brand-primary, #1a73e8) !important;
        background: var(--bg-surface, #ffffff) !important;
        border-color: var(--border-color, #e2e8f0) !important;
    }

    .subject-link.active {
        background: rgba(26, 115, 232, 0.08) !important;
        color: var(--brand-primary, #1a73e8) !important;
        border-color: rgba(26, 115, 232, 0.28) !important;
        font-weight: 800 !important;
    }

    .subject-link img {
        width: 18px !important;
        height: 18px !important;
        max-width: 18px !important;
        max-height: 18px !important;
        object-fit: contain !important;
    }

    .lectures-main-content {
        flex: 1 !important;
        min-width: 0 !important;
        width: 100% !important;
    }

    .view-options {
        display: inline-flex !important;
        align-items: center !important;
        background: var(--bg-surface, #ffffff) !important;
        border: 1px solid var(--border-color, #e2e8f0) !important;
        border-radius: 10px !important;
        padding: 3px !important;
        gap: 3px !important;
    }

    .view-btn {
        background: transparent !important;
        border: none !important;
        border-radius: 7px !important;
        width: 34px !important;
        height: 34px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        cursor: pointer !important;
    }

    .view-btn.active {
        background: var(--brand-primary, #1a73e8) !important;
        color: #ffffff !important;
    }

    .view-btn img {
        width: 16px !important;
        height: 16px !important;
        max-width: 16px !important;
        max-height: 16px !important;
        object-fit: contain !important;
    }

    .lectures-grid {
        display: grid !important;
        grid-template-columns: repeat(auto-fill, minmax(285px, 1fr)) !important;
        gap: 20px !important;
        width: 100% !important;
    }

    .lecture-card {
        background: var(--bg-surface, #ffffff) !important;
        border: 1px solid var(--border-color, #e2e8f0) !important;
        border-radius: 18px !important;
        overflow: hidden !important;
        display: flex !important;
        flex-direction: column !important;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.04) !important;
        width: 100% !important;
    }

    .lecture-card .lecture-cover {
        width: 100% !important;
        height: 215px !important;
        min-height: 215px !important;
        background: #0f172a !important;
        position: relative !important;
        overflow: hidden !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        cursor: pointer !important;
    }

    .lecture-card .lecture-cover img.actual-cover {
        width: 100% !important;
        height: 100% !important;
        object-fit: cover !important;
    }

    .lecture-card .file-type-pill {
        position: absolute !important;
        bottom: 10px !important;
        right: 10px !important;
        background: rgba(15, 23, 42, 0.85) !important;
        color: #ffffff !important;
        padding: 3px 8px !important;
        border-radius: 6px !important;
        font-size: 11px !important;
        font-weight: 800 !important;
        z-index: 2 !important;
    }

    .lecture-content-area {
        padding: 16px !important;
        display: flex !important;
        flex-direction: column !important;
        gap: 12px !important;
        flex: 1 !important;
    }

    .lecture-title {
        margin: 0 0 6px 0 !important;
        font-size: 15.5px !important;
        font-weight: 800 !important;
        color: var(--text-primary) !important;
    }

    .uploader-meta {
        display: flex !important;
        align-items: center !important;
        gap: 8px 12px !important;
        font-size: 12px !important;
        color: var(--text-secondary) !important;
        flex-wrap: wrap !important;
    }

    .lecture-description {
        margin: 0 !important;
        font-size: 12.5px !important;
        line-height: 1.6 !important;
        color: var(--text-secondary) !important;
        background: var(--bg-body, #f8fafc) !important;
        padding: 8px 12px !important;
        border-radius: 8px !important;
        border: 1px dashed var(--border-color, #e2e8f0) !important;
        max-height: 120px !important;
        overflow-y: auto !important;
    }

    .lecture-card .card-footer {
        display: flex !important;
        flex-direction: column !important;
        gap: 10px !important;
        padding-top: 12px !important;
        border-top: 1px solid var(--border-color, #e2e8f0) !important;
        margin-top: auto !important;
    }

    .lecture-card .action-buttons, .lecture-card .reaction-buttons {
        display: flex !important;
        gap: 8px !important;
        width: 100% !important;
    }

    .lecture-card .action-btn {
        flex: 1 !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 6px !important;
        padding: 9px 8px !important;
        border-radius: 10px !important;
        font-size: 12.5px !important;
        font-weight: 700 !important;
        text-decoration: none !important;
        cursor: pointer !important;
        min-height: 38px !important;
        border: 1px solid var(--border-color, #e2e8f0) !important;
    }

    .view-btn-action { background: var(--brand-primary, #1a73e8) !important; color: #ffffff !important; }
    .download-btn-action { background: #10b981 !important; color: #ffffff !important; }
    .report-btn-action { background: var(--bg-body, #f8fafc) !important; color: #ef4444 !important; flex: 0 0 auto !important; padding: 9px 12px !important; }

    .lecture-card .reaction-btn {
        flex: 1 !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 6px !important;
        padding: 7px 10px !important;
        border-radius: 10px !important;
        background: var(--bg-body, #f8fafc) !important;
        border: 1px solid var(--border-color, #e2e8f0) !important;
        color: var(--text-primary) !important;
        font-size: 12.5px !important;
        font-weight: 700 !important;
        min-height: 36px !important;
        cursor: pointer !important;
    }

    .lecture-card .reaction-btn.active {
        background: rgba(26, 115, 232, 0.12) !important;
        border-color: var(--brand-primary, #1a73e8) !important;
        color: var(--brand-primary, #1a73e8) !important;
    }

    .comments-section { width: 100% !important; }

    .comments-toggle {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        padding: 9px 12px !important;
        background: var(--bg-body, #f8fafc) !important;
        border-radius: 10px !important;
        cursor: pointer !important;
        font-size: 13px !important;
        font-weight: 700 !important;
        border: 1px solid var(--border-color, #e2e8f0) !important;
        min-height: 38px !important;
    }

    .comments-toggle img {
        width: 18px !important;
        height: 18px !important;
        max-width: 18px !important;
        max-height: 18px !important;
        object-fit: contain !important;
    }

    .comments-count {
        background: var(--brand-primary, #1a73e8) !important;
        color: #ffffff !important;
        padding: 2px 8px !important;
        border-radius: 12px !important;
        font-size: 11px !important;
        font-weight: 700 !important;
    }

    .comment-form {
        display: flex !important;
        flex-direction: column !important;
        gap: 8px !important;
        margin-top: 10px !important;
        width: 100% !important;
    }

    .comment-form textarea {
        width: 100% !important;
        padding: 10px 12px !important;
        border-radius: 10px !important;
        border: 1px solid var(--border-color, #e2e8f0) !important;
        background: var(--bg-surface, #ffffff) !important;
        color: var(--text-primary) !important;
        font-family: inherit !important;
        font-size: 13px !important;
        resize: vertical !important;
        min-height: 68px !important;
        outline: none !important;
    }

    .comment-form button[type="submit"] {
        align-self: flex-end !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 6px !important;
        background: var(--brand-primary, #1a73e8) !important;
        color: #ffffff !important;
        border: none !important;
        border-radius: 8px !important;
        padding: 8px 16px !important;
        font-size: 12.5px !important;
        font-weight: 700 !important;
        cursor: pointer !important;
        min-height: 36px !important;
    }

    .comment-form button[type="submit"] img {
        width: 16px !important;
        height: 16px !important;
        max-width: 16px !important;
        max-height: 16px !important;
        object-fit: contain !important;
    }

    .lectures-grid.list-view { grid-template-columns: 1fr !important; }
    .lectures-grid.list-view .lecture-card { flex-direction: row !important; }
    .lectures-grid.list-view .lecture-card .lecture-cover { width: 280px !important; min-width: 280px !important; height: auto !important; min-height: 220px !important; }

    /* النوافذ المنبثقة مع عزل أمان العرض */
    .modal {
        position: fixed !important;
        inset: 0 !important;
        z-index: 9999 !important;
        background: rgba(15, 23, 42, 0.65) !important;
        backdrop-filter: blur(5px);
        padding: 16px !important;
        align-items: center !important;
        justify-content: center !important;
    }

    .modal-content {
        background: var(--bg-surface, #ffffff) !important;
        border: 1px solid var(--border-color, #e2e8f0) !important;
        border-radius: 20px !important;
        width: 100% !important;
        max-width: 480px !important;
        padding: 24px !important;
        position: relative !important;
    }

    .modal-header {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        padding-bottom: 14px !important;
        border-bottom: 1px solid var(--border-color, #e2e8f0) !important;
        margin-bottom: 16px !important;
    }

    .close-btn {
        background: transparent !important;
        border: none !important;
        font-size: 26px !important;
        cursor: pointer !important;
    }

    .preview-modal-custom {
        position: fixed;
        inset: 0;
        z-index: 999999;
        align-items: center;
        justify-content: center;
        padding: 16px;
        background: rgba(0, 0, 0, 0.85);
        backdrop-filter: blur(4px);
    }
    .preview-dialog-custom {
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
    }
    .preview-dialog-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 18px;
        border-bottom: 1px solid var(--border-color);
        background: var(--bg-body);
    }
    .preview-dialog-body {
        flex: 1;
        width: 100%;
        height: 100%;
        background: #0f172a;
    }
    .preview-dialog-body iframe {
        width: 100%;
        height: 100%;
        border: none;
        display: block;
    }

    @media (max-width: 768px) {
        .mobile-subjects-toggle-btn { display: flex !important; }
        .subjects-sidebar { display: none !important; width: 100% !important; max-width: 100% !important; position: static !important; }
        .subjects-sidebar.show-mobile { display: flex !important; }
        .lectures-grid { grid-template-columns: 1fr !important; }
        .lectures-grid.list-view .lecture-card { flex-direction: column !important; }
        .lectures-grid.list-view .lecture-card .lecture-cover { width: 100% !important; height: 200px !important; }
    }
  </style>
</head>
<body style="background-color: var(--bg-color); color: var(--text-primary); transition: background-color var(--transition-normal), color var(--transition-normal);">

<script>
    (function() {
        const savedTheme = localStorage.getItem('mo7adaraty_theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
    })();
</script>

<?php require_once '../templates/header.php'; ?>

<div class="container">
  <header class="page-header">
    <div class="header-top">
      <div class="page-header-title">
        <h1>
            <img src="<?php echo getDesignIcon('cl_icon_school', 'includesimg/class_lectures/icon_school.svg'); ?>" alt="icon" class="cust-icon-md">
            <span>محاضرات <?php echo htmlspecialchars($class_info['class_name']); ?></span>
            <span class="year-badge">موسم <?php echo htmlspecialchars($current_year_label); ?></span>
        </h1>
      </div>
      <div class="header-actions">
        <a href="profil.php" class="back-link">
            <img src="<?php echo getDesignIcon('cl_icon_back', 'includesimg/class_lectures/icon_back.svg'); ?>" alt="back" class="cust-icon-sm"> العودة إلى ملفي
        </a>
        <a href="upload_lecture.php?id=<?php echo $offering_id; ?>" class="upload-btn">
          <img src="<?php echo getDesignIcon('cl_icon_upload', 'includesimg/class_lectures/icon_upload.svg'); ?>" alt="upload" class="cust-icon-sm"> ارفع محاضرة
        </a>
      </div>
    </div>
    
    <nav class="class-nav">
      <a href="class_lectures.php?id=<?php echo $offering_id; ?>" class="active">
        <img src="<?php echo getDesignIcon('cl_icon_nav_lectures', 'includesimg/class_lectures/icon_nav_lectures.svg'); ?>" alt="nav"> المحاضرات
      </a>
      <a href="class_exams.php?id=<?php echo $offering_id; ?>">
        <img src="<?php echo getDesignIcon('cl_icon_nav_exams', 'includesimg/class_lectures/icon_nav_exams.svg'); ?>" alt="nav"> الامتحانات
      </a>
    </nav>
  </header>

  <?php if (isset($_GET['upload_status']) && $_GET['upload_status'] === 'success'): ?>
    <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid #10b981; color: #059669; padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-weight: bold; font-size: 13px;">
      <img src="<?php echo getDesignIcon('cl_icon_upload', 'includesimg/class_lectures/icon_upload.svg'); ?>" alt="success" class="cust-icon-sm">
      <span>تم رفع المحاضرة بنجاح إلى Google Drive وهي قيد المراجعة حالياً من قبل الإدارة لنشرها للعموم.</span>
    </div>
  <?php endif; ?>
  
  <button type="button" class="mobile-subjects-toggle-btn" id="mobileSubjectsBtn">
    <span style="display: flex; align-items: center; gap: 10px;">
      <i class="fa-solid fa-book-open"></i>
      <span id="currentSubjectName"><?php echo htmlspecialchars($first_subject_title); ?></span>
    </span>
    <i class="fa-solid fa-chevron-down chevron-icon"></i>
  </button>

  <div class="content-wrapper">
    <aside class="subjects-sidebar" id="subjectsSidebar">
      <div class="sidebar-header">
        <div class="sidebar-title">
          <img src="<?php echo getDesignIcon('cl_icon_sidebar', 'includesimg/class_lectures/icon_sidebar.svg'); ?>" alt="sidebar">
          <span>المواد الدراسية المختارة</span>
        </div>
      </div>
      
      <?php if (empty($subjects_with_lectures)): ?>
        <div style="text-align: center; padding: 20px; color: var(--text-secondary); font-size: 13px;">
          <p>لا توجد مواد متاحة لهذا المقرر.</p>
        </div>
      <?php else: ?>
        <?php $is_first_tab = true; ?>
        <?php foreach ($subjects_with_lectures as $subject_key => $subject_data): ?>
          <a href="javascript:void(0);" class="subject-link <?php if($is_first_tab) echo 'active'; ?>" data-tab="tab-<?php echo md5($subject_key); ?>">
            <img src="<?php echo getDesignIcon('cl_icon_subject', 'includesimg/class_lectures/icon_subject.svg'); ?>" alt="subject">
            <span><?php echo htmlspecialchars($subject_key); ?></span>
          </a>
          <?php $is_first_tab = false; ?>
        <?php endforeach; ?>
      <?php endif; ?>
    </aside>

    <main class="lectures-main-content">
      <?php if (empty($subjects_with_lectures)): ?>
        <div style="text-align: center; padding: 40px; background: var(--bg-surface); border-radius: 16px; border: 1px dashed var(--border-color); color: var(--text-secondary);">
          <p>لا يوجد محتوى لعرضه حالياً.</p>
        </div>
      <?php else: ?>
        <?php $is_first_content = true; ?>
        <?php foreach ($subjects_with_lectures as $subject_key => $subject_data): ?>
          <div id="tab-<?php echo md5($subject_key); ?>" class="tab-content <?php if($is_first_content) echo 'active'; ?>" style="<?php echo $is_first_content ? '' : 'display: none;'; ?>">
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; margin-bottom: 16px;">
              <h2 style="margin: 0; font-size: 18px; font-weight: 800; display: flex; align-items: center; gap: 8px; color: var(--text-primary);">
                <img src="<?php echo getDesignIcon('cl_icon_subject', 'includesimg/class_lectures/icon_subject.svg'); ?>" alt="subject" class="cust-icon-md">
                <span><?php echo htmlspecialchars($subject_key); ?></span>
              </h2>
              <div class="view-options">
                <button type="button" class="view-btn grid-view active" data-view="grid" title="عرض شبكي">
                  <img src="<?php echo getDesignIcon('cl_icon_grid', 'includesimg/class_lectures/icon_grid.svg'); ?>" alt="grid" class="cust-icon-sm">
                </button>
                <button type="button" class="view-btn list-view" data-view="list" title="عرض قائمة">
                  <img src="<?php echo getDesignIcon('cl_icon_list', 'includesimg/class_lectures/icon_list.svg'); ?>" alt="list" class="cust-icon-sm">
                </button>
              </div>
            </div>
            
            <div class="lectures-grid grid-view">
              <?php if (empty($subject_data['lectures'])): ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 40px 20px; background: var(--bg-surface); border-radius: 16px; border: 1px dashed var(--border-color); color: var(--text-secondary);">
                  <img src="<?php echo getDesignIcon('cl_icon_empty', 'includesimg/class_lectures/icon_empty.svg'); ?>" alt="empty" style="width: 50px; height: 50px; opacity: 0.5; margin-bottom: 10px;">
                  <p style="margin: 0; font-size: 14px; font-weight: 600;">لا توجد محاضرات لهذه المادة في هذا الموسم بعد.</p>
                </div>
              <?php else: ?>
                <?php foreach ($subject_data['lectures'] as $lecture): ?>
                  <?php
                    $target_lecture_id = $lecture['canonical_lecture_id'] ?? $lecture['lecture_id'];
                    $is_drive = (strpos($lecture['file_path'], 'http://') === 0 || strpos($lecture['file_path'], 'https://') === 0);
                    $view_url = $is_drive ? getDriveEmbedUrl($lecture['file_path']) : "../" . $lecture['file_path'];
                    $download_url = $is_drive ? getDriveDownloadUrl($lecture['file_path']) : "../download.php?type=lecture&id=" . $target_lecture_id;

                    $cover_src = getLectureCoverSrc($lecture, '../');
                    $has_cover = !empty($cover_src);
                  ?>
                  <div class="lecture-card" id="lecture-<?php echo $lecture['lecture_id']; ?>" data-lecture-canonical-id="<?php echo $target_lecture_id; ?>">
                    
                    <div class="lecture-cover" onclick="openPreviewModal('<?php echo htmlspecialchars($view_url); ?>', '<?php echo htmlspecialchars(addslashes($lecture['title'])); ?>')">
                      <?php if ($has_cover): ?>
                        <img src="<?php echo htmlspecialchars($cover_src); ?>" 
                             alt="<?php echo htmlspecialchars($lecture['title']); ?>" 
                             class="actual-cover" 
                             loading="lazy" 
                             onerror="this.style.display='none';">
                        <span class="file-type-pill"><?php echo strtoupper($lecture['file_type'] ?: 'PDF'); ?></span>
                      <?php else: ?>
                        <div class="lecture-cover-placeholder">
                          <span style="font-size: 13px; font-weight: bold; color: rgba(255,255,255,0.7); display: flex; align-items: center; gap: 6px;">
                            <i class="fa-solid fa-eye"></i> انقر للمعاينة
                          </span>
                          <span class="file-type-pill"><?php echo strtoupper($lecture['file_type'] ?: 'PDF'); ?></span>
                        </div>
                      <?php endif; ?>
                    </div>
                    
                    <div class="lecture-content-area">
                      <div class="card-header">
                        <div class="header-main-info">
                          <h3 class="lecture-title"><?php echo htmlspecialchars($lecture['title']); ?></h3>
                          <div class="uploader-meta">
                            <span><i class="fa-solid fa-user-pen"></i> تم النشر: <strong><?php echo htmlspecialchars($lecture['uploader_name']); ?></strong></span>
                            <span><i class="fa-regular fa-clock"></i> <?php echo date('Y-m-d', strtotime($lecture['upload_date'])); ?></span>
                          </div>
                        </div>
                      </div>

                      <?php if(!empty($lecture['description'])): ?>
                      <div class="card-body">
                        <p class="lecture-description"><?php echo nl2br(htmlspecialchars($lecture['description'])); ?></p>
                      </div>
                      <?php endif; ?>

                      <div class="card-footer">
                        <div class="action-buttons">
                          <button type="button" class="action-btn view-btn-action" onclick="openPreviewModal('<?php echo htmlspecialchars($view_url); ?>', '<?php echo htmlspecialchars(addslashes($lecture['title'])); ?>')">
                            <img src="<?php echo getDesignIcon('cl_icon_view', 'includesimg/class_lectures/icon_view.svg'); ?>" alt="view" class="cust-icon-sm"> معاينة
                          </button>
                          <a href="<?php echo htmlspecialchars($download_url); ?>" <?php if($is_drive) echo 'target="_blank" download'; ?> class="action-btn download-btn-action">
                            <img src="<?php echo getDesignIcon('cl_icon_download', 'includesimg/class_lectures/icon_download.svg'); ?>" alt="download" class="cust-icon-sm"> تحميل
                          </a>
                          <button type="button" class="action-btn report-btn-action" data-content-id="<?php echo $target_lecture_id; ?>" data-content-type="lecture">
                            <img src="<?php echo getDesignIcon('cl_icon_report', 'includesimg/class_lectures/icon_report.svg'); ?>" alt="report" class="cust-icon-sm"> إبلاغ
                          </button>
                        </div>

                        <div class="reaction-buttons">
                          <button type="button" class="reaction-btn like <?php if($lecture['user_reaction'] == 'like') echo 'active'; ?>" data-content-id="<?php echo $target_lecture_id; ?>" data-content-type="lecture">
                            <img src="<?php echo getDesignIcon('cl_icon_like', 'includesimg/class_lectures/icon_like.svg'); ?>" alt="like" class="cust-icon-sm">
                            <span class="reaction-count likes-count"><?php echo $lecture['likes_count']; ?></span>
                          </button>
                          <button type="button" class="reaction-btn dislike <?php if($lecture['user_reaction'] == 'dislike') echo 'active'; ?>" data-content-id="<?php echo $target_lecture_id; ?>" data-content-type="lecture">
                            <img src="<?php echo getDesignIcon('cl_icon_dislike', 'includesimg/class_lectures/icon_dislike.svg'); ?>" alt="dislike" class="cust-icon-sm">
                            <span class="reaction-count dislikes-count"><?php echo $lecture['dislikes_count']; ?></span>
                          </button>
                        </div>
                      </div>
                      
                      <div class="comments-section">
                        <div class="comments-toggle" data-lecture-id="<?php echo $lecture['lecture_id']; ?>">
                          <span style="display:flex; align-items:center; gap:8px;">
                            <img src="<?php echo getDesignIcon('cl_icon_comments', 'includesimg/class_lectures/icon_comments.svg'); ?>" alt="comments" class="cust-icon-sm"> التعليقات
                          </span>
                          <span class="comments-count"><?php echo $lecture['comments_count']; ?></span>
                        </div>
                        <div class="comments-content" id="comments-<?php echo $lecture['lecture_id']; ?>" style="display: none; margin-top: 10px;">
                          <div class="comments-container" data-content-id="<?php echo $target_lecture_id; ?>" data-content-type="lecture"></div>
                          <form class="comment-form" action="/mo7adaraty/comments/add_comment.php" method="POST">
                            <input type="hidden" name="content_id" value="<?php echo $target_lecture_id; ?>">
                            <input type="hidden" name="content_type" value="lecture">
                            <textarea name="comment_text" placeholder="اكتب تعليقك هنا..." required></textarea>
                            <button type="submit">
                              <img src="<?php echo getDesignIcon('cl_icon_send', 'includesimg/class_lectures/icon_send.svg'); ?>" alt="send" class="cust-icon-sm"> إرسال
                            </button>
                          </form>
                        </div>
                      </div>

                    </div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
          <?php $is_first_content = false; ?>
        <?php endforeach; ?>
      <?php endif; ?>
    </main>
  </div>
  
  <div id="reportModal" class="modal" style="display: none;">
    <div class="modal-content">
      <div class="modal-header">
        <h2>
            <img src="<?php echo getDesignIcon('cl_icon_report', 'includesimg/class_lectures/icon_report.svg'); ?>" alt="report" class="cust-icon-md"> الإبلاغ عن محتوى
        </h2>
        <button class="close-btn" type="button" aria-label="إغلاق">&times;</button>
      </div>
      <div class="modal-body">
        <form id="reportForm" action="../ajax/submit_report.php" method="POST">
          <input type="hidden" name="content_id" id="report_content_id">
          <input type="hidden" name="content_type" id="report_content_type">
          <div class="form-group" style="margin-bottom: 12px;">
            <label for="reason" style="display: block; margin-bottom: 6px; font-weight: bold;">سبب البلاغ:</label>
            <textarea id="reason" name="reason" placeholder="يرجى توضيح سبب الإبلاغ بدقة..." required style="width: 100%; min-height: 90px; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color);"></textarea>
          </div>
          <button type="submit" class="upload-btn" style="width:100%; justify-content:center;">
            <img src="<?php echo getDesignIcon('cl_icon_send', 'includesimg/class_lectures/icon_send.svg'); ?>" alt="send" class="cust-icon-sm"> إرسال البلاغ
          </button>
        </form>
      </div>
    </div>
  </div>

  <div id="downloadModal" class="modal" style="display: none;">
    <div class="modal-content" style="text-align: center; max-width: 400px;">
      <button class="close-btn" type="button" style="position: absolute; left: 16px; top: 12px;">&times;</button>
      <h2 style="font-size: 18px; font-weight: bold; margin-bottom: 15px; color: var(--text-primary); display:flex; align-items:center; justify-content:center; gap:8px;">
          <img src="<?php echo getDesignIcon('cl_icon_download', 'includesimg/class_lectures/icon_download.svg'); ?>" alt="download" class="cust-icon-md"> جاري تجهيز الملف...
      </h2>
      <div style="font-size: 36px; font-weight: 900; color: var(--brand-primary, #1a73e8); margin: 15px 0;">
        <span id="countdownNumber">8</span>
      </div>
      <p style="color: var(--text-secondary); line-height: 1.6; margin: 0; font-size: 13px;">سيبدأ التحميل بعد ثوانٍ قليلة... لا تنسونا بصالح الدعاء</p>
    </div>
  </div>
  
  <div id="previewModalCustom" class="preview-modal-custom" style="display: none;">
      <div class="preview-dialog-custom">
          <div class="preview-dialog-bar">
              <h3 id="previewModalTitle" style="margin: 0; font-size: 15px; font-weight: 700; color: var(--text-primary); display: flex; align-items: center; gap: 8px;">
                  <i class="fa-solid fa-file-pdf" style="color: #ef4444;"></i>
                  <span>معاينة المحاضرة</span>
              </h3>
              <button type="button" onclick="closePreviewModal()" style="background: none; border: none; font-size: 24px; color: var(--text-secondary); cursor: pointer; line-height: 1;">&times;</button>
          </div>
          <div class="preview-dialog-body">
              <iframe id="previewModalIframe" src="" allow="autoplay"></iframe>
          </div>
      </div>
  </div>

</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    window.commentsLang = {
        edit_prompt: 'تعديل التعليق:',
        error_update: 'حدث خطأ أثناء التعديل',
        server_error: 'خطأ في الاتصال بالخادم',
        confirm_delete: 'هل أنت متأكد من حذف هذا التعليق نهائياً؟',
        error_delete: 'تعذر حذف التعليق',
        server_error_delete: 'خطأ في الخادم'
    };

    function openPreviewModal(url, title) {
        if (!url) return;
        document.querySelector('#previewModalTitle span').innerText = title;
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
<script src="../assets/js/class_lectures.js?v=<?php echo time(); ?>"></script>
<script src="/mo7adaraty/comments/assets/js/comments.js?v=<?php echo time(); ?>"></script>

<?php include '../templates/footer.php'; ?>

</body>
</html>