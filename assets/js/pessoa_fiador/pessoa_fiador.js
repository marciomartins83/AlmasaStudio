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
    
    // ===== FUNCIONALIDADES PARA MÚLTIPLOS ITENS =====
    
    // Contadores para IDs únicos
    let contadorTelefone = 0;
    let contadorEndereco = 0; 
    let contadorEmail = 0;
    let contadorPix = 0;
    let contadorDocumento = 0;
    
    // Função para carregar tipos via AJAX
    async function carregarTipos(entidade) {
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
    }

    // Função para criar select de tipos
    function criarSelectTipos(tipos, name, id, onNovoTipo) {
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
    }
    
    // === TELEFONES ===
    document.getElementById('add-telefone').addEventListener('click', async function() {
        // Carregar tipos apenas quando necessário
        const tipos = window.tiposTelefone || await carregarTipos('telefone');
        window.tiposTelefone = tipos; // Armazenar para uso futuro
        contadorTelefone++;
        const container = document.getElementById('telefones-container');
        
        if (container.querySelector('.text-muted')) {
            container.innerHTML = '';
        }
        
        const telefoneHtml = `
            <div class="border p-3 mb-3 telefone-item" data-index="${contadorTelefone}">
                <div class="row align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Tipo de Telefone</label>
                        ${criarSelectTipos(tipos, `telefones[${contadorTelefone}][tipo]`, `telefone_tipo_${contadorTelefone}`, `abrirModalTipoTelefone(${contadorTelefone})`)}
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Número</label>
                        <input type="text" class="form-control" name="telefones[${contadorTelefone}][numero]" placeholder="(11) 99999-9999" required>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger btn-sm w-100" onclick="removerTelefone(${contadorTelefone})">
                            <i class="fas fa-trash"></i> Remover
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        container.insertAdjacentHTML('beforeend', telefoneHtml);
    });
    
    window.removerTelefone = function(index) {
        const item = document.querySelector(`.telefone-item[data-index="${index}"]`);
        if (item) {
            item.remove();
            const container = document.getElementById('telefones-container');
            if (container.children.length === 0) {
                container.innerHTML = '<p class="text-muted">Nenhum telefone adicionado.</p>';
            }
        }
    };
    
    window.abrirModalTipoTelefone = function(index) {
        window.telefoneIndexAtual = index;
        new bootstrap.Modal(document.getElementById('modalNovoTipoTelefone')).show();
    };
    
    // === ENDEREÇOS ===
    document.getElementById('add-endereco').addEventListener('click', async function() {
        // Garantir que os tipos sejam sempre carregados
        const tipos = window.tiposEndereco || await carregarTipos('endereco');
        window.tiposEndereco = tipos; // Armazenar para uso futuro
        contadorEndereco++;
        const container = document.getElementById('enderecos-container');
        
        if (container.querySelector('.text-muted')) {
            container.innerHTML = '';
        }
        
        const enderecoHtml = `
            <div class="border p-3 mb-3 endereco-item" data-index="${contadorEndereco}">
                <input type="hidden" class="estado-field" name="enderecos[${contadorEndereco}][estado]">
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-label">Tipo de Endereço</label>
                        ${criarSelectTipos(tipos, `enderecos[${contadorEndereco}][tipo]`, `endereco_tipo_${contadorEndereco}`, `abrirModalTipoEndereco(${contadorEndereco})`)}
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">CEP</label>
                        <input type="text" class="form-control cep-input" 
                               name="enderecos[${contadorEndereco}][cep]" 
                               placeholder="00000-000" 
                               maxlength="9"
                               oninput="this.value = this.value.replace(/\\D/g, '').replace(/^(\\d{5})(\\d)/, '$1-$2')"
                               onblur="buscarEnderecoPorCEP(this)"
                               required>
                        <div class="form-text">Digite 8 dígitos</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Logradouro</label>
                        <input type="text" class="form-control logradouro-field" name="enderecos[${contadorEndereco}][logradouro]" placeholder="Rua, Avenida..." required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Número</label>
                        <input type="text" class="form-control" name="enderecos[${contadorEndereco}][numero]" placeholder="123" required>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-4">
                        <label class="form-label">Complemento</label>
                        <input type="text" class="form-control" name="enderecos[${contadorEndereco}][complemento]" placeholder="Apto, Sala...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Bairro</label>
                        <input type="text" class="form-control bairro-field" name="enderecos[${contadorEndereco}][bairro]" placeholder="Nome do bairro" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Cidade</label>
                        <input type="text" class="form-control cidade-field" name="enderecos[${contadorEndereco}][cidade]" placeholder="Nome da cidade" required>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger btn-sm w-100 mt-4" onclick="removerEndereco(${contadorEndereco})">
                            <i class="fas fa-trash"></i> Remover
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        container.insertAdjacentHTML('beforeend', enderecoHtml);
    });
    
    window.removerEndereco = function(index) {
        const item = document.querySelector(`.endereco-item[data-index="${index}"]`);
        if (item) {
            item.remove();
            const container = document.getElementById('enderecos-container');
            if (container.children.length === 0) {
                container.innerHTML = '<p class="text-muted">Nenhum endereço adicionado.</p>';
            }
        }
    };
    
    window.abrirModalTipoEndereco = function(index) {
        window.enderecoIndexAtual = index;
        new bootstrap.Modal(document.getElementById('modalNovoTipoEndereco')).show();
    };
    
    // === EMAILS ===
    document.getElementById('add-email').addEventListener('click', async function() {
        // Garantir que os tipos sejam sempre carregados
        const tipos = window.tiposEmail || await carregarTipos('email');
        window.tiposEmail = tipos; // Armazenar para uso futuro
        contadorEmail++;
        const container = document.getElementById('emails-container');
        
        if (container.querySelector('.text-muted')) {
            container.innerHTML = '';
        }
        
        const emailHtml = `
            <div class="border p-3 mb-3 email-item" data-index="${contadorEmail}">
                <div class="row align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Tipo de Email</label>
                        ${criarSelectTipos(tipos, `emails[${contadorEmail}][tipo]`, `email_tipo_${contadorEmail}`, `abrirModalTipoEmail(${contadorEmail})`)}
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="emails[${contadorEmail}][email]" placeholder="exemplo@email.com" required>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger btn-sm w-100" onclick="removerEmail(${contadorEmail})">
                            <i class="fas fa-trash"></i> Remover
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        container.insertAdjacentHTML('beforeend', emailHtml);
    });
    
    window.removerEmail = function(index) {
        const item = document.querySelector(`.email-item[data-index="${index}"]`);
        if (item) {
            item.remove();
            const container = document.getElementById('emails-container');
            if (container.children.length === 0) {
                container.innerHTML = '<p class="text-muted">Nenhum email adicionado.</p>';
            }
        }
    };
    
    window.abrirModalTipoEmail = function(index) {
        window.emailIndexAtual = index;
        new bootstrap.Modal(document.getElementById('modalNovoTipoEmail')).show();
    };
    
    // === CHAVES PIX ===
    document.getElementById('add-pix').addEventListener('click', async function() {
        // Garantir que os tipos sejam sempre carregados
        const tipos = window.tiposChavePix || await carregarTipos('chave-pix');
        window.tiposChavePix = tipos; // Armazenar para uso futuro
        contadorPix++;
        const container = document.getElementById('pix-container');
        
        if (container.querySelector('.text-muted')) {
            container.innerHTML = '';
        }
        
        const pixHtml = `
            <div class="border p-3 mb-3 pix-item" data-index="${contadorPix}">
                <div class="row align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Tipo de Chave</label>
                        ${criarSelectTipos(tipos, `chaves_pix[${contadorPix}][tipo]`, `pix_tipo_${contadorPix}`, `abrirModalTipoChavePix(${contadorPix})`)}
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Chave PIX</label>
                        <input type="text" class="form-control" name="chaves_pix[${contadorPix}][chave]" placeholder="Digite a chave PIX" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Principal</label>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" name="chaves_pix[${contadorPix}][principal]" value="1">
                            <label class="form-check-label">Principal</label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger btn-sm w-100" onclick="removerPix(${contadorPix})">
                            <i class="fas fa-trash"></i> Remover
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        container.insertAdjacentHTML('beforeend', pixHtml);
    });
    
    window.removerPix = function(index) {
        const item = document.querySelector(`.pix-item[data-index="${index}"]`);
        if (item) {
            item.remove();
            const container = document.getElementById('pix-container');
            if (container.children.length === 0) {
                container.innerHTML = '<p class="text-muted">Nenhuma chave PIX adicionada.</p>';
            }
        }
    };
    window.abrirModalTipoChavePix = function(index) {
        window.pixIndexAtual = index;
        new bootstrap.Modal(document.getElementById('modalNovoTipoChavePix')).show();
    };

    // === DOCUMENTOS ===
    document.getElementById('add-documento').addEventListener('click', async function() {
        const tipos = window.tiposDocumento || await carregarTipos('documento');
        window.tiposDocumento = tipos;
        contadorDocumento++;
        const container = document.getElementById('documentos-container');
        
        if (container.querySelector('.text-muted')) {
            container.innerHTML = '';
        }
        
        const documentoHtml = `
            <div class="border p-3 mb-3 documento-item" data-index="${contadorDocumento}">
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-label">Tipo de Documento</label>
                        ${criarSelectTipos(tipos, `documentos[${contadorDocumento}][tipo]`, `documento_tipo_${contadorDocumento}`, `abrirModalTipoDocumento(${contadorDocumento})`)}
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Número do Documento</label>
                        <input type="text" class="form-control" name="documentos[${contadorDocumento}][numero]" placeholder="Número do documento" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Órgão Emissor</label>
                        <input type="text" class="form-control" name="documentos[${contadorDocumento}][orgao_emissor]" placeholder="Ex: SSP-SP">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Data de Emissão</label>
                        <input type="date" class="form-control" name="documentos[${contadorDocumento}][data_emissao]">
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-3">
                        <label class="form-label">Data de Vencimento</label>
                        <input type="date" class="form-control" name="documentos[${contadorDocumento}][data_vencimento]">
                    </div>
                    <div class="col-md-7">
                        <label class="form-label">Observações</label>
                        <input type="text" class="form-control" name="documentos[${contadorDocumento}][observacoes]" placeholder="Observações sobre o documento">
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger btn-sm w-100 mt-4" onclick="removerDocumento(${contadorDocumento})">
                            <i class="fas fa-trash"></i> Remover
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        container.insertAdjacentHTML('beforeend', documentoHtml);
    });
    
    window.removerDocumento = function(index) {
        const item = document.querySelector(`.documento-item[data-index="${index}"]`);
        if (item) {
            item.remove();
            const container = document.getElementById('documentos-container');
            if (container.children.length === 0) {
                container.innerHTML = '<p class="text-muted">Nenhum documento adicionado.</p>';
            }
        }
    };
    
    window.abrirModalTipoDocumento = function(index) {
        window.documentoIndexAtual = index;
        new bootstrap.Modal(document.getElementById('modalNovoTipoDocumento')).show();
    };
    
    // === SALVAMENTO DE NOVOS TIPOS ===
    async function salvarNovoTipo(entidade, valor, callback) {
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
    }

    // Event listeners para salvamento de tipos
    document.getElementById('salvarTipoTelefone').addEventListener('click', async function() {
        const valor = document.getElementById('novoTipoTelefone').value.trim();
        if (!valor) {
            alert('Digite o nome do tipo de telefone');
            return;
        }
        
        await salvarNovoTipo('telefone', valor, (novoTipo) => {
            const select = document.getElementById(`telefone_tipo_${window.telefoneIndexAtual}`);
            const option = new Option(novoTipo.tipo, novoTipo.id, true, true);
            select.add(option);
            bootstrap.Modal.getInstance(document.getElementById('modalNovoTipoTelefone')).hide();
            document.getElementById('novoTipoTelefone').value = '';
        });
    });

    document.getElementById('salvarTipoEndereco').addEventListener('click', async function() {
        const valor = document.getElementById('novoTipoEndereco').value.trim();
        if (!valor) {
            alert('Digite o nome do tipo de endereço');
            return;
        }
        
        await salvarNovoTipo('endereco', valor, (novoTipo) => {
            const select = document.getElementById(`endereco_tipo_${window.enderecoIndexAtual}`);
            const option = new Option(novoTipo.tipo, novoTipo.id, true, true);
            select.add(option);
            bootstrap.Modal.getInstance(document.getElementById('modalNovoTipoEndereco')).hide();
            document.getElementById('novoTipoEndereco').value = '';
        });
    });

    document.getElementById('salvarTipoEmail').addEventListener('click', async function() {
        const valor = document.getElementById('novoTipoEmail').value.trim();
        if (!valor) {
            alert('Digite o nome do tipo de email');
            return;
        }
        
        await salvarNovoTipo('email', valor, (novoTipo) => {
            const select = document.getElementById(`email_tipo_${window.emailIndexAtual}`);
            const option = new Option(novoTipo.tipo, novoTipo.id, true, true);
            select.add(option);
            bootstrap.Modal.getInstance(document.getElementById('modalNovoTipoEmail')).hide();
            document.getElementById('novoTipoEmail').value = '';
        });
    });

    document.getElementById('salvarTipoChavePix').addEventListener('click', async function() {
        const valor = document.getElementById('novoTipoChavePix').value.trim();
        if (!valor) {
            alert('Digite o nome do tipo de chave PIX');
            return;
        }
        
        await salvarNovoTipo('chave-pix', valor, (novoTipo) => {
            const select = document.getElementById(`pix_tipo_${window.pixIndexAtual}`);
            const option = new Option(novoTipo.tipo, novoTipo.id, true, true);
            select.add(option);
            bootstrap.Modal.getInstance(document.getElementById('modalNovoTipoChavePix')).hide();
            document.getElementById('novoTipoChavePix').value = '';
        });
    });

    document.getElementById('salvarTipoDocumento').addEventListener('click', async function() {
        const valor = document.getElementById('novoTipoDocumento').value.trim();
        if (!valor) {
            alert('Digite o nome do tipo de documento');
            return;
        }
        
        await salvarNovoTipo('documento', valor, (novoTipo) => {
            const select = document.getElementById(`documento_tipo_${window.documentoIndexAtual}`);
            const option = new Option(novoTipo.tipo, novoTipo.id, true, true);
            select.add(option);
            bootstrap.Modal.getInstance(document.getElementById('modalNovoTipoDocumento')).hide();
            document.getElementById('novoTipoDocumento').value = '';
        });
    });

    // === BUSCA DE CEP ===
    window.buscarEnderecoPorCEP = async function(input) {
        try {
            const cep = input.value.replace(/\D/g, '');
            console.log('CEP digitado:', cep);
            
            if (cep.length !== 8) {
                if (cep.length > 0) {
                    alert('CEP inválido. Deve conter 8 dígitos.');
                }
                return;
            }

            const addressBlock = input.closest('.endereco-item');
            if (!addressBlock) {
                console.error('Não foi possível encontrar o bloco de endereço');
                return;
            }

            const inputs = addressBlock.querySelectorAll('input');
            inputs.forEach(i => i.disabled = true);
            input.classList.add('loading');

            const response = await fetch(window.ROUTES.buscarCep, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ cep: cep })
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();
            console.log('Resposta do servidor:', data);

            if (data.success) {
                addressBlock.querySelector('.logradouro-field').value = data.logradouro || '';
                addressBlock.querySelector('.bairro-field').value = data.bairro || '';
                addressBlock.querySelector('.cidade-field').value = data.cidade || '';
                addressBlock.querySelector('.estado-field').value = data.estado || '';
            }

            inputs.forEach(i => i.disabled = false);
            input.classList.remove('loading');
        } catch (error) {
            console.error('Erro na busca de CEP:', error);
            const inputs = input.closest('.endereco-item').querySelectorAll('input');
            inputs.forEach(i => i.disabled = false);
            input.classList.remove('loading');
            
            let errorMessage = 'Erro ao buscar CEP. Verifique o valor digitado.';
            if (error.message.includes('Failed to fetch')) {
                errorMessage = 'Erro de conexão. Verifique sua internet.';
            }
            alert(errorMessage);
        }
    }

    // === BUSCA DE CÔNJUGE ===
    const conjugeSearch = document.getElementById('conjuge-search');
    const btnSearchConjuge = document.getElementById('btn-search-conjuge');
    const btnNewConjuge = document.getElementById('btn-new-conjuge');
    const conjugeResults = document.getElementById('conjuge-results');
    const conjugeField = document.getElementById(window.FORM_IDS.conjuge || 'conjuge_field');

    if (conjugeSearch && btnSearchConjuge && btnNewConjuge && conjugeResults) {
        btnSearchConjuge.addEventListener('click', async function() {
            const termo = conjugeSearch.value.trim();
            if (!termo || termo.length < 3) {
                alert('Digite pelo menos 3 caracteres para buscar');
                return;
            }

            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Buscando...';
            this.disabled = true;

            try {
                const response = await fetch(window.ROUTES.searchConjuge, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ termo: termo })
                });

                const data = await response.json();

                if (data.success && data.pessoas.length > 0) {
                    conjugeResults.innerHTML = data.pessoas.map(pessoa => `
                        <div class="card mb-2">
                            <div class="card-body">
                                <h5 class="card-title">${pessoa.nome}</h5>
                                <p class="card-text">CPF: ${pessoa.cpf || 'Não informado'}</p>
                                <button type="button" class="btn btn-sm btn-primary selecionar-conjuge" 
                                    data-id="${pessoa.id}" data-nome="${pessoa.nome}">
                                    Selecionar
                                </button>
                            </div>
                        </div>
                    `).join('');
                    conjugeResults.style.display = 'block';
                } else {
                    conjugeResults.innerHTML = '<div class="alert alert-info">Nenhum cônjuge encontrado</div>';
                    conjugeResults.style.display = 'block';
                }
            } catch (error) {
                console.error('Erro na busca de cônjuge:', error);
                conjugeResults.innerHTML = '<div class="alert alert-danger">Erro na busca</div>';
                conjugeResults.style.display = 'block';
            } finally {
                this.innerHTML = '<i class="fas fa-search"></i> Buscar';
                this.disabled = false;
            }
        });

        conjugeResults.addEventListener('click', function(e) {
            const btn = e.target.closest('.selecionar-conjuge');
            if (btn) {
                if (conjugeField) conjugeField.value = btn.dataset.id;
                conjugeSearch.value = btn.dataset.nome;
                conjugeResults.style.display = 'none';
            }
        });

        btnNewConjuge.addEventListener('click', function() {
            if (conjugeField) conjugeField.value = '';
            conjugeSearch.value = '';
            conjugeResults.style.display = 'none';
        });

        conjugeSearch.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                btnSearchConjuge.click();
            }
        });
    }

    // Adicionar estilo para carregamento de CEP
    const style = document.createElement('style');
    style.textContent = `
        .cep-input.loading {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23007bff' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M21 12a9 9 0 1 1-6.219-8.56'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 1rem;
            padding-right: 2.5rem;
        }
    `;
    document.head.appendChild(style);

    // Definir rotas globais
    window.ROUTES = {
        loadTipos: "{{ path('app_pessoa_fiador_load_tipos', {'entidade': 'ENTIDADE'}) }}",
        salvarTipo: "{{ path('app_pessoa_fiador_salvar_tipo', {'entidade': 'PLACEHOLDER'}) }}",
        buscarCep: "{{ path('app_pessoa_fiador_buscar_cep') }}",
        searchConjuge: "{{ path('app_pessoa_fiador_search_conjuge') }}"
    };
});