/**
 * Boleto Form Module - Funcionalidades do formulário de boleto
 *
 * Funcionalidades:
 * - Toggle de campos condicionais (desconto, juros, multa)
 * - Máscara de valores monetários
 * - Validação em tempo real
 * - Atualização do resumo lateral
 * - Select2 para pagador (autocomplete)
 */

'use strict';

// ============================================================================
// MÁSCARAS DE INPUT
// ============================================================================

function initMoneyMasks() {
    const moneyInputs = document.querySelectorAll('.valor-monetario, input[type="text"][id*="valor"]');

    moneyInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            let value = e.target.value;

            // Remove tudo que não é número
            value = value.replace(/\D/g, '');

            // Converte para centavos
            let cents = parseInt(value) || 0;

            // Formata como moeda
            let formatted = (cents / 100).toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });

            e.target.value = formatted;
        });

        // Ao perder foco, garante formatação
        input.addEventListener('blur', function(e) {
            let value = e.target.value;
            if (value === '' || value === '0,00') {
                e.target.value = '';
                return;
            }

            // Parse e reformat
            let numValue = parseFloat(value.replace(/\./g, '').replace(',', '.')) || 0;
            e.target.value = numValue.toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        });
    });
}

// ============================================================================
// TOGGLE CAMPOS CONDICIONAIS
// ============================================================================

function initConditionalFields() {
    // Mapeamento de select -> classe de campos
    const mappings = [
        { selectId: 'boleto_tipoDesconto', fieldsClass: 'campos-desconto', isentoValue: 'ISENTO' },
        { selectId: 'boleto_tipoJuros', fieldsClass: 'campos-juros', isentoValue: 'ISENTO' },
        { selectId: 'boleto_tipoMulta', fieldsClass: 'campos-multa', isentoValue: 'ISENTO' }
    ];

    mappings.forEach(({ selectId, fieldsClass, isentoValue }) => {
        const select = document.getElementById(selectId);
        if (!select) return;

        const fields = document.querySelectorAll('.' + fieldsClass);

        function toggleVisibility() {
            const isIsento = select.value === isentoValue || select.value === '';
            fields.forEach(field => {
                if (isIsento) {
                    field.classList.remove('show');
                    field.style.display = 'none';
                } else {
                    field.classList.add('show');
                    field.style.display = 'block';
                }
            });
        }

        select.addEventListener('change', toggleVisibility);
        toggleVisibility(); // Estado inicial
    });
}

// ============================================================================
// VALIDAÇÃO DO FORMULÁRIO
// ============================================================================

function initFormValidation() {
    const form = document.getElementById('formBoleto');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        // Validações customizadas
        const valorInput = form.querySelector('[id$="valorNominal"]');
        const vencimentoInput = form.querySelector('[id$="dataVencimento"]');
        const configInput = form.querySelector('[id$="configuracaoApi"]');
        const pagadorInput = form.querySelector('[id$="pessoaPagador"]');

        let isValid = true;
        let firstInvalid = null;

        // Validar valor
        if (valorInput) {
            const valor = parseFloat(valorInput.value.replace(/\./g, '').replace(',', '.')) || 0;
            if (valor <= 0) {
                markInvalid(valorInput, 'Informe um valor maior que zero');
                isValid = false;
                if (!firstInvalid) firstInvalid = valorInput;
            } else {
                markValid(valorInput);
            }
        }

        // Validar vencimento
        if (vencimentoInput) {
            if (!vencimentoInput.value) {
                markInvalid(vencimentoInput, 'Informe a data de vencimento');
                isValid = false;
                if (!firstInvalid) firstInvalid = vencimentoInput;
            } else {
                const vencimento = new Date(vencimentoInput.value);
                const hoje = new Date();
                hoje.setHours(0, 0, 0, 0);

                if (vencimento < hoje) {
                    markInvalid(vencimentoInput, 'Vencimento não pode ser no passado');
                    isValid = false;
                    if (!firstInvalid) firstInvalid = vencimentoInput;
                } else {
                    markValid(vencimentoInput);
                }
            }
        }

        // Validar configuração
        if (configInput && !configInput.value) {
            markInvalid(configInput, 'Selecione a configuração');
            isValid = false;
            if (!firstInvalid) firstInvalid = configInput;
        } else if (configInput) {
            markValid(configInput);
        }

        // Validar pagador
        if (pagadorInput && !pagadorInput.value) {
            markInvalid(pagadorInput, 'Selecione o pagador');
            isValid = false;
            if (!firstInvalid) firstInvalid = pagadorInput;
        } else if (pagadorInput) {
            markValid(pagadorInput);
        }

        if (!isValid) {
            e.preventDefault();
            if (firstInvalid) {
                firstInvalid.focus();
            }
        }
    });
}

function markInvalid(input, message) {
    input.classList.add('is-invalid');
    input.classList.remove('is-valid');

    // Adicionar mensagem de erro
    let feedback = input.parentNode.querySelector('.invalid-feedback');
    if (!feedback) {
        feedback = document.createElement('div');
        feedback.className = 'invalid-feedback';
        input.parentNode.appendChild(feedback);
    }
    feedback.textContent = message;
}

function markValid(input) {
    input.classList.remove('is-invalid');
    input.classList.add('is-valid');
}

// ============================================================================
// ATUALIZAÇÃO DO RESUMO
// ============================================================================

function initResumoUpdater() {
    const form = document.getElementById('formBoleto');
    if (!form) return;

    const resumoValor = document.getElementById('resumoValor');
    const resumoVencimento = document.getElementById('resumoVencimento');
    const resumoPagador = document.getElementById('resumoPagador');
    const resumoConfig = document.getElementById('resumoConfig');

    function updateResumo() {
        // Valor
        const valorInput = form.querySelector('[id$="valorNominal"]');
        if (valorInput && resumoValor) {
            const valor = parseFloat(valorInput.value.replace(/\./g, '').replace(',', '.')) || 0;
            resumoValor.textContent = 'R$ ' + valor.toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        // Vencimento
        const vencInput = form.querySelector('[id$="dataVencimento"]');
        if (vencInput && resumoVencimento) {
            if (vencInput.value) {
                const date = new Date(vencInput.value + 'T00:00:00');
                resumoVencimento.textContent = date.toLocaleDateString('pt-BR');
            } else {
                resumoVencimento.textContent = '-';
            }
        }

        // Pagador
        const pagadorSelect = form.querySelector('[id$="pessoaPagador"]');
        if (pagadorSelect && resumoPagador) {
            if (pagadorSelect.selectedIndex > 0) {
                resumoPagador.textContent = pagadorSelect.options[pagadorSelect.selectedIndex].text;
            } else {
                resumoPagador.textContent = '-';
            }
        }

        // Configuração
        const configSelect = form.querySelector('[id$="configuracaoApi"]');
        if (configSelect && resumoConfig) {
            if (configSelect.selectedIndex > 0) {
                resumoConfig.textContent = configSelect.options[configSelect.selectedIndex].text;
            } else {
                resumoConfig.textContent = '-';
            }
        }
    }

    // Adicionar listeners a todos os inputs
    form.querySelectorAll('input, select, textarea').forEach(el => {
        el.addEventListener('change', updateResumo);
        el.addEventListener('input', updateResumo);
    });

    // Atualização inicial
    updateResumo();
}

// ============================================================================
// CONTADOR DE CARACTERES
// ============================================================================

function initCharCounter() {
    const mensagemField = document.querySelector('[id$="mensagemPagador"]');
    const charCount = document.getElementById('charCount');

    if (!mensagemField || !charCount) return;

    function updateCount() {
        const length = mensagemField.value.length;
        charCount.textContent = length;

        // Mudar cor se próximo do limite
        if (length > 150) {
            charCount.classList.add('text-danger');
        } else if (length > 120) {
            charCount.classList.remove('text-danger');
            charCount.classList.add('text-warning');
        } else {
            charCount.classList.remove('text-danger', 'text-warning');
        }
    }

    mensagemField.addEventListener('input', updateCount);
    updateCount();
}

// ============================================================================
// DATA LIMITE AUTOMÁTICA
// ============================================================================

function initAutoDataLimite() {
    const vencimentoInput = document.querySelector('[id$="dataVencimento"]');
    const limiteInput = document.querySelector('[id$="dataLimitePagamento"]');

    if (!vencimentoInput || !limiteInput) return;

    vencimentoInput.addEventListener('change', function() {
        // Se data limite não preenchida, sugerir vencimento + 30 dias
        if (!limiteInput.value && this.value) {
            const vencimento = new Date(this.value);
            vencimento.setDate(vencimento.getDate() + 30);

            // Não definir automaticamente, apenas sugerir via placeholder
            limiteInput.placeholder = 'Sugestão: ' + vencimento.toLocaleDateString('pt-BR');
        }
    });
}

// ============================================================================
// SELECT2 PARA PAGADOR (se disponível)
// ============================================================================

function initSelect2Pagador() {
    const pagadorSelect = document.querySelector('.select2-pessoa');
    if (!pagadorSelect) return;

    // Verificar se jQuery e Select2 estão disponíveis
    if (typeof jQuery === 'undefined' || typeof jQuery.fn.select2 === 'undefined') {
        console.log('ℹ️ Select2 não disponível, usando select padrão');
        return;
    }

    jQuery(pagadorSelect).select2({
        theme: 'bootstrap-5',
        placeholder: 'Digite para buscar...',
        allowClear: true,
        minimumInputLength: 2,
        language: {
            inputTooShort: function() {
                return 'Digite ao menos 2 caracteres...';
            },
            noResults: function() {
                return 'Nenhum resultado encontrado';
            },
            searching: function() {
                return 'Buscando...';
            }
        }
    });
}

// ============================================================================
// INICIALIZAÇÃO
// ============================================================================

document.addEventListener('DOMContentLoaded', function() {
    console.log('📝 Boleto Form module loaded');

    initMoneyMasks();
    initConditionalFields();
    initFormValidation();
    initResumoUpdater();
    initCharCounter();
    initAutoDataLimite();
    initSelect2Pagador();
});
