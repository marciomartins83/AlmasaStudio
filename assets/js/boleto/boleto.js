/**
 * Boleto Module - Operações AJAX para listagem e ações de boletos
 *
 * Funcionalidades:
 * - Seleção em lote (checkbox)
 * - Registrar boleto individual/lote
 * - Consultar boleto individual/lote
 * - Baixar boleto
 * - Excluir boleto
 * - Toast notifications
 */

'use strict';

// ============================================================================
// TOAST NOTIFICATIONS
// ============================================================================

function showToast(message, type = 'info') {
    // Criar container se não existir
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'position-fixed bottom-0 end-0 p-3';
        container.style.zIndex = '1100';
        document.body.appendChild(container);
    }

    const toastId = 'toast-' + Date.now();
    const bgClass = {
        'success': 'bg-success',
        'error': 'bg-danger',
        'warning': 'bg-warning text-dark',
        'info': 'bg-info text-dark'
    }[type] || 'bg-secondary';

    const iconClass = {
        'success': 'fas fa-check-circle',
        'error': 'fas fa-times-circle',
        'warning': 'fas fa-exclamation-triangle',
        'info': 'fas fa-info-circle'
    }[type] || 'fas fa-info-circle';

    const toastHtml = `
        <div id="${toastId}" class="toast align-items-center text-white ${bgClass} border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="${iconClass} me-2"></i>
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;

    container.insertAdjacentHTML('beforeend', toastHtml);

    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, { delay: 5000 });
    toast.show();

    toastElement.addEventListener('hidden.bs.toast', () => {
        toastElement.remove();
    });
}

// Expor globalmente
window.showToast = showToast;

// ============================================================================
// CSRF TOKEN
// ============================================================================

function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : '';
}

// ============================================================================
// FETCH HELPERS
// ============================================================================

async function fetchWithCsrf(url, options = {}) {
    const defaultOptions = {
        headers: {
            'X-CSRF-Token': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json'
        }
    };

    const mergedOptions = {
        ...defaultOptions,
        ...options,
        headers: {
            ...defaultOptions.headers,
            ...(options.headers || {})
        }
    };

    try {
        const response = await fetch(url, mergedOptions);
        const data = await response.json();
        return { ok: response.ok, status: response.status, data };
    } catch (error) {
        console.error('❌ Erro na requisição:', error);
        return { ok: false, status: 0, data: { success: false, message: 'Erro de conexão' } };
    }
}

// ============================================================================
// SELEÇÃO EM LOTE
// ============================================================================

function initCheckboxSelection() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.boleto-checkbox');
    const batchActions = document.getElementById('batchActions');
    const selectedCount = document.getElementById('selectedCount');

    if (!selectAll) return;

    // Selecionar/desselecionar todos
    selectAll.addEventListener('change', function() {
        checkboxes.forEach(cb => {
            cb.checked = this.checked;
        });
        updateBatchActions();
    });

    // Atualizar ao mudar checkbox individual
    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateBatchActions);
    });

    function updateBatchActions() {
        const checked = document.querySelectorAll('.boleto-checkbox:checked');
        const count = checked.length;

        if (batchActions) {
            batchActions.style.display = count > 0 ? 'block' : 'none';
        }
        if (selectedCount) {
            selectedCount.textContent = count;
        }

        // Atualizar estado do selectAll
        if (selectAll) {
            const total = checkboxes.length;
            selectAll.checked = count === total && total > 0;
            selectAll.indeterminate = count > 0 && count < total;
        }
    }
}

function getSelectedIds() {
    const checked = document.querySelectorAll('.boleto-checkbox:checked');
    return Array.from(checked).map(cb => parseInt(cb.value));
}

// ============================================================================
// AÇÕES INDIVIDUAIS
// ============================================================================

async function registrarBoleto(id) {
    if (!window.ROUTES || !window.ROUTES.registrar) {
        showToast('Rota de registro não configurada', 'error');
        return;
    }

    const url = window.ROUTES.registrar.replace('__ID__', id);
    showToast('Registrando boleto...', 'info');

    const result = await fetchWithCsrf(url, { method: 'POST' });

    if (result.data.success) {
        showToast(result.data.message || 'Boleto registrado com sucesso!', 'success');
        updateBoletoRow(id, result.data);
    } else {
        showToast(result.data.message || 'Erro ao registrar boleto', 'error');
    }
}

async function consultarBoleto(id) {
    if (!window.ROUTES || !window.ROUTES.consultar) {
        showToast('Rota de consulta não configurada', 'error');
        return;
    }

    const url = window.ROUTES.consultar.replace('__ID__', id);
    showToast('Consultando boleto...', 'info');

    const result = await fetchWithCsrf(url, { method: 'POST' });

    if (result.data.success) {
        showToast(result.data.message || 'Consulta realizada!', 'success');
        updateBoletoRow(id, result.data);
    } else {
        showToast(result.data.message || 'Erro ao consultar boleto', 'error');
    }
}

async function baixarBoleto(id, motivo) {
    if (!window.ROUTES || !window.ROUTES.baixar) {
        showToast('Rota de baixa não configurada', 'error');
        return;
    }

    const url = window.ROUTES.baixar.replace('__ID__', id);
    showToast('Baixando boleto...', 'info');

    const result = await fetchWithCsrf(url, {
        method: 'POST',
        body: JSON.stringify({ motivo })
    });

    if (result.data.success) {
        showToast(result.data.message || 'Boleto baixado com sucesso!', 'success');
        updateBoletoRow(id, result.data);
        // Fechar modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalBaixar'));
        if (modal) modal.hide();
    } else {
        showToast(result.data.message || 'Erro ao baixar boleto', 'error');
    }
}

async function excluirBoleto(id) {
    if (!window.ROUTES || !window.ROUTES.delete) {
        showToast('Rota de exclusão não configurada', 'error');
        return;
    }

    const url = window.ROUTES.delete.replace('__ID__', id);
    showToast('Excluindo boleto...', 'info');

    const result = await fetchWithCsrf(url, { method: 'DELETE' });

    if (result.data.success) {
        showToast(result.data.message || 'Boleto excluído com sucesso!', 'success');
        // Remover linha da tabela
        const row = document.querySelector(`tr[data-boleto-id="${id}"]`);
        if (row) {
            row.remove();
        }
        // Fechar modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalExcluir'));
        if (modal) modal.hide();
        // Recarregar se página de show
        if (window.ROUTES.index) {
            setTimeout(() => window.location.href = window.ROUTES.index, 1000);
        }
    } else {
        showToast(result.data.message || 'Erro ao excluir boleto', 'error');
    }
}

// ============================================================================
// AÇÕES EM LOTE
// ============================================================================

async function registrarLote() {
    const ids = getSelectedIds();
    if (ids.length === 0) {
        showToast('Nenhum boleto selecionado', 'warning');
        return;
    }

    if (!window.ROUTES || !window.ROUTES.registrarLote) {
        showToast('Rota de registro em lote não configurada', 'error');
        return;
    }

    showToast(`Registrando ${ids.length} boleto(s)...`, 'info');

    const result = await fetchWithCsrf(window.ROUTES.registrarLote, {
        method: 'POST',
        body: JSON.stringify({ ids })
    });

    if (result.data.success) {
        showToast(result.data.message, 'success');
        // Recarregar página para atualizar status
        setTimeout(() => location.reload(), 1500);
    } else {
        showToast(result.data.message || 'Erro ao registrar boletos', 'error');
    }
}

async function consultarLote() {
    const ids = getSelectedIds();
    if (ids.length === 0) {
        showToast('Nenhum boleto selecionado', 'warning');
        return;
    }

    if (!window.ROUTES || !window.ROUTES.consultarLote) {
        showToast('Rota de consulta em lote não configurada', 'error');
        return;
    }

    showToast(`Consultando ${ids.length} boleto(s)...`, 'info');

    const result = await fetchWithCsrf(window.ROUTES.consultarLote, {
        method: 'POST',
        body: JSON.stringify({ ids })
    });

    if (result.data.success) {
        showToast(result.data.message, 'success');
        // Recarregar página para atualizar status
        setTimeout(() => location.reload(), 1500);
    } else {
        showToast(result.data.message || 'Erro ao consultar boletos', 'error');
    }
}

// ============================================================================
// ATUALIZAÇÃO DE UI
// ============================================================================

function updateBoletoRow(id, data) {
    const row = document.querySelector(`tr[data-boleto-id="${id}"]`);
    if (!row) return;

    // Atualizar badge de status
    const statusBadge = row.querySelector('.badge-status');
    if (statusBadge && data.statusLabel && data.statusClass) {
        statusBadge.textContent = data.statusLabel;
        statusBadge.className = `badge badge-status bg-${data.statusClass}`;
    }

    // Atualizar código de barras se existir
    const codigoBarrasCell = row.querySelector('.codigo-barras');
    if (codigoBarrasCell && data.codigoBarras) {
        codigoBarrasCell.textContent = data.codigoBarras.substring(0, 20) + '...';
    }

    // Atualizar botões baseado no novo status
    const actionsCell = row.querySelector('.actions-cell');
    if (actionsCell) {
        updateActionButtons(actionsCell, id, data.status);
    }
}

function updateActionButtons(cell, id, status) {
    // Por simplicidade, recarregar a página após mudança de status
    // Em uma implementação mais sofisticada, reconstruiríamos os botões
}

// ============================================================================
// INICIALIZAÇÃO
// ============================================================================

document.addEventListener('DOMContentLoaded', function() {
    console.log('🎫 Boleto module loaded');

    // Inicializar seleção em lote
    initCheckboxSelection();

    // Botões de ação individual na listagem
    document.querySelectorAll('[data-action="registrar"]').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            registrarBoleto(id);
        });
    });

    document.querySelectorAll('[data-action="consultar"]').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            consultarBoleto(id);
        });
    });

    // Modal de baixa - guardar ID
    let boletoIdBaixar = null;
    document.querySelectorAll('[data-action="baixar"]').forEach(btn => {
        btn.addEventListener('click', function() {
            boletoIdBaixar = this.dataset.id;
            const modal = new bootstrap.Modal(document.getElementById('modalBaixar'));
            modal.show();
        });
    });

    // Confirmar baixa
    const btnConfirmarBaixa = document.getElementById('btnConfirmarBaixa');
    if (btnConfirmarBaixa) {
        btnConfirmarBaixa.addEventListener('click', function() {
            const motivo = document.getElementById('motivoBaixa').value;
            if (boletoIdBaixar) {
                baixarBoleto(boletoIdBaixar, motivo);
            }
        });
    }

    // Modal de exclusão - guardar ID
    let boletoIdExcluir = null;
    document.querySelectorAll('[data-action="excluir"]').forEach(btn => {
        btn.addEventListener('click', function() {
            boletoIdExcluir = this.dataset.id;
            const modal = new bootstrap.Modal(document.getElementById('modalExcluir'));
            modal.show();
        });
    });

    // Confirmar exclusão
    const btnConfirmarExcluir = document.getElementById('btnConfirmarExcluir');
    if (btnConfirmarExcluir) {
        btnConfirmarExcluir.addEventListener('click', function() {
            if (boletoIdExcluir) {
                excluirBoleto(boletoIdExcluir);
            }
        });
    }

    // Botões de ação em lote
    const btnRegistrarLote = document.getElementById('btnRegistrarLote');
    if (btnRegistrarLote) {
        btnRegistrarLote.addEventListener('click', registrarLote);
    }

    const btnConsultarLote = document.getElementById('btnConsultarLote');
    if (btnConsultarLote) {
        btnConsultarLote.addEventListener('click', consultarLote);
    }

    // === PÁGINA SHOW (botões únicos) ===

    // Registrar (página show)
    const btnRegistrar = document.getElementById('btnRegistrar');
    if (btnRegistrar && window.BOLETO_DATA) {
        btnRegistrar.addEventListener('click', function() {
            registrarBoletoShow();
        });
    }

    // Consultar (página show)
    const btnConsultar = document.getElementById('btnConsultar');
    if (btnConsultar && window.BOLETO_DATA) {
        btnConsultar.addEventListener('click', function() {
            consultarBoletoShow();
        });
    }

    // Baixar (página show)
    const btnBaixar = document.getElementById('btnBaixar');
    if (btnBaixar && window.BOLETO_DATA) {
        btnBaixar.addEventListener('click', function() {
            const modal = new bootstrap.Modal(document.getElementById('modalBaixar'));
            modal.show();
        });
    }

    // Confirmar baixa (página show)
    if (btnConfirmarBaixa && window.BOLETO_DATA) {
        btnConfirmarBaixa.addEventListener('click', function() {
            const motivo = document.getElementById('motivoBaixa').value;
            baixarBoletoShow(motivo);
        });
    }

    // Excluir (página show)
    const btnExcluir = document.getElementById('btnExcluir');
    if (btnExcluir && window.BOLETO_DATA) {
        btnExcluir.addEventListener('click', function() {
            const modal = new bootstrap.Modal(document.getElementById('modalExcluir'));
            modal.show();
        });
    }

    // Confirmar exclusão (página show)
    if (btnConfirmarExcluir && window.BOLETO_DATA) {
        btnConfirmarExcluir.addEventListener('click', function() {
            excluirBoletoShow();
        });
    }
});

// ============================================================================
// AÇÕES PÁGINA SHOW
// ============================================================================

async function registrarBoletoShow() {
    if (!window.ROUTES || !window.ROUTES.registrar) return;

    showToast('Registrando boleto...', 'info');
    const result = await fetchWithCsrf(window.ROUTES.registrar, { method: 'POST' });

    if (result.data.success) {
        showToast(result.data.message || 'Boleto registrado!', 'success');
        setTimeout(() => location.reload(), 1500);
    } else {
        showToast(result.data.message || 'Erro ao registrar', 'error');
    }
}

async function consultarBoletoShow() {
    if (!window.ROUTES || !window.ROUTES.consultar) return;

    showToast('Consultando boleto...', 'info');
    const result = await fetchWithCsrf(window.ROUTES.consultar, { method: 'POST' });

    if (result.data.success) {
        showToast(result.data.message || 'Consulta realizada!', 'success');
        setTimeout(() => location.reload(), 1500);
    } else {
        showToast(result.data.message || 'Erro ao consultar', 'error');
    }
}

async function baixarBoletoShow(motivo) {
    if (!window.ROUTES || !window.ROUTES.baixar) return;

    showToast('Baixando boleto...', 'info');
    const result = await fetchWithCsrf(window.ROUTES.baixar, {
        method: 'POST',
        body: JSON.stringify({ motivo })
    });

    if (result.data.success) {
        showToast(result.data.message || 'Boleto baixado!', 'success');
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalBaixar'));
        if (modal) modal.hide();
        setTimeout(() => location.reload(), 1500);
    } else {
        showToast(result.data.message || 'Erro ao baixar', 'error');
    }
}

async function excluirBoletoShow() {
    if (!window.ROUTES || !window.ROUTES.delete) return;

    showToast('Excluindo boleto...', 'info');
    const result = await fetchWithCsrf(window.ROUTES.delete, { method: 'DELETE' });

    if (result.data.success) {
        showToast(result.data.message || 'Boleto excluído!', 'success');
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalExcluir'));
        if (modal) modal.hide();
        setTimeout(() => {
            window.location.href = window.ROUTES.index;
        }, 1500);
    } else {
        showToast(result.data.message || 'Erro ao excluir', 'error');
    }
}
