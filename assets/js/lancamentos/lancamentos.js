/**
 * Lancamentos - Modulo utilitarios
 *
 * Funcoes auxiliares para operacoes de lancamentos financeiros
 */

/**
 * Retorna token CSRF da meta tag
 */
export function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : '';
}

/**
 * Retorna headers padrao para requisicoes AJAX
 */
export function getAjaxHeaders() {
    return {
        'X-CSRF-Token': getCsrfToken(),
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json'
    };
}

/**
 * Formata valor para moeda BRL
 */
export function formatarMoeda(valor) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(valor);
}

/**
 * Formata data para exibicao dd/mm/yyyy
 */
export function formatarData(data) {
    if (!data) return '';
    const d = new Date(data);
    return d.toLocaleDateString('pt-BR');
}

/**
 * Realiza baixa de lancamento
 */
export async function baixarLancamento(id, dados) {
    const url = window.ROUTES.baixa.replace('__ID__', id);

    const response = await fetch(url, {
        method: 'POST',
        headers: getAjaxHeaders(),
        body: JSON.stringify(dados)
    });

    return response.json();
}

/**
 * Estorna baixa de lancamento
 */
export async function estornarBaixa(id) {
    const url = window.ROUTES.estornar.replace('__ID__', id);

    const response = await fetch(url, {
        method: 'POST',
        headers: getAjaxHeaders(),
        body: JSON.stringify({})
    });

    return response.json();
}

/**
 * Cancela lancamento
 */
export async function cancelarLancamento(id, motivo) {
    const url = window.ROUTES.cancelar.replace('__ID__', id);

    const response = await fetch(url, {
        method: 'POST',
        headers: getAjaxHeaders(),
        body: JSON.stringify({ motivo })
    });

    return response.json();
}

/**
 * Suspende lancamento
 */
export async function suspenderLancamento(id, motivo) {
    const url = window.ROUTES.suspender.replace('__ID__', id);

    const response = await fetch(url, {
        method: 'POST',
        headers: getAjaxHeaders(),
        body: JSON.stringify({ motivo })
    });

    return response.json();
}

/**
 * Exibe mensagem de sucesso
 */
export function exibirSucesso(mensagem) {
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-success alert-dismissible fade show';
    alertDiv.innerHTML = `
        ${mensagem}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    const container = document.querySelector('.container-fluid');
    if (container) {
        const breadcrumb = container.querySelector('.breadcrumb');
        if (breadcrumb) {
            breadcrumb.after(alertDiv);
        } else {
            container.prepend(alertDiv);
        }
    }

    // Auto-remove apos 5 segundos
    setTimeout(() => alertDiv.remove(), 5000);
}

/**
 * Exibe mensagem de erro
 */
export function exibirErro(mensagem) {
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-danger alert-dismissible fade show';
    alertDiv.innerHTML = `
        ${mensagem}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    const container = document.querySelector('.container-fluid');
    if (container) {
        const breadcrumb = container.querySelector('.breadcrumb');
        if (breadcrumb) {
            breadcrumb.after(alertDiv);
        } else {
            container.prepend(alertDiv);
        }
    }
}

/**
 * Exibe dialogo de confirmacao
 */
export function confirmarAcao(mensagem) {
    return window.confirm(mensagem);
}

/**
 * Calcula valor de retencao
 */
export function calcularRetencao(valor, percentual) {
    if (!valor || !percentual) return 0;
    return (parseFloat(valor) * parseFloat(percentual)) / 100;
}

/**
 * Parse valor monetario para float
 */
export function parseValor(valor) {
    if (typeof valor === 'number') return valor;
    if (!valor) return 0;

    // Remove R$ e espacos
    valor = valor.toString().replace(/[R$\s]/g, '');

    // Formato brasileiro (1.234,56)
    if (/^\d{1,3}(\.\d{3})*,\d{2}$/.test(valor)) {
        valor = valor.replace(/\./g, '').replace(',', '.');
    }
    // Formato simples com virgula (1234,56)
    else if (valor.includes(',') && !valor.includes('.')) {
        valor = valor.replace(',', '.');
    }

    return parseFloat(valor) || 0;
}
