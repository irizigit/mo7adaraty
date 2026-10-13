<?php
session_start();
require_once '../config/db_connexion.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $price = trim($_POST['price']);
    $user_id = $_SESSION['user_id'];

    if (!empty($title) && !empty($description) && !empty($price)) {
        $query = "INSERT INTO student_services (user_id, title, description, price, status) VALUES (:user_id, :title, :description, :price, 'pending')";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':title', $title, PDO::PARAM_STR);
        $stmt->bindParam(':description', $description, PDO::PARAM_STR);
        $stmt->bindParam(':price', $price, PDO::PARAM_STR);
        
        if ($stmt->execute()) {
            $message = "<div class='alert success'>تم إرسال طلب الخدمة بنجاح، وهو في انتظار موافقة الإدارة.</div>";
        } else {
            $message = "<div class='alert error'>حدث خطأ أثناء إرسال الطلب.</div>";
        }
    } else {
        $message = "<div class='alert error'>يرجى تعبئة جميع الحقول.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تقديم خدمة جديدة</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f7f6; margin: 0; padding: 20px; }
        .form-container { background: #fff; max-width: 600px; margin: 40px auto; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #333; margin-bottom: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: bold; color: #555; }
        .form-group input, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; font-family: inherit; }
        .btn-submit { background-color: #28a745; color: #fff; border: none; padding: 12px 20px; border-radius: 4px; cursor: pointer; width: 100%; font-size: 16px; font-weight: bold; }
        .btn-submit:hover { background-color: #218838; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 4px; text-align: center; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .back-link { display: block; text-align: center; margin-top: 15px; color: #0056b3; text-decoration: none; }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>إضافة خدمة طلابية جديدة</h2>
        <?php echo $message; ?>
        <form method="POST" action="">
            <div class="form-group">
                <label>عنوان الخدمة (مثال: تصميم عروض تقديمية احترافية)</label>
                <input type="text" name="title" required>
            </div>
            <div class="form-group">
                <label>الوصف والمواصفات الكاملة للخدمة</label>
                <textarea name="description" rows="6" required></textarea>
            </div>
            <div class="form-group">
                <label>السعر (مثال: 50 درهم، أو حسب الاتفاق)</label>
                <input type="text" name="price" required>
            </div>
            <button type="submit" class="btn-submit">إرسال الطلب للمراجعة</button>
        </form>
        <a href="student_services.php" class="back-link">العودة لصفحة الخدمات</a>
    </div>
</body>
</html>