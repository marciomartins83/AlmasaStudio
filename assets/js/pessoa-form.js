// Gerenciamento de formulários de pessoa específica
class PessoaFormManager {
    constructor() {
        this.pessoaId = null;
        this.init();
    }

    init() {
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Delegação de evento para a busca de CEP
        document.addEventListener('blur', (e) => {
            if (e.target && e.target.classList.contains('cep-input')) {
                this.buscarCEP(e.target);
            }
        }, true); // Usar captura para garantir que o evento seja pego

        // Botões de adicionar contatos - versão mais resiliente
        document.addEventListener('click', (e) => {
            const button = e.target.closest('[data-action="add-contact"]');
            if (button) {
                const type = button.dataset.type;
                if (type) {
                    this.openModal(type);
                }
            }
        });
    }

    async buscarCEP(cepInput) {
        const cep = cepInput.value.replace(/\D/g, '');
        if (cep.length !== 8) {
            if (cep.length > 0) {
                alert('CEP inválido. Deve conter 8 dígitos.');
            }
            return;
        }

        const addressBlock = cepInput.closest('.endereco-item');
        if (!addressBlock) {
            console.error('Não foi possível encontrar o bloco de endereço (.endereco-item) para o CEP informado.');
            return;
        }

        addressBlock.querySelectorAll('input').forEach(input => input.disabled = true);
        cepInput.classList.add('loading');

        try {
            // A URL da API deve ser acessível globalmente ou passada de outra forma
            const response = await fetch(`/api/cep/${cep}`);
            if (!response.ok) {
                throw new Error('CEP não encontrado ou erro na API.');
            }
            
            const data = await response.json();

            if (data.success) {
                const logradouroField = addressBlock.querySelector('.logradouro-field');
                const bairroField = addressBlock.querySelector('.bairro-field');
                const cidadeField = addressBlock.querySelector('.cidade-field');
                const estadoField = addressBlock.querySelector('.estado-field');

                if (logradouroField) logradouroField.value = data.logradouro || '';
                if (bairroField) bairroField.value = data.bairro || '';
                if (cidadeField) cidadeField.value = data.localidade || '';
                if (estadoField) estadoField.value = data.uf || '';
            } else {
                alert(data.message || 'Não foi possível encontrar o CEP.');
            }
        } catch (error) {
            console.error('Erro na busca de CEP:', error);
            alert('Ocorreu um erro ao buscar o CEP. Tente novamente.');
        } finally {
            addressBlock.querySelectorAll('input').forEach(input => input.disabled = false);
            cepInput.classList.remove('loading');
        }
    }

    openModal(type) {
        // Verifica se o modal existe antes de tentar instanciá-lo
        const modalElement = document.getElementById('contactModal');
        if (!modalElement) return;

        const modal = new bootstrap.Modal(modalElement);
        const modalBody = document.querySelector('#contactModal .modal-body');
        
        modalBody.innerHTML = this.getModalContent(type);
        modal.show();
        
        // Adicionar event listeners para o formulário do modal
        this.setupModalForm(type);
    }

    getModalContent(type) {
        const forms = {
            telefone: `
                <form id="telefoneForm">
                    <div class="mb-3">
                        <label for="telefone_numero" class="form-label">Número</label>
                        <input type="text" class="form-control" id="telefone_numero" required>
                    </div>
                    <div class="mb-3">
                        <label for="telefone_tipo" class="form-label">Tipo</label>
                        <select class="form-select" id="telefone_tipo" required>
                            <option value="">Selecione...</option>
                            <option value="1">Celular</option>
                            <option value="2">Residencial</option>
                            <option value="3">Comercial</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Adicionar</button>
                </form>
            `,
            // ... (outros formulários permanecem iguais)
        };
        
        return forms[type] || '';
    }

    // ... (outros métodos permanecem iguais)
}

// Inicializar quando o DOM estiver carregado
document.addEventListener('DOMContentLoaded', () => {
    window.PessoaFormManagerInstance = new PessoaFormManager();
});
