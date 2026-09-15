# Areas Slice 2B-1C — Taxonomy fixture harness gate

## Scope

This round changes PHPUnit fixture behavior only. No production migration, Location writer, or relation-index code changed. The active WordPress stand was not deployed to or mutated.

Baseline parent for the completed 2B-1C slice: `fbd08117244cacd4200560294f0845fc66183f17` (`Add Area term provenance execution`).

## Diagnostic and contract

`ADD_LOCATION_AREA` already exists as an operation-journal type. Runtime relationship reads use `wp_get_post_terms(..., ['fields' => 'ids'])`; the forthcoming mutation path requires `wp_set_object_terms(..., true)` append semantics.

The old fixture wrote a disconnected `bml_test_object_terms` entry and neither preserved an existing term set nor made the write observable through `wp_get_post_terms()`, which reads `bml_test_post_terms`.

The fixture now treats `bml_test_post_terms[objectId][taxonomy]` as the shared source of truth:

- append merges only the selected taxonomy and removes duplicate integer IDs;
- replacement replaces only the selected taxonomy;
- writes are immediately visible through `wp_get_post_terms()`;
- City and Category relationships remain intact while Areas are appended.

There was no existing `wp_set_object_terms()` failure-injection hook to preserve.

## Verification

- Targeted fixture test: PASS — 2 tests, 6 assertions, no warnings, failures, errors, or skips.
- Full PHPUnit run 1: PASS — 142 tests, 564 assertions, no warnings, failures, errors, or skips.
- Full PHPUnit run 2: PASS — identical totals.
- PHP lint: PASS for the modified bootstrap and fixture test.
- PHPStan configuration scans production paths only, so no test/bootstrap scope was run or changed.
- `git diff --check`: PASS.

## Production scope audit

`AreaMigrationExecutor`, production Location writers, and `BML_Location_Index` are unchanged in this round. Deployment, live apply/rollback, and live index mutation: NO.

## Slice 2B-1C-1 — ADD_LOCATION_AREA source execution

`AreaMigrationExecutor::executeRelationshipPhase()` enters only from the existing `RUNNING_TERMS` boundary and processes planner-owned `location_decisions` with `status = ADD_AREA`.  It creates/reuses the stable `ADD_LOCATION_AREA` journal identity `{ run, Location, City, Area }` and advances the existing lifecycle `PLANNED → STARTED → APPLIED → VERIFIED → COMPLETED`.

Immediately before source mutation it checks the Location exists; its direct City terms equal exactly the planned single City; the mapped Area exists and still matches the planned/snapshotted identity; and City/Area provenance is reciprocal.  Existing Areas are classified as none, exactly expected (reconcile without writer), or conflict (any unrelated Area blocks).  No City or unrelated taxonomy is replaced.

The only writer is `wp_set_object_terms($locationId, [$areaId], 'bml_area', true)`.  Area and City terms are re-read before journal verification, so writer success without observable source state cannot produce completion.  A narrowly test-only `AFTER_LOCATION_AREA_ADDED` checkpoint proves recovery from `STARTED` after the source write without a duplicate append.  Draft Locations are processed without changing their post status.

Existing runtime index lifecycle is automatic: `BML_Location_Indexer::hooks()` listens to WordPress `set_object_terms`, marks a supported Location taxonomy change dirty, and flushes the canonical `BML_Location_Index::upsert()` at shutdown.  This slice performs no direct index SQL and does not create or execute `REFRESH_LOCATION_INDEX`.

### Verification

- Dedicated source-execution run 1: PASS — 15 tests, 72 assertions, no warnings, failures, errors, or skips.
- Dedicated source-execution run 2: PASS — identical totals.
- Full PHPUnit run 1: PASS — 157 tests, 636 assertions, no warnings, failures, errors, or skips.
- Full PHPUnit run 2: PASS — identical totals.
- PHP lint: PASS for changed production and test PHP files.
- PHPStan affected production scope: PASS, 0 errors.
- `git diff --check`: PASS.
- Deployment, live apply/rollback, and live index mutation: NO.

## Final safety review

### Relationship PARTIAL continuation

The state store now permits `PARTIAL → RUNNING_RELATIONSHIPS` only through `AreaMigrationExecutor::resumePartialRelationshipPhase()`.  It is not a generic retry and does not alter `resumePartialTermPhase()` or `PARTIAL → ROLLBACK_RUNNING` eligibility.

Before that transition the method requires the same run in `PARTIAL`, its persisted v2 snapshot, planning evidence with no plan blockers, the same-run lock, relationship-phase failure evidence, readable journal operations, and read-only validation of every previously completed relationship operation.  A completed Location must still have exactly its planned City and Area, no unrelated Area, valid mapped Area identity, and reciprocal provenance.  Otherwise the state remains `PARTIAL` and no source write occurs.

The multi-Location fixture proves a deterministic batch: Location A completes and preserves City; Location B gains a second City and blocks before writer use; the run becomes `PARTIAL` with no rollback.  An unresolved same-run resume leaves A untouched and returns to `PARTIAL`.  Removing only B's external conflict lets the same run, snapshot, and journal namespace complete B.  Missing lock and drift of completed A both refuse resume before transition.

### Derived index boundary

The production hook chain is `wp_set_object_terms()` → WordPress `set_object_terms` action → `BML_Location_Indexer::sync_terms()` → `mark_dirty()` → shutdown `flush_dirty()` → canonical `BML_Location_Index::upsert()`.  `Plugin::bootLegacyModules()` registers the indexer's hooks.  The relation-index suite verifies direct rows for Category, City, and Area only; no ancestor expansion is introduced by this migration.

The executor receives no synchronous derived-index result: it does not call the indexer or index SQL, while the indexer's canonical upsert is deferred to shutdown.  Therefore source success cannot be rolled back merely because a later derived-index flush fails.  WordPress relationships remain canonical; derived-index repair remains the existing canonical upsert/rebuild lifecycle.  A draft remains draft because the migration never changes post status; no public-eligibility rule was modified or claimed as source-mutation behavior.

### Journal and final state

`ADD_LOCATION_AREA` records stable run/Location/City/Area identity plus `city_ids_before`, `area_ids_before`, operation state/attempt, and `relationship_added_by_run`.  This distinguishes an Area already present at operation planning from one appended by this run for future rollback analysis; rollback mutation is not implemented here.  With all planned relationship operations complete, the existing `RUNNING_RELATIONSHIPS → COMPLETED` transition is the final execution boundary.

### Final verification

- Dedicated final review run: PASS — 19 tests, 105 assertions, no warnings, failures, errors, or skips.
- Full PHPUnit final review run: PASS — 161 tests, 669 assertions, no warnings, failures, errors, or skips.
- PHP lint: PASS for every changed PHP file.
- PHPStan affected production scope: PASS, 0 errors.
- `git diff --check`: PASS.
- Deployment, live apply/rollback, and live index mutation: NO.
