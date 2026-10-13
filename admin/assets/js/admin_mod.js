// admin/assets/js/admin_mod.js
function openRejectModal(itemId) {
    document.getElementById('rejectItemId').value = itemId;
    document.getElementById('rejectModal').style.display = 'flex';
}

function closeRejectModal() {
    document.getElementById('rejectModal').style.display = 'none';
    document.getElementById('rejectItemId').value = '';
}

// Close modal if clicked outside
window.onclick = function(event) {
    const modal = document.getElementById('rejectModal');
    if (event.target == modal) {
        closeRejectModal();
    }
}