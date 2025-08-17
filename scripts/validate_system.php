<?php

require 'vendor/autoload.php';

echo "=== VALIDAÇÃO SISTEMA ALMASA ===\n";

// Testar autoload das entities
$entities = [
    'App\Entity\Agencia',
    'App\Entity\TipoTelefone',
    'App\Entity\ContaBancaria',
    'App\Entity\Banco',
            'App\Entity\Pessoas'
];

foreach ($entities as $entity) {
    if (class_exists($entity)) {
        echo "✅ Entity $entity: OK\n";

        // Testar instanciação
        try {
            $instance = new $entity();
            echo "✅ Instanciação $entity: OK\n";
        } catch (Exception $e) {
            echo "❌ Erro instanciação $entity: " . $e->getMessage() . "\n";
        }
    } else {
        echo "❌ Entity $entity: NÃO ENCONTRADA\n";
    }
}

// Testar controllers
$controllers = [
    'App\Controller\AgenciaController',
    'App\Controller\TipoTelefoneController',
    'App\Controller\ContaBancariaController'
];

foreach ($controllers as $controller) {
    if (class_exists($controller)) {
        echo "✅ Controller $controller: OK\n";
    } else {
        echo "❌ Controller $controller: NÃO ENCONTRADO\n";
    }
}

echo "=== VALIDAÇÃO CONCLUÍDA ===\n"; 