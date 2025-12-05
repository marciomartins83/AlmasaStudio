<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration para adicionar campos adicionais na tabela imoveis_contratos
 * Adiciona 11 novos campos para gestão completa de contratos de locação
 */
final class Version20251204_ContratosLocacaoCamposAdicionais extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adiciona campos adicionais para gestão completa de contratos de locação';
    }

    public function up(Schema $schema): void
    {
        // Adicionar novos campos
        $this->addSql("
            ALTER TABLE imoveis_contratos
            ADD COLUMN IF NOT EXISTS taxa_administracao DECIMAL(5,2) DEFAULT 10.00,
            ADD COLUMN IF NOT EXISTS tipo_garantia VARCHAR(30) DEFAULT 'fiador',
            ADD COLUMN IF NOT EXISTS valor_caucao DECIMAL(10,2) DEFAULT 0,
            ADD COLUMN IF NOT EXISTS indice_reajuste VARCHAR(20) DEFAULT 'IGPM',
            ADD COLUMN IF NOT EXISTS periodicidade_reajuste VARCHAR(20) DEFAULT 'anual',
            ADD COLUMN IF NOT EXISTS data_proximo_reajuste DATE,
            ADD COLUMN IF NOT EXISTS multa_rescisao DECIMAL(10,2) DEFAULT 0,
            ADD COLUMN IF NOT EXISTS carencia_dias INTEGER DEFAULT 0,
            ADD COLUMN IF NOT EXISTS gera_boleto BOOLEAN DEFAULT true,
            ADD COLUMN IF NOT EXISTS envia_email BOOLEAN DEFAULT true,
            ADD COLUMN IF NOT EXISTS ativo BOOLEAN DEFAULT true
        ");

        // Criar índices para otimização de consultas
        $this->addSql("CREATE INDEX IF NOT EXISTS idx_contratos_status ON imoveis_contratos(status)");
        $this->addSql("CREATE INDEX IF NOT EXISTS idx_contratos_ativo ON imoveis_contratos(ativo)");
        $this->addSql("CREATE INDEX IF NOT EXISTS idx_contratos_data_fim ON imoveis_contratos(data_fim)");
        $this->addSql("CREATE INDEX IF NOT EXISTS idx_contratos_data_proximo_reajuste ON imoveis_contratos(data_proximo_reajuste)");
    }

    public function down(Schema $schema): void
    {
        // Remover índices
        $this->addSql('DROP INDEX IF EXISTS idx_contratos_status');
        $this->addSql('DROP INDEX IF EXISTS idx_contratos_ativo');
        $this->addSql('DROP INDEX IF EXISTS idx_contratos_data_fim');
        $this->addSql('DROP INDEX IF EXISTS idx_contratos_data_proximo_reajuste');

        // Remover colunas
        $this->addSql("
            ALTER TABLE imoveis_contratos
            DROP COLUMN IF EXISTS taxa_administracao,
            DROP COLUMN IF EXISTS tipo_garantia,
            DROP COLUMN IF EXISTS valor_caucao,
            DROP COLUMN IF EXISTS indice_reajuste,
            DROP COLUMN IF EXISTS periodicidade_reajuste,
            DROP COLUMN IF EXISTS data_proximo_reajuste,
            DROP COLUMN IF EXISTS multa_rescisao,
            DROP COLUMN IF EXISTS carencia_dias,
            DROP COLUMN IF EXISTS gera_boleto,
            DROP COLUMN IF EXISTS envia_email,
            DROP COLUMN IF EXISTS ativo
        ");
    }
}
