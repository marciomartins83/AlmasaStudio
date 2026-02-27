#!/usr/bin/env python3
"""
hotfix_conjuges.py — Cria Pessoa records para cônjuges de fiadores e inquilinos
Lê dados do dump MySQL (locfiadores.nomeconj*, locinquilino.nomecjg)
e cria registros correspondentes no PostgreSQL.

Data: 2026-02-21
"""

import json
import os
import re
import sys
from datetime import datetime, date

sys.path.insert(0, os.path.dirname(__file__))
import config as cfg
from migrate import MySQLDumpParser, StateManager, safe_str, safe_int, safe_date, safe_float, clean_doc

import psycopg2
import psycopg2.extras


def main():
    parser = MySQLDumpParser(cfg.MYSQL_DUMP_PATH)
    state = StateManager()
    conn = psycopg2.connect(cfg.POSTGRES_DSN)
    conn.autocommit = False

    stats = {
        'fiador_conjuges_criados': 0,
        'fiador_conjuges_ja_existiam': 0,
        'fiador_docs_criados': 0,
        'fiador_profs_criados': 0,
        'fiador_tels_criados': 0,
        'inq_conjuges_criados': 0,
        'inq_conjuges_ja_existiam': 0,
        'erros': 0,
    }

    # =========================================================================
    # PARTE 1: Cônjuges de FIADORES (locfiadores)
    # =========================================================================
    print("=" * 60)
    print("PARTE 1: Cônjuges de Fiadores")
    print("=" * 60)

    for row in parser.iter_table("locfiadores"):
        old_id = safe_int(row.get("codigo"))
        nomeconj = safe_str(row.get("nomeconj"))

        if not nomeconj:
            continue

        # Look up the new pessoa_id for this fiador
        fiador_pessoa_id = state.get_new_id("locfiador", old_id)
        if not fiador_pessoa_id:
            print(f"  WARN: Fiador old={old_id} não encontrado no id_map")
            stats['erros'] += 1
            continue

        # Check if conjuge already set
        with conn.cursor() as cur:
            cur.execute(
                "SELECT id_conjuge FROM pessoas_fiadores WHERE id_pessoa = %s",
                (fiador_pessoa_id,)
            )
            result = cur.fetchone()
            if result and result[0]:
                stats['fiador_conjuges_ja_existiam'] += 1
                continue

        try:
            # Collect conjuge data
            cpfconj = clean_doc(safe_str(row.get("cpfconj")) or "")
            rgconj = safe_str(row.get("rgconj")) or ""
            dtnascconj = safe_date(row.get("dtnascconj"))
            rendaconj = safe_float(row.get("rendaconj"))
            nacionconj = safe_str(row.get("nacionconj")) or ""
            conjpaif = safe_str(row.get("conjpaif")) or safe_str(row.get("conjpai")) or ""
            conjmaef = safe_str(row.get("conjmaef")) or safe_str(row.get("conjmae")) or ""
            atividadeconj = safe_str(row.get("atividadeconj"))
            conjempresaf = safe_str(row.get("conjempresaf"))
            conjtrabalha_raw = safe_int(row.get("conjtrabalha") or row.get("conjtrabalhaf") or 0)
            conjtrabalha = bool(conjtrabalha_raw)
            conjtelemp = safe_str(row.get("conjtelemp")) or safe_str(row.get("conjtelempf")) or ""
            emissaorgconj = safe_str(row.get("emissaorgconj"))
            orgaorgconj = safe_str(row.get("orgaorgconj"))
            admissaoconj = safe_date(row.get("admissaoconj"))

            # 1. Create Pessoa for conjuge
            obs_parts = []
            if emissaorgconj:
                obs_parts.append(f"RG emissão: {emissaorgconj}")
            if orgaorgconj:
                obs_parts.append(f"RG órgão: {orgaorgconj}")
            obs = "\n".join(obs_parts) or None

            nacion_id = cfg.DEFAULT_NACIONALIDADE_ID if nacionconj.lower().startswith("brasil") else None

            with conn.cursor() as cur:
                cur.execute("""
                    INSERT INTO pessoas
                        (nome, dt_cadastro, tipo_pessoa, status, fisica_juridica,
                         data_nascimento, renda, nome_pai, nome_mae,
                         observacoes, theme_light, nacionalidade_id)
                    VALUES (%s, NOW(), %s, TRUE, 'fisica', %s, %s, %s, %s, %s, TRUE, %s)
                    RETURNING idpessoa
                """, (
                    nomeconj[:100],
                    cfg.TIPO_PESSOA_FIADOR_ID,  # conjuge é fiador também
                    dtnascconj,
                    str(rendaconj) if rendaconj > 0 else None,
                    conjpaif[:100] if conjpaif else None,
                    conjmaef[:100] if conjmaef else None,
                    obs,
                    nacion_id,
                ))
                conjuge_pessoa_id = cur.fetchone()[0]

            stats['fiador_conjuges_criados'] += 1
            print(f"  Cônjuge criado: '{nomeconj}' -> pessoa {conjuge_pessoa_id} (fiador pessoa {fiador_pessoa_id})")

            # 2. Create CPF document
            if cpfconj and cpfconj not in ("0", "00"):
                with conn.cursor() as cur:
                    cur.execute("""
                        INSERT INTO pessoas_documentos (id_pessoa, id_tipo_documento, numero_documento, ativo)
                        VALUES (%s, %s, %s, TRUE) ON CONFLICT DO NOTHING
                    """, (conjuge_pessoa_id, cfg.DEFAULT_TIPO_DOCUMENTO_CPF_ID, cpfconj[:30]))
                stats['fiador_docs_criados'] += 1

            # 3. Create RG document
            if rgconj and rgconj not in ("0", "00"):
                rg_clean = clean_doc(rgconj)[:30]
                if rg_clean:
                    with conn.cursor() as cur:
                        cur.execute("""
                            INSERT INTO pessoas_documentos (id_pessoa, id_tipo_documento, numero_documento, ativo)
                            VALUES (%s, %s, %s, TRUE) ON CONFLICT DO NOTHING
                        """, (conjuge_pessoa_id, cfg.DEFAULT_TIPO_DOCUMENTO_RG_ID, rg_clean))
                    stats['fiador_docs_criados'] += 1

            # 4. Create profissao if atividadeconj
            if atividadeconj:
                with conn.cursor() as cur:
                    # Find or create profissao
                    cur.execute(
                        "SELECT id FROM profissoes WHERE UPPER(nome) = UPPER(%s) LIMIT 1",
                        (atividadeconj[:100],)
                    )
                    prof_row = cur.fetchone()
                    if prof_row:
                        prof_id = prof_row[0]
                    else:
                        cur.execute(
                            "INSERT INTO profissoes (nome, ativo) VALUES (%s, TRUE) RETURNING id",
                            (atividadeconj[:100],)
                        )
                        prof_id = cur.fetchone()[0]

                    # Junction pessoas_profissoes
                    cur.execute("""
                        INSERT INTO pessoas_profissoes (id_pessoa, id_profissao, renda, empresa, data_admissao, ativo)
                        VALUES (%s, %s, %s, %s, %s, TRUE) ON CONFLICT DO NOTHING
                    """, (
                        conjuge_pessoa_id,
                        prof_id,
                        str(rendaconj) if rendaconj > 0 else None,
                        conjempresaf[:100] if conjempresaf else None,
                        admissaoconj,
                    ))
                stats['fiador_profs_criados'] += 1

            # 5. Create telefone if conjtelemp
            tel = re.sub(r"\s+", "", conjtelemp)[:25]
            if tel:
                with conn.cursor() as cur:
                    cur.execute(
                        "SELECT id FROM telefones WHERE numero = %s LIMIT 1",
                        (tel,)
                    )
                    tel_row = cur.fetchone()
                    if tel_row:
                        tel_id = tel_row[0]
                    else:
                        cur.execute(
                            "INSERT INTO telefones (id_tipo, numero) VALUES (%s, %s) RETURNING id",
                            (cfg.TIPO_TELEFONE_COMERCIAL_ID, tel)
                        )
                        tel_id = cur.fetchone()[0]

                    cur.execute("""
                        INSERT INTO pessoas_telefones (id_pessoa, id_telefone)
                        VALUES (%s, %s) ON CONFLICT DO NOTHING
                    """, (conjuge_pessoa_id, tel_id))
                stats['fiador_tels_criados'] += 1

            # 6. Update pessoas_fiadores.id_conjuge and conjuge_trabalha
            with conn.cursor() as cur:
                cur.execute("""
                    UPDATE pessoas_fiadores
                    SET id_conjuge = %s, conjuge_trabalha = %s
                    WHERE id_pessoa = %s
                """, (conjuge_pessoa_id, conjtrabalha, fiador_pessoa_id))

            # 7. Add conjuge to pessoas_tipos as fiador too
            with conn.cursor() as cur:
                cur.execute("""
                    INSERT INTO pessoas_tipos (id_pessoa, id_tipo_pessoa, data_inicio, ativo)
                    VALUES (%s, %s, CURRENT_DATE, TRUE) ON CONFLICT DO NOTHING
                """, (conjuge_pessoa_id, cfg.TIPO_PESSOA_FIADOR_ID))

            conn.commit()

        except Exception as e:
            conn.rollback()
            print(f"  ERRO fiador old={old_id}: {e}")
            stats['erros'] += 1

    # =========================================================================
    # PARTE 2: Cônjuges de INQUILINOS (locinquilino.nomecjg)
    # =========================================================================
    print()
    print("=" * 60)
    print("PARTE 2: Cônjuges de Inquilinos")
    print("=" * 60)

    for row in parser.iter_table("locinquilino"):
        old_id = safe_int(row.get("codigo"))
        nomecjg = safe_str(row.get("nomecjg"))

        if not nomecjg:
            continue

        inq_pessoa_id = state.get_new_id("locinquilino", old_id)
        if not inq_pessoa_id:
            print(f"  WARN: Inquilino old={old_id} não encontrado no id_map")
            stats['erros'] += 1
            continue

        # Check if already noted (check observacoes for conjuge marker)
        with conn.cursor() as cur:
            cur.execute(
                "SELECT observacoes FROM pessoas WHERE idpessoa = %s",
                (inq_pessoa_id,)
            )
            result = cur.fetchone()
            obs_current = result[0] if result and result[0] else ""
            if f"Cônjuge: {nomecjg}" in obs_current:
                stats['inq_conjuges_ja_existiam'] += 1
                continue

        try:
            # Create Pessoa for conjuge (minimal data - only name available)
            with conn.cursor() as cur:
                cur.execute("""
                    INSERT INTO pessoas
                        (nome, dt_cadastro, tipo_pessoa, status, fisica_juridica,
                         observacoes, theme_light)
                    VALUES (%s, NOW(), %s, TRUE, 'fisica', %s, TRUE)
                    RETURNING idpessoa
                """, (
                    nomecjg[:100],
                    cfg.TIPO_PESSOA_INQUILINO_ID,
                    f"Cônjuge do inquilino pessoa #{inq_pessoa_id}",
                ))
                conjuge_pessoa_id = cur.fetchone()[0]

            # Add to pessoas_tipos
            with conn.cursor() as cur:
                cur.execute("""
                    INSERT INTO pessoas_tipos (id_pessoa, id_tipo_pessoa, data_inicio, ativo)
                    VALUES (%s, %s, CURRENT_DATE, TRUE) ON CONFLICT DO NOTHING
                """, (conjuge_pessoa_id, cfg.TIPO_PESSOA_INQUILINO_ID))

            # Update inquilino observacoes to note conjuge
            new_obs = f"{obs_current}\nCônjuge: {nomecjg} (pessoa #{conjuge_pessoa_id})".strip()
            with conn.cursor() as cur:
                cur.execute(
                    "UPDATE pessoas SET observacoes = %s WHERE idpessoa = %s",
                    (new_obs, inq_pessoa_id)
                )

            conn.commit()
            stats['inq_conjuges_criados'] += 1
            print(f"  Cônjuge criado: '{nomecjg}' -> pessoa {conjuge_pessoa_id} (inquilino pessoa {inq_pessoa_id})")

        except Exception as e:
            conn.rollback()
            print(f"  ERRO inquilino old={old_id}: {e}")
            stats['erros'] += 1

    conn.close()

    # =========================================================================
    # RESUMO
    # =========================================================================
    print()
    print("=" * 60)
    print("RESUMO")
    print("=" * 60)
    for k, v in stats.items():
        print(f"  {k}: {v}")

    total = stats['fiador_conjuges_criados'] + stats['inq_conjuges_criados']
    print(f"\nRESULTADO: {total} cônjuges criados, "
          f"{stats['fiador_conjuges_ja_existiam'] + stats['inq_conjuges_ja_existiam']} já existiam, "
          f"{stats['erros']} erros")


if __name__ == "__main__":
    main()
