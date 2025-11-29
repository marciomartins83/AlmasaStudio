/**
 * imovel.js - Utilitários e funções básicas do módulo de imóveis
 *
 * 100% MODULAR - SEM CÓDIGO INLINE
 * Token CSRF: ajax_global (único para todo o sistema)
 */

/**
 * Obtém o token CSRF do meta tag
 * @returns {string}
 */
export function getCsrfToken() {
    const metaTag = document.querySelector('meta[name="csrf-token"]');
    return metaTag ? metaTag.content : '';
}

/**
 * Headers padrão para requisições AJAX
 * @returns {Object}
 */
export function getAjaxHeaders() {
    return {
        'X-CSRF-Token': getCsrfToken(),
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json'
    };
}

/**
 * Formata valor monetário para exibição
 * @param {number} valor
 * @returns {string}
 */
export function formatarMoeda(valor) {
    if (!valor) return 'R$ 0,00';

    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(valor);
}

/**
 * Formata área (m²) para exibição
 * @param {number} area
 * @returns {string}
 */
export function formatarArea(area) {
    if (!area) return '0,00 m²';

    return new Intl.NumberFormat('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(area) + ' m²';
}

/**
 * Exibe notificação de sucesso (Bootstrap Toast)
 * @param {string} mensagem
 */
export function exibirSucesso(mensagem) {
    console.log('✅ SUCESSO:', mensagem);

    // Criar toast (Bootstrap 5)
    const toastContainer = document.querySelector('.toast-container') || criarToastContainer();

    const toastHtml = `
        <div class="toast align-items-center text-white bg-success border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi bi-check-circle"></i> ${mensagem}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;

    toastContainer.insertAdjacentHTML('beforeend', toastHtml);
    const toastElement = toastContainer.lastElementChild;
    const toast = new bootstrap.Toast(toastElement);
    toast.show();
}

/**
 * Exibe notificação de erro (Bootstrap Toast)
 * @param {string} mensagem
 */
export function exibirErro(mensagem) {
    console.error('❌ ERRO:', mensagem);

    const toastContainer = document.querySelector('.toast-container') || criarToastContainer();

    const toastHtml = `
        <div class="toast align-items-center text-white bg-danger border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi bi-x-circle"></i> ${mensagem}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;

    toastContainer.insertAdjacentHTML('beforeend', toastHtml);
    const toastElement = toastContainer.lastElementChild;
    const toast = new bootstrap.Toast(toastElement);
    toast.show();
}

/**
 * Cria container de toasts se não existir
 * @returns {HTMLElement}
 */
function criarToastContainer() {
    const container = document.createElement('div');
    container.className = 'toast-container position-fixed top-0 end-0 p-3';
    container.style.zIndex = '9999';
    document.body.appendChild(container);
    return container;
}

/**
 * Executa DELETE via AJAX com confirmação
 * @param {string} url
 * @param {Function} onSuccess
 */
export function executarDelete(url, onSuccess) {
    if (!confirm('Tem certeza que deseja excluir?')) {
        return;
    }

    fetch(url, {
        method: 'DELETE',
        headers: getAjaxHeaders()
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            exibirSucesso('Excluído com sucesso!');
            if (onSuccess) onSuccess();
        } else {
            exibirErro(data.message || 'Erro ao excluir');
        }
    })
    .catch(error => {
        console.error('❌ Erro na requisição DELETE:', error);
        exibirErro('Erro ao excluir');
    });
}

console.log('✅ imovel.js carregado');
