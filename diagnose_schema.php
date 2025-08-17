<?php

require_once 'vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Carrega as variáveis de ambiente
$dotenv = new Dotenv();
$dotenv->load('.env');

echo "=== DIAGNÓSTICO DE SCHEMA ===\n\n";

// Testa se as entidades podem ser carregadas
try {
    echo "1. Testando carregamento de entidades...\n";
    
    $pessoa = new \App\Entity\Pessoas();
    echo "   ✅ Entidade Pessoa carregada\n";
    
    $user = new \App\Entity\Users();
    echo "   ✅ Entidade Users carregada\n";
    
    $email = new \App\Entity\Email();
    echo "   ✅ Entidade Email carregada\n";
    
    $telefone = new \App\Entity\Telefone();
    echo "   ✅ Entidade Telefone carregada\n";
    
    echo "   ✅ Todas as entidades carregadas com sucesso\n\n";
    
} catch (Exception $e) {
    echo "   ❌ Erro ao carregar entidades: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Testa se consegue acessar o Doctrine
try {
    echo "2. Testando conexão com Doctrine...\n";
    
    // Simula o kernel do Symfony
    $kernel = new \App\Kernel('dev', true);
    $kernel->boot();
    
    $container = $kernel->getContainer();
    $entityManager = $container->get('doctrine.orm.entity_manager');
    
    echo "   ✅ Doctrine conectado com sucesso\n\n";
    
} catch (Exception $e) {
    echo "   ❌ Erro no Doctrine: " . $e->getMessage() . "\n\n";
    exit(1);
}

echo "3. Verificando schema...\n";

try {
    // Tenta validar o schema
    $schemaValidator = $container->get('doctrine.orm.entity_manager');
    echo "   ✅ Schema validator carregado\n";
    
} catch (Exception $e) {
    echo "   ❌ Erro no schema validator: " . $e->getMessage() . "\n\n";
}

echo "\n=== DIAGNÓSTICO CONCLUÍDO ===\n";
echo "Se não apareceram erros acima, o problema pode ser:\n";
echo "1. Cache do Symfony não limpo\n";
echo "2. Migration não executada completamente\n";
echo "3. Diferenças entre entidades e banco de dados\n";
echo "\nTente executar:\n";
echo "php bin/console cache:clear\n";
echo "php bin/console doctrine:schema:update --force\n";