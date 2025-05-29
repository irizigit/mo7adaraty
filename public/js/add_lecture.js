document.addEventListener('DOMContentLoaded', () => {
    const addLectureForm = document.getElementById('addLectureForm');
    const messageDiv = document.getElementById('message');
    const downloadFilesContainer = document.getElementById('downloadFilesContainer');
    const addDownloadFileBtn = document.getElementById('addDownloadFileBtn');
    let downloadFileCount = 0; // لتعقب عدد حقول الملفات الإضافية

    // دالة لعرض الرسائل
    function showMessage(msg, type) {
        messageDiv.textContent = msg;
        messageDiv.className = `message ${type}`;
        messageDiv.style.display = 'block';
    }

    // دالة لإضافة حقل تحميل ملف إضافي
    function addDownloadFileInput() {
        const fileInputWrapper = document.createElement('div');
        fileInputWrapper.className = 'form-group download-file-item'; // Add a class for styling
        fileInputWrapper.innerHTML = `
            <label for="download_file_${downloadFileCount}">ملف التحميل:</label>
            <input type="file" id="download_file_${downloadFileCount}" name="download_files[]">
            <input type="text" placeholder="اسم الملف الظاهر (اختياري)" name="download_names[]">
            <button type="button" class="remove-file-btn">إزالة</button>
        `;
        downloadFilesContainer.appendChild(fileInputWrapper);
        downloadFileCount++;

        // إضافة مستمع لزر الإزالة
        fileInputWrapper.querySelector('.remove-file-btn').addEventListener('click', function() {
            fileInputWrapper.remove();
        });
    }

    // إضافة حقل تحميل ملف إضافي عند النقر على الزر
    addDownloadFileBtn.addEventListener('click', addDownloadFileInput);

    // معالجة إرسال النموذج
    addLectureForm.addEventListener('submit', async (e) => {
        e.preventDefault(); // منع الإرسال الافتراضي

        messageDiv.style.display = 'none'; // إخفاء الرسائل السابقة

        const formData = new FormData(addLectureForm); // استخدام FormData لرفع الملفات

        try {
            const response = await fetch('../server/add_lecture_handler.php', {
                method: 'POST',
                body: formData // FormData سيقوم بضبط Headers تلقائياً لرفع الملفات
            });

            const data = await response.json();

            if (data.success) {
                showMessage(data.message, 'success');
                addLectureForm.reset(); // مسح النموذج بعد النجاح
                downloadFilesContainer.innerHTML = ''; // مسح حقول الملفات الإضافية
                downloadFileCount = 0;
            } else {
                showMessage(data.message, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showMessage('حدث خطأ أثناء الاتصال بالخادم أو إضافة المحاضرة. الرجاء المحاولة مرة أخرى.', 'error');
        }
    });
});