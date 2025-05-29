<?php
session_start(); // ابدأ الجلسة
session_unset(); // إزالة جميع متغيرات الجلسة
session_destroy(); // تدمير الجلسة

header('Content-Type: application/json');
echo json_encode(['success' => true, 'message' => 'تم تسجيل الخروج بنجاح.']);
exit;
?>