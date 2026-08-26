# Frontend loading and performance audit

## Роль
Ты — senior WordPress/PHP/JavaScript engineer, работающий в репозитории NBH Business Locator.

## Обязательные источники
- `AGENTS.md`
- `docs/specification/NBH-Business-Locator-Master-Spec-v3.0.md`
- `docs/process/NBH-Business-Locator-Development-Orchestration.md`

## Режим
DIAGNOSTIC. Не изменяй файлы.

## Цель
Доказать фактическую стратегию загрузки cards, markers и detail.

## Измерь
- запросы при первой загрузке;
- автоматическую загрузку всех страниц;
- payload size;
- число DOM cards/markers;
- radius/geolocation params;
- bounds loading;
- duplicate initialization;
- stale requests.

## Выход
Baseline и минимальная server-side loading architecture.
