document.addEventListener('DOMContentLoaded', () => {
    console.log('[DEBUG] DOM Carregado. Iniciando script new.js...');

    // --- ELEMENTOS DO FORMULÁRIO ---
    const searchCriteriaSelect = document.getElementById('searchCriteria');
    const searchValueInput = document.getElementById('searchValue');
    const searchButton = document.getElementById('btn-search');
    const clearButton = document.getElementById('btn-clear');
    const searchResultsDiv = document.getElementById('search-results');
    const searchMessageContainer = document.getElementById('search-message-container');
    const searchMessageSpan = document.getElementById('search-message');
    const mainFormDiv = document.getElementById('main-form');
    const tipoPessoaSelect = document.querySelector('[id^="pessoa_form_tipoPessoa"]');
    const subFormContainer = document.getElementById('sub-form-container');

    // --- VERIFICAÇÃO DOS ELEMENTOS ---
    console.log('[DEBUG] Verificando elementos da busca...');
    if (!searchCriteriaSelect) console.error('❌ ERRO: Elemento #searchCriteria NÃO ENCONTRADO!');
    else console.log('✅ OK: Elemento #searchCriteria encontrado.');

    if (!searchValueInput) console.error('❌ ERRO: Elemento #searchValue NÃO ENCONTRADO!');
    else console.log('✅ OK: Elemento #searchValue encontrado.');


    // --- LÓGICA DA BUSCA INTELIGENTE ---
    if (searchCriteriaSelect && searchValueInput && searchButton && clearButton) {
        console.log('[DEBUG] Anexando listener de "change" ao #searchCriteria...');
        
        searchCriteriaSelect.addEventListener('change', () => {
            console.log('[DEBUG] Evento "change" disparado!');
            const selectedValue = searchCriteriaSelect.value;
            console.log(`[DEBUG] Valor selecionado: "${selectedValue}"`);
            
            // --- CORREÇÃO APLICADA AQUI (USANDO A PROPRIEDADE .disabled) ---
            searchValueInput.disabled = !selectedValue; // true se não houver valor, false se houver.
            if (selectedValue) {
                console.log('[DEBUG] Habilitando o campo de busca...');
                searchValueInput.focus();
            } else {
                console.log('[DEBUG] Desabilitando o campo de busca...');
            }

            searchValueInput.value = '';
            const selectedOptionText = searchCriteriaSelect.options[searchCriteriaSelect.selectedIndex].text;
            searchValueInput.placeholder = selectedValue ? `Digite o ${selectedOptionText}` : 'Selecione um critério primeiro';
            searchButton.disabled = true;
        });

        console.log('[DEBUG] Listener de "change" anexado com sucesso.');

        searchValueInput.addEventListener('input', () => {
            searchButton.disabled = searchValueInput.value.trim() === '';
        });

        searchButton.addEventListener('click', async () => {
            const criteria = searchCriteriaSelect.value;
            const value = searchValueInput.value.trim();
            if (!value) return;

            searchButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Buscando...';
            searchButton.disabled = true;

            try {
                const response = await fetch(window.ROUTES.searchPessoa, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ criteria, value })
                });

                const data = await response.json();

                searchResultsDiv.style.display = 'block';
                mainFormDiv.style.display = 'block';

                if (data.success && data.pessoa) {
                    searchMessageContainer.className = 'alert alert-success';
                    searchMessageSpan.textContent = 'Pessoa encontrada! Formulário preenchido.';
                    // TODO: Implementar a função para preencher o formulário com os dados de `data.pessoa`
                    console.log('Pessoa encontrada:', data.pessoa);
                } else {
                    searchMessageContainer.className = 'alert alert-info';
                    searchMessageSpan.textContent = `Nenhuma pessoa encontrada para "${value}". Você pode prosseguir com o novo cadastro.`;
                    // TODO: Implementar função para limpar/resetar o formulário, mas mantendo o valor buscado
                }

            } catch (error) {
                console.error('Erro na busca:', error);
                searchResultsDiv.style.display = 'block';
                searchMessageContainer.className = 'alert alert-danger';
                searchMessageSpan.textContent = 'Ocorreu um erro ao realizar a busca. Tente novamente.';
            } finally {
                searchButton.innerHTML = '<i class="fas fa-search"></i> Buscar';
                searchButton.disabled = false;
            }
        });
        
        clearButton.addEventListener('click', () => {
            searchCriteriaSelect.value = '';
            searchValueInput.value = '';
            // --- CORREÇÃO APLICADA AQUI ---
            searchValueInput.disabled = true;
            searchButton.disabled = true;
            searchResultsDiv.style.display = 'none';
            mainFormDiv.style.display = 'none';
        });
    }

    // --- LÓGICA DO SUB-FORMULÁRIO DINÂMICO ---
    if (tipoPessoaSelect && subFormContainer) {
        const loadSubForm = async (tipo) => {
            if (!tipo) {
                subFormContainer.innerHTML = '';
                return;
            }
            subFormContainer.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Carregando...</div>';

            try {
                const response = await fetch(window.ROUTES.subform, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                    body: new URLSearchParams({ tipo })
                });

                if (!response.ok) throw new Error('Falha na requisição.');
                
                const html = await response.text();
                subFormContainer.innerHTML = html;

            } catch (error) {
                console.error('Erro ao carregar o sub-formulário:', error);
                subFormContainer.innerHTML = '<div class="alert alert-danger">Não foi possível carregar os campos adicionais.</div>';
            }
        };

        tipoPessoaSelect.addEventListener('change', () => loadSubForm(tipoPessoaSelect.value));

        if (tipoPessoaSelect.value) {
            loadSubForm(tipoPessoaSelect.value);
        }
    }
});

