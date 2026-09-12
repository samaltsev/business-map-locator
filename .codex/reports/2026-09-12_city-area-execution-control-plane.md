# City → Area execution control plane — Slice 2B-1A

## Baseline and safety

- Branch: `codex/production-hardening`
- Starting HEAD: `cbd3a296ac764fd24918970b02d63578270fa172`
- Plugin version: `1.3.2-beta40.7`
- Working tree before implementation: clean.
- Verified backup: `.codex/backups/2026-09-12_16-27-46_areas-slice-2b-1a-execution-control-plane`.
- No sync/deployment to the active stand was performed. No live apply or rollback was invoked.

## Ownership and state machine

- `AreaMigrationService` remains the planning/snapshot orchestration owner.
- New `AreaMigrationExecutor` owns non-mutating initial-execution eligibility and explicit phase entry.
- `AreaRollbackService` owns non-mutating rollback eligibility and explicit rollback phase entry.
- `AreaMigrationStateStore` now defines `READY → RUNNING_TERMS → RUNNING_RELATIONSHIPS → COMPLETED`, interruption states, and `ROLLBACK_RUNNING → ROLLED_BACK|ROLLBACK_PARTIAL|FAILED`.
- All state changes remain explicit; no hook, REST, AJAX, cron, CLI, admin, or boot entry point invokes execution.

## Eligibility, lock, journal, and revalidation

- Initial execution requires a run, readable v2 snapshot, `READY`, plan evidence without planner blockers, same-run active lock, and no observed drift.
- v1 snapshots remain diagnostically valid but are rejected for execution and rollback eligibility.
- `AreaMigrationJournal` is resolved for the same run and read/listed only. The control plane creates no journal operations.
- Revalidation is read-only and observes City existence/identity, Area provenance/identity, and Location City/Area relationships. It returns structured drift codes including missing City, identity/provenance changes, second City, and unrelated/changed Area.
- Rollback accepts only `COMPLETED`, `PARTIAL`, or `FAILED` with v2 evidence, same-run lock, journal execution evidence, and no drift.

## No-mutation boundary

The executor and rollback control-plane sources do not call term creation/update/deletion, term meta writes/deletions, object-term assignment/removal, or index mutation APIs. State-option writes are the only allowed phase-entry mutation.

## Verification

- Targeted PHPUnit: PASS — 12 tests, 57 assertions, 0 warnings, failures, errors, or skips.
- Full PHPUnit: PASS — 126 tests, 503 assertions, 0 warnings, failures, errors, or skips.
- PHP lint for every changed PHP file: PASS.
- PHPStan for affected execution-control paths: PASS, no errors.
- `git diff --check`: PASS.

## Active stand

The requested direct final SQL count check could not run because MySQL at `127.0.0.1:3306` was not accepting connections (`ERROR 2003`, connection refused). No service was started and no active-stand state was modified. The known baseline remains City terms 96, City relationships 810, Area terms 0, Area relationships 0, Area index 0; a live count recheck is still required once the local database is running.

## Next boundary

Slice 2B-1B may implement Area Term + Provenance execution using this control plane, journaled evidence, and explicit phase completion. It must not broaden this round's non-mutating public surface.
