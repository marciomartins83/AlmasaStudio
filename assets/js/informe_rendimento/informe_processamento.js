/**
 * Informe de Rendimentos - Módulo de Processamento
 * Gerencia a aba de processamento de informes
 */

import { getAjaxHeaders, exibirSucesso, exibirErro, setButtonLoading } from './informe_rendimento.js';

/**
 * Inicializa o módulo de processamento
 */
export function initProcessamento() {
    const btnProcessar = document.getElementById('btn-processar');

    if (!btnProcessar) {
        console.warn('Botão processar não encontrado');
        return;
    }

    btnProcessar.addEventListener('click', processarInformes);

    console.log('Módulo Processamento inicializado');
}

/**
 * Processa os informes de rendimentos
 */
async function processarInformes() {
    const ano = document.getElementById('proc-ano').value;
    const proprietarioInicial = document.getElementById('proc-proprietario-inicial').value;
    const proprietarioFinal = document.getElementById('proc-proprietario-final').value;
    const reprocessar = document.getElementById('proc-reprocessar').checked;

    if (!ano) {
        exibirErro('Selecione o ano para processamento');
        return;
    }

    const btnProcessar = document.getElementById('btn-processar');
    setButtonLoading(btnProcessar, true);

    // Esconder resultado anterior
    document.getElementById('processamento-resultado').style.display = 'none';

    try {
        const response = await fetch(window.ROUTES.processar, {
            method: 'POST',
            headers: getAjaxHeaders(),
            body: JSON.stringify({
                ano: parseInt(ano),
                proprietarioInicial: proprietarioInicial ? parseInt(proprietarioInicial) : null,
                proprietarioFinal: proprietarioFinal ? parseInt(proprietarioFinal) : null,
                reprocessar: reprocessar
            })
        });

        const data = await response.json();

        if (data.success) {
            // Atualizar resultado
            document.getElementById('res-processados').textContent = data.processados || 0;
            document.getElementById('res-criados').textContent = data.criados || 0;
            document.getElementById('res-atualizados').textContent = data.atualizados || 0;
            document.getElementById('res-erros').textContent = data.erros || 0;

            // Mostrar resultado
            document.getElementById('processamento-resultado').style.display = 'block';

            exibirSucesso(data.message || 'Processamento concluído com sucesso!');
        } else {
            exibirErro(data.message || 'Erro ao processar informes');
        }

    } catch (error) {
        console.error('Erro no processamento:', error);
        exibirErro('Erro de comunicação com o servidor');
    } finally {
        setButtonLoading(btnProcessar, false, '<i class="fas fa-play me-1"></i>Processar');
    }
}
