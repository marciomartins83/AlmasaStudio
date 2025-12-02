/**
 * Informe de Rendimentos - Módulo de Impressão
 * Gerencia a aba de geração de relatórios/PDFs
 */

import { exibirErro, exibirAviso } from './informe_rendimento.js';

/**
 * Inicializa o módulo de impressão
 */
export function initImpressao() {
    const btnImprimir = document.getElementById('btn-imprimir');

    if (!btnImprimir) {
        console.warn('Botão imprimir não encontrado');
        return;
    }

    btnImprimir.addEventListener('click', gerarImpressao);

    console.log('Módulo Impressão inicializado');
}

/**
 * Gera a impressão/PDF do informe
 */
function gerarImpressao() {
    const ano = document.getElementById('imp-ano').value;
    const proprietario = document.getElementById('imp-proprietario').value;
    const modelo = document.getElementById('imp-modelo').value;
    const abaterTaxa = document.getElementById('imp-abater-taxa').checked;

    if (!ano) {
        exibirErro('Selecione o ano');
        return;
    }

    // Construir query string
    const params = new URLSearchParams({
        ano: ano,
        modelo: modelo,
        abaterTaxa: abaterTaxa ? '1' : '0'
    });

    if (proprietario) {
        params.append('proprietario', proprietario);
    }

    // Aviso sobre muitos registros
    if (!proprietario) {
        exibirAviso('Gerando relatório para todos os proprietários. Isso pode demorar...');
    }

    // Abrir em nova aba
    const url = `${window.ROUTES.impressao}?${params.toString()}`;
    window.open(url, '_blank');
}
