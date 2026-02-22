<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260222225004 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tipos_imoveis ALTER created_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE USING created_at::TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE tipos_imoveis ALTER created_at DROP NOT NULL');
        $this->addSql('ALTER TABLE tipos_imoveis ALTER updated_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE USING updated_at::TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE tipos_imoveis ALTER updated_at DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE tipos_imoveis ALTER created_at TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE tipos_imoveis ALTER created_at SET NOT NULL');
        $this->addSql('ALTER TABLE tipos_imoveis ALTER updated_at TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE tipos_imoveis ALTER updated_at SET NOT NULL');
    }
}
