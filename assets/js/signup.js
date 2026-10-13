// assets/js/signup.js
$(document).ready(function() {
    const MAX_ENROLLMENTS = 2;
    const $divisionSelect = $('#division');
    const $enrollmentContainer = $('#enrollment-container');
    const $addBtn = $('#add-enrollment-btn');
    
    const AJAX_URL = 'ajax/get_data_for_signup.php'; 

    const trans = window.SignupTranslations || {};
    const t_loading = trans.loading || 'جاري التحميل...';
    const t_select_class = trans.select_class || '-- اختر الفصل --';
    const t_no_classes = trans.no_classes || 'لا توجد فصول متاحة حالياً';
    const t_conn_error = trans.conn_error || 'حدث خطأ في الاتصال';
    const t_selected = trans.selected || ' (تم اختياره)';
    const t_select_group = trans.select_group || '-- اختر الفوج (المقرر) --';
    const t_select_track = trans.select_track || '-- اختر المسار --';
    const t_select_track_first = trans.select_track_first || '-- اختر المسار أولاً --';
    const t_select_class_first = trans.select_class_first || '-- يرجى اختيار الفصل أولاً --';
    const t_select_course = trans.select_course || '-- اختر المقرر --';
    const t_group_prefix = trans.group_prefix || 'فوج:';
    const t_general_course = trans.general_course || 'مقرر عام (بدون أفواج)';
    const t_no_courses = trans.no_courses || 'لا توجد مقررات متاحة';
    const t_err_duplicate = trans.err_duplicate || 'خطأ منطقي: لا يمكنك التسجيل في نفس المقرر (الفوج) أكثر من مرة. يرجى اختيار مقررات مختلفة.';
    const t_lbl_class = trans.lbl_class || 'الفصل الدراسي';
    const t_lbl_track = trans.lbl_track || 'المسار (التخصص)';
    const t_lbl_course = trans.lbl_course || 'المقرر / الفوج';

    let divisionId = '';

    $divisionSelect.on('change', function() {
        divisionId = $(this).val();
        $enrollmentContainer.empty(); 
        
        if (divisionId) {
            addEnrollmentBlock();
            updateAddButton();
        } else {
            $addBtn.addClass('hidden').hide();
        }
    });

    $addBtn.on('click', function() {
        if ($('.enrollment-block').length < MAX_ENROLLMENTS) {
            addEnrollmentBlock();
            updateAddButton();
        }
    });

    $(document).on('click', '.remove-enrollment', function() {
        $(this).closest('.enrollment-block').remove();
        updateAddButton();
        updateClassDropdowns();
    });

    function updateAddButton() {
        const count = $('.enrollment-block').length;
        if (count >= MAX_ENROLLMENTS) {
            $addBtn.addClass('hidden').hide();
        } else if (count > 0) {
            $addBtn.removeClass('hidden').show().css('display', 'flex');
        } else {
            $addBtn.addClass('hidden').hide();
        }
    }

    function addEnrollmentBlock() {
        const blockId = Date.now();
        const blockHtml = `
            <div class="enrollment-block bg-gray-50 p-5 rounded-3xl border border-gray-200 relative mb-4">
                ${$('.enrollment-block').length > 0 ? `<button type="button" class="remove-enrollment absolute top-4 left-4 text-red-500 hover:text-red-700 font-bold"><i class="fa-solid fa-trash"></i></button>` : ''}
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-primary mb-2">${t_lbl_class}</label>
                        <select class="class-select signup-input w-full" data-block="${blockId}" required>
                            <option value="">${t_loading}</option>
                        </select>
                    </div>

                    <div class="track-container hidden" id="track-container-${blockId}">
                        <label class="block text-sm font-semibold text-primary mb-2">${t_lbl_track}</label>
                        <select class="track-select signup-input w-full" data-block="${blockId}">
                            <option value="">${t_select_track}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-primary mb-2">${t_lbl_course}</label>
                        <select name="offering_id[]" class="offering-select signup-input w-full" id="offering-${blockId}" data-block="${blockId}" required disabled>
                            <option value="">${t_select_class_first}</option>
                        </select>
                    </div>
                    
                    <div id="subjects-${blockId}" class="subjects-container mt-4"></div>
                </div>
            </div>
        `;
        
        $enrollmentContainer.append(blockHtml);
        const $newBlock = $enrollmentContainer.find('.enrollment-block').last();
        fetchClasses($newBlock.find('.class-select'));
    }

    function fetchClasses($select) {
        $.post(AJAX_URL, { action: 'get_classes', division_id: divisionId }, function(data) {
            $select.html(`<option value="">${t_select_class}</option>`);
            if (data && !data.error && data.length > 0) {
                data.forEach(c => {
                    $select.append(`<option value="${c.class_id}">${c.class_name}</option>`);
                });
                updateClassDropdowns();
            } else {
                $select.html(`<option value="">${t_no_classes}</option>`);
            }
        }, 'json').fail(function() {
             $select.html(`<option value="">${t_conn_error}</option>`);
        });
    }

    function updateClassDropdowns() {
        let selectedClasses = [];
        
        $('.class-select').each(function() {
            let val = $(this).val();
            if (val) {
                selectedClasses.push(val);
            }
        });

        $('.class-select').each(function() {
            let $currentSelect = $(this);
            let currentVal = $currentSelect.val();

            $currentSelect.find('option').each(function() {
                let optionVal = $(this).val();
                if (!optionVal) return; 

                if (selectedClasses.includes(optionVal) && optionVal !== currentVal) {
                    $(this).prop('disabled', true).css('color', '#999').text($(this).text().replace(t_selected, '') + t_selected);
                } else {
                    $(this).prop('disabled', false).css('color', '').text($(this).text().replace(t_selected, ''));
                }
            });
        });
    }

    $(document).on('change', '.class-select', function() {
        const $this = $(this);
        const blockId = $this.data('block');
        const classId = $this.val();
        const $trackContainer = $(`#track-container-${blockId}`);
        const $trackSelect = $trackContainer.find('.track-select');
        const $offeringSelect = $(`#offering-${blockId}`);
        const $subjectsContainer = $(`#subjects-${blockId}`);

        $offeringSelect.html(`<option value="">${t_select_group}</option>`).prop('disabled', true);
        $trackSelect.html(`<option value="">${t_select_track}</option>`);
        $subjectsContainer.empty();
        $trackContainer.addClass('hidden');

        updateClassDropdowns();

        if (classId) {
            if (classId == 5 || classId == 6) {
                $.post(AJAX_URL, { action: 'get_tracks', division_id: divisionId, class_id: classId }, function(data) {
                    if (data && !data.error && data.length > 0) {
                        $trackContainer.removeClass('hidden'); 
                        data.forEach(t => {
                            $trackSelect.append(`<option value="${t.track_id}">${t.track_name}</option>`);
                        });
                    } else {
                        fetchOfferings($offeringSelect, classId, null);
                    }
                }, 'json');
            } else {
                fetchOfferings($offeringSelect, classId, null);
            }
        }
    });

    $(document).on('change', '.track-select', function() {
        const blockId = $(this).data('block');
        const trackId = $(this).val();
        const classId = $(`.class-select[data-block="${blockId}"]`).val();
        const $offeringSelect = $(`#offering-${blockId}`);
        $(`#subjects-${blockId}`).empty();

        if (classId && trackId) {
            fetchOfferings($offeringSelect, classId, trackId);
        } else {
            $offeringSelect.html(`<option value="">${t_select_track_first}</option>`).prop('disabled', true);
        }
    });

    function fetchOfferings($select, classId, trackId) {
        $select.html(`<option value="">${t_loading}</option>`).prop('disabled', false);
        let postData = { action: 'get_offerings', division_id: divisionId, class_id: classId };
        if (trackId) postData.track_id = trackId;

        $.post(AJAX_URL, postData, function(data) {
            $select.html(`<option value="">${t_select_course}</option>`);
            if (data && !data.error && data.length > 0) {
                data.forEach(o => {
                    let groupName = o.group_name ? `${t_group_prefix} ${o.group_name}` : t_general_course;
                    $select.append(`<option value="${o.offering_id}">${groupName}</option>`);
                });
            } else {
                $select.html(`<option value="">${t_no_courses}</option>`).prop('disabled', true);
            }
        }, 'json');
    }

    $(document).on('change', '.offering-select', function() {
        const blockId = $(this).data('block');
        const offeringId = $(this).val();
        const $subjectsContainer = $(`#subjects-${blockId}`);
        $subjectsContainer.empty();

        if (offeringId) {
            $.post('signup.php', { action: 'get_subjects', offering_id: offeringId }, function(data) {
                if (data && data.length > 0) {
                    let html = `<div class="p-4 mt-2 bg-blue-50 border border-blue-200 rounded-xl">
                        <p class="text-sm font-bold text-blue-800 mb-3"><i class="fa-solid fa-circle-exclamation ml-1"></i> اختر المواد التي تريد الجميع او البعض:</p>
                        <div class="space-y-3">`;
                    data.forEach(sub => {
                        let profName = sub.professor_name ? ` (أستاذ: ${sub.professor_name})` : '';
                        html += `<label class="flex items-center gap-3 text-sm cursor-pointer p-2 hover:bg-blue-100 rounded transition-colors">
                            <input type="checkbox" name="subjects[${offeringId}][]" value="${sub.subject_id}" checked class="w-5 h-5 text-blue-600 rounded focus:ring-blue-500">
                            <span class="font-medium text-gray-800">${sub.subject_name} <span class="text-gray-500 text-xs">${profName}</span></span>
                        </label>`;
                    });
                    html += `</div></div>`;
                    $subjectsContainer.html(html);
                }
            }, 'json');
        }
    });

    $('#signupForm').on('submit', function(e) {
        let selectedOfferings = [];
        let hasDuplicates = false;

        $('.offering-select').each(function() {
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
            alert(t_err_duplicate);
            
            $('.offering-select').css('border', '2px solid red');
            setTimeout(() => {
                $('.offering-select').css('border', '');
            }, 3000);
        }
    });
});