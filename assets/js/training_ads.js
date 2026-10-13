// assets/js/training_ads.js
function handleAdReaction(itemId, reactionType) {
    const formData = new FormData();
    formData.append('content_id', itemId);
    formData.append('content_type', 'service_ad');
    formData.append('reaction_type', reactionType);

    fetch('../handle_reaction.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const card = document.querySelector(`.ad-card[data-id="${itemId}"]`);
            if (card) {
                card.querySelector('.like-count').textContent = data.likes;
                card.querySelector('.dislike-count').textContent = data.dislikes;
            }
        } else {
            alert(data.message || 'Error occurred. Please login.');
            if(data.message === 'وصول غير مصرح به.') {
                window.location.href = '../login.php';
            }
        }
    })
    .catch(error => console.error('Error:', error));
}

function openAdComments(itemId) {
    window.location.href = `../student/community/index.php?service_id=${itemId}`;
}

function openAdReportModal(itemId) {
    const userId = getCookie('user_id'); 
    if (!userId && !document.cookie.includes('PHPSESSID')) {
        window.location.href = '../login.php';
        return;
    }
    document.getElementById('adReportItemId').value = itemId;
    document.getElementById('adReportReason').value = '';
    document.getElementById('adReportModal').style.display = 'flex';
}

function closeAdReportModal() {
    document.getElementById('adReportModal').style.display = 'none';
}

function submitAdReport() {
    const itemId = document.getElementById('adReportItemId').value;
    const reason = document.getElementById('adReportReason').value.trim();

    if (!reason) return;

    const formData = new FormData();
    formData.append('content_id', itemId);
    formData.append('content_type', 'service_ad');
    formData.append('reason', reason);

    fetch('../student/submit_report.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeAdReportModal();
        } else {
            alert(data.message || 'Error submitting report.');
        }
    })
    .catch(error => console.error('Error:', error));
}

function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
    return null;
}