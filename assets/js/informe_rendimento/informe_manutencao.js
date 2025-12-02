/**
 * Informe de Rendimentos - Módulo de Manutenção
 * Gerencia a aba de manutenção/edição de informes
 */

import {
    getAjaxHeaders,
    formatarMoeda,
    formatarNumero,
    parseNumero,
    exibirSucesso,
    exibirErro,
    setButtonLoading
} from './informe_rendimento.js';

let modalEditar = null;

/**
 * Inicializa o módulo de manutenção
 */
export function initManutencao() {
    const btnFiltrar = document.getElementById('btn-filtrar-manutencao');

    if (!btnFiltrar) {
        console.warn('Botão filtrar manutenção não encontrado');
        return;
    }

    btnFiltrar.addEventListener('click', filtrarManutencao);

    // Inicializar modal de edição
    const modalEl = document.getElementById('modalEditarInforme');
    if (modalEl) {
        modalEditar = new bootstrap.Modal(modalEl);

        // Listener para calcular total ao digitar
        modalEl.querySelectorAll('.valor-mensal').forEach(input => {
            input.addEventListener('input', calcularTotalAnual);
            input.addEventListener('blur', formatarCampoValor);
        });

        // Listener para salvar
        document.getElementById('btn-salvar-informe').addEventListener('click', salvarInforme);
    }

    // Permitir filtrar ao pressionar Enter nos campos
    ['manut-imovel', 'manut-inquilino'].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    filtrarManutencao();
                }
            });
        }
    });

    console.log('Módulo Manutenção inicializado');
}

/**
 * Filtra e carrega os informes
 */
async function filtrarManutencao() {
    const ano = document.getElementById('manut-ano').value;
    const proprietario = document.getElementById('manut-proprietario').value;
    const imovel = document.getElementById('manut-imovel').value;
    const inquilino = document.getElementById('manut-inquilino').value;

    // Construir query string
    const params = new URLSearchParams();
    if (ano) params.append('ano', ano);
    if (proprietario) params.append('proprietario', proprietario);
    if (imovel) params.append('imovel', imovel);
    if (inquilino) params.append('inquilino', inquilino);

    // Mostrar loading
    document.getElementById('manutencao-loading').style.display = 'block';
    document.getElementById('manutencao-tabela-container').style.display = 'none';
    document.getElementById('manutencao-vazio').style.display = 'none';

    try {
        const response = await fetch(`${window.ROUTES.manutencao}?${params.toString()}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        const data = await response.json();

        if (data.success) {
            renderizarTabela(data.informes || []);
        } else {
            exibirErro(data.message || 'Erro ao buscar informes');
        }

    } catch (error) {
        console.error('Erro ao filtrar:', error);
        exibirErro('Erro de comunicação com o servidor');
    } finally {
        document.getElementById('manutencao-loading').style.display = 'none';
    }
}

/**
 * Renderiza a tabela de informes
 */
function renderizarTabela(informes) {
    const tbody = document.getElementById('tbody-manutencao');
    const tabelaContainer = document.getElementById('manutencao-tabela-container');
    const divVazio = document.getElementById('manutencao-vazio');

    if (!informes.length) {
        tbody.innerHTML = '';
        tabelaContainer.style.display = 'none';
        divVazio.style.display = 'block';
        return;
    }

    divVazio.style.display = 'none';
    tabelaContainer.style.display = 'block';

    tbody.innerHTML = informes.map(informe => {
        const valores = informe.valores || {};
        let total = 0;

        let celulas = '';
        for (let mes = 1; mes <= 12; mes++) {
            const valor = parseFloat(valores[mes]) || 0;
            total += valor;
            celulas += `<td class="text-end">${formatarNumero(valor)}</td>`;
        }

        return `
            <tr data-id="${informe.id}" data-informe='${JSON.stringify(informe)}'>
                <td title="${informe.proprietarioNome}">${truncar(informe.proprietarioNome, 20)}</td>
                <td>${informe.imovelCodigo || '-'}</td>
                <td title="${informe.inquilinoNome}">${truncar(informe.inquilinoNome, 20)}</td>
                <td title="${informe.contaCodigo} - ${informe.contaDescricao}">${informe.contaCodigo}</td>
                ${celulas}
                <td class="text-end fw-bold">${formatarNumero(total)}</td>
                <td class="text-center">
                    <button class="btn btn-sm btn-outline-primary btn-editar" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                </td>
            </tr>
        `;
    }).join('');

    // Adicionar listeners de edição
    tbody.querySelectorAll('.btn-editar').forEach(btn => {
        btn.addEventListener('click', abrirModalEdicao);
    });
}

/**
 * Trunca texto com reticências
 */
function truncar(texto, tamanho) {
    if (!texto) return '';
    return texto.length > tamanho ? texto.substring(0, tamanho) + '...' : texto;
}

/**
 * Abre modal de edição do informe
 */
function abrirModalEdicao(event) {
    const tr = event.target.closest('tr');
    const informe = JSON.parse(tr.dataset.informe);

    // Preencher dados do modal
    document.getElementById('edit-informe-id').value = informe.id;
    document.getElementById('edit-proprietario').textContent = informe.proprietarioNome;
    document.getElementById('edit-imovel').textContent = informe.imovelCodigo || '-';
    document.getElementById('edit-inquilino').textContent = informe.inquilinoNome;
    document.getElementById('edit-conta').textContent = `${informe.contaCodigo} - ${informe.contaDescricao}`;

    // Preencher valores mensais
    for (let mes = 1; mes <= 12; mes++) {
        const input = document.getElementById(`edit-valor-${mes}`);
        const valor = parseFloat(informe.valores[mes]) || 0;
        input.value = formatarNumero(valor);
    }

    // Calcular total
    calcularTotalAnual();

    // Abrir modal
    modalEditar.show();
}

/**
 * Calcula e exibe o total anual
 */
function calcularTotalAnual() {
    let total = 0;

    for (let mes = 1; mes <= 12; mes++) {
        const input = document.getElementById(`edit-valor-${mes}`);
        total += parseNumero(input.value);
    }

    document.getElementById('edit-total-anual').textContent = formatarMoeda(total);
}

/**
 * Formata campo de valor ao sair
 */
function formatarCampoValor(event) {
    const input = event.target;
    const valor = parseNumero(input.value);
    input.value = formatarNumero(valor);
}

/**
 * Salva alterações do informe
 */
async function salvarInforme() {
    const id = document.getElementById('edit-informe-id').value;

    if (!id) {
        exibirErro('ID do informe não encontrado');
        return;
    }

    // Coletar valores mensais
    const valores = {};
    for (let mes = 1; mes <= 12; mes++) {
        const input = document.getElementById(`edit-valor-${mes}`);
        valores[mes] = parseNumero(input.value);
    }

    const btnSalvar = document.getElementById('btn-salvar-informe');
    setButtonLoading(btnSalvar, true);

    try {
        const url = window.ROUTES.atualizarInforme.replace('__ID__', id);

        const response = await fetch(url, {
            method: 'PUT',
            headers: getAjaxHeaders(),
            body: JSON.stringify({ valores })
        });

        const data = await response.json();

        if (data.success) {
            exibirSucesso('Informe atualizado com sucesso!');
            modalEditar.hide();

            // Recarregar tabela
            filtrarManutencao();
        } else {
            exibirErro(data.message || 'Erro ao salvar informe');
        }

    } catch (error) {
        console.error('Erro ao salvar:', error);
        exibirErro('Erro de comunicação com o servidor');
    } finally {
        setButtonLoading(btnSalvar, false, '<i class="fas fa-save me-1"></i>Salvar Alterações');
    }
}
