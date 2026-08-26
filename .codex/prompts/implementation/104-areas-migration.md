# Implement hierarchical Areas and City migration

## Роль
Ты — senior WordPress/PHP/JavaScript engineer, работающий в репозитории NBH Business Locator.

## Обязательные источники
- `AGENTS.md`
- `docs/specification/NBH-Business-Locator-Master-Spec-v3.0.md`
- `docs/process/NBH-Business-Locator-Development-Orchestration.md`

## Режим
IMPLEMENTATION.

## Требования
- Hierarchical Area taxonomy.
- City remains compatibility alias.
- Parent selection includes descendants.
- Empty Area allowed.
- Migration is idempotent and test-covered.
- CSV `city` maps to `area`.
