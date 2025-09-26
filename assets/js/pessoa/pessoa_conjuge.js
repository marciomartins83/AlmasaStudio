/**
 * Gerencia a funcionalidade de cônjuge
 * Versão corrigida com carregamento completo de dados
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('📋 pessoa_conjuge.js carregado');
    
    // Verificar se os elementos de cônjuge existem no DOM
    const conjugeElements = {
        search: document.getElementById('conjuge-search'),
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
    
    if (conjugeElements.search && conjugeElements.btnSearch) {
        conjugeElements.btnSearch.addEventListener('click', async function() {
            const termo = conjugeElements.search.value.trim();
            
            if (!termo || termo.length < 3) {
                alert('Digite pelo menos 3 caracteres para buscar');
                return;
            }

            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Buscando...';
            this.disabled = true;

            try {
                // Verificar se a rota existe
                if (!window.ROUTES || !window.ROUTES.searchConjuge) {
                    throw new Error('Rota de busca de cônjuge não configurada');
                }

                const response = await fetch(window.ROUTES.searchConjuge, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ termo: termo })
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
                                ${data.pessoas.map(pessoa => `
                                    <div class="col-md-6 mb-2">
                                        <div class="card">
                                            <div class="card-body">
                                                <h6 class="card-title">${pessoa.nome}</h6>
                                                <p class="card-text">
                                                    <small class="text-muted">
                                                        CPF: ${pessoa.cpf || 'Não informado'}<br>
                                                        Nascimento: ${pessoa.data_nascimento || 'Não informado'}
                                                    </small>
                                                </p>
                                                <button type="button" class="btn btn-primary btn-sm selecionar-conjuge" 
                                                        data-id="${pessoa.id}" 
                                                        data-nome="${pessoa.nome}"
                                                        data-cpf="${pessoa.cpf || ''}"
                                                        data-nascimento="${pessoa.data_nascimento || ''}"
                                                        data-nacionalidade="${pessoa.nacionalidade || ''}"
                                                        data-naturalidade="${pessoa.naturalidade || ''}">
                                                    <i class="fas fa-check"></i> Selecionar
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                `).join('')}
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
                naturalidade: btn.dataset.naturalidade
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
            nacionalidade: document.querySelector('select[name="novo_conjuge[nacionalidade]"]'),
            naturalidade: document.querySelector('select[name="novo_conjuge[naturalidade]"]')
        };

        // Preencher campos de texto
        if (campos.nome && conjuge.nome) {
            campos.nome.value = conjuge.nome;
            console.log('Nome carregado:', conjuge.nome);
        }

        if (campos.cpf && conjuge.cpf) {
            campos.cpf.value = conjuge.cpf;
            console.log('CPF carregado:', conjuge.cpf);
        }

        if (campos.dataNascimento && conjuge.data_nascimento) {
            campos.dataNascimento.value = conjuge.data_nascimento;
            console.log('Data nascimento carregada:', conjuge.data_nascimento);
        }

        // Preencher selects (tentar encontrar opção pelo texto)
        if (campos.nacionalidade && conjuge.nacionalidade) {
            Array.from(campos.nacionalidade.options).forEach(option => {
                if (option.text.toLowerCase().includes(conjuge.nacionalidade.toLowerCase())) {
                    option.selected = true;
                    console.log('Nacionalidade selecionada:', conjuge.nacionalidade);
                }
            });
        }

        if (campos.naturalidade && conjuge.naturalidade) {
            Array.from(campos.naturalidade.options).forEach(option => {
                if (option.text.toLowerCase().includes(conjuge.naturalidade.toLowerCase())) {
                    option.selected = true;
                    console.log('Naturalidade selecionada:', conjuge.naturalidade);
                }
            });
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

            // Limpar campo de busca
            if (conjugeElements.search) {
                conjugeElements.search.value = '';
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
    if (conjugeElements.search && conjugeElements.btnSearch) {
        conjugeElements.search.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                conjugeElements.btnSearch.click();
            }
        });
    }
    
    console.log('✅ pessoa_conjuge.js: Funcionalidades configuradas com carregamento de dados');
});