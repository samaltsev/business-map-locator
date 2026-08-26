# Implement REST card/detail contract

## Роль
Ты — senior WordPress/PHP/JavaScript engineer, работающий в репозитории NBH Business Locator.

## Обязательные источники
- `AGENTS.md`
- `docs/specification/NBH-Business-Locator-Master-Spec-v3.0.md`
- `docs/process/NBH-Business-Locator-Development-Orchestration.md`

## Режим
IMPLEMENTATION.

## Требования
- Card payload минимален.
- Detail lazy-loaded по ID.
- Не возвращать Free-поля принудительно пустыми.
- Safe HTML для detail content.
- Backward-compatible legacy aliases.
- Schema, validation, pagination headers и tests.
