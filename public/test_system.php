<?php
echo "<h1>Teste Sistema Almasa</h1>";
echo "<p>Servidor funcionando!</p>";

echo "<h2>Testar Rotas:</h2>";
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

echo "<ul>";
foreach($routes as $route => $desc) {
    echo "<li><a href='$route' target='_blank'>$desc</a> - $route</li>";
}
echo "</ul>";

echo "<p><strong>Teste cada link acima e verifique se carrega sem erro!</strong></p>"; 