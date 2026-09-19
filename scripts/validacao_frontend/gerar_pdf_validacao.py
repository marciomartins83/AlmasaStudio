#!/usr/bin/env python3
"""Gera o PDF de prova de validacao do nucleo financeiro (Indices Economicos,
Tabela de Imposto de Renda, Reajuste Automatico de Contratos), usando os
screenshots reais capturados via CDP em validar_reajuste_financeiro.py.

Uso (com o venv que ja tem reportlab instalado neste projeto):
    /home/marc/noteHome/AlmasaStudio/almasaVideoBoletos/almasa/.venv-whisper/bin/python3 \
        scripts/validacao_frontend/gerar_pdf_validacao.py
"""
from pathlib import Path

from reportlab.lib.pagesizes import A4
from reportlab.lib.units import cm
from reportlab.lib import colors
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.platypus import (
    SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle, Image,
    KeepTogether, PageBreak,
)
from reportlab.lib.utils import ImageReader

PASTA = Path(__file__).parent
SHOTS = PASTA / "screenshots"

SECOES = [
    ("1. Indices Economicos (IGPM / Tribunal de Justica)", [
        ("01a_indice_2025-05_preenchido.png",
         "Cadastro do indice base (competencia 2025-05)",
         "Tela nova em Financeiro > Indices e Tabelas > Indices Economicos. "
         "Campo digitado de verdade via teclado simulado (CDP Input.dispatchKeyEvent): "
         "tipo IGPM, competencia 2025-05, valor do indice 100,000000."),
        ("02a_indice_2026-05_preenchido.png",
         "Cadastro do indice atual (competencia 2026-05)",
         "Segundo registro cadastrado da mesma forma: IGPM, competencia 2026-05, "
         "valor do indice 110,500000 — necessario para calcular o fator de reajuste "
         "(indice atual / indice base)."),
        ("03_indices_economicos_lista.png",
         "Listagem confirma os dois registros",
         "CRUD completo (criar/listar/editar/excluir) funcionando com busca e "
         "ordenacao — os dois indices cadastrados aparecem na tabela."),
    ]),
    ("2. Tabela de Imposto de Renda (faixas progressivas)", [
        ("04a_faixa_ir_preenchida.png",
         "Faixa 1 — isenta (R$ 0 a R$ 2.000, aliquota 0%)",
         "Tela nova em Financeiro > Indices e Tabelas > Tabela de Imposto de Renda. "
         "Substitui o metodo antigo que sempre retornava zero de retencao de IR."),
        ("07_tabela_ir_lista.png",
         "As 3 faixas cadastradas (0-2000: 0%, 2000-3000: 10%, acima de 3000: 20%)",
         "Listagem confirma a tabela progressiva completa, pronta para ser usada no "
         "calculo real de retencao de IR em PrestacaoContasService::calcularRetencaoIR()."),
    ]),
    ("3. Reajuste Automatico de Contrato (IGPM)", [
        ("08_reajustes_pendentes_lista.png",
         "Tela nova: Contratos > Reajustes Pendentes",
         "Lista contratos com data de reajuste vencida. Contrato de teste (ID 22483, "
         "DANIELE MOLINA DE CARVALHO, valor R$ 1.042,00) aparece pendente."),
        ("09_modal_simulacao_reajuste.png",
         "Simulacao do reajuste — clique real no botao \"Simular\"",
         "Modal mostra o calculo completo antes de gravar qualquer coisa: indice IGPM, "
         "competencia base 2025-05 para atual 2026-05, fator aplicado 1,105000, "
         "valor atual R$ 1.042,00 -> valor novo R$ 1.151,41. Nada e' salvo ainda "
         "nesse ponto — e' so' preview."),
        ("11_reajustes_pendentes_apos_aplicar.png",
         "Apos clicar \"Aplicar Reajuste\" — contrato sai da lista de pendentes",
         "Reajuste gravado de verdade no banco: valor_contrato atualizado para "
         "R$ 1.151,41, data do proximo reajuste avancada em 12 meses, e um "
         "registro de historico (contratos_reajustes_historico) criado com todos "
         "os valores do calculo, para auditoria."),
    ]),
]

RODAPE = (
    "Validacao executada em ambiente local (banco copia da producao) via automacao real de "
    "browser (Chrome DevTools Protocol — clique, digitacao e screenshot reais, sem Playwright/"
    "Selenium). Testes automatizados PHPUnit tambem passaram para os 3 servicos novos "
    "(IndiceEconomicoService, TabelaImpostoRendaService, ContratoService::simularReajuste/"
    "aplicarReajuste) e para a integracao com PrestacaoContasService. "
    "Os dados de teste (indices e faixas de IR sinteticos, valor do contrato de teste) foram "
    "revertidos ao estado original apos a validacao — o banco local permanece fiel a producao."
)


def build():
    ss = getSampleStyleSheet()
    st_title = ParagraphStyle("t", parent=ss["Title"], fontSize=20,
                               textColor=colors.HexColor("#1a3c5e"))
    st_sub = ParagraphStyle("s", parent=ss["Normal"], fontSize=10.5,
                             textColor=colors.HexColor("#555555"), spaceAfter=6, leading=14)
    st_sec = ParagraphStyle("sec", parent=ss["Heading2"], fontSize=14,
                             textColor=colors.HexColor("#25597f"),
                             spaceBefore=10, spaceAfter=8)
    st_step = ParagraphStyle("step", parent=ss["BodyText"], fontSize=11,
                              leading=14, textColor=colors.HexColor("#1a3c5e"))
    st_desc = ParagraphStyle("desc", parent=ss["BodyText"], fontSize=9.3,
                              leading=12.5, spaceBefore=2)
    st_footer = ParagraphStyle("footer", parent=ss["BodyText"], fontSize=9,
                                leading=13, textColor=colors.HexColor("#444444"))

    pdf_path = PASTA / "Nucleo Financeiro - Validacao.pdf"
    doc = SimpleDocTemplate(
        str(pdf_path), pagesize=A4,
        leftMargin=1.8 * cm, rightMargin=1.8 * cm,
        topMargin=1.6 * cm, bottomMargin=1.6 * cm,
        title="AlmasaStudio - Nucleo Financeiro - Validacao", author="AlmasaStudio",
    )
    story = [
        Paragraph("AlmasaStudio — Núcleo Financeiro: Validação", st_title),
        Paragraph(
            "Índices Econômicos (IGPM/TJ), Tabela de Imposto de Renda e Reajuste Automático "
            "de Contratos — funcionalidades que faltavam no sistema comparado ao legado "
            "(UnionData Imobilis), implementadas e validadas de ponta a ponta com clique, "
            "digitação e screenshot reais no frontend local.",
            st_sub),
        Spacer(1, 0.2 * cm),
    ]

    img_w = 11 * cm
    for titulo_sec, steps in SECOES:
        story.append(Paragraph(titulo_sec, st_sec))
        for filename, titulo, desc in steps:
            png = SHOTS / filename
            iw, ih = ImageReader(str(png)).getSize()
            img = Image(str(png), width=img_w, height=img_w * ih / iw)
            txt = [
                Paragraph(f"<b>{titulo}</b>", st_step),
                Paragraph(desc, st_desc),
            ]
            row = Table([[img, txt]], colWidths=[img_w + 0.3 * cm, 5.4 * cm])
            row.setStyle(TableStyle([
                ("VALIGN", (0, 0), (-1, -1), "TOP"),
                ("LEFTPADDING", (0, 0), (-1, -1), 0),
                ("RIGHTPADDING", (0, 0), (-1, -1), 4),
                ("TOPPADDING", (0, 0), (-1, -1), 3),
                ("BOTTOMPADDING", (0, 0), (-1, -1), 3),
                ("LINEBELOW", (0, 0), (-1, -1), 0.4, colors.HexColor("#dddddd")),
            ]))
            story.append(KeepTogether([row, Spacer(1, 0.3 * cm)]))

    story.append(Spacer(1, 0.3 * cm))
    story.append(Paragraph("Notas de validação", st_sec))
    story.append(Paragraph(RODAPE, st_footer))

    doc.build(story)
    total = sum(len(s[1]) for s in SECOES)
    print(f"OK: {pdf_path.name}  ({total} passos, {len(SECOES)} secoes)")


if __name__ == "__main__":
    build()
