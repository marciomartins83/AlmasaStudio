#!/usr/bin/env python3
"""
Biblioteca de validacao de frontend via Chrome DevTools Protocol (CDP) puro.

Por que isso existe: regra do projeto (CLAUDE.md Regra 15) proibe usar
Playwright/Selenium neste projeto (framework pesado, baixa +100-300MB de
browser embutido so pra rodar). Este driver usa o `chromium` ja instalado
no sistema (apt: pacote `chromium`) + a lib padrao `websockets` do Python
(sem instalar nada extra na maioria das maquinas dev).

Cobre exatamente o que a Regra 15 exige: clique real (Input.dispatchMouseEvent),
digitacao real (Input.dispatchKeyEvent), screenshot real (Page.captureScreenshot) —
nada de simulacao HTTP com curl.

## Como usar (receita basica)

    import asyncio
    from cdp_lib import Session

    async def main():
        async with Session(base_url="http://127.0.0.1:8099") as s:
            await s.navigate("/login")
            await s.screenshot("01_login.png")
            await s.click('input[name="email"]')
            await s.type_text("usuario@exemplo.com")
            await s.click('input[name="password"]')
            await s.type_text("senha")
            await s.click('button[type="submit"]')
            await s.wait_load()
            await s.screenshot("02_dashboard.png")

    asyncio.run(main())

## Pre-requisitos

- `chromium` instalado no sistema (`apt install chromium` — ja vem em muitas
  imagens Debian/Ubuntu dev; checar com `which chromium`).
- Modulo `websockets` do Python (`python3 -c "import websockets"` — se faltar,
  `pip install --user websockets`, e' leve, nao baixa nenhum browser).
- O servidor da aplicacao (`php -S 127.0.0.1:PORTA -t public/` ou equivalente)
  rodando ANTES de chamar o script.

## Gotchas ja descobertos neste projeto (nao redescobrir)

- **Paineis "Busca Avancada" sao Bootstrap collapse fechados por padrao.**
  Precisa `click_by_text("Busca Avan")` para abrir ANTES de conseguir
  clicar/digitar nos campos internos (senao o elemento existe no DOM mas
  esta invisivel/fora da area clicavel).
- **Enter nao submete os formularios de busca avancada deste projeto.**
  Tem que clicar no botao "Buscar" de verdade (`click_by_text("Buscar")`).
  Isso ja foi validado assim no modulo Pessoas (2026-07-12).
- **Login usa SameOriginCsrfTokenManager (Symfony 7.2)** — o campo
  `_csrf_token` do form de login aparece como `value="csrf-token"` (nao e'
  bug, e' comportamento documentado no Cap 13 do livro). Um browser real
  (como este driver) resolve isso sozinho ao enviar Origin/Referer — so' de'
  problema se voce tentar simular o POST via curl sem esses headers.
- **`document.querySelectorAll('table tr').length` NAO e' um bom oraculo**
  de "quantos registros existem" neste projeto — varias paginas retornam a
  MESMA contagem (17) independente do conteudo real (provavelmente por causa
  de tabelas ocultas no menu/toolbar do profiler). Para provar que uma busca
  filtrou de verdade, LEIA o texto/nome renderizado na tabela (ex:
  `get_text(cdp, "table")` e cheque se o valor esperado aparece), nao so' a
  contagem de `<tr>`. O screenshot e' a prova mais confiavel.
"""
import asyncio
import base64
import itertools
import json
import os
import subprocess
import time
import urllib.request

import websockets

_id_counter = itertools.count(1)


class CDP:
    """Cliente CDP minimo: manda comando, espera a resposta com o mesmo id,
    empilha qualquer outra mensagem (evento) numa fila para wait_event()."""

    def __init__(self, ws):
        self.ws = ws
        self.events = asyncio.Queue()
        self.last_dialog_accepted = False

    async def send(self, method, params=None, timeout=20):
        msg_id = next(_id_counter)
        await self.ws.send(json.dumps({"id": msg_id, "method": method, "params": params or {}}))
        while True:
            try:
                raw = await asyncio.wait_for(self.ws.recv(), timeout=timeout)
            except asyncio.TimeoutError:
                raise RuntimeError(f"CDP.send('{method}') sem resposta apos {timeout}s — chromium travado ou dialog nao tratado")
            data = json.loads(raw)
            if data.get("id") == msg_id:
                return data.get("result", {})
            if data.get("method") == "Page.javascriptDialogOpening":
                # Aceitar AQUI DENTRO do loop, nao depois do send() retornar: um
                # clique que dispara confirm()/alert() sincrono (ex:
                # confirmarMarcarEntregue em cobranca.js) faz o Chrome suspender
                # a resposta do proprio comando Input.dispatchMouseEvent ate' o
                # dialog ser resolvido — se a gente so' tenta aceitar depois que
                # o click() retornou, trava para sempre (bug real encontrado na
                # validacao dos gaps 4-6 de boletos, 2026-07-12: o clique em
                # "Marcar Entregue" pendurava o script indefinidamente).
                self.last_dialog_accepted = True
                await self.ws.send(json.dumps({
                    "id": next(_id_counter),
                    "method": "Page.handleJavaScriptDialog",
                    "params": {"accept": True},
                }))
                continue
            await self.events.put(data)

    async def wait_event(self, name, timeout=15):
        deadline = time.time() + timeout
        while time.time() < deadline:
            try:
                data = await asyncio.wait_for(self.events.get(), timeout=deadline - time.time())
            except asyncio.TimeoutError:
                break
            if data.get("method") == name:
                return data
        return None

    def drain(self):
        """Descarta qualquer evento acumulado na fila (ex: Page.loadEventFired de uma
        navegacao anterior ainda nao consumido). Chamar logo apos disparar uma acao que
        vai causar navegacao (navigate/click em submit) e ANTES de aguardar o evento
        fresco correspondente — senao wait_event() pode devolver um evento velho e o
        codigo segue em frente achando que a pagina nova ja carregou quando na verdade
        ainda nao navegou (bug real encontrado e corrigido durante a validacao de
        2026-07-12: a fila acumulava eventos ao longo de varias navegacoes em sequencia
        num mesmo script)."""
        while not self.events.empty():
            try:
                self.events.get_nowait()
            except asyncio.QueueEmpty:
                break


class Session:
    """Uso: `async with Session(base_url=...) as s: await s.navigate(...)`.

    Sobe um chromium headless isolado (user-data-dir proprio, descartado ao
    sair), abre uma aba, conecta via CDP. `shot_dir` default e' uma pasta
    `./screenshots` ao lado do script chamador."""

    def __init__(self, base_url, cdp_port=9333, shot_dir=None, profile_dir=None, headless=True):
        self.base_url = base_url.rstrip("/")
        self.cdp_port = cdp_port
        self.shot_dir = shot_dir or os.path.join(os.path.dirname(os.path.abspath(__file__)), "screenshots")
        self.profile_dir = profile_dir or f"/tmp/cdp-lib-profile-{os.getpid()}"
        self.headless = headless
        self.proc = None
        self.ws = None
        self.cdp = None

    async def __aenter__(self):
        os.makedirs(self.shot_dir, exist_ok=True)
        os.makedirs(self.profile_dir, exist_ok=True)
        # --disable-back-forward-cache: sem isso, revisitar uma URL ja' visitada
        # (comum num script que navega pra frente e pra tras entre 2-3 telas)
        # pode ser restaurada do bfcache do Chrome em vez de recarregar de
        # verdade — e' rapido, mas NUNCA dispara Page.loadEventFired de novo,
        # entao wait_load()/navigate() ficam presos ate' estourar o timeout de
        # 15s em TODA navegacao subsequente pra uma URL repetida (bug real
        # encontrado na validacao dos gaps 4-6 de boletos, 2026-07-12: cada
        # navegacao repetida pagava 15s de espera morta, somando minutos no
        # script todo sem nenhum erro aparente).
        args = ["chromium", "--no-sandbox", "--disable-gpu",
                "--disable-back-forward-cache",
                "--window-size=1366,900", f"--remote-debugging-port={self.cdp_port}",
                f"--user-data-dir={self.profile_dir}", "about:blank"]
        if self.headless:
            args.insert(1, "--headless=new")
        self.proc = subprocess.Popen(args, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
        for _ in range(50):
            try:
                urllib.request.urlopen(f"http://127.0.0.1:{self.cdp_port}/json/version", timeout=1)
                break
            except Exception:
                time.sleep(0.2)
        else:
            raise RuntimeError("chromium nao respondeu na porta de debug — 'which chromium' esta instalado?")

        with urllib.request.urlopen(f"http://127.0.0.1:{self.cdp_port}/json") as r:
            tabs = json.loads(r.read())
        ws_url = next(t["webSocketDebuggerUrl"] for t in tabs if t.get("type") == "page")

        self.ws = await websockets.connect(ws_url, max_size=50 * 1024 * 1024).__aenter__()
        self.cdp = CDP(self.ws)
        await self.cdp.send("Page.enable")
        await self.cdp.send("Runtime.enable")
        await self.cdp.send("Network.enable")
        return self

    async def __aexit__(self, *exc):
        if self.ws:
            await self.ws.close()
        if self.proc:
            self.proc.terminate()
            try:
                self.proc.wait(timeout=5)
            except Exception:
                self.proc.kill()

    # --- navegacao ---

    async def navigate(self, path_or_url):
        url = path_or_url if path_or_url.startswith("http") else f"{self.base_url}{path_or_url}"
        self.cdp.drain()
        await self.cdp.send("Page.navigate", {"url": url})
        await self.cdp.wait_event("Page.loadEventFired", timeout=15)
        await asyncio.sleep(0.3)

    async def wait_load(self, timeout=15):
        """Chamar logo apos um clique que dispara navegacao (ex: submit de form).
        click()/click_by_text() ja fazem drain() da fila de eventos ANTES de disparar
        o clique, entao aqui e' seguro so' esperar o Page.loadEventFired fresco."""
        await self.cdp.wait_event("Page.loadEventFired", timeout=timeout)
        await asyncio.sleep(0.3)

    # --- interacao ---

    async def _measure(self, finder_js):
        """Roda um finder JS (que resolve `el`) e devolve seu rect + a altura da
        viewport, sem tentar rolar a pagina (ver _ensure_visible)."""
        res = await self.cdp.send("Runtime.evaluate", {
            "expression": f"""
                (function() {{
                    {finder_js}
                    if (!el) return null;
                    const r = el.getBoundingClientRect();
                    return JSON.stringify({{x: r.x, y: r.y, w: r.width, h: r.height, vh: window.innerHeight}});
                }})()
            """,
            "returnByValue": True,
        })
        val = res.get("result", {}).get("value")
        return json.loads(val) if val else None

    async def _ensure_visible(self, finder_js, not_found_msg):
        """Mede o elemento e, se estiver fora da viewport, rola ate' o centro
        dele via um evento de wheel SINTETICO DE VERDADE antes de reler o rect.

        Por que nao usar `el.scrollIntoView()`/`scrollTo()` direto em JS: neste
        projeto o Turbo (`@symfony/ux-turbo`, ligado globalmente via
        assets/controllers.json) assume o controle da rolagem da pagina, e
        qualquer scroll disparado por JS puro e' silenciosamente revertido -
        so' um evento de input real (wheel) rola de fato (bug real encontrado
        durante a validacao dos gaps 4-6 de boletos, 2026-07-12: o clique em
        "Salvar Contrato" e em "Marcar Entregue", ambos abaixo da dobra, nao
        surtia NENHUM efeito e nao levantava excecao — o clique sintetico saia
        em coordenadas fora da area realmente renderizada da janela headless)."""
        rect = await self._measure(finder_js)
        if rect is None:
            raise RuntimeError(not_found_msg)

        centro_y = rect["y"] + rect["h"] / 2
        if 0 <= centro_y <= rect["vh"]:
            return rect

        delta = centro_y - rect["vh"] / 2
        await self.cdp.send("Input.dispatchMouseEvent", {
            "type": "mouseWheel", "x": rect["vh"] / 2, "y": rect["vh"] / 2,
            "deltaX": 0, "deltaY": delta,
        })
        await asyncio.sleep(0.2)

        rect = await self._measure(finder_js)
        if rect is None:
            raise RuntimeError(not_found_msg)
        return rect

    async def _box(self, selector):
        finder_js = f"const el = document.querySelector({json.dumps(selector)});"
        return await self._ensure_visible(finder_js, f"elemento nao encontrado: {selector}")

    async def _box_by_text(self, text, tags="button, a, [role=\"button\"], .card-header"):
        finder_js = f"""
            const needle = {json.dumps(text)}.toLowerCase();
            const els = Array.from(document.querySelectorAll({json.dumps(tags)}));
            const el = els.find(e => e.innerText && e.innerText.toLowerCase().includes(needle));
        """
        return await self._ensure_visible(finder_js, f"elemento com texto nao encontrado: {text}")

    async def _click_box(self, box):
        # drain ANTES do clique: qualquer Page.loadEventFired parado na fila de uma
        # navegacao anterior nao pode "vazar" para o wait_load() que o chamador for
        # fazer em seguida (bug real de 2026-07-12 — ver CDP.drain()).
        self.cdp.drain()
        x, y = box["x"] + box["w"] / 2, box["y"] + box["h"] / 2
        await self.cdp.send("Input.dispatchMouseEvent", {"type": "mouseMoved", "x": x, "y": y})
        await self.cdp.send("Input.dispatchMouseEvent", {"type": "mousePressed", "x": x, "y": y, "button": "left", "clickCount": 1})
        await self.cdp.send("Input.dispatchMouseEvent", {"type": "mouseReleased", "x": x, "y": y, "button": "left", "clickCount": 1})

    async def click(self, selector):
        """Clique real (evento de mouse) num elemento via seletor CSS."""
        await self._click_box(await self._box(selector))

    async def click_by_text(self, text, tags="button, a, [role=\"button\"], .card-header"):
        """Clique real no primeiro elemento (button/a/card-header) cujo texto contenha `text`."""
        await self._click_box(await self._box_by_text(text, tags))

    async def type_text(self, text):
        """Digitacao real, caractere a caractere (Input.dispatchKeyEvent) — dispara
        os eventos de teclado que o JS da pagina realmente escuta."""
        for ch in text:
            await self.cdp.send("Input.dispatchKeyEvent", {"type": "keyDown", "text": ch})
            await self.cdp.send("Input.dispatchKeyEvent", {"type": "keyUp", "text": ch})

    async def press_enter(self):
        await self.cdp.send("Input.dispatchKeyEvent", {"type": "keyDown", "windowsVirtualKeyCode": 13, "key": "Enter"})
        await self.cdp.send("Input.dispatchKeyEvent", {"type": "keyUp", "windowsVirtualKeyCode": 13, "key": "Enter"})

    async def select_option(self, selector, value):
        """Seleciona uma opcao de um <select> nativo e dispara o evento 'change' real
        (equivalente a clicar no elemento e escolher a opcao — CDP nao abre o dropdown
        nativo do SO em modo headless, entao setamos o valor e disparamos o evento que
        qualquer listener JS real escutaria)."""
        await self.cdp.send("Runtime.evaluate", {
            "expression": f"""
                (function() {{
                    const el = document.querySelector({json.dumps(selector)});
                    if (!el) throw new Error('select nao encontrado: {selector}');
                    el.value = {json.dumps(value)};
                    el.dispatchEvent(new Event('change', {{bubbles: true}}));
                }})()
            """,
        })

    # --- leitura / prova ---

    async def screenshot(self, name):
        res = await self.cdp.send("Page.captureScreenshot", {"format": "png"})
        path = os.path.join(self.shot_dir, name)
        with open(path, "wb") as f:
            f.write(base64.b64decode(res["data"]))
        return path

    async def get_text(self, selector):
        res = await self.cdp.send("Runtime.evaluate", {
            "expression": f"(document.querySelector({json.dumps(selector)}) || {{}}).innerText || ''",
            "returnByValue": True,
        })
        return res.get("result", {}).get("value", "")

    async def body_contains(self, needle):
        text = await self.get_text("body")
        return needle in text

    async def accept_dialog(self, timeout=5):
        """Confirma se um window.confirm()/alert() nativo foi aberto e aceito
        durante o clique mais recente.

        O aceite em si acontece dentro de CDP.send() (ver comentario la'):
        um clique que dispara confirm() sincrono trava a resposta do proprio
        comando de input ate' o dialog ser resolvido, entao nao da' pra
        esperar o evento SO' DEPOIS que click() ja' retornou — a essa altura
        ja' e' tarde demais, o dialog precisa ser aceito enquanto o comando
        de clique ainda esta' pendente. Este metodo so' le a flag que
        CDP.send() marcou nesse meio-tempo."""
        if self.cdp.last_dialog_accepted:
            self.cdp.last_dialog_accepted = False
            return True
        evento = await self.cdp.wait_event("Page.javascriptDialogOpening", timeout=timeout)
        if evento:
            await self.cdp.send("Page.handleJavaScriptDialog", {"accept": True})
        return evento is not None
