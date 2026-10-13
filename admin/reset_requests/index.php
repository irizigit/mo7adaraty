<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية الصفحة والتأكد من أن المستخدم هو أدمن
if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

$breadcrumbs = [
    "الطلاب والمستخدمون" => "",
    "طلبات إعادة تعيين كلمة المرور" => ""
];

// التأكد من وجود جدول طلبات الدعم في قاعدة البيانات
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS support_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        username VARCHAR(100) NOT NULL,
        phone_number VARCHAR(50) NOT NULL,
        request_type VARCHAR(50) DEFAULT 'password_reset_issue',
        message TEXT NULL,
        status VARCHAR(20) DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {}

// دالة توليد رابط المحادثة الفورية عبر واتساب
function formatWhatsAppUrl($phone,$username) {
    $clean = preg_replace('/[^0-9]/', '',$phone);
    if (substr($clean, 0, 2) === '06' || substr($clean, 0, 2) === '07') {
        $clean = '212' . substr($clean, 1);
    } elseif (substr($clean, 0, 1) === '6' || substr($clean, 0, 1) === '7') {
        $clean = '212' .$clean;
    }
    $text = urlencode("مرحباً بك عزيزي الطالب (" . $username . ")، بخصوص طلبك في منصة محاضراتي لاسترجاع كلمة المرور الخاصة بحسابك...");
    return "https://wa.me/" . $clean . "?text=" . $text;
}

// معالجة إتمام الطلب وتحديث حالته
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_request'])) {$request_id = filter_input(INPUT_POST, 'request_id', FILTER_VALIDATE_INT);
    $table_source = trim($_POST['table_source'] ?? 'support_requests');
    if ($request_id) {
        try {
            if ($table_source === 'password_reset_requests') {
                $stmt =$pdo->prepare("UPDATE password_reset_requests SET status = 'completed' WHERE request_id = ?");
            } else {
                $stmt =$pdo->prepare("UPDATE support_requests SET status = 'completed' WHERE id = ?");
            }
            $stmt->execute([$request_id]);$_SESSION['msg'] = "تم تحديد الطلب كمكتمل بنجاح، وتمت إزالته من قائمة الانتظار.";
        } catch (PDOException $e) {$_SESSION['error'] = "حدث خطأ أثناء تحديث حالة الطلب.";
        }
        header("Location: index.php");
        exit();
    }
}

$requests = [];

try {
    // 1. جلب الطلبات الواردة من نموذج الدعم والواتساب الجديد
    $stmt_sr =$pdo->query("
        SELECT 
            sr.id AS request_id,
            COALESCE(sr.user_id, u.user_id) AS user_id,
            sr.username,
            sr.phone_number AS contact_info,
            sr.message,
            sr.status,
            sr.created_at AS request_date,
            'support_requests' AS source_table,
            (
                SELECT GROUP_CONCAT(
                    DISTINCT CONCAT(d.division_name, ' > ', c.class_name, ' / ', g.group_name, IF(t.track_name IS NOT NULL, CONCAT(' / ', t.track_name), '')) 
                    SEPARATOR '<br>'
                )
                FROM student_enrollments se
                JOIN course_offerings co ON se.offering_id = co.offering_id
                JOIN divisions d ON co.division_id = d.division_id
                JOIN classes c ON co.class_id = c.class_id
                JOIN `groups` g ON co.group_id = g.group_id
                LEFT JOIN tracks t ON co.track_id = t.track_id
                WHERE se.user_id = COALESCE(sr.user_id, u.user_id)
            ) as enrollment_info
        FROM support_requests sr
        LEFT JOIN users u ON u.username = sr.username
        WHERE sr.status = 'pending'
        ORDER BY sr.created_at DESC
    ");
    $support_reqs =$stmt_sr->fetchAll(PDO::FETCH_ASSOC);
    if ($support_reqs) {$requests = array_merge($requests,$support_reqs);
    }
} catch (PDOException $e) {}

try {
    // 2. جلب الطلبات السابقة إن وجدت من جدول password_reset_requests
    $stmt_prr =$pdo->query("
        SELECT 
            prr.request_id,
            prr.user_id,
            prr.username,
            prr.contact_info,
            NULL AS message,
            prr.status,
            prr.request_date,
            'password_reset_requests' AS source_table,
            (
                SELECT GROUP_CONCAT(
                    DISTINCT CONCAT(d.division_name, ' > ', c.class_name, ' / ', g.group_name, IF(t.track_name IS NOT NULL, CONCAT(' / ', t.track_name), '')) 
                    SEPARATOR '<br>'
                )
                FROM student_enrollments se
                JOIN course_offerings co ON se.offering_id = co.offering_id
                JOIN divisions d ON co.division_id = d.division_id
                JOIN classes c ON co.class_id = c.class_id
                JOIN `groups` g ON co.group_id = g.group_id
                LEFT JOIN tracks t ON co.track_id = t.track_id
                WHERE se.user_id = prr.user_id
            ) as enrollment_info
        FROM password_reset_requests prr
        WHERE prr.status = 'pending'
        ORDER BY prr.request_date DESC
    ");
    $old_reqs =$stmt_prr->fetchAll(PDO::FETCH_ASSOC);
    if ($old_reqs) {$requests = array_merge($requests,$old_reqs);
    }
} catch (PDOException $e) {}

// ترتيب كل الطلبات من الأحدث إلى الأقدم
usort($requests, function($a,$b) {
    return strtotime($b['request_date']) - strtotime($a['request_date']);
});
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>طلبات استرجاع الحسابات والدعم - Mo7adaraty</title>
    <link rel="icon" type="image/svg+xml" href="/mo7adaraty/assets/images/favicon.svg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/admin_global.css">
    <link rel="stylesheet" href="../assets/css/admin_academic.css">
    
    <style>
        .requests-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 25px;
        }

        .request-card {
            background: var(--bg-surface);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            display: flex;
            flex-direction: column;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
            transition: all var(--transition-normal);
            position: relative;
            overflow: hidden;
        }

        .request-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            border-color: var(--brand-primary);
        }

        .req-header {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 20px;
            background: rgba(26, 115, 232, 0.04);
            border-bottom: 1px dashed var(--border-color);
        }

        .req-avatar {
            width: 48px;
            height: 48px;
            object-fit: contain;
            background: var(--bg-surface);
            border-radius: 50%;
            padding: 8px;
            border: 2px solid var(--brand-primary);
        }

        .req-user-info {
            flex: 1;
        }

        .req-user-info h3 {
            margin: 0 0 5px 0;
            color: var(--text-primary);
            font-size: 17px;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .req-badge {
            font-size: 11px;
            padding: 3px 8px;
            border-radius: 12px;
            font-weight: 700;
            background: rgba(16, 185, 129, 0.15);
            color: #059669;
        }

        .req-date {
            font-size: 12px;
            color: var(--text-secondary);
            font-weight: bold;
        }

        .req-body {
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .req-contact {
            background: var(--bg-body);
            padding: 12px 14px;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-weight: 800;
            color: var(--text-primary);
            font-size: 14px;
        }

        .btn-wa-direct {
            background: #25D366;
            color: #ffffff;
            border: none;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 12.5px;
            font-weight: 800;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: 0.2s ease;
        }

        .btn-wa-direct:hover {
            background: #128C7E;
            color: #ffffff;
        }

        .req-message-box {
            background: rgba(245, 158, 11, 0.08);
            border: 1px dashed rgba(245, 158, 11, 0.4);
            padding: 12px 14px;
            border-radius: var(--radius-sm);
            font-size: 13px;
            color: var(--text-primary);
            line-height: 1.5;
        }

        .req-enrollment {
            font-size: 12.5px;
            line-height: 1.6;
            color: var(--text-secondary);
            background: var(--bg-body);
            padding: 12px 14px;
            border-radius: var(--radius-sm);
            border-right: 3px solid var(--brand-primary);
            font-weight: 600;
        }

        .req-steps {
            background: rgba(239, 68, 68, 0.05);
            padding: 12px 14px;
            border-radius: var(--radius-sm);
            border: 1px solid rgba(239, 68, 68, 0.15);
        }
        
        .req-steps h4 {
            margin: 0 0 8px 0;
            color: var(--danger-color);
            font-size: 13px;
            font-weight: 800;
        }

        .req-steps ol {
            margin: 0;
            padding-right: 18px;
            font-size: 12.5px;
            color: var(--text-primary);
            line-height: 1.6;
            font-weight: 600;
        }

        .req-actions {
            padding: 15px 20px;
            background: var(--bg-body);
            border-top: 1px solid var(--border-color);
            display: flex;
            gap: 10px;
        }

        .btn-complete {
            background: var(--success-color);
            color: #fff;
            border: none;
            padding: 10px;
            border-radius: var(--radius-sm);
            font-weight: bold;
            font-family: var(--font-family);
            cursor: pointer;
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            transition: 0.2s;
        }

        .btn-complete:hover {
            background: #218838;
        }
        
        .btn-edit-user {
            background: var(--bg-surface);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            padding: 10px;
            border-radius: var(--radius-sm);
            font-weight: bold;
            text-decoration: none;
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            transition: 0.2s;
        }

        .btn-edit-user:hover {
            background: var(--border-color);
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
            
            <div class="academic-header-actions skeleton" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; margin-bottom: 22px;">
                <h2 style="margin: 0; color: var(--text-primary); display: flex; align-items: center; gap: 10px;">
                    <img src="../dashboard_design/images/admin_icon_reset_requests.png" alt="Reset Requests" style="width: 36px; height: 36px; object-fit: contain;" onerror="this.src='../dashboard_design/images/admin_icon_dashboard.png'">
                    طلبات استرجاع الحسابات ومساعدة الطلاب
                </h2>
                
                <div style="background: var(--bg-surface); border: 1px solid var(--warning-color); padding: 8px 16px; border-radius: var(--radius-pill); font-weight: bold; font-size: 14px; color: var(--warning-color); box-shadow: var(--shadow-sm);">
                    الطلبات المعلقة: <span><?php echo count($requests); ?></span>
                </div>
            </div>

            <?php if (isset($_SESSION['msg'])): ?>
                <div style="padding: 14px 18px; background: rgba(40, 167, 69, 0.1); color: var(--success-color); border: 1px solid var(--success-color); border-radius: var(--radius-md); margin-bottom: 20px; font-weight: bold; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-circle-check"></i> <?php echo $_SESSION['msg']; unset($_SESSION['msg']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div style="padding: 14px 18px; background: rgba(220, 53, 69, 0.1); color: var(--danger-color); border: 1px solid var(--danger-color); border-radius: var(--radius-md); margin-bottom: 20px; font-weight: bold; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-circle-exclamation"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <div class="requests-grid skeleton">
                <?php if (empty($requests)): ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; background: var(--bg-surface); border-radius: var(--radius-lg); border: 1px dashed var(--border-color); color: var(--text-secondary);">
                        <i class="fa-solid fa-check-double" style="font-size: 55px; margin-bottom: 15px; color: var(--success-color); opacity: 0.85;"></i>
                        <p style="font-size: 17px; font-weight: bold; margin: 0;">رائع! لا توجد أي طلبات معلقة حالياً.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($requests as$req): ?>
                        <?php 
                            $wa_link = formatWhatsAppUrl($req['contact_info'],$req['username']);
                            $edit_user_link = !empty($req['user_id']) ? "../users/edit.php?id=" . $req['user_id'] : "../users/index.php?search=" . urlencode($req['username']);
                        ?>
                        <div class="request-card">
                            <div class="req-header">
                                <img src="../dashboard_design/images/admin_icon_users.png" alt="User" class="req-avatar" onerror="this.src='../assets/icons/user-tie.png'">
                                <div class="req-user-info">
                                    <h3>
                                        <span><?php echo htmlspecialchars($req['username']); ?></span>
                                        <span class="req-badge">طلب مباشر</span>
                                    </h3>
                                    <div class="req-date" dir="ltr">
                                        <i class="fa-regular fa-clock"></i> <?php echo date('Y-m-d H:i', strtotime($req['request_date'])); ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="req-body">
                                <div class="req-contact">
                                    <span dir="ltr"><i class="fa-solid fa-phone" style="color: var(--brand-primary); margin-inline-end: 6px;"></i> <?php echo htmlspecialchars($req['contact_info']); ?></span>
                                    <a href="<?php echo $wa_link; ?>" target="_blank" rel="noopener noreferrer" class="btn-wa-direct">
                                        <i class="fa-brands fa-whatsapp"></i> فتح واتساب
                                    </a>
                                </div>

                                <?php if (!empty($req['message'])): ?>
                                    <div class="req-message-box">
                                        <strong style="color: #d97706; display: block; margin-bottom: 4px;"><i class="fa-solid fa-comment-dots"></i> رسالة وملاحظة الطالب:</strong>
                                        <?php echo nl2br(htmlspecialchars($req['message'])); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="req-enrollment">
                                    <strong style="color: var(--brand-primary);"><i class="fa-solid fa-layer-group"></i> المقررات المسجل بها:</strong><br>
                                    <div style="margin-top: 6px;">
                                        <?php echo $req['enrollment_info'] ?: '<span style="color: var(--danger-color);">غير مسجل في أي مقرر أكاديمي</span>'; ?>
                                    </div>
                                </div>
                                
                                <div class="req-steps">
                                    <h4><i class="fa-solid fa-list-check"></i> الإجراء المطلوب من الإدارة:</h4>
                                    <ol>
                                        <li>انقر على "تعديل الحساب" لتعيين كلمة مرور جديدة للطالب.</li>
                                        <li>راسل الطالب عبر زر "فتح واتساب" وزوده بالبيانات.</li>
                                        <li>اضغط على "تم التنفيذ" لأرشفة الطلب وإزالته.</li>
                                    </ol>
                                </div>
                            </div>
                            
                            <div class="req-actions">
                                <a href="<?php echo $edit_user_link; ?>" target="_blank" class="btn-edit-user">
                                    <i class="fa-solid fa-user-pen"></i> تعديل الحساب
                                </a>
                                <form action="index.php" method="POST" style="margin: 0; flex: 1; display: flex;" onsubmit="return confirm('هل أنت متأكد من إتمام هذا الطلب؟');">
                                    <input type="hidden" name="request_id" value="<?php echo $req['request_id']; ?>">
                                    <input type="hidden" name="table_source" value="<?php echo htmlspecialchars($req['source_table']); ?>">
                                    <input type="hidden" name="complete_request" value="1">
                                    <button type="submit" class="btn-complete">
                                        <i class="fa-solid fa-check"></i> تم التنفيذ
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/admin_global.js"></script>
</body>
</html>