/**
 * Lancamentos - App principal
 *
 * Inicializacao e event listeners para o modulo de lancamentos
 */

import {
    baixarLancamento,
    estornarBaixa,
    cancelarLancamento,
    exibirSucesso,
    exibirErro,
    confirmarAcao,
    calcularRetencao,
    parseValor
} from './lancamentos.js';

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
 * Inicializa modal de baixa
 */
function initModalBaixa() {
    const btnConfirmar = document.getElementById('btnConfirmarBaixa');
    if (!btnConfirmar) return;

    btnConfirmar.addEventListener('click', async () => {
        const id = document.getElementById('baixa_id').value;
        const dados = {
            data_pagamento: document.getElementById('baixa_data').value,
            valor_pago: document.getElementById('baixa_valor').value,
            forma_pagamento: document.getElementById('baixa_forma').value
        };

        if (!dados.valor_pago || parseFloat(dados.valor_pago) <= 0) {
            exibirErro('Informe o valor pago');
            return;
        }

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
 * Inicializa preenchimento automatico de competencia
 */
function initCompetenciaAutomatica() {
    const dataVencimentoInput = document.querySelector('[name="lancamentos[dataVencimento]"]');
    const competenciaInput = document.querySelector('[name="lancamentos[competencia]"]');

    if (!dataVencimentoInput || !competenciaInput) return;

    dataVencimentoInput.addEventListener('change', () => {
        const data = dataVencimentoInput.value;
        if (data && !competenciaInput.value) {
            // Extrai ano-mes da data
            const partes = data.split('-');
            if (partes.length >= 2) {
                competenciaInput.value = `${partes[0]}-${partes[1]}`;
            }
        }
    });
}
