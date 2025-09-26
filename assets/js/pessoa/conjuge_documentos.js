/**
 * Gerencia a funcionalidade de documentos do cônjuge
 * Responsável pela adição, remoção e validação de documentos do cônjuge
 */
document.addEventListener('DOMContentLoaded', function() {
    let contadorConjugeDocumento = 0;

    document.getElementById('add-conjuge-documento')?.addEventListener('click', async function() {
        const tipos = window.tiposDocumento || await carregarTipos('documento');
        window.tiposDocumento = tipos;
        contadorConjugeDocumento++;
        const container = document.getElementById('conjuge-documentos-container');
        
        if (container.querySelector('.text-muted')) {
            container.innerHTML = '';
        }
        
        const documentoHtml = `
            <div class="border p-3 mb-3 conjuge-documento-item" data-index="${contadorConjugeDocumento}">
                <div class="row">
                    <div class="col-md-4">
                        <label class="form-label">Tipo de Documento</label>
                        ${criarSelectTipos(tipos, `conjuge_documentos[${contadorConjugeDocumento}][tipo]`, `conjuge_documento_tipo_${contadorConjugeDocumento}`, `abrirModalTipoDocumento(${contadorConjugeDocumento})`)}
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Número do Documento</label>
                        <input type="text" class="form-control" name="conjuge_documentos[${contadorConjugeDocumento}][numero]" placeholder="Número do documento">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Órgão Emissor</label>
                        <input type="text" class="form-control" name="conjuge_documentos[${contadorConjugeDocumento}][orgao_emissor]" placeholder="Ex: SSP, DETRAN">
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-3">
                        <label class="form-label">Data Emissão</label>
                        <input type="date" class="form-control" name="conjuge_documentos[${contadorConjugeDocumento}][data_emissao]">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Data Vencimento</label>
                        <input type="date" class="form-control" name="conjuge_documentos[${contadorConjugeDocumento}][data_vencimento]">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Observações</label>
                        <input type="text" class="form-control" name="conjuge_documentos[${contadorConjugeDocumento}][observacoes]" placeholder="Observações">
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger btn-sm w-100 mt-4" onclick="removerConjugeDocumento(${contadorConjugeDocumento})">
                            <i class="fas fa-trash"></i> Remover
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        container.insertAdjacentHTML('beforeend', documentoHtml);
    });
    
    window.removerConjugeDocumento = function(index) {
        const item = document.querySelector(`.conjuge-documento-item[data-index="${index}"]`);
        if (item) {
            item.remove();
            const container = document.getElementById('conjuge-documentos-container');
            if (container.children.length === 0) {
                container.innerHTML = '<p class="text-muted">Nenhum documento adicionado.</p>';
            }
        }
    };

    window.adicionarConjugeDocumentoExistente = async function(documento) {
        const tipos = window.tiposDocumento || await carregarTipos('documento');
        window.tiposDocumento = tipos;
        contadorConjugeDocumento++;
        const container = document.getElementById('conjuge-documentos-container');
        
        if (container.querySelector('.text-muted')) {
            container.innerHTML = '';
        }
        
        const documentoHtml = `
            <div class="border p-3 mb-3 conjuge-documento-item" data-index="${contadorConjugeDocumento}">
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-label">Tipo de Documento</label>
                        ${criarSelectTipos(tipos, `conjuge_documentos[${contadorConjugeDocumento}][tipo]`, `conjuge_documento_tipo_${contadorConjugeDocumento}`, `abrirModalTipoDocumento(${contadorConjugeDocumento})`, documento.tipo)}
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Número</label>
                        <input type="text" class="form-control" name="conjuge_documentos[${contadorConjugeDocumento}][numero]" 
                            value="${documento.numero || ''}" placeholder="Número do documento" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Órgão Emissor</label>
                        <input type="text" class="form-control" name="conjuge_documentos[${contadorConjugeDocumento}][orgao_emissor]" 
                            value="${documento.orgaoEmissor || ''}" placeholder="SSP, DETRAN...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Data de Emissão</label>
                        <input type="date" class="form-control" name="conjuge_documentos[${contadorConjugeDocumento}][data_emissao]" 
                            value="${documento.dataEmissao || ''}">
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-3">
                        <label class="form-label">Data de Vencimento</label>
                        <input type="date" class="form-control" name="conjuge_documentos[${contadorConjugeDocumento}][data_vencimento]" 
                            value="${documento.dataVencimento || ''}">
                    </div>
                    <div class="col-md-7">
                        <label class="form-label">Observações</label>
                        <input type="text" class="form-control" name="conjuge_documentos[${contadorConjugeDocumento}][observacoes]" 
                            value="${documento.observacoes || ''}" placeholder="Observações adicionais">
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger btn-sm w-100 mt-4" onclick="removerConjugeDocumento(${contadorConjugeDocumento})">
                            <i class="fas fa-trash"></i> Remover
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        container.insertAdjacentHTML('beforeend', documentoHtml);
    };

});