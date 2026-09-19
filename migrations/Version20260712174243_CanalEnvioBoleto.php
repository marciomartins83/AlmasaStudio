<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260712174243_CanalEnvioBoleto extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adiciona canal_envio (Administração/Correio/E-mail) em imoveis_contratos e contratos_cobrancas';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE imoveis_contratos ADD COLUMN IF NOT EXISTS canal_envio VARCHAR(20) NOT NULL DEFAULT 'EMAIL'");
        $this->addSql("ALTER TABLE contratos_cobrancas ADD COLUMN IF NOT EXISTS canal_envio VARCHAR(20) NOT NULL DEFAULT 'EMAIL'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE contratos_cobrancas DROP COLUMN IF EXISTS canal_envio");
        $this->addSql("ALTER TABLE imoveis_contratos DROP COLUMN IF EXISTS canal_envio");
    }
}
