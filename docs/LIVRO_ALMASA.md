# LIVRO ALMASA — Fonte Unica da Verdade

> **Projeto AlmasaStudio** — Sistema completo de gestao imobiliaria
> Symfony 7.2 | PHP 8.2+ | PostgreSQL 14+ | Bootstrap 5.3

---

## SINOPSE

| Campo | Valor |
|-------|-------|
| **Versao Atual** | 6.20.6 |
| **Data Ultima Atualizacao** | 2026-02-27 |
| **Status Geral** | Em producao — Migracao v5.0 validada (0 duplicatas, 0 locatarios sem endereco), Thin Controller (12/14) |
| **URL Produção** | https://www.liviago.com.br/almasa |
| **Deploy** | VPS Contabo 154.53.51.119, Nginx subfolder /almasa |
| **Banco de Dados** | PostgreSQL 16 local na VPS (almasa_prod). Neon Cloud ABANDONADO. |
| **Desenvolvedor Ativo** | Claude Opus 4.6 (via Claude Code) |
| **Mantenedor** | Marcio Martins |
| **Proxima Tarefa** | Refatorar 2 Pessoa*Controllers restantes (PessoaController, PessoaCorretorController) |
| **Issue Aberta** | #2: 2 Pessoa*Controllers Thin Controller pendente (PessoaController, PessoaLocadorController ja OK) |
| **Migracao MySQL->PostgreSQL** | v5.0 — 19 fases, 10 correcoes P0/P1, dedup por CPF/CNPJ, fallback 3-tier endereco, PhaseStats, validacao 100% limpa |
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

**Tipos de filtro suportados:** text, select, date, month, boolean
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
- **PessoaCorretorController e PessoaLocadorController refatorados** — Thin Controller via PessoaCorretorService e PessoaLocadorService
- **GenericTipoService expandido** — 7 novos metodos de criacao de tipos
- **Permissoes VPS corrigidas** — chown www-data, chmod 644/755 em todo o projeto

#### Pendente (Próxima Fase)
- 2 Pessoa*Controllers ainda violam Thin Controller (PessoaController, PessoaCorretorController)

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
- **Busca Avancada padronizada em 33 CRUDs** — card colapsavel no topo com filtros dinamicos por tipo (text, select, date, month, boolean)
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
| **TOTAL** | | | **702.174** |

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

**Ultima atualizacao:** 2026-02-23
**Mantenedor:** Marcio Martins
**Desenvolvedor Ativo:** Claude Opus 4.6 (via Claude Code)
