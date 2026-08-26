# Phase 0 — фактическое состояние runtime Business Map Locator 1.3.2-beta29

Дата: 2026-07-29  
Режим: diagnostic only. Production PHP/JS/CSS, REST, schema, options и версия не изменялись.

## 1. Executive summary

Фактическая архитектура — гибридная, но не неопределённая. `BusinessMapLocator\Plugin` является единственным bootstrap/DI owner; namespaced слой владеет Content Types, meta registration, admin actions, `LocationsController`, query handler/repository и import/export application code. Legacy `BML_*` слой по-прежнему является runtime owner frontend, shortcode, provider registry, indexer/index, cache invalidator, migrator и REST registration wrapper. Это не мёртвый legacy: `Plugin::bootLegacyModules()` намеренно запускает его на каждом boot.

Главный подтверждённый P0 runtime defect — endpoint markers не поддерживает wrapped longitude, которое возвращает Leaflet после горизонтального перемещения по world copies. Frontend передаёт значения `getWest()`/`getEast()` без нормализации; `LocationsController::markers()` отвергает каждое значение за пределами `[-180, 180]` до вызова repository. Даже после снятия range check repository не поддерживает crossing antimeridian: он использует одиночное `longitude BETWEEN west AND east`; `west > east` сейчас также отвергается controller.

Плагин готов к узкому инженерному патчу, а не к редизайну. Первый implementation round должен нормализовать/интерпретировать wrapped marker bounds в существующем REST → repository контракте, с regression tests. Это не требует migration, изменения CPT/meta/schema или нового frontend renderer.

## 2. Environment and repository state

| Item | Actual state |
| --- | --- |
| Working source root | `D:\WP plugins\business-map-locator\business-map-locator` |
| Git state | **NOT AVAILABLE — source provided as ZIP.** `.git` отсутствует и у workspace, и у source root. |
| Plugin version | `1.3.2-beta29` |
| WP minimum | `6.4` |
| PHP minimum | `8.1` (`composer.json`: `^8.1`) |
| Slug / text domain | `business-map-locator` / `business-map-locator` |
| Autoload | `vendor/autoload.php` Composer PSR-4 (`BusinessMapLocator\` → `src/`); fallback PSR-4 loader плюс `LegacyClassLoader` для `BML_*`. |
| `vendor/` | Present. |
| `tests/` | Present: PHPUnit tests and a local WordPress-function stub bootstrap. |
| CLI PHP | 8.2.26 |
| Composer | unavailable in this environment (`composer --version` tries to open `\composer.phar`). |
| JS/package tooling | no `package.json`; no JS test runner discovered. |

`pwd`, file inventory to depth two, Git commands, PHP and Composer baseline were run from the actual source root. The absence of Git is an archive-delivery property, not a code failure.

PHP syntax baseline: **PASS** — `php -l` checked **152** PHP files outside `vendor/`; zero syntax failures.

## 3. Bootstrap map

`business-map-locator.php` defines plugin constants, loads Composer (or fallback PSR-4), registers activation/deactivation callbacks, then runs `Plugin::instance()->boot()` on `plugins_loaded`. `Plugin` calls `LegacyClassLoader::register()`, creates the container, registers services, registers core hooks and namespaced admin hooks, then boots selected legacy module instances.

| Подсистема | Bootstrap owner | Runtime implementation | Legacy wrapper | Hook | Фактически активна |
| --- | --- | --- | --- | --- | --- |
| Content types | `Plugin::registerCoreHooks` | `WordPress\ContentTypes` | — | `init` | Yes |
| Meta registration | same | `WordPress\MetaRegistrar` | — | `init` | Yes |
| Capabilities | same | `WordPress\Capabilities` contract | `BML_Capabilities` installer | `admin_init`, priority 1 | Yes |
| REST | `Plugin::bootLegacyModules` | `Rest\LocationsController` | `BML_REST` registers/delegates | `rest_api_init` | Yes |
| Frontend assets/runtime | same | browser `LocatorController` | `BML_Frontend` / renderer | `wp_enqueue_scripts`, `enqueue_block_assets` | Yes when locator renders |
| Shortcode | same | renderer/template path | `BML_Shortcode` | shortcode registration | Yes |
| Gutenberg | `registerCoreHooks` | `WordPress\BlockRegistrar` calling shortcode | `BML_Shortcode` renderer | `init` | Yes |
| Index | service registration + legacy boot | index-table queries via namespaced repository | `BML_Location_Index`, `BML_Location_Indexer` | post/meta/tax hooks; `shutdown` flush | Yes |
| Cache | legacy boot | cache consumers in REST/search | `BML_Location_Cache`, invalidator | post/term/settings hooks | Yes |
| Migrator | legacy boot | `Infrastructure\Database\Migration\SchemaMigrator` is invoked during install | `BML_Migrator` | `admin_init` | Yes |
| Admin | `Plugin::boot` | `Admin\AdminModule` and pages/actions | shared legacy settings/index services | menu/admin-post/AJAX hooks | Yes |
| Settings | AdminModule | `SaveSettingsAction`, `SettingsPage` | `BML_Plugin::settings()` reader | `admin_post_bml_save_settings` | Yes |
| Import | AdminModule | `ImportManager`, jobs, mapper/importer | `BML_Location_Index` called by importer | authenticated AJAX + cleanup schedule | Yes |
| Export | AdminModule | `LocationCsvExporter` | — | `admin_post_bml_export_csv` | Yes |
| Diagnostics | legacy REST wrapper | diagnostic data composition | `BML_Diagnostics` | `GET /diagnostics` | Yes |

The actual legacy boot list is `BML_REST`, `BML_Cache_Invalidator`, `BML_Migrator`, `BML_Location_Indexer`, `BML_Frontend`, `BML_Shortcode` ([Plugin](../../business-map-locator/src/Plugin.php)).

## 4. Canonical vs legacy ownership

| Subsystem | Status | Evidence / conclusion |
| --- | --- | --- |
| Bootstrap/container | NEW CANONICAL | `BusinessMapLocator\Plugin` is sole plugin bootstrap. |
| Content types/meta/block/admin actions/import/export | NEW CANONICAL | namespaced services and `AdminModule` register active hooks. |
| Locations REST behavior | LEGACY WRAPPER OVER NEW CODE | `BML_REST::routes()` calls `LocationsController::registerRoutes()`. |
| Filters/geocode/health/diagnostics REST | LEGACY CANONICAL | callbacks are methods on `BML_REST`. |
| Search repository vs index | PARALLEL IMPLEMENTATIONS | `LocationRepository` queries the index table; `BML_Location_Index` owns table writes/schema representation. They are complementary, not two competing public queries. |
| Frontend JS | LEGACY CANONICAL | `frontend.js` creates `BMLLocatorController`; `map-controller.js` owns browser state and provider calls. |
| Frontend PHP/shortcode/provider registry | LEGACY CANONICAL | `BML_Frontend`, `BML_Shortcode`, `BML_Locator_Renderer`, `BML_Provider_Registry` are booted live. |
| Settings read/write | LEGACY WRAPPER OVER NEW CODE | namespaced action writes `bml_settings`; legacy `BML_Plugin` provides defaults/sanitizer and all readers. |
| `includes/Core/class-bml-plugin.php` | LEGACY CANONICAL, not unused | mapped by `LegacyClassLoader` and referenced by frontend, settings, diagnostics, cache and namespaced admin classes. It loads lazily when one of those usages is reached. |
| Index synchronization | PARALLEL IMPLEMENTATIONS | immediate `upsert/delete` in actions/import and deferred `BML_Location_Indexer` both run. |

There is no evidence of duplicate registration of the same REST route. The legacy wrapper is an active adapter, not an independently implemented locations controller.

## 5. REST inventory

| Route | Method | Registration owner | Callback | Permission | Data source | Frontend consumer |
| --- | --- | --- | --- | --- | --- | --- |
| `/business-map/v1/locations` | GET | `LocationsController`, via `BML_REST` | `LocationsController::index` | public | `wp_bml_locations_index` via `LocationRepository::search` | cards |
| `/locations/markers` | GET | same | `LocationsController::markers` | public | same index via `LocationRepository::markers` | map markers |
| `/locations/{id}` | GET | same | `LocationsController::show` | public | WP post/meta/terms | card/marker detail |
| `/filters` | GET | `BML_REST` | `BML_REST::filters` | public | index aggregation + cache | filter refresh |
| `/geocode/search` | GET | `BML_REST` | `geocode_search` | edit-locations capability | Nominatim + transient cache | admin editor |
| `/geocode/reverse` | GET | `BML_REST` | `geocode_reverse` | edit-locations capability | Nominatim + transient cache | admin editor |
| `/health` | GET | `BML_REST` | `publicHealth` | public | static status | no traced frontend consumer |
| `/diagnostics` | GET | `BML_REST` | `diagnostics` | diagnostics capability | settings/provider/local vendor status | admin/diagnostic consumer not traced in browser |

Only these route registrations were found. No conflicting/double registration of these paths was found.

## 6. Frontend request flows

### Cards

Input/filter change → 300 ms debounce for search or change handler for category/city → `LocatorController::load()` → `LocatorDataSource::loadLocations()` → `GET /locations` with `page`, `per_page` (default 200, capped 500), title ordering and active search/category/city → `LocationsController::index()` → `SearchLocationsQuery::fromArray()` → `SearchLocationsHandler::handle()` → `LocationRepository::search()` → `LocationResponseFactory` → `renderList()` into `.bml-location-card` DOM.

### Markers

Provider `moveend` (Leaflet) or Google `idle` → debounced 300 ms `loadMarkers()` → provider `getBounds()` → `LocatorDataSource::loadMarkers()` → `GET /locations/markers` with north/south/east/west plus filters/search → `LocationsController::markers()` validation → `LocationRepository::markers()` → `MarkerController::setLocations()` → active provider markers.

### Detail

Card/marker selection → `loadDetail(id)` → `GET /locations/{id}` → `LocationsController::show()` → `LocationDetailResponseFactory` → selected card, viewport focus and existing popup controller. Detail request errors are not separately caught in `loadDetail`; failed fetch propagates to caller rather than using the generic cards/markers error handler.

Concurrency/lifecycle facts:

- Cards, markers and details each have their own `AbortController`; a newer same-type request aborts the preceding one.
- Cards, markers and details additionally have monotonic sequence counters; stale successful responses are ignored.
- Search and map moves are debounced at 300 ms; category/city changes are not debounced.
- Server-side cards pagination exists, but the controller currently renders the current page only; no automatic all-page request is made.
- Generic request errors produce `strings.requestError`; geolocation/provider errors have dedicated messages. Loading state is not a dedicated modeled state in the inspected controller.
- `frontend.js` initializes every `.bml-locator`, destroys an existing root controller before re-init, and `destroy()` aborts requests, removes listeners, clears markers and destroys the map. Multiple locator roots are architecturally supported but not browser-tested.

## 7. Marker bounds root cause

### Confirmed causal chain

1. `OpenStreetMapProvider::getBounds()` returns raw Leaflet `map.getBounds().getWest()/getEast()`; `GoogleMapsProvider::getBounds()` returns raw Google bounds longitudes.
2. `LocatorController::loadMarkers()` passes that object directly into `new URLSearchParams(bounds)`. There is no longitude wrapping or normalization in frontend JS.
3. Leaflet can legitimately expose longitudes outside `[-180, 180]` when the map is horizontally panned through world copies. The supplied `west=-340.310...`, `east=-327.808...` request is therefore representable by the current frontend.
4. `LocationsController::markers()` calls `filter_var(..., FILTER_VALIDATE_FLOAT)` and then rejects east/west values outside `[-180,180]` with `bml_invalid_bounds` / HTTP 400. The request **does not reach** `LocationRepository::markers()`.
5. The repository does not independently normalize wrapped values and uses `longitude BETWEEN %f AND %f`, so it also cannot express antimeridian crossing.

### Contract answers

| Question | Answer |
| --- | --- |
| How does frontend obtain bounds? | Active provider `getBounds()`; raw Leaflet/Google longitude values. |
| Does frontend normalize longitude? | No. |
| Can provider pass outside range? | Yes: confirmed for Leaflet world copies; Google behavior is not browser-tested. |
| Where is request rejected? | `LocationsController::markers()` range validation. |
| Does it reach repository? | No. |
| Does repository support antimeridian? | No; only ordinary `BETWEEN west AND east`. |
| Can `west > east` be valid? | Yes, after canonical wrapping it represents a viewport crossing +180/-180 and must mean `longitude >= west OR longitude <= east`. |

Canonical endpoint contract should accept finite longitudes from map providers, use the viewport span to identify full-world bounds, normalize finite longitude endpoints to `[-180,180]`, and split/OR the repository query when normalized west exceeds east. Strict rejection is incompatible with current Leaflet behavior. Frontend-only normalization would leave external API consumers and antimeridian semantics unresolved; backend normalization plus repository split is the canonical repair. No migration is required.

Required regression cases:

1. normal European bounds;
2. `west < -180` world copy;
3. `east > 180` world copy;
4. full world / span >= 360°;
5. crossing `+180/-180` (`west > east` after normalization);
6. multiple world copies;
7. `south > north` rejection;
8. non-numeric input;
9. `NaN`/`INF`/Infinity-like input rejection;
10. category/city/search filters retain their marker semantics across every valid longitude case.

## 8. Location contract

Legend: **Y** direct support; **P** derived/partial; **—** absent.

| Field | Admin input | Meta/taxonomy | Index column | REST list | REST marker | REST detail | CSV import | CSV export |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| title | Y | `post_title` | title | Y | Y | Y | Y | Y |
| excerpt | P | `post_excerpt` / content-derived | excerpt | Y | — | Y | — | — |
| description | Y | `post_content` | derived search/excerpt only | — | — | Y | — | — |
| address | Y | `bml_address` | address | Y | — | Y | Y | Y |
| postcode | Y | `bml_postcode` | postcode | Y | — | Y | Y | Y |
| region | Y | `bml_region` | region | Y | — | Y | Y | Y |
| country | Y | `bml_country` | country | Y | — | Y | Y | Y |
| latitude | Y | `bml_lat` | latitude | Y | Y | Y | Y | Y |
| longitude | Y | `bml_lng` | longitude | Y | Y | Y | Y | Y |
| phone | Y | `bml_phone` | phone | Y | — | Y | Y | Y |
| email | Y | `bml_email` | email | Y | — | Y | Y | Y |
| website | Y | `bml_website` | website | Y | — | Y | Y | Y |
| hours | Y | `bml_hours` | hours | Y | — | Y | Y | Y |
| operational_status | Y | `bml_operational_status` | operational_status | Y | Y | Y | P (post status is imported) | P (post status exported) |
| image | Y | `_thumbnail_id` | image_id | Y | — | Y | — | — |
| external_id | no editor field traced | `bml_external_id` | — | — | — | — | Y | Y |
| category | Y optional | `bml_category` | category/category_slug, first term | Y | Y | Y | Y | Y |
| city | Y optional | `bml_city` | city/city_slug, first term | Y | — | Y | Y | Y |
| area | no canonical UI | `bml_area` registered | — | — | — | — | — | — |
| services | no Free contract | no canonical field | — | empty list | — | — | — | — |
| social/messenger | no Free contract | no canonical field | — | hard-coded empty list fields | — | — | — | — |

## 9. Index synchronization

`BML_Location_Index` is the sole table writer/deleter for `wp_bml_locations_index`; both cards and markers query that same table through `LocationRepository`. The table stores title, address, first city/category, region/country/postcode, coordinates, thumbnail ID, contact fields, hours, excerpt, operational status, visibility, search text and timestamp. It omits external ID, area, services, socials, full description, arbitrary multi-term values and sort order.

Synchronization paths:

- `SaveLocationAction`, duplicate and bulk actions call immediate `upsert()`/`delete()` and invalidate cache.
- `LocationImporter` writes post/meta/terms then calls immediate `upsert()`.
- `BML_Location_Indexer` observes `save_post_bml_location`, relevant meta and category/city term changes, adds affected IDs to a dirty set and performs another `upsert()` at `shutdown`.
- delete/trash call `delete()`; untrash marks for deferred resync. Bulk/delete paths can therefore issue an explicit delete plus observer delete, which is idempotent but duplicate work.
- direct writes to fields outside `BML_Location_Indexer::META_KEYS`, notably external ID/services/area, do not trigger an index change (those fields are not indexed).
- cache invalidator observes save/delete/category/city/settings events; search handler also uses `LocationCache` keyed by query.

An incomplete index row is possible only to the extent data is saved in separate operations: immediate action/import upserts after their writes, while ordinary WordPress lifecycle eventually coalesces at shutdown. The indexer makes repeated writes more likely than missing core indexed fields, but runtime ordering was not executed.

## 10. Test baseline

`composer.json` declares PHPUnit, PHPCS/WPCS and PHPStan but has no Composer `scripts`. `phpunit.xml.dist` points to `tests/bootstrap.php`; that bootstrap supplies a lightweight local stub of selected WordPress functions, not an installed WordPress integration test environment. Real PHPUnit test files exist. There are no JS tests and no `package.json`.

| Check | Status | Actual result |
| --- | --- | --- |
| PHP lint | PASS | 152 source PHP files, zero errors. |
| PHPUnit | FAIL | prior current-workspace run: 94 tests, 384 assertions, 1 error — `ImportJobProgressMarkersTest` cannot find `BML_Schema`. The suite can start without manual setup but is not green. |
| PHPCS | FAIL | WPCS run exits 2 with widespread style/file naming violations. |
| PHPStan | FAIL | 111 findings, including undefined runtime constants and PHP 8.2 `readonly` versus declared PHP 8.1 support. |
| Composer test/lint/install | NOT RUN | Composer unavailable; no project scripts. |
| JS tests | NOT RUN | no JS test tooling in archive. |

Marker coverage currently checks that category/city/search filters pass from JS to controller/repository (`MarkerFilterSyncTest`); it does not exercise bounds normalization, crossing or rejection cases.

## 11. Design System readiness

| Group | Readiness | Reason |
| --- | --- | --- |
| Directory | PARTIALLY READY | stable cards/index REST but test baseline red. |
| Map | BLOCKED BY RUNTIME OWNERSHIP | wrapped bounds defect in the live map request path. |
| Location Detail | PARTIALLY READY | endpoint and popup path exist; browser/a11y not verified. |
| Filters | PARTIALLY READY | category/city contract works; no Area canonical path. |
| Mobile states | BLOCKED BY TEST COVERAGE | no browser/JS visual/a11y coverage. |
| Admin Dashboard / Locations List / Location Editor / Categories | PARTIALLY READY | active namespaced owners exist. |
| Areas | BLOCKED BY DATA CONTRACT | registered taxonomy is not in index/REST/CSV/editor public path. |
| Import/Export | PARTIALLY READY | robust jobs but field parity incomplete. |
| Shortcode Builder | BLOCKED BY RUNTIME OWNERSHIP | shortcode exists; no canonical builder UI owner. |
| Gutenberg | PARTIALLY READY | dynamic block delegates to canonical shortcode. |
| Display settings | PARTIALLY READY | saved settings exist; some provider settings are not propagated. |
| Map providers | PARTIALLY READY | OSM/Google path exists; Google clustering absent and browser configuration unverified. |
| Diagnostics | PARTIALLY READY | protected REST diagnostic data exists; board UI not verified. |

## 12. Findings

### P0 — wrapped marker longitude produces a public locator failure

**Evidence:** raw provider bounds enter `URLSearchParams`; controller rejects east/west outside range before repository.  
**Impact:** panning Leaflet across world copies generates 400 and removes markers.  
**Affected files:** `assets/js/map-controller.js`, `assets/js/providers/openstreetmap-provider.js`, `src/Rest/LocationsController.php`, `src/Infrastructure/Database/LocationRepository.php`.  
**Recommended next action:** backend canonicalization of finite longitudes and full-world/crossing query semantics, with existing REST endpoint retained.  
**Required regression:** all ten bounds cases listed in section 7, including repository SQL predicates for crossing.

### P1 — immediate and deferred index synchronization overlap

**Evidence:** actions/import call `BML_Location_Index::upsert()` directly and `BML_Location_Indexer` later observes the same save/meta/term changes.  
**Impact:** redundant database writes and potentially difficult ordering diagnostics; currently no data loss is proven.  
**Affected files:** `SaveLocationAction`, `LocationImporter`, `BML_Location_Indexer`, bulk/duplicate/delete actions.  
**Recommended next action:** characterize with integration tests before consolidating ownership; do not remove either path in UI work.  
**Required regression:** one write transaction emits a final correct row after save/meta/taxonomy, import, trash/untrash and bulk status change.

### P1 — test baseline is not green

**Evidence:** PHPUnit, PHPCS and PHPStan results in section 10.  
**Impact:** subsequent UI/REST changes lack a fully trusted automated gate.  
**Affected files:** test bootstrap/config and broad codebase.  
**Recommended next action:** a separate test-bootstrap/quality round after the bounds bug or before broad refactor.  
**Required regression:** PHPUnit suite passes without loosening assertions; targeted lint/static baselines are documented.

### P2 — Area is registered but non-canonical

**Evidence:** `bml_area` registration exists; editor, index, REST parameters and CSV operate on `bml_city`.  
**Impact:** Area-design work cannot be honestly implemented end-to-end.  
**Affected files:** content types, admin taxonomy UI/actions, index, REST, importer/exporter.  
**Recommended next action:** separate backwards-compatible Area contract/migration decision.  
**Required regression:** future Area data must survive editor → index → REST → CSV paths.

## 13. Recommended engineering rounds

### Round 1

Goal: Normalize wrapped longitude bounds for `/locations/markers`.  
Allowed files: `src/Rest/LocationsController.php`, `src/Infrastructure/Database/LocationRepository.php`, focused `tests/*Marker*` tests.  
Forbidden: frontend redesign, schema/meta/options changes, migration, provider rewrite, version/ZIP.  
Regression: section 7 cases.  
Acceptance: world-copy and antimeridian requests return correct markers rather than 400; invalid latitude/order/non-finite input remains rejected.

### Round 2

Goal: Repair PHPUnit bootstrap failure for `BML_Schema`.  
Allowed files: `tests/bootstrap.php`, failing test/config only.  
Forbidden: production behavior or design changes.  
Regression: full PHPUnit suite.  
Acceptance: suite is green without weaker tests.

### Round 3

Goal: Characterize and rationalize index synchronization ownership.  
Allowed files: tests and narrowly approved index/action code.  
Forbidden: UI and REST redesign.  
Regression: save/import/bulk/trash/untrash/caches.  
Acceptance: one documented owner strategy with no stale index rows.

### Round 4

Goal: Decide and implement Area canonical contract with beta compatibility.  
Allowed files: explicit approved migration, relevant editor/index/REST/CSV/test files.  
Forbidden: destructive data change and Design System UI.  
Regression: City compatibility plus Area end-to-end parity.  
Acceptance: owner-approved migration/rollback evidence.

### Round 5

Goal: Propagate existing display settings through the current frontend/provider path.  
Allowed files: existing settings/frontend/provider files and focused tests.  
Forbidden: new settings schema or screen redesign.  
Regression: config propagation/provider fallback.  
Acceptance: saved supported settings visibly affect existing maps.

### Round 6

Goal: Adapt Directory/Filters/Detail Free UI to boards using existing contracts.  
Allowed files: existing frontend templates/assets and tests.  
Forbidden: copied board HTML, new data contract, Pro work.  
Regression: cards/markers/detail/multiple instance.  
Acceptance: no endpoint or shortcode compatibility regression.

## 14. Exact next implementation task

```text
# Codex task — normalize wrapped marker longitude bounds

Implement exactly one confirmed root cause: valid Leaflet world-copy bounds are
rejected by GET /business-map/v1/locations/markers before repository lookup.

Before changes: run the project safety preflight and create a verified backup
with .codex\scripts\New-PluginBackup.ps1. Stop and restore the stand on a
failed deployment verification.

Allowed production files:
- business-map-locator/src/Rest/LocationsController.php
- business-map-locator/src/Infrastructure/Database/LocationRepository.php

Allowed tests:
- add/modify focused marker-bounds PHPUnit tests under business-map-locator/tests/

Requirements:
1. Preserve route, method, response shape, all existing filters and REST
   permissions. Do not change schema, indexes, CPT/meta/options, frontend JS,
   providers, migrations, version or ZIP.
2. Continue rejecting invalid/non-finite numeric input and south > north.
3. Accept finite longitude values outside [-180,180] from provider world copies.
   Determine full-world coverage from the original span; normalize ordinary
   endpoints to canonical longitude; represent antimeridian crossing with a
   split/OR longitude condition rather than an invalid BETWEEN range.
4. Ensure normal European bounds retain current SQL/filter behavior and that
   category, city and search filters apply to every split clause.
5. Add regression cases for: normal bounds, west<-180, east>180, full world,
   +180/-180 crossing, multiple copies, south>north, invalid numeric and
   NaN/Infinity-like values.
6. Run PHP lint and focused PHPUnit; run available full PHPUnit/PHPCS/PHPStan
   checks and report PASS/FAIL/NOT RUN honestly. Synchronize to stand and run
   postflight; request browser verification for Leaflet horizontal panning.

Forbidden:
- any Design System work, Area/City migration, provider refactor, cache rewrite,
  display-setting work, dependency/CDN additions, version bump or ZIP build.

Acceptance:
- The supplied wrapped bounds example no longer returns HTTP 400 solely because
  its longitude values are outside the canonical range.
- Antimeridian and full-world cases produce correct repository filtering.
- Invalid geographic input remains a 400.
- Regression tests prove both compatibility and the new wrapped behavior.
```

## Changed files

Only this diagnostic report was added: `docs/audits/phase-0-runtime-baseline.md`. Production files were not changed.
