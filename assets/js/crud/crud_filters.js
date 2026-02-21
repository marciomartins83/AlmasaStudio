/**
 * crud_filters.js — JS modular para busca avancada nos CRUDs
 * Gerencia: toggle icone collapse, Enter submete form, limpar reseta campos.
 */
document.addEventListener('DOMContentLoaded', function () {
    const searchPanel = document.getElementById('searchPanelBody');
    const searchIcon = document.getElementById('searchPanelIcon');
    const searchForm = document.getElementById('searchForm');

    // Toggle chevron icon on collapse
    if (searchPanel && searchIcon) {
        searchPanel.addEventListener('shown.bs.collapse', function () {
            searchIcon.classList.remove('fa-chevron-down');
            searchIcon.classList.add('fa-chevron-up');
        });
        searchPanel.addEventListener('hidden.bs.collapse', function () {
            searchIcon.classList.remove('fa-chevron-up');
            searchIcon.classList.add('fa-chevron-down');
        });

        // Set initial icon state
        if (searchPanel.classList.contains('show')) {
            searchIcon.classList.remove('fa-chevron-down');
            searchIcon.classList.add('fa-chevron-up');
        }
    }

    // Enter key submits form from any field
    if (searchForm) {
        searchForm.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && e.target.tagName !== 'BUTTON') {
                e.preventDefault();
                searchForm.submit();
            }
        });
    }
});
