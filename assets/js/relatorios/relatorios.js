/**
 * Modulo de Relatorios PDF - JavaScript
 *
 * Gerencia preview AJAX e geracao de PDFs
 *
 * @requires window.ROUTES.preview - URL para preview AJAX
 * @requires window.ROUTES.pdf - URL para geracao de PDF
 * @requires window.RELATORIO_TIPO - Tipo do relatorio atual
 */

'use strict';

/**
 * Classe principal para gerenciamento de relatorios
 */
class RelatorioManager {
    constructor() {
        this.form = document.getElementById('filtros-form');
        this.previewContainer = document.getElementById('preview-container');
        this.loadingIndicator = document.getElementById('loading-indicator');
        this.btnPreview = document.getElementById('btn-preview');
        this.btnPdf = document.getElementById('btn-pdf');
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

        this.init();
    }

    /**
     * Inicializa event listeners
     */
    init() {
        if (!this.form) {
            console.warn('Formulario de filtros nao encontrado');
            return;
        }

        // Preview button
        if (this.btnPreview) {
            this.btnPreview.addEventListener('click', (e) => {
                e.preventDefault();
                this.carregarPreview();
            });
        }

        // PDF button
        if (this.btnPdf) {
            this.btnPdf.addEventListener('click', (e) => {
                e.preventDefault();
                this.gerarPdf();
            });
        }

        // Form submit (Enter key)
        this.form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.carregarPreview();
        });

        console.log('✅ RelatorioManager inicializado para:', window.RELATORIO_TIPO);
    }

    /**
     * Coleta dados do formulario
     * @returns {Object} Dados do formulario
     */
    coletarFiltros() {
        const formData = new FormData(this.form);
        const filtros = {};

        formData.forEach((value, key) => {
            if (value !== '') {
                filtros[key] = value;
            }
        });

        return filtros;
    }

    /**
     * Mostra indicador de carregamento
     */
    mostrarLoading() {
        if (this.loadingIndicator) {
            this.loadingIndicator.classList.remove('d-none');
        }
        if (this.previewContainer) {
            this.previewContainer.innerHTML = '';
        }
        if (this.btnPreview) {
            this.btnPreview.disabled = true;
        }
        if (this.btnPdf) {
            this.btnPdf.disabled = true;
        }
    }

    /**
     * Esconde indicador de carregamento
     */
    esconderLoading() {
        if (this.loadingIndicator) {
            this.loadingIndicator.classList.add('d-none');
        }
        if (this.btnPreview) {
            this.btnPreview.disabled = false;
        }
        if (this.btnPdf) {
            this.btnPdf.disabled = false;
        }
    }

    /**
     * Exibe mensagem de erro
     * @param {string} mensagem - Mensagem de erro
     */
    mostrarErro(mensagem) {
        if (this.previewContainer) {
            this.previewContainer.innerHTML = `
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    ${mensagem}
                </div>
            `;
        }
    }

    /**
     * Carrega preview via AJAX
     */
    async carregarPreview() {
        if (!window.ROUTES?.preview) {
            console.error('URL de preview nao configurada');
            this.mostrarErro('Configuracao de rotas ausente');
            return;
        }

        this.mostrarLoading();

        const filtros = this.coletarFiltros();
        console.log('📊 Carregando preview com filtros:', filtros);

        try {
            const response = await fetch(window.ROUTES.preview, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(filtros)
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (data.success) {
                this.previewContainer.innerHTML = data.html;
                console.log('✅ Preview carregado com sucesso');

                // Emite evento customizado
                this.form.dispatchEvent(new CustomEvent('preview:loaded', {
                    detail: { data, filtros }
                }));
            } else {
                this.mostrarErro(data.message || 'Erro ao carregar preview');
            }
        } catch (error) {
            console.error('❌ Erro ao carregar preview:', error);
            this.mostrarErro('Erro de conexao. Tente novamente.');
        } finally {
            this.esconderLoading();
        }
    }

    /**
     * Gera PDF com os filtros atuais
     */
    gerarPdf() {
        if (!window.ROUTES?.pdf) {
            console.error('URL de PDF nao configurada');
            alert('Configuracao de rotas ausente');
            return;
        }

        const filtros = this.coletarFiltros();
        const params = new URLSearchParams(filtros);
        const url = `${window.ROUTES.pdf}?${params.toString()}`;

        console.log('📄 Gerando PDF:', url);

        // Abre PDF em nova aba
        window.open(url, '_blank');
    }
}

/**
 * Inicializacao quando DOM estiver pronto
 */
document.addEventListener('DOMContentLoaded', () => {
    // Verifica se estamos em uma pagina de relatorio
    if (window.RELATORIO_TIPO) {
        window.relatorioManager = new RelatorioManager();
    }
});

// Exporta para uso global
window.RelatorioManager = RelatorioManager;
