/**
 * Gerencia a funcionalidade de endereços
 * Responsável pela criação, remoção e busca de CEP
 */
document.addEventListener('DOMContentLoaded', function() {
    let contadorEndereco = 0;

    document.getElementById('add-endereco')?.addEventListener('click', async function() {
        const tipos = window.tiposEndereco || await carregarTipos('endereco');
        window.tiposEndereco = tipos;
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

    window.adicionarEnderecoExistente = async function(endereco) {
        const tipos = window.tiposEndereco || await carregarTipos('endereco');
        window.tiposEndereco = tipos;
        contadorEndereco++;
        const container = document.getElementById('enderecos-container');

        if (container.querySelector('.text-muted')) {
            container.innerHTML = '';
        }

        const enderecoHtml = `
            <div class="border p-3 mb-3 endereco-item" data-index="${contadorEndereco}">
                <input type="hidden" class="estado-field" name="enderecos[${contadorEndereco}][estado]" value="${endereco.estado || ''}">
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-label">Tipo de Endereço</label>
                        ${criarSelectTipos(tipos, `enderecos[${contadorEndereco}][tipo]`, `endereco_tipo_${contadorEndereco}`, `abrirModalTipoEndereco(${contadorEndereco})`, endereco.tipo)}
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">CEP</label>
                        <input type="text" class="form-control cep-input" 
                            name="enderecos[${contadorEndereco}][cep]" 
                            value="${endereco.cep || ''}"
                            placeholder="00000-000" 
                            maxlength="9"
                            oninput="this.value = this.value.replace(/\\D/g, '').replace(/^(\\d{5})(\\d)/, '$1-$2')"
                            onblur="buscarEnderecoPorCEP(this)"
                            required>
                        <div class="form-text">Digite 8 dígitos</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Logradouro</label>
                        <input type="text" class="form-control logradouro-field" name="enderecos[${contadorEndereco}][logradouro]" 
                            value="${endereco.logradouro || ''}" placeholder="Rua, Avenida..." required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Número</label>
                        <input type="text" class="form-control" name="enderecos[${contadorEndereco}][numero]" 
                            value="${endereco.numero || ''}" placeholder="123" required>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-4">
                        <label class="form-label">Complemento</label>
                        <input type="text" class="form-control" name="enderecos[${contadorEndereco}][complemento]" 
                            value="${endereco.complemento || ''}" placeholder="Apto, Sala...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Bairro</label>
                        <input type="text" class="form-control bairro-field" name="enderecos[${contadorEndereco}][bairro]" 
                            value="${endereco.bairro || ''}" placeholder="Nome do bairro" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Cidade</label>
                        <input type="text" class="form-control cidade-field" name="enderecos[${contadorEndereco}][cidade]" 
                            value="${endereco.cidade || ''}" placeholder="Nome da cidade" required>
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
    };

    window.abrirModalTipoEndereco = function(index) {
        window.enderecoIndexAtual = index;
        new bootstrap.Modal(document.getElementById('modalNovoTipoEndereco')).show();
    };

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
    };
});
