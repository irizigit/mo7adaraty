/* =========================================
   Lectures Management Scripts
   ========================================= */
$(document).ready(function() {
    
    // نظام الطي الأكورديوني الهرمي
    $('.acc-header').on('click', function(e) {
        if($(e.target).closest('button, a').length) return;
        
        const body = $(this).next('.acc-body');
        const icon = $(this).find('.fa-chevron-down, .fa-chevron-up');
        
        if (body.is(':visible')) {
            body.slideUp(200);
            icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
        } else {
            body.slideDown(200);
            icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
        }
    });

    // --- المنطق الديناميكي لنموذج إضافة محاضرة (AJAX) ---
    const $form = $('form[data-context="lectures"]');
    if ($form.length > 0) {
        const $divisionSelect = $form.find('#division');
        const $offeringSelect = $form.find('#offering_id');
        const $subjectSelect = $form.find('#offering_subject_id');

        // 1. عند تغيير "الشعبة"
        $divisionSelect.on('change', function() {
            const divisionID = $(this).val();
            
            $offeringSelect.html('<option value="">-- اختر الشعبة أولاً --</option>').prop('disabled', true);
            $subjectSelect.html('<option value="">-- اختر المقرر أولاً --</option>').prop('disabled', true);

            if (divisionID) {
                $offeringSelect.html('<option value="">جارٍ التحميل...</option>').prop('disabled', false);
                
                $.post('get_data.php', { action: 'get_offerings', division_id: divisionID }, function(data) {
                    $offeringSelect.html('<option value="">-- اختر المقرر --</option>');
                    if (data.length > 0) {
                        data.forEach(function(offering) {
                            let trackInfo = offering.track_name ? ` / مسار ${offering.track_name}` : '';
                            let groupInfo = offering.group_name ? ` / فوج ${offering.group_name}` : '';
                            let displayText = `${offering.class_name}${trackInfo}${groupInfo}`;
                            $offeringSelect.append(`<option value="${offering.offering_id}">${displayText}</option>`);
                        });
                    } else {
                        $offeringSelect.html('<option value="">-- لا توجد مقررات --</option>');
                    }
                }, 'json').fail(function() {
                    $offeringSelect.html('<option value="">-- حدث خطأ --</option>');
                });
            }
        });

        // 2. عند تغيير "المقرر المتاح"
        $offeringSelect.on('change', function() {
            const offeringID = $(this).val();
            
            $subjectSelect.html('<option value="">-- اختر المقرر أولاً --</option>').prop('disabled', true);

            if (offeringID) {
                $subjectSelect.html('<option value="">جارٍ التحميل...</option>').prop('disabled', false);
                
                $.post('get_data.php', { action: 'get_subjects', offering_id: offeringID }, function(data) {
                    $subjectSelect.html('<option value="">-- اختر المادة --</option>');
                    if (data.length > 0) {
                        data.forEach(function(subject) {
                            $subjectSelect.append(`<option value="${subject.offering_subject_id}">${subject.subject_name}</option>`);
                        });
                    } else {
                        $subjectSelect.html('<option value="">-- لا توجد مواد --</option>');
                    }
                }, 'json').fail(function() {
                    $subjectSelect.html('<option value="">-- حدث خطأ --</option>');
                });
            }
        });
    }
});