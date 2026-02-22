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
                // ✅ CORREÇÃO: Adicionar a profissão em TODOS os selects de profissão existentes
                // Isso garante que a profissão apareça em todos os cards, não apenas no atual

                // 1. Buscar todos os selects de profissão da pessoa principal
                const selectsPessoaPrincipal = document.querySelectorAll('select[id^="profissao_tipo_"]');
                selectsPessoaPrincipal.forEach(select => {
                    const option = new Option(novoTipo.tipo, novoTipo.id, false, false);
                    select.add(option);
                });

                // 2. Buscar todos os selects de profissão do cônjuge
                const selectsConjuge = document.querySelectorAll('select[id^="conjuge_profissao_tipo_"]');
                selectsConjuge.forEach(select => {
                    const option = new Option(novoTipo.tipo, novoTipo.id, false, false);
                    select.add(option);
                });

                // 3. Selecionar automaticamente no select que acionou o modal (se existir)
                const selectAtual = document.getElementById(`profissao_tipo_${window.profissaoIndexAtual}`)
                                 || document.getElementById(`conjuge_profissao_tipo_${window.profissaoIndexAtual}`);

                if (selectAtual) {
                    selectAtual.value = novoTipo.id;
                    console.log('✅ Profissão selecionada automaticamente no select atual:', novoTipo);
                }

                console.log(`✅ Profissão adicionada a ${selectsPessoaPrincipal.length + selectsConjuge.length} select(s)`);

                // 4. Atualizar o cache de profissões para que novos cards tenham a profissão
                if (window.tiposProfissao && Array.isArray(window.tiposProfissao)) {
                    window.tiposProfissao.push({
                        id: novoTipo.id,
                        tipo: novoTipo.tipo
                    });
                    console.log('✅ Cache de profissões atualizado');
                }

                // Fechar modal e limpar campo
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalNovoTipoProfissao'));
                if (modal) modal.hide();
                document.getElementById('novoTipoProfissao').value = '';
            });
        });
    }

    // ============================================================================
    // SALVAR NACIONALIDADE - PESSOA PRINCIPAL E CÔNJUGE
    // ============================================================================
    const salvarNacionalidade = document.getElementById('salvarNacionalidade');
    if (salvarNacionalidade) {
        salvarNacionalidade.addEventListener('click', async function() {
            const valor = document.getElementById('novaNacionalidade')?.value?.trim();
            if (!valor) {
                alert('Digite o nome da nacionalidade');
                return;
            }

            console.log('Salvando nacionalidade:', valor);

            try {
                const response = await fetch(window.ROUTES.salvarNacionalidade, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({ nome: valor })
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const data = await response.json();

                if (data.success) {
                    // Adicionar aos selects da pessoa principal
                    const selectPessoa = document.getElementById(window.FORM_IDS.nacionalidade);
                    if (selectPessoa) {
                        const option = new Option(data.nacionalidade.nome, data.nacionalidade.id, true, true);
                        selectPessoa.add(option);
                        console.log('✅ Nacionalidade adicionada ao select da pessoa principal:', data.nacionalidade);
                    }

                    // Adicionar ao select do cônjuge
                    const selectConjuge = document.querySelector('select[name="novo_conjuge[nacionalidade]"]');
                    if (selectConjuge) {
                        const option = new Option(data.nacionalidade.nome, data.nacionalidade.id, true, true);
                        selectConjuge.add(option);
                        console.log('✅ Nacionalidade adicionada ao select do cônjuge:', data.nacionalidade);
                    }

                    // Fechar modal e limpar campo
                    const modal = bootstrap.Modal.getInstance(document.getElementById('modalNovaNacionalidade'));
                    if (modal) modal.hide();
                    document.getElementById('novaNacionalidade').value = '';
                } else {
                    alert(data.message || 'Erro ao salvar nacionalidade');
                }
            } catch (error) {
                console.error('❌ Erro ao salvar nacionalidade:', error);
                alert('Erro ao salvar nacionalidade. Tente novamente.');
            }
        });
    }

    // ============================================================================
    // SALVAR NATURALIDADE - PESSOA PRINCIPAL E CÔNJUGE
    // ============================================================================
    const salvarNaturalidade = document.getElementById('salvarNaturalidade');
    if (salvarNaturalidade) {
        salvarNaturalidade.addEventListener('click', async function() {
            const valor = document.getElementById('novaNaturalidade')?.value?.trim();
            if (!valor) {
                alert('Digite o nome da naturalidade');
                return;
            }

            console.log('Salvando naturalidade:', valor);

            try {
                const response = await fetch(window.ROUTES.salvarNaturalidade, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({ nome: valor })
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const data = await response.json();

                if (data.success) {
                    // Adicionar aos selects da pessoa principal
                    const selectPessoa = document.getElementById(window.FORM_IDS.naturalidade);
                    if (selectPessoa) {
                        const option = new Option(data.naturalidade.nome, data.naturalidade.id, true, true);
                        selectPessoa.add(option);
                        console.log('✅ Naturalidade adicionada ao select da pessoa principal:', data.naturalidade);
                    }

                    // Adicionar ao select do cônjuge
                    const selectConjuge = document.querySelector('select[name="novo_conjuge[naturalidade]"]');
                    if (selectConjuge) {
                        const option = new Option(data.naturalidade.nome, data.naturalidade.id, true, true);
                        selectConjuge.add(option);
                        console.log('✅ Naturalidade adicionada ao select do cônjuge:', data.naturalidade);
                    }

                    // Fechar modal e limpar campo
                    const modal = bootstrap.Modal.getInstance(document.getElementById('modalNovaNaturalidade'));
                    if (modal) modal.hide();
                    document.getElementById('novaNaturalidade').value = '';
                } else {
                    alert(data.message || 'Erro ao salvar naturalidade');
                }
            } catch (error) {
                console.error('❌ Erro ao salvar naturalidade:', error);
                alert('Erro ao salvar naturalidade. Tente novamente.');
            }
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