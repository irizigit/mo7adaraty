<?php
session_start();
require_once '../../config/db_connexion.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

try {
    $divisions =$pdo->query("SELECT division_id, division_name FROM divisions ORDER BY division_name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $classes =$pdo->query("SELECT class_id, class_name FROM classes ORDER BY class_name ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("خطأ في قاعدة البيانات.");
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>رفع استعمال الزمن - محاضراتي</title>
    <link rel="icon" type="image/svg+xml" href="/mo7adaraty/assets/images/favicon.svg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --brand-primary: #1a73e8;
            --brand-hover: #1557b0;
            --bg-body: #f8fafc;
            --bg-surface: #ffffff;
            --border-color: #e2e8f0;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --radius-md: 12px;
            --radius-sm: 8px;
            --success: #10b981;
            --danger: #ef4444;
        }

        * { box-sizing: border-box; }
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-primary);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            max-width: 700px;
            width: 100%;
            background: var(--bg-surface);
            padding: 35px;
            border-radius: var(--radius-md);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.03);
            border: 1px solid var(--border-color);
        }

        .header-title {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 25px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 15px;
        }

        .header-title i {
            font-size: 28px;
            color: var(--brand-primary);
        }

        .header-title h2 {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
        }

        .alert {
            padding: 14px 18px;
            margin-bottom: 20px;
            border-radius: var(--radius-sm);
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-success { background-color: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-error { background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

        .form-group { margin-bottom: 20px; }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 14px;
            color: var(--text-primary);
        }

        select, textarea, input[type="text"], input[type="url"], input[type="file"] {
            display: block;
            width: 100%;
            padding: 12px 14px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            font-family: inherit;
            font-size: 14px;
            background: #fff;
            color: var(--text-primary);
            transition: all 0.2s ease;
        }

        select:focus, textarea:focus, input[type="text"]:focus, input[type="url"]:focus {
            outline: none;
            border-color: var(--brand-primary);
            box-shadow: 0 0 0 3px rgba(26, 115, 232, 0.15);
        }

        .url-input-group {
            display: flex;
            gap: 8px;
        }

        .url-input-group input {
            direction: ltr;
            text-align: left;
            font-family: monospace;
        }

        .btn-preview {
            background-color: #0ea5e9;
            color: #fff;
            border: none;
            padding: 0 16px;
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-family: inherit;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
            transition: background 0.2s ease;
        }
        .btn-preview:hover { background-color: #0284c7; }

        .preview-box {
            display: none;
            margin-top: 15px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            overflow: hidden;
            background: #0f172a;
        }

        .preview-box-header {
            padding: 10px 14px;
            background: #1e293b;
            color: #f8fafc;
            font-size: 13px;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .preview-box iframe {
            width: 100%;
            height: 480px;
            border: none;
            display: block;
        }

        .btn-submit {
            background-color: var(--brand-primary);
            color: white;
            padding: 13px 20px;
            border: none;
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-size: 15px;
            font-weight: 700;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background 0.2s ease, transform 0.1s ease;
        }
        .btn-submit:hover { background-color: var(--brand-hover); }
        .btn-submit:active { transform: scale(0.99); }

        .links {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            color: var(--brand-primary);
            font-weight: 600;
            font-size: 14px;
            transition: color 0.2s ease;
        }
        .links:hover { color: var(--brand-hover); text-decoration: underline; }

        .hint {
            font-size: 12px;
            color: var(--text-secondary);
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-title">
            <i class="fa-brands fa-google-drive"></i>
            <h2>إضافة أو تحديث استعمال الزمن</h2>
        </div>
        
        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fa-solid fa-circle-check"></i>
                <span><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></span>
            </div>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></span>
            </div>
        <?php endif; ?>

        <form action="upload_process.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="division_id">الشعبة: <span style="color:var(--danger)">*</span></label>
                <select name="division_id" id="division_id" required>
                    <option value="">-- اختر الشعبة --</option>
                    <?php foreach($divisions as$div): ?>
                        <option value="<?php echo $div['division_id']; ?>"><?php echo htmlspecialchars($div['division_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="class_id">الفصل: <span style="color:var(--danger)">*</span></label>
                <select name="class_id" id="class_id" required disabled>
                    <option value="">-- اختر الشعبة أولاً --</option>
                    <?php foreach($classes as$cls): ?>
                        <option value="<?php echo $cls['class_id']; ?>"><?php echo htmlspecialchars($cls['class_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group" id="track_group" style="display: none;">
                <label for="track_id">المسار: <span style="color:var(--danger)">*</span></label>
                <select name="track_id" id="track_id">
                    <option value="">-- اختر المسار --</option>
                </select>
            </div>

            <div class="form-group">
                <label for="group_id">الفوج (المجموعة): <span style="color:var(--danger)">*</span></label>
                <select name="group_id" id="group_id" required disabled>
                    <option value="">-- اختر الفصل أولاً --</option>
                </select>
            </div>

            <div class="form-group">
                <label for="timetable_url">رابط ملف Google Drive: <span style="color:var(--danger)">*</span></label>
                <div class="url-input-group">
                    <input type="url" name="timetable_url" id="timetable_url" placeholder="https://drive.google.com/file/d/.../view" required>
                    <button type="button" class="btn-preview" id="btnPreview" onclick="triggerDrivePreview()">
                        <i class="fa-solid fa-eye"></i> معاينة فورية
                    </button>
                </div>
                <div class="hint">
                    تأكد من تفعيل صلاحية المشاركة في Drive على خيار (Anyone with the link can view).
                </div>

                <div class="preview-box" id="previewBox">
                    <div class="preview-box-header">
                        <span><i class="fa-solid fa-file-pdf"></i> معاينة مباشرة للملف المرفق</span>
                        <a href="#" id="previewExternalLink" target="_blank" style="color:#38bdf8; text-decoration:none; font-size:12px;">
                            <i class="fa-solid fa-arrow-up-right-from-square"></i> فتح في علامة تبويب جديدة
                        </a>
                    </div>
                    <iframe id="driveFrame" src="" allow="autoplay"></iframe>
                </div>
            </div>

            <div class="form-group">
                <label for="schedule_file">أو رفع نسخة ملف محلي (PDF أو صور - اختياري):</label>
                <input type="file" name="schedule_file" id="schedule_file" accept="image/*,application/pdf">
            </div>

            <div class="form-group">
                <label for="update_reason">سبب الرفع أو التحديث: <span style="color:var(--danger)">*</span></label>
                <textarea name="update_reason" id="update_reason" rows="3" placeholder="مثال: إضافة جدول الدورة الخريفية، تغيير توقيت قاعة، تحديث الفوج..." required></textarea>
            </div>

            <button type="submit" class="btn-submit">
                <i class="fa-solid fa-cloud-arrow-up"></i> حفظ وإرسال استعمال الزمن
            </button>
            <a href="my_schedules.php" class="links">
                <i class="fa-solid fa-arrow-right"></i> العودة إلى قائمة جداولي
            </a>
        </form>
    </div>

    <script>
        function extractDriveId(url) {
            if (!url) return null;
            const match = url.match(/\/d\/([a-zA-Z0-9_-]+)/) || url.match(/[?&]id=([a-zA-Z0-9_-]+)/);
            return match ? match[1] : null;
        }

        function triggerDrivePreview() {
            const urlInput = document.getElementById('timetable_url');
            const previewBox = document.getElementById('previewBox');
            const driveFrame = document.getElementById('driveFrame');
            const externalLink = document.getElementById('previewExternalLink');

            const url = urlInput.value.trim();
            const fileId = extractDriveId(url);

            if (!fileId) {
                alert('يرجى إدخال رابط Google Drive صالح يحتوي على معرف الملف.');
                previewBox.style.display = 'none';
                driveFrame.src = '';
                return;
            }

            const previewUrl = `https://drive.google.com/file/d/${fileId}/preview`;
            driveFrame.src = previewUrl;
            externalLink.href = url;
            previewBox.style.display = 'block';
        }

        document.getElementById('timetable_url').addEventListener('change', function() {
            if (this.value.trim()) {
                triggerDrivePreview();
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            const divisionSelect = document.getElementById('division_id');
            const classSelect = document.getElementById('class_id');
            const trackGroup = document.getElementById('track_group');
            const trackSelect = document.getElementById('track_id');
            const groupSelect = document.getElementById('group_id');

            function resetSelect(element, defaultText) {
                element.innerHTML = `<option value="">-- ${defaultText} --</option>`;
                element.disabled = true;
            }

            divisionSelect.addEventListener('change', function() {
                if(this.value) {
                    classSelect.disabled = false;
                    classSelect.value = "";
                } else {
                    classSelect.disabled = true;
                }
                trackGroup.style.display = 'none';
                trackSelect.required = false;
                resetSelect(trackSelect, 'اختر المسار');
                resetSelect(groupSelect, 'اختر الفصل أولاً');
            });

            classSelect.addEventListener('change', function() {
                const classId = this.value;
                const divisionId = divisionSelect.value;
                
                resetSelect(trackSelect, 'اختر المسار');
                resetSelect(groupSelect, 'جاري التحميل...');

                if (!classId) return;

                if (classId === '5' || classId === '6') {
                    trackGroup.style.display = 'block';
                    trackSelect.required = true;
                    trackSelect.disabled = false;
                    
                    fetch('../../admin/timetables/get_tracks.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `division_id=${divisionId}`
                    })
                    .then(res => res.json())
                    .then(data => {
                        trackSelect.innerHTML = `<option value="">-- اختر المسار --</option>`;
                        if (!data.error && Array.isArray(data)) {
                            data.forEach(t => trackSelect.innerHTML += `<option value="${t.track_id}">${t.track_name}</option>`);
                        }
                    });
                    
                    resetSelect(groupSelect, 'الرجاء اختيار المسار أولاً');
                } else {
                    trackGroup.style.display = 'none';
                    trackSelect.required = false;
                    fetchGroups(classId, null);
                }
            });

            trackSelect.addEventListener('change', function() {
                const trackId = this.value;
                const classId = classSelect.value;
                if (trackId) {
                    fetchGroups(classId, trackId);
                } else {
                    resetSelect(groupSelect, 'الرجاء اختيار المسار أولاً');
                }
            });

            function fetchGroups(classId, trackId) {
                const divisionId = divisionSelect.value;
                groupSelect.disabled = false;
                groupSelect.innerHTML = `<option value="">جاري التحميل...</option>`;
                
                let bodyData = `class_id=${classId}&division_id=${divisionId}`;
                if (trackId) {
                    bodyData += `&track_id=${trackId}`;
                }

                fetch('../../admin/timetables/get_groups.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: bodyData
                })
                .then(res => res.json())
                .then(data => {
                    groupSelect.innerHTML = `<option value="">-- اختر المجموعة --</option>`;
                    if (data.error) {
                        groupSelect.innerHTML = `<option value="">${data.error}</option>`;
                    } else if (Array.isArray(data)) {
                        data.forEach(g => groupSelect.innerHTML += `<option value="${g.group_id}">${g.group_name}</option>`);
                    }
                });
            }
        });
    </script>
</body>
</html>