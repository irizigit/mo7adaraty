/* =========================================
   Loader Page Scripts V2 - متوافق مع النظام الهجين ونظام تعدد اللغات (i18n)
   ========================================= */
document.addEventListener('DOMContentLoaded', function() {
    const progressBar = document.getElementById('progress');
    const percentageText = document.getElementById('percentage');
    const statusText = document.getElementById('status-text');
    const loaderText = document.getElementById('loader-text');
    const stepProgress = document.getElementById('step-progress');
    const retryButton = document.getElementById('retry-button');

    // جلب الترجمات من الكائن العالمي الذي تم إنشاؤه في ملف PHP
    const trans = window.loaderTranslations || {
        step1: 'جاري تهيئة بيئة العمل...',
        step2: 'الاتصال بقاعدة البيانات...',
        step3: 'تحميل ملفات النظام...',
        step4: 'تجهيز واجهة المستخدم...',
        complete: 'اكتمل التحميل، جاري التوجيه...',
        success: 'مكتمل بنجاح'
    };

    // إعدادات خطوات التحميل مع ربطها بالنصوص المترجمة
    const steps = [
        { icon: 'step1-icon', text: trans.step1, percent: 25 },
        { icon: 'step2-icon', text: trans.step2, percent: 50 },
        { icon: 'step3-icon', text: trans.step3, percent: 75 },
        { icon: 'step4-icon', text: trans.step4, percent: 100 }
    ];

    let currentProgress = 0;
    let currentStepIndex = 0;

    // دالة لتحديث واجهة الخطوات (الدوائر في الأسفل)
    function updateStepsUI(index) {
        for (let i = 0; i < steps.length; i++) {
            let stepEl = document.getElementById(steps[i].icon);
            if (!stepEl) continue;

            if (i < index) {
                // خطوة مكتملة
                stepEl.classList.add('completed');
                stepEl.classList.remove('active');
                // بما أننا أزلنا FontAwesome، نستخدم علامة الصح كـ SVG داخلي
                stepEl.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="width: 60%; height: 60%; color: white; margin: auto; display: block;"><polyline points="20 6 9 17 4 12"></polyline></svg>`;
            } else if (i === index) {
                // خطوة حالية
                stepEl.classList.add('active');
                stepEl.classList.remove('completed');
            } else {
                // خطوة قادمة
                stepEl.classList.remove('active', 'completed');
            }
        }
        
        // تحديث خط التقدم للخطوات
        if (stepProgress) {
            stepProgress.style.width = ((index) / (steps.length - 1)) * 100 + '%';
        }
    }

    const interval = setInterval(() => {
        currentProgress += Math.floor(Math.random() * 5) + 1; // زيادة عشوائية للواقعية
        if (currentProgress > 100) currentProgress = 100;

        // تحديث شريط التقدم والنسبة
        if (progressBar) progressBar.style.width = currentProgress + '%';
        if (percentageText) percentageText.textContent = currentProgress + '%';

        // تحديد الخطوة الحالية وتحديث النص
        if (currentStepIndex < steps.length && currentProgress >= steps[currentStepIndex].percent) {
            if (statusText) statusText.textContent = steps[currentStepIndex].text;
            updateStepsUI(currentStepIndex);
            
            if (currentStepIndex < steps.length - 1) {
                currentStepIndex++;
            }
        }

        // عند اكتمال التحميل 100%
        if (currentProgress >= 100) {
            clearInterval(interval);
            
            if(loaderText) loaderText.textContent = trans.complete;
            if(statusText) statusText.textContent = trans.success;
            
            // جعل الخطوة الأخيرة مكتملة وإظهار علامة (صح)
            let lastStepEl = document.getElementById(steps[steps.length-1].icon);
            if(lastStepEl) {
                lastStepEl.classList.add('completed');
                lastStepEl.classList.remove('active');
                lastStepEl.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="width: 60%; height: 60%; color: white; margin: auto; display: block;"><polyline points="20 6 9 17 4 12"></polyline></svg>`;
            }
            
            let loaderWrapper = document.querySelector('.loader-wrapper');
            if(loaderWrapper) loaderWrapper.classList.add('complete');
            
            // التلاشي والتوجيه للصفحة الرئيسية
            setTimeout(() => {
                document.body.classList.add('fade-out');
                setTimeout(() => {
                    window.location.href = 'home.php'; 
                }, 800);
            }, 1000);
        }
    }, 70); // سرعة التحديث (بالملي ثانية)
    
    // زر إعادة المحاولة في حال التوقف
    if (retryButton) {
        retryButton.addEventListener('click', () => {
            location.reload();
        });
    }
});