#!/usr/bin/env php
<?php
/**
 * Gera contas individuais no AlmasaPlanoContas para cada proprietário.
 *
 * Subgrupo pai: 2.1.01 — Obrigações com Proprietários
 * Formato:      2.1.01.001 — <Nome>
 *
 * Idempotente: se a conta já existe para o proprietário, pula.
 *
 * Uso: php scripts/migration/gerar_contas_proprietarios.php
 */

use App\Kernel;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$kernel = new Kernel($_SERVER['APP_ENV'] ?? 'dev', (bool) ($_SERVER['APP_DEBUG'] ?? true));
$kernel->boot();
$pdo = $kernel->getContainer()->get('doctrine')->getConnection()->getNativeConnection();

// === 1. Buscar subgrupo pai 2.1.01 ===
$stmt = $pdo->prepare("SELECT id, tipo FROM almasa_plano_contas WHERE codigo = '2.1.01' AND nivel = 3");
$stmt->execute();
$pai = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pai) {
    echo "ERRO: Subgrupo 2.1.01 não encontrado!\n";
    exit(1);
}

$paiId = (int) $pai['id'];
$paiTipo = $pai['tipo'];
echo "Pai encontrado: id={$paiId}, tipo={$paiTipo}\n";

// === 2. Remover conta genérica 2.1.01.01 (se existir e sem lançamentos) ===
$stmt = $pdo->prepare("SELECT id FROM almasa_plano_contas WHERE codigo = '2.1.01.01' AND nivel = 4");
$stmt->execute();
$generica = $stmt->fetch(PDO::FETCH_ASSOC);

if ($generica) {
    $gId = (int) $generica['id'];
    $refs = 0;
    foreach ([
        ['almasa_lancamentos', 'id_almasa_plano_conta'],
        ['lancamentos', 'id_plano_conta_debito'],
        ['lancamentos', 'id_plano_conta_credito'],
    ] as [$tbl, $col]) {
        $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM {$tbl} WHERE {$col} = ?");
        $stmt2->execute([$gId]);
        $refs += (int) $stmt2->fetchColumn();
    }
    if ($refs === 0) {
        $pdo->prepare("DELETE FROM almasa_plano_contas WHERE id = ?")->execute([$gId]);
        echo "Conta genérica 2.1.01.01 removida (id={$gId})\n";
    } else {
        echo "AVISO: Conta genérica 2.1.01.01 tem {$refs} referências, mantida.\n";
    }
}

// === 3. Buscar contas já existentes sob 2.1.01 ===
$stmt = $pdo->prepare("SELECT codigo, descricao FROM almasa_plano_contas WHERE id_pai = ? AND nivel = 4 ORDER BY codigo");
$stmt->execute([$paiId]);
$existentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$existentesPorNome = [];
$maxNum = 0;
foreach ($existentes as $e) {
    $nome = $e['descricao'];
    $existentesPorNome[mb_strtoupper(trim($nome))] = $e['codigo'];
    $suffix = substr($e['codigo'], strlen('2.1.01.'));
    $num = (int) $suffix;
    if ($num > $maxNum) $maxNum = $num;
}
echo "Contas existentes: " . count($existentes) . ", ultimo numero: {$maxNum}\n";

// === 4. Buscar todos os proprietários distintos ===
$stmt = $pdo->query("
    SELECT DISTINCT p.idpessoa, p.nome
    FROM pessoas p
    INNER JOIN imoveis i ON i.id_pessoa_proprietario = p.idpessoa
    WHERE p.nome IS NOT NULL AND TRIM(p.nome) != ''
    ORDER BY p.nome
");
$proprietarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Proprietarios encontrados: " . count($proprietarios) . "\n";

// === 5. Gerar contas ===
$now = date('Y-m-d H:i:s');
$insertSql = "INSERT INTO almasa_plano_contas (codigo, descricao, tipo, nivel, id_pai, aceita_lancamentos, ativo, created_at, updated_at)
              VALUES (?, ?, ?, 4, ?, true, true, ?, ?)";
$insertStmt = $pdo->prepare($insertSql);

$criadas = 0;
$puladas = 0;
$nextNum = $maxNum + 1;

foreach ($proprietarios as $prop) {
    $nome = trim($prop['nome']);
    $nomeUpper = mb_strtoupper($nome);

    if (isset($existentesPorNome[$nomeUpper])) {
        $puladas++;
        continue;
    }

    $codigo = '2.1.01.' . str_pad((string) $nextNum, 3, '0', STR_PAD_LEFT);
    $descricao = $nome;

    $insertStmt->execute([$codigo, $descricao, $paiTipo, $paiId, $now, $now]);
    $criadas++;
    $nextNum++;

    if ($criadas % 100 === 0) {
        echo "  ... {$criadas} contas criadas\n";
    }
}

echo "\n=== RESULTADO ===\n";
echo "Criadas: {$criadas}\n";
echo "Puladas (ja existiam): {$puladas}\n";
echo "Total de contas sob 2.1.01: " . ($criadas + count($existentes)) . "\n";
echo "Ultimo codigo: 2.1.01." . str_pad((string) ($nextNum - 1), 3, '0', STR_PAD_LEFT) . "\n";
