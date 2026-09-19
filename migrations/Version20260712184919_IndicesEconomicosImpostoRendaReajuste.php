<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260712184919_IndicesEconomicosImpostoRendaReajuste extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Cria tabelas indices_economicos, faixas_imposto_renda e contratos_reajustes_historico (núcleo financeiro: reajuste automático de aluguel por IGPM/TJ e retenção de IR)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE IF NOT EXISTS indices_economicos (
                id SERIAL PRIMARY KEY,
                tipo VARCHAR(20) NOT NULL,
                competencia VARCHAR(7) NOT NULL,
                tipo_valor VARCHAR(10) NOT NULL DEFAULT 'indice',
                valor_indice NUMERIC(15, 6) DEFAULT NULL,
                valor_percentual NUMERIC(8, 4) DEFAULT NULL,
                observacoes TEXT DEFAULT NULL,
                created_at TIMESTAMP(0) NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP(0) NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT uq_indice_tipo_competencia UNIQUE (tipo, competencia)
            )
        ");
        $this->addSql("CREATE INDEX IF NOT EXISTS idx_indices_tipo ON indices_economicos(tipo)");
        $this->addSql("CREATE INDEX IF NOT EXISTS idx_indices_competencia ON indices_economicos(competencia)");

        $this->addSql("
            CREATE TABLE IF NOT EXISTS faixas_imposto_renda (
                id SERIAL PRIMARY KEY,
                data_vigencia DATE NOT NULL,
                valor_inicial NUMERIC(15, 2) NOT NULL,
                valor_final NUMERIC(15, 2) DEFAULT NULL,
                aliquota NUMERIC(5, 2) NOT NULL,
                parcela_deduzir NUMERIC(15, 2) NOT NULL DEFAULT 0,
                ativo BOOLEAN NOT NULL DEFAULT true,
                created_at TIMESTAMP(0) NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP(0) NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ");
        $this->addSql("CREATE INDEX IF NOT EXISTS idx_faixas_ir_vigencia ON faixas_imposto_renda(data_vigencia)");
        $this->addSql("CREATE INDEX IF NOT EXISTS idx_faixas_ir_ativo ON faixas_imposto_renda(ativo)");

        $this->addSql("
            CREATE TABLE IF NOT EXISTS contratos_reajustes_historico (
                id SERIAL PRIMARY KEY,
                contrato_id INT NOT NULL,
                data_reajuste DATE NOT NULL,
                competencia_base VARCHAR(7) NOT NULL,
                competencia_atual VARCHAR(7) NOT NULL,
                indice_tipo VARCHAR(20) NOT NULL,
                tipo_valor VARCHAR(10) NOT NULL,
                indice_valor_anterior NUMERIC(15, 6) DEFAULT NULL,
                indice_valor_atual NUMERIC(15, 6) DEFAULT NULL,
                percentual_aplicado NUMERIC(8, 4) DEFAULT NULL,
                fator_aplicado NUMERIC(10, 6) NOT NULL,
                valor_anterior NUMERIC(10, 2) NOT NULL,
                valor_novo NUMERIC(10, 2) NOT NULL,
                created_at TIMESTAMP(0) NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_reajustes_contrato FOREIGN KEY (contrato_id) REFERENCES imoveis_contratos(id) ON DELETE CASCADE
            )
        ");
        // Nome IDX_14B9206470AE7BF1 segue a convenção automática do Doctrine para
        // índice de coluna de associação (ManyToOne) — Doctrine ignora nome customizado
        // nessas colunas, então mantemos alinhado ao que o schema comparator espera.
        $this->addSql("CREATE INDEX IF NOT EXISTS \"IDX_14B9206470AE7BF1\" ON contratos_reajustes_historico(contrato_id)");
        $this->addSql("CREATE INDEX IF NOT EXISTS idx_reajustes_data ON contratos_reajustes_historico(data_reajuste)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP TABLE IF EXISTS contratos_reajustes_historico CASCADE");
        $this->addSql("DROP TABLE IF EXISTS faixas_imposto_renda CASCADE");
        $this->addSql("DROP TABLE IF EXISTS indices_economicos CASCADE");
    }
}
