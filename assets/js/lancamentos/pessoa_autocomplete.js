/**
 * Autocomplete de Pessoa para Lancamentos
 * Campos: Credor/Fornecedor e Pagador/Cliente
 */

function initPessoaAutocomplete(displayId, hiddenId, resultsId, url) {
    const displayInput = document.getElementById(displayId);
    const hiddenInput = document.getElementById(hiddenId);
    const resultsList = document.getElementById(resultsId);

    if (!displayInput || !hiddenInput || !resultsList) return;

    let debounceTimer = null;
    let activeIndex = -1;

    displayInput.addEventListener('input', () => {
        const q = displayInput.value.trim();

        // Limpa seleção anterior ao digitar
        hiddenInput.value = '';

        clearTimeout(debounceTimer);

        if (q.length < 2) {
            fecharResultados();
            return;
        }

        debounceTimer = setTimeout(() => buscar(q), 300);
    });

    displayInput.addEventListener('keydown', (e) => {
        const items = resultsList.querySelectorAll('.list-group-item');
        if (!items.length) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            activeIndex = Math.min(activeIndex + 1, items.length - 1);
            atualizarAtivo(items);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            activeIndex = Math.max(activeIndex - 1, 0);
            atualizarAtivo(items);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (activeIndex >= 0 && items[activeIndex]) {
                items[activeIndex].click();
            }
        } else if (e.key === 'Escape') {
            fecharResultados();
        }
    });

    // Fecha ao clicar fora
    document.addEventListener('click', (e) => {
        if (!displayInput.contains(e.target) && !resultsList.contains(e.target)) {
            fecharResultados();
        }
    });

    async function buscar(q) {
        try {
            const resp = await fetch(`${url}?q=${encodeURIComponent(q)}`);
            if (!resp.ok) return;
            const pessoas = await resp.json();
            renderizar(pessoas);
        } catch (err) {
            console.error('Erro no autocomplete:', err);
        }
    }

    function renderizar(pessoas) {
        resultsList.innerHTML = '';
        activeIndex = -1;

        if (!pessoas.length) {
            resultsList.innerHTML = '<div class="list-group-item text-muted">Nenhum resultado encontrado</div>';
            resultsList.style.display = 'block';
            return;
        }

        pessoas.forEach((p) => {
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'list-group-item list-group-item-action';
            item.textContent = p.nome;
            item.addEventListener('click', () => selecionar(p));
            resultsList.appendChild(item);
        });

        resultsList.style.display = 'block';
    }

    function selecionar(p) {
        displayInput.value = p.nome;
        hiddenInput.value = p.id;
        fecharResultados();
    }

    function fecharResultados() {
        resultsList.style.display = 'none';
        resultsList.innerHTML = '';
        activeIndex = -1;
    }

    function atualizarAtivo(items) {
        items.forEach((item, i) => {
            item.classList.toggle('active', i === activeIndex);
        });
        if (items[activeIndex]) {
            items[activeIndex].scrollIntoView({ block: 'nearest' });
        }
    }
}

export function initPessoasAutocomplete() {
    const url = window.LANCAMENTOS_ROUTES?.pessoaAutocomplete;
    if (!url) return;

    initPessoaAutocomplete('credor_display', 'credor_id', 'credor_results', url);
    initPessoaAutocomplete('pagador_display', 'pagador_id', 'pagador_results', url);
}
