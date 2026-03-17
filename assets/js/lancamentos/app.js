/**
 * Lancamentos - App principal
 *
 * Inicializacao e event listeners para o modulo de lancamentos
 */

import {
    baixarLancamento,
    estornarBaixa,
    cancelarLancamento,
    excluirLancamento,
    exibirSucesso,
    exibirErro,
    confirmarAcao,
    calcularRetencao,
    parseValor
} from './lancamentos.js';

import { initPessoasAutocomplete } from './pessoa_autocomplete.js';
import { initPlanoContaAutocompletes } from './plano_conta_autocomplete.js';
import { initContaBancariaVinculos } from './conta_bancaria_vinculos.js';

/**
 * Inicializa modulo quando DOM estiver pronto
 */
document.addEventListener('DOMContentLoaded', () => {
    initBotoesBaixa();
    initBotoesEstornar();
    initBotoesCancelar();
    initModalBaixa();
    initModalCancelar();
    initCalculoRetencoes();
    initCompetenciaAutomatica();
    initPessoasAutocomplete();
    initPlanoContaAutocompletes();
    initRecorrencia();
    initFiltroPlanoConta();
    initCamposMonetarios();
    initBotoesExcluir();
    initContaBancariaVinculos();
});

/**
 * Inicializa botoes de baixa
 */
function initBotoesBaixa() {
    document.querySelectorAll('.btn-baixa').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.id;
            const saldo = btn.dataset.saldo;

            document.getElementById('baixa_id').value = id;
            document.getElementById('baixa_valor').value = saldo;

            const contaEl = document.getElementById('baixa_conta_bancaria');
            if (contaEl) contaEl.value = '';
            const erroEl = document.getElementById('baixa_conta_erro');
            if (erroEl) erroEl.style.display = 'none';

            const modal = new bootstrap.Modal(document.getElementById('modalBaixa'));
            modal.show();
        });
    });
}

/**
 * Inicializa botoes de estorno
 */
function initBotoesEstornar() {
    document.querySelectorAll('.btn-estornar').forEach(btn => {
        btn.addEventListener('click', async () => {
            const id = btn.dataset.id;

            if (!confirmarAcao('Deseja realmente estornar este pagamento?')) {
                return;
            }

            try {
                const result = await estornarBaixa(id);

                if (result.success) {
                    exibirSucesso(result.message);
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    exibirErro(result.message);
                }
            } catch (error) {
                console.error('Erro ao estornar:', error);
                exibirErro('Erro ao estornar pagamento');
            }
        });
    });
}

/**
 * Inicializa botoes de cancelamento
 */
function initBotoesCancelar() {
    document.querySelectorAll('.btn-cancelar').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.id;

            document.getElementById('cancelar_id').value = id;
            document.getElementById('cancelar_motivo').value = '';

            const modal = new bootstrap.Modal(document.getElementById('modalCancelar'));
            modal.show();
        });
    });
}

/**
 * Inicializa botoes de exclusao
 */
function initBotoesExcluir() {
    document.querySelectorAll('.btn-excluir').forEach(btn => {
        btn.addEventListener('click', async () => {
            const id = btn.dataset.id;

            if (!confirmarAcao('Deseja realmente EXCLUIR este lançamento? Esta ação não pode ser desfeita.')) {
                return;
            }

            try {
                const result = await excluirLancamento(id);

                if (result.success) {
                    exibirSucesso(result.message);
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    exibirErro(result.message);
                }
            } catch (error) {
                console.error('Erro ao excluir:', error);
                exibirErro('Erro ao excluir lançamento');
            }
        });
    });
}

/**
 * Inicializa modal de baixa
 */
function initModalBaixa() {
    const btnConfirmar = document.getElementById('btnConfirmarBaixa');
    if (!btnConfirmar) return;

    btnConfirmar.addEventListener('click', async () => {
        const id = document.getElementById('baixa_id').value;
        const contaBancariaEl = document.getElementById('baixa_conta_bancaria');
        const contaErroEl = document.getElementById('baixa_conta_erro');
        const dados = {
            data_pagamento:   document.getElementById('baixa_data').value,
            valor_pago:       document.getElementById('baixa_valor').value,
            forma_pagamento:  document.getElementById('baixa_forma').value,
            id_conta_bancaria: contaBancariaEl ? contaBancariaEl.value : ''
        };

        if (!dados.valor_pago || parseFloat(dados.valor_pago) <= 0) {
            exibirErro('Informe o valor pago');
            return;
        }

        if (!dados.id_conta_bancaria) {
            if (contaErroEl) contaErroEl.style.display = '';
            if (contaBancariaEl) contaBancariaEl.focus();
            return;
        }
        if (contaErroEl) contaErroEl.style.display = 'none';

        try {
            btnConfirmar.disabled = true;
            btnConfirmar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processando...';

            const result = await baixarLancamento(id, dados);

            if (result.success) {
                bootstrap.Modal.getInstance(document.getElementById('modalBaixa')).hide();
                exibirSucesso(result.message);
                setTimeout(() => window.location.reload(), 1000);
            } else {
                exibirErro(result.message);
                btnConfirmar.disabled = false;
                btnConfirmar.innerHTML = '<i class="fas fa-check"></i> Confirmar Baixa';
            }
        } catch (error) {
            console.error('Erro ao baixar:', error);
            exibirErro('Erro ao realizar baixa');
            btnConfirmar.disabled = false;
            btnConfirmar.innerHTML = '<i class="fas fa-check"></i> Confirmar Baixa';
        }
    });
}

/**
 * Inicializa modal de cancelamento
 */
function initModalCancelar() {
    const btnConfirmar = document.getElementById('btnConfirmarCancelar');
    if (!btnConfirmar) return;

    btnConfirmar.addEventListener('click', async () => {
        const id = document.getElementById('cancelar_id').value;
        const motivo = document.getElementById('cancelar_motivo').value;

        if (!motivo.trim()) {
            exibirErro('Informe o motivo do cancelamento');
            return;
        }

        try {
            btnConfirmar.disabled = true;
            btnConfirmar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processando...';

            const result = await cancelarLancamento(id, motivo);

            if (result.success) {
                bootstrap.Modal.getInstance(document.getElementById('modalCancelar')).hide();
                exibirSucesso(result.message);
                setTimeout(() => window.location.reload(), 1000);
            } else {
                exibirErro(result.message);
                btnConfirmar.disabled = false;
                btnConfirmar.innerHTML = '<i class="fas fa-times"></i> Confirmar Cancelamento';
            }
        } catch (error) {
            console.error('Erro ao cancelar:', error);
            exibirErro('Erro ao cancelar lancamento');
            btnConfirmar.disabled = false;
            btnConfirmar.innerHTML = '<i class="fas fa-times"></i> Confirmar Cancelamento';
        }
    });
}

/**
 * Inicializa calculo automatico de retencoes
 */
function initCalculoRetencoes() {
    const valorInput = document.querySelector('[name="lancamentos[valor]"]');
    const percInssInput = document.querySelector('[name="lancamentos[percInss]"]');
    const percIssInput = document.querySelector('[name="lancamentos[percIss]"]');
    const valorInssOutput = document.getElementById('valorInss');
    const valorIssOutput = document.getElementById('valorIss');

    if (!valorInput) return;

    function calcularINSS() {
        if (!valorInssOutput) return;
        const valor = parseValor(valorInput.value);
        const perc = parseValor(percInssInput?.value);
        const valorInss = calcularRetencao(valor, perc);
        valorInssOutput.value = valorInss.toFixed(2);
    }

    function calcularISS() {
        if (!valorIssOutput) return;
        const valor = parseValor(valorInput.value);
        const perc = parseValor(percIssInput?.value);
        const valorIss = calcularRetencao(valor, perc);
        valorIssOutput.value = valorIss.toFixed(2);
    }

    valorInput.addEventListener('change', () => {
        calcularINSS();
        calcularISS();
    });

    if (percInssInput) {
        percInssInput.addEventListener('change', calcularINSS);
    }

    if (percIssInput) {
        percIssInput.addEventListener('change', calcularISS);
    }
}

/**
 * Inicializa controle de lancamento recorrente
 */
function initRecorrencia() {
    const tipoSelect   = document.getElementById('lancamentos_recorrenciaTipo');
    const qtdWrapper   = document.getElementById('recorrencia_qtd_wrapper');
    const qtdInput     = document.getElementById('lancamentos_recorrenciaQtd');
    const infoDiv      = document.getElementById('recorrencia_info');
    const previewSpan  = document.getElementById('recorrencia_preview');

    if (!tipoSelect) return;

    const labels = {
        semanal:    'semana(s) — 7 dias cada',
        quinzenal:  'quinzena(s) — 15 dias cada',
        mensal:     'mês(es)',
        bimestral:  'bimestre(s) — a cada 2 meses',
        trimestral: 'trimestre(s) — a cada 3 meses',
        semestral:  'semestre(s) — a cada 6 meses',
        anual:      'ano(s)',
        bienal:     'bienal — a cada 2 anos',
    };

    function atualizar() {
        const tipo = tipoSelect.value;
        if (!tipo || tipo === 'nenhuma') {
            qtdWrapper.style.display = 'none';
            infoDiv.style.display    = 'none';
            return;
        }
        qtdWrapper.style.display = '';
        infoDiv.style.display    = '';
        const qtd = parseInt(qtdInput?.value) || 0;
        if (qtd >= 2) {
            previewSpan.textContent = `Serão criados ${qtd} lançamentos (${labels[tipo] || tipo})`;
        } else {
            previewSpan.textContent = 'Informe a quantidade de parcelas (mínimo 2)';
        }
    }

    tipoSelect.addEventListener('change', atualizar);
    if (qtdInput) qtdInput.addEventListener('input', atualizar);
}

/**
 * Filtra plano de conta conforme tipo (receber=Receita, pagar=Despesa)
 */
function initFiltroPlanoConta() {
    const tipoSelect  = document.getElementById('lancamentos_tipo');
    const planoSelect = document.getElementById('lancamentos_planoConta');
    if (!tipoSelect || !planoSelect) return;

    // Guarda todas as options originais
    const todasOpcoes = Array.from(planoSelect.options).map(o => ({
        value:    o.value,
        text:     o.text,
        tipoPlano: o.dataset.tipo ?? '',
    }));

    function filtrar() {
        const tipo = tipoSelect.value;
        // receber → tipo 0 (Receita) | pagar → tipo 1 (Despesa)
        const tipoFiltro = tipo === 'receber' ? '0' : (tipo === 'pagar' ? '1' : null);
        const valorAtual = planoSelect.value;

        planoSelect.innerHTML = '';
        todasOpcoes.forEach(o => {
            if (o.value === '' || tipoFiltro === null || o.tipoPlano === tipoFiltro) {
                const opt = new Option(o.text, o.value);
                if (o.value && o.value === valorAtual && o.tipoPlano === tipoFiltro) {
                    opt.selected = true;
                }
                planoSelect.appendChild(opt);
            }
        });
    }

    tipoSelect.addEventListener('change', filtrar);
    filtrar(); // aplica ao carregar a página
}

/**
 * Campos monetários: limpa zero ao focar, restaura 0.00 ao sair vazio
 */
function initCamposMonetarios() {
    const nomes = [
        'lancamentos[valor]',
        'lancamentos[valorDesconto]',
        'lancamentos[valorJuros]',
        'lancamentos[valorMulta]',
    ];

    nomes.forEach(nome => {
        const input = document.querySelector(`[name="${nome}"]`);
        if (!input) return;

        input.addEventListener('focus', () => {
            if (!parseFloat(input.value)) {
                input.value = '';
            } else {
                input.select();
            }
        });

        input.addEventListener('blur', () => {
            if (input.value === '' || input.value === null) {
                input.value = '0.00';
            }
        });
    });
}

/**
 * Inicializa preenchimento automatico de competencia
 */
function initCompetenciaAutomatica() {
    const dataVencimentoInput = document.querySelector('[name="lancamentos[dataVencimento]"]');
    const competenciaInput = document.querySelector('[name="lancamentos[competencia]"]');

    if (!dataVencimentoInput || !competenciaInput) return;

    dataVencimentoInput.addEventListener('change', () => {
        const data = dataVencimentoInput.value;
        if (data && !competenciaInput.value) {
            // Extrai mes/ano da data (formato: MM/AAAA)
            const partes = data.split('-');
            if (partes.length >= 2) {
                competenciaInput.value = `${partes[1]}/${partes[0]}`;
            }
        }
    });
}

