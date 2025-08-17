<?php

// scripts/validate_tests.php

require_once __DIR__ . '/../vendor/autoload.php';

use SymfonyComponentDotenvDotenv;
use DoctrineDBALDriverManager;

// Carregar variáveis de ambiente
$dotenv = new Dotenv();
$dotenv->loadEnv(__DIR__ . '/../.env');

// Configuração do banco de dados
$connectionParams = [
    'dbname' => $_ENV['DATABASE_NAME'],
    'user' => $_ENV['DATABASE_USER'],
    'password' => $_ENV['DATABASE_PASSWORD'],
    'host' => $_ENV['DATABASE_HOST'],
    'driver' => 'pdo_mysql',
];

$connection = DriverManager::getConnection($connectionParams);

function printHeader($message) {
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "  {$message}\n";
    echo str_repeat("=", 60) . "\n";
}

function printSuccess($message) {
    echo "✅ {$message}\n";
}

function printError($message) {
    echo "❌ {$message}\n";
}

function printInfo($message) {
    echo "ℹ️  {$message}\n";
}

printHeader("VALIDAÇÃO DE TESTES - SISTEMA ALMASA");

$allTestsPassed = true;

// 1. Verificar estrutura do banco
printHeader("1. VERIFICANDO ESTRUTURA DO BANCO");
try {
    $tables = $connection->executeQuery('SHOW TABLES')->fetchFirstColumn();
    
    $expectedTables = [
        'pessoas', 'users', 'tipos_emails', 'emails', 'telefones', 'tipos_telefones',
        'pessoa_emails', 'pessoa_telefones', 'estados', 'cidades', 'bairros', 
        'logradouros', 'tipos_documentos', 'documentos', 'enderecos'
    ];

    foreach ($expectedTables as $table) {
        if (in_array($table, $tables)) {
            printSuccess("Tabela '{$table}' encontrada");
        } else {
            printError("Tabela '{$table}' NÃO encontrada");
            $allTestsPassed = false;
        }
    }
} catch (\Exception $e) {
    printError("Erro ao verificar tabelas: " . $e->getMessage());
    $allTestsPassed = false;
}

// 2. Verificar colunas da tabela pessoas
printHeader("2. VERIFICANDO COLUNAS DA TABELA PESSOAS");
try {
    $columns = $connection->executeQuery('DESCRIBE pessoas')->fetchAllAssociative();
    $columnNames = array_column($columns, 'Field');

    $expectedColumns = [
        'fisica_juridica', 'data_nascimento', 'estado_civil', 'nacionalidade',
        'naturalidade', 'nome_pai', 'nome_mae', 'renda', 'observacoes'
    ];

    foreach ($expectedColumns as $column) {
        if (in_array($column, $columnNames)) {
            printSuccess("Coluna '{$column}' encontrada");
        } else {
            printError("Coluna '{$column}' NÃO encontrada");
            $allTestsPassed = false;
        }
    }
} catch (\Exception $e) {
    printError("Erro ao verificar colunas: " . $e->getMessage());
    $allTestsPassed = false;
}

// 3. Verificar dados iniciais
printHeader("3. VERIFICANDO DADOS INICIAIS");
try {
    $counts = [
        'tipos_emails' => 4,
        'tipos_documentos' => 8,
        'estados' => 27
    ];

    foreach ($counts as $table => $expected) {
        $result = $connection->executeQuery("SELECT COUNT(*) as total FROM {$table}")->fetchAssociative();
        if ($result['total'] >= $expected) {
            printSuccess("{$table} tem {$result['total']} registros (esperado: {$expected})");
        } else {
            printError("{$table} tem apenas {$result['total']} registros (esperado: {$expected})");
            $allTestsPassed = false;
        }
    }
} catch (\Exception $e) {
    printError("Erro ao verificar dados: " . $e->getMessage());
    $allTestsPassed = false;
}

// 4. Verificar relacionamentos
printHeader("4. VERIFICANDO RELACIONAMENTOS");
try {
    $foreignKeys = $connection->executeQuery("
        SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME 
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
        WHERE REFERENCED_TABLE_NAME IS NOT NULL 
        AND TABLE_SCHEMA = DATABASE()
    ")->fetchAllAssociative();

    $expectedRelations = [
        'emails' => 'tipos_emails',
        'telefones' => 'tipos_telefones',
        'documentos' => 'tipos_documentos',
        'documentos' => 'pessoas',
        'enderecos' => 'pessoas'
    ];

    $foundRelations = [];
    foreach ($foreignKeys as $fk) {
        $foundRelations[$fk['TABLE_NAME']] = $fk['REFERENCED_TABLE_NAME'];
    }

    foreach ($expectedRelations as $table => $referenced) {
        if (isset($foundRelations[$table]) && $foundRelations[$table] === $referenced) {
            printSuccess("Relacionamento {$table} -> {$referenced} OK");
        } else {
            printError("Relacionamento {$table} -> {$referenced} NÃO encontrado");
            $allTestsPassed = false;
        }
    }
} catch (\Exception $e) {
    printError("Erro ao verificar relacionamentos: " . $e->getMessage());
    $allTestsPassed = false;
}

// 5. Resumo final
printHeader("5. RESUMO FINAL DA VALIDAÇÃO");

if ($allTestsPassed) {
    printSuccess("🎉 TODOS OS TESTES DE ESTRUTURA PASSARAM!");
    printSuccess("✅ SISTEMA PRONTO PARA EXECUÇÃO DOS TESTES UNITÁRIOS");
    echo "\n";
    printInfo("Próximos passos:");
    printInfo("1. Execute: php bin/phpunit");
    printInfo("2. Execute: php bin/phpunit --coverage-html coverage/");
    printInfo("3. Verifique se todos os testes passam");
} else {
    printError("❌ ALGUNS TESTES FALHARAM");
    printError("Por favor, execute as migrations antes de prosseguir");
}

echo "\n";
echo "Status: " . ($allTestsPassed ? "APROVADO ✅" : "REPROVADO ❌") . "\n";
echo "Data: " . date('Y-m-d H:i:s') . "\n";
echo "\n";
echo "📋 ARQUIVOS DE TESTE CRIADOS:\n";
echo "- tests/Entity/PessoaTest.php\n";
echo "- tests/Entity/CommunicationTest.php\n";
echo "- tests/Entity/EntityTest.php\n";
echo "- tests/Repository/PessoaRepositoryTest.php\n";
echo "- tests/Migration/DatabaseStructureTest.php\n";
echo "- scripts/validate_tests.php\n";
echo "\n";
echo "🚀 SISTEMA COMPLETO COM TESTES!";

return $allTestsPassed ? 0 : 1; 