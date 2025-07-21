-- =====================================================
-- SCRIPT DE BANCO DE DADOS NORMALIZADO V3 - ALMASA STUDIO
-- Versão corrigida com normalização adequada
-- Baseado no script MySQL original normalizado para PostgreSQL
-- Aplicando as melhores práticas de normalização
-- =====================================================

-- Configurações iniciais
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SET check_function_bodies = false;
SET client_min_messages = warning;

-- =====================================================
-- TABELAS DE REFERÊNCIA (Baseadas em camposjadefinidos.txt)
-- =====================================================

-- Tabela de bancos
CREATE TABLE bancos (
    id SERIAL PRIMARY KEY,
    nome VARCHAR(60) NOT NULL,
    numero INTEGER NOT NULL
);

-- Tabela de tipos de documentos
CREATE TABLE tipos_documentos (
    id SERIAL PRIMARY KEY,
    tipo VARCHAR(60) NOT NULL
);

-- Tabela de tipos de chaves PIX
CREATE TABLE tipos_chaves_pix (
    id SERIAL PRIMARY KEY,
    tipo VARCHAR(60) NOT NULL
);

-- Tabela de tipos de contas bancárias
CREATE TABLE tipos_contas_bancarias (
    id SERIAL PRIMARY KEY,
    tipo VARCHAR(60) NOT NULL
);

-- Tabela de tipos de telefones
CREATE TABLE tipos_telefones (
    id SERIAL PRIMARY KEY,
    tipo VARCHAR(60) NOT NULL
);

-- Tabela de estados
CREATE TABLE estados (
    id SERIAL PRIMARY KEY,
    uf VARCHAR(2) NOT NULL UNIQUE
);

-- Tabela de cidades
CREATE TABLE cidades (
    id SERIAL PRIMARY KEY,
    id_estado INTEGER NOT NULL REFERENCES estados(id),
    nome VARCHAR(255) NOT NULL,
    codigo VARCHAR(15)
);

-- Tabela de bairros
CREATE TABLE bairros (
    id SERIAL PRIMARY KEY,
    id_cidade INTEGER NOT NULL REFERENCES cidades(id),
    nome VARCHAR(255) NOT NULL,
    codigo VARCHAR(15)
);

-- Tabela de logradouros
CREATE TABLE logradouros (
    id SERIAL PRIMARY KEY,
    id_bairro INTEGER NOT NULL REFERENCES bairros(id),
    logradouro VARCHAR(255) NOT NULL,
    cep VARCHAR(8) NOT NULL
);

-- Tabela de profissões
CREATE TABLE profissoes (
    id SERIAL PRIMARY KEY,
    nome VARCHAR(100) NOT NULL UNIQUE
);

-- Tabela de estados civis
CREATE TABLE estados_civis (
    id SERIAL PRIMARY KEY,
    nome VARCHAR(50) NOT NULL UNIQUE
);

-- Tabela de nacionalidades
CREATE TABLE nacionalidades (
    id SERIAL PRIMARY KEY,
    nome VARCHAR(100) NOT NULL UNIQUE
);

-- Tabela de tipos de pessoa (Física/Jurídica)
CREATE TABLE tipos_pessoa (
    id SERIAL PRIMARY KEY,
    nome VARCHAR(50) NOT NULL UNIQUE
);

-- Tabela de status de corretores
CREATE TABLE status_corretores (
    id SERIAL PRIMARY KEY,
    nome VARCHAR(30) NOT NULL UNIQUE
);

-- =====================================================
-- TABELA PRINCIPAL DE PESSOAS (já existente)
-- =====================================================

-- Tabela de pessoas (mantida conforme scriptANEXOPESSOA.sql)
CREATE TABLE pessoa (
    idpessoa SERIAL PRIMARY KEY,
    user_id INTEGER DEFAULT NULL,
    nome VARCHAR(255) NOT NULL,
    dt_cadastro TIMESTAMP WITHOUT TIME ZONE NOT NULL,
    tipo_pessoa INTEGER NOT NULL REFERENCES tipos_pessoa(id),
    status BOOLEAN NOT NULL DEFAULT true,
    theme_light BOOLEAN NOT NULL DEFAULT true
);

-- =====================================================
-- TABELAS NORMALIZADAS PARA DADOS PESSOAIS
-- =====================================================

-- Tabela de dados pessoais (CPF, RG, etc.)
CREATE TABLE dados_pessoais (
    id SERIAL PRIMARY KEY,
    id_pessoa INTEGER NOT NULL REFERENCES pessoa(idpessoa),
    cpf VARCHAR(18),
    rg VARCHAR(20),
    emissao_rg DATE,
    orgao_rg VARCHAR(50),
    data_nascimento DATE,
    id_nacionalidade INTEGER REFERENCES nacionalidades(id),
    id_estado_civil INTEGER REFERENCES estados_civis(id),
    id_profissao INTEGER REFERENCES profissoes(id),
    naturalidade VARCHAR(100),
    pai VARCHAR(100),
    mae VARCHAR(100),
    nome_conjuge VARCHAR(100),
    cpf_conjuge VARCHAR(18),
    rg_conjuge VARCHAR(20),
    emissao_rg_conjuge DATE,
    orgao_rg_conjuge VARCHAR(50),
    data_nascimento_conjuge DATE,
    id_nacionalidade_conjuge INTEGER REFERENCES nacionalidades(id),
    id_profissao_conjuge INTEGER REFERENCES profissoes(id),
    regime_casamento VARCHAR(35),
    UNIQUE(id_pessoa)
);

-- =====================================================
-- TABELAS NORMALIZADAS PARA ENDEREÇOS, TELEFONES, EMAILS
-- =====================================================

-- Tabela de endereços
CREATE TABLE enderecos (
    id SERIAL PRIMARY KEY,
    id_pessoa INTEGER NOT NULL REFERENCES pessoa(idpessoa),
    cep VARCHAR(8) NOT NULL,
    logradouro VARCHAR(255) NOT NULL,
    numero VARCHAR(10) NOT NULL,
    complemento VARCHAR(100),
    bairro VARCHAR(100) NOT NULL,
    cidade VARCHAR(100) NOT NULL,
    estado VARCHAR(2) NOT NULL,
    tipo_endereco VARCHAR(50) DEFAULT 'Residencial' -- Residencial, Comercial, Correspondência, etc.
);

-- Tabela de telefones
CREATE TABLE telefones (
    id SERIAL PRIMARY KEY,
    id_pessoa INTEGER NOT NULL REFERENCES pessoa(idpessoa),
    numero VARCHAR(15) NOT NULL,
    id_tipo INTEGER REFERENCES tipos_telefones(id),
    observacao VARCHAR(255)
);

-- Tabela de emails
CREATE TABLE emails (
    id SERIAL PRIMARY KEY,
    id_pessoa INTEGER NOT NULL REFERENCES pessoa(idpessoa),
    email VARCHAR(255) NOT NULL
);

-- Tabela de contas bancárias
CREATE TABLE contas_bancarias (
    id SERIAL PRIMARY KEY,
    id_pessoa INTEGER NOT NULL REFERENCES pessoa(idpessoa),
    id_banco INTEGER NOT NULL REFERENCES bancos(id),
    agencia VARCHAR(10) NOT NULL,
    conta VARCHAR(15) NOT NULL,
    id_tipo INTEGER REFERENCES tipos_contas_bancarias(id),
    observacao VARCHAR(255)
);

-- Tabela de PIX
CREATE TABLE pix (
    id SERIAL PRIMARY KEY,
    id_pessoa INTEGER NOT NULL REFERENCES pessoa(idpessoa),
    chave VARCHAR(255) NOT NULL,
    id_tipo INTEGER REFERENCES tipos_chaves_pix(id),
    observacao VARCHAR(255)
);

-- Tabela de documentos (para múltiplos tipos de documentos)
CREATE TABLE documentos (
    id SERIAL PRIMARY KEY,
    id_pessoa INTEGER NOT NULL REFERENCES pessoa(idpessoa),
    id_tipo INTEGER NOT NULL REFERENCES tipos_documentos(id),
    numero VARCHAR(50) NOT NULL,
    emissao DATE,
    orgao_emissor VARCHAR(50),
    observacao VARCHAR(255)
);

-- =====================================================
-- TABELAS DE NEGÓCIO NORMALIZADAS
-- =====================================================

-- Tabela de agências bancárias
CREATE TABLE agencias (
    id SERIAL PRIMARY KEY,
    codigo VARCHAR(10) NOT NULL,
    id_banco INTEGER NOT NULL REFERENCES bancos(id),
    nome VARCHAR(20),
    id_endereco INTEGER REFERENCES enderecos(id),
    ponto_venda VARCHAR(4) DEFAULT '0',
    data_hora TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de cheques
CREATE TABLE cheques (
    id SERIAL PRIMARY KEY,
    numero INTEGER NOT NULL,
    id_banco INTEGER REFERENCES bancos(id),
    valor DECIMAL(10,2) DEFAULT 0,
    data_baixa DATE,
    data_emissao DATE,
    favor VARCHAR(50),
    id_conta INTEGER,
    data_cancelamento DATE,
    status BOOLEAN NOT NULL DEFAULT false,
    malote INTEGER NOT NULL DEFAULT 0,
    data_malote DATE,
    motivo VARCHAR(100),
    motivo_cancelamento VARCHAR(200),
    cod_conta INTEGER,
    cod_cc INTEGER,
    id_lancamento INTEGER DEFAULT 0,
    data_retorno DATE,
    data_hora TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de compromissos
CREATE TABLE compromissos (
    id SERIAL PRIMARY KEY,
    grupo INTEGER,
    condominio INTEGER,
    bloco INTEGER,
    vencimento DATE,
    conta INTEGER,
    historico VARCHAR(200),
    periodo INTEGER,
    dia_mes CHAR(1),
    credor VARCHAR(50),
    favor VARCHAR(50),
    tipo_pagamento INTEGER,
    valor DECIMAL(10,2),
    plano INTEGER,
    tipo_documento BOOLEAN DEFAULT false,
    operacao BOOLEAN DEFAULT false,
    tipo_referencia BOOLEAN NOT NULL DEFAULT false,
    cod_credor VARCHAR(10),
    cod_favor VARCHAR(10),
    baixar_lancamento BOOLEAN DEFAULT true,
    num_processo INTEGER DEFAULT 0,
    data_hora TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de contas bancárias do sistema
CREATE TABLE contas_sistema (
    id SERIAL PRIMARY KEY,
    codigo INTEGER NOT NULL,
    id_banco INTEGER REFERENCES bancos(id),
    agencia VARCHAR(10) NOT NULL,
    cod_conta VARCHAR(15),
    cod_cedente DECIMAL(10,2) DEFAULT 0,
    diario BOOLEAN,
    condominio INTEGER DEFAULT 0,
    ult_cheque INTEGER DEFAULT 0,
    numero_usado INTEGER DEFAULT 0,
    numero_final INTEGER DEFAULT 0,
    carteira VARCHAR(4),
    numero_inicial INTEGER DEFAULT 0,
    controla_talao BOOLEAN DEFAULT false,
    ponto_venda VARCHAR(4) DEFAULT '0',
    flash VARCHAR(4),
    cod_contabil INTEGER DEFAULT 0,
    conta_vinculada INTEGER DEFAULT 0,
    cod_pagfor BIGINT DEFAULT 0,
    cod_ptrb BIGINT DEFAULT 0,
    cod_folha BIGINT DEFAULT 0,
    seq_pagfor INTEGER DEFAULT 0,
    seq_ptrb INTEGER DEFAULT 0,
    seq_folha INTEGER DEFAULT 0,
    razao_conta VARCHAR(4) DEFAULT '0',
    tipo_conta BOOLEAN NOT NULL DEFAULT false,
    ult_arq_rem_cob INTEGER NOT NULL DEFAULT 0,
    registrada BOOLEAN DEFAULT false,
    carencia INTEGER NOT NULL DEFAULT 0,
    multa_itau BOOLEAN NOT NULL DEFAULT false,
    mora_diaria BOOLEAN NOT NULL DEFAULT false,
    protesto INTEGER NOT NULL DEFAULT 0,
    dias_prot CHAR(2) NOT NULL DEFAULT '0',
    multi_pag BOOLEAN DEFAULT false,
    conv_multi_pag VARCHAR(20),
    tipo_dia INTEGER NOT NULL DEFAULT 0,
    desc_conta VARCHAR(40) DEFAULT '',
    tipo_remessa BOOLEAN NOT NULL DEFAULT false,
    layout_remessa CHAR(3) NOT NULL DEFAULT '400',
    nao_gerar_judicial BOOLEAN NOT NULL DEFAULT false,
    carencia_c INTEGER NOT NULL DEFAULT 0,
    carencia_a INTEGER NOT NULL DEFAULT 0,
    end_cobranca BOOLEAN NOT NULL DEFAULT false,
    tipo_arquivo BOOLEAN NOT NULL DEFAULT false,
    variacao_bb BOOLEAN NOT NULL DEFAULT false,
    multi_pag_bloq BOOLEAN DEFAULT false,
    mudar_especie BOOLEAN NOT NULL DEFAULT false,
    cond_conta BOOLEAN NOT NULL DEFAULT false,
    convenio_sicredi VARCHAR(20),
    credencial_pjbank VARCHAR(50),
    chave_pjbank VARCHAR(50),
    banco_pjbank SMALLINT,
    cob_compartilhada BOOLEAN NOT NULL DEFAULT false,
    data_hora TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de empréstimos de chaves
CREATE TABLE emprestimos_chaves (
    id SERIAL PRIMARY KEY,
    id_imovel INTEGER NOT NULL DEFAULT 0,
    id_pretendente INTEGER NOT NULL DEFAULT 0,
    tipo BOOLEAN NOT NULL DEFAULT false,
    data DATE NOT NULL DEFAULT '0000-00-00',
    hora VARCHAR(5) NOT NULL DEFAULT '',
    data_devolucao DATE NOT NULL DEFAULT '0000-00-00',
    hora_devolucao VARCHAR(5) NOT NULL DEFAULT '',
    motivo VARCHAR(100) NOT NULL DEFAULT '',
    id_usuario INTEGER, -- Referência ao usuário do sistema
    entregue_por VARCHAR(20) NOT NULL DEFAULT '',
    numero_atendimento INTEGER NOT NULL DEFAULT 0
);

-- Tabela de feriados
CREATE TABLE feriados (
    id SERIAL PRIMARY KEY,
    data DATE NOT NULL,
    descricao VARCHAR(100) NOT NULL
);

-- Tabela de índices
CREATE TABLE indices (
    id SERIAL PRIMARY KEY,
    codigo INTEGER NOT NULL DEFAULT 0,
    nome VARCHAR(50) NOT NULL DEFAULT '',
    data_hora TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de valores dos índices
CREATE TABLE valores_indices (
    id SERIAL PRIMARY KEY,
    id_indice INTEGER NOT NULL REFERENCES indices(id),
    data DATE NOT NULL DEFAULT '0000-00-00',
    valor DECIMAL(10,4) DEFAULT NULL,
    data_hora TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de imóveis
CREATE TABLE imoveis (
    id SERIAL PRIMARY KEY,
    codigo INTEGER NOT NULL DEFAULT 0,
    situacao BOOLEAN NOT NULL DEFAULT false,
    utilizacao BOOLEAN NOT NULL DEFAULT false,
    tipo_imovel BOOLEAN NOT NULL DEFAULT false,
    aluguel BOOLEAN NOT NULL DEFAULT false,
    venda BOOLEAN NOT NULL DEFAULT false,
    temporada BOOLEAN NOT NULL DEFAULT false,
    id_endereco INTEGER REFERENCES enderecos(id),
    inscricao_mobiliaria VARCHAR(50) NOT NULL DEFAULT '',
    valor DECIMAL(10,2) NOT NULL DEFAULT 0,
    iptu DECIMAL(10,2) NOT NULL DEFAULT 0,
    taxa_lixo DECIMAL(10,2) NOT NULL DEFAULT 0,
    condominio DECIMAL(10,2) NOT NULL DEFAULT 0,
    medidor_luz VARCHAR(50) NOT NULL DEFAULT '',
    medidor_agua VARCHAR(50) NOT NULL DEFAULT '',
    area_total DECIMAL(10,2) NOT NULL DEFAULT 0,
    area_construida DECIMAL(10,2) NOT NULL DEFAULT 0,
    area_privativa DECIMAL(10,2) NOT NULL DEFAULT 0,
    fundacao DATE,
    valor_mercado DECIMAL(10,2) NOT NULL DEFAULT 0,
    apartamentos_andar INTEGER NOT NULL DEFAULT 0,
    contribuinte VARCHAR(50) NOT NULL DEFAULT '',
    observacoes VARCHAR(255) NOT NULL DEFAULT '',
    imediacoes VARCHAR(255) NOT NULL DEFAULT '',
    caucao BOOLEAN NOT NULL DEFAULT false,
    valor_caucao DECIMAL(10,2) NOT NULL DEFAULT 0,
    posse_caucao BOOLEAN NOT NULL DEFAULT false,
    fiador BOOLEAN NOT NULL DEFAULT false,
    seguro_fianca BOOLEAN NOT NULL DEFAULT false,
    outros BOOLEAN NOT NULL DEFAULT false,
    obs_outros VARCHAR(255) NOT NULL DEFAULT '',
    id_corretor INTEGER NOT NULL DEFAULT 0,
    placa BOOLEAN NOT NULL DEFAULT false,
    garantido BOOLEAN NOT NULL DEFAULT false,
    remuneracao BOOLEAN NOT NULL DEFAULT false,
    taxa_minima DECIMAL(10,2) NOT NULL DEFAULT 0,
    taxa_adm DECIMAL(10,2) NOT NULL DEFAULT 0,
    seguradora VARCHAR(50) NOT NULL DEFAULT '',
    apolice VARCHAR(20) NOT NULL DEFAULT '',
    vencimento_seguro DATE,
    valor_seguro DECIMAL(10,2) NOT NULL DEFAULT 0,
    ext_sep BOOLEAN NOT NULL DEFAULT false,
    ie_prop BOOLEAN NOT NULL DEFAULT false,
    dia INTEGER NOT NULL DEFAULT 0,
    condominio_ir BOOLEAN NOT NULL DEFAULT false,
    cercado BOOLEAN NOT NULL DEFAULT false,
    edicula BOOLEAN NOT NULL DEFAULT false,
    tipo_casa BOOLEAN NOT NULL DEFAULT false,
    comercial BOOLEAN NOT NULL DEFAULT false,
    residencial BOOLEAN NOT NULL DEFAULT false,
    pavimentos INTEGER NOT NULL DEFAULT 0,
    banheiros INTEGER NOT NULL DEFAULT 0,
    quartos INTEGER NOT NULL DEFAULT 0,
    copa INTEGER NOT NULL DEFAULT 0,
    suite INTEGER NOT NULL DEFAULT 0,
    sala INTEGER NOT NULL DEFAULT 0,
    garagem INTEGER NOT NULL DEFAULT 0,
    garagem_coberta BOOLEAN NOT NULL DEFAULT false,
    jardim BOOLEAN NOT NULL DEFAULT false,
    quintal BOOLEAN NOT NULL DEFAULT false,
    interfone BOOLEAN NOT NULL DEFAULT false,
    piscina BOOLEAN NOT NULL DEFAULT false,
    sauna BOOLEAN NOT NULL DEFAULT false,
    porteiro BOOLEAN NOT NULL DEFAULT false,
    sala_festa BOOLEAN NOT NULL DEFAULT false,
    sala_jogos BOOLEAN NOT NULL DEFAULT false,
    sala_musica BOOLEAN NOT NULL DEFAULT false,
    rouparia BOOLEAN NOT NULL DEFAULT false,
    playground BOOLEAN NOT NULL DEFAULT false,
    varanda BOOLEAN NOT NULL DEFAULT false,
    lavabo BOOLEAN NOT NULL DEFAULT false,
    sala_tv BOOLEAN NOT NULL DEFAULT false,
    quadra BOOLEAN NOT NULL DEFAULT false,
    despensa BOOLEAN NOT NULL DEFAULT false,
    lavanderia BOOLEAN NOT NULL DEFAULT false,
    sotao BOOLEAN NOT NULL DEFAULT false,
    mesanino BOOLEAN NOT NULL DEFAULT false,
    terraco BOOLEAN NOT NULL DEFAULT false,
    box_despejo BOOLEAN NOT NULL DEFAULT false,
    aquecimento BOOLEAN NOT NULL DEFAULT false,
    grades BOOLEAN NOT NULL DEFAULT false,
    canil BOOLEAN NOT NULL DEFAULT false,
    coleta BOOLEAN NOT NULL DEFAULT false,
    parabolica BOOLEAN NOT NULL DEFAULT false,
    churrasqueira BOOLEAN NOT NULL DEFAULT false,
    elevador BOOLEAN NOT NULL DEFAULT false,
    vigia BOOLEAN NOT NULL DEFAULT false,
    tv_cabo BOOLEAN NOT NULL DEFAULT false,
    mobiliado BOOLEAN NOT NULL DEFAULT false,
    telefone_instalado BOOLEAN NOT NULL DEFAULT false,
    taxa_multa BOOLEAN NOT NULL DEFAULT false,
    taxa_desconto BOOLEAN NOT NULL DEFAULT false,
    cobra_cpmf BOOLEAN NOT NULL DEFAULT false,
    descricao1 VARCHAR(50) NOT NULL DEFAULT '',
    descricao2 VARCHAR(150) NOT NULL DEFAULT '',
    descricao3 VARCHAR(200) NOT NULL DEFAULT '',
    destaque BOOLEAN NOT NULL DEFAULT false,
    internet BOOLEAN NOT NULL DEFAULT false,
    cartorio VARCHAR(30) NOT NULL DEFAULT '',
    matricula VARCHAR(30) NOT NULL DEFAULT '',
    jornal BOOLEAN NOT NULL DEFAULT false,
    nome_jornal VARCHAR(50),
    data_jornal DATE,
    pagina_jornal VARCHAR(50),
    cod_cidade INTEGER NOT NULL DEFAULT 0,
    valor_venda DECIMAL(10,2) NOT NULL DEFAULT 0,
    valor_temporada DECIMAL(10,2) NOT NULL DEFAULT 0,
    ocupacao BOOLEAN NOT NULL DEFAULT false,
    situacao_financeira BOOLEAN NOT NULL DEFAULT false,
    comissao_locacao DECIMAL(10,2) NOT NULL DEFAULT 0,
    comissao_venda DECIMAL(10,2) NOT NULL DEFAULT 0,
    nome_condominio VARCHAR(50),
    comissao_aluguel DECIMAL(10,2) NOT NULL DEFAULT 0,
    data_hora TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de relacionamento proprietários-imóveis
CREATE TABLE proprietarios_imoveis (
    id SERIAL PRIMARY KEY,
    id_imovel INTEGER NOT NULL DEFAULT 0,
    id_proprietario INTEGER NOT NULL DEFAULT 0
);

-- =====================================================
-- TABELAS DE PESSOAS ESPECIALIZADAS
-- =====================================================

-- Tabela de corretores (apenas dados específicos de corretagem)
CREATE TABLE corretores (
    id SERIAL PRIMARY KEY,
    codigo INTEGER NOT NULL,
    id_pessoa INTEGER NOT NULL REFERENCES pessoa(idpessoa),
    id_usuario INTEGER, -- Referência ao usuário do sistema
    id_status INTEGER REFERENCES status_corretores(id),
    creci VARCHAR(10), -- Registro do corretor
    ativo BOOLEAN NOT NULL DEFAULT true
);

-- Tabela de inquilinos (apenas dados específicos de locação)
CREATE TABLE inquilinos (
    id SERIAL PRIMARY KEY,
    codigo INTEGER NOT NULL DEFAULT 0,
    id_pessoa INTEGER NOT NULL REFERENCES pessoa(idpessoa),
    id_imovel INTEGER NOT NULL DEFAULT 0,
    situacao BOOLEAN NOT NULL DEFAULT false,
    data_situacao DATE DEFAULT '1900-01-01',
    dependentes INTEGER NOT NULL DEFAULT 0,
    id_endereco_correspondencia INTEGER REFERENCES enderecos(id),
    correspondencia BOOLEAN NOT NULL DEFAULT false,
    observacoes VARCHAR(255) NOT NULL DEFAULT '',
    inicio_contrato DATE NOT NULL DEFAULT '1900-01-01',
    fim_contrato DATE NOT NULL DEFAULT '1900-01-01',
    periodo INTEGER NOT NULL DEFAULT 0,
    id_indice INTEGER NOT NULL DEFAULT 0,
    ultimo_reajuste DATE NOT NULL DEFAULT '1900-01-01',
    inicio_reajuste DATE NOT NULL DEFAULT '1900-01-01',
    debita_ir BOOLEAN NOT NULL DEFAULT false,
    judicial BOOLEAN NOT NULL DEFAULT false,
    aluguel DECIMAL(10,2) NOT NULL DEFAULT 0,
    dia INTEGER NOT NULL DEFAULT 0,
    competencia BOOLEAN NOT NULL DEFAULT false,
    dia_limite INTEGER NOT NULL DEFAULT 0,
    taxa BOOLEAN NOT NULL DEFAULT false,
    abono BOOLEAN NOT NULL DEFAULT false,
    tipo_abono BOOLEAN DEFAULT false,
    valor_abono DECIMAL(10,2) NOT NULL DEFAULT 0,
    multa BOOLEAN DEFAULT false,
    tipo_multa BOOLEAN DEFAULT false,
    valor_multa DECIMAL(10,2) DEFAULT 0,
    cobranca BOOLEAN NOT NULL DEFAULT false,
    caucao BOOLEAN NOT NULL DEFAULT false,
    valor_caucao DECIMAL(10,2) NOT NULL DEFAULT 0,
    dados_imovel VARCHAR(255) NOT NULL DEFAULT '',
    ja_fiador BOOLEAN NOT NULL DEFAULT false,
    seguradora VARCHAR(100) NOT NULL DEFAULT '',
    apolice VARCHAR(20) NOT NULL DEFAULT '',
    basica DECIMAL(10,2) NOT NULL DEFAULT 0,
    multa_seguro DECIMAL(10,2) NOT NULL DEFAULT 0,
    danos DECIMAL(10,2) NOT NULL DEFAULT 0,
    vencimento_seguro DATE NOT NULL DEFAULT '1900-01-01',
    obs_baixa VARCHAR(255) NOT NULL DEFAULT '',
    id_usuario_baixa INTEGER, -- Referência ao usuário do sistema
    valor_aluguel_inicial DECIMAL(10,2) NOT NULL DEFAULT 0,
    valor_aluguel_anterior DECIMAL(10,2) NOT NULL DEFAULT 0,
    tipo_juros BOOLEAN DEFAULT false,
    valor_juros DECIMAL(10,2) NOT NULL DEFAULT 1,
    cod_corretora INTEGER NOT NULL DEFAULT 0,
    seg_forma_pagamento VARCHAR(30) NOT NULL DEFAULT '',
    seg_parcelas INTEGER NOT NULL DEFAULT 0,
    dia_inicio_periodo INTEGER DEFAULT 0,
    empresa_titular INTEGER DEFAULT 0,
    numero_titular VARCHAR(20) DEFAULT '',
    valor_titular DECIMAL(10,2) NOT NULL DEFAULT 0,
    vencimento_titular DATE NOT NULL DEFAULT '1901-01-01',
    obs_titular VARCHAR(200) DEFAULT '',
    ult_indice_reajuste VARCHAR(50),
    tipo_impressao BOOLEAN NOT NULL DEFAULT false,
    id_conta_bancaria INTEGER REFERENCES contas_bancarias(id),
    rec_email BOOLEAN NOT NULL DEFAULT false,
    valor_desconto_anterior DECIMAL(10,2) NOT NULL DEFAULT 0,
    valor_desconto DECIMAL(10,2) NOT NULL DEFAULT 0,
    reajusta_desconto BOOLEAN NOT NULL DEFAULT false,
    codigo_desconto INTEGER NOT NULL DEFAULT 0,
    obs_caucao VARCHAR(255) NOT NULL DEFAULT '',
    obs_judicial VARCHAR(255) NOT NULL DEFAULT '',
    data_bloqueio DATE NOT NULL DEFAULT '1900-01-01'
);

-- Tabela de fiadores (apenas dados específicos de fiança)
CREATE TABLE fiadores (
    id SERIAL PRIMARY KEY,
    codigo INTEGER NOT NULL,
    id_pessoa INTEGER NOT NULL REFERENCES pessoa(idpessoa),
    atividade VARCHAR(25) NOT NULL DEFAULT '',
    admissao DATE,
    renda DECIMAL(10,2) NOT NULL DEFAULT 0,
    outros VARCHAR(50) NOT NULL DEFAULT '',
    empresa_fiador VARCHAR(50) NOT NULL DEFAULT '',
    id_endereco_empresa INTEGER REFERENCES enderecos(id),
    id_telefone_empresa INTEGER REFERENCES telefones(id),
    conjuge_trabalha BOOLEAN NOT NULL DEFAULT false,
    conjuge_telefone_empresa VARCHAR(20) NOT NULL DEFAULT '',
    motivo_fianca VARCHAR(150) NOT NULL DEFAULT '',
    ja_sao_fiadores BOOLEAN NOT NULL DEFAULT false,
    conjuge_empresa_fiador VARCHAR(50) NOT NULL DEFAULT '',
    conjuge_trabalha_fiador BOOLEAN NOT NULL DEFAULT false,
    conjuge_telefone_empresa_fiador VARCHAR(20) NOT NULL DEFAULT '',
    regime_casamento VARCHAR(35) NOT NULL DEFAULT '',
    estado_civil_outro BOOLEAN NOT NULL DEFAULT false
);

-- Tabela de relacionamento fiador-inquilino
CREATE TABLE fiador_inquilino (
    id SERIAL PRIMARY KEY,
    id_inquilino INTEGER NOT NULL DEFAULT 0,
    id_fiador INTEGER NOT NULL DEFAULT 0
);

-- Tabela de locadores (apenas dados específicos de locação)
CREATE TABLE locadores (
    id SERIAL PRIMARY KEY,
    codigo INTEGER NOT NULL DEFAULT 0,
    id_pessoa INTEGER NOT NULL REFERENCES pessoa(idpessoa),
    id_endereco INTEGER REFERENCES enderecos(id),
    dependentes INTEGER NOT NULL DEFAULT 0,
    forma_retirada BOOLEAN NOT NULL DEFAULT false,
    a_credito_de VARCHAR(50) NOT NULL DEFAULT '',
    id_conta_bancaria INTEGER REFERENCES contas_bancarias(id),
    cpf_favor VARCHAR(20) NOT NULL DEFAULT '',
    observacoes VARCHAR(255) NOT NULL DEFAULT '',
    cobra_cpmf BOOLEAN NOT NULL DEFAULT false,
    situacao BOOLEAN NOT NULL DEFAULT false,
    dia_retirada INTEGER NOT NULL DEFAULT 0,
    cod_contabil INTEGER NOT NULL DEFAULT 0,
    dimob BOOLEAN NOT NULL DEFAULT false,
    cod_cidade INTEGER NOT NULL DEFAULT 0,
    etiqueta BOOLEAN NOT NULL DEFAULT true,
    apura_cpmf BOOLEAN NOT NULL DEFAULT false,
    cpmf_individual BOOLEAN NOT NULL DEFAULT false,
    cobra_tarifa_recibo BOOLEAN NOT NULL DEFAULT false,
    data_fechamento DATE NOT NULL DEFAULT '1900-01-01',
    cod_cedente DECIMAL(10,2) DEFAULT 0,
    numero_usado INTEGER DEFAULT 0,
    numero_final INTEGER DEFAULT 0,
    carteira VARCHAR(4),
    numero_inicial INTEGER DEFAULT 0,
    flash VARCHAR(4),
    data_situacao DATE,
    carencia INTEGER NOT NULL DEFAULT 0,
    multa_itau BOOLEAN NOT NULL DEFAULT false,
    mora_diaria BOOLEAN NOT NULL DEFAULT false,
    protesto INTEGER NOT NULL DEFAULT 0,
    tipo_dia INTEGER NOT NULL DEFAULT 0,
    dias_protesto CHAR(2) NOT NULL DEFAULT '0',
    layout_remessa CHAR(3) NOT NULL DEFAULT '400',
    tipo_remessa BOOLEAN NOT NULL DEFAULT false,
    nao_gerar_judicial BOOLEAN NOT NULL DEFAULT false
);

-- =====================================================
-- TABELAS DE LOCAÇÃO
-- =====================================================

-- Tabela de locações
CREATE TABLE locacoes (
    id SERIAL PRIMARY KEY,
    codigo INTEGER NOT NULL DEFAULT 0,
    id_imovel INTEGER NOT NULL DEFAULT 0,
    id_inquilino INTEGER NOT NULL DEFAULT 0,
    id_locador INTEGER NOT NULL DEFAULT 0,
    data_inicio DATE NOT NULL DEFAULT '1900-01-01',
    data_fim DATE NOT NULL DEFAULT '1900-01-01',
    valor DECIMAL(10,2) NOT NULL DEFAULT 0,
    dia_vencimento INTEGER NOT NULL DEFAULT 0,
    observacoes VARCHAR(255) NOT NULL DEFAULT ''
);

-- Tabela de lançamentos de locação
CREATE TABLE lancamentos_locacao (
    id SERIAL PRIMARY KEY,
    id_inquilino INTEGER NOT NULL DEFAULT 0,
    mes_ano VARCHAR(7) NOT NULL DEFAULT '',
    conta INTEGER NOT NULL DEFAULT 0,
    referencia VARCHAR(50) NOT NULL DEFAULT '',
    valor DECIMAL(10,2) NOT NULL DEFAULT 0
);

-- Tabela de lançamentos de centro de custo
CREATE TABLE lancamentos_centro_custo (
    id SERIAL PRIMARY KEY,
    codigo INTEGER NOT NULL,
    data DATE NOT NULL DEFAULT '0000-00-00',
    contrapartida INTEGER NOT NULL DEFAULT 0,
    tipo_referencia BOOLEAN NOT NULL DEFAULT false,
    referencia INTEGER NOT NULL DEFAULT 0,
    conta INTEGER NOT NULL DEFAULT 0,
    historico VARCHAR(200) NOT NULL DEFAULT '',
    valor DECIMAL(10,2) NOT NULL DEFAULT 0,
    sinal CHAR(1) NOT NULL DEFAULT '',
    recibo INTEGER NOT NULL DEFAULT 0,
    id_imovel INTEGER NOT NULL DEFAULT 0,
    lancamento_cpmf INTEGER NOT NULL DEFAULT 0,
    id_inquilino INTEGER NOT NULL DEFAULT 0,
    lancamento_conta_pagar INTEGER NOT NULL DEFAULT 0,
    data_hora TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    id_repasse INTEGER NOT NULL DEFAULT 0,
    processo VARCHAR(50)
);

-- Tabela de históricos
CREATE TABLE historicos (
    id SERIAL PRIMARY KEY,
    tipo_referencia INTEGER NOT NULL DEFAULT 0,
    referencia INTEGER NOT NULL DEFAULT 0,
    data DATE NOT NULL DEFAULT '0000-00-00',
    texto VARCHAR(255),
    id_usuario INTEGER, -- Referência ao usuário do sistema
    data_hora TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de jornal
CREATE TABLE jornal (
    id SERIAL PRIMARY KEY,
    nome VARCHAR(50)
);

-- Tabela de ocorrências
CREATE TABLE ocorrencias (
    id SERIAL PRIMARY KEY,
    codigo INTEGER NOT NULL,
    data DATE NOT NULL DEFAULT '0000-00-00',
    tipo INTEGER NOT NULL DEFAULT 0,
    descricao VARCHAR(200) NOT NULL DEFAULT '',
    id_usuario INTEGER, -- Referência ao usuário do sistema
    data_hora TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de placas
CREATE TABLE placas (
    id SERIAL PRIMARY KEY,
    codigo INTEGER NOT NULL,
    placa VARCHAR(20) NOT NULL DEFAULT '',
    data DATE NOT NULL DEFAULT '0000-00-00',
    observacoes VARCHAR(255) NOT NULL DEFAULT ''
);

-- Tabela de planos
CREATE TABLE planos (
    id SERIAL PRIMARY KEY,
    codigo INTEGER NOT NULL,
    nome VARCHAR(50) NOT NULL DEFAULT '',
    valor DECIMAL(10,2) NOT NULL DEFAULT 0
);

-- Tabela de pretendentes
CREATE TABLE pretendentes (
    id SERIAL PRIMARY KEY,
    codigo INTEGER NOT NULL,
    id_pessoa INTEGER NOT NULL REFERENCES pessoa(idpessoa),
    id_imovel INTEGER NOT NULL DEFAULT 0,
    data DATE NOT NULL DEFAULT '0000-00-00',
    hora VARCHAR(5) NOT NULL DEFAULT '',
    observacoes VARCHAR(255) NOT NULL DEFAULT '',
    id_usuario INTEGER, -- Referência ao usuário do sistema
    data_hora TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de recibos
CREATE TABLE recibos (
    id SERIAL PRIMARY KEY,
    codigo INTEGER NOT NULL,
    data DATE NOT NULL DEFAULT '0000-00-00',
    id_inquilino INTEGER NOT NULL DEFAULT 0,
    valor DECIMAL(10,2) NOT NULL DEFAULT 0,
    referencia VARCHAR(50) NOT NULL DEFAULT '',
    observacoes VARCHAR(255) NOT NULL DEFAULT '',
    id_usuario INTEGER, -- Referência ao usuário do sistema
    data_hora TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de repasses
CREATE TABLE repasses (
    id SERIAL PRIMARY KEY,
    codigo INTEGER NOT NULL,
    data DATE NOT NULL DEFAULT '0000-00-00',
    id_locador INTEGER NOT NULL DEFAULT 0,
    valor DECIMAL(10,2) NOT NULL DEFAULT 0,
    observacoes VARCHAR(255) NOT NULL DEFAULT ''
);

-- Tabela de requisições
CREATE TABLE requisicoes (
    id SERIAL PRIMARY KEY,
    codigo INTEGER NOT NULL,
    data DATE NOT NULL DEFAULT '0000-00-00',
    tipo INTEGER NOT NULL DEFAULT 0,
    descricao VARCHAR(200) NOT NULL DEFAULT '',
    id_usuario INTEGER, -- Referência ao usuário do sistema
    data_hora TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- ÍNDICES PARA OTIMIZAÇÃO
-- =====================================================

-- Índices para tabela pessoa
CREATE INDEX idx_pessoa_nome ON pessoa(nome);
CREATE INDEX idx_pessoa_tipo ON pessoa(tipo_pessoa);
CREATE INDEX idx_pessoa_status ON pessoa(status);

-- Índices para tabela dados_pessoais
CREATE INDEX idx_dados_pessoais_cpf ON dados_pessoais(cpf);
CREATE INDEX idx_dados_pessoais_rg ON dados_pessoais(rg);

-- Índices para tabela enderecos
CREATE INDEX idx_enderecos_pessoa ON enderecos(id_pessoa);
CREATE INDEX idx_enderecos_cep ON enderecos(cep);
CREATE INDEX idx_enderecos_cidade ON enderecos(cidade);
CREATE INDEX idx_enderecos_estado ON enderecos(estado);

-- Índices para tabela telefones
CREATE INDEX idx_telefones_pessoa ON telefones(id_pessoa);
CREATE INDEX idx_telefones_numero ON telefones(numero);

-- Índices para tabela emails
CREATE INDEX idx_emails_pessoa ON emails(id_pessoa);
CREATE INDEX idx_emails_email ON emails(email);

-- Índices para tabela contas_bancarias
CREATE INDEX idx_contas_bancarias_pessoa ON contas_bancarias(id_pessoa);
CREATE INDEX idx_contas_bancarias_banco ON contas_bancarias(id_banco);

-- Índices para tabela documentos
CREATE INDEX idx_documentos_pessoa ON documentos(id_pessoa);
CREATE INDEX idx_documentos_tipo ON documentos(id_tipo);

-- Índices para tabela imoveis
CREATE INDEX idx_imoveis_codigo ON imoveis(codigo);
CREATE INDEX idx_imoveis_situacao ON imoveis(situacao);
CREATE INDEX idx_imoveis_tipo ON imoveis(tipo_imovel);

-- Índices para tabela corretores
CREATE INDEX idx_corretores_codigo ON corretores(codigo);
CREATE INDEX idx_corretores_pessoa ON corretores(id_pessoa);
CREATE INDEX idx_corretores_creci ON corretores(creci);

-- Índices para tabela inquilinos
CREATE INDEX idx_inquilinos_codigo ON inquilinos(codigo);
CREATE INDEX idx_inquilinos_pessoa ON inquilinos(id_pessoa);
CREATE INDEX idx_inquilinos_imovel ON inquilinos(id_imovel);

-- Índices para tabela fiadores
CREATE INDEX idx_fiadores_codigo ON fiadores(codigo);
CREATE INDEX idx_fiadores_pessoa ON fiadores(id_pessoa);

-- Índices para tabela locadores
CREATE INDEX idx_locadores_codigo ON locadores(codigo);
CREATE INDEX idx_locadores_pessoa ON locadores(id_pessoa);

-- Índices para tabela locacoes
CREATE INDEX idx_locacoes_codigo ON locacoes(codigo);
CREATE INDEX idx_locacoes_imovel ON locacoes(id_imovel);
CREATE INDEX idx_locacoes_inquilino ON locacoes(id_inquilino);
CREATE INDEX idx_locacoes_locador ON locacoes(id_locador);

-- =====================================================
-- COMENTÁRIOS FINAIS
-- =====================================================

/*
PRINCIPAIS CORREÇÕES APLICADAS NA V3:

1. REMOÇÃO DE CAMPOS DUPLICADOS:
   - Removido campo 'cadastro' de inquilinos (já existe em pessoa.dt_cadastro)
   - Removido campo 'cadastro' de locadores (já existe em pessoa.dt_cadastro)

2. ADIÇÃO DA TABELA CORRETORES:
   - Tabela corretores normalizada
   - Apenas dados específicos de corretagem (creci, status, ativo)
   - Relacionamento com pessoa e usuário do sistema

3. MELHORIA NOS RELACIONAMENTOS:
   - Tabela documentos criada para múltiplos tipos de documentos
   - Relacionamentos 1:N para emails, telefones, endereços, contas bancárias, PIX
   - Cada pessoa pode ter múltiplos registros em cada tabela

4. TABELA STATUS_CORRETORES:
   - Tabela de referência para status dos corretores
   - Normalização adequada

5. ESTRUTURA COMPLETA DE PESSOAS:
   - pessoa (dados básicos)
   - dados_pessoais (CPF, RG, etc.)
   - enderecos (múltiplos endereços)
   - telefones (múltiplos telefones)
   - emails (múltiplos emails)
   - contas_bancarias (múltiplas contas)
   - pix (múltiplas chaves PIX)
   - documentos (múltiplos documentos)

6. TABELAS ESPECIALIZADAS:
   - corretores (dados de corretagem)
   - inquilinos (dados de locação)
   - fiadores (dados de fiança)
   - locadores (dados de locação)

7. ÍNDICES OTIMIZADOS:
   - Índices adicionais para novas tabelas
   - Índices para relacionamentos importantes
*/ 