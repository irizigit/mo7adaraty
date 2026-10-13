<?php
ob_start();
session_start();
require_once '../config/db_connexion.php';

// إعداد نظام اللغات للملف لسحب الترجمات
$allowed_langs = ['ar', 'en', 'fr'];
$lang_code = (isset($_SESSION['lang']) && in_array($_SESSION['lang'], $allowed_langs)) ? $_SESSION['lang'] : 'fr';
$lang = require "../lang/{$lang_code}.php";

ob_clean();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => $lang['review_err_invalid'] ?? 'Invalid request.']);
    exit();
}

$user_id = $_SESSION['user_id'] ?? null;
$review_text = trim($_POST['review_text'] ?? '');
$guest_name = trim($_POST['guest_name'] ?? '');
$guest_division = trim($_POST['guest_division'] ?? '');

if (empty($review_text)) {
    echo json_encode(['success' => false, 'message' => $lang['review_err_empty'] ?? 'Please write your review first.']);
    exit();
}

if (!$user_id && (empty($guest_name) || empty($guest_division))) {
    echo json_encode(['success' => false, 'message' => $lang['review_err_name'] ?? 'Please enter your name and division.']);
    exit();
}

try {
    // إدخال الرأي في قاعدة البيانات
    $stmt = $pdo->prepare("INSERT INTO reviews (user_id, guest_name, guest_division, review_text, status, created_at) VALUES (?, ?, ?, ?, 'approved', NOW())");
    $stmt->execute([$user_id, $guest_name, $guest_division, $review_text]);
    
    // تحضير الـ HTML للرأي الجديد ليتم عرضه فوراً
    $display_name = $guest_name;
    $display_division = $guest_division;
    $display_avatar = '../uploads/profile_pictures/default_profile.png';
    $base_url = '/mo7adaraty/';

    if ($user_id) {
        // جلب بيانات المستخدم إذا كان مسجلاً
        $stmt_user = $pdo->prepare("
            SELECT u.username, u.profile_picture,
            (SELECT d.division_name FROM student_enrollments se JOIN course_offerings co ON se.offering_id = co.offering_id JOIN divisions d ON co.division_id = d.division_id WHERE se.user_id = u.user_id LIMIT 1) as user_division
            FROM users u WHERE u.user_id = ?
        ");
        $stmt_user->execute([$user_id]);
        $user_data = $stmt_user->fetch(PDO::FETCH_ASSOC);
        
        if ($user_data) {
            $display_name = $user_data['username'];
            $display_division = $user_data['user_division'] ?: ($lang['review_student_default'] ?? 'Platform Student');
            if (!empty($user_data['profile_picture'])) {
                $display_avatar = $base_url . 'uploads/profile_pictures/' . $user_data['profile_picture'];
            }
        }
    }

    $html = '
    <div class="review-card" style="animation: fadeIn 0.5s ease;">
        <div class="review-text">"' . nl2br(htmlspecialchars($review_text)) . '"</div>
        <div class="review-author">
            <img src="' . htmlspecialchars($display_avatar) . '" alt="User" class="review-avatar" onerror="this.src=\''.$base_url.'uploads/profile_pictures/default_profile.png\'">
            <div class="review-author-info">
                <h4>' . htmlspecialchars($display_name) . '</h4>
                <span>' . htmlspecialchars($display_division) . '</span>
            </div>
        </div>
    </div>';

    echo json_encode([
        'success' => true, 
        // استدعاء رسالة النجاح من ملف اللغة
        'message' => $lang['review_success'] ?? 'Thank you! Your review has been successfully added.',
        'html' => $html
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
exit();
?>