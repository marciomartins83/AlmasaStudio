# INVENTARIO DO SISTEMA ALMASASTUDIO

**Data:** 07/12/2025
**Versao:** 6.13.0
**Stack:** Symfony 7.2 + PHP 8.2 + PostgreSQL 15

---

## 1. RESUMO EXECUTIVO

| Categoria | Quantidade |
|-----------|------------|
| Controllers | 43 |
| Entities | 82 |
| Services | 15 |
| Repositories | 51 |
| Templates | 151 |
| Commands | 2 |
| Rotas | ~200 |

---

## 2. MODULOS IMPLEMENTADOS

### 2.1 PESSOAS (Cadastro Unificado)

**Status:** ✅ COMPLETO

**Controller:** `PessoaController.php` (33.930 bytes)

**Rotas:**
- `GET /pessoa/` - Listagem
- `GET|POST /pessoa/new` - Novo cadastro
- `GET|POST /pessoa/{id}/edit` - Edição
- `GET /pessoa/{id}` - Visualização
- `POST /pessoa/{id}` - Exclusão
- `POST /pessoa/_subform` - Carrega subformulário de tipo
- `POST /pessoa/search-pessoa-advanced` - Busca avançada
- `DELETE /pessoa/endereco/{id}` - Remove endereço
- `DELETE /pessoa/telefone/{id}` - Remove telefone
- `DELETE /pessoa/email/{id}` - Remove email
- `DELETE /pessoa/chave-pix/{id}` - Remove PIX
- `DELETE /pessoa/documento/{id}` - Remove documento
- `DELETE /pessoa/profissao/{id}` - Remove profissão
- `DELETE /pessoa/conta-bancaria/{id}` - Remove conta

**Entities relacionadas:**
- `Pessoas.php` - Entidade principal
- `PessoasFiadores.php` - Tipo Fiador
- `PessoasLocadores.php` - Tipo Locador
- `PessoasContratantes.php` - Tipo Contratante
- `PessoasCorretores.php` - Tipo Corretor
- `PessoasCorretoras.php` - Tipo Corretora (PJ)
- `PessoasPretendentes.php` - Tipo Pretendente
- `PessoasAdvogados.php` - Tipo Advogado
- `PessoasSocios.php` - Tipo Sócio
- `PessoasDocumentos.php` - Documentos
- `PessoasTelefones.php` - Telefones
- `PessoasEmails.php` - Emails
- `PessoasProfissoes.php` - Profissões
- `RelacionamentosFamiliares.php` - Cônjuge

**Service:** `PessoaService.php` (76.300 bytes)

**Templates:**
- `pessoa/index.html.twig`
- `pessoa/pessoa_form.html.twig` (new/edit)
- `pessoa/show.html.twig`
- `pessoa/partials/*.html.twig` (9 partials para tipos)

---

### 2.2 IMOVEIS

**Status:** ✅ COMPLETO

**Controller:** `ImovelController.php`

**Rotas:**
- `GET /imovel/` - Listagem
- `GET|POST /imovel/new` - Novo
- `GET|POST /imovel/edit/{id}` - Edição
- `GET /imovel/buscar` - Busca
- `DELETE /imovel/foto/{id}` - Remove foto
- `DELETE /imovel/medidor/{id}` - Remove medidor
- `DELETE /imovel/propriedade/{idImovel}/{idPropriedade}` - Remove propriedade
- `GET /imovel/propriedades/catalogo` - Catálogo de propriedades

**Entities:**
- `Imoveis.php` (30.523 bytes - 63 campos)
- `ImoveisContratos.php` - Contratos
- `ImoveisFotos.php` - Fotos
- `ImoveisGarantias.php` - Garantias
- `ImoveisMedidores.php` - Medidores
- `ImoveisPropriedades.php` - Propriedades

**Service:** `ImovelService.php` (18.977 bytes)

**Templates:**
- `imovel/index.html.twig`
- `imovel/new.html.twig`
- `imovel/edit.html.twig`

---

### 2.3 CONTRATOS

**Status:** ✅ COMPLETO

**Controller:** `ContratoController.php`

**Rotas:**
- `GET /contrato/` - Listagem
- `GET /contrato/show/{id}` - Detalhes
- `GET|POST /contrato/new` - Novo
- `GET|POST /contrato/edit/{id}` - Edição
- `POST /contrato/encerrar/{id}` - Encerrar
- `POST /contrato/renovar/{id}` - Renovar
- `GET /contrato/vencimento-proximo` - Vencimento próximo
- `GET /contrato/para-reajuste` - Para reajuste
- `GET /contrato/estatisticas` - Estatísticas
- `GET /contrato/imoveis-disponiveis` - Imóveis disponíveis

**Entities:**
- `ImoveisContratos.php` (14.631 bytes)
- `ContratosCobrancas.php` - Cobranças
- `ContratosItensCobranca.php` - Itens de cobrança

**Service:** `ContratoService.php` (23.131 bytes)

---

### 2.4 FICHA FINANCEIRA / CONTAS A RECEBER

**Status:** ✅ COMPLETO

**Controller:** `FichaFinanceiraController.php`

**Rotas:**
- `GET /financeiro/` - Listagem geral
- `GET /financeiro/ficha/{inquilinoId}` - Ficha do inquilino
- `GET|POST /financeiro/lancamento/new` - Novo lançamento
- `GET|POST /financeiro/lancamento/{id}/edit` - Editar lançamento
- `GET /financeiro/lancamento/{id}` - Detalhes
- `POST /financeiro/lancamento/{id}/baixa` - Baixa
- `POST /financeiro/baixa/{id}/estornar` - Estornar
- `POST /financeiro/lancamento/{id}/cancelar` - Cancelar
- `POST /financeiro/gerar-lancamentos` - Gerar automático
- `GET /financeiro/em-atraso` - Inadimplentes
- `GET /financeiro/api/lancamentos` - API Lista
- `GET /financeiro/api/estatisticas` - API Stats
- `GET /financeiro/api/ficha/{inquilinoId}` - API Ficha
- `GET /financeiro/api/baixas-recentes` - API Baixas

**Entities:**
- `LancamentosFinanceiros.php` (22.697 bytes)
- `BaixasFinanceiras.php` - Baixas
- `AcordosFinanceiros.php` - Acordos
- `PlanoContas.php` - Plano de contas
- `Lancamentos.php` - Lançamentos

**Service:** `FichaFinanceiraService.php` (25.534 bytes)

**Templates:**
- `financeiro/index.html.twig`
- `financeiro/ficha.html.twig`
- `financeiro/lancamento_form.html.twig`
- `financeiro/lancamento_show.html.twig`
- `financeiro/em_atraso.html.twig`

---

### 2.5 BOLETOS (API Santander)

**Status:** ✅ COMPLETO

**Controller:** `BoletoController.php`

**Rotas:**
- `GET /boleto/` - Listagem
- `GET /boleto/{id}` - Detalhes
- `GET|POST /boleto/new` - Novo
- `POST /boleto/{id}/registrar` - Registrar via API
- `POST /boleto/{id}/consultar` - Consultar status
- `POST /boleto/{id}/baixar` - Baixar/cancelar
- `DELETE /boleto/{id}` - Excluir
- `GET /boleto/{id}/imprimir` - Impressão
- `GET /boleto/{id}/segunda-via` - Segunda via
- `POST /boleto/registrar-lote` - Registrar lote
- `POST /boleto/consultar-lote` - Consultar lote
- `GET /boleto/api/estatisticas` - Estatísticas

**Entities:**
- `Boletos.php` (20.407 bytes)
- `BoletosLogApi.php` - Log de API

**Services:**
- `BoletoSantanderService.php` (37.052 bytes)
- `SantanderAuthService.php` (12.739 bytes)

**Templates:**
- `boleto/index.html.twig`
- `boleto/show.html.twig`
- `boleto/new.html.twig`
- `boleto/_imprimir.html.twig`

---

### 2.6 COBRANCA AUTOMATICA

**Status:** ✅ COMPLETO (v6.13.0)

**Controller:** `CobrancaController.php`

**Rotas:**
- `GET /cobranca/` - Listagem
- `GET /cobranca/pendentes` - Pendentes
- `GET /cobranca/{id}` - Detalhes
- `POST /cobranca/{id}/enviar` - Enviar individual
- `POST /cobranca/enviar-lote` - Enviar lote
- `POST /cobranca/{id}/cancelar` - Cancelar
- `POST /cobranca/gerar-preview` - Preview
- `GET /cobranca/api/estatisticas` - Estatísticas

**Entities:**
- `ContratosCobrancas.php` (14.434 bytes)
- `ContratosItensCobranca.php` (6.646 bytes)
- `EmailsEnviados.php` (6.895 bytes)

**Services:**
- `CobrancaContratoService.php` (20.423 bytes)
- `EmailService.php` (11.663 bytes)

**Command:** `app:enviar-boletos-automatico`

**Templates:**
- `cobranca/pendentes.html.twig`
- `cobranca/show.html.twig`
- `emails/boleto_cobranca.html.twig`
- `emails/lembrete_vencimento.html.twig`

---

### 2.7 INFORME DE RENDIMENTOS / DIMOB

**Status:** ✅ COMPLETO

**Controller:** `InformeRendimentoController.php`

**Rotas:**
- `GET /informe-rendimento/` - Dashboard
- `POST /informe-rendimento/processar` - Processar
- `GET /informe-rendimento/manutencao` - Manutenção
- `PUT /informe-rendimento/informe/{id}` - Atualizar
- `GET /informe-rendimento/impressao` - Impressão
- `GET /informe-rendimento/dimob` - Configuração DIMOB
- `POST /informe-rendimento/dimob` - Salvar DIMOB
- `GET /informe-rendimento/dimob/gerar` - Gerar arquivo DIMOB

**Entities:**
- `InformesRendimentos.php` (7.381 bytes)
- `InformesRendimentosValores.php` (3.320 bytes)
- `DimobConfiguracoes.php` (4.703 bytes)

**Service:** `InformeRendimentoService.php` (18.412 bytes)

---

### 2.8 CONFIGURACAO API BANCARIA

**Status:** ✅ COMPLETO

**Controller:** `ConfiguracaoApiBancoController.php`

**Rotas:**
- `GET /configuracao-api-banco/` - Listagem
- `GET|POST /configuracao-api-banco/new` - Nova
- `GET|POST /configuracao-api-banco/{id}/edit` - Editar
- `DELETE /configuracao-api-banco/{id}` - Excluir
- `POST /configuracao-api-banco/{id}/testar-conexao` - Testar
- `GET /configuracao-api-banco/api/contas-por-banco/{bancoId}` - Contas

**Entities:**
- `ConfiguracoesApiBanco.php` (8.693 bytes)
- `ConfiguracoesCobranca.php` (4.148 bytes)

**Service:** `ConfiguracaoApiBancoService.php` (9.350 bytes)

---

### 2.9 CADASTROS AUXILIARES

**Status:** ✅ COMPLETO

| Módulo | Controller | Templates | Status |
|--------|------------|-----------|--------|
| Estados | EstadoController | CRUD completo | ✅ |
| Cidades | CidadeController | CRUD completo | ✅ |
| Bairros | BairroController | CRUD completo | ✅ |
| Logradouros | LogradouroController | CRUD completo | ✅ |
| Agências | AgenciaController | CRUD completo | ✅ |
| Contas Bancárias | ContaBancariaController | CRUD completo | ✅ |
| Emails | EmailController | CRUD completo | ✅ |
| Telefones | TelefoneController | CRUD completo | ✅ |
| Nacionalidades | NacionalidadeController | CRUD completo | ✅ |
| Naturalidades | NaturalidadeController | CRUD completo | ✅ |
| Estado Civil | EstadoCivilController | CRUD completo | ✅ |
| Tipo Documento | TipoDocumentoController | CRUD completo | ✅ |
| Tipo Telefone | TipoTelefoneController | CRUD completo | ✅ |
| Tipo Email | TipoEmailController | CRUD completo | ✅ |
| Tipo Endereço | TipoEnderecoController | CRUD completo | ✅ |
| Tipo Imóvel | TipoImovelController | CRUD completo | ✅ |
| Tipo Pessoa | TipoPessoaController | CRUD completo | ✅ |
| Tipo Chave PIX | TipoChavePixController | CRUD completo | ✅ |
| Tipo Conta Bancária | TipoContaBancariaController | CRUD completo | ✅ |
| Tipo Carteira | TipoCarteiraController | CRUD completo | ✅ |
| Tipo Remessa | TipoRemessaController | CRUD completo | ✅ |
| Tipo Atendimento | TipoAtendimentoController | CRUD completo | ✅ |

---

## 3. COMMANDS DISPONIVEIS

| Command | Descrição | Status |
|---------|-----------|--------|
| `app:create-admin` | Cria usuário administrador | ✅ Ativo |
| `app:enviar-boletos-automatico` | Envio automático de boletos | ✅ Ativo (cron desativado) |

**Cron configurado:** `cron/cobranca_automatica.cron`
```bash
# Desativado - remova o # para ativar
#0 7 * * * cd /path && php bin/console app:enviar-boletos-automatico >> var/log/cobranca_automatica.log 2>&1
```

---

## 4. INTEGRACOES

| Integração | Status | Detalhes |
|------------|--------|----------|
| API Santander (Boletos) | ✅ Implementado | Registro, consulta, baixa |
| Busca CEP (ViaCEP) | ✅ Implementado | Via API externa |
| Email (Symfony Mailer) | ✅ Implementado | Configurável no .env |

---

## 5. ENTIDADES POR CATEGORIA

### Pessoas (14 entities)
- Pessoas, PessoasFiadores, PessoasLocadores, PessoasContratantes
- PessoasCorretores, PessoasCorretoras, PessoasPretendentes
- PessoasAdvogados, PessoasSocios, PessoasDocumentos
- PessoasTelefones, PessoasEmails, PessoasProfissoes
- RelacionamentosFamiliares

### Imóveis (6 entities)
- Imoveis, ImoveisContratos, ImoveisFotos
- ImoveisGarantias, ImoveisMedidores, ImoveisPropriedades

### Financeiro (8 entities)
- LancamentosFinanceiros, BaixasFinanceiras, AcordosFinanceiros
- Boletos, BoletosLogApi, ContratosCobrancas
- ContratosItensCobranca, EmailsEnviados

### Configurações (4 entities)
- ConfiguracoesApiBanco, ConfiguracoesCobranca
- DimobConfiguracoes, PlanoContas

### Endereços (5 entities)
- Estados, Cidades, Bairros, Logradouros, Enderecos

### Bancos (4 entities)
- Bancos, Agencias, ContasBancarias, ContasVinculadas

### Tipos (14 entities)
- TiposDocumentos, TiposTelefones, TiposEmails
- TiposEnderecos, TiposImoveis, TiposPessoas
- TiposChavesPix, TiposContasBancarias, TiposCarteiras
- TiposRemessa, TiposAtendimento, EstadoCivil
- Nacionalidade, Naturalidade

### Outros (27 entities)
- Users, Roles, Permissions, Sessions
- Telefones, Emails, ChavesPix, Profissoes
- Condominios, FiadoresInquilinos, FormasRetirada
- InformesRendimentos, InformesRendimentosValores
- Lancamentos, LayoutsRemessa, PropriedadesCatalogo
- RazoesConta, RegimesCasamento, RequisicoesResponsaveis
- FailedJobs, PersonalAccessTokens, PasswordResetTokens
- ModelHasPermissions, ModelHasRoles, RoleHasPermissions

---

## 6. SERVICES

| Service | Linhas | Função |
|---------|--------|--------|
| PessoaService | 76.300 | Lógica completa de pessoas |
| BoletoSantanderService | 37.052 | Integração API Santander |
| FichaFinanceiraService | 25.534 | Lançamentos e baixas |
| ContratoService | 23.131 | Gestão de contratos |
| CobrancaContratoService | 20.423 | Cobrança automática |
| ImovelService | 18.977 | Gestão de imóveis |
| InformeRendimentoService | 18.412 | DIMOB e informes |
| SantanderAuthService | 12.739 | Autenticação Santander |
| EmailService | 11.663 | Envio de emails |
| ConfiguracaoApiBancoService | 9.350 | Config API bancária |
| CepService | 3.714 | Busca de CEP |
| NacionalidadeService | 1.659 | CRUD nacionalidade |
| NaturalidadeService | 1.641 | CRUD naturalidade |
| ProfissaoService | 1.317 | CRUD profissão |

---

## 7. O QUE NAO EXISTE (ainda)

| Funcionalidade | Status |
|----------------|--------|
| Módulo Jurídico (processos, follow-up) | ❌ Não implementado |
| Relatório de Inadimplentes (PDF) | ⚠️ Parcial (tela existe, falta PDF) |
| Módulo de Seguros | ❌ Não implementado |
| Módulo de Manutenção/Obras | ❌ Não implementado |
| Módulo de Vistorias | ❌ Não implementado |
| App Mobile | ❌ Não implementado |
| Dashboard com gráficos | ⚠️ Parcial (básico) |
| Notificações Push | ❌ Não implementado |
| Integração WhatsApp | ❌ Não implementado |

---

## 8. ARQUIVOS DE CONFIGURACAO

| Arquivo | Função |
|---------|--------|
| `.env` | Variáveis de ambiente |
| `config/services.yaml` | Configuração de serviços |
| `webpack.config.js` | Build de assets (12 entries) |
| `CLAUDE.md` | Diretrizes para IA |
| `CHANGELOG.md` | Histórico de versões |
| `cron/cobranca_automatica.cron` | Configuração do cron |

---

## 9. ASSETS JAVASCRIPT

| Entry | Arquivo | Função |
|-------|---------|--------|
| app | assets/app.js | Principal |
| informe_rendimento | assets/js/informe_rendimento/*.js | DIMOB |
| financeiro | assets/js/financeiro/*.js | Ficha financeira |
| financeiro_form | assets/js/financeiro/*.js | Form financeiro |
| configuracao_api_banco | assets/js/configuracao_api_banco/*.js | Config API |
| boleto | assets/js/boleto/*.js | Listagem boletos |
| boleto_form | assets/js/boleto/*.js | Form boletos |
| cobranca | assets/js/cobranca/*.js | Cobrança automática |

---

**Gerado em:** 07/12/2025 17:45
**Por:** Claude Code (Opus 4.5)
