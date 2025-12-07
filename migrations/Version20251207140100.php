<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Fase 3 - Cobrança Automática de Contratos
 *
 * Esta migration cria a estrutura para:
 * 1. Itens de cobrança por contrato (composição do valor do boleto)
 * 2. Cobranças mensais com controle por competência
 * 3. Log de emails enviados
 * 4. Adiciona campo dias_antecedencia_boleto em imoveis_contratos
 */
final class Version20251207140100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fase 3 - Cobrança Automática de Contratos: tabelas contratos_itens_cobranca, contratos_cobrancas, emails_enviados';
    }

    public function up(Schema $schema): void
    {
        // 1. Adicionar campo em imoveis_contratos
        $this->addSql('ALTER TABLE imoveis_contratos ADD COLUMN IF NOT EXISTS dias_antecedencia_boleto INTEGER NOT NULL DEFAULT 5');

        // 2. Tabela: ITENS DE COBRANÇA DO CONTRATO
        $this->addSql('
            CREATE TABLE IF NOT EXISTS contratos_itens_cobranca (
                id SERIAL PRIMARY KEY,
                contrato_id INTEGER NOT NULL REFERENCES imoveis_contratos(id) ON DELETE CASCADE,

                tipo_item VARCHAR(50) NOT NULL,
                descricao VARCHAR(100) NOT NULL,

                valor_tipo VARCHAR(20) NOT NULL DEFAULT \'FIXO\',
                valor DECIMAL(15,2) NOT NULL,

                ativo BOOLEAN NOT NULL DEFAULT true,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

                CONSTRAINT uk_itens_contrato_tipo UNIQUE (contrato_id, tipo_item)
            )
        ');

        $this->addSql('CREATE INDEX IF NOT EXISTS idx_contratos_itens_contrato ON contratos_itens_cobranca(contrato_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_contratos_itens_tipo ON contratos_itens_cobranca(tipo_item)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_contratos_itens_ativo ON contratos_itens_cobranca(ativo)');

        // 3. Tabela: COBRANÇAS MENSAIS
        $this->addSql('
            CREATE TABLE IF NOT EXISTS contratos_cobrancas (
                id SERIAL PRIMARY KEY,
                contrato_id INTEGER NOT NULL REFERENCES imoveis_contratos(id) ON DELETE CASCADE,
                boleto_id INTEGER REFERENCES boletos(id) ON DELETE SET NULL,

                competencia VARCHAR(7) NOT NULL,

                periodo_inicio DATE NOT NULL,
                periodo_fim DATE NOT NULL,

                data_vencimento DATE NOT NULL,

                valor_aluguel DECIMAL(15,2) NOT NULL DEFAULT 0,
                valor_iptu DECIMAL(15,2) NOT NULL DEFAULT 0,
                valor_condominio DECIMAL(15,2) NOT NULL DEFAULT 0,
                valor_taxa_admin DECIMAL(15,2) NOT NULL DEFAULT 0,
                valor_outros DECIMAL(15,2) NOT NULL DEFAULT 0,
                valor_total DECIMAL(15,2) NOT NULL,

                itens_detalhados JSONB,

                status VARCHAR(30) NOT NULL DEFAULT \'PENDENTE\',

                tipo_envio VARCHAR(20),
                enviado_em TIMESTAMP,
                email_destino VARCHAR(255),

                bloqueado_rotina_auto BOOLEAN NOT NULL DEFAULT false,

                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                created_by INTEGER REFERENCES users(id),

                CONSTRAINT uk_cobranca_contrato_competencia UNIQUE (contrato_id, competencia)
            )
        ');

        $this->addSql('CREATE INDEX IF NOT EXISTS idx_cobrancas_contrato ON contratos_cobrancas(contrato_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_cobrancas_competencia ON contratos_cobrancas(competencia)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_cobrancas_status ON contratos_cobrancas(status)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_cobrancas_vencimento ON contratos_cobrancas(data_vencimento)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_cobrancas_boleto ON contratos_cobrancas(boleto_id)');

        // 4. Tabela: LOG DE EMAILS ENVIADOS
        $this->addSql('
            CREATE TABLE IF NOT EXISTS emails_enviados (
                id SERIAL PRIMARY KEY,

                tipo_referencia VARCHAR(50) NOT NULL,
                referencia_id INTEGER NOT NULL,

                destinatario VARCHAR(255) NOT NULL,
                assunto VARCHAR(255) NOT NULL,
                corpo TEXT,

                anexos JSONB,

                status VARCHAR(20) NOT NULL DEFAULT \'ENVIADO\',

                erro_mensagem TEXT,

                enviado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ');

        $this->addSql('CREATE INDEX IF NOT EXISTS idx_emails_destinatario ON emails_enviados(destinatario)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_emails_tipo ON emails_enviados(tipo_referencia, referencia_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_emails_status ON emails_enviados(status)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_emails_enviado_em ON emails_enviados(enviado_em)');

        // Comentários nas tabelas
        $this->addSql("COMMENT ON TABLE contratos_itens_cobranca IS 'Itens de composição do valor de cobrança mensal do contrato'");
        $this->addSql("COMMENT ON TABLE contratos_cobrancas IS 'Cobranças mensais por competência - controla duplicidade e status de envio'");
        $this->addSql("COMMENT ON TABLE emails_enviados IS 'Log de todos os emails enviados pelo sistema'");
        $this->addSql("COMMENT ON COLUMN contratos_cobrancas.competencia IS 'Formato YYYY-MM, ex: 2025-12'");
        $this->addSql("COMMENT ON COLUMN contratos_cobrancas.bloqueado_rotina_auto IS 'Se true, não será processado pela rotina automática'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS emails_enviados');
        $this->addSql('DROP TABLE IF EXISTS contratos_cobrancas');
        $this->addSql('DROP TABLE IF EXISTS contratos_itens_cobranca');
        $this->addSql('ALTER TABLE imoveis_contratos DROP COLUMN IF EXISTS dias_antecedencia_boleto');
    }
}
