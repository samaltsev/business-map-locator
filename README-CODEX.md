# NBH Business Locator — Codex Enterprise Kit

## Установка
1. Распакуйте архив в:
   `D:\WP plugins\business-map-locator`
2. Скопируйте `.env.codex.example` в `.env.codex`.
3. Проверьте пути.
4. Откройте корень проекта в VS Code/Codex.
5. Первый раунд запускайте промптом:
   `.codex/prompts/diagnostic/001-beta29-runtime-architecture.md`

## Модель
Ежедневная работа:
- `gpt-5.6-terra`
- reasoning `medium`

Архитектурная диагностика и сложное review:
- `gpt-5.6-sol`
- reasoning `high`

## Скрипты
- `.codex\scripts\New-PluginBackup.ps1 -TaskName <task>` — verified source/stand backup с retention.
- `.codex\scripts\Restore-PluginBackup.ps1 -BackupPath <path> -Target Source|Stand|Both -ConfirmRestore` — явное восстановление snapshot.
- `.codex\scripts\Sync-To-Stand.ps1` — backup-aware deployment и source/stand parity.
- `.codex\scripts\Test-StandHealth.ps1` — lint, static, HTTP и stand checks.
- `.codex\scripts\Invoke-CodexRound.ps1 -TaskName <task>` — backup → preflight → deployment → postflight → evidence.
- `.codex\scripts\php-lint.bat`
- `.codex\scripts\phpunit.bat`
- `.codex\scripts\phpcs.bat`
- `.codex\scripts\phpstan.bat`
- `.codex\scripts\sync-stand.bat`
- `.codex\scripts\build-zip.bat`
- `.codex\scripts\all-checks.bat`

Скрипты корректно сообщают, когда локальный runner PHPCS/PHPStan/PHPUnit отсутствует.
Они не устанавливают зависимости автоматически.

Перед каждым implementation-раундом используйте `Invoke-CodexRound.ps1` либо эквивалентный последовательный запуск backup, preflight, deployment, postflight и evidence. При failed deployment не продолжайте работу до подтверждённого rollback.

## Важно
Не добавляйте `.env.codex` в Git.
Добавьте его в `.gitignore`.
