# Changelog — AlmasaStudio

## [6.32.0] - 2026-04-14

### Corrigido
- `AlmasaPlanoContasType::createSaldoTransformer()` — classe anônima usava `?string`/`string` nos métodos `transform`/`reverseTransform`, incompatível com `DataTransformerInterface::transform(mixed): mixed` no PHP 8.4. Fatal Error impedia abrir qualquer página de edição do plano de contas (HTTP 500). Corrigido para assinatura `mixed`/`mixed` com cast `(string)` interno.
