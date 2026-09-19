#!/usr/bin/env python3
"""Gera o PDF de prova de validacao dos Gaps 4-6 do modulo de boletos (canal de
envio, lote por faixa de vencimento, bloqueio/aviso de inadimplente), usando os
screenshots reais capturados via CDP em validar_gaps_boletos.py.

Uso (com o venv que ja tem reportlab instalado neste projeto):
    /home/marc/noteHome/AlmasaStudio/almasaVideoBoletos/almasa/.venv-whisper/bin/python3 \
        scripts/validacao_frontend/gerar_pdf_gaps_boletos.py
"""
from pathlib import Path

from reportlab.lib.pagesizes import A4
from reportlab.lib.units import cm
from reportlab.lib import colors
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.platypus import (
    SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle, Image,
    KeepTogether,
)
from reportlab.lib.utils import ImageReader

PASTA = Path(__file__).parent
SHOTS = PASTA / "screenshots"

SECOES = [
    ("1. Gap 4 — Canal de Envio do Boleto", [
        ("01_form_contrato_aba_config.png",
         "Aba \"Configurações\" do formulário de contrato",
         "Contrato #22594 (real, cópia da produção). Campo novo <select> \"Canal de "
         "Envio\" substitui o antigo checkbox isolado \"envia_email\", com as 3 opções "
         "do sistema legado: Administração, Correio e E-mail."),
        ("02_canal_selecionado_correio.png",
         "Canal alterado para \"Correio\" — clique real no select",
         "Seleção feita via evento de change real disparado pelo CDP, simulando a "
         "interação de um usuário escolhendo a opção no dropdown."),
        ("03_contrato_show_canal_correio.png",
         "Tela de detalhes confirma o canal salvo",
         "Após \"Salvar Contrato\" (POST real, CSRF válido), a página de detalhes do "
         "contrato mostra o badge \"Correio\" — confirma que o valor foi persistido "
         "no banco e não é só um estado de front-end."),
    ]),
    ("2. Gap 4 + Gap 6 — Listagem de Cobranças Pendentes", [
        ("04_pendentes_lista_geral.png",
         "Badges de canal e de inadimplência na listagem",
         "Tela /cobranca/pendentes com 3 cobranças de teste (dados sintéticos "
         "vinculados a contratos reais, removidos após a validação): uma com canal "
         "Correio (badge cinza \"Correio\"), e uma vinculada a um locatário com "
         "lançamento financeiro anterior em aberto (badge vermelho \"Inadimplente\" "
         "— aviso visual, não bloqueia envio manual)."),
    ]),
    ("3. Gap 5 — Lote por Faixa de Vencimento", [
        ("05_filtro_faixa_1_10.png",
         "Botão de atalho \"1 a 10\" — clique real",
         "Preenche automaticamente o período de vencimento do mês corrente (dia 1 a "
         "10) e resubmete o formulário. Mostra somente a cobrança com vencimento "
         "dia 05, escondendo as de outras faixas."),
        ("06_filtro_faixa_11_20.png",
         "Botão de atalho \"11 a 20\" — clique real",
         "Mesmo mecanismo para a segunda faixa do mês (11 a 20) — mostra somente a "
         "cobrança com vencimento dia 15, replicando o comportamento de lotes do "
         "sistema legado (UnionData Imobilis)."),
    ]),
    ("4. Gap 4 — Marcar Entregue (canais Correio/Administração)", [
        ("07_antes_marcar_entregue.png",
         "Cobrança em \"Aguardando Entrega\" — botão \"Marcar Entregue\" disponível",
         "Boleto do canal Correio, gerado mas não enviado por e-mail (fica pronto "
         "para impressão manual). Botão específico aparece só para cobranças nesse "
         "status."),
        ("08_apos_marcar_entregue.png",
         "Após confirmar o dialog nativo — status muda para \"Enviado\"",
         "Clique real dispara um window.confirm() nativo do navegador (aceito via "
         "CDP Page.handleJavaScriptDialog), depois um fetch real com CSRF que "
         "transiciona o status no banco de AGUARDANDO_ENTREGA para ENVIADO."),
    ]),
]

RODAPE = (
    "Validação executada em ambiente local (banco cópia da produção) via automação real de "
    "browser (Chrome DevTools Protocol — clique, digitação, seleção de dropdown, dialog "
    "nativo e screenshot reais, sem Playwright/Selenium). Testes automatizados PHPUnit "
    "também passaram (CobrancaContratoServiceTest, ImoveisContratosTest, "
    "ContratosCobrancasTest) e o schema Doctrine foi validado (doctrine:schema:validate OK). "
    "Durante esta validação foram encontrados e corrigidos 4 bugs pré-existentes graves "
    "(desde dezembro/2025) que impediam o fluxo de geração/envio de boleto e a tela de "
    "cobranças pendentes de funcionar de verdade em qualquer ambiente — só descobertos "
    "porque esta foi a primeira vez que esses fluxos foram exercitados com clique real em "
    "vez de apenas revisão de código. Detalhes completos no livro de memória do projeto "
    "(capítulo \"Módulo Boletos e Cobrança\"). Os dados de teste (cobranças sintéticas "
    "vinculadas a contratos reais) foram removidos do banco após a validação — o banco "
    "local permanece fiel à produção."
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

    pdf_path = PASTA / "Gaps 4-6 Boletos - Validacao.pdf"
    doc = SimpleDocTemplate(
        str(pdf_path), pagesize=A4,
        leftMargin=1.8 * cm, rightMargin=1.8 * cm,
        topMargin=1.6 * cm, bottomMargin=1.6 * cm,
        title="AlmasaStudio - Gaps 4-6 Boletos - Validacao", author="AlmasaStudio",
    )
    story = [
        Paragraph("AlmasaStudio — Gaps 4-6 de Boletos: Validação", st_title),
        Paragraph(
            "Canal de envio (Administração/Correio/E-mail), lote por faixa de vencimento "
            "e bloqueio/aviso de inadimplente — últimos 3 gaps do comparativo com o "
            "sistema legado (UnionData Imobilis), implementados e validados de ponta a "
            "ponta com clique, digitação e screenshot reais no frontend local.",
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
