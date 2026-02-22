/**
 * Gerencia a funcionalidade de contas bancárias da pessoa
 * Responsável pela adição, remoção e validação de contas bancárias
 */
document.addEventListener('DOMContentLoaded', function() {
    let contadorContaBancaria = 0;

    document.getElementById('add-conta-bancaria')?.addEventListener('click', async function() {
        const bancos = window.bancos || await carregarTipos('banco');
        const agencias = window.agencias || await carregarTipos('agencia');
        const tiposContas = window.tiposContasBancarias || await carregarTipos('tipo_conta_bancaria');

        window.bancos = bancos;
        window.agencias = agencias;
        window.tiposContasBancarias = tiposContas;

        contadorContaBancaria++;
        const container = document.getElementById('contas-bancarias-container');

        if (container.querySelector('.text-muted')) {
            container.innerHTML = '';
        }

        const contaHtml = `
            <div class="border p-3 mb-3 conta-bancaria-item" data-index="${contadorContaBancaria}">
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-label">Banco <span class="text-danger">*</span></label>
                        ${criarSelectTipos(bancos, `contas_bancarias[${contadorContaBancaria}][banco]`, `conta_banco_${contadorContaBancaria}`, `abrirModalBanco(${contadorContaBancaria})`)}
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Agência</label>
                        ${criarSelectTipos(agencias, `contas_bancarias[${contadorContaBancaria}][agencia]`, `conta_agencia_${contadorContaBancaria}`, `abrirModalAgencia(${contadorContaBancaria})`)}
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Conta <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="contas_bancarias[${contadorContaBancaria}][codigo]" required>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">Dígito</label>
                        <input type="text" class="form-control" name="contas_bancarias[${contadorContaBancaria}][digito_conta]" maxlength="2">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tipo de Conta</label>
                        ${criarSelectTipos(tiposContas, `contas_bancarias[${contadorContaBancaria}][tipo_conta]`, `conta_tipo_${contadorContaBancaria}`, `abrirModalTipoConta(${contadorContaBancaria})`)}
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-4">
                        <label class="form-label">Titular</label>
                        <input type="text" class="form-control" name="contas_bancarias[${contadorContaBancaria}][titular]" placeholder="Nome do titular">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Descrição</label>
                        <input type="text" class="form-control" name="contas_bancarias[${contadorContaBancaria}][descricao]" placeholder="Descrição da conta">
                    </div>
                    <div class="col-md-2">
                        <div class="form-check mt-4">
                            <input class="form-check-input" type="checkbox" name="contas_bancarias[${contadorContaBancaria}][principal]" id="conta_principal_${contadorContaBancaria}">
                            <label class="form-check-label" for="conta_principal_${contadorContaBancaria}">
                                Conta Principal
                            </label>
                        </div>
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-danger btn-sm w-100 mt-4" onclick="removerContaBancaria(${contadorContaBancaria})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;

        container.insertAdjacentHTML('beforeend', contaHtml);
    });

    window.removerContaBancaria = async function(index) {
        const item = document.querySelector(`.conta-bancaria-item[data-index="${index}"]`);
        if (!item) return;

        const id = item.dataset.id;
        if (!id) {
            item.remove();
            return;
        }

        if (!confirm('Tem certeza que deseja excluir esta conta bancária?')) return;

        try {
            const res = await fetch(`/pessoa/conta-bancaria/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!res.ok) throw new Error(res.statusText);

            const data = await res.json();
            if (data.success) {
                item.remove();

                // Se container ficar vazio, mostrar mensagem
                const container = document.getElementById('contas-bancarias-container');
                if (!container.querySelector('.conta-bancaria-item')) {
                    container.innerHTML = '<p class="text-muted">Nenhuma conta bancária adicionada.</p>';
                }
            } else {
                alert(data.message || 'Erro ao excluir conta bancária');
            }
        } catch (error) {
            console.error('Erro:', error);
            alert('Erro ao excluir conta bancária');
        }
    };

    // Função para carregar contas bancárias existentes
    window.carregarContasBancarias = function(contas) {
        const container = document.getElementById('contas-bancarias-container');
        if (!contas || contas.length === 0) return;

        container.innerHTML = '';

        contas.forEach((conta, index) => {
            contadorContaBancaria++;
            const contaHtml = `
                <div class="border p-3 mb-3 conta-bancaria-item" data-index="${contadorContaBancaria}" data-id="${conta.id}">
                    <div class="row">
                        <div class="col-md-3">
                            <label class="form-label">Banco <span class="text-danger">*</span></label>
                            <select class="form-select" name="contas_bancarias[${contadorContaBancaria}][banco]" required>
                                ${window.bancos ? window.bancos.map(b =>
                                    `<option value="${b.id}" ${b.id == conta.banco ? 'selected' : ''}>${b.nome}</option>`
                                ).join('') : ''}
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Agência</label>
                            <select class="form-select" name="contas_bancarias[${contadorContaBancaria}][agencia]">
                                <option value="">Selecione...</option>
                                ${window.agencias ? window.agencias.map(a =>
                                    `<option value="${a.id}" ${a.id == conta.agencia ? 'selected' : ''}>${a.nome}</option>`
                                ).join('') : ''}
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Conta <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="contas_bancarias[${contadorContaBancaria}][codigo]" value="${conta.codigo || ''}" required>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">Dígito</label>
                            <input type="text" class="form-control" name="contas_bancarias[${contadorContaBancaria}][digito_conta]" value="${conta.digitoConta || ''}" maxlength="2">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tipo de Conta</label>
                            <select class="form-select" name="contas_bancarias[${contadorContaBancaria}][tipo_conta]">
                                <option value="">Selecione...</option>
                                ${window.tiposContasBancarias ? window.tiposContasBancarias.map(t =>
                                    `<option value="${t.id}" ${t.id == conta.tipoConta ? 'selected' : ''}>${t.tipo}</option>`
                                ).join('') : ''}
                            </select>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-4">
                            <label class="form-label">Titular</label>
                            <input type="text" class="form-control" name="contas_bancarias[${contadorContaBancaria}][titular]" value="${conta.titular || ''}" placeholder="Nome do titular">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Descrição</label>
                            <input type="text" class="form-control" name="contas_bancarias[${contadorContaBancaria}][descricao]" value="${conta.descricao || ''}" placeholder="Descrição da conta">
                        </div>
                        <div class="col-md-2">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" name="contas_bancarias[${contadorContaBancaria}][principal]" id="conta_principal_${contadorContaBancaria}" ${conta.principal ? 'checked' : ''}>
                                <label class="form-check-label" for="conta_principal_${contadorContaBancaria}">
                                    Conta Principal
                                </label>
                            </div>
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-danger btn-sm w-100 mt-4" onclick="removerContaBancaria(${contadorContaBancaria})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', contaHtml);
        });
    };

    // Funções para abrir modais
    window.abrirModalBanco = function(index) {
        window.contaBancariaIndexAtual = index;
        const modal = new bootstrap.Modal(document.getElementById('modalNovoBanco'));
        modal.show();
    };

    window.abrirModalAgencia = function(index) {
        window.contaBancariaIndexAtual = index;

        // Carregar bancos no select do modal
        const selectBanco = document.getElementById('novaAgenciaBanco');
        selectBanco.innerHTML = '<option value="">Selecione o banco...</option>';

        if (window.bancos) {
            window.bancos.forEach(banco => {
                selectBanco.innerHTML += `<option value="${banco.id}">${banco.nome || banco.tipo}</option>`;
            });
        }

        const modal = new bootstrap.Modal(document.getElementById('modalNovaAgencia'));
        modal.show();
    };

    window.abrirModalTipoConta = function(index) {
        window.contaBancariaIndexAtual = index;
        const modal = new bootstrap.Modal(document.getElementById('modalNovoTipoContaBancaria'));
        modal.show();
    };

    // Event listeners para salvar novos registros
    document.getElementById('salvarBanco')?.addEventListener('click', async function() {
        const nome = document.getElementById('novoBancoNome').value.trim();
        const numero = document.getElementById('novoBancoNumero').value.trim();

        if (!nome || !numero) {
            alert('Por favor, preencha todos os campos obrigatórios');
            return;
        }

        try {
            const response = await fetch('/pessoa/salvar-banco', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ nome, numero: parseInt(numero) })
            });

            const data = await response.json();

            if (data.success) {
                // Adicionar o novo banco ao array global
                if (!window.bancos) window.bancos = [];
                window.bancos.push(data.banco);

                // Atualizar o select atual
                const selectId = `conta_banco_${window.contaBancariaIndexAtual}`;
                const select = document.getElementById(selectId);
                if (select) {
                    const option = new Option(data.banco.nome, data.banco.id, true, true);
                    select.add(option);
                }

                // Limpar e fechar modal
                document.getElementById('novoBancoNome').value = '';
                document.getElementById('novoBancoNumero').value = '';
                bootstrap.Modal.getInstance(document.getElementById('modalNovoBanco')).hide();
            } else {
                alert(data.message || 'Erro ao salvar banco');
            }
        } catch (error) {
            console.error('Erro:', error);
            alert('Erro ao salvar banco');
        }
    });

    document.getElementById('salvarAgencia')?.addEventListener('click', async function() {
        const banco = document.getElementById('novaAgenciaBanco').value;
        const codigo = document.getElementById('novaAgenciaNumero').value.trim();
        const nome = document.getElementById('novaAgenciaNome').value.trim();

        if (!banco || !codigo || !nome) {
            alert('Por favor, preencha todos os campos obrigatórios');
            return;
        }

        try {
            const response = await fetch('/pessoa/salvar-agencia', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    banco: parseInt(banco),
                    codigo,
                    nome
                })
            });

            const data = await response.json();

            if (data.success) {
                // Adicionar a nova agência ao array global
                if (!window.agencias) window.agencias = [];
                window.agencias.push(data.agencia);

                // Atualizar o select atual
                const selectId = `conta_agencia_${window.contaBancariaIndexAtual}`;
                const select = document.getElementById(selectId);
                if (select) {
                    const option = new Option(data.agencia.nome, data.agencia.id, true, true);
                    select.add(option);
                }

                // Limpar e fechar modal
                document.getElementById('novaAgenciaBanco').value = '';
                document.getElementById('novaAgenciaNumero').value = '';
                document.getElementById('novaAgenciaNome').value = '';
                bootstrap.Modal.getInstance(document.getElementById('modalNovaAgencia')).hide();
            } else {
                alert(data.message || 'Erro ao salvar agência');
            }
        } catch (error) {
            console.error('Erro:', error);
            alert('Erro ao salvar agência');
        }
    });

    document.getElementById('salvarTipoContaBancaria')?.addEventListener('click', async function() {
        const tipo = document.getElementById('novoTipoContaBancaria').value.trim();

        if (!tipo) {
            alert('Por favor, informe o tipo de conta');
            return;
        }

        try {
            const response = await fetch('/pessoa/salvar-tipo-conta-bancaria', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ tipo })
            });

            const data = await response.json();

            if (data.success) {
                // Adicionar o novo tipo ao array global
                if (!window.tiposContasBancarias) window.tiposContasBancarias = [];
                window.tiposContasBancarias.push(data.tipoConta);

                // Atualizar o select atual
                const selectId = `conta_tipo_${window.contaBancariaIndexAtual}`;
                const select = document.getElementById(selectId);
                if (select) {
                    const option = new Option(data.tipoConta.tipo, data.tipoConta.id, true, true);
                    select.add(option);
                }

                // Limpar e fechar modal
                document.getElementById('novoTipoContaBancaria').value = '';
                bootstrap.Modal.getInstance(document.getElementById('modalNovoTipoContaBancaria')).hide();
            } else {
                alert(data.message || 'Erro ao salvar tipo de conta');
            }
        } catch (error) {
            console.error('Erro:', error);
            alert('Erro ao salvar tipo de conta');
        }
    });
});