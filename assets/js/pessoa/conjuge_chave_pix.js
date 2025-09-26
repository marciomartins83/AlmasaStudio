/**
 * Gerencia a funcionalidade de chaves PIX do cônjuge
 * Responsável pela adição, remoção e validação de chaves PIX do cônjuge
 */
document.addEventListener('DOMContentLoaded', function() {
    let contadorConjugePix = 0;

    document.getElementById('add-conjuge-pix')?.addEventListener('click', async function() {
        const tipos = window.tiposChavePix || await carregarTipos('chave-pix');
        window.tiposChavePix = tipos;
        contadorConjugePix++;
        const container = document.getElementById('conjuge-pix-container');
        
        if (container.querySelector('.text-muted')) {
            container.innerHTML = '';
        }
        
        const pixHtml = `
            <div class="border p-3 mb-3 conjuge-pix-item" data-index="${contadorConjugePix}">
                <div class="row align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Tipo de Chave</label>
                        ${criarSelectTipos(tipos, `conjuge_chaves_pix[${contadorConjugePix}][tipo]`, `conjuge_pix_tipo_${contadorConjugePix}`, `abrirModalTipoChavePix(${contadorConjugePix})`)}
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Chave PIX</label>
                        <input type="text" class="form-control" name="conjuge_chaves_pix[${contadorConjugePix}][chave]" placeholder="Digite a chave PIX" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Principal</label>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" name="conjuge_chaves_pix[${contadorConjugePix}][principal]" value="1">
                            <label class="form-check-label">Principal</label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger btn-sm w-100" onclick="removerConjugePix(${contadorConjugePix})">
                            <i class="fas fa-trash"></i> Remover
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        container.insertAdjacentHTML('beforeend', pixHtml);
    });
    
    window.removerConjugePix = function(index) {
        const item = document.querySelector(`.conjuge-pix-item[data-index="${index}"]`);
        if (item) {
            item.remove();
            const container = document.getElementById('conjuge-pix-container');
            if (container.children.length === 0) {
                container.innerHTML = '<p class="text-muted">Nenhuma chave PIX adicionada.</p>';
            }
        }
    };

    window.adicionarConjugeChavePixExistente = async function(chavePix) {
        const tipos = window.tiposChavePix || await carregarTipos('chave-pix');
        window.tiposChavePix = tipos;
        contadorConjugeChavePix++;
        const container = document.getElementById('conjuge-pix-container');
        
        if (container.querySelector('.text-muted')) {
            container.innerHTML = '';
        }
        
        const pixHtml = `
            <div class="border p-3 mb-3 conjuge-pix-item" data-index="${contadorConjugeChavePix}">
                <div class="row align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Tipo de Chave</label>
                        ${criarSelectTipos(tipos, `conjuge_chaves_pix[${contadorConjugeChavePix}][tipo]`, `conjuge_pix_tipo_${contadorConjugeChavePix}`, `abrirModalTipoChavePix(${contadorConjugeChavePix})`, chavePix.tipo)}
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Chave PIX</label>
                        <input type="text" class="form-control" name="conjuge_chaves_pix[${contadorConjugeChavePix}][chave]" 
                            value="${chavePix.chave || ''}" placeholder="Digite a chave PIX" required>
                    </div>
                    <div class="col-md-2">
                        <div class="form-check mt-4">
                            <input class="form-check-input" type="checkbox" name="conjuge_chaves_pix[${contadorConjugeChavePix}][principal]" 
                                id="conjuge_pix_principal_${contadorConjugeChavePix}" ${chavePix.principal ? 'checked' : ''}>
                            <label class="form-check-label" for="conjuge_pix_principal_${contadorConjugeChavePix}">
                                Principal
                            </label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger btn-sm w-100" onclick="removerConjugeChavePix(${contadorConjugeChavePix})">
                            <i class="fas fa-trash"></i> Remover
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        container.insertAdjacentHTML('beforeend', pixHtml);
    };


});