# Areas Phase 3D — Contextual Filter Counts + Locations Without Area

## Baseline and ownership

- Branch/HEAD before changes: `codex/production-hardening` / `a1aeef9572ce73b22bc0d840ffe7fd248384d9cd`.
- Verified backup: `.codex/backups/2026-09-13_23-16-11_areas-phase-3d-filter-counts/`.
- `/filters` remains owned by `BML_REST`; its former scalar-index count method was removed.
- `LocationRepository` is now the sole count and public-read predicate owner. `AreaDescendantResolver` remains the hierarchy owner; the relation index remains the direct relationship read owner. `BML_Location_Cache` owns REST-cache lifecycle.

## Contract implemented

- Public parameter: `without_area=1`; no magic term is used.
- `area` plus `without_area=1` returns `400 bml_conflicting_area_filters` on locations, markers, bounds, and filters.
- Area-less public locations remain included unless the new parameter is set.
- Category, city, and Area facets use standard self-dimension semantics: each grouped count applies every other active public predicate but omits its own selectable dimension.
- Counts retain publication, hidden-status, coordinates, category/city, Area-subtree, search, and missing-Area semantics from the repository predicate owner.
- Category/city grouping reads `bml_location_terms`; it no longer derives counts from scalar category/city fields. The existing normal category/city compatibility filter remains unchanged.
- Area counts are one grouped direct-area relation-index query then aggregate the active hierarchy in memory through `AreaDescendantResolver`. No ancestor rows are written or required. Empty active Areas remain `count: 0, available: false`; inactive Areas are not advertised.
- `without_area` is a virtual metadata object `{count, available}` calculated with a correlated `NOT EXISTS` direct `bml_area` relation-index predicate.
- `/filters` now accepts contextual `area`, `without_area`, and `search`. Bounds/radius are intentionally not added to this existing endpoint contract; the count implementation reuses the same predicate model for future extension.
- `/locations`, `/locations/markers`, and `/locations/bounds` accept `without_area` and pass it to the same repository predicate path.

## Query and cache strategy

- Exactly three grouped relation-index count queries (category/city/Area) plus one missing-Area aggregate query per uncached filters request; there is no query per Area option.
- Area aggregation is in memory from one direct-count map. Since the location write contract has at most one direct Area, a location contributes at most once in an Area subtree.
- Filter cache keys now include `category`, `city`, `area`, `without_area`, and `search`; location cache keys include `without_area`. Existing relation/status cache invalidation and Area hierarchy invalidation continue to invalidate these results.
- The round adds no relation, term, provenance, index, migration, rollback, or rebuild writes.

## Files

- `business-map-locator/includes/REST/class-bml-rest.php`
- `business-map-locator/src/Application/Location/SearchLocationsQuery.php`
- `business-map-locator/src/Infrastructure/Database/LocationRepository.php`
- `business-map-locator/src/Plugin.php`
- `business-map-locator/src/Rest/LocationsController.php`
- `business-map-locator/tests/AreaFilteringTest.php`
- `business-map-locator/tests/FilterCountsTest.php`

## Verification

- Dedicated FilterCountsTest: `5 tests / 29 assertions` — pass.
- Phase 3C AreaFilteringTest: `7 / 31` — pass.
- Phase 3B LocationAreaWriteTest: `9 / 50` — pass.
- Phase 3A AreaAdminTest: `6 / 23` — pass.
- Migration regression (`AreaMigration*` plus direct relation-index regression): `86 / 399` — pass.
- REST regression reconciliation: the earlier `11 / 58` invocation covered only `RestCardDetailContractTest.php` and `RestUrlCompatibilityContractTest.php`. The Phase 3C broad set is rerun fresh and passes `25 / 182`: `RestCardDetailContractTest.php`, `RestUrlCompatibilityContractTest.php`, `SearchLocationsQueryViewportRadiusContractTest.php`, `FrontendMarkerViewportContractTest.php`, `ViewportMarkerLayerLifecycleContractTest.php`, `SplitViewportDirectoryContractTest.php`, and `SelectedLocationInteractionContractTest.php`. REST coverage lost: no.
- Full suite: `210 / 910` — pass, failures/errors/warnings/skips: zero.
- PHPStan changed production scope: no errors; no suppressions added.
- PHP lint: pass. `git diff --check`: pass.

## Final review evidence

- Exact intended commit set is the seven source/test paths above plus this report; unrelated tracked paths: zero. Historical `.codex/acceptance` and prior dated reports are excluded artifacts.
- Counts have no REST-layer SQL and no duplicate count implementation. The one `filterCounts()` owner applies public visibility, hidden-status exclusion, search, category, city, Area subtree, and missing-Area predicates. `/filters` intentionally exposes only category, city, area, without-area, and search — not bounds/radius.
- Each taxonomy dimension passes its own name as the predicate exclusion while preserving every other active filter; public restrictions are never excluded.
- Read filters are side-effect free: no term/provenance/index/migration/rebuild writes. Existing `BML_Location_Cache` version invalidation covers Location and relation changes; Area taxonomy hooks also invalidate it while `AreaDescendantResolver` invalidates hierarchy resolution.
- Fresh final commands passed: dedicated `5 / 29`; Phase 3A/3B/3C combined `22 / 104`; migration `86 / 399`; broad REST `25 / 182`; full `210 / 910`; PHPStan production scope clean; lint and diff-check clean.

## Active stand and git

- No deploy, active-DB mutation, migration, rollback, or index rebuild was performed.
- Nothing was staged, committed, or pushed.

Ready for review. The next round is **Areas Phase 3D — Final Review + Isolated Commit**.
