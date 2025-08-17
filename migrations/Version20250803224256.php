<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration para remover trigger updated_at que está causando rollback
 */
final class Version20250803224256 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove trigger updated_at da tabela estados que está causando rollback';
    }

    public function up(Schema $schema): void
    {
        // Remove o trigger updated_at da tabela estados
        $this->addSql('DROP TRIGGER IF EXISTS update_updated_at_estados ON estados');
        
        // Remove a função que atualiza updated_at se não for usada por outras tabelas
        // Comentado por segurança - descomente se tiver certeza
        // $this->addSql('DROP FUNCTION IF EXISTS update_updated_at_column()');
        
        // Remove a coluna updated_at se existir na tabela estados
        $this->addSql('ALTER TABLE estados DROP COLUMN IF EXISTS updated_at');
        
        // Mensagem informativa
        $this->write('Trigger updated_at removido da tabela estados');
    }

    public function down(Schema $schema): void
    {
        // Recriar a coluna updated_at
        $this->addSql('ALTER TABLE estados ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP');
        
        // Recriar a função se não existir
        $this->addSql('
            CREATE OR REPLACE FUNCTION update_updated_at_column()
            RETURNS TRIGGER AS $$
            BEGIN
                NEW.updated_at = CURRENT_TIMESTAMP;
                RETURN NEW;
            END;
            $$ language plpgsql;
        ');
        
        // Recriar o trigger
        $this->addSql('
            CREATE TRIGGER update_updated_at_estados
            BEFORE UPDATE ON estados
            FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
        ');
        
        $this->write('Trigger updated_at recriado na tabela estados');
    }

    public function isTransactional(): bool
    {
        // Não usar transação para DDL commands
        return false;
    }
}
