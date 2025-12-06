/**
 * Ficha Financeira - Módulo Principal
 * Gerencia listagem, baixas, estornos e geração automática de lançamentos
 */

/**
 * Retorna o token CSRF da meta tag
 */
function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : '';
}

/**
 * Retorna headers padrão para requisições AJAX
 */
function getAjaxHeaders() {
    return {
        'X-CSRF-Token': getCsrfToken(),
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json'
    };
}

/**
 * Formata valor como moeda brasileira
 */
function formatarMoeda(valor) {
    const numero = parseFloat(valor) || 0;
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(numero);
}

/**
 * Converte string formatada em número
 */
function parseNumero(valorFormatado) {
    if (typeof valorFormatado === 'number') return valorFormatado;
    if (!valorFormatado) return 0;

    return parseFloat(
        valorFormatado
            .replace(/[R$\s]/g, '')
            .replace(/\./g, '')
            .replace(',', '.')
    ) || 0;
}

/**
 * Exibe toast Bootstrap
 */
function exibirToast(mensagem, tipo = 'info') {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        container.style.zIndex = '1100';
        document.body.appendChild(container);
    }

    const toastId = 'toast-' + Date.now();
    const toastHtml = `
        <div id="${toastId}" class="toast align-items-center text-bg-${tipo} border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    ${mensagem}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;

    container.insertAdjacentHTML('beforeend', toastHtml);

    const toastEl = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastEl, { delay: 5000 });
    toast.show();

    toastEl.addEventListener('hidden.bs.toast', () => {
        toastEl.remove();
    });
}

/**
 * Define estado de loading em um botão
 */
function setButtonLoading(button, loading) {
    if (loading) {
        button.disabled = true;
        button.dataset.originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Aguarde...';
    } else {
        button.disabled = false;
        button.innerHTML = button.dataset.originalText || button.innerHTML;
    }
}

/**
 * Formata campo de valor monetário
 */
function formatarCampoMonetario(input) {
    let valor = input.value.replace(/\D/g, '');
    if (valor === '') {
        input.value = '';
        return;
    }
    valor = (parseInt(valor) / 100).toFixed(2);
    input.value = valor.replace('.', ',');
}

/**
 * Inicializa a funcionalidade de Baixa (Pagamento)
 */
function initBaixa() {
    const modalBaixa = document.getElementById('modalBaixa');
    if (!modalBaixa) return;

    const baixaDataInput = document.getElementById('baixaData');
    const baixaValorInput = document.getElementById('baixaValor');
    const baixaLancamentoIdInput = document.getElementById('baixaLancamentoId');
    const btnConfirmarBaixa = document.getElementById('btnConfirmarBaixa');

    // Configurar data padrão como hoje
    if (baixaDataInput) {
        baixaDataInput.value = new Date().toISOString().split('T')[0];
    }

    // Formatação do campo valor
    if (baixaValorInput) {
        baixaValorInput.addEventListener('input', function() {
            formatarCampoMonetario(this);
        });
    }

    // Botões de baixa - event delegation
    document.addEventListener('click', function(e) {
        const btnBaixa = e.target.closest('.btn-baixa');
        if (btnBaixa) {
            e.preventDefault();
            const lancamentoId = btnBaixa.dataset.id;
            const saldo = btnBaixa.dataset.saldo;

            baixaLancamentoIdInput.value = lancamentoId;
            baixaValorInput.value = parseFloat(saldo).toFixed(2).replace('.', ',');
            baixaDataInput.value = new Date().toISOString().split('T')[0];
            document.getElementById('baixaFormaPagamento').value = 'boleto';
            document.getElementById('baixaNumeroDocumento').value = '';
            document.getElementById('baixaObservacoes').value = '';

            const modal = new bootstrap.Modal(modalBaixa);
            modal.show();
        }
    });

    // Confirmar baixa
    if (btnConfirmarBaixa) {
        btnConfirmarBaixa.addEventListener('click', async function() {
            const lancamentoId = baixaLancamentoIdInput.value;
            const valor = parseNumero(baixaValorInput.value);
            const dataPagamento = baixaDataInput.value;
            const formaPagamento = document.getElementById('baixaFormaPagamento').value;
            const numeroDocumento = document.getElementById('baixaNumeroDocumento').value;
            const observacoes = document.getElementById('baixaObservacoes').value;

            if (!dataPagamento) {
                exibirToast('Informe a data do pagamento', 'warning');
                return;
            }

            if (valor <= 0) {
                exibirToast('Informe um valor válido', 'warning');
                return;
            }

            setButtonLoading(this, true);

            try {
                const url = window.ROUTES.realizarBaixa.replace('__ID__', lancamentoId);
                const response = await fetch(url, {
                    method: 'POST',
                    headers: getAjaxHeaders(),
                    body: JSON.stringify({
                        dataPagamento,
                        valorPago: valor,
                        formaPagamento,
                        numeroDocumento,
                        observacoes
                    })
                });

                const data = await response.json();

                if (data.success) {
                    exibirToast('Pagamento registrado com sucesso!', 'success');
                    bootstrap.Modal.getInstance(modalBaixa).hide();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    exibirToast(data.message || 'Erro ao registrar pagamento', 'danger');
                }
            } catch (error) {
                console.error('Erro:', error);
                exibirToast('Erro ao processar requisição', 'danger');
            } finally {
                setButtonLoading(this, false);
            }
        });
    }
}

/**
 * Inicializa a funcionalidade de Estorno
 */
function initEstorno() {
    const modalEstorno = document.getElementById('modalEstorno');
    if (!modalEstorno) return;

    const estornoBaixaIdInput = document.getElementById('estornoBaixaId');
    const estornoMotivoInput = document.getElementById('estornoMotivo');
    const btnConfirmarEstorno = document.getElementById('btnConfirmarEstorno');

    // Botões de estorno - event delegation
    document.addEventListener('click', function(e) {
        const btnEstornar = e.target.closest('.btn-estornar');
        if (btnEstornar) {
            e.preventDefault();
            const baixaId = btnEstornar.dataset.id;

            estornoBaixaIdInput.value = baixaId;
            estornoMotivoInput.value = '';

            const modal = new bootstrap.Modal(modalEstorno);
            modal.show();
        }
    });

    // Confirmar estorno
    if (btnConfirmarEstorno) {
        btnConfirmarEstorno.addEventListener('click', async function() {
            const baixaId = estornoBaixaIdInput.value;
            const motivo = estornoMotivoInput.value.trim();

            if (!motivo) {
                exibirToast('Informe o motivo do estorno', 'warning');
                return;
            }

            setButtonLoading(this, true);

            try {
                const url = window.ROUTES.estornarBaixa.replace('__ID__', baixaId);
                const response = await fetch(url, {
                    method: 'POST',
                    headers: getAjaxHeaders(),
                    body: JSON.stringify({ motivo })
                });

                const data = await response.json();

                if (data.success) {
                    exibirToast('Pagamento estornado com sucesso!', 'success');
                    bootstrap.Modal.getInstance(modalEstorno).hide();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    exibirToast(data.message || 'Erro ao estornar pagamento', 'danger');
                }
            } catch (error) {
                console.error('Erro:', error);
                exibirToast('Erro ao processar requisição', 'danger');
            } finally {
                setButtonLoading(this, false);
            }
        });
    }
}

/**
 * Inicializa a funcionalidade de Cancelamento de Lançamento
 */
function initCancelamento() {
    const modalCancelar = document.getElementById('modalCancelar');
    if (!modalCancelar) return;

    const cancelarLancamentoIdInput = document.getElementById('cancelarLancamentoId');
    const cancelarMotivoInput = document.getElementById('cancelarMotivo');
    const btnConfirmarCancelar = document.getElementById('btnConfirmarCancelar');

    // Botões de cancelar - event delegation
    document.addEventListener('click', function(e) {
        const btnCancelar = e.target.closest('.btn-cancelar');
        if (btnCancelar) {
            e.preventDefault();
            const lancamentoId = btnCancelar.dataset.id;

            cancelarLancamentoIdInput.value = lancamentoId;
            cancelarMotivoInput.value = '';

            const modal = new bootstrap.Modal(modalCancelar);
            modal.show();
        }
    });

    // Confirmar cancelamento
    if (btnConfirmarCancelar) {
        btnConfirmarCancelar.addEventListener('click', async function() {
            const lancamentoId = cancelarLancamentoIdInput.value;
            const motivo = cancelarMotivoInput.value.trim();

            if (!motivo) {
                exibirToast('Informe o motivo do cancelamento', 'warning');
                return;
            }

            setButtonLoading(this, true);

            try {
                const url = window.ROUTES.cancelarLancamento.replace('__ID__', lancamentoId);
                const response = await fetch(url, {
                    method: 'POST',
                    headers: getAjaxHeaders(),
                    body: JSON.stringify({ motivo })
                });

                const data = await response.json();

                if (data.success) {
                    exibirToast('Lançamento cancelado com sucesso!', 'success');
                    bootstrap.Modal.getInstance(modalCancelar).hide();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    exibirToast(data.message || 'Erro ao cancelar lançamento', 'danger');
                }
            } catch (error) {
                console.error('Erro:', error);
                exibirToast('Erro ao processar requisição', 'danger');
            } finally {
                setButtonLoading(this, false);
            }
        });
    }
}

/**
 * Inicializa a funcionalidade de Geração Automática de Lançamentos
 */
function initGerarLancamentos() {
    const modalGerar = document.getElementById('modalGerarLancamentos');
    if (!modalGerar) return;

    const competenciaInput = document.getElementById('competenciaGerar');
    const btnGerar = document.getElementById('btnGerarLancamentos');

    // Configurar competência padrão como mês atual
    if (competenciaInput) {
        const hoje = new Date();
        const ano = hoje.getFullYear();
        const mes = String(hoje.getMonth() + 1).padStart(2, '0');
        competenciaInput.value = `${ano}-${mes}`;
    }

    // Confirmar geração
    if (btnGerar) {
        btnGerar.addEventListener('click', async function() {
            const competencia = competenciaInput.value;

            if (!competencia) {
                exibirToast('Selecione a competência', 'warning');
                return;
            }

            setButtonLoading(this, true);

            try {
                const response = await fetch(window.ROUTES.gerarLancamentos, {
                    method: 'POST',
                    headers: getAjaxHeaders(),
                    body: JSON.stringify({ competencia })
                });

                const data = await response.json();

                if (data.success) {
                    exibirToast(data.message || 'Lançamentos gerados com sucesso!', 'success');
                    bootstrap.Modal.getInstance(modalGerar).hide();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    exibirToast(data.message || 'Erro ao gerar lançamentos', 'danger');
                }
            } catch (error) {
                console.error('Erro:', error);
                exibirToast('Erro ao processar requisição', 'danger');
            } finally {
                setButtonLoading(this, false);
            }
        });
    }
}

// Inicialização quando DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    console.log('Inicializando módulo Ficha Financeira');

    initBaixa();
    initEstorno();
    initCancelamento();
    initGerarLancamentos();

    console.log('Módulo Ficha Financeira inicializado');
});
