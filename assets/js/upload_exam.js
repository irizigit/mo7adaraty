/* =========================================
   Upload Exam Page Scripts (upload_exam.js)
   ========================================= */
document.addEventListener('DOMContentLoaded', function() {
    
    // دالة تهيئة منطقة السحب والإفلات للامتحانات
    function setupDragAndDrop(zoneId, inputId, previewId, nameId, sizeId, removeBtnId, iconSrc) {
        const zone = document.getElementById(zoneId);
        const input = document.getElementById(inputId);
        const preview = document.getElementById(previewId);
        const nameElem = document.getElementById(nameId);
        const sizeElem = document.getElementById(sizeId);
        const removeBtn = document.getElementById(removeBtnId);
        const previewIcon = preview.querySelector('.file-preview-icon');

        if (!zone || !input) return;

        // تأثيرات السحب (Drag)
        ['dragenter', 'dragover'].forEach(eventName => {
            zone.addEventListener(eventName, preventDefaults, false);
            zone.addEventListener(eventName, () => zone.classList.add('dragover'), false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            zone.addEventListener(eventName, preventDefaults, false);
            zone.addEventListener(eventName, () => zone.classList.remove('dragover'), false);
        });

        // عند الإفلات (Drop)
        zone.addEventListener('drop', function(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            if (files.length > 0) {
                input.files = files; // تعيين الملف للإدخال
                updatePreview(files[0]);
            }
        }, false);

        // عند التحديد العادي
        input.addEventListener('change', function() {
            if (this.files.length > 0) {
                updatePreview(this.files[0]);
            }
        });

        // تحديث المعاينة
        function updatePreview(file) {
            nameElem.textContent = file.name;
            sizeElem.textContent = formatBytes(file.size);
            
            if(previewIcon && iconSrc) {
                previewIcon.src = iconSrc;
            }

            zone.style.display = 'none';
            preview.classList.add('show');
        }

        // إزالة الملف
        removeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            input.value = '';
            preview.classList.remove('show');
            zone.style.display = 'block';
        });
    }

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    function formatBytes(bytes, decimals = 2) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    }

    // تهيئة منطقة رفع الامتحان
    setupDragAndDrop('examDropZone', 'exam_file', 'examPreview', 'examFileName', 'examFileSize', 'removeExamBtn', null);
});