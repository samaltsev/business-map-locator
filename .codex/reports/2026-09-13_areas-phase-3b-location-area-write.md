# Areas Phase 3B — Location → Area canonical write verification

## Baseline and backup

- Branch: `codex/production-hardening`
- Parent: `c48405f5a65a237c2ebb392643fa8ef0879dc3e7` (`Add canonical Area admin UX`)
- The uncommitted Phase 3B state was preserved in verified backup `.codex/backups/2026-09-13_19-52-18_areas-phase-3b-before-completion/` (source 214 files, stand 211 files).
- Phase 3B remains unstaged, uncommitted, and undeployed.

## Ownership map

- Location normal-write owner: `BusinessMapLocator\Admin\Location\LocationWriteService`.
- Admin entry: `SaveLocationAction::handle()` whitelists `area_id` and calls the writer.
- AJAX entry: `LocationEditorAjaxController::autoSave()` whitelists `area_id` and calls the same writer.
- Area UI owner: `LocationEditorPage` supplies terms/current IDs and `View/location-editor.php` renders the single selector.
- Area relation writer: `LocationWriteService`, using `wp_set_object_terms()` only on an explicit Area input.
- Derived-index owner: `BML_Location_Indexer`; the normal writer uses the existing canonical index upsert lifecycle. No rebuild was added.

## Write semantics

- No Area (`0`, empty, or null): valid explicit clear.
- One numeric, active leaf `bml_area`: valid assignment; replacement is canonical and removes any invalid prior multiplicity.
- Missing `area_id`: no Area relationship write; existing Area, including inactive Area, is preserved.
- Array, malformed scalar, wrong-taxonomy ID, and non-leaf IDs: rejected before mutation.
- A new inactive Area assignment is rejected. Existing inactive Area can be preserved by an unrelated request, explicitly cleared, or explicitly replaced by an active leaf.
- City is not read, created, deleted, or reassigned by Area processing.
- No migration run, journal, provenance, ownership, or relationship evidence is written.

The HTML form always contains `area_id`; therefore ordinary form and AJAX saves submit an explicit choice (including `0`). The service separately preserves backwards-compatible callers that omit the field.

## UI and entry points

- The editor contains one Area selector, its `No Area assigned` option, hierarchy context, disabled parent/non-leaf Areas, and disabled inactive new options.
- A current inactive Area remains renderable so it is not erased merely by display.
- Focused tests verify both handlers whitelist `area_id`, invoke `$this->writer->save($id, $input)`, and that AJAX contains no Area bypass write. This is the closest repository integration seam; service behavior is covered separately.

## Dedicated coverage and index evidence

`tests/LocationAreaWriteTest.php` passes: **9 tests / 50 assertions**, failures 0, errors 0, warnings 0, skips 0.

It covers assignment, replace (including invalid external multiplicity normalization), explicit clear, missing-field preservation, malformed/multiple/wrong-taxonomy/non-leaf rejection, inactive-new rejection, inactive preservation/clear/replacement, empty dataset, City isolation, migration-evidence isolation, selector structure, and both entry-point wiring.

The test fixture records the canonical index upsert snapshot. It proves assign produces the Area row while preserving City/category, replace removes the stale Area row, and explicit clear removes only the Area row while preserving City/category. No full rebuild or direct index SQL is used.

`tests/bootstrap.php` adds test-only observation of the existing fake indexer's post-term snapshot. It neither changes production runtime loading nor fabricates migration ownership, relation mutations, or a successful production validation result.

## Regression gates

| Check | Result |
|---|---|
| Dedicated Phase 3B | PASS — 9 tests / 50 assertions |
| Phase 3A `AreaAdminTest.php` | PASS — 6 / 23 |
| Existing `LocationWriteServiceTest.php` | PASS — 9 / 30 |
| Migration-critical group (all `AreaMigration*` plus relation index) | PASS — 86 / 399 |
| Full PHPUnit | PASS — 198 / 850 |
| PHP lint (all changed production/test PHP) | PASS |
| PHPStan affected production scope | PASS — 0 errors, 0 new findings |
| `git diff --check` | PASS |

PHPStan used a temporary, analysis-only `BML_DIR` bootstrap. It was not runtime loaded, committed, suppressed, or added to `ignoreErrors`. The known `AdminMenu` parent-slug baseline finding was outside the Phase 3B scope and no new finding appeared.

## Final scope review

Diff review confirms no duplicate Location writer, admin/AJAX bypass, City write, migration ownership/provenance write, direct index SQL, normal-save full rebuild, REST change, frontend Locator change, CSV change, ancestor filtering, or card CSS/JS cleanup.

## Active stand

No deployment or active-stand test was performed for Phase 3B. The active database was not mutated; no City → Area migration, rollback, or index rebuild was run.
