<?php

// scripts/validate_etapa1.php

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

printHeader("VALIDAÇÃO DA ETAPA 1 - EXPANSÃO E COMUNICAÇÃO");

$allTestsPassed = true;

// 1. Verificar tabela pessoas
printHeader("1. VERIFICANDO TABELA PESSOAS");
try {
    $columns = $connection->executeQuery('DESCRIBE pessoas')->fetchAllAssociative();
    $columnNames = array_column($columns, 'Field');

    $expectedColumns = [
        'fisica_juridica',
        'data_nascimento',
        'estado_civil',
        'nacionalidade',
        'naturalidade',
        'nome_pai',
        'nome_mae',
        'renda',
        'observacoes'
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

// 2. Verificar tabelas de comunicação
printHeader("2. VERIFICANDO TABELAS DE COMUNICAÇÃO");
try {
    $tables = $connection->executeQuery('SHOW TABLES')->fetchFirstColumn();

    $expectedTables = [
        'tipos_emails',
        'emails',
        'telefones',
        'pessoa_emails',
        'pessoa_telefones'
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

// 3. Verificar dados iniciais
printHeader("3. VERIFICANDO DADOS INICIAIS");
try {
    $count = $connection->executeQuery('SELECT COUNT(*) as total FROM tipos_emails')->fetchAssociative();
    if ($count['total'] >= 4) {
        printSuccess("tipos_emails tem {$count['total']} registros");
    } else {
        printError("tipos_emails tem apenas {$count['total']} registros (esperado: 4)");
        $allTestsPassed = false;
    }

    $tipos = $connection->executeQuery('SELECT tipo FROM tipos_emails')->fetchFirstColumn();
    $expectedTipos = ['Pessoal', 'Profissional', 'Acadêmico', 'Temporário'];
    
    foreach ($expectedTipos as $tipo) {
        if (in_array($tipo, $tipos)) {
            printSuccess("Tipo '{$tipo}' encontrado");
        } else {
            printError("Tipo '{$tipo}' NÃO encontrado");
            $allTestsPassed = false;
        }
    }
} catch (\Exception $e) {
    printError("Erro ao verificar dados iniciais: " . $e->getMessage());
    $allTestsPassed = false;
}

// 4. Verificar relacionamentos
printHeader("4. VERIFICANDO RELACIONAMENTOS");
try {
    // Verificar chaves estrangeiras
    $foreignKeys = $connection->executeQuery("
        SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME 
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
        WHERE REFERENCED_TABLE_NAME IS NOT NULL 
        AND TABLE_SCHEMA = DATABASE()
    ")->fetchAllAssociative();

    $expectedRelations = [
        'emails' => 'tipos_emails',
        'telefones' => 'tipos_telefones',
        'pessoa_emails' => 'pessoas',
        'pessoa_emails' => 'emails',
        'pessoa_telefones' => 'pessoas',
        'pessoa_telefones' => 'telefones'
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
    printSuccess("🎉 TODOS OS TESTES PASSARAM!");
    printSuccess("✅ A ETAPA 1 FOI IMPLEMENTADA COM SUCESSO");
    echo "\n";
    printInfo("Próximos passos:");
    printInfo("1. Execute: php bin/phpunit");
    printInfo("2. Execute: php bin/phpunit --coverage-html coverage/");
    printInfo("3. Prossiga para a ETAPA 2");
} else {
    printError("❌ ALGUNS TESTES FALHARAM");
    printError("Por favor, corrija os problemas antes de prosseguir");
}

echo "\n";
echo "Status: " . ($allTestsPassed ? "APROVADO ✅" : "REPROVADO ❌") . "\n";
echo "Data: " . date('Y-m-d H:i:s') . "\n";

return $allTestsPassed ? 0 : 1; 