# Areas Slice 2B-1D-0 — forward ownership evidence diagnostic

## Baseline

- Branch: `codex/production-hardening`
- HEAD: `c4617f5c2f43d4b22bc8a2b8bd4fc9b4b472c36a`
- Verified backup: `.codex/backups/2026-09-13_09-05-58_areas-slice-2b-1d-0-forward-ownership-evidence`
- Active stand: untouched. No deployment, live apply, rollback, or index mutation was run.

## Existing uncommitted implementation reviewed

`CREATE_AREA` stores `created_by_run` and `ownership_resolution`: observed insert is
`true` / `observed_create`; a recovered `STARTED` insert is `null` /
`recovered_unknown_outcome`.

`WRITE_CITY_PROVENANCE` and `WRITE_AREA_PROVENANCE` store `written_by_run`,
`reconciled_preexisting`, and `ownership_resolution`. A normal observed update is
`true` / `false` / `observed_write`; a value already equal before a planned operation
is `false` / `true` / `preexisting`; recovery of a `STARTED` operation whose current
value equals expected is `null` / `false` / `recovered_unknown_outcome`.

For both provenance operations, `previous_value` and `expected_value` are passed to
`planOperation()` before `update_term_meta()`. Thus a matching current state proves
reconciliation eligibility, not ownership.

## Historical diagnostic (superseded by approved collision policy)

The requested `CREATE_AREA` pre-existing fixture requires accepting an independently
existing, exact name/slug/parent Area and recording `created_by_run=false`.

The committed regression `testUnownedSlugCollisionBlocksWithoutSuffixOrProvenance`
in `tests/AreaMigrationTermProvenanceExecutionTest.php` defines the opposite public
safety contract: an unowned matching Area must return `BLOCKED` and must not be
adopted. Changing that behavior would silently weaken collision protection and
conflict with the preserved beta behavior.

Therefore the requested complete ownership suite, in particular full pre-existing
forward ownership, cannot be truthfully added without a product-owner decision on
whether exact matching Areas may be adopted. No ownership inference or rollback
mutation was introduced.

## Historical checks before the decision

- Existing term/provenance targeted regression: PASS — 14 tests, 55 assertions.
- PHP lint, changed executor: PASS.
- `git diff --check`: PASS.
- Production diff has no executable rollback call to `wp_remove_object_terms()`,
  `delete_term_meta()`, or `wp_delete_term()`.
- Composer manifests unchanged; temporary untracked `vendor/` used for the test was
  removed afterward.

## Historical deferred checks (completed below after the decision)

The required dedicated ownership fixtures, their two targeted runs, full suite twice,
and fresh PHPStan were not run because the required test matrix depends on the
unresolved contract choice above. No commit was made.

## Continuation: approved collision policy and completed verification

The product decision resolves the prior diagnostic: an exact name/slug match does
not make a pre-existing Area adoptable. Without independently trusted reciprocal
provenance it remains a blocking collision. `CREATE_AREA created_by_run=false` is
supported by the evidence model but is intentionally not reachable through this
contract and was not manufactured merely to fill a state matrix.

Dedicated `AreaMigrationOwnershipEvidenceTest` now covers observed create, unknown
`STARTED` create recovery without a duplicate, exact-match unowned collision, city
and Area provenance true/false/unknown, legacy missing evidence, monotonic completed
evidence, and stable operation keys/no duplicate logical operation.

- Targeted run 1: PASS — 9 tests, 51 assertions, 0 warnings/errors/failures/skips.
- Targeted run 2: PASS — 9 tests, 51 assertions, 0 warnings/errors/failures/skips.
- Full run 1: PASS — 170 tests, 720 assertions, 0 warnings/errors/failures/skips.
- Full run 2: PASS — 170 tests, 720 assertions, 0 warnings/errors/failures/skips.
- PHP lint of changed PHP: PASS.
- PHPStan of changed production scope: PASS — 0 errors.
- `git diff --check`: PASS.

The full run includes the committed collision, term recovery/partial-resume, and
relationship execution/partial-resume regressions. Relationship ownership remains
covered by `AreaMigrationLocationAreaExecutionTest` (`true` for run-added and
`false` for pre-existing expected relationship). No executable rollback call was
added: no new `wp_remove_object_terms()`, `delete_term_meta()`, or `wp_delete_term()`.

Composer manifests remain unchanged. `vendor/` was restored only for verification and
will be removed before handoff. No deployment, live apply, rollback, index mutation,
staging, or commit occurred.
