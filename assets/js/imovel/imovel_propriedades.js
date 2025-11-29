/**
 * imovel_propriedades.js - Gerenciamento de propriedades do imóvel
 *
 * Responsabilidades:
 * - Carregar catálogo de propriedades (piscina, churrasqueira, etc.)
 * - Renderizar checkboxes organizados por categoria
 * - Gerenciar seleção/deseleção
 * - DELETE de propriedades via AJAX
 *
 * 100% MODULAR - SEM CÓDIGO INLINE
 */

import { getAjaxHeaders, executarDelete, exibirSucesso, exibirErro } from './imovel.js';

let propriedadesCatalogo = [];
let propriedadesSelecionadas = [];
let imovelId = null;

/**
 * Inicializa o módulo de propriedades
 */
export function init() {
    if (!window.ROUTES || !window.ROUTES.propriedadesCatalogo) {
        console.warn('⚠️ ROUTES não definidas para propriedades');
        return;
    }

    if (window.IMOVEL_DATA) {
        imovelId = window.IMOVEL_DATA.id;
        propriedadesSelecionadas = window.IMOVEL_DATA.propriedades || [];
    }

    carregarCatalogo();
}

/**
 * Carrega catálogo de propriedades do backend
 */
function carregarCatalogo() {
    console.log('📥 Carregando catálogo de propriedades...');

    fetch(window.ROUTES.propriedadesCatalogo, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        console.log('✅ Catálogo carregado:', data);
        propriedadesCatalogo = data;
        renderizarPropriedades();
    })
    .catch(error => {
        console.error('❌ Erro ao carregar catálogo:', error);
        exibirErro('Erro ao carregar propriedades');
    });
}

/**
 * Renderiza checkboxes de propriedades organizados por categoria
 */
function renderizarPropriedades() {
    const container = document.getElementById('propriedades-container');

    if (!container) {
        return;
    }

    // Agrupar por categoria
    const categorias = agruparPorCategoria(propriedadesCatalogo);

    let html = '';

    // Renderizar cada categoria
    Object.keys(categorias).forEach(categoria => {
        const propriedades = categorias[categoria];

        html += `
            <div class="col-md-12 mt-3">
                <h6 class="text-muted text-capitalize">
                    <i class="bi bi-tag"></i> ${formatarCategoria(categoria)}
                </h6>
            </div>
        `;

        propriedades.forEach(prop => {
            const checked = propriedadeEstaSelecionada(prop.id) ? 'checked' : '';

            html += `
                <div class="col-md-3">
                    <div class="form-check">
                        <input class="form-check-input propriedade-checkbox"
                               type="checkbox"
                               id="prop_${prop.id}"
                               value="${prop.id}"
                               data-nome="${prop.nome}"
                               ${checked}>
                        <label class="form-check-label" for="prop_${prop.id}">
                            ${prop.nome.replace(/_/g, ' ')}
                        </label>
                    </div>
                </div>
            `;
        });
    });

    container.innerHTML = html;

    // Adicionar event listeners aos checkboxes
    container.querySelectorAll('.propriedade-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', handleCheckboxChange);
    });

    console.log('✅ Propriedades renderizadas');
}

/**
 * Agrupa propriedades por categoria
 * @param {Array} propriedades
 * @returns {Object}
 */
function agruparPorCategoria(propriedades) {
    const grupos = {};

    propriedades.forEach(prop => {
        const categoria = prop.categoria || 'outros';

        if (!grupos[categoria]) {
            grupos[categoria] = [];
        }

        grupos[categoria].push(prop);
    });

    return grupos;
}

/**
 * Formata nome da categoria para exibição
 * @param {string} categoria
 * @returns {string}
 */
function formatarCategoria(categoria) {
    const nomes = {
        'lazer': 'Lazer',
        'seguranca': 'Segurança',
        'infraestrutura': 'Infraestrutura',
        'area_externa': 'Área Externa',
        'comodos': 'Cômodos',
        'outros': 'Outros'
    };

    return nomes[categoria] || categoria;
}

/**
 * Verifica se propriedade está selecionada
 * @param {number} propriedadeId
 * @returns {boolean}
 */
function propriedadeEstaSelecionada(propriedadeId) {
    return propriedadesSelecionadas.some(p => p.id === propriedadeId);
}

/**
 * Handler para mudança de checkbox
 * @param {Event} event
 */
function handleCheckboxChange(event) {
    const checkbox = event.target;
    const propriedadeId = parseInt(checkbox.value);
    const propriedadeNome = checkbox.dataset.nome;

    if (checkbox.checked) {
        // Adicionar propriedade
        adicionarPropriedade(propriedadeId, propriedadeNome);
    } else {
        // Remover propriedade
        removerPropriedade(propriedadeId);
    }
}

/**
 * Adiciona propriedade ao imóvel
 * @param {number} propriedadeId
 * @param {string} propriedadeNome
 */
function adicionarPropriedade(propriedadeId, propriedadeNome) {
    console.log(`✅ Adicionando propriedade: ${propriedadeNome}`);

    // Adicionar à lista local
    propriedadesSelecionadas.push({
        id: propriedadeId,
        nome: propriedadeNome
    });

    // Se estiver editando (tem imovelId), salvar via AJAX
    if (imovelId) {
        // TODO: Implementar endpoint de salvar propriedade
        console.log('💾 Salvaria propriedade via AJAX');
    }
}

/**
 * Remove propriedade do imóvel
 * @param {number} propriedadeId
 */
function removerPropriedade(propriedadeId) {
    console.log(`🗑️ Removendo propriedade ID: ${propriedadeId}`);

    // Remover da lista local
    propriedadesSelecionadas = propriedadesSelecionadas.filter(p => p.id !== propriedadeId);

    // Se estiver editando, executar DELETE via AJAX
    if (imovelId && window.ROUTES.deletePropriedade) {
        const url = window.ROUTES.deletePropriedade
            .replace('__IMOVEL_ID__', imovelId)
            .replace('__PROP_ID__', propriedadeId);

        executarDelete(url, () => {
            console.log('✅ Propriedade removida do backend');
        });
    }
}

/**
 * Obtém IDs das propriedades selecionadas
 * @returns {Array}
 */
export function getPropriedadesSelecionadas() {
    return propriedadesSelecionadas.map(p => p.id);
}

console.log('✅ imovel_propriedades.js carregado');
