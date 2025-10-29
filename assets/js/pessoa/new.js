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
    const tipoPessoaSelect = document.querySelector('[id$="tipoPessoa"]'); // Busca por ID que termine com tipoPessoa
    const subFormContainer = document.getElementById('sub-form-container');
    const additionalDocumentRow = document.getElementById('additionalDocumentRow');
    const additionalDocumentValue = document.getElementById('additionalDocumentValue');
    const camposPessoaFisica = document.getElementById('campos-pessoa-fisica');
    const conjugeSection = document.getElementById('conjuge-section');
    const temConjuge = document.getElementById('temConjuge');
    const camposConjuge = document.getElementById('campos-conjuge');

    // Verificar elementos críticos
    const elementosCriticos = {
        searchCriteriaSelect,
        searchValueInput, 
        searchButton,
        clearButton,
        searchResultsDiv,
        searchMessageSpan,
        mainFormDiv,
        tipoPessoaSelect,
        subFormContainer
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
            
            // Mostrar/ocultar campo adicional de documento para busca por nome
            if (additionalDocumentRow) {
                additionalDocumentRow.style.display = selectedValue === 'nome' ? 'block' : 'none';
            }
        });

        searchValueInput.addEventListener('input', () => {
            const criteria = searchCriteriaSelect.value;
            let minLength = 0;
            
            switch(criteria) {
                case 'cpf': minLength = 11; break;
                case 'cnpj': minLength = 14; break;
                case 'nome': minLength = 3; break;
                case 'id': minLength = 1; break;
            }
            
            searchButton.disabled = searchValueInput.value.trim().length < minLength;
        });

        searchButton.addEventListener('click', async () => {
            const criteria = searchCriteriaSelect.value;
            const value = searchValueInput.value.trim();
            const additionalDoc = additionalDocumentValue ? additionalDocumentValue.value.trim() : null;
            const additionalDocType = document.getElementById('additionalDocumentType') ? 
                                    document.getElementById('additionalDocumentType').value : null;
            
            if (!value) return;

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

                // PROTEÇÃO CONTRA RESPOSTAS INESPERADAS
                let data;
                const contentType = response.headers.get('content-type') || '';
                if (contentType.includes('application/json')) {
                    data = await response.json();
                } else {
                    const text = await response.text();
                    console.error('Resposta não-JSON:', text);
                    throw new Error('Resposta inválida do servidor – veja o console.');
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
                        const ativos = Object.keys(data.pessoa.tipos).filter(t => data.pessoa.tipos[t]);
                        ativos.forEach(tipo => {
                            // aguarda o card ser inserido
                            const aguarda = setInterval(() => {
                                const card = document.getElementById(`campos-${tipo}`);
                                if (card && data.pessoa.tiposDados[tipo]) {
                                    clearInterval(aguarda);
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
                    let errorMessage = 'Erro ao realizar a busca. ';
                    if (error.message.includes('500')) {
                        errorMessage += 'Erro interno do servidor.';
                    } else if (error.message.includes('não é JSON válida')) {
                        errorMessage += 'Resposta inválida do servidor.';
                    } else {
                        errorMessage += 'Tente novamente.';
                    }
                    searchMessageSpan.textContent = errorMessage;
                }
                
                // Ainda assim abrir o formulário para cadastro
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
        });
    }

    // --- LÓGICA DO SUB-FORMULÁRIO DINÂMICO ---
    if (tipoPessoaSelect && subFormContainer) {
        const loadSubForm = async (tipo) => {
        console.log('Carregando sub-formulário para tipo:', tipo); // DEBUG
        
        if (!tipo) {
            subFormContainer.innerHTML = '';
            return;
        }
        
        subFormContainer.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Carregando...</div>';

        try {
            console.log('Fazendo requisição para:', window.ROUTES.subform); // DEBUG
            
            const response = await fetch(window.ROUTES.subform, {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/x-www-form-urlencoded', 
                    'X-Requested-With': 'XMLHttpRequest' 
                },
                body: new URLSearchParams({ tipo })
            });

            console.log('Status da resposta:', response.status); // DEBUG

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const html = await response.text();
            console.log('HTML recebido:', html); // DEBUG
            subFormContainer.innerHTML = html;

        } catch (error) {
            console.error('Erro ao carregar o sub-formulário:', error);
            subFormContainer.innerHTML = '<div class="alert alert-danger">Não foi possível carregar os campos adicionais.</div>';
        }
    };

        tipoPessoaSelect.addEventListener('change', () => {
            loadSubForm(tipoPessoaSelect.value);
        });

        // Carregar sub-formulário se já tiver valor selecionado
        if (tipoPessoaSelect.value) {
            loadSubForm(tipoPessoaSelect.value);
        }
    }
    
    // --- CONTROLE DO CÔNJUGE ---
    if (temConjuge && camposConjuge) {
        temConjuge.addEventListener('change', function() {
            camposConjuge.style.display = this.checked ? 'block' : 'none';
        });
    }
    
    // --- FUNÇÕES AUXILIARES ---
    function preencherDadosBusca(criteria, value, additionalDoc, additionalDocType) {
        console.log('📝 Preenchendo dados da busca:', { criteria, value, additionalDoc, additionalDocType });
        
        // Limpar o ID da pessoa pois é nova
        window.setFormValue(window.FORM_IDS.pessoaId, '');
        
        // Status de nova pessoa
        const pessoaStatus = document.querySelector('#pessoa-status');
        if (pessoaStatus) {
            pessoaStatus.textContent = 'Nova pessoa - será cadastrada';
            pessoaStatus.classList.remove('text-warning');
            pessoaStatus.classList.add('text-success');
        }
        
        // Preencher baseado no critério de busca
        switch(criteria) {
            case 'cpf':
                // Busca por CPF - preencher searchTerm e configurar como pessoa física
                window.setFormValue(window.FORM_IDS.searchTerm, value);
                configurarTipoPessoa('fisica');
                
                // Definir tipo de pessoa padrão para física
                const tipoPessoaSelectCPF = document.getElementById(window.FORM_IDS.tipoPessoa || 'pessoa_form_tipoPessoa');
                if (tipoPessoaSelectCPF) {
                    // Se não tem valor ainda, definir padrão
                    if (!tipoPessoaSelectCPF.value) {
                        tipoPessoaSelectCPF.value = 'fiador'; // Padrão para pessoa física
                        
                        // Disparar evento change para carregar sub-formulário
                        const changeEvent = new Event('change', { bubbles: true });
                        tipoPessoaSelectCPF.dispatchEvent(changeEvent);
                    }
                }
                break;
                
            case 'cnpj':
                // Busca por CNPJ - preencher searchTerm e configurar como pessoa jurídica
                window.setFormValue(window.FORM_IDS.searchTerm, value);
                configurarTipoPessoa('juridica');
                
                // Definir tipo de pessoa padrão para jurídica
                const tipoPessoaSelectCNPJ = document.getElementById(window.FORM_IDS.tipoPessoa || 'pessoa_form_tipoPessoa');
                if (tipoPessoaSelectCNPJ) {
                    // Se não tem valor ainda, definir padrão
                    if (!tipoPessoaSelectCNPJ.value) {
                        tipoPessoaSelectCNPJ.value = 'corretora'; // Padrão para pessoa jurídica
                        
                        // Disparar evento change para carregar sub-formulário
                        const changeEvent = new Event('change', { bubbles: true });
                        tipoPessoaSelectCNPJ.dispatchEvent(changeEvent);
                    }
                }
                break;
                
            case 'nome':
                // Busca por nome - preencher o nome
                window.setFormValue(window.FORM_IDS.nome, value);
                
                // Se forneceu documento adicional, processar
                if (additionalDoc) {
                    window.setFormValue(window.FORM_IDS.searchTerm, additionalDoc);
                    
                    // Determinar tipo baseado no documento adicional
                    const tipoDocumento = additionalDocType === 'cpf' ? 'fisica' : 'juridica';
                    configurarTipoPessoa(tipoDocumento);
                    
                    // Definir tipo de pessoa baseado no documento
                    const tipoPessoaSelectNome = document.getElementById(window.FORM_IDS.tipoPessoa || 'pessoa_form_tipoPessoa');
                    if (tipoPessoaSelectNome) {
                        if (!tipoPessoaSelectNome.value) {
                            tipoPessoaSelectNome.value = tipoDocumento === 'fisica' ? 'fiador' : 'corretora';
                            
                            // Disparar evento change
                            const changeEvent = new Event('change', { bubbles: true });
                            tipoPessoaSelectNome.dispatchEvent(changeEvent);
                        }
                    }
                }
                break;
                
            case 'id':
                // Busca por ID que não encontrou - não fazer nada especial
                console.log('ID não encontrado:', value);
                break;
        }
        
        // Limpar containers de dados múltiplos (telefones, endereços, etc.)
        limparContainersDadosMultiplos();
        
        // Limpar dados do cônjuge se existir
        limparDadosConjuge();
    }


    function limparDadosConjuge() {
        console.log('🧹 Limpando dados do cônjuge');
        
        // Desmarcar checkbox
        const temConjugeCheckbox = document.getElementById('temConjuge');
        if (temConjugeCheckbox) {
            temConjugeCheckbox.checked = false;
            
            // Disparar evento para ocultar campos
            const changeEvent = new Event('change', { bubbles: true });
            temConjugeCheckbox.dispatchEvent(changeEvent);
        }
        
        // Limpar campos do cônjuge
        const camposConjuge = document.querySelectorAll('[name^="novo_conjuge["], [name^="conjuge_"]');
        camposConjuge.forEach(campo => {
            if (campo.type === 'checkbox') {
                campo.checked = false;
            } else {
                campo.value = '';
            }
        });
        
        // Limpar containers do cônjuge
        const containersConjuge = [
            'conjuge-telefones-container',
            'conjuge-enderecos-container',
            'conjuge-emails-container',
            'conjuge-documentos-container',
            'conjuge-chaves-pix-container',
            'conjuge-profissoes-container'
        ];
        
        containersConjuge.forEach(containerId => {
            const container = document.getElementById(containerId);
            if (container) {
                container.innerHTML = '<p class="text-muted">Nenhum item adicionado.</p>';
            }
        });
        
        // Resetar contadores do cônjuge se existirem
        if (typeof window.contadorConjugeTelefone !== 'undefined') window.contadorConjugeTelefone = 0;
        if (typeof window.contadorConjugeEndereco !== 'undefined') window.contadorConjugeEndereco = 0;
        if (typeof window.contadorConjugeEmail !== 'undefined') window.contadorConjugeEmail = 0;
        if (typeof window.contadorConjugeDocumento !== 'undefined') window.contadorConjugeDocumento = 0;
        if (typeof window.contadorConjugeChavePix !== 'undefined') window.contadorConjugeChavePix = 0;
        if (typeof window.contadorConjugeProfissao !== 'undefined') window.contadorConjugeProfissao = 0;
    }
    
    function preencherFormulario(pessoa) {
        console.log('📝 Preenchendo formulário com pessoa encontrada:', pessoa);

        // Status de pessoa existente
        const pessoaStatus = document.querySelector('#pessoa-status');
        if (pessoaStatus) {
            pessoaStatus.textContent = `Pessoa existente (ID: ${pessoa.id}) - será atualizada`;
            pessoaStatus.classList.remove('text-success');
            pessoaStatus.classList.add('text-warning');
        }

        // Dados básicos
        window.setFormValue(window.FORM_IDS.pessoaId, pessoa.id);
        window.setFormValue(window.FORM_IDS.nome, pessoa.nome);
        window.setFormValue(window.FORM_IDS.searchTerm, pessoa.cpf || pessoa.cnpj || '');
        window.setFormValue(window.FORM_IDS.dataNascimento, pessoa.dataNascimento || '');
        window.setFormValue(window.FORM_IDS.nomePai, pessoa.nomePai || '');
        window.setFormValue(window.FORM_IDS.nomeMae, pessoa.nomeMae || '');
        window.setFormValue(window.FORM_IDS.renda, pessoa.renda || '');
        window.setFormValue(window.FORM_IDS.observacoes, pessoa.observacoes || '');

        // Selects
        window.setSelectValue(window.FORM_IDS.estadoCivil, pessoa.estadoCivil || '');
        window.setSelectValue(window.FORM_IDS.nacionalidade, pessoa.nacionalidade || '');
        window.setSelectValue(window.FORM_IDS.naturalidade, pessoa.naturalidade || '');

        // Física / Jurídica
        const tipoFisicaJuridica = pessoa.fisicaJuridica || (pessoa.cpf ? 'fisica' : 'juridica');
        configurarTipoPessoa(tipoFisicaJuridica);

        // ✅ NOVO: usa o ID que existe no select
        if (pessoa.tipoPessoaString) {
            const select = document.getElementById(window.FORM_IDS.tipoPessoa || 'pessoa_form_tipoPessoa');
            if (select) {
                select.value = pessoa.tipoPessoaString; // ex: "contratante", "fiador"...
                select.dispatchEvent(new Event('change')); // Dispara o evento para carregar o sub-formulário
            } else {
                console.warn('⚠️ Select tipoPessoa não encontrado para preencher');
            }
        } else {
             // Se não vier string, dispara o 'change' com o valor que estiver (provavelmente vazio)
             // para garantir que o subform (vazio) seja carregado.
             const select = document.getElementById(window.FORM_IDS.tipoPessoa || 'pessoa_form_tipoPessoa');
             if (select) {
                 select.dispatchEvent(new Event('change'));
             }
        }

        // Dados múltiplos
        carregarDadosMultiplos(pessoa);

        // Cônjuge
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
                // ✅ APENAS limpa se estiver vazio ou com mensagem padrão
                if (container.children.length === 0 || container.querySelector('.text-muted')) {
                    container.innerHTML = '<p class="text-muted">Nenhum item adicionado.</p>';
                }
            }
        });

        // Resetar contadores
        if (typeof window.contadorTelefone !== 'undefined') window.contadorTelefone = 0;
        if (typeof window.contadorEndereco !== 'undefined') window.contadorEndereco = 0;
        if (typeof window.contadorEmail !== 'undefined') window.contadorEmail = 0;
        if (typeof window.contadorDocumento !== 'undefined') window.contadorDocumento = 0;
        if (typeof window.contadorChavePix !== 'undefined') window.contadorChavePix = 0;
        if (typeof window.contadorProfissao !== 'undefined') window.contadorProfissao = 0;
    }

    function carregarDadosConjuge(conjuge) {
        console.log('👫 Carregando dados do cônjuge:', conjuge);
        
        // Marcar checkbox de tem cônjuge
        const temConjugeCheckbox = document.getElementById('temConjuge');
        if (temConjugeCheckbox) {
            temConjugeCheckbox.checked = true;
            
            // Disparar evento change para mostrar campos
            const changeEvent = new Event('change', { bubbles: true });
            temConjugeCheckbox.dispatchEvent(changeEvent);
        }
        
        // Se cônjuge é um ID, apenas definir o campo hidden
        if (typeof conjuge === 'number' || typeof conjuge === 'string') {
            const conjugeIdInput = document.querySelector('input[name="conjuge_id"]');
            if (conjugeIdInput) {
                conjugeIdInput.value = conjuge;
            }
            return;
        }
        
        // Se cônjuge é um objeto com dados
        if (typeof conjuge === 'object' && conjuge !== null) {
            // Preencher campos do cônjuge
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
            
            // Carregar dados múltiplos do cônjuge
            carregarDadosMultiplosConjuge(conjuge);
        }
    }

    function carregarDadosMultiplosConjuge(conjuge) {
        console.log('📦 Carregando dados múltiplos do cônjuge');
        
        // Carregar telefones do cônjuge
        if (conjuge.telefones && Array.isArray(conjuge.telefones)) {
            const container = document.getElementById('conjuge-telefones-container');
            if (container && typeof window.adicionarConjugeTelefoneExistente === 'function') {
                conjuge.telefones.forEach(telefone => {
                    window.adicionarConjugeTelefoneExistente(telefone);
                });
            }
        }
        
        // Carregar endereços do cônjuge
        if (conjuge.enderecos && Array.isArray(conjuge.enderecos)) {
            const container = document.getElementById('conjuge-enderecos-container');
            if (container && typeof window.adicionarConjugeEnderecoExistente === 'function') {
                conjuge.enderecos.forEach(endereco => {
                    window.adicionarConjugeEnderecoExistente(endereco);
                });
            }
        }
        
        // Carregar emails do cônjuge
        if (conjuge.emails && Array.isArray(conjuge.emails)) {
            const container = document.getElementById('conjuge-emails-container');
            if (container && typeof window.adicionarConjugeEmailExistente === 'function') {
                conjuge.emails.forEach(email => {
                    window.adicionarConjugeEmailExistente(email);
                });
            }
        }
        
        // Carregar documentos do cônjuge
        if (conjuge.documentos && Array.isArray(conjuge.documentos)) {
            const container = document.getElementById('conjuge-documentos-container');
            if (container && typeof window.adicionarConjugeDocumentoExistente === 'function') {
                conjuge.documentos.forEach(documento => {
                    window.adicionarConjugeDocumentoExistente(documento);
                });
            }
        }
        
        // Carregar chaves PIX do cônjuge
        if (conjuge.chavesPix && Array.isArray(conjuge.chavesPix)) {
            const container = document.getElementById('conjuge-chaves-pix-container');
            if (container && typeof window.adicionarConjugeChavePixExistente === 'function') {
                conjuge.chavesPix.forEach(chavePix => {
                    window.adicionarConjugeChavePixExistente(chavePix);
                });
            }
        }
        
        // Carregar profissões do cônjuge
        if (conjuge.profissoes && Array.isArray(conjuge.profissoes)) {
            const container = document.getElementById('conjuge-profissoes-container');
            if (container && typeof window.adicionarConjugeProfissaoExistente === 'function') {
                conjuge.profissoes.forEach(profissao => {
                    window.adicionarConjugeProfissaoExistente(profissao);
                });
            }
        }
    }


    function carregarDadosMultiplos(pessoa) {
        console.log('📦 Carregando dados múltiplos da pessoa:', pessoa.id);
        
        // Limpar containers antes de carregar
        limparContainersDadosMultiplos();
        
        // Carregar telefones
        if (pessoa.telefones && Array.isArray(pessoa.telefones)) {
            const telefonesContainer = document.getElementById('telefones-container');
            if (telefonesContainer && typeof window.adicionarTelefoneExistente === 'function') {
                pessoa.telefones.forEach(telefone => {
                    window.adicionarTelefoneExistente(telefone);
                });
            }
        }
        
        // Carregar endereços
        if (pessoa.enderecos && Array.isArray(pessoa.enderecos)) {
            const enderecosContainer = document.getElementById('enderecos-container');
            if (enderecosContainer && typeof window.adicionarEnderecoExistente === 'function') {
                pessoa.enderecos.forEach(endereco => {
                    window.adicionarEnderecoExistente(endereco);
                });
            }
        }
        
        // Carregar emails
        if (pessoa.emails && Array.isArray(pessoa.emails)) {
            const emailsContainer = document.getElementById('emails-container');
            if (emailsContainer && typeof window.adicionarEmailExistente === 'function') {
                pessoa.emails.forEach(email => {
                    window.adicionarEmailExistente(email);
                });
            }
        }
        
        // Carregar documentos
        // --- DEBUG DOCUMENTOS ---
        console.log('📄 DEBUG: Iniciando carregamento de documentos...');
        console.log('📄 DEBUG: pessoa.documentos =', pessoa.documentos);
        console.log('📄 DEBUG: Tipo de pessoa.documentos =', typeof pessoa.documentos);
        console.log('📄 DEBUG: É array?', Array.isArray(pessoa.documentos));

        if (pessoa.documentos && Array.isArray(pessoa.documentos)) {
            console.log('📄 DEBUG: Quantidade de documentos =', pessoa.documentos.length);
            
            const documentosContainer = document.getElementById('documentos-container');
            console.log('📄 DEBUG: Container encontrado?', documentosContainer);
            console.log('📄 DEBUG: Função adicionarDocumentoExistente existe?', typeof window.adicionarDocumentoExistente);

            if (documentosContainer && typeof window.adicionarDocumentoExistente === 'function') {
                console.log('📄 DEBUG: Iniciando loop foreach...');
                
                pessoa.documentos.forEach((documento, index) => {
                    console.log(`📄 DEBUG: Processando documento ${index} =`, documento);
                    window.adicionarDocumentoExistente(documento);
                    console.log(`📄 DEBUG: Documento ${index} processado com sucesso`);
                });
                
                console.log('📄 DEBUG: Todos os documentos foram processados');
            } else {
                console.warn('📄 DEBUG: Container ou função não encontrada');
            }
        } else {
            console.warn('📄 DEBUG: pessoa.documentos não é array ou está vazio');
        }
                
        // Carregar chaves PIX
        if (pessoa.chavesPix && Array.isArray(pessoa.chavesPix)) {
            const pixContainer = document.getElementById('pix-container');
            if (pixContainer && typeof window.adicionarChavePixExistente === 'function') {
                pessoa.chavesPix.forEach(chavePix => {
                    window.adicionarChavePixExistente(chavePix);
                });
            }
        }
        
        // Carregar profissões
        if (pessoa.profissoes && Array.isArray(pessoa.profissoes)) {
            const profissoesContainer = document.getElementById('profissoes-container');
            if (profissoesContainer && typeof window.adicionarProfissaoExistente === 'function') {
                pessoa.profissoes.forEach(profissao => {
                    window.adicionarProfissaoExistente(profissao);
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
            pessoaStatus.textContent = isPessoaFisica ? 'Pessoa Física' : 'Pessoa Jurídica';
        }
    }

    function preencheSubForm(tipo, dados) {
        if (!dados) return;
        const prefixo = tipo === 'corretora' ? 'corretora' : tipo; // evita duplo "corretora"
        Object.entries(dados).forEach(([campo, valor]) => {
            const name = `${prefixo}[${campo}]`;
            const input = document.querySelector(`[name="${name}"]`);
            if (!input) return;
            if (input.type === 'checkbox') {
                input.checked = !!valor;
            } else if (valor instanceof Date || campo.includes('Date') || campo.includes('data')) {
                input.value = valor ? valor.split(' ')[0] : ''; // Y-m-d
            } else {
                input.value = valor ?? '';
            }
        });
    }
    
    console.log('✅ new.js: Todas as funcionalidades configuradas');
});