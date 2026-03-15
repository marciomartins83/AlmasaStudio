#!/usr/bin/env php
<?php
/**
 * Gera contas individuais no AlmasaPlanoContas para cada proprietário.
 *
 * Subgrupo pai: 2.1.01 — Obrigações com Proprietários
 * Formato:      2.1.01.{cod} — código do proprietário no sistema (ex: 2.1.01.914)
 *
 * Regras de descrição:
 *   - Nome com sobrenome: usa o nome completo como está
 *   - Nome sem sobrenome + com CPF: "NOME (proprietário, CPF: xxx.xxx.xxx-xx)"
 *   - Nome sem sobrenome + sem CPF: "NOME (proprietário, cod: XXX)"
 *
 * Regra de código:
 *   - Sempre 2.1.01.{cod} onde cod é o código do proprietário em pessoas.cod
 *   - Proprietários sem cod são reportados e pulados
 *
 * Idempotente por cod. Re-executar é seguro.
 *
 * Uso:
 *   php scripts/migration/gerar_contas_proprietarios.php                  # cria novas contas faltantes
 *   php scripts/migration/gerar_contas_proprietarios.php --rebuild        # apaga e recria todas
 *   php scripts/migration/gerar_contas_proprietarios.php --migrate-codes  # corrige códigos sequenciais → cod-based
 */

use App\Kernel;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$kernel = new Kernel($_SERVER['APP_ENV'] ?? 'dev', (bool) ($_SERVER['APP_DEBUG'] ?? true));
$kernel->boot();
$pdo = $kernel->getContainer()->get('doctrine')->getConnection()->getNativeConnection();

$rebuild      = in_array('--rebuild', $argv);
$migrateCodes = in_array('--migrate-codes', $argv);

// === 1. Buscar subgrupo pai 2.1.01 ===
$stmt = $pdo->prepare("SELECT id, tipo FROM almasa_plano_contas WHERE codigo = '2.1.01' AND nivel = 3");
$stmt->execute();
$pai = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pai) {
    echo "ERRO: Subgrupo 2.1.01 não encontrado!\n";
    exit(1);
}

$paiId   = (int) $pai['id'];
$paiTipo = $pai['tipo'];
echo "Pai encontrado: id={$paiId}, tipo={$paiTipo}\n";

// === 2. Se --rebuild, apagar todas as contas sob 2.1.01 sem lançamentos ===
if ($rebuild) {
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

// === 3. Buscar contas existentes sob 2.1.01 ===
$stmt = $pdo->prepare("SELECT id, codigo, descricao FROM almasa_plano_contas WHERE id_pai = ? AND nivel = 4 ORDER BY codigo");
$stmt->execute([$paiId]);
$existentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$existentesPorDescricao = []; // descricao_upper => ['id' => ..., 'codigo' => ...]
$existentesPorCod       = []; // cod (int) => ['id' => ..., 'codigo' => ...]

foreach ($existentes as $e) {
    $descUpper = mb_strtoupper(trim($e['descricao']));
    $existentesPorDescricao[$descUpper] = ['id' => $e['id'], 'codigo' => $e['codigo']];

    $suffix = substr($e['codigo'], strlen('2.1.01.'));
    if (is_numeric($suffix)) {
        $existentesPorCod[(int)$suffix] = ['id' => $e['id'], 'codigo' => $e['codigo']];
    }
}
echo "Contas existentes sob 2.1.01: " . count($existentes) . "\n";

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

// === 5. Gerar descrição legível ===
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

// === 6. --migrate-codes: corrigir códigos sequenciais → cod-based (2 fases) ===
if ($migrateCodes) {
    echo "\n--- MIGRAÇÃO DE CÓDIGOS (sequencial → cod-based) ---\n";
    echo "Estratégia: fase 1 = renomear para TEMP, fase 2 = renomear para cod-based\n\n";

    // Montar mapa: id_conta → cod_do_proprietario (via match de descrição)
    $idParaCod = []; // conta.id => cod
    $semCod    = 0;
    $semMatch  = 0;

    foreach ($proprietarios as $prop) {
        $nome = trim($prop['nome']);
        $cpf  = $prop['cpf'] ?? null;
        $cod  = $prop['cod'] ? (int) $prop['cod'] : null;

        if (!$cod) {
            $semCod++;
            continue;
        }

        $descricao = gerarDescricao($nome, $cpf, $cod);
        $descUpper = mb_strtoupper($descricao);

        if (!isset($existentesPorDescricao[$descUpper])) {
            continue; // conta não existe ainda, será criada depois
        }

        $contaId     = $existentesPorDescricao[$descUpper]['id'];
        $codigoAtual = $existentesPorDescricao[$descUpper]['codigo'];
        $codigoEsperado = '2.1.01.' . $cod;

        if ($codigoAtual === $codigoEsperado) {
            continue; // já correto, não precisa migrar
        }

        $idParaCod[$contaId] = $cod;
    }

    echo "Contas a migrar: " . count($idParaCod) . "\n";
    echo "Proprietários sem cod: {$semCod}\n\n";

    if (empty($idParaCod)) {
        echo "Nada a migrar. Todos os códigos já estão corretos ou as contas não existem ainda.\n";
        exit(0);
    }

    $pdo->beginTransaction();
    try {
        // FASE 1: renomear para TEMP.{id} (sem conflito de unicidade)
        echo "Fase 1: renomeando para temporários...\n";
        $updateTemp = $pdo->prepare("UPDATE almasa_plano_contas SET codigo = ?, updated_at = NOW() WHERE id = ?");
        foreach ($idParaCod as $contaId => $cod) {
            $updateTemp->execute(['TEMP.' . $contaId, $contaId]);
        }
        echo "  " . count($idParaCod) . " contas renomeadas para TEMP.{id}\n";

        // FASE 2: renomear para 2.1.01.{cod}
        echo "Fase 2: renomeando para códigos finais...\n";
        $updateFinal = $pdo->prepare("UPDATE almasa_plano_contas SET codigo = ?, updated_at = NOW() WHERE id = ?");
        $conflitos   = 0;
        $atualizados = 0;

        foreach ($idParaCod as $contaId => $cod) {
            $codigoFinal = '2.1.01.' . $cod;

            // Verificar conflito com conta que NÃO está sendo migrada (ex: conta já existia com esse código)
            $checkStmt = $pdo->prepare("SELECT id FROM almasa_plano_contas WHERE codigo = ? AND id != ?");
            $checkStmt->execute([$codigoFinal, $contaId]);
            if ($checkStmt->fetch()) {
                echo "  CONFLITO IRRESOLVÍVEL: {$codigoFinal} já ocupado por conta não migrada (id={$contaId})\n";
                $conflitos++;
                // Deixar com TEMP por ora — desfazer depois
                continue;
            }

            $updateFinal->execute([$codigoFinal, $contaId]);
            $atualizados++;
        }

        // Contas que ficaram em TEMP por conflito: reverter para código original
        if ($conflitos > 0) {
            echo "  Revertendo {$conflitos} contas com conflito para seu código anterior...\n";
            // Montar mapa inverso id → código original
            $codigosOriginais = [];
            foreach ($existentes as $e) {
                $codigosOriginais[$e['id']] = $e['codigo'];
            }
            $revert = $pdo->prepare("UPDATE almasa_plano_contas SET codigo = ?, updated_at = NOW() WHERE id = ?");
            foreach ($idParaCod as $contaId => $cod) {
                $codigoFinal = '2.1.01.' . $cod;
                // Verificar se ainda está como TEMP (não foi atualizado para final)
                $checkTemp = $pdo->prepare("SELECT codigo FROM almasa_plano_contas WHERE id = ?");
                $checkTemp->execute([$contaId]);
                $row = $checkTemp->fetch(PDO::FETCH_ASSOC);
                if ($row && str_starts_with($row['codigo'], 'TEMP.')) {
                    $original = $codigosOriginais[$contaId] ?? null;
                    if ($original) {
                        $revert->execute([$original, $contaId]);
                    }
                }
            }
        }

        $pdo->commit();

        echo "\n=== RESULTADO MIGRAÇÃO ===\n";
        echo "Atualizados: {$atualizados}\n";
        echo "Conflitos irresolvíveis: {$conflitos}\n";
        echo "Sem cod: {$semCod}\n";
        if ($atualizados > 0) {
            echo "\nRe-execute sem --migrate-codes para criar contas faltantes.\n";
        }

    } catch (\Throwable $e) {
        $pdo->rollBack();
        echo "ERRO: " . $e->getMessage() . "\n";
        exit(1);
    }

    exit(0);
}

// === 7. Inserir contas faltantes (usando cod como sufixo) ===
$now = date('Y-m-d H:i:s');
$insertSql = "INSERT INTO almasa_plano_contas (codigo, descricao, tipo, nivel, id_pai, aceita_lancamentos, ativo, created_at, updated_at)
              VALUES (?, ?, ?, 4, ?, true, true, ?, ?)";
$insertStmt = $pdo->prepare($insertSql);
$checkStmt  = $pdo->prepare("SELECT id FROM almasa_plano_contas WHERE codigo = ?");

$criadas   = 0;
$puladas   = 0;
$semCod    = 0;
$conflitos = 0;

foreach ($proprietarios as $prop) {
    $nome = trim($prop['nome']);
    $cpf  = $prop['cpf'] ?? null;
    $cod  = $prop['cod'] ? (int) $prop['cod'] : null;

    if (!$cod) {
        $semCod++;
        continue;
    }

    // Idempotência por cod: se já existe 2.1.01.{cod}, pular
    if (isset($existentesPorCod[$cod])) {
        $puladas++;
        continue;
    }

    $descricao  = gerarDescricao($nome, $cpf, $cod);
    $codigoNovo = '2.1.01.' . $cod;

    // Segurança: verificar conflito (outro registro com mesmo código)
    $checkStmt->execute([$codigoNovo]);
    if ($checkStmt->fetch()) {
        echo "CONFLITO: {$codigoNovo} já existe — '{$descricao}' pulada\n";
        $conflitos++;
        continue;
    }

    $insertStmt->execute([$codigoNovo, $descricao, $paiTipo, $paiId, $now, $now]);
    $existentesPorCod[$cod] = ['id' => null, 'codigo' => $codigoNovo];
    $criadas++;

    if ($criadas % 100 === 0) {
        echo "  ... {$criadas} contas criadas\n";
    }
}

echo "\n=== RESULTADO ===\n";
echo "Criadas:   {$criadas}\n";
echo "Puladas (já existiam): {$puladas}\n";
echo "Sem cod:   {$semCod}\n";
echo "Conflitos: {$conflitos}\n";
echo "Total sob 2.1.01: " . ($criadas + count($existentes)) . "\n";
