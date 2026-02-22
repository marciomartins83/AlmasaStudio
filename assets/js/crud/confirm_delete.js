/**
 * Confirm delete handler - replaces inline onsubmit/onclick JS
 * Listens for submit events on forms with data-confirm-delete attribute
 */
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('form[data-confirm-delete]').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            if (!confirm('Tem certeza que deseja excluir este registro?')) {
                e.preventDefault();
            }
        });
    });
});
