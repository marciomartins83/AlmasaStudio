/**
 * Prestacao de Contas - Utilitarios
 */

/**
 * Retorna token CSRF
 */
export function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : '';
}

/**
 * Retorna headers para requisicoes AJAX
 */
export function getAjaxHeaders() {
    return {
        'X-CSRF-Token': getCsrfToken(),
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json'
    };
}

/**
 * Formata valor monetario
 */
export function formatarMoeda(valor) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(valor);
}

/**
 * Formata data
 */
export function formatarData(data) {
    if (!data) return '-';
    const d = new Date(data);
    return d.toLocaleDateString('pt-BR');
}

/**
 * Carregar imoveis do proprietario via AJAX
 */
export async function carregarImoveisPorProprietario(idProprietario) {
    if (!idProprietario) {
        return [];
    }

    const url = window.ROUTES.imoveis.replace('__ID__', idProprietario);

    try {
        const response = await fetch(url, {
            method: 'GET',
            headers: getAjaxHeaders()
        });

        const data = await response.json();

        if (data.success) {
            return data.imoveis;
        } else {
            console.error('Erro ao carregar imoveis:', data.message);
            return [];
        }
    } catch (error) {
        console.error('Erro ao carregar imoveis:', error);
        return [];
    }
}

/**
 * Calcular periodo automaticamente
 */
export async function calcularPeriodo(tipoPeriodo, dataBase = null) {
    try {
        const response = await fetch(window.ROUTES.calcularPeriodo, {
            method: 'POST',
            headers: getAjaxHeaders(),
            body: JSON.stringify({
                tipoPeriodo: tipoPeriodo,
                dataBase: dataBase
            })
        });

        const data = await response.json();

        if (data.success) {
            return {
                dataInicio: data.dataInicio,
                dataFim: data.dataFim
            };
        } else {
            console.error('Erro ao calcular periodo:', data.message);
            return null;
        }
    } catch (error) {
        console.error('Erro ao calcular periodo:', error);
        return null;
    }
}

/**
 * Gerar preview da prestacao
 */
export async function gerarPreview(filtros) {
    try {
        const response = await fetch(window.ROUTES.preview, {
            method: 'POST',
            headers: getAjaxHeaders(),
            body: JSON.stringify(filtros)
        });

        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Erro ao gerar preview:', error);
        return { success: false, message: error.message };
    }
}

/**
 * Aprovar prestacao
 */
export async function aprovarPrestacao(id) {
    const url = window.ROUTES.aprovar.replace('__ID__', id);

    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: getAjaxHeaders()
        });

        return await response.json();
    } catch (error) {
        console.error('Erro ao aprovar prestacao:', error);
        return { success: false, message: error.message };
    }
}

/**
 * Cancelar prestacao
 */
export async function cancelarPrestacao(id, motivo) {
    const url = window.ROUTES.cancelar.replace('__ID__', id);

    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: getAjaxHeaders(),
            body: JSON.stringify({ motivo: motivo })
        });

        return await response.json();
    } catch (error) {
        console.error('Erro ao cancelar prestacao:', error);
        return { success: false, message: error.message };
    }
}

/**
 * Excluir prestacao
 */
export async function excluirPrestacao(id) {
    const url = window.ROUTES.excluir.replace('__ID__', id);

    try {
        const response = await fetch(url, {
            method: 'DELETE',
            headers: getAjaxHeaders()
        });

        return await response.json();
    } catch (error) {
        console.error('Erro ao excluir prestacao:', error);
        return { success: false, message: error.message };
    }
}

/**
 * Exibir preview na tela
 */
export function exibirPreview(dados) {
    const container = document.getElementById('previewContent');
    const card = document.getElementById('cardPreview');
    const instrucoes = document.getElementById('cardInstrucoes');

    if (!container) return;

    const resumo = dados.resumo;

    let html = `
        <h6 class="text-muted mb-3">Resumo da Prestacao</h6>
        <table class="table table-sm">
            <tr>
                <td>Quantidade de Itens</td>
                <td class="text-end">${resumo.quantidade_itens}</td>
            </tr>
            <tr>
                <td>Total Receitas</td>
                <td class="text-end">${formatarMoeda(resumo.total_receitas)}</td>
            </tr>
            <tr class="text-danger">
                <td>(-) Taxa Administracao</td>
                <td class="text-end">(${formatarMoeda(resumo.total_taxa_admin)})</td>
            </tr>
            <tr class="text-danger">
                <td>(-) Retencao IR</td>
                <td class="text-end">(${formatarMoeda(resumo.total_retencao_ir)})</td>
            </tr>
            <tr class="text-danger">
                <td>(-) Total Despesas</td>
                <td class="text-end">(${formatarMoeda(resumo.total_despesas)})</td>
            </tr>
            <tr class="table-primary fw-bold">
                <td>Valor de Repasse</td>
                <td class="text-end">${formatarMoeda(resumo.valor_repasse)}</td>
            </tr>
        </table>
    `;

    if (resumo.quantidade_itens === 0) {
        html += `
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                Nenhum item encontrado para o periodo selecionado.
            </div>
        `;
    }

    container.innerHTML = html;
    card.style.display = 'block';
    if (instrucoes) instrucoes.style.display = 'none';
}

/**
 * Exibir mensagem de sucesso
 */
export function exibirSucesso(mensagem) {
    alert(mensagem);
}

/**
 * Exibir mensagem de erro
 */
export function exibirErro(mensagem) {
    alert('Erro: ' + mensagem);
}

/**
 * Confirmar acao
 */
export function confirmarAcao(mensagem) {
    return confirm(mensagem);
}
