<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration: Expandir tabela lancamentos para Contas a Pagar/Receber
 *
 * Adiciona colunas para suportar:
 * - Tipo (pagar/receber)
 * - Datas de vencimento e pagamento
 * - Vínculos com pessoas (credor/pagador), contratos, contas bancárias, boletos
 * - Valores adicionais (desconto, juros, multa, retenções)
 * - Controle de status e origem
 */
final class Version20251207000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Expandir tabela lancamentos para módulo completo de Contas a Pagar/Receber';
    }

    public function up(Schema $schema): void
    {
        // Renomear colunas existentes para manter compatibilidade
        $this->addSql('ALTER TABLE lancamentos RENAME COLUMN data TO data_movimento');
        $this->addSql('ALTER TABLE lancamentos RENAME COLUMN tipo_sinal TO tipo');

        // Atualizar valores de tipo_sinal para novo formato
        $this->addSql("UPDATE lancamentos SET tipo = CASE WHEN tipo = 'D' THEN 'pagar' ELSE 'receber' END");

        // Alterar tipo da coluna
        $this->addSql('ALTER TABLE lancamentos ALTER COLUMN tipo TYPE VARCHAR(10)');
        $this->addSql("ALTER TABLE lancamentos ALTER COLUMN tipo SET DEFAULT 'receber'");

        // Alterar competencia de DATE para VARCHAR(7) para formato YYYY-MM
        $this->addSql('ALTER TABLE lancamentos ALTER COLUMN competencia TYPE VARCHAR(7) USING to_char(competencia, \'YYYY-MM\')');
        $this->addSql('ALTER TABLE lancamentos ALTER COLUMN competencia DROP NOT NULL');

        // Adicionar novas colunas - DATAS
        $this->addSql('ALTER TABLE lancamentos ADD COLUMN data_vencimento DATE');
        $this->addSql('ALTER TABLE lancamentos ADD COLUMN data_pagamento DATE');

        // Preencher data_vencimento com data_movimento existente
        $this->addSql('UPDATE lancamentos SET data_vencimento = data_movimento WHERE data_vencimento IS NULL');
        $this->addSql('ALTER TABLE lancamentos ALTER COLUMN data_vencimento SET NOT NULL');

        // Adicionar novas colunas - NÚMERO
        $this->addSql('ALTER TABLE lancamentos ADD COLUMN numero INT');

        // Adicionar novas colunas - CLASSIFICAÇÃO
        $this->addSql('ALTER TABLE lancamentos ADD COLUMN centro_custo VARCHAR(20)');

        // Adicionar novas colunas - PESSOAS
        $this->addSql('ALTER TABLE lancamentos ADD COLUMN id_pessoa_credor INT');
        $this->addSql('ALTER TABLE lancamentos ADD COLUMN id_pessoa_pagador INT');

        // Adicionar novas colunas - VÍNCULOS
        $this->addSql('ALTER TABLE lancamentos ADD COLUMN id_contrato INT');
        $this->addSql('ALTER TABLE lancamentos ADD COLUMN id_conta_bancaria INT');
        $this->addSql('ALTER TABLE lancamentos ADD COLUMN id_boleto INT');

        // Adicionar novas colunas - VALORES
        $this->addSql('ALTER TABLE lancamentos ADD COLUMN valor_pago DECIMAL(15,2) DEFAULT 0');
        $this->addSql('ALTER TABLE lancamentos ADD COLUMN valor_desconto DECIMAL(15,2) DEFAULT 0');
        $this->addSql('ALTER TABLE lancamentos ADD COLUMN valor_juros DECIMAL(15,2) DEFAULT 0');
        $this->addSql('ALTER TABLE lancamentos ADD COLUMN valor_multa DECIMAL(15,2) DEFAULT 0');

        // Adicionar novas colunas - RETENÇÕES FISCAIS
        $this->addSql('ALTER TABLE lancamentos ADD COLUMN reter_inss BOOLEAN DEFAULT FALSE');
        $this->addSql('ALTER TABLE lancamentos ADD COLUMN perc_inss DECIMAL(5,2)');
        $this->addSql('ALTER TABLE lancamentos ADD COLUMN valor_inss DECIMAL(15,2)');
        $this->addSql('ALTER TABLE lancamentos ADD COLUMN reter_iss BOOLEAN DEFAULT FALSE');
        $this->addSql('ALTER TABLE lancamentos ADD COLUMN perc_iss DECIMAL(5,2)');
        $this->addSql('ALTER TABLE lancamentos ADD COLUMN valor_iss DECIMAL(15,2)');

        // Adicionar novas colunas - PAGAMENTO
        $this->addSql('ALTER TABLE lancamentos ADD COLUMN forma_pagamento VARCHAR(20)');

        // Adicionar novas colunas - DOCUMENTO
        $this->addSql('ALTER TABLE lancamentos ADD COLUMN tipo_documento VARCHAR(20)');

        // Adicionar novas colunas - CONTROLE
        $this->addSql("ALTER TABLE lancamentos ADD COLUMN status VARCHAR(15) DEFAULT 'aberto'");
        $this->addSql('ALTER TABLE lancamentos ADD COLUMN suspenso_motivo VARCHAR(200)');
        $this->addSql("ALTER TABLE lancamentos ADD COLUMN origem VARCHAR(20) DEFAULT 'manual'");

        // Adicionar novas colunas - JURÍDICO
        $this->addSql('ALTER TABLE lancamentos ADD COLUMN id_processo INT');

        // Adicionar novas colunas - OBSERVAÇÕES
        $this->addSql('ALTER TABLE lancamentos ADD COLUMN observacoes TEXT');

        // Adicionar novas colunas - AUDITORIA
        $this->addSql('ALTER TABLE lancamentos ADD COLUMN created_by INT');

        // Tornar created_at e updated_at nullable para permitir defaults
        $this->addSql('ALTER TABLE lancamentos ALTER COLUMN created_at DROP NOT NULL');
        $this->addSql('ALTER TABLE lancamentos ALTER COLUMN updated_at DROP NOT NULL');
        $this->addSql('ALTER TABLE lancamentos ALTER COLUMN created_at SET DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE lancamentos ALTER COLUMN updated_at SET DEFAULT CURRENT_TIMESTAMP');

        // Adicionar FKs
        $this->addSql('ALTER TABLE lancamentos ADD CONSTRAINT fk_lancamentos_pessoa_credor FOREIGN KEY (id_pessoa_credor) REFERENCES pessoas(idpessoa) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE lancamentos ADD CONSTRAINT fk_lancamentos_pessoa_pagador FOREIGN KEY (id_pessoa_pagador) REFERENCES pessoas(idpessoa) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE lancamentos ADD CONSTRAINT fk_lancamentos_contrato FOREIGN KEY (id_contrato) REFERENCES imoveis_contratos(id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE lancamentos ADD CONSTRAINT fk_lancamentos_conta_bancaria FOREIGN KEY (id_conta_bancaria) REFERENCES contas_bancarias(id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE lancamentos ADD CONSTRAINT fk_lancamentos_boleto FOREIGN KEY (id_boleto) REFERENCES boletos(id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE lancamentos ADD CONSTRAINT fk_lancamentos_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL');

        // Adicionar CONSTRAINTS de validação
        $this->addSql("ALTER TABLE lancamentos ADD CONSTRAINT chk_lancamentos_tipo CHECK (tipo IN ('pagar', 'receber'))");
        $this->addSql("ALTER TABLE lancamentos ADD CONSTRAINT chk_lancamentos_status CHECK (status IN ('aberto', 'pago', 'pago_parcial', 'cancelado', 'suspenso'))");

        // Criar ÍNDICES para performance
        $this->addSql('CREATE INDEX idx_lancamentos_tipo ON lancamentos(tipo)');
        $this->addSql('CREATE INDEX idx_lancamentos_vencimento ON lancamentos(data_vencimento)');
        $this->addSql('CREATE INDEX idx_lancamentos_status ON lancamentos(status)');
        $this->addSql('CREATE INDEX idx_lancamentos_pessoa_credor ON lancamentos(id_pessoa_credor)');
        $this->addSql('CREATE INDEX idx_lancamentos_pessoa_pagador ON lancamentos(id_pessoa_pagador)');
        $this->addSql('CREATE INDEX idx_lancamentos_contrato ON lancamentos(id_contrato)');
        $this->addSql('CREATE INDEX idx_lancamentos_competencia ON lancamentos(competencia)');
    }

    public function down(Schema $schema): void
    {
        // Remover índices
        $this->addSql('DROP INDEX IF EXISTS idx_lancamentos_tipo');
        $this->addSql('DROP INDEX IF EXISTS idx_lancamentos_vencimento');
        $this->addSql('DROP INDEX IF EXISTS idx_lancamentos_status');
        $this->addSql('DROP INDEX IF EXISTS idx_lancamentos_pessoa_credor');
        $this->addSql('DROP INDEX IF EXISTS idx_lancamentos_pessoa_pagador');
        $this->addSql('DROP INDEX IF EXISTS idx_lancamentos_contrato');
        $this->addSql('DROP INDEX IF EXISTS idx_lancamentos_competencia');

        // Remover constraints
        $this->addSql('ALTER TABLE lancamentos DROP CONSTRAINT IF EXISTS chk_lancamentos_tipo');
        $this->addSql('ALTER TABLE lancamentos DROP CONSTRAINT IF EXISTS chk_lancamentos_status');

        // Remover FKs
        $this->addSql('ALTER TABLE lancamentos DROP CONSTRAINT IF EXISTS fk_lancamentos_pessoa_credor');
        $this->addSql('ALTER TABLE lancamentos DROP CONSTRAINT IF EXISTS fk_lancamentos_pessoa_pagador');
        $this->addSql('ALTER TABLE lancamentos DROP CONSTRAINT IF EXISTS fk_lancamentos_contrato');
        $this->addSql('ALTER TABLE lancamentos DROP CONSTRAINT IF EXISTS fk_lancamentos_conta_bancaria');
        $this->addSql('ALTER TABLE lancamentos DROP CONSTRAINT IF EXISTS fk_lancamentos_boleto');
        $this->addSql('ALTER TABLE lancamentos DROP CONSTRAINT IF EXISTS fk_lancamentos_created_by');

        // Remover novas colunas
        $this->addSql('ALTER TABLE lancamentos DROP COLUMN IF EXISTS data_vencimento');
        $this->addSql('ALTER TABLE lancamentos DROP COLUMN IF EXISTS data_pagamento');
        $this->addSql('ALTER TABLE lancamentos DROP COLUMN IF EXISTS numero');
        $this->addSql('ALTER TABLE lancamentos DROP COLUMN IF EXISTS centro_custo');
        $this->addSql('ALTER TABLE lancamentos DROP COLUMN IF EXISTS id_pessoa_credor');
        $this->addSql('ALTER TABLE lancamentos DROP COLUMN IF EXISTS id_pessoa_pagador');
        $this->addSql('ALTER TABLE lancamentos DROP COLUMN IF EXISTS id_contrato');
        $this->addSql('ALTER TABLE lancamentos DROP COLUMN IF EXISTS id_conta_bancaria');
        $this->addSql('ALTER TABLE lancamentos DROP COLUMN IF EXISTS id_boleto');
        $this->addSql('ALTER TABLE lancamentos DROP COLUMN IF EXISTS valor_pago');
        $this->addSql('ALTER TABLE lancamentos DROP COLUMN IF EXISTS valor_desconto');
        $this->addSql('ALTER TABLE lancamentos DROP COLUMN IF EXISTS valor_juros');
        $this->addSql('ALTER TABLE lancamentos DROP COLUMN IF EXISTS valor_multa');
        $this->addSql('ALTER TABLE lancamentos DROP COLUMN IF EXISTS reter_inss');
        $this->addSql('ALTER TABLE lancamentos DROP COLUMN IF EXISTS perc_inss');
        $this->addSql('ALTER TABLE lancamentos DROP COLUMN IF EXISTS valor_inss');
        $this->addSql('ALTER TABLE lancamentos DROP COLUMN IF EXISTS reter_iss');
        $this->addSql('ALTER TABLE lancamentos DROP COLUMN IF EXISTS perc_iss');
        $this->addSql('ALTER TABLE lancamentos DROP COLUMN IF EXISTS valor_iss');
        $this->addSql('ALTER TABLE lancamentos DROP COLUMN IF EXISTS forma_pagamento');
        $this->addSql('ALTER TABLE lancamentos DROP COLUMN IF EXISTS tipo_documento');
        $this->addSql('ALTER TABLE lancamentos DROP COLUMN IF EXISTS status');
        $this->addSql('ALTER TABLE lancamentos DROP COLUMN IF EXISTS suspenso_motivo');
        $this->addSql('ALTER TABLE lancamentos DROP COLUMN IF EXISTS origem');
        $this->addSql('ALTER TABLE lancamentos DROP COLUMN IF EXISTS id_processo');
        $this->addSql('ALTER TABLE lancamentos DROP COLUMN IF EXISTS observacoes');
        $this->addSql('ALTER TABLE lancamentos DROP COLUMN IF EXISTS created_by');

        // Reverter competencia para DATE
        $this->addSql("ALTER TABLE lancamentos ALTER COLUMN competencia TYPE DATE USING (competencia || '-01')::DATE");
        $this->addSql('ALTER TABLE lancamentos ALTER COLUMN competencia SET NOT NULL');

        // Reverter tipo para tipo_sinal
        $this->addSql("UPDATE lancamentos SET tipo = CASE WHEN tipo = 'pagar' THEN 'D' ELSE 'C' END");
        $this->addSql('ALTER TABLE lancamentos ALTER COLUMN tipo TYPE VARCHAR(1)');
        $this->addSql('ALTER TABLE lancamentos RENAME COLUMN tipo TO tipo_sinal');

        // Reverter data_movimento para data
        $this->addSql('ALTER TABLE lancamentos RENAME COLUMN data_movimento TO data');
    }
}
