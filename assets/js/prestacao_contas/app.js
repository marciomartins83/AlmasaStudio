/**
 * Prestacao de Contas - Aplicacao Principal
 */

import {
    carregarImoveisPorProprietario,
    calcularPeriodo,
    gerarPreview,
    aprovarPrestacao,
    cancelarPrestacao,
    excluirPrestacao,
    exibirPreview,
    exibirSucesso,
    exibirErro,
    confirmarAcao
} from './prestacao_contas.js';

/**
 * Inicializa a pagina de geracao
 */
function initGerar() {
    const tipoPeriodo = document.getElementById('tipoPeriodo');
    const proprietario = document.getElementById('proprietario');
    const imovelSelect = document.getElementById('imovel');
    const btnPreview = document.getElementById('btnPreview');

    // Ao mudar tipo de periodo, recalcular datas
    if (tipoPeriodo) {
        tipoPeriodo.addEventListener('change', async function() {
            const periodo = await calcularPeriodo(this.value);
            if (periodo) {
                const dataInicio = document.getElementById('dataInicio');
                const dataFim = document.getElementById('dataFim');
                if (dataInicio) dataInicio.value = periodo.dataInicio;
                if (dataFim) dataFim.value = periodo.dataFim;
            }
        });

        // Trigger inicial para calcular periodo
        tipoPeriodo.dispatchEvent(new Event('change'));
    }

    // Ao mudar proprietario, carregar imoveis
    if (proprietario && imovelSelect) {
        proprietario.addEventListener('change', async function() {
            const idProprietario = this.value;

            // Limpar select de imoveis
            imovelSelect.innerHTML = '<option value="">Todos os imoveis</option>';

            if (idProprietario) {
                const imoveis = await carregarImoveisPorProprietario(idProprietario);

                imoveis.forEach(function(imovel) {
                    const option = document.createElement('option');
                    option.value = imovel.id;
                    option.textContent = imovel.descricao;
                    imovelSelect.appendChild(option);
                });
            }
        });
    }

    // Botao Preview
    if (btnPreview) {
        btnPreview.addEventListener('click', async function() {
            const proprietarioValue = proprietario ? proprietario.value : null;
            const imovelValue = imovelSelect ? imovelSelect.value : null;
            const dataInicio = document.getElementById('dataInicio');
            const dataFim = document.getElementById('dataFim');
            const incluirFichaFinanceira = document.getElementById('incluirFichaFinanceira');
            const incluirLancamentos = document.getElementById('incluirLancamentos');

            if (!proprietarioValue) {
                exibirErro('Selecione um proprietario');
                return;
            }

            if (!dataInicio || !dataInicio.value) {
                exibirErro('Informe a data de inicio');
                return;
            }

            if (!dataFim || !dataFim.value) {
                exibirErro('Informe a data de fim');
                return;
            }

            const filtros = {
                proprietario: proprietarioValue,
                imovel: imovelValue || null,
                dataInicio: dataInicio.value,
                dataFim: dataFim.value,
                incluirFichaFinanceira: incluirFichaFinanceira ? incluirFichaFinanceira.checked : true,
                incluirLancamentos: incluirLancamentos ? incluirLancamentos.checked : true
            };

            btnPreview.disabled = true;
            btnPreview.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Carregando...';

            const resultado = await gerarPreview(filtros);

            btnPreview.disabled = false;
            btnPreview.innerHTML = '<i class="fas fa-search"></i> Preview';

            if (resultado.success) {
                exibirPreview(resultado.data);
            } else {
                exibirErro(resultado.message);
            }
        });
    }
}

/**
 * Inicializa a listagem (index)
 */
function initIndex() {
    // Botoes de aprovar
    document.querySelectorAll('.btn-aprovar').forEach(function(btn) {
        btn.addEventListener('click', async function() {
            const id = this.dataset.id;

            if (!confirmarAcao('Deseja aprovar esta prestacao de contas?')) {
                return;
            }

            const resultado = await aprovarPrestacao(id);

            if (resultado.success) {
                exibirSucesso(resultado.message);
                location.reload();
            } else {
                exibirErro(resultado.message);
            }
        });
    });

    // Botoes de cancelar
    document.querySelectorAll('.btn-cancelar').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const modal = document.getElementById('modalCancelar');
            const inputId = document.getElementById('cancelar_id');

            if (inputId) inputId.value = id;

            if (modal) {
                const bsModal = new bootstrap.Modal(modal);
                bsModal.show();
            }
        });
    });

    // Confirmar cancelamento
    const btnConfirmarCancelar = document.getElementById('btnConfirmarCancelar');
    if (btnConfirmarCancelar) {
        btnConfirmarCancelar.addEventListener('click', async function() {
            const id = document.getElementById('cancelar_id').value;
            const motivo = document.getElementById('cancelar_motivo').value;

            const resultado = await cancelarPrestacao(id, motivo);

            if (resultado.success) {
                exibirSucesso(resultado.message);
                location.reload();
            } else {
                exibirErro(resultado.message);
            }
        });
    }

    // Botoes de excluir
    document.querySelectorAll('.btn-excluir').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const modal = document.getElementById('modalExcluir');
            const inputId = document.getElementById('excluir_id');

            if (inputId) inputId.value = id;

            if (modal) {
                const bsModal = new bootstrap.Modal(modal);
                bsModal.show();
            }
        });
    });

    // Confirmar exclusao
    const btnConfirmarExcluir = document.getElementById('btnConfirmarExcluir');
    if (btnConfirmarExcluir) {
        btnConfirmarExcluir.addEventListener('click', async function() {
            const id = document.getElementById('excluir_id').value;

            const resultado = await excluirPrestacao(id);

            if (resultado.success) {
                exibirSucesso(resultado.message);
                location.reload();
            } else {
                exibirErro(resultado.message);
            }
        });
    }
}

/**
 * Inicializa a pagina de visualizacao
 */
function initVisualizar() {
    // Botao de aprovar (unico na pagina)
    const btnAprovar = document.querySelector('.btn-aprovar');
    if (btnAprovar) {
        btnAprovar.addEventListener('click', async function() {
            const id = this.dataset.id || window.PRESTACAO_ID;

            if (!confirmarAcao('Deseja aprovar esta prestacao de contas?')) {
                return;
            }

            const resultado = await aprovarPrestacao(id);

            if (resultado.success) {
                exibirSucesso(resultado.message);
                location.reload();
            } else {
                exibirErro(resultado.message);
            }
        });
    }

    // Botao de cancelar
    const btnCancelar = document.querySelector('.btn-cancelar');
    if (btnCancelar) {
        btnCancelar.addEventListener('click', function() {
            const id = this.dataset.id || window.PRESTACAO_ID;
            const modal = document.getElementById('modalCancelar');
            const inputId = document.getElementById('cancelar_id');

            if (inputId) inputId.value = id;

            if (modal) {
                const bsModal = new bootstrap.Modal(modal);
                bsModal.show();
            }
        });
    }

    // Confirmar cancelamento
    const btnConfirmarCancelar = document.getElementById('btnConfirmarCancelar');
    if (btnConfirmarCancelar) {
        btnConfirmarCancelar.addEventListener('click', async function() {
            const id = document.getElementById('cancelar_id').value;
            const motivo = document.getElementById('cancelar_motivo').value;

            const resultado = await cancelarPrestacao(id, motivo);

            if (resultado.success) {
                exibirSucesso(resultado.message);
                location.reload();
            } else {
                exibirErro(resultado.message);
            }
        });
    }
}

/**
 * Inicializacao
 */
document.addEventListener('DOMContentLoaded', function() {
    // Detectar qual pagina estamos
    const formGerar = document.getElementById('formGerar');
    const btnAprovarList = document.querySelectorAll('.btn-aprovar');
    const btnPreview = document.getElementById('btnPreview');

    if (formGerar || btnPreview) {
        // Pagina de geracao
        initGerar();
    } else if (window.PRESTACAO_ID) {
        // Pagina de visualizacao
        initVisualizar();
    } else if (btnAprovarList.length > 0) {
        // Pagina de listagem
        initIndex();
    }

    console.log('Modulo Prestacao de Contas carregado');
});
