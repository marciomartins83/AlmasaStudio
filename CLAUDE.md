# CLAUDE.md — Regras do Projeto AlmasaStudio

> Sistema de gestao imobiliaria | Symfony 7.2 | PHP 8.2+ | PostgreSQL 14+

**Toda documentacao detalhada esta em `docs/LIVRO_ALMASA.md` — a fonte unica da verdade.**

---

## Metodologia Multi-Agente

- **Opus 4.6** = Engenheiro (orquestrador, decisoes arquiteturais)
- **Haiku 4.5** = Mestre de Obras (subagente via Task tool, executa e monitora)
- **GPT-OSS 20B** = Pedreiro (executor via Aider + OpenRouter)

A chave OpenRouter esta salva no MEMORY.md (privado, fora do git).
Modelo Aider: `openrouter/openai/gpt-oss-20b`

---

## Regra 0 — Ao abrir sessao, iniciar reminder

Ao iniciar qualquer sessao de trabalho:
1. Rodar `scripts/automacao/reminder_update_docs.sh` em background
2. Ler a **Sinopse** e o **Indice** do livro (`docs/LIVRO_ALMASA.md`)
3. Rodar `php bin/console doctrine:schema:validate`

---

## Regra 1 — Ler o livro antes de agir

Antes de modificar qualquer modulo, leia o capitulo correspondente no livro.
O livro tem 14 capitulos + changelog. Consulte o indice para navegar.

---

## Regra 2 — A estrutura do livro e sagrada

O `docs/LIVRO_ALMASA.md` segue esta estrutura fixa:

```
SINOPSE (status atual, versao, proxima tarefa)
INDICE
Cap 1 — Historico e Evolucao
Cap 2 — Arquitetura Tecnica
Cap 3 — Mapa de Arquivos
Cap 4 — Modulo Pessoas
Cap 5 — Modulo Imoveis
Cap 6 — Modulo Contratos
Cap 7 — Modulo Financeiro
Cap 8 — Modulo Boletos e Cobranca
Cap 9 — Modulo Relatorios e Prestacao de Contas
Cap 10 — Cadastros Auxiliares e Configuracoes
Cap 11 — Banco de Dados
Cap 12 — Frontend
Cap 13 — Licoes Aprendidas
Cap 14 — Plano de Testes
CHANGELOG
```

NUNCA remova ou reordene capitulos. Pode adicionar novos ao final (antes do Changelog).

---

## Regra 3 — Como atualizar o livro

Apos QUALQUER mudanca no codigo:
1. Atualize o capitulo relevante do livro
2. Adicione entrada no Changelog (fundo do livro)
3. Atualize a Sinopse se mudou versao, status ou proxima tarefa

Formato changelog: [Keep a Changelog](https://keepachangelog.com/pt-BR/1.0.0/)
Categorias: Adicionado | Alterado | Descontinuado | Removido | Corrigido | Seguranca

---

## Regra 4 — Nao criar documentos avulsos

**PROIBIDO criar:**
- `CORRECAO_*.md`, `MIGRATION_*.md`, `FIX_*.md`, `UPDATE_*.md`
- Qualquer `.md` temporario fora do livro

**PERMITIDO:**
- `CLAUDE.md` — este arquivo (so regras)
- `docs/LIVRO_ALMASA.md` — fonte unica da verdade

---

## Regra 5 — MEMORY.md e complementar

O `~/.claude/projects/*/memory/MEMORY.md` e para notas rapidas entre sessoes:
- Estado atual de trabalho em progresso
- Bugs sendo investigados
- Contexto de sessao anterior

NAO duplicar conteudo do livro no MEMORY.md.

---

## Regra 6 — Schema Doctrine DEVE bater (Lei Suprema)

```bash
php bin/console doctrine:schema:validate
```

**SE O SCHEMA NAO BATER, PARA TUDO E CORRIGE. IMEDIATAMENTE.**

1. SEMPRE rodar ao iniciar qualquer tarefa
2. SE `[ERROR]` → PARAR tarefa atual
3. Diagnosticar com `php bin/console doctrine:schema:update --dump-sql`
4. Corrigir entidades (banco e fonte da verdade)
5. Validar ate `[OK]` no Mapping

**Aceitaveis:** DROP SEQUENCE, ALTER DROP DEFAULT, ALTER INDEX RENAME
**NAO aceitaveis:** ALTER TYPE, ALTER SET NOT NULL, DROP/ADD COLUMN

---

## Regra 7 — Thin Controller / Fat Service

**Controllers:** Recebem Request, validam form, chamam Service, retornam Response.
PROIBIDO: logica de negocio, transacoes, flush(), persist(), remove().

**Services:** Toda logica de negocio, transacoes, persistencia.

**Repositories:** DQL/SQL complexo. NUNCA colocar DQL em Controller ou Service.

---

## Regra 8 — JavaScript 100% modular

**PROIBIDO:** JS inline, onclick/onchange, `<script>` com codigo em .twig

**OBRIGATORIO:** Todo JS em `assets/js/` — modular por funcionalidade.

**EXCECAO:** Variaveis globais (`window.ROUTES`, `window.FORM_IDS`) no final do .twig para passar dados do backend.

---

## Regra 9 — Token CSRF unico

Token `ajax_global` para TODAS as requisicoes AJAX.
```javascript
headers: {
    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content,
    'X-Requested-With': 'XMLHttpRequest',
    'Content-Type': 'application/json'
}
```

---

## Regra 10 — Padrao CRUD templates

- Block: `{% block content %}` (NAO `body`)
- Breadcrumb: `_partials/breadcrumb.html.twig`
- Icones: FontAwesome (`fas fa-*`)
- Tabela: `table-striped table-hover`, `thead class="table-dark"`
- Twig: SEMPRE camelCase (`{{ item.codigoInterno }}`)
- Booleanos: `isAtivo()` (nao `getAtivo()`)

Para detalhes e exemplos completos, ver **Cap 12** do livro.

---

## Comandos Essenciais

```bash
# Desenvolvimento
composer install && npm install
symfony server:start
npm run dev | npm run build | npm run watch

# Banco de Dados
php bin/console doctrine:schema:validate
php bin/console make:migration
php bin/console doctrine:migrations:migrate

# Debug
php bin/console cache:clear
php bin/console debug:router
php bin/console debug:container
```

---

## Organizacao de Pastas

```
AlmasaStudio/
├── CLAUDE.md                    — REGRAS (este arquivo)
├── docs/
│   └── LIVRO_ALMASA.md          — FONTE UNICA DA VERDADE
├── src/
│   ├── Controller/              — Thin Controllers
│   ├── Service/                 — Fat Services
│   ├── Entity/                  — 82 entidades Doctrine
│   ├── Repository/              — 51 repositorios
│   ├── Form/                    — FormTypes
│   └── Command/                 — 2 commands
├── assets/js/                   — JavaScript modular
├── templates/                   — Twig templates (151)
├── scripts/
│   └── automacao/
│       └── reminder_update_docs.sh
├── logs/                        — Logs do reminder
└── config/                      — Symfony config
```

---

**Ultima atualizacao:** 2026-02-19
**Mantenedor:** Marcio Martins
