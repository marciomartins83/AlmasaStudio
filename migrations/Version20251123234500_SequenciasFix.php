<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * CORREÇÃO CRÍTICA: Adiciona sequências PostgreSQL para TODAS as tabelas que precisam de auto-incremento
 *
 * Esta migration foi criada para corrigir o erro:
 * "null value in column 'id' violates not-null constraint"
 *
 * Aplica correções em TODAS as tabelas que usam GeneratedValue(strategy: 'IDENTITY')
 */
final class Version20251123234500_SequenciasFix extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CORREÇÃO CRÍTICA: Adiciona todas as sequências PostgreSQL faltantes para auto-incremento de IDs';
    }

    public function up(Schema $schema): void
    {
        // Tabela principal pessoas
        $this->addSql("DO $$
        BEGIN
            IF NOT EXISTS (SELECT 1 FROM pg_sequences WHERE sequencename = 'pessoas_idpessoa_seq') THEN
                CREATE SEQUENCE pessoas_idpessoa_seq;
                SELECT setval('pessoas_idpessoa_seq', COALESCE((SELECT MAX(idpessoa) FROM pessoas), 1));
                ALTER TABLE pessoas ALTER COLUMN idpessoa SET DEFAULT nextval('pessoas_idpessoa_seq');
            END IF;
        END $$");

        // Tabelas de tipos de pessoas
        $this->addSql("DO $$
        BEGIN
            IF NOT EXISTS (SELECT 1 FROM pg_sequences WHERE sequencename = 'pessoas_tipos_id_seq') THEN
                CREATE SEQUENCE pessoas_tipos_id_seq;
                SELECT setval('pessoas_tipos_id_seq', COALESCE((SELECT MAX(id) FROM pessoas_tipos), 1));
                ALTER TABLE pessoas_tipos ALTER COLUMN id SET DEFAULT nextval('pessoas_tipos_id_seq');
            END IF;
        END $$");

        $this->addSql("DO $$
        BEGIN
            IF NOT EXISTS (SELECT 1 FROM pg_sequences WHERE sequencename = 'pessoas_contratantes_id_seq') THEN
                CREATE SEQUENCE pessoas_contratantes_id_seq;
                SELECT setval('pessoas_contratantes_id_seq', COALESCE((SELECT MAX(id) FROM pessoas_contratantes), 1));
                ALTER TABLE pessoas_contratantes ALTER COLUMN id SET DEFAULT nextval('pessoas_contratantes_id_seq');
            END IF;
        END $$");

        $this->addSql("DO $$
        BEGIN
            IF NOT EXISTS (SELECT 1 FROM pg_sequences WHERE sequencename = 'pessoas_fiadores_id_seq') THEN
                CREATE SEQUENCE pessoas_fiadores_id_seq;
                SELECT setval('pessoas_fiadores_id_seq', COALESCE((SELECT MAX(id) FROM pessoas_fiadores), 1));
                ALTER TABLE pessoas_fiadores ALTER COLUMN id SET DEFAULT nextval('pessoas_fiadores_id_seq');
            END IF;
        END $$");

        $this->addSql("DO $$
        BEGIN
            IF NOT EXISTS (SELECT 1 FROM pg_sequences WHERE sequencename = 'pessoas_locadores_id_seq') THEN
                CREATE SEQUENCE pessoas_locadores_id_seq;
                SELECT setval('pessoas_locadores_id_seq', COALESCE((SELECT MAX(id) FROM pessoas_locadores), 1));
                ALTER TABLE pessoas_locadores ALTER COLUMN id SET DEFAULT nextval('pessoas_locadores_id_seq');
            END IF;
        END $$");

        $this->addSql("DO $$
        BEGIN
            IF NOT EXISTS (SELECT 1 FROM pg_sequences WHERE sequencename = 'pessoas_corretores_id_seq') THEN
                CREATE SEQUENCE pessoas_corretores_id_seq;
                SELECT setval('pessoas_corretores_id_seq', COALESCE((SELECT MAX(id) FROM pessoas_corretores), 1));
                ALTER TABLE pessoas_corretores ALTER COLUMN id SET DEFAULT nextval('pessoas_corretores_id_seq');
            END IF;
        END $$");

        $this->addSql("DO $$
        BEGIN
            IF NOT EXISTS (SELECT 1 FROM pg_sequences WHERE sequencename = 'pessoas_corretoras_id_seq') THEN
                CREATE SEQUENCE pessoas_corretoras_id_seq;
                SELECT setval('pessoas_corretoras_id_seq', COALESCE((SELECT MAX(id) FROM pessoas_corretoras), 1));
                ALTER TABLE pessoas_corretoras ALTER COLUMN id SET DEFAULT nextval('pessoas_corretoras_id_seq');
            END IF;
        END $$");

        $this->addSql("DO $$
        BEGIN
            IF NOT EXISTS (SELECT 1 FROM pg_sequences WHERE sequencename = 'pessoas_pretendentes_id_seq') THEN
                CREATE SEQUENCE pessoas_pretendentes_id_seq;
                SELECT setval('pessoas_pretendentes_id_seq', COALESCE((SELECT MAX(id) FROM pessoas_pretendentes), 1));
                ALTER TABLE pessoas_pretendentes ALTER COLUMN id SET DEFAULT nextval('pessoas_pretendentes_id_seq');
            END IF;
        END $$");

        // Outras tabelas que precisam de sequências
        $tabelas = [
            'agencias', 'bairros', 'bancos', 'chaves_pix', 'cidades',
            'configuracoes_cobranca', 'contas_bancarias', 'contas_vinculadas',
            'emails', 'enderecos', 'estado_civil', 'estados', 'failed_jobs',
            'fiadores_inquilinos', 'formas_retirada', 'layouts_remessa',
            'logradouros', 'permissions', 'personal_access_tokens',
            'pessoas_documentos', 'pessoas_emails', 'pessoas_profissoes',
            'pessoas_telefones', 'razoes_conta', 'regimes_casamento',
            'relacionamentos_familiares', 'requisicoes_responsaveis',
            'roles', 'sessions', 'telefones', 'tipos_atendimento',
            'tipos_carteiras', 'tipos_chaves_pix', 'tipos_contas_bancarias',
            'tipos_documentos', 'tipos_emails', 'tipos_enderecos',
            'tipos_imoveis', 'tipos_pessoas', 'tipos_remessa', 'tipos_telefones',
            'users'
        ];

        foreach ($tabelas as $tabela) {
            $seq_name = $tabela . '_id_seq';
            $this->addSql("DO $$
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_sequences WHERE sequencename = '{$seq_name}') THEN
                    CREATE SEQUENCE {$seq_name};
                    BEGIN
                        PERFORM setval('{$seq_name}', COALESCE((SELECT MAX(id) FROM {$tabela}), 1));
                        ALTER TABLE {$tabela} ALTER COLUMN id SET DEFAULT nextval('{$seq_name}');
                    EXCEPTION WHEN undefined_table THEN
                        -- Tabela não existe, pular
                    END;
                END IF;
            END $$");
        }
    }

    public function down(Schema $schema): void
    {
        // Não remover sequências no down para evitar perda de dados
        $this->addSql('-- Sequências mantidas para segurança');
    }
}