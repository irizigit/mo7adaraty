<?php
session_start();
require_once '../../config/db_connexion.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {    header("Location: ../../login.php");
    exit();
}

$breadcrumbs = [
    "إدارة المحتوى" => "",
    "الامتحانات" => "index.php",
    "رفع امتحان جديد" => ""
];

try {
    // 1. جلب الموسم الأكاديمي المفعل حالياً
    $stmt_year =$pdo->query("SELECT id, year_label FROM academic_years WHERE is_current = 1 LIMIT 1");
    $current_year =$stmt_year->fetch(PDO::FETCH_ASSOC);

    if (!$current_year) {
        die("خطأ: لا يوجد موسم أكاديمي مفعل في النظام. يرجى تفعيل موسم دراسي أولاً.");
    }

    $current_year_id = (int)$current_year['id'];
    $current_year_label =$current_year['year_label'];

    // 2. جلب الشعب المتاحة في الموسم الحالي حصراً
    $stmt_divs =$pdo->prepare("
        SELECT DISTINCT d.division_id, d.division_name 
        FROM divisions d
        JOIN course_offerings co ON d.division_id = co.division_id
        WHERE co.academic_year_id = ? AND (co.status = 1 OR co.status = 'active')
        ORDER BY d.division_name ASC
    ");
    $stmt_divs->execute([$current_year_id]);
    $divisions =$stmt_divs->fetchAll(PDO::FETCH_ASSOC);

    // خطة بديلة: إذا لم توجد شعب مسندة في المقررات، جلب كافة الشعب
    if (empty($divisions)) {
        $divisions =$pdo->query("SELECT division_id, division_name FROM divisions ORDER BY division_name ASC")->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    die("خطأ في قاعدة البيانات: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>رفع امتحان جديد - Mo7adaraty</title>
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
                        <i class="fa-solid fa-file-signature text-[var(--brand-primary)]"></i> رفع ونشر امتحان جديد
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

                <form action="add_process.php" method="POST" enctype="multipart/form-data" id="examAdminForm">
                    <input type="hidden" name="academic_year_id" value="<?= $current_year_id ?>">

                    <div style="background: var(--bg-light); padding: 20px; border-radius: var(--radius-md); border: 1px dashed var(--border-color); margin-bottom: 25px;">
                        <h4 style="margin-top: 0; margin-bottom: 15px; color: var(--brand-primary); font-weight: 800;">
                            <i class="fa-solid fa-sitemap"></i> التوجيه الأكاديمي الإلزامي (الموسم الحالي)
                        </h4>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div class="form-group">
                                <label class="form-label" for="division_id">1. اختر الشعبة: <span style="color:var(--danger-color)">*</span></label>
                                <select name="division_id" id="division_id" class="form-control" required>
                                    <option value="" disabled selected>-- اختر الشعبة --</option>
                                    <?php foreach ($divisions as$d): ?>
                                        <option value="<?php echo $d['division_id']; ?>"><?php echo htmlspecialchars($d['division_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="class_id">2. اختر الفصل: <span style="color:var(--danger-color)">*</span></label>
                                <select name="class_id" id="class_id" class="form-control" required disabled>
                                    <option value="" disabled selected>-- اختر الشعبة أولاً --</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div style="background: rgba(255, 193, 7, 0.05); padding: 20px; border-radius: var(--radius-md); border: 1px dashed var(--warning-color); margin-bottom: 25px;">
                        <h4 style="margin-top: 0; margin-bottom: 15px; color: var(--warning-color); font-weight: 800;">
                            <i class="fa-solid fa-filter"></i> التخصيص الدقيق (اختياري)
                        </h4>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div class="form-group">
                                <label class="form-label" for="offering_id">تحديد الفوج / المسار:</label>
                                <select name="offering_id" id="offering_id" class="form-control" disabled>
                                    <option value="">-- يرجى اختيار الشعبة والفصل أولاً --</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="offering_subject_id">تحديد المادة:</label>
                                <select name="offering_subject_id" id="offering_subject_id" class="form-control" disabled>
                                    <option value="">-- يرجى اختيار الفوج أولاً --</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="title">عنوان الامتحان: <span style="color:var(--danger-color)">*</span></label>
                        <input type="text" name="title" id="title" class="form-control" required placeholder="مثال: الدورة العادية 2026 - القانون الدستوري">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="description">ملاحظات أو وصف:</label>
                        <textarea name="description" id="description" class="form-control" style="min-height: 80px; resize: vertical;" placeholder="أضف أي ملاحظات تخص الامتحان أو طبيعة الأسئلة..."></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="exam_file">ملف الامتحان (PDF, Word, صور): <span style="color:var(--danger-color)">*</span></label>
                        <div style="background: rgba(26, 115, 232, 0.05); border: 1px dashed var(--brand-primary); padding: 15px; border-radius: var(--radius-sm); text-align: center;">
                            <i class="fa-solid fa-cloud-arrow-up" style="font-size: 26px; color: var(--brand-primary); margin-bottom: 10px;"></i>
                            <input type="file" name="exam_file" id="exam_file" class="form-control" required accept=".pdf,.doc,.docx,.png,.jpg,.jpeg">
                            <small style="display: block; margin-top: 8px; color: var(--text-secondary); font-weight: bold;">(تخزين سحابي مباشر على Google Drive)</small>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 15px;">
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; color: var(--text-primary); font-weight: bold;">
                            <input type="checkbox" name="send_notification" value="1" checked style="width: 18px; height: 18px; accent-color: var(--brand-primary);">
                            إرسال إشعار للطلاب المسجلين في هذا النطاق الأكاديمي.
                        </label>
                    </div>

                    <div style="display: flex; gap: 10px; margin-top: 35px;">
                        <button type="submit" id="submitExamBtn" class="btn-add-new" style="flex: 1; justify-content: center; background: var(--success-color); color: #fff !important; padding: 12px; font-size: 16px;">
                            <i class="fa-solid fa-cloud-arrow-up"></i> <span>رفع ونشر الامتحان</span>
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
    <script src="../assets/js/admin_global.js"></script>
    <script>
        $(document).ready(function() {
            const $divisionSelect =$('#division_id');
            const $classSelect =$('#class_id');
            const $offeringSelect =$('#offering_id');
            const $subjectSelect =$('#offering_subject_id');

            // 1. عند اختيار الشعبة: جلب الفصول وتفعيل الحقل فوراً
            $divisionSelect.on('change', function() {
                const divId = $(this).val();

                $classSelect.html('<option value="">جارٍ تحميل الفصول...</option>').prop('disabled', true);
                $offeringSelect.html('<option value="">-- يرجى اختيار الفصل أولاً --</option>').prop('disabled', true);
                $subjectSelect.html('<option value="">-- يرجى اختيار الفوج أولاً --</option>').prop('disabled', true);

                if (divId) {
                    $.post('get_data.php', { action: 'get_classes', division_id: divId }, function(data) {
                        $classSelect.html('<option value="" disabled selected>-- اختر الفصل --</option>');
                        if (data && data.length > 0) {
                            data.forEach(function(cls) {
                                $classSelect.append(`<option value="${cls.class_id}">${cls.class_name}</option>`);
                            });
                            $classSelect.prop('disabled', false);
                        } else {
                            $classSelect.html('<option value="" disabled selected>-- لا توجد فصول متاحة لهذا الموسم --</option>');
                        }
                    }, 'json').fail(function() {
                        $classSelect.html('<option value="" disabled selected>-- خطأ في الاتصال بالخادم --</option>');
                    });
                }
            });

            // 2. عند اختيار الفصل: جلب المقررات والأفواج
            $classSelect.on('change', function() {
                const divId = $divisionSelect.val();
                const clsId = $(this).val();

                $offeringSelect.html('<option value="">جارٍ التحميل...</option>').prop('disabled', true);
                $subjectSelect.html('<option value="">-- يرجى اختيار الفوج أولاً --</option>').prop('disabled', true);

                if (divId && clsId) {
                    $.post('get_data.php', { action: 'get_offerings', division_id: divId, class_id: clsId }, function(data) {
                        $offeringSelect.html('<option value="">-- اختياري: اختر الفوج/المسار --</option>');
                        if (data && data.length > 0) {
                            data.forEach(function(off) {
                                let trackInfo = off.track_name ? ` / مسار: ${off.track_name}` : '';
                                let groupInfo = off.group_name ? ` / فوج: ${off.group_name}` : '';
                                let displayText = `مقرر: ${off.class_name}${trackInfo}${groupInfo}`;
                                $offeringSelect.append(`<option value="${off.offering_id}">${displayText}</option>`);
                            });
                            $offeringSelect.prop('disabled', false);
                        } else {
                            $offeringSelect.html('<option value="">-- لا توجد أفواج مخصصة (نشر عام) --</option>').prop('disabled', false);
                        }
                    }, 'json').fail(function() {
                        $offeringSelect.html('<option value="">-- خطأ في الاتصال --</option>');
                    });
                }
            });

            // 3. عند اختيار الفوج: جلب المواد
            $offeringSelect.on('change', function() {
                const offId = $(this).val();$subjectSelect.html('<option value="">-- اختياري: اختر المادة --</option>').prop('disabled', true);

                if (offId) {
                    $subjectSelect.html('<option value="">جارٍ التحميل...</option>').prop('disabled', false);
                    $.post('get_data.php', { action: 'get_subjects', offering_id: offId }, function(data) {
                        $subjectSelect.html('<option value="">-- اختياري: اختر المادة --</option>');
                        if (data && data.length > 0) {
                            data.forEach(function(sub) {
                                $subjectSelect.append(`<option value="${sub.offering_subject_id}">${sub.subject_name}</option>`);
                            });
                            $subjectSelect.prop('disabled', false);
                        } else {
                            $subjectSelect.html('<option value="">-- لا توجد مواد مسندة --</option>');
                        }
                    }, 'json').fail(function() {
                        $subjectSelect.html('<option value="">-- خطأ في الاتصال --</option>');
                    });
                }
            });

            // إشعار التحميل عند الإرسال
            $('#examAdminForm').on('submit', function() {
                const btn = $('#submitExamBtn');
                btn.prop('disabled', true).css('opacity', '0.8');
                btn.find('span').text('جاري الرفع إلى Google Drive... يرجى الانتظار');
            });
        });
    </script>
</body>
</html>