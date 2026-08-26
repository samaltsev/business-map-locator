# Fix confirmed Settings Studio runtime cause

## Роль
Ты — senior WordPress/PHP/JavaScript engineer, работающий в репозитории NBH Business Locator.

## Обязательные источники
- `AGENTS.md`
- `docs/specification/NBH-Business-Locator-Master-Spec-v3.0.md`
- `docs/process/NBH-Business-Locator-Development-Orchestration.md`

## Режим
IMPLEMENTATION. Меняй только файлы, необходимые для подтверждённой причины.

## Предусловие
Используй результаты диагностического отчёта Settings Studio.

## Требования
- Устранить первопричину, а не скрыть loader.
- Одна initialization path.
- Published locations загружаются конечным и наблюдаемым процессом.
- Ошибка REST/provider отображается пользователю.
- Добавить regression coverage.
- Не менять unrelated UI.
