<?php

require_once 'vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Carrega as variáveis de ambiente
$dotenv = new Dotenv();
$dotenv->load('.env');

// Testa se as entidades podem ser carregadas
try {
    $pessoa = new \App\Entity\Pessoas();
    echo "✅ Entidade Pessoa carregada com sucesso\n";
} catch (Exception $e) {
    echo "❌ Erro ao carregar entidade Pessoa: " . $e->getMessage() . "\n";
}

try {
    $email = new \App\Entity\Email();
    echo "✅ Entidade Email carregada com sucesso\n";
} catch (Exception $e) {
    echo "❌ Erro ao carregar entidade Email: " . $e->getMessage() . "\n";
}

try {
    $telefone = new \App\Entity\Telefone();
    echo "✅ Entidade Telefone carregada com sucesso\n";
} catch (Exception $e) {
    echo "❌ Erro ao carregar entidade Telefone: " . $e->getMessage() . "\n";
}

try {
    $documento = new \App\Entity\Documento();
    echo "✅ Entidade Documento carregada com sucesso\n";
} catch (Exception $e) {
    echo "❌ Erro ao carregar entidade Documento: " . $e->getMessage() . "\n";
}

try {
    $endereco = new \App\Entity\Endereco();
    echo "✅ Entidade Endereco carregada com sucesso\n";
} catch (Exception $e) {
    echo "❌ Erro ao carregar entidade Endereco: " . $e->getMessage() . "\n";
}

echo "Teste concluído!\n";