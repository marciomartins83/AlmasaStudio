/**
 * Autocomplete de Pessoa para Lancamentos
 * Campos: Credor/Fornecedor e Pagador/Cliente
 */

function initPessoaAutocomplete(cfg, url) {
    const displayInput = document.getElementById(cfg.displayId);
    const hiddenInput  = document.getElementById(cfg.hiddenId);
    const resultsList  = document.getElementById(cfg.resultsId);

    if (!displayInput || !hiddenInput || !resultsList) {
        console.warn('[autocomplete] elemento nao encontrado', cfg);
        return;
    }

    // Preenche preload (modo edição)
    if (cfg.preloadNome) displayInput.value = cfg.preloadNome;
    if (cfg.preloadId)   hiddenInput.value  = cfg.preloadId;

    let debounceTimer = null;
    let activeIndex   = -1;

    displayInput.addEventListener('input', () => {
        const q = displayInput.value.trim();
        hiddenInput.value = '';
        clearTimeout(debounceTimer);

        if (q.length < 2) {
            fechar();
            return;
        }

        debounceTimer = setTimeout(() => buscar(q), 300);
    });

    displayInput.addEventListener('keydown', (e) => {
        const items = resultsList.querySelectorAll('.list-group-item-action');
        if (!items.length) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            activeIndex = Math.min(activeIndex + 1, items.length - 1);
            marcarAtivo(items);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            activeIndex = Math.max(activeIndex - 1, 0);
            marcarAtivo(items);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (activeIndex >= 0 && items[activeIndex]) {
                items[activeIndex].click();
            }
        } else if (e.key === 'Escape') {
            fechar();
        }
    });

    document.addEventListener('click', (e) => {
        if (!displayInput.closest('.pessoa-autocomplete-wrapper').contains(e.target)) {
            fechar();
        }
    });

    async function buscar(q) {
        try {
            const resp = await fetch(`${url}?q=${encodeURIComponent(q)}`);
            if (!resp.ok) {
                console.warn('[autocomplete] resposta nao-ok:', resp.status);
                return;
            }
            const pessoas = await resp.json();
            renderizar(pessoas);
        } catch (err) {
            console.error('[autocomplete] erro na busca:', err);
        }
    }

    function renderizar(pessoas) {
        resultsList.innerHTML = '';
        activeIndex = -1;

        if (!pessoas.length) {
            resultsList.innerHTML = '<div class="list-group-item text-muted fst-italic">Nenhum resultado encontrado</div>';
            resultsList.style.display = 'block';
            return;
        }

        pessoas.forEach((p) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'list-group-item list-group-item-action';
            const label = p.cod ? `${p.nome}  —  cód. ${p.cod}` : p.nome;
            btn.textContent = label;
            btn.addEventListener('mousedown', (e) => {
                e.preventDefault();
                selecionar(p);
            });
            resultsList.appendChild(btn);
        });

        resultsList.style.display = 'block';
    }

    function selecionar(p) {
        displayInput.value = p.nome;
        hiddenInput.value  = p.id;
        fechar();
    }

    function fechar() {
        resultsList.style.display = 'none';
        resultsList.innerHTML = '';
        activeIndex = -1;
    }

    function marcarAtivo(items) {
        items.forEach((item, i) => item.classList.toggle('active', i === activeIndex));
        if (items[activeIndex]) {
            items[activeIndex].scrollIntoView({ block: 'nearest' });
        }
    }
}

export function initPessoasAutocomplete() {
    const cfg = window.LANCAMENTOS_AUTOCOMPLETE;
    if (!cfg) return;

    initPessoaAutocomplete(cfg.credor, cfg.url);
    initPessoaAutocomplete(cfg.pagador, cfg.url);
}
