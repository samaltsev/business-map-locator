# Review migration safety

## Роль
Ты — senior WordPress/PHP/JavaScript engineer, работающий в репозитории NBH Business Locator.

## Обязательные источники
- `AGENTS.md`
- `docs/specification/NBH-Business-Locator-Master-Spec-v3.0.md`
- `docs/process/NBH-Business-Locator-Development-Orchestration.md`

## Режим
REVIEW. Не изменяй файлы.

Проверь idempotency, rollback/retry behavior, partial failure, preserved IDs/meta/terms, multisite, version gates и upgrade tests.
