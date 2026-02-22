/**
 * Gerencia a funcionalidade de endereços do cônjuge
 * Responsável pela criação, remoção e busca de CEP
 */
document.addEventListener('DOMContentLoaded', function() {
    let contadorConjugeEndereco = 0;

    // Botão para copiar endereços da pessoa principal para o cônjuge
    document.getElementById('copy-enderecos-to-conjuge')?.addEventListener('click', async function() {
        const enderecosContainer = document.getElementById('enderecos-container');
        if (!enderecosContainer) {
            alert('Seção de endereços da pessoa principal não encontrada.');
            return;
        }

        const enderecoItems = enderecosContainer.querySelectorAll('.endereco-item');
        if (enderecoItems.length === 0) {
            alert('Nenhum endereço para copiar. Adicione endereços à pessoa principal primeiro.');
            return;
        }

        // Extrai dados de cada endereço e adiciona ao cônjuge
        for (const item of enderecoItems) {
            const endereco = {
                tipo: item.querySelector('[name*="[tipo]"]')?.value || '',
                cep: item.querySelector('[name*="[cep]"]')?.value || '',
                logradouro: item.querySelector('[name*="[logradouro]"]')?.value || '',
                numero: item.querySelector('[name*="[numero]"]')?.value || '',
                complemento: item.querySelector('[name*="[complemento]"]')?.value || '',
                bairro: item.querySelector('[name*="[bairro]"]')?.value || '',
                cidade: item.querySelector('[name*="[cidade]"]')?.value || '',
                estado: item.querySelector('.estado-field')?.value || ''
            };

            await adicionarConjugeEnderecoExistente(endereco);
        }

        alert(`${enderecoItems.length} endereço(s) copiado(s) para o cônjuge com sucesso!`);
    });

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

    window.adicionarConjugeEnderecoExistente = async function(endereco) {
        const tipos = window.tiposEndereco || await carregarTipos('endereco');
        window.tiposEndereco = tipos;
        contadorConjugeEndereco++;
        const container = document.getElementById('conjuge-enderecos-container');

        if (container.querySelector('.text-muted')) {
            container.innerHTML = '';
        }

        const enderecoHtml = `
            <div class="border p-3 mb-3 conjuge-endereco-item" data-index="${contadorConjugeEndereco}">
                <input type="hidden" class="estado-field" name="conjuge_enderecos[${contadorConjugeEndereco}][estado]" value="${endereco.estado || ''}">
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-label">Tipo de Endereço</label>
                        ${criarSelectTipos(tipos, `conjuge_enderecos[${contadorConjugeEndereco}][tipo]`, `conjuge_endereco_tipo_${contadorConjugeEndereco}`, `abrirModalTipoEndereco(${contadorConjugeEndereco})`, endereco.tipo)}
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">CEP</label>
                        <input type="text" class="form-control cep-input" 
                            name="conjuge_enderecos[${contadorConjugeEndereco}][cep]" 
                            value="${endereco.cep || ''}"
                            placeholder="00000-000" 
                            maxlength="9"
                            oninput="this.value = this.value.replace(/\\D/g, '').replace(/^(\\d{5})(\\d)/, '$1-$2')"
                            onblur="buscarEnderecoPorCEPConjuge(this)"
                            required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Logradouro</label>
                        <input type="text" class="form-control logradouro-field" name="conjuge_enderecos[${contadorConjugeEndereco}][logradouro]" 
                            value="${endereco.logradouro || ''}" placeholder="Rua, Avenida..." required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Número</label>
                        <input type="text" class="form-control" name="conjuge_enderecos[${contadorConjugeEndereco}][numero]" 
                            value="${endereco.numero || ''}" placeholder="123" required>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-4">
                        <label class="form-label">Complemento</label>
                        <input type="text" class="form-control" name="conjuge_enderecos[${contadorConjugeEndereco}][complemento]" 
                            value="${endereco.complemento || ''}" placeholder="Apto, Sala...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Bairro</label>
                        <input type="text" class="form-control bairro-field" name="conjuge_enderecos[${contadorConjugeEndereco}][bairro]" 
                            value="${endereco.bairro || ''}" placeholder="Nome do bairro" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Cidade</label>
                        <input type="text" class="form-control cidade-field" name="conjuge_enderecos[${contadorConjugeEndereco}][cidade]" 
                            value="${endereco.cidade || ''}" placeholder="Nome da cidade" required>
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
    };

});