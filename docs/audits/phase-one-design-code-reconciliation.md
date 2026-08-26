# Design System Phase One — Code Reconciliation Audit

Date: 2026-07-29  
Mode: diagnostic / architecture reconciliation. No production PHP, JS, CSS, schema, options, REST contracts, versions, or ZIP artefacts were changed.

## 1. Executive summary

The current plugin is a working beta with a namespaced application/admin layer and a deliberately booted legacy runtime layer. The split is not merely historical: both layers are active on every request. Namespaced code owns the CPT, meta registration, REST search repository and admin actions; legacy `BML_*` code owns the frontend renderer, shortcode, provider registry, index implementation, migration hook, cache invalidation and the REST registration wrapper.

The major architecture risk is therefore ownership overlap, not an absent locator. A Design System implementation must extend the existing renderer and contracts rather than add a third UI/data path. The second P0 concern is the product-model mismatch: the code's public filtering contract is `bml_city`/`city`, while the Phase One boards explicitly require **Areas** as the product model. `bml_area` is registered but is not the canonical data flow.

The Free locator is sufficient to begin narrowly scoped, contract-preserving visual work, but it is not ready for wholesale board-to-HTML replacement. The recommended first implementation milestone is to make the existing saved display settings (`map_style`, `marker_color`) affect the existing frontend map renderers, with regression coverage. It is a confirmed, isolated defect and does not alter storage, REST, migrations, or release metadata.

## 2. Repository state

| Item | Result |
| --- | --- |
| Workspace | `D:\WP plugins\business-map-locator` |
| Actual plugin source root | `business-map-locator/` |
| Git | **NOT AVAILABLE**: neither workspace nor source root contains `.git`; branch, commit and dirty-file state cannot be determined. |
| Plugin | `Business Map Locator`, slug/text domain `business-map-locator`, version `1.3.2-beta29` ([bootstrap](../../business-map-locator/business-map-locator.php)) |
| PHP/runtime requirement | PHP `^8.1`; local CLI PHP `8.2.26` |
| Autoload | Composer PSR-4 `BusinessMapLocator\\` → `src/`, with fallback PSR-4 loader and legacy class loader ([composer.json](../../business-map-locator/composer.json), [bootstrap](../../business-map-locator/business-map-locator.php)) |
| Test tooling | PHPUnit 10, PHPCS/WPCS and PHPStan; no `scripts` section in `composer.json`; no `package.json` |
| Schema version | `BML_Database::VERSION = 1.3.3` |
| Design input | all 49 boards plus supporting upload notes were inspected from the supplied ZIP; boards are treated as UX specification, not executable markup. |

Directory map: `src/` contains namespaced domain/application/admin/REST/import code; `includes/` contains live legacy runtime modules; `assets/` contains unbundled browser assets and local Leaflet/MarkerCluster vendor copies; `templates/` contains the Free locator fragments; `tests/` contains static and unit-style tests.

## 3. Runtime bootstrap map

| Subsystem | Runtime owner | Legacy owner | Hook / registration | Status | Risk |
| --- | --- | --- | --- | --- | --- |
| Bootstrap / DI | `BusinessMapLocator\\Plugin` | legacy loader | `plugins_loaded` → `Plugin::boot()` | active | Both dependency styles are live. |
| CPT/taxonomies | `WordPress\\ContentTypes` | none | `init` | active | `bml_area` exists, but `bml_city` remains the usable locator taxonomy. |
| Meta registration | `WordPress\\MetaRegistrar` | none | `init` | active | Free-field registration exists; all new fields need contract review. |
| Admin UI/actions | `Admin\\AdminModule` | `BML_Capabilities` | `admin_menu`, `admin_post_*`, AJAX | active | Namespaced canonical UI/action layer. |
| Settings persistence | `Admin\\Settings\\Action\\SaveSettingsAction` | `BML_Plugin::settings()` | `admin_post_bml_save_settings` | active | Namespaced writer, legacy option reader. |
| REST route registration | `Rest\\LocationsController` | `BML_REST` | `rest_api_init` | active | Wrapper owns registration; controller owns locations behavior. |
| Search / cards | `Application\\Location\\SearchLocationsHandler`, `Infrastructure\\Database\\LocationRepository` | `BML_Location_Index` table | REST `GET /locations` | active | Query is index-only; no `WP_Query` fallback. |
| Indexing | none | `BML_Location_Index`, `BML_Location_Indexer` | post/meta/taxonomy hooks + `shutdown` | active | Indexed fields omit external ID, services, area and social fields. |
| Frontend / shortcode | none | `BML_Frontend`, `BML_Locator_Renderer`, `BML_Shortcode` | enqueue hooks; both shortcodes | active | Canonical frontend remains legacy. |
| Block | `WordPress\\BlockRegistrar` | calls `BML_Shortcode` | `init` | active | Block correctly reuses shortcode renderer. |
| Map providers | none | provider registry and `BML_*_Provider` classes | enqueue through frontend | active | OSM clusters; Google does not. |
| Import/export | namespaced `ImportManager`, exporter | index class invoked by import/action paths | authenticated AJAX/admin post/cron | active | Import contract is narrower than Location UI/Design board. |
| Migrations / diagnostics | namespaced schema migrator | `BML_Migrator`, diagnostics | `admin_init`, REST | active | Upgrade work may run on an admin request; no runtime verification performed. |

Activation invokes `Plugin::activate()` → `Lifecycle\\Activator::run()`; deactivation invokes `Deactivator::run()`. `Plugin::boot()` first registers namespaced core/admin hooks and then calls `hooks()` on `BML_REST`, `BML_Cache_Invalidator`, `BML_Migrator`, `BML_Location_Indexer`, `BML_Frontend`, and `BML_Shortcode` ([Plugin](../../business-map-locator/src/Plugin.php)). This establishes the actual runtime owner map.

Registered public contracts: CPT `bml_location`; taxonomies `bml_category`, `bml_city`, `bml_area`; shortcodes `[business_map_locator]` and beta alias `[business_locator]`; dynamic Gutenberg block `business-map-locator/business-locator`; and `business-map/v1` routes for locations, markers, detail, filters, geocoding, health and diagnostics.

## 4. Location contract matrix

Legend: **Y** supported; **P** partly/derived; **—** absent. Storage means canonical persisted source; index means available in `wp_bml_locations_index`.

| Field | Admin UI / validation | Storage | Index | REST card / detail | Frontend | CSV in / out | Risk |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Title | Y / required | `post_title` | Y | Y / Y | Y | Y / Y | low |
| Excerpt | P / no explicit editor field | `post_excerpt`/derived content | Y | Y / Y | card | — / — | Design needs short description control. |
| Full description | Y | `post_content` | derived only | — / Y | detail only | — / — | no card/search parity guarantee. |
| Image/thumbnail | Y / attachment check | `_thumbnail_id` | `image_id` | Y / Y | card/detail | — / — | saved correctly; CSV has no image contract. |
| External ID | not editor-exposed | `bml_external_id` | — | — / — | — | Y / Y | import-only identifier; not searchable/card-visible. |
| Address | Y / sanitized | `bml_address` | Y | Y / Y | Y | Y / Y | low |
| Postcode / region / country | Y | `bml_*` | Y | Y / Y | Y | Y / Y | low |
| Latitude / longitude | Y / range checked | `bml_lat`, `bml_lng` | Y | Y / Y | required for locator | Y / Y | location without valid coordinates is hidden from public REST. |
| Phone / email / website | Y / email+URL sanitized | `bml_*` | Y | Y / Y | settings-gated | Y / Y | endpoint preserves values; UI flags are not fully traced to card rendering. |
| Plain hours | Y | `bml_hours` | Y | Y / Y | Y | Y / Y | no machine-readable schedule/open-now computation. |
| Operational status | Y / `active`, `temporarily_closed`, `hidden` | `bml_operational_status` | Y | Y / Y | Y | input `status` means post status; output `status` means post status | `open` normalizes to `active`; no valid real-time “Open now”. |
| Category | optional | `bml_category` | first term only | Y / Y | filter/card | Y / Y | locations without category remain included. |
| City / Area | City UI optional; Area no canonical UI | `bml_city`; `bml_area` registered | city only | city Y / Y; area — | city filter | city Y / Y; area — | **P0** product-model mismatch. |
| Services | no Free editor | no canonical Free field | — | empty array / — | — | — / — | Pro/data-contract blocker. |
| Messenger/social | no editor | no canonical Free field | — | hard-coded empty card values / — | — | — / — | cannot implement boards without contract. |
| Sort order | no editor trace | `menu_order` possible WP field | — | accepts `menu_order` but SQL maps it to title | — | — | implementation discrepancy. |

Manual save is presence-aware: it updates only submitted Free fields and does not clear unknown/Pro meta. It preserves the thumbnail unless `remove_featured_image` is set; it also validates and synchronizes the legacy index ([SaveLocationAction](../../business-map-locator/src/Admin/Location/Action/SaveLocationAction.php), [LocationContractTest](../../business-map-locator/tests/LocationContractTest.php)). Hidden locations are deliberately excluded by the index queries. `open` is a legacy alias for `active`, not an availability evaluation.

## 5. Admin → frontend data flow

1. `AdminMenu` renders the namespaced location editor; its form posts to `admin_post_bml_save_location_custom`.
2. `SaveLocationAction::handle()` authorizes, validates title/coordinates/status, writes the `bml_location` post, selected `bml_category`/`bml_city`, explicit submitted `bml_*` meta and thumbnail.
3. The action calls `BML_Location_Index::upsert()`. Independently, `BML_Location_Indexer` observes save/meta/term hooks, batches dirty IDs and upserts at `shutdown`; cache invalidation is separately hooked.
4. `BML_Location_Index::upsert()` writes `wp_bml_locations_index` with public visibility, first category/city, coordinates and searchable text.
5. `BML_REST::routes()` delegates `GET /business-map/v1/locations` to `LocationsController`; its handler queries the index and produces the card DTO.
6. `BML_Frontend::localize()` supplies the REST base URL, settings and active provider to `assets/js/frontend.js`; `LocatorController` creates per-root controllers in `assets/js/map-controller.js`.
7. The controller renders cards from `/locations`, map markers from bounds-specific `/locations/markers`, and details from `/locations/{id}` into the existing templates/DOM.

## 6. REST/search/index map

| Route | Actual owner/path | Key request/response behavior |
| --- | --- | --- |
| `GET /business-map/v1/locations` | `LocationsController::index` → `SearchLocationsHandler` → `LocationRepository::search` → index table | `search`, `category`, `city`, `page`, `per_page` (1–500), bounds/bbox, `lat`, `lng`, `radius`, unit and order. Returns `items` plus `pagination.totalPages`. |
| `GET /locations/markers` | `LocationsController::markers` → `LocationRepository::markers` | required north/south/east/west; category/city/search; 1,000 markers returned then `truncated=true` (hard max 2,000 internally). |
| `GET /locations/{id}` | `LocationsController::show` → `LocationDetailResponseFactory` | published `bml_location` with valid coordinates only. |
| `GET /filters` | legacy `BML_REST::filters` → index aggregation | category/city only; excludes hidden/nonpublic/no-coordinate records. |
| `/geocode/*`, `/health`, `/diagnostics` | legacy `BML_REST` | geocoding requires edit capability; diagnostics requires diagnostics capability. |

There is one active locations controller and one active public repository, but two layers participate in route ownership (legacy registration wrapper plus namespaced controller). Search is index-only: there is no fallback to `WP_Query`. Query-level pagination is server-side; `LocationResponseFactory` emits `totalPages`. A deterministic query cache key is generated by `SearchLocationsQuery`; legacy cache invalidation runs on relevant post, term and settings events. The code supports Haversine distance and `orderby=distance` server-side. Near Me obtains the browser position then reloads using that origin; it is not client-only filtering.

## 7. Frontend loading audit

`LocatorDataSource` calls exactly the three routes above. It has distinct `AbortController`s for cards, markers and detail, aborts a preceding request of the same type, debounces input and viewport changes, and `LocatorController::destroy()` removes listeners, aborts requests, clears markers and destroys the map. Each locator root has its own controller, so the architecture supports multiple shortcodes on one page.

Cards are paged through `/locations` (default `per_page` comes from settings, bounded to 500). Markers are not loaded through pagination: they reload by bounds. This prevents automatic all-page card fetches, but dense bounds may hit the marker cap and the current UI needs a tested/traced presentation for `truncated`. Detail is fetched on selection. Potential performance risks are a 200 default card page size and index `LIKE` searches over `search_text` without a full-text index.

The current UI offers geolocation, but the board's distance selector, URL sync, rich filter drawer, load-more semantics, modal focus handling, responsive list/map switch and explicit accessible state catalogue are not proven by code inspection. Browser behaviour has not been executed.

## 8. Map provider audit

| Concern | OSM / Leaflet | Google Maps |
| --- | --- | --- |
| Assets | Local Leaflet and local MarkerCluster files; no Leaflet CDN | Google JS API is dynamically requested at runtime from Google; provider JS is local. |
| Init/fallback | `OpenStreetMapProvider::createMap`; default provider | `GoogleMapsProvider`; failed/missing config falls back to OSM through `MapEngine`. |
| Markers/popup/focus/bounds | implemented in provider/controller | implemented in provider/controller |
| Clustering | supported through Leaflet.markercluster when enabled | **not implemented**: `cluster()` returns `false`. |
| User location/cleanup | provider methods and controller destroy path exist | provider methods and destroy path exist |
| Multiple instances | per-root MapEngine/provider instances | same model, not browser-verified |

`map_style` and `marker_color` are persisted by `SaveSettingsAction` and defaulted by `Settings`, but the provider configs and provider JS do not consume them. This is a confirmed no-op settings defect. OSM correctly uses local Leaflet assets; Google is necessarily external API loading and must receive manual browser verification because billing/key restrictions are runtime-only.

## 9. Admin and Settings audit

`AdminMenu` is the only active menu registrar. It exposes Overview, Locations, Categories, **Cities**, Import/Export and Settings; hidden pages route Map Providers, Display and Gutenberg to the settings renderer. There is no separate active legacy settings page. `SaveSettingsAction` owns writes to canonical `bml_settings`; `BML_Plugin::settings()` supplies the legacy/frontend reader. Admin assets are centrally conditionally enqueued by `AdminAssets`.

The editor has a live card preview and map-related assets, but preview/browser behaviour was not executed. The settings renderer groups provider/display behaviour rather than implementing the board's dedicated route-level studio surfaces. `map_style`/`marker_color` are saved but do not flow to frontend providers. The codebase uses scoped `bml-*` selectors in the reviewed frontend templates/assets, but no full browser stylesheet collision audit was performed.

## 10. CSV pipeline audit

`ImportAjaxController` exposes authenticated nonce-protected prepare, process, pause, resume, cancel, duplicate scan and duplicate delete actions. The path is: upload validation → `ImportManager::prepare()` (including dry run) → database-backed `bml_import_jobs`/row/event tables → versioned/locked batch processing → `ImportMapper` → duplicate resolution by external ID then fingerprint → `LocationImporter` → index upsert → terminal job summary/cleanup cron.

The job repository stores owner, status, read/committed positions, token hash, lock/version and expiration. The row table has unique `(job_id, source_row_number)` and `(job_id, row_hash)` constraints; pause/resume/cancel are modeled by an explicit state machine. CSV formula injection is escaped on export. Main gap: import/export parity does not include content/excerpt, image, area, services, socials or explicit operational status semantics (CSV `status` maps post status); therefore it cannot yet be a complete Design System location contract.

## 11. Design-to-code reconciliation

Statuses: **IMPLEMENTED**, **PARTIALLY IMPLEMENTED**, **MISSING**, **BLOCKED BY DATA CONTRACT**, **BLOCKED BY ARCHITECTURE**, **PRO — OUT OF CURRENT SCOPE**.

| Board / screen | Status | Existing implementation and gap | Recommended iteration |
| --- | --- | --- | --- |
| Product Principles / IA & flows | BLOCKED BY ARCHITECTURE | Current public model says City; boards require Area. | Area ownership decision before new IA. |
| Foundations / Components / Responsive-A11y / Handoff | PARTIALLY IMPLEMENTED | local CSS and reusable template/controller pieces; no token layer or verified a11y interaction system. | Token adapter after contract fixes. |
| FO Directory Default / Filtered | PARTIALLY IMPLEMENTED | existing shortcode renderer, cards, filters, index REST. Missing board controls/states and visual system. | Free frontend shell. |
| FO Location Detail / Map Popup | PARTIALLY IMPLEMENTED | detail REST and renderer exist; board modal/focus protocol is not proven. | Detail accessibility round. |
| FO Tablet / Mobile List / Mobile Map / Filters Drawer / Mobile Detail | MISSING | no board-equivalent responsive layouts/drawer/sheet controller traced. | Responsive frontend round. |
| FO Split List+Map / Map Only / Directory Only | PARTIALLY IMPLEMENTED | `split`, `map`, `cards` shortcode layouts exist. | Align names, controls and fallback states. |
| FO Free States | PARTIALLY IMPLEMENTED | request/geolocation/provider messages exist; 12-state accessible catalogue absent. | State/a11y round. |
| BO Onboarding / Dashboard | PARTIALLY IMPLEMENTED | dashboard/demo/import exist; no first-run guided flow verified. | Admin shell round. |
| BO Locations List / Location Editor / Categories | PARTIALLY IMPLEMENTED | namespaced pages/actions and asset modules exist. | Design adaptation without new storage. |
| BO Areas | BLOCKED BY ARCHITECTURE | `bml_area` registered but menu, editor, REST/index/filter/CSV use City. | Dedicated Area migration/contract round. |
| BO Import/Export | PARTIALLY IMPLEMENTED | robust job lifecycle exists; field parity and board workspace/report gaps remain. | CSV contract round. |
| BO Shortcode Builder | MISSING | shortcode exists but no builder traced. | Builder UI only after stable display contract. |
| BO Gutenberg Block | PARTIALLY IMPLEMENTED | dynamic block reuses shortcode; editor UI/configuration is minimal. | Block controls round. |
| BO Display / Map Providers | PARTIALLY IMPLEMENTED | settings page persists values; `map_style`/`marker_color` no-op, Google lacks clustering. | Display propagation first. |
| BO Diagnostics / Help | PARTIALLY IMPLEMENTED / MISSING | REST diagnostics exists; no board-equivalent remediation/help surfaces. | Diagnostics UI then help. |
| All Pro frontend/admin boards | PRO — OUT OF CURRENT SCOPE | services, schedules, locators, presets and licensing lack Free contracts. | Do not mix into Free rounds. |

## 12. Findings by severity

### P0

1. **Area is not the canonical product model.** Evidence: [ContentTypes](../../business-map-locator/business-map-locator/src/WordPress/ContentTypes.php) registers `bml_area`, but [AdminMenu](../../business-map-locator/business-map-locator/src/Admin/Menu/AdminMenu.php), `SaveLocationAction`, `LocationRepository`, REST args and CSV use `bml_city`/`city`; boards prohibit using Cities as the product model. Impact: Area screens and filters cannot be truthfully implemented. Action: approve a separate non-destructive Area contract/migration plan, with migration and REST/CSV regression tests. Do not silently rename or repurpose keys.
2. **Display controls contain confirmed no-ops.** Evidence: `SaveSettingsAction` persists `map_style` and `marker_color`; neither provider config/JS reads them. Impact: BO Display/Map Providers cannot be trusted as a WYSIWYG source. Action: one narrow propagation round with PHP/JS tests and browser verification. 

### P1

1. **Google clustering is absent.** Evidence: `GoogleMapsProvider.prototype.cluster()` returns `false`; OSM has MarkerCluster. Impact: provider parity and map board claims diverge. Action: explicitly choose Google clustering approach in a provider-only round; browser/key/billing test required.
2. **Import/export is not full Location-contract parity.** Evidence: allowed import/export columns omit image, content, area, services and social fields. Impact: a design-led editor can create data that cannot round-trip. Action: contract-first CSV expansion with fixtures and formula-injection regression tests.
3. **Automated baseline is red.** PHPUnit has one bootstrap/schema loading error; PHPStan reports 111 errors; PHPCS has extensive violations. Impact: refactor/design rounds lack a green safety net. Action: repair test bootstrap first in a separate quality round; do not combine with UI.

### P2

1. `menu_order` is accepted as REST ordering but the repository maps it to `title`. Add endpoint/repository regression coverage when sort UI is introduced.
2. The marker endpoint truncates results; make this an explicit UX state and test it before dense-map polish.
3. No verified URL-state, focus-trap/return-focus, keyboard or responsive browser evidence exists. Treat as manual acceptance gates, not implemented behaviour.

## 13. Test and verification results

| Check | Result | Command | Result detail |
| --- | --- | --- | --- |
| PHP CLI | PASS | `php -v` | PHP 8.2.26. |
| Composer version | FAIL | `composer --version` | environment wrapper attempts to open `\\composer.phar`; Composer scripts could not run. |
| Composer install | NOT RUN | `composer install --no-interaction` | dependencies already exist; intentionally avoided unnecessary workspace mutation in a diagnostic round. |
| Composer test/lint | NOT RUN | `composer test`, `composer lint` | no Composer scripts are declared; Composer wrapper is also unavailable. |
| PHPUnit | FAIL | `vendor\\bin\\phpunit --configuration phpunit.xml.dist` | 94 tests, 384 assertions, 1 error: `ImportJobProgressMarkersTest` cannot find `BML_Schema`. |
| PHPCS | FAIL | `vendor\\bin\\phpcs --standard=phpcs.xml.dist` | exits 2; widespread WPCS/file-naming/formatting violations. |
| PHPStan | FAIL | `vendor\\bin\\phpstan analyse --configuration=phpstan.neon` | 111 errors, including WordPress/plugin constants unavailable to analysis and PHP 8.2 `readonly` declarations versus declared PHP 8.1 support. |
| npm test/lint/build | NOT RUN | n/a | no `package.json`. |
| WordPress runtime/browser | NOT RUN | n/a | no local WordPress/browser acceptance session was started. |

## 14. Recommended implementation sequence

Round 1 — Display setting propagation  
Goal: Make canonical `map_style` and `marker_color` affect the existing Free frontend maps.  
Allowed: existing settings/renderer/provider PHP and JS, focused regression tests.  
Forbidden: new design shell, REST/schema/meta/options changes, Area work, version/ZIP.  
Acceptance: both settings flow to both provider configs; unsupported provider behavior is explicit; tests pass.

Round 2 — Test-bootstrap repair  
Goal: Repair the single `BML_Schema` test bootstrap failure.  
Allowed: test bootstrap/config and the failing test only.  
Forbidden: production refactor or design work.  
Acceptance: PHPUnit suite passes without weakening assertions.

Round 3 — Area architecture decision and migration plan  
Goal: establish an approved canonical Area contract and backwards-compatible City migration strategy.  
Allowed: diagnostic documentation and tests that characterize current data.  
Forbidden: destructive migration, UI redesign, REST breaking change.  
Acceptance: owner-approved mapping, rollback and compatibility plan.

Round 4 — Area contract implementation  
Goal: implement the approved Area path end-to-end, preserving beta City data.  
Allowed: explicitly approved migration, storage/index/REST/CSV/test files.  
Forbidden: unrelated Design System visual changes and release.  
Acceptance: editor → index → REST → frontend/CSV Area parity with rollback evidence.

Round 5 — Free frontend shell and tokens  
Goal: adapt the existing locator templates/components to Foundation and Directory boards.  
Allowed: existing Free templates/CSS/JS and visual regression/unit tests.  
Forbidden: new data fields, Pro UI, HTML copied from boards.  
Acceptance: desktop directory/default/filtered layouts preserve current REST contract.

Round 6 — Detail, states and accessibility  
Goal: board-conformant detail popup/modal, loading/error/empty states and keyboard/focus behavior.  
Allowed: existing frontend controller/templates/CSS and tests.  
Forbidden: REST schema expansion, provider rewrite.  
Acceptance: manual browser a11y verification plus automated request-state tests.

Round 7 — Responsive Free layouts  
Goal: implement tablet/mobile list/map/drawer/detail transformations.  
Allowed: frontend assets/templates/tests only.  
Forbidden: admin redesign, data-contract change.  
Acceptance: specified breakpoints, 44px targets and list fallback for Map Only verified in browser.

Round 8 — Admin Free information architecture  
Goal: reconcile existing admin pages with onboarding, locations, editor, categories/areas, display and diagnostics boards.  
Allowed: existing namespaced admin renderer/assets after Area contract exists.  
Forbidden: Pro UI, new persistence model, release.  
Acceptance: each Free route has a canonical renderer and no duplicate menu/settings owner.

## 15. Exact next Codex task

```text
# Codex task: propagate existing map display settings

Mode: one implementation round. Solve only the confirmed root cause that
`bml_settings.map_style` and `bml_settings.marker_color` are saved but never
reach the current frontend map providers.

Before edits: create a verified backup with .codex\scripts\New-PluginBackup.ps1,
run the repository preflight, and identify the actual source/stand paths. Stop
on a failed preflight or deployment verification and restore the stand backup.

Allowed production files (only if required):
- business-map-locator/includes/Frontend/class-bml-frontend.php
- business-map-locator/includes/Providers/class-bml-openstreetmap-provider.php
- business-map-locator/includes/Providers/class-bml-google-maps-provider.php
- business-map-locator/assets/js/providers/openstreetmap-provider.js
- business-map-locator/assets/js/providers/google-maps-provider.js

Allowed test files: add or amend focused tests under business-map-locator/tests/
that prove both values are localized/configured and that an unsupported provider
does not silently claim support.

Requirements:
1. Preserve the `bml_settings` option shape, all REST routes, CPT/meta keys,
   shortcode attributes, provider fallback behavior and beta aliases.
2. Pass the values through the existing BML_Frontend → provider config → existing
   provider instance path. Do not create another map controller or renderer.
3. Apply marker color in OSM and Google using their existing marker mechanisms.
   Apply map style only where the existing provider can support it; otherwise
   preserve current behavior and expose no false claim of application.
4. Add regression tests. Run focused tests plus available PHPUnit/PHPCS/PHPStan
   checks and record PASS/FAIL/NOT RUN honestly.
5. Synchronize source to the configured stand and run postflight. Request manual
   browser verification for OSM and, if configured, Google because provider keys,
   billing and visual rendering are browser/runtime-only.

Forbidden:
- Area/City migration; frontend or admin redesign; modal/drawer work; new options,
  REST fields, schema/meta changes, dependencies/CDNs; version bump; ZIP build.

Acceptance:
- A saved non-default marker color is present in both existing provider configs
  and changes markers in manual provider verification.
- A supported selected map style reaches and changes the provider; unsupported
  behavior is not reported as applied.
- Existing fallback to OSM and multiple-locator lifecycle remain intact.
- Regression tests cover the propagation and all production changes are limited
  to the approved files.
```

## 16. Changed files

Only this diagnostic report was added: `docs/audits/phase-one-design-code-reconciliation.md`. No production files were changed.
