<?php
session_start();
require_once '../config/db_connexion.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
$user_id  = $_SESSION['user_id'];

$offering_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$offering_id) {
    header("Location: profil.php?error=رابط المقرر غير صحيح");
    exit();
}

try {
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

    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM student_enrollments WHERE user_id = ? AND offering_id = ?");
    $stmt_check->execute([$user_id, $offering_id]);
    if ($stmt_check->fetchColumn() == 0 && (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin')) {
        header("Location: profil.php?error=ليس لديك صلاحية الوصول لهذا المقرر");
        exit();
    }
    
    $stmt_offering_info = $pdo->prepare("
        SELECT c.class_id, c.class_name 
        FROM course_offerings co
        JOIN classes c ON co.class_id = c.class_id
        WHERE co.offering_id = ?
    ");
    $stmt_offering_info->execute([$offering_id]);
    $class_info = $stmt_offering_info->fetch(PDO::FETCH_ASSOC);
    if (!$class_info) {
        die("خطأ: لم يتم العثور على المقرر.");
    }
    $current_class_id = $class_info['class_id'];

    $stmt_exams = $pdo->prepare("
        SELECT 
            e.exam_id, e.title, e.description, e.file_path, e.file_type, e.upload_date,
            s.subject_name,
            p.professor_name,
            u.username as uploader_name,
            (SELECT COUNT(*) FROM content_reactions WHERE content_id=e.exam_id AND content_type='exam' AND reaction_type='like') as likes_count,
            (SELECT COUNT(*) FROM content_reactions WHERE content_id=e.exam_id AND content_type='exam' AND reaction_type='dislike') as dislikes_count,
            (SELECT COUNT(*) FROM comments WHERE content_id = e.exam_id AND content_type = 'exam') as comments_count,
            (SELECT reaction_type FROM content_reactions WHERE content_id=e.exam_id AND content_type='exam' AND user_id = :user_id) as user_reaction
        FROM exams e
        JOIN users u ON e.uploader_user_id = u.user_id
        LEFT JOIN offering_subjects os ON e.offering_subject_id = os.offering_subject_id
        LEFT JOIN subjects s ON os.subject_id = s.subject_id
        LEFT JOIN professors p ON os.professor_id = p.professor_id
        WHERE e.class_id = :class_id AND e.academic_year_id = :year_id
        ORDER BY e.upload_date DESC
    ");
    $stmt_exams->execute(['user_id' => $user_id, 'class_id' => $current_class_id, 'year_id' => $current_year_id]);
    $exams = $stmt_exams->fetchAll(PDO::FETCH_ASSOC);

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
  <title>امتحانات <?php echo htmlspecialchars($class_info['class_name']); ?> - Mo7adaraty</title>
  <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
  
  <link rel="stylesheet" href="../assets/css/global.css">
  <link rel="stylesheet" href="../assets/css/pages/class_exams.css">
  <link rel="stylesheet" href="/mo7adaraty/comments/assets/css/comments.css">

  <style>
    .page-header::before {
        background-image: url('<?php echo getDesignIcon("cl_bg_header", "includesimg/class_lectures/bg_header.png"); ?>');
        background-size: cover;
        background-position: center;
        opacity: 0.15;
        z-index: 0;
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
            امتحانات <?php echo htmlspecialchars($class_info['class_name']); ?>
            <span class="year-badge">موسم <?php echo htmlspecialchars($current_year_label); ?></span>
        </h1>
      </div>
      <div class="header-actions">
        <a href="profil.php" class="back-link">
            <img src="<?php echo getDesignIcon('cl_icon_back', 'includesimg/class_lectures/icon_back.svg'); ?>" alt="back" class="cust-icon-sm"> العودة لملفي الشخصي
        </a>
        <a href="upload_exam.php?id=<?php echo $offering_id; ?>" class="upload-btn">
          <img src="<?php echo getDesignIcon('cl_icon_upload', 'includesimg/class_lectures/icon_upload.svg'); ?>" alt="upload" class="cust-icon-sm"> ارفع امتحان
        </a>
      </div>
    </div>
   <?php include 'nav-bar.php'; ?>
  </header>
  
  <main class="lectures-main-content">
      <?php if (empty($exams)): ?>
          <div class="empty-state">
              <img src="<?php echo getDesignIcon('cl_icon_empty', 'includesimg/class_lectures/icon_empty.png'); ?>" alt="empty" class="cust-icon-xl">
              <p>لا توجد امتحانات منشورة لهذا الفصل في هذا الموسم حالياً.</p>
          </div>
      <?php else: ?>
          <div class="lectures-grid">
              <?php foreach ($exams as $exam): ?>
                  <?php
                    $file_icon_key = 'cl_icon_default';
                    $default_file_icon = 'includesimg/class_lectures/icon_default.svg';
                    
                    if ($exam['file_type'] == 'pdf') { $file_icon_key = 'cl_icon_pdf'; $default_file_icon = 'includesimg/class_lectures/icon_pdf.svg'; }
                    elseif (in_array($exam['file_type'], ['jpg', 'jpeg', 'png'])) { $file_icon_key = 'cl_icon_image'; $default_file_icon = 'includesimg/class_lectures/icon_image.svg'; }
                    elseif (in_array($exam['file_type'], ['doc', 'docx'])) { $file_icon_key = 'cl_icon_word'; $default_file_icon = 'includesimg/class_lectures/icon_word.svg'; }
                    elseif (in_array($exam['file_type'], ['xls', 'xlsx'])) { $file_icon_key = 'cl_icon_excel'; $default_file_icon = 'includesimg/class_lectures/icon_excel.svg'; }
                    
                    $file_icon_src = getDesignIcon($file_icon_key, $default_file_icon);
                  ?>
                  <div class="lecture-card" id="exam-<?php echo $exam['exam_id']; ?>">
                      
                      <div class="lecture-cover">
                        <div class="lecture-cover-placeholder" data-lecture-id="<?php echo $exam['exam_id']; ?>">
                          <span class="file-type"><?php echo strtoupper($exam['file_type']); ?></span>
                          <div class="lecture-cover-placeholder-content">
                            <img src="<?php echo $file_icon_src; ?>" alt="file" class="cust-icon-xl">
                            <h3 class="placeholder-title"><?php echo htmlspecialchars($exam['title']); ?></h3>
                            <p class="placeholder-info">تم النشر بواسطة : <?php echo htmlspecialchars($exam['uploader_name']); ?></p>
                          </div>
                        </div>
                      </div>

                      <div class="card-header">
                          <img src="<?php echo $file_icon_src; ?>" alt="file" class="cust-icon-lg">
                          <div class="header-info">
                              <h3><?php echo htmlspecialchars($exam['title']); ?></h3>
                              <p>تم النشر بواسطة: <strong><?php echo htmlspecialchars($exam['uploader_name']); ?></strong></p>
                              <p style="margin-top: 2px;">
                                المادة: <strong><?php echo htmlspecialchars($exam['subject_name'] ?? 'مادة غير محددة'); ?></strong>
                              </p>
                          </div>
                      </div>
                      <?php if(!empty($exam['description'])): ?>
                      <div class="card-body">
                          <p><?php echo nl2br(htmlspecialchars($exam['description'])); ?></p>
                      </div>
                      <?php endif; ?>
                      
                      <div class="card-footer">
                          <div class="action-buttons">
                              <a href="../<?php echo htmlspecialchars($exam['file_path']); ?>" target="_blank" class="action-btn view-btn-action">
                                  <img src="<?php echo getDesignIcon('cl_icon_view', 'includesimg/class_lectures/icon_view.svg'); ?>" alt="view" class="cust-icon-sm"> عرض
                              </a>
                              <a href="../download.php?type=exam&id=<?php echo $exam['exam_id']; ?>" class="action-btn download-btn-action">
                                  <img src="<?php echo getDesignIcon('cl_icon_download', 'includesimg/class_lectures/icon_download.svg'); ?>" alt="download" class="cust-icon-sm"> تحميل
                              </a>
                              <button class="action-btn report-btn-action" data-content-id="<?php echo $exam['exam_id']; ?>" data-content-type="exam">
                                  <img src="<?php echo getDesignIcon('cl_icon_report', 'includesimg/class_lectures/icon_report.svg'); ?>" alt="report" class="cust-icon-sm"> إبلاغ
                              </button>
                          </div>
                          <div class="reaction-buttons">
                              <button class="reaction-btn like <?php if($exam['user_reaction'] == 'like') echo 'active'; ?>" data-content-id="<?php echo $exam['exam_id']; ?>" data-content-type="exam">
                                  <img src="<?php echo getDesignIcon('cl_icon_like', 'includesimg/class_lectures/icon_like.svg'); ?>" alt="like" class="cust-icon-sm">
                                  <span class="reaction-count"><?php echo $exam['likes_count']; ?></span>
                              </button>
                              <button class="reaction-btn dislike <?php if($exam['user_reaction'] == 'dislike') echo 'active'; ?>" data-content-id="<?php echo $exam['exam_id']; ?>" data-content-type="exam">
                                  <img src="<?php echo getDesignIcon('cl_icon_dislike', 'includesimg/class_lectures/icon_dislike.svg'); ?>" alt="dislike" class="cust-icon-sm">
                                  <span class="reaction-count"><?php echo $exam['dislikes_count']; ?></span>
                              </button>
                          </div>
                      </div>

                      <div class="comments-section">
                          <div class="comments-toggle" data-exam-id="<?php echo $exam['exam_id']; ?>">
                              <span style="display:flex; align-items:center; gap:8px;">
                                  <img src="<?php echo getDesignIcon('cl_icon_comments', 'includesimg/class_lectures/icon_comments.svg'); ?>" alt="comments" class="cust-icon-sm"> التعليقات
                              </span>
                              <span class="comments-count"><?php echo $exam['comments_count']; ?></span>
                          </div>
                          <div class="comments-content" id="comments-<?php echo $exam['exam_id']; ?>">
                              <div class="comments-container" data-content-id="<?php echo $exam['exam_id']; ?>" data-content-type="exam"></div>
                              <form class="comment-form" action="/mo7adaraty/comments/add_comment.php" method="POST">
                                  <input type="hidden" name="content_id" value="<?php echo $exam['exam_id']; ?>">
                                  <input type="hidden" name="content_type" value="exam">
                                  <textarea name="comment_text" placeholder="اكتب تعليقك هنا..." required></textarea>
                                  <button type="submit">
                                      <img src="<?php echo getDesignIcon('cl_icon_send', 'includesimg/class_lectures/icon_send.svg'); ?>" alt="send" class="cust-icon-sm"> إرسال
                                  </button>
                              </form>
                          </div>
                      </div>
                  </div>
              <?php endforeach; ?>
          </div>
      <?php endif; ?>
  </main>
</div>

<div id="reportModal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <h2>
          <img src="<?php echo getDesignIcon('cl_icon_report', 'includesimg/class_lectures/icon_report.svg'); ?>" alt="report" class="cust-icon-md"> الإبلاغ عن محتوى
      </h2>
      <button class="close-btn" aria-label="إغلاق">&times;</button>
    </div>
    <div class="modal-body">
      <form id="reportForm" action="../ajax/submit_report.php" method="POST">
        <input type="hidden" name="content_id" id="report_content_id">
        <input type="hidden" name="content_type" id="report_content_type">
        <div class="form-group">
          <label for="reason">سبب البلاغ:</label>
          <textarea id="reason" name="reason" placeholder="يرجى توضيح سبب الإبلاغ بدقة..." required></textarea>
        </div>
        <button type="submit" class="upload-btn" style="width:100%; justify-content:center; margin-top:15px; background: var(--brand-primary);">
          <img src="<?php echo getDesignIcon('cl_icon_send', 'includesimg/class_lectures/icon_send.svg'); ?>" alt="send" class="cust-icon-sm"> إرسال البلاغ
        </button>
      </form>
    </div>
  </div>
</div>

<div id="downloadModal" class="modal">
  <div class="modal-content download-modal-content">
    <div class="modal-header" style="justify-content: center; border: none; padding-bottom: 0;">
      <button class="close-btn" style="position: absolute; right: 20px; top: 20px;" aria-label="إغلاق">&times;</button>
    </div>
    <div class="modal-body">
      <h2 style="font-size: 20px; font-weight: bold; margin-bottom: 10px; color: var(--text-primary); display:flex; align-items:center; justify-content:center; gap:8px;">
          <img src="<?php echo getDesignIcon('cl_icon_download', 'includesimg/class_lectures/icon_download.svg'); ?>" alt="download" class="cust-icon-md"> جاري تجهيز الملف...
      </h2>
      <div class="countdown-circle">
        <svg class="progress-ring" width="120" height="120">
          <circle class="progress-ring-circle" stroke="var(--brand-primary)" stroke-width="8" fill="transparent" r="52" cx="60" cy="60"/>
        </svg>
        <span class="countdown-number">8</span>
      </div>
      <p style="color: var(--text-secondary); line-height: 1.6;">سيبدأ التحميل بعد <span id="countdownText" style="font-weight:bold; color:var(--text-primary);">8</span> ثوانٍ...<br> لا تنسونا بصالح الدعاء </p>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="../assets/js/class_exams.js"></script>
<script src="/mo7adaraty/comments/assets/js/comments.js"></script>

<?php include '../templates/footer.php'; ?>

</body>
</html>