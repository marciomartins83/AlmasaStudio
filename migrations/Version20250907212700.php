<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250907212700 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove as colunas redundantes contato_nome e contato_telefone da tabela pessoas_corretoras.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE pessoas_corretoras DROP contato_nome');
        $this->addSql('ALTER TABLE pessoas_corretoras DROP contato_telefone');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE pessoas_corretoras ADD contato_nome VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE pessoas_corretoras ADD contato_telefone VARCHAR(30) DEFAULT NULL');
    }
}