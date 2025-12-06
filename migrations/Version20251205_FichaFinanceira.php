<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration para criar tabelas do módulo Ficha Financeira / Contas a Receber
 *
 * Tabelas criadas:
 * - lancamentos_financeiros: Lançamentos de aluguel e outras cobranças
 * - baixas_financeiras: Registro de pagamentos (baixas)
 * - acordos_financeiros: Acordos de parcelamento de dívidas
 */
final class Version20251205_FichaFinanceira extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Cria tabelas para Ficha Financeira: lancamentos_financeiros, baixas_financeiras e acordos_financeiros';
    }

    public function up(Schema $schema): void
    {
        // Tabela principal de lançamentos financeiros
        $this->addSql("
            CREATE TABLE IF NOT EXISTS lancamentos_financeiros (
                id SERIAL PRIMARY KEY,

                -- Relacionamentos
                id_contrato INTEGER REFERENCES imoveis_contratos(id) ON DELETE SET NULL,
                id_imovel INTEGER REFERENCES imoveis(id) ON DELETE SET NULL,
                id_inquilino INTEGER REFERENCES pessoas(idpessoa) ON DELETE SET NULL,
                id_proprietario INTEGER REFERENCES pessoas(idpessoa) ON DELETE SET NULL,
                id_conta INTEGER REFERENCES plano_contas(id) ON DELETE SET NULL,
                id_conta_bancaria INTEGER REFERENCES contas_bancarias(id) ON DELETE SET NULL,

                -- Identificação
                numero_acordo INTEGER,
                numero_parcela INTEGER DEFAULT 1,
                numero_recibo VARCHAR(20),
                numero_boleto VARCHAR(50),

                -- Competência
                competencia DATE NOT NULL,
                data_lancamento DATE NOT NULL DEFAULT CURRENT_DATE,
                data_vencimento DATE NOT NULL,
                data_limite DATE,

                -- Valores originais
                valor_principal DECIMAL(10,2) NOT NULL DEFAULT 0,
                valor_condominio DECIMAL(10,2) DEFAULT 0,
                valor_iptu DECIMAL(10,2) DEFAULT 0,
                valor_agua DECIMAL(10,2) DEFAULT 0,
                valor_luz DECIMAL(10,2) DEFAULT 0,
                valor_gas DECIMAL(10,2) DEFAULT 0,
                valor_outros DECIMAL(10,2) DEFAULT 0,

                -- Acréscimos e descontos
                valor_multa DECIMAL(10,2) DEFAULT 0,
                valor_juros DECIMAL(10,2) DEFAULT 0,
                valor_honorarios DECIMAL(10,2) DEFAULT 0,
                valor_desconto DECIMAL(10,2) DEFAULT 0,
                valor_bonificacao DECIMAL(10,2) DEFAULT 0,

                -- Totais
                valor_total DECIMAL(10,2) NOT NULL DEFAULT 0,
                valor_pago DECIMAL(10,2) DEFAULT 0,
                valor_saldo DECIMAL(10,2) DEFAULT 0,

                -- Status e controle
                situacao VARCHAR(20) DEFAULT 'aberto',
                tipo_lancamento VARCHAR(30) DEFAULT 'aluguel',
                origem VARCHAR(30) DEFAULT 'contrato',

                -- Observações
                descricao TEXT,
                historico TEXT,
                observacoes TEXT,

                -- Auditoria
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                created_by INTEGER,
                updated_by INTEGER,

                -- Controle de geração
                gerado_automaticamente BOOLEAN DEFAULT false,
                data_geracao TIMESTAMP,

                -- Flags
                ativo BOOLEAN DEFAULT true,
                enviado_email BOOLEAN DEFAULT false,
                data_envio_email TIMESTAMP,
                impresso BOOLEAN DEFAULT false,
                data_impressao TIMESTAMP
            )
        ");

        // Índices para performance
        $this->addSql("CREATE INDEX IF NOT EXISTS idx_lanc_contrato ON lancamentos_financeiros(id_contrato)");
        $this->addSql("CREATE INDEX IF NOT EXISTS idx_lanc_inquilino ON lancamentos_financeiros(id_inquilino)");
        $this->addSql("CREATE INDEX IF NOT EXISTS idx_lanc_proprietario ON lancamentos_financeiros(id_proprietario)");
        $this->addSql("CREATE INDEX IF NOT EXISTS idx_lanc_competencia ON lancamentos_financeiros(competencia)");
        $this->addSql("CREATE INDEX IF NOT EXISTS idx_lanc_vencimento ON lancamentos_financeiros(data_vencimento)");
        $this->addSql("CREATE INDEX IF NOT EXISTS idx_lanc_situacao ON lancamentos_financeiros(situacao)");
        $this->addSql("CREATE INDEX IF NOT EXISTS idx_lanc_imovel ON lancamentos_financeiros(id_imovel)");

        // Tabela de baixas (pagamentos)
        $this->addSql("
            CREATE TABLE IF NOT EXISTS baixas_financeiras (
                id SERIAL PRIMARY KEY,

                -- Relacionamento com lançamento
                id_lancamento INTEGER NOT NULL REFERENCES lancamentos_financeiros(id) ON DELETE CASCADE,
                id_conta_bancaria INTEGER REFERENCES contas_bancarias(id) ON DELETE SET NULL,

                -- Dados do pagamento
                data_pagamento DATE NOT NULL,
                valor_pago DECIMAL(10,2) NOT NULL,
                valor_multa_paga DECIMAL(10,2) DEFAULT 0,
                valor_juros_pago DECIMAL(10,2) DEFAULT 0,
                valor_desconto DECIMAL(10,2) DEFAULT 0,
                valor_total_pago DECIMAL(10,2) NOT NULL,

                -- Forma de pagamento
                forma_pagamento VARCHAR(30) DEFAULT 'boleto',
                numero_documento VARCHAR(50),
                numero_autenticacao VARCHAR(100),

                -- Tipo de baixa
                tipo_baixa VARCHAR(20) DEFAULT 'normal',

                -- Observações
                observacoes TEXT,

                -- Auditoria
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                created_by INTEGER,

                -- Controle
                estornada BOOLEAN DEFAULT false,
                data_estorno TIMESTAMP,
                motivo_estorno TEXT
            )
        ");

        // Índices para baixas
        $this->addSql("CREATE INDEX IF NOT EXISTS idx_baixa_lancamento ON baixas_financeiras(id_lancamento)");
        $this->addSql("CREATE INDEX IF NOT EXISTS idx_baixa_data ON baixas_financeiras(data_pagamento)");
        $this->addSql("CREATE INDEX IF NOT EXISTS idx_baixa_conta ON baixas_financeiras(id_conta_bancaria)");

        // Tabela de acordos (parcelamento de dívidas)
        $this->addSql("
            CREATE TABLE IF NOT EXISTS acordos_financeiros (
                id SERIAL PRIMARY KEY,

                -- Identificação
                numero_acordo INTEGER NOT NULL,
                id_inquilino INTEGER NOT NULL REFERENCES pessoas(idpessoa) ON DELETE CASCADE,

                -- Datas
                data_acordo DATE NOT NULL DEFAULT CURRENT_DATE,
                data_primeira_parcela DATE NOT NULL,

                -- Valores
                valor_divida_original DECIMAL(10,2) NOT NULL,
                valor_desconto DECIMAL(10,2) DEFAULT 0,
                valor_juros DECIMAL(10,2) DEFAULT 0,
                valor_total_acordo DECIMAL(10,2) NOT NULL,

                -- Parcelamento
                quantidade_parcelas INTEGER NOT NULL DEFAULT 1,
                valor_parcela DECIMAL(10,2) NOT NULL,
                dia_vencimento INTEGER DEFAULT 10,

                -- Status
                situacao VARCHAR(20) DEFAULT 'ativo',

                -- Observações
                observacoes TEXT,

                -- Auditoria
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                created_by INTEGER
            )
        ");

        // Índices para acordos
        $this->addSql("CREATE INDEX IF NOT EXISTS idx_acordo_inquilino ON acordos_financeiros(id_inquilino)");
        $this->addSql("CREATE INDEX IF NOT EXISTS idx_acordo_situacao ON acordos_financeiros(situacao)");

        // Sequência para número de acordo
        $this->addSql("CREATE SEQUENCE IF NOT EXISTS seq_numero_acordo START 1");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP TABLE IF EXISTS baixas_financeiras CASCADE");
        $this->addSql("DROP TABLE IF EXISTS acordos_financeiros CASCADE");
        $this->addSql("DROP TABLE IF EXISTS lancamentos_financeiros CASCADE");
        $this->addSql("DROP SEQUENCE IF EXISTS seq_numero_acordo");
    }
}
