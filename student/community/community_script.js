// ========================================================
// ملف التفاعل والمجتمع الطلابي (community_script.js)
// ========================================================

function selectStyle(style) {
    document.querySelectorAll('.color-option').forEach(function(el) {
        el.classList.remove('selected');
    });
    
    var selectedOption = document.querySelector('.color-option[data-style="' + style + '"]');
    if (selectedOption) {
        selectedOption.classList.add('selected');
    }
    
    var bgInput = document.getElementById('background_style');
    if (bgInput) {
        bgInput.value = style;
    }
    
    var textarea = document.getElementById('post_text');
    if (textarea) {
        textarea.className = 'create-post-textarea auto-resize';
        if (style !== 'none') {
            textarea.classList.add('has-bg', 'bg-style-' + style);
        }
    }
}

window.loadComments = function(container) {
    var contentId = container.data('content-id');
    var contentType = container.data('content-type');
    
    if (contentId && contentType) {
        $.get('/mo7adaraty/comments/get_comments.php', {
            content_id: contentId,
            content_type: contentType
        }, function(html) {
            container.html(html);
            var count = container.find('.comment-item').length;
            var countElem = container.closest('.post-card, .lecture-card').find('.comments-count');
            if (countElem.length) {
                countElem.text(count);
            }
        });
    }
};

$(document).ready(function() {

    // 1. التكبير التلقائي لحقول النصوص عند الكتابة
    $(document).on('input', '.auto-resize', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });

    // 2. معاينة الصورة المختارة قبل الرفع
    $('#post_image').on('change', function() {
        var preview = $('#image-preview');
        preview.empty();
        
        if (this.files && this.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                preview.html('<div style="position: relative; display: inline-block; margin-top: 10px;">' +
                    '<img src="' + e.target.result + '" style="max-width: 100%; max-height: 250px; border-radius: 8px; border: 1px solid var(--border-color); display: block;">' +
                    '<button type="button" id="remove-image-btn" style="position: absolute; top: 5px; right: 5px; background: rgba(0,0,0,0.6); color: #fff; border: none; border-radius: 50%; width: 26px; height: 26px; cursor: pointer; display: flex; align-items: center; justify-content: center;">&times;</button>' +
                    '</div>');
            };
            reader.readAsDataURL(this.files[0]);
        }
    });

    $(document).on('click', '#remove-image-btn', function() {
        $('#post_image').val('');
        $('#image-preview').empty();
    });

    // 3. التفاعل مع المنشورات (الإعجاب)
    $(document).off('click', '.like-btn').on('click', '.like-btn', function(e) {
        e.preventDefault();
        var button = $(this);
        var contentId = button.data('content-id');
        var contentType = 'post';
        var likesCountElem = button.find('.likes-count');

        button.css('pointer-events', 'none');

        $.ajax({
            url: 'toggle_reaction.php',
            type: 'POST',
            data: {
                content_id: contentId,
                content_type: contentType,
                reaction_type: 'like'
            },
            dataType: 'json'
        }).done(function(res) {
            button.css('pointer-events', 'auto');
            if (res.success) {
                if (res.action === 'added') {
                    button.addClass('liked');
                } else if (res.action === 'removed') {
                    button.removeClass('liked');
                }
                if (typeof res.likes_count !== 'undefined') {
                    likesCountElem.text(res.likes_count);
                }
            }
        }).fail(function() {
            button.css('pointer-events', 'auto');
        });
    });

    // 4. فتح وإغلاق قسم التعليقات
    $(document).off('click', '.comment-toggle-btn').on('click', '.comment-toggle-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();

        var postCard = $(this).closest('.post-card');
        var commentsSection = postCard.find('.comments-section');
        var container = postCard.find('.comments-container');

        commentsSection.stop(true, true).slideToggle(200, function() {
            if (commentsSection.is(':visible') && container.children().length === 0) {
                window.loadComments(container);
            }
        });
    });

    // 5. إدارة نافذة الإبلاغ عن المنشورات
    $(document).on('click', '.report-btn', function(e) {
        e.preventDefault();
        var contentId = $(this).data('content-id');
        $('#report_content_id').val(contentId);
        $('#report_content_type').val('post');
        $('#reason').val('');
        $('#reportModal').fadeIn(200);
    });

    $('#reportModal .close-btn').on('click', function() {
        $('#reportModal').fadeOut(200);
    });

    $(window).on('click', function(e) {
        if ($(e.target).is('#reportModal')) {
            $('#reportModal').fadeOut(200);
        }
    });

    // 6. نظام الفحص والتحديث الصامت التلقائي لكامل الصفحة (يشمل الإضافات والحذف)
    function getMaxPostId() {
        var maxId = 0;
        $('.post-card').each(function() {
            var id = parseInt($(this).attr('id').replace('post-', '')) || 0;
            if (id > maxId) maxId = id;
        });
        return maxId;
    }

    function getMaxCommentId() {
        var maxId = 0;
        $('.comment-item').each(function() {
            var id = parseInt($(this).attr('id').replace('comment-', '')) || 0;
            if (id > maxId) maxId = id;
        });
        return maxId;
    }

    if ($('#new-posts-banner').length === 0) {
        $('.posts-feed').before(
            '<div id="new-posts-banner" style="display: none; text-align: center; margin-bottom: 15px;">' +
            '<button type="button" id="load-new-posts-btn" style="background: var(--brand-primary, #0284c7); color: #fff; border: none; padding: 10px 20px; border-radius: 20px; font-weight: bold; cursor: pointer; box-shadow: 0 4px 12px rgba(2, 132, 199, 0.3); font-family: inherit;">' +
            '<i class="fa-solid fa-arrow-up"></i> <span id="banner-text">منشورات جديدة متوفرة</span>' +
            '</button>' +
            '</div>'
        );
    }

    $(document).on('click', '#load-new-posts-btn', function() {
        location.reload();
    });

    function checkLiveUpdates() {
        if ($('textarea:focus').length > 0) return;

        var activePostIds = [];
        $('.post-card').each(function() {
            var id = parseInt($(this).attr('id').replace('post-', '')) || 0;
            if (id > 0) activePostIds.push(id);
        });

        $.ajax({
            url: '/mo7adaraty/api/check_live_updates.php',
            type: 'GET',
            data: {
                last_post_id: getMaxPostId(),
                last_comment_id: getMaxCommentId(),
                active_post_ids: activePostIds
            },
            dataType: 'json'
        }).done(function(res) {
            if (!res || !res.success) return;

            // إظهار زر التنبيه بوجود منشورات جديدة
            if (res.new_posts_count > 0) {
                $('#banner-text').text('يوجد ' + res.new_posts_count + ' منشور جديد - اضغط للعرض');
                $('#new-posts-banner').slideDown(200);
            }

            // تحديث العدادات ومزامنة التعليقات المفتوحة عند حدوث إضافة أو حذف
            if (res.counters && res.counters.length) {
                res.counters.forEach(function(item) {
                    var card = $('#post-' + item.post_id);
                    if (card.length) {
                        var likesElem = card.find('.likes-count');
                        var commentsElem = card.find('.comments-count');

                        if (likesElem.length && likesElem.text() !== item.likes.toString()) {
                            likesElem.fadeOut(100, function() {
                                $(this).text(item.likes).fadeIn(100);
                            });
                        }

                        // إذا تغير عدد التعليقات (بالزيادة أو النقصان نتيجة الحذف)
                        if (commentsElem.length && commentsElem.text() !== item.comments.toString()) {
                            commentsElem.fadeOut(100, function() {
                                $(this).text(item.comments).fadeIn(100);
                            });

                            // مزامنة فورية لقسم التعليقات المفتوح لمسح التعليقات المحذوفة أو إظهار الجديدة
                            var container = card.find('.comments-container');
                            if (card.find('.comments-section').is(':visible') && container.find('textarea:focus').length === 0) {
                                var contentId = container.data('content-id');
                                var contentType = container.data('content-type');
                                
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
                                });
                            }
                        }
                    }
                });
            }
        });
    }

    // تشغيل الفحص الحي كل 5 ثوانٍ
    setInterval(checkLiveUpdates, 5000);
});