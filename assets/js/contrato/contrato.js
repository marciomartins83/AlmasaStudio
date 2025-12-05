/**
 * Contratos - Index Page
 * Gerencia filtros, estatísticas e listagem de contratos
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ contrato.js carregado');

    // Inicializar componentes
    inicializarFiltros();
    inicializarAcoesTabela();
    carregarEstatisticas();
});

/**
 * Inicializar filtros do painel
 */
function inicializarFiltros() {
    const form = document.querySelector('#filtrosCollapse form');
    if (!form) return;

    // Limpar filtros ao clicar no botão
    const btnLimpar = form.querySelector('a[href*="app_contrato_index"]');
    if (btnLimpar) {
        btnLimpar.addEventListener('click', function(e) {
            e.preventDefault();
            form.reset();
            form.submit();
        });
    }
}

/**
 * Inicializar ações da tabela
 */
function inicializarAcoesTabela() {
    // Adicionar confirmação para ações críticas se necessário
    const botoesAcao = document.querySelectorAll('[data-action]');

    botoesAcao.forEach(botao => {
        botao.addEventListener('click', function(e) {
            const action = this.dataset.action;

            if (action === 'encerrar' || action === 'renovar') {
                const confirmMsg = action === 'encerrar'
                    ? 'Tem certeza que deseja encerrar este contrato?'
                    : 'Deseja renovar este contrato?';

                if (!confirm(confirmMsg)) {
                    e.preventDefault();
                }
            }
        });
    });
}

/**
 * Carregar estatísticas atualizadas via AJAX
 */
async function carregarEstatisticas() {
    if (!window.ROUTES || !window.ROUTES.estatisticas) {
        console.warn('⚠️ Rota de estatísticas não definida');
        return;
    }

    try {
        const response = await fetch(window.ROUTES.estatisticas, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json'
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const data = await response.json();

        if (data.success && data.estatisticas) {
            atualizarCardsEstatisticas(data.estatisticas);
        }
    } catch (error) {
        console.error('❌ Erro ao carregar estatísticas:', error);
    }
}

/**
 * Atualizar cards de estatísticas na interface
 */
function atualizarCardsEstatisticas(estatisticas) {
    // Atualizar total
    const totalCard = document.querySelector('.card.bg-primary h2');
    if (totalCard) {
        totalCard.textContent = estatisticas.total || 0;
    }

    // Atualizar ativos
    const ativosCard = document.querySelector('.card.bg-success h2');
    if (ativosCard) {
        ativosCard.textContent = estatisticas.ativos || 0;
    }

    // Atualizar encerrados
    const encerradosCard = document.querySelector('.card.bg-info h2');
    if (encerradosCard) {
        encerradosCard.textContent = estatisticas.encerrados || 0;
    }

    // Atualizar valor total
    const valorCard = document.querySelector('.card.bg-warning h2');
    if (valorCard) {
        const valorFormatado = formatarMoeda(estatisticas.valor_total_ativos || 0);
        valorCard.textContent = valorFormatado;
    }
}

/**
 * Formatar valor como moeda brasileira
 */
function formatarMoeda(valor) {
    return 'R$ ' + parseFloat(valor).toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

/**
 * Buscar contratos próximos ao vencimento
 */
async function buscarContratosVencimento(dias = 30) {
    if (!window.ROUTES || !window.ROUTES.vencimentoProximo) {
        console.warn('⚠️ Rota vencimentoProximo não definida');
        return;
    }

    try {
        const response = await fetch(`${window.ROUTES.vencimentoProximo}?dias=${dias}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json'
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const data = await response.json();

        if (data.success) {
            console.log('📋 Contratos próximos ao vencimento:', data.contratos);
            return data.contratos;
        }
    } catch (error) {
        console.error('❌ Erro ao buscar contratos:', error);
        return [];
    }
}

/**
 * Buscar contratos que precisam de reajuste
 */
async function buscarContratosReajuste() {
    if (!window.ROUTES || !window.ROUTES.paraReajuste) {
        console.warn('⚠️ Rota paraReajuste não definida');
        return;
    }

    try {
        const response = await fetch(window.ROUTES.paraReajuste, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json'
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const data = await response.json();

        if (data.success) {
            console.log('📈 Contratos para reajuste:', data.contratos);
            return data.contratos;
        }
    } catch (error) {
        console.error('❌ Erro ao buscar contratos:', error);
        return [];
    }
}

// Exportar funções para uso global
window.contratoIndex = {
    buscarContratosVencimento,
    buscarContratosReajuste,
    carregarEstatisticas
};
