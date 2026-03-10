-- ============================================================================
-- MIGRACAO PLANO DE CONTAS ALMASA v2
-- De: 2 grupos (Receitas/Despesas), 3 niveis
-- Para: 5 grupos (Ativo/Passivo/PL/Receitas/Despesas), 5 niveis
-- ============================================================================
-- SEGURO: 0 lancamentos em almasa_lancamentos, 10 vinculos em plano_contas
-- ============================================================================

BEGIN;

-- 0. Ampliar coluna tipo de varchar(10) para varchar(25) (patrimonio_liquido = 20 chars)
ALTER TABLE almasa_plano_contas ALTER COLUMN tipo TYPE varchar(25);

-- 1. Limpar vinculos FK em plano_contas
UPDATE plano_contas SET id_almasa_plano_conta = NULL WHERE id_almasa_plano_conta IS NOT NULL;

-- 2. Limpar tabela (respeitando FK pai/filho)
DELETE FROM almasa_plano_contas WHERE nivel = 5;
DELETE FROM almasa_plano_contas WHERE nivel = 4;
DELETE FROM almasa_plano_contas WHERE nivel = 3;
DELETE FROM almasa_plano_contas WHERE nivel = 2;
DELETE FROM almasa_plano_contas WHERE nivel = 1;

-- 3. Resetar sequence
SELECT setval('almasa_plano_contas_id_seq', 1, false);

-- ============================================================================
-- 4. INSERIR NOVA ESTRUTURA v2
-- ============================================================================

-- === 1. ATIVO ===
INSERT INTO almasa_plano_contas (id, codigo, descricao, tipo, nivel, id_pai, aceita_lancamentos, ativo, created_at, updated_at)
VALUES (1, '1', 'ATIVO', 'ativo', 1, NULL, false, true, NOW(), NOW());

INSERT INTO almasa_plano_contas (id, codigo, descricao, tipo, nivel, id_pai, aceita_lancamentos, ativo, created_at, updated_at)
VALUES (2, '1.1', 'Ativo Circulante', 'ativo', 2, 1, false, true, NOW(), NOW());

-- 1.1.01 Disponivel
INSERT INTO almasa_plano_contas (id, codigo, descricao, tipo, nivel, id_pai, aceita_lancamentos, ativo, created_at, updated_at)
VALUES (3, '1.1.01', 'Disponível', 'ativo', 3, 2, false, true, NOW(), NOW());

INSERT INTO almasa_plano_contas (id, codigo, descricao, tipo, nivel, id_pai, aceita_lancamentos, ativo, created_at, updated_at)
VALUES (4, '1.1.01.001', 'Caixa', 'ativo', 4, 3, true, true, NOW(), NOW());

INSERT INTO almasa_plano_contas (id, codigo, descricao, tipo, nivel, id_pai, aceita_lancamentos, ativo, created_at, updated_at)
VALUES (5, '1.1.01.002', 'Banco Conta Movimento', 'ativo', 4, 3, true, true, NOW(), NOW());

INSERT INTO almasa_plano_contas (id, codigo, descricao, tipo, nivel, id_pai, aceita_lancamentos, ativo, created_at, updated_at)
VALUES (6, '1.1.01.003', 'Aplicações Financeiras', 'ativo', 4, 3, true, true, NOW(), NOW());

-- 1.1.02 Contas a Receber
INSERT INTO almasa_plano_contas (id, codigo, descricao, tipo, nivel, id_pai, aceita_lancamentos, ativo, created_at, updated_at)
VALUES (7, '1.1.02', 'Contas a Receber', 'ativo', 3, 2, false, true, NOW(), NOW());

INSERT INTO almasa_plano_contas (id, codigo, descricao, tipo, nivel, id_pai, aceita_lancamentos, ativo, created_at, updated_at)
VALUES (8, '1.1.02.001', 'Aluguéis a Receber', 'ativo', 4, 7, true, true, NOW(), NOW());

INSERT INTO almasa_plano_contas (id, codigo, descricao, tipo, nivel, id_pai, aceita_lancamentos, ativo, created_at, updated_at)
VALUES (9, '1.1.02.002', 'Taxa de Administração a Receber', 'ativo', 4, 7, true, true, NOW(), NOW());

INSERT INTO almasa_plano_contas (id, codigo, descricao, tipo, nivel, id_pai, aceita_lancamentos, ativo, created_at, updated_at)
VALUES (10, '1.1.02.003', 'Multas Contratuais a Receber', 'ativo', 4, 7, true, true, NOW(), NOW());

INSERT INTO almasa_plano_contas (id, codigo, descricao, tipo, nivel, id_pai, aceita_lancamentos, ativo, created_at, updated_at)
VALUES (11, '1.1.02.004', 'Juros de Mora a Receber', 'ativo', 4, 7, true, true, NOW(), NOW());

-- === 2. PASSIVO ===
INSERT INTO almasa_plano_contas (id, codigo, descricao, tipo, nivel, id_pai, aceita_lancamentos, ativo, created_at, updated_at)
VALUES (12, '2', 'PASSIVO', 'passivo', 1, NULL, false, true, NOW(), NOW());

INSERT INTO almasa_plano_contas (id, codigo, descricao, tipo, nivel, id_pai, aceita_lancamentos, ativo, created_at, updated_at)
VALUES (13, '2.1', 'Passivo Circulante', 'passivo', 2, 12, false, true, NOW(), NOW());

-- 2.1.01 Obrigacoes com Proprietarios
INSERT INTO almasa_plano_contas (id, codigo, descricao, tipo, nivel, id_pai, aceita_lancamentos, ativo, created_at, updated_at)
VALUES (14, '2.1.01', 'Obrigações com Proprietários', 'passivo', 3, 13, false, true, NOW(), NOW());

INSERT INTO almasa_plano_contas (id, codigo, descricao, tipo, nivel, id_pai, aceita_lancamentos, ativo, created_at, updated_at)
VALUES (15, '2.1.01.01', 'Conta Corrente de Proprietários', 'passivo', 4, 14, false, true, NOW(), NOW());

INSERT INTO almasa_plano_contas (id, codigo, descricao, tipo, nivel, id_pai, aceita_lancamentos, ativo, created_at, updated_at)
VALUES (16, '2.1.01.01.001', 'Proprietário João da Silva', 'passivo', 5, 15, true, true, NOW(), NOW());

INSERT INTO almasa_plano_contas (id, codigo, descricao, tipo, nivel, id_pai, aceita_lancamentos, ativo, created_at, updated_at)
VALUES (17, '2.1.01.01.002', 'Proprietário Maria Oliveira', 'passivo', 5, 15, true, true, NOW(), NOW());

INSERT INTO almasa_plano_contas (id, codigo, descricao, tipo, nivel, id_pai, aceita_lancamentos, ativo, created_at, updated_at)
VALUES (18, '2.1.01.01.003', 'Proprietário Empresa ABC', 'passivo', 5, 15, true, true, NOW(), NOW());

-- 2.1.02 Obrigacoes Trabalhistas
INSERT INTO almasa_plano_contas (id, codigo, descricao, tipo, nivel, id_pai, aceita_lancamentos, ativo, created_at, updated_at)
VALUES (19, '2.1.02', 'Obrigações Trabalhistas', 'passivo', 3, 13, false, true, NOW(), NOW());

INSERT INTO almasa_plano_contas (id, codigo, descricao, tipo, nivel, id_pai, aceita_lancamentos, ativo, created_at, updated_at)
VALUES (20, '2.1.02.001', 'Salários a Pagar', 'passivo', 4, 19, true, true, NOW(), NOW());

INSERT INTO almasa_plano_contas (id, codigo, descricao, tipo, nivel, id_pai, aceita_lancamentos, ativo, created_at, updated_at)
VALUES (21, '2.1.02.002', 'INSS a Recolher', 'passivo', 4, 19, true, true, NOW(), NOW());

INSERT INTO almasa_plano_contas (id, codigo, descricao, tipo, nivel, id_pai, aceita_lancamentos, ativo, created_at, updated_at)
VALUES (22, '2.1.02.003', 'FGTS a Recolher', 'passivo', 4, 19, true, true, NOW(), NOW());

INSERT INTO almasa_plano_contas (id, codigo, descricao, tipo, nivel, id_pai, aceita_lancamentos, ativo, created_at, updated_at)
VALUES (23, '2.1.02.004', 'Férias a Pagar', 'passivo', 4, 19, true, true, NOW(), NOW());

INSERT INTO almasa_plano_contas (id, codigo, descricao, tipo, nivel, id_pai, aceita_lancamentos, ativo, created_at, updated_at)
VALUES (24, '2.1.02.005', '13º Salário', 'passivo', 4, 19, true, true, NOW(), NOW());

-- 2.1.03 Obrigacoes Tributarias
INSERT INTO almasa_plano_contas (id, codigo, descricao, tipo, nivel, id_pai, aceita_lancamentos, ativo, created_at, updated_at)
VALUES (25, '2.1.03', 'Obrigações Tributárias', 'passivo', 3, 13, false, true, NOW(), NOW());

INSERT INTO almasa_plano_contas (id, codigo, descricao, tipo, nivel, id_pai, aceita_lancamentos, ativo, created_at, updated_at)
VALUES (26, '2.1.03.001', 'ISS a Recolher', 'passivo', 4, 25, true, true, NOW(), NOW());

INSERT INTO almasa_plano_contas (id, codigo, descricao, tipo, nivel, id_pai, aceita_lancamentos, ativo, created_at, updated_at)
VALUES (27, '2.1.03.002', 'PIS a Recolher', 'passivo', 4, 25, true, true, NOW(), NOW());

INSERT INTO almasa_plano_contas (id, codigo, descricao, tipo, nivel, id_pai, aceita_lancamentos, ativo, created_at, updated_at)
VALUES (28, '2.1.03.003', 'COFINS a Recolher', 'passivo', 4, 25, true, true, NOW(), NOW());

INSERT INTO almasa_plano_contas (id, codigo, descricao, tipo, nivel, id_pai, aceita_lancamentos, ativo, created_at, updated_at)
VALUES (29, '2.1.03.004', 'IRPJ a Recolher', 'passivo', 4, 25, true, true, NOW(), NOW());

INSERT INTO almasa_plano_contas (id, codigo, descricao, tipo, nivel, id_pai, aceita_lancamentos, ativo, created_at, updated_at)
VALUES (30, '2.1.03.005', 'CSLL a Recolher', 'passivo', 4, 25, true, true, NOW(), NOW());

-- === 3. PATRIMONIO LIQUIDO ===
INSERT INTO almasa_plano_contas (id, codigo, descricao, tipo, nivel, id_pai, aceita_lancamentos, ativo, created_at, updated_at)
VALUES (31, '3', 'PATRIMÔNIO LÍQUIDO', 'patrimonio_liquido', 1, NULL, false, true, NOW(), NOW());

INSERT INTO almasa_plano_contas (id, codigo, descricao, tipo, nivel, id_pai, aceita_lancamentos, ativo, created_at, updated_at)
VALUES (32, '3.1', 'Capital Social', 'patrimonio_liquido', 2, 31, false, true, NOW(), NOW());

INSERT INTO almasa_plano_contas (id, codigo, descricao, tipo, nivel, id_pai, aceita_lancamentos, ativo, created_at, updated_at)
VALUES (33, '3.1.01', 'Capital Social Integralizado', 'patrimonio_liquido', 3, 32, true, true, NOW(), NOW());

INSERT INTO almasa_plano_contas (id, codigo, descricao, tipo, nivel, id_pai, aceita_lancamentos, ativo, created_at, updated_at)
VALUES (34, '3.2', 'Conta Corrente de Sócios', 'patrimonio_liquido', 2, 31, false, true, NOW(), NOW());

INSERT INTO almasa_plano_contas (id, codigo, descricao, tipo, nivel, id_pai, aceita_lancamentos, ativo, created_at, updated_at)
VALUES (35, '3.2.01', 'Conta Corrente Sócios', 'patrimonio_liquido', 3, 34, false, true, NOW(), NOW());

INSERT INTO almasa_plano_contas (id, codigo, descricao, tipo, nivel, id_pai, aceita_lancamentos, ativo, created_at, updated_at)
VALUES (36, '3.2.01.001', 'Sócio – Celestino Almeida Silva', 'patrimonio_liquido', 4, 35, true, true, NOW(), NOW());

INSERT INTO almasa_plano_contas (id, codigo, descricao, tipo, nivel, id_pai, aceita_lancamentos, ativo, created_at, updated_at)
VALUES (37, '3.2.01.002', 'Sócio – (outro sócio)', 'patrimonio_liquido', 4, 35, true, true, NOW(), NOW());

-- === 4. RECEITAS ===
INSERT INTO almasa_plano_contas (id, codigo, descricao, tipo, nivel, id_pai, aceita_lancamentos, ativo, created_at, updated_at)
VALUES (38, '4', 'RECEITAS', 'receita', 1, NULL, false, true, NOW(), NOW());

INSERT INTO almasa_plano_contas (id, codigo, descricao, tipo, nivel, id_pai, aceita_lancamentos, ativo, created_at, updated_at)
VALUES (39, '4.1', 'Receitas de Administração', 'receita', 2, 38, false, true, NOW(), NOW());

INSERT INTO almasa_plano_contas (id, codigo, descricao, tipo, nivel, id_pai, aceita_lancamentos, ativo, created_at, updated_at)
VALUES (40, '4.1.01', 'Taxa de Administração de Aluguel', 'receita', 3, 39, true, true, NOW(), NOW());

INSERT INTO almasa_plano_contas (id, codigo, descricao, tipo, nivel, id_pai, aceita_lancamentos, ativo, created_at, updated_at)
VALUES (41, '4.1.02', 'Comissão de Locação', 'receita', 3, 39, true, true, NOW(), NOW());

INSERT INTO almasa_plano_contas (id, codigo, descricao, tipo, nivel, id_pai, aceita_lancamentos, ativo, created_at, updated_at)
VALUES (42, '4.1.03', 'Taxa de Renovação de Contrato', 'receita', 3, 39, true, true, NOW(), NOW());

INSERT INTO almasa_plano_contas (id, codigo, descricao, tipo, nivel, id_pai, aceita_lancamentos, ativo, created_at, updated_at)
VALUES (43, '4.1.04', 'Taxa de Cadastro', 'receita', 3, 39, true, true, NOW(), NOW());

INSERT INTO almasa_plano_contas (id, codigo, descricao, tipo, nivel, id_pai, aceita_lancamentos, ativo, created_at, updated_at)
VALUES (44, '4.2', 'Outras Receitas', 'receita', 2, 38, false, true, NOW(), NOW());

INSERT INTO almasa_plano_contas (id, codigo, descricao, tipo, nivel, id_pai, aceita_lancamentos, ativo, created_at, updated_at)
VALUES (45, '4.2.01', 'Multas Contratuais', 'receita', 3, 44, true, true, NOW(), NOW());

INSERT INTO almasa_plano_contas (id, codigo, descricao, tipo, nivel, id_pai, aceita_lancamentos, ativo, created_at, updated_at)
VALUES (46, '4.2.02', 'Juros de Mora', 'receita', 3, 44, true, true, NOW(), NOW());

INSERT INTO almasa_plano_contas (id, codigo, descricao, tipo, nivel, id_pai, aceita_lancamentos, ativo, created_at, updated_at)
VALUES (47, '4.2.03', 'Serviços de Vistoria', 'receita', 3, 44, true, true, NOW(), NOW());

-- === 5. DESPESAS OPERACIONAIS ===
INSERT INTO almasa_plano_contas (id, codigo, descricao, tipo, nivel, id_pai, aceita_lancamentos, ativo, created_at, updated_at)
VALUES (48, '5', 'DESPESAS OPERACIONAIS', 'despesa', 1, NULL, false, true, NOW(), NOW());

INSERT INTO almasa_plano_contas (id, codigo, descricao, tipo, nivel, id_pai, aceita_lancamentos, ativo, created_at, updated_at)
VALUES (49, '5.1', 'Despesas Administrativas', 'despesa', 2, 48, false, true, NOW(), NOW());

INSERT INTO almasa_plano_contas (id, codigo, descricao, tipo, nivel, id_pai, aceita_lancamentos, ativo, created_at, updated_at)
VALUES (50, '5.1.01', 'Aluguel do Escritório', 'despesa', 3, 49, true, true, NOW(), NOW());

INSERT INTO almasa_plano_contas (id, codigo, descricao, tipo, nivel, id_pai, aceita_lancamentos, ativo, created_at, updated_at)
VALUES (51, '5.1.02', 'Energia Elétrica', 'despesa', 3, 49, true, true, NOW(), NOW());

INSERT INTO almasa_plano_contas (id, codigo, descricao, tipo, nivel, id_pai, aceita_lancamentos, ativo, created_at, updated_at)
VALUES (52, '5.1.03', 'Água', 'despesa', 3, 49, true, true, NOW(), NOW());

INSERT INTO almasa_plano_contas (id, codigo, descricao, tipo, nivel, id_pai, aceita_lancamentos, ativo, created_at, updated_at)
VALUES (53, '5.1.04', 'Internet', 'despesa', 3, 49, true, true, NOW(), NOW());

INSERT INTO almasa_plano_contas (id, codigo, descricao, tipo, nivel, id_pai, aceita_lancamentos, ativo, created_at, updated_at)
VALUES (54, '5.1.05', 'Telefone', 'despesa', 3, 49, true, true, NOW(), NOW());

INSERT INTO almasa_plano_contas (id, codigo, descricao, tipo, nivel, id_pai, aceita_lancamentos, ativo, created_at, updated_at)
VALUES (55, '5.1.06', 'Material de Escritório', 'despesa', 3, 49, true, true, NOW(), NOW());

INSERT INTO almasa_plano_contas (id, codigo, descricao, tipo, nivel, id_pai, aceita_lancamentos, ativo, created_at, updated_at)
VALUES (56, '5.1.07', 'Software Imobiliário', 'despesa', 3, 49, true, true, NOW(), NOW());

INSERT INTO almasa_plano_contas (id, codigo, descricao, tipo, nivel, id_pai, aceita_lancamentos, ativo, created_at, updated_at)
VALUES (57, '5.1.08', 'Contabilidade', 'despesa', 3, 49, true, true, NOW(), NOW());

INSERT INTO almasa_plano_contas (id, codigo, descricao, tipo, nivel, id_pai, aceita_lancamentos, ativo, created_at, updated_at)
VALUES (58, '5.2', 'Despesas com Pessoal', 'despesa', 2, 48, false, true, NOW(), NOW());

INSERT INTO almasa_plano_contas (id, codigo, descricao, tipo, nivel, id_pai, aceita_lancamentos, ativo, created_at, updated_at)
VALUES (59, '5.2.01', 'Salários', 'despesa', 3, 58, true, true, NOW(), NOW());

INSERT INTO almasa_plano_contas (id, codigo, descricao, tipo, nivel, id_pai, aceita_lancamentos, ativo, created_at, updated_at)
VALUES (60, '5.2.02', 'Pró-labore', 'despesa', 3, 58, true, true, NOW(), NOW());

INSERT INTO almasa_plano_contas (id, codigo, descricao, tipo, nivel, id_pai, aceita_lancamentos, ativo, created_at, updated_at)
VALUES (61, '5.2.03', 'INSS', 'despesa', 3, 58, true, true, NOW(), NOW());

INSERT INTO almasa_plano_contas (id, codigo, descricao, tipo, nivel, id_pai, aceita_lancamentos, ativo, created_at, updated_at)
VALUES (62, '5.2.04', 'FGTS', 'despesa', 3, 58, true, true, NOW(), NOW());

-- 5. Atualizar sequence para proximo ID
SELECT setval('almasa_plano_contas_id_seq', (SELECT MAX(id) FROM almasa_plano_contas));

-- ============================================================================
-- 6. REMAPEAR VINCULOS plano_contas → almasa_plano_contas (novos IDs)
-- ============================================================================
-- Receitas cliente → grupo 4 (Receitas Almasa)
UPDATE plano_contas SET id_almasa_plano_conta = 40 WHERE codigo = '2001'; -- Taxa Adm → 4.1.01
UPDATE plano_contas SET id_almasa_plano_conta = 40 WHERE codigo = '2051'; -- Tx Adm Cond → 4.1.01
UPDATE plano_contas SET id_almasa_plano_conta = 41 WHERE codigo = '2046'; -- Taxa Locação → 4.1.02
UPDATE plano_contas SET id_almasa_plano_conta = 40 WHERE codigo = '1030'; -- Tx Adm (Almasa) → 4.1.01
UPDATE plano_contas SET id_almasa_plano_conta = 45 WHERE codigo = '1035'; -- Honorarios → 4.2.01
UPDATE plano_contas SET id_almasa_plano_conta = 41 WHERE codigo = '1011'; -- Taxa Locação → 4.1.02
UPDATE plano_contas SET id_almasa_plano_conta = 41 WHERE codigo = '1026'; -- Comissões → 4.1.02
UPDATE plano_contas SET id_almasa_plano_conta = 46 WHERE codigo = '1041'; -- Juros → 4.2.02
UPDATE plano_contas SET id_almasa_plano_conta = 47 WHERE codigo = '1013'; -- Rec Diversas → 4.2.03
-- Despesas cliente → grupo 5 (Despesas Almasa)
UPDATE plano_contas SET id_almasa_plano_conta = 59 WHERE codigo = '2029'; -- Comissões Pagas → 5.2.01

COMMIT;

-- Verificacao final
SELECT COUNT(*) as total_contas FROM almasa_plano_contas;
SELECT DISTINCT tipo, COUNT(*) as qtd FROM almasa_plano_contas GROUP BY tipo ORDER BY tipo;
SELECT DISTINCT nivel, COUNT(*) as qtd FROM almasa_plano_contas GROUP BY nivel ORDER BY nivel;
SELECT COUNT(*) as vinculos_ativos FROM plano_contas WHERE id_almasa_plano_conta IS NOT NULL;
