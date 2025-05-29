document.addEventListener('DOMContentLoaded', () => {
    const categoryForm = document.getElementById('categoryForm');
    const categoryIdInput = document.getElementById('categoryId');
    const categoryNameInput = document.getElementById('categoryName');
    const submitCategoryBtn = document.getElementById('submitCategoryBtn');
    const cancelEditBtn = document.getElementById('cancelEditBtn');
    const categoriesTableBody = document.getElementById('categoriesTableBody');
    const messageDiv = document.getElementById('message');

    // دالة لعرض الرسائل
    function showMessage(msg, type) {
        messageDiv.textContent = msg;
        messageDiv.className = `message ${type}`;
        messageDiv.style.display = 'block';
    }

    // دالة لإعادة تحميل جدول التصنيفات
    async function loadCategories() {
        try {
            // بما أن الصفحة نفسها تقوم بتحميل التصنيفات، يمكننا إعادة تحميل الصفحة كلها
            // أو عمل طلب AJAX جديد لجلب البيانات
            // للخيار الثاني:
            // const response = await fetch('../data/categories.json');
            // const categories = await response.json();
            // displayCategories(categories);
            window.location.reload(); // أسهل طريقة للتحديث الكامل بعد التعديل/الحذف
        } catch (error) {
            console.error('Error loading categories:', error);
            showMessage('فشل تحميل التصنيفات.', 'error');
        }
    }

    // معالجة إرسال النموذج (إضافة أو تعديل)
    categoryForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        messageDiv.style.display = 'none';

        const id = categoryIdInput.value;
        const name = categoryNameInput.value.trim();
        const action = id ? 'edit' : 'add'; // تحديد الإجراء بناءً على وجود الـ ID

        if (!name) {
            showMessage('اسم التصنيف لا يمكن أن يكون فارغاً.', 'error');
            return;
        }

        try {
            const response = await fetch('../server/manage_categories_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ action, id, name })
            });

            const data = await response.json();

            if (data.success) {
                showMessage(data.message, 'success');
                categoryForm.reset();
                categoryIdInput.value = ''; // مسح الـ ID المخفي
                submitCategoryBtn.textContent = 'إضافة تصنيف';
                cancelEditBtn.style.display = 'none';
                loadCategories(); // إعادة تحميل الجدول
            } else {
                showMessage(data.message, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showMessage('حدث خطأ أثناء الاتصال بالخادم.', 'error');
        }
    });

    // معالجة أزرار التعديل والحذف في الجدول
    categoriesTableBody.addEventListener('click', async (e) => {
        // زر التعديل
        if (e.target.classList.contains('edit-btn')) {
            const id = e.target.dataset.id;
            const name = e.target.dataset.name;
            categoryIdInput.value = id;
            categoryNameInput.value = name;
            submitCategoryBtn.textContent = 'حفظ التعديل';
            cancelEditBtn.style.display = 'inline-block';
            showMessage('أنت في وضع التعديل. عدّل الاسم واضغط حفظ التعديل.', 'info');
        }
        // زر الحذف
        else if (e.target.classList.contains('delete-btn')) {
            const id = e.target.dataset.id;
            if (confirm('هل أنت متأكد أنك تريد حذف هذا التصنيف؟ (لن يتم الحذف إذا كان يحتوي على محاضرات مرتبطة)')) {
                try {
                    const response = await fetch('../server/manage_categories_handler.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ action: 'delete', id })
                    });
                    const data = await response.json();
                    if (data.success) {
                        showMessage(data.message, 'success');
                        loadCategories(); // إعادة تحميل الجدول
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
        categoryForm.reset();
        categoryIdInput.value = '';
        submitCategoryBtn.textContent = 'إضافة تصنيف';
        cancelEditBtn.style.display = 'none';
        messageDiv.style.display = 'none';
    });
});