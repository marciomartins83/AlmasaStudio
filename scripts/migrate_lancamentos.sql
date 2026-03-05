-- Migration Script: lancamentos_financeiros → lancamentos
-- Date: 2026-03-05 (v3 — final)
-- Records to migrate: ~506,704 from lancamentos_financeiros
-- Fixes v3: imovel_map IM0005 format, valor_total direto, plano_conta por tipo, credor/pagador corretos

-- Step 0: Clear previous migration (safe — no external references)
DELETE FROM lancamentos;

-- Step 1: Insert with corrected mappings
INSERT INTO lancamentos (
    data_movimento, id_plano_conta, id_imovel, id_proprietario, id_inquilino,
    valor, tipo, historico, numero_recibo, numero_documento, competencia,
    created_at, updated_at, data_vencimento, data_pagamento, numero, centro_custo,
    id_pessoa_credor, id_pessoa_pagador, id_contrato, id_conta_bancaria, id_boleto,
    valor_pago, valor_desconto, valor_juros, valor_multa,
    reter_inss, perc_inss, valor_inss, reter_iss, perc_iss, valor_iss,
    forma_pagamento, tipo_documento, status, suspenso_motivo, origem, id_processo,
    observacoes, created_by
)
SELECT
    lf.data_lancamento,

    -- id_plano_conta: mapeado por tipo_lancamento (id_conta é NULL em 100% dos registros)
    CASE
        WHEN lf.tipo_lancamento = 'aluguel' THEN 995    -- Aluguel (codigo 1001)
        WHEN lf.tipo_lancamento = 'receita' THEN 1029   -- Receitas Diversas (codigo 1013)
        WHEN lf.tipo_lancamento = 'despesa' THEN 1021   -- Despesas Gerais (codigo 2011)
        ELSE 995
    END,

    lf.id_imovel,
    lf.id_proprietario,
    lf.id_inquilino,

    -- valor: usar valor_total direto (NOT soma de sub-componentes)
    lf.valor_total,

    CASE
        WHEN lf.tipo_lancamento IN ('receita', 'aluguel') THEN 'receber'
        WHEN lf.tipo_lancamento = 'despesa' THEN 'pagar'
        ELSE 'receber'
    END,

    COALESCE(lf.historico, lf.descricao),

    CASE WHEN lf.numero_recibo ~ '^[0-9]+$' THEN CAST(lf.numero_recibo AS INTEGER) ELSE NULL END,

    NULL, -- numero_documento
    TO_CHAR(lf.competencia, 'YYYY-MM'),
    lf.created_at,
    lf.updated_at,
    lf.data_vencimento,

    CASE WHEN lf.situacao = 'pago' THEN lf.data_vencimento ELSE NULL END,

    NULL, -- numero
    NULL, -- centro_custo

    -- credor: proprietario para receber (quem recebe o pagamento)
    CASE WHEN lf.tipo_lancamento IN ('receita', 'aluguel') THEN lf.id_proprietario ELSE NULL END,
    -- pagador: inquilino para receber (quem paga); pagar: sem mapeamento claro no source
    CASE WHEN lf.tipo_lancamento IN ('receita', 'aluguel') THEN lf.id_inquilino ELSE NULL END,

    lf.id_contrato,
    lf.id_conta_bancaria,
    NULL, -- id_boleto

    COALESCE(lf.valor_pago, 0)::numeric(15,2),
    COALESCE(lf.valor_desconto, 0)::numeric(15,2),
    COALESCE(lf.valor_juros, 0)::numeric(15,2),
    COALESCE(lf.valor_multa, 0)::numeric(15,2),

    false, NULL, NULL, -- INSS
    false, NULL, NULL, -- ISS
    NULL, NULL, -- forma_pagamento, tipo_documento

    CASE
        WHEN lf.situacao = 'aberto' THEN 'aberto'
        WHEN lf.situacao = 'pago' THEN 'pago'
        WHEN lf.situacao = 'cancelado' THEN 'cancelado'
        WHEN lf.situacao = 'estornado' THEN 'cancelado'
        WHEN lf.situacao = 'parcial' THEN 'pago_parcial'
        ELSE 'aberto'
    END,

    NULL, -- suspenso_motivo
    CASE WHEN lf.origem IS NOT NULL THEN lf.origem ELSE 'importacao' END,
    NULL, -- id_processo
    lf.observacoes,
    lf.created_by
FROM lancamentos_financeiros lf
WHERE lf.id IS NOT NULL;
