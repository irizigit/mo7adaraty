/* ========================================================
   Class Lectures Page Scripts (class_lectures.js)
   ======================================================== */

$(document).ready(function() {

    // 1. نظام التبويبات الفوري والمحمي من أخطاء الروابط
    $(document).on('click', '.subject-link', function(e) {
        e.preventDefault();
        var link = $(this);
        var tabId = link.data('tab');
        var subjectName = link.find('span').text().trim();

        $('.subject-link').removeClass('active');
        link.addClass('active');

        $('.tab-content').hide();
        $('#' + tabId).fadeIn(150);

        $('#currentSubjectName').text(subjectName);

        if ($(window).width() <= 768) {
            $('#subjectsSidebar').removeClass('show-mobile');
            $('#mobileSubjectsBtn').removeClass('open');
        }
    });

    // 2. قائمة الموبايل للشريط الجانبي
    $(document).on('click', '#mobileSubjectsBtn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).toggleClass('open');
        $('#subjectsSidebar').toggleClass('show-mobile');
    });

    $(document).on('click', function(e) {
        if ($(window).width() <= 768) {
            if (!$(e.target).closest('#subjectsSidebar, #mobileSubjectsBtn').length) {
                $('#subjectsSidebar').removeClass('show-mobile');
                $('#mobileSubjectsBtn').removeClass('open');
            }
        }
    });

    // 3. التبديل بين العرض الشبكي والقائمة
    $(document).on('click', '.view-btn', function(e) {
        e.preventDefault();
        var viewType = $(this).data('view');
        var currentGrid = $(this).closest('.tab-content').find('.lectures-grid');

        $(this).siblings().removeClass('active');
        $(this).addClass('active');

        if (viewType === 'list') {
            currentGrid.removeClass('grid-view').addClass('list-view');
        } else {
            currentGrid.removeClass('list-view').addClass('grid-view');
        }
    });

    // 4. نظام التفاعلات (الإعجاب وعدم الإعجاب)
    $(document).on('click', '.reaction-btn', function(e) {
        e.preventDefault();
        var button = $(this);
        var reactionType = button.hasClass('like') ? 'like' : 'dislike';
        var contentId = button.data('content-id');
        var contentType = button.data('content-type') || 'lecture';
        var lectureCard = button.closest('.lecture-card');

        button.css('pointer-events', 'none');

        $.post('../handle_reaction.php', {
            content_id: contentId,
            content_type: contentType,
            reaction_type: reactionType
        }, function(response) {
            button.css('pointer-events', 'auto');
            try {
                var data = typeof response === 'string' ? JSON.parse(response) : response;
                if (data.success) {
                    lectureCard.find('.reaction-btn.like .reaction-count').text(data.likes);
                    lectureCard.find('.reaction-btn.dislike .reaction-count').text(data.dislikes);

                    lectureCard.find('.reaction-btn').removeClass('active');
                    if (data.user_reaction) {
                        lectureCard.find('.reaction-btn.' + data.user_reaction).addClass('active');
                    }
                }
            } catch (ex) {
                console.error("Parse Error:", ex);
            }
        }).fail(function(xhr) {
            button.css('pointer-events', 'auto');
            console.error("Reaction request failed:", xhr.status);
        });
    });

    // 5. فتح وإغلاق قسم التعليقات
    $(document).on('click', '.comments-toggle', function(e) {
        e.preventDefault();
        var lecId = $(this).data('lecture-id');
        var commentsContent = $('#comments-' + lecId);
        var container = commentsContent.find('.comments-container');

        commentsContent.stop(true, true).slideToggle(200, function() {
            if (commentsContent.is(':visible') && container.children().length === 0) {
                if (typeof window.loadComments === 'function') {
                    window.loadComments(container);
                }
            }
        });
    });

    // 6. نافذة الإبلاغ
    $(document).on('click', '.report-btn-action', function(e) {
        e.preventDefault();
        var contentId = $(this).data('content-id');
        var contentType = $(this).data('content-type') || 'lecture';
        $('#report_content_id').val(contentId);
        $('#report_content_type').val(contentType);
        $('#reportModal').css('display', 'flex').hide().fadeIn(150);
    });

    // 7. نافذة التحميل والعداد التنازلي
    var downloadTimer = null;
    $(document).on('click', '.download-btn-action', function(e) {
        var href = $(this).attr('href');
        if ($(this).attr('target') === '_blank') return;

        e.preventDefault();
        var downloadUrl = href;
        var timeLeft = 8;
        
        $('#countdownNumber').text(timeLeft);
        $('#downloadModal').css('display', 'flex').hide().fadeIn(150);

        if (downloadTimer) clearInterval(downloadTimer);

        downloadTimer = setInterval(function() {
            timeLeft--;
            $('#countdownNumber').text(timeLeft);
            if (timeLeft <= 0) {
                clearInterval(downloadTimer);
                $('#downloadModal').fadeOut(150);
                window.location.href = downloadUrl;
            }
        }, 1000);
    });

    // 8. إغلاق النوافذ المنبثقة
    $(document).on('click', '.modal .close-btn', function(e) {
        e.preventDefault();
        if (downloadTimer) clearInterval(downloadTimer);
        $(this).closest('.modal').fadeOut(150);
    });

    $(window).on('click', function(event) {
        if ($(event.target).hasClass('modal')) {
            if (downloadTimer) clearInterval(downloadTimer);
            $(event.target).fadeOut(150);
        }
    });

    // 9. نظام المزامنة الحية لقسم المحاضرات
    function getActiveLectureIds() {
        var ids = [];
        $('.lecture-card').each(function() {
            var cid = parseInt($(this).attr('data-lecture-canonical-id')) || parseInt($(this).attr('id').replace('lecture-', '')) || 0;
            if (cid > 0 && ids.indexOf(cid) === -1) {
                ids.push(cid);
            }
        });
        return ids;
    }

    function checkLiveLectureUpdates() {
        var activeLectureIds = getActiveLectureIds();
        if (activeLectureIds.length === 0) return;

        $.ajax({
            url: '/mo7adaraty/api/check_live_updates.php',
            type: 'GET',
            data: {
                content_type: 'lecture',
                active_lecture_ids: activeLectureIds
            },
            dataType: 'json'
        }).done(function(res) {
            if (!res || !res.success || !res.counters) return;

            res.counters.forEach(function(item) {
                var card = $('.lecture-card[data-lecture-canonical-id="' + item.lecture_id + '"], #lecture-' + item.lecture_id);
                if (card.length) {
                    var likesElem = card.find('.reaction-btn.like .reaction-count');
                    var dislikesElem = card.find('.reaction-btn.dislike .reaction-count');
                    var commentsElem = card.find('.comments-count');

                    if (likesElem.length && likesElem.text() !== item.likes.toString()) {
                        likesElem.fadeOut(100, function() { $(this).text(item.likes).fadeIn(100); });
                    }
                    if (dislikesElem.length && dislikesElem.text() !== item.dislikes.toString()) {
                        dislikesElem.fadeOut(100, function() { $(this).text(item.dislikes).fadeIn(100); });
                    }
                    if (commentsElem.length && commentsElem.text() !== item.comments.toString()) {
                        commentsElem.fadeOut(100, function() { $(this).text(item.comments).fadeIn(100); });
                    }

                    var commentsContent = card.find('.comments-content');
                    var container = commentsContent.find('.comments-container');

                    if (commentsContent.is(':visible')) {
                        var currentDomCommentsCount = container.find('.comment-item').length;
                        var maxDomCommentId = 0;
                        container.find('.comment-item').each(function() {
                            var mid = parseInt($(this).attr('id').replace('comment-', '')) || 0;
                            if (mid > maxDomCommentId) maxDomCommentId = mid;
                        });

                        if (currentDomCommentsCount !== parseInt(item.comments) || maxDomCommentId < parseInt(item.max_comment_id)) {
                            if (typeof window.loadComments === 'function') {
                                window.loadComments(container);
                            }
                        }
                    }
                }
            });
        });
    }

    setInterval(checkLiveLectureUpdates, 4000);
});