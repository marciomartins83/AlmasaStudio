/**
 * Contratos - Formulário
 * Gerencia validações, máscaras e lógica do formulário de contratos
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ contrato_form.js carregado');

    const form = document.getElementById('contrato-form');
    if (!form) {
        console.warn('⚠️ Formulário não encontrado');
        return;
    }

    // Inicializar componentes
    inicializarValidacoes();
    inicializarMascaras();
    inicializarCalculos();
    inicializarCondicionalGarantia();
    inicializarSubmit();
});

/**
 * Inicializar validações customizadas
 */
function inicializarValidacoes() {
    const form = document.getElementById('contrato-form');

    // Validar datas
    const dataInicio = form.querySelector('[name="data_inicio"]');
    const dataFim = form.querySelector('[name="data_fim"]');

    if (dataInicio && dataFim) {
        dataFim.addEventListener('change', function() {
            if (this.value && dataInicio.value) {
                const inicio = new Date(dataInicio.value);
                const fim = new Date(this.value);

                if (fim <= inicio) {
                    alert('A data de fim deve ser posterior à data de início');
                    this.value = '';
                }
            }
        });
    }

    // Validar valor do contrato
    const valorContrato = form.querySelector('[name="valor_contrato"]');
    if (valorContrato) {
        valorContrato.addEventListener('blur', function() {
            const valor = parseFloat(this.value);
            if (valor <= 0) {
                alert('O valor do contrato deve ser maior que zero');
                this.value = '';
                this.focus();
            }
        });
    }

    // Validar taxa de administração
    const taxaAdmin = form.querySelector('[name="taxa_administracao"]');
    if (taxaAdmin) {
        taxaAdmin.addEventListener('blur', function() {
            const taxa = parseFloat(this.value);
            if (taxa < 0 || taxa > 100) {
                alert('A taxa de administração deve estar entre 0 e 100%');
                this.value = '10.00';
                this.focus();
            }
        });
    }
}

/**
 * Inicializar máscaras de entrada
 */
function inicializarMascaras() {
    const form = document.getElementById('contrato-form');

    // Máscara para dia de vencimento (1-31)
    const diaVencimento = form.querySelector('[name="dia_vencimento"]');
    if (diaVencimento) {
        diaVencimento.addEventListener('input', function() {
            let valor = parseInt(this.value);
            if (valor > 31) this.value = 31;
            if (valor < 1 && this.value !== '') this.value = 1;
        });
    }

    // Formatar valores monetários ao sair do campo
    const camposMonetarios = form.querySelectorAll('[name="valor_contrato"], [name="valor_caucao"], [name="multa_rescisao"]');
    camposMonetarios.forEach(campo => {
        campo.addEventListener('blur', function() {
            if (this.value) {
                const valor = parseFloat(this.value);
                this.value = valor.toFixed(2);
            }
        });
    });
}

/**
 * Inicializar cálculos automáticos
 */
function inicializarCalculos() {
    const form = document.getElementById('contrato-form');

    const valorContrato = form.querySelector('[name="valor_contrato"]');
    const taxaAdmin = form.querySelector('[name="taxa_administracao"]');

    if (valorContrato && taxaAdmin) {
        // Calcular valor líquido do proprietário
        const calcularValorLiquido = () => {
            const valor = parseFloat(valorContrato.value) || 0;
            const taxa = parseFloat(taxaAdmin.value) || 0;
            const valorLiquido = valor - (valor * taxa / 100);

            // Exibir cálculo (se houver elemento na UI)
            const displayLiquido = document.getElementById('valor-liquido-display');
            if (displayLiquido) {
                displayLiquido.textContent = `R$ ${valorLiquido.toFixed(2).replace('.', ',')}`;
            }
        };

        valorContrato.addEventListener('input', calcularValorLiquido);
        taxaAdmin.addEventListener('input', calcularValorLiquido);

        // Calcular na carga inicial se houver valores
        if (valorContrato.value || taxaAdmin.value) {
            calcularValorLiquido();
        }
    }

    // Calcular próximo reajuste automaticamente
    const dataInicio = form.querySelector('[name="data_inicio"]');
    const periodicidade = form.querySelector('[name="periodicidade_reajuste"]');
    const dataReajuste = form.querySelector('[name="data_proximo_reajuste"]');

    if (dataInicio && periodicidade && dataReajuste) {
        const calcularProximoReajuste = () => {
            if (!dataInicio.value || dataReajuste.value) return;

            const inicio = new Date(dataInicio.value);
            const mesesParaSomar = periodicidade.value === 'semestral' ? 6 : 12;

            inicio.setMonth(inicio.getMonth() + mesesParaSomar);

            const ano = inicio.getFullYear();
            const mes = String(inicio.getMonth() + 1).padStart(2, '0');
            const dia = String(inicio.getDate()).padStart(2, '0');

            dataReajuste.value = `${ano}-${mes}-${dia}`;
        };

        dataInicio.addEventListener('change', calcularProximoReajuste);
        periodicidade.addEventListener('change', calcularProximoReajuste);
    }
}

/**
 * Controlar exibição de campos condicionais baseado no tipo de garantia
 */
function inicializarCondicionalGarantia() {
    const tipoGarantia = document.getElementById('tipo-garantia-select');
    const valorCaucaoContainer = document.getElementById('valor-caucao-container');

    if (!tipoGarantia || !valorCaucaoContainer) return;

    const atualizarVisibilidade = () => {
        if (tipoGarantia.value === 'caucao') {
            valorCaucaoContainer.style.display = 'block';
        } else {
            valorCaucaoContainer.style.display = 'none';
        }
    };

    tipoGarantia.addEventListener('change', atualizarVisibilidade);
    atualizarVisibilidade(); // Executar na carga inicial
}

/**
 * Inicializar submit do formulário
 */
function inicializarSubmit() {
    const form = document.getElementById('contrato-form');

    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        // Validação final
        if (!validarFormulario()) {
            return;
        }

        // Coletar dados do formulário
        const formData = new FormData(form);
        const dados = {};

        formData.forEach((valor, chave) => {
            // Converter checkboxes
            if (chave === 'gera_boleto' || chave === 'envia_email' || chave === 'ativo') {
                dados[chave] = true;
            } else {
                dados[chave] = valor;
            }
        });

        // Adicionar checkboxes desmarcados como false
        if (!dados.gera_boleto) dados.gera_boleto = false;
        if (!dados.envia_email) dados.envia_email = false;
        if (!dados.ativo) dados.ativo = false;

        // Desabilitar botão de submit
        const btnSubmit = form.querySelector('button[type="submit"]');
        const textoOriginal = btnSubmit.innerHTML;
        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';

        try {
            // Submeter via POST tradicional (não AJAX para manter flash messages)
            form.submit();
        } catch (error) {
            console.error('❌ Erro ao salvar:', error);
            alert('Erro ao salvar contrato. Por favor, tente novamente.');

            // Reabilitar botão
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = textoOriginal;
        }
    });
}

/**
 * Validar formulário antes de submeter
 */
function validarFormulario() {
    const form = document.getElementById('contrato-form');

    // Validar campos obrigatórios
    const camposObrigatorios = form.querySelectorAll('[required]');
    let todosPreenchidos = true;

    camposObrigatorios.forEach(campo => {
        if (!campo.value) {
            campo.classList.add('is-invalid');
            todosPreenchidos = false;
        } else {
            campo.classList.remove('is-invalid');
        }
    });

    if (!todosPreenchidos) {
        alert('Por favor, preencha todos os campos obrigatórios');
        return false;
    }

    // Validar datas
    const dataInicio = form.querySelector('[name="data_inicio"]').value;
    const dataFim = form.querySelector('[name="data_fim"]').value;

    if (dataFim && dataInicio) {
        const inicio = new Date(dataInicio);
        const fim = new Date(dataFim);

        if (fim <= inicio) {
            alert('A data de fim deve ser posterior à data de início');
            return false;
        }
    }

    // Validar valor do contrato
    const valorContrato = parseFloat(form.querySelector('[name="valor_contrato"]').value);
    if (valorContrato <= 0) {
        alert('O valor do contrato deve ser maior que zero');
        return false;
    }

    return true;
}

/**
 * Buscar imóveis disponíveis via AJAX
 */
async function buscarImoveisDisponiveis() {
    if (!window.ROUTES || !window.ROUTES.imoveisDisponiveis) {
        console.warn('⚠️ Rota imoveisDisponiveis não definida');
        return [];
    }

    try {
        const response = await fetch(window.ROUTES.imoveisDisponiveis, {
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
            return data.imoveis;
        }

        return [];
    } catch (error) {
        console.error('❌ Erro ao buscar imóveis:', error);
        return [];
    }
}

// Exportar funções para uso global
window.contratoForm = {
    buscarImoveisDisponiveis,
    validarFormulario
};
