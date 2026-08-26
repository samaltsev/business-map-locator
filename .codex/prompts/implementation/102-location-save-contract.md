# Fix Location save contract

## Роль
Ты — senior WordPress/PHP/JavaScript engineer, работающий в репозитории NBH Business Locator.

## Обязательные источники
- `AGENTS.md`
- `docs/specification/NBH-Business-Locator-Master-Spec-v3.0.md`
- `docs/process/NBH-Business-Locator-Development-Orchestration.md`

## Режим
IMPLEMENTATION.

## Требования
- Сохранение не удаляет email, website, image, hours и неизвестные/Pro meta.
- Отдельная sanitation для каждого поля.
- Publish требует coordinates.
- Category и Area не обязательны.
- Operational status: active / temporarily_closed / hidden.
- Добавить tests, которые падали бы до исправления.
