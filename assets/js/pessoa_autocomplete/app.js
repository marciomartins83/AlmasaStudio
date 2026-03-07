/**
 * Pessoa Autocomplete — módulo global reutilizável
 *
 * Inicializa automaticamente qualquer elemento com:
 *   <div class="pessoa-autocomplete-wrapper"
 *        data-url="/caminho/autocomplete"
 *        data-display-id="campo_display"
 *        data-hidden-id="campo_id"
 *        data-results-id="campo_results">
 */

function initAutocomplete(wrapper) {
    const url        = wrapper.dataset.url;
    const displayEl  = document.getElementById(wrapper.dataset.displayId);
    const hiddenEl   = document.getElementById(wrapper.dataset.hiddenId);
    const resultsEl  = document.getElementById(wrapper.dataset.resultsId);

    if (!displayEl || !hiddenEl || !resultsEl || !url) return;

    let debounce = null;
    let activeIndex = -1;

    displayEl.addEventListener('input', () => {
        const q = displayEl.value.trim();
        hiddenEl.value = '';
        clearTimeout(debounce);
        if (q.length < 2) { fechar(); return; }
        debounce = setTimeout(() => buscar(q), 300);
    });

    displayEl.addEventListener('keydown', (e) => {
        const items = resultsEl.querySelectorAll('.list-group-item-action');
        if (!items.length) return;
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            activeIndex = Math.min(activeIndex + 1, items.length - 1);
            marcar(items);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            activeIndex = Math.max(activeIndex - 1, 0);
            marcar(items);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (activeIndex >= 0 && items[activeIndex]) items[activeIndex].click();
        } else if (e.key === 'Escape') {
            fechar();
        }
    });

    document.addEventListener('click', (e) => {
        if (!wrapper.contains(e.target)) fechar();
    });

    async function buscar(q) {
        try {
            const resp = await fetch(`${url}?q=${encodeURIComponent(q)}`);
            if (!resp.ok) return;
            renderizar(await resp.json());
        } catch (err) {
            console.error('[pessoa-autocomplete] erro:', err);
        }
    }

    function renderizar(pessoas) {
        resultsEl.innerHTML = '';
        activeIndex = -1;
        if (!pessoas.length) {
            resultsEl.innerHTML = '<div class="list-group-item text-muted fst-italic">Nenhum resultado</div>';
            resultsEl.style.display = 'block';
            return;
        }
        pessoas.forEach((p) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'list-group-item list-group-item-action';
            btn.textContent = p.cod ? `${p.nome}  —  cód. ${p.cod}` : p.nome;
            // mousedown previne blur no displayEl (fecha dropdown antes do click)
            btn.addEventListener('mousedown', (e) => { e.preventDefault(); });
            btn.addEventListener('click', () => { selecionar(p); });
            resultsEl.appendChild(btn);
        });
        resultsEl.style.display = 'block';
    }

    function selecionar(p) {
        displayEl.value = p.nome;
        hiddenEl.value  = p.id;
        fechar();
        wrapper.dispatchEvent(new CustomEvent('pessoa:selecionada', { detail: p, bubbles: true }));
    }

    function fechar() {
        resultsEl.style.display = 'none';
        resultsEl.innerHTML = '';
        activeIndex = -1;
    }

    function marcar(items) {
        items.forEach((item, i) => item.classList.toggle('active', i === activeIndex));
        if (items[activeIndex]) items[activeIndex].scrollIntoView({ block: 'nearest' });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.pessoa-autocomplete-wrapper[data-url]').forEach(initAutocomplete);
});
