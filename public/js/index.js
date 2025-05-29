document.addEventListener('DOMContentLoaded', function() {
    // JavaScript for Nested Menu Toggle
    document.querySelectorAll('.nested-menu .menu-item').forEach(item => {
        item.addEventListener('click', function() {
            const toggleId = this.dataset.toggleId;
            const subList = document.getElementById(toggleId);
            const toggleIcon = this.querySelector('.toggle-icon');

            if (subList) {
                const isActive = subList.style.display === 'block';

                // Close all other sub-lists at the same level
                this.closest('.nested-menu, .sub-list').querySelectorAll(':scope > .menu-item + .sub-list').forEach(otherSubList => {
                    if (otherSubList.id !== toggleId && otherSubList.style.display === 'block') {
                        otherSubList.style.display = 'none';
                        otherSubList.previousElementSibling.querySelector('.toggle-icon').textContent = '+';
                    }
                });

                // Toggle current sub-list
                if (isActive) {
                    subList.style.display = 'none';
                    if (toggleIcon) toggleIcon.textContent = '+';
                    this.classList.remove('active');
                } else {
                    subList.style.display = 'block';
                    if (toggleIcon) toggleIcon.textContent = '-';
                    this.classList.add('active');
                }
            }
        });
    });

    // JavaScript for Scroll to Section (for horizontal branch buttons)
    document.querySelectorAll('.scroll-to-section').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const targetId = this.getAttribute('href').substring(1); // Remove '#'
            const target = document.getElementById(targetId);
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    // JavaScript for Logout functionality
    const logoutLink = document.getElementById('logoutLink');
    if (logoutLink) {
        logoutLink.addEventListener('click', async (e) => {
            e.preventDefault();
            try {
                const response = await fetch('/islamique/server/logout_handler.php');
                const data = await response.json();
                if (data.success) {
                    window.location.href = '/islamique/public/login.php';
                } else {
                    alert('حدث خطأ أثناء تسجيل الخروج.');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('تعذر الاتصال بالخادم للخروج.');
            }
        });
    }

    // JavaScript for updating View Count and Download Count
    document.querySelectorAll('.view-action, .download-action').forEach(actionElement => {
        actionElement.addEventListener('click', async function(e) {
            e.preventDefault(); // منع أي سلوك افتراضي للرابط إذا كان هناك
            const lectureId = this.dataset.lectureId;
            const actionType = this.classList.contains('view-action') ? 'view' : 'download';
            const countElement = this.querySelector('.count');

            try {
                const response = await fetch('/islamique/server/update_view_download_count_handler.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ id: lectureId, type: actionType })
                });
                const data = await response.json();

                if (data.success) {
                    // Update the displayed count
                    countElement.textContent = data.new_count;
                    // If it's a download action, trigger the actual download
                    if (actionType === 'download' && data.file_url) {
                        window.open(data.file_url, '_blank');
                    }
                    // For view action, redirect to lecture details page
                    if (actionType === 'view') {
                        window.location.href = `/islamique/public/lecture_details.php?id=${lectureId}`;
                    }
                } else {
                    console.error('Failed to update count:', data.message);
                }
            } catch (error) {
                console.error('Error updating count:', error);
            }
        });
    });

    // JavaScript for updating Interaction Counts (Likes/Dislikes)
    document.querySelectorAll('.like-btn, .dislike-btn').forEach(button => {
        button.addEventListener('click', async function() {
            const lectureId = this.dataset.lectureId;
            const interactionType = this.classList.contains('like-btn') ? 'like' : 'dislike';
            const parentCardBody = this.closest('.card-body');
            const likeCountElement = parentCardBody.querySelector('.interaction-counts .like-count');
            const dislikeCountElement = parentCardBody.querySelector('.interaction-counts .dislike-count');

            try {
                const response = await fetch('/islamique/server/update_interaction_handler.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ id: lectureId, type: interactionType })
                });
                const data = await response.json();

                if (data.success) {
                    likeCountElement.textContent = `👍 ${data.new_likes}`;
                    dislikeCountElement.textContent = `👎 ${data.new_dislikes}`;
                } else {
                    console.error('Failed to update interaction:', data.message);
                }
            } catch (error) {
                console.error('Error updating interaction:', error);
            }
        });
    });
});