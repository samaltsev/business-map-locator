# Areas Phase 3A — admin UX (incomplete verification)

Baseline `ffb99ac` on `codex/production-hardening`; verified backup:
`.codex/backups/2026-09-13_18-50-58_areas-phase-3a-admin-ux`.

Ownership map: `ContentTypes` owns `bml_area` registration on `init`; `AdminMenu`
owns NBH navigation; the new `AreaTermMeta` is the only normal Area custom-meta
writer; `BML_Location_Indexer` owns derived relation-index hooks; migration owners
remain `AreaMigrationExecutor` and `AreaRollbackService`.

Implemented, not committed: native hierarchical Area taxonomy UI is enabled and
linked as **Business Map → Areas**. Normal Area metadata keys are
`bml_area_type`, existing `bml_sort_order`, and `bml_area_active`; provenance
keys are neither displayed nor written.

Validation completed: PHP lint passed; migration regression passed (46 tests,
201 assertions); full PHPUnit passed (183 tests, 777 assertions); diff check
passed. PHPStan cannot reach a clean affected-file gate because the existing
`AdminMenu.php` contains a pre-existing `add_submenu_page(null, ...)` contract
finding; including `Plugin.php` also exposes the known missing `BML_DIR`
analysis bootstrap. No deployment, migration, or database mutation occurred.

The working changes remain unstaged and require a focused follow-up to complete
dedicated Area admin behavior coverage and close the PHPStan baseline issue.

## Dedicated Area Admin Regression Coverage

`tests/AreaAdminTest.php` executes six tests / 23 assertions. It covers the
native `bml_area` hierarchy and Areas menu target; valid/invalid type handling,
sort normalization, active state, provenance preservation, City isolation, and
normal WordPress term deletion without Location deletion.

## PHPStan Baseline Classification

An analysis-only `.codex/phpstan/bml-dir-bootstrap.php` defined `BML_DIR` for
static analysis only; no production runtime code or suppression was added.

The exact `AdminMenu.php` nullable `parent_slug` finding is pre-existing:

```text
BASELINE: AdminMenu.php:45 — add_submenu_page() parameter #1 expects string, null given
CURRENT:  AdminMenu.php:46 — same message
```

It belongs to the unchanged hidden-submenu loop, not the new Areas menu entry.
With the helper, `AreaTermMeta.php`, `ContentTypes.php`, and `Plugin.php` pass
PHPStan with 0 errors. Phase-3A new findings: 0.

## Final Test Counts

```text
DEDICATED: 6 tests / 23 assertions
MIGRATION REGRESSION: 46 tests / 201 assertions
FULL BEFORE: 183 tests / 777 assertions
FULL AFTER: 189 tests / 800 assertions
PHP LINT: PASS
DIFF CHECK: PASS
```

## Final Diff Review

One Area metadata writer exists. No duplicate taxonomy registration, provenance
editor, City writer, raw relation-index SQL, frontend, REST, or CSV change was
introduced. No active stand deployment, migration, or database mutation occurred.
