<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260303_AddCodToPessoasTipos extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add cod legacy field to pessoas_corretores, pessoas_corretoras, pessoas_pretendentes, pessoas_socios, pessoas_advogados';
    }

    public function up(Schema $schema): void
    {
        // Adicionar colunas cod
        $this->addSql('ALTER TABLE pessoas_corretores ADD cod INT DEFAULT NULL');
        $this->addSql('ALTER TABLE pessoas_corretoras ADD cod INT DEFAULT NULL');
        $this->addSql('ALTER TABLE pessoas_pretendentes ADD cod INT DEFAULT NULL');
        $this->addSql('ALTER TABLE pessoas_socios ADD cod INT DEFAULT NULL');
        $this->addSql('ALTER TABLE pessoas_advogados ADD cod INT DEFAULT NULL');

        // Backfill: copiar cod de pessoas via id_pessoa
        $this->addSql('
            UPDATE pessoas_corretores pc
            SET cod = p.cod
            FROM pessoas p
            WHERE pc.id_pessoa = p.idpessoa
            AND pc.cod IS NULL
            AND p.cod IS NOT NULL
        ');

        $this->addSql('
            UPDATE pessoas_corretoras pc
            SET cod = p.cod
            FROM pessoas p
            WHERE pc.id_pessoa = p.idpessoa
            AND pc.cod IS NULL
            AND p.cod IS NOT NULL
        ');

        $this->addSql('
            UPDATE pessoas_pretendentes pp
            SET cod = p.cod
            FROM pessoas p
            WHERE pp.id_pessoa = p.idpessoa
            AND pp.cod IS NULL
            AND p.cod IS NOT NULL
        ');

        $this->addSql('
            UPDATE pessoas_socios ps
            SET cod = p.cod
            FROM pessoas p
            WHERE ps.id_pessoa = p.idpessoa
            AND ps.cod IS NULL
            AND p.cod IS NOT NULL
        ');

        $this->addSql('
            UPDATE pessoas_advogados pa
            SET cod = p.cod
            FROM pessoas p
            WHERE pa.id_pessoa = p.idpessoa
            AND pa.cod IS NULL
            AND p.cod IS NOT NULL
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE pessoas_corretores DROP cod');
        $this->addSql('ALTER TABLE pessoas_corretoras DROP cod');
        $this->addSql('ALTER TABLE pessoas_pretendentes DROP cod');
        $this->addSql('ALTER TABLE pessoas_socios DROP cod');
        $this->addSql('ALTER TABLE pessoas_advogados DROP cod');
    }
}