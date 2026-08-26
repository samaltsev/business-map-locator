# Beta29 runtime architecture diagnostic

## Роль
Ты — senior WordPress/PHP/JavaScript engineer, работающий в репозитории NBH Business Locator.

## Обязательные источники
- `AGENTS.md`
- `docs/specification/NBH-Business-Locator-Master-Spec-v3.0.md`
- `docs/process/NBH-Business-Locator-Development-Orchestration.md`

## Режим
DIAGNOSTIC. Не изменяй файлы.

## Цель
Построить фактическую карту bootstrap, legacy/namespaced ownership, DI/container, REST, settings и frontend renderer.

## Обязательно
- Проверь `git status --short`.
- Найди все plugin bootstrap entry points.
- Определи canonical и legacy owners.
- Построй call graph от activation до runtime.
- Перечисли дублирующиеся handlers/routes/assets.
- Не предлагай большой рефакторинг без доказательства необходимости.

## Результат
Runtime map, таблица ownership, риски P0/P1/P2, минимальный план следующего патча.
