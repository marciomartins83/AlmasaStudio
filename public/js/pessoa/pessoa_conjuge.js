/**
 * Gerencia a funcionalidade de cônjuge
 * Versão corrigida com carregamento completo de dados
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('📋 pessoa_conjuge.js carregado');
    
    // Verificar se os elementos de cônjuge existem no DOM
    const conjugeElements = {
        searchCriteria: document.getElementById('conjugeSearchCriteria'),
        searchValue: document.getElementById('conjugeSearchValue'),
        btnSearch: document.getElementById('btn-search-conjuge'),
        btnNew: document.getElementById('btn-new-conjuge'),
        results: document.getElementById('conjuge-results'),
        field: document.getElementById('pessoa_form_conjuge') // CORRIGIDO: nome correto do campo
    };
    
    // Verificar se algum elemento existe
    const hasConjugeElements = Object.values(conjugeElements).some(el => el !== null);
    
    if (!hasConjugeElements) {
        console.log('ℹ️ Elementos de cônjuge não encontrados no DOM atual');
        return;
    }
    
    console.log('✅ Elementos de cônjuge encontrados:', conjugeElements);
    
    // =========================================================================
    // FUNCIONALIDADES DE CÔNJUGE
    // =========================================================================

    // Controlar habilitação do campo de valor baseado no critério selecionado
    if (conjugeElements.searchCriteria && conjugeElements.searchValue && conjugeElements.btnSearch) {
        conjugeElements.searchCriteria.addEventListener('change', () => {
            const selectedValue = conjugeElements.searchCriteria.value;

            if (selectedValue) {
                conjugeElements.searchValue.removeAttribute('disabled');
                conjugeElements.searchValue.focus();
            } else {
                conjugeElements.searchValue.setAttribute('disabled', 'disabled');
                conjugeElements.btnSearch.setAttribute('disabled', 'disabled');
            }

            conjugeElements.searchValue.value = '';
            const selectedOptionText = conjugeElements.searchCriteria.options[conjugeElements.searchCriteria.selectedIndex].text;
            conjugeElements.searchValue.placeholder = selectedValue ? `Digite o ${selectedOptionText}` : 'Selecione um critério primeiro';
        });

        conjugeElements.searchValue.addEventListener('input', () => {
            const criteria = conjugeElements.searchCriteria.value;
            let minLength = 0;
            let rawValue = conjugeElements.searchValue.value.replace(/[^\d]/g, '');
            let currentLength = 0;

            switch(criteria) {
                case 'cpf':
                    minLength = 11;
                    currentLength = rawValue.length;
                    // Aplicar máscara de CPF em tempo real
                    if (rawValue.length <= 11) {
                        conjugeElements.searchValue.value = window.formatarCPF ? window.formatarCPF(rawValue) : rawValue;
                    }
                    break;
                case 'nome':
                    minLength = 3;
                    currentLength = conjugeElements.searchValue.value.trim().length;
                    break;
                case 'id':
                    minLength = 1;
                    currentLength = rawValue.length;
                    break;
            }

            conjugeElements.btnSearch.disabled = currentLength < minLength;
        });
    }

    if (conjugeElements.searchCriteria && conjugeElements.searchValue && conjugeElements.btnSearch) {
        conjugeElements.btnSearch.addEventListener('click', async function() {
            const criteria = conjugeElements.searchCriteria.value;
            const value = conjugeElements.searchValue.value.trim();

            if (!criteria || !value) {
                alert('Selecione um critério e digite o valor da busca');
                return;
            }

            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Buscando...';
            this.disabled = true;

            try {
                // Verificar se a rota existe
                if (!window.ROUTES || !window.ROUTES.searchConjuge) {
                    throw new Error('Rota de busca de cônjuge não configurada');
                }

                // ✅ Obter ID da pessoa principal para evitar auto-relacionamento
                const pessoaIdField = document.getElementById(window.FORM_IDS?.pessoaId || 'pessoa_form_pessoaId');
                const pessoaId = pessoaIdField ? pessoaIdField.value : null;

                const response = await fetch(window.ROUTES.searchConjuge, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        criteria: criteria,
                        value: value,
                        pessoaId: pessoaId // Enviar ID para evitar auto-relacionamento
                    })
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const data = await response.json();

                if (conjugeElements.results) {
                    if (data.success && data.pessoas && data.pessoas.length > 0) {
                        conjugeElements.results.innerHTML = `
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                Encontradas ${data.pessoas.length} pessoa(s). Selecione uma das opções:
                            </div>
                            <div class="row">
                                ${data.pessoas.map(pessoa => {
                                    const cpfFormatado = pessoa.cpf && window.formatarCPF ? window.formatarCPF(pessoa.cpf.replace(/[^\d]/g, '')) : (pessoa.cpf || 'Não informado');
                                    return `
                                    <div class="col-md-6 mb-2">
                                        <div class="card">
                                            <div class="card-body">
                                                <h6 class="card-title">${pessoa.nome}</h6>
                                                <p class="card-text">
                                                    <small class="text-muted">
                                                        CPF: ${cpfFormatado}<br>
                                                        Nascimento: ${pessoa.data_nascimento || 'Não informado'}
                                                    </small>
                                                </p>
                                                <button type="button" class="btn btn-primary btn-sm selecionar-conjuge" 
                                                        data-id="${pessoa.id}" 
                                                        data-nome="${pessoa.nome}"
                                                        data-cpf="${pessoa.cpf || ''}"
                                                        data-nascimento="${pessoa.data_nascimento || ''}"
                                                        data-nacionalidade="${pessoa.nacionalidade || ''}"
                                                        data-nacionalidade-nome="${pessoa.nacionalidadeNome || ''}"
                                                        data-naturalidade="${pessoa.naturalidade || ''}"
                                                        data-naturalidade-nome="${pessoa.naturalidadeNome || ''}">
                                                    <i class="fas fa-check"></i> Selecionar
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                `;}).join('')}
                            </div>
                        `;
                        conjugeElements.results.style.display = 'block';
                    } else {
                        conjugeElements.results.innerHTML = '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Nenhuma pessoa encontrada com este termo.</div>';
                        conjugeElements.results.style.display = 'block';
                    }
                }
                
            } catch (error) {
                console.error('Erro na busca de cônjuge:', error);
                
                if (conjugeElements.results) {
                    conjugeElements.results.innerHTML = `<div class="alert alert-danger">Erro na busca: ${error.message}</div>`;
                    conjugeElements.results.style.display = 'block';
                }
                
            } finally {
                this.innerHTML = '<i class="fas fa-search"></i> Buscar';
                this.disabled = false;
            }
        });
    }

    // Event listener para seleção de cônjuge - CORRIGIDO
    if (conjugeElements.results) {
        conjugeElements.results.addEventListener('click', function (e) {
            const btn = e.target.closest('.selecionar-conjuge');
            if (!btn) return;

            // Dados do cônjuge selecionado
            const conjugeData = {
                id: btn.dataset.id,
                nome: btn.dataset.nome,
                cpf: btn.dataset.cpf,
                data_nascimento: btn.dataset.nascimento,
                nacionalidade: btn.dataset.nacionalidade,
                nacionalidadeNome: btn.dataset.nacionalidadeNome,
                naturalidade: btn.dataset.naturalidade,
                naturalidadeNome: btn.dataset.naturalidadeNome
            };

            console.log('Cônjuge selecionado:', conjugeData);

            // 1. Preencher campo hidden com ID do cônjuge
            if (conjugeElements.field) {
                conjugeElements.field.value = conjugeData.id;
                console.log('ID do cônjuge salvo:', conjugeData.id);
            }

            // 2. Preencher campo de busca com nome
            if (conjugeElements.search) {
                conjugeElements.search.value = conjugeData.nome;
            }

            // 3. NOVO: Carregar dados nos campos editáveis do formulário
            carregarDadosConjugeNosInputs(conjugeData);

            // 4. Ocultar resultados
            conjugeElements.results.style.display = 'none';

            // 5. Mostrar feedback de sucesso
            mostrarFeedbackSucesso(conjugeData.nome);
        });
    }

    // NOVA FUNÇÃO: Carregar dados do cônjuge nos inputs editáveis
    function carregarDadosConjugeNosInputs(conjuge) {
        console.log('Carregando dados nos inputs:', conjuge);

        // Carregar dados básicos nos inputs
        const campos = {
            nome: document.querySelector('input[name="novo_conjuge[nome]"]'),
            cpf: document.querySelector('input[name="novo_conjuge[cpf]"]'),
            dataNascimento: document.querySelector('input[name="novo_conjuge[data_nascimento]"]'),
            nacionalidade: document.getElementById('conjuge_nacionalidade'),
            nacionalidadeDisplay: document.getElementById('conjuge_nacionalidade_display'),
            naturalidade: document.getElementById('conjuge_naturalidade'),
            naturalidadeDisplay: document.getElementById('conjuge_naturalidade_display')
        };

        // Preencher campos de texto
        if (campos.nome && conjuge.nome) {
            campos.nome.value = conjuge.nome;
            console.log('Nome carregado:', conjuge.nome);
        }

        if (campos.cpf && conjuge.cpf) {
            // Aplicar máscara de CPF ao carregar
            const cpfFormatado = window.formatarCPF ? window.formatarCPF(conjuge.cpf.replace(/[^\d]/g, '')) : conjuge.cpf;
            campos.cpf.value = cpfFormatado;
            console.log('CPF carregado:', cpfFormatado);
        }

        // Vincular máscara de CPF ao campo de CPF do cônjuge para digitação
        if (campos.cpf) {
            campos.cpf.addEventListener('input', () => {
                const rawValue = campos.cpf.value.replace(/[^\d]/g, '');
                if (rawValue.length <= 11) {
                    campos.cpf.value = window.formatarCPF ? window.formatarCPF(rawValue) : rawValue;
                }
            });
        }

        if (campos.dataNascimento && conjuge.data_nascimento) {
            campos.dataNascimento.value = conjuge.data_nascimento;
            console.log('Data nascimento carregada:', conjuge.data_nascimento);
        }

        // Preencher autocomplete (hidden + display) para nacionalidade e naturalidade
        if (campos.nacionalidade && conjuge.nacionalidade) {
            campos.nacionalidade.value = conjuge.nacionalidade;
            if (campos.nacionalidadeDisplay && conjuge.nacionalidadeNome) {
                campos.nacionalidadeDisplay.value = conjuge.nacionalidadeNome;
            }
            console.log('Nacionalidade preenchida:', conjuge.nacionalidade);
        }

        if (campos.naturalidade && conjuge.naturalidade) {
            campos.naturalidade.value = conjuge.naturalidade;
            if (campos.naturalidadeDisplay && conjuge.naturalidadeNome) {
                campos.naturalidadeDisplay.value = conjuge.naturalidadeNome;
            }
            console.log('Naturalidade preenchida:', conjuge.naturalidade);
        }

        // Marcar campos como readonly para indicar que são de cônjuge existente
        Object.values(campos).forEach(campo => {
            if (campo) {
                campo.style.backgroundColor = '#f8f9fa';
                campo.title = 'Dados carregados de cônjuge existente';
            }
        });
    }

    // NOVA FUNÇÃO: Mostrar feedback de sucesso
    function mostrarFeedbackSucesso(nomeConjuge) {
        const alert = document.createElement('div');
        alert.className = 'alert alert-success alert-dismissible fade show mt-2';
        alert.innerHTML = `
            <i class="fas fa-check-circle"></i>
            Dados do cônjuge "${nomeConjuge}" carregados com sucesso nos campos do formulário!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        const camposConjuge = document.getElementById('campos-conjuge');
        if (camposConjuge) {
            camposConjuge.insertBefore(alert, camposConjuge.firstChild);
            
            // Remover o alerta após 4 segundos
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.remove();
                }
            }, 4000);
        }
    }

    // Botão para novo cônjuge - CORRIGIDO
    if (conjugeElements.btnNew) {
        conjugeElements.btnNew.addEventListener('click', function() {
            console.log('Limpando campos para novo cônjuge');

            // Limpar campo hidden
            if (conjugeElements.field) {
                conjugeElements.field.value = '';
            }

            // Limpar campos de busca
            if (conjugeElements.searchCriteria) {
                conjugeElements.searchCriteria.value = '';
            }

            if (conjugeElements.searchValue) {
                conjugeElements.searchValue.value = '';
                conjugeElements.searchValue.setAttribute('disabled', 'disabled');
            }

            if (conjugeElements.btnSearch) {
                conjugeElements.btnSearch.setAttribute('disabled', 'disabled');
            }

            // Ocultar resultados
            if (conjugeElements.results) {
                conjugeElements.results.style.display = 'none';
            }
            
            // Limpar todos os campos do cônjuge
            const campos = document.querySelectorAll('#campos-conjuge input, #campos-conjuge select, #campos-conjuge textarea');
            campos.forEach(campo => {
                if (campo.type === 'checkbox' || campo.type === 'radio') {
                    campo.checked = false;
                } else {
                    campo.value = '';
                }
                // Remover styling de readonly
                campo.style.backgroundColor = '';
                campo.title = '';
            });

            // Focar no campo nome
            const nomeInput = document.querySelector('input[name="novo_conjuge[nome]"]');
            if (nomeInput) {
                nomeInput.focus();
            }

            console.log('Campos limpos para novo cadastro');
        });
    }

    // Enter no campo de busca
    if (conjugeElements.searchValue && conjugeElements.btnSearch) {
        conjugeElements.searchValue.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (!conjugeElements.btnSearch.disabled) {
                    conjugeElements.btnSearch.click();
                }
            }
        });
    }
    
    console.log('✅ pessoa_conjuge.js: Funcionalidades configuradas com carregamento de dados');
});