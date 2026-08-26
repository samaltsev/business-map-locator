# Локальное окружение

| Переменная | Значение |
|---|---|
| Project root | `D:\WP plugins\business-map-locator` |
| Plugin source | `D:\WP plugins\business-map-locator\business-map-locator` |
| WordPress stand | `D:\OSPanel\home\business-map.local\public` |
| Installed plugin | `D:\OSPanel\home\business-map.local\public\wp-content\plugins\business-map-locator` |
| PHP CLI | `D:\OSPanel\modules\PHP-8.2\PHP\php.exe` |
| Backup root | `D:\WP plugins\business-map-locator\.codex\backups` |
| Evidence root | `D:\WP plugins\business-map-locator\.codex\reports` |

## Важное правило
Абсолютные пути используются только в локальных скриптах и документации.
Они не должны попадать в production PHP/JS/CSS, distributable ZIP и публичные логи.

## Безопасный implementation workflow

1. `New-PluginBackup.ps1` создаёт и проверяет source/stand snapshots.
2. `Test-StandHealth.ps1` запускает доступные preflight checks.
3. `Sync-To-Stand.ps1` развёртывает только в plugin directory стенда и сверяет manifests.
4. После deployment повторить health checks и сохранить evidence в `.codex\reports`.
5. При failed deployment скрипт восстанавливает stand snapshot; дальнейшие изменения остановить до расследования.
