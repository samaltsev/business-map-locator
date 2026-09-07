# P0 CSV Import Update Safety — evidence

## 1. Baseline

- Branch: `codex/production-hardening`
- HEAD: `e4a5f198cec1ea784e879be82ee2332c322aec4d`
- Plugin version: `1.3.2-beta40.7`
- Baseline import tests: 24 tests / 67 assertions, PASS.

## 2. Backup

- Status: verified.
- Path: `.codex/backups/2026-09-07_07-39-14_p0-import-update-safety`
- Evidence: source and stand SHA-256 manifests validated by `New-PluginBackup.ps1`.

## 3. Confirmed root cause

`ImportMapper::map()` keeps headers as array keys, but `LocationImporter::saveLocation()` wrote every optional meta field with `$data[$key] ?? ''`. An update with an absent CSV column therefore wrote an empty value through `update_post_meta()`.

## 4. Update policy design

- Default: `non_empty_only`.
- Supported: `non_empty_only`, `overwrite_mapped`, `create_only`, `update_only`.
- Absent mapped field: preserve for every policy.
- Empty mapped scalar: preserve for `non_empty_only`; clear for `overwrite_mapped`.
- Non-empty mapped scalar: update.
- Taxonomy empty cells preserve terms; explicit taxonomy clear is not part of the existing contract.

## 5. Architectural decision

Column presence remains in the associative mapper result. `ImportUpdatePolicy` normalizes the job option; `LocationImporter::shouldWrite()` is the single write-decision owner. `LocationImporter` remains the canonical import storage/index owner.

## 6. Changed files

- `src/Import/Config/ImportUpdatePolicy.php` — policy normalization.
- `src/Import/ImportManager.php` — validated job policy persistence and summary.
- `src/Admin/Ajax/ImportAjaxController.php` — optional request plumbing.
- `src/Import/Mapping/ImportMapper.php` — empty `visible` remains empty.
- `src/Import/Processing/LocationImporter.php` — safe writes and policy skip decisions.
- `tests/LocationImporterUpdateSafetyTest.php` — behaviour regressions.

## 7. Regression tests

`LocationImporterUpdateSafetyTest` covers absent versus empty mapping, preserve/update/clear, external ID/status/terms preservation, legacy `open` normalization, both directed policies, dry-run skip parity, and index writes. These cases fail against the previous unconditional optional-meta writes or policy-free branching.

## 8. Targeted tests

- Status: PASS.
- Tests: 41.
- Assertions: 98.
- Failures: 0.
- Errors: 0.

## 9. Import test suite

- Status: PASS.
- Tests: 41.
- Assertions: 98.

## 10. Full suite

- Status: PASS.
- Tests: 95.
- Assertions: 370.
- Failures: 0.
- Errors: 0.
- Skipped: 0.

## 11. Static checks

- PHP lint: PASS for all changed PHP files.
- PHPStan P0: PASS for affected import path.
- PHPStan project-wide: 111 pre-existing diagnostics (WordPress constants/stubs, PHP target and legacy code); no new import finding.
- PHPCS P0: project ruleset reports namespaced PSR-style project-wide debt and pre-existing violations; no mass reformat applied.
- PHPCS pre-existing: historical rule/config incompatibility confirmed.

## 12. Runtime

- WordPress: NOT RUN.
- Manual import: NOT RUN; stand deployment was SHA-256 verified, but no browser/manual acceptance was performed.

## 13. Diff summary

P0 working-tree patch changes the five production files and adds two P0 files listed above. No version bump, archive build or commit was made.

## 14. Unrelated changes

`settings-ux.js`, `map-controller.js`, REST compatibility work, and split tests were not edited by this round.

## 15. Final git status

The worktree remains intentionally dirty with unrelated user changes plus the uncommitted P0 patch. No `reset`, `checkout`, `clean`, `stash`, or broad add was used.

## 16. Remaining risks

Manual WordPress UI acceptance has not been executed. Taxonomy clear semantics remain intentionally unsupported. The policy has server-side request plumbing but no new policy-selector UI in this P0 round.

## 17. Verdict

`P0 IMPORT UPDATE SAFETY COMPLETE — READY FOR REVIEW`

## 18. Next recommended round

`Isolated P0 Import Update Safety commit`
