<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250304040630 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE pessoa ADD user_id INT NOT NULL');
        $this->addSql('ALTER TABLE pessoa ALTER theme_light SET DEFAULT true');
        $this->addSql('ALTER TABLE pessoa ALTER theme_light SET NOT NULL');
        $this->addSql('ALTER TABLE pessoa ADD CONSTRAINT FK_1CDFAB82A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1CDFAB82A76ED395 ON pessoa (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE pessoa DROP CONSTRAINT FK_1CDFAB82A76ED395');
        $this->addSql('DROP INDEX UNIQ_1CDFAB82A76ED395');
        $this->addSql('ALTER TABLE pessoa DROP user_id');
        $this->addSql('ALTER TABLE pessoa ALTER theme_light DROP DEFAULT');
        $this->addSql('ALTER TABLE pessoa ALTER theme_light DROP NOT NULL');
    }
}
