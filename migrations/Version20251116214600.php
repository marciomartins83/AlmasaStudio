<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration para corrigir sequence de profissoes
 * Ajusta o valor inicial da sequence para evitar conflitos de ID
 */
final class Version20251116214600 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Vincula sequence e ajusta valor inicial para profissoes';
    }

    public function up(Schema $schema): void
    {
        // Profissoes: ajustar sequence para o próximo valor disponível
        $this->addSql("SELECT setval('profissoes_id_seq', COALESCE((SELECT MAX(id) FROM profissoes), 0) + 1, false)");
        $this->addSql('ALTER TABLE profissoes ALTER id SET DEFAULT nextval(\'profissoes_id_seq\')');
    }

    public function down(Schema $schema): void
    {
        // Remover default
        $this->addSql('ALTER TABLE profissoes ALTER id DROP DEFAULT');
    }
}
