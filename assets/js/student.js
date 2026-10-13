/* =========================================
   Student Profile & Enrollment Scripts
   ========================================= */
$(document).ready(function() {
    const ADVANCED_CLASSES_IDS = [5, 6];
    
    $('#division').on('change', function() {
        if($(this).is('select')) { 
            const divisionID = $(this).val();
            $('#class').html('<option value="">-- اختر --</option>').prop('disabled', !divisionID);
            $('#track-group').hide();
            $('#group').html('<option value="">-- اختر --</option>').prop('disabled', true);

            if (divisionID) {
                $.post('../ajax/get_data_for_signup.php', { action: 'get_classes', division_id: divisionID }, function(data) {
                    try {
                        const classes = typeof data === 'string' ? JSON.parse(data) : data;
                        classes.forEach(cls => {
                            $('#class').append(`<option value="${cls.class_id}">${cls.class_name}</option>`);
                        });
                    } catch(e) { console.error("Error parsing classes:", e); }
                });
            }
        }
    });

    $('#class').on('change', function() {
        const classID = parseInt($(this).val());
        const divisionID = $('#division').val();
        $('#track-group').hide();
        $('#track').html('<option value="">-- اختر --</option>');
        $('#group').html('<option value="">-- اختر --</option>').prop('disabled', !classID);

        if (classID) {
            if (ADVANCED_CLASSES_IDS.includes(classID)) {
                $('#track-group').show();
                $.post('../ajax/get_data_for_signup.php', { action: 'get_tracks', division_id: divisionID }, function(data) {
                    try {
                        const tracks = typeof data === 'string' ? JSON.parse(data) : data;
                        tracks.forEach(track => {
                            $('#track').append(`<option value="${track.track_id}">${track.track_name}</option>`);
                        });
                    } catch(e) {}
                });
            } else {
                $.post('../ajax/get_data_for_signup.php', { action: 'get_groups', class_id: classID }, function(data) {
                    try {
                        const groups = typeof data === 'string' ? JSON.parse(data) : data;
                        groups.forEach(group => {
                            $('#group').append(`<option value="${group.offering_id}">${group.group_name}</option>`);
                        });
                        $('#group').prop('disabled', false);
                    } catch(e) {}
                });
            }
        }
    });

    $('#track').on('change', function() {
        const classID = $('#class').val();
        const trackID = $(this).val();
        $('#group').html('<option value="">-- اختر --</option>').prop('disabled', !trackID);

        if (trackID) {
            $.post('../ajax/get_data_for_signup.php', { action: 'get_groups', class_id: classID, track_id: trackID }, function(data) {
                try {
                    const groups = typeof data === 'string' ? JSON.parse(data) : data;
                    groups.forEach(group => {
                        $('#group').append(`<option value="${group.offering_id}">${group.group_name}</option>`);
                    });
                    $('#group').prop('disabled', false);
                } catch(e) {}
            });
        }
    });
});

$(document).ready(function() {
    const AJAX_URL = '../ajax/get_data_for_signup.php';
    const MAX_ENROLLMENTS = 2;
    const $divisionSelect = $('#division');
    const $enrollmentContainer = $('#enrollment-container');
    const $addBtn = $('#add-enrollment-btn');

    let divisionId = $divisionSelect.val();

    if (divisionId && $divisionSelect.is('input[type="hidden"]')) {
        addEnrollmentBlock();
        updateAddButton();
    }

    $divisionSelect.on('change', function() {
        if($(this).is('select')) {
            divisionId = $(this).val();
            $enrollmentContainer.empty();
            if (divisionId) {
                addEnrollmentBlock();
                updateAddButton();
            } else {
                $addBtn.hide();
            }
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
            $addBtn.hide();
        } else if (count > 0) {
            $addBtn.css('display', 'flex');
        } else {
            $addBtn.hide();
        }
    }

    function addEnrollmentBlock() {
        const blockId = Date.now();
        const blockHtml = `
            <div class="enrollment-block" style="background: var(--bg-surface); padding: 20px; border-radius: 12px; border: 1px solid var(--border-color); position: relative; margin-bottom: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
                ${$('.enrollment-block').length > 0 ? `<button type="button" class="remove-enrollment" style="position: absolute; top: 15px; left: 15px; background: none; border: none; color: #ef4444; font-size: 18px; cursor: pointer;" title="حذف المقرر"><i class="fa-solid fa-trash"></i></button>` : ''}
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: bold; color: var(--text-primary);">الفصل الدراسي</label>
                    <select class="class-select form-input" data-block="${blockId}" required style="width: 100%; padding: 12px; border-radius: 8px; background: var(--bg-color); color: var(--text-primary); border: 1px solid var(--border-color);">
                        <option value="">جاري التحميل...</option>
                    </select>
                </div>

                <div class="track-container" id="track-container-${blockId}" style="display: none; margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: bold; color: var(--text-primary);">المسار (التخصص)</label>
                    <select class="track-select form-input" data-block="${blockId}" style="width: 100%; padding: 12px; border-radius: 8px; background: var(--bg-color); color: var(--text-primary); border: 1px solid var(--border-color);">
                        <option value="">-- اختر المسار --</option>
                    </select>
                </div>

                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: bold; color: var(--text-primary);">المقرر / الفوج</label>
                    <select name="offering_id[]" class="offering-select form-input" id="offering-${blockId}" data-block="${blockId}" required disabled style="width: 100%; padding: 12px; border-radius: 8px; background: var(--bg-color); color: var(--text-primary); border: 1px solid var(--border-color);">
                        <option value="">-- يرجى اختيار الفصل أولاً --</option>
                    </select>
                </div>
                
                <div id="subjects-${blockId}" class="subjects-container" style="margin-top: 15px;"></div>
            </div>
        `;
        
        $enrollmentContainer.append(blockHtml);
        const $newBlock = $enrollmentContainer.find('.enrollment-block').last();
        fetchClasses($newBlock.find('.class-select'));
    }

    function fetchClasses($select) {
        $.post(AJAX_URL, { action: 'get_classes', division_id: divisionId }, function(data) {
            $select.html('<option value="">-- اختر الفصل --</option>');
            try {
                const classes = typeof data === 'string' ? JSON.parse(data) : data;
                if (classes && !classes.error && classes.length > 0) {
                    classes.forEach(c => {
                        $select.append(`<option value="${c.class_id}">${c.class_name}</option>`);
                    });
                    updateClassDropdowns(); 
                } else {
                    $select.html('<option value="">لا توجد فصول متاحة في هذا الموسم</option>');
                }
            } catch(e) {}
        }, 'json').fail(function() {
             $select.html('<option value="">حدث خطأ في الاتصال</option>');
        });
    }

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

    $(document).on('change', '.class-select', function() {
        const $this = $(this);
        const blockId = $this.data('block');
        const classId = $this.val();
        const $trackContainer = $(`#track-container-${blockId}`);
        const $trackSelect = $trackContainer.find('.track-select');
        const $offeringSelect = $(`#offering-${blockId}`);
        const $subjectsContainer = $(`#subjects-${blockId}`);

        $offeringSelect.html('<option value="">-- اختر الفوج (المقرر) --</option>').prop('disabled', true);
        $trackSelect.html('<option value="">-- اختر المسار --</option>');
        $subjectsContainer.empty();
        $trackContainer.hide();

        updateClassDropdowns(); 

        if (classId) {
            $.post(AJAX_URL, { action: 'get_tracks', division_id: divisionId, class_id: classId }, function(data) {
                try {
                    const tracks = typeof data === 'string' ? JSON.parse(data) : data;
                    if (tracks && !tracks.error && tracks.length > 0) {
                        $trackContainer.show();
                        $trackSelect.prop('required', true);
                        tracks.forEach(t => {
                            $trackSelect.append(`<option value="${t.track_id}">${t.track_name}</option>`);
                        });
                    } else {
                        $trackSelect.prop('required', false);
                        fetchOfferings($offeringSelect, classId, null);
                    }
                } catch(e) {}
            }, 'json');
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
            $offeringSelect.html('<option value="">-- اختر المسار أولاً --</option>').prop('disabled', true);
        }
    });

    function fetchOfferings($select, classId, trackId) {
        $select.html('<option value="">جاري التحميل...</option>').prop('disabled', false);
        let postData = { action: 'get_offerings', division_id: divisionId, class_id: classId };
        if (trackId) postData.track_id = trackId;

        $.post(AJAX_URL, postData, function(data) {
            $select.html('<option value="">-- اختر المقرر --</option>');
            try {
                const offerings = typeof data === 'string' ? JSON.parse(data) : data;
                if(offerings && !offerings.error && offerings.length > 0) {
                    offerings.forEach(offering => {
                        let groupName = offering.group_name ? `فوج: ${offering.group_name}` : 'مقرر عام (بدون أفواج)';
                        $select.append(`<option value="${offering.offering_id}">${groupName}</option>`);
                    });
                } else {
                    $select.html('<option value="">لا توجد أفواج متاحة لهذا الاختيار</option>').prop('disabled', true);
                }
            } catch(e) {}
        }, 'json');
    }

    $(document).on('change', '.offering-select', function() {
        const blockId = $(this).data('block');
        const offeringId = $(this).val();
        const $subjectsContainer = $(`#subjects-${blockId}`);
        $subjectsContainer.empty();

        if (offeringId) {
            $.post('../signup.php', { action: 'get_subjects', offering_id: offeringId }, function(data) {
                if (data && data.length > 0) {
                    let html = `<div style="padding: 15px; margin-top: 10px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px;">
                        <p style="font-size: 14px; font-weight: bold; color: #1e40af; margin-bottom: 12px;"><i class="fa-solid fa-circle-exclamation" style="margin-left: 5px;"></i> اختر المواد التي تريد الجميع او البعض:</p>
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

    $('#reEnrollForm').on('submit', function(e) {
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

        if (selectedOfferings.length === 0) {
            e.preventDefault();
            alert('يرجى اختيار مقرر واحد على الأقل.');
            return;
        }

        if (hasDuplicates) {
            e.preventDefault(); 
            alert('خطأ منطقي: لا يمكنك التسجيل في نفس المقرر (الفوج) أكثر من مرة. يرجى اختيار مقررات مختلفة.');
            
            $('.offering-select').css('border', '2px solid red');
            setTimeout(() => {
                $('.offering-select').css('border', '');
            }, 3000);
        }
    });
});