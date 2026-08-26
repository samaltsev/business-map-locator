# AGENTS.md — NBH Business Locator

## Источники истины
1. `docs/specification/NBH-Business-Locator-Master-Spec-v3.0.md`
2. `docs/process/NBH-Business-Locator-Development-Orchestration.md`
3. Фактический runtime-код репозитория
4. Тесты, которые проверяют реальное поведение

## Основные правила
- Один раунд — одна инженерная цель.
- Для неясной причины сначала Diagnostic без изменения файлов.
- Не создавать третью реализацию рядом с legacy и namespaced слоями.
- Перед изменением определить canonical owner, bootstrap и публичный контракт.
- Сохранять обратную совместимость beta, если задача явно не утверждает breaking change.
- Не удалять пользовательские данные и неизвестные/Pro meta при сохранении Free-полей.
- Любой существенный фикс сопровождается regression test.
- Не заявлять о runtime/browser проверке, если она не выполнялась.
- Не добавлять CDN, секреты, абсолютные локальные пути в production-код.
- Не выполнять version bump и сборку ZIP до отдельного Release-раунда.

## Локальное окружение
См. `.env.codex.example` и `.codex/environment.md`.

## Обязательный безопасный цикл implementation
- Перед каждым implementation-раундом создать verified backup через `.codex\scripts\New-PluginBackup.ps1`.
- Выполнить preflight, внести ограниченный патч, синхронизировать source со стендом и выполнить postflight.
- Создавать evidence в `.codex\reports\`; при неудачной deployment-проверке остановиться и восстановить stand из backup.
- Manual UI/UX-проверку запрашивать только для browser-only, login-protected, provider/billing и финального acceptance сценариев.

## Формат итогового отчёта
1. Summary
2. Root cause / implementation
3. Files changed
4. Tests and checks
5. Manual verification
6. Risks / limitations
7. Next recommended step

## Stop conditions
Остановиться без изменения кода, если:
- причина не доказана;
- рабочее дерево содержит несвязанные изменения, мешающие безопасному diff;
- нужен destructive migration без утверждённого плана;
- отсутствуют необходимые файлы;
- задача конфликтует с Master Spec;
- публичный контракт нельзя сохранить без решения владельца продукта.
