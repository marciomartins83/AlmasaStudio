-- Migration Script: lancamentos_financeiros → lancamentos
-- Date: 2026-03-04
-- Records to migrate: ~49,998 from lancamentos_financeiros (0 currently in lancamentos)

-- Step 1: Insert records from lancamentos_financeiros to lancamentos
INSERT INTO lancamentos (
    data_movimento,
    id_plano_conta,
    id_imovel,
    id_proprietario,
    id_inquilino,
    valor,
    tipo,
    historico,
    numero_recibo,
    numero_documento,
    competencia,
    created_at,
    updated_at,
    data_vencimento,
    data_pagamento,
    numero,
    centro_custo,
    id_pessoa_credor,
    id_pessoa_pagador,
    id_contrato,
    id_conta_bancaria,
    id_boleto,
    valor_pago,
    valor_desconto,
    valor_juros,
    valor_multa,
    reter_inss,
    perc_inss,
    valor_inss,
    reter_iss,
    perc_iss,
    valor_iss,
    forma_pagamento,
    tipo_documento,
    status,
    suspenso_motivo,
    origem,
    id_processo,
    observacoes,
    created_by
)
SELECT
    -- data_movimento: use data_lancamento from lancamentos_financeiros
    lf.data_lancamento,

    -- id_plano_conta: use id_conta from lancamentos_financeiros, with FK validation
    COALESCE(lf.id_conta, (SELECT id FROM plano_contas LIMIT 1)),

    -- id_imovel: direct copy
    lf.id_imovel,

    -- id_proprietario: direct copy
    lf.id_proprietario,

    -- id_inquilino: direct copy
    lf.id_inquilino,

    -- valor: sum all value components (valor_principal + all adicionais)
    (COALESCE(lf.valor_principal, 0) +
     COALESCE(lf.valor_condominio, 0) +
     COALESCE(lf.valor_iptu, 0) +
     COALESCE(lf.valor_agua, 0) +
     COALESCE(lf.valor_luz, 0) +
     COALESCE(lf.valor_gas, 0) +
     COALESCE(lf.valor_outros, 0))::numeric(15,2),

    -- tipo: map tipo_lancamento to tipo
    CASE
        WHEN lf.tipo_lancamento IN ('receita', 'aluguel') THEN 'receber'
        WHEN lf.tipo_lancamento = 'despesa' THEN 'pagar'
        ELSE 'receber'  -- default
    END,

    -- historico: copy from historico or descricao
    COALESCE(lf.historico, lf.descricao),

    -- numero_recibo: convert from varchar to integer
    CASE
        WHEN lf.numero_recibo ~ '^[0-9]+$' THEN CAST(lf.numero_recibo AS INTEGER)
        ELSE NULL
    END,

    -- numero_documento: not in lancamentos_financeiros, leave NULL
    NULL,

    -- competencia: convert date to YYYY-MM format
    TO_CHAR(lf.competencia, 'YYYY-MM'),

    -- created_at: copy
    lf.created_at,

    -- updated_at: copy
    lf.updated_at,

    -- data_vencimento: copy
    lf.data_vencimento,

    -- data_pagamento: set if situacao is 'pago'
    CASE
        WHEN lf.situacao = 'pago' THEN lf.data_vencimento
        ELSE NULL
    END,

    -- numero: not in lancamentos_financeiros, leave NULL
    NULL,

    -- centro_custo: not in lancamentos_financeiros, leave NULL
    NULL,

    -- id_pessoa_credor: for 'receber', use id_proprietario; for 'pagar', NULL
    CASE
        WHEN lf.tipo_lancamento IN ('receita', 'aluguel') THEN lf.id_proprietario
        ELSE NULL
    END,

    -- id_pessoa_pagador: for 'pagar', use id_inquilino; for 'receber', NULL
    CASE
        WHEN lf.tipo_lancamento = 'despesa' THEN lf.id_inquilino
        ELSE NULL
    END,

    -- id_contrato: copy
    lf.id_contrato,

    -- id_conta_bancaria: copy
    lf.id_conta_bancaria,

    -- id_boleto: not in lancamentos_financeiros, leave NULL
    NULL,

    -- valor_pago: copy
    COALESCE(lf.valor_pago, 0)::numeric(15,2),

    -- valor_desconto: copy
    COALESCE(lf.valor_desconto, 0)::numeric(15,2),

    -- valor_juros: copy
    COALESCE(lf.valor_juros, 0)::numeric(15,2),

    -- valor_multa: copy
    COALESCE(lf.valor_multa, 0)::numeric(15,2),

    -- reter_inss: false by default (not in lancamentos_financeiros)
    false,

    -- perc_inss: NULL (not in lancamentos_financeiros)
    NULL,

    -- valor_inss: NULL (not in lancamentos_financeiros)
    NULL,

    -- reter_iss: false by default (not in lancamentos_financeiros)
    false,

    -- perc_iss: NULL (not in lancamentos_financeiros)
    NULL,

    -- valor_iss: NULL (not in lancamentos_financeiros)
    NULL,

    -- forma_pagamento: not in lancamentos_financeiros, leave NULL
    NULL,

    -- tipo_documento: not in lancamentos_financeiros, leave NULL
    NULL,

    -- status: map situacao to status
    CASE
        WHEN lf.situacao = 'aberto' THEN 'aberto'
        WHEN lf.situacao = 'pago' THEN 'pago'
        WHEN lf.situacao = 'cancelado' THEN 'cancelado'
        WHEN lf.situacao = 'estornado' THEN 'cancelado'
        WHEN lf.situacao = 'parcial' THEN 'pago_parcial'
        ELSE 'aberto'  -- default
    END,

    -- suspenso_motivo: NULL (not needed for migration)
    NULL,

    -- origem: map from origem field
    CASE
        WHEN lf.origem IS NOT NULL THEN lf.origem
        ELSE 'importacao'
    END,

    -- id_processo: not in lancamentos_financeiros, leave NULL
    NULL,

    -- observacoes: use observacoes if available
    lf.observacoes,

    -- created_by: use existing value or NULL
    lf.created_by
FROM lancamentos_financeiros lf
WHERE lf.id IS NOT NULL;  -- Ensure we only migrate valid records

-- Step 2: Display migration summary
-- SELECT tipo, status, COUNT(*) as total FROM lancamentos GROUP BY tipo, status ORDER BY tipo, status;
