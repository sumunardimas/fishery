$(document).ready(function () {
    window.confirmDelete = function (delete_url, item_name = null) {
        const deleteForm = document.getElementById('deleteConfirmationModal-deleteForm');
        const deleteItemNameSpan = document.getElementById('deleteConfirmationModal-deleteItemName');

        deleteItemNameSpan.textContent = item_name;
        deleteForm.setAttribute('action', delete_url);
    }
})
