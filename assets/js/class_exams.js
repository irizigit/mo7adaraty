/* =========================================
   Class Exams Page Scripts (class_exams.js)
   ========================================= */
$(document).ready(function(){
    // 1. نظام الإعجابات (Reactions) مخصص للامتحانات
    $('.reaction-btn').on('click', function() {
        var button = $(this);
        var reactionType = button.hasClass('like') ? 'like' : 'dislike';
        var contentId = button.data('content-id');
        var contentType = button.data('content-type'); // سيكون 'exam'
        var examCard = $('#exam-' + contentId);
        
        var originalHtml = button.html();
        button.html('<span class="loader"></span>');
        button.prop('disabled', true);
        
        $.post('../handle_reaction.php', {
            content_id: contentId, content_type: contentType, reaction_type: reactionType
        }, function(response) {
            try {
                var data = JSON.parse(response);
                if (data.success) {
                    examCard.find('.reaction-btn.like .reaction-count').text(data.likes);
                    examCard.find('.reaction-btn.dislike .reaction-count').text(data.dislikes);
                    examCard.find('.reaction-btn').removeClass('active');
                    if (data.user_reaction) {
                       examCard.find('.reaction-btn.' + data.user_reaction).addClass('active');
                    }
                }
            } catch (e) {}
            button.html(originalHtml);
            button.prop('disabled', false);
        });
    });

    // 2. نظام التعليقات مخصص للامتحانات
    $('.comments-toggle').on('click', function() {
        var examId = $(this).data('exam-id');
        var commentsContent = $('#comments-' + examId);
        commentsContent.toggleClass('show');
        if (commentsContent.hasClass('show') && commentsContent.find('.comments-container').is(':empty')) {
            loadComments(examId, 'exam');
        }
    });

    function loadComments(contentId, contentType) {
        var container = $('.comments-container[data-content-id="' + contentId + '"][data-content-type="' + contentType + '"]');
        $.get('../comments/get_comments.php', { content_id: contentId, content_type: contentType }, function(response) {
            container.html(response);
        }).fail(function() {
            container.html('<p class="empty-state">فشل تحميل التعليقات. يرجى المحاولة مرة أخرى.</p>');
        });
    }

    $('.comment-form').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var submitBtn = form.find('button[type="submit"]');
        var originalBtnHtml = submitBtn.html();
        var contentId = form.find('input[name="content_id"]').val();
        var contentType = form.find('input[name="content_type"]').val();
        
        submitBtn.html('<span class="loader"></span> جاري الإرسال...');
        submitBtn.prop('disabled', true);
        
        $.post(form.attr('action'), form.serialize(), function(response) {
            try {
                var data = JSON.parse(response);
                if (data.success) {
                    loadComments(contentId, contentType);
                    form.find('textarea').val('');
                    updateCommentsCount(contentId);
                    alert(data.message);
                } else { alert(data.message); }
            } catch (ex) { alert("حدث خطأ غير متوقع."); }
            submitBtn.html(originalBtnHtml);
            submitBtn.prop('disabled', false);
        }).fail(function() {
            alert("فشل إرسال التعليق.");
            submitBtn.html(originalBtnHtml);
            submitBtn.prop('disabled', false);
        });
    });

    function updateCommentsCount(contentId) {
        var examCard = $('#exam-' + contentId);
        var commentsCount = examCard.find('.comments-count');
        $.get('../comments/get_comments_count.php', { content_id: contentId, content_type: 'exam' }, function(response) {
            try { var data = JSON.parse(response); if (data.success) commentsCount.text(data.count); } catch (e) {}
        });
    }

    // 3. نافذة الإبلاغ عن محتوى
    var reportModal = $("#reportModal");
    $(document).on('click', '.report-btn-action', function() {
        var contentId = $(this).data('content-id');
        var contentType = $(this).data('content-type');
        $('#report_content_id').val(contentId);
        $('#report_content_type').val(contentType);
        reportModal.show();
    });

    $(".close-btn").on('click', function() { $(this).closest('.modal').hide(); });
    $(window).on('click', function(event) { if ($(event.target).hasClass('modal')) $(event.target).hide(); });

    // 4. نافذة التحميل مع العد التنازلي
    var downloadModal = $("#downloadModal");
    var countdownText = $("#countdownText");
    var countdownNumber = $(".countdown-number");
    var progressCircle = $(".progress-ring-circle");
    var downloadUrl = '';

    if(progressCircle.length) {
        var radius = progressCircle.attr('r');
        var circumference = 2 * Math.PI * radius;
        progressCircle.css('strokeDasharray', `${circumference} ${circumference}`);
        progressCircle.css('strokeDashoffset', '0');
    }

    $(document).on('click', '.download-btn-action', function(e) {
        e.preventDefault();
        downloadUrl = $(this).attr('href');
        downloadModal.show();

        var timeLeft = 8;
        countdownText.text(timeLeft);
        countdownNumber.text(timeLeft);
        if(progressCircle.length) {
            progressCircle.css('strokeDashoffset', '0');
            progressCircle.css('strokeDashoffset', circumference);
            setTimeout(function() {
                progressCircle.css('transition', 'stroke-dashoffset 8s linear');
                progressCircle.css('strokeDashoffset', '0');
            }, 50);
        }

        var countdown = setInterval(function() {
            timeLeft--;
            countdownText.text(timeLeft);
            countdownNumber.text(timeLeft);
            if (timeLeft <= 0) { 
                clearInterval(countdown); 
                downloadModal.hide(); 
                window.location.href = downloadUrl; 
            }
        }, 1000);
    });

    // 5. إنشاء نصوص بديلة (Placeholder)
    function createExamPlaceholder() {
        $('.lecture-cover-placeholder').each(function() {
            var $placeholder = $(this);
            var $title = $placeholder.find('.placeholder-title');
            var originalTitle = $title.text();
            var maxTitleLength = 50;
            var displayTitle = originalTitle.length > maxTitleLength ? originalTitle.substring(0, maxTitleLength) + '...' : originalTitle;
            $title.text(displayTitle);
            
            $placeholder.on('mouseenter', function() { $title.text(originalTitle); })
                        .on('mouseleave', function() { $title.text(displayTitle); });
        });
    }

    createExamPlaceholder();
});