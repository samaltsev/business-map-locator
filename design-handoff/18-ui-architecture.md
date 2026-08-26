# UI Architecture Specification

## Governing principle

The new UI must not become a third parallel implementation. Every new UI concern has one canonical owner; legacy layers may be temporary compatibility adapters, never independent sources of business logic, rendering rules or UI state.

**One responsibility → one canonical implementation → optional temporary adapters.**

## Current Architecture Inventory

| Layer | Current owner | Role | Risk | Target |
|---|---|---|---|---|
| Bootstrap/container | `business-map-locator.php`, `src/Plugin` | boot and services | duplicate boot paths | CANONICAL |
| Namespaced domain/app/REST | `src/` | content, metadata, REST, repositories | bypass via legacy | CANONICAL |
| Legacy includes | `includes/` | shortcode, cache, migration, compatibility | duplicate rendering/state | ADAPTER / LEGACY-STABLE |
| Frontend assets | `assets/js`, legacy frontend | locator/map interaction | global state | REQUIRES-DECISION |
| Admin pages/assets | namespaced admin plus legacy hooks | menu, forms, actions | local page shells | CANONICAL via `src/Admin` |

## Canonical Ownership Matrix

| Concern | Canonical owner | Temporary adapter | Forbidden parallel owner |
|---|---|---|---|
| Admin composition/assets | `src/Admin/Page`, `src/Admin/Asset` | legacy menu hook | page-local shell |
| Frontend locator/rendering | `src/Frontend/Locator` | shortcode/block adapters | independent shortcode UI |
| Shared components/tokens | `src/UI/Component`, `src/UI/Token` | legacy CSS bridge | per-page copies |
| REST/data transformation | `src/Rest`, `src/Application`, repositories | legacy REST adapter | duplicate controller/repository |
| Map/filter/selection state | locator JS controller/store | legacy event bridge | global mutable state |
| Capabilities/notices/templates | application/page layer | WordPress hooks | reusable component logic |

## Target Directory Architecture

```text
src/Admin/{Page,ViewModel,Action,Component,Asset,Support}
src/Frontend/{Locator,ViewModel,Component,Shortcode,Block,Asset}
src/UI/{Contract,Component,Token,Support}
src/Application/
```

These are future targets, not directories created by this round. Page/Locator may depend on Application and UI contracts; templates may depend only on ViewModels and components. Repositories never depend on templates; components never contain SQL, capability decisions or legacy globals.

## Admin Shell

`WordPress Admin Frame → Plugin Navigation → Page Header → Primary Action → Toolbar → Main Content → optional Sidebar → Sticky Save Bar → Notices → Live Preview → Modal/Drawer layer`.

`Page` composes the shell and supplies a ViewModel; Application performs capabilities/actions; shared components render states; an editor controller owns dirty/saving/saved state. Pages must not recreate navigation, headers, notices or sticky save bars.

## Frontend Locator Shell

`Locator Root → Search/Filters → Results Header → Directory Region + Map Region → Selected/Detail layers → Status/Error → Pagination/Load More`.

Each instance owns `{query, filters, sort, pagination, viewport, selectedLocationId, detailLocationId, layout, loading, error, geolocation, providerStatus}`. Directory and map consume this same state; URL sync is explicit/optional; locators never share accidental global state.

## PHP Rendering Layers

`Controller → Application handler → Repository/query → ViewModel → Template composition → UI component → Field renderer`.

Compatibility adapters route legacy entry points to canonical services. No SQL/business logic in templates, no REST-response parsing in templates, and no direct legacy global calls from new components.

## CSS Architecture

Use scoped plugin bundles: tokens, base, layout, components, page composition and utilities. Tokens are semantic (`--nbh-*`); FO remains theme-neutral. No global unscoped selectors, page-local copies of component styles, or admin assets outside plugin screens.

## JavaScript Architecture

Controllers: `LocatorState`, `FilterController`, `MapController`, `PreviewController`, `SaveController`, `ValidationController`, `ImportController`. Controllers orchestrate; state is canonical per locator/editor; REST client normalizes requests/responses. Use AbortController, debouncing and explicit cleanup—never DOM state as source of truth.

## Event Flow and Data Flow

Frontend: action → state update → request plan → REST → normalization → shared state → directory/map → optional URL.

Admin: page load → ViewModel/bootstrap → edit → validation → dirty → preview → save → response/notices → clean.

Import: action → handler/job → polling/progress → normalized state → progress component. Backend owns business truth; frontend owns transient interaction; ViewModels own presentation-ready server data.

## Component Ownership

Pages own composition; components own rendering/states; controllers own interaction; Application owns business actions; repositories own persistence. `CMP-Button`, `CMP-Input`, `CMP-Modal`, `CMP-Drawer`, `CMP-BottomSheet` are Shared UI; `CMP-PageHeader`, `CMP-AdminTable`, `CMP-StickySaveBar`, `CMP-LivePreview` are BO; `CMP-LocationCard`, `CMP-LocationDetail`, `CMP-MapCanvas`, `CMP-MapPopup`, `CMP-FilterToolbar` are FO. Extensions are documented variants only; local copies are forbidden.

## Responsive Architecture

| Desktop | Tablet | Mobile |
|---|---|---|
| Sticky map | constrained split | List/Map mode |
| Filter toolbar | compact controls | drawer |
| Detail side panel/modal | side panel | full-screen sheet |
| Editor sidebar | narrow sidebar | below content |
| Admin table | responsive table | rows/cards |
| Live preview | optional side preview | deferred/full-width |

## Performance Budget

Targets requiring measurement: lazy map initialization; initial locator JS/CSS page budgets; maximum initial card count; paged/virtualized lists where justified; batched markers; 250–300ms debounced filters; one active locator request with cancellation; lazy images via IntersectionObserver; no advanced panels at initial load. Admin loads page-specific assets only; preview is debounced; imports poll at bounded intervals.

## WordPress Integration Boundaries

Integrate, do not replace: admin/enqueue hooks, REST and shortcode/block registration, nonces, capabilities, media library, notices, translations, screen IDs and page routing. No standalone admin SPA, no unrelated-page assets, and no bypass of WordPress security.

## Legacy → Canonical Migration

1. identify owner; 2. route legacy call through adapter; 3. move rendering/state ownership; 4. deprecate duplicate; 5. remove only after compatibility/tests. Do not delete legacy merely because a new component exists. Required coverage: adapter, entry-point, asset, ViewModel and interaction regression tests.

## Forbidden Patterns

Third UI implementation; duplicate REST/repository; page-specific shared components; business logic in templates; global CSS/state; separate map/directory results; all admin assets globally; direct DOM truth; silent legacy fallback; Free/Pro decisions in JS.

## UI Testing Strategy

PHP: ViewModel, rendering, capability, enqueue and adapter tests. JS: state/controller, cancellation, events, URLs and multi-locator isolation. Browser: map-directory sync, responsive transformations, keyboard/focus, provider failure, slow network and large data. Visual: shared components, FO/BO cores, breakpoints and Free/Pro differences.

## UI-0 Readiness Gate

| Gate | Status |
|---|---|
| Directory First approval | READY |
| sticky map, detail, pagination | READY |
| provider/image fallback | READY |
| canonical owners/CSS/JS/adapter strategy | READY |
| performance budget | READY |

## Open-decision impact

| Decision | Architecture impact | Layer | Blocks UI-0 | Default |
|---|---|---|---|---|
| OD-01 | locator shell | FO | Yes | Directory First |
| OD-02 | map transformation | FO | Yes | List/Map mobile |
| OD-03 | detail owner | FO | Yes | side panel → sheet |
| OD-04 | list controller | FO | Yes | paged response |
| OD-05 | provider/media boundaries | FO/BO | Yes | explicit failure state |

**UI-0 Readiness: READY.** Defaults: Directory First; sticky ≥1280 / split 1024–1279 / List-Map <1024; side panel → full-screen sheet; explicit Load More; directory-only provider safe mode; local placeholder.

This gate authorizes only a separate UI-0 task: tokens, CSS isolation, shared component foundation and compatibility adapters. It does not authorize locator/map rewrite, all FO/BO screens, legacy removal, REST changes or deployment.
