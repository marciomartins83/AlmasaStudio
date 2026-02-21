#!/usr/bin/env python3
"""
migrate.py — Script de Migração MySQL 5.5 -> PostgreSQL 15
AlmasaStudio | Sistema de Gestão Imobiliária

v2 (2026-02-21) — Reescrito com parametrização correta:
  - Fase 00: Valida TODAS tabelas de parâmetros antes de importar
  - tipos_pessoas corrigidos (locador=4, fiador=1, contratante=6, inquilino=12)
  - Telefones classificados por campo (residencial/celular/comercial)
  - Cache de bairros validado contra DB (elimina FK violations por cache stale)
  - Fase 12 corrige enderecos.id_pessoa dos imóveis
  - Fase 16 idempotente

Uso:
    python3 migrate.py --phase all          # Executa todas as fases (inclui 00)
    python3 migrate.py --phase 01           # Só a fase 01 (bancos)
    python3 migrate.py --phase 08,09,10     # Fases específicas
    python3 migrate.py --list               # Lista as fases disponíveis
    python3 migrate.py --dry-run --phase all  # Simula sem gravar
    python3 migrate.py --reset-phase 08     # Desmarca fase para re-execução

Engenheiro Senior: Opus 4.6
Data: 2026-02-21
"""

import argparse
import json
import logging
import os
import re
import sys
import traceback
import calendar
from datetime import datetime, date
from typing import Any, Dict, Iterator, List, Optional, Tuple

import psycopg2
import psycopg2.extras

# ---------------------------------------------------------------------------
# Importa configuração local
# ---------------------------------------------------------------------------
sys.path.insert(0, os.path.dirname(__file__))
import config as cfg

# ---------------------------------------------------------------------------
# LOGGING
# ---------------------------------------------------------------------------

os.makedirs(cfg.LOG_DIR, exist_ok=True)

_log_file = os.path.join(cfg.LOG_DIR, f"migrate_{datetime.now().strftime('%Y%m%d_%H%M%S')}.log")

logging.basicConfig(
    level=logging.DEBUG,
    format="%(asctime)s [%(levelname)s] %(message)s",
    handlers=[
        logging.FileHandler(_log_file, encoding="utf-8"),
        logging.StreamHandler(sys.stdout),
    ],
)
log = logging.getLogger("migrate")


# ===========================================================================
# UTILITÁRIOS GERAIS
# ===========================================================================

def safe_str(value: Any, encoding: str = "utf-8") -> Optional[str]:
    """Converte bytes latin-1 para str UTF-8 limpa, retorna None se vazio."""
    if value is None:
        return None
    if isinstance(value, bytes):
        value = value.decode(cfg.MYSQL_DUMP_ENCODING, errors="replace")
    s = str(value).strip()
    return s if s else None


def safe_date(value: Any) -> Optional[date]:
    """
    Converte string de data MySQL para objeto date.
    Retorna None para datas inválidas: '0000-00-00', '1900-01-01', '1901-01-01'.
    """
    if value is None:
        return None
    s = str(value).strip()
    if not s or s in ("0000-00-00", "1900-01-01", "1901-01-01", "NULL", "null"):
        return None
    try:
        return datetime.strptime(s, "%Y-%m-%d").date()
    except ValueError:
        log.warning(f"Data invalida ignorada: {s!r}")
        return None


def safe_float(value: Any, default: float = 0.0) -> float:
    """Converte para float, retorna default se falhar."""
    if value is None:
        return default
    try:
        f = float(str(value).strip())
        return f if f == f else default  # NaN check
    except (ValueError, TypeError):
        return default


def safe_int(value: Any, default: int = 0) -> int:
    """Converte para int, retorna default se falhar."""
    if value is None:
        return default
    try:
        return int(str(value).strip().split(".")[0])
    except (ValueError, TypeError):
        return default


def safe_money(value: Any, max_val: float = 99999999.99) -> Optional[str]:
    """Converte para valor monetário com cap para evitar numeric overflow."""
    if value is None:
        return None
    f = safe_float(value)
    if f == 0.0:
        return None
    if f > max_val:
        f = max_val
    if f < -max_val:
        f = -max_val
    return str(round(f, 2))


def clean_doc(doc: str) -> str:
    """Remove pontuação de CPF/CNPJ/RG para armazenamento limpo."""
    if not doc:
        return ""
    return re.sub(r"[.\-/\s]", "", doc.strip())


def parse_competencia(competencia_str: str) -> Optional[date]:
    """
    Converte competência MySQL (formato 'MM/AAAA' ou 'AAAA-MM') para date (primeiro dia do mês).
    """
    if not competencia_str:
        return None
    s = competencia_str.strip()
    # Tenta MM/AAAA
    m = re.match(r"^(\d{1,2})/(\d{4})$", s)
    if m:
        try:
            return date(int(m.group(2)), int(m.group(1)), 1)
        except ValueError:
            pass
    # Tenta AAAA-MM
    m = re.match(r"^(\d{4})-(\d{2})$", s)
    if m:
        try:
            return date(int(m.group(1)), int(m.group(2)), 1)
        except ValueError:
            pass
    log.warning(f"Competencia nao reconhecida: {s!r}")
    return None


# ===========================================================================
# PARSER DO DUMP MYSQL
# ===========================================================================

class MySQLDumpParser:
    """
    Parseia um dump MySQL 5.5 em formato SQL sem precisar de servidor MySQL.

    Estratégia:
    1. Lê o arquivo linha a linha (evita carregar 285MB em RAM de uma vez).
    2. Identifica blocos INSERT INTO `table` VALUES (...), (...);
    3. Extrai as colunas do CREATE TABLE correspondente.
    4. Usa um tokenizer robusto para separar os valores (trata strings com vírgulas,
       aspas escapadas, NULL, etc.).
    """

    def __init__(self, dump_path: str):
        self.dump_path = dump_path
        self._schema: Dict[str, List[str]] = {}  # table -> [col1, col2, ...]
        self._parsed_schema = False

    # ------------------------------------------------------------------
    # Pré-processamento: extrai esquema (colunas) de CREATE TABLE
    # ------------------------------------------------------------------

    def parse_schema(self) -> Dict[str, List[str]]:
        """
        Lê o dump inteiro uma vez e extrai os nomes de colunas de cada CREATE TABLE.
        Retorna dict: {table_name: [col1, col2, ...]}
        """
        if self._parsed_schema:
            return self._schema

        log.info("Parseando schema do dump MySQL...")
        schema: Dict[str, List[str]] = {}
        in_create = False
        current_table = None
        current_cols: List[str] = []

        with open(self.dump_path, "r", encoding=cfg.MYSQL_DUMP_ENCODING, errors="replace") as f:
            for line in f:
                line = line.rstrip("\n")

                # Detecta início de CREATE TABLE
                m = re.match(r"^CREATE TABLE `(.+?)`\s*\(", line)
                if m:
                    in_create = True
                    current_table = m.group(1)
                    current_cols = []
                    continue

                if in_create:
                    # Fim do CREATE TABLE
                    if re.match(r"^\)\s*ENGINE=", line):
                        schema[current_table] = current_cols
                        in_create = False
                        current_table = None
                        current_cols = []
                        continue

                    # Linha de coluna: `nome_coluna` tipo ...
                    col_m = re.match(r"^\s+`(.+?)`\s+", line)
                    if col_m:
                        col_name = col_m.group(1)
                        # Ignora chaves/indexes (PRIMARY KEY, KEY, UNIQUE KEY, INDEX)
                        if col_name.upper() not in ("PRIMARY", "KEY", "UNIQUE", "INDEX", "CONSTRAINT"):
                            current_cols.append(col_name)

        self._schema = schema
        self._parsed_schema = True
        log.info(f"Schema parseado: {len(schema)} tabelas encontradas.")
        return schema

    # ------------------------------------------------------------------
    # Tokenizer de valores MySQL INSERT
    # ------------------------------------------------------------------

    @staticmethod
    def tokenize_values(values_str: str) -> List[Optional[str]]:
        """
        Tokeniza uma string de valores MySQL de um INSERT.
        Exemplo: "'Joao\\'s',123,NULL,'foo,bar'"
        Retorna: ["Joao's", "123", None, "foo,bar"]

        Tratamento especial:
        - NULL -> None
        - Strings entre aspas simples com escapes \\' e \\\\
        - Números sem aspas
        """
        tokens: List[Optional[str]] = []
        i = 0
        n = len(values_str)

        while i < n:
            # Pula espaços
            while i < n and values_str[i] in (' ', '\t'):
                i += 1

            if i >= n:
                break

            # NULL
            if values_str[i:i+4].upper() == "NULL":
                tokens.append(None)
                i += 4
                # Avança até próxima vírgula ou fim
                while i < n and values_str[i] not in (',',):
                    i += 1
                if i < n and values_str[i] == ',':
                    i += 1
                continue

            # String entre aspas simples
            if values_str[i] == "'":
                i += 1  # pula abre aspas
                buf = []
                while i < n:
                    c = values_str[i]
                    if c == '\\' and i + 1 < n:
                        nc = values_str[i + 1]
                        if nc == "'":
                            buf.append("'")
                        elif nc == '\\':
                            buf.append('\\')
                        elif nc == 'n':
                            buf.append('\n')
                        elif nc == 'r':
                            buf.append('\r')
                        elif nc == 't':
                            buf.append('\t')
                        elif nc == '0':
                            buf.append('\x00')
                        else:
                            buf.append(nc)
                        i += 2
                    elif c == "'":
                        i += 1  # fecha aspas
                        break
                    else:
                        buf.append(c)
                        i += 1
                tokens.append("".join(buf))
                # Avança até próxima vírgula
                while i < n and values_str[i] in (' ', '\t'):
                    i += 1
                if i < n and values_str[i] == ',':
                    i += 1
                continue

            # Número ou outro valor sem aspas
            j = i
            while j < n and values_str[j] not in (',',):
                j += 1
            raw = values_str[i:j].strip()
            if raw.upper() == "NULL":
                tokens.append(None)
            else:
                tokens.append(raw)
            i = j
            if i < n and values_str[i] == ',':
                i += 1

        return tokens

    # ------------------------------------------------------------------
    # Iterador principal: retorna linhas como dicionários
    # ------------------------------------------------------------------

    def iter_table(self, table_name: str) -> Iterator[Dict[str, Any]]:
        """
        Itera sobre todas as linhas de uma tabela do dump.
        Yields: dict {coluna: valor} por linha.
        Valores NULL ficam como None.
        Strings são decodificadas de latin-1 para UTF-8.
        """
        schema = self.parse_schema()
        columns = schema.get(table_name)
        if columns is None:
            log.warning(f"Tabela '{table_name}' nao encontrada no schema do dump.")
            return

        log.info(f"Iterando tabela '{table_name}' ({len(columns)} colunas)...")
        count = 0
        in_insert = False
        accumulated = ""

        with open(self.dump_path, "r", encoding=cfg.MYSQL_DUMP_ENCODING, errors="replace") as f:
            for raw_line in f:
                line = raw_line.rstrip("\n")

                # Detecta início do bloco INSERT da tabela alvo
                insert_m = re.match(
                    r"^INSERT INTO `" + re.escape(table_name) + r"` VALUES\s*(.*)$",
                    line
                )
                if insert_m:
                    in_insert = True
                    rest = insert_m.group(1).strip()
                    accumulated = rest
                    # Se a linha termina com ; é linha única
                    if rest.endswith(";"):
                        accumulated = rest[:-1]
                        yield from self._parse_insert_block(accumulated, columns, table_name)
                        count_yielded = accumulated.count("),(")
                        in_insert = False
                        accumulated = ""
                    continue

                if in_insert:
                    accumulated += line.strip()
                    if line.strip().endswith(";"):
                        accumulated = accumulated[:-1]  # remove ;
                        yield from self._parse_insert_block(accumulated, columns, table_name)
                        in_insert = False
                        accumulated = ""

        log.info(f"Tabela '{table_name}': iteracao concluida.")

    def _parse_insert_block(
        self, block: str, columns: List[str], table_name: str
    ) -> Iterator[Dict[str, Any]]:
        """
        Parseia o bloco de VALUES de um INSERT.
        block: '(v1,v2,...),(v3,v4,...)'
        """
        # Divide em grupos de (...) respeitando strings aninhadas
        depth = 0
        current = []
        i = 0
        in_str = False
        escape = False

        while i < len(block):
            c = block[i]

            if escape:
                current.append(c)
                escape = False
                i += 1
                continue

            if c == '\\' and in_str:
                current.append(c)
                escape = True
                i += 1
                continue

            if c == "'" and not escape:
                in_str = not in_str
                current.append(c)
                i += 1
                continue

            if in_str:
                current.append(c)
                i += 1
                continue

            if c == '(':
                if depth == 0:
                    current = []  # começa nova tupla
                else:
                    current.append(c)
                depth += 1
                i += 1
                continue

            if c == ')':
                depth -= 1
                if depth == 0:
                    # Fim de uma tupla
                    row_str = "".join(current)
                    try:
                        values = self.tokenize_values(row_str)
                        if len(values) != len(columns):
                            log.warning(
                                f"{table_name}: colunas={len(columns)} valores={len(values)} "
                                f"— row parcial ignorada"
                            )
                        else:
                            row: Dict[str, Any] = {}
                            for col, val in zip(columns, values):
                                row[col] = self._decode_value(val)
                            yield row
                    except Exception as e:
                        log.error(f"{table_name}: erro ao parsear linha: {e}")
                else:
                    current.append(c)
                i += 1
                continue

            current.append(c)
            i += 1

    def _decode_value(self, val: Optional[str]) -> Optional[str]:
        """Garante que strings estejam em UTF-8 válido."""
        if val is None:
            return None
        # Re-encode bytes latin-1 -> utf-8 se necessário
        try:
            encoded = val.encode(cfg.MYSQL_DUMP_ENCODING)
            return encoded.decode("utf-8", errors="replace")
        except Exception:
            return val


# ===========================================================================
# ESCRITOR POSTGRESQL
# ===========================================================================

class PostgreSQLWriter:
    """
    Gerencia conexão com PostgreSQL e operações de escrita.
    Usa psycopg2 com execute_values para batches eficientes.
    """

    def __init__(self, dsn: str, dry_run: bool = False):
        self.dsn = dsn
        self.dry_run = dry_run
        self._conn: Optional[psycopg2.extensions.connection] = None

    def connect(self) -> None:
        log.info("Conectando ao PostgreSQL...")
        self._conn = psycopg2.connect(self.dsn)
        self._conn.autocommit = False
        log.info("Conectado com sucesso.")

    def disconnect(self) -> None:
        if self._conn:
            self._conn.close()
            self._conn = None

    @property
    def conn(self) -> psycopg2.extensions.connection:
        if not self._conn or self._conn.closed:
            self.connect()
        return self._conn

    def execute(self, sql: str, params: tuple = ()) -> None:
        if self.dry_run:
            log.debug(f"DRY RUN: {sql[:120]!r} params={params!r}")
            return
        with self.conn.cursor() as cur:
            cur.execute(sql, params)

    def execute_returning(self, sql: str, params: tuple = ()) -> Any:
        if self.dry_run:
            log.debug(f"DRY RUN RETURNING: {sql[:120]!r}")
            return None
        with self.conn.cursor() as cur:
            cur.execute(sql, params)
            return cur.fetchone()

    def execute_batch(self, sql: str, rows: List[tuple]) -> int:
        if not rows:
            return 0
        if self.dry_run:
            log.debug(f"DRY RUN BATCH: {sql[:80]!r} ({len(rows)} rows)")
            return len(rows)
        with self.conn.cursor() as cur:
            psycopg2.extras.execute_values(cur, sql, rows, page_size=cfg.BATCH_SIZE)
        return len(rows)

    def commit(self) -> None:
        if not self.dry_run:
            self.conn.commit()

    def rollback(self) -> None:
        if not self.dry_run:
            self.conn.rollback()

    def fetch_one(self, sql: str, params: tuple = ()) -> Optional[tuple]:
        with self.conn.cursor() as cur:
            cur.execute(sql, params)
            return cur.fetchone()

    def fetch_all(self, sql: str, params: tuple = ()) -> List[tuple]:
        with self.conn.cursor() as cur:
            cur.execute(sql, params)
            return cur.fetchall()

    def table_exists(self, table: str) -> bool:
        row = self.fetch_one(
            "SELECT 1 FROM information_schema.tables WHERE table_name=%s AND table_schema='public'",
            (table,)
        )
        return row is not None

    def count(self, table: str) -> int:
        row = self.fetch_one(f"SELECT COUNT(*) FROM {table}")
        return row[0] if row else 0


# ===========================================================================
# GERENCIADOR DE ESTADO (ID MAP + FASES CONCLUÍDAS)
# ===========================================================================

class StateManager:
    """
    Persiste o mapeamento old_id -> new_id em JSON entre reinicializações.
    Também controla quais fases já foram concluídas.
    """

    def __init__(self):
        os.makedirs(cfg.LOG_DIR, exist_ok=True)
        self._id_map: Dict[str, Dict[str, int]] = {}
        self._phases_done: List[str] = []
        self._load()

    def _load(self) -> None:
        if os.path.exists(cfg.ID_MAP_FILE):
            try:
                with open(cfg.ID_MAP_FILE, "r", encoding="utf-8") as f:
                    self._id_map = json.load(f)
                log.info(f"ID map carregado: {sum(len(v) for v in self._id_map.values())} entradas")
            except Exception as e:
                log.warning(f"Nao foi possivel carregar ID map: {e}")
                self._id_map = {}
        if os.path.exists(cfg.PHASES_DONE_FILE):
            try:
                with open(cfg.PHASES_DONE_FILE, "r", encoding="utf-8") as f:
                    self._phases_done = json.load(f)
                log.info(f"Fases ja concluidas: {self._phases_done}")
            except Exception as e:
                log.warning(f"Nao foi possivel carregar phases_done: {e}")
                self._phases_done = []

    def save(self) -> None:
        with open(cfg.ID_MAP_FILE, "w", encoding="utf-8") as f:
            json.dump(self._id_map, f, ensure_ascii=False, indent=2)
        with open(cfg.PHASES_DONE_FILE, "w", encoding="utf-8") as f:
            json.dump(self._phases_done, f, ensure_ascii=False, indent=2)

    def set_mapping(self, namespace: str, old_id: Any, new_id: int) -> None:
        ns = self._id_map.setdefault(namespace, {})
        ns[str(old_id)] = new_id

    def get_new_id(self, namespace: str, old_id: Any) -> Optional[int]:
        return self._id_map.get(namespace, {}).get(str(old_id))

    def is_phase_done(self, phase: str) -> bool:
        return phase in self._phases_done

    def mark_phase_done(self, phase: str) -> None:
        if phase not in self._phases_done:
            self._phases_done.append(phase)
        self.save()

    def unmark_phase(self, phase: str) -> None:
        if phase in self._phases_done:
            self._phases_done.remove(phase)
        self.save()

    def get_mappings(self, namespace: str) -> Dict[str, int]:
        return self._id_map.get(namespace, {})


# ===========================================================================
# CLASSES DE TRANSFORMAÇÃO POR FASE
# ===========================================================================

class BasePhase:
    """Classe base para todas as fases de migração."""

    def __init__(self, parser: MySQLDumpParser, writer: PostgreSQLWriter, state: StateManager):
        self.parser = parser
        self.writer = writer
        self.state = state

    def run(self) -> int:
        """Executa a fase. Retorna número de registros migrados."""
        raise NotImplementedError

    def _insert_pessoa(
        self,
        nome: str,
        tipo_pessoa: int,
        fisica_juridica: str,
        status: bool,
        data_nascimento: Optional[date],
        estado_civil_id: Optional[int],
        renda: Optional[float],
        nome_pai: Optional[str],
        nome_mae: Optional[str],
        observacoes: Optional[str],
    ) -> Optional[int]:
        """
        Insere uma linha em `pessoas` e retorna o novo idpessoa.
        Usa ON CONFLICT (nome, tipo_pessoa) para idempotência.
        """
        sql = """
            INSERT INTO pessoas
                (nome, dt_cadastro, tipo_pessoa, status, fisica_juridica,
                 data_nascimento, estado_civil_id, renda, nome_pai, nome_mae,
                 observacoes, theme_light)
            VALUES (%s, NOW(), %s, %s, %s, %s, %s, %s, %s, %s, %s, TRUE)
            RETURNING idpessoa
        """
        row = self.writer.execute_returning(sql, (
            nome[:100] if nome else "SEM NOME",
            tipo_pessoa,
            status,
            fisica_juridica,
            data_nascimento,
            estado_civil_id,
            str(renda) if renda else None,
            nome_pai[:100] if nome_pai else None,
            nome_mae[:100] if nome_mae else None,
            observacoes,
        ))
        return row[0] if row else None

    def _get_or_create_bairro(
        self,
        bairro_nome: str,
        cidade_nome: str,
        estado_uf: str,
    ) -> Optional[int]:
        """
        Encontra ou cria bairro, cidade e estado conforme necessário.
        Retorna id do bairro.
        """
        if not bairro_nome:
            bairro_nome = "SEM BAIRRO"
        bairro_nome = bairro_nome.strip()[:100]
        estado_uf = (estado_uf or "SP").upper()[:2]
        cidade_nome = (cidade_nome or "SEM CIDADE").strip()[:100]

        # 1. Estado
        estado_id = self.state.get_new_id("estado", estado_uf)
        if not estado_id:
            existing = self.writer.fetch_one("SELECT id FROM estados WHERE uf = %s", (estado_uf,))
            if existing:
                estado_id = existing[0]
                self.state.set_mapping("estado", estado_uf, estado_id)
            else:
                row = self.writer.execute_returning(
                    "INSERT INTO estados (uf, nome) VALUES (%s, %s) RETURNING id",
                    (estado_uf, estado_uf)
                )
                if row:
                    estado_id = row[0]
                    self.state.set_mapping("estado", estado_uf, estado_id)

        # 2. Cidade — busca case-insensitive via JOIN com estados.uf
        #    para evitar mismatch de id_estado entre Phase 06 e estados reais
        cidade_key = f"{estado_uf}_{cidade_nome.upper()}"
        cidade_id = self.state.get_new_id("cidade", cidade_key)
        if not cidade_id:
            existing = self.writer.fetch_one(
                """SELECT c.id FROM cidades c
                   JOIN estados e ON e.id = c.id_estado
                   WHERE UPPER(c.nome) = UPPER(%s) AND e.uf = %s
                   LIMIT 1""",
                (cidade_nome, estado_uf)
            )
            if not existing:
                # Fallback: busca case-insensitive sem filtro de estado
                existing = self.writer.fetch_one(
                    "SELECT id FROM cidades WHERE UPPER(nome) = UPPER(%s) LIMIT 1",
                    (cidade_nome,)
                )
            if existing:
                cidade_id = existing[0]
                self.state.set_mapping("cidade", cidade_key, cidade_id)
            else:
                row = self.writer.execute_returning(
                    "INSERT INTO cidades (nome, id_estado) VALUES (%s, %s) RETURNING id",
                    (cidade_nome, estado_id)
                )
                if row:
                    cidade_id = row[0]
                    self.state.set_mapping("cidade", cidade_key, cidade_id)

        # 3. Bairro — busca case-insensitive com validação de cache
        bairro_key = f"{estado_uf}_{cidade_nome.upper()}_{bairro_nome.upper()}"
        bairro_id = self.state.get_new_id("bairro", bairro_key)

        # Valida cache: confirma que o ID ainda existe no DB (pode ter sido de transação rollback)
        if bairro_id:
            check = self.writer.fetch_one("SELECT id FROM bairros WHERE id = %s", (bairro_id,))
            if not check:
                log.warning(f"Cache bairro stale: id={bairro_id} nao existe no DB. Limpando cache.")
                bairro_id = None

        if not bairro_id:
            existing = self.writer.fetch_one(
                """SELECT b.id FROM bairros b
                   WHERE UPPER(b.nome) = UPPER(%s) AND b.id_cidade = %s
                   LIMIT 1""",
                (bairro_nome, cidade_id)
            )
            if not existing:
                existing = self.writer.fetch_one(
                    """SELECT b.id FROM bairros b
                       JOIN cidades c ON c.id = b.id_cidade
                       WHERE UPPER(b.nome) = UPPER(%s) AND UPPER(c.nome) = UPPER(%s)
                       LIMIT 1""",
                    (bairro_nome, cidade_nome)
                )
            if existing:
                bairro_id = existing[0]
                self.state.set_mapping("bairro", bairro_key, bairro_id)
            else:
                row = self.writer.execute_returning(
                    "INSERT INTO bairros (nome, id_cidade) VALUES (%s, %s) RETURNING id",
                    (bairro_nome, cidade_id)
                )
                if row:
                    bairro_id = row[0]
                    self.state.set_mapping("bairro", bairro_key, bairro_id)

        return bairro_id

    def _get_or_create_logradouro(
        self,
        endereco_str: str,
        numero: str,
        bairro: str,
        cidade: str,
        estado: str,
        cep: str,
    ) -> Optional[int]:
        """
        Encontra ou cria logradouro normalizado.
        Retorna id do logradouro.
        Schema real: logradouros(id, id_bairro NOT NULL, logradouro NOT NULL, cep NOT NULL, created_at, updated_at)
        """
        logr_nome = (endereco_str or "SEM ENDERECO").strip()[:255]
        cep_clean = re.sub(r"\D", "", cep or "")[:8] if cep else ""
        if not cep_clean:
            cep_clean = "00000000"

        # Obtém ou cria bairro
        bairro_id = self._get_or_create_bairro(bairro, cidade, estado)
        if not bairro_id:
            log.warning(f"Nao foi possivel criar bairro para logradouro: {logr_nome}")
            return None

        # Verifica se ja existe pelo logradouro + cep + bairro
        existing = self.writer.fetch_one(
            "SELECT id FROM logradouros WHERE logradouro = %s AND cep = %s AND id_bairro = %s LIMIT 1",
            (logr_nome, cep_clean, bairro_id)
        )
        if existing:
            return existing[0]

        # Cria novo logradouro
        sql = """
            INSERT INTO logradouros (id_bairro, logradouro, cep, created_at, updated_at)
            VALUES (%s, %s, %s, NOW(), NOW())
            RETURNING id
        """
        row = self.writer.execute_returning(sql, (bairro_id, logr_nome, cep_clean))
        return row[0] if row else None

    def _insert_endereco(
        self,
        pessoa_id: int,
        logradouro_id: int,
        numero: str,
        complemento: Optional[str],
        tipo_id: int = cfg.DEFAULT_TIPO_ENDERECO_ID,
    ) -> Optional[int]:
        """Insere linha em `enderecos` e retorna id."""
        # Converte numero para int (alguns têm letras — guarda como 0)
        try:
            num_int = int(re.sub(r"\D", "", numero or "0") or "0")
        except ValueError:
            num_int = 0

        sql = """
            INSERT INTO enderecos (id_pessoa, id_logradouro, id_tipo, end_numero, complemento)
            VALUES (%s, %s, %s, %s, %s)
            RETURNING id
        """
        row = self.writer.execute_returning(sql, (
            pessoa_id, logradouro_id, tipo_id, num_int,
            complemento[:100] if complemento else None
        ))
        return row[0] if row else None

    def _insert_telefone(
        self, pessoa_id: int, numero: str, tipo_id: int = cfg.TIPO_TELEFONE_CELULAR_ID
    ) -> None:
        """
        Insere telefone: 1) INSERT em telefones, 2) junction pessoas_telefones.
        Schema: telefones(id, id_tipo, numero)
                pessoas_telefones(id, id_pessoa, id_telefone)
        """
        if not numero:
            return
        numero = re.sub(r"\s+", "", numero)[:25]
        if not numero:
            return
        # 1. Cria ou encontra telefone
        existing = self.writer.fetch_one(
            "SELECT id FROM telefones WHERE numero = %s AND id_tipo = %s LIMIT 1",
            (numero, tipo_id)
        )
        if existing:
            tel_id = existing[0]
        else:
            row = self.writer.execute_returning(
                "INSERT INTO telefones (id_tipo, numero) VALUES (%s, %s) RETURNING id",
                (tipo_id, numero)
            )
            if not row:
                return
            tel_id = row[0]
        # 2. Junction
        existing_j = self.writer.fetch_one(
            "SELECT id FROM pessoas_telefones WHERE id_pessoa = %s AND id_telefone = %s",
            (pessoa_id, tel_id)
        )
        if not existing_j:
            self.writer.execute(
                "INSERT INTO pessoas_telefones (id_pessoa, id_telefone) VALUES (%s, %s)",
                (pessoa_id, tel_id)
            )

    def _insert_email(
        self, pessoa_id: int, email: str, tipo_id: int = cfg.DEFAULT_TIPO_EMAIL_ID
    ) -> None:
        """
        Insere email: 1) INSERT em emails, 2) junction pessoas_emails.
        Schema: emails(id, email, id_tipo, descricao)
                pessoas_emails(id, id_pessoa, id_email)
        """
        if not email or "@" not in email:
            return
        email = email.strip()[:100]
        # 1. Cria ou encontra email
        existing = self.writer.fetch_one(
            "SELECT id FROM emails WHERE email = %s LIMIT 1", (email,)
        )
        if existing:
            email_id = existing[0]
        else:
            row = self.writer.execute_returning(
                "INSERT INTO emails (email, id_tipo) VALUES (%s, %s) RETURNING id",
                (email, tipo_id)
            )
            if not row:
                return
            email_id = row[0]
        # 2. Junction
        existing_j = self.writer.fetch_one(
            "SELECT id FROM pessoas_emails WHERE id_pessoa = %s AND id_email = %s",
            (pessoa_id, email_id)
        )
        if not existing_j:
            self.writer.execute(
                "INSERT INTO pessoas_emails (id_pessoa, id_email) VALUES (%s, %s)",
                (pessoa_id, email_id)
            )

    def _insert_documento(
        self, pessoa_id: int, numero: str, tipo_id: int
    ) -> None:
        """
        Schema: pessoas_documentos(id, id_pessoa, id_tipo_documento, numero_documento,
                data_emissao, data_vencimento, orgao_emissor, observacoes, ativo)
        """
        if not numero:
            return
        numero = clean_doc(numero)[:30]
        if not numero or numero in ("0", "00"):
            return
        # Idempotência por pessoa + tipo + numero
        existing = self.writer.fetch_one(
            "SELECT id FROM pessoas_documentos WHERE id_pessoa = %s AND id_tipo_documento = %s AND numero_documento = %s",
            (pessoa_id, tipo_id, numero)
        )
        if existing:
            return
        sql = """
            INSERT INTO pessoas_documentos (id_pessoa, id_tipo_documento, numero_documento, ativo)
            VALUES (%s, %s, %s, TRUE)
        """
        self.writer.execute(sql, (pessoa_id, tipo_id, numero))


# ---------------------------------------------------------------------------
# Fase 00: Validação de Parametrização
# ---------------------------------------------------------------------------

class Phase00Validacao(BasePhase):
    """
    Valida que TODAS tabelas de parâmetros existem e têm os IDs esperados.
    Carrega IDs dinamicamente do banco para evitar hardcoding errado.
    Se algum ID obrigatório não existir, ABORTA a migração.
    """
    PHASE_ID = "00"

    REQUIRED_PARAMS = {
        "tipos_pessoas": {
            "locador": "TIPO_PESSOA_LOCADOR_ID",
            "fiador": "TIPO_PESSOA_FIADOR_ID",
            "contratante": "TIPO_PESSOA_CONTRATANTE_ID",
            "inquilino": "TIPO_PESSOA_INQUILINO_ID",
        },
        "tipos_documentos": {
            "CPF": "DEFAULT_TIPO_DOCUMENTO_CPF_ID",
            "RG": "DEFAULT_TIPO_DOCUMENTO_RG_ID",
            "CNPJ": "DEFAULT_TIPO_DOCUMENTO_CNPJ_ID",
        },
        "tipos_telefones": {
            "Residencial": "TIPO_TELEFONE_RESIDENCIAL_ID",
            "Celular": "TIPO_TELEFONE_CELULAR_ID",
            "Comercial": "TIPO_TELEFONE_COMERCIAL_ID",
        },
        "tipos_enderecos": {
            "Residencial": "DEFAULT_TIPO_ENDERECO_ID",
        },
        "tipos_emails": {
            "Pessoal": "DEFAULT_TIPO_EMAIL_ID",
        },
        "tipos_imoveis": {
            "Casa": "DEFAULT_TIPO_IMOVEL_ID",
        },
        "estado_civil": None,  # Apenas verifica que tem registros
    }

    def run(self) -> int:
        log.info("=== FASE 00: Validação de Parametrização ===")
        errors = []
        loaded = 0

        for table, expected in self.REQUIRED_PARAMS.items():
            # Verifica que a tabela existe
            if not self.writer.table_exists(table):
                errors.append(f"Tabela '{table}' NÃO EXISTE no banco!")
                continue

            count = self.writer.count(table)
            if count == 0:
                errors.append(f"Tabela '{table}' está VAZIA! Precisa ser populada antes da migração.")
                continue

            log.info(f"  {table}: {count} registros")

            if expected is None:
                continue

            # Determina coluna de nome (tipo ou nome)
            col_nome = "tipo"
            if table == "estado_civil":
                col_nome = "nome"

            # Carrega IDs e valida
            rows = self.writer.fetch_all(f"SELECT id, {col_nome} FROM {table} ORDER BY id")
            db_map = {r[1].strip(): r[0] for r in rows}

            for nome_esperado, config_attr in expected.items():
                # Busca case-insensitive
                found_id = None
                for db_nome, db_id in db_map.items():
                    if db_nome.lower() == nome_esperado.lower():
                        found_id = db_id
                        break

                if found_id is None:
                    errors.append(
                        f"  {table}: tipo '{nome_esperado}' NÃO encontrado! "
                        f"Existentes: {list(db_map.keys())}"
                    )
                else:
                    # Atualiza config dinamicamente
                    current_val = getattr(cfg, config_attr, None)
                    if current_val != found_id:
                        log.warning(
                            f"  {table}.{nome_esperado}: config diz id={current_val}, "
                            f"banco diz id={found_id}. CORRIGINDO para {found_id}."
                        )
                    setattr(cfg, config_attr, found_id)
                    loaded += 1
                    log.info(f"  {table}.{nome_esperado} = id {found_id} ✓")

        # Atualiza TELEFONE_FIELD_MAP após carregar IDs dinâmicos
        cfg.TELEFONE_FIELD_MAP = {
            "telefone": cfg.TIPO_TELEFONE_RESIDENCIAL_ID,
            "celular": cfg.TIPO_TELEFONE_CELULAR_ID,
            "comercial": cfg.TIPO_TELEFONE_COMERCIAL_ID,
            "tel": cfg.TIPO_TELEFONE_RESIDENCIAL_ID,
        }

        if errors:
            log.error("=" * 60)
            log.error("ERROS DE PARAMETRIZAÇÃO — MIGRAÇÃO ABORTADA!")
            log.error("=" * 60)
            for e in errors:
                log.error(f"  {e}")
            log.error("")
            log.error("Corrija os dados nas tabelas de parâmetros e tente novamente.")
            raise RuntimeError(f"Parametrização inválida: {len(errors)} erros encontrados.")

        log.info(f"Fase 00 concluída: {loaded} IDs carregados dinamicamente. Tudo OK.")
        self.writer.commit()
        return loaded


# ---------------------------------------------------------------------------
# Fase 01: banco -> bancos
# ---------------------------------------------------------------------------

class Phase01Bancos(BasePhase):
    PHASE_ID = "01"

    def run(self) -> int:
        log.info("=== FASE 01: banco -> bancos ===")
        count = 0
        for row in self.parser.iter_table("banco"):
            old_codigo = safe_int(row.get("Codigo"))
            nome = safe_str(row.get("Descricao")) or f"Banco {old_codigo}"

            # Idempotência: verifica se já existe por numero
            existing = self.writer.fetch_one(
                "SELECT id FROM bancos WHERE numero = %s", (old_codigo,)
            )
            if existing:
                self.state.set_mapping("banco", old_codigo, existing[0])
                log.debug(f"Banco {old_codigo} ja existe (id={existing[0]})")
                continue

            row_ret = self.writer.execute_returning(
                "INSERT INTO bancos (nome, numero) VALUES (%s, %s) RETURNING id",
                (nome[:50], old_codigo)
            )
            if row_ret:
                new_id = row_ret[0]
                self.state.set_mapping("banco", old_codigo, new_id)
                log.debug(f"Banco inserido: {old_codigo} -> {new_id} ({nome})")
                count += 1

        self.writer.commit()
        self.state.save()
        log.info(f"Fase 01 concluida: {count} bancos inseridos.")
        return count


# ---------------------------------------------------------------------------
# Fase 02: agencia -> agencias
# ---------------------------------------------------------------------------

class Phase02Agencias(BasePhase):
    PHASE_ID = "02"

    def run(self) -> int:
        log.info("=== FASE 02: agencia -> agencias ===")
        count = 0
        for row in self.parser.iter_table("agencia"):
            codigo_agencia = safe_str(row.get("Codigo")) or "0000"
            banco_old = safe_int(row.get("Banco"))
            nome = safe_str(row.get("Nome")) or f"Agência {codigo_agencia}"

            banco_new_id = self.state.get_new_id("banco", banco_old)
            if not banco_new_id:
                log.warning(f"Agencia {codigo_agencia}: banco {banco_old} nao mapeado, pulando.")
                continue

            # Idempotência: verifica se já existe
            existing = self.writer.fetch_one(
                "SELECT id FROM agencias WHERE codigo = %s AND id_banco = %s",
                (codigo_agencia, banco_new_id)
            )
            if existing:
                # Chave composta: banco_old + codigo_agencia
                map_key = f"{banco_old}_{codigo_agencia}"
                self.state.set_mapping("agencia", map_key, existing[0])
                continue

            row_ret = self.writer.execute_returning(
                "INSERT INTO agencias (codigo, id_banco, nome) VALUES (%s, %s, %s) RETURNING id",
                (codigo_agencia[:20], banco_new_id, nome[:100])
            )
            if row_ret:
                new_id = row_ret[0]
                map_key = f"{banco_old}_{codigo_agencia}"
                self.state.set_mapping("agencia", map_key, new_id)
                log.debug(f"Agencia inserida: {map_key} -> {new_id}")
                count += 1

        self.writer.commit()
        self.state.save()
        log.info(f"Fase 02 concluida: {count} agencias inseridas.")
        return count


# ---------------------------------------------------------------------------
# Fase 03: conta -> contas_bancarias (contas do sistema, sem pessoa)
# ---------------------------------------------------------------------------

class Phase03ContasBancarias(BasePhase):
    PHASE_ID = "03"

    def run(self) -> int:
        log.info("=== FASE 03: conta -> contas_bancarias ===")
        count = 0
        for row in self.parser.iter_table("conta"):
            old_codigo = safe_int(row.get("Codigo"))
            banco_old = safe_int(row.get("Banco"))
            agencia_cod = safe_str(row.get("Agencia")) or "0000"
            cod_conta = safe_str(row.get("CodConta")) or ""
            descricao = safe_str(row.get("descconta")) or f"Conta {old_codigo}"
            tipo_conta = safe_int(row.get("tipoconta"))
            registrada = bool(safe_int(row.get("registrada")))
            multipag = bool(safe_int(row.get("multipag")))
            cod_cedente_raw = safe_str(row.get("CodCedente"))
            # codigo_cedente é numeric(10,0) — trunca se > 10 dígitos
            try:
                cod_cedente_val = int(re.sub(r"\D", "", cod_cedente_raw or "0") or "0")
                cod_cedente = cod_cedente_val if cod_cedente_val < 10000000000 else None
            except (ValueError, OverflowError):
                cod_cedente = None

            banco_new_id = self.state.get_new_id("banco", banco_old)
            map_key_ag = f"{banco_old}_{agencia_cod}"
            agencia_new_id = self.state.get_new_id("agencia", map_key_ag)

            # Idempotência
            existing = self.writer.fetch_one(
                "SELECT id FROM contas_bancarias WHERE codigo = %s", (cod_conta or str(old_codigo),)
            )
            if existing:
                self.state.set_mapping("conta", old_codigo, existing[0])
                continue

            sql = """
                INSERT INTO contas_bancarias
                    (codigo, id_banco, id_agencia, descricao, principal, ativo,
                     registrada, aceita_multipag, codigo_cedente,
                     usa_endereco_cobranca, cobranca_compartilhada)
                VALUES (%s, %s, %s, %s, FALSE, TRUE, %s, %s, %s, FALSE, FALSE)
                RETURNING id
            """
            row_ret = self.writer.execute_returning(sql, (
                (cod_conta or str(old_codigo))[:50],
                banco_new_id,
                agencia_new_id,
                descricao[:100],
                registrada,
                multipag,
                cod_cedente,
            ))
            if row_ret:
                new_id = row_ret[0]
                self.state.set_mapping("conta", old_codigo, new_id)
                log.debug(f"Conta {old_codigo} -> {new_id}")
                count += 1

        self.writer.commit()
        self.state.save()
        log.info(f"Fase 03 concluida: {count} contas inseridas.")
        return count


# ---------------------------------------------------------------------------
# Fase 04: locplano -> plano_contas
# ---------------------------------------------------------------------------

class Phase04PlanoContas(BasePhase):
    PHASE_ID = "04"

    def run(self) -> int:
        log.info("=== FASE 04: locplano -> plano_contas ===")
        count = 0
        for row in self.parser.iter_table("locplano"):
            old_codigo = safe_int(row.get("codigo"))
            tipo = safe_int(row.get("tipo"))
            descricao = safe_str(row.get("descricao")) or f"Conta {old_codigo}"
            taxa_admin = bool(safe_int(row.get("taxaadm")))
            incide_ir = bool(safe_int(row.get("ir")))
            entra_informe = bool(safe_int(row.get("informe")))
            entra_desconto = bool(safe_int(row.get("desconto")))
            entra_multa = bool(safe_int(row.get("multa")))
            cod_contabil = safe_str(row.get("codcontabil"))

            # Código: usa o codigo antigo como string
            codigo_novo = str(old_codigo)

            existing = self.writer.fetch_one(
                "SELECT id FROM plano_contas WHERE codigo = %s", (codigo_novo,)
            )
            if existing:
                self.state.set_mapping("locplano", old_codigo, existing[0])
                continue

            sql = """
                INSERT INTO plano_contas
                    (codigo, tipo, descricao, incide_taxa_admin, incide_ir,
                     entra_informe, entra_desconto, entra_multa,
                     codigo_contabil, ativo, created_at, updated_at)
                VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,TRUE,NOW(),NOW())
                RETURNING id
            """
            row_ret = self.writer.execute_returning(sql, (
                codigo_novo, tipo, descricao[:100],
                taxa_admin, incide_ir, entra_informe, entra_desconto, entra_multa,
                cod_contabil[:20] if cod_contabil else None,
            ))
            if row_ret:
                new_id = row_ret[0]
                self.state.set_mapping("locplano", old_codigo, new_id)
                count += 1

        self.writer.commit()
        self.state.save()
        log.info(f"Fase 04 concluida: {count} planos inseridos.")
        return count


# ---------------------------------------------------------------------------
# Fase 05: p_estado -> estados
# ---------------------------------------------------------------------------

class Phase05Estados(BasePhase):
    PHASE_ID = "05"

    # O dump p_estado tem: estado(sigla), nome
    # A tabela nova `estados` provavelmente tem: id, sigla, nome
    # Como o dump tem poucas linhas únicas, usamos a sigla como chave.

    def run(self) -> int:
        log.info("=== FASE 05: p_estado -> estados ===")
        count = 0
        seen_siglas = set()

        for row in self.parser.iter_table("p_estado"):
            sigla = safe_str(row.get("estado") or row.get("Estado") or row.get("uf"))
            nome = safe_str(row.get("nome") or row.get("Nome"))

            if not sigla:
                continue
            sigla = sigla.upper()[:2]
            if sigla in seen_siglas:
                continue
            seen_siglas.add(sigla)

            existing = self.writer.fetch_one(
                "SELECT id FROM estados WHERE uf = %s", (sigla,)
            )
            if existing:
                self.state.set_mapping("estado", sigla, existing[0])
                continue

            row_ret = self.writer.execute_returning(
                "INSERT INTO estados (uf, nome) VALUES (%s, %s) RETURNING id",
                (sigla, nome[:50] if nome else sigla)
            )
            if row_ret:
                self.state.set_mapping("estado", sigla, row_ret[0])
                count += 1

        self.writer.commit()
        self.state.save()
        log.info(f"Fase 05 concluida: {count} estados inseridos.")
        return count


# ---------------------------------------------------------------------------
# Fase 06: p_cidade -> cidades
# ---------------------------------------------------------------------------

class Phase06Cidades(BasePhase):
    PHASE_ID = "06"

    def run(self) -> int:
        log.info("=== FASE 06: p_cidade -> cidades (BATCH) ===")

        # 1. Pre-load all existing estados into cache
        existing_estados: Dict[str, int] = {}
        for row in self.writer.fetch_all("SELECT id, uf FROM estados"):
            existing_estados[row[1].upper()] = row[0]
            self.state.set_mapping("estado", row[1].upper(), row[0])

        # 2. Pre-load existing cidades as set of (nome, id_estado)
        existing_cidades: set = set()
        for row in self.writer.fetch_all("SELECT nome, id_estado FROM cidades"):
            existing_cidades.add((row[0], row[1]))

        # 3. Collect new cities from dump
        seen: set = set()
        new_cities: List[Tuple] = []  # (nome, id_estado, codigo, sigla_for_mapping)
        for row in self.parser.iter_table("p_cidade"):
            sigla = safe_str(row.get("estado") or row.get("Estado"))
            nome_cidade = safe_str(row.get("cidade") or row.get("Cidade"))
            cod_ibge = safe_int(row.get("codcidade") or row.get("CodCidade"), 0) or None

            if not sigla or not nome_cidade:
                continue
            sigla = sigla.upper()[:2]
            key = (sigla, nome_cidade.upper())
            if key in seen:
                continue
            seen.add(key)

            estado_id = existing_estados.get(sigla)
            if not estado_id:
                continue

            nome_trunc = nome_cidade[:100]
            if (nome_trunc, estado_id) in existing_cidades:
                continue

            new_cities.append((nome_trunc, estado_id, str(cod_ibge) if cod_ibge else None, sigla, nome_cidade.upper()))

        log.info(f"Cidades novas a inserir: {len(new_cities)}")

        # 4. Bulk insert in chunks
        count = 0
        CHUNK = 500
        for i in range(0, len(new_cities), CHUNK):
            chunk = new_cities[i:i + CHUNK]
            rows = [(c[0], c[1], c[2]) for c in chunk]
            with self.writer.conn.cursor() as cur:
                psycopg2.extras.execute_values(
                    cur,
                    "INSERT INTO cidades (nome, id_estado, codigo) VALUES %s ON CONFLICT DO NOTHING",
                    rows,
                    template="(%s, %s, %s)"
                )
            self.writer.commit()
            count += len(chunk)
            log.info(f"  ... {count}/{len(new_cities)} cidades inseridas")

        # 5. Re-load all cidades to build id_map (needed by subsequent phases)
        # Build reverse map: estado_id -> sigla
        estado_id_to_uf: Dict[int, str] = {v: k for k, v in existing_estados.items()}
        for row in self.writer.fetch_all("SELECT id, nome, id_estado FROM cidades"):
            cidade_id, cidade_nome, estado_id = row
            uf = estado_id_to_uf.get(estado_id, "SP")
            self.state.set_mapping("cidade", f"{uf}_{cidade_nome.upper()}", cidade_id)

        self.state.save()
        log.info(f"Fase 06 concluida: {count} cidades inseridas.")
        return count


# ---------------------------------------------------------------------------
# Fase 07: p_bairro -> bairros
# ---------------------------------------------------------------------------

class Phase07Bairros(BasePhase):
    PHASE_ID = "07"

    def run(self) -> int:
        log.info("=== FASE 07: p_bairro -> bairros (BATCH) ===")

        # 1. Pre-load existing cidades into local cache: key=(nome_upper, id_estado) -> city_id
        #    Also build cidade_key -> city_id for fast lookups
        cidade_cache: Dict[str, int] = {}
        for row in self.writer.fetch_all("SELECT id, nome, id_estado FROM cidades"):
            city_id, nome, id_estado = row
            # We need to reverse-lookup estado UF; use state mappings built in phase 06
            # Key: UF_NOME_UPPER (same format as set_mapping in phase 06)
            # Store by (id_estado, nome_upper) for lookup from dump rows
            cidade_cache[(id_estado, nome.upper())] = city_id

        # Build estado uf -> id map from state
        estado_uf_to_id: Dict[str, int] = {}
        for row in self.writer.fetch_all("SELECT id, uf FROM estados"):
            estado_uf_to_id[row[1].upper()] = row[0]

        # 2. Pre-load existing bairros as set of (nome_upper, id_cidade)
        existing_bairros: set = set()
        for row in self.writer.fetch_all("SELECT nome, id_cidade FROM bairros"):
            existing_bairros.add((row[0].upper(), row[1]))

        # 3. Collect new bairros from dump
        seen: set = set()
        new_bairros: List[Tuple] = []  # (nome_trunc, id_cidade, sigla, nome_cidade_upper, nome_bairro_upper)
        for row in self.parser.iter_table("p_bairro"):
            sigla = safe_str(row.get("estado"))
            nome_cidade = safe_str(row.get("cidade"))
            nome_bairro = safe_str(row.get("bairro"))

            if not nome_bairro:
                continue

            sigla = (sigla or "SP").upper()[:2]
            nome_cidade_upper = (nome_cidade or "").upper()
            nome_bairro_upper = nome_bairro.upper()

            key = (sigla, nome_cidade_upper, nome_bairro_upper)
            if key in seen:
                continue
            seen.add(key)

            estado_id = estado_uf_to_id.get(sigla)
            if not estado_id:
                continue

            cidade_id = cidade_cache.get((estado_id, nome_cidade_upper))
            if not cidade_id:
                # Try via state mapping
                cidade_id = self.state.get_new_id("cidade", f"{sigla}_{nome_cidade_upper}")
            if not cidade_id:
                continue

            nome_trunc = nome_bairro[:100]
            if (nome_trunc.upper(), cidade_id) in existing_bairros:
                continue

            new_bairros.append((nome_trunc, cidade_id, sigla, nome_cidade_upper, nome_bairro_upper))

        log.info(f"Bairros novos a inserir: {len(new_bairros)}")

        # 4. Bulk insert in chunks
        count = 0
        CHUNK = 1000
        for i in range(0, len(new_bairros), CHUNK):
            chunk = new_bairros[i:i + CHUNK]
            rows = [(c[0], c[1]) for c in chunk]
            with self.writer.conn.cursor() as cur:
                psycopg2.extras.execute_values(
                    cur,
                    "INSERT INTO bairros (nome, id_cidade) VALUES %s ON CONFLICT DO NOTHING",
                    rows,
                    template="(%s, %s)"
                )
            self.writer.commit()
            count += len(chunk)
            log.info(f"  ... {count}/{len(new_bairros)} bairros inseridos")

        # 5. Re-load all bairros to build id_map
        # Need cidade_id -> (sigla, nome_cidade_upper) reverse lookup
        cidade_id_to_key: Dict[int, Tuple[str, str]] = {}
        for row in self.writer.fetch_all("SELECT c.id, c.nome, e.uf FROM cidades c JOIN estados e ON c.id_estado = e.id"):
            cidade_id_to_key[row[0]] = (row[2].upper(), row[1].upper())

        for row in self.writer.fetch_all("SELECT id, nome, id_cidade FROM bairros"):
            bairro_id, bairro_nome, cidade_id = row
            city_info = cidade_id_to_key.get(cidade_id)
            if city_info:
                uf, cidade_nome_upper = city_info
                bairro_key = f"{uf}_{cidade_nome_upper}_{bairro_nome.upper()}"
                self.state.set_mapping("bairro", bairro_key, bairro_id)

        self.state.save()
        log.info(f"Fase 07 concluida: {count} bairros inseridos.")
        return count


# ---------------------------------------------------------------------------
# Fase 08: loclocadores -> pessoas (tipo=locador) + documentos + telefones + emails
# ---------------------------------------------------------------------------

class Phase08Locadores(BasePhase):
    PHASE_ID = "08"
    CHUNK_SIZE = 200

    def run(self) -> int:
        log.info("=== FASE 08: loclocadores -> pessoas (locadores) BATCH ===")
        count = 0

        # Collect all rows first, skipping already migrated
        pending: List[dict] = []
        for row in self.parser.iter_table("loclocadores"):
            old_id = safe_int(row.get("codigo"))
            if self.state.get_new_id("loclocador", old_id):
                log.debug(f"Locador {old_id} ja migrado, pulando.")
                continue
            pending.append(dict(row))

        log.info(f"Locadores a migrar: {len(pending)}")

        for chunk_start in range(0, len(pending), self.CHUNK_SIZE):
            chunk = pending[chunk_start:chunk_start + self.CHUNK_SIZE]

            # --- Step 1: Batch INSERT into pessoas ---
            pessoas_rows = []
            for row in chunk:
                nome = safe_str(row.get("nome")) or "SEM NOME"
                tipo_pessoa_old = safe_int(row.get("tipopessoa"))
                fisica_juridica = cfg.TIPO_PESSOA_MAP.get(tipo_pessoa_old, "fisica")
                nascto = safe_date(row.get("nascto"))
                estadocivil_old = safe_int(row.get("estadocivil"))
                estado_civil_id = cfg.ESTADO_CIVIL_MAP.get(estadocivil_old)
                renda = safe_float(row.get("renda"))
                obs = safe_str(row.get("obs")) or ""
                profissao = safe_str(row.get("profissao"))
                situacao = bool(safe_int(row.get("situacao")) == 0)
                obs_completo = obs
                if profissao:
                    obs_completo = f"Profissao: {profissao}\n" + obs_completo
                pessoas_rows.append((
                    nome[:100],
                    cfg.TIPO_PESSOA_LOCADOR_ID,
                    situacao,
                    fisica_juridica,
                    nascto,
                    estado_civil_id,
                    str(renda) if renda > 0 else None,
                    None,  # nome_pai
                    None,  # nome_mae
                    obs_completo or None,
                ))

            sql_pessoa = """
                INSERT INTO pessoas
                    (nome, dt_cadastro, tipo_pessoa, status, fisica_juridica,
                     data_nascimento, estado_civil_id, renda, nome_pai, nome_mae,
                     observacoes, theme_light)
                VALUES %s
                RETURNING idpessoa
            """
            with self.writer.conn.cursor() as cur:
                result = psycopg2.extras.execute_values(
                    cur,
                    sql_pessoa,
                    pessoas_rows,
                    template="(%s, NOW(), %s, %s, %s, %s, %s, %s, %s, %s, %s, TRUE)",
                    fetch=True,
                )
                new_ids = [r[0] for r in result]

            if not new_ids:
                log.error(f"Fase 08: batch INSERT retornou 0 ids para {len(chunk)} locadores")
                self.writer.rollback()
                continue

            # Map old_id -> new pessoa_id
            for i, row in enumerate(chunk):
                old_id = safe_int(row.get("codigo"))
                if i < len(new_ids):
                    self.state.set_mapping("loclocador", old_id, new_ids[i])

            # --- Step 2: Collect and batch insert documentos, telefones, emails, enderecos ---
            docs_rows: List[tuple] = []
            tels_data: List[tuple] = []   # (pessoa_id, numero)
            emails_data: List[tuple] = [] # (pessoa_id, email)
            addr_data: List[dict] = []    # for _get_or_create_logradouro + _insert_endereco
            contas_rows: List[tuple] = []

            for i, row in enumerate(chunk):
                old_id = safe_int(row.get("codigo"))
                pessoa_id = new_ids[i] if i < len(new_ids) else None
                if not pessoa_id:
                    continue

                cpf = clean_doc(safe_str(row.get("cpf")) or "")
                rg = safe_str(row.get("rg")) or ""
                cnpj = clean_doc(safe_str(row.get("cnpj")) or "")
                if cpf:
                    num = cpf[:30]
                    if num and num not in ("0", "00"):
                        docs_rows.append((pessoa_id, cfg.DEFAULT_TIPO_DOCUMENTO_CPF_ID, num))
                if rg:
                    num = clean_doc(rg)[:30]
                    if num and num not in ("0", "00"):
                        docs_rows.append((pessoa_id, cfg.DEFAULT_TIPO_DOCUMENTO_RG_ID, num))
                if cnpj:
                    num = cnpj[:30]
                    if num and num not in ("0", "00"):
                        docs_rows.append((pessoa_id, cfg.DEFAULT_TIPO_DOCUMENTO_CNPJ_ID, num))

                for tel_field in ("telefone", "celular", "comercial"):
                    tel = safe_str(row.get(tel_field)) or ""
                    tel = re.sub(r"\s+", "", tel)[:25]
                    if tel:
                        tipo_tel = cfg.TELEFONE_FIELD_MAP.get(tel_field, cfg.TIPO_TELEFONE_CELULAR_ID)
                        tels_data.append((pessoa_id, tel, tipo_tel))

                email_raw = safe_str(row.get("email")) or ""
                for em in email_raw.split(";"):
                    em = em.strip()[:100]
                    if em and "@" in em:
                        emails_data.append((pessoa_id, em))

                endereco_str = safe_str(row.get("endereco")) or ""
                if endereco_str:
                    addr_data.append({
                        "pessoa_id": pessoa_id,
                        "endereco": endereco_str,
                        "numero": "",
                        "complemento": safe_str(row.get("complemento")) or "",
                        "bairro": safe_str(row.get("bairro")) or "",
                        "cidade": safe_str(row.get("cidade")) or "",
                        "estado": safe_str(row.get("estado")) or "SP",
                        "cep": safe_str(row.get("cep")) or "",
                    })

                banco_old = safe_int(row.get("banco"))
                agencia_cod = safe_str(row.get("agencia")) or ""
                conta_cod = safe_str(row.get("conta")) or ""
                if conta_cod and banco_old:
                    banco_new_id = self.state.get_new_id("banco", banco_old)
                    agencia_new_id = self.state.get_new_id("agencia", f"{banco_old}_{agencia_cod}")
                    contas_rows.append((pessoa_id, banco_new_id, agencia_new_id, conta_cod[:50]))

            # Batch insert documentos
            if docs_rows:
                with self.writer.conn.cursor() as cur:
                    psycopg2.extras.execute_values(
                        cur,
                        """INSERT INTO pessoas_documentos
                           (id_pessoa, id_tipo_documento, numero_documento, ativo)
                           VALUES %s ON CONFLICT DO NOTHING""",
                        docs_rows,
                        template="(%s, %s, %s, TRUE)"
                    )

            # Batch insert telefones then junctions (com tipo correto por campo)
            if tels_data:
                # tels_data = [(pessoa_id, numero, tipo_id), ...]
                unique_tels = {}
                for _, num, tipo in tels_data:
                    unique_tels[(num, tipo)] = (tipo, num)
                with self.writer.conn.cursor() as cur:
                    psycopg2.extras.execute_values(
                        cur,
                        """INSERT INTO telefones (id_tipo, numero)
                           VALUES %s ON CONFLICT DO NOTHING""",
                        list(unique_tels.values()),
                        template="(%s, %s)"
                    )
                # Build tel numero -> id map
                tel_numeros = list(set(t[1] for t in tels_data))
                tel_id_map: Dict[str, int] = {}
                if tel_numeros:
                    placeholders = ",".join(["%s"] * len(tel_numeros))
                    rows_tel = self.writer.fetch_all(
                        f"SELECT id, numero FROM telefones WHERE numero IN ({placeholders})",
                        tuple(tel_numeros)
                    )
                    tel_id_map = {r[1]: r[0] for r in rows_tel}
                junctions_tel = []
                for pessoa_id, numero, _ in tels_data:
                    tel_id = tel_id_map.get(numero)
                    if tel_id:
                        junctions_tel.append((pessoa_id, tel_id))
                if junctions_tel:
                    with self.writer.conn.cursor() as cur:
                        psycopg2.extras.execute_values(
                            cur,
                            "INSERT INTO pessoas_telefones (id_pessoa, id_telefone) VALUES %s ON CONFLICT DO NOTHING",
                            junctions_tel,
                            template="(%s, %s)"
                        )

            # Batch insert emails then junctions
            if emails_data:
                unique_emails = list({e[1]: e for e in emails_data}.values())
                with self.writer.conn.cursor() as cur:
                    psycopg2.extras.execute_values(
                        cur,
                        "INSERT INTO emails (email, id_tipo) VALUES %s ON CONFLICT DO NOTHING",
                        [(e[1], cfg.DEFAULT_TIPO_EMAIL_ID) for e in unique_emails],
                        template="(%s, %s)"
                    )
                email_addrs = list(set(e[1] for e in emails_data))
                placeholders = ",".join(["%s"] * len(email_addrs))
                rows_em = self.writer.fetch_all(
                    f"SELECT id, email FROM emails WHERE email IN ({placeholders})",
                    tuple(email_addrs)
                )
                email_id_map = {r[1]: r[0] for r in rows_em}
                junctions_em = []
                for pessoa_id, em in emails_data:
                    email_id = email_id_map.get(em)
                    if email_id:
                        junctions_em.append((pessoa_id, email_id))
                if junctions_em:
                    with self.writer.conn.cursor() as cur:
                        psycopg2.extras.execute_values(
                            cur,
                            "INSERT INTO pessoas_emails (id_pessoa, id_email) VALUES %s ON CONFLICT DO NOTHING",
                            junctions_em,
                            template="(%s, %s)"
                        )

            # Endereços (sequential — depends on logradouros lookup)
            for addr in addr_data:
                try:
                    logr_id = self._get_or_create_logradouro(
                        addr["endereco"], addr["numero"], addr["bairro"],
                        addr["cidade"], addr["estado"], addr["cep"]
                    )
                    if logr_id:
                        self._insert_endereco(addr["pessoa_id"], logr_id, "0", addr["complemento"] or None)
                except Exception as e:
                    log.warning(f"Endereco locador pessoa {addr['pessoa_id']}: {e}")

            # Batch insert contas bancarias
            if contas_rows:
                with self.writer.conn.cursor() as cur:
                    psycopg2.extras.execute_values(
                        cur,
                        """INSERT INTO contas_bancarias
                           (id_pessoa, id_banco, id_agencia, codigo, principal,
                            ativo, registrada, aceita_multipag,
                            usa_endereco_cobranca, cobranca_compartilhada)
                           VALUES %s ON CONFLICT DO NOTHING""",
                        contas_rows,
                        template="(%s, %s, %s, %s, TRUE, TRUE, FALSE, FALSE, FALSE, FALSE)"
                    )

            self.writer.commit()
            self.state.save()
            count += len(new_ids)
            log.info(f"  ... {count}/{len(pending)} locadores migrados")

        log.info(f"Fase 08 concluida: {count} locadores migrados.")
        return count


# ---------------------------------------------------------------------------
# Fase 09: locfiadores -> pessoas (tipo=fiador)
# ---------------------------------------------------------------------------

class Phase09Fiadores(BasePhase):
    PHASE_ID = "09"
    CHUNK_SIZE = 200

    def run(self) -> int:
        log.info("=== FASE 09: locfiadores -> pessoas (fiadores) BATCH ===")
        count = 0

        # Collect all rows, skip already migrated
        pending: List[dict] = []
        for row in self.parser.iter_table("locfiadores"):
            old_id = safe_int(row.get("codigo"))
            if self.state.get_new_id("locfiador", old_id):
                continue
            pending.append(dict(row))

        log.info(f"Fiadores a migrar: {len(pending)}")

        for chunk_start in range(0, len(pending), self.CHUNK_SIZE):
            chunk = pending[chunk_start:chunk_start + self.CHUNK_SIZE]

            # Step 1: Batch INSERT pessoas
            pessoas_rows = []
            for row in chunk:
                nome = safe_str(row.get("nome")) or "SEM NOME"
                nascto = safe_date(row.get("dtnasc"))
                estadocivil_old = safe_int(row.get("estadocivil"))
                estado_civil_id = cfg.ESTADO_CIVIL_MAP.get(estadocivil_old)
                renda = safe_float(row.get("renda"))
                pai = safe_str(row.get("paif")) or ""
                mae = safe_str(row.get("maef")) or ""
                obs_parts = []
                if safe_str(row.get("empresaf")):
                    obs_parts.append(f"Empresa: {row.get('empresaf')}")
                if safe_str(row.get("motivofianca")):
                    obs_parts.append(f"Motivo: {row.get('motivofianca')}")
                obs = "\n".join(obs_parts) or None
                pessoas_rows.append((
                    nome[:100],
                    cfg.TIPO_PESSOA_FIADOR_ID,
                    True,
                    "fisica",
                    nascto,
                    estado_civil_id,
                    str(renda) if renda > 0 else None,
                    pai[:100] if pai else None,
                    mae[:100] if mae else None,
                    obs,
                ))

            sql_pessoa = """
                INSERT INTO pessoas
                    (nome, dt_cadastro, tipo_pessoa, status, fisica_juridica,
                     data_nascimento, estado_civil_id, renda, nome_pai, nome_mae,
                     observacoes, theme_light)
                VALUES %s
                RETURNING idpessoa
            """
            with self.writer.conn.cursor() as cur:
                result = psycopg2.extras.execute_values(
                    cur,
                    sql_pessoa,
                    pessoas_rows,
                    template="(%s, NOW(), %s, %s, %s, %s, %s, %s, %s, %s, %s, TRUE)",
                    fetch=True,
                )
                new_ids = [r[0] for r in result]

            for i, row in enumerate(chunk):
                old_id = safe_int(row.get("codigo"))
                if i < len(new_ids):
                    self.state.set_mapping("locfiador", old_id, new_ids[i])

            # Step 2: Collect sub-records
            docs_rows: List[tuple] = []
            tels_data: List[tuple] = []
            emails_data: List[tuple] = []
            addr_data: List[dict] = []

            for i, row in enumerate(chunk):
                pessoa_id = new_ids[i] if i < len(new_ids) else None
                if not pessoa_id:
                    continue

                cpf = clean_doc(safe_str(row.get("cpf")) or "")
                rg = safe_str(row.get("rg")) or ""
                if cpf:
                    num = cpf[:30]
                    if num and num not in ("0", "00"):
                        docs_rows.append((pessoa_id, cfg.DEFAULT_TIPO_DOCUMENTO_CPF_ID, num))
                if rg:
                    num = clean_doc(rg)[:30]
                    if num and num not in ("0", "00"):
                        docs_rows.append((pessoa_id, cfg.DEFAULT_TIPO_DOCUMENTO_RG_ID, num))

                for tel_field in ("telefone",):
                    tel = re.sub(r"\s+", "", safe_str(row.get(tel_field)) or "")[:25]
                    if tel:
                        tipo_tel = cfg.TELEFONE_FIELD_MAP.get(tel_field, cfg.TIPO_TELEFONE_CELULAR_ID)
                        tels_data.append((pessoa_id, tel, tipo_tel))

                em = (safe_str(row.get("email")) or "").strip()[:100]
                if em and "@" in em:
                    emails_data.append((pessoa_id, em))

                endereco_str = safe_str(row.get("endereco")) or ""
                if endereco_str:
                    addr_data.append({
                        "pessoa_id": pessoa_id,
                        "endereco": endereco_str,
                        "complemento": safe_str(row.get("complemento")) or "",
                        "bairro": safe_str(row.get("bairro")) or "",
                        "cidade": safe_str(row.get("cidade")) or "",
                        "estado": safe_str(row.get("estado")) or "SP",
                        "cep": safe_str(row.get("cep")) or "",
                    })

            # Batch docs
            if docs_rows:
                with self.writer.conn.cursor() as cur:
                    psycopg2.extras.execute_values(
                        cur,
                        "INSERT INTO pessoas_documentos (id_pessoa, id_tipo_documento, numero_documento, ativo) VALUES %s ON CONFLICT DO NOTHING",
                        docs_rows,
                        template="(%s, %s, %s, TRUE)"
                    )

            # Batch telefones + junctions (com tipo correto)
            if tels_data:
                unique_tels = {}
                for _, num, tipo in tels_data:
                    unique_tels[(num, tipo)] = (tipo, num)
                with self.writer.conn.cursor() as cur:
                    psycopg2.extras.execute_values(
                        cur,
                        "INSERT INTO telefones (id_tipo, numero) VALUES %s ON CONFLICT DO NOTHING",
                        list(unique_tels.values()),
                        template="(%s, %s)"
                    )
                tel_numeros = list(set(t[1] for t in tels_data))
                placeholders = ",".join(["%s"] * len(tel_numeros))
                rows_tel = self.writer.fetch_all(
                    f"SELECT id, numero FROM telefones WHERE numero IN ({placeholders})",
                    tuple(tel_numeros)
                )
                tel_id_map = {r[1]: r[0] for r in rows_tel}
                junctions_tel = [(p, tel_id_map[n]) for p, n, _ in tels_data if n in tel_id_map]
                if junctions_tel:
                    with self.writer.conn.cursor() as cur:
                        psycopg2.extras.execute_values(
                            cur,
                            "INSERT INTO pessoas_telefones (id_pessoa, id_telefone) VALUES %s ON CONFLICT DO NOTHING",
                            junctions_tel,
                            template="(%s, %s)"
                        )

            # Batch emails + junctions
            if emails_data:
                with self.writer.conn.cursor() as cur:
                    psycopg2.extras.execute_values(
                        cur,
                        "INSERT INTO emails (email, id_tipo) VALUES %s ON CONFLICT DO NOTHING",
                        [(e[1], cfg.DEFAULT_TIPO_EMAIL_ID) for e in emails_data],
                        template="(%s, %s)"
                    )
                email_addrs = list(set(e[1] for e in emails_data))
                placeholders = ",".join(["%s"] * len(email_addrs))
                rows_em = self.writer.fetch_all(
                    f"SELECT id, email FROM emails WHERE email IN ({placeholders})",
                    tuple(email_addrs)
                )
                email_id_map = {r[1]: r[0] for r in rows_em}
                junctions_em = [(p, email_id_map[e]) for p, e in emails_data if e in email_id_map]
                if junctions_em:
                    with self.writer.conn.cursor() as cur:
                        psycopg2.extras.execute_values(
                            cur,
                            "INSERT INTO pessoas_emails (id_pessoa, id_email) VALUES %s ON CONFLICT DO NOTHING",
                            junctions_em,
                            template="(%s, %s)"
                        )

            # Endereços sequencial
            for addr in addr_data:
                try:
                    logr_id = self._get_or_create_logradouro(
                        addr["endereco"], "", addr["bairro"],
                        addr["cidade"], addr["estado"], addr["cep"]
                    )
                    if logr_id:
                        self._insert_endereco(addr["pessoa_id"], logr_id, "0", addr["complemento"] or None)
                except Exception as e:
                    log.warning(f"Endereco fiador pessoa {addr['pessoa_id']}: {e}")

            self.writer.commit()
            self.state.save()
            count += len(new_ids)
            log.info(f"  ... {count}/{len(pending)} fiadores migrados")

        log.info(f"Fase 09 concluida: {count} fiadores migrados.")
        return count


# ---------------------------------------------------------------------------
# Fase 10: loccontratantes -> pessoas (tipo=contratante)
# ---------------------------------------------------------------------------

class Phase10Contratantes(BasePhase):
    PHASE_ID = "10"
    CHUNK_SIZE = 200

    def run(self) -> int:
        log.info("=== FASE 10: loccontratantes -> pessoas (contratantes) BATCH ===")
        count = 0

        # Collect all rows, skip already migrated
        pending: List[dict] = []
        for row in self.parser.iter_table("loccontratantes"):
            old_id = safe_int(row.get("codigo"))
            if self.state.get_new_id("loccontratante", old_id):
                continue
            pending.append(dict(row))

        log.info(f"Contratantes a migrar: {len(pending)}")

        for chunk_start in range(0, len(pending), self.CHUNK_SIZE):
            chunk = pending[chunk_start:chunk_start + self.CHUNK_SIZE]

            # Step 1: Batch INSERT pessoas
            pessoas_rows = []
            for row in chunk:
                nome = safe_str(row.get("nome")) or "SEM NOME"
                nascto = safe_date(row.get("dtnasc"))
                estadocivil_old = safe_int(row.get("estadocivil"))
                estado_civil_id = cfg.ESTADO_CIVIL_MAP.get(estadocivil_old)
                renda = safe_float(row.get("renda"))
                pai = safe_str(row.get("paic")) or ""
                mae = safe_str(row.get("maec")) or ""
                atividade = safe_str(row.get("atividade")) or ""
                obs = f"Atividade: {atividade}" if atividade else None
                pessoas_rows.append((
                    nome[:100],
                    cfg.TIPO_PESSOA_CONTRATANTE_ID,
                    True,
                    "fisica",
                    nascto,
                    estado_civil_id,
                    str(renda) if renda > 0 else None,
                    pai[:100] if pai else None,
                    mae[:100] if mae else None,
                    obs,
                ))

            sql_pessoa = """
                INSERT INTO pessoas
                    (nome, dt_cadastro, tipo_pessoa, status, fisica_juridica,
                     data_nascimento, estado_civil_id, renda, nome_pai, nome_mae,
                     observacoes, theme_light)
                VALUES %s
                RETURNING idpessoa
            """
            with self.writer.conn.cursor() as cur:
                result = psycopg2.extras.execute_values(
                    cur,
                    sql_pessoa,
                    pessoas_rows,
                    template="(%s, NOW(), %s, %s, %s, %s, %s, %s, %s, %s, %s, TRUE)",
                    fetch=True,
                )
                new_ids = [r[0] for r in result]

            for i, row in enumerate(chunk):
                old_id = safe_int(row.get("codigo"))
                if i < len(new_ids):
                    self.state.set_mapping("loccontratante", old_id, new_ids[i])

            # Step 2: Collect sub-records
            docs_rows: List[tuple] = []
            tels_data: List[tuple] = []
            emails_data: List[tuple] = []
            addr_data: List[dict] = []

            for i, row in enumerate(chunk):
                pessoa_id = new_ids[i] if i < len(new_ids) else None
                if not pessoa_id:
                    continue

                cpf = clean_doc(safe_str(row.get("cpf")) or "")
                rg = safe_str(row.get("rg")) or ""
                if cpf:
                    num = cpf[:30]
                    if num and num not in ("0", "00"):
                        docs_rows.append((pessoa_id, cfg.DEFAULT_TIPO_DOCUMENTO_CPF_ID, num))
                if rg:
                    num = clean_doc(rg)[:30]
                    if num and num not in ("0", "00"):
                        docs_rows.append((pessoa_id, cfg.DEFAULT_TIPO_DOCUMENTO_RG_ID, num))

                for tel_field in ("telefone",):
                    tel = re.sub(r"\s+", "", safe_str(row.get(tel_field)) or "")[:25]
                    if tel:
                        tipo_tel = cfg.TELEFONE_FIELD_MAP.get(tel_field, cfg.TIPO_TELEFONE_CELULAR_ID)
                        tels_data.append((pessoa_id, tel, tipo_tel))

                em = (safe_str(row.get("email")) or "").strip()[:100]
                if em and "@" in em:
                    emails_data.append((pessoa_id, em))

                endereco_str = safe_str(row.get("endereco")) or ""
                if endereco_str:
                    addr_data.append({
                        "pessoa_id": pessoa_id,
                        "endereco": endereco_str,
                        "complemento": safe_str(row.get("complemento")) or "",
                        "bairro": safe_str(row.get("bairro")) or "",
                        "cidade": safe_str(row.get("cidade")) or "",
                        "estado": safe_str(row.get("estado")) or "SP",
                        "cep": safe_str(row.get("cep")) or "",
                    })

            if docs_rows:
                with self.writer.conn.cursor() as cur:
                    psycopg2.extras.execute_values(
                        cur,
                        "INSERT INTO pessoas_documentos (id_pessoa, id_tipo_documento, numero_documento, ativo) VALUES %s ON CONFLICT DO NOTHING",
                        docs_rows,
                        template="(%s, %s, %s, TRUE)"
                    )

            # Batch telefones + junctions (com tipo correto)
            if tels_data:
                unique_tels = {}
                for _, num, tipo in tels_data:
                    unique_tels[(num, tipo)] = (tipo, num)
                with self.writer.conn.cursor() as cur:
                    psycopg2.extras.execute_values(
                        cur,
                        "INSERT INTO telefones (id_tipo, numero) VALUES %s ON CONFLICT DO NOTHING",
                        list(unique_tels.values()),
                        template="(%s, %s)"
                    )
                tel_numeros = list(set(t[1] for t in tels_data))
                placeholders = ",".join(["%s"] * len(tel_numeros))
                rows_tel = self.writer.fetch_all(
                    f"SELECT id, numero FROM telefones WHERE numero IN ({placeholders})",
                    tuple(tel_numeros)
                )
                tel_id_map = {r[1]: r[0] for r in rows_tel}
                junctions_tel = [(p, tel_id_map[n]) for p, n, _ in tels_data if n in tel_id_map]
                if junctions_tel:
                    with self.writer.conn.cursor() as cur:
                        psycopg2.extras.execute_values(
                            cur,
                            "INSERT INTO pessoas_telefones (id_pessoa, id_telefone) VALUES %s ON CONFLICT DO NOTHING",
                            junctions_tel,
                            template="(%s, %s)"
                        )

            if emails_data:
                with self.writer.conn.cursor() as cur:
                    psycopg2.extras.execute_values(
                        cur,
                        "INSERT INTO emails (email, id_tipo) VALUES %s ON CONFLICT DO NOTHING",
                        [(e[1], cfg.DEFAULT_TIPO_EMAIL_ID) for e in emails_data],
                        template="(%s, %s)"
                    )
                email_addrs = list(set(e[1] for e in emails_data))
                placeholders = ",".join(["%s"] * len(email_addrs))
                rows_em = self.writer.fetch_all(
                    f"SELECT id, email FROM emails WHERE email IN ({placeholders})",
                    tuple(email_addrs)
                )
                email_id_map = {r[1]: r[0] for r in rows_em}
                junctions_em = [(p, email_id_map[e]) for p, e in emails_data if e in email_id_map]
                if junctions_em:
                    with self.writer.conn.cursor() as cur:
                        psycopg2.extras.execute_values(
                            cur,
                            "INSERT INTO pessoas_emails (id_pessoa, id_email) VALUES %s ON CONFLICT DO NOTHING",
                            junctions_em,
                            template="(%s, %s)"
                        )

            for addr in addr_data:
                try:
                    logr_id = self._get_or_create_logradouro(
                        addr["endereco"], "", addr["bairro"],
                        addr["cidade"], addr["estado"], addr["cep"]
                    )
                    if logr_id:
                        self._insert_endereco(addr["pessoa_id"], logr_id, "0", addr["complemento"] or None)
                except Exception as e:
                    log.warning(f"Endereco contratante pessoa {addr['pessoa_id']}: {e}")

            self.writer.commit()
            self.state.save()
            count += len(new_ids)
            log.info(f"  ... {count}/{len(pending)} contratantes migrados")

        log.info(f"Fase 10 concluida: {count} contratantes migrados.")
        return count


# ---------------------------------------------------------------------------
# Fase 11: locimoveis -> imoveis
# ---------------------------------------------------------------------------

class Phase11Imoveis(BasePhase):
    PHASE_ID = "11"
    CHUNK_SIZE = 100

    def run(self) -> int:
        log.info("=== FASE 11: locimoveis -> imoveis (BATCH) ===")
        count = 0

        # Placeholder proprietário (será corrigido na Fase 12)
        placeholder_proprietario = self.writer.fetch_one(
            "SELECT idpessoa FROM pessoas WHERE tipo_pessoa = %s LIMIT 1",
            (cfg.TIPO_PESSOA_LOCADOR_ID,)
        )
        placeholder_prop_id = placeholder_proprietario[0] if placeholder_proprietario else None

        # Tipo de imóvel padrão
        tipo_imovel_row = self.writer.fetch_one("SELECT id FROM tipos_imoveis LIMIT 1")
        tipo_imovel_id = tipo_imovel_row[0] if tipo_imovel_row else cfg.DEFAULT_TIPO_IMOVEL_ID

        # Pre-load valid tipos_imoveis IDs
        valid_tipos_imoveis = set(
            r[0] for r in self.writer.fetch_all("SELECT id FROM tipos_imoveis")
        )

        # Collect pending rows
        pending: List[dict] = []
        for row in self.parser.iter_table("locimoveis"):
            old_id = safe_int(row.get("codigo"))
            if self.state.get_new_id("locimovel", old_id):
                continue
            pending.append(dict(row))

        log.info(f"Imoveis a migrar: {len(pending)}")

        for chunk_start in range(0, len(pending), self.CHUNK_SIZE):
            chunk = pending[chunk_start:chunk_start + self.CHUNK_SIZE]

            # For each imovel: create logradouro+endereco first (sequential, depends on geography),
            # then batch-insert imoveis using execute_values with RETURNING
            imovel_params: List[tuple] = []
            imovel_old_ids: List[int] = []

            for row in chunk:
                old_id = safe_int(row.get("codigo"))
                nome_antigo = f"IM{old_id:04d}"

                situacao_old = safe_int(row.get("situacao"))
                situacao = cfg.SITUACAO_IMOVEL_MAP.get(situacao_old, "disponivel")
                utilizacao = safe_int(row.get("utilizacao"))
                tipo_util = {0: "residencial", 1: "comercial", 2: "misto"}.get(utilizacao, "residencial")

                disponivel_aluguel = bool(safe_int(row.get("aluguel")))
                disponivel_venda = bool(safe_int(row.get("venda")))
                disponivel_temporada = bool(safe_int(row.get("temporada")))
                aluguel_garantido = bool(safe_int(row.get("garantido")))

                area_total = safe_float(row.get("areatotal")) or None
                area_construida = safe_float(row.get("areaconstruida")) or None
                area_privativa = safe_float(row.get("areaprivativa")) or None
                qtd_quartos = safe_int(row.get("quarto"))
                qtd_suites = safe_int(row.get("suite"))
                qtd_banheiros = safe_int(row.get("banheiro"))
                qtd_salas = safe_int(row.get("sala"))
                qtd_garagem = safe_int(row.get("garagem"))
                qtd_pavimentos = safe_int(row.get("pavimentos")) or 1

                valor_aluguel = safe_float(row.get("valor")) or None
                valor_venda = safe_float(row.get("vrvenda")) or None
                valor_condominio = safe_float(row.get("condominio")) or None
                valor_iptu = safe_float(row.get("iptu")) or None
                valor_taxa_lixo = safe_float(row.get("taxalixo")) or None
                valor_mercado = safe_float(row.get("valormercado")) or None
                taxa_adm = safe_float(row.get("taxaadm")) or None
                taxa_minima = safe_float(row.get("taxaminima")) or None
                dia_vencimento = safe_int(row.get("dia")) or None

                inscr_imob = safe_str(row.get("inscrimob")) or None
                matricula = safe_str(row.get("matricula")) or None
                cartorio = safe_str(row.get("cartorio")) or None
                contribuinte = safe_str(row.get("contribuinte")) or None
                data_fundacao = safe_date(row.get("fundacao"))
                data_cadastro = safe_date(row.get("cadastro"))

                obs = safe_str(row.get("obs")) or None
                imediacoes = safe_str(row.get("imediacoes")) or None
                descricao_parts = [d for d in [
                    safe_str(row.get("desc1")),
                    safe_str(row.get("desc2")),
                    safe_str(row.get("desc3")),
                ] if d]
                descricao = "\n".join(descricao_parts) or None

                tem_chaves = bool(safe_int(row.get("chaves") or 0))
                endereco_str = safe_str(row.get("endereco")) or ""
                numero_str = safe_str(row.get("numero")) or "0"
                complemento = safe_str(row.get("complemento")) or ""
                bairro_str = safe_str(row.get("bairro")) or ""
                cidade_str = safe_str(row.get("cidade")) or ""
                estado_str = safe_str(row.get("estado")) or "SP"
                cep_str = safe_str(row.get("cep")) or ""

                tpimovel_old = safe_int(row.get("tpimovel"))
                id_tipo_imovel = tpimovel_old if tpimovel_old in valid_tipos_imoveis else tipo_imovel_id

                try:
                    # SAVEPOINT para isolar erros individuais sem abortar a transação inteira
                    self.writer.execute(f"SAVEPOINT sp_imovel_{old_id}")

                    logr_id = self._get_or_create_logradouro(
                        endereco_str, numero_str, bairro_str, cidade_str, estado_str, cep_str
                    )
                    if not logr_id:
                        self.writer.execute(f"ROLLBACK TO SAVEPOINT sp_imovel_{old_id}")
                        log.warning(f"Imovel {old_id}: nao foi possivel criar logradouro, pulando.")
                        continue

                    num_int = 0
                    try:
                        num_int = int(re.sub(r"\D", "", numero_str or "0") or "0")
                    except ValueError:
                        num_int = 0

                    end_row = self.writer.execute_returning(
                        "INSERT INTO enderecos (id_pessoa, id_logradouro, id_tipo, end_numero, complemento) VALUES (%s, %s, %s, %s, %s) RETURNING id",
                        (placeholder_prop_id, logr_id, cfg.DEFAULT_TIPO_ENDERECO_ID, num_int, complemento[:100] if complemento else None)
                    )
                    if not end_row:
                        self.writer.execute(f"ROLLBACK TO SAVEPOINT sp_imovel_{old_id}")
                        log.warning(f"Imovel {old_id}: falha ao criar endereco.")
                        continue

                    # Release savepoint on success
                    self.writer.execute(f"RELEASE SAVEPOINT sp_imovel_{old_id}")
                    endereco_id = end_row[0]

                    imovel_old_ids.append(old_id)
                    imovel_params.append((
                        nome_antigo, id_tipo_imovel, endereco_id,
                        placeholder_prop_id,
                        situacao, tipo_util, None,
                        aluguel_garantido, disponivel_aluguel, disponivel_venda, disponivel_temporada,
                        safe_money(area_total),
                        safe_money(area_construida),
                        safe_money(area_privativa),
                        qtd_quartos, qtd_suites, qtd_banheiros, qtd_salas, qtd_garagem,
                        qtd_pavimentos,
                        safe_money(valor_aluguel),
                        safe_money(valor_venda),
                        None,  # temporada
                        safe_money(valor_condominio),
                        safe_money(valor_iptu),
                        safe_money(valor_taxa_lixo),
                        safe_money(valor_mercado),
                        safe_money(taxa_adm, max_val=999.99),
                        safe_money(taxa_minima),
                        dia_vencimento,
                        inscr_imob[:50] if inscr_imob else None,
                        matricula[:30] if matricula else None,
                        cartorio[:100] if cartorio else None,
                        contribuinte[:100] if contribuinte else None,
                        data_fundacao,
                        descricao,
                        obs,
                        imediacoes,
                        tem_chaves, 0,
                        None, None, None,
                        True,
                        False, False, False, False,
                        False,
                        data_cadastro or datetime.now().date(),
                        datetime.now(),
                    ))

                except Exception as e:
                    log.error(f"Imovel {old_id}: ERRO preparando — {e}")
                    log.debug(traceback.format_exc())
                    try:
                        self.writer.execute(f"ROLLBACK TO SAVEPOINT sp_imovel_{old_id}")
                    except Exception:
                        pass
                    if cfg.STOP_ON_ERROR:
                        raise

            # Batch INSERT imoveis with RETURNING
            if imovel_params:
                sql_imovel = """
                    INSERT INTO imoveis (
                        codigo_interno, id_tipo_imovel, id_endereco,
                        id_pessoa_proprietario,
                        situacao, tipo_utilizacao, ocupacao,
                        aluguel_garantido, disponivel_aluguel, disponivel_venda, disponivel_temporada,
                        area_total, area_construida, area_privativa,
                        qtd_quartos, qtd_suites, qtd_banheiros, qtd_salas, qtd_vagas_garagem,
                        qtd_pavimentos,
                        valor_aluguel, valor_venda, valor_temporada,
                        valor_condominio, valor_iptu_mensal, valor_taxa_lixo, valor_mercado,
                        taxa_administracao, taxa_minima, dia_vencimento,
                        inscricao_imobiliaria, matricula_cartorio, nome_cartorio,
                        nome_contribuinte_iptu, data_fundacao,
                        descricao, observacoes, descricao_imediacoes,
                        tem_chaves, qtd_chaves,
                        numero_chave, localizacao_chaves, numero_controle_remoto,
                        publicar_site,
                        publicar_zap, publicar_vivareal, publicar_gruposp, ocultar_valor_site,
                        tem_placa,
                        data_cadastro, updated_at
                    ) VALUES %s RETURNING id
                """
                with self.writer.conn.cursor() as cur:
                    result = psycopg2.extras.execute_values(
                        cur,
                        sql_imovel,
                        imovel_params,
                        template="(" + ",".join(["%s"] * 51) + ")",
                        fetch=True,
                    )
                    new_ids = [r[0] for r in result]

                for i, old_id in enumerate(imovel_old_ids):
                    if i < len(new_ids):
                        self.state.set_mapping("locimovel", old_id, new_ids[i])
                        count += 1

            self.writer.commit()
            self.state.save()
            log.info(f"  ... {count}/{len(pending)} imoveis migrados")

        log.info(f"Fase 11 concluida: {count} imoveis migrados.")
        return count


# ---------------------------------------------------------------------------
# Fase 12: locimovelprop -> atualiza imoveis.id_pessoa_proprietario
#          e insere em imoveis_propriedades (se tabela suportar N:N com percentual)
# ---------------------------------------------------------------------------

class Phase12ImovelProprietario(BasePhase):
    PHASE_ID = "12"

    def run(self) -> int:
        log.info("=== FASE 12: locimovelprop -> vinculo imovel-proprietario (BATCH UPDATE) ===")
        count = 0
        # locimovelprop: imovel, proprietario (old loclocador.codigo), percentual

        # Collect all updates first — last write wins for each imovel
        updates: Dict[int, int] = {}  # imovel_new_id -> prop_new_id
        for row in self.parser.iter_table("locimovelprop"):
            imovel_old = safe_int(row.get("imovel"))
            prop_old = safe_int(row.get("proprietario"))

            imovel_new_id = self.state.get_new_id("locimovel", imovel_old)
            prop_new_id = self.state.get_new_id("loclocador", prop_old)

            if not imovel_new_id:
                log.debug(f"locimovelprop: imovel {imovel_old} nao mapeado.")
                continue
            if not prop_new_id:
                log.debug(f"locimovelprop: proprietario {prop_old} nao mapeado.")
                continue

            updates[imovel_new_id] = prop_new_id

        log.info(f"locimovelprop: {len(updates)} vinculos a atualizar.")

        # Batch UPDATE using execute_values with a temp VALUES list + UPDATE FROM
        update_pairs = list(updates.items())  # [(imovel_id, prop_id), ...]
        CHUNK = 500
        for i in range(0, len(update_pairs), CHUNK):
            chunk = update_pairs[i:i + CHUNK]
            try:
                with self.writer.conn.cursor() as cur:
                    psycopg2.extras.execute_values(
                        cur,
                        """UPDATE imoveis SET id_pessoa_proprietario = v.prop_id
                           FROM (VALUES %s) AS v(imovel_id, prop_id)
                           WHERE imoveis.id = v.imovel_id""",
                        chunk,
                        template="(%s::bigint, %s::bigint)"
                    )
                self.writer.commit()
                count += len(chunk)
                log.info(f"  ... {count}/{len(update_pairs)} vinculos atualizados")
            except Exception as e:
                log.error(f"Batch UPDATE locimovelprop chunk {i}: ERRO — {e}")
                if cfg.STOP_ON_ERROR:
                    raise
                self.writer.rollback()

        # Corrige enderecos.id_pessoa dos imóveis (Phase 11 usou placeholder)
        log.info("Fase 12: corrigindo enderecos.id_pessoa dos imoveis...")
        fix_count = 0
        try:
            with self.writer.conn.cursor() as cur:
                cur.execute("""
                    UPDATE enderecos e
                    SET id_pessoa = i.id_pessoa_proprietario
                    FROM imoveis i
                    WHERE i.id_endereco = e.id
                      AND e.id_pessoa != i.id_pessoa_proprietario
                """)
                fix_count = cur.rowcount
            self.writer.commit()
            log.info(f"  {fix_count} enderecos.id_pessoa corrigidos para proprietario real.")
        except Exception as ex:
            log.warning(f"Erro ao corrigir enderecos.id_pessoa: {ex}")
            self.writer.rollback()

        self.state.save()
        log.info(f"Fase 12 concluida: {count} vinculos proprietario atualizados, {fix_count} enderecos corrigidos.")
        return count


# ---------------------------------------------------------------------------
# Fase 13: locinquilino -> pessoas (tipo=inquilino) + imoveis_contratos
# ---------------------------------------------------------------------------

class Phase13Inquilinos(BasePhase):
    PHASE_ID = "13"
    CHUNK_SIZE = 200

    def run(self) -> int:
        log.info("=== FASE 13: locinquilino -> pessoas (inquilinos) + contratos (BATCH) ===")
        count_pessoas = 0
        count_contratos = 0

        # Collect all rows, skip already migrated
        pending: List[dict] = []
        for row in self.parser.iter_table("locinquilino"):
            old_id = safe_int(row.get("codigo"))
            if self.state.get_new_id("locinquilino", old_id):
                continue
            pending.append(dict(row))

        log.info(f"Inquilinos a migrar: {len(pending)}")

        for chunk_start in range(0, len(pending), self.CHUNK_SIZE):
            chunk = pending[chunk_start:chunk_start + self.CHUNK_SIZE]

            # Step 1: Batch INSERT pessoas
            pessoas_rows = []
            for row in chunk:
                nome = safe_str(row.get("nome")) or "SEM NOME"
                nascto = safe_date(row.get("nascto"))
                estadocivil_old = safe_int(row.get("estcivil"))
                estado_civil_id = cfg.ESTADO_CIVIL_MAP.get(estadocivil_old)
                profissao = safe_str(row.get("profissao")) or ""
                obs = safe_str(row.get("obs")) or ""
                pessoa_old = safe_int(row.get("pessoa"))
                fisica_juridica = "juridica" if pessoa_old == 1 else "fisica"
                situacao_inq = safe_int(row.get("situacao"))
                obs_full = obs
                if profissao:
                    obs_full = f"Profissao: {profissao}\n" + obs_full
                pessoas_rows.append((
                    nome[:100],
                    cfg.TIPO_PESSOA_INQUILINO_ID,
                    (situacao_inq == 0),
                    fisica_juridica,
                    nascto,
                    estado_civil_id,
                    None,  # renda
                    None,  # nome_pai
                    None,  # nome_mae
                    obs_full or None,
                ))

            sql_pessoa = """
                INSERT INTO pessoas
                    (nome, dt_cadastro, tipo_pessoa, status, fisica_juridica,
                     data_nascimento, estado_civil_id, renda, nome_pai, nome_mae,
                     observacoes, theme_light)
                VALUES %s
                RETURNING idpessoa
            """
            with self.writer.conn.cursor() as cur:
                result = psycopg2.extras.execute_values(
                    cur,
                    sql_pessoa,
                    pessoas_rows,
                    template="(%s, NOW(), %s, %s, %s, %s, %s, %s, %s, %s, %s, TRUE)",
                    fetch=True,
                )
                new_ids = [r[0] for r in result]

            for i, row in enumerate(chunk):
                old_id = safe_int(row.get("codigo"))
                if i < len(new_ids):
                    self.state.set_mapping("locinquilino", old_id, new_ids[i])
                    count_pessoas += 1

            # Step 2: Collect sub-records
            docs_rows: List[tuple] = []
            tels_data: List[tuple] = []
            emails_data: List[tuple] = []
            contratos_params: List[tuple] = []
            contratos_old_ids: List[int] = []

            for i, row in enumerate(chunk):
                old_id = safe_int(row.get("codigo"))
                pessoa_id = new_ids[i] if i < len(new_ids) else None
                if not pessoa_id:
                    continue

                cpf = clean_doc(safe_str(row.get("cpf")) or "")
                rg = safe_str(row.get("rg")) or ""
                if cpf:
                    num = cpf[:30]
                    if num and num not in ("0", "00"):
                        docs_rows.append((pessoa_id, cfg.DEFAULT_TIPO_DOCUMENTO_CPF_ID, num))
                if rg:
                    num = clean_doc(rg)[:30]
                    if num and num not in ("0", "00"):
                        docs_rows.append((pessoa_id, cfg.DEFAULT_TIPO_DOCUMENTO_RG_ID, num))

                for tel_field in ("tel", "celular"):
                    tel = re.sub(r"\s+", "", safe_str(row.get(tel_field)) or "")[:25]
                    if tel:
                        tipo_tel = cfg.TELEFONE_FIELD_MAP.get(tel_field, cfg.TIPO_TELEFONE_CELULAR_ID)
                        tels_data.append((pessoa_id, tel, tipo_tel))

                em = (safe_str(row.get("email")) or "").strip()[:100]
                if em and "@" in em:
                    emails_data.append((pessoa_id, em))

                # Contrato
                imovel_old = safe_int(row.get("imovel"))
                ini_contrato = safe_date(row.get("inicontrato"))
                fim_contrato = safe_date(row.get("fimcontrato"))
                indice_old = safe_int(row.get("indice"))
                indice_reajuste = cfg.INDICE_REAJUSTE_MAP.get(indice_old, "IGPM")
                aluguel = safe_float(row.get("aluguel"))
                dia_vencimento = safe_int(row.get("dia"))
                situacao_inq = safe_int(row.get("situacao"))
                fiador_old = safe_int(row.get("fiador") or 0)

                imovel_new_id = self.state.get_new_id("locimovel", imovel_old)
                if imovel_new_id and ini_contrato:
                    fiador_new_id = self.state.get_new_id("locfiador", fiador_old) if fiador_old else None

                    if situacao_inq == 0 and (not fim_contrato or fim_contrato >= date.today()):
                        status_contrato = "ativo"
                    else:
                        status_contrato = "encerrado"

                    tipo_garantia = "fiador"
                    caucao_val = safe_float(row.get("valorcaucao") or 0)
                    if safe_int(row.get("caucao")):
                        tipo_garantia = "caucao"
                    elif safe_int(row.get("segurofianca")):
                        tipo_garantia = "seguro_fianca"

                    contratos_old_ids.append(old_id)
                    contratos_params.append((
                        imovel_new_id, pessoa_id, fiador_new_id,
                        ini_contrato, fim_contrato,
                        str(aluguel) if aluguel else "0.00",
                        dia_vencimento or None,
                        status_contrato,
                        None,  # taxa_administracao
                        tipo_garantia,
                        str(caucao_val) if caucao_val else "0.00",
                        indice_reajuste,
                        status_contrato == "ativo",
                    ))

            # Batch docs
            if docs_rows:
                with self.writer.conn.cursor() as cur:
                    psycopg2.extras.execute_values(
                        cur,
                        "INSERT INTO pessoas_documentos (id_pessoa, id_tipo_documento, numero_documento, ativo) VALUES %s ON CONFLICT DO NOTHING",
                        docs_rows,
                        template="(%s, %s, %s, TRUE)"
                    )

            # Batch telefones + junctions (com tipo correto)
            if tels_data:
                unique_tels = {}
                for _, num, tipo in tels_data:
                    unique_tels[(num, tipo)] = (tipo, num)
                with self.writer.conn.cursor() as cur:
                    psycopg2.extras.execute_values(
                        cur,
                        "INSERT INTO telefones (id_tipo, numero) VALUES %s ON CONFLICT DO NOTHING",
                        list(unique_tels.values()),
                        template="(%s, %s)"
                    )
                tel_numeros = list(set(t[1] for t in tels_data))
                placeholders = ",".join(["%s"] * len(tel_numeros))
                rows_tel = self.writer.fetch_all(
                    f"SELECT id, numero FROM telefones WHERE numero IN ({placeholders})",
                    tuple(tel_numeros)
                )
                tel_id_map = {r[1]: r[0] for r in rows_tel}
                junctions_tel = [(p, tel_id_map[n]) for p, n, _ in tels_data if n in tel_id_map]
                if junctions_tel:
                    with self.writer.conn.cursor() as cur:
                        psycopg2.extras.execute_values(
                            cur,
                            "INSERT INTO pessoas_telefones (id_pessoa, id_telefone) VALUES %s ON CONFLICT DO NOTHING",
                            junctions_tel,
                            template="(%s, %s)"
                        )

            # Batch emails + junctions
            if emails_data:
                with self.writer.conn.cursor() as cur:
                    psycopg2.extras.execute_values(
                        cur,
                        "INSERT INTO emails (email, id_tipo) VALUES %s ON CONFLICT DO NOTHING",
                        [(e[1], cfg.DEFAULT_TIPO_EMAIL_ID) for e in emails_data],
                        template="(%s, %s)"
                    )
                email_addrs = list(set(e[1] for e in emails_data))
                placeholders = ",".join(["%s"] * len(email_addrs))
                rows_em = self.writer.fetch_all(
                    f"SELECT id, email FROM emails WHERE email IN ({placeholders})",
                    tuple(email_addrs)
                )
                email_id_map = {r[1]: r[0] for r in rows_em}
                junctions_em = [(p, email_id_map[e]) for p, e in emails_data if e in email_id_map]
                if junctions_em:
                    with self.writer.conn.cursor() as cur:
                        psycopg2.extras.execute_values(
                            cur,
                            "INSERT INTO pessoas_emails (id_pessoa, id_email) VALUES %s ON CONFLICT DO NOTHING",
                            junctions_em,
                            template="(%s, %s)"
                        )

            # Batch INSERT contratos with RETURNING
            if contratos_params:
                sql_contrato = """
                    INSERT INTO imoveis_contratos (
                        id_imovel, id_pessoa_locatario, id_pessoa_fiador,
                        tipo_contrato, data_inicio, data_fim,
                        valor_contrato, dia_vencimento, status,
                        taxa_administracao, tipo_garantia, valor_caucao,
                        indice_reajuste, periodicidade_reajuste,
                        ativo, gera_boleto, envia_email,
                        dias_antecedencia_boleto,
                        created_at, updated_at
                    ) VALUES %s RETURNING id
                """
                with self.writer.conn.cursor() as cur:
                    result_c = psycopg2.extras.execute_values(
                        cur,
                        sql_contrato,
                        contratos_params,
                        template="(%s,%s,%s,'locacao',%s,%s,%s,%s,%s,%s,%s,%s,%s,'anual',%s,TRUE,TRUE,5,NOW(),NOW())",
                        fetch=True,
                    )
                    cont_ids = [r[0] for r in result_c]

                for i, old_id in enumerate(contratos_old_ids):
                    if i < len(cont_ids):
                        self.state.set_mapping("contrato_inq", old_id, cont_ids[i])
                        count_contratos += 1

            self.writer.commit()
            self.state.save()
            log.info(f"  ... {count_pessoas}/{len(pending)} inquilinos, {count_contratos} contratos")

        log.info(f"Fase 13 concluida: {count_pessoas} inquilinos, {count_contratos} contratos.")
        return count_pessoas


# ---------------------------------------------------------------------------
# Fase 14: locrecibo -> lancamentos_financeiros (recibos/boletos mensais)
# ---------------------------------------------------------------------------

class Phase14Recibos(BasePhase):
    PHASE_ID = "14"
    CHUNK_SIZE = 1000

    def run(self) -> int:
        log.info("=== FASE 14: locrecibo -> lancamentos_financeiros (BATCH execute_values) ===")
        count = 0

        # Pre-load contrato -> imovel mapping to avoid per-row DB queries
        contrato_to_imovel: Dict[int, int] = {}
        for row in self.writer.fetch_all("SELECT id, id_imovel FROM imoveis_contratos"):
            contrato_to_imovel[row[0]] = row[1]

        sql_insert = """
            INSERT INTO lancamentos_financeiros (
                id_contrato, id_imovel, id_inquilino,
                numero_recibo, numero_boleto,
                competencia, data_lancamento, data_vencimento, data_limite,
                valor_principal, valor_multa, valor_juros, valor_total, valor_pago,
                situacao, tipo_lancamento, origem,
                historico, ativo,
                created_at, updated_at
            ) VALUES %s RETURNING id
        """

        batch_rows: List[tuple] = []
        batch_old_ids: List[int] = []

        def flush(rows: List[tuple], old_ids: List[int]) -> int:
            if not rows:
                return 0
            try:
                with self.writer.conn.cursor() as cur:
                    result = psycopg2.extras.execute_values(
                        cur,
                        sql_insert,
                        rows,
                        template="(%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,'aluguel','migracao_mysql',%s,TRUE,NOW(),NOW())",
                        fetch=True,
                    )
                    new_ids = [r[0] for r in result]
                for i, old_id in enumerate(old_ids):
                    if i < len(new_ids):
                        self.state.set_mapping("locrecibo", old_id, new_ids[i])
                self.writer.commit()
                self.state.save()
                return len(new_ids)
            except Exception as e:
                log.error(f"Batch recibos ERRO — {e}")
                if cfg.STOP_ON_ERROR:
                    raise
                self.writer.rollback()
                return 0

        for row in self.parser.iter_table("locrecibo"):
            old_recibo = safe_int(row.get("recibo"))

            if self.state.get_new_id("locrecibo", old_recibo):
                continue

            inq_old = safe_int(row.get("inquilino"))
            inquilino_new_id = self.state.get_new_id("locinquilino", inq_old)
            contrato_new_id = self.state.get_new_id("contrato_inq", inq_old)
            imovel_new_id = contrato_to_imovel.get(contrato_new_id) if contrato_new_id else None

            competencia_str = safe_str(row.get("competencia")) or ""
            competencia = parse_competencia(competencia_str) or date.today().replace(day=1)
            vencto = safe_date(row.get("vencto")) or date.today()
            limite = safe_date(row.get("limite"))
            valor = safe_float(row.get("valor"))
            valor_pago = safe_float(row.get("valorpago"))
            multa = safe_float(row.get("multa"))
            juros = safe_float(row.get("irate"))
            situacao_old = safe_int(row.get("situacao"))
            situacao = cfg.SITUACAO_LANCAMENTO_MAP.get(situacao_old, "aberto")
            num_bancario = safe_str(row.get("nrobancario")) or ""
            valor_total = valor + multa + juros

            batch_old_ids.append(old_recibo)
            batch_rows.append((
                contrato_new_id,
                imovel_new_id,
                inquilino_new_id,
                str(old_recibo),
                num_bancario[:50] if num_bancario else None,
                competencia,
                competencia,
                vencto,
                limite,
                str(round(valor, 2)),
                str(round(multa, 2)),
                str(round(juros, 2)),
                str(round(valor_total, 2)),
                str(round(valor_pago, 2)),
                situacao,
                f"Migrado do sistema antigo. Recibo={old_recibo}",
            ))

            if len(batch_rows) >= self.CHUNK_SIZE:
                n = flush(batch_rows, batch_old_ids)
                count += n
                batch_rows.clear()
                batch_old_ids.clear()
                log.info(f"  ... {count} recibos migrados")

        count += flush(batch_rows, batch_old_ids)
        log.info(f"Fase 14 concluida: {count} recibos migrados.")
        return count


# ---------------------------------------------------------------------------
# Fase 15: locrechist -> atualiza verbas nos lancamentos_financeiros
# ---------------------------------------------------------------------------

class Phase15RecibosHistorico(BasePhase):
    PHASE_ID = "15"

    # locrechist: recibo, conta (old locplano.codigo), valor, sinal
    # Mapeia verbas para campos de lancamentos_financeiros
    VERBA_MAP = {
        1001: "valor_principal",
        1006: "valor_condominio",
        1007: "valor_iptu",
        1008: "valor_agua",
        1009: "valor_luz",
        1029: "valor_gas",
        5021: "valor_outros",
    }
    CHUNK_SIZE = 500

    def run(self) -> int:
        log.info("=== FASE 15: locrechist -> verbas em lancamentos_financeiros (BATCH UPDATE) ===")
        count = 0

        # Step 1: Accumulate all verba totals in memory (216k rows → fast)
        updates: Dict[int, Dict[str, float]] = {}
        for row in self.parser.iter_table("locrechist"):
            recibo_old = safe_int(row.get("recibo"))
            conta_old = safe_int(row.get("conta"))
            valor = safe_float(row.get("valor"))

            lancamento_id = self.state.get_new_id("locrecibo", recibo_old)
            if not lancamento_id:
                continue

            campo = self.VERBA_MAP.get(conta_old, "valor_outros")
            if lancamento_id not in updates:
                updates[lancamento_id] = {}
            updates[lancamento_id][campo] = updates[lancamento_id].get(campo, 0.0) + valor

        log.info(f"locrechist: {len(updates)} lancamentos a atualizar com verbas.")

        # Step 2: Group by campo combination to reduce number of distinct UPDATE statements.
        # For each unique set of campos, batch-update using execute_values with UPDATE FROM VALUES.
        # Group updates by frozenset of campo names → list of (lancamento_id, *values)
        campo_groups: Dict[tuple, List[tuple]] = {}
        for lancamento_id, verbas in updates.items():
            key = tuple(sorted(verbas.keys()))
            if key not in campo_groups:
                campo_groups[key] = []
            row_vals = tuple(str(round(verbas[c], 2)) for c in key) + (lancamento_id,)
            campo_groups[key].append(row_vals)

        log.info(f"locrechist: {len(campo_groups)} padroes de colunas distintos.")

        for campos, rows in campo_groups.items():
            # Build UPDATE ... FROM (VALUES ...) AS v(col1,...,id) WHERE lf.id = v.id
            col_count = len(campos)
            v_cols = [f"v{i}" for i in range(col_count)] + ["lid"]
            set_clause = ", ".join(f"{c} = v.{v_cols[i]}" for i, c in enumerate(campos))
            alias_list = ", ".join(v_cols)
            sql = f"""
                UPDATE lancamentos_financeiros lf
                SET {set_clause}
                FROM (VALUES %s) AS v({alias_list})
                WHERE lf.id = v.lid::bigint
            """
            # Build type template: numeric strings + bigint id
            placeholders = "(" + ",".join(["%s::numeric"] * col_count + ["%s::bigint"]) + ")"

            for i in range(0, len(rows), self.CHUNK_SIZE):
                chunk = rows[i:i + self.CHUNK_SIZE]
                try:
                    with self.writer.conn.cursor() as cur:
                        psycopg2.extras.execute_values(cur, sql, chunk, template=placeholders)
                    self.writer.commit()
                    count += len(chunk)
                    if count % 5000 == 0:
                        log.info(f"  ... {count} lancamentos com verbas atualizados")
                except Exception as e:
                    log.error(f"locrechist batch UPDATE ({campos}): ERRO — {e}")
                    if cfg.STOP_ON_ERROR:
                        raise
                    self.writer.rollback()

        log.info(f"Fase 15 concluida: {count} lancamentos atualizados com verbas.")
        return count


# ---------------------------------------------------------------------------
# Fase 16: loclanctocc -> lancamentos_financeiros (extrato de conta corrente)
# ---------------------------------------------------------------------------

class Phase16LancamentoCC(BasePhase):
    PHASE_ID = "16"
    CHUNK_SIZE = 1000

    def run(self) -> int:
        log.info("=== FASE 16: loclanctocc -> lancamentos_financeiros (extrato CC) BATCH ===")
        count = 0

        sql_insert = """
            INSERT INTO lancamentos_financeiros (
                id_imovel, id_inquilino, id_conta_bancaria,
                competencia, data_lancamento, data_vencimento,
                valor_principal, valor_total,
                situacao, tipo_lancamento, origem,
                historico, ativo, created_at, updated_at
            ) VALUES %s
        """

        batch_rows: List[tuple] = []

        def flush(rows: List[tuple]) -> int:
            if not rows:
                return 0
            try:
                with self.writer.conn.cursor() as cur:
                    psycopg2.extras.execute_values(
                        cur,
                        sql_insert,
                        rows,
                        template="(%s,%s,%s,%s,%s,%s,%s,%s,'pago',%s,'extrato_cc_migracao',%s,TRUE,NOW(),NOW())"
                    )
                self.writer.commit()
                return len(rows)
            except Exception as e:
                log.error(f"loclanctocc batch INSERT ERRO — {e}")
                if cfg.STOP_ON_ERROR:
                    raise
                self.writer.rollback()
                return 0

        for row in self.parser.iter_table("loclanctocc"):
            old_codigo = safe_int(row.get("codigo"))
            data = safe_date(row.get("data"))
            if not data:
                continue

            historico = safe_str(row.get("historico")) or ""
            valor = safe_float(row.get("valor"))
            sinal = safe_str(row.get("sinal")) or "D"
            conta_old = safe_int(row.get("conta"))
            inq_old = safe_int(row.get("inquilino"))
            imovel_old = safe_int(row.get("imovel"))

            if valor <= 0:
                continue

            conta_new_id = self.state.get_new_id("conta", conta_old)
            inq_new_id = self.state.get_new_id("locinquilino", inq_old)
            imovel_new_id = self.state.get_new_id("locimovel", imovel_old)

            tipo_sinal = cfg.SINAL_MAP.get(sinal, "debito")
            tipo_lanc = "receita" if tipo_sinal == "credito" else "despesa"

            batch_rows.append((
                imovel_new_id,
                inq_new_id,
                conta_new_id,
                data.replace(day=1),
                data,
                data,
                str(round(valor, 2)),
                str(round(valor, 2)),
                tipo_lanc,
                historico[:500] if historico else f"Lancto CC #{old_codigo}",
            ))

            if len(batch_rows) >= self.CHUNK_SIZE:
                n = flush(batch_rows)
                count += n
                batch_rows.clear()
                log.info(f"  ... {count} lancamentos CC migrados")

        count += flush(batch_rows)
        log.info(f"Fase 16 concluida: {count} lancamentos CC migrados.")
        return count


# ---------------------------------------------------------------------------
# Fase 17: locacordo -> acordos_financeiros (se tabela existir)
# ---------------------------------------------------------------------------

class Phase17Acordos(BasePhase):
    PHASE_ID = "17"

    """
    Schema novo acordos_financeiros:
      numero_acordo (NOT NULL), id_inquilino (NOT NULL),
      data_acordo (NOT NULL), data_primeira_parcela (NOT NULL),
      valor_divida_original (NOT NULL), valor_desconto, valor_juros,
      valor_total_acordo (NOT NULL), quantidade_parcelas (NOT NULL),
      valor_parcela (NOT NULL), dia_vencimento, situacao, observacoes

    locacordo antigo tem uma linha POR PARCELA. Precisamos agrupar por numero_acordo
    e extrair o header (valor total, qtd parcelas, etc.).
    """

    def run(self) -> int:
        log.info("=== FASE 17: locacordo -> acordos_financeiros (BATCH) ===")

        if not self.writer.table_exists("acordos_financeiros"):
            log.warning("Tabela 'acordos_financeiros' nao existe no novo banco. Fase pulada.")
            return 0

        # Step 1: Aggregate all parcelas into acordos in memory (74 acordos, tiny)
        acordos_data: Dict[int, Dict] = {}
        for row in self.parser.iter_table("locacordo"):
            acordo_num = safe_int(row.get("Acordo"))
            if acordo_num == 0:
                continue
            inq_old = safe_int(row.get("Inquilino"))
            data = safe_date(row.get("Data"))
            principal = safe_float(row.get("Principal"))
            multa = safe_float(row.get("Multa"))
            vencto = safe_date(row.get("Vencto"))
            situacao_ch = safe_str(row.get("situacao")) or "A"
            valor_pago = safe_float(row.get("ValorPago"))

            if acordo_num not in acordos_data:
                acordos_data[acordo_num] = {
                    "inq_old": inq_old,
                    "data_acordo": data,
                    "primeira_parcela": vencto,
                    "total_divida": 0.0,
                    "total_multa": 0.0,
                    "total_pago": 0.0,
                    "qtd_parcelas": 0,
                    "dia_vencimento": None,
                    "todas_pagas": True,
                }

            ac = acordos_data[acordo_num]
            ac["total_divida"] += principal
            ac["total_multa"] += multa
            ac["total_pago"] += valor_pago
            ac["qtd_parcelas"] += 1
            if vencto and (not ac["primeira_parcela"] or vencto < ac["primeira_parcela"]):
                ac["primeira_parcela"] = vencto
            if vencto:
                ac["dia_vencimento"] = vencto.day
            if situacao_ch != "C":
                ac["todas_pagas"] = False

        log.info(f"locacordo: {len(acordos_data)} acordos agrupados.")

        # Step 2: Pre-load existing acordo numbers for idempotency
        existing_acordos: set = set(
            r[0] for r in self.writer.fetch_all("SELECT numero_acordo FROM acordos_financeiros")
        )

        # Step 3: Build batch insert rows
        insert_rows: List[tuple] = []
        for acordo_num, ac in acordos_data.items():
            if acordo_num in existing_acordos:
                continue
            inq_new_id = self.state.get_new_id("locinquilino", ac["inq_old"])
            if not inq_new_id:
                log.debug(f"Acordo {acordo_num}: inquilino {ac['inq_old']} nao mapeado.")
                continue

            total_acordo = ac["total_divida"] + ac["total_multa"]
            qtd = max(ac["qtd_parcelas"], 1)
            valor_parcela = total_acordo / qtd if qtd > 0 else total_acordo
            situacao = "quitado" if ac["todas_pagas"] else "aberto"

            insert_rows.append((
                acordo_num, inq_new_id,
                ac["data_acordo"] or date.today(),
                ac["primeira_parcela"] or date.today(),
                str(round(ac["total_divida"], 2)),
                str(round(ac["total_multa"], 2)),
                str(round(total_acordo, 2)),
                qtd,
                str(round(valor_parcela, 2)),
                ac["dia_vencimento"],
                situacao,
                f"Migrado do sistema antigo. Total pago: R${ac['total_pago']:.2f}",
            ))

        log.info(f"acordos_financeiros: {len(insert_rows)} acordos a inserir.")

        count = 0
        if insert_rows:
            try:
                sql = """
                    INSERT INTO acordos_financeiros (
                        numero_acordo, id_inquilino,
                        data_acordo, data_primeira_parcela,
                        valor_divida_original, valor_juros,
                        valor_total_acordo, quantidade_parcelas, valor_parcela,
                        dia_vencimento, situacao,
                        observacoes, created_at, updated_at
                    ) VALUES %s
                """
                with self.writer.conn.cursor() as cur:
                    psycopg2.extras.execute_values(
                        cur,
                        sql,
                        insert_rows,
                        template="(%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,NOW(),NOW())"
                    )
                self.writer.commit()
                count = len(insert_rows)
            except Exception as e:
                log.error(f"Batch INSERT acordos_financeiros: {e}")
                if cfg.STOP_ON_ERROR:
                    raise
                self.writer.rollback()

        log.info(f"Fase 17 concluida: {count} acordos migrados.")
        return count


# ---------------------------------------------------------------------------
# Fase 18: locrepasse -> prestacoes_contas (se tabela existir)
# ---------------------------------------------------------------------------

class Phase18Repasses(BasePhase):
    PHASE_ID = "18"

    """
    Schema novo prestacoes_contas:
      numero (NOT NULL), ano (NOT NULL),
      data_inicio (NOT NULL), data_fim (NOT NULL),
      tipo_periodo (NOT NULL), competencia,
      id_proprietario (NOT NULL), id_imovel,
      total_receitas, total_despesas, total_taxa_admin, total_retencao_ir,
      valor_repasse, status, data_repasse, forma_repasse,
      observacoes, created_at, updated_at
    """
    CHUNK_SIZE = 1000

    def run(self) -> int:
        log.info("=== FASE 18: locrepasse -> prestacoes_contas (BATCH) ===")

        if not self.writer.table_exists("prestacoes_contas"):
            log.warning("Tabela 'prestacoes_contas' nao existe. Fase pulada.")
            return 0

        schema = self.parser.parse_schema()
        if "locrepasse" not in schema:
            log.warning("Tabela 'locrepasse' nao encontrada no dump.")
            return 0

        cols = schema["locrepasse"]
        log.info(f"locrepasse colunas: {cols}")

        # Sequential counter for numero
        max_num = self.writer.fetch_one("SELECT COALESCE(MAX(numero), 0) FROM prestacoes_contas")
        numero_seq = (max_num[0] if max_num else 0) + 1

        # Collect all valid rows first, then batch insert
        insert_rows: List[tuple] = []

        for row in self.parser.iter_table("locrepasse"):
            locador_old = safe_int(row.get("proprietario") or 0)
            imovel_old = safe_int(row.get("imovel") or 0)
            data_inicio = safe_date(row.get("periodo1"))
            data_fim = safe_date(row.get("periodo2"))
            data_baixa = safe_date(row.get("databaixa"))
            valor = safe_float(row.get("valor"))

            if not data_inicio or not data_fim:
                continue

            locador_new_id = self.state.get_new_id("loclocador", locador_old)
            if not locador_new_id:
                log.debug(f"Repasse: locador {locador_old} nao mapeado.")
                continue

            imovel_new_id = self.state.get_new_id("locimovel", imovel_old)
            if imovel_new_id == 0:
                imovel_new_id = None

            # Derive competencia from data_fim
            ano = data_fim.year
            mes_str = f"{data_fim.month:02d}/{data_fim.year}"

            insert_rows.append((
                numero_seq, ano,
                data_inicio, data_fim,
                mes_str,
                locador_new_id, imovel_new_id,
                str(round(valor, 2)) if valor else "0.00",
                data_baixa or data_fim,
                "Migrado do sistema antigo",
            ))
            numero_seq += 1

        log.info(f"locrepasse: {len(insert_rows)} repasses a inserir.")

        sql_insert = """
            INSERT INTO prestacoes_contas (
                numero, ano,
                data_inicio, data_fim,
                tipo_periodo, competencia,
                id_proprietario, id_imovel,
                valor_repasse, status,
                data_repasse, forma_repasse,
                observacoes, created_at, updated_at
            ) VALUES %s
        """

        count = 0
        for i in range(0, len(insert_rows), self.CHUNK_SIZE):
            chunk = insert_rows[i:i + self.CHUNK_SIZE]
            try:
                with self.writer.conn.cursor() as cur:
                    psycopg2.extras.execute_values(
                        cur,
                        sql_insert,
                        chunk,
                        template="(%s,%s,%s,%s,'mensal',%s,%s,%s,%s,'pago',%s,'transferencia',%s,NOW(),NOW())"
                    )
                self.writer.commit()
                count += len(chunk)
                log.info(f"  ... {count}/{len(insert_rows)} repasses migrados")
            except Exception as e:
                log.error(f"Batch INSERT prestacoes_contas chunk {i}: {e}")
                if cfg.STOP_ON_ERROR:
                    raise
                self.writer.rollback()

        log.info(f"Fase 18 concluida: {count} repasses migrados.")
        return count


# ===========================================================================
# REGISTRO DE TODAS AS FASES
# ===========================================================================

PHASE_REGISTRY: Dict[str, type] = {
    "00": Phase00Validacao,
    "01": Phase01Bancos,
    "02": Phase02Agencias,
    "03": Phase03ContasBancarias,
    "04": Phase04PlanoContas,
    "05": Phase05Estados,
    "06": Phase06Cidades,
    "07": Phase07Bairros,
    "08": Phase08Locadores,
    "09": Phase09Fiadores,
    "10": Phase10Contratantes,
    "11": Phase11Imoveis,
    "12": Phase12ImovelProprietario,
    "13": Phase13Inquilinos,
    "14": Phase14Recibos,
    "15": Phase15RecibosHistorico,
    "16": Phase16LancamentoCC,
    "17": Phase17Acordos,
    "18": Phase18Repasses,
}


# ===========================================================================
# ORQUESTRADOR PRINCIPAL
# ===========================================================================

class MigrationOrchestrator:

    def __init__(self, dry_run: bool = False):
        self.parser = MySQLDumpParser(cfg.MYSQL_DUMP_PATH)
        self.writer = PostgreSQLWriter(cfg.POSTGRES_DSN, dry_run=dry_run)
        self.state = StateManager()

    def run_phase(self, phase_id: str) -> int:
        phase_class = PHASE_REGISTRY.get(phase_id)
        if not phase_class:
            log.error(f"Fase '{phase_id}' nao encontrada.")
            return 0

        if self.state.is_phase_done(phase_id):
            log.info(f"Fase {phase_id} ja concluida anteriormente. Pulando.")
            log.info("Use --reset-phase {phase_id} para re-executar.")
            return 0

        log.info(f"Iniciando fase {phase_id}: {phase_class.__name__}")
        phase = phase_class(self.parser, self.writer, self.state)
        start = datetime.now()
        try:
            count = phase.run()
            elapsed = (datetime.now() - start).total_seconds()
            log.info(f"Fase {phase_id} OK: {count} registros em {elapsed:.1f}s")
            self.state.mark_phase_done(phase_id)
            return count
        except Exception as e:
            log.error(f"Fase {phase_id} FALHOU: {e}")
            log.debug(traceback.format_exc())
            self.writer.rollback()
            return 0

    def run_phases(self, phase_ids: List[str]) -> None:
        self.writer.connect()
        try:
            total = 0
            for phase_id in phase_ids:
                n = self.run_phase(phase_id)
                total += n
            log.info(f"Migração concluída. Total de registros processados: {total}")
        finally:
            self.writer.disconnect()

    def reset_phase(self, phase_id: str) -> None:
        """Remove a marca de fase concluída para permitir re-execução."""
        self.state.unmark_phase(phase_id)
        log.info(f"Fase {phase_id} desmarcada. Execute novamente para re-migrar.")

    def list_phases(self) -> None:
        print("\nFases disponíveis:")
        print(f"{'ID':>4}  {'Classe':35}  {'Status'}")
        print("-" * 60)
        for pid, pclass in PHASE_REGISTRY.items():
            status = "CONCLUIDA" if self.state.is_phase_done(pid) else "pendente"
            print(f"  {pid}  {pclass.__name__:35}  {status}")
        print()

    def pre_flight_check(self) -> bool:
        """Verifica pré-condições antes de executar."""
        ok = True

        # 1. Dump existe?
        if not os.path.exists(cfg.MYSQL_DUMP_PATH):
            log.error(f"Dump MySQL nao encontrado: {cfg.MYSQL_DUMP_PATH}")
            ok = False

        # 2. PostgreSQL acessível?
        try:
            self.writer.connect()
            row = self.writer.fetch_one("SELECT version()")
            log.info(f"PostgreSQL: {row[0][:60]}")
            self.writer.disconnect()
        except Exception as e:
            log.error(f"Nao foi possivel conectar ao PostgreSQL: {e}")
            ok = False

        # 3. Usuário admin preservado?
        try:
            self.writer.connect()
            admin = self.writer.fetch_one(
                "SELECT id, email FROM users WHERE email = %s", (cfg.ADMIN_EMAIL,)
            )
            if admin:
                log.info(f"Usuario admin encontrado: id={admin[0]}, email={admin[1]}")
            else:
                log.warning(f"Usuario admin '{cfg.ADMIN_EMAIL}' NAO encontrado no banco.")
            self.writer.disconnect()
        except Exception as e:
            log.warning(f"Nao foi possivel verificar usuario admin: {e}")

        return ok


# ===========================================================================
# ENTRY POINT
# ===========================================================================

def main() -> None:
    parser = argparse.ArgumentParser(
        description="Migração MySQL 5.5 -> PostgreSQL 15 — AlmasaStudio",
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""
Exemplos:
  python3 migrate.py --list
  python3 migrate.py --check
  python3 migrate.py --phase 01
  python3 migrate.py --phase 01,02,03
  python3 migrate.py --phase all
  python3 migrate.py --phase all --dry-run
  python3 migrate.py --reset-phase 08
        """
    )
    parser.add_argument(
        "--phase",
        help="ID(s) da fase a executar. Use 'all' para todas. Separe múltiplos com vírgula.",
    )
    parser.add_argument(
        "--list",
        action="store_true",
        help="Lista todas as fases e seus status.",
    )
    parser.add_argument(
        "--check",
        action="store_true",
        help="Verifica pré-condições (conexão, dump, admin).",
    )
    parser.add_argument(
        "--dry-run",
        action="store_true",
        help="Simula a migração sem gravar no banco.",
    )
    parser.add_argument(
        "--reset-phase",
        metavar="PHASE_ID",
        help="Remove a marca de fase concluída para re-executar.",
    )
    parser.add_argument(
        "--stop-on-error",
        action="store_true",
        help="Para na primeira exceção (default: loga e continua).",
    )

    args = parser.parse_args()

    # Aplica opções globais
    if args.dry_run:
        cfg.DRY_RUN = True
    if args.stop_on_error:
        cfg.STOP_ON_ERROR = True

    orchestrator = MigrationOrchestrator(dry_run=cfg.DRY_RUN)

    if args.list:
        orchestrator.list_phases()
        return

    if args.check:
        ok = orchestrator.pre_flight_check()
        sys.exit(0 if ok else 1)

    if args.reset_phase:
        orchestrator.reset_phase(args.reset_phase)
        return

    if not args.phase:
        parser.print_help()
        sys.exit(1)

    # Determina quais fases executar
    if args.phase.lower() == "all":
        phase_ids = sorted(PHASE_REGISTRY.keys())
    else:
        phase_ids = [p.strip().zfill(2) for p in args.phase.split(",")]
        invalid = [p for p in phase_ids if p not in PHASE_REGISTRY]
        if invalid:
            log.error(f"Fases inválidas: {invalid}")
            sys.exit(1)

    log.info(f"Iniciando migração. Fases: {phase_ids}. DRY_RUN={cfg.DRY_RUN}")
    log.info(f"Log: {_log_file}")
    log.info(f"ID Map: {cfg.ID_MAP_FILE}")

    orchestrator.run_phases(phase_ids)


if __name__ == "__main__":
    main()
