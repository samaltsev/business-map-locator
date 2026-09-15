# Areas Phase 3E — CSV Area Import + Legacy City Compatibility

## Baseline and ownership

- Baseline: `cdbb72160ad5a8537efece7b5e6e952492089f10` on `codex/production-hardening`; verified backup `.codex/backups/2026-09-14_00-02-32_areas-phase-3e-csv-area/`.
- Import entry is `ImportAjaxController` to `ImportManager`; `CsvReader` parses, `ImportMapper` normalizes/maps headers, and `LocationImporter` owns row policy and writes. Import errors remain owned by the existing manager/logger job flow.
- `LocationWriteService` and CSV importer now share `Domain\Area\AreaAssignment` for Area leaf/active/max-one validation. `BML_Location_Index::upsert()` remains the targeted relation-index lifecycle owner.

## Contract

- Canonical field is `area`, one existing `bml_area` leaf slug. No Area creation, hierarchy/path input, name fallback, multi-value input, direct relation-index SQL, or full rebuild is added.
- Unknown, parent, and inactive Areas fail before the location post or taxonomy mutation. Valid Area replaces all direct Area relations with exactly one selected term.
- Omitted Area preserves an existing Area; Area-less creates remain valid. Empty Area preserves under `non_empty_only` and follows existing taxonomy behavior under `overwrite_mapped` (existing taxonomy fields do not clear when the mapped value yields no terms), so it also preserves.
- `create_only` and `update_only` skip before Area validation/mutation.
- Legacy `city` continues through its existing importer path. A City additionally supplies an Area only when its `_bml_area_term_id` points to an existing valid Area whose reciprocal `_bml_migrated_from_city_term_id` matches. Unmapped/stale/same-slug City never guesses or creates an Area and does not erase an existing Area.
- Explicit Area plus trusted City Area must match; mismatch returns `bml_conflicting_area_fields` before mutations. Explicit Area plus unmapped City remains valid.
- Provenance is read only. Import creates no migration run, migration journal, ownership evidence, or City/Area provenance. Existing cache invalidation is reused after successful import writes.

## Verification

- Dedicated `CsvAreaImportTest.php` plus existing import suites: PASS — `40 tests / 107 assertions`.
- Full PHPUnit: PASS — `216 tests / 931 assertions`, zero failures/errors/warnings/skips.
- PHP lint and `git diff --check`: PASS.
- Active stand untouched: no deployment, active import, DB mutation, migration, rollback, or index rebuild.

## Final Completion Evidence

Final-review gate rerun: the counts below are freshly reconfirmed immediately before the isolated Phase 3E commit.

Final publish review fixed the ownership boundary without changing behavior: `trustedAreaForCity()` was removed from `AreaAssignment`; reciprocal City → Area provenance resolution is now owned by `LocationImporter`. `AreaAssignment` has no City taxonomy or migration-provenance references. The final targeted matrix, full `219 / 938` suite, affected-scope PHPStan (`0` findings), lint, and diff check were rerun after that move. The active stand remains untouched.

- Dedicated `CsvAreaImportTest.php` only: PASS — `9 tests / 28 assertions`.
- Generic importer (`LocationImporterUpdateSafetyTest.php`, `LocationImporterVisibilityContractTest.php`): PASS — `34 tests / 86 assertions`.
- Phase 3B writer (`LocationAreaWriteTest.php`): PASS — `9 tests / 50 assertions`.
- Phase 3D (`FilterCountsTest.php`): PASS — `5 tests / 29 assertions`; Phase 3C (`AreaFilteringTest.php`): PASS — `7 tests / 31 assertions`; Phase 3A (`AreaAdminTest.php`): PASS — `6 tests / 23 assertions`.
- Migration-critical suite: PASS — `86 tests / 399 assertions`; broad REST contract suite: PASS — `25 tests / 182 assertions`.
- Fresh full PHPUnit: PASS — `219 tests / 938 assertions`, zero failures/errors/warnings/skips.
- PHPStan affected production scope: PASS — `0` errors using temporary PHP 8.2 configuration/bootstrap (`.codex/phpstan/phase3e.neon`, `.codex/phpstan/bml-dir-bootstrap.php`). The four production files above were analysed; the temporary files are removed after verification. No suppressions were added; this execution had no baseline noise.
- Fresh PHP lint and `git diff --check`: PASS.
- `CsvAreaImportTest.php` proves Area create/replacement/index, both preserve policies, unknown/parent/inactive rejection/no creation, trusted/unmapped/same-slug/stale City handling, matching and conflicting Area+City, explicit Area plus unmapped City, and policy skips. Generic tests preserve existing create/update, mapped-field policy, Category/City, malformed-row, and row-reporting coverage.
- `tests/bootstrap.php` is test-only `term_exists` parity support; it neither bypasses production behavior nor manufactures provenance or index repairs.
- Active stand untouched: no deployment, active import, DB mutation, migration, rollback, or index rebuild. Nothing was staged, committed, or pushed.

## Scope

Changed: shared Area validation, importer/mapping support, test bootstrap parity, dedicated CSV Area tests, and this evidence report. `area_path`, frontend, REST/filter work, Area creation, schema, migration execution, and deployment are absent.
