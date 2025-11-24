<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Adiciona constraints UNIQUE para prevenir duplicatas em tabelas de tipos de pessoa
 */
final class Version20251124000300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adiciona constraints UNIQUE em id_pessoa para todas as tabelas de tipos de pessoa para prevenir duplicatas';
    }

    public function up(Schema $schema): void
    {
        // Adicionar UNIQUE constraints apenas onde ainda não existem
        // pessoas_contratantes já tem UNIQ_25184ED03AE81E6F
        // pessoas_corretoras já tem UNIQ_80304A5F3AE81E6F

        // Adicionar UNIQUE para pessoas_fiadores
        $this->addSql('CREATE UNIQUE INDEX UNIQ_pessoas_fiadores_id_pessoa ON pessoas_fiadores (id_pessoa)');

        // Adicionar UNIQUE para pessoas_locadores
        $this->addSql('CREATE UNIQUE INDEX UNIQ_pessoas_locadores_id_pessoa ON pessoas_locadores (id_pessoa)');

        // Adicionar UNIQUE para pessoas_corretores
        $this->addSql('CREATE UNIQUE INDEX UNIQ_pessoas_corretores_id_pessoa ON pessoas_corretores (id_pessoa)');

        // Adicionar UNIQUE para pessoas_pretendentes
        $this->addSql('CREATE UNIQUE INDEX UNIQ_pessoas_pretendentes_id_pessoa ON pessoas_pretendentes (id_pessoa)');
    }

    public function down(Schema $schema): void
    {
        // Remover as constraints UNIQUE
        $this->addSql('DROP INDEX IF EXISTS UNIQ_pessoas_fiadores_id_pessoa');
        $this->addSql('DROP INDEX IF EXISTS UNIQ_pessoas_locadores_id_pessoa');
        $this->addSql('DROP INDEX IF EXISTS UNIQ_pessoas_corretores_id_pessoa');
        $this->addSql('DROP INDEX IF EXISTS UNIQ_pessoas_pretendentes_id_pessoa');
    }
}