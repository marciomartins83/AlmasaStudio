<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260308143452 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE almasa_lancamentos (id SERIAL NOT NULL, id_almasa_plano_conta INT NOT NULL, id_pessoa INT DEFAULT NULL, id_conta_bancaria INT DEFAULT NULL, id_lancamento_origem INT DEFAULT NULL, id_lancamento_financeiro_origem INT DEFAULT NULL, tipo VARCHAR(10) NOT NULL, descricao VARCHAR(255) DEFAULT NULL, valor NUMERIC(15, 2) NOT NULL, data_competencia DATE NOT NULL, data_vencimento DATE DEFAULT NULL, data_pagamento DATE DEFAULT NULL, status VARCHAR(20) NOT NULL, observacao TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_8C0AAD2B7FC9CB72 ON almasa_lancamentos (id_almasa_plano_conta)');
        $this->addSql('CREATE INDEX IDX_8C0AAD2B3AE81E6F ON almasa_lancamentos (id_pessoa)');
        $this->addSql('CREATE INDEX IDX_8C0AAD2B20B2308E ON almasa_lancamentos (id_conta_bancaria)');
        $this->addSql('CREATE INDEX IDX_8C0AAD2BB65E7D98 ON almasa_lancamentos (id_lancamento_origem)');
        $this->addSql('CREATE INDEX IDX_8C0AAD2B45616F2F ON almasa_lancamentos (id_lancamento_financeiro_origem)');
        $this->addSql('CREATE TABLE almasa_plano_contas (id SERIAL NOT NULL, id_pai INT DEFAULT NULL, codigo VARCHAR(20) NOT NULL, descricao VARCHAR(255) NOT NULL, tipo VARCHAR(10) NOT NULL, nivel SMALLINT NOT NULL, aceita_lancamentos BOOLEAN NOT NULL, ativo BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E3908A1B20332D99 ON almasa_plano_contas (codigo)');
        $this->addSql('CREATE INDEX IDX_E3908A1B7D9CDA02 ON almasa_plano_contas (id_pai)');
        $this->addSql('ALTER TABLE almasa_lancamentos ADD CONSTRAINT FK_8C0AAD2B7FC9CB72 FOREIGN KEY (id_almasa_plano_conta) REFERENCES almasa_plano_contas (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE almasa_lancamentos ADD CONSTRAINT FK_8C0AAD2B3AE81E6F FOREIGN KEY (id_pessoa) REFERENCES pessoas (idpessoa) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE almasa_lancamentos ADD CONSTRAINT FK_8C0AAD2B20B2308E FOREIGN KEY (id_conta_bancaria) REFERENCES contas_bancarias (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE almasa_lancamentos ADD CONSTRAINT FK_8C0AAD2BB65E7D98 FOREIGN KEY (id_lancamento_origem) REFERENCES lancamentos (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE almasa_lancamentos ADD CONSTRAINT FK_8C0AAD2B45616F2F FOREIGN KEY (id_lancamento_financeiro_origem) REFERENCES lancamentos_financeiros (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE almasa_plano_contas ADD CONSTRAINT FK_E3908A1B7D9CDA02 FOREIGN KEY (id_pai) REFERENCES almasa_plano_contas (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE plano_contas ADD id_almasa_plano_conta INT DEFAULT NULL');
        $this->addSql('ALTER TABLE plano_contas ADD CONSTRAINT FK_DB6AFAE17FC9CB72 FOREIGN KEY (id_almasa_plano_conta) REFERENCES almasa_plano_contas (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_DB6AFAE17FC9CB72 ON plano_contas (id_almasa_plano_conta)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE plano_contas DROP CONSTRAINT FK_DB6AFAE17FC9CB72');
        $this->addSql('ALTER TABLE almasa_lancamentos DROP CONSTRAINT FK_8C0AAD2B7FC9CB72');
        $this->addSql('ALTER TABLE almasa_lancamentos DROP CONSTRAINT FK_8C0AAD2B3AE81E6F');
        $this->addSql('ALTER TABLE almasa_lancamentos DROP CONSTRAINT FK_8C0AAD2B20B2308E');
        $this->addSql('ALTER TABLE almasa_lancamentos DROP CONSTRAINT FK_8C0AAD2BB65E7D98');
        $this->addSql('ALTER TABLE almasa_lancamentos DROP CONSTRAINT FK_8C0AAD2B45616F2F');
        $this->addSql('ALTER TABLE almasa_plano_contas DROP CONSTRAINT FK_E3908A1B7D9CDA02');
        $this->addSql('DROP TABLE almasa_lancamentos');
        $this->addSql('DROP TABLE almasa_plano_contas');
        $this->addSql('DROP INDEX IDX_DB6AFAE17FC9CB72');
        $this->addSql('ALTER TABLE plano_contas DROP id_almasa_plano_conta');
    }
}
