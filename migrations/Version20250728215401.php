<?php
declare(strict_types=1);
namespace DoctrineMigrations;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
final class Version20250728215401 extends AbstractMigration
{
public function up(Schema $schema): void
 {
// 1. Tabelas base do sistema (Laravel/autenticação)
$this->addSql('CREATE TABLE users (
 id BIGSERIAL PRIMARY KEY,
name VARCHAR(255) NOT NULL,
 email VARCHAR(255) UNIQUE NOT NULL,
 email_verified_at TIMESTAMP NULL,
password VARCHAR(255) NOT NULL,
 remember_token VARCHAR(100) NULL,
 current_team_id BIGINT NULL,
 profile_photo_path VARCHAR(2048) NULL,
 two_factor_secret TEXT NULL,
 two_factor_recovery_codes TEXT NULL,
 two_factor_confirmed_at TIMESTAMP NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
 );');
$this->addSql('CREATE TABLE password_reset_tokens (
 email VARCHAR(255) PRIMARY KEY,
 token VARCHAR(255) NOT NULL,
 created_at TIMESTAMP NULL
 );');
$this->addSql('CREATE TABLE failed_jobs (
 id BIGSERIAL PRIMARY KEY,
 uuid VARCHAR(255) UNIQUE NOT NULL,
connection TEXT NOT NULL,
queue TEXT NOT NULL,
 payload TEXT NOT NULL,
 exception TEXT NOT NULL,
 failed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
 );');
$this->addSql('CREATE TABLE personal_access_tokens (
 id BIGSERIAL PRIMARY KEY,
 tokenable_type VARCHAR(255) NOT NULL,
 tokenable_id BIGINT NOT NULL,
name VARCHAR(255) NOT NULL,
 token VARCHAR(64) UNIQUE NOT NULL,
 abilities TEXT NULL,
 last_used_at TIMESTAMP NULL,
 expires_at TIMESTAMP NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
 );');
$this->addSql('CREATE TABLE sessions (
 id VARCHAR(255) PRIMARY KEY,
 user_id BIGINT NULL,
 ip_address VARCHAR(45) NULL,
 user_agent TEXT NULL,
 payload TEXT NOT NULL,
 last_activity INTEGER NOT NULL
 );');
// 2. Tabelas de permissões e roles
$this->addSql('CREATE TABLE permissions (
 id BIGSERIAL PRIMARY KEY,
name VARCHAR(255) NOT NULL,
 guard_name VARCHAR(255) NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
UNIQUE(name, guard_name)
 );');
$this->addSql('CREATE TABLE roles (
 id BIGSERIAL PRIMARY KEY,
name VARCHAR(255) NOT NULL,
 guard_name VARCHAR(255) NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
UNIQUE(name, guard_name)
 );');
// 3. Tabelas de relacionamento de permissões (dependem de permissions e roles)
$this->addSql('CREATE TABLE model_has_permissions (
 permission_id BIGINT NOT NULL,
 model_type VARCHAR(255) NOT NULL,
 model_id BIGINT NOT NULL,
PRIMARY KEY (permission_id, model_id, model_type),
FOREIGN KEY (permission_id) REFERENCES permissions (id) ON DELETE CASCADE
 );');
$this->addSql('CREATE TABLE model_has_roles (
 role_id BIGINT NOT NULL,
 model_type VARCHAR(255) NOT NULL,
 model_id BIGINT NOT NULL,
PRIMARY KEY (role_id, model_id, model_type),
FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE CASCADE
 );');
$this->addSql('CREATE TABLE role_has_permissions (
 permission_id BIGINT NOT NULL,
 role_id BIGINT NOT NULL,
PRIMARY KEY (permission_id, role_id),
FOREIGN KEY (permission_id) REFERENCES permissions (id) ON DELETE CASCADE,
FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE CASCADE
 );');
// 4. Tabelas de localização (hierarquia: estado -> cidade -> bairro -> logradouro)
$this->addSql('CREATE TABLE estados (
 id BIGSERIAL PRIMARY KEY,
 uf CHAR(2) UNIQUE NOT NULL,
 nome VARCHAR(255) NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
 );');
$this->addSql('CREATE TABLE cidades (
 id BIGSERIAL PRIMARY KEY,
 id_estado BIGINT NOT NULL,
 nome VARCHAR(255) NOT NULL,
 codigo VARCHAR(15) NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (id_estado) REFERENCES estados (id) ON DELETE CASCADE
 );');
$this->addSql('CREATE TABLE bairros (
 id BIGSERIAL PRIMARY KEY,
 id_cidade BIGINT NOT NULL,
 nome VARCHAR(255) NOT NULL,
 codigo VARCHAR(15) NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (id_cidade) REFERENCES cidades (id) ON DELETE CASCADE
 );');
$this->addSql('CREATE TABLE logradouros (
 id BIGSERIAL PRIMARY KEY,
 id_bairro BIGINT NOT NULL,
 logradouro VARCHAR(255) NOT NULL,
 cep CHAR(8) NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (id_bairro) REFERENCES bairros (id) ON DELETE CASCADE
 );');
// 5. Tabelas bancárias base
$this->addSql('CREATE TABLE bancos (
 id BIGSERIAL PRIMARY KEY,
 nome VARCHAR(60) NOT NULL,
 numero INTEGER NOT NULL UNIQUE,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
 );');
// 6. Tabelas de tipos e configurações (sem dependências)
$this->addSql('CREATE TABLE tipos_carteiras (
 id BIGSERIAL PRIMARY KEY,
 tipo VARCHAR(60) NOT NULL,
 descricao VARCHAR(255) NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
 );');
$this->addSql('CREATE TABLE tipos_remessa (
 id BIGSERIAL PRIMARY KEY,
 tipo VARCHAR(60) NOT NULL,
 descricao VARCHAR(255) NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
 );');
$this->addSql('CREATE TABLE layouts_remessa (
 id BIGSERIAL PRIMARY KEY,
 layout VARCHAR(10) NOT NULL,
 descricao VARCHAR(255) NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
 );');
$this->addSql('CREATE TABLE razoes_conta (
 id BIGSERIAL PRIMARY KEY,
 razao VARCHAR(60) NOT NULL,
 descricao VARCHAR(255) NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
 );');
$this->addSql('CREATE TABLE tipos_documentos (
 id BIGSERIAL PRIMARY KEY,
 tipo VARCHAR(60) NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
 );');
$this->addSql('CREATE TABLE tipos_telefones (
 id BIGSERIAL PRIMARY KEY,
 tipo VARCHAR(60) NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
 );');
$this->addSql('CREATE TABLE tipos_chaves_pix (
 id BIGSERIAL PRIMARY KEY,
 tipo VARCHAR(60) NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
 );');
$this->addSql('CREATE TABLE tipos_contas_bancarias (
 id BIGSERIAL PRIMARY KEY,
 tipo VARCHAR(60) NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
 );');
$this->addSql('CREATE TABLE tipos_enderecos (
 id BIGSERIAL PRIMARY KEY,
 tipo VARCHAR(60) NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
 );');
$this->addSql('CREATE TABLE tipos_emails (
 id BIGSERIAL PRIMARY KEY,
 tipo VARCHAR(60) NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
 );');
$this->addSql('CREATE TABLE tipos_pessoas (
 id BIGSERIAL PRIMARY KEY,
 tipo VARCHAR(60) NOT NULL,
 descricao VARCHAR(255) NULL,
 ativo BOOLEAN NOT NULL DEFAULT TRUE,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
 );');
$this->addSql('CREATE TABLE profissoes (
 id BIGSERIAL PRIMARY KEY,
 nome VARCHAR(100) NOT NULL,
 descricao VARCHAR(255) NULL,
 ativo BOOLEAN NOT NULL DEFAULT TRUE,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
 );');
$this->addSql('CREATE TABLE tipos_imoveis (
 id BIGSERIAL PRIMARY KEY,
 tipo VARCHAR(60) NOT NULL,
 descricao VARCHAR(255) NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
 );');
$this->addSql('CREATE TABLE tipos_atendimento (
 id BIGSERIAL PRIMARY KEY,
 tipo VARCHAR(60) NOT NULL,
 descricao VARCHAR(255) NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
 );');
$this->addSql('CREATE TABLE formas_retirada (
 id BIGSERIAL PRIMARY KEY,
 forma VARCHAR(60) NOT NULL,
 descricao VARCHAR(255) NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
 );');
$this->addSql('CREATE TABLE regimes_casamento (
 id BIGSERIAL PRIMARY KEY,
 regime VARCHAR(60) NOT NULL,
 descricao VARCHAR(255) NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
 );');
// 7. Tabela central pessoas
$this->addSql('CREATE TABLE pessoas (
 id BIGSERIAL PRIMARY KEY,
 nome VARCHAR(255) NOT NULL,
 dt_cadastro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 tipo_pessoa INTEGER NOT NULL,
status BOOLEAN NOT NULL DEFAULT TRUE,
 fisica_juridica VARCHAR(10) NOT NULL,
 data_nascimento DATE NULL,
 estado_civil VARCHAR(20) NULL,
 nacionalidade VARCHAR(50) NULL,
 naturalidade VARCHAR(50) NULL,
 nome_pai VARCHAR(100) NULL,
 nome_mae VARCHAR(100) NULL,
 renda DECIMAL(15,2) NULL,
 observacoes TEXT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
 );');
// 7.1. Adicionando CHECK constraints para os campos que eram enum
$this->addSql('ALTER TABLE pessoas ADD CONSTRAINT check_fisica_juridica CHECK (fisica_juridica IN (\'fisica\', \'juridica\'));');
$this->addSql('ALTER TABLE pessoas ADD CONSTRAINT check_estado_civil CHECK (estado_civil IN (\'solteiro\', \'casado\', \'divorciado\', \'viuvo\', \'separado\', \'uniao_estavel\', \'outro\'));');
// 8. Tabelas de contato base (dependem de tipos)
$this->addSql('CREATE TABLE emails (
 id BIGSERIAL PRIMARY KEY,
 email VARCHAR(255) NOT NULL,
 id_tipo BIGINT NOT NULL,
 descricao VARCHAR(255) NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (id_tipo) REFERENCES tipos_emails (id) ON DELETE RESTRICT
 );');
$this->addSql('CREATE TABLE telefones (
 id BIGSERIAL PRIMARY KEY,
 id_tipo BIGINT NOT NULL,
 numero VARCHAR(30) NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (id_tipo) REFERENCES tipos_telefones (id) ON DELETE RESTRICT
 );');
// 9. Endereços (dependem de pessoas, logradouros e tipos_enderecos)
$this->addSql('CREATE TABLE enderecos (
 id BIGSERIAL PRIMARY KEY,
 id_pessoa BIGINT NOT NULL,
 id_logradouro BIGINT NOT NULL,
 id_tipo BIGINT NOT NULL,
 end_numero INTEGER NOT NULL,
 complemento VARCHAR(255) NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (id_pessoa) REFERENCES pessoas (id) ON DELETE CASCADE,
FOREIGN KEY (id_logradouro) REFERENCES logradouros (id) ON DELETE RESTRICT,
FOREIGN KEY (id_tipo) REFERENCES tipos_enderecos (id) ON DELETE RESTRICT
 );');
// 10. Agências (dependem de bancos e enderecos)
$this->addSql('CREATE TABLE agencias (
 id BIGSERIAL PRIMARY KEY,
 codigo VARCHAR(10) NOT NULL,
 id_banco BIGINT NOT NULL,
 nome VARCHAR(50) NOT NULL,
 id_endereco BIGINT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (id_banco) REFERENCES bancos (id) ON DELETE RESTRICT,
FOREIGN KEY (id_endereco) REFERENCES enderecos (id) ON DELETE SET NULL,
UNIQUE(codigo, id_banco)
 );');
// 11. Tabelas de relacionamento pessoa-contato
$this->addSql('CREATE TABLE pessoas_telefones (
 id BIGSERIAL PRIMARY KEY,
 id_pessoa BIGINT NOT NULL,
 id_telefone BIGINT NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (id_pessoa) REFERENCES pessoas (id) ON DELETE CASCADE,
FOREIGN KEY (id_telefone) REFERENCES telefones (id) ON DELETE CASCADE,
UNIQUE(id_pessoa, id_telefone)
 );');
$this->addSql('CREATE TABLE pessoas_emails (
 id BIGSERIAL PRIMARY KEY,
 id_pessoa BIGINT NOT NULL,
 id_email BIGINT NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (id_pessoa) REFERENCES pessoas (id) ON DELETE CASCADE,
FOREIGN KEY (id_email) REFERENCES emails (id) ON DELETE CASCADE,
UNIQUE(id_pessoa, id_email)
 );');
// 12. Outras tabelas de relacionamento pessoa-dados
$this->addSql('CREATE TABLE pessoas_tipos (
 id BIGSERIAL PRIMARY KEY,
 id_pessoa BIGINT NOT NULL,
 id_tipo_pessoa BIGINT NOT NULL,
 data_inicio DATE NOT NULL DEFAULT CURRENT_DATE,
 data_fim DATE NULL,
 ativo BOOLEAN NOT NULL DEFAULT TRUE,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (id_pessoa) REFERENCES pessoas (id) ON DELETE CASCADE,
FOREIGN KEY (id_tipo_pessoa) REFERENCES tipos_pessoas (id) ON DELETE RESTRICT,
UNIQUE(id_pessoa, id_tipo_pessoa, data_inicio)
 );');
$this->addSql('CREATE TABLE pessoas_documentos (
 id BIGSERIAL PRIMARY KEY,
 id_pessoa BIGINT NOT NULL,
 id_tipo_documento BIGINT NOT NULL,
 numero_documento VARCHAR(50) NOT NULL,
 data_emissao DATE NULL,
 data_vencimento DATE NULL,
 orgao_emissor VARCHAR(100) NULL,
 observacoes TEXT NULL,
 ativo BOOLEAN NOT NULL DEFAULT TRUE,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (id_pessoa) REFERENCES pessoas (id) ON DELETE CASCADE,
FOREIGN KEY (id_tipo_documento) REFERENCES tipos_documentos (id) ON DELETE RESTRICT,
UNIQUE(id_pessoa, id_tipo_documento, numero_documento)
 );');
$this->addSql('CREATE TABLE pessoas_profissoes (
 id BIGSERIAL PRIMARY KEY,
 id_pessoa BIGINT NOT NULL,
 id_profissao BIGINT NOT NULL,
 empresa VARCHAR(100) NULL,
 data_admissao DATE NULL,
 data_demissao DATE NULL,
 renda DECIMAL(15,2) NULL,
 ativo BOOLEAN NOT NULL DEFAULT TRUE,
 observacoes TEXT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (id_pessoa) REFERENCES pessoas (id) ON DELETE CASCADE,
FOREIGN KEY (id_profissao) REFERENCES profissoes (id) ON DELETE RESTRICT
 );');
// 13. Relacionamentos familiares
$this->addSql('CREATE TABLE relacionamentos_familiares (
 id BIGSERIAL PRIMARY KEY,
 id_pessoa_origem BIGINT NOT NULL,
 id_pessoa_destino BIGINT NOT NULL,
 tipo_relacionamento VARCHAR(30) NOT NULL,
 id_regime_casamento BIGINT NULL,
 data_inicio DATE NULL,
 data_fim DATE NULL,
 ativo BOOLEAN NOT NULL DEFAULT TRUE,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (id_pessoa_origem) REFERENCES pessoas (id) ON DELETE CASCADE,
FOREIGN KEY (id_pessoa_destino) REFERENCES pessoas (id) ON DELETE CASCADE,
FOREIGN KEY (id_regime_casamento) REFERENCES regimes_casamento (id) ON DELETE SET NULL,
UNIQUE(id_pessoa_origem, id_pessoa_destino, tipo_relacionamento)
 );');
// 14. Contas bancárias (dependem de várias tabelas, incluindo pessoas_documentos)
$this->addSql('CREATE TABLE contas_bancarias (
 id BIGSERIAL PRIMARY KEY,
 id_pessoa BIGINT NOT NULL,
 id_banco BIGINT NOT NULL,
 id_agencia BIGINT NULL,
 codigo VARCHAR(15) NOT NULL,
 digito_conta VARCHAR(2) NULL,
 id_tipo_conta BIGINT NOT NULL,
 id_razao_conta BIGINT NULL,
 titular VARCHAR(100) NULL,
 id_documento_titular BIGINT NULL,
 principal BOOLEAN NOT NULL DEFAULT FALSE,
 ativo BOOLEAN NOT NULL DEFAULT TRUE,
 codigo_cedente DECIMAL(15,0) NULL,
 id_tipo_carteira BIGINT NULL,
 numero_inicial INTEGER NULL,
 numero_final INTEGER NULL,
 numero_usado INTEGER NULL,
 registrada BOOLEAN NOT NULL DEFAULT FALSE,
 aceita_multipag BOOLEAN NOT NULL DEFAULT FALSE,
 convenio_sicredi VARCHAR(20) NULL,
 id_tipo_remessa BIGINT NULL,
 id_layout_remessa BIGINT NULL,
 usa_endereco_cobranca BOOLEAN NOT NULL DEFAULT FALSE,
 cobranca_compartilhada BOOLEAN NOT NULL DEFAULT FALSE,
 descricao VARCHAR(100) NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (id_pessoa) REFERENCES pessoas (id) ON DELETE CASCADE,
FOREIGN KEY (id_banco) REFERENCES bancos (id) ON DELETE RESTRICT,
FOREIGN KEY (id_agencia) REFERENCES agencias (id) ON DELETE SET NULL,
FOREIGN KEY (id_tipo_conta) REFERENCES tipos_contas_bancarias (id) ON DELETE RESTRICT,
FOREIGN KEY (id_razao_conta) REFERENCES razoes_conta (id) ON DELETE SET NULL,
FOREIGN KEY (id_documento_titular) REFERENCES pessoas_documentos (id) ON DELETE SET NULL,
FOREIGN KEY (id_tipo_carteira) REFERENCES tipos_carteiras (id) ON DELETE SET NULL,
FOREIGN KEY (id_tipo_remessa) REFERENCES tipos_remessa (id) ON DELETE SET NULL,
FOREIGN KEY (id_layout_remessa) REFERENCES layouts_remessa (id) ON DELETE SET NULL
 );');
// 15. Tabelas que dependem de contas bancárias
$this->addSql('CREATE TABLE configuracoes_cobranca (
 id BIGSERIAL PRIMARY KEY,
 id_conta_bancaria BIGINT NOT NULL,
 carencia INTEGER NOT NULL DEFAULT 0,
 carencia_cartorio INTEGER NOT NULL DEFAULT 0,
 carencia_advocacia INTEGER NOT NULL DEFAULT 0,
 multa_itau BOOLEAN NOT NULL DEFAULT FALSE,
 mora_diaria BOOLEAN NOT NULL DEFAULT FALSE,
 protesto INTEGER NOT NULL DEFAULT 0,
 tipo_dias_protesto INTEGER NOT NULL DEFAULT 0,
 dias_protesto INTEGER NOT NULL DEFAULT 0,
 nao_gerar_judicial BOOLEAN NOT NULL DEFAULT FALSE,
 tipo_arquivo INTEGER NOT NULL DEFAULT 0,
 variacao_bb BOOLEAN NOT NULL DEFAULT FALSE,
 mudar_especie BOOLEAN NOT NULL DEFAULT FALSE,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (id_conta_bancaria) REFERENCES contas_bancarias (id) ON DELETE CASCADE,
UNIQUE(id_conta_bancaria)
 );');
$this->addSql('CREATE TABLE contas_vinculadas (
 id BIGSERIAL PRIMARY KEY,
 id_conta_principal BIGINT NOT NULL,
 id_conta_vinculada BIGINT NOT NULL,
 tipo_vinculo VARCHAR(30) NOT NULL,
 ativo BOOLEAN NOT NULL DEFAULT TRUE,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (id_conta_principal) REFERENCES contas_bancarias (id) ON DELETE CASCADE,
FOREIGN KEY (id_conta_vinculada) REFERENCES contas_bancarias (id) ON DELETE CASCADE,
UNIQUE(id_conta_principal, id_conta_vinculada, tipo_vinculo)
 );');
$this->addSql('CREATE TABLE chaves_pix (
 id BIGSERIAL PRIMARY KEY,
 id_pessoa BIGINT NOT NULL,
 id_tipo_chave BIGINT NOT NULL,
 chave_pix VARCHAR(100) NOT NULL,
 id_conta_bancaria BIGINT NULL,
 principal BOOLEAN NOT NULL DEFAULT FALSE,
 ativo BOOLEAN NOT NULL DEFAULT TRUE,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (id_pessoa) REFERENCES pessoas (id) ON DELETE CASCADE,
FOREIGN KEY (id_tipo_chave) REFERENCES tipos_chaves_pix (id) ON DELETE RESTRICT,
FOREIGN KEY (id_conta_bancaria) REFERENCES contas_bancarias (id) ON DELETE SET NULL,
UNIQUE(chave_pix, id_tipo_chave)
 );');
// 16. Tabelas de especialização de pessoas
$this->addSql('CREATE TABLE pessoas_contratantes (
 id BIGSERIAL PRIMARY KEY,
 id_pessoa BIGINT NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (id_pessoa) REFERENCES pessoas (id) ON DELETE CASCADE,
UNIQUE(id_pessoa)
 );');
$this->addSql('CREATE TABLE pessoas_corretores (
 id BIGSERIAL PRIMARY KEY,
 id_pessoa BIGINT NOT NULL,
 creci VARCHAR(20) NULL,
 usuario VARCHAR(50) NULL,
status VARCHAR(30) NULL,
 data_cadastro DATE NULL,
 ativo BOOLEAN NOT NULL DEFAULT TRUE,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (id_pessoa) REFERENCES pessoas (id) ON DELETE CASCADE,
UNIQUE(id_pessoa),
UNIQUE(creci)
 );');
$this->addSql('CREATE TABLE pessoas_fiadores (
 id BIGSERIAL PRIMARY KEY,
 id_pessoa BIGINT NOT NULL,
 id_conjuge BIGINT NULL,
 motivo_fianca TEXT NULL,
 ja_foi_fiador BOOLEAN NOT NULL DEFAULT FALSE,
 conjuge_trabalha BOOLEAN NOT NULL DEFAULT FALSE,
 outros TEXT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (id_pessoa) REFERENCES pessoas (id) ON DELETE CASCADE,
FOREIGN KEY (id_conjuge) REFERENCES pessoas (id) ON DELETE SET NULL,
UNIQUE(id_pessoa)
 );');
$this->addSql('CREATE TABLE pessoas_locadores (
 id BIGSERIAL PRIMARY KEY,
 id_pessoa BIGINT NOT NULL,
 id_forma_retirada BIGINT NULL,
 dependentes INTEGER NOT NULL DEFAULT 0,
 dia_retirada INTEGER NULL,
 cobrar_cpmf BOOLEAN NOT NULL DEFAULT FALSE,
 situacao INTEGER NOT NULL DEFAULT 0,
 codigo_contabil INTEGER NULL,
 etiqueta BOOLEAN NOT NULL DEFAULT TRUE,
 cobrar_tarifa_rec BOOLEAN NOT NULL DEFAULT FALSE,
 data_fechamento DATE NULL,
 carencia INTEGER NOT NULL DEFAULT 0,
 multa_itau BOOLEAN NOT NULL DEFAULT FALSE,
 mora_diaria BOOLEAN NOT NULL DEFAULT FALSE,
 protesto INTEGER NOT NULL DEFAULT 0,
 dias_protesto INTEGER NOT NULL DEFAULT 0,
 nao_gerar_judicial BOOLEAN NOT NULL DEFAULT FALSE,
 endereco_cobranca BOOLEAN NOT NULL DEFAULT FALSE,
 condominio_conta BOOLEAN NOT NULL DEFAULT FALSE,
 ext_email BOOLEAN NOT NULL DEFAULT FALSE,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (id_pessoa) REFERENCES pessoas (id) ON DELETE CASCADE,
FOREIGN KEY (id_forma_retirada) REFERENCES formas_retirada (id) ON DELETE SET NULL,
UNIQUE(id_pessoa)
 );');
$this->addSql('CREATE TABLE pessoas_pretendentes (
 id BIGSERIAL PRIMARY KEY,
 id_pessoa BIGINT NOT NULL,
 id_tipo_imovel BIGINT NULL,
 quartos_desejados INTEGER NULL,
 aluguel_maximo DECIMAL(15,2) NULL,
 id_logradouro_desejado BIGINT NULL,
 disponivel BOOLEAN NOT NULL DEFAULT TRUE,
 procura_aluguel BOOLEAN NOT NULL DEFAULT FALSE,
 procura_compra BOOLEAN NOT NULL DEFAULT FALSE,
 id_atendente BIGINT NULL,
 id_tipo_atendimento BIGINT NULL,
 data_cadastro DATE NULL,
 observacoes TEXT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (id_pessoa) REFERENCES pessoas (id) ON DELETE CASCADE,
FOREIGN KEY (id_tipo_imovel) REFERENCES tipos_imoveis (id) ON DELETE SET NULL,
FOREIGN KEY (id_logradouro_desejado) REFERENCES logradouros (id) ON DELETE SET NULL,
FOREIGN KEY (id_atendente) REFERENCES users (id) ON DELETE SET NULL,
FOREIGN KEY (id_tipo_atendimento) REFERENCES tipos_atendimento (id) ON DELETE SET NULL,
UNIQUE(id_pessoa)
 );');
$this->addSql('CREATE TABLE pessoas_corretoras (
 id BIGSERIAL PRIMARY KEY,
 id_pessoa BIGINT NOT NULL,
 contato_nome VARCHAR(100) NULL,
 contato_telefone VARCHAR(30) NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (id_pessoa) REFERENCES pessoas (id) ON DELETE CASCADE,
UNIQUE(id_pessoa)
 );');
// 17. Tabelas de relacionamento entre pessoas
$this->addSql('CREATE TABLE fiadores_inquilinos (
 id BIGSERIAL PRIMARY KEY,
 id_fiador BIGINT NOT NULL,
 id_inquilino BIGINT NOT NULL,
 data_inicio DATE NOT NULL,
 data_fim DATE NULL,
 ativo BOOLEAN NOT NULL DEFAULT TRUE,
 observacoes TEXT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (id_fiador) REFERENCES pessoas (id) ON DELETE CASCADE,
FOREIGN KEY (id_inquilino) REFERENCES pessoas (id) ON DELETE CASCADE,
UNIQUE(id_fiador, id_inquilino, data_inicio)
 );');
$this->addSql('CREATE TABLE requisicoes_responsaveis (
 id BIGSERIAL PRIMARY KEY,
 numero_requisicao INTEGER NOT NULL,
 id_pessoa BIGINT NOT NULL,
 id_inquilino BIGINT NULL,
 observacoes TEXT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (id_pessoa) REFERENCES pessoas (id) ON DELETE CASCADE,
FOREIGN KEY (id_inquilino) REFERENCES pessoas (id) ON DELETE SET NULL
 );');
// Criação dos índices
$this->addSql('CREATE INDEX idx_personal_access_tokens_tokenable ON personal_access_tokens(tokenable_type, tokenable_id);');
$this->addSql('CREATE INDEX idx_sessions_user_id ON sessions(user_id);');
$this->addSql('CREATE INDEX idx_sessions_last_activity ON sessions(last_activity);');
$this->addSql('CREATE INDEX idx_model_has_permissions_model ON model_has_permissions(model_id, model_type);');
$this->addSql('CREATE INDEX idx_model_has_roles_model ON model_has_roles(model_id, model_type);');
$this->addSql('CREATE INDEX idx_cidades_estado ON cidades(id_estado);');
$this->addSql('CREATE INDEX idx_bairros_cidade ON bairros(id_cidade);');
$this->addSql('CREATE INDEX idx_logradouros_bairro ON logradouros(id_bairro);');
$this->addSql('CREATE INDEX idx_logradouros_cep ON logradouros(cep);');
$this->addSql('CREATE INDEX idx_pessoas_tipo ON pessoas(tipo_pessoa);');
$this->addSql('CREATE INDEX idx_pessoas_status ON pessoas(status);');
$this->addSql('CREATE INDEX idx_pessoas_fisica_juridica ON pessoas(fisica_juridica);');
$this->addSql('CREATE INDEX idx_pessoas_dt_cadastro ON pessoas(dt_cadastro);');
$this->addSql('CREATE INDEX idx_emails_tipo ON emails(id_tipo);');
$this->addSql('CREATE INDEX idx_emails_email ON emails(email);');
$this->addSql('CREATE INDEX idx_telefones_tipo ON telefones(id_tipo);');
$this->addSql('CREATE INDEX idx_telefones_numero ON telefones(numero);');
$this->addSql('CREATE INDEX idx_enderecos_pessoa ON enderecos(id_pessoa);');
$this->addSql('CREATE INDEX idx_enderecos_logradouro ON enderecos(id_logradouro);');
$this->addSql('CREATE INDEX idx_enderecos_tipo ON enderecos(id_tipo);');
$this->addSql('CREATE INDEX idx_pessoas_telefones_pessoa ON pessoas_telefones(id_pessoa);');
$this->addSql('CREATE INDEX idx_pessoas_telefones_telefone ON pessoas_telefones(id_telefone);');
$this->addSql('CREATE INDEX idx_pessoas_emails_pessoa ON pessoas_emails(id_pessoa);');
$this->addSql('CREATE INDEX idx_pessoas_emails_email ON pessoas_emails(id_email);');
$this->addSql('CREATE INDEX idx_pessoas_tipos_pessoa ON pessoas_tipos(id_pessoa);');
$this->addSql('CREATE INDEX idx_pessoas_tipos_tipo ON pessoas_tipos(id_tipo_pessoa);');
$this->addSql('CREATE INDEX idx_pessoas_documentos_pessoa ON pessoas_documentos(id_pessoa);');
$this->addSql('CREATE INDEX idx_pessoas_documentos_tipo ON pessoas_documentos(id_tipo_documento);');
$this->addSql('CREATE INDEX idx_pessoas_documentos_numero ON pessoas_documentos(numero_documento);');
$this->addSql('CREATE INDEX idx_pessoas_profissoes_pessoa ON pessoas_profissoes(id_pessoa);');
$this->addSql('CREATE INDEX idx_pessoas_profissoes_profissao ON pessoas_profissoes(id_profissao);');
$this->addSql('CREATE INDEX idx_relacionamentos_origem ON relacionamentos_familiares(id_pessoa_origem);');
$this->addSql('CREATE INDEX idx_relacionamentos_destino ON relacionamentos_familiares(id_pessoa_destino);');
$this->addSql('CREATE INDEX idx_relacionamentos_regime ON relacionamentos_familiares(id_regime_casamento);');
$this->addSql('CREATE INDEX idx_contas_bancarias_pessoa ON contas_bancarias(id_pessoa);');
$this->addSql('CREATE INDEX idx_contas_bancarias_banco ON contas_bancarias(id_banco);');
$this->addSql('CREATE INDEX idx_contas_bancarias_agencia ON contas_bancarias(id_agencia);');
$this->addSql('CREATE INDEX idx_contas_bancarias_principal ON contas_bancarias(principal) WHERE principal = true;');
$this->addSql('CREATE INDEX idx_contas_bancarias_ativo ON contas_bancarias(ativo);');
$this->addSql('CREATE INDEX idx_agencias_banco ON agencias(id_banco);');
$this->addSql('CREATE INDEX idx_agencias_codigo ON agencias(codigo);');
$this->addSql('CREATE INDEX idx_agencias_endereco ON agencias(id_endereco);');
$this->addSql('CREATE INDEX idx_configuracoes_cobranca_conta ON configuracoes_cobranca(id_conta_bancaria);');
$this->addSql('CREATE INDEX idx_contas_vinculadas_principal ON contas_vinculadas(id_conta_principal);');
$this->addSql('CREATE INDEX idx_contas_vinculadas_vinculada ON contas_vinculadas(id_conta_vinculada);');
$this->addSql('CREATE INDEX idx_chaves_pix_pessoa ON chaves_pix(id_pessoa);');
$this->addSql('CREATE INDEX idx_chaves_pix_chave ON chaves_pix(chave_pix);');
$this->addSql('CREATE INDEX idx_chaves_pix_tipo ON chaves_pix(id_tipo_chave);');
$this->addSql('CREATE INDEX idx_chaves_pix_conta ON chaves_pix(id_conta_bancaria);');
$this->addSql('CREATE INDEX idx_fiadores_inquilinos_fiador ON fiadores_inquilinos(id_fiador);');
$this->addSql('CREATE INDEX idx_fiadores_inquilinos_inquilino ON fiadores_inquilinos(id_inquilino);');
$this->addSql('CREATE INDEX idx_requisicoes_pessoa ON requisicoes_responsaveis(id_pessoa);');
$this->addSql('CREATE INDEX idx_requisicoes_inquilino ON requisicoes_responsaveis(id_inquilino);');
$this->addSql('CREATE INDEX idx_requisicoes_numero ON requisicoes_responsaveis(numero_requisicao);');
// Criação da função de trigger para updated_at
$this->addSql('CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
 NEW.updated_at = CURRENT_TIMESTAMP;
RETURN NEW;
END;
$$ language \'plpgsql\';');
// Criação dos triggers para todas as tabelas com updated_at
$this->addSql('CREATE TRIGGER update_users_updated_at BEFORE UPDATE ON users FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();');
$this->addSql('CREATE TRIGGER update_personal_access_tokens_updated_at BEFORE UPDATE ON personal_access_tokens FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();');
$this->addSql('CREATE TRIGGER update_permissions_updated_at BEFORE UPDATE ON permissions FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();');
$this->addSql('CREATE TRIGGER update_roles_updated_at BEFORE UPDATE ON roles FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();');
$this->addSql('CREATE TRIGGER update_estados_updated_at BEFORE UPDATE ON estados FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();');
$this->addSql('CREATE TRIGGER update_cidades_updated_at BEFORE UPDATE ON cidades FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();');
$this->addSql('CREATE TRIGGER update_bairros_updated_at BEFORE UPDATE ON bairros FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();');
$this->addSql('CREATE TRIGGER update_logradouros_updated_at BEFORE UPDATE ON logradouros FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();');
$this->addSql('CREATE TRIGGER update_bancos_updated_at BEFORE UPDATE ON bancos FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();');
$this->addSql('CREATE TRIGGER update_agencias_updated_at BEFORE UPDATE ON agencias FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();');
$this->addSql('CREATE TRIGGER update_tipos_carteiras_updated_at BEFORE UPDATE ON tipos_carteiras FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();');
$this->addSql('CREATE TRIGGER update_tipos_remessa_updated_at BEFORE UPDATE ON tipos_remessa FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();');
$this->addSql('CREATE TRIGGER update_layouts_remessa_updated_at BEFORE UPDATE ON layouts_remessa FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();');
$this->addSql('CREATE TRIGGER update_razoes_conta_updated_at BEFORE UPDATE ON razoes_conta FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();');
$this->addSql('CREATE TRIGGER update_tipos_documentos_updated_at BEFORE UPDATE ON tipos_documentos FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();');
$this->addSql('CREATE TRIGGER update_tipos_telefones_updated_at BEFORE UPDATE ON tipos_telefones FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();');
$this->addSql('CREATE TRIGGER update_tipos_chaves_pix_updated_at BEFORE UPDATE ON tipos_chaves_pix FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();');
$this->addSql('CREATE TRIGGER update_tipos_contas_bancarias_updated_at BEFORE UPDATE ON tipos_contas_bancarias FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();');
$this->addSql('CREATE TRIGGER update_tipos_enderecos_updated_at BEFORE UPDATE ON tipos_enderecos FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();');
$this->addSql('CREATE TRIGGER update_tipos_emails_updated_at BEFORE UPDATE ON tipos_emails FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();');
$this->addSql('CREATE TRIGGER update_tipos_pessoas_updated_at BEFORE UPDATE ON tipos_pessoas FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();');
$this->addSql('CREATE TRIGGER update_profissoes_updated_at BEFORE UPDATE ON profissoes FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();');
$this->addSql('CREATE TRIGGER update_tipos_imoveis_updated_at BEFORE UPDATE ON tipos_imoveis FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();');
$this->addSql('CREATE TRIGGER update_tipos_atendimento_updated_at BEFORE UPDATE ON tipos_atendimento FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();');
$this->addSql('CREATE TRIGGER update_formas_retirada_updated_at BEFORE UPDATE ON formas_retirada FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();');
$this->addSql('CREATE TRIGGER update_regimes_casamento_updated_at BEFORE UPDATE ON regimes_casamento FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();');
$this->addSql('CREATE TRIGGER update_pessoas_updated_at BEFORE UPDATE ON pessoas FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();');
$this->addSql('CREATE TRIGGER update_emails_updated_at BEFORE UPDATE ON emails FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();');
$this->addSql('CREATE TRIGGER update_telefones_updated_at BEFORE UPDATE ON telefones FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();');
$this->addSql('CREATE TRIGGER update_enderecos_updated_at BEFORE UPDATE ON enderecos FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();');
$this->addSql('CREATE TRIGGER update_pessoas_telefones_updated_at BEFORE UPDATE ON pessoas_telefones FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();');
$this->addSql('CREATE TRIGGER update_pessoas_emails_updated_at BEFORE UPDATE ON pessoas_emails FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();');
$this->addSql('CREATE TRIGGER update_pessoas_tipos_updated_at BEFORE UPDATE ON pessoas_tipos FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();');
$this->addSql('CREATE TRIGGER update_pessoas_documentos_updated_at BEFORE UPDATE ON pessoas_documentos FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();');
$this->addSql('CREATE TRIGGER update_pessoas_profissoes_updated_at BEFORE UPDATE ON pessoas_profissoes FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();');
$this->addSql('CREATE TRIGGER update_relacionamentos_familiares_updated_at BEFORE UPDATE ON relacionamentos_familiares FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();');
$this->addSql('CREATE TRIGGER update_contas_bancarias_updated_at BEFORE UPDATE ON contas_bancarias FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();');
$this->addSql('CREATE TRIGGER update_configuracoes_cobranca_updated_at BEFORE UPDATE ON configuracoes_cobranca FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();');
$this->addSql('CREATE TRIGGER update_contas_vinculadas_updated_at BEFORE UPDATE ON contas_vinculadas FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();');
$this->addSql('CREATE TRIGGER update_chaves_pix_updated_at BEFORE UPDATE ON chaves_pix FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();');
$this->addSql('CREATE TRIGGER update_pessoas_contratantes_updated_at BEFORE UPDATE ON pessoas_contratantes FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();');
$this->addSql('CREATE TRIGGER update_pessoas_corretores_updated_at BEFORE UPDATE ON pessoas_corretores FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();');
$this->addSql('CREATE TRIGGER update_pessoas_fiadores_updated_at BEFORE UPDATE ON pessoas_fiadores FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();');
$this->addSql('CREATE TRIGGER update_pessoas_locadores_updated_at BEFORE UPDATE ON pessoas_locadores FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();');
$this->addSql('CREATE TRIGGER update_pessoas_pretendentes_updated_at BEFORE UPDATE ON pessoas_pretendentes FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();');
$this->addSql('CREATE TRIGGER update_pessoas_corretoras_updated_at BEFORE UPDATE ON pessoas_corretoras FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();');
$this->addSql('CREATE TRIGGER update_fiadores_inquilinos_updated_at BEFORE UPDATE ON fiadores_inquilinos FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();');
$this->addSql('CREATE TRIGGER update_requisicoes_responsaveis_updated_at BEFORE UPDATE ON requisicoes_responsaveis FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();');
 }
public function down(Schema $schema): void
 {
// Drop triggers em ordem reversa
$this->addSql('DROP TRIGGER IF EXISTS update_requisicoes_responsaveis_updated_at ON requisicoes_responsaveis;');
$this->addSql('DROP TRIGGER IF EXISTS update_fiadores_inquilinos_updated_at ON fiadores_inquilinos;');
$this->addSql('DROP TRIGGER IF EXISTS update_pessoas_corretoras_updated_at ON pessoas_corretoras;');
$this->addSql('DROP TRIGGER IF EXISTS update_pessoas_pretendentes_updated_at ON pessoas_pretendentes;');
$this->addSql('DROP TRIGGER IF EXISTS update_pessoas_locadores_updated_at ON pessoas_locadores;');
$this->addSql('DROP TRIGGER IF EXISTS update_pessoas_fiadores_updated_at ON pessoas_fiadores;');
$this->addSql('DROP TRIGGER IF EXISTS update_pessoas_corretores_updated_at ON pessoas_corretores;');
$this->addSql('DROP TRIGGER IF EXISTS update_pessoas_contratantes_updated_at ON pessoas_contratantes;');
$this->addSql('DROP TRIGGER IF EXISTS update_chaves_pix_updated_at ON chaves_pix;');
$this->addSql('DROP TRIGGER IF EXISTS update_contas_vinculadas_updated_at ON contas_vinculadas;');
$this->addSql('DROP TRIGGER IF EXISTS update_configuracoes_cobranca_updated_at ON configuracoes_cobranca;');
$this->addSql('DROP TRIGGER IF EXISTS update_contas_bancarias_updated_at ON contas_bancarias;');
$this->addSql('DROP TRIGGER IF EXISTS update_relacionamentos_familiares_updated_at ON relacionamentos_familiares;');
$this->addSql('DROP TRIGGER IF EXISTS update_pessoas_profissoes_updated_at ON pessoas_profissoes;');
$this->addSql('DROP TRIGGER IF EXISTS update_pessoas_documentos_updated_at ON pessoas_documentos;');
$this->addSql('DROP TRIGGER IF EXISTS update_pessoas_tipos_updated_at ON pessoas_tipos;');
$this->addSql('DROP TRIGGER IF EXISTS update_pessoas_emails_updated_at ON pessoas_emails;');
$this->addSql('DROP TRIGGER IF EXISTS update_pessoas_telefones_updated_at ON pessoas_telefones;');
$this->addSql('DROP TRIGGER IF EXISTS update_enderecos_updated_at ON enderecos;');
$this->addSql('DROP TRIGGER IF EXISTS update_telefones_updated_at ON telefones;');
$this->addSql('DROP TRIGGER IF EXISTS update_emails_updated_at ON emails;');
$this->addSql('DROP TRIGGER IF EXISTS update_pessoas_updated_at ON pessoas;');
$this->addSql('DROP TRIGGER IF EXISTS update_regimes_casamento_updated_at ON regimes_casamento;');
$this->addSql('DROP TRIGGER IF EXISTS update_formas_retirada_updated_at ON formas_retirada;');
$this->addSql('DROP TRIGGER IF EXISTS update_tipos_atendimento_updated_at ON tipos_atendimento;');
$this->addSql('DROP TRIGGER IF EXISTS update_tipos_imoveis_updated_at ON tipos_imoveis;');
$this->addSql('DROP TRIGGER IF EXISTS update_profissoes_updated_at ON profissoes;');
$this->addSql('DROP TRIGGER IF EXISTS update_tipos_pessoas_updated_at ON tipos_pessoas;');
$this->addSql('DROP TRIGGER IF EXISTS update_tipos_emails_updated_at ON tipos_emails;');
$this->addSql('DROP TRIGGER IF EXISTS update_tipos_enderecos_updated_at ON tipos_enderecos;');
$this->addSql('DROP TRIGGER IF EXISTS update_tipos_contas_bancarias_updated_at ON tipos_contas_bancarias;');
$this->addSql('DROP TRIGGER IF EXISTS update_tipos_chaves_pix_updated_at ON tipos_chaves_pix;');
$this->addSql('DROP TRIGGER IF EXISTS update_tipos_telefones_updated_at ON tipos_telefones;');
$this->addSql('DROP TRIGGER IF EXISTS update_tipos_documentos_updated_at ON tipos_documentos;');
$this->addSql('DROP TRIGGER IF EXISTS update_razoes_conta_updated_at ON razoes_conta;');
$this->addSql('DROP TRIGGER IF EXISTS update_layouts_remessa_updated_at ON layouts_remessa;');
$this->addSql('DROP TRIGGER IF EXISTS update_tipos_remessa_updated_at ON tipos_remessa;');
$this->addSql('DROP TRIGGER IF EXISTS update_tipos_carteiras_updated_at ON tipos_carteiras;');
$this->addSql('DROP TRIGGER IF EXISTS update_agencias_updated_at ON agencias;');
$this->addSql('DROP TRIGGER IF EXISTS update_bancos_updated_at ON bancos;');
$this->addSql('DROP TRIGGER IF EXISTS update_logradouros_updated_at ON logradouros;');
$this->addSql('DROP TRIGGER IF EXISTS update_bairros_updated_at ON bairros;');
$this->addSql('DROP TRIGGER IF EXISTS update_cidades_updated_at ON cidades;');
$this->addSql('DROP TRIGGER IF EXISTS update_estados_updated_at ON estados;');
$this->addSql('DROP TRIGGER IF EXISTS update_roles_updated_at ON roles;');
$this->addSql('DROP TRIGGER IF EXISTS update_permissions_updated_at ON permissions;');
$this->addSql('DROP TRIGGER IF EXISTS update_personal_access_tokens_updated_at ON personal_access_tokens;');
$this->addSql('DROP TRIGGER IF EXISTS update_users_updated_at ON users;');
// Drop function
$this->addSql('DROP FUNCTION IF EXISTS update_updated_at_column();');
// Drop CHECK constraints antes de dropar as tabelas
$this->addSql('ALTER TABLE pessoas DROP CONSTRAINT IF EXISTS check_estado_civil;');
$this->addSql('ALTER TABLE pessoas DROP CONSTRAINT IF EXISTS check_fisica_juridica;');
// Drop tables em ordem reversa de criação (respeitando dependências)
$this->addSql('DROP TABLE IF EXISTS requisicoes_responsaveis CASCADE;');
$this->addSql('DROP TABLE IF EXISTS fiadores_inquilinos CASCADE;');
$this->addSql('DROP TABLE IF EXISTS pessoas_corretoras CASCADE;');
$this->addSql('DROP TABLE IF EXISTS pessoas_pretendentes CASCADE;');
$this->addSql('DROP TABLE IF EXISTS pessoas_locadores CASCADE;');
$this->addSql('DROP TABLE IF EXISTS pessoas_fiadores CASCADE;');
$this->addSql('DROP TABLE IF EXISTS pessoas_corretores CASCADE;');
$this->addSql('DROP TABLE IF EXISTS pessoas_contratantes CASCADE;');
$this->addSql('DROP TABLE IF EXISTS chaves_pix CASCADE;');
$this->addSql('DROP TABLE IF EXISTS contas_vinculadas CASCADE;');
$this->addSql('DROP TABLE IF EXISTS configuracoes_cobranca CASCADE;');
$this->addSql('DROP TABLE IF EXISTS contas_bancarias CASCADE;');
$this->addSql('DROP TABLE IF EXISTS relacionamentos_familiares CASCADE;');
$this->addSql('DROP TABLE IF EXISTS pessoas_profissoes CASCADE;');
$this->addSql('DROP TABLE IF EXISTS pessoas_documentos CASCADE;');
$this->addSql('DROP TABLE IF EXISTS pessoas_tipos CASCADE;');
$this->addSql('DROP TABLE IF EXISTS pessoas_emails CASCADE;');
$this->addSql('DROP TABLE IF EXISTS pessoas_telefones CASCADE;');
$this->addSql('DROP TABLE IF EXISTS agencias CASCADE;');
$this->addSql('DROP TABLE IF EXISTS enderecos CASCADE;');
$this->addSql('DROP TABLE IF EXISTS telefones CASCADE;');
$this->addSql('DROP TABLE IF EXISTS emails CASCADE;');
$this->addSql('DROP TABLE IF EXISTS pessoas CASCADE;');
$this->addSql('DROP TABLE IF EXISTS regimes_casamento CASCADE;');
$this->addSql('DROP TABLE IF EXISTS formas_retirada CASCADE;');
$this->addSql('DROP TABLE IF EXISTS tipos_atendimento CASCADE;');
$this->addSql('DROP TABLE IF EXISTS tipos_imoveis CASCADE;');
$this->addSql('DROP TABLE IF EXISTS profissoes CASCADE;');
$this->addSql('DROP TABLE IF EXISTS tipos_pessoas CASCADE;');
$this->addSql('DROP TABLE IF EXISTS tipos_emails CASCADE;');
$this->addSql('DROP TABLE IF EXISTS tipos_enderecos CASCADE;');
$this->addSql('DROP TABLE IF EXISTS tipos_contas_bancarias CASCADE;');
$this->addSql('DROP TABLE IF EXISTS tipos_chaves_pix CASCADE;');
$this->addSql('DROP TABLE IF EXISTS tipos_telefones CASCADE;');
$this->addSql('DROP TABLE IF EXISTS tipos_documentos CASCADE;');
$this->addSql('DROP TABLE IF EXISTS razoes_conta CASCADE;');
$this->addSql('DROP TABLE IF EXISTS layouts_remessa CASCADE;');
$this->addSql('DROP TABLE IF EXISTS tipos_remessa CASCADE;');
$this->addSql('DROP TABLE IF EXISTS tipos_carteiras CASCADE;');
$this->addSql('DROP TABLE IF EXISTS bancos CASCADE;');
$this->addSql('DROP TABLE IF EXISTS logradouros CASCADE;');
$this->addSql('DROP TABLE IF EXISTS bairros CASCADE;');
$this->addSql('DROP TABLE IF EXISTS cidades CASCADE;');
$this->addSql('DROP TABLE IF EXISTS estados CASCADE;');
$this->addSql('DROP TABLE IF EXISTS role_has_permissions CASCADE;');
$this->addSql('DROP TABLE IF EXISTS model_has_roles CASCADE;');
$this->addSql('DROP TABLE IF EXISTS model_has_permissions CASCADE;');
$this->addSql('DROP TABLE IF EXISTS roles CASCADE;');
$this->addSql('DROP TABLE IF EXISTS permissions CASCADE;');
$this->addSql('DROP TABLE IF EXISTS sessions CASCADE;');
$this->addSql('DROP TABLE IF EXISTS personal_access_tokens CASCADE;');
$this->addSql('DROP TABLE IF EXISTS failed_jobs CASCADE;');
$this->addSql('DROP TABLE IF EXISTS password_reset_tokens CASCADE;');
$this->addSql('DROP TABLE IF EXISTS users CASCADE;');
 }
}