/**
 * Gerencia o formulário de conta bancária
 * Responsável pelos modais e funcionalidade AJAX
 */
document.addEventListener('DOMContentLoaded', function() {

    // Botões para abrir modais
    document.getElementById('btnAddBanco')?.addEventListener('click', function() {
        const modal = new bootstrap.Modal(document.getElementById('modalNovoBanco'));
        modal.show();
    });

    document.getElementById('btnAddAgencia')?.addEventListener('click', function() {
        // Carregar bancos existentes no select do modal
        carregarBancosParaAgencia();
        const modal = new bootstrap.Modal(document.getElementById('modalNovaAgencia'));
        modal.show();
    });

    document.getElementById('btnAddTipoConta')?.addEventListener('click', function() {
        const modal = new bootstrap.Modal(document.getElementById('modalNovoTipoConta'));
        modal.show();
    });

    // Salvar novo banco
    document.getElementById('btnSalvarBanco')?.addEventListener('click', async function() {
        const nome = document.getElementById('novoBancoNome').value.trim();
        const numero = document.getElementById('novoBancoNumero').value.trim();

        if (!nome || !numero) {
            alert('Por favor, preencha todos os campos obrigatórios');
            return;
        }

        try {
            const response = await fetch('/pessoa/salvar-banco', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ nome, numero: parseInt(numero) })
            });

            const data = await response.json();

            if (data.success) {
                // Adicionar novo banco ao select principal
                const selectBanco = document.getElementById('conta_bancaria_idBanco');
                if (selectBanco) {
                    const option = new Option(data.banco.nome, data.banco.id, true, true);
                    selectBanco.add(option);
                }

                // Limpar e fechar modal
                document.getElementById('novoBancoNome').value = '';
                document.getElementById('novoBancoNumero').value = '';
                bootstrap.Modal.getInstance(document.getElementById('modalNovoBanco')).hide();

                // Mostrar mensagem de sucesso
                mostrarMensagem('Banco cadastrado com sucesso!', 'success');
            } else {
                alert(data.message || 'Erro ao salvar banco');
            }
        } catch (error) {
            console.error('Erro:', error);
            alert('Erro ao salvar banco');
        }
    });

    // Salvar nova agência
    document.getElementById('btnSalvarAgencia')?.addEventListener('click', async function() {
        const banco = document.getElementById('novaAgenciaBanco').value;
        const codigo = document.getElementById('novaAgenciaCodigo').value.trim();
        const nome = document.getElementById('novaAgenciaNome').value.trim();

        if (!banco || !codigo || !nome) {
            alert('Por favor, preencha todos os campos obrigatórios');
            return;
        }

        try {
            const response = await fetch('/pessoa/salvar-agencia', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    banco: parseInt(banco),
                    codigo,
                    nome
                })
            });

            const data = await response.json();

            if (data.success) {
                // Adicionar nova agência ao select principal
                const selectAgencia = document.getElementById('conta_bancaria_idAgencia');
                if (selectAgencia) {
                    const option = new Option(`${data.agencia.codigo} - ${data.agencia.nome}`, data.agencia.id, true, true);
                    selectAgencia.add(option);
                }

                // Limpar e fechar modal
                document.getElementById('novaAgenciaBanco').value = '';
                document.getElementById('novaAgenciaCodigo').value = '';
                document.getElementById('novaAgenciaNome').value = '';
                bootstrap.Modal.getInstance(document.getElementById('modalNovaAgencia')).hide();

                // Mostrar mensagem de sucesso
                mostrarMensagem('Agência cadastrada com sucesso!', 'success');
            } else {
                alert(data.message || 'Erro ao salvar agência');
            }
        } catch (error) {
            console.error('Erro:', error);
            alert('Erro ao salvar agência');
        }
    });

    // Salvar novo tipo de conta
    document.getElementById('btnSalvarTipoConta')?.addEventListener('click', async function() {
        const tipo = document.getElementById('novoTipoConta').value.trim();

        if (!tipo) {
            alert('Por favor, informe o tipo de conta');
            return;
        }

        try {
            const response = await fetch('/pessoa/salvar-tipo-conta-bancaria', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ tipo })
            });

            const data = await response.json();

            if (data.success) {
                // Adicionar novo tipo ao select principal
                const selectTipo = document.getElementById('conta_bancaria_idTipoConta');
                if (selectTipo) {
                    const option = new Option(data.tipoConta.tipo, data.tipoConta.id, true, true);
                    selectTipo.add(option);
                }

                // Limpar e fechar modal
                document.getElementById('novoTipoConta').value = '';
                bootstrap.Modal.getInstance(document.getElementById('modalNovoTipoConta')).hide();

                // Mostrar mensagem de sucesso
                mostrarMensagem('Tipo de conta cadastrado com sucesso!', 'success');
            } else {
                alert(data.message || 'Erro ao salvar tipo de conta');
            }
        } catch (error) {
            console.error('Erro:', error);
            alert('Erro ao salvar tipo de conta');
        }
    });

    // Função auxiliar para carregar bancos no modal de agência
    async function carregarBancosParaAgencia() {
        const selectBanco = document.getElementById('novaAgenciaBanco');
        if (!selectBanco) return;

        // Limpar select
        selectBanco.innerHTML = '<option value="">Selecione o banco...</option>';

        // Obter bancos do select principal
        const selectBancoPrincipal = document.getElementById('conta_bancaria_idBanco');
        if (selectBancoPrincipal) {
            Array.from(selectBancoPrincipal.options).forEach(option => {
                if (option.value) {
                    const newOption = new Option(option.text, option.value);
                    selectBanco.add(newOption);
                }
            });
        }
    }

    // Função para mostrar mensagens de feedback
    function mostrarMensagem(mensagem, tipo) {
        // Verifica se existe um container de alertas
        let alertContainer = document.querySelector('.alert-container');
        if (!alertContainer) {
            // Cria o container se não existir
            alertContainer = document.createElement('div');
            alertContainer.className = 'alert-container';
            alertContainer.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999;';
            document.body.appendChild(alertContainer);
        }

        // Cria o alerta
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${tipo} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${mensagem}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        // Adiciona ao container
        alertContainer.appendChild(alertDiv);

        // Remove após 5 segundos
        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    }

    // Listener para quando o banco mudar, filtrar agências
    document.getElementById('conta_bancaria_idBanco')?.addEventListener('change', async function() {
        const bancoId = this.value;
        const selectAgencia = document.getElementById('conta_bancaria_idAgencia');

        if (!selectAgencia || !bancoId) return;

        // Por enquanto não vamos filtrar, mas isso pode ser implementado futuramente
        // se necessário buscar apenas agências do banco selecionado
    });

    // Validação do formulário
    const form = document.querySelector('form.needs-validation');
    if (form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    }
});