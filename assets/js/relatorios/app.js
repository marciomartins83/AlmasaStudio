/**
 * Entry point para o modulo de Relatorios
 *
 * Importa e inicializa todos os modulos necessarios
 */

'use strict';

// Importa CSS (se houver)
// import '../../styles/relatorios.css';

// Importa o modulo principal
import './relatorios.js';

// Funcoes utilitarias especificas de relatorios

/**
 * Formata valor monetario para exibicao
 * @param {number} valor - Valor a formatar
 * @returns {string} Valor formatado
 */
window.formatarMoeda = function(valor) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(valor || 0);
};

/**
 * Formata data para exibicao
 * @param {string|Date} data - Data a formatar
 * @returns {string} Data formatada
 */
window.formatarData = function(data) {
    if (!data) return '-';
    const d = new Date(data);
    return d.toLocaleDateString('pt-BR');
};

/**
 * Define datas padrao no formulario
 * @param {string} inicio - ID do campo de data inicio
 * @param {string} fim - ID do campo de data fim
 */
window.definirPeriodoPadrao = function(inicio = 'data_inicio', fim = 'data_fim') {
    const hoje = new Date();
    const primeiroDia = new Date(hoje.getFullYear(), hoje.getMonth(), 1);

    const campoInicio = document.getElementById(inicio);
    const campoFim = document.getElementById(fim);

    if (campoInicio && !campoInicio.value) {
        campoInicio.value = primeiroDia.toISOString().split('T')[0];
    }

    if (campoFim && !campoFim.value) {
        campoFim.value = hoje.toISOString().split('T')[0];
    }
};

/**
 * Limpa todos os campos do formulario
 */
window.limparFiltros = function() {
    const form = document.getElementById('filtros-form');
    if (form) {
        form.reset();

        // Limpa preview
        const preview = document.getElementById('preview-container');
        if (preview) {
            preview.innerHTML = `
                <div class="text-center text-muted py-5">
                    <i class="fas fa-search fa-3x mb-3"></i>
                    <p>Configure os filtros e clique em "Visualizar" para ver o relatorio.</p>
                </div>
            `;
        }
    }
};

// Inicializacao quando DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    // Define periodo padrao se os campos existirem
    definirPeriodoPadrao();

    // Adiciona evento ao botao limpar (se existir)
    const btnLimpar = document.getElementById('btn-limpar');
    if (btnLimpar) {
        btnLimpar.addEventListener('click', (e) => {
            e.preventDefault();
            limparFiltros();
        });
    }

    console.log('✅ Modulo de Relatorios carregado');
});
