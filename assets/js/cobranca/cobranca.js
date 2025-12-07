/**
 * Módulo de Cobranças - JavaScript
 *
 * Funcionalidades:
 * - Seleção de múltiplas cobranças
 * - Envio individual e em lote
 * - Preview antes do envio
 * - Cancelamento de cobrança
 */

document.addEventListener('DOMContentLoaded', function() {
    initCobranca();
});

/**
 * Inicializa o módulo de cobranças
 */
function initCobranca() {
    // Elementos
    const checkAll = document.getElementById('checkAll');
    const cobrancaChecks = document.querySelectorAll('.cobranca-check');
    const btnEnviarSelecionados = document.getElementById('btnEnviarSelecionados');
    const btnPreview = document.getElementById('btnPreview');
    const contadorSelecionados = document.getElementById('contadorSelecionados');

    // Verificar se estamos na página de listagem
    if (checkAll) {
        initCheckboxes(checkAll, cobrancaChecks, btnEnviarSelecionados, btnPreview, contadorSelecionados);
    }

    // Inicializar botões de envio individual
    initBotoesEnviar();

    // Inicializar botões de cancelar
    initBotoesCancelar();

    // Inicializar preview
    initPreview();
}

/**
 * Inicializa checkboxes de seleção
 */
function initCheckboxes(checkAll, cobrancaChecks, btnEnviar, btnPreview, contador) {
    // Selecionar/Desselecionar todos
    checkAll.addEventListener('change', function() {
        cobrancaChecks.forEach(check => {
            check.checked = this.checked;
        });
        atualizarContador(cobrancaChecks, btnEnviar, btnPreview, contador);
    });

    // Cada checkbox individual
    cobrancaChecks.forEach(check => {
        check.addEventListener('change', function() {
            atualizarContador(cobrancaChecks, btnEnviar, btnPreview, contador);

            // Atualizar checkAll
            const todosChecados = Array.from(cobrancaChecks).every(c => c.checked);
            const algunsChecados = Array.from(cobrancaChecks).some(c => c.checked);

            checkAll.checked = todosChecados;
            checkAll.indeterminate = algunsChecados && !todosChecados;
        });
    });

    // Botão enviar selecionados
    if (btnEnviar) {
        btnEnviar.addEventListener('click', function() {
            const selecionados = getSelecionados(cobrancaChecks);
            if (selecionados.length > 0) {
                confirmarEnvioLote(selecionados);
            }
        });
    }
}

/**
 * Atualiza contador de selecionados
 */
function atualizarContador(checks, btnEnviar, btnPreview, contador) {
    const selecionados = getSelecionados(checks);
    const qtd = selecionados.length;

    if (contador) {
        contador.textContent = qtd;
    }

    if (btnEnviar) {
        btnEnviar.disabled = qtd === 0;
    }

    if (btnPreview) {
        btnPreview.disabled = qtd === 0;
    }
}

/**
 * Retorna IDs das cobranças selecionadas
 */
function getSelecionados(checks) {
    return Array.from(checks)
        .filter(c => c.checked)
        .map(c => parseInt(c.value));
}

/**
 * Inicializa botões de envio individual
 */
function initBotoesEnviar() {
    document.querySelectorAll('.btn-enviar').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            confirmarEnvio(id);
        });
    });
}

/**
 * Confirma e envia cobrança individual
 */
function confirmarEnvio(id) {
    if (!confirm('Confirma o envio desta cobrança?')) {
        return;
    }

    const btn = document.querySelector(`.btn-enviar[data-id="${id}"]`);
    const originalHtml = btn.innerHTML;

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

    const url = window.COBRANCA_ROUTES.enviar.replace('__ID__', id);
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-Token': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('success', data.message || 'Cobrança enviada com sucesso!');

            // Atualizar linha na tabela
            const row = document.querySelector(`tr[data-id="${id}"]`);
            if (row) {
                // Atualizar badge de status
                const statusCell = row.querySelector('td:nth-child(9)');
                if (statusCell && data.statusClass && data.statusLabel) {
                    statusCell.innerHTML = `<span class="badge ${data.statusClass}">${data.statusLabel}</span>`;
                }

                // Remover checkbox e botões de ação
                const checkCell = row.querySelector('td:first-child');
                if (checkCell) {
                    checkCell.innerHTML = '';
                }

                const actionsCell = row.querySelector('td:last-child .btn-group');
                if (actionsCell) {
                    // Manter apenas botão de ver detalhes
                    const viewBtn = actionsCell.querySelector('a.btn-info');
                    actionsCell.innerHTML = viewBtn ? viewBtn.outerHTML : '';
                }
            }
        } else {
            showToast('danger', data.message || 'Erro ao enviar cobrança');
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        showToast('danger', 'Erro de comunicação com o servidor');
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    });
}

/**
 * Confirma e envia cobranças em lote
 */
function confirmarEnvioLote(ids) {
    if (!confirm(`Confirma o envio de ${ids.length} cobrança(s)?`)) {
        return;
    }

    const btnEnviar = document.getElementById('btnEnviarSelecionados');
    const originalHtml = btnEnviar.innerHTML;

    btnEnviar.disabled = true;
    btnEnviar.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Enviando...';

    const url = window.COBRANCA_ROUTES.enviarLote;
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-Token': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ ids: ids })
    })
    .then(response => response.json())
    .then(data => {
        btnEnviar.disabled = false;
        btnEnviar.innerHTML = originalHtml;

        if (data.success) {
            mostrarResultadoLote(data);
        } else {
            showToast('danger', data.message || 'Erro ao processar envio em lote');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        showToast('danger', 'Erro de comunicação com o servidor');
        btnEnviar.disabled = false;
        btnEnviar.innerHTML = originalHtml;
    });
}

/**
 * Mostra resultado do envio em lote
 */
function mostrarResultadoLote(data) {
    const content = document.getElementById('resultadoLoteContent');

    let html = `
        <div class="alert ${data.falha > 0 ? 'alert-warning' : 'alert-success'}">
            <strong>${data.message}</strong>
        </div>
        <div class="row text-center mb-3">
            <div class="col">
                <h4 class="text-success">${data.sucesso}</h4>
                <small>Sucesso</small>
            </div>
            <div class="col">
                <h4 class="text-danger">${data.falha}</h4>
                <small>Falha</small>
            </div>
            <div class="col">
                <h4 class="text-muted">${data.total}</h4>
                <small>Total</small>
            </div>
        </div>
    `;

    if (data.detalhes && data.detalhes.length > 0) {
        html += '<div class="table-responsive"><table class="table table-sm">';
        html += '<thead><tr><th>ID</th><th>Status</th><th>Mensagem</th></tr></thead><tbody>';

        data.detalhes.forEach(d => {
            const statusClass = d.sucesso ? 'text-success' : 'text-danger';
            const statusIcon = d.sucesso ? '<i class="fas fa-check"></i>' : '<i class="fas fa-times"></i>';
            html += `<tr>
                <td>#${d.id}</td>
                <td class="${statusClass}">${statusIcon}</td>
                <td>${d.mensagem || '-'}</td>
            </tr>`;
        });

        html += '</tbody></table></div>';
    }

    content.innerHTML = html;

    const modal = new bootstrap.Modal(document.getElementById('resultadoLoteModal'));
    modal.show();
}

/**
 * Inicializa botões de cancelar
 */
function initBotoesCancelar() {
    document.querySelectorAll('.btn-cancelar').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            confirmarCancelamento(id);
        });
    });
}

/**
 * Confirma e cancela cobrança
 */
function confirmarCancelamento(id) {
    if (!confirm('Tem certeza que deseja cancelar esta cobrança? Esta ação não pode ser desfeita.')) {
        return;
    }

    const btn = document.querySelector(`.btn-cancelar[data-id="${id}"]`);
    const originalHtml = btn.innerHTML;

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

    const url = window.COBRANCA_ROUTES.cancelar.replace('__ID__', id);
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-Token': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('success', data.message || 'Cobrança cancelada com sucesso!');

            // Atualizar linha na tabela ou página
            const row = document.querySelector(`tr[data-id="${id}"]`);
            if (row) {
                // Atualizar badge de status
                const statusCell = row.querySelector('td:nth-child(9)');
                if (statusCell && data.statusClass && data.statusLabel) {
                    statusCell.innerHTML = `<span class="badge ${data.statusClass}">${data.statusLabel}</span>`;
                }

                // Remover checkbox e botões de ação
                const checkCell = row.querySelector('td:first-child');
                if (checkCell) {
                    checkCell.innerHTML = '';
                }

                const actionsCell = row.querySelector('td:last-child .btn-group');
                if (actionsCell) {
                    const viewBtn = actionsCell.querySelector('a.btn-info');
                    actionsCell.innerHTML = viewBtn ? viewBtn.outerHTML : '';
                }
            } else {
                // Estamos na página de detalhes, recarregar
                location.reload();
            }
        } else {
            showToast('danger', data.message || 'Erro ao cancelar cobrança');
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        showToast('danger', 'Erro de comunicação com o servidor');
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    });
}

/**
 * Inicializa funcionalidade de preview
 */
function initPreview() {
    const btnPreview = document.getElementById('btnPreview');
    const btnConfirmarEnvio = document.getElementById('btnConfirmarEnvio');

    if (btnPreview) {
        btnPreview.addEventListener('click', function() {
            const checks = document.querySelectorAll('.cobranca-check');
            const selecionados = getSelecionados(checks);

            if (selecionados.length > 0) {
                carregarPreview(selecionados);
            }
        });
    }

    if (btnConfirmarEnvio) {
        btnConfirmarEnvio.addEventListener('click', function() {
            const modal = bootstrap.Modal.getInstance(document.getElementById('previewModal'));
            modal.hide();

            const checks = document.querySelectorAll('.cobranca-check');
            const selecionados = getSelecionados(checks);

            if (selecionados.length > 0) {
                confirmarEnvioLote(selecionados);
            }
        });
    }
}

/**
 * Carrega preview das cobranças selecionadas
 */
function carregarPreview(ids) {
    const content = document.getElementById('previewContent');
    content.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Carregando...</span>
            </div>
        </div>
    `;

    const modal = new bootstrap.Modal(document.getElementById('previewModal'));
    modal.show();

    const url = window.COBRANCA_ROUTES.preview;
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-Token': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ ids: ids })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            renderPreview(data, content);
        } else {
            content.innerHTML = `
                <div class="alert alert-danger">
                    ${data.message || 'Erro ao carregar preview'}
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        content.innerHTML = `
            <div class="alert alert-danger">
                Erro de comunicação com o servidor
            </div>
        `;
    });
}

/**
 * Renderiza o preview
 */
function renderPreview(data, container) {
    let html = `
        <div class="alert alert-info">
            <strong>${data.quantidade}</strong> cobrança(s) selecionada(s)
            <br>
            <strong>Valor Total: ${data.valor_total_formatado}</strong>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Contrato</th>
                        <th>Locatário</th>
                        <th>Competência</th>
                        <th>Vencimento</th>
                        <th class="text-end">Valor</th>
                    </tr>
                </thead>
                <tbody>
    `;

    data.cobrancas.forEach(c => {
        html += `
            <tr>
                <td>#${c.contrato}</td>
                <td>${c.locatario}</td>
                <td>${c.competencia}</td>
                <td>${c.vencimento}</td>
                <td class="text-end">${c.valor_formatado}</td>
            </tr>
        `;
    });

    html += `
                </tbody>
            </table>
        </div>
    `;

    container.innerHTML = html;
}

/**
 * Mostra toast de notificação
 */
function showToast(type, message) {
    // Criar container se não existir
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
    }

    const toastId = 'toast-' + Date.now();
    const bgClass = type === 'success' ? 'bg-success' :
                    type === 'danger' ? 'bg-danger' :
                    type === 'warning' ? 'bg-warning text-dark' : 'bg-info';

    const toastHtml = `
        <div id="${toastId}" class="toast align-items-center ${bgClass} text-white border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;

    container.insertAdjacentHTML('beforeend', toastHtml);

    const toastEl = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastEl, { delay: 5000 });
    toast.show();

    toastEl.addEventListener('hidden.bs.toast', function() {
        toastEl.remove();
    });
}
