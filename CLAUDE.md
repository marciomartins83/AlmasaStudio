# CLAUDE.md — Regras do Projeto AlmasaStudio

> Sistema de gestao imobiliaria | Symfony 7.2 | PHP 8.2+ | PostgreSQL 14+

**Toda documentacao detalhada esta em `docs/LIVRO_ALMASA.md` — a fonte unica da verdade.**

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

### Manutencao do livro (OBRIGATORIO)

O livro DEVE ser mantido enxuto. Livro grande = nao leio = nao entendo = faco merda.

1. **Changelog e temporario.** Mantenha no maximo 3-5 versoes recentes no changelog.
2. **Consolidar periodicamente.** Entradas antigas do changelog DEVEM ser incorporadas nos capitulos relevantes e removidas do changelog.
3. **Capitulos sao a verdade permanente.** Toda informacao util do changelog vira conteudo de capitulo.
4. **Indice atualizado.** Se criar subsecao nova num capitulo, atualizar o indice.
5. **Conciso.** Capitulos descrevem o estado atual, nao historico detalhado. Uma frase clara > paragrafo longo.

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

## Regra 11 — Selects com mais de 15 itens DEVEM ser autocomplete

**PROIBIDO:** EntityType ou `<select>` com `{% for %}` que possa ter mais de 15 opcoes.

**OBRIGATORIO:** Usar autocomplete (typeahead/search-as-you-type) com:
- Endpoint em `AutocompleteController.php` (SQL direto, LIMIT 20)
- `public/js/generic_autocomplete.js` (JS generico reutilizavel)
- `templates/_partials/autocomplete_field.html.twig` (partial Twig)
- HiddenType no FormType (`mapped: false`)

**Filtro `?apenas=almasa`:** Para contas bancarias proprias (id_pessoa IS NULL).

---

## Regra 12 — Deploy completo = commit + push + VPS + webpack

Apos QUALQUER mudanca:
1. `php -l` nos arquivos PHP alterados
2. `git add` + `git commit` + `git push`
3. Deploy VPS: `git pull` + `php bin/console cache:clear --env=prod`
4. Se alterou `assets/js/`: rodar `npx encore production` na VPS (browser carrega o build, nao o source)
5. Atualizar capitulo relevante do livro

**NUNCA entregar sem deploy completo.**

---

## Regra 13 — Entities boolean NOT NULL DEVEM ter default

Todo campo `#[ORM\Column(type: 'boolean')]` DEVE ter valor default na propriedade:
```php
private bool $campo = false;  // CORRETO
private bool $campo;          // ERRADO — causa not-null violation no INSERT
```

---

## Regra 14 — Nao perguntar o obvio

Se a acao e claramente necessaria (instalar pacote, corrigir bug, fazer deploy), faz direto.
Nao perguntar "quer que eu faca X?" quando X e a unica opcao logica.

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

**Ultima atualizacao:** 2026-04-07 (v6.29.0)
**Mantenedor:** Marcio Martins
