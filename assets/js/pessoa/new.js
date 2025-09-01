document.addEventListener('DOMContentLoaded', () => {
    const tipoSelect = document.getElementById('pessoa_tipoPessoa'); // id gerado pelo Symfony
    const container  = document.getElementById('sub-form-container');

    const loadSubForm = (tipo) => {
        if (!tipo) { container.innerHTML = ''; return; }

        fetch(container.dataset.url, {          // rota injetada via data-*
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ tipo })
        })
        .then(r => r.text())
        .then(html => container.innerHTML = html)
        .catch(console.error);
    };

    tipoSelect?.addEventListener('change', () => loadSubForm(tipoSelect.value));
    loadSubForm(tipoSelect?.value);           // inicial
});