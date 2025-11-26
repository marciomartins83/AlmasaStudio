/**
 * Gerencia a funcionalidade de contas bancárias do cônjuge
 * Responsável pela adição, remoção e validação de contas bancárias do cônjuge
 */
document.addEventListener('DOMContentLoaded', function() {
    let contadorConjugeContaBancaria = 0;

    document.getElementById('add-conjuge-conta-bancaria')?.addEventListener('click', async function() {
        const bancos = window.bancos || await carregarTipos('banco');
        const agencias = window.agencias || await carregarTipos('agencia');
        const tiposContas = window.tiposContasBancarias || await carregarTipos('tipo_conta_bancaria');

        window.bancos = bancos;
        window.agencias = agencias;
        window.tiposContasBancarias = tiposContas;

        contadorConjugeContaBancaria++;
        const container = document.getElementById('conjuge-contas-bancarias-container');

        if (container.querySelector('.text-muted')) {
            container.innerHTML = '';
        }

        const contaHtml = `
            <div class="border p-3 mb-3 conjuge-conta-bancaria-item" data-index="${contadorConjugeContaBancaria}">
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-label">Banco <span class="text-danger">*</span></label>
                        ${criarSelectTipos(bancos, `conjuge_contas_bancarias[${contadorConjugeContaBancaria}][banco]`, `conjuge_conta_banco_${contadorConjugeContaBancaria}`, `abrirModalConjugeBanco(${contadorConjugeContaBancaria})`)}
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Agência</label>
                        ${criarSelectTipos(agencias, `conjuge_contas_bancarias[${contadorConjugeContaBancaria}][agencia]`, `conjuge_conta_agencia_${contadorConjugeContaBancaria}`, `abrirModalConjugeAgencia(${contadorConjugeContaBancaria})`)}
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Conta <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="conjuge_contas_bancarias[${contadorConjugeContaBancaria}][codigo]" required>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">Dígito</label>
                        <input type="text" class="form-control" name="conjuge_contas_bancarias[${contadorConjugeContaBancaria}][digito_conta]" maxlength="2">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tipo de Conta</label>
                        ${criarSelectTipos(tiposContas, `conjuge_contas_bancarias[${contadorConjugeContaBancaria}][tipo_conta]`, `conjuge_conta_tipo_${contadorConjugeContaBancaria}`, `abrirModalConjugeTipoConta(${contadorConjugeContaBancaria})`)}
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-4">
                        <label class="form-label">Titular</label>
                        <input type="text" class="form-control" name="conjuge_contas_bancarias[${contadorConjugeContaBancaria}][titular]" placeholder="Nome do titular">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Descrição</label>
                        <input type="text" class="form-control" name="conjuge_contas_bancarias[${contadorConjugeContaBancaria}][descricao]" placeholder="Descrição da conta">
                    </div>
                    <div class="col-md-2">
                        <div class="form-check mt-4">
                            <input class="form-check-input" type="checkbox" name="conjuge_contas_bancarias[${contadorConjugeContaBancaria}][principal]" id="conjuge_conta_principal_${contadorConjugeContaBancaria}">
                            <label class="form-check-label" for="conjuge_conta_principal_${contadorConjugeContaBancaria}">
                                Conta Principal
                            </label>
                        </div>
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-danger btn-sm w-100 mt-4" onclick="removerConjugeContaBancaria(${contadorConjugeContaBancaria})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;

        container.insertAdjacentHTML('beforeend', contaHtml);
    });

    window.removerConjugeContaBancaria = async function(index) {
        const item = document.querySelector(`.conjuge-conta-bancaria-item[data-index="${index}"]`);
        if (!item) return;

        const id = item.dataset.id;
        if (!id) {
            item.remove();
            return;
        }

        if (!confirm('Tem certeza que deseja excluir esta conta bancária do cônjuge?')) return;

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
                const container = document.getElementById('conjuge-contas-bancarias-container');
                if (!container.querySelector('.conjuge-conta-bancaria-item')) {
                    container.innerHTML = '<p class="text-muted">Nenhuma conta bancária adicionada.</p>';
                }
            } else {
                alert(data.message || 'Erro ao excluir conta bancária do cônjuge');
            }
        } catch (error) {
            console.error('Erro:', error);
            alert('Erro ao excluir conta bancária do cônjuge');
        }
    };

    // Função para carregar contas bancárias existentes do cônjuge
    window.carregarConjugeContasBancarias = function(contas) {
        const container = document.getElementById('conjuge-contas-bancarias-container');
        if (!contas || contas.length === 0) return;

        container.innerHTML = '';

        contas.forEach((conta, index) => {
            contadorConjugeContaBancaria++;
            const contaHtml = `
                <div class="border p-3 mb-3 conjuge-conta-bancaria-item" data-index="${contadorConjugeContaBancaria}" data-id="${conta.id}">
                    <div class="row">
                        <div class="col-md-3">
                            <label class="form-label">Banco <span class="text-danger">*</span></label>
                            <select class="form-select" name="conjuge_contas_bancarias[${contadorConjugeContaBancaria}][banco]" required>
                                ${window.bancos ? window.bancos.map(b =>
                                    `<option value="${b.id}" ${b.id == conta.banco ? 'selected' : ''}>${b.nome}</option>`
                                ).join('') : ''}
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Agência</label>
                            <select class="form-select" name="conjuge_contas_bancarias[${contadorConjugeContaBancaria}][agencia]">
                                <option value="">Selecione...</option>
                                ${window.agencias ? window.agencias.map(a =>
                                    `<option value="${a.id}" ${a.id == conta.agencia ? 'selected' : ''}>${a.nome}</option>`
                                ).join('') : ''}
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Conta <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="conjuge_contas_bancarias[${contadorConjugeContaBancaria}][codigo]" value="${conta.codigo || ''}" required>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">Dígito</label>
                            <input type="text" class="form-control" name="conjuge_contas_bancarias[${contadorConjugeContaBancaria}][digito_conta]" value="${conta.digitoConta || ''}" maxlength="2">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tipo de Conta</label>
                            <select class="form-select" name="conjuge_contas_bancarias[${contadorConjugeContaBancaria}][tipo_conta]">
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
                            <input type="text" class="form-control" name="conjuge_contas_bancarias[${contadorConjugeContaBancaria}][titular]" value="${conta.titular || ''}" placeholder="Nome do titular">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Descrição</label>
                            <input type="text" class="form-control" name="conjuge_contas_bancarias[${contadorConjugeContaBancaria}][descricao]" value="${conta.descricao || ''}" placeholder="Descrição da conta">
                        </div>
                        <div class="col-md-2">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" name="conjuge_contas_bancarias[${contadorConjugeContaBancaria}][principal]" id="conjuge_conta_principal_${contadorConjugeContaBancaria}" ${conta.principal ? 'checked' : ''}>
                                <label class="form-check-label" for="conjuge_conta_principal_${contadorConjugeContaBancaria}">
                                    Conta Principal
                                </label>
                            </div>
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-danger btn-sm w-100 mt-4" onclick="removerConjugeContaBancaria(${contadorConjugeContaBancaria})">
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
    window.abrirModalConjugeBanco = function(index) {
        window.conjugeContaBancariaIndexAtual = index;
        // Implementar modal para adicionar novo banco
        alert('Funcionalidade de adicionar novo banco será implementada');
    };

    window.abrirModalConjugeAgencia = function(index) {
        window.conjugeContaBancariaIndexAtual = index;
        // Implementar modal para adicionar nova agência
        alert('Funcionalidade de adicionar nova agência será implementada');
    };

    window.abrirModalConjugeTipoConta = function(index) {
        window.conjugeContaBancariaIndexAtual = index;
        // Implementar modal para adicionar novo tipo de conta
        alert('Funcionalidade de adicionar novo tipo de conta será implementada');
    };
});