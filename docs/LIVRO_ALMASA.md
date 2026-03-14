# LIVRO ALMASA — Fonte Unica da Verdade

> **Projeto AlmasaStudio** — Sistema completo de gestao imobiliaria
> Symfony 7.2 | PHP 8.2+ | PostgreSQL 14+ | Bootstrap 5.3

---

## SINOPSE

| Campo | Valor |
|-------|-------|
| **Versao Atual** | 6.28.0 |
| **Data Ultima Atualizacao** | 2026-03-10 (Plano de Contas Almasa v2 + Vinculos Bancarios + DRE + Schema sincronizado) |
| **Status Geral** | Em producao — Migracao 100% completa, schema validado (Doctrine OK), FK integrity 0 erros, 13 novos commits integrados. |
| **URL Produção** | https://www.liviago.com.br/almasa |
| **Deploy** | VPS Contabo 154.53.51.119, Nginx subfolder /almasa |
| **Banco de Dados** | PostgreSQL 16 local na VPS (almasa_prod). Neon Cloud ABANDONADO. 85 tabelas, ~630k registros. |
| **Desenvolvedor Ativo** | Claude Opus 4.6 (via Claude Code) — Arquiteto/Planejador; Implementação via KIMI |
| **Mantenedor** | Marcio Martins |
| **Proxima Tarefa** | Deploy v6.28.0 na VPS + testes E2E Plano de Contas |
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
| 6.25.1 | 2026-03-04 | Code review completo — 17 Thin Controllers refatorados, inline JS corrigido, schema validado |
| 6.25.0 | 2026-03-04 | Tema escuro completo — 40+ componentes CSS com dark mode, dashboard e base.html.twig adaptados |
| 6.24.4 | 2026-03-04 | Card Lançamentos adicionado ao dashboard e menu Financeiro |
| 6.24.3 | 2026-03-04 | Tema escuro global — ThemeService + ThemeExtension para todas as páginas |
| 6.24.2 | 2026-03-04 | Filtro format_documento corrigido — RG brasileiro com dígito verificador (X ou número) |
| 6.24.1 | 2026-03-04 | Remove CPF/CNPJ do card Dados da Pessoa Principal no modo edicao |
| 6.24.0 | 2026-03-04 | Mascara CPF/RG/CNPJ em todas as telas — filtro Twig mask_documento (revertido em show) |
| 6.20.3 | 2026-02-22 | Refactor: Remove CRUD orfao PessoaFiador, Thin Controller Corretor/Locador, banco migrado Neon→PostgreSQL local VPS |
| 6.20.2 | 2026-02-22 | Fix: Thin Controller (10/14), Issue #1 Conjugue resolvida, banco Neon limpo (64 registros teste) |
| 6.20.1 | 2026-02-22 | Fix: Code review 16 modulos — templates corrompidos, entities datetime, FormTypes constraints, inline JS removido, 4 repositories criados |

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
│   └── configuracao_api_banco/     # Config API
```

### Templates

```
templates/
├── base.html.twig
├── _partials/
│   └── breadcrumb.html.twig
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

### Issue Aberta

**#1: Conjuge nao carrega na busca**
- Severidade: MEDIA
- `searchPessoaAdvanced` retorna `'conjuge' => null`
- Causa: Metodo `buscarConjugePessoa()` nao implementado completamente
- Proxima tarefa: Implementar busca completa com todos dados multiplos

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

**Regras de Negocio:**
- Numero sequencial automatico por tipo
- Competencia default = mes do vencimento
- Status automatico baseado em valor_pago vs valor_liquido
- Nao permite editar/cancelar lancamentos pagos
- Valor liquido = valor - desconto + juros + multa - INSS - ISS

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

**Tabela `almasa_plano_contas` (10 colunas) — NOVO v6.28.0**

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

### [6.26.1] - 2026-03-05

#### Corrigido
- **Migracao completa v6 — Importacao 100% dos dados financeiros do MySQL**
  - **Causa raiz:** config.py apontava para dump compacto (36MB, ~10% dados) em vez do completo (272MB)
  - **Correcao dump:** Alterado para `bkpjpw_20260220_121003.sql` (dump completo)
  - **Correcao imovel_map:** Formato `IM0005` agora parseado com `REGEXP_REPLACE` (antes usava regex `^[0-9]+$` que nao casava)
  - **Correcao Phase 12:** Removido INSERT em `imoveis_propriedades` (tabela de caracteristicas, nao proprietarios). Proprietario agora em `imoveis.id_pessoa_proprietario`
  - **Correcao migrate_lancamentos.sql v3:** valor_total direto (nao soma), plano_conta por tipo, credor/pagador corretos
  - **Correcao corretores:** `fisica_juridica` usa `'fisica'`/`'juridica'` (nao `'F'`/`'J'`), inclui `tipo_pessoa`

#### Numeros finais da migracao
| Tabela | Registros | Completude |
|--------|-----------|------------|
| lancamentos_financeiros | 506.704 | 100% do dump MySQL |
| lancamentos | 506.704 | espelhado de lancamentos_financeiros |
| prestacoes_contas | 42.844 | 100% de locrepasse |
| pessoas | 4.933 | 100% (locadores+inquilinos+fiadores+contratantes+corretores) |
| imoveis | 3.236 | 100% de locimoveis |
| imoveis_contratos | 2.130 | 211 ativos, 1.919 encerrados |
| fiadores_inquilinos | 92 | 92 de 265 (173 sem mapeamento no MySQL) |
| FK integrity | 0 erros | todas as FKs validadas cross-table |

---

### [6.26.0] - 2026-03-04

#### Corrigido (v6.26.0 — 7 problemas criticos)
- Situacao imoveis: 211 `vendido` → `locado` (contratos ativos)
- Show pessoa: cards completos (tipos, profissoes, telefones, enderecos, emails, PIX, contas bancarias)
- Filtro imoveis: removido alugado/reservado/em_reforma, adicionado locado
- Mascara CPF/CNPJ no show pessoa
- Endereco numero 0 exibe S/N

---

### [6.25.1] - 2026-03-04

#### Code Review Completo

##### Thin Controllers (17 refatorados)
- **Batch 1 (13 controllers):** Banco, Agencia, ContaBancaria, Telefone, Email, EstadoCivil, Estado, Bairro, Cidade, Nacionalidade, Naturalidade, TipoTelefone, TipoRemessa
- **Batch 2 (4 controllers):** Theme, Logradouro, Contrato, Pessoa
- **Services criados:** BancoService, AgenciaService, ContaBancariaService, TelefoneService, EstadoCivilService, EstadoService, BairroService, CidadeService, LogradouroService
- **Services atualizados:** EmailService, NacionalidadeService, NaturalidadeService, GenericTipoService, ThemeService, PessoaService

##### Inline JS
- `base.html.twig`: JavaScript de toggle de tema movido para `assets/js/theme.js`
- Demais templates críticos já estavam corretos (apenas variáveis globais)

##### Schema Doctrine
- Validado: `[OK] The mapping files are correct`
- Validado: `[OK] The database schema is in sync with the mapping files`

### [6.25.0] - 2026-03-04

#### Adicionado
- **Tema escuro completo** — 40+ componentes Almasa com dark mode via `[data-bs-theme="dark"]`
  - `public/css/app.css`: override de variáveis CSS + overrides para body, navbar, cards, forms, tabelas, sidebar, stats-cards, property-cards, alertas, modais, border-gradient, stimulus components, botões, dropdown, footer
  - `dashboard/index.html.twig`: removidas 5 classes `text-dark` hardcoded que ficavam invisíveis no dark mode
  - `base.html.twig`: botões toggle/dropdown alterados de `btn-light` para `btn-outline-secondary` (adaptam a ambos os temas)

---

### [6.23.3] - 2026-03-03

#### Alterado
- **Campo `cod` visível em edição e visualização de Pessoas**
  - `PessoaFormType.php`: adicionado campo `cod` (IntegerType, opcional, label 'COD')
  - `pessoa_form.html.twig`: campo COD renderizado no card "Dados da Pessoa Principal"
  - `show.html.twig`: linha COD exibida na tabela de dados pessoais (após ID, antes de Nome)

---

### [6.23.2] - 2026-03-03

#### Adicionado
- **Fase 21 de Migração: Auditoria e Autocorreção Automática de Qualidade**
  - Classe `Phase21AuditAndRepairCadastroQualidade` em `migrate.py`
  - Pipeline sequencial idempotente: higienização → deduplicação → backfill → auditoria
  - **A) Higienização:** trim em nomes, remove registros vazios (NULL/blank)
  - **B) Deduplicação:** case-insensitive em `nacionalidades` e `profissoes`, mantém menor id, remapeia FKs
  - **C) Deduplicação vínculos:** remove duplicatas em `pessoas_profissoes` por `(id_pessoa, id_profissao)`
  - **D) Backfill:** extrai `Profissao: ...` de `pessoas.observacoes` e vincula automaticamente
  - **E) Auditoria final:** detecta duplicidade, inválidos, órfãos; loga métricas de cobertura por tipo
  - **Critérios de falha explícita:** falha se duplicados > 0 OR inválidos > 0 OR órfãos > 0
  - Integração automática: `--phase all` inclui Fase 21; compatível com `--phase 21` individual

#### Alterado
- `config.py`: adicionada fase 21 na lista `PHASES`
- `migrate.py`: registrada fase 21 no `PHASE_REGISTRY`

---

### [6.23.1] - 2026-03-03

#### Corrigido
- **Fase 20 de Migração: Correções críticas de SQL e cobertura**
  - `nacionalidades`: removida coluna `ativo` do INSERT (tabela só possui `id, nome`)
  - `pessoas_profissoes`: removida coluna `created_at` do INSERT (não existe na tabela)
  - Atualização de vínculo existente: preenche `empresa`/`renda` quando NULL e há valor de origem
  - Cobertura completa: adicionado `nacionalidade` para `locinquilino` e `estado_civil` para `loclocadores`
  - Busca normalizada: usa `UPPER(nome) = UPPER(%s)` com fallback `ILIKE` (sem cadeia de REPLACE)
  - Idempotência garantida: não duplica vínculos nem gera erro em re-execuções

---

### [6.23.0] - 2026-03-03

#### Adicionado
- **Fase 20 de Migração: Sincronização de Estado Civil, Nacionalidade e Profissão**
  - Classe `Phase20SyncCadastroCivilProfissaoNacionalidade` em `migrate.py`
  - Processa fontes do legado: `loclocadores`, `locfiadores`, `loccontratantes`, `locinquilino`
  - Resolve `id_pessoa` via `StateManager` (namespaces existentes) ou fallback por `pessoas.cod`
  - **Estado Civil:** mapeia código antigo usando `cfg.ESTADO_CIVIL_MAP`, atualiza apenas quando NULL, loga conflitos
  - **Nacionalidade:** `get_or_create` em `nacionalidades`, atualiza apenas quando NULL
  - **Profissão:** `get_or_create` em `profissoes`, vincula em `pessoas_profissoes` sem duplicar
  - Relatório completo: pessoas processadas, estado civil atualizados, nacionalidades atualizadas, profissões vinculadas, conflitos, não encontradas

#### Alterado
- `config.py`: adicionada fase 20 na lista `PHASES`
- `migrate.py`: registrada fase 20 no `PHASE_REGISTRY`

---

### [6.22.0] - 2026-03-03

#### Adicionado
- **Máscaras de CPF e RG em todos os formulários de pessoa**
  - Funções utilitárias em `pessoa.js`: `formatarCPF()`, `formatarRG()`, `formatarCNPJ()`, `aplicarMascaraDocumento()`, `detectarTipoDocumentoPorTexto()`
  - `pessoa_form.js`: máscara em tempo real nos campos de busca (#searchValue para CPF/CNPJ, #additionalDocumentValue)
  - `pessoa_documentos.js`: máscara dinâmica baseada no tipo de documento selecionado (CPF=1, RG=2 ou detecção por texto)
  - `conjuge_documentos.js`: máscara para documentos do cônjuge seguindo mesma lógica
  - `pessoa_conjuge.js`: máscara no campo de busca de cônjuge e exibição formatada nos resultados
  - Documentos carregados da API são exibidos já formatados
  - Sincronização entre `public/js/pessoa/` e `assets/js/pessoa/` mantida

#### Alterado
- Capítulo 12 (Frontend): adicionada seção "Máscaras de Documentos (v6.22.0)" documentando as funções utilitárias.

### [6.21.4] - 2026-03-03

#### Adicionado
- Suporte a filtros `type='number'` no componente reutilizável `search_panel.html.twig`.
- Filtro `cod` na tela de pessoas agora usa input type="number" para melhor UX em dispositivos móveis.

#### Alterado
- `PessoaController`: filtro 'cod' alterado de 'text' para 'number'.
- Capítulo 12 (Frontend): documentação atualizada com novo tipo de filtro suportado.

### [6.21.3] - 2026-03-03

#### Adicionado
- **Campo `cod` legado em TODOS os tipos de pessoa** — Completada a cobertura do código legado para todos os 9 tipos de pessoa
  - Entidades atualizadas: `PessoasCorretores`, `PessoasCorretoras`, `PessoasPretendentes`, `PessoasSocios`, `PessoasAdvogados`
  - Migration Doctrine: `Version20260303_AddCodToPessoasTipos` com colunas `cod` e SQL de backfill
  - Fase 19 de migração (`Phase19SyncCodPessoasTipos`) sincroniza `cod` em todas as tabelas de tipos via UPDATE
  - Tipos cobertos: fiador(1), corretor(2), corretora(3), locador(4), pretendente(5), contratante(6), sócio(7), advogado(8), inquilino(12)

### [6.21.1] - 2026-03-03

#### Corrigido
- **Menu Superior pos-deploy** — Ajustado `templates/_partials/top_navigation.html.twig` para refletir fielmente os cards navegaveis do sistema
  - Removidos links de CRUDs legados: Locadores (`app_pessoa_locador_index`) e Corretores (`app_pessoa_corretor_index`)
  - Nova estrutura de categorias: Dashboard, Cadastros, Pessoas, Imobiliario, Financeiro, Relatorios
  - Dashboard expandido com submenus completos: Enderecos (5 itens) e Tipos (14 itens)
  - Cadastros: Emails e Telefones (movidos de Pessoas)
  - Pessoas: apenas link unico para Todas as Pessoas
  - Imobiliario: Imoveis e Contratos
  - Financeiro: Ficha Financeira, Bancos, Agencias, Contas Bancarias, Boletos, API Bancaria, Informe de Rendimentos
  - Relatorios: Central + 6 relatorios especificos (Inadimplentes, Despesas, Receitas, Despesas x Receitas, Contas Bancarias, Plano de Contas)
- Documentacao atualizada no Cap 12 (estrutura completa do menu)

### [6.21.0] - 2026-03-03

#### Adicionado
- **Menu de Navegacao Superior horizontal** — Novo partial `templates/_partials/top_navigation.html.twig` com acesso organizado por categorias (Dashboard, Pessoas, Imobiliario, Financeiro, Relatorios)
- Dropdowns com icones FontAwesome, headers de secao e divisores logicos
- Estilos responsivos em `public/css/app.css` com suporte mobile (colapso) e temas claro/escuro
- Integracao no `templates/base.html.twig` abaixo da navbar principal
- Documentacao completa no Cap 12 do livro

### [6.20.10] - 2026-03-03

#### Adicionado
- **Fluxo Operacional do Assistente de IA formalizado** — Documentação explícita no Cap 2 da metodologia de trabalho: assistente atua como arquiteto/planejador/revisor (sem escrever código de produção, sem deploy), subagente `@agent/kimi` executa implementação, ciclo de code review com prompts corretivos quando necessário.

### [6.20.9] - 2026-02-27

#### Corrigido
- **ContactController — transacoes atomicas:** `addTelefone` e `addEmail` agora usam `beginTransaction/commit/rollback` para evitar registros orfaos em caso de falha no segundo `flush()`.
- **ContactController — guard tipoEndereco:** Fallback para id=1 agora tem validacao final; retorna 422 se tipo nao encontrado em vez de estourar `flush()` com null em coluna obrigatoria.

#### Validado (Rodada 4 — Consistencia de Dados)
- **Report baseado no dump Neon antigo — banco de producao atual OK:**
  - 389 contratos fiador sem id_pessoa_fiador → **0 no banco atual** (corrigido pela migracao v5.0)
  - 3236/3236 imoveis com proprietario OK
  - id_contrato NULL em lancamentos → **esperado** (legado JPW vincula por id_imovel, nao contrato)
  - boletos/contratos_cobrancas vazias → **esperado** (funcionalidade futura Santander)
  - 11 inquilinos orfaos → dados reais do sistema legado, nao bug de migracao

### [6.20.8] - 2026-02-27

#### Corrigido
- **ContactController reescrito (API entidades incompativeis):** Todos os 5 endpoints POST (`/telefone`, `/email`, `/endereco`, `/conta`, `/pix`) chamavam metodos inexistentes. Telefone/Email agora criam registro base + junction. Endereco usa objetos Doctrine (`setPessoa`, `setLogradouro`, `setTipo`, `setEndNumero`). ContaBancaria recebe entidades (nao ints). ChavesPix usa nomes corretos (`setIdTipoChave`, `setChavePix`).
- **ContratoService checkboxes:** `gera_boleto`, `envia_email`, `ativo` agora gravam `false` quando desmarcados (trocado `isset()` por `!empty()`).

### [6.20.7] - 2026-02-27

#### Corrigido
- **Templates pessoa_corretor/edit e _delete_form:** Continham codigo PHP de controller em vez de Twig. Reescritos com templates validos.
- **Templates email/show e pessoa_corretor/show:** Usavam `block body` (correto: `block content`). Conteudo nao aparecia na pagina.
- **email.idTipo e pessoa_corretor.idPessoa:** Campos inexistentes nas entidades. Corrigidos para `email.tipo.tipo` e `pessoa_corretor.pessoa.nome`.
- **PessoaCorretorType e PessoaLocadorType:** Campo obrigatorio `pessoa` (EntityType) adicionado aos formularios. Sem ele, `flush()` falhava com constraint violation.

### [6.20.6] - 2026-02-27

#### Alterado
- **Migracao v5.0 — 10 correcoes obrigatorias aplicadas (correcoesMigrate.md):**
  - P0.1: Resolver canonico de pessoa (`resolve_or_create_pessoa`) com dedup por CPF/CNPJ/RG+nome+nascimento
  - P0.2: Vinculo fiador-inquilino corrigido via `locfiador_inq` (nao mais `row.get("fiador")`)
  - P0.3: Multiplos proprietarios preservados (maior percentual, co-owners em observacoes)
  - P0.4: Fallback 3-tier para endereco de inquilino (proprio → imovel mapeado → dump MySQL)
  - P0.5: PhaseStats com contadores e bloqueio de fase com erros criticos
  - P1.6: Validacao de distribuicao de estado civil (heuristica conjuge vs mapping)
  - P1.7: Conjuge de fiador com nacionalidade e admissao persistidas
  - P1.8: Junction de telefones com chave composta (numero, tipo_id)
  - P1.9: Normalizacao de agencia (remove pontuacao, zero-padding)
  - Fix extra: `_insert_endereco` defensivo contra int vs string (bug TypeError em re.sub)
- **Banco re-importado do zero:** Limpeza total + re-execucao no VPS (nao mais via tunnel SSH)
- **Validacao pos-migracao 100% limpa:** 0 duplicatas CPF/CNPJ, 0 locatarios sem endereco, 0 fiadores sem pessoa, 0 imoveis sem proprietario

#### Corrigido
- **Bug critico: fallback endereco imovel→inquilino com TypeError** — `end_numero` (INT do PostgreSQL) passado para `re.sub()` que espera string. Causava 2.088 inquilinos sem endereco. Corrigido com cast defensivo.
- **Tabela nacionalidades sem registro id=1** — Causava FK violation em fases 09 e 13. Inserido "Brasileira" id=1.
- **Tabela tipos_telefones sem Fax(4) e Outros(5)** — Causava FK violation em fase 13. Inseridos registros faltantes.
- **id_map com entradas stale apos rollback** — set_mapping gravava no id_map antes do commit, rollback deixava IDs fantasma. Corrigido limpando entries de fases falhadas.

### [6.20.5] - 2026-02-27

#### Alterado
- **Banco de dados re-importado com dados completos** — Limpeza total (cleanup_db.sql preservando admin) + re-execucao da migracao v4.0 com dump compacto (bkpjpw_compacto_2025.sql)
- **Migracao v4.0 — 7 fixes em migrate.py:** naturalidade_id para locadores (fase 08), nacionalidade_id para fiadores/contratantes/inquilinos (fases 09/10/13), telefones fax/outros/telcom (fases 08/13), fallback data nascimento 1900-01-01

#### Corrigido
- **Campos faltantes na migracao:** naturalidade (427 pessoas), nacionalidade (2.260 pessoas), telefones extras (fax/outros/telcom agora extraidos)
- **INSERT em naturalidades/nacionalidades sem coluna `ativo`** — Tabelas so tem (id, nome), corrigido o INSERT
- **User duplicado pos-migracao** — Admin id=13 criado pela migracao removido, mantido original id=1
- **Password PostgreSQL com `!`** — Caracter especial causava falha no psycopg2 DSN, senha resetada sem `!`
- **phases_done.json como dict `{}`** — Inicializado como lista `[]` para compatibilidade com `.append()`

### [6.20.2] - 2026-02-22

#### Corrigido
- **Issue #1: Conjugue não carregava na busca avançada** — Implementado `buscarConjugePessoa()` completo, retorna dados via `relacionamentos_familiares`
- **10 Controllers Thin Controller refatorados** — Movido persist/flush/remove para Services (8 Tipo* + ContratoController + GenericTipoService base)
- **64 registros de teste removidos do banco** — Estados, cidades, bairros, pessoas, documentos (limpeza completa de E2E test data)

### [6.20.4] - 2026-02-23

#### Corrigido
- **RelatorioService — Inadimplentes:** `getInadimplentes()` consultava LancamentosFinanceiros sem eager loading, causando N+1 queries e resposta de 2.3MB (2462 entidades). Adicionado eager loading (`addSelect` para inquilino/imovel/prop/contrato) e `setMaxResults(500)`. Banner de aviso no template quando 500+ registros.
- **RelatorioService — Despesas:** `getDespesas()` e `getTotalDespesas()` consultavam tabela `lancamentos` (vazia). Migrados para `lancamentos_financeiros` com filtro `tipoLancamento = 'despesa'`. `getDespesas()` retorna arrays normalizados. `getTotalDespesas()` usa SQL nativo com COUNT/SUM/CASE WHEN por situacao.
- **RelatorioService — Receitas:** `getReceitas()` e `getTotalReceitas()` consultavam tabela `lancamentos` (vazia). Migrados para `lancamentos_financeiros` com filtro `tipoLancamento IN ('receita', 'aluguel')`. Mesmo padrao de arrays normalizados e SQL nativo.
- **RelatorioService — Contas Bancarias:** `getMovimentosContaBancaria()` consultava `Lancamentos` (vazia) e usava `isReceber()` e `dataPagamento` inexistentes em LancamentosFinanceiros. Migrado para `LancamentosFinanceiros` com `contaBancaria IS NOT NULL` e `situacao = 'pago'`. Retorna arrays normalizados com `dataPagamento` (alias de dataVencimento), `receber` (bool), `historico`, `numeroDocumento`, `valorFloat`. `getSaldoInicialConta()` reescrito com SQL nativo (CASE WHEN por tipoLancamento). `getResumoContas()` adaptado para consumir arrays normalizados.
- **VPS — APP_ENV:** Estava em `dev` em producao, causando log de todas as queries e overhead massivo. Corrigido para `APP_ENV=prod / APP_DEBUG=false`.

#### Adicionado
- **Metodo `agruparDespesas()`** — Agrupa arrays normalizados de despesas por plano_conta/fornecedor/imovel/mes.
- **Metodo `getSituacaoBadge()`** — Mapeia situacao (pago/cancelado/aberto) para classe Bootstrap badge.

### [6.20.3] - 2026-02-22

#### Removido
- **CRUD orfao PessoaFiador eliminado** — Controller, Service, Form, Repository, templates, JS e testes deletados. Nunca tinha entrada no dashboard. Entity PessoasFiadores preservada (288 registros).
- **6 arquivos .md de planejamento** — Violavam Regra 4

#### Alterado
- **Banco de dados migrado de Neon Cloud para PostgreSQL local na VPS** — pg_dump do Neon (14 MB), pg_restore na VPS, DATABASE_URL atualizado para localhost
- **PessoaCorretorController e PessoaLocadorController abandonados** — Código existente mas não utilizado (não fazem parte do fluxo ativo)
- **GenericTipoService expandido** — 7 novos metodos de criacao de tipos
- **Permissoes VPS corrigidas** — chown www-data, chmod 644/755 em todo o projeto

#### Pendente (Próxima Fase)
- 1 PessoaController ainda viola Thin Controller (PessoaCorretorController e PessoaLocadorController abandonados, código existente mas não utilizado)

---

### [6.20.1] - 2026-02-22

#### Adicionado
- **4 novos Repositories** — TiposAtendimentoRepository, TiposCarteirasRepository, TipoPessoaRepository, TipoRemessaRepository
- **Validacao em 13 FormTypes** — NotBlank + Length(min=2, max=255) adicionados em: TipoAtendimentoType, TipoCarteiraType, TipoChavePixType, TipoContaBancariaType, TipoDocumentoType, TipoEmailType, TipoEnderecoType, TipoTelefoneType, EstadoCivilType, NaturalidadeType, TipoPessoaType, TipoRemessaType, TipoImovelType
- **Handler JS `data-confirm-delete`** — `assets/js/crud_filters.js` agora intercepta forms com atributo `data-confirm-delete` e exibe confirmacao antes de submeter
- **Templates show.html.twig faltantes** — Criados para tipo_carteira e pessoa_locador (antes davam Error 500)

#### Alterado
- **12 templates index.html.twig** — Removido `onsubmit="return confirm(...)"` e `onclick="return confirm(...)"` inline, substituido por `data-confirm-delete` (Regra 8)
- **4 Entities com repositoryClass** — TiposAtendimento, TiposCarteiras, TiposPessoas, TiposRemessa agora referenciam seus repositorios
- **2 Entities com repositoryClass** — TiposDocumentos, TiposEmails agora referenciam seus repositorios
- **TipoEnderecoType.php** — Adicionado `'attr' => ['class' => 'form-control']` no campo tipo

#### Corrigido
- **CobrancaController: Warning Array to string conversion** — `$request->query->all()` passava array `status[]` pro Twig `path()`. Fix: `array_filter(..., fn($v) => !is_array($v))`
- **Templates _delete_form.html.twig corrompidos** — `pessoa_locador/_delete_form.html.twig` e `tipo_telefone/_delete_form.html.twig` continham codigo PHP do Controller em vez de Twig. Substituidos por Twig valido
- **TiposImoveis: createdAt/updatedAt como string** — Entity declarava `type: 'string'` em vez de `type: 'datetime'`. Corrigido + migration executada (ALTER TYPE VARCHAR→TIMESTAMP)
- **TipoEmailFixtures.php** — Referenciava `App\Entity\TipoEmail` (nao existe). Corrigido para `TiposEmails`
- **TipoImovelFixtures.php** — Referenciava `App\Entity\TipoImovel` (nao existe). Corrigido para `TiposImoveis`
- **EstadoCivilControllerTest.php** — Testava classe errada `RegimesCasamento`. Corrigido para `EstadoCivil`
- **Templates tipo_imovel** — Usavam `{% block body %}` em vez de `{% block content %}`. Breadcrumb vazio corrigido

---

### [6.20.0] - 2026-02-21

#### Adicionado
- **Busca Avancada padronizada em 33 CRUDs** — card colapsavel no topo com filtros dinamicos por tipo (text, number, select, date, month, boolean)
- **Ordenacao padronizada em 33 CRUDs** — barra de botoes abaixo da busca, clique alterna ASC/DESC, botao ativo destacado
- **Paginacao unificada** — preserva todos os GET params (filtros + sort) nos links de navegacao
- `src/DTO/SearchFilterDTO.php` — DTO para definicao de filtros de busca
- `src/DTO/SortOptionDTO.php` — DTO para definicao de opcoes de ordenacao
- `templates/_partials/search_panel.html.twig` — Partial de busca avancada colapsavel
- `templates/_partials/sort_panel.html.twig` — Partial de barra de ordenacao
- `assets/js/crud/crud_filters.js` — JS modular para toggle collapse, Enter submit, webpack entry
- `{% block javascripts %}` no `base.html.twig` para scripts de pagina

#### Alterado
- `src/Service/PaginationService.php` — Suporte a SearchFilterDTO[], SortOptionDTO[], defaultSort, retrocompativel
- `templates/_partials/pagination.html.twig` — Preserva GET params, variavel showSearch para retrocompat
- 33 Controllers atualizados com filtros e ordenacao via PaginationService
- 33 Templates atualizados com search_panel + sort_panel + pagination padronizado
- 4 Repositories (Boletos, Lancamentos, LancamentosFinanceiros, PrestacoesContas) — novo metodo `createBaseQueryBuilder()`
- Boletos, Lancamentos, FichaFinanceira, PrestacaoContas migrados de filtro custom para PaginationService
- LogradouroController migrado de findAll() para PaginationService

#### Corrigido
- **Bug Contratos: filtros nao aplicados** — `$filtros` eram coletados mas nunca passados ao QueryBuilder. Corrigido via SearchFilterDTO no PaginationService

---

### [6.19.10] - 2026-02-21

#### Corrigido
- **Dashboard: links quebrados dos cards Bancos e Relatórios** — apontavam para `#` em vez das rotas corretas (`app_banco_index`, `app_relatorios_index`)
- **VPS: permissões de arquivos** — 9 controllers, templates, services e assets tinham permissão `600` (só dono), PHP-FPM (`www-data`) não conseguia ler → erro 500 em `/contrato/`, `/boleto/`, `/financeiro/`, etc.
- **VPS: cache Twig sem permissão de escrita** — diretório `var/cache/prod/` pertencia a `deployer`, alterado para `www-data:www-data` com `775`

---

### [6.19.9] - 2026-02-21

#### Corrigido
- **Valores de boletos/recibos corrigidos — 100% consistência com sistema antigo**
  - `valor_principal` estava duplicado em 62.742 recibos: continha o total (aluguel+verbas) e as verbas (condomínio, IPTU, água) foram adicionadas separadamente nos campos próprios
  - Causa: Phase 15 setava verbas nos campos separados mas NÃO subtraía do `valor_principal` quando a conta 1001 (aluguel puro) não existia no `locrechist`
  - Correção: `valor_principal = dump.valor - (condomínio + IPTU + água + luz + gás + outros)`
  - `valor_total` recalculado: `principal + verbas + multa + juros`
  - Verificação 100%: 80.009/80.009 recibos com soma_verbas = dump.valor, valor_pago e situação OK
  - `migrate.py` Phase 15 atualizada para corrigir automaticamente em futuras execuções

---

### [6.19.8] - 2026-02-21

#### Corrigido
- **Cadeia financeira inquilino→proprietário completada**
  - `lancamentos_financeiros.id_proprietario` preenchido para 367.686 lancamentos (era NULL em 100%)
  - 80.009 recibos (migracao_mysql): proprietário derivado via contrato → imóvel → proprietário
  - 287.677 extrato CC com imóvel: proprietário derivado via imóvel → proprietário
  - 139.018 restantes são despesas/receitas administrativas da imobiliária (sem proprietário — correto)
  - `prestacoes_contas.id_imovel` preenchido para 14.882 registros (proprietários com 1 imóvel)
  - 27.962 prestações restantes: proprietários com múltiplos imóveis, dump sem campo `imovel` (100% zerado)
  - `migrate.py` Phases 14, 16, 18 atualizadas para preencher id_proprietario em futuras execuções

#### Verificação da cadeia completa
- **100% contratos** têm imóvel válido (0 quebras)
- **100% imóveis** têm proprietário válido (0 quebras)
- **100% recibos** (migracao_mysql) têm id_proprietario preenchido
- **100% proprietários** de imóveis locados ativos têm prestações de contas (139/139)
- **6 contratos ativos sem lançamento**: são contratos novos criados em 20/fev/2026 (ainda não geraram boleto)
- **13 contratos encerrados sem lançamento**: dados antigos do sistema (imovel=0, contratos de 2004-2024)

---

### [6.19.7] - 2026-02-21

#### Adicionado
- **Migracao de conjuges (relacionamentos familiares)**
  - 165 conjuges de fiadores criados como Pessoa independente (com CPF, RG, profissao, telefone)
  - `pessoas_fiadores.id_conjuge` vinculado para todos os 165 fiadores casados
  - `pessoas_fiadores.conjuge_trabalha` preenchido a partir de `locfiadores.conjtrabalha`
  - 11 conjuges de inquilinos criados (apenas nome disponivel em `locinquilino.nomecjg`)
  - Total: 176 novas Pessoas criadas, 319 documentos, 9 profissoes, 1 telefone
  - Script `migrate.py` Phase 09 e Phase 13 atualizados para contemplar conjuges em futuras execucoes
  - Script `hotfix_conjuges.py` idempotente criado para correcoes ad-hoc

#### Dados de conjuges migrados (locfiadores -> pessoas)
- **nomeconj** -> pessoas.nome
- **cpfconj** -> pessoas_documentos (tipo=CPF)
- **rgconj** -> pessoas_documentos (tipo=RG)
- **dtnascconj** -> pessoas.data_nascimento
- **rendaconj** -> pessoas.renda + pessoas_profissoes.renda
- **conjpaif/conjmaef** -> pessoas.nome_pai/nome_mae
- **atividadeconj** -> profissoes + pessoas_profissoes
- **conjempresaf** -> pessoas_profissoes.empresa
- **conjtelemp** -> telefones + pessoas_telefones
- **conjtrabalha** -> pessoas_fiadores.conjuge_trabalha

---

### [6.19.6] - 2026-02-21

#### Corrigido
- **Inquilinos sem endereco proprio agora recebem endereco do imovel locado**
  - No sistema antigo, inquilinos sem campo `endac` tinham como endereco implicito o imovel que alugavam
  - Script Phase 13 atualizado: se `endac` vazio, copia endereco do imovel vinculado via `locinquilino.imovel`
  - 2.088 enderecos copiados do imovel para o inquilino + 42 enderecos proprios = 2.130 total
  - Apenas 2 inquilinos ficaram sem endereco (sem imovel vinculado no dump)

---

### [6.19.5] - 2026-02-21

#### Corrigido
- **Enderecos proprios de inquilinos (campo endac) nao eram migrados na Fase 13**
  - 42 inquilinos com endereco proprio no dump MySQL — inseridos no banco

#### Relacoes Inquilino-Imovel-Proprietario (verificadas)
- **Cadeia completa**: `ImoveisContratos.id_pessoa_locatario` -> `Pessoas` (inquilino)
- **Imovel**: `ImoveisContratos.id_imovel` -> `Imoveis.id_pessoa_proprietario` -> `Pessoas` (dono)
- **Fiador**: `ImoveisContratos.id_pessoa_fiador` -> `Pessoas` (fiador)
- 2.130 contratos migrados, todos com inquilino e imovel vinculados

---

### [6.19.4] - 2026-02-21

#### Corrigido
- **Tipo Inquilino (e outros) nao aparecia na tela de edicao de pessoa**
  - `PessoaRepository::findTiposComDados()` so buscava em tabelas dedicadas (PessoasLocadores, PessoasFiadores, etc.)
  - Nao existia `PessoasInquilinos` entity, entao inquilinos nunca eram detectados
  - Corrigido: agora le da tabela `pessoas_tipos` como fonte de verdade para TODOS os tipos
  - Tambem mantem busca nas tabelas dedicadas para consistencia
- **Select de tipos no template faltava opcoes: Socio, Advogado, Inquilino**
  - Adicionadas 3 opcoes no select do template `pessoa_form.html.twig`
- **JS `pessoa_tipos.js` nao tinha configuracao para Inquilino**
  - Adicionado `inquilino` no `tiposConfig` com icone `fas fa-home`
- **Controller `tipoParaId` faltava socio, advogado, inquilino**
  - Adicionados mapeamentos: socio=7, advogado=8, inquilino=12

---

### [6.19.3] - 2026-02-21

#### Corrigido
- **Assets JS nao compilados no VPS — Symfony AssetMapper retornava 404 para todos os 19 JS de pessoa**
  - O Symfony AssetMapper versiona arquivos de `assets/` para `public/assets/` com hashes (ex: `pessoa_form-3XJfXni.js`)
  - O comando `php bin/console asset-map:compile` nunca havia sido executado no VPS
  - Template renderizava `<script src="/almasa/assets/js/pessoa/pessoa_form-3XJfXni.js">` → HTTP 404
  - Sem JS, nenhum AJAX de carregamento de dados era executado — todas as secoes ficavam vazias
  - Corrigido: executado `asset-map:compile --env=prod` no VPS, 19 arquivos compilados
- **Arquivos .md proibidos removidos (Regra 4 do CLAUDE.md)**
  - Removido `CHANGELOG.md` (avulso, redundante com o Changelog do livro)
  - Removido `src/DataFixtures/README.md` (documento avulso proibido)
  - Removido `docs/.UPDATE_PENDING` (arquivo temporario)

#### Importante — Deploy no VPS
- Apos qualquer alteracao em `assets/js/`, executar no VPS:
  ```bash
  php bin/console asset-map:compile --env=prod
  php bin/console cache:clear --env=prod
  ```

---

### [6.19.2] - 2026-02-21

#### Corrigido
- **Arquivos JS de pessoa NAO existiam em public/ — UI ficava 100% vazia**
  - Template `pessoa_form.html.twig` usa `asset('js/pessoa/...')` que resolve para `public/js/pessoa/`
  - A pasta `public/js/pessoa/` NAO existia — 19 arquivos JS estavam apenas em `assets/js/pessoa/`
  - Sem o JS, nenhum AJAX rodava e todas as secoes (telefones, emails, docs, etc.) ficavam vazias
  - Corrigido: 19 arquivos copiados para `public/js/pessoa/` local e VPS
- **Tabela `pessoas_tipos` estava VAZIA (0 registros)**
  - Secao "Tipos de Pessoa" da UI le de `pessoas_tipos` para saber os papeis (locador, fiador, etc.)
  - Script de migracao nunca inseriu nessa tabela — apenas nas tabelas de dados especificos
  - Corrigido: 4.921 registros inseridos (2.498 locadores + 288 fiadores + 3 contratantes + 2.132 inquilinos)
  - Script `migrate.py` atualizado com `INSERT INTO pessoas_tipos` em todas as 4 fases de pessoas

#### Alterado
- Script de migracao atualizado para v3.2 no repo almasa-migration

---

### [6.19.1] - 2026-02-21

#### Adicionado
- **Migracao v3.1 — associacoes de tipo, profissoes, chaves PIX completas**
  - `pessoas_locadores`: 2.498 registros com 19 campos financeiros (forma_retirada, dependentes, multa, etc.)
  - `pessoas_fiadores`: 288 registros com motivo_fianca, conjuge_trabalha, etc.
  - `pessoas_contratantes`: 3 registros
  - `pessoas_profissoes`: 2.311 vinculos pessoa-profissao com empresa e renda
  - `profissoes`: 1.453 profissoes unicas extraidas dos dados MySQL
  - `chaves_pix`: 22 chaves PIX importadas (CPF, CNPJ, Telefone, Email, Aleatoria)
  - `formas_retirada`: 5 formas cadastradas (Transferencia, Credito, Cheque, Dinheiro, Outro)
  - Pai/Mae e nacionalidade dos inquilinos importados

#### Corrigido
- **Enderecos de imoveis distribuidos corretamente**: Fase 12 agora atribui id_pessoa ao proprietario real (antes todos iam para placeholder)
- **Profissoes nao eram importadas**: iam apenas como texto no campo observacoes — agora vao para tabela propria com vinculo
- **Papeis nunca atribuidos**: pessoas_locadores/fiadores/contratantes estavam VAZIOS — agora populados

#### Alterado
- Script de migracao atualizado para v3.1 no repo almasa-migration
- config.py: adicionados TIPO_CHAVE_PIX_MAP, FORMAS_RETIRADA_SEED, FORMA_RETIRADA_MYSQL_MAP, DEFAULT_NACIONALIDADE_ID

---

### [6.19.0] - 2026-02-21

#### Adicionado
- **Script de migracao v2 com parametrizacao completa** (scripts/migration/migrate.py)
  - Fase 00: Validacao automatica de TODAS tabelas de parametros antes de importar
  - IDs carregados dinamicamente do banco (nunca mais hardcoded errado)
  - Tipo "inquilino" adicionado a tabela tipos_pessoas (id=12)
- **PaginationService** (src/Service/PaginationService.php) — paginacao 15/30/50/100 por pagina
- **BancoController + CRUD completo** (src/Controller/BancoController.php) — antes nao existia
- **Paginacao e busca em TODOS os 29 CRUDs** do sistema

#### Corrigido
- **tipos_pessoas INVERTIDOS na migracao anterior**: locador era id=1 (fiador!), fiador era id=4 (locador!)
  - Corrigido: locador=4, fiador=1, contratante=6, inquilino=12
- **Telefones TODOS como Celular**: script antigo usava DEFAULT_TIPO_TELEFONE_ID=6 para tudo
  - Corrigido: campo "telefone"=Residencial(2), "celular"=Celular(6), "comercial"=Comercial(3)
  - Resultado: 2450 Residencial + 3315 Celular + 298 Comercial (antes: 6063 todos Celular)
- **23 imoveis falhavam por FK de bairro** (cache stale de transacao rollback)
  - Corrigido: validacao de cache contra DB antes de usar ID cacheado
  - Resultado: 3236/3236 imoveis migrados (100%, antes 99.3%)
- **enderecos.id_pessoa de imoveis apontava para placeholder**
  - Corrigido: Fase 12 agora atualiza enderecos.id_pessoa para proprietario real (2997 corrigidos)
- **Memory error em /almasa/imovel/** — carregava todos 3236 registros sem paginacao
- **Agencia template RuntimeError** — `agencia.idBanco` nao existia como propriedade
- **Tipo imovel sem botao "Novo"**

#### Alterado
- config.py: IDs de parametrizacao documentados e confirmados contra banco real
- Migracao completa: 702.174 registros em 19 fases, 0 erros, 0 warnings
- Script de migracao movido para repo separado: https://github.com/marciomartins83/almasa-migration
  - Removido de `scripts/migration/` do AlmasaStudio
  - Repo privado, independente, não vai para VPS

#### Registros Migrados (2026-02-21)

| Fase | Tabela Origem | Destino | Registros |
|------|--------------|---------|-----------|
| 00 | — | Validacao | 13 IDs |
| 01 | banco | bancos | 15 |
| 02 | agencia | agencias | 16 |
| 03 | conta | contas_bancarias | 14 |
| 04 | locplano | plano_contas | 159 |
| 05 | p_estado | estados | 18 |
| 06 | p_cidade | cidades | 9.627 |
| 07 | p_bairro | bairros | 51.603 |
| 08 | loclocadores | pessoas (locador) | 2.498 |
| 09 | locfiadores | pessoas (fiador) | 288 |
| 10 | loccontratantes | pessoas (contratante) | 3 |
| 11 | locimoveis | imoveis | 3.236 |
| 12 | locimovelprop | vinculos proprietario | 2.998 + 2.997 end |
| 13 | locinquilino | pessoas + contratos | 2.132 + 2.130 |
| 14 | locrecibo | lancamentos_financeiros | 80.009 |
| 15 | locrechist | verbas lancamentos | 80.002 |
| 16 | loclanctocc | lancamentos CC | 426.695 |
| 17 | locacordo | acordos_financeiros | 4 |
| 18 | locrepasse | prestacoes_contas | 42.844 |
| 19 | — | sync_cod_pessoas | — |
| 20 | loclocadores/locfiadores/loccontratantes/locinquilino | sync estado_civil/nacionalidade/profissao | variável |
| **TOTAL** | | | **702.174+** |

---

### [6.17.1] - 2026-02-20

#### Corrigido
- **Assets em producao quebrados** — Webpack publicPath apontava `/build` mas site roda em `/almasa`
  - webpack.config.js: `setPublicPath` agora usa env var `PUBLIC_PATH` + `setManifestKeyPrefix`
  - Build producao com `PUBLIC_PATH=/almasa/build` gera manifest e entrypoints corretos
  - Nginx config: corrigido regex location com capture group para alias funcionar
- **Login quebrado em producao** — `dump()` no UserAuthenticator enviava output antes dos headers HTTP
  - Removidos todos os `dump()` de debug do metodo `authenticate()`
- **Dados de teste no banco** — Limpeza de dados com marcacao "(Fake)" e profissao "Teste Migration"
  - Removida profissao "Teste Migration"
  - Limpados complementos "(Fake)" dos enderecos
  - Corrigida empresa e observacoes das profissoes

---

### [6.17.0] - 2026-02-19

#### Adicionado
- Deploy em produção: https://www.liviago.com.br/almasa
- Config Nginx subfolder /almasa (deploy/nginx-almasa.conf)
- Banco PostgreSQL local na VPS (migrado do Neon Cloud em 2026-02-22)
- MetodologiaAPI v1.1.0 criada e deployada em /apiCode
- API coercitiva que injeta regras no CLAUDE.md dos projetos

---

### [6.16.1] - 2026-02-19

#### Corrigido
- **Testes PHPUnit em tests/Service/** — Corrigidas 3 suites de testes com 44 testes
  - **ImovelServiceTest.php** (2 erros):
    - Linha 70 e 151: `getEndereco()` retornava `null` mas tipo de retorno é `Enderecos` (não-nulável)
    - Solução: Criar mock de `Enderecos` e `Pessoas` e retornar no lugar de `null`
    - 12 testes agora passam com 31 assertions
  - **InformeRendimentoServiceTest.php** (1 erro):
    - Linha 92: Tentava chamar `setId()` que não existe em `InformesRendimentos` (ID é auto-gerado)
    - Solução: Usar Reflection para definir propriedade `id` privada
    - 16 testes agora passam com 48 assertions
  - **RelatorioServiceTest.php** (múltiplas chamadas):
    - Múltiplos testes com `expects($this->once())` em `getResult()` que era chamado mais de uma vez
    - Solução: Alterar para `expects($this->any())` em 5 métodos (getDespesas, getTotalDespesas, getTotalReceitas, getPlanoContas, getMovimentosContaBancaria)
    - 16 testes agora passam com 40 assertions
  - **Schema Doctrine:** Validado e sincronizado (`[OK]`)
  - **Total:** 44 testes, 119 assertions, 0 falhas

---

### [6.16.0] - 2025-12-08

#### Adicionado
- **Modulo Relatorios PDF** — 6 relatorios com preview AJAX e geracao PDF via DomPDF
  - Inadimplentes, Despesas, Receitas, Despesas x Receitas, Contas Bancarias, Plano de Contas
  - RelatorioService.php (~800 linhas), RelatorioController.php (~490 linhas)
  - 19 rotas, 20+ templates, 2 modulos JS
  - webpack.config.js atualizado

### [6.15.0] - 2025-12-08

#### Adicionado
- **Modulo Prestacao de Contas aos Proprietarios**
  - Migration com tabelas `prestacoes_contas` e `prestacoes_contas_itens`
  - PrestacoesContas.php (580+ linhas), PrestacoesContasItens.php (300+ linhas)
  - PrestacaoContasService.php (600+ linhas), PrestacaoContasController.php (350+ linhas)
  - 13 rotas, 6 templates, 2 modulos JS, 2 FormTypes

### [6.14.0] - 2025-12-07

#### Adicionado
- **Modulo Lancamentos (Contas a Pagar/Receber)** — CRUD completo
  - Migration expansao tabela `lancamentos`
  - Lancamentos.php (860+ linhas), LancamentosService.php (550+ linhas)
  - 12 rotas, 5 templates, 2 modulos JS

### [6.13.0] - 2025-12-07

#### Adicionado
- **Sistema Completo de Cobranca Automatica**
  - 4 tabelas: contratos_itens_cobranca, contratos_cobrancas, emails_enviados
  - CobrancaContratoService.php (450+ linhas), EmailService.php (343 linhas)
  - Command `app:enviar-boletos-automatico`, 8 rotas AJAX

### [6.12.0] - 2025-12-07

#### Adicionado
- **CRUD Completo de Boletos Bancarios**
  - BoletoController (400 linhas), BoletoType (270 linhas), 12 rotas, JS modular

### [6.11.1] - 2025-12-07

#### Alterado
- Regra 0 Schema Doctrine no CLAUDE.md

#### Corrigido
- Sincronizacao completa de Schema Doctrine (tipos, nullability, campos, indices)

### [6.11.0] - 2025-12-07

#### Adicionado
- **Integracao API Santander** — Auth OAuth 2.0 + mTLS, Boletos + BoletosLogApi entities, 2 services

### [6.10.0] - 2025-12-07

#### Adicionado
- **Configuracao API Bancaria** — CRUD, upload certificado A1, validacao OpenSSL

### [6.9.0] - 2025-12-05

#### Adicionado
- **Ficha Financeira / Contas a Receber** — 3 tabelas, FichaFinanceiraService (600+ linhas), 14 metodos

### [6.8.0] - 2025-12-05

#### Adicionado
- **Contratos de Locacao** — 11 campos novos, ContratoService (615 linhas), renovacao/encerramento

### [6.23.4] - 2026-03-03

#### Corrigido
- **CSRF Login quebrado (Symfony 7.2)** — SecurityController injetava `CsrfTokenManagerInterface` (session-based) mas o `CsrfProtectionListener` validava via `SameOriginCsrfTokenManager` (double-submit/origin). Token gerado por um manager, validado por outro = sempre inválido. Fix: removida injeção manual, template usa `{{ csrf_token('authenticate') }}` do Twig.
- **Permissões de arquivo 600 nos controllers** — BoletoController, CobrancaController, ConfiguracaoApiBancoController, ContratoController tinham permissão `600` (só owner lê). PHP-FPM (www-data) não conseguia ler → 500 Internal Server Error. Fix: `chmod 644` em todos os arquivos do projeto.
- **Password hash corrompido no banco** — Hash do admin estava com apenas 33 chars (sem prefixo `$2y$`), impedindo login. Fix: regenerado hash bcrypt válido (60 chars).
- **APP_DEBUG=true deixado em produção** — Restaurado para `false`.
- **Logs não eram gravados** — Em modo `prod`, monolog envia para `php://stderr` (PHP-FPM log), não para arquivo. Identificado e documentado.
- **Schema TiposImoveis desincronizado** — Entity tinha `created_at`/`updated_at` como `nullable: true` mas banco tinha `NOT NULL`. Fix: entity alinhada com banco (NOT NULL). Schema agora 100% sincronizado local + VPS.
- **2.132 inquilinos SEM tipo em pessoas_tipos** — Fase 18.5 do script de migração filtrava `tipo_pessoa IN (1..8)` mas inquilino é `tipo_pessoa=12`. TIPO_MAPPING também não incluía 12. Fix: adicionado inquilino no TIPO_MAPPING e no filtro SQL. SQL direto aplicado no banco local + VPS para corrigir dados existentes. Validação 100% adicionada ao final da fase (raise RuntimeError se qualquer pessoa ficar sem tipo).

#### Lição Aprendida
- Symfony 7.2 introduziu `SameOriginCsrfTokenManager` como padrão para o security listener. Ele valida CSRF por Origin header (browsers enviam automaticamente) ou double-submit cookie. Não usar `CsrfTokenManagerInterface` diretamente no controller de login — usar `{{ csrf_token('id') }}` no Twig.
- **TODA pessoa DEVE ter pelo menos 1 tipo em pessoas_tipos.** O script de migração DEVE validar 100% no final e falhar explicitamente se qualquer pessoa ficar sem tipo. Não existe fallback — cada pessoa vem de uma tabela MySQL (loclocadores, locfiadores, loccontratantes, locinquilino) e o tipo é determinístico.

### [6.24.4] - 2026-03-04

#### Adicionado
- Card "Lançamentos" no dashboard (Financeiro e Documentos)
- Item "Lançamentos" no menu Financeiro
- Template: `templates/dashboard/index.html.twig` - card adicionado após Bancos
- Template: `templates/_partials/top_navigation.html.twig` - item adicionado após Ficha Financeira

### [6.24.3] - 2026-03-04

#### Adicionado
- ThemeService para gerenciar tema global do usuário logado
- ThemeExtension para expor variáveis de tema no Twig
- Tema escuro agora funciona em todas as páginas (não só no form de pessoa)

#### Alterado
- `templates/base.html.twig` - usa variável global `{{ theme }}` ao invés de verificação condicional
- `config/services.yaml` - registro do ThemeService

### [6.24.2] - 2026-03-04

#### Alterado
- Filtro Twig `format_documento` corrigido para RG brasileiro
- RG com dígito verificador (número ou X): `43.820.141-3`
- Suporta 7, 8, 9 e 10+ dígitos
- Arquivo: `src/Twig/DocumentoFormatExtension.php`
- Função JavaScript `formatarRG` corrigida em `assets/js/pessoa/pessoa.js`

### [6.24.1] - 2026-03-04

#### Alterado
- Campo CPF/CNPJ (searchTerm) removido do card "Dados da Pessoa Principal" no modo edição
- Template: `templates/pessoa/pessoa_form.html.twig` - adicionado condição `{% if not isEditMode %}`
- CPF/CNPJ agora aparece apenas na seção Documentos

### [6.24.0] - 2026-03-04

#### Adicionado
- Filtro Twig `mask_documento` para mascarar CPF, RG e CNPJ
- Mascara CPF: `***.123.456-**`
- Mascara CNPJ: `**.123.456/0001-**`
- Mascara RG: `*.123.456-*` (detecção automática por qtd dígitos)
- Arquivo: `src/Twig/DocumentoMaskExtension.php`

#### Alterado
- Templates atualizados com filtro `mask_documento`:
  - `templates/pessoa/show.html.twig`
  - `templates/relatorios/pdf/contas_bancarias.html.twig`
  - `templates/relatorios/pdf/despesas_receitas.html.twig`
  - `templates/relatorios/pdf/receitas.html.twig`
  - `templates/relatorios/pdf/despesas.html.twig`
  - `templates/relatorios/preview/contas_bancarias.html.twig`
  - `templates/relatorios/preview/despesas.html.twig`
  - `templates/financeiro/lancamento_show.html.twig`

### [6.28.0] - 2026-03-10

#### Adicionado
- **Modulo Almasa Plano de Contas v2 — Refactor e Expansão**
  - 5 grupos contábeis (Ativo, Passivo, Receita, Despesa, Patrimônio)
  - 5 níveis hierárquicos de lançamentos
  - Busca avançada, ordenação por grupo/código, paginação
  - Relatório Plano de Contas Almasa com preview AJAX e PDF
  - Seção "Almasa Empresa" adicionada ao dashboard
  - 15 rotas, 8 templates, 3 modulos JS, FormType com validações
- **Modulo Vinculos Bancarios — Integração Contas/Plano de Contas**
  - CRUD completo de vinculos entre contas bancárias e plano de contas
  - Busca de pessoa no formulário (autocomplete)
  - Unique constraint: (conta_bancaria, plano_conta)
  - 6 rotas, 4 templates, 2 modulos JS
- **Partidas Dobradas — Débito/Crédito no Plano**
  - Sistema de débito/crédito integrado ao formulário
  - Validação de saldo na edição
  - DRE (Demonstrativo de Resultado) gerado dinamicamente

#### Alterado
- Plano de Contas: código auto-sugerido com prefixo travado (2.1.01.XXX, etc.)
- Script PHP `gerar_contas_proprietarios.php` — cria contas individuais por proprietário
- UX do formulário Plano de Contas: redesenhado com grupos visiveis
- IDs do JavaScript corrigidos no formulário (seletores jQuery)
- Remover filtro Twig inexistente 'repeat' (erro em template)

#### Corrigido
- Prefixo "Conta Corrente de" removido das contas de proprietários (apenas nome agora)

#### Lição Aprendida
- Unique constraints em migrations devem ser declaradas como `uniqueConstraints` na Entity ORM, não como índices
- PostgreSQL requer remoção de constraint antes de remover índice dependente
- Plano de Contas Almasa é o centro da contabilidade — todas as contas bancárias devem vincular aqui

---

### [6.27.0] - 2026-03-08

#### Adicionado
- **Documentação de Banco de Dados** — Mapa completo com 85 tabelas, ~630k registros
  - Módulos: Pessoas, Imóveis, Contratos, Financeiro, Contas Bancárias, Plano de Contas
  - Hierarquias de FK: pessoas → enderecos → logradouros → bairros → cidades → estados
  - Tabelas históricas: `lancamentos_financeiros` (506.704 migrados), `lancamentos` (CRUD novo)
- **Documentação de Tipos de Lançamentos** — 99 tipos catalogados (50 receitas, 49 despesas)
  - Códigos 1001-1049 (receitas), 2001-2055 (despesas)
  - Mapeamento legado: IDs 1313-1466 migrados para códigos 1001-1049

---

### [6.21.2] - 2026-03-03

#### Adicionado
- Campo `cod` em `pessoas` para manter código legado da migração MySQL
- Campo `cod` em `pessoas_tipos` para herança de código por tipo (locador, fiador, contratante, inquilino)
- Campo `cod` em `pessoas_locadores`, `pessoas_fiadores`, `pessoas_contratantes`
- Campo `flg_proprietario` em `pessoas_locadores` para identificar locadores que são proprietários de imóveis
- Filtro no index de pessoas para filtrar locadores por perfil: todos, proprietários, não-proprietários
- Coluna COD exibida no grid de pessoas

#### Alterado
- Migration Python atualizada para persistir `cod` em todas as tabelas de tipo
- Migration Python Fase 08 agora identifica proprietários via `locimovelprop.proprietario`
- PessoaController::index() com lógica de filtro customizado para locadores
- Template pessoa/index.html.twig com coluna COD e filtro de locador

### [6.7.1] - 2025-12-04

#### Adicionado
- **Tipos Socio e Advogado** — 2 tabelas, entities, repositories, FormTypes, templates

### [6.7.0] - 2025-12-01

#### Adicionado
- **Informe de Rendimentos / DIMOB** — 5 tabelas, InformeRendimentoService (500+ linhas)

### [6.6.6] - 2025-11-30

#### Corrigido
- Codigo corrompido em ImovelController, atributos snake_case para camelCase

### [6.6.5] - 2025-11-29

#### Adicionado
- **Modulo Completo de Imoveis** — 9 tabelas, 8 entidades, ImovelService (540 linhas)

### [6.6.4] - 2025-11-27

#### Removido
- Arquivos .md temporarios (README.md, MIGRATION_*.md, CORRECAO_*.md)

#### Adicionado
- Regras no CLAUDE.md sobre uso exclusivo do CHANGELOG.md

### [6.6.3] ate [6.5.5] - 2025-11-24 ate 2025-11-16

#### Corrigido
- Persistencia data admissao conjuge
- NonUniqueResultException (registros duplicados)
- PRIMARY KEYs faltantes
- Select tipo documento conjuge
- Validacoes CSRF adicionadas
- Enriquecimento de dados

### [6.5.4] ate [6.5.0] - 2025-11-16

#### Adicionado
- `buscarConjugePessoa()` — busca completa dados do conjuge
- Carregamento automatico modo edicao
- Melhorias listagem (CPF/CNPJ, tipos por extenso)

### [6.4.1] - 2025-11-16

#### Adicionado
- CLAUDE.md com diretrizes completas

#### Alterado
- Template renomeado new.html.twig para pessoa_form.html.twig

### [6.4.0] - 2025-11-16

#### Corrigido
- Carregamento de tipos de pessoa ao buscar pessoa existente
- Metodos de busca de documentos

### [6.3.0] - 2025-11-09

#### Adicionado
- PessoaService (Fat Service), PessoaController refatorado (Thin Controller)

### [6.2.0] - 2025-11-08

#### Adicionado
- Modulos JS para dados multiplos do conjuge, pessoa_conjuge.js, pessoa_modals.js

### [6.1.0] - 2025-11-07

#### Adicionado
- Rotas DELETE para dados multiplos, modulos JS, token CSRF padronizado

### [6.0.0] - 2025-11-06

#### Adicionado
- Busca inteligente, sistema de tipos multiplos, FormTypes por tipo

### [5.0.0] - 2025-11-05

#### Adicionado
- Implementacao inicial Modulo Pessoas — 13 entidades, PostgreSQL + Webpack Encore + Bootstrap 5

---

### Migracoes Criticas (Referencia)

- **User -> Users:** Entity singular para tabela plural
- **Pessoa -> Pessoas:** 15 arquivos atualizados
- **isThemeLight():** Controle de tema integrado

---

**Ultima atualizacao:** 2026-03-14 (v6.28.0 — Plano de Contas Almasa v2 + Vinculos Bancarios + Partidas Dobradas + Documentação DB integrada)
**Mantenedor:** Marcio Martins
**Desenvolvedor Ativo:** Claude Opus 4.6 (via Claude Code) — Arquiteto/Planejador; Implementação via @agent/kimi
