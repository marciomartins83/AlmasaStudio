document.addEventListener('DOMContentLoaded', function() {
    // URL da busca
    const searchUrl = window.SEARCH_URL;
    
    // Elementos da interface de busca
    const searchCriteria = document.getElementById('searchCriteria');
    const searchValue = document.getElementById('searchValue');
    const searchHelp = document.getElementById('searchHelp');
    const btnSearch = document.getElementById('btn-search');
    const btnClear = document.getElementById('btn-clear');
    const additionalDocumentRow = document.getElementById('additionalDocumentRow');
    const additionalDocumentValue = document.getElementById('additionalDocumentValue');
    const searchResults = document.getElementById('search-results');
    const searchMessage = document.getElementById('search-message');
    const resultsList = document.getElementById('results-list');
    const mainForm = document.getElementById('main-form');
    const camposPessoaFisica = document.getElementById('campos-pessoa-fisica');
    const conjugeSection = document.getElementById('conjuge-section');
    const temConjuge = document.getElementById('temConjuge');
    const camposConjuge = document.getElementById('campos-conjuge');
    
    // Verificar se todos os elementos necessários estão presentes
    const elementosRequeridos = [
        searchCriteria, searchValue, searchHelp, btnSearch, btnClear,
        additionalDocumentRow, additionalDocumentValue, searchResults,
        searchMessage, resultsList, mainForm, camposPessoaFisica,
        conjugeSection, temConjuge, camposConjuge
    ];
    
    // Se algum elemento crítico estiver faltando, não inicializar
    if (elementosRequeridos.some(el => !el)) {
        console.warn('Alguns elementos necessários não foram encontrados. A funcionalidade pode estar limitada.');
        return;
    }

    // Configurar interface baseada no critério selecionado
    searchCriteria.addEventListener('change', function() {
        const criteria = this.value;
        searchValue.disabled = !criteria;
        searchValue.value = '';
        btnSearch.disabled = !criteria;
        additionalDocumentRow.style.display = 'none';
        
        switch(criteria) {
            case 'cpf':
                searchValue.placeholder = 'Digite o CPF (11 dígitos)';
                searchValue.maxLength = 11;
                searchHelp.textContent = 'Apenas números, sem pontos ou traços';
                break;
            case 'cnpj':
                searchValue.placeholder = 'Digite o CNPJ (14 dígitos)';
                searchValue.maxLength = 14;
                searchHelp.textContent = 'Apenas números, sem pontos ou traços';
                break;
            case 'nome':
                searchValue.placeholder = 'Digite o nome completo';
                searchValue.maxLength = 255;
                searchHelp.textContent = 'Busca por nome similarmente (LIKE)';
                additionalDocumentRow.style.display = 'block';
                break;
            case 'id':
                searchValue.placeholder = 'Digite o ID da pessoa';
                searchValue.maxLength = 10;
                searchHelp.textContent = 'Apenas números';
                break;
            default:
                searchValue.placeholder = 'Digite o valor para busca';
                searchHelp.textContent = '';
        }
    });
    
    // Validação de entrada baseada no critério
    searchValue.addEventListener('input', function() {
        const criteria = searchCriteria.value;
        let value = this.value;
        
        switch(criteria) {
            case 'cpf':
            case 'cnpj':
                // Apenas números e letras
                this.value = value.replace(/[^0-9a-zA-Z]/g, '');
                break;
            case 'id':
                // Apenas números
                this.value = value.replace(/[^0-9]/g, '');
                break;
        }
        
        // Validar tamanho mínimo para habilitar busca
        let minLength = 0;
        switch(criteria) {
            case 'cpf': minLength = 11; break;
            case 'cnpj': minLength = 14; break;
            case 'nome': minLength = 3; break;
            case 'id': minLength = 1; break;
        }
        
        btnSearch.disabled = this.value.length < minLength;
    });
    
    // Validação do documento adicional
    additionalDocumentValue.addEventListener('input', function() {
        const docType = document.getElementById('additionalDocumentType').value;
        let value = this.value;
        
        // Apenas números e letras
        this.value = value.replace(/[^0-9a-zA-Z]/g, '');
        
        // Validar tamanho
        if (docType === 'cpf' && this.value.length > 11) {
            this.value = this.value.substring(0, 11);
        } else if (docType === 'cnpj' && this.value.length > 14) {
            this.value = this.value.substring(0, 14);
        }
    });
    
    // Realizar busca
    btnSearch.addEventListener('click', function() {
        const criteria = searchCriteria.value;
        const value = searchValue.value;
        const additionalDoc = criteria === 'nome' ? additionalDocumentValue.value : null;
        const additionalDocType = criteria === 'nome' ? document.getElementById('additionalDocumentType').value : null;
        
        if (!value) return;
        
        // Mostrar loading
        btnSearch.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Buscando...';
        btnSearch.disabled = true;
        
        // Preparar dados da busca
        const searchData = {
            criteria: criteria,
            value: value,
            additionalDoc: additionalDoc,
            additionalDocType: additionalDocType
        };
        
        // Debug da URL e dados
        console.log('URL da busca:', searchUrl);
        console.log('Dados da busca:', searchData);
        
        // Fazer requisição AJAX
        fetch(searchUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: JSON.stringify(searchData)
        })
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            btnSearch.innerHTML = '<i class="fas fa-search"></i> Buscar';
            btnSearch.disabled = false;
            
            console.log('Response data:', data);
            
            if (data.success) {
                showSearchResults(data);
            } else {
                showError(data.message || 'Erro na busca');
            }
        })
        .catch(error => {
            btnSearch.innerHTML = '<i class="fas fa-search"></i> Buscar';
            btnSearch.disabled = false;
            console.error('Erro na requisição:', error);
            
            // Mostrar mensagem de erro específica
            let errorMessage;
            if (error.message.includes('NetworkError') || error.message.includes('Failed to fetch')) {
                errorMessage = 'Erro de conexão. Verifique sua internet e tente novamente.';
            } else if (error.message) {
                errorMessage = error.message;
            } else {
                errorMessage = 'Erro ao processar a requisição. Tente novamente.';
            }
            
            showError(errorMessage);
        });
    });
    
    // Limpar busca
    btnClear.addEventListener('click', function() {
        searchCriteria.value = '';
        searchValue.value = '';
        searchValue.disabled = true;
        btnSearch.disabled = true;
        additionalDocumentValue.value = '';
        additionalDocumentRow.style.display = 'none';
        searchResults.style.display = 'none';
        mainForm.style.display = 'none';
    });
    
    // Mostrar resultados da busca
    function showSearchResults(data) {
        searchResults.style.display = 'block';
        
        if (data.pessoa) {
            // Pessoa encontrada - preencher formulário
            searchMessage.textContent = 'Pessoa encontrada! Formulário preenchido automaticamente.';
            searchMessage.className = 'alert alert-success';
            preencherFormulario(data.pessoa);
            mainForm.style.display = 'block';
        } else {
            // Pessoa não encontrada - abrir para cadastro
            searchMessage.textContent = 'Pessoa não encontrada. Preencha os dados para cadastrar uma nova pessoa.';
            searchMessage.className = 'alert alert-info';
            limparFormulario();
            
            // APROVEITAR dados da busca no formulário
            preencherDadosBusca();
            
            mainForm.style.display = 'block';
        }
    }
    
    // Função para aproveitar dados da busca no formulário
    function preencherDadosBusca() {
        const criteria = searchCriteria.value;
        const value = searchValue.value;
        const additionalDoc = additionalDocumentValue.value;
        const additionalDocType = document.getElementById('additionalDocumentType').value;
        
        console.log('Preenchendo dados da busca:', {
            criteria: criteria,
            value: value,
            additionalDoc: additionalDoc,
            additionalDocType: additionalDocType
        });
        
        // Debug: verificar se todos os IDs existem no DOM
        console.log('🔍 Verificando existência dos elementos:');
        Object.keys(window.FORM_IDS).forEach(key => {
            const id = window.FORM_IDS[key];
            const element = document.getElementById(id);
            console.log(`${key}: ${id} - ${element ? '✅ Encontrado' : '❌ Não encontrado'}`);
        });
        
        // Função auxiliar para definir valores com verificação
        const setValue = (id, value) => {
            const element = document.getElementById(id);
            if (element) {
                element.value = value || '';
                console.log(`✅ Campo preenchido: ${id} = ${value || ''}`);
            } else {
                console.error(`❌ Elemento não encontrado: ${id}`);
                // Tentar encontrar elemento com outro padrão de ID
                const alternativeElement = document.querySelector(`[id*="${id.replace('form_', '')}"]`);
                if (alternativeElement) {
                    console.log(`🔄 Encontrado elemento alternativo:`, alternativeElement.id);
                    alternativeElement.value = value || '';
                }
            }
        };
        
        // Preencher nome se foi critério de busca
        if ((criteria === 'nome' || criteria === 'Nome' || criteria === 'Nome Completo') && value) {
            console.log('Preenchendo nome:', value);
            setValue(window.FORM_IDS.nome, value);
        }
        
        // Preencher CPF/CNPJ conforme critério
        if ((criteria === 'cpf' || criteria === 'CPF' || criteria === 'CPF (Pessoa Física)') && value) {
            console.log('Preenchendo CPF direto:', value);
            setValue(window.FORM_IDS.searchTerm, value);
        } else if ((criteria === 'cnpj' || criteria === 'CNPJ' || criteria === 'CNPJ (Pessoa Jurídica)') && value) {
            console.log('Preenchendo CNPJ direto:', value);
            setValue(window.FORM_IDS.searchTerm, value);
        } else if ((criteria === 'nome' || criteria === 'Nome' || criteria === 'Nome Completo') && additionalDoc) {
            if (additionalDocType.includes('cpf') || additionalDocType.includes('CPF')) {
                console.log('Preenchendo CPF adicional:', additionalDoc);
                setValue(window.FORM_IDS.searchTerm, additionalDoc);
            } else if (additionalDocType.includes('cnpj') || additionalDocType.includes('CNPJ')) {
                console.log('Preenchendo CNPJ adicional:', additionalDoc);
                setValue(window.FORM_IDS.searchTerm, additionalDoc);
            }
        }
        
        // Configurar interface baseada no tipo de documento (flexível)
        const isPessoaFisica = criteria.includes('cpf') || criteria.includes('CPF') || 
                               (additionalDocType && additionalDocType.includes('CPF') && additionalDoc);
        const isPessoaJuridica = criteria.includes('cnpj') || criteria.includes('CNPJ') || 
                                (additionalDocType && additionalDocType.includes('CNPJ') && additionalDoc);
        
        if (isPessoaFisica) {
            camposPessoaFisica.style.display = 'block';
            conjugeSection.style.display = 'block';
            document.querySelector('#pessoa-status').textContent = 'Cadastrando nova Pessoa Física';
        } else if (isPessoaJuridica) {
            camposPessoaFisica.style.display = 'none';
            conjugeSection.style.display = 'none';
            document.querySelector('#pessoa-status').textContent = 'Cadastrando nova Pessoa Jurídica';
        } else {
            // Mostrar campos de pessoa física por padrão
            camposPessoaFisica.style.display = 'block';
            conjugeSection.style.display = 'block';
            document.querySelector('#pessoa-status').textContent = 'Cadastrando nova pessoa (defina se é física ou jurídica)';
        }
    }
    
    // Preencher formulário com dados da pessoa
    function preencherFormulario(pessoa) {
        // Usar IDs reais do formulário
        const setValue = (id, value) => {
            const element = document.getElementById(id);
            if (element) {
                element.value = value || '';
            } else {
                console.error(`Elemento não encontrado: #${id}`);
            }
        };
        
        // Preencher campos com IDs reais
        setValue(window.FORM_IDS.pessoaId, pessoa.id);
        setValue(window.FORM_IDS.nome, pessoa.nome);
        
        // Preencher CPF/CNPJ
        if (pessoa.cpf) {
            setValue(window.FORM_IDS.searchTerm, pessoa.cpf);
        } else if (pessoa.cnpj) {
            setValue(window.FORM_IDS.searchTerm, pessoa.cnpj);
        }
        
        setValue(window.FORM_IDS.dataNascimento, pessoa.dataNascimento);
        setValue(window.FORM_IDS.nomePai, pessoa.nomePai);
        setValue(window.FORM_IDS.nomeMae, pessoa.nomeMae);
        setValue(window.FORM_IDS.renda, pessoa.renda);
        setValue(window.FORM_IDS.observacoes, pessoa.observacoes);
        
        // Preencher selects
        setSelectValue(window.FORM_IDS.estadoCivil, pessoa.estadoCivil);
        setSelectValue(window.FORM_IDS.nacionalidade, pessoa.nacionalidade);
        setSelectValue(window.FORM_IDS.naturalidade, pessoa.naturalidade);
        
        // Configurar interface baseada no tipo de pessoa
        const isPessoaFisica = pessoa.fisicaJuridica === 'fisica';
        camposPessoaFisica.style.display = isPessoaFisica ? 'block' : 'none';
        conjugeSection.style.display = isPessoaFisica ? 'block' : 'none';
        
        document.querySelector('#pessoa-status').textContent = 
            isPessoaFisica ? 'Pessoa Física encontrada' : 'Pessoa Jurídica encontrada';
    }
    
    // Limpar formulário
    function limparFormulario() {
        // Usar IDs corretos do window.FORM_IDS
        const setValue = (id, value) => {
            const element = document.getElementById(id);
            if (element) {
                element.value = value || '';
            }
        };
        
        setValue(window.FORM_IDS.pessoaId, '');
        setValue(window.FORM_IDS.nome, '');
        setValue(window.FORM_IDS.searchTerm, '');
        setValue(window.FORM_IDS.dataNascimento, '');
        setValue(window.FORM_IDS.nomePai, '');
        setValue(window.FORM_IDS.nomeMae, '');
        setValue(window.FORM_IDS.renda, '');
        setValue(window.FORM_IDS.observacoes, '');
        
        // Limpar selects
        const setSelectValue = (id, value) => {
            const element = document.getElementById(id);
            if (element) {
                element.value = value || '';
            }
        };
        
        setSelectValue(window.FORM_IDS.estadoCivil, '');
        setSelectValue(window.FORM_IDS.nacionalidade, '');
        setSelectValue(window.FORM_IDS.naturalidade, '');
        
        // Mostrar todos os campos por padrão
        camposPessoaFisica.style.display = 'block';
        conjugeSection.style.display = 'block';
        document.querySelector('#pessoa-status').textContent = 'Cadastrando nova pessoa';
    }
    
    // Função auxiliar para selects
    function setSelectValue(elementId, value) {
        const select = document.getElementById(elementId);
        if (select && value) {
            select.value = value;
        }
    }
    
    // Mostrar erro
    function showError(message) {
        searchResults.style.display = 'block';
        searchMessage.textContent = message;
        resultsList.innerHTML = '';
        
        // SEMPRE abrir o formulário e preencher com os dados da busca, mesmo com erro de conexão
        const criteria = searchCriteria.value;
        const value = searchValue.value;
        const additionalDoc = additionalDocumentValue.value;
        
        console.log('Erro na busca, mas preenchendo formulário com:', {criteria, value, additionalDoc});
        
        if (value) { // Se tem valor na busca, preencher formulário
            mainForm.style.display = 'block';
            limparFormulario();
            preencherDadosBusca();
            
            // Atualizar mensagem para ser mais útil
            if ((criteria === 'Nome Completo' || criteria === 'nome') && additionalDoc) {
                searchMessage.textContent = message + ' Os dados informados foram preenchidos no formulário para cadastro.';
                searchMessage.className = 'alert alert-warning';
            } else if (value) {
                searchMessage.textContent = message + ' Os dados da busca foram preenchidos no formulário para cadastro.';
                searchMessage.className = 'alert alert-warning';
            } else {
                searchMessage.className = 'alert alert-danger';
            }
        } else {
            searchMessage.className = 'alert alert-danger';
        }
    }
    
    // Controle do cônjuge
    temConjuge.addEventListener('change', function() {
        camposConjuge.style.display = this.checked ? 'block' : 'none';
    });
    
    // Funções utilitárias compartilhadas entre módulos
    window.carregarTipos = async function(entidade) {
        try {
            // URL corrigida usando rota nomeada
            const url = window.ROUTES.loadTipos.replace('ENTIDADE', entidade);
            const response = await fetch(url);
            const data = await response.json();
            return data.tipos || [];
        } catch (error) {
            console.error(`Erro ao carregar tipos de ${entidade}:`, error);
            return [];
        }
    };

    window.criarSelectTipos = function(tipos, name, id, onNovoTipo) {
        const options = tipos.map(tipo => `<option value="${tipo.id}">${tipo.tipo}</option>`).join('');
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
    
    // === SALVAMENTO DE NOVOS TIPOS ===
    window.salvarNovoTipo = async function(entidade, valor, callback) {
        try {
            const url = window.ROUTES.salvarTipo.replace('PLACEHOLDER', entidade);
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ tipo: valor })
            });
            
            const data = await response.json();
            
            if (data.success) {
                callback(data.tipo);
                return true;
            } else {
                alert(`Erro: ${data.message || 'Falha ao salvar o tipo'}`);
                return false;
            }
        } catch (error) {
            console.error('Erro ao salvar tipo:', error);
            if (error.message.includes('NetworkError')) {
                alert('Erro de rede. Verifique sua conexão com a internet.');
            } else {
                alert('Erro no servidor. Tente novamente mais tarde.');
            }
            return false;
        }
    };
});
