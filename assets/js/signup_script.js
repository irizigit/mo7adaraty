$(document).ready(function() {
    const MAX_ENROLLMENTS = 2; // الحد الأقصى للمقررات المسموح التسجيل فيها
    const $divisionSelect = $('#division');
    const $enrollmentContainer = $('#enrollment-container');
    const $addBtn = $('#add-enrollment-btn');

    let divisionId = '';

    // 1. عند اختيار الشعبة
    $divisionSelect.on('change', function() {
        divisionId = $(this).val();
        $enrollmentContainer.empty(); // مسح أي مقررات سابقة إذا تم تغيير الشعبة
        
        if (divisionId) {
            addEnrollmentBlock(); // إضافة أول قالب اختيار تلقائياً
            updateAddButton();
        } else {
            $addBtn.addClass('hidden').hide();
        }
    });

    // 2. زر إضافة مقرر آخر
    $addBtn.on('click', function() {
        if ($('.enrollment-block').length < MAX_ENROLLMENTS) {
            addEnrollmentBlock();
            updateAddButton();
        }
    });

    // 3. زر حذف مقرر
    $(document).on('click', '.remove-enrollment', function() {
        $(this).closest('.enrollment-block').remove();
        updateAddButton();
    });

    // تحديث حالة زر الإضافة وزر الحذف
    function updateAddButton() {
        const count = $('.enrollment-block').length;
        
        // إظهار/إخفاء زر "إضافة مقرر آخر"
        if (count > 0 && count < MAX_ENROLLMENTS) {
            $addBtn.removeClass('hidden').css('display', 'flex');
        } else {
            $addBtn.addClass('hidden').hide();
        }
        
        // لا نسمح بحذف المقرر إذا كان هو الوحيد المتبقي
        if (count > 1) {
            $('.remove-enrollment').removeClass('hidden');
        } else {
            $('.remove-enrollment').addClass('hidden');
        }
    }

    // الدالة المعمارية: توليد قالب اختيار المقرر ديناميكياً
    function addEnrollmentBlock() {
        const blockId = Date.now(); // معرّف فريد للقالب
        const blockHtml = `
            <div class="enrollment-block p-5 border border-gray-200 rounded-2xl bg-gray-50/50 relative shadow-sm" data-id="${blockId}" style="display: none;">
                <button type="button" class="remove-enrollment absolute top-3 left-3 text-red-500 hover:text-red-700 hidden" title="إلغاء هذا المقرر">
                    <i class="fa-solid fa-trash-can text-lg"></i>
                </button>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-primary mb-2">الفصل <span class="text-red-500">*</span></label>
                        <select name="class_id[]" class="signup-input w-full class-select" required>
                            <option value="">جاري التحميل...</option>
                        </select>
                    </div>
                    
                    <div class="track-group hidden">
                        <label class="block text-sm font-semibold text-primary mb-2">المسار <span class="text-red-500">*</span></label>
                        <select name="track_id[]" class="signup-input w-full track-select">
                            <option value="">-- اختر المسار --</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-primary mb-2">الفوج / المقرر <span class="text-red-500">*</span></label>
                        <select name="offering_id[]" class="signup-input w-full offering-select" required disabled>
                            <option value="">-- يرجى اختيار الفصل أولاً --</option>
                        </select>
                    </div>
                </div>
            </div>
        `;
        
        $enrollmentContainer.append(blockHtml);
        const $newBlock = $enrollmentContainer.find(`.enrollment-block[data-id="${blockId}"]`);
        $newBlock.slideDown(300); // تأثير حركي أنيق
        
        // جلب الفصول للقالب الجديد
        fetchClasses($newBlock.find('.class-select'));
        
        // تفعيل الذكاء الاصطناعي (Cascading) داخل هذا القالب فقط
        attachBlockEvents($newBlock);
    }

    // تفعيل التغييرات داخل قالب محدد (لكي لا تتداخل القوالب مع بعضها)
    function attachBlockEvents($block) {
        const $classSelect = $block.find('.class-select');
        const $trackGroup = $block.find('.track-group');
        const $trackSelect = $block.find('.track-select');
        const $offeringSelect = $block.find('.offering-select');

        $classSelect.on('change', function() {
            const classId = $(this).val();
            $trackSelect.html('<option value="">-- اختر المسار --</option>').prop('required', false);
            $offeringSelect.html('<option value="">-- يرجى اختيار الفصل/المسار --</option>').prop('disabled', true);
            
            if (!classId) {
                $trackGroup.addClass('hidden');
                return;
            }

            // الفصول 5 و 6 تتطلب مساراً (تعديل إذا كان لديك معرفات مختلفة)
            if (classId === '5' || classId === '6') {
                $trackGroup.removeClass('hidden');
                $trackSelect.prop('required', true);
                fetchTracks($trackSelect);
                $offeringSelect.html('<option value="">-- يرجى اختيار المسار أولاً --</option>');
            } else {
                $trackGroup.addClass('hidden');
                fetchOfferings($offeringSelect, classId, null);
            }
        });

        $trackSelect.on('change', function() {
            const trackId = $(this).val();
            const classId = $classSelect.val();
            if (trackId) {
                fetchOfferings($offeringSelect, classId, trackId);
            } else {
                $offeringSelect.html('<option value="">-- يرجى اختيار المسار أولاً --</option>').prop('disabled', true);
            }
        });
    }

    // دوال جلب البيانات عبر AJAX
    function fetchClasses($select) {
        $.post('ajax/get_data_for_signup.php', { action: 'get_classes', division_id: divisionId }, function(data) {
            $select.html('<option value="">-- اختر الفصل --</option>');
            if (data && !data.error) {
                data.forEach(c => {
                    $select.append(`<option value="${c.class_id}">${c.class_name}</option>`);
                });
            }
        }, 'json').fail(function() {
            $select.html('<option value="">-- خطأ في الاتصال بالخادم --</option>');
        });
    }

    function fetchTracks($select) {
        $select.html('<option value="">جاري التحميل...</option>');
        $.post('ajax/get_data_for_signup.php', { action: 'get_tracks', division_id: divisionId }, function(data) {
            $select.html('<option value="">-- اختر المسار --</option>');
            if (data && !data.error) {
                data.forEach(t => {
                    $select.append(`<option value="${t.track_id}">${t.track_name}</option>`);
                });
            }
        }, 'json');
    }

    function fetchOfferings($select, classId, trackId) {
        $select.html('<option value="">جاري التحميل...</option>').prop('disabled', false);
        let postData = { action: 'get_offerings', division_id: divisionId, class_id: classId };
        if (trackId) postData.track_id = trackId;

        $.post('ajax/get_data_for_signup.php', postData, function(data) {
            $select.html('<option value="">-- اختر الفوج (المقرر) --</option>');
            if (data && !data.error) {
                data.forEach(o => {
                    let groupName = o.group_name ? `فوج: ${o.group_name}` : 'مقرر عام (بدون أفواج)';
                    // القيمة التي سترسل للمعالجة هي offering_id
                    $select.append(`<option value="${o.offering_id}">${groupName}</option>`);
                });
            } else {
                $select.html(`<option value="" disabled>${data.error || 'لا توجد مقررات متاحة'}</option>`);
            }
        }, 'json');
    }
});