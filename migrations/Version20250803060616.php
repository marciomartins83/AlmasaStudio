<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250803060616 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajusta tabela pessoas para refletir campos da entity Pessoa antiga';
    }

    public function up(Schema $schema): void
    {
        // Renomeia PK id → idpessoa
        $this->addSql('ALTER TABLE pessoas RENAME COLUMN id TO idpessoa');

        // Adiciona coluna theme_light (boolean, default true)
        $this->addSql('ALTER TABLE pessoas ADD COLUMN theme_light BOOLEAN DEFAULT true NOT NULL');

        // Adiciona coluna user_id (FK para users.id)
        $this->addSql('ALTER TABLE pessoas ADD COLUMN user_id INT');
        $this->addSql('ALTER TABLE pessoas
                       ADD CONSTRAINT fk_pessoas_user
                       FOREIGN KEY (user_id) REFERENCES "users" (id)
                       ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');

        // Garante o índice único (one-to-one)
        $this->addSql('CREATE UNIQUE INDEX UNIQ_PESSOA_USER ON pessoas (user_id)');

        // Ajusta dt_cadastro para DateTimeImmutable
        $this->addSql('ALTER TABLE pessoas ALTER COLUMN dt_cadastro TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE pessoas ALTER COLUMN dt_cadastro SET DEFAULT NOW()');
    }

    public function down(Schema $schema): void
    {
        // Remove FK e índice
        $this->addSql('ALTER TABLE pessoas DROP CONSTRAINT fk_pessoas_user');
        $this->addSql('DROP INDEX UNIQ_PESSOA_USER');

        // Remove colunas
        $this->addSql('ALTER TABLE pessoas DROP COLUMN theme_light');
        $this->addSql('ALTER TABLE pessoas DROP COLUMN user_id');

        // Reverte PK
        $this->addSql('ALTER TABLE pessoas RENAME COLUMN idpessoa TO id');
    }
}
