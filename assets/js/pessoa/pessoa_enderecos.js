/**
 * Gerencia a funcionalidade de endereços
 * Endereço principal exibido no topo, demais em seção colapsável
 */
document.addEventListener('DOMContentLoaded', function() {
    let contadorEndereco = 0;

    function gerarEnderecoHtml(index, endereco, isPrincipal) {
        const id = endereco ? (endereco.id_endereco || endereco.id || '') : '';
        const principalChecked = isPrincipal ? 'checked' : '';
        const badgePrincipal = isPrincipal
            ? '<span class="badge bg-success ms-2">Principal</span>'
            : `<button type="button" class="btn btn-outline-success btn-sm ms-2" onclick="definirPrincipal(${index})">
                   <i class="fas fa-star"></i> Tornar principal
               </button>`;

        return `
            <div class="border p-3 mb-3 endereco-item ${isPrincipal ? 'border-success' : ''}" data-index="${index}" data-id="${id}" data-principal="${isPrincipal ? '1' : '0'}">
                <input type="hidden" class="estado-field" name="enderecos[${index}][estado]" value="${endereco ? (endereco.estado || '') : ''}">
                <input type="hidden" name="enderecos[${index}][principal]" value="${isPrincipal ? '1' : '0'}" class="principal-field">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>${badgePrincipal}</div>
                    <button type="button" class="btn btn-danger btn-sm" onclick="removerEndereco(${index})">
                        <i class="fas fa-trash"></i> Remover
                    </button>
                </div>
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-label">Tipo de Endereço</label>
                        ${criarSelectTipos(window.tiposEndereco || [], 'enderecos[' + index + '][tipo]', 'endereco_tipo_' + index, 'abrirModalTipoEndereco(' + index + ')', endereco ? endereco.tipo : null)}
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">CEP</label>
                        <input type="text" class="form-control cep-input"
                               name="enderecos[${index}][cep]"
                               value="${endereco ? (endereco.cep || '') : ''}"
                               placeholder="00000-000"
                               maxlength="9"
                               oninput="this.value = this.value.replace(/\\D/g, '').replace(/^(\\d{5})(\\d)/, '$1-$2')"
                               onblur="buscarEnderecoPorCEP(this)"
                               required>
                        <div class="form-text">Digite 8 dígitos</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Logradouro</label>
                        <input type="text" class="form-control logradouro-field" name="enderecos[${index}][logradouro]"
                            value="${endereco ? (endereco.logradouro || '') : ''}" placeholder="Rua, Avenida..." required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Número</label>
                        <input type="text" class="form-control" name="enderecos[${index}][numero]"
                            value="${endereco ? (endereco.numero || '') : ''}" placeholder="123" required>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-4">
                        <label class="form-label">Complemento</label>
                        <input type="text" class="form-control" name="enderecos[${index}][complemento]"
                            value="${endereco ? (endereco.complemento || '') : ''}" placeholder="Apto, Sala...">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Bairro</label>
                        <input type="text" class="form-control bairro-field" name="enderecos[${index}][bairro]"
                            value="${endereco ? (endereco.bairro || '') : ''}" placeholder="Nome do bairro" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Cidade</label>
                        <input type="text" class="form-control cidade-field" name="enderecos[${index}][cidade]"
                            value="${endereco ? (endereco.cidade || '') : ''}" placeholder="Nome da cidade" required>
                    </div>
                </div>
            </div>
        `;
    }

    function atualizarContadorExtras() {
        const extras = document.getElementById('enderecos-container');
        const wrapper = document.getElementById('enderecos-extras-wrapper');
        const label = document.getElementById('enderecos-extras-label');
        if (!extras || !wrapper) return;

        const count = extras.querySelectorAll('.endereco-item').length;
        if (count > 0) {
            wrapper.style.display = '';
            label.textContent = `Mais endereços (${count})`;
        } else {
            wrapper.style.display = 'none';
        }
    }

    function posicionarEndereco(html, isPrincipal) {
        if (isPrincipal) {
            const principal = document.getElementById('endereco-principal-container');
            principal.innerHTML = html;
        } else {
            const extras = document.getElementById('enderecos-container');
            extras.insertAdjacentHTML('beforeend', html);
            atualizarContadorExtras();
        }
    }

    document.getElementById('add-endereco')?.addEventListener('click', async function() {
        const tipos = window.tiposEndereco || await carregarTipos('endereco');
        window.tiposEndereco = tipos;
        contadorEndereco++;

        // Se não tem principal, o novo é principal
        const principalContainer = document.getElementById('endereco-principal-container');
        const temPrincipal = principalContainer && principalContainer.querySelector('.endereco-item');
        const isPrincipal = !temPrincipal;

        const html = gerarEnderecoHtml(contadorEndereco, null, isPrincipal);
        posicionarEndereco(html, isPrincipal);
    });

    window.adicionarEnderecoExistente = async function(endereco) {
        console.log('>>> adicionarEnderecoExistente CHAMADO com:', JSON.stringify(endereco));
        try {
            const tipos = window.tiposEndereco || await carregarTipos('endereco');
            window.tiposEndereco = tipos;
            contadorEndereco++;

            const isPrincipal = !!endereco.principal;
            console.log('>>> isPrincipal:', isPrincipal, 'contador:', contadorEndereco);
            const html = gerarEnderecoHtml(contadorEndereco, endereco, isPrincipal);
            console.log('>>> HTML gerado OK, length:', html.length);
            posicionarEndereco(html, isPrincipal);
            console.log('>>> posicionarEndereco OK');
        } catch (err) {
            console.error('>>> ERRO em adicionarEnderecoExistente:', err);
        }
    };

    window.definirPrincipal = async function(index) {
        const item = document.querySelector(`.endereco-item[data-index="${index}"]`);
        if (!item) return;

        const id = item.dataset.id;

        // Se tem id (já salvo), chamar API
        if (id) {
            try {
                const url = window.ROUTES.setEnderecoPrincipal.replace('__ID__', id);
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                if (!res.ok) throw new Error(res.statusText);
                const data = await res.json();
                if (!data.success) {
                    alert(data.message || 'Erro ao definir principal');
                    return;
                }
            } catch (e) {
                console.error(e);
                alert('Erro de rede ao definir principal.');
                return;
            }
        }

        // Mover antigo principal para extras
        const antigoPrincipal = document.querySelector('#endereco-principal-container .endereco-item');
        if (antigoPrincipal) {
            antigoPrincipal.dataset.principal = '0';
            antigoPrincipal.classList.remove('border-success');
            antigoPrincipal.querySelector('.principal-field').value = '0';
            // Trocar badge por botão
            const headerDiv = antigoPrincipal.querySelector('.d-flex > div:first-child');
            const idx = antigoPrincipal.dataset.index;
            headerDiv.innerHTML = `<button type="button" class="btn btn-outline-success btn-sm ms-2" onclick="definirPrincipal(${idx})">
                <i class="fas fa-star"></i> Tornar principal
            </button>`;
            document.getElementById('enderecos-container').appendChild(antigoPrincipal);
        }

        // Mover novo principal para container principal
        item.dataset.principal = '1';
        item.classList.add('border-success');
        item.querySelector('.principal-field').value = '1';
        const headerDiv = item.querySelector('.d-flex > div:first-child');
        headerDiv.innerHTML = '<span class="badge bg-success ms-2">Principal</span>';
        document.getElementById('endereco-principal-container').innerHTML = '';
        document.getElementById('endereco-principal-container').appendChild(item);

        atualizarContadorExtras();
    };

    window.removerEndereco = async function (index) {
        const item = document.querySelector(`.endereco-item[data-index="${index}"]`);
        if (!item) return;

        const id = item.dataset.id;
        const isPrincipal = item.dataset.principal === '1';

        if (id) {
            if (!confirm('Excluir este endereço?')) return;

            try {
                const res = await fetch(`/pessoa/endereco/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!res.ok) throw new Error(res.statusText);
                const data = await res.json();
                if (!data.success) {
                    alert(data.message || 'Erro ao excluir');
                    return;
                }
            } catch (e) {
                console.error(e);
                alert('Erro de rede – veja o console (F12).');
                return;
            }
        }

        item.remove();

        // Se removeu o principal, promover o primeiro extra
        if (isPrincipal) {
            const principalContainer = document.getElementById('endereco-principal-container');
            principalContainer.innerHTML = '<p class="text-muted">Nenhum endereço principal.</p>';

            const primeiroExtra = document.querySelector('#enderecos-container .endereco-item');
            if (primeiroExtra) {
                window.definirPrincipal(primeiroExtra.dataset.index);
            }
        }

        atualizarContadorExtras();
    };

    window.abrirModalTipoEndereco = function(index) {
        window.enderecoIndexAtual = index;
        new bootstrap.Modal(document.getElementById('modalNovoTipoEndereco')).show();
    };

    window.buscarEnderecoPorCEP = async function(input) {
        try {
            const cep = input.value.replace(/\D/g, '');
            if (cep.length !== 8) {
                if (cep.length > 0) {
                    alert('CEP inválido. Deve conter 8 dígitos.');
                }
                return;
            }

            const addressBlock = input.closest('.endereco-item');
            if (!addressBlock) return;

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

            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            const data = await response.json();
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
            alert('Erro ao buscar CEP. Verifique o valor digitado.');
        }
    };
});
