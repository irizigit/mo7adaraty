document.addEventListener('DOMContentLoaded', () => {
    const editLectureForm = document.getElementById('editLectureForm');
    const messageDiv = document.getElementById('message');
    const newDownloadFilesContainer = document.getElementById('newDownloadFilesContainer');
    const addDownloadFileBtn = document.getElementById('addDownloadFileBtn');
    const existingDownloadFilesContainer = document.getElementById('existingDownloadFilesContainer');

    let newDownloadFileCount = 0; // لتعقب عدد حقول الملفات الإضافية الجديدة

    // دالة لعرض الرسائل
    function showMessage(msg, type) {
        messageDiv.textContent = msg;
        messageDiv.className = `message ${type}`;
        messageDiv.style.display = 'block';
    }

    // دالة لإضافة حقل تحميل ملف إضافي جديد
    function addDownloadFileInput() {
        const fileInputWrapper = document.createElement('div');
        fileInputWrapper.className = 'form-group download-file-item';
        fileInputWrapper.innerHTML = `
            <label for="new_download_file_${newDownloadFileCount}">ملف تحميل جديد:</label>
            <input type="file" id="new_download_file_${newDownloadFileCount}" name="new_download_files[]">
            <input type="text" placeholder="اسم الملف الظاهر (اختياري)" name="new_download_names[]">
            <button type="button" class="remove-file-btn">إزالة</button>
        `;
        newDownloadFilesContainer.appendChild(fileInputWrapper);
        newDownloadFileCount++;

        // إضافة مستمع لزر الإزالة
        fileInputWrapper.querySelector('.remove-file-btn').addEventListener('click', function() {
            fileInputWrapper.remove();
        });
    }

    // إضافة حقل تحميل ملف إضافي جديد عند النقر على الزر
    addDownloadFileBtn.addEventListener('click', addDownloadFileInput);

    // معالج لإزالة الملفات الموجودة
    existingDownloadFilesContainer.addEventListener('click', async (e) => {
        if (e.target.classList.contains('remove-existing-file-btn')) {
            const filePath = e.target.dataset.path; // المسار الكامل للملف المطلوب حذفه
            const lectureId = editLectureForm.querySelector('input[name="id"]').value;

            if (confirm('هل أنت متأكد أنك تريد إزالة هذا الملف؟ سيتم حذفه نهائياً من الخادم.')) {
                try {
                    const response = await fetch('../server/delete_download_file_handler.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ lecture_id: lectureId, file_path: filePath })
                    });
                    const data = await response.json();
                    if (data.success) {
                        showMessage('تم حذف الملف بنجاح.', 'success');
                        e.target.closest('.download-file-item').remove(); // إزالة العنصر من الواجهة
                        // يمكن أيضاً إعادة تحميل المحاضرة لتحديث القائمة بالكامل
                        // window.location.reload();
                    } else {
                        showMessage('فشل حذف الملف: ' + data.message, 'error');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showMessage('حدث خطأ أثناء الاتصال بالخادم لحذف الملف.', 'error');
                }
            }
        }
    });

    // معالجة إرسال النموذج للتعديل
    editLectureForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        messageDiv.style.display = 'none';

        const formData = new FormData(editLectureForm);

        // لإضافة أسماء الملفات الجديدة (إذا كانت موجودة) يدوياً إلى FormData
        newDownloadFilesContainer.querySelectorAll('input[name^="new_download_names"]').forEach((input, index) => {
            // تحقق من أن حقل الملف المقابل غير فارغ
            const fileInput = document.getElementById(`new_download_file_${index}`);
            if (fileInput && fileInput.files.length > 0) {
                 formData.append(`new_download_names[${index}]`, input.value);
            }
        });


        try {
            const response = await fetch('../server/edit_lecture_handler.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                showMessage(data.message, 'success');
                // يمكن إعادة توجيه المستخدم إلى صفحة إدارة المحاضرات بعد التعديل
                // setTimeout(() => {
                //     window.location.href = 'manage_lectures.php';
                // }, 1500);
            } else {
                showMessage(data.message, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showMessage('حدث خطأ أثناء الاتصال بالخادم أو تعديل المحاضرة. الرجاء المحاولة مرة أخرى.', 'error');
        }
    });
});