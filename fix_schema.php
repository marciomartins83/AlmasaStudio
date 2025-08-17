<?php

require_once 'vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Define o APP_SECRET antes de carregar as variáveis de ambiente
if (!isset($_ENV['APP_SECRET'])) {
    $_ENV['APP_SECRET'] = 'your-secret-key-here-for-development-only';
}

// Carrega as variáveis de ambiente
$dotenv = new Dotenv();
$dotenv->load('.env');

echo "=== CORREÇÃO DE SCHEMA ===\n\n";

try {
    echo "1. Inicializando kernel...\n";
    $kernel = new \App\Kernel('dev', true);
    $kernel->boot();
    
    echo "2. Obtendo container...\n";
    $container = $kernel->getContainer();
    
    echo "3. Obtendo EntityManager...\n";
    $entityManager = $container->get('doctrine.orm.entity_manager');
    
    echo "4. Obtendo SchemaTool...\n";
    $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($entityManager);
    
    echo "5. Obtendo metadados das entidades...\n";
    $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
    
    echo "6. Gerando schema SQL...\n";
    $schema = $schemaTool->getSchemaFromMetadata($metadata);
    
    echo "7. Obtendo conexão com banco...\n";
    $connection = $entityManager->getConnection();
    
    echo "8. Obtendo plataforma...\n";
    $platform = $connection->getDatabasePlatform();
    
    echo "9. Gerando SQL de atualização...\n";
    $sql = $schemaTool->getUpdateSchemaSql($metadata, $platform);
    
    if (empty($sql)) {
        echo "✅ Schema já está sincronizado!\n";
    } else {
        echo "📝 SQL necessário para sincronizar:\n";
        foreach ($sql as $query) {
            echo "   " . $query . "\n";
        }
        
        echo "\n10. Executando atualizações...\n";
        foreach ($sql as $query) {
            $connection->executeStatement($query);
            echo "   ✅ Executado: " . substr($query, 0, 50) . "...\n";
        }
        
        echo "✅ Schema sincronizado com sucesso!\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== CORREÇÃO CONCLUÍDA ===\n";