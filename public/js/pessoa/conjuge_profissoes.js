/**
 * Gerencia a funcionalidade de profissões do cônjuge
 * Responsável pela adição, remoção e validação de profissões do cônjuge
 */
document.addEventListener('DOMContentLoaded', function() {
    let contadorConjugeProfissao = 0;

    document.getElementById('add-conjuge-profissao')?.addEventListener('click', async function() {
        const tipos = window.tiposProfissao || await carregarTipos('profissao');
        window.tiposProfissao = tipos;
        contadorConjugeProfissao++;
        const container = document.getElementById('conjuge-profissoes-container');
        
        if (container.querySelector('.text-muted')) {
            container.innerHTML = '';
        }
        
        const profissaoHtml = `
            <div class="border p-3 mb-3 conjuge-profissao-item" data-index="${contadorConjugeProfissao}">
                <div class="row">
                    <div class="col-md-4">
                        <label class="form-label">Profissão</label>
                        ${criarSelectTipos(tipos, `conjuge_profissoes[${contadorConjugeProfissao}][profissao]`, `conjuge_profissao_tipo_${contadorConjugeProfissao}`, `abrirModalTipoProfissao(${contadorConjugeProfissao})`)}
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Empresa</label>
                        <input type="text" class="form-control" name="conjuge_profissoes[${contadorConjugeProfissao}][empresa]" placeholder="Nome da empresa">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Renda</label>
                        <input type="number" class="form-control" name="conjuge_profissoes[${contadorConjugeProfissao}][renda]" placeholder="0,00" step="0.01">
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-3">
                        <label class="form-label">Data Admissão</label>
                        <input type="date" class="form-control" name="conjuge_profissoes[${contadorConjugeProfissao}][data_admissao]">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Data Demissão</label>
                        <input type="date" class="form-control" name="conjuge_profissoes[${contadorConjugeProfissao}][data_demissao]">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Ativo</label>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" name="conjuge_profissoes[${contadorConjugeProfissao}][ativo]" value="1" checked>
                            <label class="form-check-label">Ativo</label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger btn-sm w-100 mt-4" onclick="removerConjugeProfissao(${contadorConjugeProfissao})">
                            <i class="fas fa-trash"></i> Remover
                        </button>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-12">
                        <label class="form-label">Observações</label>
                        <textarea class="form-control" name="conjuge_profissoes[${contadorConjugeProfissao}][observacoes]" rows="2" placeholder="Observações sobre a profissão"></textarea>
                    </div>
                </div>
            </div>
        `;
        
        container.insertAdjacentHTML('beforeend', profissaoHtml);
    });
    
    window.removerConjugeProfissao = function(index) {
        const item = document.querySelector(`.conjuge-profissao-item[data-index="${index}"]`);
        if (item) {
            item.remove();
            const container = document.getElementById('conjuge-profissoes-container');
            if (container.children.length === 0) {
                container.innerHTML = '<p class="text-muted">Nenhuma profissão adicionada.</p>';
            }
        }
    };
    window.abrirModalTipoProfissao = function(index) {
        window.profissaoIndexAtual = index;
        new bootstrap.Modal(document.getElementById('modalNovoTipoProfissao')).show();
    };

    window.adicionarConjugeProfissaoExistente = async function(profissao) {
        const tipos = window.tiposProfissao || await carregarTipos('profissao');
        window.tiposProfissao = tipos;
        contadorConjugeProfissao++;
        const container = document.getElementById('conjuge-profissoes-container');
        
        if (container.querySelector('.text-muted')) {
            container.innerHTML = '';
        }
        
        const profissaoHtml = `
            <div class="border p-3 mb-3 conjuge-profissao-item" data-index="${contadorConjugeProfissao}">
                <div class="row">
                    <div class="col-md-4">
                        <label class="form-label">Profissão</label>
                        ${criarSelectTipos(tipos, `conjuge_profissoes[${contadorConjugeProfissao}][profissao]`, `conjuge_profissao_tipo_${contadorConjugeProfissao}`, `abrirModalTipoProfissao(${contadorConjugeProfissao})`, profissao.profissao)}
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Empresa</label>
                        <input type="text" class="form-control" name="conjuge_profissoes[${contadorConjugeProfissao}][empresa]" 
                            value="${profissao.empresa || ''}" placeholder="Nome da empresa">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Renda</label>
                        <input type="number" step="0.01" class="form-control" name="conjuge_profissoes[${contadorConjugeProfissao}][renda]" 
                            value="${profissao.renda || ''}" placeholder="0,00">
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-3">
                        <label class="form-label">Data de Admissão</label>
                        <input type="date" class="form-control" name="conjuge_profissoes[${contadorConjugeProfissao}][data_admissao]" 
                            value="${profissao.dataAdmissao || ''}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Data de Demissão</label>
                        <input type="date" class="form-control" name="conjuge_profissoes[${contadorConjugeProfissao}][data_demissao]" 
                            value="${profissao.dataDemissao || ''}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Observações</label>
                        <input type="text" class="form-control" name="conjuge_profissoes[${contadorConjugeProfissao}][observacoes]" 
                            value="${profissao.observacoes || ''}" placeholder="Observações">
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger btn-sm w-100 mt-4" onclick="removerConjugeProfissao(${contadorConjugeProfissao})">
                            <i class="fas fa-trash"></i> Remover
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        container.insertAdjacentHTML('beforeend', profissaoHtml);
    };

});