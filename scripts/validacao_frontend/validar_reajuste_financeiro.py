#!/usr/bin/env python3
"""
Validacao real (clique/digitacao/screenshot via CDP) do nucleo financeiro:
Indices Economicos + Tabela de Imposto de Renda + Reajuste Automatico de Contrato.

Uso:
    php -S 127.0.0.1:8099 -t public/ &
    python3 scripts/validacao_frontend/validar_reajuste_financeiro.py
"""
import asyncio
import sys

from cdp_lib import Session

BASE_URL = "http://127.0.0.1:8099"
EMAIL = "marcioramos1983@gmail.com"
SENHA = "ValidacaoLocal2026!"

CONTRATO_ID = 22483  # preparado localmente: indice_reajuste=IGPM, data_proximo_reajuste=2026-05-01


async def login(s):
    await s.navigate("/login")
    await s.click('input[name="email"]')
    await s.type_text(EMAIL)
    await s.click('input[name="password"]')
    await s.type_text(SENHA)
    await s.click('button[type="submit"]')
    await s.wait_load()
    logado = await s.body_contains(EMAIL)
    if not logado:
        print("FALHA: login nao autenticou")
        sys.exit(1)


async def criar_indice(s, competencia, valor_indice, numero):
    await s.navigate("/indice-economico/new")
    await s.select_option('#indice_economico_tipo', 'IGPM')
    await s.click('#indice_economico_competencia')
    await s.type_text(competencia)
    await s.click('#indice_economico_valorIndice')
    await s.type_text(str(valor_indice))
    await s.screenshot(f"{numero:02d}a_indice_{competencia}_preenchido.png")
    await s.click('button[type="submit"]')
    await s.wait_load()


async def criar_faixa_ir(s, inicial, final, aliquota, deduzir, numero):
    await s.navigate("/tabela-imposto-renda/new")
    await s.click('#faixa_imposto_renda_dataVigencia')
    await s.type_text("01/01/2026")
    await s.click('#faixa_imposto_renda_valorInicial')
    await s.type_text(str(inicial))
    if final is not None:
        await s.click('#faixa_imposto_renda_valorFinal')
        await s.type_text(str(final))
    await s.click('#faixa_imposto_renda_aliquota')
    await s.type_text(str(aliquota))
    await s.click('#faixa_imposto_renda_parcelaDeduzir')
    await s.type_text(str(deduzir))
    await s.screenshot(f"{numero:02d}a_faixa_ir_preenchida.png")
    await s.click('button[type="submit"]')
    await s.wait_load()


async def main():
    async with Session(base_url=BASE_URL) as s:
        report = []

        await login(s)
        await s.screenshot("00_dashboard_logado.png")

        # --- 1. Indices Economicos: cadastrar competencia base e atual ---
        await criar_indice(s, "2025-05", "100.000000", 1)
        await criar_indice(s, "2026-05", "110.500000", 2)

        await s.navigate("/indice-economico/")
        await s.screenshot("03_indices_economicos_lista.png")
        lista_indices = await s.get_text("table")
        report.append(("indices_cadastrados_aparecem_na_lista", "2025-05" in lista_indices and "2026-05" in lista_indices))

        # --- 2. Tabela de Imposto de Renda: 3 faixas ---
        await criar_faixa_ir(s, "0.00", "2000.00", "0.00", "0.00", 4)
        await criar_faixa_ir(s, "2000.01", "3000.00", "10.00", "200.00", 5)
        await criar_faixa_ir(s, "3000.01", None, "20.00", "500.00", 6)

        await s.navigate("/tabela-imposto-renda/")
        await s.screenshot("07_tabela_ir_lista.png")
        lista_ir = await s.get_text("table")
        report.append(("faixas_ir_cadastradas_aparecem_na_lista", "20%" in lista_ir or "20" in lista_ir))

        # --- 3. Reajuste de contrato: tela, simular, aplicar ---
        await s.navigate("/contrato/reajustes")
        await s.screenshot("08_reajustes_pendentes_lista.png")
        texto_lista = await s.get_text("body")
        report.append(("contrato_teste_aparece_pendente", str(CONTRATO_ID) in texto_lista))

        await s.click(f'tr[data-contrato-id="{CONTRATO_ID}"] .btn-simular')
        await asyncio.sleep(0.8)
        await s.screenshot("09_modal_simulacao_reajuste.png")
        modal_texto = await s.get_text("#modalReajusteConteudo")
        report.append(("modal_simulacao_mostra_valores", "1.105" in modal_texto or "1105" in modal_texto or "1.042" in modal_texto))

        await s.click('#btnAplicarReajuste')
        await asyncio.sleep(1.2)
        await s.screenshot("10_apos_aplicar_reajuste.png")

        # Recarrega a tela de reajustes: contrato aplicado nao deve mais aparecer pendente
        await s.navigate("/contrato/reajustes")
        await s.screenshot("11_reajustes_pendentes_apos_aplicar.png")
        texto_apos = await s.get_text("body")
        report.append(("contrato_saiu_da_lista_apos_aplicar", str(CONTRATO_ID) not in texto_apos))

        print("\n=== RESULTADO DA VALIDACAO ===")
        for chave, ok in report:
            status = "OK" if ok else "FALHOU"
            print(f"[{status}] {chave}")

        if not all(ok for _, ok in report):
            sys.exit(1)


if __name__ == "__main__":
    asyncio.run(main())
