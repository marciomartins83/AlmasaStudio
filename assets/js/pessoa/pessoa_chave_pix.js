/**
 * Gerencia a funcionalidade de chaves PIX
 * Responsável pela adição, remoção e validação de chaves PIX
 */
document.addEventListener('DOMContentLoaded', function() {
    let contadorPix = 0;   // ✅ único contador global

    document.getElementById('add-pix')?.addEventListener('click', async function() {
        const tipos = window.tiposChavePix || await carregarTipos('chave-pix');
        window.tiposChavePix = tipos;
        contadorPix++;
        const container = document.getElementById('pix-container');

        if (container.querySelector('.text-muted')) container.innerHTML = '';

        const pixHtml = `
            <div class="border p-3 mb-3 pix-item" data-index="${contadorPix}">
                <div class="row align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Tipo de Chave</label>
                        ${criarSelectTipos(tipos, `chaves_pix[${contadorPix}][tipo]`, `pix_tipo_${contadorPix}`, `abrirModalTipoChavePix(${contadorPix})`)}
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Chave PIX</label>
                        <input type="text" class="form-control" name="chaves_pix[${contadorPix}][chave]" placeholder="Digite a chave PIX" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Principal</label>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" name="chaves_pix[${contadorPix}][principal]" value="1">
                            <label class="form-check-label">Principal</label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger btn-sm w-100" onclick="removerPix(${contadorPix})">
                            <i class="fas fa-trash"></i> Remover
                        </button>
                    </div>
                </div>
            </div>`;

        container.insertAdjacentHTML('beforeend', pixHtml);
    });

    /* ----------  API p/ new.js  ---------- */
    window.adicionarChavePixExistente = async function(chavePix) {
        const tipos = window.tiposChavePix || await carregarTipos('chave-pix');
        window.tiposChavePix = tipos;
        contadorPix++;   // ✅ usa o mesmo contador
        const container = document.getElementById('pix-container');

        if (container.querySelector('.text-muted')) container.innerHTML = '';

        const pixHtml = `
            <div class="border p-3 mb-3 pix-item" data-index="${contadorPix}">
                <div class="row align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Tipo de Chave</label>
                        ${criarSelectTipos(tipos, `chaves_pix[${contadorPix}][tipo]`, `pix_tipo_${contadorPix}`, `abrirModalTipoChavePix(${contadorPix})`, chavePix.tipo)}
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Chave PIX</label>
                        <input type="text" class="form-control" name="chaves_pix[${contadorPix}][chave]"
                               value="${chavePix.chave || ''}" placeholder="Digite a chave PIX" required>
                    </div>
                    <div class="col-md-2">
                        <div class="form-check mt-4">
                            <input class="form-check-input" type="checkbox" name="chaves_pix[${contadorPix}][principal]"
                                   id="pix_principal_${contadorPix}" ${chavePix.principal ? 'checked' : ''}>
                            <label class="form-check-label" for="pix_principal_${contadorPix}">Principal</label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger btn-sm w-100" onclick="removerPix(${contadorPix})">
                            <i class="fas fa-trash"></i> Remover
                        </button>
                    </div>
                </div>
            </div>`;

        container.insertAdjacentHTML('beforeend', pixHtml);
    };

    /* ----------  Remoção (única função)  ---------- */
    window.removerPix = function(index) {
        const item = document.querySelector(`.pix-item[data-index="${index}"]`);
        if (item) {
            item.remove();
            const container = document.getElementById('pix-container');
            if (container.children.length === 0) {
                container.innerHTML = '<p class="text-muted">Nenhuma chave PIX adicionada.</p>';
            }
        }
    };

    /* ----------  Modal novo tipo  ---------- */
    window.abrirModalTipoChavePix = function(index) {
        window.pixIndexAtual = index;
        new bootstrap.Modal(document.getElementById('modalNovoTipoChavePix')).show();
    };
});