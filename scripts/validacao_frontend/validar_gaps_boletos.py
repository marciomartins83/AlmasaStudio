#!/usr/bin/env python3
"""
Validacao real (clique/digitacao/screenshot via CDP) dos Gaps 4-6 do modulo
de boletos: canal de envio (Administracao/Correio/E-mail), lote por faixa
de vencimento e bloqueio/aviso de inadimplente.

Pre-requisito: 3 cobrancas de teste ja inseridas via dbal:run-sql (ver
comentario no final do script) usando contratos reais ja existentes no
banco local restaurado da VPS (22594, 22679, 22933).

Uso:
    php -S 127.0.0.1:8000 -t public/ &
    python3 scripts/validacao_frontend/validar_gaps_boletos.py
"""
import asyncio
import sys

from cdp_lib import Session

BASE_URL = "http://127.0.0.1:8000"
EMAIL = "marcioramos1983@gmail.com"
SENHA = "ValidacaoLocal2026!"

CONTRATO_CANAL_TESTE = 22594  # canal EMAIL -> CORREIO -> volta pra EMAIL


async def login(s):
    await s.navigate("/login")
    await s.click('input[name="email"]')
    await s.type_text(EMAIL)
    await s.click('input[name="password"]')
    await s.type_text(SENHA)
    await s.click('button[type="submit"]')
    await s.wait_load()
    if not await s.body_contains(EMAIL):
        print("FALHA: login nao autenticou")
        sys.exit(1)


async def main():
    async with Session(base_url=BASE_URL) as s:
        report = []

        await login(s)

        # --- Gap 4a: select de canal de envio no formulario de contrato ---
        await s.navigate(f"/contrato/edit/{CONTRATO_CANAL_TESTE}")
        await s.click_by_text("Configura")  # aba/painel "Configurações" (mesmo padrao busca-avancada)
        await s.screenshot("01_form_contrato_aba_config.png")
        await s.select_option("#canal-envio", "CORREIO")
        await s.screenshot("02_canal_selecionado_correio.png")
        await s.click_by_text("Salvar")
        await s.wait_load()

        await s.navigate(f"/contrato/show/{CONTRATO_CANAL_TESTE}")
        await s.screenshot("03_contrato_show_canal_correio.png")
        texto_show = await s.get_text("body")
        report.append(("contrato_show_exibe_canal_correio", "Correio" in texto_show))

        # Reverte para nao deixar dado de teste alterado no contrato real
        await s.navigate(f"/contrato/edit/{CONTRATO_CANAL_TESTE}")
        await s.click_by_text("Configura")
        await s.select_option("#canal-envio", "EMAIL")
        await s.click_by_text("Salvar")
        await s.wait_load()

        # --- Gap 4b + 6: listagem de pendentes mostra badge de canal e de inadimplente ---
        # Precisa forcar status[] (AGUARDANDO_ENTREGA nao entra no filtro-padrao de
        # pendentes) e um periodo largo (o filtro-padrao sem parametros usa
        # data_vencimento = hoje, que nao bate com nenhuma das cobrancas de teste).
        TODOS_STATUS = "status%5B%5D=PENDENTE&status%5B%5D=BOLETO_GERADO&status%5B%5D=AGUARDANDO_ENTREGA"
        PERIODO_MES_TESTE = "vencimento_inicio=2026-07-01&vencimento_fim=2026-07-31"
        await s.navigate(f"/cobranca/pendentes?{PERIODO_MES_TESTE}&{TODOS_STATUS}")
        await s.screenshot("04_pendentes_lista_geral.png")
        corpo_lista = await s.get_text("table")
        report.append(("badge_canal_correio_aparece_na_lista", "Correio" in corpo_lista))
        report.append(("badge_inadimplente_aparece_na_lista", "Inadimplente" in corpo_lista))

        # --- Gap 5: filtro de lote por faixa de vencimento ---
        # O JS dos botoes de faixa reenvia o MESMO form (com o status[] ja marcado
        # na pagina atual), so' sobrescrevendo vencimento_inicio/vencimento_fim.
        await s.click_by_text("1 a 10")
        await asyncio.sleep(0.8)
        await s.wait_load()
        await s.screenshot("05_filtro_faixa_1_10.png")
        texto_faixa1 = await s.get_text("table")
        report.append(("faixa_1_10_mostra_so_cobranca_correio", "05/07/2026" in texto_faixa1 and "15/07/2026" not in texto_faixa1))

        await s.navigate(f"/cobranca/pendentes?{PERIODO_MES_TESTE}&{TODOS_STATUS}")
        await s.click_by_text("11 a 20")
        await asyncio.sleep(0.8)
        await s.wait_load()
        await s.screenshot("06_filtro_faixa_11_20.png")
        texto_faixa2 = await s.get_text("table")
        report.append(("faixa_11_20_mostra_so_cobranca_email", "15/07/2026" in texto_faixa2 and "05/07/2026" not in texto_faixa2))

        # --- Gap 4c: marcar entregue (cobranca ja em AGUARDANDO_ENTREGA) ---
        # So' existe 1 cobranca AGUARDANDO_ENTREGA nesta massa de teste, entao
        # ".btn-marcar-entregue" sem qualificador de id basta (o id real da
        # cobranca e' dinamico - depende da sequence do banco local).
        await s.navigate(f"/cobranca/pendentes?{PERIODO_MES_TESTE}&{TODOS_STATUS}")
        await s.screenshot("07_antes_marcar_entregue.png")
        await s.click(".btn-marcar-entregue")
        aceitou = await s.accept_dialog()
        report.append(("confirm_dialog_marcar_entregue_apareceu", aceitou))
        await asyncio.sleep(1.0)

        # A cobranca marcada como entregue vira ENVIADO, entao some da lista
        # filtrada por PENDENTE/BOLETO_GERADO/AGUARDANDO_ENTREGA (comportamento
        # correto). Para confirmar a transicao, filtra especificamente por
        # status[]=ENVIADO e verifica que o Correio aparece la'.
        await s.navigate(f"/cobranca/pendentes?{PERIODO_MES_TESTE}&status%5B%5D=ENVIADO")
        await s.screenshot("08_apos_marcar_entregue.png")
        texto_apos = await s.get_text("table")
        report.append(("cobranca_status_enviado_apos_marcar_entregue", "Correio" in texto_apos and "Enviado" in texto_apos))

        await s.navigate(f"/cobranca/pendentes?{PERIODO_MES_TESTE}&{TODOS_STATUS}")
        texto_sumiu = await s.get_text("table")
        report.append(("cobranca_some_da_lista_aguardando_entrega_apos_marcar", "Correio" not in texto_sumiu))

        print("\n=== RESULTADO DA VALIDACAO (Gaps 4-6) ===")
        for chave, ok in report:
            status = "OK" if ok else "FALHOU"
            print(f"[{status}] {chave}")

        if not all(ok for _, ok in report):
            sys.exit(1)


if __name__ == "__main__":
    asyncio.run(main())

# Fixture usada (inserida manualmente antes de rodar este script):
#
# INSERT INTO contratos_cobrancas (contrato_id, competencia, periodo_inicio,
#   periodo_fim, data_vencimento, valor_aluguel, valor_total, status,
#   canal_envio, created_at, updated_at) VALUES
# (22594, '2026-07', '2026-06-11', '2026-07-10', '2026-07-15', 1000.00, 1000.00, 'PENDENTE', 'EMAIL', now(), now()),
# (22933, '2026-07', '2026-06-11', '2026-07-10', '2026-07-25', 900.00, 900.00, 'PENDENTE', 'EMAIL', now(), now()),
# (22679, '2026-07', '2026-06-11', '2026-07-10', '2026-07-05', 1100.00, 1100.00, 'AGUARDANDO_ENTREGA', 'CORREIO', now(), now());
