/**
 * Gerencia a funcionalidade de profissões
 * Responsável pela adição, remoção e validação de profissões
 */
document.addEventListener('DOMContentLoaded', function() {
    let contadorProfissao = 0;

    document.getElementById('add-profissao')?.addEventListener('click', async function() {
        const tipos = window.tiposProfissao || await carregarTipos('profissao');
        window.tiposProfissao = tipos;
        contadorProfissao++;
        const container = document.getElementById('profissoes-container');
        
        if (container.querySelector('.text-muted')) {
            container.innerHTML = '';
        }
        
        const profissaoHtml = `
            <div class="border p-3 mb-3 profissao-item" data-index="${contadorProfissao}">
                <div class="row">
                    <div class="col-md-4">
                        <label class="form-label">Profissão</label>
                        ${criarSelectTipos(tipos, `profissoes[${contadorProfissao}][profissao]`, `profissao_tipo_${contadorProfissao}`, `abrirModalTipoProfissao(${contadorProfissao})`)}
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Empresa</label>
                        <input type="text" class="form-control" name="profissoes[${contadorProfissao}][empresa]" placeholder="Nome da empresa">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Renda</label>
                        <input type="number" class="form-control" name="profissoes[${contadorProfissao}][renda]" placeholder="0,00" step="0.01">
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-3">
                        <label class="form-label">Data Admissão</label>
                        <input type="date" class="form-control" name="profissoes[${contadorProfissao}][data_admissao]">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Data Demissão</label>
                        <input type="date" class="form-control" name="profissoes[${contadorProfissao}][data_demissao]">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Ativo</label>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" name="profissoes[${contadorProfissao}][ativo]" value="1" checked>
                            <label class="form-check-label">Ativo</label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger btn-sm w-100 mt-4" onclick="removerProfissao(${contadorProfissao})">
                            <i class="fas fa-trash"></i> Remover
                        </button>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-12">
                        <label class="form-label">Observações</label>
                        <textarea class="form-control" name="profissoes[${contadorProfissao}][observacoes]" rows="2" placeholder="Observações sobre a profissão"></textarea>
                    </div>
                </div>
            </div>
        `;
        
        container.insertAdjacentHTML('beforeend', profissaoHtml);
    });
    
    window.adicionarProfissaoExistente = async function(profissao) {
        const tipos = window.tiposProfissao || await carregarTipos('profissao');
        window.tiposProfissao = tipos;
        contadorProfissao++;
        const container = document.getElementById('profissoes-container');
        
        if (container.querySelector('.text-muted')) {
            container.innerHTML = '';
        }
        
        const profissaoHtml = `
            <div class="border p-3 mb-3 profissao-item" data-index="${contadorProfissao}" data-id="${profissao.id || ''}">
                <div class="row">
                    <div class="col-md-4">
                        <label class="form-label">Profissão</label>
                        ${criarSelectTipos(tipos, `profissoes[${contadorProfissao}][profissao]`, `profissao_tipo_${contadorProfissao}`, `abrirModalTipoProfissao(${contadorProfissao})`, profissao.profissao)}
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Empresa</label>
                        <input type="text" class="form-control" name="profissoes[${contadorProfissao}][empresa]" 
                            value="${profissao.empresa || ''}" placeholder="Nome da empresa">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Renda</label>
                        <input type="number" step="0.01" class="form-control" name="profissoes[${contadorProfissao}][renda]" 
                            value="${profissao.renda || ''}" placeholder="0,00">
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-3">
                        <label class="form-label">Data de Admissão</label>
                        <input type="date" class="form-control" name="profissoes[${contadorProfissao}][data_admissao]" 
                            value="${profissao.dataAdmissao || ''}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Data de Demissão</label>
                        <input type="date" class="form-control" name="profissoes[${contadorProfissao}][data_demissao]" 
                            value="${profissao.dataDemissao || ''}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Observações</label>
                        <input type="text" class="form-control" name="profissoes[${contadorProfissao}][observacoes]" 
                            value="${profissao.observacoes || ''}" placeholder="Observações">
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger btn-sm w-100 mt-4" onclick="removerProfissao(${contadorProfissao})">
                            <i class="fas fa-trash"></i> Remover
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        container.insertAdjacentHTML('beforeend', profissaoHtml);
    };

    window.removerProfissao = async function (index) {
        const item = document.querySelector(`.profissao-item[data-index="${index}"]`);
        if (!item) return;

        const id = item.dataset.id;
        if (!id) { // profissão nova – só limpa
            item.remove();
            const container = document.getElementById('profissoes-container');
            if (container.children.length === 0) {
                container.innerHTML = '<p class="text-muted">Nenhuma profissão adicionada.</p>';
            }
            return;
        }

        if (!confirm('Excluir esta profissão?')) return;

        try {
            const res = await fetch(`/pessoa/profissao/${id}`, {
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
                const container = document.getElementById('profissoes-container');
                if (container.children.length === 0) {
                    container.innerHTML = '<p class="text-muted">Nenhuma profissão adicionada.</p>';
                }
            } else {
                alert(data.message || 'Erro ao excluir');
            }
        } catch (e) {
            console.error(e);
            alert('Erro de rede – veja o console (F12).');
        }
    };
    
    window.abrirModalTipoProfissao = function(index) {
        window.profissaoIndexAtual = index;
        new bootstrap.Modal(document.getElementById('modalNovoTipoProfissao')).show();
    };
});