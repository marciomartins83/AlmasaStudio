/**
 * Gerencia os modais para salvamento de novos tipos
 * Baseado na funcionalidade do PessoaFiadorController
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('📋 Modais de tipos carregados');

    // =========================================================================
    // EVENT LISTENERS PARA SALVAMENTO DE TIPOS
    // =========================================================================

    // Salvar Tipo de Telefone
    const salvarTipoTelefone = document.getElementById('salvarTipoTelefone');
    if (salvarTipoTelefone) {
        salvarTipoTelefone.addEventListener('click', async function() {
            const valor = document.getElementById('novoTipoTelefone')?.value?.trim();
            if (!valor) {
                alert('Digite o nome do tipo de telefone');
                return;
            }
            
            console.log('Salvando tipo de telefone:', valor);
            
            const sucesso = await salvarNovoTipo('telefone', valor, (novoTipo) => {
                const select = document.getElementById(`telefone_tipo_${window.telefoneIndexAtual}`);
                if (select) {
                    const option = new Option(novoTipo.tipo, novoTipo.id, true, true);
                    select.add(option);
                    console.log('Tipo de telefone adicionado ao select:', novoTipo);
                }
                
                // Fechar modal e limpar campo
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalNovoTipoTelefone'));
                if (modal) modal.hide();
                document.getElementById('novoTipoTelefone').value = '';
            });
        });
    }

    // Salvar Tipo de Endereço
    const salvarTipoEndereco = document.getElementById('salvarTipoEndereco');
    if (salvarTipoEndereco) {
        salvarTipoEndereco.addEventListener('click', async function() {
            const valor = document.getElementById('novoTipoEndereco')?.value?.trim();
            if (!valor) {
                alert('Digite o nome do tipo de endereço');
                return;
            }
            
            console.log('Salvando tipo de endereço:', valor);
            
            const sucesso = await salvarNovoTipo('endereco', valor, (novoTipo) => {
                const select = document.getElementById(`endereco_tipo_${window.enderecoIndexAtual}`);
                if (select) {
                    const option = new Option(novoTipo.tipo, novoTipo.id, true, true);
                    select.add(option);
                    console.log('Tipo de endereço adicionado ao select:', novoTipo);
                }
                
                // Fechar modal e limpar campo
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalNovoTipoEndereco'));
                if (modal) modal.hide();
                document.getElementById('novoTipoEndereco').value = '';
            });
        });
    }

    // Salvar Tipo de Email
    const salvarTipoEmail = document.getElementById('salvarTipoEmail');
    if (salvarTipoEmail) {
        salvarTipoEmail.addEventListener('click', async function() {
            const valor = document.getElementById('novoTipoEmail')?.value?.trim();
            if (!valor) {
                alert('Digite o nome do tipo de email');
                return;
            }
            
            console.log('Salvando tipo de email:', valor);
            
            const sucesso = await salvarNovoTipo('email', valor, (novoTipo) => {
                const select = document.getElementById(`email_tipo_${window.emailIndexAtual}`);
                if (select) {
                    const option = new Option(novoTipo.tipo, novoTipo.id, true, true);
                    select.add(option);
                    console.log('Tipo de email adicionado ao select:', novoTipo);
                }
                
                // Fechar modal e limpar campo
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalNovoTipoEmail'));
                if (modal) modal.hide();
                document.getElementById('novoTipoEmail').value = '';
            });
        });
    }

    // Salvar Tipo de Chave PIX
    const salvarTipoChavePix = document.getElementById('salvarTipoChavePix');
    if (salvarTipoChavePix) {
        salvarTipoChavePix.addEventListener('click', async function() {
            const valor = document.getElementById('novoTipoChavePix')?.value?.trim();
            if (!valor) {
                alert('Digite o nome do tipo de chave PIX');
                return;
            }
            
            console.log('Salvando tipo de chave PIX:', valor);
            
            const sucesso = await salvarNovoTipo('chave-pix', valor, (novoTipo) => {
                const select = document.getElementById(`pix_tipo_${window.pixIndexAtual}`);
                if (select) {
                    const option = new Option(novoTipo.tipo, novoTipo.id, true, true);
                    select.add(option);
                    console.log('Tipo de chave PIX adicionado ao select:', novoTipo);
                }
                
                // Fechar modal e limpar campo
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalNovoTipoChavePix'));
                if (modal) modal.hide();
                document.getElementById('novoTipoChavePix').value = '';
            });
        });
    }

    // Salvar Tipo de Documento
    const salvarTipoDocumento = document.getElementById('salvarTipoDocumento');
    if (salvarTipoDocumento) {
        salvarTipoDocumento.addEventListener('click', async function() {
            const valor = document.getElementById('novoTipoDocumento')?.value?.trim();
            if (!valor) {
                alert('Digite o nome do tipo de documento');
                return;
            }
            
            console.log('Salvando tipo de documento:', valor);
            
            const sucesso = await salvarNovoTipo('documento', valor, (novoTipo) => {
                const select = document.getElementById(`documento_tipo_${window.documentoIndexAtual}`);
                if (select) {
                    const option = new Option(novoTipo.tipo, novoTipo.id, true, true);
                    select.add(option);
                    console.log('Tipo de documento adicionado ao select:', novoTipo);
                }
                
                // Fechar modal e limpar campo
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalNovoTipoDocumento'));
                if (modal) modal.hide();
                document.getElementById('novoTipoDocumento').value = '';
            });
        });
    }

    // ============================================================================
    // SALVAR TIPO DE PROFISSÃO - CORRIGIDO PARA PESSOA E CÔNJUGE
    // ============================================================================
    const salvarTipoProfissao = document.getElementById('salvarTipoProfissao');
    if (salvarTipoProfissao) {
        salvarTipoProfissao.addEventListener('click', async function() {
            const valor = document.getElementById('novoTipoProfissao')?.value?.trim();
            if (!valor) {
                alert('Digite o nome da profissão');
                return;
            }
            
            console.log('Salvando tipo de profissão:', valor);
            
            const sucesso = await salvarNovoTipo('profissao', valor, (novoTipo) => {
                // Tentar primeiro o select da pessoa principal
                let select = document.getElementById(`profissao_tipo_${window.profissaoIndexAtual}`);
                
                // Se não encontrou, tentar o select do cônjuge
                if (!select) {
                    select = document.getElementById(`conjuge_profissao_tipo_${window.profissaoIndexAtual}`);
                }
                
                if (select) {
                    const option = new Option(novoTipo.tipo, novoTipo.id, true, true);
                    select.add(option);
                    console.log('Tipo de profissão adicionado ao select:', novoTipo);
                } else {
                    console.error('Select de profissão não encontrado para index:', window.profissaoIndexAtual);
                }
                
                // Fechar modal e limpar campo
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalNovoTipoProfissao'));
                if (modal) modal.hide();
                document.getElementById('novoTipoProfissao').value = '';
            });
        });
    }

    // =========================================================================
    // FUNÇÃO DE SALVAMENTO (usando a do pessoa.js)
    // =========================================================================
    
    // Verificar se a função salvarNovoTipo está disponível
    if (typeof window.salvarNovoTipo !== 'function') {
        console.error('❌ Função salvarNovoTipo não encontrada. Verifique se pessoa.js foi carregado.');
    } else {
        console.log('✅ Função salvarNovoTipo disponível');
    }

    console.log('✅ Event listeners dos modais configurados');
});