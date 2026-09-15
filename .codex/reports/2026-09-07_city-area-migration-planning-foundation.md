# City to Area migration planning foundation

## Baseline and safety

- Branch/head: `codex/production-hardening` / `f7636db8788b53b097d4cce74f8fef40d92f721e`.
- Verified backup: `.codex/backups/2026-09-07_16-38-05_city-area-migration-planning-foundation`.
- Before and after live dry run: City terms 96, City relationships 810, Area terms 0, Area relationships 0, Area relation-index rows 0.

## Architecture

`AreaMigrationService` remains the owner. `AreaMigrationPlanner` is pure and side-effect free; it makes only CREATE, ALREADY_MAPPED, COLLISION, AMBIGUOUS, and REQUIRES_DECISION results. Reciprocal provenance is the sole mapping authority. `AreaMigrationStateStore` stores small run metadata in an option and validates NEW -> INSPECTED -> SNAPSHOTTED -> SIMULATED -> READY/BLOCKED. `AreaMigrationLock` supplies option-backed ownership, TTL, release, and stale-lock recovery for a future executor. Initial acquisition uses atomic `add_option`; stale replacement conditionally deletes only the exact observed option value, so it cannot remove a replacement lock.

Snapshots are filesystem-backed, immutable v2 JSON and include City/Area inventory and provenance, affected locations and statuses, and full plan evidence. No 810-location snapshot is duplicated in the state option.

## Live dry run

The stand enumerated 96 Cities and 0 Areas, included all 810 City-bearing locations (809 publish, 1 draft), and produced City decisions CREATE=96, ALREADY_MAPPED=0, COLLISION=0, AMBIGUOUS=0, REQUIRES_DECISION=0; location plans ADD_AREA=810, NOOP=0, REQUIRES_DECISION=0. Snapshot: `uploads/business-map-locator/migrations/snapshot-20260907-134641.json`.

No taxonomy, relationship, provenance, or relation-index write path was introduced or invoked. The only persisted artifact is the immutable snapshot file.

## Verification

- Targeted PHPUnit: 7 tests, 32 assertions, pass.
- Full PHPUnit: 109 tests, 428 assertions, pass.
- PHP lint: pass for all changed PHP files.
- PHPStan affected migration paths: pass, no findings.
- PHPCS project standard reports extensive pre-existing convention debt across existing PascalCase filenames/camelCase APIs and line endings; it is not a Slice 2A behavioral/static-analysis failure and was not broadened into a style rewrite.
- Source/stand SHA-256 parity passed for all six deployed production files.

## Remaining risks

No apply/rollback capability exists. A later Slice 2B needs review of the plan and an explicitly authorized executor that reuses/revalidates this planner. The normal stand sync/health scripts still contain stale `business-map.local` configuration, so deployment used explicit reviewed files and hashes.
