/**
 * Informe de Rendimentos - Módulo Principal
 * Inicializa todos os submódulos e exporta utilitários compartilhados
 */

import { initProcessamento } from './informe_processamento.js';
import { initManutencao } from './informe_manutencao.js';
import { initImpressao } from './informe_impressao.js';
import { initDimob } from './informe_dimob.js';

/**
 * Retorna o token CSRF da meta tag
 */
export function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : '';
}

/**
 * Retorna headers padrão para requisições AJAX
 */
export function getAjaxHeaders() {
    return {
        'X-CSRF-Token': getCsrfToken(),
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json'
    };
}

/**
 * Formata valor como moeda brasileira
 */
export function formatarMoeda(valor) {
    const numero = parseFloat(valor) || 0;
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(numero);
}

/**
 * Formata valor como número com 2 casas decimais
 */
export function formatarNumero(valor) {
    const numero = parseFloat(valor) || 0;
    return numero.toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

/**
 * Converte string formatada em número
 */
export function parseNumero(valorFormatado) {
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
 * Exibe mensagem de sucesso (Toast Bootstrap)
 */
export function exibirSucesso(mensagem) {
    exibirToast(mensagem, 'success');
}

/**
 * Exibe mensagem de erro (Toast Bootstrap)
 */
export function exibirErro(mensagem) {
    exibirToast(mensagem, 'danger');
}

/**
 * Exibe mensagem de aviso (Toast Bootstrap)
 */
export function exibirAviso(mensagem) {
    exibirToast(mensagem, 'warning');
}

/**
 * Exibe toast Bootstrap
 */
function exibirToast(mensagem, tipo = 'info') {
    // Verificar se existe container de toasts
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        container.style.zIndex = '1100';
        document.body.appendChild(container);
    }

    // Criar toast
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

    // Remover do DOM após esconder
    toastEl.addEventListener('hidden.bs.toast', () => {
        toastEl.remove();
    });
}

/**
 * Define estado de loading em um botão
 */
export function setButtonLoading(button, loading, textoOriginal = null) {
    if (loading) {
        button.disabled = true;
        button.dataset.originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Aguarde...';
    } else {
        button.disabled = false;
        button.innerHTML = textoOriginal || button.dataset.originalText || button.innerHTML;
    }
}

// Inicialização quando DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    console.log('Inicializando módulo Informe de Rendimentos');

    // Inicializar cada submódulo
    initProcessamento();
    initManutencao();
    initImpressao();
    initDimob();

    console.log('Módulo Informe de Rendimentos inicializado');
});
