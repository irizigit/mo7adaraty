/* =========================================
   Comments System Scripts (comments.js)
   ========================================= */

window.loadComments = function(target, fallbackType) {
    var container;
    if (target instanceof jQuery) {
        container = target;
    } else if (typeof target === 'string' && isNaN(target)) {
        container = $(target);
    } else {
        var type = fallbackType || 'lecture';
        container = $('.comments-container[data-content-id="' + target + '"][data-content-type="' + type + '"]');
    }

    if (!container || !container.length) return;

    var contentId = container.data('content-id');
    var contentType = container.data('content-type') || 'lecture';

    if (contentId && contentType) {
        $.get('/mo7adaraty/comments/get_comments.php', {
            content_id: contentId,
            content_type: contentType
        }, function(html) {
            var openForms = [];
            container.find('.nested-reply-form:visible').each(function() {
                openForms.push($(this).data('parent-id'));
            });

            container.html(html);

            openForms.forEach(function(pid) {
                container.find('.nested-reply-form[data-parent-id="' + pid + '"]').show();
            });

            var count = container.find('.comment-item').length;
            var countElem = container.closest('.post-card, .lecture-card').find('.comments-count');
            if (countElem.length) {
                countElem.text(count);
            }
        });
    }
};

$(document).ready(function() {    
    const lang = window.commentsLang || {
        edit_prompt: 'تعديل التعليق:',
        error_update: 'حدث خطأ أثناء التعديل',
        server_error: 'خطأ في الاتصال بالخادم',
        confirm_delete: 'هل أنت متأكد من حذف هذا التعليق نهائياً؟',
        error_delete: 'تعذر حذف التعليق',
        server_error_delete: 'خطأ في الخادم'
    };

    // 1. فتح وإغلاق نموذج الرد للتعليق الأصلي أو الفرعي
    $(document).off('click', '.reply-toggle-btn').on('click', '.reply-toggle-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var commentItem = $(this).closest('.comment-item');
        var replyForm = commentItem.find('> .comment-body > .nested-reply-form');
        
        commentItem.closest('.comments-container').find('.nested-reply-form').not(replyForm).slideUp(150);

        replyForm.stop(true, true).slideToggle(200, function() {
            if ($(this).is(':visible')) {
                $(this).find('textarea').focus();
            }
        });
    });

    // 2. إرسال بالضغط على زر Enter
    $(document).off('keydown', '.comment-form textarea, .nested-reply-form textarea')
               .on('keydown', '.comment-form textarea, .nested-reply-form textarea', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            $(this).closest('form').trigger('submit');
        }
    });

    // 3. معالج الإرسال الموحد للتعليقات والردود
    $(document).off('submit', '.comment-form, .nested-reply-form').on('submit', '.comment-form, .nested-reply-form', function(e) {
        e.preventDefault();
        var form = $(this);
        var submitBtn = form.find('button[type="submit"], button');
        var textarea = form.find('textarea[name="comment_text"], textarea');
        var commentText = $.trim(textarea.val());

        if (commentText === '') {
            console.warn('[Comments] حقل التعليق فارغ.');
            return;
        }

        console.log('[Comments] جاري إرسال البيانات:', form.serialize());
        submitBtn.prop('disabled', true);

        var actionUrl = form.attr('action') || '/mo7adaraty/comments/add_comment.php';

        $.ajax({
            url: actionUrl,
            type: 'POST',
            data: form.serialize(),
            dataType: 'json'
        }).done(function(data) {
            console.log('[Comments] استجابة الخادم:', data);
            submitBtn.prop('disabled', false);

            if (data && data.success) {
                textarea.val('');
                if (form.hasClass('nested-reply-form')) {
                    form.slideUp(150);
                }

                var container = form.closest('.comments-content, .comments-section, .post-card, .lecture-card').find('.comments-container');
                window.loadComments(container);
            } else {
                var msg = (data && data.message) ? data.message : lang.server_error;
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'error', title: 'خطأ', text: msg, confirmButtonText: 'حسناً', confirmButtonColor: '#0284c7' });
                } else {
                    alert(msg);
                }
            }
        }).fail(function(xhr) {
            console.error('[Comments] فشل طلب AJAX:', xhr.status, xhr.responseText);
            submitBtn.prop('disabled', false);

            var msg = lang.server_error;
            try {
                var res = JSON.parse(xhr.responseText);
                if (res && res.message) msg = res.message;
            } catch(ex) {}

            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'error', title: 'خطأ', text: msg, confirmButtonText: 'حسناً', confirmButtonColor: '#0284c7' });
            } else {
                alert(msg);
            }
        });
    });

    // 4. تعديل التعليق
    $(document).off('click', '.edit-comment-btn').on('click', '.edit-comment-btn', function(e) {
        e.preventDefault();
        var button = $(this);
        var commentId = button.data('comment-id');
        var commentContainer = button.closest('.comment-item');
        var commentTextElement = commentContainer.find('> .comment-body > .comment-text');
        var currentText = commentTextElement.html().replace(/<br\s*[\/]?>/gi, '\n').trim();

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'تعديل التعليق',
                input: 'textarea',
                inputValue: currentText,
                inputAttributes: {
                    'placeholder': 'اكتب تعليقك هنا...',
                    'style': 'direction: rtl; font-family: inherit; font-size: 0.95rem; min-height: 100px;'
                },
                showCancelButton: true,
                confirmButtonText: '<i class="fa-solid fa-check"></i> حفظ التعديل',
                cancelButtonText: 'إلغاء',
                confirmButtonColor: '#0284c7',
                cancelButtonColor: '#64748b',
                reverseButtons: true,
                focusConfirm: false,
                inputValidator: (value) => {
                    if (!value || !value.trim()) {
                        return 'لا يمكن حفظ التعليق فارغاً';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    var newText = result.value.trim();
                    if (newText === currentText) return;

                    button.css('pointer-events', 'none');
                    $.post('/mo7adaraty/comments/update_comment.php', {
                        comment_id: commentId,
                        comment_text: newText
                    }, function(response) {
                        try {
                            var data = typeof response === 'string' ? JSON.parse(response) : response;
                            if (data.success) {
                                commentTextElement.html(newText.replace(/\n/g, '<br>'));
                                Swal.fire({ icon: 'success', title: 'تم التعديل بنجاح', timer: 1500, showConfirmButton: false });
                            } else {
                                Swal.fire({ icon: 'error', title: 'خطأ', text: data.message || lang.error_update, confirmButtonText: 'حسناً', confirmButtonColor: '#0284c7' });
                            }
                        } catch (ex) {
                            commentTextElement.html(newText.replace(/\n/g, '<br>'));
                        }
                        button.css('pointer-events', 'auto');
                    }).fail(function() {
                        button.css('pointer-events', 'auto');
                        Swal.fire({ icon: 'error', title: 'خطأ', text: lang.server_error, confirmButtonText: 'حسناً', confirmButtonColor: '#0284c7' });
                    });
                }
            });
        }
    });

    // 5. حذف التعليق
    $(document).off('click', '.delete-comment-btn').on('click', '.delete-comment-btn', function(e) {
        e.preventDefault(); 
        var button = $(this);
        var commentContainer = button.closest('.comment-item');
        var commentId = button.data('comment-id');
        var container = commentContainer.closest('.comments-container');

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'حذف التعليق',
                text: 'هل أنت متأكد من رغبتك في حذف هذا التعليق نهائياً؟',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '<i class="fa-solid fa-trash-can"></i> نعم، حذف',
                cancelButtonText: 'إلغاء',
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                reverseButtons: true,
                focusCancel: true
            }).then((result) => {
                if (result.isConfirmed) {
                    button.css('pointer-events', 'none');
                    $.post('/mo7adaraty/comments/delete_comment.php', { comment_id: commentId }, function(response) {
                        try {
                            var data = typeof response === 'string' ? JSON.parse(response) : response;
                            if (data.success) {
                                commentContainer.slideUp(250, function() { 
                                    $(this).remove(); 
                                    var count = container.find('.comment-item').length;
                                    var countElem = container.closest('.post-card, .lecture-card').find('.comments-count');
                                    if (countElem.length) {
                                        countElem.text(count);
                                    }
                                });
                                Swal.fire({ icon: 'success', title: 'تم الحذف', text: 'تم حذف التعليق بنجاح.', timer: 1500, showConfirmButton: false });
                            } else {
                                Swal.fire({ icon: 'error', title: 'خطأ', text: data.message || lang.error_delete, confirmButtonText: 'حسناً', confirmButtonColor: '#0284c7' });
                            }
                        } catch (ex) {
                            alert(lang.error_delete);
                        }
                        button.css('pointer-events', 'auto');
                    }).fail(function() {
                        button.css('pointer-events', 'auto');
                        Swal.fire({ icon: 'error', title: 'خطأ', text: lang.server_error_delete, confirmButtonText: 'حسناً', confirmButtonColor: '#0284c7' });
                    });
                }
            });
        }
    });
});