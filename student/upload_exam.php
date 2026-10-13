<?php
session_start();
require_once '../config/db_connexion.php';

// 1. حماية الصفحة
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];$offering_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$offering_id) {
    header("Location: profil.php?error=" . urlencode("رابط المقرر غير صحيح"));
    exit();
}

try {
    // جلب الأيقونات الموحدة من الإدارة
    $stmt_design =$pdo->prepare("SELECT asset_key, file_path FROM design_assets WHERE page_section = 'class_lectures'");
    $stmt_design->execute();$design_settings = [];
    while ($row = $stmt_design->fetch(PDO::FETCH_ASSOC)) {$design_settings[$row['asset_key']] =$row['file_path'];
    }

    function getDesignIcon($key,$default_path) {
        global $design_settings;
        return !empty($design_settings[$key]) ? '../' .$design_settings[$key] : '../' .$default_path;
    }

    // 2. التحقق من تسجيل الطالب في هذا المقرر
    $stmt_check =$pdo->prepare("SELECT COUNT(*) FROM student_enrollments WHERE user_id = ? AND offering_id = ?");
    $stmt_check->execute([$user_id,$offering_id]);
    
if ($stmt_check->fetchColumn() == 0 && (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin')) {        header("Location: profil.php?error=" . urlencode("ليس لديك صلاحية لرفع ملفات لهذا المقرر"));
        exit();
    }

    // 3. جلب تفاصيل المقرر الدراسي
    $stmt_class =$pdo->prepare("
        SELECT c.class_name, d.division_name, g.group_name, t.track_name
        FROM course_offerings co
        JOIN classes c ON co.class_id = c.class_id
        JOIN divisions d ON co.division_id = d.division_id
        JOIN `groups` g ON co.group_id = g.group_id
        LEFT JOIN tracks t ON co.track_id = t.track_id
        WHERE co.offering_id = ?
    ");
    $stmt_class->execute([$offering_id]);
    $course_details =$stmt_class->fetch(PDO::FETCH_ASSOC);

    $class_name =$course_details['class_name'] ?? 'الفصل';
    $subtitle_parts = array_filter([
        $course_details['division_name'] ?? '',$class_name,
        $course_details['track_name'] ?? '',$course_details['group_name'] ?? ''
    ]);
    $full_course_title = implode(' - ',$subtitle_parts);

    // 4. جلب المواد المرتبطة بهذا المقرر لتعبئة القائمة المنسدلة
    $stmt_subjects =$pdo->prepare("
        SELECT os.offering_subject_id, s.subject_name, p.professor_name
        FROM offering_subjects os
        JOIN subjects s ON os.subject_id = s.subject_id
        LEFT JOIN professors p ON os.professor_id = p.professor_id
        WHERE os.offering_id = ?
        ORDER BY s.subject_name
    ");
    $stmt_subjects->execute([$offering_id]);
    $subjects =$stmt_subjects->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("خطأ في الاتصال بقاعدة البيانات.");
}

$error_message = null;
if (isset($_GET['error'])) {
    $error_message =$_GET['error'];
} elseif (isset($_SESSION['upload_error'])) {
    $error_message =$_SESSION['upload_error'];
    unset($_SESSION['upload_error']);
}

$success_message = null;
if (isset($_GET['success'])) {
    $success_message =$_GET['success'];
} elseif (isset($_SESSION['upload_success'])) {
    $success_message =$_SESSION['upload_success'];
    unset($_SESSION['upload_success']);
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>رفع امتحان - Mo7adaraty</title>
    <link rel="icon" type="image/svg+xml" href="/mo7adaraty/assets/images/favicon.svg">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;900&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/pages/upload_exam.css">
</head>
<body>

    <script>
        (function() {
            const savedTheme = localStorage.getItem('mo7adaraty_theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>

    <?php require_once '../templates/header.php'; ?>

    <main class="upload-page-wrapper">
        <div class="upload-card">
            
            <div class="upload-header">
                <a href="class_exams.php?id=<?= $offering_id ?>" class="back-btn">
                    <i class="fa-solid fa-arrow-right"></i> عودة
                </a>
                <h1>
                    <img src="<?php echo getDesignIcon('cl_icon_upload', 'includesimg/class_lectures/icon_upload.png'); ?>" alt="upload" class="upload-header-icon">
                    رفع نموذج امتحان
                </h1>
                <p class="upload-subtitle"><?php echo htmlspecialchars($full_course_title); ?></p>
            </div>

            <div class="upload-body">
                
                <?php if ($error_message): ?>
                    <div class="alert-box alert-error">
                        <i class="fa-solid fa-circle-exclamation"></i> 
                        <span><?php echo htmlspecialchars($error_message); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($success_message): ?>
                    <div class="alert-box alert-success" style="background: rgba(16, 185, 129, 0.15); border: 1px solid #10b981; color: #059669; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                        <i class="fa-solid fa-circle-check"></i> 
                        <span><?php echo htmlspecialchars($success_message); ?></span>
                    </div>
                <?php endif; ?>

                <form id="examUploadForm" action="upload_exam_process.php?id=<?= $offering_id ?>" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="offering_id" value="<?php echo $offering_id; ?>">

                    <div class="form-group">
                        <label for="subject_id" class="form-label">المادة الدراسية <span style="color:var(--danger-color)">*</span></label>
                        <select name="subject_id" id="subject_id" class="form-control" required>
                            <option value="" disabled selected>-- اختر المادة الخاصة بالامتحان --</option>
                            <?php foreach ($subjects as$sub): ?>
                                <option value="<?php echo $sub['offering_subject_id']; ?>">
                                    <?php echo htmlspecialchars($sub['subject_name'] . ' - أ.' . ($sub['professor_name'] ?? 'غير محدد')); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="title" class="form-label">عنوان الامتحان <span style="color:var(--danger-color)">*</span></label>
                        <input type="text" id="title" name="title" class="form-control" placeholder="مثال: امتحان الدورة العادية 2025" required>
                    </div>

                    <div class="form-group">
                        <label for="exam_date" class="form-label">تاريخ إجراء الامتحان (اختياري)</label>
                        <input type="date" id="exam_date" name="exam_date" class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="description" class="form-label">ملاحظات (اختياري)</label>
                        <textarea id="description" name="description" class="form-control" placeholder="اكتب أي ملاحظات تخص الامتحان أو طبيعة الأسئلة..."></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">ملف الامتحان <span style="color:var(--danger-color)">*</span></label>
                        
                        <div class="drag-drop-zone" id="examDropZone">
                            <img src="<?php echo getDesignIcon('cl_icon_pdf', 'includesimg/class_lectures/icon_pdf.png'); ?>" alt="file" class="drag-icon">
                            <div class="drag-text">اضغط هنا أو اسحب الامتحان للإفلات</div>
                            <div class="drag-hint">الصيغ المدعومة: PDF, JPG, PNG, DOC (الرفع السحابي المباشر عبر Google Drive)</div>
                            <input type="file" id="exam_file" name="exam_file" required>
                        </div>
                        
                        <div class="file-preview" id="examPreview">
                            <img src="<?php echo getDesignIcon('cl_icon_pdf', 'includesimg/class_lectures/icon_pdf.png'); ?>" alt="file" class="file-preview-icon">
                            <div class="file-preview-info">
                                <span class="file-name" id="examFileName">اسم الملف</span>
                                <span class="file-size" id="examFileSize">0 KB</span>
                            </div>
                            <button type="button" class="remove-file-btn" id="removeExamBtn" title="إزالة الملف">
                                <i class="fa-solid fa-times"></i>
                            </button>
                        </div>

                        <!-- إشعار استخراج الغلاف التلقائي -->
                        <div id="autoCoverNotice" style="display: none; margin-top: 10px; font-size: 13px; color: #059669; background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 8px; padding: 8px 12px; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-wand-magic-sparkles"></i>
                            <span>تم التقاط الصفحة الأولى من الامتحان كصورة غلاف تلقائياً وسيتم رفعها إلى Google Drive.</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">صورة غلاف الامتحان (توليد تلقائي للـ PDF أو رفع مخصص)</label>
                        
                        <div class="drag-drop-zone" id="coverDropZone" style="padding: 25px 20px;">
                            <img src="<?php echo getDesignIcon('cl_icon_image', 'includesimg/class_lectures/icon_image.png'); ?>" alt="image" class="drag-icon" style="width: 40px; height: 40px; margin-bottom: 10px;">
                            <div class="drag-text" style="font-size: 15px;">اضغط لاختيار صورة غلاف مخصصة (اختياري)</div>
                            <div class="drag-hint">الصيغ المدعومة: JPG, PNG, WEBP (يتم استخراج الصفحة الأولى تلقائياً عند اختيار PDF)</div>
                            <input type="file" id="cover_image" name="cover_image" accept="image/*">
                        </div>
                        
                        <div class="file-preview" id="coverPreview">
                            <img src="<?php echo getDesignIcon('cl_icon_image', 'includesimg/class_lectures/icon_image.png'); ?>" alt="image" id="coverPreviewImg" class="file-preview-icon" style="border-radius: 4px; object-fit: cover;">
                            <div class="file-preview-info">
                                <span class="file-name" id="coverFileName">اسم الصورة</span>
                                <span class="file-size" id="coverFileSize">0 KB</span>
                            </div>
                            <button type="button" class="remove-file-btn" id="removeCoverBtn" title="إزالة الصورة">
                                <i class="fa-solid fa-times"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" id="submitExamBtn" class="submit-btn">
                        <img src="<?php echo getDesignIcon('cl_icon_upload', 'includesimg/class_lectures/icon_upload.png'); ?>" alt="upload">
                        <span>رفع الامتحان وبدء المراجعة</span>
                    </button>
                </form>

            </div>
        </div>
    </main>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script src="../assets/js/upload_exam.js"></script>
    <script>
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

        const examInput = document.getElementById('exam_file');
        const coverInput = document.getElementById('cover_image');
        const coverDropZone = document.getElementById('coverDropZone');
        const coverPreview = document.getElementById('coverPreview');
        const coverPreviewImg = document.getElementById('coverPreviewImg');
        const coverFileName = document.getElementById('coverFileName');
        const coverFileSize = document.getElementById('coverFileSize');
        const autoNotice = document.getElementById('autoCoverNotice');

        // التقاط الصفحة الأولى تلقائياً وتحويلها لغلاف عند اختيار ملف PDF
        examInput.addEventListener('change', async function(e) {
            const file = e.target.files[0];
            if (!file) return;

            const isPdf = file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf');
            if (!isPdf) {
                if (autoNotice) autoNotice.style.display = 'none';
                return;
            }

            try {
                const arrayBuffer = await file.arrayBuffer();
                const pdf = await pdfjsLib.getDocument({ data: arrayBuffer }).promise;
                const page = await pdf.getPage(1);

                const viewport = page.getViewport({ scale: 1.5 });
                const canvas = document.createElement('canvas');
                const context = canvas.getContext('2d');
                canvas.width = viewport.width;
                canvas.height = viewport.height;

                await page.render({ canvasContext: context, viewport: viewport }).promise;

                canvas.toBlob(function(blob) {
                    if (!blob) return;

                    const autoCoverName = "cover_exam_" + file.name.replace(/\.[^/.]+$/, "") + ".webp";
                    const generatedFile = new File([blob], autoCoverName, { type: "image/webp" });

                    const dt = new DataTransfer();
                    dt.items.add(generatedFile);
                    coverInput.files = dt.files;

                    if (coverPreviewImg) coverPreviewImg.src = canvas.toDataURL('image/webp');
                    if (coverFileName) coverFileName.textContent = autoCoverName + " (تلقائي)";
                    if (coverFileSize) coverFileSize.textContent = (blob.size / 1024).toFixed(1) + " KB";

                    if (coverDropZone) coverDropZone.style.display = 'none';
                    if (coverPreview) coverPreview.style.display = 'flex';
                    if (autoNotice) autoNotice.style.display = 'flex';
                }, 'image/webp', 0.88);

            } catch (err) {
                console.warn("تعذر استخراج الصفحة الأولى من ملف الامتحان:", err);
            }
        });

        // زر إزالة الغلاف المخصص
        const removeCoverBtn = document.getElementById('removeCoverBtn');
        if (removeCoverBtn) {
            removeCoverBtn.addEventListener('click', function() {
                coverInput.value = '';
                if (coverPreview) coverPreview.style.display = 'none';
                if (coverDropZone) coverDropZone.style.display = 'block';
                if (autoNotice) autoNotice.style.display = 'none';
            });
        }

        // إظهار نص التحميل عند الإرسال
        document.getElementById('examUploadForm').addEventListener('submit', function() {
            const btn = document.getElementById('submitExamBtn');
            btn.disabled = true;
            btn.style.opacity = '0.8';
            btn.querySelector('span').textContent = 'جاري الرفع السحابي إلى Google Drive... يرجى الانتظار';
        });
    </script>
    <?php require_once '../templates/footer.php'; ?>

</body>
</html>