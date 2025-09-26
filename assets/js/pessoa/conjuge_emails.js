/**
 * Gerencia a funcionalidade de endereços do cônjuge
 * Responsável pela criação, remoção e busca de CEP
 */
document.addEventListener('DOMContentLoaded', function() {
    let contadorConjugeEndereco = 0;

    document.getElementById('add-conjuge-endereco')?.addEventListener('click', async function() {
        const tipos = window.tiposEndereco || await carregarTipos('endereco');
        window.tiposEndereco = tipos;
        contadorConjugeEndereco++;
        const container = document.getElementById('conjuge-enderecos-container');

        if (container.querySelector('.text-muted')) {
            container.innerHTML = '';
        }

        const enderecoHtml = `
            <div class="border p-3 mb-3 conjuge-endereco-item" data-index="${contadorConjugeEndereco}">
                <input type="hidden" class="estado-field" name="conjuge_enderecos[${contadorConjugeEndereco}][estado]">
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-label">Tipo de Endereço</label>
                        ${criarSelectTipos(tipos, `conjuge_enderecos[${contadorConjugeEndereco}][tipo]`, `conjuge_endereco_tipo_${contadorConjugeEndereco}`, `abrirModalTipoEndereco(${contadorConjugeEndereco})`)}
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">CEP</label>
                        <input type="text" class="form-control cep-input" 
                               name="conjuge_enderecos[${contadorConjugeEndereco}][cep]" 
                               placeholder="00000-000" 
                               maxlength="9"
                               oninput="this.value = this.value.replace(/\\D/g, '').replace(/^(\\d{5})(\\d)/, '$1-$2')"
                               onblur="buscarEnderecoPorCEPConjuge(this)"
                               required>
                        <div class="form-text">Digite 8 dígitos</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Logradouro</label>
                        <input type="text" class="form-control logradouro-field" name="conjuge_enderecos[${contadorConjugeEndereco}][logradouro]" placeholder="Rua, Avenida..." required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Número</label>
                        <input type="text" class="form-control" name="conjuge_enderecos[${contadorConjugeEndereco}][numero]" placeholder="123" required>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-4">
                        <label class="form-label">Complemento</label>
                        <input type="text" class="form-control" name="conjuge_enderecos[${contadorConjugeEndereco}][complemento]" placeholder="Apto, Sala...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Bairro</label>
                        <input type="text" class="form-control bairro-field" name="conjuge_enderecos[${contadorConjugeEndereco}][bairro]" placeholder="Nome do bairro" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Cidade</label>
                        <input type="text" class="form-control cidade-field" name="conjuge_enderecos[${contadorConjugeEndereco}][cidade]" placeholder="Nome da cidade" required>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger btn-sm w-100 mt-4" onclick="removerConjugeEndereco(${contadorConjugeEndereco})">
                            <i class="fas fa-trash"></i> Remover
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        container.insertAdjacentHTML('beforeend', enderecoHtml);
    });

    window.removerConjugeEndereco = function(index) {
        const item = document.querySelector(`.conjuge-endereco-item[data-index="${index}"]`);
        if (item) {
            item.remove();
            const container = document.getElementById('conjuge-enderecos-container');
            if (container.children.length === 0) {
                container.innerHTML = '<p class="text-muted">Nenhum endereço adicionado.</p>';
            }
        }
    };

    // Função específica para busca de CEP do cônjuge
    window.buscarEnderecoPorCEPConjuge = async function(input) {
        try {
            const cep = input.value.replace(/\D/g, '');
            console.log('CEP do cônjuge digitado:', cep);
            
            if (cep.length !== 8) {
                if (cep.length > 0) {
                    alert('CEP inválido. Deve conter 8 dígitos.');
                }
                return;
            }

            const addressBlock = input.closest('.conjuge-endereco-item');
            if (!addressBlock) {
                console.error('Não foi possível encontrar o bloco de endereço do cônjuge');
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
            console.log('Resposta do servidor para cônjuge:', data);

            if (data.success) {
                addressBlock.querySelector('.logradouro-field').value = data.logradouro || '';
                addressBlock.querySelector('.bairro-field').value = data.bairro || '';
                addressBlock.querySelector('.cidade-field').value = data.cidade || '';
                addressBlock.querySelector('.estado-field').value = data.estado || '';
            }

            inputs.forEach(i => i.disabled = false);
            input.classList.remove('loading');
        } catch (error) {
            console.error('Erro na busca de CEP do cônjuge:', error);
            const inputs = input.closest('.conjuge-endereco-item').querySelectorAll('input');
            inputs.forEach(i => i.disabled = false);
            input.classList.remove('loading');
            
            let errorMessage = 'Erro ao buscar CEP. Verifique o valor digitado.';
            if (error.message.includes('Failed to fetch')) {
                errorMessage = 'Erro de conexão. Verifique sua internet.';
            }
            alert(errorMessage);
        }
    };

    window.adicionarConjugeEmailExistente = async function(email) {
        const tipos = window.tiposEmail || await carregarTipos('email');
        window.tiposEmail = tipos;
        contadorConjugeEmail++;
        const container = document.getElementById('conjuge-emails-container');
        
        if (container.querySelector('.text-muted')) {
            container.innerHTML = '';
        }
        
        const emailHtml = `
            <div class="border p-3 mb-3 conjuge-email-item" data-index="${contadorConjugeEmail}">
                <div class="row align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Tipo de Email</label>
                        ${criarSelectTipos(tipos, `conjuge_emails[${contadorConjugeEmail}][tipo]`, `conjuge_email_tipo_${contadorConjugeEmail}`, `abrirModalTipoEmail(${contadorConjugeEmail})`, email.tipo)}
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="conjuge_emails[${contadorConjugeEmail}][email]" 
                            value="${email.email || ''}" placeholder="email@exemplo.com" required>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger btn-sm w-100" onclick="removerConjugeEmail(${contadorConjugeEmail})">
                            <i class="fas fa-trash"></i> Remover
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        container.insertAdjacentHTML('beforeend', emailHtml);
    };

});