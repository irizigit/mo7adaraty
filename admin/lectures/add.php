<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية الصفحة والتحقق من الصلاحيات
if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

$breadcrumbs = [
    "إدارة المحتوى" => "",
    "المحاضرات" => "index.php",
    "رفع محاضرة جديدة" => ""
];

try {
    // 1. جلب الموسم الأكاديمي المفعل حالياً
    $stmt_year = $pdo->query("SELECT id, year_label FROM academic_years WHERE is_current = 1 LIMIT 1");
    $current_year = $stmt_year->fetch(PDO::FETCH_ASSOC);

    if (!$current_year) {
        die("خطأ: لا يوجد موسم أكاديمي مفعل في النظام. يرجى تفعيل موسم دراسي أولاً من إعدادات المواسم الجامعية.");
    }

    $current_year_id = (int)$current_year['id'];
    $current_year_label = $current_year['year_label'];

    // 2. جلب الشعب التي تملك فصولاً ومقررات نشطة في الموسم المفعل حصراً
    $stmt_divs = $pdo->prepare("
        SELECT DISTINCT d.division_id, d.division_name 
        FROM divisions d
        JOIN course_offerings co ON d.division_id = co.division_id
        WHERE co.academic_year_id = ? AND (co.status = 1 OR co.status = 'active')
        ORDER BY d.division_name ASC
    ");
    $stmt_divs->execute([$current_year_id]);
    $divisions = $stmt_divs->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("خطأ في قاعدة البيانات: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>رفع محاضرة جديدة - Mo7adaraty</title>
    <link rel="icon" type="image/svg+xml" href="/mo7adaraty/assets/images/favicon.svg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/admin_global.css">
    <link rel="stylesheet" href="../assets/css/admin_academic.css">
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
            
            <div class="academic-form-container skeleton" style="max-width: 800px;">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 15px; margin-bottom: 25px; flex-wrap: wrap; gap: 10px;">
                    <h2 style="margin: 0; color: var(--text-primary);">
                        <i class="fa-solid fa-cloud-arrow-up text-[var(--brand-primary)]"></i> رفع محاضرة جديدة
                    </h2>
                    <span style="background: rgba(16, 185, 129, 0.15); border: 1px solid #10b981; color: #059669; padding: 6px 14px; border-radius: 20px; font-weight: bold; font-size: 13px; display: inline-flex; align-items: center; gap: 6px;">
                        <i class="fa-regular fa-calendar-check"></i> الموسم المفعل: <?= htmlspecialchars($current_year_label) ?>
                    </span>
                </div>

                <?php if (isset($_GET['error'])): ?>
                    <div style="padding: 15px; background: rgba(220, 53, 69, 0.1); color: var(--danger-color); border: 1px solid var(--danger-color); border-radius: var(--radius-md); margin-bottom: 20px; font-weight: bold;">
                        <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($_GET['error']); ?>
                    </div>
                <?php endif; ?>

                <div style="padding: 15px; background: rgba(14, 165, 233, 0.1); color: var(--brand-primary); border: 1px solid var(--brand-primary); border-radius: var(--radius-md); margin-bottom: 20px; font-weight: bold; display: flex; align-items: center; gap: 10px;">
                    <i class="fa-solid fa-circle-info" style="font-size: 24px;"></i>
                    <span style="line-height: 1.5;"><strong>ميزة النشر الذكي (الشامل):</strong> سيتم ربط المحاضرة تلقائياً بموسم <strong><?= htmlspecialchars($current_year_label) ?></strong> ونشرها لجميع الأفواج التابعة لنفس (الفصل والمسار والمادة والأستاذ) داخل هذا الموسم المفعل فقط.</span>
                </div>

                <form id="lectureAdminForm" action="add_process.php" method="POST" enctype="multipart/form-data" data-context="lectures">
                    <input type="hidden" name="academic_year_id" value="<?= $current_year_id ?>">
                    
                    <div style="background: var(--bg-light); padding: 20px; border-radius: var(--radius-md); border: 1px dashed var(--border-color); margin-bottom: 25px;">
                        <h4 style="margin-top: 0; margin-bottom: 15px; color: var(--brand-primary); font-weight: 800;">
                            <i class="fa-solid fa-sitemap"></i> تحديد المسار الأكاديمي للمحاضرة (الموسم الحالي)
                        </h4>
                        
                        <div class="form-group">
                            <label class="form-label" for="division">1. اختر الشعبة: <span style="color:var(--danger-color)">*</span></label>
                            <select name="division" id="division" class="form-control" required>
                                <option value="" disabled selected>-- اختر الشعبة لتحديث الفصول المتاحة --</option>
                                <?php foreach ($divisions as $div): ?>
                                    <option value="<?php echo $div['division_id']; ?>"><?php echo htmlspecialchars($div['division_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="offering_id">2. اختر الفصل الدراسي / الفوج: <span style="color:var(--danger-color)">*</span></label>
                            <select name="offering_id" id="offering_id" class="form-control" required disabled>
                                <option value="" disabled selected>-- يرجى اختيار الشعبة أولاً --</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="offering_subject_id">3. اختر المادة الدراسية: <span style="color:var(--danger-color)">*</span></label>
                            <select name="offering_subject_id" id="offering_subject_id" class="form-control" required disabled>
                                <option value="" disabled selected>-- يرجى اختيار المقرر أولاً --</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="title">عنوان المحاضرة: <span style="color:var(--danger-color)">*</span></label>
                        <input type="text" name="title" id="title" class="form-control" required placeholder="مثال: مدخل إلى القانون الإداري - المحاضرة الأولى">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="description">وصف تفصيلي (اختياري):</label>
                        <textarea name="description" id="description" class="form-control" style="min-height: 100px; resize: vertical;" placeholder="اكتب نبذة مختصرة عن محاور المحاضرة..."></textarea>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 20px; border-top: 1px solid var(--border-color); padding-top: 20px;">
                        
                        <div class="form-group">
                            <label class="form-label" for="lecture_file">ملف المحاضرة الأساسي: <span style="color:var(--danger-color)">*</span></label>
                            <div style="background: rgba(26, 115, 232, 0.05); border: 1px dashed var(--brand-primary); padding: 15px; border-radius: var(--radius-sm); text-align: center;">
                                <i class="fa-solid fa-cloud-arrow-up" style="font-size: 26px; color: var(--brand-primary); margin-bottom: 10px;"></i>
                                <input type="file" name="lecture_file" id="lecture_file" class="form-control" required accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.mp4,.png,.jpg,.jpeg">
                                <small style="display: block; margin-top: 8px; color: var(--text-secondary); font-weight: bold;">(PDF, Word, PPT, MP4, الصور - الرفع سحابياً إلى Google Drive)</small>
                            </div>
                            <div id="adminAutoCoverNotice" style="display: none; margin-top: 10px; font-size: 13px; color: #059669; background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 8px; padding: 8px 12px; align-items: center; gap: 8px;">
                                <i class="fa-solid fa-wand-magic-sparkles"></i>
                                <span>تم استخراج الصفحة الأولى كصورة غلاف تلقائياً وسيتم رفعها إلى Google Drive.</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="cover_image">صورة الغلاف (توليد تلقائي للـ PDF أو رفع مخصص):</label>
                            <div style="background: var(--bg-light); border: 1px solid var(--border-color); padding: 15px; border-radius: var(--radius-sm);">
                                <input type="file" name="cover_image" id="cover_image" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp">
                                
                                <div id="cover_preview" style="display: none; margin-top: 15px; text-align: center; border-top: 1px solid var(--border-color); padding-top: 15px;">
                                    <p style="margin: 0 0 10px 0; font-size: 13px; font-weight: bold; color: var(--text-secondary);">معاينة الغلاف:</p>
                                    <img id="preview_img" src="" alt="Cover Preview" style="max-height: 120px; max-width: 100%; object-fit: contain; border-radius: 8px; box-shadow: var(--shadow-sm);">
                                </div>
                            </div>
                        </div>

                    </div>

                    <div style="display: flex; gap: 10px; margin-top: 35px;">
                        <button type="submit" id="submitAdminLectureBtn" class="btn-add-new" style="flex: 1; justify-content: center; background: var(--success-color); color: #fff !important; padding: 12px; font-size: 16px;">
                            <i class="fa-solid fa-cloud-arrow-up"></i> <span>رفع وحفظ المحاضرة</span>
                        </button>
                        <a href="index.php" class="btn-add-new" style="flex: 1; justify-content: center; background: var(--bg-surface); color: var(--text-primary) !important; border: 1px solid var(--border-color); padding: 12px; font-size: 16px;">
                            <i class="fa-solid fa-xmark"></i> إلغاء وعودة
                        </a>
                    </div>
                </form>
            </div>

        </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script src="../assets/js/admin_global.js"></script>
    <script src="lectures_script.js"></script>
    <script>
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

        const lectureFileInput = document.getElementById('lecture_file');
        const coverFileInput = document.getElementById('cover_image');
        const previewContainer = document.getElementById('cover_preview');
        const previewImg = document.getElementById('preview_img');
        const autoNotice = document.getElementById('adminAutoCoverNotice');

        lectureFileInput.addEventListener('change', async function(e) {
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

                    const autoCoverName = "cover_" + file.name.replace(/\.[^/.]+$/, "") + ".webp";
                    const generatedFile = new File([blob], autoCoverName, { type: "image/webp" });

                    const dt = new DataTransfer();
                    dt.items.add(generatedFile);
                    coverFileInput.files = dt.files;

                    if (previewImg) previewImg.src = canvas.toDataURL('image/webp');
                    if (previewContainer) previewContainer.style.display = 'block';
                    if (autoNotice) autoNotice.style.display = 'flex';
                }, 'image/webp', 0.88);

            } catch (err) {
                console.warn("تعذر التقاط الصفحة الأولى من الـ PDF:", err);
            }
        });

        coverFileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    previewImg.src = event.target.result;
                    previewContainer.style.display = 'block';
                }
                reader.readAsDataURL(file);
            } else {
                previewContainer.style.display = 'none';
                previewImg.src = '';
            }
        });

        document.getElementById('lectureAdminForm').addEventListener('submit', function() {
            const btn = document.getElementById('submitAdminLectureBtn');
            btn.disabled = true;
            btn.style.opacity = '0.8';
            btn.querySelector('span').textContent = 'جاري الرفع إلى Google Drive... يرجى الانتظار';
        });
    </script>
</body>
</html>