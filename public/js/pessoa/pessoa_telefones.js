/**
 * Gerencia a funcionalidade de telefones
 * Responsável pela adição, remoção e validação de telefones
 */
document.addEventListener('DOMContentLoaded', function() {
    let contadorTelefone = 0;

    document.getElementById('add-telefone')?.addEventListener('click', async function() {
        const tipos = window.tiposTelefone || await carregarTipos('telefone');
        window.tiposTelefone = tipos;
        contadorTelefone++;
        const container = document.getElementById('telefones-container');
        
        if (container.querySelector('.text-muted')) {
            container.innerHTML = '';
        }
        
        const telefoneHtml = `
            <div class="border p-3 mb-3 telefone-item" data-index="${contadorTelefone}">
                <div class="row align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Tipo de Telefone</label>
                        ${criarSelectTipos(tipos, `telefones[${contadorTelefone}][tipo]`, `telefone_tipo_${contadorTelefone}`, `abrirModalTipoTelefone(${contadorTelefone})`)}
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Número</label>
                        <input type="text" class="form-control" name="telefones[${contadorTelefone}][numero]" placeholder="(11) 99999-9999" required>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger btn-sm w-100" onclick="removerTelefone(${contadorTelefone})">
                            <i class="fas fa-trash"></i> Remover
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        container.insertAdjacentHTML('beforeend', telefoneHtml);
    });
    
    window.removerTelefone = async function (index) {
        const item = document.querySelector(`.telefone-item[data-index="${index}"]`);
        if (!item) return;

        const id = item.dataset.id; // ← agora vem do JSON
        if (!id) { // telefone novo – só limpa
            item.remove();
            return;
        }

        if (!confirm('Excluir este telefone?')) return;

        try {
            const res = await fetch(`/pessoa/telefone/${id}`, {
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
            } else {
                alert(data.message || 'Erro ao excluir');
            }
        } catch (e) {
            console.error(e);
            alert('Erro de rede – veja o console (F12).');
        }
    };
    
    window.abrirModalTipoTelefone = function(index) {
        window.telefoneIndexAtual = index;
        new bootstrap.Modal(document.getElementById('modalNovoTipoTelefone')).show();
    };

    window.adicionarTelefoneExistente = async function(telefone) {
        const tipos = window.tiposTelefone || await carregarTipos('telefone');
        window.tiposTelefone = tipos;
        contadorTelefone++;
        const container = document.getElementById('telefones-container');
        
        // Limpar mensagem padrão se existir
        if (container.querySelector('.text-muted')) {
            container.innerHTML = '';
        }
        
        const telefoneHtml = `
            <div class="border p-3 mb-3 telefone-item" data-index="${contadorTelefone}" data-id="${telefone.id || ''}">
                <div class="row align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Tipo de Telefone</label>
                        ${criarSelectTipos(tipos, `telefones[${contadorTelefone}][tipo]`, `telefone_tipo_${contadorTelefone}`, `abrirModalTipoTelefone(${contadorTelefone})`, telefone.tipo)}
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Número</label>
                        <input type="text" class="form-control" name="telefones[${contadorTelefone}][numero]" 
                            value="${telefone.numero || ''}" placeholder="(11) 99999-9999" required>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger btn-sm w-100" onclick="removerTelefone(${contadorTelefone})">
                            <i class="fas fa-trash"></i> Remover
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        container.insertAdjacentHTML('beforeend', telefoneHtml);
    };

});
