/* =========================================
   Exams AJAX Logic (Dependency Dropdowns)
   ========================================= */
$(document).ready(function() {
    
    const $divisionSelect = $('#division_id');
    const $classSelect = $('#class_id');
    const $offeringSelect = $('#offering_id');
    const $subjectSelect = $('#offering_subject_id');

    // 1. عند تغيير الشعبة: جلب الفصول المتاحة في هذه الشعبة حصراً ضمن الموسم المفعل
    $divisionSelect.on('change', function() {
        const divId = $(this).val();

        $classSelect.html('<option value="">-- جارٍ التحميل... --</option>').prop('disabled', true);
        $offeringSelect.html('<option value="">-- يرجى اختيار الفصل أولاً --</option>').prop('disabled', true);
        $subjectSelect.html('<option value="">-- يرجى اختيار الفوج أولاً --</option>').prop('disabled', true);

        if (divId) {
            $.post('get_data.php', { action: 'get_classes', division_id: divId }, function(data) {
                $classSelect.html('<option value="" disabled selected>-- اختر الفصل --</option>');
                if (data.length > 0) {
                    data.forEach(function(cls) {
                        $classSelect.append(`<option value="${cls.class_id}">${cls.class_name}</option>`);
                    });
                    $classSelect.prop('disabled', false);
                } else {
                    $classSelect.html('<option value="">-- لا توجد فصول متاحة لهذا الموسم --</option>');
                }
            }, 'json').fail(function() {
                $classSelect.html('<option value="">-- خطأ في الاتصال --</option>');
            });
        }
    });

    // 2. عند تغيير الفصل: جلب الأفواج / المسارات المتاحة
    $classSelect.on('change', function() {
        const divId = $divisionSelect.val();
        const clsId = $(this).val();

        $offeringSelect.html('<option value="">-- جارٍ التحميل... --</option>').prop('disabled', true);
        $subjectSelect.html('<option value="">-- يرجى اختيار الفوج أولاً --</option>').prop('disabled', true);

        if (divId && clsId) {
            $.post('get_data.php', { action: 'get_offerings', division_id: divId, class_id: clsId }, function(data) {
                $offeringSelect.html('<option value="">-- اختياري: اختر الفوج/المسار --</option>');
                if (data.length > 0) {
                    data.forEach(function(off) {
                        let trackInfo = off.track_name ? ` / مسار: ${off.track_name}` : '';
                        let groupInfo = off.group_name ? ` / فوج: ${off.group_name}` : '';
                        let displayText = `مقرر: ${off.class_name}${trackInfo}${groupInfo}`;
                        $offeringSelect.append(`<option value="${off.offering_id}">${displayText}</option>`);
                    });
                    $offeringSelect.prop('disabled', false);
                } else {
                    $offeringSelect.html('<option value="">-- لا توجد أفواج مخصصة --</option>');
                }
            }, 'json').fail(function() {
                $offeringSelect.html('<option value="">-- خطأ في الاتصال --</option>');
            });
        }
    });

    // 3. عند اختيار الفوج / المقرر: جلب المواد المتاحة
    $offeringSelect.on('change', function() {
        const offId = $(this).val();
        
        $subjectSelect.html('<option value="">-- اختياري: اختر المادة --</option>').prop('disabled', true);

        if (offId) {
            $subjectSelect.html('<option value="">جارٍ التحميل...</option>').prop('disabled', false);
            
            $.post('get_data.php', { action: 'get_subjects', offering_id: offId }, function(data) {
                $subjectSelect.html('<option value="">-- اختياري: اختر المادة --</option>');
                if (data.length > 0) {
                    data.forEach(function(sub) {
                        $subjectSelect.append(`<option value="${sub.offering_subject_id}">${sub.subject_name}</option>`);
                    });
                    $subjectSelect.prop('disabled', false);
                } else {
                    $subjectSelect.html('<option value="">-- لا توجد مواد --</option>');
                }
            }, 'json').fail(function() {
                $subjectSelect.html('<option value="">-- خطأ في الاتصال --</option>');
            });
        }
    });

});