<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260305123654 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adiciona campo principal em enderecos e marca o mais antigo de cada pessoa';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE enderecos ADD principal BOOLEAN DEFAULT false NOT NULL');

        // Marca o endereco mais antigo (menor id) de cada pessoa como principal
        $this->addSql('
            UPDATE enderecos SET principal = true
            WHERE id IN (
                SELECT DISTINCT ON (id_pessoa) id
                FROM enderecos
                ORDER BY id_pessoa, id ASC
            )
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE enderecos DROP principal');
    }
}
