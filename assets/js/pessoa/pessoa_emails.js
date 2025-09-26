/**
 * Gerencia a funcionalidade de emails
 * Responsável pela adição, remoção e validação de emails
 */
document.addEventListener('DOMContentLoaded', function() {
    let contadorEmail = 0;

    document.getElementById('add-email')?.addEventListener('click', async function() {
        const tipos = window.tiposEmail || await carregarTipos('email');
        window.tiposEmail = tipos;
        contadorEmail++;
        const container = document.getElementById('emails-container');
        
        if (container.querySelector('.text-muted')) {
            container.innerHTML = '';
        }
        
        const emailHtml = `
            <div class="border p-3 mb-3 email-item" data-index="${contadorEmail}">
                <div class="row align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Tipo de Email</label>
                        ${criarSelectTipos(tipos, `emails[${contadorEmail}][tipo]`, `email_tipo_${contadorEmail}`, `abrirModalTipoEmail(${contadorEmail})`)}
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="emails[${contadorEmail}][email]" placeholder="exemplo@email.com" required>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger btn-sm w-100" onclick="removerEmail(${contadorEmail})">
                            <i class="fas fa-trash"></i> Remover
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        container.insertAdjacentHTML('beforeend', emailHtml);
    });

    window.adicionarEmailExistente = async function(email) {
        const tipos = window.tiposEmail || await carregarTipos('email');
        window.tiposEmail = tipos;
        contadorEmail++;
        const container = document.getElementById('emails-container');
        
        if (container.querySelector('.text-muted')) {
            container.innerHTML = '';
        }
        
        const emailHtml = `
            <div class="border p-3 mb-3 email-item" data-index="${contadorEmail}">
                <div class="row align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Tipo de Email</label>
                        ${criarSelectTipos(tipos, `emails[${contadorEmail}][tipo]`, `email_tipo_${contadorEmail}`, `abrirModalTipoEmail(${contadorEmail})`, email.tipo)}
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="emails[${contadorEmail}][email]" 
                            value="${email.email || ''}" placeholder="email@exemplo.com" required>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger btn-sm w-100" onclick="removerEmail(${contadorEmail})">
                            <i class="fas fa-trash"></i> Remover
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        container.insertAdjacentHTML('beforeend', emailHtml);
    };
    
    window.removerEmail = function(index) {
        const item = document.querySelector(`.email-item[data-index="${index}"]`);
        if (item) {
            item.remove();
            const container = document.getElementById('emails-container');
            if (container.children.length === 0) {
                container.innerHTML = '<p class="text-muted">Nenhum email adicionado.</p>';
            }
        }
    };
    
    window.abrirModalTipoEmail = function(index) {
        window.emailIndexAtual = index;
        new bootstrap.Modal(document.getElementById('modalNovoTipoEmail')).show();
    };
});