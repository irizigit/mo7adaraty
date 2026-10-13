<?php
session_start();

// التأكد من أن المستخدم مسجل الدخول ولديه صلاحيات إدارية أساساً
if (isset($_SESSION['user_id']) && ($_SESSION['is_admin'] == 1 || $_SESSION['is_superadmin'] == 1)) {
    
    // إذا كان يتصفح كمدير، حوّله إلى طالب
    if (isset($_SESSION['view_as']) && $_SESSION['view_as'] === 'admin') {
        $_SESSION['view_as'] = 'student';
        header("Location: ../student/index.php");
        exit();
    } 
    // إذا كان يتصفح كطالب، حوّله إلى مدير
    else {
        $_SESSION['view_as'] = 'admin';
        header("Location: ../admin/index.php");
        exit();
    }
} else {
    // إذا لم تكن لديه صلاحيات، أعِده للصفحة الرئيسية
    header("Location: ../index.php");
    exit();
}
?>