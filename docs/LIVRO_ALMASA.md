# LIVRO ALMASA — Fonte Unica da Verdade

> **Projeto AlmasaStudio** — Sistema completo de gestao imobiliaria
> Symfony 7.2 | PHP 8.2+ | PostgreSQL 14+ | Bootstrap 5.3

---

## SINOPSE

| Campo | Valor |
|-------|-------|
| **Versao Atual** | 6.31.1 |
| **Data Ultima Atualizacao** | 2026-04-14 (Follow-up de code review no saldo anterior do plano de contas) |
| **Status Geral** | Em producao — saldo anterior do plano de contas sincroniza lancamento, vinculo bancario e formatacao monetaria BR com consistencia. |
| **URL Produção** | https://www.liviago.com.br/almasa |
| **Deploy** | VPS Contabo 154.53.51.119, Nginx subfolder /almasa |
| **Banco de Dados** | PostgreSQL 16 local na VPS (almasa_prod). Neon Cloud ABANDONADO. 85 tabelas, ~630k registros. |
| **Desenvolvedor Ativo** | Claude Code + Qwen3.5 27b (Qwen Code) |
| **Mantenedor** | Marcio Martins |
| **Proxima Tarefa** | Testes E2E completos — validar autocompletes, relatorios, lancamentos |
| **Issue Aberta** | — |
| **Migracao MySQL->PostgreSQL** | v6.0 — 21 fases + hotfix completo. 506.704 lancamentos, 42.844 repasses, 4.933 pessoas, 3.236 imoveis. FK integrity 0 erros. |
| **Repo Migracao** | https://github.com/marciomartins83/almasa-migration (privado, separado) |
| **Repo Principal** | https://github.com/marciomartins83/AlmasaStudio |

---

## INDICE

- [Cap 1 — Historico e Evolucao](#cap-1--historico-e-evolucao)
- [Cap 2 — Arquitetura Tecnica](#cap-2--arquitetura-tecnica)
- [Cap 3 — Mapa de Arquivos](#cap-3--mapa-de-arquivos)
- [Cap 4 — Modulo Pessoas](#cap-4--modulo-pessoas)
- [Cap 5 — Modulo Imoveis](#cap-5--modulo-imoveis)
- [Cap 6 — Modulo Contratos](#cap-6--modulo-contratos)
- [Cap 7 — Modulo Financeiro](#cap-7--modulo-financeiro)
- [Cap 8 — Modulo Boletos e Cobranca](#cap-8--modulo-boletos-e-cobranca)
- [Cap 9 — Modulo Relatorios e Prestacao de Contas](#cap-9--modulo-relatorios-e-prestacao-de-contas)
- [Cap 10 — Cadastros Auxiliares e Configuracoes](#cap-10--cadastros-auxiliares-e-configuracoes)
- [Cap 11 — Banco de Dados](#cap-11--banco-de-dados)
- [Cap 12 — Frontend](#cap-12--frontend)
- [Cap 13 — Licoes Aprendidas](#cap-13--licoes-aprendidas)
- [Cap 14 — Plano de Testes](#cap-14--plano-de-testes)
- [Changelog](#changelog)

---

## Cap 1 — Historico e Evolucao

### Linha do Tempo

| Versao | Data | Marco |
|--------|------|-------|
| 5.0.0 | 2025-11-05 | Implementacao inicial — Modulo Pessoas, 13 entidades, PostgreSQL + Webpack Encore + Bootstrap 5 |
| 6.0.0 | 2025-11-06 | Busca inteligente, sistema de tipos multiplos, FormTypes por tipo |
| 6.1.0 | 2025-11-07 | Rotas DELETE para dados multiplos, token CSRF `ajax_global` |
| 6.2.0 | 2025-11-08 | Modulos JS para conjuge, pessoa_modals.js |
| 6.3.0 | 2025-11-09 | PessoaService (Fat Service), PessoaController refatorado (Thin Controller) |
| 6.4.0 | 2025-11-16 | Correcao carregamento de tipos, CLAUDE.md criado |
| 6.5.x | 2025-11-16 | buscarConjugePessoa(), melhorias na listagem, CSRF |
| 6.6.x | 2025-11-24–30 | Modulo Imoveis completo, correcoes criticas |
| 6.7.0 | 2025-12-01 | Informe de Rendimentos / DIMOB |
| 6.7.1 | 2025-12-04 | Tipos Socio e Advogado |
| 6.8.0 | 2025-12-05 | Contratos de Locacao |
| 6.9.0 | 2025-12-05 | Ficha Financeira / Contas a Receber |
| 6.10.0 | 2025-12-07 | Configuracao API Bancaria |
| 6.11.0 | 2025-12-07 | Integracao API Santander — Auth + Services |
| 6.11.1 | 2025-12-07 | Regra 0 Schema Doctrine, sincronizacao completa |
| 6.12.0 | 2025-12-07 | CRUD Boletos Bancarios |
| 6.13.0 | 2025-12-07 | Cobranca Automatica de Contratos |
| 6.14.0 | 2025-12-07 | Lancamentos (Contas a Pagar/Receber) |
| 6.15.0 | 2025-12-08 | Prestacao de Contas aos Proprietarios |
| 6.16.0 | 2025-12-08 | Modulo Relatorios PDF (6 relatorios com preview AJAX) |
| 6.17.0 | 2026-02-19 | Deploy producao em liviago.com.br/almasa |
| 6.17.1 | 2026-02-20 | Correcao assets producao, login, dados teste |
| 6.18.0 | 2026-02-20 | Paginacao em 29 CRUDs, CRUD Bancos, correcoes templates |
| 6.19.0 | 2026-02-21 | Migracao MySQL->PostgreSQL v2 — 702k registros, 100% sucesso, 0 erros |
| 6.19.3 | 2026-02-21 | Fix: AssetMapper compile no VPS, remocao .md proibidos |
| 6.19.4 | 2026-02-21 | Fix: Tipo Inquilino faltando — findTiposComDados agora le de pessoas_tipos |
| 6.19.5 | 2026-02-21 | Fix: Enderecos proprios de 42 inquilinos migrados |
| 6.19.6 | 2026-02-21 | Fix: 2.088 inquilinos recebem endereco do imovel locado, script Phase 13 completo |
| 6.20.1 | 2026-02-22 | Fix: Code review 16 modulos — templates corrompidos, entities datetime, FormTypes constraints, inline JS removido, 4 repositories criados |
| 6.20.2 | 2026-02-22 | Fix: Thin Controller (10/14), Issue #1 Conjugue resolvida, banco Neon limpo (64 registros teste) |
| 6.20.3 | 2026-02-22 | Refactor: Remove CRUD orfao PessoaFiador, Thin Controller Corretor/Locador, banco migrado Neon→PostgreSQL local VPS |
| 6.24.x | 2026-03-04 | Card Lancamentos, tema escuro global, mascaras CPF/RG/CNPJ, filtro Twig mask_documento |
| 6.25.0 | 2026-03-04 | Tema escuro completo — 40+ componentes CSS com dark mode, dashboard e base.html.twig adaptados |
| 6.25.1 | 2026-03-04 | Code review completo — 17 Thin Controllers refatorados, inline JS corrigido, schema validado |
| 6.26.x | 2026-03-04–05 | Migracao completa v6 — 506.704 lancamentos, 42.844 repasses, FK integrity 0 erros |
| 6.27.0 | 2026-03-08 | Lancamentos: EntityType selects convertidos para autocomplete AJAX |
| 6.28.0 | 2026-03-10 | Modulo Almasa Plano de Contas v2, Vinculos Bancarios, Partidas Dobradas |
| 6.29.0 | 2026-04-07 | Autocomplete global (17 endpoints), PaginationRedirectTrait, 30+ selects convertidos |
| 6.30.0 | 2026-04-09 | Saldo anterior/atual em relatorios financeiros, lancamento automatico de saldo anterior no plano de contas |
| 6.31.0 | 2026-04-14 | Correcao sincronizacao saldo anterior plano de contas (DataTransformer + transacoes) — Qwen3.5 27b |
| 6.31.1 | 2026-04-14 | Follow-up de code review: desvinculo bancario correto e normalizacao monetaria BR robusta |

### Migracoes Criticas (Referencia Historica)

- **User -> Users:** Corrigida inconsistencia entre entity singular e tabela plural
- **Pessoa -> Pessoas:** Corrigidas referencias em 15 arquivos principais
- **isThemeLight():** Implementado controle de tema em entity Pessoas

### Diario de Bordo Historico

Para historico completo das versoes V6.0–V6.4, consulte:
`/workspaces/AlmasaStudio/diarioAlmasaEm16112025_pdf.pdf`

---

## Cap 2 — Arquitetura Tecnica

### Stack Completa

| Camada | Tecnologia |
|--------|------------|
| **PHP** | 8.2+ |
| **Framework** | Symfony 7.2 (CLI 5.15.1) |
| **ORM** | Doctrine 2 |
| **Banco** | PostgreSQL 14+ |
| **Templates** | Twig 3 |
| **CSS** | Bootstrap 5.3 |
| **JavaScript** | Vanilla JS (ES6) — Modular |
| **Build** | Webpack Encore (entries gerais) + Symfony AssetMapper (JS de pessoa) |
| **Componentes** | Hotwired Stimulus, Hotwired Turbo |
| **CSRF** | Token unico global `ajax_global` |
| **Auth** | Symfony Security Bundle |
| **PDF** | DomPDF |
| **Email** | Symfony Mailer |

### Fluxo Operacional do Assistente de IA

**Papel do Assistente (Opus/Sonnet):**
- **Arquiteto/Planejador:** Define arquitetura, planeja tarefas, coordena implementação
- **Revisor de Código:** Valida qualidade, identifica problemas, solicita correções
- **Coordenador:** Delega implementação ao subagente `@agent/kimi`

**Restrições Obrigatórias:**
| Restrição | Descrição |
|-----------|-----------|
| **SEM deploy** | O assistente NÃO realiza deploy em nenhum ambiente (local ou VPS) |
| **SEM código de produção** | O assistente NÃO escreve código diretamente nos arquivos do projeto |
| **Planejamento em .md** | Tarefas são planejadas e documentadas em arquivos markdown específicos |

**Ciclo de Trabalho:**
1. Assistente analisa necessidade e planeja tarefa em arquivo `.md`
2. Subagente `@agent/kimi` implementa o código conforme instruções do `.md`
3. Assistente realiza **code review** da implementação
4. **Se houver problemas:** assistente envia prompt corretivo ao `@agent/kimi` com descrição clara dos ajustes necessários
5. **Se estiver válido:** assistente cria próxima tarefa ou finaliza o ciclo

**Arquitetura de Responsabilidades:**
```
┌─────────────────────────────────────────────┐
│  ASSISTENTE (Opus/Sonnet)                   │
│  ├── Planejamento arquitetural              │
│  ├── Criação de arquivos .md com tarefas    │
│  ├── Code review (validação de qualidade)   │
│  ├── Prompts corretivos (quando necessário) │
│  └── Coordenação do fluxo                   │
├─────────────────────────────────────────────┤
│  SUBAGENTE (@agent/kimi)                    │
│  └── Implementação de código (PHP/JS/Twig)  │
└─────────────────────────────────────────────┘
```

> **Nota:** Este fluxo garante separação de responsabilidades — o assistente atua como "cérebro" (planejamento e revisão) enquanto o subagente executa o trabalho bruto de codificação.

### Padrao Arquitetural: Thin Controller / Fat Service

**Controllers** (`src/Controller/`):
- Recebem `Request`, validam formulario, chamam Service, retornam Response
- PROIBIDO: logica de negocio, transacoes, `flush()`, `persist()`, `remove()`

**Services** (`src/Service/`):
- Toda logica de negocio, validacoes complexas
- Gerenciamento de transacoes (`beginTransaction`, `commit`, `rollBack`)
- Operacoes de persistencia (`persist`, `remove`, `flush`)

**Repositorios** (`src/Repository/`):
- Consultas DQL/SQL complexas
- SEMPRE colocar DQL em Repository, NUNCA em Controller ou Service

### PaginationRedirectTrait (v6.29.0)

Trait reutilizavel que preserva pagina, ordenacao e filtros apos editar/excluir em qualquer CRUD. Salva a URL atual na session e redireciona de volta apos a operacao, evitando que o usuario perca a posicao na listagem. Aplicado em 25 Controllers.

**Uso:** Controller usa `$this->savePaginationUrl()` antes de processar e `$this->redirectToPagination()` apos salvar/excluir.

### Padroes de Codigo

- **Clean Code** — nomes descritivos, metodos pequenos e focados
- **SOLID** — especialmente Single Responsibility
- **DRY** — evitar duplicacao
- **Type Hints** — sempre declarar tipos de parametros e retorno
- **DocBlocks** — documentar metodos complexos

### Rotas Padrao

- **DELETE:** Padrao `/pessoa/{entidade}/{id}` usando metodo HTTP DELETE
- **Resposta JSON:** Sempre `{'success': true}` ou `{'success': false, 'message': '...'}`
- **SEMPRE incluir `id` no JSON** de entidades que podem ser deletadas

### Integracoes

| Integracao | Status | Detalhes |
|------------|--------|----------|
| API Santander (Boletos) | Implementado | Registro, consulta, baixa via OAuth 2.0 + mTLS |
| Busca CEP (ViaCEP) | Implementado | Via API externa |
| Email (Symfony Mailer) | Implementado | Configuravel no .env |

### Resumo Executivo

| Categoria | Quantidade |
|-----------|------------|
| Controllers | 43 |
| Entities | 82 |
| Services | 15 |
| Repositories | 55 |
| Templates | 151 |
| Commands | 2 |
| Rotas | ~200 |

---

## Cap 3 — Mapa de Arquivos

### Backend

```
src/
├── Controller/
│   ├── PessoaController.php
│   ├── ImovelController.php
│   ├── ContratoController.php
│   ├── FichaFinanceiraController.php
│   ├── LancamentosController.php
│   ├── BoletoController.php
│   ├── CobrancaController.php
│   ├── PrestacaoContasController.php
│   ├── RelatorioController.php
│   ├── InformeRendimentoController.php
│   ├── ConfiguracaoApiBancoController.php
│   ├── AutocompleteController.php       (17 endpoints de busca AJAX)
│   └── ... (cadastros auxiliares)
│
├── Service/
│   ├── PessoaService.php          (76.300 bytes)
│   ├── BoletoSantanderService.php (37.052 bytes)
│   ├── FichaFinanceiraService.php (25.534 bytes)
│   ├── ContratoService.php        (23.131 bytes)
│   ├── CobrancaContratoService.php(20.423 bytes)
│   ├── ImovelService.php          (18.977 bytes)
│   ├── InformeRendimentoService.php(18.412 bytes)
│   ├── LancamentosService.php     (550+ linhas)
│   ├── PrestacaoContasService.php (600+ linhas)
│   ├── RelatorioService.php       (800+ linhas)
│   ├── SantanderAuthService.php   (12.739 bytes)
│   ├── EmailService.php           (11.663 bytes)
│   ├── ConfiguracaoApiBancoService.php (9.350 bytes)
│   ├── CepService.php             (3.714 bytes)
│   └── ... (servicos auxiliares)
│
├── Entity/        (82 entidades)
├── Repository/    (51 repositorios)
├── Form/          (FormTypes)
└── Command/
    ├── CreateAdminCommand.php
    └── EnviarBoletosAutomaticoCommand.php
```

### Frontend

```
assets/
├── app.js                          # Entry point principal
├── js/
│   ├── pessoa/                     # 16 modulos JS
│   │   ├── pessoa.js               # Utilitarios, setFormValue
│   │   ├── new.js                  # Busca inteligente
│   │   ├── pessoa_tipos.js         # Gerenciamento de tipos
│   │   ├── pessoa_enderecos.js     # DELETE enderecos
│   │   ├── pessoa_telefones.js     # DELETE telefones
│   │   ├── pessoa_emails.js        # DELETE emails
│   │   ├── pessoa_chave_pix.js     # DELETE chaves PIX
│   │   ├── pessoa_documentos.js    # DELETE documentos
│   │   ├── pessoa_profissoes.js    # DELETE profissoes
│   │   ├── pessoa_conjuge.js       # salvarConjuge, carregarDados
│   │   ├── pessoa_modals.js        # salvarNovoTipo (reutilizavel)
│   │   ├── conjuge_telefones.js
│   │   ├── conjuge_enderecos.js
│   │   ├── conjuge_emails.js
│   │   ├── conjuge_documentos.js
│   │   ├── conjuge_chave_pix.js
│   │   └── conjuge_profissoes.js
│   ├── financeiro/                 # Ficha financeira
│   ├── lancamentos/                # Contas a pagar/receber
│   ├── boleto/                     # Boletos
│   ├── cobranca/                   # Cobranca automatica
│   ├── relatorios/                 # Relatorios PDF
│   ├── prestacao_contas/           # Prestacao de contas
│   ├── informe_rendimento/         # DIMOB
│   ├── configuracao_api_banco/     # Config API
│   ├── crud/                       # Filtros e confirm delete
│   └── generic_autocomplete.js     # Autocomplete reutilizavel (typeahead, keyboard nav, debounce)
```

### Templates

```
templates/
├── base.html.twig
├── _partials/
│   ├── breadcrumb.html.twig
│   ├── autocomplete_field.html.twig  # Partial generico para campos autocomplete
│   ├── search_panel.html.twig
│   ├── sort_panel.html.twig
│   └── pagination.html.twig
├── pessoa/
│   ├── index.html.twig
│   ├── pessoa_form.html.twig
│   ├── show.html.twig
│   └── partials/ (9 subformularios)
├── imovel/
├── contrato/
├── financeiro/
├── lancamentos/
├── boleto/
├── cobranca/
├── prestacao_contas/
├── relatorios/
│   ├── index.html.twig (dashboard)
│   ├── 6 filtros + 6 previews + 8 PDFs
│   └── pdf/ (_header, _footer, 6 templates)
├── informe_rendimento/
└── ... (cadastros auxiliares)
```

### Webpack Entries

| Entry | Caminho | Funcao |
|-------|---------|--------|
| app | assets/app.js | Principal |
| informe_rendimento | assets/js/informe_rendimento/*.js | DIMOB |
| financeiro | assets/js/financeiro/*.js | Ficha financeira |
| financeiro_form | assets/js/financeiro/*.js | Form financeiro |
| configuracao_api_banco | assets/js/configuracao_api_banco/*.js | Config API |
| boleto | assets/js/boleto/*.js | Listagem boletos |
| boleto_form | assets/js/boleto/*.js | Form boletos |
| cobranca | assets/js/cobranca/*.js | Cobranca automatica |
| lancamentos | assets/js/lancamentos/*.js | Lancamentos |
| prestacao_contas | assets/js/prestacao_contas/*.js | Prestacao contas |
| relatorios | assets/js/relatorios/*.js | Relatorios PDF |

**JS via Symfony AssetMapper (versionados com hash):**

| Pasta Fonte | Arquivos | Funcao |
|-------------|----------|--------|
| assets/js/pessoa/ | 19 arquivos (pessoa_form.js, pessoa_tipos.js, etc.) | Formulario completo de Pessoa (CRUD + conjuge) |

> **ATENCAO:** Os JS de pessoa passam pelo **Symfony AssetMapper**. O template usa `{{ asset('js/pessoa/...') }}` que gera URLs versionadas tipo `/almasa/assets/js/pessoa/pessoa_form-3XJfXni.js`. Apos qualquer alteracao em `assets/js/pessoa/`, executar no VPS:
> ```bash
> php bin/console asset-map:compile --env=prod
> php bin/console cache:clear --env=prod
> ```

---

## Cap 4 — Modulo Pessoas

**Status:** Completo

### Entidade Central: `Pessoas`

Uma pessoa pode ter multiplos tipos/papeis simultaneamente:
- **Contratante** (`PessoasContratantes`)
- **Fiador** (`PessoasFiadores`)
- **Locador** (`PessoasLocadores`)
- **Corretor** (`PessoasCorretores`)
- **Corretora** (`PessoasCorretoras` — pessoa juridica)
- **Pretendente** (`PessoasPretendentes`)
- **Advogado** (`PessoasAdvogados`)
- **Socio** (`PessoasSocios`)
- **Inquilino** (tipo_pessoa=12, sem tabela de dados especificos)

**Tabela `pessoas_tipos`:** Registra QUAIS papeis cada pessoa tem (id_pessoa, id_tipo_pessoa, data_inicio, ativo). A UI le dessa tabela para exibir a secao "Tipos de Pessoa". Cada papel tambem tem uma tabela de dados especificos (ex: `pessoas_locadores` com 19 campos financeiros).

**Fluxo:** `PessoaRepository::findTiposComDados()` busca diretamente das tabelas de dados (PessoasLocadores, PessoasFiadores, etc.) e retorna booleanos + objetos. A tabela `pessoas_tipos` e usada como registro auxiliar.

#### Codigo Legado (campo `cod`)

Todas as entidades de pessoas mantem o campo `cod` que preserva o identificador legado do sistema MySQL original:

| Entidade | Campo `cod` | Origem (MySQL) |
|----------|-------------|----------------|
| `Pessoas` | `cod` | `locpessoas.codigo` |
| `PessoasLocadores` | `cod` | `loclocadores.codigo` |
| `PessoasLocadores` | `flg_proprietario` | Verificacao em `locimovelprop.proprietario` |
| `PessoasFiadores` | `cod` | `locfiadores.codigo` |
| `PessoasContratantes` | `cod` | `loccontratantes.codigo` |
| `PessoasTipos` | `cod` | Mesmo valor da tabela de origem |

O campo `flg_proprietario` (booleano, default `false`) indica se o locador e proprietario de algum imovel cadastrado. Na migracao, este campo e determinado verificando se o codigo do locador existe na tabela `locimovelprop.proprietario`.

### Entities (14)

Pessoas, PessoasFiadores, PessoasLocadores, PessoasContratantes, PessoasCorretores, PessoasCorretoras, PessoasPretendentes, PessoasAdvogados, PessoasSocios, PessoasDocumentos, PessoasTelefones, PessoasEmails, PessoasProfissoes, RelacionamentosFamiliares

### Controller e Rotas

**Controller:** `PessoaController.php` (33.930 bytes)

| Metodo | Rota | Descricao |
|--------|------|-----------|
| GET | /pessoa/ | Listagem |
| GET/POST | /pessoa/new | Novo cadastro |
| GET/POST | /pessoa/{id}/edit | Edicao |
| GET | /pessoa/{id} | Visualizacao |
| POST | /pessoa/{id} | Exclusao |
| POST | /pessoa/_subform | Carrega subformulario de tipo |
| POST | /pessoa/search-pessoa-advanced | Busca avancada |
| DELETE | /pessoa/endereco/{id} | Remove endereco |
| DELETE | /pessoa/telefone/{id} | Remove telefone |
| DELETE | /pessoa/email/{id} | Remove email |
| DELETE | /pessoa/chave-pix/{id} | Remove PIX |
| DELETE | /pessoa/documento/{id} | Remove documento |
| DELETE | /pessoa/profissao/{id} | Remove profissao |
| DELETE | /pessoa/conta-bancaria/{id} | Remove conta |

### Service

**PessoaService.php** (76.300 bytes) — Fat Service com toda logica de negocio

Metodos principais:
- `findByCpfDocumento()` — Busca pessoa por CPF
- `findCnpjDocumento()` — Busca pessoa por CNPJ
- `buscarConjugePessoa()` — Busca dados completos do conjuge
- Validacao de duplicidade antes de salvar

### Sub-formularios Dinamicos

Selecao de tipo carrega via AJAX um partial `.twig` especifico:
- Rota: `app_pessoa__subform`
- FormType dedicado para cada tipo (ex: `PessoaFiadorType`)

### Dados Multiplos

Uma pessoa pode ter multiplos: Telefones, Enderecos, Emails, Documentos (CPF, CNPJ, RG, etc.), Chaves PIX, Profissoes

### Arquitetura de Conjuge

- A coluna `conjuge_id` existe na tabela `pessoas`, mas NAO e a fonte da verdade
- **Fonte oficial:** Tabela `relacionamentos_familiares` (tipoRelacionamento = 'Conjuge')
- Permite historico, dados contextuais (regime de casamento, datas), e relacionamento bidirecional
- **Conjuges de fiadores:** Tabela `pessoas_fiadores.id_conjuge` aponta para uma `Pessoa` independente (tipo fiador)
  - 165 conjuges migrados com CPF, RG, profissao, telefone, pai/mae, nacionalidade
  - Campo `pessoas_fiadores.conjuge_trabalha` preenchido a partir dos dados do sistema antigo
- **Conjuges de inquilinos:** 11 registros criados (apenas nome disponivel em `locinquilino.nomecjg`)
  - Referencia salva em `pessoas.observacoes` do inquilino principal

### Busca Avancada de Pessoa

A busca avancada (`searchPessoaAdvanced`) retorna campo `cod` legado alem dos dados padroes.

### Issue Resolvida

**#1: Conjuge nao carrega na busca** — RESOLVIDA (v6.20.2)
- `buscarConjugePessoa()` implementado completo, retorna dados via `relacionamentos_familiares`
- Campo `cod` visivel em edicao e visualizacao de Pessoas (v6.23.3)
- Filtro `cod` na tela de pessoas usa input type="number" (v6.21.4)

---

## Cap 5 — Modulo Imoveis

**Status:** Completo

### Controller e Rotas

**Controller:** `ImovelController.php`

| Metodo | Rota | Descricao |
|--------|------|-----------|
| GET | /imovel/ | Listagem |
| GET/POST | /imovel/new | Novo |
| GET/POST | /imovel/edit/{id} | Edicao |
| GET | /imovel/buscar | Busca |
| DELETE | /imovel/foto/{id} | Remove foto |
| DELETE | /imovel/medidor/{id} | Remove medidor |
| DELETE | /imovel/propriedade/{idImovel}/{idPropriedade} | Remove propriedade |
| GET | /imovel/propriedades/catalogo | Catalogo de propriedades |

### Entities (6)

- `Imoveis.php` (30.523 bytes — 63 campos)
- `ImoveisContratos.php` — Contratos
- `ImoveisFotos.php` — Fotos
- `ImoveisGarantias.php` — Garantias
- `ImoveisMedidores.php` — Medidores
- `ImoveisPropriedades.php` — Propriedades

### Service

`ImovelService.php` (18.977 bytes)

---

## Cap 6 — Modulo Contratos

**Status:** Completo

### Controller e Rotas

**Controller:** `ContratoController.php`

| Metodo | Rota | Descricao |
|--------|------|-----------|
| GET | /contrato/ | Listagem |
| GET | /contrato/show/{id} | Detalhes |
| GET/POST | /contrato/new | Novo |
| GET/POST | /contrato/edit/{id} | Edicao |
| POST | /contrato/encerrar/{id} | Encerrar |
| POST | /contrato/renovar/{id} | Renovar |
| GET | /contrato/vencimento-proximo | Vencimento proximo |
| GET | /contrato/para-reajuste | Para reajuste |
| GET | /contrato/estatisticas | Estatisticas |
| GET | /contrato/imoveis-disponiveis | Imoveis disponiveis |

### Entities

- `ImoveisContratos.php` (14.631 bytes) — 11 campos extras (taxa admin, garantia, reajuste, etc.)
- `ContratosCobrancas.php` — Cobrancas
- `ContratosItensCobranca.php` — Itens de cobranca

### Service

`ContratoService.php` (23.131 bytes) — CRUD, renovacao, encerramento

---

## Cap 7 — Modulo Financeiro

### 7.1 Ficha Financeira / Contas a Receber

**Status:** Completo

**Controller:** `FichaFinanceiraController.php`

| Metodo | Rota | Descricao |
|--------|------|-----------|
| GET | /financeiro/ | Listagem geral |
| GET | /financeiro/ficha/{inquilinoId} | Ficha do inquilino |
| GET/POST | /financeiro/lancamento/new | Novo lancamento |
| GET/POST | /financeiro/lancamento/{id}/edit | Editar |
| GET | /financeiro/lancamento/{id} | Detalhes |
| POST | /financeiro/lancamento/{id}/baixa | Baixa |
| POST | /financeiro/baixa/{id}/estornar | Estornar |
| POST | /financeiro/lancamento/{id}/cancelar | Cancelar |
| POST | /financeiro/gerar-lancamentos | Gerar automatico |
| GET | /financeiro/em-atraso | Inadimplentes |
| GET | /financeiro/api/* | APIs JSON (4 rotas) |

**Entities:** LancamentosFinanceiros (22.697 bytes), BaixasFinanceiras, AcordosFinanceiros

**Service:** `FichaFinanceiraService.php` (25.534 bytes) — 14 metodos de gestao financeira

**Campos financeiros migrados (lancamentos_financeiros):**
- `id_proprietario`: Derivado via contrato→imovel→proprietario (367.686 registros preenchidos)
- `valor_principal`: Aluguel puro (dump.valor - verbas separadas). Corrigido em 80.009 recibos para 100% consistencia
- `valor_condominio`, `valor_iptu`, `valor_agua`, `valor_luz`, `valor_gas`, `valor_outros`: Verbas individuais do `locrechist`
- `valor_total`: Recalculado como soma de todas verbas + multa + juros
- Verificacao 100%: 80.009/80.009 recibos batem campo a campo com dump MySQL (valor, valor_pago, situacao)

**Cadeia financeira completa:**
- Inquilino paga → `lancamentos_financeiros` com `id_inquilino` + `id_proprietario` + `id_imovel` + `id_contrato`
- Proprietario recebe → `prestacoes_contas` com `id_proprietario` + `id_imovel` (14.882 com imovel inferido)
- 139.018 lancamentos sem proprietario sao despesas/receitas administrativas da imobiliaria (correto)

### 7.2 Lancamentos (Contas a Pagar/Receber)

**Status:** Completo (v6.14.0)

**Controller:** `LancamentosController.php` (370+ linhas) — 12 rotas

| Rota | Metodo | Descricao |
|------|--------|-----------|
| /lancamentos/ | GET | Listagem com filtros |
| /lancamentos/new | GET/POST | Novo lancamento |
| /lancamentos/{id}/edit | GET/POST | Editar |
| /lancamentos/{id} | DELETE | Excluir |
| /lancamentos/{id}/baixa | POST | Realizar baixa |
| /lancamentos/{id}/estornar | POST | Estornar |
| /lancamentos/{id}/cancelar | POST | Cancelar |
| /lancamentos/{id}/suspender | POST | Suspender |
| /lancamentos/vencidos | GET | Lista vencidos |
| /lancamentos/estatisticas | GET | Dashboard |
| /lancamentos/api/* | GET | APIs JSON (2 rotas) |

**Entity:** `Lancamentos.php` (860+ linhas) — Constantes para tipos (PAGAR/RECEBER), status, origens

**Service:** `LancamentosService.php` (550+ linhas) — CRUD, baixa (total/parcial), estorno, calculo retencoes INSS/ISS

**Formulario (LancamentosType):**
- Campos com autocomplete AJAX (HiddenType unmapped): pessoaCredorId, pessoaPagadorId, planoContaDebito, planoContaCredito, planoContaId, contratoId, imovelId, contaBancariaId
- Usa `generic_autocomplete.js` para contrato, imovel e planoConta (legado)
- Autocomplete customizado para pessoa e plano de contas Almasa (debito/credito)
- Endpoints: `/autocomplete/contratos`, `/autocomplete/imoveis`, `/autocomplete/plano-contas`

**Regras de Negocio:**
- Numero sequencial automatico por tipo
- Competencia default = mes do vencimento
- Status automatico baseado em valor_pago vs valor_liquido
- Nao permite editar/cancelar lancamentos pagos
- Valor liquido = valor - desconto + juros + multa - INSS - ISS
- Contas a pagar nao exigem banco na criacao — so na baixa
- Baixa de lancamento: opcoes Dinheiro, Cheque, Debito em Conta, Transferencia (removidos Credito e Boleto em v6.29.0)

**Coluna "Historico" e Flag Contas Proprias (v6.29.0):**
- Index de lancamentos exibe coluna "Historico"
- Formulario inclui flag "Incluir contas de proprietarios" na aba Vinculos
- Filtro `?apenas=almasa` no endpoint de contas bancarias para mostrar so contas da Almasa

### 7.3 Informe de Rendimentos / DIMOB

**Status:** Completo (v6.7.0)

**Controller:** `InformeRendimentoController.php` — 8 rotas

**Entities:** InformesRendimentos, InformesRendimentosValores, DimobConfiguracoes

**Service:** `InformeRendimentoService.php` (18.412 bytes) — processamento, impressao, geracao DIMOB

---

## Cap 8 — Modulo Boletos e Cobranca

### 8.1 CRUD de Boletos (API Santander)

**Status:** Completo (v6.12.0)

**Controller:** `BoletoController.php` (400 linhas) — 12 rotas

| Metodo | Rota | Descricao |
|--------|------|-----------|
| GET | /boleto/ | Listagem |
| GET | /boleto/{id} | Detalhes |
| GET/POST | /boleto/new | Novo |
| POST | /boleto/{id}/registrar | Registrar via API |
| POST | /boleto/{id}/consultar | Consultar status |
| POST | /boleto/{id}/baixar | Baixar/cancelar |
| DELETE | /boleto/{id} | Excluir |
| GET | /boleto/{id}/imprimir | Impressao |
| GET | /boleto/{id}/segunda-via | Segunda via |
| POST | /boleto/registrar-lote | Registrar lote |
| POST | /boleto/consultar-lote | Consultar lote |
| GET | /boleto/api/estatisticas | Estatisticas |

**Entities:** Boletos (20.407 bytes), BoletosLogApi

**Services:**
- `BoletoSantanderService.php` (37.052 bytes) — geracao, registro, consulta, baixa
- `SantanderAuthService.php` (12.739 bytes) — OAuth 2.0 com mTLS

### 8.2 Cobranca Automatica

**Status:** Completo (v6.13.0)

**Controller:** `CobrancaController.php` (355 linhas) — 8 rotas AJAX

**Entities:** ContratosCobrancas, ContratosItensCobranca, EmailsEnviados

**Services:**
- `CobrancaContratoService.php` (20.423 bytes)
- `EmailService.php` (11.663 bytes)

**Command:** `app:enviar-boletos-automatico` (cron job diario)

**Regras de Negocio:**
- Competencia definida pelo periodo de locacao
- Impossivel duplicar cobrancas (constraint unica)
- Override manual bloqueia envio automatico
- Boletos gerados X dias antes do vencimento (configuravel)

### 8.3 Configuracao API Bancaria

**Status:** Completo (v6.10.0)

**Controller:** `ConfiguracaoApiBancoController.php` — 6 rotas

**Entities:** ConfiguracoesApiBanco, ConfiguracoesCobranca

**Service:** `ConfiguracaoApiBancoService.php` — upload seguro, validacao OpenSSL, certificados A1

---

## Cap 9 — Modulo Relatorios e Prestacao de Contas

### 9.1 Relatorios PDF

**Status:** Completo e corrigido (v6.20.4)

6 relatorios com preview AJAX e geracao PDF via DomPDF:

1. **Inadimplentes** — Lista de inquilinos em atraso com calculo de juros/multa
2. **Despesas** — Contas a pagar com agrupamento e totalizadores
3. **Receitas** — Contas a receber com agrupamento e totalizadores
4. **Despesas x Receitas** — Comparativo com saldo do periodo
5. **Contas Bancarias** — Extrato com saldos e movimentacoes por conta
6. **Plano de Contas** — Cadastro de contas contabeis

**Controller:** `RelatorioController.php` (~490 linhas) — 19 rotas (dashboard + 3 por relatorio)

**Service:** `RelatorioService.php` (~1150 linhas) — Fat Service com toda logica

**Templates:** 6 filtros + 6 previews + 8 PDFs + dashboard

#### Licao Critica (v6.20.4) — Tabela correta para cada relatorio

O sistema possui DUAS tabelas financeiras distintas. Confundir as duas causa retorno vazio:

| Tabela | Registros | Uso correto |
|--------|-----------|-------------|
| `lancamentos` | 0 (vazia — legado) | NAO usar |
| `lancamentos_financeiros` | 506k+ registros | SEMPRE usar |

**Todos os metodos do RelatorioService DEVEM usar `LancamentosFinanceiros::class`.**

#### Mapeamento de campos LancamentosFinanceiros

| Conceito | Campo/Metodo |
|----------|-------------|
| Tipo de movimento | `getTipoLancamento()` → 'receita' / 'aluguel' / 'despesa' |
| Entrada (receber) | `tipoLancamento IN ('receita', 'aluguel')` |
| Saida (pagar) | `tipoLancamento = 'despesa'` |
| Data | `getDataVencimento()` (nao existe dataPagamento) |
| Pago/em aberto | `getSituacao()` → 'pago' / 'aberto' / 'cancelado' |
| Vinculo conta bancaria | `getContaBancaria()` — coluna `id_conta_bancaria` |
| Valor | `getValorTotal()` |
| Historico | `getHistorico() ?? getDescricao()` |
| Documento | `getNumeroBoleto() ?? getNumeroRecibo()` |

#### Metodos do Service (resumo)

| Metodo | Fonte | Retorno |
|--------|-------|---------|
| `getInadimplentes()` | LancamentosFinanceiros | array entidades (eager load, max 500) |
| `getDespesas()` | LancamentosFinanceiros tipo=despesa | array normalizado (max 500) |
| `getTotalDespesas()` | SQL nativo LF | array totais sem limite |
| `getReceitas()` | LancamentosFinanceiros tipo IN(receita,aluguel) | array normalizado (max 500) |
| `getTotalReceitas()` | SQL nativo LF | array totais sem limite |
| `getMovimentosContaBancaria()` | LancamentosFinanceiros conta IS NOT NULL | array normalizado |
| `getSaldoInicialConta()` | SQL nativo LF | float |
| `getResumoContas()` | Usa getMovimentosContaBancaria() | array agrupado por conta |

#### Preview AJAX — limite de 500 registros

`getDespesas()`, `getReceitas()` e `getInadimplentes()` usam `setMaxResults(500)`.
Totais (`getTotalDespesas()`, `getTotalReceitas()`) usam SQL nativo sem limite para garantir exatidao.

#### Autocomplete nos Filtros de Relatorios (v6.29.0)

Todos os selects nos templates de relatorios foram convertidos para autocomplete AJAX:
proprietarios, plano de contas, contas bancarias, inquilinos, pagadores, fornecedores, imoveis, locatarios, fiadores.

#### Relatorios Almasa — Tabela Correta (v6.29.0)

Relatorios Almasa de despesas/receitas agora buscam na tabela `lancamentos` (tipo=pagar/receber) em vez de `almasa_lancamentos`. Relatorios historicos continuam usando `lancamentos_financeiros`.

#### Saldo Anterior e Saldo Atual (v6.30.0)

Todos os relatorios financeiros Almasa (despesas, receitas, despesas x receitas) exibem:

- **Saldo Anterior** no topo do relatorio — calculado pelo metodo `AlmasaRelatorioService::calcularSaldoAnterior($filtros)`. SQL: `SUM(receber) - SUM(pagar)` para todos os lancamentos com data anterior a `data_inicio`. Respeita os filtros `tipo_data` (competencia/vencimento/pagamento), `status` e `id_plano_conta`.
- **Saldo Atual** no rodape — `saldo_anterior +/- movimentacao_periodo`:
  - Despesas: `saldo_anterior - total_despesas`
  - Receitas: `saldo_anterior + total_receitas`
  - Despesas x Receitas: `saldo_anterior + saldo_periodo`
- Implementado em preview AJAX e PDF (DomPDF) — chave `saldo_anterior is defined` no Twig.

### 9.2 Prestacao de Contas aos Proprietarios

**Status:** Completo (v6.15.0)

**Controller:** `PrestacaoContasController.php` (350+ linhas) — 13 rotas

| Metodo | Rota | Descricao |
|--------|------|-----------|
| GET | /prestacao-contas/ | Dashboard |
| GET/POST | /prestacao-contas/gerar | Gerar |
| POST | /prestacao-contas/preview | Preview |
| GET | /prestacao-contas/{id} | Visualizar |
| GET | /prestacao-contas/{id}/pdf | PDF |
| POST | /prestacao-contas/{id}/aprovar | Aprovar |
| GET/POST | /prestacao-contas/{id}/repasse | Repasse |
| POST | /prestacao-contas/{id}/cancelar | Cancelar |
| DELETE | /prestacao-contas/{id} | Excluir |
| GET | /prestacao-contas/historico/{id} | Historico |
| GET | /prestacao-contas/imoveis/{id} | Imoveis |
| POST | /prestacao-contas/calcular-periodo | Calcular periodo |

**Entities:** PrestacoesContas (580+ linhas), PrestacoesContasItens (300+ linhas)

**Service:** `PrestacaoContasService.php` (600+ linhas) — geracao, preview, aprovacao, repasse, cancelamento

---

## Cap 10 — Cadastros Auxiliares e Configuracoes

### Modulos CRUD Auxiliares

Todos com status Completo:

| Modulo | Controller |
|--------|------------|
| Estados | EstadoController |
| Cidades | CidadeController |
| Bairros | BairroController |
| Logradouros | LogradouroController |
| Agencias | AgenciaController |
| Contas Bancarias | ContaBancariaController |
| Emails | EmailController |
| Telefones | TelefoneController |
| Nacionalidades | NacionalidadeController |
| Naturalidades | NaturalidadeController |
| Estado Civil | EstadoCivilController |
| Tipo Documento | TipoDocumentoController |
| Tipo Telefone | TipoTelefoneController |
| Tipo Email | TipoEmailController |
| Tipo Endereco | TipoEnderecoController |
| Tipo Imovel | TipoImovelController |
| Tipo Pessoa | TipoPessoaController |
| Tipo Chave PIX | TipoChavePixController |
| Tipo Conta Bancaria | TipoContaBancariaController |
| Tipo Carteira | TipoCarteiraController |
| Tipo Remessa | TipoRemessaController |
| Tipo Atendimento | TipoAtendimentoController |

### Contas Bancarias — Detalhes (v6.29.0)

- Formulario inclui campos `descricao` e `titular`
- Busca por `codigo`, `descricao` e `titular` (OR) no filtro de titular
- Filtro `?apenas=almasa` no autocomplete para retornar so contas da Almasa (sem contas de proprietarios)
- Campos boolean (`principal`, `ativo`, `registrada`, `aceitaMultipag`, `usaEnderecoCobranca`, `cobrancaCompartilhada`) possuem default `false` na entity para evitar not-null violation
- 12 contas proprias da Almasa receberam titular "Almasa Administradora"

### Plano de Contas Almasa — Saldo Anterior (v6.31.1)

- Campo `saldoAnterior` (decimal 15,2, default 0.00) na entity `AlmasaPlanoContas` — saldo inicial da conta contabil
- Ao **criar** uma conta com saldo > 0, `AlmasaPlanoContasService::criarLancamentoSaldoAnterior()` gera lancamento tipo `receber` ja pago, vinculado via `planoContaCredito`, com historico `Saldo anterior — {codigo} {descricao}`
- Ao **editar** o saldo, `atualizarLancamentoSaldoAnterior()` mantem o lancamento sincronizado:
  - Saldo zerou e existia lancamento → remove
  - Saldo > 0 e nao existia → cria
  - Saldo > 0 e ja existia → atualiza valor, historico e conta bancaria vinculada
- Se o vinculo bancario padrao for removido, o lancamento de saldo anterior limpa `contaBancaria` para nao manter referencia orfa
- Lancamento existente e localizado por `planoContaCredito = conta AND historico LIKE 'Saldo anterior%'`
- `AlmasaPlanoContasType` usa DataTransformer para exibir `saldoAnterior` sempre com 2 casas no formato BR e aceitar entrada com milhar (`1.234,56`) sem corromper o valor salvo
- `AlmasaPlanoContasService::atualizar()` abre transacao; os `flush()` internos do sincronismo sao intencionais e permanecem na mesma transacao Doctrine

### Commands Disponiveis

| Command | Descricao | Status |
|---------|-----------|--------|
| `app:create-admin` | Cria usuario administrador | Ativo |
| `app:enviar-boletos-automatico` | Envio automatico de boletos | Ativo (cron desativado) |

**Cron configurado:** `cron/cobranca_automatica.cron`

### Arquivos de Configuracao

| Arquivo | Funcao |
|---------|--------|
| `.env` | Variaveis de ambiente |
| `config/services.yaml` | Configuracao de servicos |
| `webpack.config.js` | Build de assets (12 entries) |
| `CLAUDE.md` | Regras para Claude Code |
| `docs/LIVRO_ALMASA.md` | Fonte unica da verdade |
| `cron/cobranca_automatica.cron` | Configuracao do cron |

### O Que Nao Existe (ainda)

| Funcionalidade | Status |
|----------------|--------|
| Modulo Juridico (processos, follow-up) | Nao implementado |
| Modulo de Seguros | Nao implementado |
| Modulo de Manutencao/Obras | Nao implementado |
| Modulo de Vistorias | Nao implementado |
| App Mobile | Nao implementado |
| Dashboard com graficos | Parcial (basico) |
| Notificacoes Push | Nao implementado |
| Integracao WhatsApp | Nao implementado |

---

## Cap 11 — Banco de Dados

### Entidades por Categoria

**Pessoas (14 entities):**
Pessoas, PessoasFiadores, PessoasLocadores, PessoasContratantes, PessoasCorretores, PessoasCorretoras, PessoasPretendentes, PessoasAdvogados, PessoasSocios, PessoasDocumentos, PessoasTelefones, PessoasEmails, PessoasProfissoes, RelacionamentosFamiliares

**Imoveis (6 entities):**
Imoveis, ImoveisContratos, ImoveisFotos, ImoveisGarantias, ImoveisMedidores, ImoveisPropriedades

**Financeiro (8 entities):**
LancamentosFinanceiros, BaixasFinanceiras, AcordosFinanceiros, Boletos, BoletosLogApi, ContratosCobrancas, ContratosItensCobranca, EmailsEnviados

**Plano de Contas Almasa (3 entities) — NOVO v6.28.0:**
AlmasaPlanoContas, AlmasaVinculoBancario, AlmasaLancamentos

**Configuracoes (4 entities):**
ConfiguracoesApiBanco, ConfiguracoesCobranca, DimobConfiguracoes, PlanoContas

**Enderecos (5 entities):**
Estados, Cidades, Bairros, Logradouros, Enderecos

**Bancos (4 entities):**
Bancos, Agencias, ContasBancarias, ContasVinculadas

**Tipos (14 entities):**
TiposDocumentos, TiposTelefones, TiposEmails, TiposEnderecos, TiposImoveis, TiposPessoas, TiposChavesPix, TiposContasBancarias, TiposCarteiras, TiposRemessa, TiposAtendimento, EstadoCivil, Nacionalidade, Naturalidade

**Outros (27 entities):**
Users, Roles, Permissions, Sessions, Telefones, Emails, ChavesPix, Profissoes, Condominios, FiadoresInquilinos, FormasRetirada, InformesRendimentos, InformesRendimentosValores, Lancamentos, LayoutsRemessa, PropriedadesCatalogo, RazoesConta, RegimesCasamento, RequisicoesResponsaveis, FailedJobs, PersonalAccessTokens, PasswordResetTokens, ModelHasPermissions, ModelHasRoles, RoleHasPermissions, PrestacoesContas, PrestacoesContasItens

### Tabelas de Dados Multiplos

| Tabela | Coluna ID | Chave Estrangeira | Observacao |
|--------|-----------|-------------------|------------|
| `enderecos` | `id` | `pessoa_id -> pessoas.id` | Direto |
| `telefones` | `id` | `pessoas_telefones.telefone_id` | Tabela pivot |
| `emails` | `id` | `pessoas_emails.email_id` | Tabela pivot |
| `chaves_pix` | `id` | `id_pessoa -> pessoas.id` | Direto |
| `pessoas_documentos` | `id` | `id_pessoa -> pessoas.id` | Direto |
| `pessoas_profissoes` | `id` | `id_pessoa -> pessoas.id` | Direto |
| `relacionamentos_familiares` | `id` | `idPessoaOrigem / idPessoaDestino -> pessoas.id` | Fonte da verdade para Conjuge |

### Estatísticas do Banco (88 tabelas — atualizado 2026-03-14)

| Tabela | Registros | Módulo |
|--------|-----------|--------|
| `lancamentos_financeiros` | 503.704 | Financeiro (migrado) |
| `bairros` | 51.998 | Endereços |
| `prestacoes_contas` | 42.844 | Financeiro |
| `pessoas_tipos` | 9.787 | Pessoas |
| `cidades` | 9.674 | Endereços |
| `enderecos` | 8.323 | Endereços |
| `pessoas_telefones` | 7.412 | Pessoas |
| `telefones` | 7.291 | Pessoas |
| `pessoas_documentos` | 5.409 | Pessoas |
| `pessoas` | 4.795 | Pessoas |
| `imoveis` | 3.236 | Imóveis |
| `logradouros` | 2.737 | Endereços |
| `pessoas_locadores` | 2.498 | Pessoas |
| `pessoas_profissoes` | 2.320 | Pessoas |
| `imoveis_contratos` | 2.130 | Contratos |
| `almasa_plano_contas` | 2.103 | Plano de Contas Almasa |
| `pessoas_emails` | 1.971 | Pessoas |
| `emails` | 1.961 | Pessoas |
| `profissoes` | 1.459 | Pessoas |
| `contas_bancarias` | 390 | Bancos |
| `pessoas_fiadores` | 288 | Pessoas |
| `plano_contas` | 159 | Financeiro (tipos lançamento) |
| `doctrine_migration_versions` | 46 | Sistema |
| `nacionalidades` | 38 | Pessoas |
| `estados` | 31 | Endereços |
| `chaves_pix` | 22 | Pessoas |
| `agencias` | 16 | Bancos |
| `bancos` | 15 | Bancos |
| `tipos_pessoas` | 12 | Tipos |
| `estado_civil` | 11 | Tipos |
| Demais 58 tabelas | 0–7 | Tipos, Config, Auth, etc. |

> **Total geral:** ~648.000 registros em 88 tabelas.

### Hierarquia de Módulos (Diagrama de FK)

```
pessoas (4.795)
├── enderecos (8.323) → logradouros (2.737) → bairros (51.998) → cidades (9.674) → estados (31)
├── pessoas_documentos (5.409)
├── pessoas_telefones (7.412) → telefones (7.291)
├── pessoas_emails (1.971) → emails (1.961)
├── pessoas_locadores (2.498)
├── pessoas_fiadores (288)
├── pessoas_contratantes (3)
├── pessoas_profissoes (2.320) → profissoes (1.459)
├── chaves_pix (22)
└── relacionamentos_familiares (cônjuges)

imoveis (3.236)
├── enderecos (compartilhado com pessoas)
├── imoveis_contratos (2.130) → pessoas (locatário, fiador)
│   └── contratos_cobrancas → boletos
├── imoveis_fotos, imoveis_garantias, imoveis_medidores
└── imoveis_propriedades

lancamentos_financeiros (503.704) — HISTÓRICO MIGRADO
├── FK: id_contrato → imoveis_contratos
├── FK: id_imovel → imoveis
├── FK: id_inquilino → pessoas
├── FK: id_proprietario → pessoas
└── FK: id_conta_bancaria → contas_bancarias

lancamentos (0 — CRUD novo, em uso a partir de mar/2026)
├── FK: id_plano_conta → plano_contas
├── FK: id_plano_conta_debito → almasa_plano_contas
├── FK: id_plano_conta_credito → almasa_plano_contas
├── FK: id_imovel → imoveis
├── FK: id_proprietario, id_inquilino → pessoas
└── FK: id_conta_bancaria → contas_bancarias

almasa_plano_contas (2.103)
├── FK: id_pai → almasa_plano_contas (auto-referência hierárquica)
└── almasa_vinculos_bancarios → contas_bancarias
```

### Estrutura das Tabelas Principais

**Tabela `pessoas` (17 colunas)**

| Coluna | Tipo | NULL | Descrição |
|--------|------|------|-----------|
| `idpessoa` | integer PK | NO | Auto-incremento |
| `nome` | varchar(255) | NO | Nome completo ou razão social |
| `dt_cadastro` | timestamp | NO | Data de cadastro |
| `tipo_pessoa` | integer | NO | FK → tipos_pessoas |
| `status` | boolean | NO | Ativo/Inativo |
| `fisica_juridica` | varchar(255) | NO | `F` = Física, `J` = Jurídica |
| `data_nascimento` | date | YES | — |
| `nome_pai` | varchar(255) | YES | — |
| `nome_mae` | varchar(255) | YES | — |
| `renda` | numeric | YES | — |
| `observacoes` | text | YES | — |
| `theme_light` | boolean | NO | Preferência de tema (default true) |
| `user_id` | integer | YES | FK → users |
| `estado_civil_id` | integer | YES | FK → estado_civil |
| `nacionalidade_id` | integer | YES | FK → nacionalidades |
| `naturalidade_id` | integer | YES | FK → naturalidades |
| `cod` | integer | YES | Código legado migração MySQL |

**Tabela `lancamentos_financeiros` (47 colunas) — HISTÓRICO MIGRADO**

| Coluna | Tipo | NULL | Descrição |
|--------|------|------|-----------|
| `id` | integer PK | NO | Auto-incremento |
| `id_contrato` | integer | YES | FK → imoveis_contratos |
| `id_imovel` | integer | YES | FK → imoveis |
| `id_inquilino` | integer | YES | FK → pessoas |
| `id_proprietario` | integer | YES | FK → pessoas |
| `id_conta` | integer | YES | FK → plano_contas (tipo lançamento) |
| `id_conta_bancaria` | integer | YES | FK → contas_bancarias |
| `competencia` | date | NO | Mês/ano de referência |
| `data_lancamento` | date | NO | Data do lançamento |
| `data_vencimento` | date | NO | Data de vencimento |
| `data_limite` | date | YES | Data limite para pagamento |
| `valor_principal` | numeric | NO | Valor do aluguel (default 0) |
| `valor_condominio` | numeric | YES | — |
| `valor_iptu` | numeric | YES | — |
| `valor_agua` | numeric | YES | — |
| `valor_luz` | numeric | YES | — |
| `valor_gas` | numeric | YES | — |
| `valor_outros` | numeric | YES | — |
| `valor_multa` | numeric | YES | — |
| `valor_juros` | numeric | YES | — |
| `valor_honorarios` | numeric | YES | — |
| `valor_desconto` | numeric | YES | — |
| `valor_bonificacao` | numeric | YES | — |
| `valor_total` | numeric | NO | Soma de todos os valores (default 0) |
| `valor_pago` | numeric | YES | — |
| `valor_saldo` | numeric | YES | Saldo devedor |
| `situacao` | varchar(20) | YES | `pago`, `aberto`, etc. |
| `tipo_lancamento` | varchar(30) | YES | `receita`, `aluguel`, `despesa` |
| `origem` | varchar(30) | YES | Origem do lançamento |
| `descricao` | text | YES | — |
| `historico` | text | YES | — |
| `observacoes` | text | YES | — |
| `gerado_automaticamente` | boolean | YES | Flag de geração automática |
| `ativo` | boolean | YES | — |
| + 13 colunas de auditoria/controle | — | — | created_at, updated_at, created_by, etc. |

**Tabela `lancamentos` (43 colunas) — CRUD NOVO (produção mar/2026+)**

| Coluna | Tipo | NULL | Descrição |
|--------|------|------|-----------|
| `id` | integer PK | NO | Auto-incremento |
| `data_movimento` | date | NO | Data do movimento contábil |
| `data_vencimento` | date | NO | Data de vencimento |
| `data_pagamento` | date | YES | Data efetiva de pagamento |
| `id_plano_conta` | integer | YES | FK → plano_contas (tipo legado) |
| `id_plano_conta_debito` | integer | YES | FK → almasa_plano_contas (partida dobrada) |
| `id_plano_conta_credito` | integer | YES | FK → almasa_plano_contas (partida dobrada) |
| `id_imovel` | integer | YES | FK → imoveis |
| `id_proprietario` | integer | YES | FK → pessoas |
| `id_inquilino` | integer | YES | FK → pessoas |
| `id_pessoa_credor` | integer | YES | FK → pessoas |
| `id_pessoa_pagador` | integer | YES | FK → pessoas |
| `id_contrato` | integer | YES | FK → imoveis_contratos |
| `id_conta_bancaria` | integer | YES | FK → contas_bancarias |
| `id_boleto` | integer | YES | FK → boletos |
| `valor` | numeric | NO | Valor do lançamento |
| `tipo` | varchar(10) | NO | `receber` ou `pagar` |
| `status` | varchar(15) | YES | `aberto`, `pago`, `cancelado`, `pago_parcial` |
| `historico` | varchar(255) | YES | Descrição do lançamento |
| `competencia` | varchar(7) | YES | Formato `YYYY-MM` |
| `centro_custo` | varchar(20) | YES | — |
| `forma_pagamento` | varchar(20) | YES | — |
| `valor_pago`, `valor_desconto`, `valor_juros`, `valor_multa` | numeric | YES | Valores de baixa |
| `reter_inss`, `perc_inss`, `valor_inss` | — | YES | Retenção INSS |
| `reter_iss`, `perc_iss`, `valor_iss` | — | YES | Retenção ISS |
| + 6 colunas de auditoria/controle | — | — | created_at, updated_at, etc. |

> **ATENÇÃO:** `lancamentos_financeiros` usa campos granulares (valor_principal, valor_condominio, etc.) e `tipo_lancamento IN ('receita', 'aluguel', 'despesa')`. A tabela `lancamentos` usa campo único `valor` e `tipo IN ('receber', 'pagar')`. Os Services usam tabelas diferentes: `RelatorioService` → `LancamentosFinanceiros`, `LancamentosController` → `Lancamentos`.

**Tabela `imoveis` (50 colunas)**

| Coluna | Tipo | NULL | Descrição |
|--------|------|------|-----------|
| `id` | integer PK | NO | Auto-incremento |
| `codigo_interno` | varchar(20) | YES | Formato `IM0005` |
| `id_tipo_imovel` | integer | NO | FK → tipos_imoveis |
| `id_endereco` | integer | NO | FK → enderecos |
| `id_condominio` | integer | YES | FK → condominios |
| `id_pessoa_proprietario` | integer | NO | FK → pessoas |
| `id_pessoa_fiador` | integer | YES | FK → pessoas |
| `id_pessoa_corretor` | integer | YES | FK → pessoas |
| `situacao` | varchar(20) | NO | Status do imóvel |
| `valor_aluguel` | numeric | YES | Valor mensal |
| `valor_condominio` | numeric | YES | — |
| `valor_iptu_mensal` | numeric | YES | — |
| `taxa_administracao` | numeric | YES | % de administração |
| `dia_vencimento` | integer | YES | Dia do mês |
| + 36 colunas de característica e controle | — | — | Áreas, quartos, publicação, etc. |

**Tabela `imoveis_contratos` (25 colunas)**

| Coluna | Tipo | NULL | Descrição |
|--------|------|------|-----------|
| `id` | integer PK | NO | Auto-incremento |
| `id_imovel` | integer | NO | FK → imoveis |
| `id_pessoa_locatario` | integer | YES | FK → pessoas (inquilino) |
| `id_pessoa_fiador` | integer | YES | FK → pessoas |
| `tipo_contrato` | varchar(20) | NO | Tipo (locação, etc.) |
| `data_inicio` | date | NO | Início da vigência |
| `data_fim` | date | YES | Fim da vigência |
| `valor_contrato` | numeric | NO | Valor mensal |
| `dia_vencimento` | integer | YES | Dia do mês |
| `status` | varchar(20) | NO | `ativo`, `encerrado`, etc. |
| `taxa_administracao` | numeric | YES | % (default 10) |
| `tipo_garantia` | varchar(30) | YES | `fiador`, `caucao`, `seguro` |
| `indice_reajuste` | varchar(20) | YES | `IGPM`, `IPCA`, etc. |
| `periodicidade_reajuste` | varchar(20) | YES | `anual`, etc. |
| `data_proximo_reajuste` | date | YES | — |
| `ativo` | boolean | YES | Default true |
| + 9 colunas de configuração | — | — | Multa, carência, boleto, email, etc. |

**Tabela `almasa_plano_contas` (11 colunas) — NOVO v6.28.0, saldo_anterior em v6.30.0**

| Coluna | Tipo | NULL | Descrição |
|--------|------|------|-----------|
| `id` | integer PK | NO | Auto-incremento |
| `id_pai` | integer | YES | FK → almasa_plano_contas (hierarquia) |
| `codigo` | varchar(20) | NO | Ex: `1`, `1.1`, `1.1.01`, `1.1.01.001` |
| `descricao` | varchar(255) | NO | Nome da conta |
| `tipo` | varchar(25) | NO | `ativo`, `passivo`, `receita`, `despesa`, `patrimonio` |
| `nivel` | smallint | NO | 1 a 5 |
| `aceita_lancamentos` | boolean | NO | Só contas analíticas (nível 4-5) |
| `ativo` | boolean | NO | — |
| `saldo_anterior` | numeric(15,2) | NO | Default 0.00 — saldo inicial; gera/atualiza lancamento automatico via Service |
| `created_at` | timestamp | NO | — |
| `updated_at` | timestamp | NO | — |

**Tabela `contas_bancarias` (25 colunas)**

| Coluna | Tipo | NULL | Descrição |
|--------|------|------|-----------|
| `id` | integer PK | NO | Auto-incremento |
| `id_pessoa` | integer | YES | FK → pessoas (titular) |
| `id_banco` | integer | YES | FK → bancos |
| `id_agencia` | integer | YES | FK → agencias |
| `codigo` | varchar(255) | NO | Número da conta |
| `digito_conta` | varchar(255) | YES | — |
| `titular` | varchar(255) | YES | Nome do titular |
| `principal` | boolean | NO | Conta principal |
| `ativo` | boolean | NO | — |
| + 16 colunas de configuração bancária | — | — | Carteira, remessa, convênio, etc. |

### Tipos de Lançamentos (tabela `plano_contas` — 159 registros)

Mapeamento completo dos tipos usados em `lancamentos_financeiros.id_conta`:

- **tipo=0** → Receita (42 entradas, códigos 1001–1049)
- **tipo=1** → Despesa (109 entradas, códigos 2001–2110)
- **tipo=2** → Repasse/Controle (8 entradas, códigos 5001–5025)

**Receitas (tipo=0, códigos 1001–1049):**

| ID | Código | Descrição |
|----|--------|-----------|
| 1313 | 1001 | Aluguel |
| 1314 | 1002 | Desconto |
| 1315 | 1003 | Multa |
| 1316 | 1004 | I.R. |
| 1317 | 1005 | Seg. Fiança |
| 1318 | 1006 | Condomínio |
| 1319 | 1007 | CPMF |
| 1320 | 1008 | IPTU |
| 1321 | 1009 | Acordo |
| 1344 | 1010 | Honorários de Administração |
| 1345 | 1011 | Taxa de Locação |
| 1346 | 1012 | Multa Sobre Aluguéis |
| 1347 | 1013 | Receitas Diversas |
| 1353 | 1021 | Taxa de Envio |
| 1354 | 1022 | Reembolso |
| 1356 | 1023 | Saldo Anterior (c) |
| 1358 | 1024 | Transferência (c) |
| 1360 | 1025 | Movimentação Contas Bancária (c) |
| 1362 | 1026 | Comissões Recebidas |
| 1365 | 1027 | Depósito efetuado |
| 1366 | 1028 | Conta de Luz |
| 1367 | 1029 | Conta de Água |
| 1369 | 1030 | Tx. Administração (Almasa) |
| 1376 | 1031 | Custas Processuais |
| 1379 | 1032 | Conta de Gás |
| 1380 | 1033 | Internet - Assinatura |
| 1383 | 1034 | Água, Luz, IPTU e Cond |
| 1387 | 1035 | Honorários - Jurídico |
| 1390 | 1036 | Seguro do Imóvel |
| 1394 | 1037 | Acerto de Saldo (c) |
| 1398 | 1038 | Pró-Labore |
| 1399 | 1039 | Multa Contratual |
| 1402 | 1040 | Lucro Apurado |
| 1404 | 1041 | Juros |
| 1406 | 1042 | Rateio Extra |
| 1409 | 1043 | Fdo Reserva |
| 1410 | 1044 | Vaga Extra |
| 1412 | 1045 | Cartório / Registro |
| 1420 | 1046 | Transfer. Divisão entre os filhos |
| 1430 | 1047 | Documentação Imobiliária (C) |
| 1456 | 1048 | Distribuição de Lucros (C) |
| 1466 | 1049 | Levantamento de ficha |

**Despesas (tipo=1, códigos 2001–2110) — 109 entradas:**

| ID | Código | Descrição |
|----|--------|-----------|
| 1322 | 2001 | Taxa de Administração |
| 1323 | 2002 | IPTU |
| 1324 | 2003 | Depósito Efetuado |
| 1325 | 2004 | CPMF |
| 1326 | 2005 | Encargos Sociais |
| 1334 | 2006 | Combustível |
| 1335 | 2007 | Prestador de Serviço |
| 1336 | 2008 | Taxas Bancárias - Boletos |
| 1337 | 2009 | Material de Escritório |
| 1338 | 2010 | Material de Cozinha/Limpeza |
| 1339 | 2011 | Despesas Gerais |
| 1340 | 2012 | Publicidade |
| 1341 | 2013 | Manutenção Veículo |
| 1342 | 2014 | Seguro |
| 1343 | 2015 | Reforma de Imóvel |
| 1348 | 2016 | Sindicatos / Conselhos |
| 1349 | 2017 | Cartão de Crédito |
| 1350 | 2018 | Faculdade |
| 1351 | 2019 | Manutenção Prédio |
| 1352 | 2020 | Máquinas / Equipamentos |
| 1330 | 2021 | Reembolso |
| 1331 | 2022 | Salários |
| 1332 | 2023 | Manutenção Jardim |
| 1333 | 2024 | Pró-Labore |
| 1355 | 2025 | Imposto de Renda |
| 1357 | 2026 | Saldo anterior (d) |
| 1359 | 2027 | Transferência (d) |
| 1361 | 2028 | Movimentação Contas Bancárias (d) |
| 1363 | 2029 | Comissões Pagas |
| 1364 | 2030 | Retirada dos Sócios |
| 1370 | 2031 | Custas Judiciais |
| 1371 | 2032 | Condomínio |
| 1372 | 2033 | Conta de Luz |
| 1373 | 2034 | Conta de Água |
| 1374 | 2035 | Conta de Telefone / Celular |
| 1375 | 2036 | Plano de Saúde |
| 1378 | 2038 | Honorários |
| 1381 | 2039 | Pgto. Diversos |
| 1384 | 2040 | Condução / Correio |
| 1385 | 2041 | Cartório / Reg.Imóveis |
| 1386 | 2042 | Juros |
| 1389 | 2043 | Antecipação de aluguéis |
| 1391 | 2044 | Manutenção site |
| 1392 | 2045 | Gasolina |
| 1393 | 2046 | Taxa de Locação |
| 1395 | 2047 | Acerto de Saldo (D) |
| 1396 | 2048 | Devolução Depósito |
| 1397 | 2049 | Remessa |
| 1400 | 2050 | Certidões |
| 1401 | 2051 | Tx. Administração Condomínio |
| 1403 | 2052 | Distribuição de Lucros (D) |
| 1407–1470 | 2053–2110 | Manutenção Geral, Construção (58 subcategorias: cimento, ferro, areia, pintura, etc.) |

**Repasse/Controle (tipo=2, códigos 5001–5025):**

| ID | Código | Descrição |
|----|--------|-----------|
| 1327 | 5001 | Caixa |
| 1328 | 5002 | Condomínio |
| 1329 | 5003 | Taxa Adm. |
| 1368 | 5021 | Taxa de Envio |
| 1377 | 5022 | Honorários Advogado |
| 1382 | 5023 | Seguro Fiança |
| 1388 | 5024 | Cheque Devolvido |
| 1405 | 5025 | Internet - Assinatura |

> **Nota:** IDs são legado MySQL (1313-1470). Códigos são a referência para o sistema (1001-5025). O campo `tipo` mapeia: 0=Receita, 1=Despesa, 2=Repasse.

### Duas Tabelas Financeiras — Regra de Uso

| Aspecto | `lancamentos_financeiros` | `lancamentos` |
|---------|---------------------------|---------------|
| **Registros** | 503.704 | 0 (novo) |
| **Origem** | Migração MySQL (histórico) | CRUD novo (mar/2026+) |
| **Service** | `RelatorioService` (dados granulares) | `LancamentosService` (interface) |
| **Tipo entrada** | `tipoLancamento IN ('receita','aluguel')` | `tipo = 'receber'` |
| **Tipo saída** | `tipoLancamento = 'despesa'` | `tipo = 'pagar'` |
| **Status pago** | `situacao = 'pago'` | `status IN ('pago','pago_parcial')` |
| **Data** | `dataVencimento` (sem dataPagamento) | `dataVencimento` + `dataPagamento` |
| **Valores** | Granulares (principal, condomínio, IPTU, água...) | Consolidado (`valor` único) |
| **Partida dobrada** | Não | Sim (`id_plano_conta_debito`, `id_plano_conta_credito`) |

### Validacao de Schema

```bash
# SEMPRE rodar ao iniciar qualquer tarefa
php bin/console doctrine:schema:validate

# Diagnosticar divergencias
php bin/console doctrine:schema:update --dump-sql
```

**Divergencias aceitaveis:** DROP SEQUENCE, ALTER DROP DEFAULT, ALTER INDEX RENAME, DROP/CREATE INDEX

**Divergencias NAO aceitaveis:** ALTER TYPE, ALTER SET NOT NULL, DROP/ADD COLUMN

**Regra de boolean NOT NULL (v6.29.0):** Campos boolean NOT NULL em entities Doctrine DEVEM ter valor default (`= false` ou `= true` na propriedade PHP), caso contrario INSERT falha com not-null violation no PostgreSQL.

### Script de Migracao MySQL -> PostgreSQL

**Repositório separado:** https://github.com/marciomartins83/almasa-migration (privado)

**NÃO está dentro do AlmasaStudio.** Foi movido para repo próprio em 2026-02-21.

**Versao atual:** v4.0 (2026-02-27) — 19 fases + hotfix conjuges, campos completos, re-importacao limpa

**Pré-requisitos obrigatórios:**
1. Tabelas de parâmetros populadas (tipos_pessoas, tipos_documentos, tipos_telefones, etc.)
2. Dump MySQL compacto em `bkpBancoFormatoAntigo/bkpjpw_compacto_2025.sql` (36 MB)
3. PostgreSQL acessível (local na VPS, porta 5432)

**Cadeia de dependência respeitada:**
```
Grupo 0: tipos_* (parametros puros, sem FK)
Grupo 1: estados, bancos
Grupo 2: cidades, agencias, pessoas
Grupo 3: bairros, contas_bancarias, telefones
Grupo 4: logradouros
Grupo 5: enderecos
Grupo 6: imoveis, condominios
Grupo 7: contratos
Grupo 8: lancamentos_financeiros
Grupo 9: boletos, prestacoes_contas
```

**IDs de parametrização confirmados (2026-02-21):**

| Tabela | Tipo | ID |
|--------|------|----|
| tipos_pessoas | fiador | 1 |
| tipos_pessoas | locador | 4 |
| tipos_pessoas | contratante | 6 |
| tipos_pessoas | inquilino | 12 |
| tipos_documentos | CPF | 1 |
| tipos_documentos | RG | 2 |
| tipos_documentos | CNPJ | 4 |
| tipos_telefones | Residencial | 2 |
| tipos_telefones | Comercial | 3 |
| tipos_telefones | Fax | 4 |
| tipos_telefones | Outros | 5 |
| tipos_telefones | Celular | 6 |
| tipos_enderecos | Residencial | 1 |
| tipos_emails | Pessoal | 1 |
| tipos_imoveis | Casa | 1 |

**Contagem pos-migracao v4.0 (2026-02-27):**

| Tabela | Registros | Observacao |
|--------|-----------|------------|
| pessoas | 5.098 | 2498 locadores + 288 fiadores + 3 contratantes + 2132 inquilinos + 176 conjuges + 1 admin |
| imoveis | 3.236 | |
| imoveis_contratos | 2.130 | |
| lancamentos_financeiros | 49.998 | 7573 recibos + 7572 verbas + 42425 CC + extras |
| telefones | 7.291 | Inclui fax/outros/telcom (v4 novo) |
| emails | 1.961 | |
| enderecos | 6.102 | |
| pessoas_documentos | 5.906 | CPF + RG + CNPJ |
| pessoas_profissoes | 2.320 | Inclui profissao de locadores (v4 novo) |
| contas_bancarias | 390 | So locadores tinham no legado |
| chaves_pix | 22 | |
| acordos_financeiros | 4 | |
| prestacoes_contas | 4.038 | |

**Campos adicionados na v4.0 (2026-02-27) — fixes na migracao:**

| Fase | Campo MySQL | Destino PostgreSQL | Status |
|------|-------------|-------------------|--------|
| 08 locadores | `naturalidade` | pessoas.naturalidade_id | Adicionado |
| 08 locadores | `fax`, `outros` | telefones (tipos 4, 5) | Adicionado |
| 09 fiadores | `nacion` | pessoas.nacionalidade_id | Adicionado |
| 10 contratantes | `nacion` | pessoas.nacionalidade_id | Adicionado |
| 13 inquilinos | `telcom`, `fax`, `outros` | telefones (tipos 3, 4, 5) | Adicionado |
| todas | `nascto` NULL | fallback 1900-01-01 | Adicionado |

**Campos que NAO existem no dump MySQL (limitacao do sistema legado JPW):**

| Campo | loclocadores | locfiadores | loccontratantes | locinquilino |
|-------|:-----------:|:-----------:|:---------------:|:------------:|
| Nacionalidade | NAO TEM | nacion | nacion | nacionalidade |
| Naturalidade | naturalidade | NAO TEM | NAO TEM | NAO TEM |
| Conta Bancaria | banco/agencia/conta | NAO TEM | NAO TEM | NAO TEM |
| Conjuge | NAO TEM | COMPLETO (51 cols) | NAO TEM | SO NOME (nomecjg) |

**Bugs corrigidos na v2/v3:**
- tipos_pessoas estavam INVERTIDOS (locador=1 era fiador, fiador=4 era locador)
- Todos telefones classificados como Celular (agora Residencial/Celular/Comercial)
- Cache de bairros stale causava 23 falhas de FK violation
- enderecos de imoveis apontavam para placeholder em vez do proprietario real
- `id_proprietario` era NULL em 100% dos lancamentos — corrigido via cadeia contrato→imovel→proprietario
- `valor_principal` duplicado em 62.742 recibos — corrigido: principal = dump.valor - verbas separadas
- Conjuges de fiadores/inquilinos nao eram migrados — 176 Pessoas criadas com docs e profissoes
- `pessoas_tipos` vazia — 4.921 registros inseridos
- Inquilinos sem endereco proprio (2.088) recebem endereco do imovel locado

**Bugs corrigidos na v4.0:**
- naturalidade_id nunca era preenchido para locadores — corrigido (427 registros)
- nacionalidade_id nunca era preenchido para fiadores/contratantes/inquilinos — corrigido (2.260 registros)
- Telefones fax/outros/telcom eram ignorados — agora extraidos como tipos 4/5/3
- Data nascimento NULL causava erros em validacoes — fallback 1900-01-01
- Tabelas naturalidades/nacionalidades nao tem coluna `ativo` — INSERT corrigido

**Fase 20: Sincronização de Estado Civil, Nacionalidade e Profissão (v6.23.1):**

A Fase 20 é uma fase de correção pós-importação que garante consistência dos campos `estado_civil_id`, `nacionalidade_id` e profissões para pessoas já migradas:

| Aspecto | Implementação |
|---------|---------------|
| **Fontes** | `loclocadores`, `locfiadores`, `loccontratantes`, `locinquilino` |
| **Resolução pessoa** | StateManager (namespaces existentes) → fallback `pessoas.cod` |
| **Estado Civil** | Mapeia via `cfg.ESTADO_CIVIL_MAP`, atualiza apenas se NULL, loga conflitos |
| **Nacionalidade** | Busca `UPPER(nome) = UPPER(%s)` → fallback `ILIKE` → `get_or_create` em `nacionalidades` → atualiza se NULL |
| **Profissão** | Busca `UPPER(nome) = UPPER(%s)` → fallback `ILIKE` → `get_or_create` em `profissoes` → vincula em `pessoas_profissoes` |
| **Vínculo existente** | Atualiza `empresa`/`renda` quando NULL e há valor de origem (não duplica) |
| **Idempotência** | Pode rodar múltiplas vezes sem duplicar dados nem erro |
| **Relatório** | Contadores separados: pessoas, estado civil, nacionalidades, profissões, conflitos, não encontradas |

Campos processados por fonte:
- `locfiadores`: `nacion`, `atividade`, `estadocivil`, `renda`, `empresaf`
- `loccontratantes`: `nacion`, `atividade`, `estadocivil`, `renda`
- `loclocadores`: `profissao`, `estadocivil`
- `locinquilino`: `profissao`, `estcivil`, `nacionalidade`

**Correções SQL v6.23.1:**
- `nacionalidades`: INSERT apenas com `nome` (tabela não possui `ativo`)
- `pessoas_profissoes`: INSERT sem `created_at` (coluna não existe)

**Fase 21: Auditoria e Autocorreção de Qualidade de Dados Cadastrais (v6.23.2):**

A Fase 21 é uma fase de auditoria e correção automática pós-importação que elimina a necessidade de queries SQL manuais para validação de dados. Executa em pipeline sequencial idempotente:

| Etapa | Descrição | Critério de Sucesso |
|-------|-----------|---------------------|
| **A) Higienização** | Trim em `nacionalidades.nome` e `profissoes.nome`; remove registros vazios (NULL/blank) | 0 registros inválidos |
| **B) Deduplicação Nacionalidades** | Case-insensitive: mantém menor `id`, remapeia FK em `pessoas.nacionalidade_id`, remove duplicatas | 0 duplicatas |
| **C) Deduplicação Profissões** | Case-insensitive: mantém menor `id`, remapeia FK em `pessoas_profissoes.id_profissao`, remove duplicatas | 0 duplicatas |
| **D) Deduplicação Vínculos** | Remove duplicatas em `pessoas_profissoes` por `(id_pessoa, id_profissao)` mantendo menor `id` | 0 duplicatas |
| **E) Backfill por Observações** | Para pessoas sem profissão vinculada, extrai `Profissao: ...` de `pessoas.observacoes`, cria profissão se não existir, vincula sem duplicar | % cobertura logado |
| **F) Auditoria Final** | Detecta duplicidade, inválidos, órfãos; loga métricas de cobertura por tipo de pessoa | Todos os critérios críticos = 0 |

**Critérios de Falha da Fase 21 (lança exceção, não marca concluída):**

```python
if duplicados_pessoas_profissoes > 0:        # CRÍTICO → FALHA
if nacionalidades_invalidas > 0:              # CRÍTICO → FALHA
if profissoes_invalidas > 0:                  # CRÍTICO → FALHA
if vinculos_orfaos > 0:                       # CRÍTICO → FALHA
```

Métricas de cobertura por tipo (1,2,3,4,5,6,7,8,12) são logadas informativamente sem causar falha.

**Integração com execução:**
- `--phase all`: Inclui Fase 21 automaticamente no final
- `--phase 21`: Execução individual para reparos pontuais
- `--reset-phase 21`: Re-executa auditoria após correções manuais

**Robustez:**
- Idempotente: múltiplas execuções não duplicam dados nem quebram FK
- Usa transação existente do `writer` (PostgreSQL)
- Sem SQLs destrutivos sem remapeamento prévio
- Back compatível com Fases 1-20 já executadas

**Procedimento de limpeza + reimportacao (cleanup_db.sql):**

O script `cleanup_db.sql` foi criado para limpar o banco preservando o usuario master:
1. Salva dados do admin (user + pessoa + sub-registros) em tabelas temporarias
2. TRUNCATE em todas as tabelas de dados num unico statement (evita problemas de FK ordering)
3. Restaura admin + reseta sequences
4. NAO toca em tabelas de referencia (tipos_*, estados, cidades, bairros, bancos, agencias, profissoes, etc.)

**Uso:**
```bash
cd /opt/almasa-migration   # Na VPS
python3 migrate.py --phase all      # Executa tudo (inclui validacao)
python3 migrate.py --phase 08       # So locadores
python3 migrate.py --list           # Ver status das fases
python3 migrate.py --reset-phase 08 # Desmarca fase para re-executar
python3 hotfix_conjuges.py          # Conjuges de fiadores/inquilinos
```

---

## Cap 12 — Frontend

### JavaScript 100% Modular

**PROIBIDO:**
- Codigo JavaScript inline em templates Twig
- Atributos `onclick`, `onchange`, etc.
- Tags `<script>` com codigo dentro dos arquivos `.twig`

**OBRIGATORIO:**
- Todo JavaScript em arquivos `.js` dedicados em `assets/js/`
- Organizacao modular por funcionalidade

### Tarefa Ativa — Menu Horizontal no Topo (Planejamento)

**Objetivo:**
- Manter os cards do dashboard e adicionar um menu horizontal global no topo da tela.
- Organizar navegacao por categorias, menus e submenus com estrutura intuitiva.

**Escopo da implementacao:**
- Criar partial Twig para menu superior global reutilizavel.
- Integrar partial no layout base (`templates/base.html.twig`) sem quebrar dropdown de usuario e toggle de tema.
- Adicionar suporte visual e responsivo no CSS existente (`public/css/app.css`).
- Criar JS modular dedicado para submenus no mobile/desktop (`assets/js/`), sem JS inline novo.
- Preservar funcionamento atual do dashboard por cards (nao remover cards).

**Categorias sugeridas do menu:**
- Dashboard
- Pessoas
- Cadastros
- Imobiliario
- Financeiro
- Relatorios

**Criterios de aceitacao:**
- Menu visivel e funcional em desktop e mobile.
- Submenus navegaveis por clique no mobile e hover/click no desktop.
- Links principais apontando para rotas ja existentes no projeto.
- Sem regressao no layout base, no login e nas paginas CRUD.

**UNICA EXCECAO — Passar dados do backend para frontend:**
```twig
{# No FINAL do arquivo .twig #}
<script>
    window.ROUTES = {
        subform: '{{ path("app_pessoa__subform") }}',
        delete: '{{ path("app_pessoa_delete_telefone", {id: '__ID__'}) }}'
    };
    window.FORM_IDS = {
        pessoaId: '{{ form.pessoaId.vars.id | default('') }}'
    };
</script>
```

### Token CSRF Unico

- **UM UNICO TOKEN:** `ajax_global` para TODAS as requisicoes AJAX
- Meta tag: `<meta name="csrf-token" content="{{ csrf_token('ajax_global') }}">`
- Headers obrigatorios:
```javascript
headers: {
    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content,
    'X-Requested-With': 'XMLHttpRequest',
    'Content-Type': 'application/json'
}
```

### Padrao DELETE em JS

```javascript
fetch(`/pessoa/telefone/${id}`, {
    method: 'DELETE',
    headers: {
        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content,
        'X-Requested-With': 'XMLHttpRequest'
    }
})
.then(response => response.json())
.then(data => {
    if (data.success) { /* Remove da UI */ }
});
```

### Padrao de Templates CRUD

**Estrutura obrigatoria:**
- Block: `{% block content %}` (NAO `body`)
- Breadcrumb: Incluir `_partials/breadcrumb.html.twig`
- Icones: FontAwesome (`fas fa-*`), NAO Bootstrap Icons
- Tabela: `table-striped table-hover` com `thead class="table-dark"`
- Card: `<div class="card">` (sem `shadow-sm`)
- Botao voltar: `<i class="fas fa-arrow-left"></i> Voltar`
- Botao salvar: `<i class="fas fa-check"></i> Salvar`

**Nomes em Twig — SEMPRE camelCase:**
- `{{ item.codigoInterno }}` (nao `codigo_interno`)
- `{{ item.valorVenda }}` (nao `valor_venda`)
- `{{ item.tipoEntidade.descricao }}` (para relacionamentos)
- `isAtivo()` (para booleanos)

### Sistema de Busca Avancada, Ordenacao e Paginacao (v6.20.0)

**Arquitetura:**
- `src/DTO/SearchFilterDTO.php` — Define filtros de busca (nome, label, tipo, campo DQL, operador, choices)
- `src/DTO/SortOptionDTO.php` — Define opcoes de ordenacao (campo, label, direcao padrao)
- `src/Service/PaginationService.php` — Centraliza paginacao, filtragem e ordenacao. Retrocompativel.

**Tipos de filtro suportados:** text, number, select, date, month, boolean
**Operadores:** LIKE, EXACT, GTE, LTE, IN, BOOL, MONTH_GTE, MONTH_LTE

**Partials Twig:**
- `_partials/search_panel.html.twig` — Card colapsavel no topo com filtros dinamicos
- `_partials/sort_panel.html.twig` — Barra de botoes de ordenacao ASC/DESC
- `_partials/pagination.html.twig` — Total registros, seletor por pagina, navegacao (preserva GET params)

**JavaScript:** `assets/js/crud/crud_filters.js` — Toggle collapse, Enter submete form, confirmacao de delete via `data-confirm-delete`, webpack entry `crud_filters`

**Padrao de Delete (v6.20.1):**
- PROIBIDO: `onsubmit="return confirm(...)"` ou `onclick="return confirm(...)"`
- OBRIGATORIO: atributo `data-confirm-delete` no `<form>` — handler em `crud_filters.js`
- Exemplo: `<form method="post" action="..." data-confirm-delete="Tem certeza?">`

**Layout padrao de todo CRUD index:**
```
Breadcrumb
Titulo + [+ Novo Registro]
[Cards estatisticas — se aplicavel]
Busca Avancada (card colapsavel)
Ordenar: [botoes]
Tabela (table-striped table-hover, thead table-dark)
Paginacao (total, por pagina, navegacao)
```

**CRUDs padronizados (33):** Pessoas, Imoveis, Contratos, Boletos, Lancamentos, FichaFinanceira, PrestacaoContas, ConfiguracaoApiBanco, 11x Tipo*, Estado, Cidade, Bairro, Logradouro, Banco, Agencia, ContaBancaria, EstadoCivil, Nacionalidade, Naturalidade, Email, Telefone, PessoaLocador, PessoaFiador, PessoaCorretor

**CRUDs nao aplicaveis (3):** InformeRendimento (SPA multi-tab), Cobranca (filtros custom), Profissao (controller inexistente)

### Menu de Navegacao Superior (v6.21.1)

**Arquivo:** `templates/_partials/top_navigation.html.twig`

Menu horizontal global exibido abaixo da navbar principal, com acesso rapido a todos os modulos do sistema organizados por categorias.

**Categorias e estrutura:**
1. **Dashboard** — Painel Principal
   - Submenu **Endereços**: Gerenciar, Estados, Cidades, Bairros, Logradouros
   - Submenu **Tipos**: Documento, Conta Bancária, Telefone, Email, Chave PIX, Atendimento, Carteira, Endereço, Imóvel, Pessoa, Remessa, Estado Civil, Nacionalidade, Naturalidade
2. **Cadastros** — Emails, Telefones
3. **Pessoas** — Todas as Pessoas (único item, sem CRUDs legados)
4. **Imobiliario** — Imoveis, Contratos
5. **Financeiro** — Ficha Financeira, Bancos, Agencias, Contas Bancarias, Boletos, API Bancaria, Informe de Rendimentos
6. **Relatorios** — Central de Relatorios, Inadimplentes, Despesas, Receitas, Despesas x Receitas, Contas Bancarias, Plano de Contas

**Caracteristicas tecnicas:**
- Bootstrap 5 Navbar com dropdowns
- Icones FontAwesome (`fas fa-*`)
- Dropdown headers para separar secoes (`dropdown-header`)
- Dividers entre grupos logicos (`dropdown-divider`)
- Classes customizadas prefixadas com `almasa-*` para estilizacao
- Responsivo: colapso mobile com toggle hamburger
- Apenas exibido quando usuario logado (`{% if app.user %}`)
- Suporte a temas claro/escuro via atributo `data-bs-theme`

### Tema Escuro Completo (v6.25.0)

**Mecanismo:** Bootstrap 5.3 `data-bs-theme="dark"` no `<html>`, toggle persiste em localStorage + banco (Pessoas.themeLight).

**Backend (já existia desde v6.24.3):** ThemeService, ThemeController, ThemeExtension — sem alteração.

**CSS (`public/css/app.css`) — Override completo em `[data-bs-theme="dark"]`:**
- **Variáveis CSS invertidas:** `--almasa-white: #1a1d21`, `--almasa-light-gray: #121416`, `--almasa-dark-gray: #e0e0e0`, `--almasa-black: #f8f9fa`, sombras mais fortes
- **Componentes com override dark:** body, navbar, cards, card-footer, forms (form-control + form-label), tabelas, sidebar, stats-cards, property-cards, alertas (4 variantes com rgba escuros), modais, border-gradient, stimulus search/filter, btn-almasa-secondary, dropdown menu, footer

**Templates corrigidos:**
- `dashboard/index.html.twig` — removidas 5 classes `text-dark` hardcoded (Bootstrap 5.3 adapta automaticamente)
- `base.html.twig` — toggle e dropdown do usuario: `btn-light` → `btn-outline-secondary` (funciona nos dois temas)

**Classes CSS customizadas (app.css):**
- `.almasa-top-nav` — Container do menu (gradiente Almasa)
- `.almasa-nav-toggler` — Botao mobile
- `.almasa-nav-main` — Lista de itens
- `.almasa-nav-item` — Item individual
- `.almasa-nav-link` — Link de navegacao
- `.almasa-dropdown-menu` — Menu dropdown estilizado
- `.almasa-dropdown-header` — Titulo de secao
- `.almasa-dropdown-item` — Item do dropdown
- `.almasa-divider` — Divisor estilizado

### Sistema de Autocomplete Global (v6.27.0–v6.29.0)

**AutocompleteController** (`src/Controller/AutocompleteController.php`) — 17 endpoints centralizados de busca AJAX:
`/autocomplete/pessoas`, `/autocomplete/bancos`, `/autocomplete/agencias`, `/autocomplete/contas-bancarias`, `/autocomplete/imoveis`, `/autocomplete/contratos`, `/autocomplete/nacionalidades`, `/autocomplete/naturalidades`, `/autocomplete/logradouros`, `/autocomplete/enderecos`, `/autocomplete/bairros`, `/autocomplete/condominios`, `/autocomplete/plano-contas`, `/autocomplete/almasa-plano-contas`, `/autocomplete/cidades`, `/autocomplete/ufs`, `/autocomplete/users`

**generic_autocomplete.js** (`assets/js/generic_autocomplete.js`) — Modulo JS reutilizavel:
- Busca typeahead com debounce
- Navegacao por teclado (setas + Enter + Escape)
- Preload de valores existentes em modo edicao
- Inicializa mesmo se DOM ja carregou (fix DOMContentLoaded)

**autocomplete_field.html.twig** (`templates/_partials/autocomplete_field.html.twig`) — Partial generico para renderizar campos autocomplete em qualquer formulario.

**30+ campos EntityType convertidos para autocomplete** em 15+ FormTypes:
ImovelFormType, ContaBancariaType, PessoaFormType, LancamentosType, BoletoType, PessoaPretendenteType, PessoaLocadorType, PessoaCorretorType, PessoaFiadorCombinedType, LogradouroType, AlmasaLancamentoType, AlmasaVinculoBancarioType, PrestacaoContasFiltroType, PrestacaoContasRepasseType, ConfiguracaoApiBancoType, AlmasaPlanoContasType, AgenciaType, BairroType, PessoaAdvogadoType

**Criterio de conversao:** Todo select com mais de ~15 opcoes foi convertido para autocomplete AJAX.

**pessoa_tipos.js** — `inicializarComponentesTipo()` inicializa autocompletes em sub-forms carregados via AJAX; `preencherDadosTipo()` preenche display de autocompletes.

### PaginationRedirectTrait no Frontend (v6.29.0)

Apos editar ou excluir um registro, o usuario e redirecionado de volta para a mesma pagina da listagem, mantendo filtros e ordenacao. Implementado em 25 Controllers via `PaginationRedirectTrait`. Alternativa superior a passar page/sort nos parametros GET — usa session para armazenar a URL.

### Máscaras de Documentos (v6.22.0)

**Funções utilitárias em `public/js/pessoa/pessoa.js`:**

| Função | Descrição | Exemplo |
|--------|-----------|---------|
| `window.formatarCPF(cpf)` | Formata CPF no padrão 000.000.000-00 | `"12345678901"` → `"123.456.789-01"` |
| `window.formatarRG(rg)` | Formata RG no padrão XX.XXX.XXX-X | `"123456789"` → `"12.345.678-9"` |
| `window.formatarCNPJ(cnpj)` | Formata CNPJ no padrão 00.000.000/0000-00 | `"12345678000190"` → `"12.345.678/0001-90"` |
| `window.aplicarMascaraDocumento(input, tipo)` | Aplica máscara baseada no tipo ('cpf'/'rg') | - |
| `window.detectarTipoDocumentoPorTexto(texto)` | Detecta tipo a partir de texto do select | `"CPF do titular"` → `"cpf"` |
| `window.aplicarMascaraInputDocumento(input)` | Aplica máscara baseada em data-tipo-documento | - |

**Arquivos com máscaras implementadas:**
- `pessoa_form.js` — Campos de busca (#searchValue, #additionalDocumentValue)
- `pessoa_documentos.js` — Documentos dinâmicos da pessoa principal
- `conjuge_documentos.js` — Documentos dinâmicos do cônjuge
- `pessoa_conjuge.js` — Busca de cônjuge e exibição formatada

**Comportamento:**
- Máscara aplicada em tempo real durante digitação
- Documentos carregados da API são exibidos já formatados
- Apenas CPF e RG recebem máscara; outros documentos não são alterados
- Detecção por ID (CPF=1, RG=2) ou por texto do option do select

---

## Cap 13 — Licoes Aprendidas

### 1. Assinaturas de Funcao
Sempre verificar quantos parametros uma funcao espera antes de chama-la. Uma funcao que espera 2 parametros (`tipos`, `tiposDados`) nao pode ser chamada com apenas 1.

### 2. Campos de Sistema vs. Campos de Formulario
Ao iterar objetos vindos do backend, sempre filtrar campos de banco que nao existem no formulario HTML:
```javascript
const camposIgnorados = ['id', 'created_at', 'updated_at', 'createdAt', 'updatedAt', 'pessoa_id', 'pessoaId'];
```

### 3. Logs sao Essenciais
```javascript
console.log('Sucesso:', dados);
console.warn('Aviso:', mensagem);
console.error('Erro:', erro);
```

### 4. Separacao de Responsabilidades
- `new.js` — Responsavel por chamar funcoes de carregamento
- `pessoa_tipos.js` — Responsavel por criar cards e preencher dados

### 5. Sempre Testar com Dados Reais
Testes com dados mockados nao revelam todos os problemas. Sempre validar com dados reais do banco.

### 6. Schema Doctrine Deve Bater Sempre
Qualquer divergencia estrutural entre entities e banco deve ser corrigida imediatamente, independente da tarefa em andamento.

### 7. Banco de Dados e a Fonte da Verdade
Em caso de divergencia entre entity e tabela PostgreSQL, o banco prevalece.

### 8. CSRF no Symfony 7.2 — SameOriginCsrfTokenManager
Symfony 7.2 introduziu o `SameOriginCsrfTokenManager` como manager padrão no security listener. Ele valida CSRF por **Origin header** (browsers enviam automaticamente em POST) ou **double-submit cookie** — NÃO por sessão. O `CsrfTokenManagerInterface` do container ainda resolve para o antigo `CsrfTokenManager` (session-based). **NUNCA injetar `CsrfTokenManagerInterface` no SecurityController.** Usar `{{ csrf_token('authenticate') }}` no Twig, que resolve para o manager correto.

### 9. Permissões de Arquivo no Deploy
Sempre verificar permissões após deploy. PHP-FPM roda como `www-data` e precisa de `644` (leitura) em todos os arquivos PHP. Arquivos com `600` causam 500 silencioso. Comando de verificação: `find /var/www/AlmasaStudio/src -perm 600 | wc -l`.

### 10. Monolog em Produção
Em `when@prod`, monolog usa `fingers_crossed` → `php://stderr`. Logs vão para o log do PHP-FPM (`/var/log/php8.4-fpm.log`), não para `var/log/`. Para debug temporário, trocar `APP_ENV=dev` e verificar `var/log/dev.log`. **Sempre restaurar `APP_ENV=prod` e `APP_DEBUG=false` após debug.**

### 11. Checkpoints Obrigatórios Antes de Deploy
**ANTES de qualquer deploy ou correção na VPS, SEMPRE criar checkpoint:**
```bash
# 1. Tag no git
git tag -a vX.Y.Z-stable -m "Checkpoint: descrição do estado"
git push origin vX.Y.Z-stable

# 2. Backup na VPS
mkdir -p /var/www/AlmasaStudio/backups/checkpoint-vX.Y.Z
cp .env.local backups/checkpoint-vX.Y.Z/
cp /etc/nginx/sites-available/liviago.com.br backups/checkpoint-vX.Y.Z/nginx-liviago.conf
PGPASSWORD=AlmasaProd2026 psql -h 127.0.0.1 -U almasa_prod -d almasa_prod -c "SELECT id,email,roles,password FROM users" -t > backups/checkpoint-vX.Y.Z/users_dump.txt
find src/Controller -name "*.php" -exec stat -c "%a %n" {} \; > backups/checkpoint-vX.Y.Z/permissions.txt

# 3. Restaurar se necessário
git checkout vX.Y.Z-stable
cp backups/checkpoint-vX.Y.Z/.env.local .
cp backups/checkpoint-vX.Y.Z/nginx-liviago.conf /etc/nginx/sites-available/liviago.com.br
find src -type f -perm 600 -exec chmod 644 {} \;
php bin/console cache:clear && nginx -t && systemctl reload nginx
```
**Último checkpoint estável:** `v6.23.4-stable` (commit `e2ecb9c`, 2026-03-04)

### 12. Doctrine DQL CONCAT Aceita Apenas 2 Argumentos
`CONCAT(a, b, c)` nao funciona em DQL. Para buscas multi-campo, usar `OR` no `WHERE` em vez de concatenar.

### 13. Webpack Encore Precisa Recompilar Apos Alterar Assets
O browser carrega o build compilado (`public/build/`), nao o source de `assets/js/`. Sempre rodar `npx encore production` apos alterar JS.

### 14. Campos Boolean NOT NULL Precisam de Default
Entities Doctrine com campos boolean NOT NULL DEVEM ter `= false` ou `= true` na propriedade PHP, senao INSERT falha com not-null violation no PostgreSQL.

### 15. PaginationService com Session e Melhor que Params GET
Salvar URL de retorno na session (PaginationRedirectTrait) e mais robusto que passar page/sort nos parametros — solucao centralizada que funciona em todos os CRUDs.

### 16. Unique Constraints em Migrations Doctrine
Devem ser declaradas como `uniqueConstraints` na Entity ORM, nao como indices. PostgreSQL requer remocao de constraint antes de remover indice dependente.

### 17. Toda Pessoa DEVE Ter Tipo em pessoas_tipos
O script de migracao DEVE validar 100% no final e falhar explicitamente se qualquer pessoa ficar sem tipo. Nao existe fallback — cada pessoa vem de uma tabela MySQL e o tipo e deterministico.

---

## Cap 14 — Plano de Testes

### Arquitetura Multi-Agente

```
┌─────────────────────────────────────────────┐
│          OPUS (Engenheiro)                   │
│          Claude Code - Orquestrador          │
│                                              │
│  - Define cenarios de teste                  │
│  - Analisa resultados                        │
│  - Toma decisoes arquiteturais               │
├──────────────┬───────────────────────────────┤
│              │                                │
│     ┌────────▼────────┐  ┌──────────────────┐│
│     │ HAIKU            │  │ HAIKU            ││
│     │ test-runner      │  │ e2e-navigator    ││
│     │ (Mestre de Obras)│  │ (Navegador)      ││
│     │                  │  │                  ││
│     │ - Aider + GPT    │  │ - Playwright     ││
│     │ - PHPUnit        │  │ - Testa rotas    ││
│     │ - Monitora       │  │ - Captura erros  ││
│     └──────────────────┘  └──────────────────┘│
└─────────────────────────────────────────────┘
```

### Etapa 1 — Testes Unitarios (PHPUnit)

**Fluxo:**
1. Opus analisa estrutura e identifica classes prioritarias
2. Opus define instrucoes atomicas (uma tarefa por vez)
3. Opus spawna `test-runner` (Haiku) com a tarefa
4. Haiku abre Aider com GPT-OSS 20B via OpenRouter
5. GPT-OSS gera os testes
6. Haiku roda `php bin/phpunit` e coleta resultados
7. Haiku reporta ao Opus
8. Opus analisa, decide correcoes, dispara proxima rodada

**Configuracao do Aider:**
```bash
export OPENROUTER_API_KEY=<sua-chave>
aider --model openrouter/open-gpt-oss-20b --no-auto-commits
```

**Prioridade de cobertura:**
1. Entidades/Models (Doctrine)
2. Services (logica de negocio)
3. Controllers (rotas e responses)
4. Repositories (queries customizadas)
5. Validators/Constraints

### Etapa 2 — Testes E2E (Playwright)

**Fluxo:**
1. Opus define cenarios de navegacao
2. Opus spawna `e2e-navigator` (Haiku) com o cenario
3. Haiku executa via Playwright
4. Haiku reporta resultados com screenshots
5. Opus analisa e prioriza correcoes

**Setup Playwright MCP:**
```json
{
  "mcpServers": {
    "playwright": {
      "command": "npx",
      "args": ["@anthropic/playwright-mcp"]
    }
  }
}
```

**Cenarios prioritarios:**
1. Login/Autenticacao
2. Dashboard principal
3. CRUD de contratos imobiliarios
4. Gestao financeira
5. Integracao bancaria (API)
6. Relatorios

### Agente: test-runner (Haiku)

**Papel:** Mestre de obras — executa e monitora, nunca toma decisoes arquiteturais

**Responsabilidades:**
- Abre Aider via Bash com GPT-OSS 20B
- Passa instrucoes exatas do Opus
- Roda `php bin/phpunit` para validar
- Reporta: quantos testes gerados, passaram, falharam, erros
- Se GPT-OSS entrar em loop, interrompe e reporta

**Regras:**
- NUNCA tome decisoes arquiteturais
- NUNCA altere codigo de producao
- NUNCA faca commits sem autorizacao
- SEMPRE reporte de forma estruturada

### Agente: e2e-navigator (Haiku)

**Papel:** Navegador de testes E2E via Playwright

**Responsabilidades:**
- Executar cenarios de teste definidos pelo Opus
- Navegar rotas, preencher formularios, validar respostas
- Capturar screenshots de falhas
- Reportar detalhado por cenario

**Formato de reporte:**
```
CENARIO: [nome]
ROTA: [URL]
STATUS: PASSOU | FALHOU | ERRO
TEMPO: [duracao]
DETALHES: [se falhou]
SCREENSHOT: [caminho]
```

**Regras:**
- NUNCA edite codigo
- NUNCA altere dados de producao
- Se rota retornar 500, capture info e reporte
- Se sistema estiver down, reporte imediatamente

### Pre-requisitos

- [ ] Node.js instalado
- [ ] Aider instalado (`pip install aider-chat`)
- [ ] Chave OpenRouter configurada
- [ ] AlmasaStudio rodando localmente
- [ ] Banco com dados de teste
- [ ] Playwright instalado (`npx playwright install`)

### Notas Importantes

- **Prompt Atomicity:** Cada tarefa ao GPT-OSS deve ser atomica
- **Sem commits automaticos:** Aider roda com `--no-auto-commits`
- **Budget control:** Configurar `maxTurns` nos sub-agentes
- **Contexto limpo:** Sub-agentes nao poluem contexto do Opus

---

## Changelog

### Formato

Baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.0.0/) + [Semantic Versioning](https://semver.org/lang/pt-BR/).

**Categorias:** Adicionado | Alterado | Descontinuado | Removido | Corrigido | Seguranca

---

### [6.31.1] - 2026-04-14

#### Corrigido
- **Vinculo bancario do saldo anterior** — `AlmasaPlanoContasService::atualizarLancamentoSaldoAnterior()` agora atualiza `contaBancaria` mesmo quando o vinculo foi removido, limpando referencias antigas no lancamento
- **Criacao do saldo anterior** — `criarLancamentoSaldoAnterior()` passou a aplicar o mesmo comportamento consistente de conta bancaria nullable
- **DataTransformer de `saldoAnterior`** — normaliza formatos BR com separador de milhar (`1.234,56`) antes de persistir e sempre devolve 2 casas decimais no form
- **Documentacao da transacao** — `AlmasaPlanoContasService::atualizar()` agora deixa explicito que os `flush()` internos do sincronismo fazem parte da mesma transacao
- **Linha do tempo do livro** — entradas 6.20.x–6.31.x reorganizadas em ordem cronologica

#### Cobertura adicionada
- `tests/Form/AlmasaPlanoContasTypeTest.php` cobre transformacao DB → form e form → model
- `tests/Service/AlmasaPlanoContasServiceTest.php` cobre a limpeza de `contaBancaria` quando nao existe vinculo ativo

---

### [6.31.0] - 2026-04-14

#### Corrigido
- **Sincronizacao de saldo anterior no plano de contas** — correcao completa do problema de persistencia de saldo anterior ao editar contas do plano de contas Almasa
- **Metodo `AlmasaPlanoContasService::atualizar()`** — adicionada transacao com beginTransaction/commit/rollback para garantir atomicidade
- **Metodo `AlmasaPlanoContasService::atualizarLancamentoSaldoAnterior()`** — melhorado com:
  - Verificacao de mudanca real antes de atualizar (tolerancia 0.01 para float)
  - Uso de `sprintf('%.2F')` para formatacao consistente de valores
  - Logs detalhados para debug (valor_antigo, valor_novo, lancamento_id)
  - Verificacao condicional de conta bancaria (so seta se existir)
- **Metodo `AlmasaPlanoContasService::criarLancamentoSaldoAnterior()`** — melhorado com:
  - Warning log ao tentar criar com saldo <= 0
  - Uso de `sprintf()` para formatacao consistente
  - Verificacao condicional de conta bancaria
- **FormType `AlmasaPlanoContasType`** — adicionado DataTransformer customizado para campo `saldoAnterior`:
  - Converte NumberType para TextType com transformer
  - Transformer converte formato BR (virgula) para DB (ponto) automaticamente
  - Garante formatacao consistente com `sprintf('%.2F')`
  - Remove caracteres invalidos durante a conversao

#### Verificado
- **8 contas** com `saldo_anterior > 0` na producao
- **8 lancamentos** correspondentes criados e sincronizados
- **0 divergencias** entre saldo e lancamentos
- **0 contas** sem lancamento correspondente

#### Autor
- **Qwen3.5 27b** (Qwen Code) — 2026-04-14 01:40:47 UTC

#### Deploy
- **Commit:** `763bec8` — "fix: adiciona DataTransformer para campo saldoAnterior"
- **VPS:** 154.53.51.119 (`/var/www/AlmasaStudio`)
- **Status:** ✅ Git pull + cache clear concluidos

---

### [6.30.0] - 2026-04-09

#### Adicionado
- **Saldo Anterior e Saldo Atual nos relatorios financeiros Almasa** — despesas, receitas e despesas x receitas exibem saldo anterior no topo (soma de receber - pagar antes da data inicio) e saldo atual no rodape (saldo anterior +/- movimentacao do periodo). Implementado em preview AJAX e PDF.
- **Metodo `AlmasaRelatorioService::calcularSaldoAnterior($filtros)`** — SQL nativo respeitando filtros `tipo_data`, `status` e `id_plano_conta`
- **Campo `saldoAnterior`** (decimal 15,2, default 0.00) na entity `AlmasaPlanoContas` e coluna `saldo_anterior` na tabela `almasa_plano_contas`
- **`AlmasaPlanoContasService::criarLancamentoSaldoAnterior()`** — ao criar conta com saldo > 0, gera lancamento tipo `receber` ja pago, vinculado via `planoContaCredito`
- **`AlmasaPlanoContasService::atualizarLancamentoSaldoAnterior()`** — ao editar conta, sincroniza lancamento (cria se nao existia, atualiza se mudou, remove se zerou)

#### Corrigido
- 8 contas de plano que tinham `saldo_anterior > 0` mas nao possuiam lancamento correspondente — lancamentos criados via SQL direto na VPS

#### Removido
- Campo `saldoAnterior` foi removido erroneamente de `ContasBancarias` (havia sido colocado por engano) e migrado para `AlmasaPlanoContas`

---

### [6.29.0] - 2026-04-07

#### Adicionado
- **AutocompleteController** — 17 endpoints centralizados de busca AJAX (`/autocomplete/pessoas`, `/autocomplete/bancos`, `/autocomplete/agencias`, `/autocomplete/contas-bancarias`, `/autocomplete/imoveis`, `/autocomplete/contratos`, `/autocomplete/nacionalidades`, `/autocomplete/naturalidades`, `/autocomplete/logradouros`, `/autocomplete/enderecos`, `/autocomplete/bairros`, `/autocomplete/condominios`, `/autocomplete/plano-contas`, `/autocomplete/almasa-plano-contas`, `/autocomplete/cidades`, `/autocomplete/ufs`, `/autocomplete/users`)
- **generic_autocomplete.js** — Modulo JS reutilizavel com busca typeahead, navegacao por teclado, debounce e preload
- **autocomplete_field.html.twig** — Partial Twig generico para campos autocomplete
- **PaginationRedirectTrait** — Trait que preserva pagina, ordenacao e filtros apos editar/excluir em qualquer CRUD
- Campo `descricao` e `titular` no formulario de ContaBancaria
- Flag "Incluir contas de proprietarios" na aba Vinculos do lancamento
- Coluna "Historico" no index de lancamentos
- Filtro `?apenas=almasa` no endpoint `/autocomplete/contas-bancarias`
- Busca por campo `codigo` alem de `descricao`/`titular` nas contas bancarias

#### Alterado
- **30+ campos EntityType convertidos para autocomplete** em 15 FormTypes: ImovelFormType, ContaBancariaType, PessoaFormType, LancamentosType, BoletoType, PessoaPretendenteType, PessoaLocadorType, PessoaCorretorType, PessoaFiadorCombinedType, LogradouroType, AlmasaLancamentoType, AlmasaVinculoBancarioType, PrestacaoContasFiltroType, PrestacaoContasRepasseType, ConfiguracaoApiBancoType, AlmasaPlanoContasType, AgenciaType, BairroType, PessoaAdvogadoType
- **25 Controllers** com PaginationRedirectTrait para preservar paginacao
- Selects em templates de relatorios (proprietarios, plano contas, contas bancarias, inquilinos, pagadores, fornecedores, imoveis, locatarios, fiadores) convertidos para autocomplete
- Relatorios Almasa despesas/receitas agora buscam na tabela `lancamentos` (tipo=pagar/receber) em vez de `almasa_lancamentos`
- Baixa de lancamento: removidas opcoes Credito e Boleto, Debito renomeado para "Debito em Conta"
- Busca de pessoa (searchPessoaAdvanced) agora retorna campo `cod`
- Filtro titular em contas bancarias busca em pessoa.nome, titular e descricao (OR)

#### Corrigido
- Plano de contas preserva pagina apos editar/excluir
- Contas a pagar nao exigem banco na criacao — so na baixa
- ContasBancarias: default false para campos boolean (principal, ativo, registrada, aceitaMultipag, usaEnderecoCobranca, cobrancaCompartilhada)
- generic_autocomplete.js inicializa mesmo se DOM ja carregou (fix DOMContentLoaded)
- Contas proprias Almasa receberam titular "Almasa Administradora" (12 contas)

#### Licao Aprendida
- Doctrine DQL `CONCAT` aceita apenas 2 argumentos, nao varios. Para buscas multi-campo, usar OR no WHERE em vez de CONCAT
- Webpack Encore precisa ser recompilado (`npx encore production`) apos alterar assets/js — o browser carrega o build compilado, nao o source
- Campos boolean NOT NULL em entities Doctrine DEVEM ter valor default, senao INSERT falha com not-null violation
- PaginationService salvar URL na session e melhor que passar page/sort nos params — solucao centralizada

---

### [6.28.0] - 2026-03-27

#### Adicionado
- **Novos endpoints de autocomplete:**
  - `/autocomplete/cidades` — busca cidades com UF do estado
  - `/autocomplete/ufs` — busca estatica de 27 UFs brasileiras
  - `/autocomplete/almasa-plano-contas` agora aceita filtro `?nivel=N` para filtrar por nivel hierarquico

#### Alterado
- **Conversao de 4 EntityType/ChoiceType selects para autocomplete AJAX:**
  - `AlmasaPlanoContasType.pai` (100+ itens) — EntityType para HiddenType unmapped com autocomplete customizado que filtra por nivel e herda tipo contabil
  - `AgenciaType.banco` (200+ bancos) — EntityType para HiddenType unmapped com autocomplete generico
  - `BairroType.cidade` (centenas de cidades) — EntityType para HiddenType unmapped com autocomplete generico; removida opcao `cidades` do FormType
  - `PessoaAdvogadoType.seccionalOab` (27 UFs) — ChoiceType para HiddenType mapped com autocomplete generico
- **Controllers atualizados:**
  - `AlmasaPlanoContasController`: new/edit resolvem `pai` a partir do HiddenType; preloads para label
  - `AgenciaController`: new/edit resolvem `banco` a partir do HiddenType; preloads para label
  - `BairroController`: new/edit resolvem `cidade` a partir do HiddenType; removida carga de todas as cidades; preloads para label
- **Templates atualizados:**
  - `almasa_plano_contas/_form.html.twig` — reescrito JS para usar autocomplete com filtro por nivel e heranca de tipo
  - `agencia/new.html.twig` e `edit.html.twig` — autocomplete para banco
  - `bairro/new.html.twig` e `edit.html.twig` — autocomplete para cidade
  - `pessoa/partials/advogado.html.twig` — autocomplete para seccionalOab
- **pessoa_tipos.js:** `inicializarComponentesTipo()` agora inicializa autocompletes em sub-forms carregados via AJAX; `preencherDadosTipo()` preenche display de autocompletes

---

> **Nota:** Entradas anteriores a [6.28.0] foram consolidadas nos capitulos relevantes do livro.
> Para historico detalhado, consulte Cap 1 (Linha do Tempo), Cap 9 (Relatorios), Cap 10 (Cadastros) e Cap 11 (Banco de Dados).

---

**Ultima atualizacao:** 2026-04-14 (v6.31.1 — follow-up de code review no saldo anterior do plano de contas)
**Mantenedor:** Marcio Martins
**Desenvolvedor Ativo:** Claude Code + Qwen3.5 27b (Qwen Code)
