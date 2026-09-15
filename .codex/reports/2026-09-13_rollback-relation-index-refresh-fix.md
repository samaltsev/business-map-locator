# Rollback relation-index refresh narrow fix

Baseline: `30e4e9140f31c507fe46f9fba9bc911cc1d44628`. Verified backup: `.codex/backups/2026-09-13_15-11-07_areas-rollback-index-refresh-narrow-fix`.

The acceptance runtime proved that `AreaRollbackService` removes the WordPress Area relationship with `wp_remove_object_terms()`, while `BML_Location_Indexer` listens only to `set_object_terms` (plus post/meta hooks), not `delete_term_relationships` or `deleted_term_relationships`. Therefore the source relationship was correct but the derived `bml_location_terms` Area row remained stale until a broad rebuild.

The narrow change injects the existing canonical `BML_Location_Index` into `AreaRollbackService` and calls `upsert($locationId)` only after source removal is verified absent and City preservation is verified. The same canonical refresh is also performed for the crash-recovery case where the source Area relationship is already absent. It remains part of the existing `REMOVE_LOCATION_AREA` reverse operation: no direct SQL, no new index implementation, no source relationship re-add, and no full rebuild in the normal rollback path.

If canonical refresh returns false, the reverse operation is failed with `LOCATION_INDEX_REFRESH_FAILED`; rollback therefore becomes partial/resumable instead of falsely claiming completion. WordPress taxonomy relationships remain the source of truth.

Changed production files: `src/Migration/AreaRollbackService.php` and `src/Plugin.php`. PHP lint passed for both; `git diff --check` passed. The local workspace has no PHPUnit/PHPStan executable or vendor tree, so targeted/full test and static-analysis requirements remain outstanding and are not claimed. Active stand was not deployed or mutated.

## Tooling closure

Composer install used the existing lock with PHP 8.2 and did not modify `composer.json` or `composer.lock`. The Composer bin proxy was unusable in this environment, so PHPUnit was invoked directly at `vendor/phpunit/phpunit/phpunit`.

The `BML_DIR` PHPStan finding is pre-existing analysis-bootstrap debt: runtime defines it in `business-map-locator.php` through `define('BML_DIR', plugin_dir_path(__FILE__))`, while `phpstan.neon` analyzes `src/Plugin.php` without loading that plugin bootstrap. An analysis-only bootstrap at `.codex/phpstan/bml-dir-bootstrap.php` mirrors that constant and is not loaded by production, REST, AJAX, admin, or public runtime. With it, affected-scope PHPStan (`AreaRollbackService.php`, `Plugin.php`) returned 0 errors with no ignore or suppression.

Final checks: targeted PHPUnit PASS (13 tests, 58 assertions); full PHPUnit PASS (183 tests, 777 assertions); changed-file PHP lint PASS; diff-check PASS. The temporary untracked `vendor/` was removed after verification. Active stand was untouched; no deployment, staging, or commit occurred.
