/**
 * Gerencia a funcionalidade de documentos
 * Responsável pela adição, remoção e validação de documentos
 */
document.addEventListener('DOMContentLoaded', function() {
    let contadorDocumento = 0;

    document.getElementById('add-documento')?.addEventListener('click', async function() {
        const tipos = window.tiposDocumento || await carregarTipos('documento');
        window.tiposDocumento = tipos;
        contadorDocumento++;
        const container = document.getElementById('documentos-container');
        
        if (container.querySelector('.text-muted')) {
            container.innerHTML = '';
        }
        
        const documentoHtml = `
            <div class="border p-3 mb-3 documento-item" data-index="${contadorDocumento}">
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-label">Tipo de Documento</label>
                        ${criarSelectTipos(tipos, `documentos[${contadorDocumento}][tipo]`, `documento_tipo_${contadorDocumento}`, `abrirModalTipoDocumento(${contadorDocumento})`)}
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Número do Documento</label>
                        <input type="text" class="form-control" name="documentos[${contadorDocumento}][numero]" placeholder="Número do documento" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Órgão Emissor</label>
                        <input type="text" class="form-control" name="documentos[${contadorDocumento}][orgao_emissor]" placeholder="Ex: SSP-SP">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Data de Emissão</label>
                        <input type="date" class="form-control" name="documentos[${contadorDocumento}][data_emissao]">
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-3">
                        <label class="form-label">Data de Vencimento</label>
                        <input type="date" class="form-control" name="documentos[${contadorDocumento}][data_vencimento]">
                    </div>
                    <div class="col-md-7">
                        <label class="form-label">Observações</label>
                        <input type="text" class="form-control" name="documentos[${contadorDocumento}][observacoes]" placeholder="Observações sobre o documento">
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger btn-sm w-100 mt-4" onclick="removerDocumento(${contadorDocumento})">
                            <i class="fas fa-trash"></i> Remover
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        container.insertAdjacentHTML('beforeend', documentoHtml);
    });
    
    window.removerDocumento = function(index) {
        const item = document.querySelector(`.documento-item[data-index="${index}"]`);
        if (item) {
            item.remove();
            const container = document.getElementById('documentos-container');
            if (container.children.length === 0) {
                container.innerHTML = '<p class="text-muted">Nenhum documento adicionado.</p>';
            }
        }
    };
    
    window.abrirModalTipoDocumento = function(index) {
        window.documentoIndexAtual = index;
        new bootstrap.Modal(document.getElementById('modalNovoTipoDocumento')).show();
    };
});
