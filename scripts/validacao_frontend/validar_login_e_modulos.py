#!/usr/bin/env python3
"""
Exemplo pronto de uso da cdp_lib: login real + navegacao pelos modulos
principais (Pessoas com busca, Imoveis, Contratos, Financeiro), com
screenshot real em cada etapa.

Uso:
    # 1. Subir o servidor da app (ajustar porta se necessario):
    php -S 127.0.0.1:8099 -t public/ &

    # 2. Rodar este script (ajustar EMAIL/SENHA/BASE_URL abaixo se mudou):
    python3 scripts/validacao_frontend/validar_login_e_modulos.py

Screenshots vao para scripts/validacao_frontend/screenshots/ (git-ignorado,
ver .gitignore do projeto — nao sao artefato para versionar, sao prova
descartavel de validacao local).

Adaptar este arquivo para novos fluxos de validacao (outro modulo, outra
sequencia de cliques) em vez de reescrever o driver do zero.
"""
import asyncio
import sys

from cdp_lib import Session

BASE_URL = "http://127.0.0.1:8099"
EMAIL = "marcioramos1983@gmail.com"
SENHA = "ValidacaoLocal2026!"  # senha de validacao local, resetada via:
# php bin/console app:create-admin marcioramos1983@gmail.com 'ValidacaoLocal2026!'


async def main():
    async with Session(base_url=BASE_URL) as s:
        # --- Login ---
        await s.navigate("/login")
        await s.screenshot("01_login.png")

        await s.click('input[name="email"]')
        await s.type_text(EMAIL)
        await s.click('input[name="password"]')
        await s.type_text(SENHA)
        await s.screenshot("02_login_preenchido.png")

        await s.click('button[type="submit"]')
        await s.wait_load()
        await s.screenshot("03_dashboard.png")

        logado = await s.body_contains(EMAIL)
        print(f"login_efetuado: {logado}")
        if not logado:
            print("FALHA: nao autenticou. Ver 03_dashboard.png.")
            sys.exit(1)

        # --- Pessoas: abrir busca avancada (collapse fechado por padrao),
        # digitar um nome real e clicar em Buscar (Enter nao submete o form) ---
        await s.navigate("/pessoa/")
        await s.screenshot("04_pessoas_index.png")

        await s.click_by_text("Busca Avan")
        await asyncio.sleep(0.5)
        await s.click('input[name="nome"]')
        await s.type_text("MARCELO")
        await s.screenshot("05_pessoas_busca_preenchida.png")

        await s.click_by_text("Buscar")
        await s.wait_load()
        await s.screenshot("06_pessoas_busca_resultado.png")

        resultado_tem_marcelo = "MARCELO" in (await s.get_text("table")).upper()
        print(f"busca_pessoas_filtrou_corretamente: {resultado_tem_marcelo}")

        # --- Outros modulos principais ---
        await s.navigate("/imovel/")
        await s.screenshot("07_imoveis_index.png")

        await s.navigate("/contrato/")
        await s.screenshot("08_contratos_index.png")

        await s.navigate("/financeiro/")
        await s.screenshot("09_financeiro_index.png")

        print("Validacao concluida. Screenshots em scripts/validacao_frontend/screenshots/")


if __name__ == "__main__":
    asyncio.run(main())
