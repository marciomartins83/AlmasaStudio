"""
config.py — Configurações da Migração MySQL -> PostgreSQL
AlmasaStudio | Sistema de Gestão Imobiliária

Engenheiro Senior: Opus 4.6
Data: 2026-02-21 (v3 — associações de tipo, profissões, chaves PIX, pai/mãe)
"""

import os

# ============================================================
# BANCO DE ORIGEM: MySQL dump (arquivo local, sem servidor)
# ============================================================

MYSQL_DUMP_PATH = os.getenv(
    "MYSQL_DUMP_PATH",
    "/home/marciorsm/AlmasaStudio/bkpBancoFormatoAntigo/bkpjpw_compacto_2025.sql"
)

# Encoding do dump MySQL (as tabelas são latin1, mas o SET NAMES é utf8)
MYSQL_DUMP_ENCODING = "latin-1"

# ============================================================
# BANCO DE DESTINO: PostgreSQL 15 Neon Cloud
# ============================================================

POSTGRES_DSN = os.getenv(
    "POSTGRES_DSN",
    "postgresql://almasa_prod:AlmasaProd2026@localhost:5432/almasa_prod"
)

# ============================================================
# USUÁRIO ADMIN A PRESERVAR
# ============================================================

ADMIN_EMAIL = "marcioramos1983@gmail.com"

# ============================================================
# ARQUIVOS DE LOG E ESTADO
# ============================================================

LOG_DIR = os.getenv(
    "MIGRATION_LOG_DIR",
    "/home/marciorsm/AlmasaStudio/logs/migration"
)

ID_MAP_FILE = os.path.join(LOG_DIR, "id_map.json")
PHASES_DONE_FILE = os.path.join(LOG_DIR, "phases_done.json")

# ============================================================
# OPÇÕES DE COMPORTAMENTO
# ============================================================

BATCH_SIZE = 500
DRY_RUN = False
STOP_ON_ERROR = False

# ============================================================
# MAPEAMENTOS DE DOMÍNIO
# ============================================================

PLANO_TIPO_MAP = {
    0: 0,   # Receita
    1: 1,   # Despesa
    2: 2,   # Transitória
    3: 3,   # Caixa
}

TIPO_PESSOA_MAP = {
    0: "fisica",
    1: "juridica",
}

# locinquilino.estcivil -> estado_civil (id na tabela estado_civil)
# IDs CONFIRMADOS 2026-02-21:
#   1=Casado, 3=Solteiro, 8=Separado, 9=Divorciado, 10=Viúvo, 11=União Estável
ESTADO_CIVIL_MAP = {
    0: None,   # não informado
    1: 3,      # solteiro  -> id=3
    2: 1,      # casado    -> id=1
    3: 8,      # separado  -> id=8
    4: 9,      # divorciado -> id=9
    5: 10,     # viuvo     -> id=10
    6: 11,     # uniao estavel -> id=11
}

INDICE_REAJUSTE_MAP = {
    0:  "IGPM",
    1:  "IGPM",
    2:  "IPCA",
    3:  "INPC",
    4:  "TR",
    5:  "IPC",
    6:  "INPC",
    7:  "IGPM",
    8:  "IPCA",
    9:  "INCC",
    10: "OUTRO",
}

SITUACAO_IMOVEL_MAP = {
    0: "disponivel",
    1: "locado",
    2: "vendido",
    3: "suspenso",
    4: "inativo",
}

TIPO_IMOVEL_FALLBACK_ID = 1  # Casa

SITUACAO_LANCAMENTO_MAP = {
    0: "aberto",
    1: "pago",
    2: "cancelado",
    3: "acordado",
    4: "judicial",
    5: "aberto",
}

SINAL_MAP = {
    "D": "debito",
    "C": "credito",
    "d": "debito",
    "c": "credito",
}

FORMA_RETIRADA_MAP = {
    0: "transferencia",
    1: "credito",
    2: "cheque",
    3: "dinheiro",
    4: "outro",
}

# ============================================================
# FASES DA MIGRAÇÃO (ordem de execução)
# ============================================================

PHASES = [
    ("00",   None,                "validacao",               "Validação de Parametrização"),
    ("01",   "banco",             "bancos",                  "Bancos"),
    ("02",   "agencia",           "agencias",                "Agências"),
    ("03",   "conta",             "contas_bancarias",        "Contas Bancárias (sistema)"),
    ("04",   "locplano",          "plano_contas",            "Plano de Contas"),
    ("05",   "p_estado",          "estados",                 "Estados"),
    ("06",   "p_cidade",          "cidades",                 "Cidades"),
    ("07",   "p_bairro",          "bairros",                 "Bairros"),
    ("08",   "loclocadores",      "pessoas+enderecos",       "Locadores -> Pessoas"),
    ("09",   "locfiadores",       "pessoas+enderecos",       "Fiadores -> Pessoas"),
    ("10",   "loccontratantes",   "pessoas+enderecos",       "Contratantes -> Pessoas"),
    ("11",   "locimoveis",        "imoveis",                 "Imóveis"),
    ("12",   "locimovelprop",     "imoveis (proprietario)",  "Vínculo Imóvel-Proprietário"),
    ("13",   "locinquilino",      "pessoas+imoveis_contratos","Inquilinos -> Pessoas + Contratos"),
    ("14",   "locrecibo",         "lancamentos_financeiros",  "Recibos -> Lançamentos"),
    ("15",   "locrechist",        "lancamentos_financeiros",  "Histórico Recibo -> Verbas"),
    ("16",   "loclanctocc",       "lancamentos_financeiros",  "Extrato CC -> Lançamentos"),
    ("17",   "locacordo",         "acordos_financeiros",      "Acordos Financeiros"),
    ("18",   "locrepasse",        "prestacoes_contas",        "Repasses -> Prestações de Contas"),
]

# ============================================================
# IDs DE PARAMETRIZAÇÃO — CONFIRMADOS em 2026-02-21
# ============================================================
# ESTES IDs SÃO CARREGADOS DINAMICAMENTE NA FASE 00.
# Os valores abaixo são fallback caso a Fase 00 não rode.

# tipos_pessoas — CONFIRMADOS:
#   1=fiador, 2=corretor, 3=corretora, 4=locador,
#   5=pretendente, 6=contratante, 7=socio, 8=advogado, 12=inquilino
TIPO_PESSOA_LOCADOR_ID = 4
TIPO_PESSOA_FIADOR_ID = 1
TIPO_PESSOA_CONTRATANTE_ID = 6
TIPO_PESSOA_INQUILINO_ID = 12

# tipos_documentos — CONFIRMADOS: 1=CPF, 2=RG, 4=CNPJ, 5=IE, 7=Passaporte
DEFAULT_TIPO_DOCUMENTO_CPF_ID = 1
DEFAULT_TIPO_DOCUMENTO_RG_ID = 2
DEFAULT_TIPO_DOCUMENTO_CNPJ_ID = 4

# tipos_telefones — CONFIRMADOS: 2=Residencial, 3=Comercial, 6=Celular
TIPO_TELEFONE_RESIDENCIAL_ID = 2
TIPO_TELEFONE_CELULAR_ID = 6
TIPO_TELEFONE_COMERCIAL_ID = 3
TIPO_TELEFONE_FAX_ID = 4        # Fax — will be created in Phase 00 if not exists
TIPO_TELEFONE_OUTROS_ID = 5     # Outros — will be created in Phase 00 if not exists

# tipos_enderecos — CONFIRMADOS: 1=Residencial, 3=Comercial
DEFAULT_TIPO_ENDERECO_ID = 1
TIPO_ENDERECO_COMERCIAL_ID = 3

# tipos_emails — CONFIRMADOS: 1=Pessoal, 4=Profissional
DEFAULT_TIPO_EMAIL_ID = 1

# tipos_imoveis — CONFIRMADOS: 1=Casa, 2=Apartamento, 3=Terreno, 4=Comercial, 5=Rural
DEFAULT_TIPO_IMOVEL_ID = 1

# tipos_contas_bancarias — CONFIRMADOS: 1=Conta Corrente
DEFAULT_TIPO_CONTA_BANCARIA_ID = 1

# Mapa campo MySQL -> tipo telefone
# loclocadores/locfiadores/locinquilino tem campos separados para cada tipo
TELEFONE_FIELD_MAP = {
    "telefone": TIPO_TELEFONE_RESIDENCIAL_ID,
    "celular": TIPO_TELEFONE_CELULAR_ID,
    "comercial": TIPO_TELEFONE_COMERCIAL_ID,
    "tel": TIPO_TELEFONE_RESIDENCIAL_ID,  # alias em locinquilino
    "fax": TIPO_TELEFONE_FAX_ID,
    "outros": TIPO_TELEFONE_OUTROS_ID,
    "telcom": TIPO_TELEFONE_COMERCIAL_ID,
}

# ============================================================
# CHAVES PIX — mapeamento loclocadores.tipoChavePix -> tipos_chaves_pix.id
# ============================================================
# tipos_chaves_pix: 1=CPF, 3=Telefone, 4=Email, 8=CNPJ, 9=Aleatória
TIPO_CHAVE_PIX_MAP = {
    1: 1,   # CPF
    2: 8,   # CNPJ
    3: 3,   # Telefone
    4: 4,   # Email
    5: 9,   # Aleatória
}

# ============================================================
# FORMAS DE RETIRADA — loclocadores.formaretirada
# ============================================================
# Valores MySQL: -1=não informado, 0=transferência, 1=crédito, 2=cheque, 3=dinheiro
FORMAS_RETIRADA_SEED = [
    (1, "Transferência Bancária", "Transferência via TED/PIX"),
    (2, "Crédito em Conta", "Crédito direto em conta corrente"),
    (3, "Cheque", "Retirada via cheque"),
    (4, "Dinheiro", "Retirada em espécie"),
    (5, "Outro", "Outra forma de retirada"),
]

FORMA_RETIRADA_MYSQL_MAP = {
    -1: None,  # não informado
    0: 1,      # transferência -> id=1
    1: 2,      # crédito -> id=2
    2: 3,      # cheque -> id=3
    3: 4,      # dinheiro -> id=4
}

# ============================================================
# NACIONALIDADE — padrão Brasileira (id=1)
# ============================================================
DEFAULT_NACIONALIDADE_ID = 1
