<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration para criação do módulo completo de imóveis
 * Cria 9 tabelas: condominios, propriedades_catalogo, imoveis,
 * imoveis_propriedades, imoveis_medidores, imoveis_garantias,
 * imoveis_fotos, imoveis_contratos + dados em tipos_imoveis
 */
final class Version20251129230000_CriarModuloImoveis extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Criação do módulo completo de imóveis com todas as tabelas e relacionamentos';
    }

    public function up(Schema $schema): void
    {
        // 0. Corrigir IDs duplicados em enderecos antes de adicionar PK
        $this->addSql("
            UPDATE enderecos
            SET id = nextval('enderecos_id_seq')
            WHERE id IN (
                SELECT id FROM (
                    SELECT id, ROW_NUMBER() OVER (PARTITION BY id ORDER BY id) as rn
                    FROM enderecos
                ) sub WHERE rn > 1
            )
        ");

        // 1. Garantir que enderecos tenha PK (pode já ter sido adicionada)
        $this->addSql("DO $$ BEGIN
            IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'enderecos_pkey') THEN
                ALTER TABLE enderecos ADD PRIMARY KEY (id);
            END IF;
        END $$");

        // 2. Garantir que tipos_imoveis tenha PK (pode já ter sido adicionada)
        $this->addSql("DO $$ BEGIN
            IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'tipos_imoveis_pkey') THEN
                ALTER TABLE tipos_imoveis ADD PRIMARY KEY (id);
            END IF;
        END $$");

        // 3. Garantir que pessoas tenha PK (pode já ter sido adicionada)
        $this->addSql("DO $$ BEGIN
            IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'pessoas_pkey') THEN
                ALTER TABLE pessoas ADD PRIMARY KEY (idpessoa);
            END IF;
        END $$");

        // 3. Tabela condominios
        $this->addSql("
            CREATE TABLE condominios (
                id                  SERIAL PRIMARY KEY,
                nome                VARCHAR(100) NOT NULL,
                id_endereco         INT NOT NULL,
                cnpj                VARCHAR(18),
                telefone            VARCHAR(20),
                email               VARCHAR(100),
                nome_sindico        VARCHAR(100),
                valor_condominio    DECIMAL(10,2),
                dia_vencimento      INT,
                observacoes         TEXT,
                ativo               BOOLEAN DEFAULT true,
                created_at          TIMESTAMP DEFAULT NOW(),

                CONSTRAINT fk_cond_endereco FOREIGN KEY (id_endereco) REFERENCES enderecos(id)
            )
        ");

        // 3. Tabela propriedades_catalogo
        $this->addSql("
            CREATE TABLE propriedades_catalogo (
                id          SERIAL PRIMARY KEY,
                nome        VARCHAR(50) NOT NULL UNIQUE,
                categoria   VARCHAR(30),
                icone       VARCHAR(50),
                ativo       BOOLEAN DEFAULT true
            )
        ");

        // Inserir dados iniciais
        $this->addSql("
            INSERT INTO propriedades_catalogo (nome, categoria) VALUES
                ('piscina', 'lazer'),
                ('churrasqueira', 'lazer'),
                ('sauna', 'lazer'),
                ('playground', 'lazer'),
                ('salao_festas', 'lazer'),
                ('salao_jogos', 'lazer'),
                ('quadra_esportiva', 'lazer'),
                ('academia', 'lazer'),
                ('canil', 'lazer'),
                ('interfone', 'seguranca'),
                ('porteiro_eletronico', 'seguranca'),
                ('vigia_24h', 'seguranca'),
                ('cerca_eletrica', 'seguranca'),
                ('grades', 'seguranca'),
                ('elevador', 'infraestrutura'),
                ('aquecimento_central', 'infraestrutura'),
                ('gas_encanado', 'infraestrutura'),
                ('jardim', 'area_externa'),
                ('quintal', 'area_externa'),
                ('varanda', 'area_externa'),
                ('terraco', 'area_externa'),
                ('edicula', 'area_externa'),
                ('cercado', 'area_externa'),
                ('lavabo', 'comodos'),
                ('despensa', 'comodos'),
                ('lavanderia', 'comodos'),
                ('rouparia', 'comodos'),
                ('sotao', 'comodos'),
                ('mesanino', 'comodos'),
                ('box_despejo', 'comodos'),
                ('sala_tv', 'comodos'),
                ('copa', 'comodos'),
                ('mobiliado', 'outros'),
                ('armarios_embutidos', 'outros'),
                ('tvcabo', 'outros'),
                ('telefone_instalado', 'outros')
        ");

        // 4. Tabela imoveis (PRINCIPAL - 63 campos)
        $this->addSql("
            CREATE TABLE imoveis (
                -- IDENTIFICAÇÃO
                id                      SERIAL PRIMARY KEY,
                codigo_interno          VARCHAR(20) UNIQUE,

                -- RELACIONAMENTOS
                id_tipo_imovel          INT NOT NULL,
                id_endereco             INT NOT NULL,
                id_condominio           INT NULL,
                id_pessoa_proprietario  INT NOT NULL,
                id_pessoa_fiador        INT NULL,
                id_pessoa_corretor      INT NULL,

                -- SITUAÇÃO
                situacao                VARCHAR(20) NOT NULL,
                tipo_utilizacao         VARCHAR(30),
                ocupacao                VARCHAR(20),
                situacao_financeira     VARCHAR(30),
                aluguel_garantido       BOOLEAN DEFAULT false,
                disponivel_aluguel      BOOLEAN DEFAULT false,
                disponivel_venda        BOOLEAN DEFAULT false,
                disponivel_temporada    BOOLEAN DEFAULT false,

                -- CARACTERÍSTICAS FÍSICAS
                area_total              DECIMAL(10,2),
                area_construida         DECIMAL(10,2),
                area_privativa          DECIMAL(10,2),
                qtd_quartos             INT DEFAULT 0,
                qtd_suites              INT DEFAULT 0,
                qtd_banheiros           INT DEFAULT 0,
                qtd_salas               INT DEFAULT 0,
                qtd_vagas_garagem       INT DEFAULT 0,
                qtd_pavimentos          INT DEFAULT 1,

                -- CONSTRUÇÃO
                ano_construcao          INT,
                data_fundacao           DATE,
                tipo_construcao         VARCHAR(30),
                aptos_por_andar         INT,

                -- VALORES
                valor_aluguel           DECIMAL(10,2),
                valor_venda             DECIMAL(10,2),
                valor_temporada         DECIMAL(10,2),
                valor_condominio        DECIMAL(10,2),
                valor_iptu_mensal       DECIMAL(10,2),
                valor_taxa_lixo         DECIMAL(10,2),
                valor_mercado           DECIMAL(10,2),
                dia_vencimento          INT,

                -- COMISSÕES
                taxa_administracao      DECIMAL(5,2),
                taxa_minima             DECIMAL(10,2),
                comissao_locacao        DECIMAL(5,2),
                comissao_venda          DECIMAL(5,2),
                comissao_aluguel        DECIMAL(5,2),
                tipo_remuneracao        VARCHAR(20),

                -- DOCUMENTAÇÃO
                inscricao_imobiliaria   VARCHAR(50),
                matricula_cartorio      VARCHAR(30),
                nome_cartorio           VARCHAR(100),
                nome_contribuinte_iptu  VARCHAR(100),

                -- DESCRIÇÃO
                descricao               TEXT,
                observacoes             TEXT,
                descricao_imediacoes    TEXT,

                -- CHAVES
                tem_chaves              BOOLEAN DEFAULT false,
                qtd_chaves              INT DEFAULT 0,
                numero_chave            VARCHAR(20),
                localizacao_chaves      VARCHAR(100),
                numero_controle_remoto  VARCHAR(30),

                -- PUBLICAÇÃO
                publicar_site           BOOLEAN DEFAULT true,
                publicar_zap            BOOLEAN DEFAULT false,
                publicar_vivareal       BOOLEAN DEFAULT false,
                publicar_gruposp        BOOLEAN DEFAULT false,
                ocultar_valor_site      BOOLEAN DEFAULT false,
                tem_placa               BOOLEAN DEFAULT false,

                -- AUDITORIA
                data_cadastro           TIMESTAMP DEFAULT NOW(),
                updated_at              TIMESTAMP DEFAULT NOW(),

                -- CONSTRAINTS
                CONSTRAINT fk_imovel_tipo FOREIGN KEY (id_tipo_imovel) REFERENCES tipos_imoveis(id),
                CONSTRAINT fk_imovel_endereco FOREIGN KEY (id_endereco) REFERENCES enderecos(id),
                CONSTRAINT fk_imovel_condominio FOREIGN KEY (id_condominio) REFERENCES condominios(id),
                CONSTRAINT fk_imovel_proprietario FOREIGN KEY (id_pessoa_proprietario) REFERENCES pessoas(idpessoa),
                CONSTRAINT fk_imovel_fiador FOREIGN KEY (id_pessoa_fiador) REFERENCES pessoas(idpessoa),
                CONSTRAINT fk_imovel_corretor FOREIGN KEY (id_pessoa_corretor) REFERENCES pessoas(idpessoa),
                CONSTRAINT chk_dia_vencimento CHECK (dia_vencimento BETWEEN 1 AND 31)
            )
        ");

        // Criar índices para otimização
        $this->addSql("CREATE INDEX idx_imoveis_situacao ON imoveis(situacao)");
        $this->addSql("CREATE INDEX idx_imoveis_tipo ON imoveis(id_tipo_imovel)");
        $this->addSql("CREATE INDEX idx_imoveis_proprietario ON imoveis(id_pessoa_proprietario)");
        $this->addSql("CREATE INDEX idx_imoveis_corretor ON imoveis(id_pessoa_corretor)");
        $this->addSql("CREATE INDEX idx_imoveis_disponivel_aluguel ON imoveis(disponivel_aluguel) WHERE disponivel_aluguel = true");
        $this->addSql("CREATE INDEX idx_imoveis_disponivel_venda ON imoveis(disponivel_venda) WHERE disponivel_venda = true");

        // 5. Tabela imoveis_propriedades (N:N)
        $this->addSql("
            CREATE TABLE imoveis_propriedades (
                id              SERIAL PRIMARY KEY,
                id_imovel       INT NOT NULL,
                id_propriedade  INT NOT NULL,
                created_at      TIMESTAMP DEFAULT NOW(),

                CONSTRAINT fk_prop_imovel FOREIGN KEY (id_imovel) REFERENCES imoveis(id) ON DELETE CASCADE,
                CONSTRAINT fk_prop_catalogo FOREIGN KEY (id_propriedade) REFERENCES propriedades_catalogo(id),
                CONSTRAINT uk_imovel_propriedade UNIQUE(id_imovel, id_propriedade)
            )
        ");

        // 6. Tabela imoveis_medidores
        $this->addSql("
            CREATE TABLE imoveis_medidores (
                id              SERIAL PRIMARY KEY,
                id_imovel       INT NOT NULL,
                tipo_medidor    VARCHAR(20) NOT NULL,
                numero_medidor  VARCHAR(50) NOT NULL,
                concessionaria  VARCHAR(100),
                observacoes     TEXT,
                ativo           BOOLEAN DEFAULT true,
                created_at      TIMESTAMP DEFAULT NOW(),

                CONSTRAINT fk_med_imovel FOREIGN KEY (id_imovel) REFERENCES imoveis(id) ON DELETE CASCADE,
                CONSTRAINT uk_imovel_tipo_medidor UNIQUE(id_imovel, tipo_medidor)
            )
        ");

        // 7. Tabela imoveis_garantias
        $this->addSql("
            CREATE TABLE imoveis_garantias (
                id                          SERIAL PRIMARY KEY,
                id_imovel                   INT NOT NULL,
                aceita_caucao               BOOLEAN DEFAULT false,
                aceita_fiador               BOOLEAN DEFAULT false,
                aceita_seguro_fianca        BOOLEAN DEFAULT false,
                aceita_outras               BOOLEAN DEFAULT false,
                valor_caucao                DECIMAL(10,2),
                qtd_meses_caucao            INT,
                seguradora                  VARCHAR(100),
                numero_apolice              VARCHAR(30),
                vencimento_seguro           DATE,
                valor_seguro                DECIMAL(10,2),
                observacoes                 TEXT,
                created_at                  TIMESTAMP DEFAULT NOW(),
                updated_at                  TIMESTAMP DEFAULT NOW(),

                CONSTRAINT fk_gar_imovel FOREIGN KEY (id_imovel) REFERENCES imoveis(id) ON DELETE CASCADE,
                CONSTRAINT uk_imovel_garantia UNIQUE(id_imovel)
            )
        ");

        // 8. Tabela imoveis_fotos
        $this->addSql("
            CREATE TABLE imoveis_fotos (
                id              SERIAL PRIMARY KEY,
                id_imovel       INT NOT NULL,
                arquivo         VARCHAR(255) NOT NULL,
                caminho         VARCHAR(500) NOT NULL,
                legenda         VARCHAR(255),
                ordem           INT DEFAULT 0,
                capa            BOOLEAN DEFAULT false,
                created_at      TIMESTAMP DEFAULT NOW(),

                CONSTRAINT fk_foto_imovel FOREIGN KEY (id_imovel) REFERENCES imoveis(id) ON DELETE CASCADE
            )
        ");

        $this->addSql("CREATE INDEX idx_fotos_imovel ON imoveis_fotos(id_imovel)");
        $this->addSql("CREATE INDEX idx_fotos_ordem ON imoveis_fotos(id_imovel, ordem)");

        // 9. Tabela imoveis_contratos
        $this->addSql("
            CREATE TABLE imoveis_contratos (
                id                  SERIAL PRIMARY KEY,
                id_imovel           INT NOT NULL,
                id_pessoa_locatario INT,
                id_pessoa_fiador    INT,
                tipo_contrato       VARCHAR(20) NOT NULL,
                data_inicio         DATE NOT NULL,
                data_fim            DATE,
                valor_contrato      DECIMAL(10,2) NOT NULL,
                dia_vencimento      INT,
                status              VARCHAR(20) NOT NULL,
                observacoes         TEXT,
                created_at          TIMESTAMP DEFAULT NOW(),
                updated_at          TIMESTAMP DEFAULT NOW(),

                CONSTRAINT fk_contr_imovel FOREIGN KEY (id_imovel) REFERENCES imoveis(id),
                CONSTRAINT fk_contr_locatario FOREIGN KEY (id_pessoa_locatario) REFERENCES pessoas(idpessoa),
                CONSTRAINT fk_contr_fiador FOREIGN KEY (id_pessoa_fiador) REFERENCES pessoas(idpessoa)
            )
        ");
    }

    public function down(Schema $schema): void
    {
        // Remover tabelas na ordem inversa (respeitando foreign keys)
        $this->addSql('DROP TABLE IF EXISTS imoveis_contratos CASCADE');
        $this->addSql('DROP TABLE IF EXISTS imoveis_fotos CASCADE');
        $this->addSql('DROP TABLE IF EXISTS imoveis_garantias CASCADE');
        $this->addSql('DROP TABLE IF EXISTS imoveis_medidores CASCADE');
        $this->addSql('DROP TABLE IF EXISTS imoveis_propriedades CASCADE');
        $this->addSql('DROP TABLE IF EXISTS imoveis CASCADE');
        $this->addSql('DROP TABLE IF EXISTS propriedades_catalogo CASCADE');
        $this->addSql('DROP TABLE IF EXISTS condominios CASCADE');
    }
}
