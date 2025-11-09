/**
 * Gerencia múltiplos tipos de pessoa
 * Uma pessoa pode ser simultaneamente: fiador, locador, contratante, etc.
 */
document.addEventListener('DOMContentLoaded', function() {
    let contadorTipos = 0;
    const tiposAtivos = new Set();
    
    // Mapeamento de tipos e seus formulários
    const tiposConfig = {
        'fiador': { 
            label: 'Fiador', 
            icon: 'fas fa-handshake',
            temCampos: true 
        },
        'corretor': { 
            label: 'Corretor', 
            icon: 'fas fa-user-tie',
            temCampos: true 
        },
        'corretora': { 
            label: 'Corretora', 
            icon: 'fas fa-building',
            temCampos: false 
        },
        'locador': { 
            label: 'Locador', 
            icon: 'fas fa-key',
            temCampos: true 
        },
        'pretendente': { 
            label: 'Pretendente', 
            icon: 'fas fa-search-location',
            temCampos: true 
        },
        'contratante': { 
            label: 'Contratante', 
            icon: 'fas fa-file-signature',
            temCampos: false 
        }
    };

    // Adicionar novo tipo
    document.getElementById('add-tipo-pessoa')?.addEventListener('click', function() {
        const container = document.getElementById('tipos-pessoa-container');
        const selectTipos = document.getElementById('select-tipo-pessoa');
        const tipoSelecionado = selectTipos.value;
        
        if (!tipoSelecionado) {
            alert('Selecione um tipo de pessoa');
            return;
        }
        
        if (tiposAtivos.has(tipoSelecionado)) {
            alert('Este tipo já foi adicionado');
            return;
        }
        
        contadorTipos++;
        tiposAtivos.add(tipoSelecionado);
        
        // Atualizar select removendo opção já selecionada
        selectTipos.querySelector(`option[value="${tipoSelecionado}"]`).disabled = true;
        selectTipos.value = '';
        
        // Criar card do tipo
        const config = tiposConfig[tipoSelecionado];
        const tipoHtml = `
            <div class="tipo-pessoa-card" data-tipo="${tipoSelecionado}" data-index="${contadorTipos}">
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="${config.icon}"></i> ${config.label}</h5>
                        <button type="button" class="btn btn-danger btn-sm" onclick="removerTipoPessoa('${tipoSelecionado}', ${contadorTipos})">
                            <i class="fas fa-times"></i> Remover
                        </button>
                    </div>
                    <div class="card-body">
                        <input type="hidden" name="tipos_pessoa[]" value="${tipoSelecionado}">
                        <div id="campos-${tipoSelecionado}" class="tipo-campos-container">
                            ${config.temCampos ? '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Carregando campos...</div>' : '<p class="text-muted">Este tipo não possui campos adicionais</p>'}
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        container.insertAdjacentHTML('beforeend', tipoHtml);
        
        // Carregar campos específicos do tipo se necessário
        if (config.temCampos) {
            carregarCamposTipo(tipoSelecionado);
        }
        
        // Se não houver mais tipos disponíveis, desabilitar botão
        verificarTiposDisponiveis();
    });
    
    // Remover tipo
    window.removerTipoPessoa = function(tipo, index) {
        const card = document.querySelector(`.tipo-pessoa-card[data-tipo="${tipo}"][data-index="${index}"]`);
        if (!card) return;
        
        if (!confirm(`Remover ${tiposConfig[tipo].label}? Os dados deste tipo serão perdidos.`)) {
            return;
        }
        
        card.remove();
        tiposAtivos.delete(tipo);
        
        // Reabilitar no select
        const selectTipos = document.getElementById('select-tipo-pessoa');
        if (selectTipos) {
            const option = selectTipos.querySelector(`option[value="${tipo}"]`);
            if (option) option.disabled = false;
        }
        
        verificarTiposDisponiveis();
    };
    
    // Carregar campos específicos do tipo via AJAX
    async function carregarCamposTipo(tipo) {
        try {
            const response = await fetch(window.ROUTES.subform, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams({ tipo: tipo })
            });
            
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            
            const html = await response.text();
            const container = document.getElementById(`campos-${tipo}`);
            if (container) {
                container.innerHTML = html;
                
                // Re-inicializar componentes se necessário
                inicializarComponentesTipo(tipo);
            }
        } catch (error) {
            console.error(`Erro ao carregar campos de ${tipo}:`, error);
            const container = document.getElementById(`campos-${tipo}`);
            if (container) {
                container.innerHTML = '<div class="alert alert-danger">Erro ao carregar campos</div>';
            }
        }
    }
    
    // Inicializar componentes específicos do tipo
    function inicializarComponentesTipo(tipo) {
        const container = document.getElementById(`campos-${tipo}`);
        if (!container) return;
        
        // Inicializar selects, datepickers, etc.
        container.querySelectorAll('select.form-select').forEach(select => {
            // Inicializar select2 se necessário
        });
        
        // Ajustar nomes dos campos para array
        container.querySelectorAll('input, select, textarea').forEach(field => {
            const name = field.getAttribute('name');
            if (name && !name.includes('[')) {
                field.setAttribute('name', `${tipo}[${name}]`);
            }
        });
    }
    
    // Verificar tipos disponíveis
    function verificarTiposDisponiveis() {
        const selectTipos = document.getElementById('select-tipo-pessoa');
        const btnAdd = document.getElementById('add-tipo-pessoa');
        const container = document.getElementById('tipos-pessoa-container');
        
        if (!selectTipos || !btnAdd) return;
        
        const opcaoDisponivel = Array.from(selectTipos.options).some(opt => 
            opt.value && !opt.disabled
        );
        
        btnAdd.disabled = !opcaoDisponivel;
        
        // Mostrar mensagem se não houver tipos
        if (container && container.children.length === 0) {
            container.innerHTML = '<p class="text-muted">Nenhum tipo selecionado. Adicione pelo menos um tipo de pessoa.</p>';
        }
    }
    
    // Carregar tipos existentes (para edição)
    window.carregarTiposExistentes = function(tiposData) {
        if (!tiposData || typeof tiposData !== 'object') return;
        
        const container = document.getElementById('tipos-pessoa-container');
        if (!container) return;
        
        container.innerHTML = '';
        
        Object.entries(tiposData).forEach(([tipo, dados]) => {
            if (dados === true || (dados && typeof dados === 'object')) {
                // Simular clique no botão adicionar
                const selectTipos = document.getElementById('select-tipo-pessoa');
                if (selectTipos) {
                    selectTipos.value = tipo;
                    document.getElementById('add-tipo-pessoa')?.click();
                    
                    // Se houver dados, preencher após carregamento
                    if (typeof dados === 'object') {
                        setTimeout(() => {
                            preencherDadosTipo(tipo, dados);
                        }, 500);
                    }
                }
            }
        });
    };
    
    // Preencher dados do tipo
    function preencherDadosTipo(tipo, dados) {
        const container = document.getElementById(`campos-${tipo}`);
        if (!container) return;
        
        Object.entries(dados).forEach(([campo, valor]) => {
            const input = container.querySelector(`[name*="${campo}"]`);
            if (input) {
                if (input.type === 'checkbox') {
                    input.checked = !!valor;
                } else if (input.tagName === 'SELECT') {
                    input.value = valor;
                } else {
                    input.value = valor || '';
                }
            }
        });
    }
    
    // Validação antes de enviar
    window.validarTiposPessoa = function() {
        const container = document.getElementById('tipos-pessoa-container');
        if (!container) return true;
        
        const cards = container.querySelectorAll('.tipo-pessoa-card');
        if (cards.length === 0) {
            alert('Selecione pelo menos um tipo de pessoa');
            return false;
        }
        
        // Validar campos obrigatórios de cada tipo
        let valido = true;
        cards.forEach(card => {
            const tipo = card.dataset.tipo;
            const camposObrigatorios = card.querySelectorAll('[required]');
            
            camposObrigatorios.forEach(campo => {
                if (!campo.value) {
                    campo.classList.add('is-invalid');
                    valido = false;
                } else {
                    campo.classList.remove('is-invalid');
                }
            });
        });
        
        if (!valido) {
            alert('Preencha todos os campos obrigatórios');
        }
        
        return valido;
    };
    
    // Inicialização
    verificarTiposDisponiveis();
});