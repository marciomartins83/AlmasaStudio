/**
 * Módulo de Configuração de API Bancária
 *
 * Gerencia as funcionalidades de CRUD para configurações de integração
 * com APIs bancárias (Santander, etc.)
 */

const ConfiguracaoApiBanco = {
    /**
     * Inicializa o módulo
     */
    init: function() {
        this.bindEvents();
        this.initBancoFilter();
    },

    /**
     * Vincula eventos aos elementos da página
     */
    bindEvents: function() {
        document.querySelectorAll('.btn-deletar').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const id = e.currentTarget.dataset.id;
                this.deletarConfiguracao(id);
            });
        });

        document.querySelectorAll('.btn-testar-conexao').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const id = e.currentTarget.dataset.id || window.CONFIG_ID;
                this.testarConexao(id);
            });
        });

        const bancoSelect = document.getElementById('config_banco');
        if (bancoSelect) {
            bancoSelect.addEventListener('change', (e) => {
                this.filtrarContasPorBanco(e.target.value);
            });
        }
    },

    /**
     * Inicializa o filtro de banco se houver valor selecionado
     */
    initBancoFilter: function() {
        const bancoSelect = document.getElementById('config_banco');
        const contaSelect = document.getElementById('config_contaBancaria');

        if (bancoSelect && bancoSelect.value && contaSelect) {
            const contaSelecionada = contaSelect.value;
            this.filtrarContasPorBanco(bancoSelect.value, contaSelecionada);
        }
    },

    /**
     * Filtra as contas bancárias baseado no banco selecionado
     */
    filtrarContasPorBanco: function(bancoId, contaSelecionada = null) {
        const contaSelect = document.getElementById('config_contaBancaria');

        if (!contaSelect) {
            return;
        }

        if (!bancoId) {
            contaSelect.innerHTML = '<option value="">Selecione o banco primeiro...</option>';
            return;
        }

        contaSelect.innerHTML = '<option value="">Carregando...</option>';
        contaSelect.disabled = true;

        const url = window.ROUTES.contasPorBanco.replace('__BANCO_ID__', bancoId);

        fetch(url, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            contaSelect.innerHTML = '<option value="">Selecione a conta...</option>';

            data.forEach(conta => {
                const option = document.createElement('option');
                option.value = conta.id;
                option.textContent = conta.label;

                if (contaSelecionada && conta.id == contaSelecionada) {
                    option.selected = true;
                }

                contaSelect.appendChild(option);
            });

            contaSelect.disabled = false;
        })
        .catch(error => {
            console.error('Erro ao carregar contas:', error);
            contaSelect.innerHTML = '<option value="">Erro ao carregar contas</option>';
            contaSelect.disabled = false;
        });
    },

    /**
     * Testa a conexão com a API bancária
     */
    testarConexao: function(configId) {
        const modal = new bootstrap.Modal(document.getElementById('modalTesteConexao'));
        const modalBody = document.getElementById('modalTesteConexaoBody');

        modalBody.innerHTML = `
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Verificando...</span>
                </div>
                <p class="mt-2">Verificando configuração...</p>
            </div>
        `;

        modal.show();

        const url = window.ROUTES.testarConexao.replace('__ID__', configId);
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
            modalBody.innerHTML = this.renderResultadoTeste(data);
        })
        .catch(error => {
            console.error('Erro ao testar conexão:', error);
            modalBody.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    Erro ao verificar configuração: ${error.message}
                </div>
            `;
        });
    },

    /**
     * Renderiza o resultado do teste de conexão
     */
    renderResultadoTeste: function(data) {
        let html = '';

        if (data.success) {
            html += `<div class="alert alert-success"><i class="fas fa-check-circle"></i> ${data.message}</div>`;
        } else {
            html += `<div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> ${data.message}</div>`;
        }

        if (data.detalhes) {
            html += '<ul class="list-group">';

            for (const [key, value] of Object.entries(data.detalhes)) {
                if (key === 'urls') {
                    html += `
                        <li class="list-group-item">
                            <strong><i class="fas fa-link"></i> URLs:</strong>
                            <br><small class="text-muted">Auth: ${value.autenticacao}</small>
                            <br><small class="text-muted">API: ${value.api}</small>
                        </li>
                    `;
                } else {
                    const statusClass = this.getStatusClass(value.status);
                    const statusIcon = this.getStatusIcon(value.status);

                    html += `
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>${value.mensagem}</span>
                            <span class="badge ${statusClass}">
                                <i class="fas ${statusIcon}"></i>
                            </span>
                        </li>
                    `;
                }
            }

            html += '</ul>';
        }

        return html;
    },

    /**
     * Retorna a classe CSS baseada no status
     */
    getStatusClass: function(status) {
        const classes = {
            'ok': 'bg-success',
            'warning': 'bg-warning',
            'error': 'bg-danger',
            'info': 'bg-info'
        };
        return classes[status] || 'bg-secondary';
    },

    /**
     * Retorna o ícone baseado no status
     */
    getStatusIcon: function(status) {
        const icons = {
            'ok': 'fa-check',
            'warning': 'fa-exclamation',
            'error': 'fa-times',
            'info': 'fa-info'
        };
        return icons[status] || 'fa-question';
    },

    /**
     * Deleta uma configuração via AJAX
     */
    deletarConfiguracao: function(configId) {
        if (!confirm('Tem certeza que deseja excluir esta configuração? Esta ação não pode ser desfeita.')) {
            return;
        }

        const url = window.ROUTES.delete.replace('__ID__', configId);
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        fetch(url, {
            method: 'DELETE',
            headers: {
                'X-CSRF-Token': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const row = document.querySelector(`tr[data-id="${configId}"]`);
                if (row) {
                    row.remove();
                }

                this.showToast('success', data.message);

                const tbody = document.querySelector('table tbody');
                if (tbody && tbody.children.length === 0) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="fas fa-info-circle fa-2x mb-2 d-block"></i>
                                <span>Nenhuma configuração de API cadastrada</span><br>
                                <small>Clique em "Nova Configuração" para começar</small>
                            </td>
                        </tr>
                    `;
                }
            } else {
                this.showToast('error', data.message || 'Erro ao excluir configuração');
            }
        })
        .catch(error => {
            console.error('Erro ao deletar:', error);
            this.showToast('error', 'Erro ao excluir configuração');
        });
    },

    /**
     * Valida o formulário antes de enviar
     */
    validarFormulario: function() {
        const form = document.querySelector('form');
        if (!form) return true;

        const banco = document.getElementById('config_banco');
        const conta = document.getElementById('config_contaBancaria');
        const convenio = document.querySelector('[name*="convenio"]');

        let valido = true;

        if (banco && !banco.value) {
            this.showError(banco, 'Selecione um banco');
            valido = false;
        }

        if (conta && !conta.value) {
            this.showError(conta, 'Selecione uma conta bancária');
            valido = false;
        }

        if (convenio && !convenio.value.trim()) {
            this.showError(convenio, 'Informe o convênio');
            valido = false;
        }

        return valido;
    },

    /**
     * Mostra erro em um campo
     */
    showError: function(element, message) {
        element.classList.add('is-invalid');

        let feedback = element.nextElementSibling;
        if (!feedback || !feedback.classList.contains('invalid-feedback')) {
            feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            element.parentNode.insertBefore(feedback, element.nextSibling);
        }
        feedback.textContent = message;
    },

    /**
     * Exibe uma mensagem toast
     */
    showToast: function(type, message) {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';

        const alertHtml = `
            <div class="alert ${alertClass} alert-dismissible fade show position-fixed"
                 style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                <i class="fas ${icon}"></i> ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;

        const container = document.createElement('div');
        container.innerHTML = alertHtml;
        document.body.appendChild(container.firstElementChild);

        setTimeout(() => {
            const alert = document.querySelector('.alert.position-fixed');
            if (alert) {
                alert.remove();
            }
        }, 5000);
    }
};

document.addEventListener('DOMContentLoaded', function() {
    ConfiguracaoApiBanco.init();
});

export default ConfiguracaoApiBanco;
