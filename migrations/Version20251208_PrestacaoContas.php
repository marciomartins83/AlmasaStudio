<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration: Módulo Prestação de Contas
 *
 * Cria tabelas para:
 * - prestacoes_contas: Cabeçalho com totais e dados do repasse
 * - prestacoes_contas_itens: Itens detalhados (receitas/despesas)
 */
final class Version20251208_PrestacaoContas extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Criar tabelas para módulo de Prestação de Contas aos proprietários';
    }

    public function up(Schema $schema): void
    {
        // === TABELA PRINCIPAL: prestacoes_contas ===
        $this->addSql("
            CREATE TABLE prestacoes_contas (
                id                          SERIAL PRIMARY KEY,

                -- IDENTIFICAÇÃO
                numero                      INT NOT NULL,
                ano                         INT NOT NULL,

                -- PERÍODO
                data_inicio                 DATE NOT NULL,
                data_fim                    DATE NOT NULL,
                tipo_periodo                VARCHAR(20) NOT NULL DEFAULT 'mensal',
                competencia                 VARCHAR(7),

                -- VÍNCULOS
                id_proprietario             INT NOT NULL,
                id_imovel                   INT,

                -- ORIGEM DOS DADOS
                incluir_ficha_financeira    BOOLEAN DEFAULT TRUE,
                incluir_lancamentos         BOOLEAN DEFAULT TRUE,

                -- TOTAIS CALCULADOS
                total_receitas              DECIMAL(15,2) DEFAULT 0,
                total_despesas              DECIMAL(15,2) DEFAULT 0,
                total_taxa_admin            DECIMAL(15,2) DEFAULT 0,
                total_retencao_ir           DECIMAL(15,2) DEFAULT 0,
                valor_repasse               DECIMAL(15,2) DEFAULT 0,

                -- REPASSE
                status                      VARCHAR(20) DEFAULT 'gerado',
                data_repasse                DATE,
                forma_repasse               VARCHAR(20),
                id_conta_bancaria           INT,
                comprovante_repasse         VARCHAR(255),

                -- OBSERVAÇÕES
                observacoes                 TEXT,

                -- AUDITORIA
                created_at                  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at                  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                created_by                  INT,

                -- FOREIGN KEYS
                CONSTRAINT fk_prestacao_proprietario FOREIGN KEY (id_proprietario)
                    REFERENCES pessoas(idpessoa) ON DELETE RESTRICT,
                CONSTRAINT fk_prestacao_imovel FOREIGN KEY (id_imovel)
                    REFERENCES imoveis(id) ON DELETE SET NULL,
                CONSTRAINT fk_prestacao_conta_bancaria FOREIGN KEY (id_conta_bancaria)
                    REFERENCES contas_bancarias(id) ON DELETE SET NULL,
                CONSTRAINT fk_prestacao_created_by FOREIGN KEY (created_by)
                    REFERENCES users(id) ON DELETE SET NULL,

                -- CONSTRAINTS
                CONSTRAINT chk_prestacao_status CHECK (status IN ('gerado', 'aprovado', 'pago', 'cancelado')),
                CONSTRAINT chk_prestacao_periodo CHECK (tipo_periodo IN ('personalizado', 'diario', 'semanal', 'quinzenal', 'mensal', 'trimestral', 'semestral', 'anual', 'bienal')),
                CONSTRAINT uk_prestacao_numero_ano UNIQUE (numero, ano)
            )
        ");

        // Índices para prestacoes_contas
        $this->addSql('CREATE INDEX idx_prestacoes_contas_proprietario ON prestacoes_contas(id_proprietario)');
        $this->addSql('CREATE INDEX idx_prestacoes_contas_imovel ON prestacoes_contas(id_imovel)');
        $this->addSql('CREATE INDEX idx_prestacoes_contas_periodo ON prestacoes_contas(data_inicio, data_fim)');
        $this->addSql('CREATE INDEX idx_prestacoes_contas_status ON prestacoes_contas(status)');
        $this->addSql('CREATE INDEX idx_prestacoes_contas_ano ON prestacoes_contas(ano)');

        // === TABELA DE ITENS: prestacoes_contas_itens ===
        $this->addSql("
            CREATE TABLE prestacoes_contas_itens (
                id                          SERIAL PRIMARY KEY,
                id_prestacao_conta          INT NOT NULL,

                -- ORIGEM
                origem                      VARCHAR(30) NOT NULL,
                id_lancamento_financeiro    INT,
                id_lancamento               INT,

                -- DADOS DO ITEM
                data_movimento              DATE NOT NULL,
                data_vencimento             DATE,
                data_pagamento              DATE,

                -- CLASSIFICAÇÃO
                tipo                        VARCHAR(10) NOT NULL,
                id_plano_conta              INT,
                historico                   VARCHAR(200),

                -- IMÓVEL (para prestações com múltiplos imóveis)
                id_imovel                   INT,

                -- VALORES
                valor_bruto                 DECIMAL(15,2) NOT NULL,
                valor_taxa_admin            DECIMAL(15,2) DEFAULT 0,
                valor_retencao_ir           DECIMAL(15,2) DEFAULT 0,
                valor_liquido               DECIMAL(15,2) NOT NULL,

                -- AUDITORIA
                created_at                  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

                -- FOREIGN KEYS
                CONSTRAINT fk_item_prestacao FOREIGN KEY (id_prestacao_conta)
                    REFERENCES prestacoes_contas(id) ON DELETE CASCADE,
                CONSTRAINT fk_item_lancamento_financeiro FOREIGN KEY (id_lancamento_financeiro)
                    REFERENCES lancamentos_financeiros(id) ON DELETE SET NULL,
                CONSTRAINT fk_item_lancamento FOREIGN KEY (id_lancamento)
                    REFERENCES lancamentos(id) ON DELETE SET NULL,
                CONSTRAINT fk_item_plano_conta FOREIGN KEY (id_plano_conta)
                    REFERENCES plano_contas(id) ON DELETE SET NULL,
                CONSTRAINT fk_item_imovel FOREIGN KEY (id_imovel)
                    REFERENCES imoveis(id) ON DELETE SET NULL,

                -- CONSTRAINTS
                CONSTRAINT chk_item_tipo CHECK (tipo IN ('receita', 'despesa')),
                CONSTRAINT chk_item_origem CHECK (origem IN ('ficha_financeira', 'lancamento_pagar', 'lancamento_receber'))
            )
        ");

        // Índices para prestacoes_contas_itens
        $this->addSql('CREATE INDEX idx_prestacoes_itens_prestacao ON prestacoes_contas_itens(id_prestacao_conta)');
        $this->addSql('CREATE INDEX idx_prestacoes_itens_tipo ON prestacoes_contas_itens(tipo)');
        $this->addSql('CREATE INDEX idx_prestacoes_itens_imovel ON prestacoes_contas_itens(id_imovel)');
    }

    public function down(Schema $schema): void
    {
        // Remover índices primeiro
        $this->addSql('DROP INDEX IF EXISTS idx_prestacoes_itens_prestacao');
        $this->addSql('DROP INDEX IF EXISTS idx_prestacoes_itens_tipo');
        $this->addSql('DROP INDEX IF EXISTS idx_prestacoes_itens_imovel');

        $this->addSql('DROP INDEX IF EXISTS idx_prestacoes_contas_proprietario');
        $this->addSql('DROP INDEX IF EXISTS idx_prestacoes_contas_imovel');
        $this->addSql('DROP INDEX IF EXISTS idx_prestacoes_contas_periodo');
        $this->addSql('DROP INDEX IF EXISTS idx_prestacoes_contas_status');
        $this->addSql('DROP INDEX IF EXISTS idx_prestacoes_contas_ano');

        // Remover tabelas (itens primeiro por causa da FK)
        $this->addSql('DROP TABLE IF EXISTS prestacoes_contas_itens');
        $this->addSql('DROP TABLE IF EXISTS prestacoes_contas');
    }
}
