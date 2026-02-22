/**
 * CRUD Filters
 *
 * Este script intercepta a submissão de formulários que possuem o atributo
 * `data-confirm-delete`. Quando o usuário tenta excluir um registro, será
 * exibida uma caixa de confirmação com a mensagem definida nesse atributo.
 * Se o usuário cancelar, a submissão do formulário é cancelada.
 */

document.addEventListener('DOMContentLoaded', function () {
    // Seleciona todos os formulários que têm o atributo data-confirm-delete
    const deleteForms = document.querySelectorAll('form[data-confirm-delete]');

    deleteForms.forEach(function (form) {
        form.addEventListener('submit', function (event) {
            const message = form.getAttribute('data-confirm-delete');
            if (!message) {
                // Se não houver mensagem, não faz nada especial
                return;
            }

            // Exibe a confirmação
            const confirmed = confirm(message);

            // Se o usuário cancelar, impede a submissão
            if (!confirmed) {
                event.preventDefault();
            }
        });
    });
});
