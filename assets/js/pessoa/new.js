document.addEventListener('DOMContentLoaded', () => {
    console.log('🚀 new.js carregado');
    
    // --- ELEMENTOS DO FORMULÁRIO ---
    const searchCriteriaSelect = document.getElementById('searchCriteria');
    const searchValueInput = document.getElementById('searchValue');
    const searchButton = document.getElementById('btn-search');
    const clearButton = document.getElementById('btn-clear');
    const searchResultsDiv = document.getElementById('search-results');
    const searchMessageContainer = document.getElementById('search-message').parentElement;
    const searchMessageSpan = document.getElementById('search-message');
    const mainFormDiv = document.getElementById('main-form');
    const additionalDocumentRow = document.getElementById('additionalDocumentRow');
    const additionalDocumentValue = document.getElementById('additionalDocumentValue');
    const camposPessoaFisica = document.getElementById('campos-pessoa-fisica');
    const conjugeSection = document.getElementById('conjuge-section');
    const temConjuge = document.getElementById('temConjuge');
    const camposConjuge = document.getElementById('campos-conjuge');

    // --- CONTROLE DO ACTION DO FORM ---
    function setFormActionToEdit(id) {
        const formEl = document.querySelector('#main-form form');
        if (formEl && window.ROUTES && window.ROUTES.editPessoa) {
            formEl.setAttribute('action', window.ROUTES.editPessoa.replace('__ID__', id));
            formEl.setAttribute('method', 'post');
            console.log('🛠️ Form action -> EDIT:', formEl.getAttribute('action'));
        } else {
            console.warn('⚠️ Não foi possível configurar action de edição.');
        }
    }

    function setFormActionToNew() {
        const formEl = document.querySelector('#main-form form');
        if (formEl && window.ROUTES && window.ROUTES.newPessoa) {
            formEl.setAttribute('action', window.ROUTES.newPessoa);
            formEl.setAttribute('method', 'post');
            console.log('🛠️ Form action -> NEW:', formEl.getAttribute('action'));
        } else {
            console.warn('⚠️ Não foi possível configurar action de criação.');
        }
    }

    // Verificar elementos críticos
    const elementosCriticos = {
        searchCriteriaSelect,
        searchValueInput, 
        searchButton,
        clearButton,
        searchResultsDiv,
        searchMessageSpan,
        mainFormDiv
    };
    
    const elementosFaltando = Object.entries(elementosCriticos)
        .filter(([nome, elemento]) => !elemento)
        .map(([nome]) => nome);
    
    if (elementosFaltando.length > 0) {
        console.error('❌ Elementos críticos não encontrados:', elementosFaltando);
        return;
    }
    
    console.log('✅ Todos os elementos críticos encontrados');

    // --- LÓGICA DA BUSCA INTELIGENTE ---
    if (searchCriteriaSelect && searchValueInput && searchButton && clearButton) {
        
        searchCriteriaSelect.addEventListener('change', () => {
            const selectedValue = searchCriteriaSelect.value;
            
            if (selectedValue) {
                searchValueInput.removeAttribute('disabled');
                searchValueInput.focus();
            } else {
                searchValueInput.setAttribute('disabled', 'disabled');
            }

            searchValueInput.value = '';
            const selectedOptionText = searchCriteriaSelect.options[searchCriteriaSelect.selectedIndex].text;
            searchValueInput.placeholder = selectedValue ? `Digite o ${selectedOptionText}` : 'Selecione um critério primeiro';
            searchButton.disabled = true;
            
            if (additionalDocumentRow) {
                additionalDocumentRow.style.display = selectedValue === 'nome' ? 'block' : 'none';
            }
        });

        searchValueInput.addEventListener('input', () => {
            const criteria = searchCriteriaSelect.value;
            let minLength = 0;
            
            switch(criteria) {
                case 'cpf': 
                    minLength = 11; 
                    break;
                case 'cnpj': 
                    minLength = 14; 
                    break;
                case 'nome': 
                    minLength = 3; 
                    break;
                case 'id': 
                    minLength = 1; 
                    break;
            }
            
            searchButton.disabled = searchValueInput.value.trim().length < minLength;
        });

        searchButton.addEventListener('click', async () => {
            const criteria = searchCriteriaSelect.value;
            const value = searchValueInput.value.trim();
            const additionalDoc = additionalDocumentValue ? additionalDocumentValue.value.trim() : null;
            const additionalDocType = document.getElementById('additionalDocumentType') ? 
                                    document.getElementById('additionalDocumentType').value : null;
            
            if (!value) {
                return;
            }

            searchButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Buscando...';
            searchButton.disabled = true;

            try {
                const response = await fetch(window.ROUTES.searchPessoa, {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json', 
                        'X-Requested-With': 'XMLHttpRequest' 
                    },
                    body: JSON.stringify({ 
                        criteria, 
                        value,
                        additionalDoc,
                        additionalDocType 
                    })
                });

                let data;
                const contentType = response.headers.get('content-type') || '';
                if (contentType.includes('application/json')) {
                    data = await response.json();
                } else {
                    const text = await response.text();
                    console.error('Resposta não-JSON:', text);
                    throw new Error('Resposta inválida do servidor');
                }

                if (searchResultsDiv) {
                    searchResultsDiv.style.display = 'block';
                }
                
                if (mainFormDiv) {
                    mainFormDiv.style.display = 'block';
                }

                if (data.success && data.pessoa) {
                    if (searchMessageContainer) {
                        searchMessageContainer.className = 'alert alert-success';
                    }
                    if (searchMessageSpan) {
                        searchMessageSpan.textContent = 'Pessoa encontrada! Formulário preenchido.';
                    }
                    preencherFormulario(data.pessoa);
                    
                    if (data.pessoa && data.pessoa.tiposDados) {
                        const tiposAtivos = Object.keys(data.pessoa.tipos).filter(tipo => data.pessoa.tipos[tipo]);
                        tiposAtivos.forEach(tipo => {
                            const intervaloAguarda = setInterval(() => {
                                const card = document.getElementById(`campos-${tipo}`);
                                if (card && data.pessoa.tiposDados[tipo]) {
                                    clearInterval(intervaloAguarda);
                                    preencheSubForm(tipo, data.pessoa.tiposDados[tipo]);
                                }
                            }, 50);
                        });
                    }
                } else {
                    if (searchMessageContainer) {
                        searchMessageContainer.className = 'alert alert-info';
                    }
                    if (searchMessageSpan) {
                        searchMessageSpan.textContent = `Nenhuma pessoa encontrada para "${value}". Você pode prosseguir com o novo cadastro.`;
                    }
                    preencherDadosBusca(criteria, value, additionalDoc, additionalDocType);
                }

            } catch (error) {
                console.error('Erro na busca:', error);
                
                if (searchResultsDiv) {
                    searchResultsDiv.style.display = 'block';
                }
                
                if (searchMessageContainer) {
                    searchMessageContainer.className = 'alert alert-danger';
                }
                
                if (searchMessageSpan) {
                    searchMessageSpan.textContent = 'Erro ao realizar a busca. Tente novamente.';
                }
                
                if (mainFormDiv) {
                    mainFormDiv.style.display = 'block';
                }
                
            } finally {
                searchButton.innerHTML = '<i class="fas fa-search"></i> Buscar';
                searchButton.disabled = false;
            }
        });
        
        clearButton.addEventListener('click', () => {
            searchCriteriaSelect.value = '';
            searchValueInput.value = '';
            searchValueInput.setAttribute('disabled', 'disabled');
            searchButton.disabled = true;
            
            if (additionalDocumentValue) {
                additionalDocumentValue.value = '';
            }
            
            if (additionalDocumentRow) {
                additionalDocumentRow.style.display = 'none';
            }
            
            if (searchResultsDiv) {
                searchResultsDiv.style.display = 'none';
            }
            
            if (mainFormDiv) {
                mainFormDiv.style.display = 'none';
            }

            const tiposContainer = document.getElementById('tipos-pessoa-container');
            if (tiposContainer) {
                tiposContainer.innerHTML = '<p class="text-muted">Nenhum tipo selecionado. Adicione pelo menos um tipo de pessoa.</p>';
            }

            setFormActionToNew();
        });
    }
    
    // --- CONTROLE DO CÔNJUGE ---
    if (temConjuge && camposConjuge) {
        temConjuge.addEventListener('change', function() {
            camposConjuge.style.display = this.checked ? 'block' : 'none';
        });
    }
    
    // --- FUNÇÕES AUXILIARES ---
    function preencherDadosBusca(criteria, value, additionalDoc, additionalDocType) {
        setFormActionToNew();

        console.log('📝 Preenchendo dados da busca:', { criteria, value, additionalDoc, additionalDocType });
        
        window.setFormValue(window.FORM_IDS.pessoaId, '');
        
        const pessoaStatus = document.querySelector('#pessoa-status');
        if (pessoaStatus) {
            pessoaStatus.textContent = 'Nova pessoa - será cadastrada';
            pessoaStatus.classList.remove('text-warning');
            pessoaStatus.classList.add('text-success');
        }
        
        switch(criteria) {
            case 'cpf':
                window.setFormValue(window.FORM_IDS.searchTerm, value);
                configurarTipoPessoa('fisica');
                break;
                
            case 'cnpj':
                window.setFormValue(window.FORM_IDS.searchTerm, value);
                configurarTipoPessoa('juridica');
                break;
                
            case 'nome':
                window.setFormValue(window.FORM_IDS.nome, value);
                
                if (additionalDoc) {
                    window.setFormValue(window.FORM_IDS.searchTerm, additionalDoc);
                    const tipoDocumento = additionalDocType === 'cpf' ? 'fisica' : 'juridica';
                    configurarTipoPessoa(tipoDocumento);
                }
                break;
                
            case 'id':
                console.log('ID não encontrado:', value);
                break;
        }
        
        limparContainersDadosMultiplos();
        limparDadosConjuge();
        
        const tiposContainer = document.getElementById('tipos-pessoa-container');
        if (tiposContainer) {
            tiposContainer.innerHTML = '<p class="text-muted">Nenhum tipo selecionado. Adicione pelo menos um tipo de pessoa.</p>';
        }
    }

    function limparDadosConjuge() {
        console.log('🧹 Limpando dados do cônjuge');
        
        const temConjugeCheckbox = document.getElementById('temConjuge');
        if (temConjugeCheckbox) {
            temConjugeCheckbox.checked = false;
            
            const changeEvent = new Event('change', { bubbles: true });
            temConjugeCheckbox.dispatchEvent(changeEvent);
        }
        
        const camposConjuge = document.querySelectorAll('[name^="novo_conjuge["], [name^="conjuge_"]');
        camposConjuge.forEach(campo => {
            if (campo.type === 'checkbox') {
                campo.checked = false;
            } else {
                campo.value = '';
            }
        });
        
        const containersConjuge = [
            'conjuge-telefones-container',
            'conjuge-enderecos-container',
            'conjuge-emails-container',
            'conjuge-documentos-container',
            'conjuge-pix-container',
            'conjuge-profissoes-container'
        ];
        
        containersConjuge.forEach(containerId => {
            const container = document.getElementById(containerId);
            if (container) {
                container.innerHTML = '<p class="text-muted">Nenhum item adicionado.</p>';
            }
        });
        
        if (typeof window.contadorConjugeTelefone !== 'undefined') {
            window.contadorConjugeTelefone = 0;
        }
        if (typeof window.contadorConjugeEndereco !== 'undefined') {
            window.contadorConjugeEndereco = 0;
        }
        if (typeof window.contadorConjugeEmail !== 'undefined') {
            window.contadorConjugeEmail = 0;
        }
        if (typeof window.contadorConjugeDocumento !== 'undefined') {
            window.contadorConjugeDocumento = 0;
        }
        if (typeof window.contadorConjugeChavePix !== 'undefined') {
            window.contadorConjugeChavePix = 0;
        }
        if (typeof window.contadorConjugeProfissao !== 'undefined') {
            window.contadorConjugeProfissao = 0;
        }
    }
    
    function preencherFormulario(pessoa) {
        console.log('📝 Preenchendo formulário com pessoa encontrada:', pessoa);
        
        const pessoaStatus = document.querySelector('#pessoa-status');
        if (pessoaStatus) {
            pessoaStatus.textContent = `Pessoa existente (ID: ${pessoa.id}) - será atualizada`;
            pessoaStatus.classList.remove('text-success');
            pessoaStatus.classList.add('text-warning');
        }

        window.setFormValue(window.FORM_IDS.pessoaId, pessoa.id);
        setFormActionToEdit(pessoa.id);

        window.setFormValue(window.FORM_IDS.nome, pessoa.nome);
        window.setFormValue(window.FORM_IDS.searchTerm, pessoa.cpf || pessoa.cnpj || '');
        window.setFormValue(window.FORM_IDS.dataNascimento, pessoa.dataNascimento || '');
        window.setFormValue(window.FORM_IDS.nomePai, pessoa.nomePai || '');
        window.setFormValue(window.FORM_IDS.nomeMae, pessoa.nomeMae || '');
        window.setFormValue(window.FORM_IDS.renda, pessoa.renda || '');
        window.setFormValue(window.FORM_IDS.observacoes, pessoa.observacoes || '');

        window.setSelectValue(window.FORM_IDS.estadoCivil, pessoa.estadoCivil || '');
        window.setSelectValue(window.FORM_IDS.nacionalidade, pessoa.nacionalidade || '');
        window.setSelectValue(window.FORM_IDS.naturalidade, pessoa.naturalidade || '');

        const tipoFisicaJuridica = pessoa.fisicaJuridica || (pessoa.cpf ? 'fisica' : 'juridica');
        configurarTipoPessoa(tipoFisicaJuridica);

        if (pessoa.tipos) {
            const tiposContainer = document.getElementById('tipos-pessoa-container');
            if (tiposContainer) {
                tiposContainer.innerHTML = '';
            }
            
            const tiposAtivos = {};
            Object.entries(pessoa.tipos).forEach(([tipo, ativo]) => {
                if (ativo) {
                    tiposAtivos[tipo] = pessoa.tiposDados ? pessoa.tiposDados[tipo] : true;
                }
            });
            
            if (typeof window.carregarTiposExistentes === 'function') {
                window.carregarTiposExistentes(tiposAtivos);
            }
        }

        carregarDadosMultiplos(pessoa);

        if (pessoa.conjuge) {
            carregarDadosConjuge(pessoa.conjuge);
        }
    }

    function limparContainersDadosMultiplos() {
        console.log('🧹 Limpando containers de dados múltiplos');

        const containers = [
            'telefones-container',
            'enderecos-container',
            'emails-container',
            'documentos-container',
            'pix-container',
            'profissoes-container'
        ];

        containers.forEach(containerId => {
            const container = document.getElementById(containerId);
            if (container) {
                if (container.children.length === 0 || container.querySelector('.text-muted')) {
                    container.innerHTML = '<p class="text-muted">Nenhum item adicionado.</p>';
                }
            }
        });

        if (typeof window.contadorTelefone !== 'undefined') {
            window.contadorTelefone = 0;
        }
        if (typeof window.contadorEndereco !== 'undefined') {
            window.contadorEndereco = 0;
        }
        if (typeof window.contadorEmail !== 'undefined') {
            window.contadorEmail = 0;
        }
        if (typeof window.contadorDocumento !== 'undefined') {
            window.contadorDocumento = 0;
        }
        if (typeof window.contadorChavePix !== 'undefined') {
            window.contadorChavePix = 0;
        }
        if (typeof window.contadorProfissao !== 'undefined') {
            window.contadorProfissao = 0;
        }
    }

    function carregarDadosConjuge(conjuge) {
        console.log('👫 Carregando dados do cônjuge:', conjuge);
        
        const temConjugeCheckbox = document.getElementById('temConjuge');
        if (temConjugeCheckbox) {
            temConjugeCheckbox.checked = true;
            
            const changeEvent = new Event('change', { bubbles: true });
            temConjugeCheckbox.dispatchEvent(changeEvent);
        }
        
        if (typeof conjuge === 'number' || typeof conjuge === 'string') {
            const conjugeIdInput = document.querySelector('input[name="conjuge_id"]');
            if (conjugeIdInput) {
                conjugeIdInput.value = conjuge;
            }
            return;
        }
        
        if (typeof conjuge === 'object' && conjuge !== null) {
            const conjugeIdInput = document.querySelector('input[name="conjuge_id"]');
            if (conjugeIdInput && conjuge.id) {
                conjugeIdInput.value = conjuge.id;
            }
            
            const campos = {
                'novo_conjuge[nome]': conjuge.nome,
                'novo_conjuge[cpf]': conjuge.cpf,
                'novo_conjuge[data_nascimento]': conjuge.dataNascimento,
                'novo_conjuge[estado_civil]': conjuge.estadoCivil,
                'novo_conjuge[nacionalidade]': conjuge.nacionalidade,
                'novo_conjuge[naturalidade]': conjuge.naturalidade,
                'novo_conjuge[nome_pai]': conjuge.nomePai,
                'novo_conjuge[nome_mae]': conjuge.nomeMae,
                'novo_conjuge[renda]': conjuge.renda,
                'novo_conjuge[observacoes]': conjuge.observacoes
            };
            
            Object.entries(campos).forEach(([name, value]) => {
                const input = document.querySelector(`[name="${name}"]`);
                if (input && value !== undefined && value !== null) {
                    input.value = value;
                }
            });
            
            carregarDadosMultiplosConjuge(conjuge);
            
            const camposConjugeForm = document.querySelectorAll('#campos-conjuge input, #campos-conjuge select, #campos-conjuge textarea');
            camposConjugeForm.forEach(campo => {
                campo.dataset.conjugeId = conjuge.id;
            });
        }
    }

    function carregarDadosMultiplos(pessoa) {
        console.log('📦 Carregando dados múltiplos da pessoa:', pessoa.id);
        
        limparContainersDadosMultiplos();
        
        if (pessoa.telefones && Array.isArray(pessoa.telefones)) {
            const telefonesContainer = document.getElementById('telefones-container');
            if (telefonesContainer && typeof window.adicionarTelefoneExistente === 'function') {
                pessoa.telefones.forEach(telefone => {
                    window.adicionarTelefoneExistente(telefone);
                });
            }
        }
        
        if (pessoa.enderecos && Array.isArray(pessoa.enderecos)) {
            const enderecosContainer = document.getElementById('enderecos-container');
            if (enderecosContainer && typeof window.adicionarEnderecoExistente === 'function') {
                pessoa.enderecos.forEach(endereco => {
                    window.adicionarEnderecoExistente(endereco);
                });
            }
        }
        
        if (pessoa.emails && Array.isArray(pessoa.emails)) {
            const emailsContainer = document.getElementById('emails-container');
            if (emailsContainer && typeof window.adicionarEmailExistente === 'function') {
                pessoa.emails.forEach(email => {
                    window.adicionarEmailExistente(email);
                });
            }
        }
        
        if (pessoa.documentos && Array.isArray(pessoa.documentos)) {
            const documentosContainer = document.getElementById('documentos-container');
            if (documentosContainer && typeof window.adicionarDocumentoExistente === 'function') {
                pessoa.documentos.forEach(documento => {
                    window.adicionarDocumentoExistente(documento);
                });
            }
        }
        
        if (pessoa.chavesPix && Array.isArray(pessoa.chavesPix)) {
            const pixContainer = document.getElementById('pix-container');
            if (pixContainer && typeof window.adicionarChavePixExistente === 'function') {
                pessoa.chavesPix.forEach(chavePix => {
                    window.adicionarChavePixExistente(chavePix);
                });
            }
        }
        
        if (pessoa.profissoes && Array.isArray(pessoa.profissoes)) {
            const profissoesContainer = document.getElementById('profissoes-container');
            if (profissoesContainer && typeof window.adicionarProfissaoExistente === 'function') {
                pessoa.profissoes.forEach(profissao => {
                    window.adicionarProfissaoExistente(profissao);
                });
            }
        }
    }

    function carregarDadosMultiplosConjuge(conjuge) {
        console.log('📦 Carregando dados múltiplos do cônjuge');
        
        if (conjuge.telefones && Array.isArray(conjuge.telefones)) {
            const container = document.getElementById('conjuge-telefones-container');
            if (container && typeof window.adicionarConjugeTelefoneExistente === 'function') {
                container.innerHTML = '';
                conjuge.telefones.forEach(telefone => {
                    window.adicionarConjugeTelefoneExistente(telefone);
                });
            }
        }
        
        if (conjuge.enderecos && Array.isArray(conjuge.enderecos)) {
            const container = document.getElementById('conjuge-enderecos-container');
            if (container && typeof window.adicionarConjugeEnderecoExistente === 'function') {
                container.innerHTML = '';
                conjuge.enderecos.forEach(endereco => {
                    window.adicionarConjugeEnderecoExistente(endereco);
                });
            }
        }
        
        if (conjuge.emails && Array.isArray(conjuge.emails)) {
            const container = document.getElementById('conjuge-emails-container');
            if (container && typeof window.adicionarConjugeEmailExistente === 'function') {
                container.innerHTML = '';
                conjuge.emails.forEach(email => {
                    window.adicionarConjugeEmailExistente(email);
                });
            }
        }
        
        if (conjuge.documentos && Array.isArray(conjuge.documentos)) {
            const container = document.getElementById('conjuge-documentos-container');
            if (container && typeof window.adicionarConjugeDocumentoExistente === 'function') {
                container.innerHTML = '';
                conjuge.documentos.forEach(documento => {
                    window.adicionarConjugeDocumentoExistente(documento);
                });
            }
        }
        
        if (conjuge.chavesPix && Array.isArray(conjuge.chavesPix)) {
            const container = document.getElementById('conjuge-pix-container');
            if (container && typeof window.adicionarConjugeChavePixExistente === 'function') {
                container.innerHTML = '';
                conjuge.chavesPix.forEach(chavePix => {
                    window.adicionarConjugeChavePixExistente(chavePix);
                });
            }
        }
        
        if (conjuge.profissoes && Array.isArray(conjuge.profissoes)) {
            const container = document.getElementById('conjuge-profissoes-container');
            if (container && typeof window.adicionarConjugeProfissaoExistente === 'function') {
                container.innerHTML = '';
                conjuge.profissoes.forEach(profissao => {
                    window.adicionarConjugeProfissaoExistente(profissao);
                });
            }
        }
    }
    
    function configurarTipoPessoa(tipo) {
        const isPessoaFisica = tipo === 'fisica';
        
        if (camposPessoaFisica) {
            camposPessoaFisica.style.display = isPessoaFisica ? 'block' : 'none';
        }
        
        if (conjugeSection) {
            conjugeSection.style.display = isPessoaFisica ? 'block' : 'none';
        }
        
        const pessoaStatus = document.querySelector('#pessoa-status');
        if (pessoaStatus) {
            const statusText = pessoaStatus.textContent;
            if (!statusText.includes('existente') && !statusText.includes('será')) {
                pessoaStatus.textContent = isPessoaFisica ? 'Pessoa Física' : 'Pessoa Jurídica';
            }
        }
    }

    function preencheSubForm(tipo, dados) {
        if (!dados) {
            console.log(`⚠️ Sem dados para preencher sub-form do tipo: ${tipo}`);
            return;
        }
        
        console.log(`📝 Preenchendo sub-form do tipo: ${tipo}`, dados);
        
        const prefixos = [
            `pessoa_form[${tipo}]`,
            `${tipo}`,
            `form[${tipo}]`
        ];
        
        Object.entries(dados).forEach(([campo, valor]) => {
            let input = null;
            
            for (const prefixo of prefixos) {
                const seletores = [
                    `[name="${prefixo}[${campo}]"]`,
                    `[id*="${tipo}_${campo}"]`,
                    `[id$="_${campo}"]`
                ];
                
                for (const seletor of seletores) {
                    input = document.querySelector(seletor);
                    if (input) {
                        console.log(`✅ Campo encontrado: ${seletor}`, valor);
                        break;
                    }
                }
                
                if (input) {
                    break;
                }
            }
            
            if (!input) {
                console.warn(`⚠️ Campo não encontrado para: ${campo}`, valor);
                return;
            }
            
            if (input.type === 'checkbox') {
                input.checked = !!valor;
            } else if (input.tagName === 'SELECT') {
                input.value = valor ?? '';
                input.dispatchEvent(new Event('change', { bubbles: true }));
            } else if (valor instanceof Date || campo.toLowerCase().includes('date') || campo.toLowerCase().includes('data')) {
                input.value = valor ? valor.split(' ')[0] : '';
            } else {
                input.value = valor ?? '';
            }
            
            console.log(`✅ Campo preenchido: ${campo} = ${valor}`);
        });
    }

    const formEl = document.querySelector('#main-form form');
    if (formEl) {
        formEl.addEventListener('submit', function(event) {
            if (typeof window.validarTiposPessoa === 'function') {
                if (!window.validarTiposPessoa()) {
                    event.preventDefault();
                    alert('Por favor, adicione pelo menos um tipo de pessoa.');
                    return false;
                }
            }
            
            const nomeInput = document.getElementById(window.FORM_IDS.nome);
            if (nomeInput && !nomeInput.value.trim()) {
                event.preventDefault();
                alert('O nome é obrigatório.');
                nomeInput.focus();
                return false;
            }
            
            const searchTermInput = document.getElementById(window.FORM_IDS.searchTerm);
            if (searchTermInput && !searchTermInput.value.trim()) {
                event.preventDefault();
                alert('CPF ou CNPJ é obrigatório.');
                searchTermInput.focus();
                return false;
            }
            
            return true;
        });
    }
    
    console.log('✅ new.js: Todas as funcionalidades configuradas');

    setFormActionToNew();
});