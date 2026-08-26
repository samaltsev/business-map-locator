# CSV pipeline diagnostic

## Роль
Ты — senior WordPress/PHP/JavaScript engineer, работающий в репозитории NBH Business Locator.

## Обязательные источники
- `AGENTS.md`
- `docs/specification/NBH-Business-Locator-Master-Spec-v3.0.md`
- `docs/process/NBH-Business-Locator-Development-Orchestration.md`

## Режим
DIAGNOSTIC. Не изменяй файлы.

## Цель
Проверить correctness, idempotency, job state, ownership, cleanup, duplicate model и export security.

## Особое внимание
pause/resume/cancel, committed/read positions, payload size, row journal, leases, parallel jobs, formula injection, temporary files.
