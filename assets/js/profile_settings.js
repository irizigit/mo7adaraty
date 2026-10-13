/* =========================================
   Profile Settings Page Scripts (profile_settings.js)
   ========================================= */
$(document).ready(function() {
    const MAX_ENROLLMENTS = 2;
    const ADVANCED_CLASSES_IDS = [5, 6];
    
    // توجيه الطلبات لنفس محرك البحث المستخدم في التسجيل لضمان عدم التكرار ودقة الفلترة
    const AJAX_URL = '../../ajax/get_data_for_signup.php'; 
    let enrollmentCounter = 0;

    function updateAddButtonVisibility() {
        const count = $('.enrollment-block').length;
        if (count >= MAX_ENROLLMENTS) {
            $('#add-enrollment-btn').hide();
        } else if (count > 0) {
            $('#add-enrollment-btn').show();
        } else {
            $('#add-enrollment-btn').hide();
        }
    }

    function addEnrollmentBlock() {
        if ($('.enrollment-block').length >= MAX_ENROLLMENTS) return;
        enrollmentCounter++;
        const blockId = `enrollment-block-${enrollmentCounter}`;

        // إضافة زر الحذف إذا لم يكن هذا هو المقرر الأول
        const removeBtnHtml = $('.enrollment-block').length > 0 
            ? `<button type="button" class="btn-remove-enrollment" style="position: absolute; top: 15px; left: 15px; background: none; border: none; color: #ef4444; font-size: 14px; font-weight: bold; cursor: pointer; display: flex; align-items: center; gap: 5px;"><i class="fa-solid fa-trash"></i> إلغاء المقرر</button>` 
            : '';

        const enrollmentHtml = `
            <div class="enrollment-block" id="${blockId}" style="background: var(--bg-surface); padding: 20px; border-radius: 12px; border: 1px solid var(--border-color); position: relative; margin-bottom: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                ${removeBtnHtml}
                <h4 style="margin-top: 0; color: var(--brand-primary); margin-bottom: 15px;">المقرر المضاف</h4>
                
                <div class="form-group">
                    <label class="form-label">الفصل:</label>
                    <select name="enrollments[${enrollmentCounter}][class_id]" class="form-control class-select" required>
                        <option value="">جاري التحميل...</option>
                    </select>
                </div>
                
                <div class="form-group track-group" style="display: none;">
                    <label class="form-label">المسار:</label>
                    <select name="enrollments[${enrollmentCounter}][track_id]" class="form-control track-select">
                        <option value="">-- اختر المسار --</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">الفوج:</label>
                    <select name="enrollments[${enrollmentCounter}][offering_id]" class="form-control group-select" required disabled>
                         <option value="">-- يرجى اختيار الفصل أولاً --</option>
                    </select>
                </div>

                <div class="subjects-container" style="margin-top: 15px;"></div>
            </div>`;
        
        $('#enrollment-container').append(enrollmentHtml);
        const $newBlock = $(`#${blockId}`);
        updateAddButtonVisibility();
        populateClasses($newBlock.find('.class-select'));
    }

    // حدث تغيير الشعبة الأساسية
    $('#division').on('change', function() {
        $('#enrollment-container').empty();
        enrollmentCounter = 0;
        if ($(this).val()) {
            addEnrollmentBlock();
        } else {
             $('#add-enrollment-btn').hide();
        }
    });

    // إضافة مقرر إضافي
    $('#add-enrollment-btn').on('click', addEnrollmentBlock);

    // حذف مقرر
    $('#enrollment-container').on('click', '.btn-remove-enrollment', function() {
        $(this).closest('.enrollment-block').remove();
        updateAddButtonVisibility();
        updateClassDropdowns();
    });

    function populateClasses($select) {
        const divisionID = $('#division').val();
        $.post(AJAX_URL, { action: 'get_classes', division_id: divisionID }, function(classes) {
            $select.html('<option value="">-- اختر الفصل --</option>');
            if (classes && classes.length > 0) {
                classes.forEach(cls => {
                    $select.append(`<option value="${cls.class_id}">${cls.class_name}</option>`);
                });
                updateClassDropdowns();
            } else {
                $select.html('<option value="">لا توجد فصول متاحة</option>');
            }
        }, 'json').fail(function() {
            $select.html('<option value="">خطأ في الاتصال</option>');
        });
    }

    // منع اختيار نفس الفصل مرتين
    function updateClassDropdowns() {
        let selectedClasses = [];
        $('.class-select').each(function() {
            let val = $(this).val();
            if (val) selectedClasses.push(val);
        });

        $('.class-select').each(function() {
            let $currentSelect = $(this);
            let currentVal = $currentSelect.val();

            $currentSelect.find('option').each(function() {
                let optionVal = $(this).val();
                if (!optionVal) return;

                if (selectedClasses.includes(optionVal) && optionVal !== currentVal) {
                    $(this).prop('disabled', true).css('color', '#999').text($(this).text().replace(' (تم اختياره)', '') + ' (تم اختياره)');
                } else {
                    $(this).prop('disabled', false).css('color', '').text($(this).text().replace(' (تم اختياره)', ''));
                }
            });
        });
    }

    $('#enrollment-container').on('change', '.class-select', function() {
        const block = $(this).closest('.enrollment-block');
        const classID = parseInt($(this).val());
        const divisionID = $('#division').val();
        
        const $trackGroup = block.find('.track-group');
        const $trackSelect = block.find('.track-select');
        const $groupSelect = block.find('.group-select');
        const $subjectsContainer = block.find('.subjects-container');

        $trackGroup.hide();
        $trackSelect.html('').prop('required', false);
        $groupSelect.html('<option value="">-- اختر الفوج --</option>').prop('disabled', true);
        $subjectsContainer.empty();

        updateClassDropdowns();

        if (classID) {
            if (ADVANCED_CLASSES_IDS.includes(classID)) {
                $trackGroup.show();
                $trackSelect.prop('required', true);
                $.post(AJAX_URL, { action: 'get_tracks', division_id: divisionID, class_id: classID }, function(tracks) {
                    $trackSelect.html('<option value="">-- اختر المسار --</option>');
                    if(tracks && tracks.length > 0) {
                        tracks.forEach(track => {
                            $trackSelect.append(`<option value="${track.track_id}">${track.track_name}</option>`);
                        });
                    }
                }, 'json');
            } else {
                fetchOfferings($groupSelect, divisionID, classID, null);
            }
        }
    });

    $('#enrollment-container').on('change', '.track-select', function() {
        const block = $(this).closest('.enrollment-block');
        const classID = block.find('.class-select').val();
        const divisionID = $('#division').val();
        const trackID = $(this).val();
        const $groupSelect = block.find('.group-select');
        const $subjectsContainer = block.find('.subjects-container');

        $groupSelect.html('<option value="">-- اختر الفوج --</option>').prop('disabled', true);
        $subjectsContainer.empty();

        if (trackID) {
            fetchOfferings($groupSelect, divisionID, classID, trackID);
        }
    });

    function fetchOfferings($select, divisionID, classID, trackID) {
        $select.html('<option value="">جاري التحميل...</option>').prop('disabled', false);
        let postData = { action: 'get_offerings', division_id: divisionID, class_id: classID };
        if (trackID) postData.track_id = trackID;

        $.post(AJAX_URL, postData, function(data) {
            $select.html('<option value="">-- اختر المقرر --</option>');
            if (data && !data.error && data.length > 0) {
                data.forEach(o => {
                    let groupName = o.group_name ? `فوج: ${o.group_name}` : 'مقرر عام (بدون أفواج)';
                    $select.append(`<option value="${o.offering_id}">${groupName}</option>`);
                });
            } else {
                $select.html('<option value="">لا توجد أفواج متاحة لهذا الاختيار</option>').prop('disabled', true);
            }
        }, 'json');
    }

    // جلب المواد عند اختيار الفوج
    $('#enrollment-container').on('change', '.group-select', function() {
        const offeringId = $(this).val();
        const $subjectsContainer = $(this).closest('.enrollment-block').find('.subjects-container');
        $subjectsContainer.empty();

        if (offeringId) {
            $.post('../../signup.php', { action: 'get_subjects', offering_id: offeringId }, function(data) {
                if (data && data.length > 0) {
                    let html = `<div style="padding: 15px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px;">
                        <p style="font-size: 14px; font-weight: bold; color: #1e40af; margin-bottom: 12px;"><i class="fa-solid fa-list-check" style="margin-left: 5px;"></i> اختر المواد التي تريد دراستها:</p>
                        <div style="display: flex; flex-direction: column; gap: 10px;">`;
                    data.forEach(sub => {
                        let profName = sub.professor_name ? ` (أستاذ: ${sub.professor_name})` : '';
                        html += `<label style="display: flex; align-items: center; gap: 10px; font-size: 14px; cursor: pointer; padding: 8px; background: #fff; border-radius: 6px; border: 1px solid #d1d5db;">
                            <input type="checkbox" name="subjects[${offeringId}][]" value="${sub.subject_id}" checked style="width: 18px; height: 18px; accent-color: #2563eb;">
                            <span style="font-weight: 500; color: #1f2937;">${sub.subject_name} <span style="color: #6b7280; font-size: 12px;">${profName}</span></span>
                        </label>`;
                    });
                    html += `</div></div>`;
                    $subjectsContainer.html(html);
                }
            }, 'json');
        }
    });

    // التحقق قبل الإرسال لمنع اختيار نفس الفوج مرتين
    $('#enrollmentChangeForm').on('submit', function(e) {
        let selectedOfferings = [];
        let hasDuplicates = false;

        $('.group-select').each(function() {
            let val = $(this).val();
            if (val) {
                if (selectedOfferings.includes(val)) {
                    hasDuplicates = true;
                }
                selectedOfferings.push(val);
            }
        });

        if (hasDuplicates) {
            e.preventDefault(); 
            alert('لا يمكنك طلب التغيير لنفس المقرر (الفوج) مرتين. يرجى اختيار مقررات مختلفة.');
        }
    });
});