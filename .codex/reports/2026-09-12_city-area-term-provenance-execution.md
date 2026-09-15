# Slice 2B-1B term and provenance execution

- Baseline: `e3aed8d32fd6baa3bc918e7dc0ff7f5e17b25f13`; backup verified at `.codex/backups/2026-09-12_17-28-47_areas-slice-2b-1b-term-provenance-execution`.
- Owner: `AreaMigrationExecutor::executeTermPhase()` performs only `CREATE_AREA`, `WRITE_CITY_PROVENANCE`, and `WRITE_AREA_PROVENANCE` with the existing v2 snapshot, same-run lock, state, planner-plan, revalidation, and journal gates.
- Recovery: test-only checkpoint injection covers Area created, City provenance written, and Area provenance written before journal APPLIED. Resume reconciles STARTED evidence and does not create a duplicate Area.
- Coverage: idempotent rerun; one-sided City/Area provenance; STARTED City/Area provenance; unowned slug/name collision; City/Area drift; conflicting City/Area provenance; partial two-City batch (first mapping preserved, second blocked, no auto-rollback).
- No Location assignment, index mutation, rollback mutation, deployment, or live apply occurred. Implementation remains local to this repository and its PHPUnit fixture environment; mutating Slice 2B-1B code was not deployed or executed on the active stand.
- PHPUnit: targeted 14 tests / 55 assertions; full 140 tests / 558 assertions; no warnings, failures, errors, or skips.
- PARTIAL resume: explicit same-run term-phase resume requires v2 snapshot, planning evidence, same-run lock, journal readability, and revalidation. Unresolved conflicts return to PARTIAL without replaying completed operations; removing only the external blocker permits the same run/snapshot/journal to complete the remaining City. Do not start 2B-1C from this round.
