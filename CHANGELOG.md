# Changelog - Projeto Almasa

Todas as mudanças notáveis neste projeto serão documentadas neste arquivo.

O formato é baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.0.0/),
e este projeto adere ao [Semantic Versioning](https://semver.org/lang/pt-BR/).

## [Unreleased]

---

## 📌 VERSÕES RECENTES (Detalhadas)

## [6.14.0] - 2025-12-07

### Adicionado
- **Módulo Lançamentos (Contas a Pagar/Receber) - CRUD Completo**
  - Migration `Version20251207000000` - Expansão da tabela `lancamentos`:
    - Novos campos: tipo (pagar/receber), status, datas, pessoas, valores adicionais
    - Campos de retenção fiscal (INSS, ISS)
    - Vínculos com contratos, imóveis, contas bancárias, boletos
    - Índices otimizados para performance
  - **Entity** `Lancamentos.php` (860+ linhas):
    - Constantes para tipos (PAGAR/RECEBER), status, origens
    - Relacionamentos ManyToOne com PlanoContas, Pessoas, Contratos, etc.
    - Métodos auxiliares: getValorLiquido(), getSaldo(), isVencido(), getDiasAtraso()
    - Métodos de badge para UI: getStatusBadgeClass(), getTipoBadgeClass()
  - **Repository** `LancamentosRepository.php`:
    - `findByFiltros()` - listagem com 10+ filtros combinados
    - `findVencidos()` - lançamentos em atraso
    - `findByCompetencia()` - filtro por mês/ano
    - `getProximoNumero()` - sequencial por tipo
    - `getEstatisticas()` - totais a pagar/receber/vencidos
  - **Service** `LancamentosService.php` (550+ linhas):
    - CRUD completo com transações
    - Baixa de pagamento (total/parcial)
    - Estorno de baixa
    - Cancelamento e suspensão
    - Cálculo automático de retenções (INSS/ISS)
    - Validações de regras de negócio
  - **FormType** `LancamentosType.php`:
    - Campos organizados por abas (Principal, Pessoas, Vínculos, Documento, Retenções)
    - EntityType para relacionamentos
    - Máscaras e validações
  - **Controller** `LancamentosController.php` (370+ linhas):
    - 12 rotas (CRUD + operações financeiras + APIs)
    - Padrão Thin Controller
    - Validação CSRF em todas operações AJAX
  - **Templates** (5 arquivos):
    - `index.html.twig` - Cards de estatísticas, filtros, tabela com badges, modais de baixa/cancelamento
    - `new.html.twig` / `edit.html.twig` - Formulários com abas
    - `_form.html.twig` - Partial reutilizável
    - `vencidos.html.twig` - Lista de vencidos com dias de atraso
    - `estatisticas.html.twig` - Dashboard de resumo financeiro
  - **JavaScript** modular (`lancamentos/`):
    - `lancamentos.js` - Funções utilitárias, requisições AJAX, formatação
    - `app.js` - Event listeners, modais, cálculo de retenções, auto-preenchimento

### Rotas Disponíveis
| Rota | Método | Descrição |
|------|--------|-----------|
| `/lancamentos/` | GET | Listagem com filtros |
| `/lancamentos/new` | GET/POST | Novo lançamento |
| `/lancamentos/{id}/edit` | GET/POST | Editar lançamento |
| `/lancamentos/{id}` | DELETE | Excluir lançamento |
| `/lancamentos/{id}/baixa` | POST | Realizar baixa |
| `/lancamentos/{id}/estornar` | POST | Estornar baixa |
| `/lancamentos/{id}/cancelar` | POST | Cancelar lançamento |
| `/lancamentos/{id}/suspender` | POST | Suspender lançamento |
| `/lancamentos/vencidos` | GET | Lista vencidos |
| `/lancamentos/estatisticas` | GET | Dashboard |
| `/lancamentos/api/lista` | GET | API JSON |
| `/lancamentos/api/estatisticas` | GET | API estatísticas |

### Regras de Negócio
- Número sequencial automático por tipo (pagar/receber separados)
- Competência default = mês do vencimento
- Status automático baseado em valor_pago vs valor_liquido
- Não permite editar/cancelar lançamentos pagos
- Cálculo automático de retenções INSS/ISS
- Valor líquido = valor - desconto + juros + multa - INSS - ISS

---

## [6.13.0] - 2025-12-07

### Adicionado
- **Sistema Completo de Cobrança Automática de Contratos**
  - Migration com 4 novas tabelas: `contratos_itens_cobranca`, `contratos_cobrancas`, `emails_enviados`
  - Entities: ContratosItensCobranca, ContratosCobrancas, EmailsEnviados + atualização de ImoveisContratos
  - Services: EmailService (343 linhas), CobrancaContratoService (450+ linhas)
  - Command: `app:enviar-boletos-automatico` (cron job diário)
  - Controller: CobrancaController (355 linhas) com 8 rotas AJAX
  - Templates: pendentes, show, emails de cobrança/lembrete
  - JavaScript modular (380 linhas) - seleção em lote, envio, preview, cancelamento

### Regras de Negócio
- Competência definida pelo período de locação
- Impossível duplicar cobranças (constraint única)
- Override manual bloqueia envio automático
- Boletos gerados X dias antes do vencimento (configurável)

---

## [6.12.0] - 2025-12-07

### Adicionado
- **CRUD Completo de Boletos Bancários**
  - BoletoController (400 linhas) com 12 rotas
  - BoletoType (270 linhas) - formulário completo
  - Templates: index (estatísticas + filtros), show (detalhes + QR Code), new, impressão
  - JavaScript modular: boleto.js (AJAX), boleto_form.js (máscaras + validações)
  - Novos métodos no BoletoSantanderService: CRUD, lote, estatísticas

---

## [6.11.1] - 2025-12-07

### Alterado
- **CLAUDE.md: Adicionada Regra 0 - Schema Doctrine DEVE BATER**
  - Nova regra de ouro com prioridade máxima
  - Procedimento obrigatório de verificação
  - Tabela de divergências aceitáveis vs não-aceitáveis

### Corrigido
- **Sincronização COMPLETA de Schema Doctrine**
  - Corrigido tipo de coluna `id_pessoa` em PessoasSocios e PessoasAdvogados
  - Corrigida nullability em Boletos, LancamentosFinanceiros, BaixasFinanceiras, AcordosFinanceiros
  - Adicionados campos faltantes em LancamentosFinanceiros (auditoria)
  - Adicionados índices customizados em múltiplas entidades
  - **Resultado:** Sistema 100% sincronizado com banco

---

## [6.11.0] - 2025-12-07

### Adicionado
- **Integração API Santander - Autenticação e Services Base**
  - Migration: tabelas `boletos` e `boletos_log_api`
  - Entities: Boletos (550+ linhas), BoletosLogApi (170+ linhas)
  - Services:
    - SantanderAuthService (300+ linhas) - OAuth 2.0 com mTLS
    - BoletoSantanderService (450+ linhas) - geração, registro, consulta, baixa

---

## [6.10.0] - 2025-12-07

### Adicionado
- **Módulo Configuração API Bancária (CRUD)**
  - Migration: tabela `configuracoes_api_banco`
  - Entity ConfiguracoesApiBanco com validação de certificado A1
  - ConfiguracaoApiBancoService - upload seguro, validação OpenSSL
  - Controller + Templates + JavaScript modular
  - Armazenamento seguro de certificados em `var/certificates/`

---

## 📦 VERSÕES INTERMEDIÁRIAS (Resumidas)

## [6.9.0] - 2025-12-05
**Ficha Financeira / Contas a Receber**
- 3 tabelas: lancamentos_financeiros, baixas_financeiras, acordos_financeiros
- FichaFinanceiraService (600+ linhas) - 14 métodos de gestão financeira
- Controller (395 linhas) + 5 templates + 2 arquivos JS modulares
- Estatísticas, inadimplência, geração automática de lançamentos

## [6.8.0] - 2025-12-05
**Contratos de Locação**
- 11 novos campos em `imoveis_contratos` (taxa admin, garantia, reajuste, etc.)
- ContratoService (615 linhas) - CRUD completo, renovação, encerramento
- Controller (280 linhas) + 3 templates + 2 módulos JS

## [6.7.1] - 2025-12-04
**Novos Tipos de Pessoa: Sócio e Advogado**
- 2 tabelas: pessoas_socios, pessoas_advogados
- Entities + Repositories + FormTypes + Templates
- Integração completa no sistema de tipos múltiplos

## [6.7.0] - 2025-12-01
**Informe de Rendimentos / DIMOB**
- 5 tabelas: plano_contas, lancamentos, informes_rendimentos, valores, configurações
- InformeRendimentoService (500+ linhas) - processamento, impressão, geração DIMOB
- Template com 4 abas + 5 arquivos JS modulares

## [6.6.6] - 2025-11-30
**Correções Críticas no Módulo Imóveis**
- Corrigido código corrompido em ImovelController
- Corrigidos atributos snake_case → camelCase em templates
- Padronizados templates para seguir layout do projeto

## [6.6.5] - 2025-11-29
**Módulo Completo de Imóveis**
- 9 tabelas: condominios, propriedades_catalogo, imoveis, relacionamentos, medidores, garantias, fotos, contratos
- 8 entidades + 8 repositórios
- ImovelService (540 linhas) + Controller (224 linhas)
- 3 templates + 3 arquivos JS modulares

## [6.6.4] - 2025-11-27
**Limpeza e Governança**
- Removidos arquivos .md temporários (README.md, MIGRATION_*.md, CORRECAO_*.md)
- Adicionadas regras explícitas no CLAUDE.md sobre uso exclusivo do CHANGELOG.md
- Consolidado histórico de migrações no CHANGELOG

## [6.6.3] até [6.5.5] - 2025-11-24 até 2025-11-16
**Correções e Refinamentos**
- Persistência de data de admissão do cônjuge
- Correção de NonUniqueResultException (registros duplicados)
- Correção de PRIMARY KEYs faltantes
- Select de tipo de documento do cônjuge
- Adicionadas validações CSRF
- Implementação de enriquecimento de dados

## [6.5.4] até [6.5.0] - 2025-11-16
**Melhorias no Módulo Pessoas**
- Implementado `buscarConjugePessoa()` - busca completa de dados do cônjuge
- Carregamento automático de dados no modo edição
- Melhorias na listagem (CPF/CNPJ, tipos por extenso)

---

## 📚 HISTÓRICO CONSOLIDADO (Versões Antigas)

## [6.4.1] - 2025-11-16
- Criado `CLAUDE.md` com diretrizes completas do projeto
- Renomeado template `new.html.twig` → `pessoa_form.html.twig`

## [6.4.0] - 2025-11-16
- Corrigido carregamento de tipos de pessoa ao buscar pessoa existente
- Corrigidos métodos de busca de documentos

## [6.3.0] - 2025-11-09
- Criado `PessoaService` (Fat Service) com toda lógica de negócio
- Refatorado `PessoaController` (Thin Controller)

## [6.2.0] - 2025-11-08
- Implementados módulos JS para dados múltiplos do cônjuge
- Implementado `pessoa_conjuge.js` e `pessoa_modals.js`

## [6.1.0] - 2025-11-07
- Implementadas rotas DELETE para dados múltiplos
- Implementados módulos JS para DELETE
- Token CSRF `ajax_global` padronizado

## [6.0.0] - 2025-11-06
- Busca inteligente de pessoa no formulário
- Sistema de tipos múltiplos
- FormTypes para cada tipo de pessoa

## [5.0.0] - 2025-11-05
- Implementação inicial do módulo de Pessoas
- 13 entidades Doctrine
- Repositórios + CRUD básico
- Configuração PostgreSQL + Webpack Encore + Bootstrap 5

---

## 📝 REFERÊNCIA HISTÓRICA - Migrações Críticas

### ✅ Migração: User → Users
- Corrigida inconsistência entre entity singular e tabela plural
- Atualizados: security.yaml, UserRepository, controllers, Twig extensions

### ✅ Migração: Pessoa → Pessoas
- Corrigidas referências usando singular quando entity é plural
- Atualizados: 15 arquivos principais (controllers, forms, scripts)

### ✅ Correção: Controle de Tema (isThemeLight)
- Implementado método `isThemeLight()` em entity Pessoas
- Integrado com ThemeController e template base

---

## ⚠️ REGRAS IMPORTANTES

### Para Claude Code e Desenvolvedores:

**SEMPRE** atualize este CHANGELOG.md imediatamente após qualquer mudança no código.

### Categorias de mudanças:
- **Adicionado** - novas funcionalidades
- **Alterado** - mudanças em funcionalidades existentes
- **Descontinuado** - funcionalidades a serem removidas
- **Removido** - funcionalidades removidas
- **Corrigido** - correção de bugs
- **Segurança** - vulnerabilidades corrigidas

### Sempre inclua:
- Data no formato YYYY-MM-DD
- Descrição clara e concisa
- Arquivos principais afetados (não precisa listar todos)
- Motivação (quando relevante)

### Versionamento Semântico:
- **MAJOR** (X.0.0): Mudanças incompatíveis na API
- **MINOR** (x.Y.0): Novas funcionalidades compatíveis
- **PATCH** (x.y.Z): Correções de bugs compatíveis

---

**Última atualização:** 2025-12-07
**Mantenedor:** Marcio Martins
**Desenvolvedor Ativo:** Claude 4.5 Sonnet (via Claude Code)