<?php
/**
 * نظام التصفية التلقائية والحصرية (Automatic & Exclusive Filtering)
 * هذا الملف يقوم بجلب صلاحيات ونطاق المشرف وبناء شروط SQL تلقائياً
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * دالة للتحقق مما إذا كان للمشرف صلاحية الوصول لصفحة معينة
 */
function has_page_permission($pdo, $user_id, $page_slug, $academic_year_id) {
    // السوبر أدمن يملك صلاحية الوصول لكل شيء
    if (isset($_SESSION['is_superadmin']) && $_SESSION['is_superadmin'] == 1) {
        return true;
    }

    $stmt = $pdo->prepare("SELECT id FROM admin_permissions WHERE user_id = ? AND page_slug = ? AND academic_year_id = ?");
    $stmt->execute([$user_id, $page_slug, $academic_year_id]);
    return $stmt->rowCount() > 0;
}

/**
 * دالة بناء شرط الاستعلام (WHERE Clause) بناءً على نطاق المشرف
 * @param string $table_alias الاسم المستعار للجدول في الاستعلام (مثل 'l' لجدول lectures)
 * @return array تحتوي على نص الشرط والمصفوفة الخاصة بـ PDO
 */
function get_admin_scope_condition($table_alias = '') {
    $prefix = $table_alias ? $table_alias . '.' : '';
    $condition = " 1=1 "; // شرط افتراضي لا يؤثر على الاستعلام
    $params = [];

    // إذا كان سوبر أدمن، لا نطبق أي تصفية (يرى كل شيء)
    if (isset($_SESSION['is_superadmin']) && $_SESSION['is_superadmin'] == 1) {
        return ['sql' => $condition, 'params' => $params];
    }

    $scope_type = $_SESSION['managed_scope_type'] ?? 'global';

    // تطبيق التصفية الحصرية بناءً على نوع النطاق المخزن في الجلسة
    switch ($scope_type) {
        case 'division':
            $condition = "{$prefix}division_id = :managed_div";
            $params[':managed_div'] = $_SESSION['managed_division_id'];
            break;
            
        case 'class':
            $condition = "{$prefix}class_id = :managed_class";
            $params[':managed_class'] = $_SESSION['managed_class_id'];
            break;
            
        case 'track':
            $condition = "{$prefix}track_id = :managed_track";
            $params[':managed_track'] = $_SESSION['managed_track_id'];
            break;
            
        case 'group':
            $condition = "{$prefix}group_id = :managed_group";
            $params[':managed_group'] = $_SESSION['managed_group_id'];
            break;
            
        case 'global':
        default:
            // المشرف العام يرى كل شيء لكن ضمن صلاحيات صفحاته فقط
            $condition = " 1=1 ";
            break;
    }

    return ['sql' => $condition, 'params' => $params];
}

/**
 * دالة لحماية الصفحة: تمنع الدخول إذا لم تكن الصفحة ضمن صلاحيات المشرف
 */
function protect_admin_page($pdo, $page_slug) {
    // جلب السنة الدراسية الحالية (يمكن جلبها من الجلسة أو قاعدة البيانات)
    $current_year_id = $_SESSION['active_academic_year'] ?? 1; 
    
    if (!has_page_permission($pdo, $_SESSION['user_id'], $page_slug, $current_year_id)) {
        $_SESSION['error'] = "عذراً، لا تملك صلاحية الوصول لهذه الصفحة ضمن نطاقك الحالي.";
        header("Location: ../dashboard/index.php");
        exit();
    }
}