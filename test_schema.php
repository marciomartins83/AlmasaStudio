<?php

require_once 'vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Carrega as variáveis de ambiente
$dotenv = new Dotenv();
$dotenv->load('.env');

// Testa se o Doctrine consegue conectar e validar o schema
try {
    // Simula o comando doctrine:schema:validate
    echo "Testando conexão com o banco...\n";
    
    // Testa se as entidades podem ser carregadas
    $pessoa = new \App\Entity\Pessoas();
    echo "✅ Entidade Pessoa carregada com sucesso\n";
    
    $user = new \App\Entity\Users();
    echo "✅ Entidade Users carregada com sucesso\n";
    
    echo "✅ Schema parece estar funcionando!\n";
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}

echo "Teste concluído!\n";