document.addEventListener('DOMContentLoaded', () => {
    const lecturerForm = document.getElementById('lecturerForm');
    const lecturerIdInput = document.getElementById('lecturerId');
    const lecturerNameInput = document.getElementById('lecturerName');
    const lecturerEmailInput = document.getElementById('lecturerEmail');
    const lecturerBioInput = document.getElementById('lecturerBio');
    const semesterCheckboxes = document.querySelectorAll('input[name="taught_semesters[]"]');
    const submitLecturerBtn = document.getElementById('submitLecturerBtn');
    const cancelEditBtn = document.getElementById('cancelEditBtn');
    const lecturersTableBody = document.getElementById('lecturersTableBody');
    const messageDiv = document.getElementById('message');

    function showMessage(msg, type) {
        messageDiv.textContent = msg;
        messageDiv.className = `message ${type}`;
        messageDiv.style.display = 'block';
    }

    async function loadLecturers() {
        // بما أن الصفحة نفسها تقوم بتحميل الأساتذة، يمكننا إعادة تحميل الصفحة كلها
        window.location.reload();
    }

    // معالجة إرسال النموذج (إضافة أو تعديل)
    lecturerForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        messageDiv.style.display = 'none';

        const id = lecturerIdInput.value;
        const name = lecturerNameInput.value.trim();
        const email = lecturerEmailInput.value.trim();
        const bio = lecturerBioInput.value.trim();
        const selectedSemesters = Array.from(semesterCheckboxes)
                                   .filter(checkbox => checkbox.checked)
                                   .map(checkbox => checkbox.value);
        const action = id ? 'edit' : 'add';

        if (!name) {
            showMessage('اسم الأستاذ لا يمكن أن يكون فارغاً.', 'error');
            return;
        }

        try {
            const response = await fetch('../server/manage_lecturers_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action,
                    id,
                    name,
                    email,
                    bio,
                    taught_semesters: selectedSemesters
                })
            });

            const data = await response.json();

            if (data.success) {
                showMessage(data.message, 'success');
                lecturerForm.reset();
                lecturerIdInput.value = ''; // مسح الـ ID المخفي
                submitLecturerBtn.textContent = 'إضافة أستاذ';
                cancelEditBtn.style.display = 'none';
                semesterCheckboxes.forEach(checkbox => checkbox.checked = false); // إلغاء تحديد الكل
                loadLecturers(); // إعادة تحميل الجدول
            } else {
                showMessage(data.message, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showMessage('حدث خطأ أثناء الاتصال بالخادم.', 'error');
        }
    });

    // معالجة أزرار التعديل والحذف في الجدول
    lecturersTableBody.addEventListener('click', async (e) => {
        // زر التعديل
        if (e.target.classList.contains('edit-btn')) {
            const lecturerData = e.target.dataset; // جلب كل الـ data-* attributes
            lecturerIdInput.value = lecturerData.id;
            lecturerNameInput.value = lecturerData.name;
            lecturerEmailInput.value = lecturerData.email;
            lecturerBioInput.value = lecturerData.bio;

            // تحديد الفصول التي يدرسها الأستاذ
            const taughtSemesters = JSON.parse(lecturerData.semesters); // تحليل مصفوفة الفصول
            semesterCheckboxes.forEach(checkbox => {
                checkbox.checked = taughtSemesters.includes(checkbox.value);
            });

            submitLecturerBtn.textContent = 'حفظ التعديل';
            cancelEditBtn.style.display = 'inline-block';
            showMessage('أنت في وضع التعديل. عدّل البيانات واضغط حفظ التعديل.', 'info');
        }
        // زر الحذف
        else if (e.target.classList.contains('delete-btn')) {
            const id = e.target.dataset.id;
            if (confirm('هل أنت متأكد أنك تريد حذف هذا الأستاذ؟ (لن يتم الحذف إذا كان مرتبطاً بمحاضرات)')) {
                try {
                    const response = await fetch('../server/manage_lecturers_handler.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ action: 'delete', id })
                    });
                    const data = await response.json();
                    if (data.success) {
                        showMessage(data.message, 'success');
                        loadLecturers(); // إعادة تحميل الجدول
                    } else {
                        showMessage('فشل الحذف: ' + data.message, 'error');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showMessage('حدث خطأ أثناء الاتصال بالخادم للحذف.', 'error');
                }
            }
        }
    });

    // زر إلغاء التعديل
    cancelEditBtn.addEventListener('click', () => {
        lecturerForm.reset();
        lecturerIdInput.value = '';
        submitLecturerBtn.textContent = 'إضافة أستاذ';
        cancelEditBtn.style.display = 'none';
        messageDiv.style.display = 'none';
        semesterCheckboxes.forEach(checkbox => checkbox.checked = false); // إلغاء تحديد الكل
    });
});