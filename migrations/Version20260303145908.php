<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260303145908 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add cod legacy field to pessoas and related tables, add flg_proprietario to pessoas_locadores';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE pessoas ADD cod INT DEFAULT NULL');
        $this->addSql('ALTER TABLE pessoas_tipos ADD cod INT DEFAULT NULL');
        $this->addSql('ALTER TABLE pessoas_locadores ADD cod INT DEFAULT NULL');
        $this->addSql('ALTER TABLE pessoas_locadores ADD flg_proprietario BOOLEAN DEFAULT FALSE');
        $this->addSql('ALTER TABLE pessoas_fiadores ADD cod INT DEFAULT NULL');
        $this->addSql('ALTER TABLE pessoas_contratantes ADD cod INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE pessoas DROP cod');
        $this->addSql('ALTER TABLE pessoas_tipos DROP cod');
        $this->addSql('ALTER TABLE pessoas_locadores DROP cod');
        $this->addSql('ALTER TABLE pessoas_locadores DROP flg_proprietario');
        $this->addSql('ALTER TABLE pessoas_fiadores DROP cod');
        $this->addSql('ALTER TABLE pessoas_contratantes DROP cod');
    }
}
