/**
 * Gerencia a funcionalidade de cônjuge
 * Responsável pela busca e seleção de cônjuge
 */
document.addEventListener('DOMContentLoaded', function() {
    const conjugeSearch = document.getElementById('conjuge-search');
    const btnSearchConjuge = document.getElementById('btn-search-conjuge');
    const btnNewConjuge = document.getElementById('btn-new-conjuge');
    const conjugeResults = document.getElementById('conjuge-results');
    const conjugeField = document.getElementById(window.FORM_IDS.conjuge || 'conjuge_field');
    
    const url = Routing.generate('app_pessoa_fiador_search_conjuge');
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
                                    data-id="${pessoa.id}"
                                    data-nome="${pessoa.nome}"
                                    data-cpf="${pessoa.cpf || ''}"
                                    data-nascimento="${pessoa.data_nascimento || ''}"
                                    data-nacionalidade="${pessoa.nacionalidade || ''}"
                                    data-naturalidade="${pessoa.naturalidade || ''}">
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

        conjugeResults.addEventListener('click', function (e) {
            const btn = e.target.closest('.selecionar-conjuge');
            if (!btn) return;

            // esconder formulário de novo cônjuge
            document.getElementById('campos-novo-conjuge').style.display = 'none';

            // preencher id e nome
            if (conjugeField) conjugeField.value = btn.dataset.id;
            conjugeSearch.value = btn.dataset.nome;

            // preencher dados de leitura
            document.getElementById('conjuge-cpf-readonly').textContent   = btn.dataset.cpf || '';
            document.getElementById('conjuge-nasc-readonly').textContent  = btn.dataset.nascimento || '';
            document.getElementById('conjuge-nac-readonly').textContent   = btn.dataset.nacionalidade || '';
            document.getElementById('conjuge-nat-readonly').textContent   = btn.dataset.naturalidade || '';

            // mostrar bloco
            document.getElementById('dados-conjuge-existente').style.display = 'block';

            // ocultar resultados
            conjugeResults.style.display = 'none';
        });

        btnNewConjuge.addEventListener('click', function() {
            conjugeResults.style.display = 'none';
            conjugeSearch.value = '';
            
            // Mostrar campos de novo cônjuge
            const novoConjugeSection = document.getElementById('campos-novo-conjuge');
            if (novoConjugeSection) {
                novoConjugeSection.style.display = 'block';
                
                // Limpar campos para novo preenchimento
                novoConjugeSection.querySelectorAll('input, select, textarea').forEach(field => {
                    field.value = '';
                });
            }
        });

        // Evento para cancelar
        document.addEventListener('click', function(e) {
            if (e.target.id === 'cancelar-novo-conjuge') {
                document.getElementById('novo-conjuge-form').style.display = 'none';
            }
        });

        // Evento para salvar novo cônjuge (exemplo)
        document.addEventListener('click', function(e) {
            if (e.target.id === 'salvar-novo-conjuge') {
                const nome = document.getElementById('novo-conjuge-nome').value.trim();
                const cpf = document.getElementById('novo-conjuge-cpf').value.trim();
                
                if (!nome || !cpf) {
                    alert('Preencha nome e CPF do cônjuge.');
                    return;
                }

                // Aqui você pode:
                // 1. Fazer AJAX para criar a pessoa
                // 2. Redirecionar para uma página de cadastro
                // 3. Ou abrir um modal externo
                alert(`Cônjuge "${nome}" (CPF: ${cpf}) cadastrado com sucesso!`);
                
                // Ocultar formulário
                document.getElementById('novo-conjuge-form').style.display = 'none';
            }
        });

        conjugeSearch.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                btnSearchConjuge.click();
            }
        });
    }
});
