<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260921133853 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Boletos: flag copia_emitente por contrato + email_copia_emitente (CC) na config de API bancaria';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE configuracoes_api_banco ADD email_copia_emitente VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE imoveis_contratos ADD copia_emitente BOOLEAN DEFAULT false');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE imoveis_contratos DROP copia_emitente');
        $this->addSql('ALTER TABLE configuracoes_api_banco DROP email_copia_emitente');
    }
}
