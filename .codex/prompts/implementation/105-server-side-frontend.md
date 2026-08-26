# Implement server-side frontend loading

## Роль
Ты — senior WordPress/PHP/JavaScript engineer, работающий в репозитории NBH Business Locator.

## Обязательные источники
- `AGENTS.md`
- `docs/specification/NBH-Business-Locator-Master-Spec-v3.0.md`
- `docs/process/NBH-Business-Locator-Development-Orchestration.md`

## Режим
IMPLEMENTATION.

## Требования
- Не загружать все REST pages.
- Cards: default 24, pagination/load more.
- Markers: bounds-based lightweight payload.
- Abort stale requests.
- Radius выполняется сервером.
- Multiple instances independent.
