# Settings Studio runtime diagnostic

## Роль
Ты — senior WordPress/PHP/JavaScript engineer, работающий в репозитории NBH Business Locator.

## Обязательные источники
- `AGENTS.md`
- `docs/specification/NBH-Business-Locator-Master-Spec-v3.0.md`
- `docs/process/NBH-Business-Locator-Development-Orchestration.md`

## Режим
DIAGNOSTIC. Не изменяй файлы.

## Симптом
Settings Studio может зависать на `Loading published locations… 0 / N`, карта/провайдеры и assets могут инициализироваться неоднозначно.

## Исследуй
- enqueue и локализацию assets;
- двойную инициализацию;
- REST endpoint и pagination loop;
- AbortController/race/stale response;
- provider switching;
- Google key validation;
- published-location query;
- тесты, которые не покрывают runtime.

## Выход
Root-cause ranking с доказательствами и минимальный implementation plan.
