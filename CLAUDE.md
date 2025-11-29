# CLAUDE.md - Projeto Almasa

Este arquivo fornece orientação completa para o Claude Code ao trabalhar neste repositório.

---

## 🚨 ATENÇÃO - ARQUIVO ÚNICO DE MUDANÇAS

**⚠️ PARA TODOS OS MODELOS (Sonnet, Opus, Haiku):**

### CHANGELOG.md É O ÚNICO ARQUIVO PARA REGISTRAR MUDANÇAS

✅ **PERMITIDO:**
- `CLAUDE.md` - Diretrizes e documentação do projeto
- `CHANGELOG.md` - **ÚNICO** arquivo para registrar mudanças

❌ **PROIBIDO - NUNCA CRIE:**
- `CORRECAO_*.md`
- `MIGRATION_*.md`
- `FIX_*.md`
- `UPDATE_*.md`
- Qualquer outro arquivo `.md` temporário

**REGRA DE OURO:** Se você fez uma mudança, atualize **IMEDIATAMENTE** o `CHANGELOG.md` seguindo o formato [Keep a Changelog](https://keepachangelog.com/pt-BR/1.0.0/).

Veja detalhes completos na seção "📚 Documentação e Histórico" abaixo.

---

## 🎯 Visão Geral do Projeto

**AlmasaStudio** é um sistema completo de gestão imobiliária desenvolvido em **Symfony 7.2** e **PHP 8.2+**. 

O sistema gerencia relacionamentos complexos entre pessoas (locadores, inquilinos, fiadores, corretores), imóveis, contratos e entidades de negócio relacionadas. O modelo de domínio está em **português brasileiro**, refletindo o mercado imobiliário do Brasil.

---

## 📚 Stack Tecnológica (REFERÊNCIA OFICIAL)

### Backend
- **PHP:** 8.2+
- **Framework:** Symfony 7.2 (CLI: Symfony CLI 5.15.1)
- **ORM:** Doctrine 2
- **Banco de Dados:** PostgreSQL 14+

### Frontend
- **Templates:** Twig 3
- **CSS Framework:** Bootstrap 5.3
- **JavaScript:** Vanilla JS (ES6) - Modular
- **Build Tool:** Webpack Encore
- **Componentes:** Hotwired Stimulus, Hotwired Turbo

### Segurança
- **CSRF:** Token único global `ajax_global` para TODAS as requisições AJAX
- **Autenticação:** Symfony Security Bundle

### Rotas Padrão
- **DELETE:** Padrão `/pessoa/{entidade}/{id}` usando método HTTP DELETE
- **Resposta JSON:** Sempre `{'success': true}` ou `{'success': false, 'message': '...'}`

---

## 🚨 REGRAS DE OURO (INQUEBRÁVEIS)

### 1. Arquitetura: "Thin Controller, Fat Service"

**Controllers** (`src/Controller/`):
- Apenas recebem `Request`
- Validam formulário (se houver)
- Chamam o `Service` apropriado
- Retornam `Response` (View ou JSON)
- **PROIBIDO:** Lógica de negócio, transações, `flush()`, `persist()`, `remove()`

**Services** (`src/Service/`):
- Contêm TODA a lógica de negócio
- Validações complexas
- Gerenciamento de transações (`beginTransaction`, `commit`, `rollBack`)
- Operações de persistência (`persist`, `remove`, `flush`)

**Repositórios** (`src/Repository/`):
- Consultas DQL/SQL complexas
- Métodos de busca customizados
- **SEMPRE colocar DQL em Repository, NUNCA em Controller ou Service**
- Exemplo: `findByCpfDocumento`, `searchPessoa`

### 2. Frontend: JavaScript 100% Modular

**PROIBIDO:**
- Código JavaScript inline em templates Twig
- Atributos `onclick`, `onchange`, etc.
- Tags `<script>` com código dentro dos arquivos `.twig`

**OBRIGATÓRIO:**
- Todo JavaScript em arquivos `.js` dedicados em `assets/js/`
- Organização modular por funcionalidade

**ÚNICA EXCEÇÃO:**
- Passar dados do backend para frontend via variáveis globais no final do Twig:
```twig
{# No FINAL do arquivo .twig #}

    window.ROUTES = {
        subform: '{{ path("app_pessoa__subform") }}',
        delete: '{{ path("app_pessoa_delete_telefone", {id: '__ID__'}) }}'
    };
    
    window.FORM_IDS = {
        pessoaId: '{{ form.pessoaId.vars.id | default('') }}'
    };


{# Depois carrega os scripts externos #}


```

### 3. Banco de Dados é a Fonte da Verdade

- Entidades Doctrine devem **refletir exatamente** as tabelas PostgreSQL
- Em caso de divergência: **O BANCO PREVALECE**
- Sempre validar com `php bin/console doctrine:schema:validate`

### 4. Token CSRF Único

- **UM ÚNICO TOKEN:** `ajax_global` para TODAS as requisições AJAX
- Definido em meta tag: `<meta name="csrf-token" content="{{ csrf_token('ajax_global') }}">`
- Headers obrigatórios em fetch:
```javascript
headers: {
    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content,
    'X-Requested-With': 'XMLHttpRequest',
    'Content-Type': 'application/json'
}
```

### 5. JSON de Entidades com DELETE

**SEMPRE incluir `id` no JSON** de entidades que podem ser deletadas:
```php
// ✅ CORRETO
return new JsonResponse([
    'id' => $telefone->getId(),
    'numero' => $telefone->getNumero(),
    'tipo' => $telefone->getTipo()
]);

// ❌ ERRADO (sem id)
return new JsonResponse([
    'numero' => $telefone->getNumero(),
    'tipo' => $telefone->getTipo()
]);
```

### 6. Symfony Best Practices

**SEMPRE aplicar:**
- ✅ **Clean Code** - nomes descritivos, métodos pequenos e focados
- ✅ **SOLID Principles** - especialmente Single Responsibility
- ✅ **DRY** - evitar duplicação de código
- ✅ **Type Hints** - sempre declarar tipos de parâmetros e retorno
- ✅ **DocBlocks** - documentar métodos complexos

**Exemplos práticos:**
```php
// ✅ CORRETO - DQL em Repository
class PessoasRepository extends ServiceEntityRepository
{
    public function findByCpfDocumento(string $cpf): ?Pessoas
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.documentos', 'd')
            ->where('d.numero = :cpf')
            ->andWhere('d.tipo = :tipo')
            ->setParameter('cpf', $cpf)
            ->setParameter('tipo', 'CPF')
            ->getQuery()
            ->getOneOrNullResult();
    }
}

// ❌ ERRADO - DQL em Controller
class PessoaController extends AbstractController
{
    public function search(EntityManagerInterface $em): Response
    {
        // ❌ NUNCA fazer isso
        $pessoa = $em->createQueryBuilder()
            ->select('p')
            ->from(Pessoas::class, 'p')
            ->where('p.id = :id')
            ->getQuery()
            ->getOneOrNullResult();
    }
}
```

### 7. Code Review e Aprovação

Antes de aplicar qualquer mudança:
1. **Claude Code mostra o diff** (o que será alterado)
2. **Você pode:**
   - ✅ **Aprovar** - mudança é aplicada
   - ❌ **Rejeitar** - mudança é descartada
   - 🔄 **Pedir ajustes** - exemplo:
     - "DQL sempre em Repository, não em Service"
     - "Aplique Symfony best practices"
     - "Use Clean Code, esse método está muito grande"
     - "Adicione type hints e DocBlocks"
3. **Processo iterativo** - pode ajustar quantas vezes precisar

**Comandos úteis para feedback:**
```
❌ "Rejeitado. DQL deve estar em Repository, não em Controller"
❌ "Rejeitado. Aplique Clean Code - esse método tem 500 linhas"
❌ "Rejeitado. Faltam type hints nos parâmetros"
✅ "Aprovado, mas adicione DocBlock explicando a lógica"
🔄 "Refatore usando Symfony best practices"
```
---

## 📁 Estrutura de Pastas e Arquivos

### Backend
```
src/
├── Controller/
│   └── PessoaController.php          # Thin Controller (delega para Service)
│
├── Service/
│   ├── PessoaService.php              # Fat Service (lógica de negócio)
│   └── CepService.php                 # Busca CEP (API + banco local)
│
├── Entity/
│   ├── Pessoas.php                    # Entidade central
│   ├── PessoasFiadores.php           # Tipo: Fiador
│   ├── PessoasLocadores.php          # Tipo: Locador
│   ├── PessoasCorretores.php         # Tipo: Corretor
│   ├── Enderecos.php                  # Dados múltiplos
│   ├── Telefones.php                  # Dados múltiplos
│   └── ...
│
├── Repository/
│   ├── PessoasRepository.php          # Consultas DQL customizadas
│   └── ...
│
└── Form/
    ├── PessoaFormType.php             # Formulário principal
    └── ...
```

### Frontend
```
assets/js/pessoa/
├── pessoa.js                    # Utilitários, setFormValue, carregar tipos
├── new.js                       # Busca inteligente, preencherFormulario
├── pessoa_tipos.js              # Gerenciamento de tipos múltiplos
├── pessoa_enderecos.js          # DELETE de endereços
├── pessoa_telefones.js          # DELETE de telefones
├── pessoa_emails.js             # DELETE de emails
├── pessoa_chave_pix.js          # DELETE de chaves PIX
├── pessoa_documentos.js         # DELETE de documentos
├── pessoa_profissoes.js         # DELETE de profissões
├── pessoa_conjuge.js            # salvarConjuge, carregarDadosConjuge
├── pessoa_modals.js             # salvarNovoTipo (reutilizável)
├── conjuge_telefones.js         # Dados múltiplos do cônjuge
├── conjuge_enderecos.js         # Dados múltiplos do cônjuge
├── conjuge_emails.js            # Dados múltiplos do cônjuge
├── conjuge_documentos.js        # Dados múltiplos do cônjuge
├── conjuge_chave_pix.js         # Dados múltiplos do cônjuge
└── conjuge_profissoes.js        # Dados múltiplos do cônjuge
```

### Templates
```
templates/
├── pessoa/
│   ├── index.html.twig          # Listagem
│   ├── new.html.twig            # Cadastro
│   ├── edit.html.twig           # Edição
│   └── partials/
│       ├── _subform_fiador.html.twig
│       ├── _subform_locador.html.twig
│       └── ...
```

---

## 🗄️ Referência do Banco de Dados

### Tabelas de Dados Múltiplos

**ATENÇÃO:** Coluna `id` é OBRIGATÓRIA em TODOS os SELECT de entidades deletáveis.

| Tabela | Coluna ID | Chave Estrangeira | Observação |
|--------|-----------|-------------------|------------|
| `enderecos` | `id` | `pessoa_id -> pessoas.id` | Já devolve `id` no JSON |
| `telefones` | `id` | Ligação via `pessoas_telefones.telefone_id` | Tabela pivot |
| `emails` | `id` | Ligação via `pessoas_emails.email_id` | Tabela pivot |
| `chaves_pix` | `id` | `id_pessoa -> pessoas.id` | Direto na tabela |
| `pessoas_documentos` | `id` | `id_pessoa -> pessoas.id` | Direto na tabela |
| `pessoas_profissoes` | `id` | `id_pessoa -> pessoas.id` | Direto na tabela |
| `relacionamentos_familiares` | `id` | `idPessoaOrigem -> pessoas.id`<br>`idPessoaDestino -> pessoas.id` | **Fonte da verdade para Cônjuge**<br>`tipoRelacionamento = 'Cônjuge'` |

### Arquitetura de Cônjuge

**Observação Importante:**
- A coluna `conjuge_id` existe na tabela `pessoas`, mas **NÃO é a fonte da verdade**
- **Fonte oficial:** Tabela `relacionamentos_familiares`
- **Por quê?** Permite histórico, dados contextuais (regime de casamento, datas), e relacionamento bidirecional

---

## 🏗️ Arquitetura e Padrões

### Módulo de Pessoas

**Entidade Central:** `Pessoas`

Uma pessoa pode ter **múltiplos tipos/papéis simultaneamente:**
- **Contratante** (`PessoasContratantes`)
- **Fiador** (`PessoasFiadores`)
- **Locador** (`PessoasLocadores`)
- **Corretor** (`PessoasCorretores`)
- **Corretora** (`PessoasCorretoras` - pessoa jurídica)
- **Pretendente** (`PessoasPretendentes`)

**Sub-formulários Dinâmicos:**
- Seleção de tipo carrega via AJAX um partial `.twig` específico
- Rota: `app_pessoa__subform`
- FormType dedicado para cada tipo (ex: `PessoaFiadorType`)

### Dados Múltiplos

Uma pessoa pode ter múltiplos:
- Telefones
- Endereços
- Emails
- Documentos (CPF, CNPJ, RG, etc.)
- Chaves PIX
- Profissões

**Padrão de DELETE:**
```javascript
// Exemplo: deletar telefone
fetch(`/pessoa/telefone/${id}`, {
    method: 'DELETE',
    headers: {
        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content,
        'X-Requested-With': 'XMLHttpRequest'
    }
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        // Remove da UI
    }
});
```

### Validação de CPF/CNPJ

**Service:** `PessoaService`
- `findByCpfDocumento()` - Busca pessoa por CPF
- `findCnpjDocumento()` - Busca pessoa por CNPJ
- Valida duplicidade ANTES de salvar

---

## 📋 Comandos Essenciais

### Desenvolvimento
```bash
# Instalar dependências
composer install
npm install

# Servidor de desenvolvimento
symfony server:start

# Build de assets
npm run dev          # Desenvolvimento
npm run build        # Produção
npm run watch        # Watch mode (auto-rebuild)
```

### Banco de Dados
```bash
# Criar database
php bin/console doctrine:database:create

# Gerar migration (SEMPRE após alterar entidades)
php bin/console make:migration

# Executar migrations
php bin/console doctrine:migrations:migrate

# Validar schema (comparar entidades vs banco)
php bin/console doctrine:schema:validate
```

### Debug
```bash
# Limpar cache
php bin/console cache:clear

# Listar rotas
php bin/console debug:router

# Ver detalhes de rota específica
php bin/console debug:router app_pessoa_index

# Listar services
php bin/console debug:container
```

---

## 🎯 Contexto Atual do Projeto

### Status: V6.4 (16/11/2025)

**Último Bug Corrigido:**
- Tipos de pessoa não carregavam no frontend ao buscar pessoa existente
- **Arquivos corrigidos:**
  - `assets/js/pessoa/new.js` (2 correções)
  - `assets/js/pessoa/pessoa_tipos.js` (2 correções)
- **Status:** Código corrigido, aguardando validação

### Próxima Tarefa Planejada

**Implementar buscarConjugePessoa() no PessoaService:**

O método deve:
1. Buscar relacionamento em `relacionamentos_familiares` onde:
   - `idPessoaOrigem = $pessoaId`
   - `tipoRelacionamento = 'Cônjuge'`
2. Se encontrar, buscar entidade `Pessoas` do `idPessoaDestino`
3. Recuperar TODOS os dados múltiplos do cônjuge:
   - Telefones (`buscarTelefonesPessoa($conjugeId)`)
   - Endereços (`buscarEnderecosPessoa($conjugeId)`)
   - Emails (`buscarEmailsPessoa($conjugeId)`)
   - Documentos (`buscarDocumentosPessoa($conjugeId)`)
   - Chaves PIX (`buscarChavesPixPessoa($conjugeId)`)
   - Profissões (`buscarProfissoesPessoa($conjugeId)`)
4. Retornar array completo ou `null`

**Validações necessárias:**
- Relacionamento bidirecional está correto (A→B E B→A)
- Remoção de cônjuge exclui AMBOS os registros
- Não existem cônjuges órfãos (relacionamento em apenas uma direção)

---

## 🐛 Issues Conhecidos

### Issue #1: Cônjuge não carrega na busca
- **Severidade:** MÉDIA
- **Status:** Planejado
- **Descrição:** `searchPessoaAdvanced` retorna `'conjuge' => null`
- **Causa:** Método `buscarConjugePessoa()` não implementado
- **Solução:** Implementar conforme descrito em "Próxima Tarefa Planejada"

---

## 📖 Glossário Técnico

| Termo | Definição |
|-------|-----------|
| **Thin Controller** | Controller que apenas delega para Services, sem lógica de negócio. Responsabilidades: receber Request, validar formulário, chamar Service, retornar Response. |
| **Fat Service** | Service que contém toda a lógica de negócio, validações complexas, gerenciamento de transações e operações de persistência. |
| **Tipos de Pessoa** | Papéis que uma pessoa pode assumir simultaneamente (Fiador, Locador, Contratante, Corretor, Corretora, Pretendente). Uma pessoa pode ter múltiplos tipos ativos. |
| **Dados Múltiplos** | Entidades relacionadas a uma pessoa que podem ter múltiplos registros: Telefones, Endereços, Emails, Documentos, Chaves PIX, Profissões. |
| **tiposDados** | Objeto JSON contendo dados específicos salvos para cada tipo de pessoa. Estrutura: `{"contratante": {"id": 1}, "fiador": {"id": 2, "valor_patrimonio": 500000}}` |
| **Sub-formulário** | Formulário dinâmico carregado via AJAX para cada tipo de pessoa, contendo campos específicos. Carregado pela rota `app_pessoa__subform`. |
| **Campo de Sistema** | Campos de banco de dados que NÃO devem aparecer em formulários HTML: `id`, `created_at`, `updated_at`, chaves estrangeiras, etc. |
| **Relacionamento Bidirecional** | Relacionamento que existe nas duas direções na tabela `relacionamentos_familiares`. Ex: se A é cônjuge de B, deve existir registro de A→B e de B→A. |

---

## �� Aprendizados Recentes

### 1. Assinaturas de Função
Sempre verificar quantos parâmetros uma função espera antes de chamá-la. Uma função que espera 2 parâmetros (`tipos`, `tiposDados`) não pode ser chamada com apenas 1.

### 2. Campos de Sistema vs. Campos de Formulário
Ao iterar objetos vindos do backend, sempre filtrar campos de banco (`id`, `created_at`, `updated_at`, etc.) que não existem no formulário HTML.

**Lista de campos a ignorar:**
```javascript
const camposIgnorados = [
    'id', 
    'created_at', 
    'updated_at', 
    'createdAt', 
    'updatedAt', 
    'pessoa_id', 
    'pessoaId'
];
```

### 3. Logs são Essenciais
Sempre usar logs detalhados no JavaScript:
```javascript
console.log('✅ Sucesso:', dados);
console.warn('⚠️ Aviso:', mensagem);
console.error('❌ Erro:', erro);
```

### 4. Separação de Responsabilidades
- `new.js` → Responsável por chamar funções de carregamento
- `pessoa_tipos.js` → Responsável por criar cards e preencher dados

### 5. Sempre Testar com Dados Reais
Testes com dados mockados não revelam todos os problemas. Sempre validar com dados reais do banco.

---

## 📚 Documentação e Histórico

### CHANGELOG.md - FONTE DA VERDADE PARA MUDANÇAS

**⚠️ REGRA OBRIGATÓRIA PARA CLAUDE CODE (todos os modelos: Sonnet, Opus, Haiku):**

1. **CHANGELOG.md é o ÚNICO arquivo para registrar mudanças**
2. **NUNCA crie arquivos `.md` extras** (como `CORRECAO_*.md`, `MIGRATION_*.md`, etc.)
3. **SEMPRE atualize o CHANGELOG.md IMEDIATAMENTE** após qualquer mudança no código
4. **Formato obrigatório:** Siga o padrão [Keep a Changelog](https://keepachangelog.com/pt-BR/1.0.0/)

**Estrutura de versionamento:**
- **MAJOR** (X.0.0): Mudanças incompatíveis na API
- **MINOR** (x.Y.0): Novas funcionalidades compatíveis
- **PATCH** (x.y.Z): Correções de bugs compatíveis

**Categorias de mudanças:**
- **Adicionado** - novas funcionalidades
- **Alterado** - mudanças em funcionalidades existentes
- **Descontinuado** - funcionalidades a serem removidas
- **Removido** - funcionalidades removidas
- **Corrigido** - correção de bugs
- **Segurança** - vulnerabilidades corrigidas

**O que SEMPRE incluir:**
- Data no formato YYYY-MM-DD
- Descrição clara e concisa
- Arquivos afetados (com números de linha quando relevante)
- Motivação (quando relevante)
- Links para issues/PRs quando aplicável

**Exemplo de entrada no CHANGELOG.md:**
```markdown
## [6.6.4] - 2025-11-27

### Corrigido
- **CRÍTICO:** Descrição do problema
  - **Sintoma:** O que acontecia
  - **Causa raiz:** Por que acontecia
  - **Solução implementada:** Como foi resolvido
  - **Arquivos modificados:**
    - `src/Controller/PessoaController.php` (linhas 123-145)
    - `assets/js/pessoa/pessoa_form.js` (linha 67)
```

### Diário de Bordo (Referência Histórica)

**Para histórico completo de versões anteriores, consulte:**

`/workspaces/AlmasaStudio/diarioAlmasaEm16112025_pdf.pdf`

O diário contém:
- Histórico completo de todas as versões (V6.0 - V6.4)
- Bugs resolvidos com análise detalhada
- Decisões de arquitetura
- Code reviews
- Lições aprendidas

**⚠️ IMPORTANTE:** O diário em PDF é apenas referência histórica. **TODAS as novas mudanças devem ser registradas APENAS no CHANGELOG.md**

---

## ⚡ Início Rápido
```bash
# 1. Clone o repositório
git clone <repo-url>
cd AlmasaStudio

# 2. Instale dependências
composer install
npm install

# 3. Configure o banco
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# 4. Build assets
npm run dev

# 5. Inicie servidor
symfony server:start

# 6. Acesse
# http://localhost:8000
```

---

## 🔒 Segurança

### Flash Messages
```php
$this->addFlash('success', 'Operação realizada com sucesso');
$this->addFlash('error', 'Erro ao processar requisição');
```

### CSRF em Formulários
```twig
{{ form_start(form) }}
    {# Token CSRF incluído automaticamente #}
    {{ form_widget(form) }}
{{ form_end(form) }}
```

---

**FIM DO CLAUDE.MD**

Última atualização: 16/11/2025  
Mantenedor: Marcio Martins  
Desenvolvedor Ativo: Claude 4.5 Sonnet (via Claude Code)
