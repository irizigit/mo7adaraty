/* =========================================
   Community Page Scripts (community.js)
   ========================================= */
document.addEventListener('DOMContentLoaded', function() {
    
    // 1. التمدد التلقائي لصندوق النص
    const textareas = document.querySelectorAll('.auto-resize');
    textareas.forEach(textarea => {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    });

    // 2. معاينة اسم الصورة المرفوعة
    const fileInput = document.getElementById('post_image');
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            const preview = document.getElementById('image-preview');
            if (this.files.length > 0) {
                preview.innerHTML = '<i class="fa-solid fa-check-circle"></i> تم إرفاق الصورة: ' + this.files[0].name;
            } else {
                preview.innerHTML = '';
            }
        });
    }

    // 3. تغيير لون خلفية المنشور
    window.selectStyle = function(style) {
        const colorOptions = document.querySelectorAll('.color-option');
        colorOptions.forEach(option => option.classList.remove('selected'));
        
        document.querySelector(`.color-option[data-style="${style}"]`).classList.add('selected');
        document.getElementById('background_style').value = style;
        
        const textarea = document.getElementById('post_text');
        
        // إزالة الكلاسات السابقة
        textarea.classList.remove('has-bg', 'bg-style-blue', 'bg-style-red', 'bg-style-green', 'bg-style-yellow', 'bg-style-purple');
        
        if (style !== 'none') {
            textarea.classList.add('has-bg', 'bg-style-' + style);
        }
    };
});

// استخدام jQuery للعمليات المعقدة (AJAX) لضمان التوافق مع الكود القديم بعد تحسينه
$(document).ready(function() {
    
    // ===================================
    // 1. دالة لجلب وعرض التعليقات
    // ===================================
    function loadComments(container) {
        var contentId = container.data('content-id');
        var contentType = container.data('content-type');
        if (contentId && contentType) {
            // المسار بالنسبة لـ index.php
            $.get('get_comments.php', { content_id: contentId, content_type: contentType }, function(data) {
                container.html(data);
            });
        }
    }

    // ===================================
    // 2. زر الإعجاب
    // ===================================
    $('.like-btn').on('click', function() {
        var button = $(this);
        var postId = button.data('content-id');
        
        // إضافة أنيميشن صغير للزر
        button.css('transform', 'scale(0.95)');
        setTimeout(() => button.css('transform', 'scale(1)'), 150);

        $.post('handle_reaction.php', { 
            content_id: postId,
            content_type: 'post',
            reaction_type: 'like'
        }, function(response) {
            try {
                var data = JSON.parse(response);
                if (data.success) {
                    button.find('.likes-count').text(data.likes);
                    button.toggleClass('liked', data.user_reaction === 'like');
                }
            } catch(e) { console.error("Error parsing like response: ", response); }
        });
    });

    // ===================================
    // 3. زر إظهار/إخفاء التعليقات
    // ===================================
    $('.comment-toggle-btn').on('click', function() {
        var commentsSection = $(this).closest('.post-card').find('.comments-section');
        commentsSection.slideToggle(300, function() {
            if ($(this).is(':visible')) {
                loadComments($(this).find('.comments-container'));
            }
        });
    });

    // ===================================
    // 4. إرسال تعليق جديد
    // ===================================
    $(document).on('submit', '.comment-form', function(e) {
        e.preventDefault();
        var form = $(this);
        var submitBtn = form.find('button');
        var originalContent = submitBtn.html();
        
        submitBtn.html('<i class="fa-solid fa-spinner fa-spin"></i>').prop('disabled', true);

        $.ajax({
            type: "POST",
            url: form.attr('action'),
            data: form.serialize(),
            success: function() {
                form.find('textarea').val('').css('height', 'auto');
                loadComments(form.siblings('.comments-container'));
                
                // تحديث عداد التعليقات في الزر
                var countSpan = form.closest('.post-card').find('.comments-count');
                countSpan.text(parseInt(countSpan.text()) + 1);
            },
            error: function() { alert('حدث خطأ أثناء إرسال التعليق.'); },
            complete: function() {
                submitBtn.html(originalContent).prop('disabled', false);
            }
        });
        
    });
// ===================================
    // 6. تعديل التعليق (إصلاح وتفعيل الـ AJAX)
    // ===================================
    $(document).on('click', '.edit-comment-btn', function(e) {
        e.preventDefault();
        var button = $(this);
        var commentId = button.data('comment-id');
        var commentContainer = button.closest('.comment-item');
        var commentTextElement = commentContainer.find('.comment-text');
        
        var currentText = commentTextElement.html().replace(/<br\s*[\/]?>/gi, '\n').trim();
        var newText = prompt("قم بتعديل تعليقك:", currentText);
        
        if (newText !== null && newText.trim() !== "" && newText !== currentText) {
            var originalHtml = button.html();
            button.html('<i class="fa-solid fa-spinner fa-spin"></i>').css('pointer-events', 'none');
            
            // توجيه الطلب لملف التحديث المحلي الخاص بالمجتمع
            $.post('update_comment.php', {
                comment_id: commentId,
                comment_text: newText
            }, function(response) {
                try {
                    var data = typeof response === 'string' ? JSON.parse(response) : response;
                    if (data.success) {
                        commentTextElement.html(newText.replace(/\n/g, '<br>'));
                    } else {
                        alert(data.message || "حدث خطأ أثناء التعديل.");
                    }
                } catch (ex) {
                    alert("حدث خطأ في قراءة رد الخادم.");
                }
                button.html(originalHtml).css('pointer-events', 'auto');
            }).fail(function() {
                alert("فشل الاتصال بالخادم.");
                button.html(originalHtml).css('pointer-events', 'auto');
            });
        }
    });

    // ===================================
    // 7. حذف التعليق (إصلاح وتفعيل الـ AJAX)
    // ===================================
    $(document).on('click', '.delete-comment-btn', function(e) {
        e.preventDefault();
        if (!confirm('هل أنت متأكد من حذف هذا التعليق نهائياً؟')) return;

        var button = $(this);
        var commentId = button.data('comment-id');
        var commentContainer = button.closest('.comment-item');
        var originalHtml = button.html();
        
        button.html('<i class="fa-solid fa-spinner fa-spin"></i>').css('pointer-events', 'none');

        // توجيه الطلب لملف الحذف المحلي الخاص بالمجتمع (POST Request)
        $.post('delete_comment.php', { comment_id: commentId }, function(response) {
            try {
                var data = typeof response === 'string' ? JSON.parse(response) : response;
                if (data.success) {
                    commentContainer.slideUp(300, function() { $(this).remove(); });
                    
                    // تحديث عداد التعليقات الديناميكي في المنشور
                    var countSpan = button.closest('.post-card').find('.comments-count');
                    if(countSpan.length) {
                        var currentCount = parseInt(countSpan.text()) || 0;
                        if(currentCount > 0) countSpan.text(currentCount - 1);
                    }
                } else {
                    alert(data.message || "حدث خطأ أثناء الحذف.");
                    button.html(originalHtml).css('pointer-events', 'auto');
                }
            } catch (ex) {
                alert("حدث خطأ في قراءة رد الخادم.");
                button.html(originalHtml).css('pointer-events', 'auto');
            }
        }).fail(function() {
            alert("فشل الاتصال بالخادم.");
            button.html(originalHtml).css('pointer-events', 'auto');
        });
    });
    // ===================================
    // 5. زر الإبلاغ عن منشور
    // ===================================
    var modal = $("#reportModal");
    $('.report-btn').on('click', function() {
        $('#report_content_id').val($(this).data('content-id'));
        modal.fadeIn(200);
    });
    
    $(".close-btn").on('click', function() {
        modal.fadeOut(200);
    });

    $(window).on('click', function(e) {
        if ($(e.target).is('.modal')) {
            modal.fadeOut(200);
        }
    });

    $('#reportForm').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var submitBtn = form.find('button[type="submit"]');
        var originalBtnHtml = submitBtn.html();
        
        submitBtn.html('<i class="fa-solid fa-spinner fa-spin"></i> جاري الإرسال...').prop('disabled', true);

        $.post(form.attr('action'), form.serialize(), function(response) {
            try {
                var data = JSON.parse(response);
                alert(data.message); 
                if (data.success) {
                    modal.fadeOut(200);
                    form[0].reset();
                }
            } catch (ex) {}
            submitBtn.html(originalBtnHtml).prop('disabled', false);
        }).fail(function() { 
            alert("فشل إرسال البلاغ."); 
            submitBtn.html(originalBtnHtml).prop('disabled', false);
        });
    });
});