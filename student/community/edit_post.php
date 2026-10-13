<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية الصفحة
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// إعدادات محرك الترجمة والاتجاهات
$allowed_langs = ['ar', 'en', 'fr'];
$lang_code = (isset($_SESSION['lang']) && in_array($_SESSION['lang'], $allowed_langs)) ? $_SESSION['lang'] : 'fr';
$lang = require "../lang/{$lang_code}.php";
$dir = ($lang_code === 'ar') ? 'rtl' : 'ltr';

// جلب ID المنشور والتحقق منه
$post_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$post_id) {
    header("Location: my_pending_posts.php");
    exit();
}

try {
    // جلب بيانات المنشور والتأكد من أن الطالب هو المالك وأن المنشور لا يزال قيد المراجعة
    $stmt = $pdo->prepare("
        SELECT * FROM posts 
        WHERE post_id = ? AND user_id = ? AND status = 'pending'
    ");
    $stmt->execute([$post_id, $user_id]);
    $post = $stmt->fetch();

    if (!$post) {
        // إذا لم يتم العثور عليه (إما لأنه ليس ملكه أو تمت مراجعته بالفعل)
        $_SESSION['post_action_status'] = ['type' => 'error', 'message' => $lang['cannot_edit_post']];
        header("Location: my_pending_posts.php");
        exit();
    }

} catch (PDOException $e) {
    die("خطأ في جلب البيانات: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang_code; ?>" dir="<?php echo $dir; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang['edit_post_page_title']; ?></title>
    <link rel="icon" type="image/svg+xml" href="/mo7adaraty/assets/images/favicon.svg">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;900&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="../../assets/css/global.css">
    <link rel="stylesheet" href="../../assets/css/student.css">
    <link rel="stylesheet" href="../../assets/css/student/community/edit_post.css">

        
</head>
<body class="student-body">

    <script>
        (function() {
            const savedTheme = localStorage.getItem('mo7adaraty_theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>

    <?php require_once '../../templates/header.php'; ?>

    <main class="page-container">
        <div class="page-header skeleton">
            <h1 class="page-title">
                <i class="fa-solid fa-pen-to-square"></i> 
                <?php echo $lang['edit_post']; ?>
            </h1>
            <a href="my_pending_posts.php" class="back-btn">
                <?php if ($dir === 'rtl'): ?>
                    <?php echo $lang['back_to_my_posts']; ?> <i class="fa-solid fa-arrow-left"></i>
                <?php else: ?>
                    <i class="fa-solid fa-arrow-left"></i> <?php echo $lang['back_to_my_posts']; ?>
                <?php endif; ?>
            </a>
        </div>

        <div class="edit-card skeleton">
            <form action="update_post_process.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="post_id" value="<?php echo $post['post_id']; ?>">

                <div class="form-group">
                    <label for="post_text"><?php echo $lang['text_label']; ?></label>
                    <textarea id="post_text" name="post_text" class="form-control" rows="5"><?php echo htmlspecialchars($post['post_text']); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="post_image"><?php echo $lang['replace_image_optional']; ?></label>
                    <input type="file" id="post_image" name="post_image" class="form-control" accept="image/*">
                    
                    <?php if (!empty($post['image_path'])): ?>
                        <div class="current-image-wrapper">
                            <p class="hint"><i class="fa-regular fa-image"></i> <?php echo $lang['current_image']; ?></p>
                            <img src="../../<?php echo htmlspecialchars($post['image_path']); ?>" alt="<?php echo $lang['current_image_alt']; ?>" class="current-image" loading="lazy">
                        </div>
                    <?php endif; ?>
                </div>
                
                <button type="submit" class="btn-submit">
                    <i class="fa-regular fa-floppy-disk"></i> <?php echo $lang['save_changes']; ?>
                </button>
            </form>
        </div>
    </main>

    <?php require_once '../../templates/footer.php'; ?>

</body>
</html>