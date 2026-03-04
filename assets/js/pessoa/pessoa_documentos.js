/**
 * Gerencia a funcionalidade de documentos
 * Responsável pela adição, remoção e validação de documentos
 */
document.addEventListener('DOMContentLoaded', function() {
    let contadorDocumento = 0;

    document.getElementById('add-documento')?.addEventListener('click', async function() {
        console.log('Carregando tipos de documento...');
        const tipos = window.tiposDocumento || await window.carregarTipos('documento');
        console.log('Tipos recebidos:', tipos);
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
                        ${window.criarSelectTipos(tipos, `documentos[${contadorDocumento}][tipo]`, `documento_tipo_${contadorDocumento}`, `abrirModalTipoDocumento(${contadorDocumento})`, null)}
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
        
        // Vincular máscara ao novo documento
        vincularMascaraDocumento(contadorDocumento);
    });
    
    /**
     * Vincula máscara de CPF/RG ao select e input de documento
     */
    function vincularMascaraDocumento(index) {
        const tipoSelect = document.getElementById(`documento_tipo_${index}`);
        const numeroInput = document.querySelector(`input[name="documentos[${index}][numero]"]`);
        
        if (!tipoSelect || !numeroInput) return;
        
        const aplicarMascara = () => {
            const tipoId = parseInt(tipoSelect.value);
            const tipoTexto = tipoSelect.options[tipoSelect.selectedIndex]?.text?.toUpperCase() || '';
            
            // Detectar tipo: CPF=1, RG=2 ou por texto
            let tipoDoc = null;
            if (tipoId === 1 || tipoTexto.includes('CPF')) {
                tipoDoc = 'cpf';
            } else if (tipoId === 2 || tipoTexto.includes('RG')) {
                tipoDoc = 'rg';
            }
            
            // Aplicar máscara no input
            if (tipoDoc && window.aplicarMascaraDocumento) {
                numeroInput.setAttribute('data-tipo-documento', tipoDoc);
                window.aplicarMascaraDocumento(numeroInput, tipoDoc);
            } else {
                numeroInput.removeAttribute('data-tipo-documento');
            }
        };
        
        // Aplicar quando mudar o tipo
        tipoSelect.addEventListener('change', aplicarMascara);
        
        // Aplicar máscara durante digitação
        numeroInput.addEventListener('input', () => {
            const tipo = numeroInput.getAttribute('data-tipo-documento');
            if (tipo && window.aplicarMascaraDocumento) {
                window.aplicarMascaraDocumento(numeroInput, tipo);
            }
        });
    }
    
    window.adicionarDocumentoExistente = async function(documento) {
        const tipos = window.tiposDocumento || await window.carregarTipos('documento');
        window.tiposDocumento = tipos;
        contadorDocumento++;
        const container = document.getElementById('documentos-container');
        
        if (container.querySelector('.text-muted')) {
            container.innerHTML = '';
        }

        // documento.tipo agora já vem como ID do backend
        const tipoId = documento.tipo;
        
        const documentoHtml = `
            <div class="border p-3 mb-3 documento-item" data-index="${contadorDocumento}" data-id="${documento.id || ''}">
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-label">Tipo de Documento</label>
                        ${window.criarSelectTipos(tipos, `documentos[${contadorDocumento}][tipo]`, `documento_tipo_${contadorDocumento}`, `abrirModalTipoDocumento(${contadorDocumento})`, tipoId)}
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Número</label>
                        <input type="text" class="form-control" name="documentos[${contadorDocumento}][numero]" 
                            value="${documento.numero || ''}" placeholder="Número do documento" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Órgão Emissor</label>
                        <input type="text" class="form-control" name="documentos[${contadorDocumento}][orgao_emissor]" 
                            value="${documento.orgaoEmissor || ''}" placeholder="SSP, DETRAN...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Data de Emissão</label>
                        <input type="date" class="form-control" name="documentos[${contadorDocumento}][data_emissao]" 
                            value="${documento.dataEmissao || ''}">
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-3">
                        <label class="form-label">Data de Vencimento</label>
                        <input type="date" class="form-control" name="documentos[${contadorDocumento}][data_vencimento]" 
                            value="${documento.dataVencimento || ''}">
                    </div>
                    <div class="col-md-7">
                        <label class="form-label">Observações</label>
                        <input type="text" class="form-control" name="documentos[${contadorDocumento}][observacoes]" 
                            value="${documento.observacoes || ''}" placeholder="Observações adicionais">
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
        
        // Vincular máscara ao documento existente
        vincularMascaraDocumento(contadorDocumento);
        
        // Aplicar máscara inicial baseada no tipo
        const tipoSelect = document.getElementById(`documento_tipo_${contadorDocumento}`);
        const numeroInput = document.querySelector(`input[name="documentos[${contadorDocumento}][numero]"]`);
        if (tipoSelect && numeroInput && documento.numero) {
            const tipoId = parseInt(tipoSelect.value);
            const tipoTexto = tipoSelect.options[tipoSelect.selectedIndex]?.text?.toUpperCase() || '';
            
            if ((tipoId === 1 || tipoTexto.includes('CPF')) && window.formatarCPF) {
                numeroInput.value = window.formatarCPF(documento.numero.replace(/[^\d]/g, ''));
            } else if ((tipoId === 2 || tipoTexto.includes('RG')) && window.formatarRG) {
                numeroInput.value = window.formatarRG(documento.numero);
            }
        }
    };

    window.removerDocumento = async function (index) {
        const item = document.querySelector(`.documento-item[data-index="${index}"]`);
        if (!item) return;

        const id = item.dataset.id;
        if (!id) {
            item.remove();
            const container = document.getElementById('documentos-container');
            if (container.children.length === 0) {
                container.innerHTML = '<p class="text-muted">Nenhum documento adicionado.</p>';
            }
            return;
        }

        if (!confirm('Excluir este documento?')) return;

        try {
            const res = await fetch(`/pessoa/documento/${id}`, {
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
                const container = document.getElementById('documentos-container');
                if (container.children.length === 0) {
                    container.innerHTML = '<p class="text-muted">Nenhum documento adicionado.</p>';
                }
            } else {
                alert(data.message || 'Erro ao excluir');
            }
        } catch (e) {
            console.error(e);
            alert('Erro de rede – veja o console (F12).');
        }
    };
    
    window.abrirModalTipoDocumento = function(index) {
        window.documentoIndexAtual = index;
        new bootstrap.Modal(document.getElementById('modalNovoTipoDocumento')).show();
    };
});