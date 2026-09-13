# Areas Slice 2B-1D — safe rollback execution (in progress)

- Baseline: `ddad65513ac154f627025732b57c8d06937843c6`.
- Verified backup: `.codex/backups/2026-09-13_09-57-23_areas-slice-2b-1d-safe-rollback-execution`.
- Entry remains explicit `AreaRollbackService::beginRollback()` with snapshot-v2,
  same-run lock, and journal evidence gates before `ROLLBACK_RUNNING`.
- Candidate selection accepts only explicit forward ownership `true`; false, null,
  and absent evidence are preserved.
- Reverse order is Location Area relationship, City provenance, Area provenance,
  then run-created Area deletion. City terms/relationships are never removed.
- Reverse operations use WordPress APIs and the durable rollback journal lifecycle;
  no relation-index SQL is used. `ROLLBACK_PARTIAL` has a same-run gated resume.

Current verification: targeted rollback fixtures PASS (3 tests, 12 assertions);
full suite PASS (173 tests, 731 assertions); changed PHP lint PASS; `git diff --check`
PASS. No deployment, live rollback, index mutation, staging, or commit occurred.

Crash-recovery coverage now includes the four post-mutation windows: Location Area
removal, City provenance removal, Area provenance removal, and Area deletion. Each
retry reuses a single reverse journal key and reconciles the already-absent source
effect without repeating the destructive writer. The suite also covers explicit
unknown ownership preservation and external-provenance-drift partial/resume.

Final verification: targeted run 1 PASS and targeted run 2 PASS (7 tests, 28
assertions each); full run 1 PASS and full run 2 PASS (177 tests, 747 assertions
each); changed PHP lint PASS; PHPStan rollback production scope PASS (0 errors);
`git diff --check` PASS. Composer manifests are unchanged and temporary `vendor/`
was removed. Active stand remains untouched; no staging or commit occurred.

The Location relationship removal uses WordPress taxonomy hooks; the Location index
is a derived, deferred/rebuildable owner. Source relationship rollback is not undone
if later index refresh fails, and no direct migration SQL is used.

Final Area-deletion fixtures: a renamed/slug-drifted run-created Area, a run-created
Area with a child, and a run-created Area with an unrelated Location use all remain
present with zero `wp_delete_term()` calls and `ROLLBACK_PARTIAL`. Existing successful
reverse work remains applied; the blocked delete reverse record is never marked
`ROLLED_BACK`. Initial and partial-resume calls with an invalid same-run lock retain
their prior state and make zero relationship/meta/term destructive calls.

Final targeted runs: PASS twice, 11 tests and 48 assertions each. Final full runs:
PASS twice, 181 tests and 767 assertions each. Final PHP lint: PASS. Fresh PHPStan:
0 errors. `git diff --check`: PASS. Composer manifests remain unchanged and vendor
was removed after verification. No deploy, live apply, live rollback, index mutation,
staging, or commit occurred.
