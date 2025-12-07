<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration para criar tabelas de Boletos e Log de API
 *
 * Tabelas criadas:
 * - boletos: Boletos registrados via API Santander
 * - boletos_log_api: Log de todas as comunicações com a API
 */
final class Version20251207_CreateBoletos extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Cria tabelas boletos e boletos_log_api para integração com API Santander';
    }

    public function up(Schema $schema): void
    {
        // Tabela principal de boletos
        $this->addSql("
            CREATE TABLE IF NOT EXISTS boletos (
                id SERIAL PRIMARY KEY,

                -- Relacionamentos
                configuracao_api_id INTEGER NOT NULL REFERENCES configuracoes_api_banco(id) ON DELETE RESTRICT,
                lancamento_financeiro_id INTEGER REFERENCES lancamentos_financeiros(id) ON DELETE SET NULL,
                pessoa_pagador_id INTEGER NOT NULL REFERENCES pessoas(idpessoa) ON DELETE RESTRICT,
                imovel_id INTEGER REFERENCES imoveis(id) ON DELETE SET NULL,

                -- Identificação do boleto
                nosso_numero VARCHAR(20) NOT NULL,
                seu_numero VARCHAR(15),

                -- Dados do título
                valor_nominal DECIMAL(15,2) NOT NULL,
                valor_desconto DECIMAL(15,2) DEFAULT 0,
                valor_multa DECIMAL(15,2) DEFAULT 0,
                valor_juros_dia DECIMAL(15,2) DEFAULT 0,
                valor_abatimento DECIMAL(15,2) DEFAULT 0,

                -- Datas
                data_emissao DATE NOT NULL,
                data_vencimento DATE NOT NULL,
                data_limite_pagamento DATE,

                -- Desconto
                tipo_desconto VARCHAR(20) DEFAULT 'ISENTO',
                data_desconto DATE,

                -- Juros e Multa
                tipo_juros VARCHAR(20) DEFAULT 'ISENTO',
                tipo_multa VARCHAR(20) DEFAULT 'ISENTO',
                data_multa DATE,

                -- Dados retornados pela API
                codigo_barras VARCHAR(44),
                linha_digitavel VARCHAR(47),
                txid_pix VARCHAR(35),
                qrcode_pix TEXT,

                -- Status
                status VARCHAR(30) NOT NULL DEFAULT 'PENDENTE',

                -- Resposta da API
                id_titulo_banco VARCHAR(50),
                convenio_banco VARCHAR(20),

                -- Mensagens
                mensagem_pagador TEXT,

                -- Controle
                tentativas_registro INTEGER DEFAULT 0,
                ultimo_erro TEXT,
                data_registro TIMESTAMP,
                data_pagamento TIMESTAMP,
                valor_pago DECIMAL(15,2),
                data_baixa TIMESTAMP,
                motivo_baixa VARCHAR(100),

                -- Timestamps
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Índices para performance
        $this->addSql("CREATE INDEX IF NOT EXISTS idx_boletos_config ON boletos(configuracao_api_id)");
        $this->addSql("CREATE INDEX IF NOT EXISTS idx_boletos_lancamento ON boletos(lancamento_financeiro_id)");
        $this->addSql("CREATE INDEX IF NOT EXISTS idx_boletos_pagador ON boletos(pessoa_pagador_id)");
        $this->addSql("CREATE INDEX IF NOT EXISTS idx_boletos_imovel ON boletos(imovel_id)");
        $this->addSql("CREATE INDEX IF NOT EXISTS idx_boletos_vencimento ON boletos(data_vencimento)");
        $this->addSql("CREATE INDEX IF NOT EXISTS idx_boletos_status ON boletos(status)");
        $this->addSql("CREATE UNIQUE INDEX IF NOT EXISTS idx_boletos_nosso_numero ON boletos(configuracao_api_id, nosso_numero)");

        // Tabela de log de comunicação com API
        $this->addSql("
            CREATE TABLE IF NOT EXISTS boletos_log_api (
                id SERIAL PRIMARY KEY,
                boleto_id INTEGER REFERENCES boletos(id) ON DELETE CASCADE,

                operacao VARCHAR(50) NOT NULL,
                request_payload TEXT,
                response_payload TEXT,
                http_code INTEGER,
                sucesso BOOLEAN NOT NULL DEFAULT false,
                mensagem_erro TEXT,

                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Índices para log
        $this->addSql("CREATE INDEX IF NOT EXISTS idx_boletos_log_boleto ON boletos_log_api(boleto_id)");
        $this->addSql("CREATE INDEX IF NOT EXISTS idx_boletos_log_operacao ON boletos_log_api(operacao)");
        $this->addSql("CREATE INDEX IF NOT EXISTS idx_boletos_log_created ON boletos_log_api(created_at)");

        // Sequência para nosso número
        $this->addSql("CREATE SEQUENCE IF NOT EXISTS seq_nosso_numero START WITH 1");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP TABLE IF EXISTS boletos_log_api CASCADE");
        $this->addSql("DROP TABLE IF EXISTS boletos CASCADE");
        $this->addSql("DROP SEQUENCE IF EXISTS seq_nosso_numero");
    }
}
