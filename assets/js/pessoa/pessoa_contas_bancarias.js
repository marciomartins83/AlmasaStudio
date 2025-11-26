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

    // Funções para abrir modais (caso seja necessário adicionar novos bancos/agências/tipos)
    window.abrirModalBanco = function(index) {
        window.contaBancariaIndexAtual = index;
        // Implementar modal para adicionar novo banco
        alert('Funcionalidade de adicionar novo banco será implementada');
    };

    window.abrirModalAgencia = function(index) {
        window.contaBancariaIndexAtual = index;
        // Implementar modal para adicionar nova agência
        alert('Funcionalidade de adicionar nova agência será implementada');
    };

    window.abrirModalTipoConta = function(index) {
        window.contaBancariaIndexAtual = index;
        // Implementar modal para adicionar novo tipo de conta
        alert('Funcionalidade de adicionar novo tipo de conta será implementada');
    };
});