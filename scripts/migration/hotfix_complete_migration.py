#!/usr/bin/env python3
"""
Hotfix: Importar 100% dos dados faltantes do dump MySQL completo.

Causa raiz: config.py apontava para dump compacto (36MB, ~10% dos dados financeiros)
             em vez do dump completo (272MB).

O que este script faz:
  1. Reconstrói mapeamentos (cod→id) a partir do PostgreSQL existente
  2. Importa recibos faltantes (locrecibo → lancamentos_financeiros): ~72k
  3. Atualiza verbas dos novos recibos (locrechist): ~216k
  4. Importa lançamentos CC faltantes (loclanctocc → lancamentos_financeiros): ~391k
  5. Importa repasses faltantes (locrepasse → prestacoes_contas): ~38k
  6. Importa corretores (10) e corretoras (2) → pessoas + tipo
  7. Cria vínculos fiador↔inquilino (265) → fiadores_inquilinos
  8. Re-migra lancamentos_financeiros → lancamentos (tabela final)

Uso: python3 hotfix_complete_migration.py
"""
import re
import sys
import psycopg2
import psycopg2.extras
from datetime import datetime, date

DUMP = '/home/marciorsm/AlmasaStudio/bkpBancoFormatoAntigo/bkpjpw_20260220_121003.sql'
PG_DSN = "host=127.0.0.1 port=5432 dbname=almasa_prod user=almasa_local password=password"

# Column indices for MySQL tables
# locrecibo: recibo(0), nrobancario(1), inquilino(2), competencia(3), vencto(4), limite(5),
#            valor(6), situacao(7), datasit(8), valorpago(9), tipobx(10), abono(11),
#            irate(12), multa(13), irapos(14), contabanc(15), contrapartida(16),
#            codbanco(17), carta(18), acordo(19), dtacordo(20), multaacordo(21),
#            comissao(22), parccomis(23), contaprop(24), datahora(25)

# loclanctocc: codigo(0), data(1), contrapartida(2), tpref(3), referencia(4), conta(5),
#              historico(6), valor(7), sinal(8), recibo(9), imovel(10), lanctocpmf(11),
#              inquilino(12), lctoConpag(13), datahora(14), idrepasse(15), processo(16)

# locrepasse: codigo(0), proprietario(1), valor(2), periodo1(3), periodo2(4),
#             databaixa(5), idlancto(6), imovel(7)

# locrechist: id(0), recibo(1), conta(2), descricao(3), valor(4), datahora(5)


def parse_mysql_values(line: str) -> list:
    """Parse MySQL INSERT INTO ... VALUES (...),(...); into list of tuples."""
    idx = line.find('VALUES')
    if idx == -1:
        return []
    data = line[idx + 6:].rstrip().rstrip(';')

    rows = []
    current_row = []
    current_val = ''
    in_string = False
    escape_next = False
    paren_depth = 0

    for ch in data:
        if escape_next:
            current_val += ch
            escape_next = False
            continue
        if ch == '\\':
            escape_next = True
            current_val += ch
            continue
        if ch == "'" and not in_string:
            in_string = True
            continue
        if ch == "'" and in_string:
            in_string = False
            continue
        if in_string:
            current_val += ch
            continue
        if ch == '(':
            paren_depth += 1
            if paren_depth == 1:
                current_row = []
                current_val = ''
            continue
        if ch == ')':
            paren_depth -= 1
            if paren_depth == 0:
                current_row.append(current_val.strip())
                rows.append(tuple(current_row))
                current_row = []
                current_val = ''
            continue
        if ch == ',' and paren_depth == 1:
            current_row.append(current_val.strip())
            current_val = ''
            continue
        if paren_depth >= 1:
            current_val += ch

    return rows


def safe_float(v, default=0.0):
    try:
        f = float(v)
        return f
    except (ValueError, TypeError):
        return default


def safe_int(v, default=0):
    try:
        return int(v)
    except (ValueError, TypeError):
        return default


def safe_date(v):
    """Parse MySQL date. Returns None for invalid/zero dates."""
    if not v or v in ('NULL', 'None', '0000-00-00', '1901-01-01'):
        return None
    v = v.strip()
    try:
        return datetime.strptime(v, '%Y-%m-%d').date()
    except ValueError:
        return None


def parse_competencia(comp_str):
    """Parse competencia MM/YYYY or YYYY-MM to date (first of month)."""
    if not comp_str or comp_str == 'NULL':
        return None
    comp_str = comp_str.strip()
    try:
        if '/' in comp_str:
            parts = comp_str.split('/')
            return date(int(parts[1]), int(parts[0]), 1)
        elif '-' in comp_str:
            parts = comp_str.split('-')
            return date(int(parts[0]), int(parts[1]), 1)
    except (ValueError, IndexError):
        pass
    return date(2020, 1, 1)


SITUACAO_MAP = {0: 'aberto', 1: 'pago', 2: 'cancelado', 3: 'acordado', 4: 'judicial', 5: 'aberto'}


def main():
    t0 = datetime.now()
    print(f"[{t0}] Hotfix: Importação completa de dados faltantes")
    print(f"  Dump: {DUMP}")

    conn = psycopg2.connect(PG_DSN)
    conn.autocommit = False
    cur = conn.cursor()

    # =========================================================
    # STEP 0: Rebuild ID mappings from PostgreSQL
    # =========================================================
    print("\n=== STEP 0: Reconstruindo mapeamentos ===")

    # loclocador[cod] → pessoas.idpessoa (tipo=4)
    cur.execute("SELECT p.cod, p.idpessoa FROM pessoas p JOIN pessoas_tipos pt ON pt.id_pessoa = p.idpessoa WHERE pt.id_tipo_pessoa = 4")
    locador_map = {r[0]: r[1] for r in cur.fetchall()}

    # locinquilino[cod] → pessoas.idpessoa (tipo=12)
    cur.execute("SELECT p.cod, p.idpessoa FROM pessoas p JOIN pessoas_tipos pt ON pt.id_pessoa = p.idpessoa WHERE pt.id_tipo_pessoa = 12")
    inquilino_map = {r[0]: r[1] for r in cur.fetchall()}

    # locfiador[cod] → pessoas.idpessoa (tipo=1)
    cur.execute("SELECT p.cod, p.idpessoa FROM pessoas p JOIN pessoas_tipos pt ON pt.id_pessoa = p.idpessoa WHERE pt.id_tipo_pessoa = 1")
    fiador_map = {r[0]: r[1] for r in cur.fetchall()}

    # locimovel[cod] → imoveis.id (formato IM0005 → 5)
    cur.execute("SELECT CAST(REGEXP_REPLACE(codigo_interno, '[^0-9]', '', 'g') AS INTEGER), id FROM imoveis WHERE codigo_interno ~ '[0-9]'")
    imovel_map = {r[0]: r[1] for r in cur.fetchall()}

    # contrato: inquilino_cod → imoveis_contratos.id (via imoveis_contratos join)
    cur.execute("""
        SELECT p.cod, ic.id, ic.id_imovel
        FROM imoveis_contratos ic
        JOIN pessoas p ON p.idpessoa = ic.id_pessoa_locatario
        JOIN pessoas_tipos pt ON pt.id_pessoa = p.idpessoa AND pt.id_tipo_pessoa = 12
    """)
    contrato_map = {}  # inq_cod → (contrato_id, imovel_id)
    for r in cur.fetchall():
        contrato_map[r[0]] = (r[1], r[2])

    # imovel → proprietario (for loclanctocc)
    cur.execute("SELECT id, id_pessoa_proprietario FROM imoveis WHERE id_pessoa_proprietario IS NOT NULL")
    imovel_prop_map = {r[0]: r[1] for r in cur.fetchall()}

    # plano_contas: codigo → id
    cur.execute("SELECT CAST(codigo AS INTEGER), id FROM plano_contas WHERE codigo ~ '^[0-9]+$'")
    plano_map = {r[0]: r[1] for r in cur.fetchall()}

    # Existing recibos (to skip duplicates)
    cur.execute("SELECT numero_recibo FROM lancamentos_financeiros WHERE numero_recibo IS NOT NULL")
    existing_recibos = {r[0] for r in cur.fetchall()}

    # Existing loclanctocc IDs (check by origin)
    cur.execute("SELECT id FROM lancamentos_financeiros WHERE origem = 'extrato_cc_migracao'")
    existing_cc_count = cur.rowcount

    print(f"  Locadores: {len(locador_map)}, Inquilinos: {len(inquilino_map)}, Fiadores: {len(fiador_map)}")
    print(f"  Imóveis: {len(imovel_map)}, Contratos: {len(contrato_map)}")
    print(f"  Plano contas: {len(plano_map)}, Recibos existentes: {len(existing_recibos)}")

    # =========================================================
    # STEP 1: Parse full MySQL dump
    # =========================================================
    print("\n=== STEP 1: Lendo dump MySQL completo ===")
    recibos = []
    rechist = []
    lanctocc = []
    repasses = []
    fiador_inq = []
    corretores = []
    corretoras = []

    with open(DUMP, 'r', errors='replace') as f:
        for line in f:
            if 'INSERT INTO `locrecibo`' in line:
                for r in parse_mysql_values(line):
                    if len(r) >= 20:
                        recibos.append(r)
            elif 'INSERT INTO `locrechist`' in line:
                for r in parse_mysql_values(line):
                    if len(r) >= 5:
                        rechist.append(r)
            elif 'INSERT INTO `loclanctocc`' in line:
                for r in parse_mysql_values(line):
                    if len(r) >= 15:
                        lanctocc.append(r)
            elif 'INSERT INTO `locrepasse`' in line:
                for r in parse_mysql_values(line):
                    if len(r) >= 6:
                        repasses.append(r)
            elif 'INSERT INTO `locfiador_inq`' in line:
                for r in parse_mysql_values(line):
                    if len(r) >= 2:
                        fiador_inq.append(r)
            elif 'INSERT INTO `loccorretores`' in line:
                for r in parse_mysql_values(line):
                    if len(r) >= 2:
                        corretores.append(r)
            elif 'INSERT INTO `loccorretora`' in line:
                for r in parse_mysql_values(line):
                    if len(r) >= 2:
                        corretoras.append(r)

    print(f"  locrecibo: {len(recibos)}")
    print(f"  locrechist: {len(rechist)}")
    print(f"  loclanctocc: {len(lanctocc)}")
    print(f"  locrepasse: {len(repasses)}")
    print(f"  locfiador_inq: {len(fiador_inq)}")
    print(f"  loccorretores: {len(corretores)}")
    print(f"  loccorretora: {len(corretoras)}")

    # =========================================================
    # STEP 2: Import missing recibos → lancamentos_financeiros
    # =========================================================
    print("\n=== STEP 2: Importando recibos faltantes ===")
    recibo_inserted = 0
    recibo_skipped = 0
    recibo_id_map = {}  # old_recibo_id → new_lf_id

    # First, build map for existing recibos
    cur.execute("SELECT numero_recibo, id FROM lancamentos_financeiros WHERE origem = 'migracao_mysql'")
    for r in cur.fetchall():
        recibo_id_map[r[0]] = r[1]

    now = datetime.now()
    batch = []
    for r in recibos:
        recibo_id = r[0].strip()
        if recibo_id in existing_recibos:
            recibo_skipped += 1
            continue

        inq_old = safe_int(r[2])
        inq_new = inquilino_map.get(inq_old)
        competencia = parse_competencia(r[3])
        if not competencia:
            competencia = date(2020, 1, 1)
        vencto = safe_date(r[4]) or competencia
        limite = safe_date(r[5])
        valor = safe_float(r[6])
        situacao = SITUACAO_MAP.get(safe_int(r[7]), 'aberto')
        datasit = safe_date(r[8])
        valorpago = safe_float(r[9])
        multa = safe_float(r[13])
        juros = 0.0  # irate is IR rate, not juros
        nrobancario = r[1].strip() if r[1].strip() else None
        data_lancamento = datasit or vencto

        # Resolve contrato and imovel via inquilino
        contrato_id = None
        imovel_id = None
        prop_id = None
        if inq_old in contrato_map:
            contrato_id, imovel_id = contrato_map[inq_old]
            prop_id = imovel_prop_map.get(imovel_id)

        valor_total = valor + multa

        batch.append((
            contrato_id, imovel_id, inq_new, prop_id,
            None,  # id_conta (plano_contas) — NULL, like source
            None,  # id_conta_bancaria
            None,  # numero_acordo
            None,  # numero_parcela
            recibo_id, nrobancario,
            competencia, data_lancamento, vencto, limite,
            valor, 0, 0, 0, 0, 0, 0,  # principal, cond, iptu, agua, luz, gas, outros
            multa, juros, 0, 0, 0,  # multa, juros, honorarios, desconto, bonificacao
            valor_total, valorpago, max(valor_total - valorpago, 0),
            situacao, 'aluguel', 'migracao_mysql',
            None, None, None,  # descricao, historico, observacoes
            now, now
        ))

        if len(batch) >= 1000:
            psycopg2.extras.execute_values(
                cur,
                """INSERT INTO lancamentos_financeiros (
                    id_contrato, id_imovel, id_inquilino, id_proprietario,
                    id_conta, id_conta_bancaria, numero_acordo, numero_parcela,
                    numero_recibo, numero_boleto,
                    competencia, data_lancamento, data_vencimento, data_limite,
                    valor_principal, valor_condominio, valor_iptu, valor_agua, valor_luz, valor_gas, valor_outros,
                    valor_multa, valor_juros, valor_honorarios, valor_desconto, valor_bonificacao,
                    valor_total, valor_pago, valor_saldo,
                    situacao, tipo_lancamento, origem,
                    descricao, historico, observacoes,
                    created_at, updated_at
                ) VALUES %s""",
                batch,
                template="(%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s)"
            )
            recibo_inserted += len(batch)
            conn.commit()
            batch = []
            if recibo_inserted % 10000 == 0:
                print(f"  ... {recibo_inserted} recibos inseridos")

    if batch:
        psycopg2.extras.execute_values(
            cur,
            """INSERT INTO lancamentos_financeiros (
                id_contrato, id_imovel, id_inquilino, id_proprietario,
                id_conta, id_conta_bancaria, numero_acordo, numero_parcela,
                numero_recibo, numero_boleto,
                competencia, data_lancamento, data_vencimento, data_limite,
                valor_principal, valor_condominio, valor_iptu, valor_agua, valor_luz, valor_gas, valor_outros,
                valor_multa, valor_juros, valor_honorarios, valor_desconto, valor_bonificacao,
                valor_total, valor_pago, valor_saldo,
                situacao, tipo_lancamento, origem,
                descricao, historico, observacoes,
                created_at, updated_at
            ) VALUES %s""",
            batch,
            template="(%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s)"
        )
        recibo_inserted += len(batch)
        conn.commit()

    print(f"  Recibos inseridos: {recibo_inserted}, pulados: {recibo_skipped}")

    # Rebuild recibo_id_map for rechist
    cur.execute("SELECT numero_recibo, id FROM lancamentos_financeiros WHERE origem = 'migracao_mysql'")
    recibo_id_map = {r[0]: r[1] for r in cur.fetchall()}

    # =========================================================
    # STEP 3: Update verbas from locrechist
    # =========================================================
    print(f"\n=== STEP 3: Atualizando verbas ({len(rechist)} registros) ===")
    VERBA_MAP = {
        1001: 'valor_principal', 1006: 'valor_condominio', 1008: 'valor_iptu',
        1028: 'valor_agua', 1048: 'valor_luz', 1029: 'valor_gas',
    }

    # Accumulate verbas per recibo
    verba_accum = {}  # recibo_str → {column: total}
    for r in rechist:
        recibo_str = r[1].strip()
        conta = safe_int(r[2])
        valor = safe_float(r[4])
        col = VERBA_MAP.get(conta, 'valor_outros')
        if recibo_str not in verba_accum:
            verba_accum[recibo_str] = {}
        verba_accum[recibo_str][col] = verba_accum[recibo_str].get(col, 0.0) + valor

    # Apply updates in batches
    updated = 0
    for recibo_str, verbas in verba_accum.items():
        lf_id = recibo_id_map.get(recibo_str)
        if not lf_id:
            continue
        sets = []
        vals = []
        for col, total in verbas.items():
            sets.append(f"{col} = %s")
            vals.append(round(total, 2))
        if not sets:
            continue
        # Also recalc valor_total
        sets.append("valor_total = COALESCE(valor_principal,0)+COALESCE(valor_condominio,0)+COALESCE(valor_iptu,0)+COALESCE(valor_agua,0)+COALESCE(valor_luz,0)+COALESCE(valor_gas,0)+COALESCE(valor_outros,0)+COALESCE(valor_multa,0)+COALESCE(valor_juros,0)")
        vals.append(lf_id)
        cur.execute(f"UPDATE lancamentos_financeiros SET {', '.join(sets)} WHERE id = %s", vals)
        updated += 1
        if updated % 5000 == 0:
            conn.commit()
            print(f"  ... {updated} recibos atualizados com verbas")

    conn.commit()
    print(f"  Verbas aplicadas: {updated} recibos")

    # =========================================================
    # STEP 4: Import missing loclanctocc → lancamentos_financeiros
    # =========================================================
    print(f"\n=== STEP 4: Importando lançamentos CC faltantes ({len(lanctocc)} total) ===")

    # Get max existing loclanctocc codigo to detect what's already imported
    cur.execute("SELECT COUNT(*) FROM lancamentos_financeiros WHERE origem = 'extrato_cc_migracao'")
    existing_cc = cur.fetchone()[0]
    print(f"  Já importados: {existing_cc}")

    # Delete existing CC records and re-import all (safest approach for idempotency)
    cur.execute("DELETE FROM lancamentos_financeiros WHERE origem = 'extrato_cc_migracao'")
    conn.commit()
    print(f"  Deletados {existing_cc} registros CC antigos para re-importação completa")

    cc_inserted = 0
    cc_skipped = 0
    batch = []

    for r in lanctocc:
        data = safe_date(r[1])
        if not data:
            cc_skipped += 1
            continue
        valor = safe_float(r[7])
        if valor <= 0:
            cc_skipped += 1
            continue

        conta_cod = safe_int(r[5])
        sinal = r[8].strip().upper()
        imovel_old = safe_int(r[10])
        inq_old = safe_int(r[12])
        historico = r[6].strip()[:255] if r[6] else None

        imovel_new = imovel_map.get(imovel_old)
        inq_new = inquilino_map.get(inq_old)
        prop_new = imovel_prop_map.get(imovel_new) if imovel_new else None
        plano_id = plano_map.get(conta_cod)

        tipo_lancamento = 'receita' if sinal == 'C' else 'despesa'
        competencia = data.replace(day=1)

        batch.append((
            None,  # id_contrato
            imovel_new,
            inq_new,
            prop_new,
            plano_id,  # id_conta
            None,  # id_conta_bancaria
            None, None,  # numero_acordo, parcela
            None, None,  # numero_recibo, boleto
            competencia, data, data, None,  # competencia, data_lancamento, data_vencimento, data_limite
            valor, 0, 0, 0, 0, 0, 0,  # valor_principal, cond, iptu, agua, luz, gas, outros
            0, 0, 0, 0, 0,  # multa, juros, honorarios, desconto, bonificacao
            valor, valor, 0,  # valor_total, valor_pago, valor_saldo
            'pago', tipo_lancamento, 'extrato_cc_migracao',
            None, historico, None,  # descricao, historico, observacoes
            now, now
        ))

        if len(batch) >= 2000:
            psycopg2.extras.execute_values(
                cur,
                """INSERT INTO lancamentos_financeiros (
                    id_contrato, id_imovel, id_inquilino, id_proprietario,
                    id_conta, id_conta_bancaria, numero_acordo, numero_parcela,
                    numero_recibo, numero_boleto,
                    competencia, data_lancamento, data_vencimento, data_limite,
                    valor_principal, valor_condominio, valor_iptu, valor_agua, valor_luz, valor_gas, valor_outros,
                    valor_multa, valor_juros, valor_honorarios, valor_desconto, valor_bonificacao,
                    valor_total, valor_pago, valor_saldo,
                    situacao, tipo_lancamento, origem,
                    descricao, historico, observacoes,
                    created_at, updated_at
                ) VALUES %s""",
                batch,
                template="(%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s)"
            )
            cc_inserted += len(batch)
            conn.commit()
            batch = []
            if cc_inserted % 50000 == 0:
                print(f"  ... {cc_inserted} lançamentos CC inseridos")

    if batch:
        psycopg2.extras.execute_values(
            cur,
            """INSERT INTO lancamentos_financeiros (
                id_contrato, id_imovel, id_inquilino, id_proprietario,
                id_conta, id_conta_bancaria, numero_acordo, numero_parcela,
                numero_recibo, numero_boleto,
                competencia, data_lancamento, data_vencimento, data_limite,
                valor_principal, valor_condominio, valor_iptu, valor_agua, valor_luz, valor_gas, valor_outros,
                valor_multa, valor_juros, valor_honorarios, valor_desconto, valor_bonificacao,
                valor_total, valor_pago, valor_saldo,
                situacao, tipo_lancamento, origem,
                descricao, historico, observacoes,
                created_at, updated_at
            ) VALUES %s""",
            batch,
            template="(%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s)"
        )
        cc_inserted += len(batch)
        conn.commit()

    print(f"  Lançamentos CC inseridos: {cc_inserted}, pulados: {cc_skipped}")

    # =========================================================
    # STEP 5: Import missing repasses → prestacoes_contas
    # =========================================================
    print(f"\n=== STEP 5: Importando repasses faltantes ({len(repasses)} total) ===")

    # Delete and re-import (idempotent)
    cur.execute("DELETE FROM prestacoes_contas")
    conn.commit()

    rep_inserted = 0
    rep_skipped = 0
    numero_seq = 1
    batch = []

    for r in repasses:
        prop_old = safe_int(r[1])
        prop_new = locador_map.get(prop_old)
        if not prop_new:
            rep_skipped += 1
            continue

        valor = safe_float(r[2])
        periodo1 = safe_date(r[3])
        periodo2 = safe_date(r[4])
        databaixa = safe_date(r[5])
        imovel_old = safe_int(r[7]) if len(r) > 7 else 0
        imovel_new = imovel_map.get(imovel_old)

        if not periodo1:
            periodo1 = date(2020, 1, 1)
        if not periodo2:
            periodo2 = periodo1

        ano = periodo2.year
        competencia = f"{periodo2.year}-{periodo2.month:02d}"

        batch.append((
            numero_seq, ano, periodo1, periodo2, 'mensal', competencia,
            prop_new, imovel_new,
            False, False,  # incluir_ficha, incluir_lancamentos
            valor, 0, 0, 0,  # total_receitas, despesas, taxa_admin, retencao_ir
            valor,  # valor_repasse
            'pago', databaixa or periodo2, 'transferencia',
            None, None, None,  # id_conta_bancaria, comprovante, observacoes
            now, now, None  # created_at, updated_at, created_by
        ))
        numero_seq += 1

        if len(batch) >= 1000:
            psycopg2.extras.execute_values(
                cur,
                """INSERT INTO prestacoes_contas (
                    numero, ano, data_inicio, data_fim, tipo_periodo, competencia,
                    id_proprietario, id_imovel,
                    incluir_ficha_financeira, incluir_lancamentos,
                    total_receitas, total_despesas, total_taxa_admin, total_retencao_ir,
                    valor_repasse,
                    status, data_repasse, forma_repasse,
                    id_conta_bancaria, comprovante_repasse, observacoes,
                    created_at, updated_at, created_by
                ) VALUES %s""",
                batch,
                template="(%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s)"
            )
            rep_inserted += len(batch)
            conn.commit()
            batch = []
            if rep_inserted % 10000 == 0:
                print(f"  ... {rep_inserted} repasses inseridos")

    if batch:
        psycopg2.extras.execute_values(
            cur,
            """INSERT INTO prestacoes_contas (
                numero, ano, data_inicio, data_fim, tipo_periodo, competencia,
                id_proprietario, id_imovel,
                incluir_ficha_financeira, incluir_lancamentos,
                total_receitas, total_despesas, total_taxa_admin, total_retencao_ir,
                valor_repasse,
                status, data_repasse, forma_repasse,
                id_conta_bancaria, comprovante_repasse, observacoes,
                created_at, updated_at, created_by
            ) VALUES %s""",
            batch,
            template="(%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s)"
        )
        rep_inserted += len(batch)
        conn.commit()

    print(f"  Repasses inseridos: {rep_inserted}, pulados: {rep_skipped}")

    # =========================================================
    # STEP 6: Import corretores (10) + corretoras (2)
    # =========================================================
    print(f"\n=== STEP 6: Importando corretores ({len(corretores)}) e corretoras ({len(corretoras)}) ===")

    cor_inserted = 0
    for r in corretores:
        codigo = safe_int(r[0])
        nome = r[1].strip()[:255] if r[1] else f'Corretor {codigo}'
        usuario = r[2].strip()[:255] if len(r) > 2 and r[2] else None
        status_str = r[3].strip()[:255] if len(r) > 3 and r[3] and r[3] != 'NULL' else None
        data_cadastro = safe_date(r[5]) if len(r) > 5 else None
        ativo = bool(safe_int(r[16])) if len(r) > 16 else True

        # Create pessoa
        cur.execute(
            "INSERT INTO pessoas (nome, fisica_juridica, tipo_pessoa, status, cod, dt_cadastro) VALUES (%s, 'fisica', 'fisica', true, %s, %s) RETURNING idpessoa",
            (nome, codigo, now)
        )
        pessoa_id = cur.fetchone()[0]

        # Create pessoas_tipos (tipo=2 = corretor)
        cur.execute(
            "INSERT INTO pessoas_tipos (id_pessoa, id_tipo_pessoa, data_inicio, ativo) VALUES (%s, 2, %s, %s)",
            (pessoa_id, date.today(), ativo)
        )

        # Create pessoas_corretores
        cur.execute(
            "INSERT INTO pessoas_corretores (id_pessoa, usuario, status, data_cadastro, ativo, cod) VALUES (%s, %s, %s, %s, %s, %s)",
            (pessoa_id, usuario, status_str, data_cadastro, ativo, codigo)
        )

        # Create endereco placeholder
        cur.execute("SELECT id FROM logradouros WHERE logradouro = 'SEM ENDERECO' LIMIT 1")
        logr = cur.fetchone()
        if logr:
            cur.execute(
                "INSERT INTO enderecos (id_pessoa, id_logradouro, id_tipo, end_numero) VALUES (%s, %s, 1, 0)",
                (pessoa_id, logr[0])
            )

        cor_inserted += 1

    for r in corretoras:
        codigo = safe_int(r[0])
        nome = r[1].strip()[:255] if r[1] else f'Corretora {codigo}'

        cur.execute(
            "INSERT INTO pessoas (nome, fisica_juridica, tipo_pessoa, status, cod, dt_cadastro) VALUES (%s, 'juridica', 'juridica', true, %s, %s) RETURNING idpessoa",
            (nome, codigo, now)
        )
        pessoa_id = cur.fetchone()[0]

        cur.execute(
            "INSERT INTO pessoas_tipos (id_pessoa, id_tipo_pessoa, data_inicio, ativo) VALUES (%s, 3, %s, true)",
            (pessoa_id, date.today())
        )

        cur.execute(
            "INSERT INTO pessoas_corretoras (id_pessoa, created_at, updated_at, cod) VALUES (%s, %s, %s, %s)",
            (pessoa_id, now, now, codigo)
        )

        # Endereco from dump
        endereco = r[2].strip()[:255] if len(r) > 2 and r[2] else 'SEM ENDERECO'
        cep = r[3].strip()[:8] if len(r) > 3 and r[3] else '00000000'
        cep = re.sub(r'\D', '', cep).ljust(8, '0')[:8]

        cur.execute("SELECT id FROM logradouros WHERE logradouro = 'SEM ENDERECO' LIMIT 1")
        logr = cur.fetchone()
        if logr:
            cur.execute(
                "INSERT INTO enderecos (id_pessoa, id_logradouro, id_tipo, end_numero) VALUES (%s, %s, 1, 0)",
                (pessoa_id, logr[0])
            )

        cor_inserted += 1

    conn.commit()
    print(f"  Corretores/Corretoras inseridos: {cor_inserted}")

    # =========================================================
    # STEP 7: Create fiador↔inquilino links
    # =========================================================
    print(f"\n=== STEP 7: Criando vínculos fiador↔inquilino ({len(fiador_inq)}) ===")

    fi_inserted = 0
    fi_skipped = 0
    batch = []

    for r in fiador_inq:
        fiador_old = safe_int(r[0])
        inq_old = safe_int(r[1])

        fiador_new = fiador_map.get(fiador_old)
        inq_new = inquilino_map.get(inq_old)

        if not fiador_new or not inq_new:
            fi_skipped += 1
            continue

        batch.append((fiador_new, inq_new, True, None))

    if batch:
        psycopg2.extras.execute_values(
            cur,
            "INSERT INTO fiadores_inquilinos (id_fiador, id_inquilino, ativo, observacoes) VALUES %s ON CONFLICT DO NOTHING",
            batch,
            template="(%s, %s, %s, %s)"
        )
        fi_inserted = len(batch) - fi_skipped

    conn.commit()
    print(f"  Vínculos inseridos: {len(batch)}, pulados: {fi_skipped}")

    # =========================================================
    # STEP 8: Re-migrate lancamentos_financeiros → lancamentos
    # =========================================================
    print("\n=== STEP 8: Re-migrando lancamentos (tabela final) ===")

    cur.execute("DELETE FROM lancamentos")
    conn.commit()

    cur.execute("""
        INSERT INTO lancamentos (
            data_movimento, id_plano_conta, id_imovel, id_proprietario, id_inquilino,
            valor, tipo, historico, numero_recibo, numero_documento, competencia,
            created_at, updated_at, data_vencimento, data_pagamento, numero, centro_custo,
            id_pessoa_credor, id_pessoa_pagador, id_contrato, id_conta_bancaria, id_boleto,
            valor_pago, valor_desconto, valor_juros, valor_multa,
            reter_inss, perc_inss, valor_inss, reter_iss, perc_iss, valor_iss,
            forma_pagamento, tipo_documento, status, suspenso_motivo, origem, id_processo,
            observacoes, created_by
        )
        SELECT
            lf.data_lancamento,
            CASE
                WHEN lf.tipo_lancamento = 'aluguel' THEN 995
                WHEN lf.tipo_lancamento = 'receita' THEN 1029
                WHEN lf.tipo_lancamento = 'despesa' THEN 1021
                ELSE 995
            END,
            lf.id_imovel, lf.id_proprietario, lf.id_inquilino,
            lf.valor_total,
            CASE
                WHEN lf.tipo_lancamento IN ('receita', 'aluguel') THEN 'receber'
                WHEN lf.tipo_lancamento = 'despesa' THEN 'pagar'
                ELSE 'receber'
            END,
            COALESCE(lf.historico, lf.descricao),
            CASE WHEN lf.numero_recibo ~ '^[0-9]+$' THEN CAST(lf.numero_recibo AS INTEGER) ELSE NULL END,
            NULL,
            TO_CHAR(lf.competencia, 'YYYY-MM'),
            lf.created_at, lf.updated_at,
            lf.data_vencimento,
            CASE WHEN lf.situacao = 'pago' THEN lf.data_vencimento ELSE NULL END,
            NULL, NULL,
            CASE WHEN lf.tipo_lancamento IN ('receita', 'aluguel') THEN lf.id_proprietario ELSE NULL END,
            CASE WHEN lf.tipo_lancamento IN ('receita', 'aluguel') THEN lf.id_inquilino ELSE NULL END,
            lf.id_contrato, lf.id_conta_bancaria, NULL,
            COALESCE(lf.valor_pago, 0)::numeric(15,2),
            COALESCE(lf.valor_desconto, 0)::numeric(15,2),
            COALESCE(lf.valor_juros, 0)::numeric(15,2),
            COALESCE(lf.valor_multa, 0)::numeric(15,2),
            false, NULL, NULL,
            false, NULL, NULL,
            NULL, NULL,
            CASE
                WHEN lf.situacao = 'aberto' THEN 'aberto'
                WHEN lf.situacao = 'pago' THEN 'pago'
                WHEN lf.situacao = 'cancelado' THEN 'cancelado'
                WHEN lf.situacao = 'estornado' THEN 'cancelado'
                WHEN lf.situacao = 'parcial' THEN 'pago_parcial'
                ELSE 'aberto'
            END,
            NULL,
            CASE WHEN lf.origem IS NOT NULL THEN lf.origem ELSE 'importacao' END,
            NULL,
            lf.observacoes,
            lf.created_by
        FROM lancamentos_financeiros lf
        WHERE lf.id IS NOT NULL
    """)
    lanc_count = cur.rowcount
    conn.commit()
    print(f"  Lancamentos migrados: {lanc_count}")

    # =========================================================
    # STEP 9: Final validation
    # =========================================================
    print("\n=== STEP 9: Validação final ===")

    checks = [
        ("lancamentos_financeiros", "SELECT COUNT(*) FROM lancamentos_financeiros"),
        ("  - aluguel", "SELECT COUNT(*) FROM lancamentos_financeiros WHERE tipo_lancamento = 'aluguel'"),
        ("  - receita", "SELECT COUNT(*) FROM lancamentos_financeiros WHERE tipo_lancamento = 'receita'"),
        ("  - despesa", "SELECT COUNT(*) FROM lancamentos_financeiros WHERE tipo_lancamento = 'despesa'"),
        ("lancamentos", "SELECT COUNT(*) FROM lancamentos"),
        ("prestacoes_contas", "SELECT COUNT(*) FROM prestacoes_contas"),
        ("fiadores_inquilinos", "SELECT COUNT(*) FROM fiadores_inquilinos"),
        ("pessoas (total)", "SELECT COUNT(*) FROM pessoas"),
        ("pessoas_corretores", "SELECT COUNT(*) FROM pessoas_corretores"),
        ("pessoas_corretoras", "SELECT COUNT(*) FROM pessoas_corretoras"),
        ("pessoas sem endereco", "SELECT COUNT(*) FROM pessoas p WHERE NOT EXISTS (SELECT 1 FROM enderecos e WHERE e.id_pessoa = p.idpessoa)"),
        ("FK orfas lancamentos", """
            SELECT COUNT(*) FROM lancamentos l WHERE l.id_plano_conta NOT IN (SELECT id FROM plano_contas)
        """),
    ]

    for label, sql in checks:
        cur.execute(sql)
        print(f"  {label}: {cur.fetchone()[0]}")

    cur.close()
    conn.close()

    t1 = datetime.now()
    print(f"\n[{t1}] Concluído em {(t1-t0).total_seconds():.1f}s")


if __name__ == '__main__':
    main()
