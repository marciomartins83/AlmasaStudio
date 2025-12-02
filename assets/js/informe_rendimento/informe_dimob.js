/**
 * Informe de Rendimentos - Módulo DIMOB
 * Gerencia a aba de configuração e geração DIMOB
 */

import { getAjaxHeaders, exibirSucesso, exibirErro, setButtonLoading } from './informe_rendimento.js';

/**
 * Inicializa o módulo DIMOB
 */
export function initDimob() {
    const btnGravar = document.getElementById('btn-gravar-dimob');
    const btnGerar = document.getElementById('btn-gerar-dimob');
    const selectAno = document.getElementById('dimob-ano');

    if (!btnGravar) {
        console.warn('Botões DIMOB não encontrados');
        return;
    }

    // Listeners
    btnGravar.addEventListener('click', gravarConfiguracao);
    btnGerar.addEventListener('click', gerarArquivoDimob);
    selectAno.addEventListener('change', carregarConfiguracao);

    // Aplicar máscaras
    aplicarMascaras();

    // Carregar configuração do ano atual
    carregarConfiguracao();

    console.log('Módulo DIMOB inicializado');
}

/**
 * Aplica máscaras nos campos
 */
function aplicarMascaras() {
    // Máscara CNPJ
    const cnpj = document.getElementById('dimob-cnpj');
    if (cnpj) {
        cnpj.addEventListener('input', (e) => {
            let v = e.target.value.replace(/\D/g, '');
            v = v.substring(0, 14);
            v = v.replace(/^(\d{2})(\d)/, '$1.$2');
            v = v.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
            v = v.replace(/\.(\d{3})(\d)/, '.$1/$2');
            v = v.replace(/(\d{4})(\d)/, '$1-$2');
            e.target.value = v;
        });
    }

    // Máscara CPF
    const cpf = document.getElementById('dimob-cpf');
    if (cpf) {
        cpf.addEventListener('input', (e) => {
            let v = e.target.value.replace(/\D/g, '');
            v = v.substring(0, 11);
            v = v.replace(/(\d{3})(\d)/, '$1.$2');
            v = v.replace(/(\d{3})(\d)/, '$1.$2');
            v = v.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            e.target.value = v;
        });
    }

    // Máscara código cidade (apenas números)
    const cidade = document.getElementById('dimob-cidade');
    if (cidade) {
        cidade.addEventListener('input', (e) => {
            e.target.value = e.target.value.replace(/\D/g, '').substring(0, 7);
        });
    }
}

/**
 * Carrega configuração DIMOB do ano selecionado
 */
async function carregarConfiguracao() {
    const ano = document.getElementById('dimob-ano').value;

    try {
        const response = await fetch(`${window.ROUTES.dimobGet}?ano=${ano}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        const data = await response.json();

        if (data.success && data.data) {
            // Preencher campos
            document.getElementById('dimob-cnpj').value = data.data.cnpjDeclarante || '';
            document.getElementById('dimob-cpf').value = data.data.cpfResponsavel || '';
            document.getElementById('dimob-cidade').value = data.data.codigoCidade || '';
            document.getElementById('dimob-retificadora').checked = data.data.declaracaoRetificadora || false;
            document.getElementById('dimob-situacao-especial').checked = data.data.situacaoEspecial || false;

            // Mostrar data de última geração se houver
            if (data.data.dataGeracao) {
                document.getElementById('dimob-ultima-geracao').textContent = data.data.dataGeracao;
                document.getElementById('dimob-data-geracao').style.display = 'block';
            } else {
                document.getElementById('dimob-data-geracao').style.display = 'none';
            }
        } else {
            // Limpar campos se não houver configuração
            limparCampos();
        }

    } catch (error) {
        console.error('Erro ao carregar configuração:', error);
        limparCampos();
    }
}

/**
 * Limpa campos do formulário DIMOB
 */
function limparCampos() {
    document.getElementById('dimob-cnpj').value = '';
    document.getElementById('dimob-cpf').value = '';
    document.getElementById('dimob-cidade').value = '';
    document.getElementById('dimob-retificadora').checked = false;
    document.getElementById('dimob-situacao-especial').checked = false;
    document.getElementById('dimob-data-geracao').style.display = 'none';
}

/**
 * Grava configuração DIMOB
 */
async function gravarConfiguracao() {
    const cnpj = document.getElementById('dimob-cnpj').value.trim();
    const cpf = document.getElementById('dimob-cpf').value.trim();
    const cidade = document.getElementById('dimob-cidade').value.trim();

    // Validar campos obrigatórios
    if (!cnpj || !cpf || !cidade) {
        exibirErro('Preencha todos os campos obrigatórios (CNPJ, CPF e Código da Cidade)');
        return;
    }

    // Validar formato CNPJ
    if (cnpj.replace(/\D/g, '').length !== 14) {
        exibirErro('CNPJ deve ter 14 dígitos');
        return;
    }

    // Validar formato CPF
    if (cpf.replace(/\D/g, '').length !== 11) {
        exibirErro('CPF deve ter 11 dígitos');
        return;
    }

    const dados = {
        ano: parseInt(document.getElementById('dimob-ano').value),
        cnpjDeclarante: cnpj,
        cpfResponsavel: cpf,
        codigoCidade: cidade,
        declaracaoRetificadora: document.getElementById('dimob-retificadora').checked,
        situacaoEspecial: document.getElementById('dimob-situacao-especial').checked
    };

    const btnGravar = document.getElementById('btn-gravar-dimob');
    setButtonLoading(btnGravar, true);

    try {
        const response = await fetch(window.ROUTES.dimobSalvar, {
            method: 'POST',
            headers: getAjaxHeaders(),
            body: JSON.stringify(dados)
        });

        const data = await response.json();

        if (data.success) {
            exibirSucesso('Configuração salva com sucesso!');
        } else {
            exibirErro(data.message || 'Erro ao salvar configuração');
        }

    } catch (error) {
        console.error('Erro ao salvar:', error);
        exibirErro('Erro de comunicação com o servidor');
    } finally {
        setButtonLoading(btnGravar, false, '<i class="fas fa-save me-1"></i>Gravar Configuração');
    }
}

/**
 * Gera arquivo DIMOB para download
 */
function gerarArquivoDimob() {
    const ano = document.getElementById('dimob-ano').value;
    const proprietarioInicial = document.getElementById('dimob-proprietario-inicial').value;
    const proprietarioFinal = document.getElementById('dimob-proprietario-final').value;

    // Construir query string
    const params = new URLSearchParams({ ano: ano });

    if (proprietarioInicial) {
        params.append('proprietarioInicial', proprietarioInicial);
    }

    if (proprietarioFinal) {
        params.append('proprietarioFinal', proprietarioFinal);
    }

    // Redirecionar para download
    window.location.href = `${window.ROUTES.dimobGerar}?${params.toString()}`;
}
