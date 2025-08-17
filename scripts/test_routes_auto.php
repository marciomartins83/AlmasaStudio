<?php
echo "=== TESTE AUTOMATIZADO ROTAS ALMASA ===\n";

$base_url = "http://127.0.0.1:8001";
$routes = [
    '/dashboard/' => 'Dashboard Principal',
    '/agencia/' => 'Gerenciar Agências',
    '/tipo-telefone/' => 'Tipos de Telefone',
    '/conta-bancaria/' => 'Contas Bancárias',
    '/pessoa-fiador/' => 'Pessoas Fiadores',
    '/pessoa-locador/' => 'Pessoas Locadores',
    '/pessoa-corretor/' => 'Pessoas Corretores',
    '/email/' => 'Gerenciar Emails',
    '/telefone/' => 'Gerenciar Telefones',
    '/tipo-email/' => 'Tipos de Email'
];

$success = 0;
$failures = [];

foreach($routes as $route => $desc) {
    $url = $base_url . $route;
    
    echo "Testando: $url ... ";
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'method' => 'GET',
            'header' => "User-Agent: Sistema-Teste\r\n"
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    if ($response !== false) {
        $http_code = 200;
        if (isset($http_response_header)) {
            preg_match('/HTTP\/\d\.\d\s+(\d+)/', $http_response_header[0], $matches);
            $http_code = isset($matches[1]) ? intval($matches[1]) : 200;
        }
        
        if ($http_code == 200) {
            echo "✅ OK ($http_code)\n";
            $success++;
        } else {
            echo "❌ ERRO ($http_code)\n";
            $failures[] = "$route - HTTP $http_code";
        }
    } else {
        echo "❌ FALHA (sem resposta)\n";
        $failures[] = "$route - Sem resposta";
    }
}

echo "\n=== RESULTADO ===\n";
echo "✅ Sucessos: $success\n";
echo "❌ Falhas: " . count($failures) . "\n";

if (count($failures) > 0) {
    echo "\n=== ROTAS COM PROBLEMA ===\n";
    foreach($failures as $failure) {
        echo "❌ $failure\n";
    }
}

echo "\n=== TESTE CONCLUÍDO ===\n"; 