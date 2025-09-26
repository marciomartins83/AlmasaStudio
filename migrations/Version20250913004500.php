<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration para corrigir o auto-incremento da tabela pessoas
 * Resolve o erro SQLSTATE[23502]: Not null violation na coluna idpessoa
 */
final class Version20250913004500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Corrige configuração de auto-incremento na tabela pessoas - adiciona sequence e configura default';
    }

    public function up(Schema $schema): void
    {
        // Verificar se a sequence já existe e criar se necessário
        $this->addSql("
            DO \$\$
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_sequences WHERE sequencename = 'pessoas_idpessoa_seq') THEN
                    CREATE SEQUENCE pessoas_idpessoa_seq;
                    
                    -- Definir valor atual baseado no maior ID existente
                    PERFORM setval('pessoas_idpessoa_seq', COALESCE((SELECT MAX(idpessoa) FROM pessoas), 0) + 1, false);
                END IF;
            END \$\$;
        ");

        // Configurar a coluna idpessoa para usar a sequence como default
        $this->addSql('ALTER TABLE pessoas ALTER COLUMN idpessoa SET DEFAULT nextval(\'pessoas_idpessoa_seq\')');
        
        // Estabelecer propriedade da sequence pela tabela
        $this->addSql('ALTER SEQUENCE pessoas_idpessoa_seq OWNED BY pessoas.idpessoa');
    }

    public function down(Schema $schema): void
    {
        // Remover o default da coluna
        $this->addSql('ALTER TABLE pessoas ALTER COLUMN idpessoa DROP DEFAULT');
        
        // Remover a sequence
        $this->addSql('DROP SEQUENCE IF EXISTS pessoas_idpessoa_seq CASCADE');
    }

    public function isTransactional(): bool
    {
        return true;
    }
}