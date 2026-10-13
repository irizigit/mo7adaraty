<?php
// منع الوصول المباشر للصفحة بدون بيانات
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['target'])) {
    header("Location: index.php");
    exit();
}

$target = $_POST['target'];
$message = $_POST['message'] ?? 'جاري التحميل...';

// استخراج باقي البيانات لتمريرها في الفورم المخفي
$postData = $_POST;
unset($postData['target']);
unset($postData['message']);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>جاري المعالجة - Mo7adaraty</title>
    
    <link rel="icon" type="image/png" href="assets/images/favicon.png">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/loader.css">
</head>
<body class="loader-page">

    <div class="loader-wrapper" id="loader">
        <h1 class="site-title">محاضراتي Mo7adaraty</h1>
        
        <div class="logo-box">
            <div class="circle-bg"></div>
            <img src="assets/images/favicon.png" alt="شعار الموقع" class="loader-logo">
        </div>
        
        <div class="loading-status">
            <span id="status-text">جاري التهيئة...</span>
            <span id="percentage" class="percentage">0%</span>
        </div>
        
        <div class="progress-bar-bg">
            <div class="progress-bar" id="progress"></div>
        </div>
        
        <p class="loader-text" id="loader-text"><?= htmlspecialchars($message) ?></p>
        
        <div class="loader-steps">
            <div class="step-line">
                <div class="step-line-progress" id="step-progress"></div>
            </div>
            <div class="loader-step">
                <div class="step-icon" id="step1-icon">1</div>
                <div class="step-text">تشفير</div>
            </div>
            <div class="loader-step">
                <div class="step-icon" id="step2-icon">2</div>
                <div class="step-text">مصادقة</div>
            </div>
            <div class="loader-step">
                <div class="step-icon" id="step3-icon">3</div>
                <div class="step-text">معالجة</div>
            </div>
            <div class="loader-step">
                <div class="step-icon" id="step4-icon">4</div>
                <div class="step-text">توجيه</div>
            </div>
        </div>
        
        <form id="redirectForm" action="<?= htmlspecialchars($target) ?>" method="POST" style="display: none;">
            <?php foreach ($postData as $key => $value): ?>
                <?php if (is_array($value)): ?>
                    <?php foreach ($value as $v): ?>
                        <input type="hidden" name="<?= htmlspecialchars($key) ?>[]" value="<?= htmlspecialchars($v) ?>">
                    <?php endforeach; ?>
                <?php else: ?>
                    <input type="hidden" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($value) ?>">
                <?php endif; ?>
            <?php endforeach; ?>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const progressBar = document.getElementById('progress');
            const percentageText = document.getElementById('percentage');
            const statusText = document.getElementById('status-text');
            const loaderText = document.getElementById('loader-text');
            const stepProgress = document.getElementById('step-progress');
            const redirectForm = document.getElementById('redirectForm');

            // إعدادات خطوات معالجة البيانات
            const steps = [
                { icon: 'step1-icon', text: 'تشفير البيانات وحمايتها...', percent: 25 },
                { icon: 'step2-icon', text: 'التحقق والمصادقة...', percent: 50 },
                { icon: 'step3-icon', text: 'جاري معالجة الطلب...', percent: 75 },
                { icon: 'step4-icon', text: 'توجيه آمن للوجهة...', percent: 100 }
            ];

            let currentProgress = 0;
            let currentStepIndex = 0;

            function updateStepsUI(index) {
                for (let i = 0; i < steps.length; i++) {
                    let stepEl = document.getElementById(steps[i].icon);
                    if (!stepEl) continue;

                    if (i < index) {
                        stepEl.classList.add('completed');
                        stepEl.classList.remove('active');
                        stepEl.innerHTML = '<i class="fa-solid fa-check"></i>'; 
                    } else if (i === index) {
                        stepEl.classList.add('active');
                        stepEl.classList.remove('completed');
                    }
                }
                
                if(stepProgress) {
                    stepProgress.style.width = (index / (steps.length - 1)) * 100 + '%';
                }
            }

            updateStepsUI(0);
            if(statusText) statusText.textContent = steps[0].text;

            // تسريع محاكاة التحميل لأن المستخدم في مرحلة انتقال بين الصفحات
            const interval = setInterval(() => {
                currentProgress += Math.random() * 5 + 2; 
                if (currentProgress > 100) currentProgress = 100;

                let roundedProgress = Math.floor(currentProgress);
                
                if(progressBar) progressBar.style.width = roundedProgress + '%';
                if(percentageText) percentageText.textContent = roundedProgress + '%';

                if (currentStepIndex < steps.length && currentProgress >= steps[currentStepIndex].percent) {
                    if(statusText) statusText.textContent = steps[currentStepIndex].text;
                    updateStepsUI(currentStepIndex);
                    
                    if (currentStepIndex < steps.length - 1) {
                        currentStepIndex++;
                    }
                }

                if (currentProgress >= 100) {
                    clearInterval(interval);
                    
                    if(loaderText) loaderText.textContent = "تمت المعالجة بنجاح، جاري التوجيه...";
                    if(statusText) statusText.textContent = "مكتمل";
                    
                    let lastStepEl = document.getElementById(steps[steps.length-1].icon);
                    if(lastStepEl) {
                        lastStepEl.classList.add('completed');
                        lastStepEl.classList.remove('active');
                        lastStepEl.innerHTML = '<i class="fa-solid fa-check"></i>';
                    }
                    
                    document.querySelector('.loader-wrapper').classList.add('complete');
                    
                    // تلاشي الشاشة ثم إرسال النموذج تلقائياً
                    setTimeout(() => {
                        document.body.classList.add('fade-out');
                        setTimeout(() => {
                            if(redirectForm) {
                                redirectForm.submit();
                            }
                        }, 400);
                    }, 600);
                }
            }, 40); // سرعة تحديث الشريط (بالملي ثانية)
        });
    </script>
</body>
</html>