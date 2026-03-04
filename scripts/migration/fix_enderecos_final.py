#!/usr/bin/env python3
"""
Hotfix: Importar endereços dos 2132 inquilinos sem endereço.
Fonte: MySQL dump locinquilino (endac) + locimoveis (fallback).
"""
import re
import psycopg2
from datetime import datetime

DUMP = '/home/marciorsm/AlmasaStudio/bkpBancoFormatoAntigo/bkpjpw_20260220_121003.sql'
PG_DSN = "host=127.0.0.1 port=5432 dbname=almasa_prod user=almasa_local password=password"

def parse_mysql_values(line: str) -> list:
    """Parse MySQL INSERT INTO ... VALUES (...),(...); into list of tuples."""
    # Find VALUES
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
            current_val += ch  # we'll strip the trailing quote below
            # Peek: could be '' escape
            in_string = False
            continue
        if in_string:
            current_val += ch
            continue
        # Not in string
        if ch == '(':
            paren_depth += 1
            if paren_depth == 1:
                current_row = []
                current_val = ''
            continue
        if ch == ')':
            paren_depth -= 1
            if paren_depth == 0:
                current_row.append(current_val.rstrip("'").strip())
                rows.append(tuple(current_row))
                current_row = []
                current_val = ''
            continue
        if ch == ',' and paren_depth == 1:
            current_row.append(current_val.rstrip("'").strip())
            current_val = ''
            continue
        if paren_depth >= 1:
            current_val += ch

    return rows


def main():
    print(f"[{datetime.now()}] Iniciando fix de endereços...")

    # Step 1: Get people without address from PostgreSQL
    conn = psycopg2.connect(PG_DSN)
    conn.autocommit = False
    cur = conn.cursor()

    cur.execute("""
        SELECT p.idpessoa, p.cod
        FROM pessoas p
        WHERE NOT EXISTS (SELECT 1 FROM enderecos e WHERE e.id_pessoa = p.idpessoa)
        AND p.cod IS NOT NULL
    """)
    sem_endereco = {row[1]: row[0] for row in cur.fetchall()}  # cod -> idpessoa
    print(f"  Pessoas sem endereço: {len(sem_endereco)}")

    if not sem_endereco:
        print("  Nada a fazer!")
        return

    # Step 2: Parse MySQL dump
    print(f"  Lendo dump MySQL...")
    inquilinos = {}  # codigo -> dict
    imoveis = {}     # codigo -> dict

    with open(DUMP, 'r', errors='replace') as f:
        for line in f:
            if 'INSERT INTO `locinquilino`' in line:
                rows = parse_mysql_values(line)
                for r in rows:
                    if len(r) < 20:
                        continue
                    codigo = r[0].strip()
                    try:
                        codigo_int = int(codigo)
                    except (ValueError, TypeError):
                        continue
                    inquilinos[codigo_int] = {
                        'imovel': int(r[2]) if r[2].strip().lstrip('-').isdigit() else 0,
                        'endac': r[14].strip() if len(r) > 14 else '',
                        'complac': r[15].strip() if len(r) > 15 else '',
                        'bairroac': r[16].strip() if len(r) > 16 else '',
                        'cidadeac': r[17].strip() if len(r) > 17 else '',
                        'estac': r[18].strip() if len(r) > 18 else '',
                        'cepac': r[19].strip() if len(r) > 19 else '',
                    }

            elif 'INSERT INTO `locimoveis`' in line:
                rows = parse_mysql_values(line)
                for r in rows:
                    if len(r) < 16:
                        continue
                    codigo = r[0].strip()
                    try:
                        codigo_int = int(codigo)
                    except (ValueError, TypeError):
                        continue
                    imoveis[codigo_int] = {
                        'endereco': r[7].strip() if len(r) > 7 else '',
                        'numero': r[8].strip() if len(r) > 8 else '',
                        'complemento': r[9].strip() if len(r) > 9 else '',
                        'bairro': r[10].strip() if len(r) > 10 else '',
                        'cidade': r[12].strip() if len(r) > 12 else '',
                        'estado': r[13].strip() if len(r) > 13 else '',
                        'cep': r[14].strip() if len(r) > 14 else '',
                    }

    print(f"  Inquilinos no dump: {len(inquilinos)}")
    print(f"  Imóveis no dump: {len(imoveis)}")

    # Step 3: Build caches for estado/cidade/bairro
    cur.execute("SELECT id, uf FROM estados")
    estado_cache = {row[1].upper(): row[0] for row in cur.fetchall()}

    cur.execute("SELECT id, nome, id_estado FROM cidades")
    cidade_cache = {}
    for row in cur.fetchall():
        key = f"{row[1].upper()}_{row[2]}"
        cidade_cache[key] = row[0]

    cur.execute("SELECT id, nome, id_cidade FROM bairros")
    bairro_cache = {}
    for row in cur.fetchall():
        key = f"{row[1].upper()}_{row[2]}"
        bairro_cache[key] = row[0]

    cur.execute("SELECT id, logradouro, cep, id_bairro FROM logradouros")
    logr_cache = {}
    for row in cur.fetchall():
        key = f"{row[1]}_{row[2]}_{row[3]}"
        logr_cache[key] = row[0]

    print(f"  Cache: {len(estado_cache)} estados, {len(cidade_cache)} cidades, {len(bairro_cache)} bairros, {len(logr_cache)} logradouros")

    now = datetime.now()

    def get_or_create_estado(uf):
        uf = (uf or 'SP').strip().upper()[:2]
        if not uf:
            uf = 'SP'
        if uf in estado_cache:
            return estado_cache[uf]
        cur.execute("INSERT INTO estados (uf, nome) VALUES (%s, %s) RETURNING id", (uf, uf))
        eid = cur.fetchone()[0]
        estado_cache[uf] = eid
        return eid

    def get_or_create_cidade(nome, estado_id):
        nome = (nome or 'SEM CIDADE').strip()[:100]
        if not nome:
            nome = 'SEM CIDADE'
        key = f"{nome.upper()}_{estado_id}"
        if key in cidade_cache:
            return cidade_cache[key]
        cur.execute("INSERT INTO cidades (nome, id_estado) VALUES (%s, %s) RETURNING id", (nome, estado_id))
        cid = cur.fetchone()[0]
        cidade_cache[key] = cid
        return cid

    def get_or_create_bairro(nome, cidade_id):
        nome = (nome or 'SEM BAIRRO').strip()[:100]
        if not nome:
            nome = 'SEM BAIRRO'
        key = f"{nome.upper()}_{cidade_id}"
        if key in bairro_cache:
            return bairro_cache[key]
        cur.execute("INSERT INTO bairros (nome, id_cidade) VALUES (%s, %s) RETURNING id", (nome, cidade_id))
        bid = cur.fetchone()[0]
        bairro_cache[key] = bid
        return bid

    def get_or_create_logradouro(logr_str, cep, bairro_id):
        logr_str = (logr_str or 'SEM ENDERECO').strip()[:255]
        if not logr_str:
            logr_str = 'SEM ENDERECO'
        cep = re.sub(r'\D', '', cep or '')[:8]
        if not cep:
            cep = '00000000'
        cep = cep.ljust(8, '0')
        key = f"{logr_str}_{cep}_{bairro_id}"
        if key in logr_cache:
            return logr_cache[key]
        cur.execute(
            "INSERT INTO logradouros (logradouro, cep, id_bairro, created_at, updated_at) VALUES (%s, %s, %s, %s, %s) RETURNING id",
            (logr_str, cep, bairro_id, now, now)
        )
        lid = cur.fetchone()[0]
        logr_cache[key] = lid
        return lid

    # Step 4: Process each person without address
    inserted = 0
    skipped = 0
    errors = 0
    tier1 = 0  # endac proprio
    tier2 = 0  # imovel

    for cod, pessoa_id in sem_endereco.items():
        inq = inquilinos.get(cod)
        if not inq:
            skipped += 1
            continue

        try:
            endereco_str = ''
            complemento = ''
            bairro_str = ''
            cidade_str = ''
            estado_str = ''
            cep_str = ''
            numero = 0

            # Tier 1: endac proprio
            if inq['endac']:
                endereco_str = inq['endac']
                complemento = inq['complac']
                bairro_str = inq['bairroac']
                cidade_str = inq['cidadeac']
                estado_str = inq['estac']
                cep_str = inq['cepac']
                tier1 += 1
            else:
                # Tier 2: imovel
                im = imoveis.get(inq['imovel'])
                if im and im['endereco']:
                    endereco_str = im['endereco']
                    try:
                        numero = int(re.sub(r'\D', '', im['numero'] or '0') or '0')
                    except:
                        numero = 0
                    complemento = im['complemento']
                    bairro_str = im['bairro']
                    cidade_str = im['cidade']
                    estado_str = im['estado']
                    cep_str = im['cep']
                    tier2 += 1
                else:
                    skipped += 1
                    continue

            if not endereco_str:
                skipped += 1
                continue

            estado_id = get_or_create_estado(estado_str)
            cidade_id = get_or_create_cidade(cidade_str, estado_id)
            bairro_id = get_or_create_bairro(bairro_str, cidade_id)
            logr_id = get_or_create_logradouro(endereco_str, cep_str, bairro_id)

            cur.execute(
                "INSERT INTO enderecos (id_pessoa, id_logradouro, id_tipo, end_numero, complemento) VALUES (%s, %s, %s, %s, %s)",
                (pessoa_id, logr_id, 1, numero, complemento or None)
            )
            inserted += 1

            if inserted % 500 == 0:
                conn.commit()
                print(f"  ... {inserted} inseridos")

        except Exception as e:
            errors += 1
            if errors <= 5:
                print(f"  ERRO pessoa {pessoa_id} (cod={cod}): {e}")
            conn.rollback()

    conn.commit()

    # Final check
    cur.execute("SELECT COUNT(*) FROM pessoas p WHERE NOT EXISTS (SELECT 1 FROM enderecos e WHERE e.id_pessoa = p.idpessoa)")
    restantes = cur.fetchone()[0]

    print(f"\n=== RESULTADO ===")
    print(f"  Inseridos: {inserted} (tier1/endac: {tier1}, tier2/imovel: {tier2})")
    print(f"  Pulados: {skipped}")
    print(f"  Erros: {errors}")
    print(f"  Restantes sem endereço: {restantes}")

    cur.close()
    conn.close()
    print(f"[{datetime.now()}] Concluído!")


if __name__ == '__main__':
    main()
