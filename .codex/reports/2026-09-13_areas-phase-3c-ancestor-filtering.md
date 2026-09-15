# Areas Phase 3C — Ancestor-aware Area filtering

## Baseline and safety

- Baseline: `0c96461bb01c4860cf612eabe39b53f85ec7924d` on `codex/production-hardening`.
- Verified source/stand backups: `.codex/backups/2026-09-13_21-41-38_areas-phase-3c-ancestor-filtering/` and retry `.codex/backups/2026-09-13_21-42-42_areas-phase-3c-filtering-precheck/`.
- No stand deployment, database mutation, migration, rollback, or index rebuild was performed.

## Read ownership and public contract

- `/locations`, `/locations/markers`, and `/locations/bounds` are owned by `LocationsController` and delegate to `LocationRepository`.
- `/filters` remains owned by legacy `BML_REST` and receives the same `AreaDescendantResolver` from the container.
- Public identity is the established taxonomy slug convention: `area=<area-slug>`.
- Existing unknown category/city filters produce a successful empty result; unknown Area now follows that policy by adding `1 = 0` to the canonical repository query.
- `area` composes with category, city, search, bounds, and radius through the shared WHERE predicate, therefore uses normal AND semantics.

## Resolver, cache, and direct index semantics

- `AreaDescendantResolver` resolves selected Area plus every descendant at arbitrary depth using native `bml_area` parents; it does not modify Location taxonomy relationships.
- Its versioned object-cache key is WordPress-object-cache compatible and has a request cache. `created_bml_area`, `edited_bml_area`, and `delete_bml_area` invalidate it; the location REST cache invalidator also now invalidates Area taxonomy changes.
- The repository queries `bml_location_terms` only for direct `bml_area` rows whose term ID is in the resolved descendant set. No ancestor rows, direct controller SQL, schema, or full rebuild were added.

## Endpoint behavior

- Locations pagination totals are based on the Area-filtered canonical query.
- Markers and bounds pass Area through to the same repository predicate.
- `/filters` returns an `areas` flat hierarchy representation with `term_id`, `slug`, `name`, `parent`, and `depth`; inactive Areas are omitted and no counts are reported.
- A direct request for an inactive Area follows the existing taxonomy-slug filtering policy; only its advertisement in `/filters` is suppressed.
- Empty Area taxonomies and Area-less locations remain valid because no Area predicate is added unless `area` is supplied.

## Coverage

- `AreaFilteringTest.php`: deep leaf/ancestor/root resolution, empty and unknown Areas, create/reparent/delete invalidation, active public options, query cache identity, shared repository SQL for locations/markers/bounds, category/city/search composition, and direct-only index contract.
- Dedicated `AreaFilteringTest.php`: PASS — 7 tests / 31 assertions. Cache create/reparent/delete assertions invoke the registered production taxonomy hooks, not `invalidate()` directly.
- Phase 3B `LocationAreaWriteTest.php`: PASS — 9 / 50; Phase 3A `AreaAdminTest.php`: PASS — 6 / 23.
- Migration-critical group: PASS — 86 / 399. REST regression group: PASS — 25 / 182 (`RestCardDetailContractTest`, `RestUrlCompatibilityContractTest`, `SearchLocationsQueryViewportRadiusContractTest`, `FrontendMarkerViewportContractTest`, `ViewportMarkerLayerLifecycleContractTest`, `SplitViewportDirectoryContractTest`, and `SelectedLocationInteractionContractTest`). Full PHPUnit: PASS — 205 / 881, with failures/errors/warnings/skips all zero.
- PHP lint and `git diff --check`: PASS. PHPStan Phase 3C scope: 0 errors under the actual PHP 8.2 target using temporary analysis-only `BML_DIR`/`ARRAY_A` definitions. The project PHP 8.1 static baseline reports existing `readonly` and WordPress-stub `ARRAY_A` configuration noise; no suppression or production change was made.

## Scope review

Only Area read/query behavior, invalidation, REST argument schemas, filter representation, dedicated tests, and this report are changed. No Location writer, frontend controller, Area UI, filter counts, CSV, migration execution, cards, release, or deploy change is included.
