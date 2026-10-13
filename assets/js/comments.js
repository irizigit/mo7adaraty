/* =========================================
   Comments System Scripts (comments.js)
   ========================================= */

$(document).ready(function() {    
    const lang = window.commentsLang || {
        edit_prompt: 'تعديل التعليق:',
        error_update: 'حدث خطأ أثناء التعديل',
        server_error: 'خطأ في الاتصال بالخادم',
        confirm_delete: 'هل أنت متأكد من حذف هذا التعليق؟',
        error_delete: 'تعذر حذف التعليق',
        server_error_delete: 'خطأ في الخادم'
    };

    // 1. فتح / إغلاق نموذج الرد
    $(document).on('click', '.reply-toggle-btn', function(e) {
        e.preventDefault();
        var commentItem = $(this).closest('.comment-item');
        var replyForm = commentItem.find('> .comment-body > .nested-reply-form');
        
        replyForm.slideToggle(200, function() {
            if ($(this).is(':visible')) {
                $(this).find('textarea').focus();
            }
        });
    });

    // 2. إرسال الرد عبر AJAX
    $(document).on('submit', '.nested-reply-form', function(e) {
        e.preventDefault();
        var form = $(this);
        var submitBtn = form.find('button[type="submit"]');
        var textarea = form.find('textarea');
        var commentItem = form.closest('.comment-item');
        var container = commentItem.closest('.comments-container');
        var contentId = container.data('content-id');
        var contentType = container.data('content-type');

        if ($.trim(textarea.val()) === '') return;

        submitBtn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i>');

        $.post('/mo7adaraty/comments/add_comment.php', form.serialize(), function(response) {
            submitBtn.prop('disabled', false).html('<i class="fa-solid fa-paper-plane"></i>');
            textarea.val('');
            form.slideUp(200);

            // إعادة تحميل التعليقات لتحديث الردود
            if (typeof loadComments === 'function') {
                loadComments(contentId, contentType, container);
            } else {
                container.load('/mo7adaraty/comments/fetch_comments.php?content_id=' + contentId + '&content_type=' + contentType);
            }

            // زيادة عداد التعليقات في المنشور
            var countElem = commentItem.closest('.post-card, .lecture-card').find('.comments-count');
            if (countElem.length) {
                var currentCount = parseInt(countElem.text()) || 0;
                countElem.text(currentCount + 1);
            }
        }).fail(function() {
            submitBtn.prop('disabled', false).html('<i class="fa-solid fa-paper-plane"></i>');
            alert(lang.server_error);
        });
    });

    // 3. تعديل التعليق
    $(document).on('click', '.edit-comment-btn', function(e) {
        e.preventDefault();
        var button = $(this);
        var commentId = button.data('comment-id');
        var commentContainer = button.closest('.comment-item');
        var commentTextElement = commentContainer.find('> .comment-body > .comment-text');
        
        var currentText = commentTextElement.html().replace(/<br\s*[\/]?>/gi, '\n').trim();
        var newText = prompt(lang.edit_prompt, currentText);
        
        if (newText !== null && newText.trim() !== "" && newText !== currentText) {
            var originalHtml = button.html();
            button.html('<i class="fa-solid fa-spinner fa-spin"></i>').css('pointer-events', 'none');
            
            $.post('/mo7adaraty/comments/update_comment.php', {
                comment_id: commentId,
                comment_text: newText
            }, function(response) {
                try {
                    var data = typeof response === 'string' ? JSON.parse(response) : response;
                    if (data.success) {
                        commentTextElement.html(newText.replace(/\n/g, '<br>'));
                    } else {
                        alert(data.message || lang.error_update);
                    }
                } catch (ex) {
                    commentTextElement.html(newText.replace(/\n/g, '<br>')); 
                }
                button.html(originalHtml).css('pointer-events', 'auto');
            }).fail(function() {
                alert(lang.server_error);
                button.html(originalHtml).css('pointer-events', 'auto');
            });
        }
    });

    // 4. حذف التعليق
    $(document).on('click', '.delete-comment-btn', function(e) {
        e.preventDefault(); 
        if (!confirm(lang.confirm_delete)) return;

        var button = $(this);
        var commentContainer = button.closest('.comment-item');
        var commentId = button.data('comment-id');
        
        var originalHtml = button.html();
        button.html('<i class="fa-solid fa-spinner fa-spin"></i>').css('pointer-events', 'none');

        $.post('/mo7adaraty/comments/delete_comment.php', { comment_id: commentId }, function(response) {
            try {
                var data = typeof response === 'string' ? JSON.parse(response) : response;
                if (data.success) {
                    commentContainer.slideUp(300, function() { 
                        $(this).remove(); 
                        var countElem = button.closest('.post-card, .lecture-card').find('.comments-count');
                        if (countElem.length) {
                            var currentCount = parseInt(countElem.text()) || 0;
                            if (currentCount > 0) countElem.text(currentCount - 1);
                        }
                    });
                } else {
                    alert(data.message || lang.error_delete);
                    button.html(originalHtml).css('pointer-events', 'auto');
                }
            } catch (ex) {
                alert(lang.error_delete);
                button.html(originalHtml).css('pointer-events', 'auto');
            }
        }).fail(function() {
            alert(lang.server_error_delete);
            button.html(originalHtml).css('pointer-events', 'auto');
        });
    });
});