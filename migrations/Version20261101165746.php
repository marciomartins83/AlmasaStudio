<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261101165746 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE contratos_itens_cobranca ADD plano_conta_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE contratos_itens_cobranca ADD CONSTRAINT FK_9D5C3319463DA11 FOREIGN KEY (plano_conta_id) REFERENCES plano_contas (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_9D5C3319463DA11 ON contratos_itens_cobranca (plano_conta_id)');
        $this->addSql('CREATE INDEX idx_contratos_itens_tipo ON contratos_itens_cobranca (tipo_item)');
        $this->addSql('CREATE INDEX idx_contratos_itens_ativo ON contratos_itens_cobranca (ativo)');
        $this->addSql('CREATE UNIQUE INDEX uk_itens_contrato_plano ON contratos_itens_cobranca (contrato_id, plano_conta_id)');
        $this->addSql('ALTER INDEX idx_9d5c33170ae7bf1 RENAME TO idx_contratos_itens_contrato');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE contratos_itens_cobranca DROP CONSTRAINT FK_9D5C3319463DA11');
        $this->addSql('DROP INDEX IDX_9D5C3319463DA11');
        $this->addSql('DROP INDEX idx_contratos_itens_tipo');
        $this->addSql('DROP INDEX idx_contratos_itens_ativo');
        $this->addSql('DROP INDEX uk_itens_contrato_plano');
        $this->addSql('ALTER TABLE contratos_itens_cobranca DROP plano_conta_id');
        $this->addSql('ALTER INDEX idx_contratos_itens_contrato RENAME TO idx_9d5c33170ae7bf1');
    }
}
