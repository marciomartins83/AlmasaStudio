-- Adiciona campo 'principal' na tabela enderecos e marca o mais antigo de cada pessoa
-- Executar como superuser: sudo su - postgres -c "psql -d almasa_prod -f /caminho/para/este/arquivo.sql"

-- 1. Adicionar coluna
ALTER TABLE enderecos ADD COLUMN IF NOT EXISTS principal BOOLEAN DEFAULT false NOT NULL;

-- 2. Marcar o endereco mais antigo (menor id) de cada pessoa como principal
UPDATE enderecos SET principal = true
WHERE id IN (
    SELECT DISTINCT ON (id_pessoa) id
    FROM enderecos
    ORDER BY id_pessoa, id ASC
);

-- 3. Registrar migration como executada
INSERT INTO doctrine_migration_versions (version, executed_at, execution_time)
VALUES ('DoctrineMigrations\Version20260305123654', NOW(), 0)
ON CONFLICT DO NOTHING;

-- 4. Dar ownership ao almasa_local para futuras migrations
ALTER TABLE enderecos OWNER TO almasa_local;

-- 5. Verificacao
SELECT 'Enderecos principais:' AS check, COUNT(*) FROM enderecos WHERE principal = true;
SELECT 'Enderecos secundarios:' AS check, COUNT(*) FROM enderecos WHERE principal = false;
SELECT 'Pessoas com endereco principal:' AS check, COUNT(DISTINCT id_pessoa) FROM enderecos WHERE principal = true;
SELECT 'Total pessoas:' AS check, COUNT(*) FROM pessoas;
