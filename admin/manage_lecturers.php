<?php
// تفعيل عرض الأخطاء للمساعدة في التصحيح
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../server/session_check.php';

// التأكد من أن المستخدم مسؤول قبل الوصول إلى هذه الصفحة
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Location: ../public/login.php');
    exit;
}

$lecturers_file = '../data/lecturers.json';
$semesters_file = '../data/semesters.json'; // لجلب قائمة الفصول

$lecturers_data = [];
if (file_exists($lecturers_file) && filesize($lecturers_file) > 0) {
    $lecturers_json = file_get_contents($lecturers_file);
    $lecturers_data = json_decode($lecturers_json, true);
    if ($lecturers_data === null) $lecturers_data = [];
}

$semesters_data = [];
if (file_exists($semesters_file) && filesize($semesters_file) > 0) {
    $semesters_json = file_get_contents($semesters_file);
    $semesters_data = json_decode($semesters_json, true);
    if ($semesters_data === null) $semesters_data = [];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الأساتذة</title>
    <link rel="stylesheet" href="../public/css/style.css">
    <style>
        .lecturer-form, .manage-table {
            margin-top: 20px;
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .lecturer-form label {
            font-weight: bold;
            margin-bottom: 5px;
            display: block;
        }
        .lecturer-form input[type="text"],
        .lecturer-form input[type="email"],
        .lecturer-form textarea,
        .lecturer-form select {
            width: calc(100% - 22px);
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .lecturer-form textarea {
            resize: vertical;
            min-height: 80px;
        }
        .semester-checkboxes {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
            padding: 10px;
            border: 1px solid #eee;
            border-radius: 4px;
            background-color: #fff;
        }
        .semester-checkboxes label {
            display: flex;
            align-items: center;
            font-weight: normal;
            margin-bottom: 0;
            cursor: pointer;
        }
        .semester-checkboxes input[type="checkbox"] {
            margin-left: 5px; /* Adjust for RTL */
            margin-right: 0;
            width: auto;
        }
        .lecturer-form button {
            background-color: #007bff;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.3s ease;
        }
        .lecturer-form button:hover {
            background-color: #0056b3;
        }
        .manage-table {
            width: 100%;
            border-collapse: collapse;
        }
        .manage-table th, .manage-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: right;
        }
        .manage-table th {
            background-color: #007bff;
            color: white;
        }
        .manage-table .actions button {
            padding: 6px 10px;
            margin: 0 3px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9em;
            transition: background-color 0.3s ease;
        }
        .manage-table .actions .edit-btn {
            background-color: #ffc107;
            color: #333;
        }
        .manage-table .actions .edit-btn:hover {
            background-color: #e0a800;
        }
        .manage-table .actions .delete-btn {
            background-color: #dc3545;
            color: white;
        }
        .manage-table .actions .delete-btn:hover {
            background-color: #c82333;
        }
        #message {
            margin-top: 15px;
            padding: 10px;
            border-radius: 5px;
            font-weight: bold;
            display: none;
        }
        #message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        #message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <div class="container">
        <h2>إدارة الأساتذة</h2>
        <p><a href="dashboard.php">العودة للوحة التحكم</a></p>

        <div id="message" class="message"></div>

        <div class="lecturer-form">
            <h3>إضافة/تعديل أستاذ</h3>
            <form id="lecturerForm">
                <input type="hidden" id="lecturerId" name="id">
                <div class="form-group">
                    <label for="lecturerName">اسم الأستاذ:</label>
                    <input type="text" id="lecturerName" name="name" required>
                </div>
                <div class="form-group">
                    <label for="lecturerEmail">البريد الإلكتروني (اختياري):</label>
                    <input type="email" id="lecturerEmail" name="email">
                </div>
                <div class="form-group">
                    <label for="lecturerBio">نبذة تعريفية (اختياري):</label>
                    <textarea id="lecturerBio" name="bio" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label>الفصول التي يدرسها:</label>
                    <div class="semester-checkboxes">
                        <?php if (empty($semesters_data)): ?>
                            <p>لا توجد فصول متاحة. الرجاء إضافة فصول أولاً في إدارة الفصول.</p>
                        <?php else: ?>
                            <?php foreach ($semesters_data as $semester): ?>
                                <label>
                                    <input type="checkbox" name="taught_semesters[]" value="<?php echo htmlspecialchars($semester['id']); ?>">
                                    <?php echo htmlspecialchars($semester['name']); ?>
                                </label>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <button type="submit" id="submitLecturerBtn">إضافة أستاذ</button>
                <button type="button" id="cancelEditBtn" style="display:none; background-color: #6c757d;">إلغاء التعديل</button>
            </form>
        </div>

        <h3 style="margin-top: 30px;">الأساتذة الحاليون</h3>
        <table class="manage-table">
            <thead>
                <tr>
                    <th>الاسم</th>
                    <th>البريد الإلكتروني</th>
                    <th>الفصول التي يدرسها</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody id="lecturersTableBody">
                <?php if (empty($lecturers_data)): ?>
                    <tr>
                        <td colspan="4" style="text-align: center;">لا يوجد أساتذة لعرضهم بعد.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($lecturers_data as $lecturer): ?>
                        <tr data-lecturer-id="<?php echo htmlspecialchars($lecturer['id']); ?>">
                            <td><?php echo htmlspecialchars($lecturer['name']); ?></td>
                            <td><?php echo htmlspecialchars($lecturer['email'] ?? 'N/A'); ?></td>
                            <td>
                                <?php
                                $taught_semester_names = [];
                                // التأكد من أن 'taught_semesters' موجودة ومصفوفة
                                if (isset($lecturer['taught_semesters']) && is_array($lecturer['taught_semesters'])) {
                                    foreach ($lecturer['taught_semesters'] as $ts_id) {
                                        foreach ($semesters_data as $semester) {
                                            if ($semester['id'] === $ts_id) {
                                                $taught_semester_names[] = $semester['name'];
                                                break;
                                            }
                                        }
                                    }
                                }
                                echo empty($taught_semester_names) ? 'لا يوجد' : htmlspecialchars(implode(', ', $taught_semester_names));
                                ?>
                            </td>
                            <td class="actions">
                                <button class="edit-btn"
                                        data-id="<?php echo htmlspecialchars($lecturer['id']); ?>"
                                        data-name="<?php echo htmlspecialchars($lecturer['name']); ?>"
                                        data-email="<?php echo htmlspecialchars($lecturer['email'] ?? ''); ?>"
                                        data-bio="<?php echo htmlspecialchars($lecturer['bio'] ?? ''); ?>"
                                        data-semesters='<?php echo json_encode($lecturer['taught_semesters'] ?? []); ?>'
                                >تعديل</button>
                                <button class="delete-btn" data-id="<?php echo htmlspecialchars($lecturer['id']); ?>">حذف</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script src="../public/js/manage_lecturers.js"></script>
</body>
</html>