/**
 * Autocomplete generico reutilizavel
 *
 * Uso:
 *   <div class="autocomplete-wrapper" data-url="/autocomplete/pessoas" data-hidden="#campo_id" data-min="2">
 *       <div class="input-group">
 *           <span class="input-group-text"><i class="fas fa-search"></i></span>
 *           <input type="text" class="form-control autocomplete-display" placeholder="Digite para buscar...">
 *       </div>
 *       <div class="autocomplete-results list-group" style="display:none"></div>
 *   </div>
 *   <input type="hidden" id="campo_id" name="campo_id">
 */
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.autocomplete-wrapper').forEach(initAutocomplete);
});

function initAutocomplete(wrapper) {
    var display = wrapper.querySelector('.autocomplete-display');
    var results = wrapper.querySelector('.autocomplete-results');
    var hiddenSel = wrapper.dataset.hidden;
    var hidden = hiddenSel ? document.querySelector(hiddenSel) : null;
    var url = wrapper.dataset.url;
    var minLen = parseInt(wrapper.dataset.min || '2', 10);
    var labelField = wrapper.dataset.label || 'label';
    var valueField = wrapper.dataset.value || 'id';

    if (!display || !results || !hidden || !url) return;

    var timer = null;
    var activeIndex = -1;

    display.addEventListener('input', function () {
        var q = display.value.trim();
        hidden.value = '';
        clearTimeout(timer);
        if (q.length < minLen) { fechar(); return; }
        timer = setTimeout(function () { buscar(q); }, 300);
    });

    display.addEventListener('keydown', function (e) {
        var items = results.querySelectorAll('.list-group-item-action');
        if (!items.length) return;
        if (e.key === 'ArrowDown') { e.preventDefault(); activeIndex = Math.min(activeIndex + 1, items.length - 1); marcar(items); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); activeIndex = Math.max(activeIndex - 1, 0); marcar(items); }
        else if (e.key === 'Enter') { e.preventDefault(); if (activeIndex >= 0 && items[activeIndex]) items[activeIndex].click(); }
        else if (e.key === 'Escape') { fechar(); }
    });

    document.addEventListener('click', function (e) {
        if (!wrapper.contains(e.target)) fechar();
    });

    function buscar(q) {
        fetch(url + '?q=' + encodeURIComponent(q))
            .then(function (r) { return r.ok ? r.json() : []; })
            .then(function (data) { renderizar(data); })
            .catch(function () { fechar(); });
    }

    function renderizar(items) {
        results.innerHTML = '';
        activeIndex = -1;
        if (!items.length) {
            results.innerHTML = '<div class="list-group-item text-muted fst-italic">Nenhum resultado</div>';
            results.style.display = 'block';
            return;
        }
        items.forEach(function (item) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'list-group-item list-group-item-action';
            btn.textContent = item[labelField] || item.label || item.nome || '';
            btn.addEventListener('mousedown', function (e) {
                e.preventDefault();
                display.value = btn.textContent;
                hidden.value = item[valueField] || item.id || '';
                fechar();
                // Dispatch change event on hidden field
                hidden.dispatchEvent(new Event('change', { bubbles: true }));
            });
            results.appendChild(btn);
        });
        results.style.display = 'block';
    }

    function fechar() { results.style.display = 'none'; results.innerHTML = ''; activeIndex = -1; }
    function marcar(items) {
        items.forEach(function (el, i) { el.classList.toggle('active', i === activeIndex); });
        if (items[activeIndex]) items[activeIndex].scrollIntoView({ block: 'nearest' });
    }

    // Preload: se hidden ja tem valor e display esta vazio, buscar label
    if (hidden.value && !display.value && wrapper.dataset.preloadUrl) {
        fetch(wrapper.dataset.preloadUrl + '?id=' + hidden.value)
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (data) { if (data && data[labelField]) display.value = data[labelField]; });
    }

    // Preload via data attribute
    if (wrapper.dataset.preloadLabel) {
        display.value = wrapper.dataset.preloadLabel;
    }
}
