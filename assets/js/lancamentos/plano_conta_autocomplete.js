/**
 * Autocomplete de Plano de Contas Almasa para Lancamentos
 * Campos: Conta Débito e Conta Crédito
 */

function initPlanoContaAutocomplete(cfg, url) {
    const displayInput = document.getElementById(cfg.displayId);
    const hiddenInput  = document.getElementById(cfg.hiddenId);
    const resultsList  = document.getElementById(cfg.resultsId);
    const clearBtn     = document.getElementById(cfg.clearId);
    const lupaBtn      = cfg.lupaId ? document.getElementById(cfg.lupaId) : null;

    if (!displayInput || !hiddenInput || !resultsList) return;

    // Preload em modo edição
    if (cfg.preloadId && cfg.preloadLabel) {
        displayInput.value = cfg.preloadLabel;
        hiddenInput.value  = cfg.preloadId;
        if (clearBtn) clearBtn.style.display = '';
        setTimeout(() => {
            hiddenInput.dispatchEvent(new CustomEvent('plano-conta-selecionado', {
                bubbles: true,
                detail: { id: cfg.preloadId, codigo: '', descricao: cfg.preloadLabel }
            }));
        }, 100);
    }

    let debounceTimer = null;
    let activeIndex   = -1;

    displayInput.addEventListener('input', () => {
        const q = displayInput.value.trim();
        hiddenInput.value = '';
        if (clearBtn) clearBtn.style.display = 'none';
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

    if (clearBtn) {
        clearBtn.addEventListener('click', () => {
            displayInput.value = '';
            hiddenInput.value  = '';
            clearBtn.style.display = 'none';
            displayInput.focus();
            hiddenInput.dispatchEvent(new CustomEvent('plano-conta-limpo', { bubbles: true }));
        });
    }

    if (lupaBtn) {
        lupaBtn.addEventListener('click', () => {
            // Mostrar todas (busca com q vazio)
            buscar('');
            displayInput.focus();
        });
    }

    document.addEventListener('click', (e) => {
        if (!displayInput.closest('.plano-conta-autocomplete-wrapper').contains(e.target)) {
            fechar();
        }
    });

    async function buscar(q) {
        try {
            let fullUrl = `${url}?q=${encodeURIComponent(q)}`;
            if (cfg.natureza) {
                fullUrl += `&natureza=${encodeURIComponent(cfg.natureza)}`;
            }
            const resp = await fetch(fullUrl);
            if (!resp.ok) return;
            const contas = await resp.json();
            renderizar(contas);
        } catch (err) {
            console.error('[plano-conta-autocomplete] erro:', err);
        }
    }

    function renderizar(contas) {
        resultsList.innerHTML = '';
        activeIndex = -1;

        if (!contas.length) {
            resultsList.innerHTML = '<div class="list-group-item text-muted fst-italic">Nenhuma conta encontrada</div>';
            resultsList.style.display = 'block';
            return;
        }

        contas.forEach((c) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'list-group-item list-group-item-action';
            btn.innerHTML = `<span class="text-muted me-2 small">${c.codigo}</span>${c.descricao}`;
            btn.addEventListener('mousedown', (e) => {
                e.preventDefault();
                selecionar(c);
            });
            resultsList.appendChild(btn);
        });

        resultsList.style.display = 'block';
    }

    function selecionar(c) {
        displayInput.value = c.codigo + ' - ' + c.descricao;
        hiddenInput.value  = c.id;
        if (clearBtn) clearBtn.style.display = '';
        fechar();
        hiddenInput.dispatchEvent(new CustomEvent('plano-conta-selecionado', {
            bubbles: true,
            detail: { id: c.id, codigo: c.codigo, descricao: c.descricao }
        }));
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

export function initPlanoContaAutocompletes() {
    const cfg = window.LANCAMENTOS_PLANO_CONTA;
    if (!cfg) return;

    initPlanoContaAutocomplete(cfg.debito,  cfg.url);
    initPlanoContaAutocomplete(cfg.credito, cfg.url);
}
