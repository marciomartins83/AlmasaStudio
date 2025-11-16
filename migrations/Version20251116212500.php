<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration para corrigir sequences de naturalidades e nacionalidades
 * Ajusta o valor inicial das sequences para evitar conflitos de ID
 */
final class Version20251116212500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Vincula sequences e ajusta valores iniciais para naturalidades e nacionalidades';
    }

    public function up(Schema $schema): void
    {
        // Naturalidades: ajustar sequence para o próximo valor disponível
        $this->addSql("SELECT setval('naturalidades_id_seq', COALESCE((SELECT MAX(id) FROM naturalidades), 0) + 1, false)");
        $this->addSql('ALTER TABLE naturalidades ALTER id SET DEFAULT nextval(\'naturalidades_id_seq\')');

        // Nacionalidades: ajustar sequence para o próximo valor disponível
        $this->addSql("SELECT setval('nacionalidades_id_seq', COALESCE((SELECT MAX(id) FROM nacionalidades), 0) + 1, false)");
        $this->addSql('ALTER TABLE nacionalidades ALTER id SET DEFAULT nextval(\'nacionalidades_id_seq\')');
    }

    public function down(Schema $schema): void
    {
        // Remover defaults
        $this->addSql('ALTER TABLE naturalidades ALTER id DROP DEFAULT');
        $this->addSql('ALTER TABLE nacionalidades ALTER id DROP DEFAULT');
    }
}
