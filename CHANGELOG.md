# Changelog - Projeto Almasa

Todas as mudanças notáveis neste projeto serão documentadas neste arquivo.

O formato é baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.0.0/),
e este projeto adere ao [Semantic Versioning](https://semver.org/lang/pt-BR/).

## [Unreleased]

---

## [6.5.6] - 2025-11-23

### Corrigido
- **CRÍTICO:** Campos do cônjuge não chegavam ao `PessoaService` ao salvar/editar pessoa
  - **Sintoma:** Campos `novo_conjuge`, `temConjuge` e `conjuge_id` enviados pelo JavaScript não eram processados
  - **Causa raiz:** Controller descartava esses campos ao extrair apenas `$requestData['pessoa_form']`
  - **Solução implementada:**
    - Fazer merge explícito dos campos raw com os dados do formulário no Controller
    - Métodos `new()` e `edit()` do `PessoaController` agora incluem:
      ```php
      $formData = array_merge(
          $requestData['pessoa_form'] ?? [],
          [
              'novo_conjuge' => $requestData['novo_conjuge'] ?? null,
              'temConjuge' => $requestData['temConjuge'] ?? null,
              'conjuge_id' => $requestData['conjuge_id'] ?? null
          ]
      );
      ```
  - **Impacto:** Dados do cônjuge agora chegam corretamente ao Service para processamento
  - **Arquivos modificados:** `src/Controller/PessoaController.php` (linhas ~57 e ~449)
  - **Solução segue padrão:** Mantém Controller "thin" (apenas prepara dados) e Service "fat" (processa lógica)

---

## [6.5.5] - 2025-11-16

### Corrigido
- **CRÍTICO:** Select de profissão não atualiza após adicionar nova profissão via modal
  - **Sintoma:** Profissão é persistida no banco, mas não aparece nos selects
  - **Causa raiz:** Nova profissão era adicionada apenas ao select atual (índice específico), não a todos os selects existentes
  - **Solução implementada:**
    1. Adicionar profissão a **TODOS** os selects de profissão existentes (pessoa principal + cônjuge)
    2. Atualizar cache `window.tiposProfissao` para que novos cards criados já tenham a profissão
    3. Selecionar automaticamente a profissão no select que acionou o modal
  - **Impacto:** Agora profissões aparecem em todos os selects existentes E nos novos cards criados
- **CRÍTICO:** Cônjuge não é salvo ao editar pessoa existente
  - **Sintoma:** Ao preencher dados do cônjuge e salvar, página apenas recarrega sem persistir nenhum dado
  - **Possível causa:** Checkbox `temConjuge` não tinha atributo `name`, então não era enviado no POST
  - **Solução temporária (investigação em andamento):**
    1. Adicionar `name="temConjuge"` e `value="1"` ao checkbox
    2. Atualizar validação em `processarConjugeEdicao()` para verificar também o checkbox
    3. Adicionar logs detalhados para debug (campos recebidos, decisões, chamadas de métodos)
  - **Status:** Correção parcial aplicada, aguardando testes para confirmar resolução completa

### Alterado
- `PessoaService::processarConjugeEdicao()` agora registra logs detalhados de debug
- Modal de profissão atualiza cache global e todos os selects existentes

### Para Testar
- [ ] Adicionar profissão via modal e verificar se aparece em todos os selects
- [ ] Criar novo card de profissão após adicionar profissão e verificar se aparece
- [ ] Tentar salvar cônjuge e verificar logs do console/servidor para identificar problema
- [ ] Confirmar se dados do cônjuge são persistidos no banco

---

## [6.5.4] - 2025-11-16

### Adicionado
- ✅ **Implementação completa do método `buscarConjugePessoa()` no `PessoaService`**
  - Busca relacionamento em `relacionamentos_familiares`
  - Recupera **TODOS** os dados múltiplos do cônjuge:
    - Telefones
    - Endereços
    - Emails
    - Documentos
    - Chaves PIX
    - Profissões
  - Validação de relacionamento bidirecional garantida pelo método `estabelecerRelacionamentoConjugal()`
  - Retorna array completo ou `null` se não houver cônjuge
- Método `listarPessoasEnriquecidas()` no `PessoaService`
  - Retorna lista de pessoas com CPF/CNPJ e tipos ativos
  - Usado no `PessoaController::index()` para listagem enriquecida

### Alterado
- `PessoaController::searchPessoaAdvanced()` agora retorna dados do cônjuge via `buscarConjugePessoa()`
- Frontend (`pessoa_form.js`) carrega dados do cônjuge automaticamente ao buscar pessoa existente
- Função `carregarDadosConjuge()` processa objeto completo de cônjuge com todos os dados múltiplos

### Validado
- ✅ Backend: `buscarConjugePessoa()` retorna dados completos do cônjuge
- ✅ Frontend: `carregarDadosConjuge()` preenche formulário com dados do cônjuge
- ✅ Dados múltiplos do cônjuge são carregados e exibidos corretamente

---

## [6.5.3] - 2025-11-16

### Corrigido
- **CRÍTICO:** Erro 500 ao salvar naturalidade/nacionalidade
  - **Causa raiz dupla:**
    1. Coluna `id` no banco PostgreSQL não tinha valor padrão (sequence não vinculada)
    2. Sequence começava em 1, mas já existia registro com ID 1 no banco
  - **Sintomas:**
    - `SQLSTATE[23502]: Not null violation: null value in column "id"`
    - `another object of class Naturalidade was already present for the same ID`
  - **Solução CORRETA:** Migration `Version20251116212500` que:
    - Vincula sequences às colunas `id`
    - Ajusta valor inicial: `COALESCE((SELECT MAX(id) FROM tabela), 0) + 1`
    - Garante que próximo ID não conflita com dados existentes
  - **Solução entidades:** Adicionada estratégia explícita `IDENTITY` em `#[ORM\GeneratedValue]`
  - ⚠️ **NUNCA alterar banco diretamente - SEMPRE via migrations**
- **CRÍTICO:** Violação de arquitetura "Thin Controller, Fat Service"
  - Controllers `NaturalidadeController` e `NacionalidadeController` faziam persist/flush diretamente
  - **Solução:** Criados `NaturalidadeService` e `NacionalidadeService`
  - Controllers agora apenas delegam para Services (conforme CLAUDE.md)

### Adicionado
- `src/Service/NaturalidadeService.php`
  - Método `salvarNaturalidade(string $nome): Naturalidade`
  - Fat Service com toda a lógica de negócio
  - Validação, persist, flush e logging
- `src/Service/NacionalidadeService.php`
  - Método `salvarNacionalidade(string $nome): Nacionalidade`
  - Fat Service com toda a lógica de negócio
  - Validação, persist, flush e logging

### Alterado
- `src/Entity/Naturalidade.php`
  - Adicionada estratégia explícita `IDENTITY` em `#[ORM\GeneratedValue(strategy: 'IDENTITY')]`
  - Especificado tipo da coluna `#[ORM\Column(type: 'integer')]`
- `src/Entity/Nacionalidade.php`
  - Adicionada estratégia explícita `IDENTITY` em `#[ORM\GeneratedValue(strategy: 'IDENTITY')]`
  - Especificado tipo da coluna `#[ORM\Column(type: 'integer')]`
- `src/Controller/NaturalidadeController.php`
  - Injetado `NaturalidadeService` via construtor
  - Método `salvar()` refatorado para usar Service
  - Removido acesso direto ao EntityManager
  - Tratamento separado de RuntimeException (400) e Exception (500)
- `src/Controller/NacionalidadeController.php`
  - Injetado `NacionalidadeService` via construtor
  - Método `salvar()` refatorado para usar Service
  - Removido acesso direto ao EntityManager
  - Tratamento separado de RuntimeException (400) e Exception (500)
- **Migration criada:** `migrations/Version20251116212500.php`
  - Vincula sequence `naturalidades_id_seq` à coluna `id`
  - Vincula sequence `nacionalidades_id_seq` à coluna `id`
  - Ajusta valor inicial das sequences: `setval(..., MAX(id) + 1, false)`
  - Evita conflitos de ID com registros existentes
  - Método `down()` implementado para rollback seguro

### Motivação
- **Correção crítica:** Sequence do PostgreSQL não estava vinculada à coluna `id`
- **Banco de dados é fonte da verdade:** Mapeamento ORM deve refletir exatamente a estrutura do banco
- **Conformidade com CLAUDE.md:** "Thin Controller, Fat Service" é regra de ouro
- **Clean Code:** Controller não deve conter lógica de negócio nem transações
- **Symfony Best Practices:**
  - Services devem conter persist/flush, não controllers
  - Entidades devem especificar estratégia de geração de ID para PostgreSQL (`IDENTITY`)
  - **BANCO SÓ SE ALTERA VIA MIGRATIONS** - nunca executar SQL diretamente
  - Migrations garantem reprodutibilidade em produção

### Arquivos Afetados
- `src/Service/NaturalidadeService.php` (novo)
- `src/Service/NacionalidadeService.php` (novo)
- `src/Entity/Naturalidade.php` (corrigido mapeamento ORM)
- `src/Entity/Nacionalidade.php` (corrigido mapeamento ORM)
- `src/Controller/NaturalidadeController.php` (refatorado)
- `src/Controller/NacionalidadeController.php` (refatorado)
- `migrations/Version20251116212500.php` (migration criada e executada)

---

## [6.5.2] - 2025-11-16

### Segurança
- **CRÍTICO:** Correção de vulnerabilidade CSRF
  - Adicionada validação de token CSRF no método `salvar()` do `NaturalidadeController`
  - Adicionada validação de token CSRF no método `salvar()` do `NacionalidadeController`
  - Validação usando padrão `ajax_global` (consistente com `PessoaController`)
  - Retorna HTTP 403 quando token é inválido

### Corrigido
- Erro 500 ao tentar criar nova naturalidade via modal (PARCIAL - Corrigido completamente em 6.5.3)
  - **Causa inicial:** Falta de validação de token CSRF
  - **Sintoma:** Transação era iniciada, INSERT executado, mas rollback automático ocorria
  - **Solução inicial:** Validação de token CSRF antes de processar requisição
- Mesma vulnerabilidade presente em nacionalidades (corrigida preventivamente)

### Motivação
- **Segurança:** Todas as requisições AJAX POST devem validar token CSRF
- **Consistência:** Padrão `ajax_global` usado em todo o projeto
- **OWASP Top 10:** Proteção contra Cross-Site Request Forgery

### Arquivos Afetados
- `src/Controller/NaturalidadeController.php` (método `salvar()` linhas 99-106)
- `src/Controller/NacionalidadeController.php` (método `salvar()` linhas 99-106)

---

## [6.5.1] - 2025-11-16

### Corrigido
- **CRÍTICO:** Violação de arquitetura "Thin Controller, Fat Service"
  - Movida lógica de enriquecimento de dados do `PessoaController::index()` para `PessoaService`
  - Criado método `PessoaService::listarPessoasEnriquecidas()`
  - Controller agora apenas delega para Service (conforme CLAUDE.md)
  - Arquivo: `src/Controller/PessoaController.php:32-41`
  - Arquivo: `src/Service/PessoaService.php:1431-1465`

### Alterado
- **JavaScript Modular:** Renomeado `assets/js/pessoa/new.js` para `assets/js/pessoa/pessoa_form.js`
  - Nome mais descritivo: reflete que é usado tanto para criar quanto editar
  - Consolidação: removido arquivo `edit.js` redundante
  - Integrada lógica de modo de edição diretamente em `pessoa_form.js`
  - Detecção automática de modo via `window.IS_EDIT_MODE`
  - Carregamento automático de dados em modo de edição
- **templates/pessoa/pessoa_form.html.twig** (linha 575)
  - Atualizada referência de `new.js` para `pessoa_form.js`
  - Removida carga condicional de `edit.js`

### Removido
- Arquivo `assets/js/pessoa/edit.js` (redundante)
  - Conteúdo integrado em `pessoa_form.js`
  - Eliminação de duplicação de código

### Motivação
- **Conformidade com CLAUDE.md:** Aplicar rigorosamente "Thin Controller, Fat Service"
- **Clean Code:** Controller não deve conter lógica de negócio
- **DRY:** Evitar duplicação entre `new.js` e `edit.js`
- **Nomenclatura:** Nome de arquivo deve refletir responsabilidade real

### Arquivos Afetados
- `src/Controller/PessoaController.php` (método `index()` refatorado)
- `src/Service/PessoaService.php` (novo método `listarPessoasEnriquecidas()`)
- `assets/js/pessoa/new.js` → `assets/js/pessoa/pessoa_form.js` (renomeado)
- `assets/js/pessoa/edit.js` (deletado)
- `templates/pessoa/pessoa_form.html.twig` (referências atualizadas)

---

## [6.5.0] - 2025-11-16

### Adicionado
- Implementado carregamento automático de dados no modo de edição
  - Arquivo: `assets/js/pessoa/edit.js`
  - Busca automática dos dados da pessoa quando clica em "Editar" no index
  - Preenchimento automático do formulário sem necessidade de busca manual
- Adicionado enriquecimento de dados no método `PessoaController::index()`
  - Busca automática de CPF/CNPJ de cada pessoa via `PessoaRepository`
  - Busca automática dos tipos de cada pessoa via `findTiposComDados()`
  - Estrutura de dados retornada: `['entidade' => $pessoa, 'cpf' => $cpf, 'cnpj' => $cnpj, 'tipos' => $tiposAtivos]`

### Alterado
- **PessoaController::index()** (`src/Controller/PessoaController.php:32-64`)
  - Modificado para buscar CPF/CNPJ e tipos de cada pessoa
  - Retorna array enriquecido com dados adicionais
- **PessoaController::edit()** (`src/Controller/PessoaController.php:451-481`)
  - Adicionado parâmetro `isEditMode => true`
  - Adicionado parâmetro `pessoaId` para identificar a pessoa sendo editada
- **PessoaController::new()** (`src/Controller/PessoaController.php:91-96`)
  - Adicionado parâmetro `isEditMode => false` para consistência
  - Adicionado parâmetro `pessoaId => null`
- **templates/pessoa/index.html.twig** (linhas 33-85)
  - Modificada estrutura de loop para acessar `pessoa.entidade` em vez de `pessoa` diretamente
  - Implementada exibição de CPF/CNPJ na coluna "Documento"
  - Implementada exibição de tipos por extenso na coluna "Tipo(s)"
  - Tipos agora aparecem um embaixo do outro (vertical) em vez de lado a lado
  - Mapeamento de tipos: `{'contratante': 'Contratante', 'fiador': 'Fiador', ...}`
- **templates/pessoa/pessoa_form.html.twig**
  - Interface de busca agora fica oculta quando `isEditMode = true` (linha 20)
  - Formulário principal aparece automaticamente quando `isEditMode = true` (linha 76)
  - Adicionadas variáveis globais JavaScript: `window.IS_EDIT_MODE` e `window.PESSOA_ID` (linhas 561-563)
  - Script `edit.js` carregado condicionalmente apenas em modo de edição (linhas 583-585)

### Corrigido
- Coluna "Documento" no index agora exibe corretamente CPF ou CNPJ
  - Dados buscados da tabela `pessoas_documentos` (fonte da verdade)
  - Nunca mais usa campos inexistentes `cpf`/`cnpj` diretamente na entidade `Pessoas`
- Coluna "Tipo(s)" no index agora exibe os tipos da pessoa
  - Tipos exibidos por extenso: "Contratante", "Fiador", "Locador", etc.
  - Múltiplos tipos aparecem verticalmente (um por linha)
  - Exibe "Sem tipo" quando a pessoa não possui tipos ativos
- Botão "Editar" no index agora abre o formulário já preenchido
  - Não cai mais na interface de busca
  - Dados carregados automaticamente via AJAX
  - Experiência de edição mais fluida e intuitiva

### Motivação
- Melhorar experiência do usuário ao editar pessoas
- Eliminar passo desnecessário de busca quando já sabemos qual pessoa editar
- Exibir informações corretas (CPF/CNPJ e tipos) na listagem de pessoas
- Seguir arquitetura do projeto: dados de documentos devem vir da tabela `pessoas_documentos`
- Manter consistência visual com tipos exibidos verticalmente

### Arquivos Afetados
- `src/Controller/PessoaController.php` (métodos `index`, `new`, `edit`)
- `templates/pessoa/index.html.twig` (toda a tabela de listagem)
- `templates/pessoa/pessoa_form.html.twig` (interface de busca e variáveis globais)
- `assets/js/pessoa/edit.js` (novo arquivo)

---

## [6.4.1] - 2025-11-16

### Adicionado
- Criado arquivo `CLAUDE.md` com diretrizes completas do projeto
  - Documentação de stack tecnológica
  - Regras de ouro de arquitetura
  - Estrutura de pastas e arquivos
  - Referência do banco de dados
  - Comandos essenciais
  - Glossário técnico

### Alterado
- Renomeado `templates/pessoa/new.html.twig` para `templates/pessoa/pessoa_form.html.twig`
- Atualizado `PessoaController.php` para usar novo caminho do template

### Motivação
- Nome `new.html.twig` era ambíguo pois o template serve tanto para criar quanto editar
- `pessoa_form.html.twig` deixa claro que é um formulário reutilizável
- Centralização de documentação técnica no `CLAUDE.md`

---

## [6.4.0] - 2025-11-16

### Corrigido
- **Bug crítico:** Tipos de pessoa não carregavam ao buscar pessoa existente
  - Arquivo: `assets/js/pessoa/new.js` (linha 393)
    - Problema: Função `carregarTiposExistentes()` chamada com 1 parâmetro, esperava 2
    - Solução: Corrigida chamada para passar `tipos` e `tiposDados` separadamente
  - Arquivo: `assets/js/pessoa/new.js` (função `preencheSubForm`)
    - Problema: Tentativa de preencher campo `id` inexistente no formulário HTML
    - Solução: Adicionada lista de campos de sistema a serem ignorados
  - Arquivo: `assets/js/pessoa/pessoa_tipos.js` (linha 216)
    - Problema: Assinatura incorreta da função `carregarTiposExistentes()`
    - Solução: Corrigida para receber 2 parâmetros distintos: `tipos` e `tiposDados`
  - Arquivo: `assets/js/pessoa/pessoa_tipos.js` (função `preencherDadosTipo`)
    - Problema: Tentativa de preencher campos de sistema
    - Solução: Adicionado filtro para ignorar campos: `id`, `created_at`, `updated_at`, etc.

- Corrigidos métodos de busca de documentos em `PessoaService`
  - Melhorada estrutura da resposta JSON na busca avançada de pessoa
  - Corrigido método `buscarDocumentosPessoa()` para retornar estrutura consistente

### Alterado
- Removidos campos e listeners obsoletos do formulário de Pessoa
  - Simplificada lógica de tipos via JavaScript
  - Melhor separação de responsabilidades entre módulos JS

### Aprendizados
- Sempre verificar assinatura de funções antes de chamá-las
- Filtrar campos de banco de dados ao preencher formulários HTML
- Logs detalhados são essenciais para debug (`console.log`, `console.warn`, `console.error`)
- Separação de responsabilidades: `new.js` chama funções, `pessoa_tipos.js` gerencia tipos

---

## [6.3.0] - 2025-11-09

### Adicionado
- Criado `src/Service/PessoaService.php` (Fat Service)
  - Método `buscarTelefonesPessoa(int $pessoaId): array`
  - Método `buscarEnderecosPessoa(int $pessoaId): array`
  - Método `buscarEmailsPessoa(int $pessoaId): array`
  - Método `buscarDocumentosPessoa(int $pessoaId): array`
  - Método `buscarChavesPixPessoa(int $pessoaId): array`
  - Método `buscarProfissoesPessoa(int $pessoaId): array`
  - Método `deleteTelefone(int $id): bool`
  - Método `deleteEndereco(int $id): bool`
  - Método `deleteEmail(int $id): bool`
  - Método `deleteDocumento(int $id): bool`
  - Método `deleteChavePix(int $id): bool`
  - Método `deleteProfissao(int $id): bool`
  - Método `findByCpfDocumento(string $cpf): ?Pessoas`
  - Método `findCnpjDocumento(string $cnpj): ?Pessoas`

### Alterado
- Refatorado `src/Controller/PessoaController.php` (Thin Controller)
  - Movida TODA lógica de negócio para `PessoaService`
  - Controller agora apenas delega para Service
  - Métodos movidos: `buscar*`, `delete*`, `criarPessoa`, `atualizarPessoa`
  - Controller apenas valida Request, chama Service e retorna Response

### Motivação
- Aplicar arquitetura "Thin Controller, Fat Service"
- Separar responsabilidades (Controller = HTTP, Service = Negócio)
- Facilitar testes unitários e reutilização de lógica
- Seguir Symfony Best Practices

---

## [6.2.0] - 2025-11-08

### Adicionado
- Implementados módulos JavaScript para dados múltiplos do cônjuge:
  - `assets/js/pessoa/conjuge_telefones.js`
  - `assets/js/pessoa/conjuge_enderecos.js`
  - `assets/js/pessoa/conjuge_emails.js`
  - `assets/js/pessoa/conjuge_documentos.js`
  - `assets/js/pessoa/conjuge_chave_pix.js`
  - `assets/js/pessoa/conjuge_profissoes.js`

- Implementado `assets/js/pessoa/pessoa_conjuge.js`
  - Método `salvarConjuge()`
  - Método `carregarDadosConjuge()`

- Implementado `assets/js/pessoa/pessoa_modals.js`
  - Método `salvarNovoTipo()` reutilizável para modais de tipos

### Motivação
- Gerenciar dados múltiplos do cônjuge de forma consistente
- Reutilizar lógica de salvamento de tipos via modais
- Manter arquitetura modular do JavaScript

---

## [6.1.0] - 2025-11-07

### Adicionado
- Implementadas rotas DELETE para dados múltiplos:
  - `/pessoa/telefone/{id}` (DELETE) - `app_pessoa_delete_telefone`
  - `/pessoa/endereco/{id}` (DELETE) - `app_pessoa_delete_endereco`
  - `/pessoa/email/{id}` (DELETE) - `app_pessoa_delete_email`
  - `/pessoa/documento/{id}` (DELETE) - `app_pessoa_delete_documento`
  - `/pessoa/chave-pix/{id}` (DELETE) - `app_pessoa_delete_chave_pix`
  - `/pessoa/profissao/{id}` (DELETE) - `app_pessoa_delete_profissao`

- Implementados módulos JavaScript para DELETE:
  - `assets/js/pessoa/pessoa_enderecos.js`
  - `assets/js/pessoa/pessoa_telefones.js`
  - `assets/js/pessoa/pessoa_emails.js`
  - `assets/js/pessoa/pessoa_chave_pix.js`
  - `assets/js/pessoa/pessoa_documentos.js`
  - `assets/js/pessoa/pessoa_profissoes.js`

### Corrigido
- Token CSRF `ajax_global` implementado corretamente em TODAS requisições AJAX
- Todos os JSONs de entidades agora incluem campo `id` obrigatório
- Headers padronizados para fetch:
  ```javascript
  {
    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content,
    'X-Requested-With': 'XMLHttpRequest',
    'Content-Type': 'application/json'
  }
  ```

### Motivação
- Permitir exclusão de dados múltiplos via AJAX
- Padronizar respostas JSON (`{'success': true}` ou `{'success': false, 'message': '...'}`)
- Garantir segurança com CSRF token único global

---

## [6.0.0] - 2025-11-06

### Adicionado
- Implementada busca inteligente de pessoa no formulário de cadastro
  - Busca por CPF/CNPJ via `searchPessoaAdvanced`
  - Preenchimento automático do formulário com dados existentes
  - Rota: `app_pessoa_search_advanced`

- Implementado sistema de tipos múltiplos:
  - `assets/js/pessoa/pessoa_tipos.js`
  - Gerenciamento dinâmico de cards de tipo
  - Carregamento de sub-formulários via AJAX
  - Rota: `app_pessoa__subform`

- Implementados FormTypes para cada tipo de pessoa:
  - `PessoaFiadorType`
  - `PessoaLocadorType`
  - `PessoaContratoType`
  - `PessoaCorretorType`
  - `PessoaCorretoraType`
  - `PessoaPretendentesType`

- Criado `assets/js/pessoa/pessoa.js` com utilitários:
  - `setFormValue()` - Define valor em campo de formulário
  - `carregarTiposExistentes()` - Carrega tipos ao buscar pessoa

### Alterado
- Atualizado template `templates/pessoa/new.html.twig`
  - Adicionado campo de busca por CPF/CNPJ
  - Implementado sistema de abas para tipos
  - Melhorada experiência do usuário

### Motivação
- Evitar duplicação de cadastros
- Permitir que uma pessoa tenha múltiplos papéis simultaneamente
- Melhorar usabilidade do formulário

---

## [5.0.0] - 2025-11-05

### Adicionado
- Implementação inicial do módulo de Pessoas
- Entidades Doctrine:
  - `Pessoas` (entidade central)
  - `PessoasFiadores`
  - `PessoasLocadores`
  - `PessoasCorretores`
  - `PessoasCorretoras`
  - `PessoasContratantes`
  - `PessoasPretendentes`
  - `Enderecos`
  - `Telefones`
  - `Emails`
  - `ChavesPix`
  - `PessoasDocumentos`
  - `PessoasProfissoes`
  - `RelacionamentosFamiliares`

- Repositórios:
  - `PessoasRepository` com consultas DQL customizadas
  - Repositórios para todas as entidades relacionadas

- Controller inicial:
  - `PessoaController` com CRUD básico
  - Rotas: `app_pessoa_index`, `app_pessoa_new`, `app_pessoa_edit`, `app_pessoa_delete`

- Formulário:
  - `PessoaFormType` com todos os campos básicos

### Configuração
- Configurado PostgreSQL 14+ como banco de dados
- Configurado Webpack Encore para build de assets
- Configurado Bootstrap 5.3 para frontend

---

## REGRA IMPORTANTE PARA CLAUDE CODE:

**SEMPRE que realizar qualquer mudança no código, IMEDIATAMENTE atualize este CHANGELOG.md seguindo o formato acima.**

### Categorias de mudanças:
- **Adicionado** - para novas funcionalidades
- **Alterado** - para mudanças em funcionalidades existentes
- **Descontinuado** - para funcionalidades a serem removidas
- **Removido** - para funcionalidades removidas
- **Corrigido** - para correção de bugs
- **Segurança** - para vulnerabilidades corrigidas

### Sempre inclua:
- Data no formato YYYY-MM-DD
- Descrição clara e concisa
- Arquivos afetados
- Motivação (quando relevante)
- Links para issues/PRs quando aplicável

### Versionamento Semântico:
- **MAJOR** (X.0.0): Mudanças incompatíveis na API
- **MINOR** (x.Y.0): Novas funcionalidades compatíveis
- **PATCH** (x.y.Z): Correções de bugs compatíveis

---

**Última atualização:** 2025-11-16
**Mantenedor:** Marcio Martins
**Desenvolvedor Ativo:** Claude 4.5 Sonnet (via Claude Code)
