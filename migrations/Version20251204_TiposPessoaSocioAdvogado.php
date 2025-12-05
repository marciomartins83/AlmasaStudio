<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration para criação dos novos tipos de pessoa: Sócio e Advogado
 * Cria 2 tabelas: pessoas_socios, pessoas_advogados
 * Insere os tipos na tabela tipos_pessoas
 */
final class Version20251204_TiposPessoaSocioAdvogado extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Criação dos tipos de pessoa Sócio e Advogado com tabelas especializadas';
    }

    public function up(Schema $schema): void
    {
        // 1. Tabela pessoas_socios
        $this->addSql("
            CREATE TABLE pessoas_socios (
                id SERIAL PRIMARY KEY,
                id_pessoa INTEGER NOT NULL,
                percentual_participacao DECIMAL(5,2),
                data_entrada DATE,
                tipo_socio VARCHAR(50),
                observacoes TEXT,
                ativo BOOLEAN NOT NULL DEFAULT true,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

                CONSTRAINT fk_socios_pessoa FOREIGN KEY (id_pessoa)
                    REFERENCES pessoas(idpessoa) ON DELETE CASCADE,
                CONSTRAINT uk_socios_pessoa UNIQUE(id_pessoa)
            )
        ");

        $this->addSql("CREATE INDEX idx_pessoas_socios_pessoa ON pessoas_socios(id_pessoa)");
        $this->addSql("CREATE INDEX idx_pessoas_socios_ativo ON pessoas_socios(ativo)");

        // 2. Tabela pessoas_advogados
        $this->addSql("
            CREATE TABLE pessoas_advogados (
                id SERIAL PRIMARY KEY,
                id_pessoa INTEGER NOT NULL,
                numero_oab VARCHAR(20) NOT NULL,
                seccional_oab VARCHAR(2) NOT NULL,
                especialidade VARCHAR(100),
                observacoes TEXT,
                ativo BOOLEAN NOT NULL DEFAULT true,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

                CONSTRAINT fk_advogados_pessoa FOREIGN KEY (id_pessoa)
                    REFERENCES pessoas(idpessoa) ON DELETE CASCADE,
                CONSTRAINT uk_advogados_pessoa UNIQUE(id_pessoa)
            )
        ");

        $this->addSql("CREATE INDEX idx_pessoas_advogados_pessoa ON pessoas_advogados(id_pessoa)");
        $this->addSql("CREATE INDEX idx_pessoas_advogados_oab ON pessoas_advogados(numero_oab)");
        $this->addSql("CREATE INDEX idx_pessoas_advogados_ativo ON pessoas_advogados(ativo)");

        // 3. Inserir novos tipos na tabela tipos_pessoas (se não existirem)
        $this->addSql("
            INSERT INTO tipos_pessoas (tipo, descricao, ativo)
            SELECT 'socio', 'Sócio', true
            WHERE NOT EXISTS (SELECT 1 FROM tipos_pessoas WHERE tipo = 'socio')
        ");

        $this->addSql("
            INSERT INTO tipos_pessoas (tipo, descricao, ativo)
            SELECT 'advogado', 'Advogado', true
            WHERE NOT EXISTS (SELECT 1 FROM tipos_pessoas WHERE tipo = 'advogado')
        ");
    }

    public function down(Schema $schema): void
    {
        // Remover tabelas
        $this->addSql('DROP TABLE IF EXISTS pessoas_advogados CASCADE');
        $this->addSql('DROP TABLE IF EXISTS pessoas_socios CASCADE');

        // Remover tipos (apenas se não houver registros relacionados)
        $this->addSql("DELETE FROM tipos_pessoas WHERE tipo IN ('socio', 'advogado')");
    }
}
