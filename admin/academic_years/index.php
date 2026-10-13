<?php
session_start();
require_once '../../config/db_connexion.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

$breadcrumbs = [
    "الإعدادات" => "",
    "الأعوام الدراسية" => ""
];

try {
    $stmt_design = $pdo->query("SELECT asset_key, file_path FROM design_assets WHERE page_section = 'global_icons'");
    $design_settings = [];
    while ($row = $stmt_design->fetch(PDO::FETCH_ASSOC)) {
        $design_settings[$row['asset_key']] = $row['file_path'];
    }

    // جلب الأعوام الدراسية وترتيبها تنازلياً
    $stmt = $pdo->query("SELECT * FROM academic_years ORDER BY year_label DESC");
    $academic_years = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("خطأ في قاعدة البيانات: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الأعوام الدراسية - Mo7adaraty</title>
    <link rel="icon" type="image/svg+xml" href="/mo7adaraty/assets/images/favicon.svg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/admin_global.css">
    <link rel="stylesheet" href="../assets/css/admin_academic.css">
    <style>
        /* تمييز بصري لبطاقة العام الحالي */
        .card-current {
            border: 2px solid var(--success-color) !important;
            box-shadow: 0 0 15px rgba(40, 167, 69, 0.2) !important;
        }
        .badge-current {
            background-color: var(--success-color);
            color: #fff;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-top: 10px;
        }
        .badge-old {
            background-color: var(--bg-light);
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-top: 10px;
        }
        .btn-set-current {
            background: rgba(40, 167, 69, 0.1);
            color: var(--success-color);
            border-left: 1px solid var(--border-color);
        }
        .btn-set-current:hover {
            background: var(--success-color);
            color: #fff;
        }
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
            
            <div class="academic-header-actions skeleton">
                <h2 style="margin: 0; color: var(--text-primary); display: flex; align-items: center; gap: 10px;">
                    <img src="../dashboard_design/images/admin_icon_academic_years.png" alt="Years" style="width: 40px; height: 40px; object-fit: contain;">
                    الأعوام الدراسية
                </h2>
                <button class="btn-add-new" onclick="document.getElementById('addModal').style.display='block'">
                    <i class="fa-solid fa-plus"></i> إضافة عام دراسي جديد
                </button>
            </div>

            <?php if (isset($_SESSION['msg'])): ?>
                <div style="padding: 15px; background: rgba(40, 167, 69, 0.1); color: var(--success-color); border: 1px solid var(--success-color); border-radius: var(--radius-md); margin-bottom: 20px; font-weight: bold;">
                    <i class="fa-solid fa-circle-check"></i> <?php echo $_SESSION['msg']; unset($_SESSION['msg']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div style="padding: 15px; background: rgba(220, 53, 69, 0.1); color: var(--danger-color); border: 1px solid var(--danger-color); border-radius: var(--radius-md); margin-bottom: 20px; font-weight: bold;">
                    <i class="fa-solid fa-circle-exclamation"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <div class="academic-grid skeleton">
                <?php if (empty($academic_years)): ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 40px; background: var(--bg-surface); border-radius: var(--radius-md); border: 1px dashed var(--border-color); color: var(--text-secondary);">
                        <i class="fa-regular fa-calendar" style="font-size: 40px; margin-bottom: 15px; opacity: 0.5;"></i>
                        <p>لا توجد أعوام دراسية مضافة حتى الآن.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($academic_years as $year): ?>
                        <?php $is_current = ($year['is_current'] == 1); ?>
                        <div class="academic-card <?php echo $is_current ? 'card-current' : ''; ?>" id="year-card-<?php echo $year['id']; ?>">
                            <div class="card-img-wrapper" style="height: 140px;">
                                <img src="../dashboard_design/images/admin_icon_academic_years.png" alt="Year Icon" style="width: 80px; height: 80px; object-fit: contain;">
                            </div>
                            <div class="card-content" style="text-align: center;">
                                <h3 class="card-title" style="font-size: 24px;" dir="ltr"><?php echo htmlspecialchars($year['year_label']); ?></h3>
                                
                                <?php if ($is_current): ?>
                                    <div class="badge-current status-badge-ui"><i class="fa-solid fa-circle-check"></i> العام الحالي</div>
                                <?php else: ?>
                                    <div class="badge-old status-badge-ui"><i class="fa-solid fa-clock-rotate-left"></i> عام سابق</div>
                                <?php endif; ?>
                            </div>
                            <div class="card-actions">
                                <?php if (!$is_current): ?>
                                    <button class="btn-card-action btn-set-current" data-id="<?php echo $year['id']; ?>">
                                        <i class="fa-solid fa-power-off"></i> تفعيل
                                    </button>
                                <?php endif; ?>
                                
                                <a href="edit.php?id=<?php echo $year['id']; ?>" class="btn-card-action btn-edit">
                                    <i class="fa-solid fa-pen"></i> تعديل
                                </a>
                                <form action="delete_process.php" method="POST" style="flex: 1; display: flex;" onsubmit="return confirm('تحذير: سيتم حذف هذا العام وجميع بياناته المرتبطة نهائياً! هل أنت متأكد؟');">
                                    <input type="hidden" name="id" value="<?php echo $year['id']; ?>">
                                    <button type="submit" class="btn-card-action btn-delete" style="width: 100%;">
                                        <i class="fa-solid fa-trash"></i> حذف
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div>
    </main>

    <div id="addModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h2>إضافة عام دراسي جديد</h2>
                <button class="close-modal" onclick="document.getElementById('addModal').style.display='none'">&times;</button>
            </div>
            <div class="modal-body">
                <form action="add_process.php" method="POST">
                    <div class="form-group">
                        <label class="form-label" for="year_label">اسم العام (مثال: 2025-2026): <span style="color:var(--danger-color)">*</span></label>
                        <input type="text" name="year_label" id="year_label" class="form-control" dir="ltr" style="text-align: right;" required>
                    </div>
                    <button type="submit" class="btn-add-new" style="width: 100%; justify-content: center; margin-top: 15px;">
                        <i class="fa-solid fa-save"></i> حفظ العام الدراسي
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/admin_global.js"></script>
    <script>
        window.onclick = function(event) {
            var modal = document.getElementById('addModal');
            if (event.target == modal) { modal.style.display = "none"; }
        }

        // نظام الـ AJAX لتعيين العام الحالي بسلاسة
        $(document).ready(function() {
            $('.btn-set-current').on('click', function(e) {
                e.preventDefault();
                var btn = $(this);
                var yearId = btn.data('id');
                var originalHtml = btn.html();
                
                if (!confirm('هل أنت متأكد من تعيين هذا العام كالعام الدراسي الحالي للمنصة؟')) return;

                btn.html('<i class="fa-solid fa-spinner fa-spin"></i> جاري...').prop('disabled', true);

                $.ajax({
                    url: 'set_current.php',
                    type: 'POST',
                    data: { id: yearId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            location.reload(); // إعادة التحميل ليعكس التصميم الجديد بالكامل
                        } else {
                            alert(response.message);
                            btn.html(originalHtml).prop('disabled', false);
                        }
                    },
                    error: function() {
                        alert('حدث خطأ في الاتصال بالخادم.');
                        btn.html(originalHtml).prop('disabled', false);
                    }
                });
            });
        });
    </script>
</body>
</html>