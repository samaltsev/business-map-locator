# Areas Migration — Phase A Evidence

**Project:** Business Map Locator  
**Baseline:** `1.3.2-beta29`  
**Phase:** Areas Migration — Phase A  
**Owner acceptance:** Conditionally accepted.  
**Result:** Implementation and runtime evidence accepted conditionally; Phase B is blocked pending AC-9 full PHPUnit-suite and AC-10 browser acceptance, unless the owner accepts these environment limitations separately.

## Scope delivered

- Registered canonical, hierarchical, REST-enabled `bml_area` taxonomy for `bml_location` with dedicated Area capabilities; its admin UI and menu exposure remain disabled.
- Added `AreaMigrationService` for read-only system inspection, JSON snapshots, and non-mutating migration simulation.
- Added `MigrationSnapshotStore` using `uploads/business-map-locator/migrations/snapshot-YYYYMMDD-HHMMSS.json`.
- Added ownership metadata: site URL, plugin version, WordPress version, PHP version, timestamp, and creator user ID.
- Added `RollbackInterface` and `AreaRollbackService` for snapshot discovery, validation, and status reporting only.

## Explicitly not delivered

No Area terms or relationships were created or migrated. No search, REST route/query/response, frontend, shortcode, block, CSV, repository, filter, cache, index, or public admin integration was changed.

## Acceptance criteria

| Criterion | Result | Evidence |
|---|---|---|
| AC-1 `bml_area` registration | PASS | Runtime smoke confirmed public, REST-enabled, hierarchical CPT attachment with admin UI disabled and dedicated capabilities; regression coverage was added. |
| AC-2 state inspection | PASS | Stand: City exists, Area exists, 809 Locations, 97 City terms, 0 Area terms. |
| AC-3 snapshot creation | PASS | Runtime smoke created and validated a JSON snapshot in the WordPress uploads migration directory. |
| AC-4 snapshot validation | PASS | Store and rollback service both validated the generated snapshot. |
| AC-5 dry run does not mutate DB | PASS | Stand counts before/after dry run were equal; isolated smoke also compared terms, relationships, and posts. |
| AC-6 dry-run statistics | PASS | Stand returned 809 Locations, 97 City terms, 97 would-create Areas, and 809 would-migrate relationships. |
| AC-7 rollback detects snapshot | PASS | Generated snapshot was returned by `detectSnapshots()`. |
| AC-8 rollback validates snapshot | PASS | `validateSnapshot()` returned valid for the generated snapshot. |
| AC-9 existing tests | PARTIAL | PHP syntax passed. PHPUnit runner is unavailable in source and stand, so the added isolated smoke was run instead. |
| AC-10 frontend unchanged | PARTIAL | No frontend assets/templates/controller files changed; stand home and Locations REST health checks returned HTTP 200. Browser acceptance was not run. |
| AC-11 REST compatibility | PASS | No REST files changed; `/wp-json/`, health, and Locations endpoints returned HTTP 200 after deployment. |
| AC-12 Free behavior unchanged | PARTIAL | Scope excludes all Free consumer paths; deployment health checks and source/stand parity passed. Full regression-suite confirmation is unavailable with PHPUnit absent. |
| AC-13 capability upgrade | PASS | Capability schema `1.3.0`, regression coverage, and runtime upgrade evidence prove an existing `1.2.0` installation receives `manage`, `edit`, `delete`, and `assign` Area capabilities. |

## Verification

- Verified backup: `.codex/backups/2026-07-24_11-06-09_areas-phase-a`.
- Source/stand parity: PASS — `.codex/reports/2026-07-24_11-49-00-source-stand-manifest.json`.
- Postflight health: PASS — `.codex/reports/2026-07-24_11-49-23-stand-health.json`.
- PHP syntax: PASS for Phase A files and the new regression test; postflight full-plugin PHP lint also passed.
- Area capability upgrade smoke: PASS for the `1.2.0` to `1.3.0` path; the real stand upgraded from `1.2.0` to `1.3.0` and all four administrator capabilities were present.
- Isolated service smoke: PASS for inspection, dry-run non-mutation, snapshot creation/validation, and rollback status.
- Runtime smoke: PASS. The test snapshot was deleted after validation; no migration-owned data was retained.
- PHPUnit, PHPCS, and PHPStan: SKIPPED because their runners are unavailable. JavaScript syntax: SKIPPED because Node is unavailable.

## Risks and Phase B entry conditions

- The taxonomy is registered but deliberately has no public consumer integration.
- Snapshot capture is read-only but needs Phase B validation against malformed data, hierarchy conflicts, and repeated dry runs.
- Rollback is detection/validation only; it cannot restore data until a later accepted phase.
- Current term and relationship reads treat WordPress API errors as empty results; Phase B must surface these errors as dry-run errors rather than silently reporting zero data.
- Snapshot protection against direct web access, atomic write/rename behavior, and collision-safe naming must be designed before any destructive migration phase.
- `would_create_areas` is intentionally a baseline City-term count; Phase B must replace it with validated mapping logic that accounts for existing Areas.
- Location counting currently loads IDs; this is acceptable for the Phase A baseline but should be made count-oriented before large migrations.
- Phase B must remain non-mutating and add validation/reporting only.
- Do not start Phase B until AC-9 and AC-10 are evidenced or the owner records an explicit exception.
