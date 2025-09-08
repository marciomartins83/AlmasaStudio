document.addEventListener('DOMContentLoaded', () => {
    const tipoSelect = document.getElementById('pessoa_form_tipoPessoa'); // ID gerado pelo Symfony
    const container  = document.getElementById('sub-form-container');

    // Verifica se os elementos essenciais existem antes de prosseguir
    if (!tipoSelect || !container) {
        console.error('Elemento select de tipo de pessoa ou container do sub-formulário não encontrado.');
        return;
    }

    const loadSubForm = (tipo) => {
        // Se nenhum tipo for selecionado, limpa o contêiner
        if (!tipo) {
            container.innerHTML = '';
            return;
        }

        // Exibe um indicador de carregamento
        container.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Carregando...</div>';

        fetch(window.ROUTES.subform, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams({
                'tipo': tipo
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Falha na requisição do sub-formulário.');
            }
            return response.text();
        })
        .then(html => {
            container.innerHTML = html;
        })
        .catch(error => {
            console.error('Erro ao carregar o sub-formulário:', error);
            container.innerHTML = '<div class="alert alert-danger">Não foi possível carregar os campos adicionais. Tente novamente.</div>';
        });
    };

    // Adiciona o listener para o evento de mudança
    tipoSelect.addEventListener('change', () => loadSubForm(tipoSelect.value));

    // Carrega o sub-formulário inicial se já houver um valor selecionado
    if (tipoSelect.value) {
        loadSubForm(tipoSelect.value);
    }
});
