/**
 * Ficha Financeira - Módulo de Formulário
 * Gerencia o formulário de criação/edição de lançamentos
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
        input.value = '0,00';
        return;
    }
    valor = (parseInt(valor) / 100).toFixed(2);
    input.value = valor.replace('.', ',');
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
 * Calcula e atualiza o total do lançamento
 */
function calcularTotal() {
    const campos = [
        'valorPrincipal',
        'valorCondominio',
        'valorIptu',
        'valorAgua',
        'valorLuz',
        'valorGas',
        'valorOutros'
    ];

    let total = 0;
    campos.forEach(campo => {
        const input = document.querySelector(`[name="${campo}"]`);
        if (input) {
            total += parseNumero(input.value);
        }
    });

    // Subtrair desconto
    const desconto = document.querySelector('[name="valorDesconto"]');
    if (desconto) {
        total -= parseNumero(desconto.value);
    }

    // Atualizar display do total (se existir)
    const totalDisplay = document.getElementById('totalLancamento');
    if (totalDisplay) {
        totalDisplay.textContent = total.toLocaleString('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        });
    }

    return total;
}

/**
 * Inicializa máscaras de valor monetário
 */
function initMascarasMonetarias() {
    const camposMonetarios = document.querySelectorAll('.valor-monetario');

    camposMonetarios.forEach(input => {
        // Formatar valor inicial se existir
        if (input.value && input.value !== '0.00') {
            const valor = parseFloat(input.value);
            if (!isNaN(valor)) {
                input.value = valor.toFixed(2).replace('.', ',');
            }
        } else {
            input.value = '0,00';
        }

        // Evento de input
        input.addEventListener('input', function() {
            formatarCampoMonetario(this);
            calcularTotal();
        });

        // Evento de focus - selecionar tudo
        input.addEventListener('focus', function() {
            this.select();
        });
    });
}

/**
 * Inicializa o formulário de lançamento
 */
function initFormulario() {
    const form = document.getElementById('formLancamento');
    if (!form) return;

    // Inicializar máscaras
    initMascarasMonetarias();

    // Calcular total inicial
    calcularTotal();

    // Submissão do formulário
    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        const submitBtn = form.querySelector('[type="submit"]');
        setButtonLoading(submitBtn, true);

        // Coletar dados do formulário
        const formData = new FormData(form);
        const dados = {};

        // Converter FormData para objeto
        formData.forEach((value, key) => {
            if (key === '_token') return; // Token vai no header

            // Campos monetários - converter para float
            if (key.startsWith('valor')) {
                dados[key] = parseNumero(value);
            } else {
                dados[key] = value;
            }
        });

        try {
            const response = await fetch(form.action || window.location.href, {
                method: 'POST',
                headers: getAjaxHeaders(),
                body: JSON.stringify(dados)
            });

            const data = await response.json();

            if (data.success) {
                exibirToast(data.message || 'Lançamento salvo com sucesso!', 'success');
                // Redirecionar após sucesso
                setTimeout(() => {
                    window.location.href = '/financeiro';
                }, 1000);
            } else {
                exibirToast(data.message || 'Erro ao salvar lançamento', 'danger');
            }
        } catch (error) {
            console.error('Erro:', error);
            exibirToast('Erro ao processar requisição', 'danger');
        } finally {
            setButtonLoading(submitBtn, false);
        }
    });
}

/**
 * Atualiza descrição automática baseada no tipo e competência
 */
function initDescricaoAutomatica() {
    const tipoSelect = document.querySelector('[name="tipoLancamento"]');
    const competenciaInput = document.querySelector('[name="competencia"]');
    const descricaoInput = document.querySelector('[name="descricao"]');

    if (!tipoSelect || !competenciaInput || !descricaoInput) return;

    function atualizarDescricao() {
        // Só atualiza se descrição estiver vazia ou for automática
        const descricaoAtual = descricaoInput.value;
        const isAutomatica = descricaoAtual === '' ||
            descricaoAtual.match(/^(Aluguel|Condomínio|IPTU|Água|Luz|Gás|Outros) de \d{2}\/\d{4}$/);

        if (!isAutomatica) return;

        const tipo = tipoSelect.value;
        const competencia = competenciaInput.value;

        if (!tipo || !competencia) return;

        const tipoLabel = {
            'aluguel': 'Aluguel',
            'condominio': 'Condomínio',
            'iptu': 'IPTU',
            'agua': 'Água',
            'luz': 'Luz',
            'gas': 'Gás',
            'outros': 'Outros'
        }[tipo] || tipo;

        const [ano, mes] = competencia.split('-');
        descricaoInput.value = `${tipoLabel} de ${mes}/${ano}`;
    }

    tipoSelect.addEventListener('change', atualizarDescricao);
    competenciaInput.addEventListener('change', atualizarDescricao);

    // Executar uma vez ao carregar
    atualizarDescricao();
}

// Inicialização quando DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    console.log('Inicializando módulo Formulário Financeiro');

    initFormulario();
    initDescricaoAutomatica();

    console.log('Módulo Formulário Financeiro inicializado');
});
