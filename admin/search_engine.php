<?php
session_start();
// ---- كود إظهار وعرض جميع أخطاء PHP لضمان الشفافية البرمجية ----
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once '../config/db_connexion.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$breadcrumbs = [
    "الرئيسية" => "index.php",
    "محرك البحث الشامل" => ""
];

// دالة لاستخراج ID العرض بناءً على قاعدة البيانات الفعلية
function extractId($row) {
    if (isset($row['lecture_id'])) return $row['lecture_id'];
    if (isset($row['exam_id'])) return $row['exam_id'];
    if (isset($row['subject_id'])) return $row['subject_id'];
    if (isset($row['user_id'])) return $row['user_id'];
    if (isset($row['professor_id'])) return $row['professor_id'];
    return $row['id'] ?? '';
}

// دالة لاستخراج اسم العرض بناءً على الأعمدة الموجودة فعلياً
function extractName($row) {
    if (!empty($row['professor_name'])) return $row['professor_name'];
    if (!empty($row['username'])) return $row['username'];
    return $row['title'] ?? $row['subject_name'] ?? 'عنصر غير محدد';
}

// جلب بيانات الفلاتر
$academic_years = []; $divisions_opts = []; $classes_opts = [];
try { $academic_years = $pdo->query("SELECT id, year_label, is_current FROM academic_years ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC); } catch(Throwable $e){}
try { $divisions_opts = $pdo->query("SELECT division_id, division_name FROM divisions ORDER BY 1")->fetchAll(PDO::FETCH_ASSOC); } catch(Throwable $e){}
try { $classes_opts = $pdo->query("SELECT class_id, class_name FROM classes ORDER BY 1")->fetchAll(PDO::FETCH_ASSOC); } catch(Throwable $e){}

// استقبال المدخلات
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$s_yr = isset($_GET['year_id']) ? $_GET['year_id'] : 'all';
$s_div = isset($_GET['division_id']) && is_numeric($_GET['division_id']) ? (int)$_GET['division_id'] : '';
$s_cls = isset($_GET['class_id']) && is_numeric($_GET['class_id']) ? (int)$_GET['class_id'] : '';
$like_q = "%$q%";

$results = ['students' => [], 'professors' => [], 'subjects' => [], 'lectures' => [], 'exams' => []];

// 1. بحث الطلاب (تم تصحيح الأعمدة إلى username و email)
try {
    $sql = "SELECT DISTINCT u.* FROM users u";
    $conds = ["u.role = 'student'"]; $params = [];
    
    if (($s_yr && $s_yr !== 'all') || $s_div || $s_cls) {
        $sql .= " LEFT JOIN student_enrollments se ON u.user_id = se.user_id";
        if ($s_yr !== 'all') { $conds[] = "se.academic_year_id = ?"; $params[] = $s_yr; }
        if ($s_div) { $conds[] = "se.division_id = ?"; $params[] = $s_div; }
        if ($s_cls) { $conds[] = "se.class_id = ?"; $params[] = $s_cls; }
    }
    
    if ($q) {
        $conds[] = "(u.username LIKE ? OR u.email LIKE ?)";
        array_push($params, $like_q, $like_q);
    }
    
    if ($conds) $sql .= " WHERE " . implode(" AND ", $conds);
    $sql .= " LIMIT 100";
    
    $stmt = $pdo->prepare($sql); 
    $stmt->execute($params);
    $results['students'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { echo ""; }

// 2. بحث المحاضرات (تم تصحيح الجدول إلى course_offerings)
try {
    $sql = "SELECT l.* FROM lectures l";
    $conds = []; $params = [];
    
    if (($s_yr && $s_yr !== 'all') || $s_div || $s_cls) {
        $sql .= " LEFT JOIN course_offerings co ON l.offering_id = co.offering_id";
        if ($s_yr !== 'all') { $conds[] = "l.academic_year_id = ?"; $params[] = $s_yr; }
        if ($s_div) { $conds[] = "co.division_id = ?"; $params[] = $s_div; }
        if ($s_cls) { $conds[] = "co.class_id = ?"; $params[] = $s_cls; }
    }
    
    if ($q) {
        $conds[] = "(l.title LIKE ? OR l.description LIKE ?)";
        array_push($params, $like_q, $like_q);
    }
    
    if ($conds) $sql .= " WHERE " . implode(" AND ", $conds);
    $sql .= " GROUP BY l.lecture_id LIMIT 100";
    
    $stmt = $pdo->prepare($sql); 
    $stmt->execute($params);
    $results['lectures'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { echo ""; }

// 3. بحث الامتحانات (تم تصحيح الجدول إلى course_offerings والأعمدة المشتركة)
try {
    $sql = "SELECT ex.* FROM exams ex";
    $conds = []; $params = [];
    
    if (($s_yr && $s_yr !== 'all') || $s_div || $s_cls) {
        $sql .= " LEFT JOIN course_offerings co ON ex.offering_id = co.offering_id";
        if ($s_yr !== 'all') { $conds[] = "ex.academic_year_id = ?"; $params[] = $s_yr; }
        if ($s_div) { $conds[] = "(ex.division_id = ? OR co.division_id = ?)"; array_push($params, $s_div, $s_div); }
        if ($s_cls) { $conds[] = "(ex.class_id = ? OR co.class_id = ?)"; array_push($params, $s_cls, $s_cls); }
    }
    
    if ($q) {
        $conds[] = "(ex.title LIKE ? OR ex.description LIKE ?)";
        array_push($params, $like_q, $like_q);
    }
    
    if ($conds) $sql .= " WHERE " . implode(" AND ", $conds);
    $sql .= " GROUP BY ex.exam_id LIMIT 100";
    
    $stmt = $pdo->prepare($sql); 
    $stmt->execute($params);
    $results['exams'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { echo ""; }

// 4. بحث الأساتذة (تم تصحيح العمود إلى professor_name)
try {
    $sql_prof = "SELECT * FROM professors";
    $conds = []; $params = [];
    if ($q) { 
        $conds[] = "(professor_name LIKE ?)"; 
        array_push($params, $like_q); 
    }
    $stmt = $pdo->prepare($sql_prof . ($conds ? " WHERE " . implode(" AND ", $conds) : "") . " LIMIT 100");
    $stmt->execute($params); 
    $results['professors'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { echo ""; }

// 5. بحث المواد (تم تصحيح العمود إلى subject_name)
try {
    $sql_subj = "SELECT * FROM subjects";
    $conds_s = []; $params_s = [];
    if ($q) { 
        $conds_s[] = "(subject_name LIKE ?)"; 
        array_push($params_s, $like_q); 
    }
    if ($s_cls) { 
        $conds_s[] = "class_id = ?"; 
        $params_s[] = $s_cls; 
    }
    $stmt = $pdo->prepare($sql_subj . ($conds_s ? " WHERE " . implode(" AND ", $conds_s) : "") . " LIMIT 100");
    $stmt->execute($params_s); 
    $results['subjects'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { echo ""; }
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>البحث الشامل والفلترة - لوحة التحكم</title>
    <link rel="icon" type="image/svg+xml" href="../assets/images/favicon.svg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="assets/css/admin_global.css">
    <link rel="stylesheet" href="assets/css/admin_academic.css">
    <style>
        .filter-box { background: var(--bg-primary); padding: 20px; border-radius: 12px; border: 1px solid var(--border-color); margin-bottom: 30px; box-shadow: 0 5px 15px rgba(0,0,0,0.03); }
        .filter-form { display: flex; gap: 15px; flex-wrap: wrap; align-items: center; }
        .filter-input { padding: 10px 15px; height: 45px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-surface); color: var(--text-primary); font-family: inherit; font-size: 14px; flex: 1; min-width: 200px; }
        .filter-btn { background: var(--brand-primary); color: white; border: none; padding: 0 25px; height: 45px; border-radius: 8px; cursor: pointer; font-weight: bold; display: flex; align-items: center; gap: 8px; font-family: inherit; }
        .filter-btn:hover { background: #5a359c; }
        .filter-reset { background: var(--bg-secondary); color: var(--text-primary); border: 1px solid var(--border-color); padding: 0 20px; height: 45px; border-radius: 8px; text-decoration: none; display: flex; align-items: center; gap: 8px; font-weight: bold; }

        .tabs-nav { display: flex; gap: 10px; margin-bottom: 20px; overflow-x: auto; padding-bottom: 5px; border-bottom: 2px solid var(--border-color); }
        .tab-btn { background: transparent; color: var(--text-secondary); border: none; padding: 10px 20px; font-size: 16px; font-weight: bold; cursor: pointer; border-bottom: 3px solid transparent; transition: 0.3s; font-family: inherit; white-space: nowrap; }
        .tab-btn:hover { color: var(--brand-primary); }
        .tab-btn.active { color: var(--brand-primary); border-bottom-color: var(--brand-primary); }
        
        .tab-pane { display: none; animation: fadeIn 0.3s ease; }
        .tab-pane.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }

        .result-card { background: var(--bg-primary); padding: 15px; border-radius: 10px; border: 1px solid var(--border-color); display: flex; flex-direction: column; align-items: center; text-align: center; gap: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.02); }
        .result-card img { width: 50px; height: 50px; object-fit: contain; }
        .result-card h4 { margin: 0; font-size: 16px; color: var(--text-primary); word-break: break-word; }
        .result-actions { margin-top: auto; width: 100%; border-top: 1px solid var(--border-color); padding-top: 10px; display: flex; gap: 5px; }
        .result-actions a { flex: 1; padding: 8px; background: rgba(23,162,184,0.1); color: #17a2b8; text-decoration: none; border-radius: 6px; font-size: 13px; font-weight: bold; }
        .result-actions a:hover { background: #17a2b8; color: white; }
    </style>
</head>
<body class="admin-body" data-theme="<?php echo $_COOKIE['theme'] ?? 'light'; ?>">
    <?php include 'templates/sidebar.php'; ?>
    <main class="admin-main">
        <?php include 'templates/header.php'; ?>
        <div class="admin-content-wrapper">
            
            <h1 style="margin-bottom: 20px; display: flex; align-items: center; gap: 10px; color: var(--text-primary);">
                <i class="fa-solid fa-magnifying-glass" style="color: var(--brand-primary);"></i> محرك البحث المتقدم
            </h1>

            <div class="filter-box">
                <form method="GET" action="" class="filter-form">
                    <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" class="filter-input" placeholder="ابحث عن اسم طالب (Username)، أستاذ، مادة، عنوان محاضرة...">
                    
                    <select name="year_id" class="filter-input">
                        <option value="all">كل السنوات الجامعية</option>
                        <?php foreach ($academic_years as $y): ?>
                            <option value="<?= $y['id'] ?>" <?= ($s_yr == $y['id']) ? 'selected' : '' ?>><?= htmlspecialchars($y['year_label']) ?> <?= ($y['is_current'] == 1) ? '(الحالي)' : '' ?></option>
                        <?php endforeach; ?>
                    </select>

                    <select name="division_id" class="filter-input">
                        <option value="">كل الشعب والأقطاب</option>
                        <?php foreach ($divisions_opts as $d): ?>
                            <option value="<?= $d['division_id'] ?>" <?= ($s_div == $d['division_id']) ? 'selected' : '' ?>><?= htmlspecialchars($d['division_name']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <select name="class_id" class="filter-input">
                        <option value="">كل الفصول الدراسية</option>
                        <?php foreach ($classes_opts as $c): ?>
                            <option value="<?= $c['class_id'] ?>" <?= ($s_cls == $c['class_id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['class_name']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit" class="filter-btn"><i class="fa-solid fa-filter"></i> تطبيق وبحث</button>
                    <?php if($q || $s_div || $s_cls || $s_yr !== 'all'): ?>
                        <a href="search_engine.php" class="filter-reset"><i class="fa-solid fa-rotate-left"></i> إلغاء</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="tabs-nav">
                <button class="tab-btn active" onclick="openTab(event, 'tab-students')">
                    <i class="fa-solid fa-user-graduate"></i> الطلاب (<?= count($results['students']) ?>)
                </button>
                <button class="tab-btn" onclick="openTab(event, 'tab-professors')">
                    <i class="fa-solid fa-user-tie"></i> الأساتذة (<?= count($results['professors']) ?>)
                </button>
                <button class="tab-btn" onclick="openTab(event, 'tab-lectures')">
                    <i class="fa-solid fa-book-open"></i> المحاضرات (<?= count($results['lectures']) ?>)
                </button>
                <button class="tab-btn" onclick="openTab(event, 'tab-exams')">
                    <i class="fa-solid fa-file-signature"></i> الامتحانات (<?= count($results['exams']) ?>)
                </button>
                <button class="tab-btn" onclick="openTab(event, 'tab-subjects')">
                    <i class="fa-solid fa-layer-group"></i> المواد (<?= count($results['subjects']) ?>)
                </button>
            </div>

            <div id="tab-students" class="tab-pane active">
                <div class="academic-grid" style="grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));">
                    <?php if(empty($results['students'])) echo '<p style="color:var(--text-secondary); width:100%;">لا توجد نتائج مطابقة.</p>'; ?>
                    <?php foreach($results['students'] as $row): ?>
                        <div class="result-card">
                            <img src="dashboard_design/images/admin_icon_users.png" onerror="this.src='assets/icons/menu.png'">
                            <h4><?= htmlspecialchars(extractName($row)) ?></h4>
                            <div class="result-actions">
                                <a href="users/edit.php?id=<?= extractId($row) ?>"><i class="fa-solid fa-pen"></i> إدارة</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div id="tab-professors" class="tab-pane">
                <div class="academic-grid" style="grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));">
                    <?php if(empty($results['professors'])) echo '<p style="color:var(--text-secondary); width:100%;">لا توجد نتائج مطابقة.</p>'; ?>
                    <?php foreach($results['professors'] as $row): ?>
                        <div class="result-card">
                            <img src="dashboard_design/images/admin_icon_professors.png" onerror="this.src='assets/icons/menu.png'">
                            <h4><?= htmlspecialchars(extractName($row)) ?></h4>
                            <div class="result-actions">
                                <a href="professors/edit.php?id=<?= extractId($row) ?>"><i class="fa-solid fa-pen"></i> إدارة</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div id="tab-lectures" class="tab-pane">
                <div class="academic-grid" style="grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));">
                    <?php if(empty($results['lectures'])) echo '<p style="color:var(--text-secondary); width:100%;">لا توجد نتائج مطابقة.</p>'; ?>
                    <?php foreach($results['lectures'] as $row): ?>
                        <div class="result-card">
                            <img src="dashboard_design/images/admin_icon_lectures.png" onerror="this.src='assets/icons/menu.png'">
                            <h4><?= htmlspecialchars(extractName($row)) ?></h4>
                            <div class="result-actions">
                                <a href="lectures/edit.php?id=<?= extractId($row) ?>"><i class="fa-solid fa-pen"></i> إدارة</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div id="tab-exams" class="tab-pane">
                <div class="academic-grid" style="grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));">
                    <?php if(empty($results['exams'])) echo '<p style="color:var(--text-secondary); width:100%;">لا توجد نتائج مطابقة.</p>'; ?>
                    <?php foreach($results['exams'] as $row): ?>
                        <div class="result-card">
                            <img src="dashboard_design/images/admin_icon_exams.png" onerror="this.src='assets/icons/menu.png'">
                            <h4><?= htmlspecialchars(extractName($row)) ?></h4>
                            <div class="result-actions">
                                <a href="exams/edit.php?id=<?= extractId($row) ?>"><i class="fa-solid fa-pen"></i> إدارة</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div id="tab-subjects" class="tab-pane">
                <div class="academic-grid" style="grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));">
                    <?php if(empty($results['subjects'])) echo '<p style="color:var(--text-secondary); width:100%;">لا توجد نتائج مطابقة.</p>'; ?>
                    <?php foreach($results['subjects'] as $row): ?>
                        <div class="result-card">
                            <img src="dashboard_design/images/admin_icon_subjects.png" onerror="this.src='assets/icons/menu.png'">
                            <h4><?= htmlspecialchars(extractName($row)) ?></h4>
                            <div class="result-actions">
                                <a href="subjects/edit.php?id=<?= extractId($row) ?>"><i class="fa-solid fa-pen"></i> إدارة</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="assets/js/admin_global.js"></script>
    <script>
        function openTab(evt, tabId) {
            var i, tabPanes, tabBtns;
            tabPanes = document.getElementsByClassName("tab-pane");
            for (i = 0; i < tabPanes.length; i++) { tabPanes[i].classList.remove("active"); }
            tabBtns = document.getElementsByClassName("tab-btn");
            for (i = 0; i < tabBtns.length; i++) { tabBtns[i].classList.remove("active"); }
            document.getElementById(tabId).classList.add("active");
            evt.currentTarget.classList.add("active");
        }
    </script>
</body>
</html>