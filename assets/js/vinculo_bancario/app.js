/**
 * Vinculo Bancario — filtro de contas bancarias por pessoa
 *
 * Quando o usuario seleciona uma pessoa no autocomplete,
 * filtra o select de contas bancarias para mostrar apenas as contas dessa pessoa.
 * Botao "Mostrar todas" restaura as opcoes originais.
 */

document.addEventListener('DOMContentLoaded', () => {
    const cfg = window.VINCULO_BANCARIO_CFG;
    if (!cfg) return;

    const wrapper = document.querySelector('.pessoa-autocomplete-wrapper');
    const contaSelect = document.getElementById(cfg.contaBancariaSelectId);
    const btnLimpar = document.getElementById('btn-limpar-filtro-pessoa');
    const statusEl = document.getElementById('filtro-pessoa-status');

    if (!wrapper || !contaSelect) return;

    // Guardar todas as opcoes originais
    const opcoesOriginais = [...contaSelect.options].map(opt => ({
        value: opt.value,
        text: opt.textContent,
        selected: opt.selected
    }));

    wrapper.addEventListener('pessoa:selecionada', async (e) => {
        const pessoa = e.detail;
        try {
            const url = cfg.contasPorPessoaUrl.replace('__PESSOA_ID__', pessoa.id);
            const resp = await fetch(url);
            if (!resp.ok) return;
            const contas = await resp.json();

            // Limpar select e adicionar placeholder
            contaSelect.innerHTML = '';
            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = contas.length
                ? `Selecione (${contas.length} conta(s) de ${pessoa.nome})...`
                : `Nenhuma conta bancaria encontrada para ${pessoa.nome}`;
            contaSelect.appendChild(placeholder);

            // Adicionar contas da pessoa
            contas.forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.id;
                opt.textContent = c.label;
                contaSelect.appendChild(opt);
            });

            // Mostrar status e botao limpar
            if (statusEl) {
                statusEl.textContent = contas.length
                    ? `Mostrando ${contas.length} conta(s) de "${pessoa.nome}"`
                    : `Nenhuma conta bancaria para "${pessoa.nome}"`;
                statusEl.style.display = 'block';
            }
            if (btnLimpar) btnLimpar.style.display = 'inline-block';

        } catch (err) {
            console.error('[vinculo-bancario] erro ao buscar contas:', err);
        }
    });

    // Botao limpar: restaura todas as opcoes
    if (btnLimpar) {
        btnLimpar.addEventListener('click', () => {
            restaurarOpcoes();
        });
    }

    function restaurarOpcoes() {
        contaSelect.innerHTML = '';
        opcoesOriginais.forEach(o => {
            const opt = document.createElement('option');
            opt.value = o.value;
            opt.textContent = o.text;
            contaSelect.appendChild(opt);
        });
        if (statusEl) statusEl.style.display = 'none';
        if (btnLimpar) btnLimpar.style.display = 'none';
    }
});
