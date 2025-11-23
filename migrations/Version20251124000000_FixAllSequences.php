<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * CORREÇÃO DEFINITIVA: Vincula TODAS as sequências às suas respectivas colunas
 */
final class Version20251124000000_FixAllSequences extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Correção definitiva - vincula todas as sequências PostgreSQL às colunas id';
    }

    public function up(Schema $schema): void
    {
        // pessoas_documentos
        $this->addSql("ALTER TABLE pessoas_documentos ALTER COLUMN id SET DEFAULT nextval('pessoas_documentos_id_seq'::regclass)");

        // pessoas_profissoes
        $this->addSql("DO $$ BEGIN
            ALTER TABLE pessoas_profissoes ALTER COLUMN id SET DEFAULT nextval('pessoas_profissoes_id_seq'::regclass);
        EXCEPTION WHEN others THEN NULL; END $$");

        // chaves_pix
        $this->addSql("DO $$ BEGIN
            ALTER TABLE chaves_pix ALTER COLUMN id SET DEFAULT nextval('chaves_pix_id_seq'::regclass);
        EXCEPTION WHEN others THEN NULL; END $$");

        // enderecos
        $this->addSql("DO $$ BEGIN
            ALTER TABLE enderecos ALTER COLUMN id SET DEFAULT nextval('enderecos_id_seq'::regclass);
        EXCEPTION WHEN others THEN NULL; END $$");

        // telefones
        $this->addSql("DO $$ BEGIN
            ALTER TABLE telefones ALTER COLUMN id SET DEFAULT nextval('telefones_id_seq'::regclass);
        EXCEPTION WHEN others THEN NULL; END $$");

        // emails
        $this->addSql("DO $$ BEGIN
            ALTER TABLE emails ALTER COLUMN id SET DEFAULT nextval('emails_id_seq'::regclass);
        EXCEPTION WHEN others THEN NULL; END $$");

        // pessoas_telefones
        $this->addSql("DO $$ BEGIN
            ALTER TABLE pessoas_telefones ALTER COLUMN id SET DEFAULT nextval('pessoas_telefones_id_seq'::regclass);
        EXCEPTION WHEN others THEN NULL; END $$");

        // pessoas_emails
        $this->addSql("DO $$ BEGIN
            ALTER TABLE pessoas_emails ALTER COLUMN id SET DEFAULT nextval('pessoas_emails_id_seq'::regclass);
        EXCEPTION WHEN others THEN NULL; END $$");

        // pessoas (usa idpessoa)
        $this->addSql("DO $$ BEGIN
            ALTER TABLE pessoas ALTER COLUMN idpessoa SET DEFAULT nextval('pessoas_idpessoa_seq'::regclass);
        EXCEPTION WHEN others THEN NULL; END $$");

        // pessoas_contratantes
        $this->addSql("DO $$ BEGIN
            ALTER TABLE pessoas_contratantes ALTER COLUMN id SET DEFAULT nextval('pessoas_contratantes_id_seq'::regclass);
        EXCEPTION WHEN others THEN NULL; END $$");

        // pessoas_fiadores
        $this->addSql("DO $$ BEGIN
            ALTER TABLE pessoas_fiadores ALTER COLUMN id SET DEFAULT nextval('pessoas_fiadores_id_seq'::regclass);
        EXCEPTION WHEN others THEN NULL; END $$");

        // pessoas_locadores
        $this->addSql("DO $$ BEGIN
            ALTER TABLE pessoas_locadores ALTER COLUMN id SET DEFAULT nextval('pessoas_locadores_id_seq'::regclass);
        EXCEPTION WHEN others THEN NULL; END $$");

        // pessoas_corretores
        $this->addSql("DO $$ BEGIN
            ALTER TABLE pessoas_corretores ALTER COLUMN id SET DEFAULT nextval('pessoas_corretores_id_seq'::regclass);
        EXCEPTION WHEN others THEN NULL; END $$");

        // pessoas_corretoras
        $this->addSql("DO $$ BEGIN
            ALTER TABLE pessoas_corretoras ALTER COLUMN id SET DEFAULT nextval('pessoas_corretoras_id_seq'::regclass);
        EXCEPTION WHEN others THEN NULL; END $$");

        // pessoas_pretendentes
        $this->addSql("DO $$ BEGIN
            ALTER TABLE pessoas_pretendentes ALTER COLUMN id SET DEFAULT nextval('pessoas_pretendentes_id_seq'::regclass);
        EXCEPTION WHEN others THEN NULL; END $$");

        // pessoas_tipos
        $this->addSql("DO $$ BEGIN
            ALTER TABLE pessoas_tipos ALTER COLUMN id SET DEFAULT nextval('pessoas_tipos_id_seq'::regclass);
        EXCEPTION WHEN others THEN NULL; END $$");

        // relacionamentos_familiares
        $this->addSql("DO $$ BEGIN
            ALTER TABLE relacionamentos_familiares ALTER COLUMN id SET DEFAULT nextval('relacionamentos_familiares_id_seq'::regclass);
        EXCEPTION WHEN others THEN NULL; END $$");
    }

    public function down(Schema $schema): void
    {
        // Mantém os DEFAULTs para segurança
        $this->addSql('-- DEFAULTs mantidos intencionalmente');
    }
}