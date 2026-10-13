<?php
session_start();
require_once '../../config/db_connexion.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

$breadcrumbs = [
    "التفاعل والمجتمع" => "",
    "آراء الطلاب والزوار" => ""
];

try {
    $stmt_design = $pdo->query("SELECT asset_key, file_path FROM design_assets WHERE page_section = 'global_icons'");
    $design_settings = [];
    while ($row = $stmt_design->fetch(PDO::FETCH_ASSOC)) {
        $design_settings[$row['asset_key']] = $row['file_path'];
    }

    // الاستعلام الذكي: جلب الآراء مع بيانات المستخدمين المسجلين وشعبهم إن وجدت
    $stmt = $pdo->query("
        SELECT r.*, 
               u.username, u.profile_picture,
               (SELECT d.division_name 
                FROM student_enrollments se 
                JOIN course_offerings co ON se.offering_id = co.offering_id 
                JOIN divisions d ON co.division_id = d.division_id 
                WHERE se.user_id = u.user_id LIMIT 1) as user_division
        FROM reviews r
        LEFT JOIN users u ON r.user_id = u.user_id
        ORDER BY r.created_at DESC
    ");
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("خطأ في قاعدة البيانات: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الآراء - Mo7adaraty</title>
    <link rel="icon" type="image/svg+xml" href="/mo7adaraty/assets/images/favicon.svg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/admin_global.css">
    
    <style>
        .reviews-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
        }

        .review-card {
            background: var(--bg-surface);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 25px;
            display: flex;
            flex-direction: column;
            gap: 15px;
            position: relative;
            box-shadow: var(--shadow-sm);
            transition: all var(--transition-normal);
            overflow: hidden;
            z-index: 1;
        }

        .review-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
            border-color: var(--brand-primary);
        }

        /* علامة الاقتباس المائية في الخلفية */
        .review-card::after {
            content: '\201D';
            position: absolute;
            top: -20px;
            left: 20px;
            font-size: 150px;
            color: var(--brand-light);
            opacity: 0.3;
            font-family: Georgia, serif;
            z-index: -1;
            line-height: 1;
        }

        /* رأس البطاقة: معلومات الكاتب */
        .review-header {
            display: flex;
            align-items: center;
            gap: 15px;
            border-bottom: 1px dashed var(--border-color);
            padding-bottom: 15px;
        }

        .reviewer-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--bg-light);
            box-shadow: var(--shadow-sm);
        }

        .reviewer-info h4 {
            margin: 0 0 5px 0;
            font-size: 16px;
            font-weight: 800;
            color: var(--text-primary);
        }

        .reviewer-info .meta-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .reviewer-badge {
            font-size: 11px;
            padding: 3px 8px;
            border-radius: 12px;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            width: fit-content;
        }

        .badge-registered { background: rgba(26, 115, 232, 0.1); color: var(--brand-primary); }
        .badge-guest { background: rgba(108, 117, 125, 0.1); color: var(--text-secondary); }

        .reviewer-division {
            font-size: 12px;
            color: var(--text-secondary);
            font-weight: 600;
        }

        /* نص الرأي */
        .review-body {
            font-size: 14px;
            line-height: 1.7;
            color: var(--text-primary);
            flex-grow: 1;
            position: relative;
            z-index: 2;
        }

        /* التذييل والأزرار */
        .review-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid var(--border-color);
            margin-top: auto;
        }

        .review-date {
            font-size: 12px;
            color: var(--text-secondary);
            font-weight: bold;
        }

        .review-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .btn-delete {
            color: var(--danger-color);
            background: rgba(220, 53, 69, 0.1);
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-delete:hover {
            background: var(--danger-color);
            color: #fff;
        }

        /* Toggle Switch للحالة */
        .status-toggle-wrapper {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            font-weight: bold;
            color: var(--text-secondary);
        }

        .switch { position: relative; display: inline-block; width: 40px; height: 22px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: var(--border-color); transition: .4s; border-radius: 22px; }
        .slider:before { position: absolute; content: ""; height: 16px; width: 16px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: var(--success-color); }
        input:checked + .slider:before { transform: translateX(18px); }
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
            
            <div class="academic-header-actions skeleton" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                <h2 style="margin: 0; color: var(--text-primary); display: flex; align-items: center; gap: 10px;">
                    <img src="../dashboard_design/images/admin_icon_reviews.png" alt="Reviews" style="width: 40px; height: 40px; object-fit: contain;">
                    آراء الطلاب والزوار
                </h2>
                <div style="background: var(--bg-surface); border: 1px solid var(--border-color); padding: 8px 15px; border-radius: var(--radius-pill); font-weight: bold; font-size: 14px; color: var(--text-secondary); box-shadow: var(--shadow-sm);">
                    إجمالي الآراء: <span style="color: var(--brand-primary);"><?php echo count($reviews); ?></span>
                </div>
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

            <div class="reviews-grid skeleton">
                <?php if (empty($reviews)): ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 50px; background: var(--bg-surface); border-radius: var(--radius-lg); border: 1px dashed var(--border-color); color: var(--text-secondary);">
                        <i class="fa-regular fa-comments" style="font-size: 50px; margin-bottom: 15px; opacity: 0.5;"></i>
                        <p style="font-size: 18px; font-weight: bold;">لا توجد آراء مسجلة حتى الآن.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($reviews as $review): ?>
                        <?php 
                            $is_registered = !empty($review['user_id']);
                            $display_name = $is_registered ? $review['username'] : $review['guest_name'];
                            $display_division = $is_registered ? ($review['user_division'] ?: 'لم يحدد الشعبة') : $review['guest_division'];
                            $avatar_src = ($is_registered && !empty($review['profile_picture'])) ? '../../uploads/profile_pictures/' . $review['profile_picture'] : '../../uploads/profile_pictures/default_profile.png';
                        ?>
                        
                        <div class="review-card">
                            <div class="review-header">
                                <img src="<?php echo htmlspecialchars($avatar_src); ?>" alt="Avatar" class="reviewer-avatar" onerror="this.src='../../uploads/profile_pictures/default_profile.png'">
                                <div class="reviewer-info">
                                    <h4><?php echo htmlspecialchars($display_name); ?></h4>
                                    <div class="meta-info">
                                        <?php if ($is_registered): ?>
                                            <span class="reviewer-badge badge-registered"><i class="fa-solid fa-user-check"></i> طالب مسجل</span>
                                        <?php else: ?>
                                            <span class="reviewer-badge badge-guest"><i class="fa-solid fa-user-clock"></i> زائر</span>
                                        <?php endif; ?>
                                        <span class="reviewer-division"><i class="fa-solid fa-layer-group"></i> <?php echo htmlspecialchars($display_division); ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="review-body">
                                "<?php echo nl2br(htmlspecialchars($review['review_text'])); ?>"
                            </div>
                            
                            <div class="review-footer">
                                <div class="review-date" dir="ltr">
                                    <i class="fa-regular fa-calendar"></i> <?php echo date('Y-m-d H:i', strtotime($review['created_at'])); ?>
                                </div>
                                
                                <div class="review-actions">
                                    <div class="status-toggle-wrapper" title="قبول الرأي ليظهر في الصفحة الرئيسية">
                                        <label class="switch">
                                            <input type="checkbox" class="toggle-status-btn" data-id="<?php echo $review['id']; ?>" <?php echo ($review['status'] === 'approved') ? 'checked' : ''; ?>>
                                            <span class="slider"></span>
                                        </label>
                                        <span class="status-text"><?php echo ($review['status'] === 'approved') ? 'مقبول' : 'مخفي'; ?></span>
                                    </div>

                                    <form action="delete_process.php" method="POST" style="margin: 0;" onsubmit="return confirm('هل أنت متأكد من حذف هذا الرأي نهائياً؟');">
                                        <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                        <button type="submit" class="btn-delete" title="حذف الرأي">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/admin_global.js"></script>
    <script>
        // AJAX لتغيير حالة الرأي (قبول / إخفاء)
        $(document).ready(function() {
            $('.toggle-status-btn').on('change', function() {
                var isChecked = $(this).is(':checked');
                var reviewId = $(this).data('id');
                var newStatus = isChecked ? 'approved' : 'pending';
                var statusTextElem = $(this).closest('.status-toggle-wrapper').find('.status-text');

                $.ajax({
                    url: 'toggle_status.php',
                    type: 'POST',
                    data: { review_id: reviewId, status: newStatus },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            statusTextElem.text(isChecked ? 'مقبول' : 'مخفي');
                            statusTextElem.css('color', isChecked ? 'var(--success-color)' : 'var(--text-secondary)');
                        } else {
                            alert(response.message);
                            $(this).prop('checked', !isChecked); // التراجع في حال الفشل
                        }
                    }.bind(this),
                    error: function() {
                        alert('فشل الاتصال بالخادم.');
                        $(this).prop('checked', !isChecked);
                    }.bind(this)
                });
            });
        });
    </script>
</body>
</html>