/**
 * Gerencia a funcionalidade de telefones do cônjuge
 * Responsável pela adição, remoção e validação de telefones do cônjuge
 */
document.addEventListener('DOMContentLoaded', function() {
    let contadorConjugeTelefone = 0;

    document.getElementById('add-conjuge-telefone')?.addEventListener('click', async function() {
        const tipos = window.tiposTelefone || await carregarTipos('telefone');
        window.tiposTelefone = tipos;
        contadorConjugeTelefone++;
        const container = document.getElementById('conjuge-telefones-container');
        
        if (container.querySelector('.text-muted')) {
            container.innerHTML = '';
        }
        
        const telefoneHtml = `
            <div class="border p-3 mb-3 conjuge-telefone-item" data-index="${contadorConjugeTelefone}">
                <div class="row align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Tipo de Telefone</label>
                        ${criarSelectTipos(tipos, `conjuge_telefones[${contadorConjugeTelefone}][tipo]`, `conjuge_telefone_tipo_${contadorConjugeTelefone}`, `abrirModalTipoTelefone(${contadorConjugeTelefone})`)}
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Número</label>
                        <input type="text" class="form-control" name="conjuge_telefones[${contadorConjugeTelefone}][numero]" placeholder="(11) 99999-9999" required>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger btn-sm w-100" onclick="removerConjugeTelefone(${contadorConjugeTelefone})">
                            <i class="fas fa-trash"></i> Remover
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        container.insertAdjacentHTML('beforeend', telefoneHtml);
    });
    
    window.removerConjugeTelefone = function(index) {
        const item = document.querySelector(`.conjuge-telefone-item[data-index="${index}"]`);
        if (item) {
            item.remove();
            const container = document.getElementById('conjuge-telefones-container');
            if (container.children.length === 0) {
                container.innerHTML = '<p class="text-muted">Nenhum telefone adicionado.</p>';
            }
        }
    };

    window.adicionarConjugeTelefoneExistente = async function(telefone) {
        const tipos = window.tiposTelefone || await carregarTipos('telefone');
        window.tiposTelefone = tipos;
        contadorConjugeTelefone++;
        const container = document.getElementById('conjuge-telefones-container');
        
        if (container.querySelector('.text-muted')) {
            container.innerHTML = '';
        }
        
        const telefoneHtml = `
            <div class="border p-3 mb-3 conjuge-telefone-item" data-index="${contadorConjugeTelefone}">
                <div class="row align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Tipo de Telefone</label>
                        ${criarSelectTipos(tipos, `conjuge_telefones[${contadorConjugeTelefone}][tipo]`, `conjuge_telefone_tipo_${contadorConjugeTelefone}`, `abrirModalTipoTelefone(${contadorConjugeTelefone})`, telefone.tipo)}
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Número</label>
                        <input type="text" class="form-control" name="conjuge_telefones[${contadorConjugeTelefone}][numero]" 
                            value="${telefone.numero || ''}" placeholder="(11) 99999-9999" required>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger btn-sm w-100" onclick="removerConjugeTelefone(${contadorConjugeTelefone})">
                            <i class="fas fa-trash"></i> Remover
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        container.insertAdjacentHTML('beforeend', telefoneHtml);
    };

});