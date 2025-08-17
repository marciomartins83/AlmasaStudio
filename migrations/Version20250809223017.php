<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250809223017 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE pessoas ADD estado_civil_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE pessoas ADD nacionalidade_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE pessoas ADD naturalidade_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE pessoas DROP estado_civil');
        $this->addSql('ALTER TABLE pessoas DROP nacionalidade');
        $this->addSql('ALTER TABLE pessoas DROP naturalidade');
        $this->addSql('ALTER TABLE pessoas ADD CONSTRAINT FK_18A4F2AC75376D93 FOREIGN KEY (estado_civil_id) REFERENCES estado_civil (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE pessoas ADD CONSTRAINT FK_18A4F2AC21CE4288 FOREIGN KEY (nacionalidade_id) REFERENCES nacionalidades (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE pessoas ADD CONSTRAINT FK_18A4F2AC76BE313 FOREIGN KEY (naturalidade_id) REFERENCES naturalidades (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_18A4F2AC75376D93 ON pessoas (estado_civil_id)');
        $this->addSql('CREATE INDEX IDX_18A4F2AC21CE4288 ON pessoas (nacionalidade_id)');
        $this->addSql('CREATE INDEX IDX_18A4F2AC76BE313 ON pessoas (naturalidade_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE pessoas DROP CONSTRAINT FK_18A4F2AC75376D93');
        $this->addSql('ALTER TABLE pessoas DROP CONSTRAINT FK_18A4F2AC21CE4288');
        $this->addSql('ALTER TABLE pessoas DROP CONSTRAINT FK_18A4F2AC76BE313');
        $this->addSql('DROP INDEX IDX_18A4F2AC75376D93');
        $this->addSql('DROP INDEX IDX_18A4F2AC21CE4288');
        $this->addSql('DROP INDEX IDX_18A4F2AC76BE313');
        $this->addSql('ALTER TABLE pessoas ADD estado_civil VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pessoas ADD nacionalidade VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pessoas ADD naturalidade VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pessoas DROP estado_civil_id');
        $this->addSql('ALTER TABLE pessoas DROP nacionalidade_id');
        $this->addSql('ALTER TABLE pessoas DROP naturalidade_id');
    }
}
