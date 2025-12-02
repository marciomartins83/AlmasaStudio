<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration para criação do módulo de Informe de Rendimentos / DIMOB
 * Cria 5 tabelas: plano_contas, lancamentos, informes_rendimentos,
 * informes_rendimentos_valores, dimob_configuracoes
 */
final class Version20251201_ModuloInformeRendimentos extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Criação do módulo completo de Informe de Rendimentos / DIMOB com 5 tabelas';
    }

    public function up(Schema $schema): void
    {
        // 1. Tabela plano_contas
        $this->addSql("
            CREATE TABLE plano_contas (
                id SERIAL PRIMARY KEY,
                codigo VARCHAR(20) NOT NULL UNIQUE,
                tipo SMALLINT NOT NULL DEFAULT 0,
                descricao VARCHAR(100) NOT NULL,
                incide_taxa_admin BOOLEAN NOT NULL DEFAULT false,
                incide_ir BOOLEAN NOT NULL DEFAULT false,
                entra_informe BOOLEAN NOT NULL DEFAULT false,
                entra_desconto BOOLEAN NOT NULL DEFAULT false,
                entra_multa BOOLEAN NOT NULL DEFAULT false,
                codigo_contabil VARCHAR(20),
                ativo BOOLEAN NOT NULL DEFAULT true,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $this->addSql("CREATE INDEX idx_plano_contas_codigo ON plano_contas(codigo)");
        $this->addSql("CREATE INDEX idx_plano_contas_ativo ON plano_contas(ativo)");

        // 2. Tabela lancamentos
        $this->addSql("
            CREATE TABLE lancamentos (
                id SERIAL PRIMARY KEY,
                data DATE NOT NULL,
                id_plano_conta INTEGER NOT NULL,
                id_imovel INTEGER,
                id_proprietario INTEGER,
                id_inquilino INTEGER,
                valor DECIMAL(15,2) NOT NULL DEFAULT 0,
                tipo_sinal CHAR(1) NOT NULL DEFAULT 'C',
                historico VARCHAR(255),
                numero_recibo INTEGER,
                numero_documento VARCHAR(50),
                competencia DATE NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

                CONSTRAINT fk_lancamentos_plano_conta FOREIGN KEY (id_plano_conta) REFERENCES plano_contas(id),
                CONSTRAINT fk_lancamentos_imovel FOREIGN KEY (id_imovel) REFERENCES imoveis(id),
                CONSTRAINT fk_lancamentos_proprietario FOREIGN KEY (id_proprietario) REFERENCES pessoas(idpessoa),
                CONSTRAINT fk_lancamentos_inquilino FOREIGN KEY (id_inquilino) REFERENCES pessoas(idpessoa)
            )
        ");

        $this->addSql("CREATE INDEX idx_lancamentos_data ON lancamentos(data)");
        $this->addSql("CREATE INDEX idx_lancamentos_competencia ON lancamentos(competencia)");
        $this->addSql("CREATE INDEX idx_lancamentos_proprietario ON lancamentos(id_proprietario)");
        $this->addSql("CREATE INDEX idx_lancamentos_inquilino ON lancamentos(id_inquilino)");
        $this->addSql("CREATE INDEX idx_lancamentos_imovel ON lancamentos(id_imovel)");
        $this->addSql("CREATE INDEX idx_lancamentos_plano_conta ON lancamentos(id_plano_conta)");

        // 3. Tabela informes_rendimentos
        $this->addSql("
            CREATE TABLE informes_rendimentos (
                id SERIAL PRIMARY KEY,
                ano INTEGER NOT NULL,
                id_proprietario INTEGER NOT NULL,
                id_imovel INTEGER NOT NULL,
                id_inquilino INTEGER NOT NULL,
                id_plano_conta INTEGER NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'pendente',
                data_processamento TIMESTAMP,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

                CONSTRAINT fk_informes_proprietario FOREIGN KEY (id_proprietario) REFERENCES pessoas(idpessoa),
                CONSTRAINT fk_informes_imovel FOREIGN KEY (id_imovel) REFERENCES imoveis(id),
                CONSTRAINT fk_informes_inquilino FOREIGN KEY (id_inquilino) REFERENCES pessoas(idpessoa),
                CONSTRAINT fk_informes_plano_conta FOREIGN KEY (id_plano_conta) REFERENCES plano_contas(id),
                CONSTRAINT uk_informes_composicao UNIQUE(ano, id_proprietario, id_imovel, id_inquilino, id_plano_conta)
            )
        ");

        $this->addSql("CREATE INDEX idx_informes_ano ON informes_rendimentos(ano)");
        $this->addSql("CREATE INDEX idx_informes_proprietario ON informes_rendimentos(id_proprietario)");
        $this->addSql("CREATE INDEX idx_informes_status ON informes_rendimentos(status)");

        // 4. Tabela informes_rendimentos_valores
        $this->addSql("
            CREATE TABLE informes_rendimentos_valores (
                id SERIAL PRIMARY KEY,
                id_informe INTEGER NOT NULL,
                mes SMALLINT NOT NULL CHECK (mes >= 1 AND mes <= 12),
                valor DECIMAL(15,2) NOT NULL DEFAULT 0,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

                CONSTRAINT fk_informes_valores_informe FOREIGN KEY (id_informe) REFERENCES informes_rendimentos(id) ON DELETE CASCADE,
                CONSTRAINT uk_informes_valores_mes UNIQUE(id_informe, mes)
            )
        ");

        $this->addSql("CREATE INDEX idx_informes_valores_informe ON informes_rendimentos_valores(id_informe)");

        // 5. Tabela dimob_configuracoes
        $this->addSql("
            CREATE TABLE dimob_configuracoes (
                id SERIAL PRIMARY KEY,
                ano INTEGER NOT NULL UNIQUE,
                cnpj_declarante VARCHAR(18) NOT NULL,
                cpf_responsavel VARCHAR(14) NOT NULL,
                codigo_cidade VARCHAR(10) NOT NULL,
                declaracao_retificadora BOOLEAN NOT NULL DEFAULT false,
                situacao_especial BOOLEAN NOT NULL DEFAULT false,
                data_geracao TIMESTAMP,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // 6. SEED - Dados iniciais do Plano de Contas
        $this->addSql("
            INSERT INTO plano_contas (codigo, tipo, descricao, incide_taxa_admin, incide_ir, entra_informe, entra_desconto, entra_multa) VALUES
            ('1.001', 0, 'Aluguel', true, true, true, false, true),
            ('1.002', 0, 'Desconto', false, false, false, true, false),
            ('1.003', 0, 'Multa', false, false, false, false, false),
            ('1.004', 0, 'I.R.', false, false, false, false, true),
            ('1.005', 0, 'Seg Fiança', false, false, false, false, false),
            ('1.006', 0, 'Condomínio', false, false, false, false, false),
            ('1.007', 0, 'CPMF', false, false, false, false, false),
            ('1.008', 0, 'IPTU', false, false, false, false, true),
            ('1.009', 0, 'Acordo', false, false, false, false, true),
            ('2.001', 1, 'Taxa de Administração', false, false, false, false, false),
            ('2.002', 1, 'IPTU', false, false, false, false, false),
            ('2.003', 1, 'Depósito Efetuado', false, false, false, false, false),
            ('2.004', 1, 'CPMF', false, false, false, false, false),
            ('2.005', 1, 'Encargos Sociais', false, false, false, false, false),
            ('5.001', 3, 'Caixa', false, false, false, false, false),
            ('5.002', 2, 'Condomínio', false, false, false, false, false),
            ('5.003', 2, 'Taxa Adm.', false, false, false, false, false),
            ('2.021', 1, 'Reembolso', false, false, false, false, false),
            ('2.022', 1, 'Salários', false, false, false, false, false),
            ('2.023', 1, 'Manutenção Jardim', false, false, false, false, false),
            ('2.024', 1, 'Pró - Labore', false, false, false, false, false),
            ('2.006', 1, 'Combustível', false, false, false, false, false),
            ('2.007', 1, 'Prestador de Serviço', false, false, false, false, false),
            ('2.008', 1, 'Taxas Bancárias - Boletos', false, false, false, false, false),
            ('2.009', 1, 'Material de Escritório', false, false, false, false, false),
            ('2.010', 1, 'Material de Cozinha/Limpeza', false, false, false, false, false),
            ('2.011', 1, 'Despesas Gerais', false, false, false, false, false),
            ('2.012', 1, 'Publicidade', false, false, false, false, false),
            ('2.013', 1, 'Manutenção Veículo', false, false, false, false, false),
            ('2.014', 1, 'Seguro', false, false, false, false, false),
            ('2.015', 1, 'Reforma de Imóvel', false, false, false, false, false),
            ('1.010', 0, 'Honorários de Administração', false, false, false, false, false),
            ('1.011', 0, 'Taxa de Locação', false, false, false, false, false),
            ('1.012', 0, 'Multa Sobre Aluguéis', false, false, false, false, false),
            ('1.013', 0, 'Receitas Diversas', false, false, false, false, false),
            ('2.016', 1, 'Sindicatos / Conselhos', false, false, false, false, false),
            ('2.017', 1, 'Cartão de Crédito', false, false, false, false, false),
            ('2.018', 1, 'Faculdade', false, false, false, false, false),
            ('2.019', 1, 'Manutenção Prédio', false, false, false, false, false),
            ('2.020', 1, 'Máquinas / Equipamentos', false, false, false, false, false)
        ");

        // Comentários nas tabelas
        $this->addSql("COMMENT ON TABLE plano_contas IS 'Plano de contas para lançamentos financeiros'");
        $this->addSql("COMMENT ON TABLE lancamentos IS 'Lançamentos financeiros do sistema'");
        $this->addSql("COMMENT ON TABLE informes_rendimentos IS 'Informes de rendimentos por proprietário/imóvel/inquilino/conta'");
        $this->addSql("COMMENT ON TABLE informes_rendimentos_valores IS 'Valores mensais dos informes de rendimentos'");
        $this->addSql("COMMENT ON TABLE dimob_configuracoes IS 'Configurações para geração do arquivo DIMOB da Receita Federal'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP TABLE IF EXISTS informes_rendimentos_valores CASCADE");
        $this->addSql("DROP TABLE IF EXISTS informes_rendimentos CASCADE");
        $this->addSql("DROP TABLE IF EXISTS lancamentos CASCADE");
        $this->addSql("DROP TABLE IF EXISTS dimob_configuracoes CASCADE");
        $this->addSql("DROP TABLE IF EXISTS plano_contas CASCADE");
    }
}
