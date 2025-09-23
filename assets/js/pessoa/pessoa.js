document.addEventListener('DOMContentLoaded', function() {
    console.log('🎯 pessoa.js carregado');
    
    // Verificar se as rotas estão disponíveis
    if (!window.ROUTES) {
        console.error('❌ window.ROUTES não está definido');
        return;
    }
    
    console.log('✅ Rotas disponíveis:', window.ROUTES);
    
    // =========================================================================
    // FUNÇÕES UTILITÁRIAS COMPARTILHADAS - CORRIGIDAS
    // =========================================================================
    
    /**
     * Carrega tipos de uma entidade específica - FUNÇÃO CORRIGIDA
     */
    window.carregarTipos = async function(entidade) {
        try {
            console.log(`Carregando tipos de ${entidade}...`);
            
            // URL usando window.ROUTES (será definida no template)
            if (!window.ROUTES.loadTipos) {
                console.error('URL loadTipos não definida em window.ROUTES');
                return [];
            }
            
            const url = window.ROUTES.loadTipos.replace('__ENTIDADE__', entidade);
            
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            console.log(`✅ Tipos de ${entidade} carregados:`, data);
            
            return data.tipos || [];
            
        } catch (error) {
            console.error(`❌ Erro ao carregar tipos de ${entidade}:`, error);
            alert(`Erro ao carregar tipos de ${entidade}. Verifique sua conexão.`);
            return [];
        }
    };

    /**
     * Cria um select com opções de tipos - MELHORADA
     * Parei aqui em 22/09/2025
     */
    window.criarSelectTipos = function(tipos, name, id, onNovoTipo) {
        if (!Array.isArray(tipos) || tipos.length === 0) {
            console.warn(`⚠️ Nenhum tipo encontrado para ${name}`);
            return `
                <div class="input-group">
                    <select class="form-select" name="${name}" id="${id}" required>
                        <option value="">Nenhum tipo disponível</option>
                    </select>
                    <button type="button" class="btn btn-outline-secondary" onclick="${onNovoTipo}">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            `;
        }
        
        const options = tipos.map(tipo => 
            `<option value="${tipo.id}">${tipo.tipo || tipo.nome}</option>`
        ).join('');
        
        return `
            <div class="input-group">
                <select class="form-select" name="${name}" id="${id}" required>
                    <option value="">Selecione o tipo...</option>
                    ${options}
                </select>
                <button type="button" class="btn btn-outline-secondary" onclick="${onNovoTipo}">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
        `;
    };
    
    /**
     * Salva um novo tipo via AJAX - CORRIGIDA
     */
    window.salvarNovoTipo = async function(entidade, valor, callback) {
        try {
            console.log(`Salvando novo tipo: ${entidade} = ${valor}`);
            
            if (!window.ROUTES.salvarTipo) {
                throw new Error('URL salvarTipo não definida');
            }
            
            const url = window.ROUTES.salvarTipo.replace('__ENTIDADE__', entidade);
            
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ tipo: valor })
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                callback(data.tipo);
                return true;
            } else {
                throw new Error(data.message || 'Erro ao salvar tipo');
            }
            
        } catch (error) {
            console.error('❌ Erro ao salvar tipo:', error);
            alert('Erro ao salvar o tipo. Tente novamente.');
            return false;
        }
    };
    
    // =========================================================================
    // FUNÇÕES DE UTILITÁRIOS PARA FORMULÁRIOS
    // =========================================================================
    
    /**
     * Define valor em um campo do formulário de forma segura
     */
    window.setFormValue = function(fieldId, value) {
        const element = document.getElementById(fieldId);
        if (element) {
            element.value = value || '';
            console.log(`✅ Campo preenchido: ${fieldId} = ${value || ''}`);
            return true;
        } else {
            console.warn(`⚠️ Campo não encontrado: ${fieldId}`);
            return false;
        }
    };
    
    /**
     * Define valor em um select de forma segura
     */
    window.setSelectValue = function(fieldId, value) {
        const element = document.getElementById(fieldId);
        if (element && value) {
            element.value = value;
            console.log(`✅ Select preenchido: ${fieldId} = ${value}`);
            return true;
        } else {
            console.warn(`⚠️ Select não encontrado ou valor vazio: ${fieldId}`);
            return false;
        }
    };
    
    /**
     * Limpa todos os campos de um formulário
     */
    window.limparFormulario = function(formSelector = 'form') {
        const form = document.querySelector(formSelector);
        if (form) {
            form.reset();
            console.log('✅ Formulário limpo');
        }
    };
    
    // =========================================================================
    // VALIDAÇÕES DE DOCUMENTOS
    // =========================================================================
    
    /**
     * Valida CPF
     */
    window.validarCPF = function(cpf) {
        cpf = cpf.replace(/[^\d]/g, '');
        
        if (cpf.length !== 11 || /^(\d)\1+$/.test(cpf)) {
            return false;
        }
        
        let soma = 0;
        for (let i = 0; i < 9; i++) {
            soma += parseInt(cpf.charAt(i)) * (10 - i);
        }
        let resto = (soma * 10) % 11;
        if (resto === 10 || resto === 11) resto = 0;
        if (resto !== parseInt(cpf.charAt(9))) return false;
        
        soma = 0;
        for (let i = 0; i < 10; i++) {
            soma += parseInt(cpf.charAt(i)) * (11 - i);
        }
        resto = (soma * 10) % 11;
        if (resto === 10 || resto === 11) resto = 0;
        if (resto !== parseInt(cpf.charAt(10))) return false;
        
        return true;
    };
    
    /**
     * Valida CNPJ
     */
    window.validarCNPJ = function(cnpj) {
        cnpj = cnpj.replace(/[^\d]/g, '');
        
        if (cnpj.length !== 14 || /^(\d)\1+$/.test(cnpj)) {
            return false;
        }
        
        let tamanho = cnpj.length - 2;
        let numeros = cnpj.substring(0, tamanho);
        let digitos = cnpj.substring(tamanho);
        let soma = 0;
        let pos = tamanho - 7;
        
        for (let i = tamanho; i >= 1; i--) {
            soma += numeros.charAt(tamanho - i) * pos--;
            if (pos < 2) pos = 9;
        }
        
        let resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
        if (resultado !== parseInt(digitos.charAt(0))) return false;
        
        tamanho = tamanho + 1;
        numeros = cnpj.substring(0, tamanho);
        soma = 0;
        pos = tamanho - 7;
        
        for (let i = tamanho; i >= 1; i--) {
            soma += numeros.charAt(tamanho - i) * pos--;
            if (pos < 2) pos = 9;
        }
        
        resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
        if (resultado !== parseInt(digitos.charAt(1))) return false;
        
        return true;
    };
    
    /**
     * Formata CPF
     */
    window.formatarCPF = function(cpf) {
        cpf = cpf.replace(/[^\d]/g, '');
        return cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
    };
    
    /**
     * Formata CNPJ
     */
    window.formatarCNPJ = function(cnpj) {
        cnpj = cnpj.replace(/[^\d]/g, '');
        return cnpj.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
    };
    
    // =========================================================================
    // MÁSCARAS AUTOMÁTICAS
    // =========================================================================
    
    /**
     * Aplica máscara de telefone
     */
    window.aplicarMascaraTelefone = function(input) {
        let value = input.value.replace(/[^\d]/g, '');
        
        if (value.length <= 10) {
            // Telefone fixo: (XX) XXXX-XXXX
            value = value.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
        } else {
            // Celular: (XX) XXXXX-XXXX
            value = value.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
        }
        
        input.value = value;
    };
    
    /**
     * Aplica máscara de CEP
     */
    window.aplicarMascaraCEP = function(input) {
        let value = input.value.replace(/[^\d]/g, '');
        value = value.replace(/(\d{5})(\d{3})/, '$1-$2');
        input.value = value;
    };
    
    // =========================================================================
    // INICIALIZAÇÃO
    // =========================================================================
    
    console.log('✅ pessoa.js: Todas as funções utilitárias carregadas');
});
