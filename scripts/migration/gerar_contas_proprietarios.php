#!/usr/bin/env php
<?php
/**
 * Gera contas individuais no AlmasaPlanoContas para cada proprietário.
 *
 * Subgrupo pai: 2.1.01 — Obrigações com Proprietários
 * Formato:      2.1.01.0001 — <Nome> ou <Nome> (proprietário, cod: XXX)
 *
 * Regras de descrição:
 *   - Nome com sobrenome: usa o nome completo como está
 *   - Nome sem sobrenome + com CPF: "NOME (proprietário, CPF: xxx.xxx.xxx-xx)"
 *   - Nome sem sobrenome + sem CPF: "NOME (proprietário, cod: XXX)"
 *
 * Idempotente por idpessoa (não por nome). Re-executar é seguro.
 *
 * Uso:
 *   php scripts/migration/gerar_contas_proprietarios.php          # cria novas
 *   php scripts/migration/gerar_contas_proprietarios.php --rebuild # apaga e recria todas
 */

use App\Kernel;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$kernel = new Kernel($_SERVER['APP_ENV'] ?? 'dev', (bool) ($_SERVER['APP_DEBUG'] ?? true));
$kernel->boot();
$pdo = $kernel->getContainer()->get('doctrine')->getConnection()->getNativeConnection();

$rebuild = in_array('--rebuild', $argv);

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

// === 2. Se --rebuild, apagar todas as contas sob 2.1.01 sem lançamentos ===
if ($rebuild) {
    // Verificar quais contas têm lançamentos vinculados
    $stmt = $pdo->prepare("
        SELECT apc.id FROM almasa_plano_contas apc
        WHERE apc.id_pai = ? AND apc.nivel = 4
        AND (
            EXISTS (SELECT 1 FROM almasa_lancamentos al WHERE al.id_almasa_plano_conta = apc.id)
            OR EXISTS (SELECT 1 FROM lancamentos l WHERE l.id_plano_conta_debito = apc.id)
            OR EXISTS (SELECT 1 FROM lancamentos l WHERE l.id_plano_conta_credito = apc.id)
        )
    ");
    $stmt->execute([$paiId]);
    $comLancamentos = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (count($comLancamentos) > 0) {
        echo "AVISO: " . count($comLancamentos) . " contas têm lançamentos vinculados e NÃO serão removidas.\n";
        $placeholders = implode(',', array_fill(0, count($comLancamentos), '?'));
        $delStmt = $pdo->prepare("DELETE FROM almasa_plano_contas WHERE id_pai = ? AND nivel = 4 AND id NOT IN ({$placeholders})");
        $delStmt->execute(array_merge([$paiId], $comLancamentos));
    } else {
        $delStmt = $pdo->prepare("DELETE FROM almasa_plano_contas WHERE id_pai = ? AND nivel = 4");
        $delStmt->execute([$paiId]);
    }

    $deleted = $delStmt->rowCount();
    echo "Rebuild: {$deleted} contas removidas.\n";
}

// === 3. Buscar contas já existentes sob 2.1.01 (indexadas por idpessoa via descrição) ===
$stmt = $pdo->prepare("SELECT id, codigo, descricao FROM almasa_plano_contas WHERE id_pai = ? AND nivel = 4 ORDER BY codigo");
$stmt->execute([$paiId]);
$existentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$maxNum = 0;
$existentesCodigos = [];
foreach ($existentes as $e) {
    $existentesCodigos[mb_strtoupper(trim($e['descricao']))] = $e['codigo'];
    $suffix = substr($e['codigo'], strlen('2.1.01.'));
    $num = (int) $suffix;
    if ($num > $maxNum) $maxNum = $num;
}
echo "Contas existentes: " . count($existentes) . ", último número: {$maxNum}\n";

// === 4. Buscar todos os proprietários distintos com CPF e cod ===
$stmt = $pdo->query("
    SELECT DISTINCT p.idpessoa, p.nome, p.cod,
           pd.numero_documento AS cpf
    FROM pessoas p
    INNER JOIN imoveis i ON i.id_pessoa_proprietario = p.idpessoa
    LEFT JOIN pessoas_documentos pd ON pd.id_pessoa = p.idpessoa AND pd.id_tipo_documento = 1
    WHERE p.nome IS NOT NULL AND TRIM(p.nome) != ''
    ORDER BY p.nome
");
$proprietarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Proprietários encontrados: " . count($proprietarios) . "\n";

// === 5. Gerar descrições únicas ===
function gerarDescricao(string $nome, ?string $cpf, ?int $cod): string
{
    $nome = trim($nome);

    // Nome com sobrenome (contém espaço): usar como está
    if (str_contains($nome, ' ')) {
        return $nome;
    }

    // Nome sem sobrenome: complementar com CPF ou cod
    if ($cpf && trim($cpf) !== '' && trim($cpf) !== '0') {
        return "{$nome} (proprietário, CPF: {$cpf})";
    }

    if ($cod) {
        return "{$nome} (proprietário, cod: {$cod})";
    }

    return $nome;
}

// === 6. Inserir contas ===
$now = date('Y-m-d H:i:s');
$insertSql = "INSERT INTO almasa_plano_contas (codigo, descricao, tipo, nivel, id_pai, aceita_lancamentos, ativo, created_at, updated_at)
              VALUES (?, ?, ?, 4, ?, true, true, ?, ?)";
$insertStmt = $pdo->prepare($insertSql);

$criadas = 0;
$puladas = 0;
$nextNum = $maxNum + 1;

foreach ($proprietarios as $prop) {
    $nome = trim($prop['nome']);
    $cpf = $prop['cpf'] ?? null;
    $cod = $prop['cod'] ? (int) $prop['cod'] : null;

    $descricao = gerarDescricao($nome, $cpf, $cod);
    $descricaoUpper = mb_strtoupper($descricao);

    // Idempotência por descrição gerada (agora única por pessoa)
    if (isset($existentesCodigos[$descricaoUpper])) {
        $puladas++;
        continue;
    }

    $codigo = '2.1.01.' . str_pad((string) $nextNum, 4, '0', STR_PAD_LEFT);
    $insertStmt->execute([$codigo, $descricao, $paiTipo, $paiId, $now, $now]);
    $existentesCodigos[$descricaoUpper] = $codigo;
    $criadas++;
    $nextNum++;

    if ($criadas % 100 === 0) {
        echo "  ... {$criadas} contas criadas\n";
    }
}

echo "\n=== RESULTADO ===\n";
echo "Criadas: {$criadas}\n";
echo "Puladas (já existiam): {$puladas}\n";
echo "Total de contas sob 2.1.01: " . ($criadas + count($existentes)) . "\n";
echo "Último código: 2.1.01." . str_pad((string) ($nextNum - 1), 4, '0', STR_PAD_LEFT) . "\n";
