/**
 * Gerencia a funcionalidade de emails do cônjuge
 * Responsável pela adição, remoção e validação de emails
 */
document.addEventListener('DOMContentLoaded', function() {
    let contadorConjugeEmail = 0;

    document.getElementById('add-conjuge-email')?.addEventListener('click', async function() {
        const tipos = window.tiposEmail || await carregarTipos('email');
        window.tiposEmail = tipos;
        contadorConjugeEmail++;
        const container = document.getElementById('conjuge-emails-container');

        if (container.querySelector('.text-muted')) {
            container.innerHTML = '';
        }

        const emailHtml = `
            <div class="border p-3 mb-3 conjuge-email-item" data-index="${contadorConjugeEmail}">
                <div class="row align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Tipo de Email</label>
                        ${criarSelectTipos(tipos, `conjuge_emails[${contadorConjugeEmail}][tipo]`, `conjuge_email_tipo_${contadorConjugeEmail}`, `abrirModalTipoEmailConjuge(${contadorConjugeEmail})`)}
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="conjuge_emails[${contadorConjugeEmail}][email]" placeholder="exemplo@email.com" required>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger btn-sm w-100" onclick="removerConjugeEmail(${contadorConjugeEmail})">
                            <i class="fas fa-trash"></i> Remover
                        </button>
                    </div>
                </div>
            </div>
        `;

        container.insertAdjacentHTML('beforeend', emailHtml);
    });

    window.adicionarConjugeEmailExistente = async function(email) {
        const tipos = window.tiposEmail || await carregarTipos('email');
        window.tiposEmail = tipos;
        contadorConjugeEmail++;
        const container = document.getElementById('conjuge-emails-container');

        if (container.querySelector('.text-muted')) {
            container.innerHTML = '';
        }

        const emailHtml = `
            <div class="border p-3 mb-3 conjuge-email-item" data-index="${contadorConjugeEmail}" data-id="${email.id || ''}">
                <div class="row align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Tipo de Email</label>
                        ${criarSelectTipos(tipos, `conjuge_emails[${contadorConjugeEmail}][tipo]`, `conjuge_email_tipo_${contadorConjugeEmail}`, `abrirModalTipoEmailConjuge(${contadorConjugeEmail})`, email.tipo)}
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="conjuge_emails[${contadorConjugeEmail}][email]"
                            value="${email.email || ''}" placeholder="email@exemplo.com" required>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger btn-sm w-100" onclick="removerConjugeEmail(${contadorConjugeEmail})">
                            <i class="fas fa-trash"></i> Remover
                        </button>
                    </div>
                </div>
            </div>
        `;

        container.insertAdjacentHTML('beforeend', emailHtml);
    };

    window.removerConjugeEmail = async function (index) {
        const item = document.querySelector(`.conjuge-email-item[data-index="${index}"]`);
        if (!item) return;

        const id = item.dataset.id;
        if (!id) { // email novo – só limpa
            item.remove();
            const container = document.getElementById('conjuge-emails-container');
            if (container.children.length === 0) {
                container.innerHTML = '<p class="text-muted">Nenhum email adicionado.</p>';
            }
            return;
        }

        if (!confirm('Excluir este email?')) return;

        try {
            const res = await fetch(`/pessoa/email/${id}`, {
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
                const container = document.getElementById('conjuge-emails-container');
                if (container.children.length === 0) {
                    container.innerHTML = '<p class="text-muted">Nenhum email adicionado.</p>';
                }
            } else {
                alert(data.message || 'Erro ao excluir');
            }
        } catch (e) {
            console.error(e);
            alert('Erro de rede – veja o console (F12).');
        }
    };

    window.abrirModalTipoEmailConjuge = function(index) {
        window.conjugeEmailIndexAtual = index;
        new bootstrap.Modal(document.getElementById('modalNovoTipoEmail')).show();
    };
});
