<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration para criar tabela de configuração de API Bancária
 *
 * Tabela criada:
 * - configuracoes_api_banco: Credenciais e configurações para integração com APIs bancárias (Santander, etc.)
 */
final class Version20251207_ConfiguracaoApiBanco extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Cria tabela configuracoes_api_banco para integração com APIs de cobrança bancária';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE IF NOT EXISTS configuracoes_api_banco (
                id SERIAL PRIMARY KEY,

                -- Relacionamentos
                banco_id INTEGER NOT NULL REFERENCES bancos(id) ON DELETE RESTRICT,
                conta_bancaria_id INTEGER NOT NULL REFERENCES contas_bancarias(id) ON DELETE RESTRICT,

                -- Credenciais OAuth
                client_id VARCHAR(255),
                client_secret VARCHAR(255),
                workspace_id VARCHAR(100),

                -- Certificado A1
                certificado_path VARCHAR(500),
                certificado_senha VARCHAR(255),
                certificado_validade DATE,

                -- Convênio/Cobrança
                convenio VARCHAR(20) NOT NULL,
                carteira VARCHAR(10) NOT NULL DEFAULT '101',

                -- Ambiente
                ambiente VARCHAR(20) NOT NULL DEFAULT 'sandbox',

                -- URLs (preenchidas automaticamente baseado no ambiente)
                url_autenticacao VARCHAR(500),
                url_api VARCHAR(500),

                -- Token (cache)
                access_token TEXT,
                token_expira_em TIMESTAMP,

                -- Controle
                ativo BOOLEAN NOT NULL DEFAULT true,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Índices para performance
        $this->addSql("CREATE INDEX IF NOT EXISTS idx_config_api_banco_conta ON configuracoes_api_banco(conta_bancaria_id)");
        $this->addSql("CREATE INDEX IF NOT EXISTS idx_config_api_banco_banco ON configuracoes_api_banco(banco_id)");
        $this->addSql("CREATE INDEX IF NOT EXISTS idx_config_api_banco_ambiente ON configuracoes_api_banco(ambiente)");

        // Índice único para evitar duplicatas (mesma conta + ambiente)
        $this->addSql("CREATE UNIQUE INDEX IF NOT EXISTS idx_config_api_banco_unique ON configuracoes_api_banco(conta_bancaria_id, ambiente)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP TABLE IF EXISTS configuracoes_api_banco CASCADE");
    }
}
