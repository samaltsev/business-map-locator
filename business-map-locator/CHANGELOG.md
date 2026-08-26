# Changelog

## 1.3.2-beta40.7 — Filter → Map Focus Fix

- Category and city changes now fit the map to the matching loaded locations.
- A single matching location focuses at zoom 16; multiple matches use provider bounds.
- Viewport markers reload after the map moves, preserving the lightweight marker endpoint.
- Added equivalent coordinate-fit behavior for OpenStreetMap and Google Maps providers.
- No REST contract, directory design, modal design or Settings Studio changes.


## 1.3.2-beta40.6 — Map Focus + Real Modal Fix

- Fixed desktop split modal being hidden by a legacy `display: none !important` rule.
- Clicking a directory location now moves the map immediately to the location coordinates at zoom 16.
- After viewport marker refresh, the selected marker is focused again.
- Detail loading uses returned coordinates when the selected marker is outside the previous viewport.
- Modal/backdrop are layered above Leaflet/Google map panes without changing REST or directory loading.


## 1.3.2-beta40.5 — Compact Directory + Real Map Modal

- Restored readable 72–86 px desktop split directory rows with two-line titles and one-line addresses.
- Disabled native provider popups in desktop split layout so BML detail modal is the only detail surface.
- Marker click and directory click now use the same detail-modal path.
- Added a map-area backdrop, Escape handling and keyboard focus trapping for the detail dialog.
- Closing the split modal keeps the selected location and map position intact.


## 1.3.2-beta40.4 — Compact Cards + Map Modal

- Compact retail-style location rows in desktop split layout.
- Location details open as a floating modal over the map instead of expanding the directory row.
- Preserved list → map focus, marker → list synchronization, REST contracts, viewport marker loading and existing directory loading behavior.
- Card images remain hidden in desktop split layout without removing card content or actions.


## 1.3.2-beta40.3 — Retail split locator

- Moved the split-layout search/filter toolbar into the directory column so the map starts at the top of the locator.
- Directory now auto-loads the full filtered location list in batches up to 500 records and removes the manual Load more step.
- Kept map data viewport-based; full directory loading does not load all map markers.
- Added Default sorting based on menu order before title sorting options.
- Desktop split detail now expands inline inside the selected directory item instead of consuming map width.
- Refined split layout toward a dense retail-locator interaction pattern while keeping Business Map Locator branding and contracts.


## 1.3.2-beta40.1 — Rozetka-style Split Locator

- Refined the public split locator into a dense retail-style directory + map experience.
- Restored useful location information in compact directory items: address/area, distance/hours and actions.
- Kept images hidden only in the desktop split directory while preserving card body semantics.
- Made the map the dominant desktop surface and reduced the directory width.
- Restyled desktop detail as a compact floating map card instead of a full-height third column.
- Moved Leaflet zoom controls to the lower-right map corner for the split layout.
- Kept REST, map loading, CSV, Studio and data contracts unchanged.

## 1.3.2-beta40 — Frontend Locator Renderer

- Introduced `BMLLocatorRenderer` as the public frontend renderer entry point so the same component can be embedded by frontend placements and, in a follow-up release, Settings Studio.
- Reworked the primary split layout into a compact directory + large map presentation inspired by modern retail locators.
- Added immediate card ↔ marker selection synchronization and automatic directory scrolling when a marker is selected.
- Added card hover/focus marker emphasis without loading location detail.
- Added a mobile List / Map switch instead of forcing both regions into one narrow viewport.
- Kept directory loading server-paginated and marker loading bounds-based; location detail remains lazy-loaded by ID.
- Preserved existing REST, CSV, CPT, index, data model and Free/Pro contracts.

## 1.3.2-beta39 — Lightweight Studio Preview

- Limits Settings Studio viewport marker payloads to 200 lightweight records.
- Keeps the Studio directory preview capped at six cards.
- Adds a lightweight `/locations/bounds` REST contract for Fit locations without downloading location records.
- Makes viewport marker requests abortable and guarantees loading state cleanup.
- Prevents preview-only appearance changes from triggering location data reloads.
- Keeps the beta38 Locator Studio visual architecture unchanged while reducing preview runtime load.

## 1.3.2-beta38 — Locator Studio 3.0

- Rebuilt Settings Studio again at the markup/CSS architecture level after beta37 proved too visually close to the previous editor.
- Added a dedicated vertical task rail for Map, Layout, Filters, Cards and Publish with only one inspector panel active at a time.
- Reduced the settings column and gave the live locator canvas visual priority.
- Kept the real frontend preview flow visible as search/filters → map → directory cards instead of a separate location-list column.
- Removed the large preview status bar and replaced it with a compact live status/count strip.
- Moved device preview controls into a compact canvas toolbar and kept map actions over the map itself.
- Added a persistent directory below the map so lower-height viewports can scroll the preview instead of collapsing the map.
- Preserved the beta36.2 OSM tile fallback, viewport marker loading protections, existing option keys, REST endpoints and data model.
- No changes to CSV, CPT, location index, REST contracts or Free/Pro rules.

## 1.3.2-beta37 — Locator Studio 2.0

- Rebuilt the fullscreen Settings Studio around a two-pane model: settings on the left and one integrated live locator preview on the right.
- Replaced the separate location-list/map composition with a real preview flow: search and filters, map, and directory cards in one canvas.
- Simplified the header and condensed published/location/viewport status into a compact preview status line.
- Renamed editor sections to Map, Layout & style, Search & filters, Location card, and Publish.
- Added desktop, tablet, and mobile preview-width controls without changing frontend REST or data contracts.
- Reduced map controls to a compact overlay and retained the beta36.2 OSM tile fallback and viewport-loading protections.
- Preserved existing settings option keys, shortcode, REST endpoints, location data model, CSV pipeline, and Free/Pro behavior.
- Converted SettingsPage source strings and Studio dirty-state strings touched by this release to English source text.

## 1.3.2-beta36.2 — Settings map runtime fix

- Switched the default OpenStreetMap tile endpoint to the canonical `https://tile.openstreetmap.org/{z}/{x}/{y}.png` URL.
- Added Settings Studio tile-load error handling and a one-time fallback to the canonical OpenStreetMap endpoint for legacy saved tile URLs.
- Prevented programmatic Leaflet resize refreshes from continuously retriggering viewport marker requests.
- Made Settings Studio marker coordinates tolerant of numeric REST values serialized as strings.
- Kept REST contracts, stored locations and public frontend behavior unchanged.

## 1.3.2-beta36.1 — Release integrity

- Synchronized plugin, readme and Gutenberg block version metadata.
- Corrected the declared Leaflet.markercluster asset version to 1.5.3 to match the bundled vendor files and fetch script.
- Removed the duplicated changelog header and duplicate beta36 entry.
- No REST, data model, frontend behavior or Settings Studio behavior changes.

## 1.3.2-beta36

- Restored bundled Leaflet 1.9.4 and MarkerCluster 1.5.3 assets omitted from beta32–beta35 release archives.
- Fixed 404 errors for the Map Studio and frontend map CSS/JavaScript.
- Rebuilt Map Studio as a fullscreen Elementor-style editor.
- Added a fixed left inspector and full-canvas live frontend preview.
- Added inspector collapse and explicit exit to the Business Map dashboard.
- Preserved instant preview updates for provider, center, zoom, layout, controls and card fields.

## 1.3.2-beta34

- Rebuilt Settings Studio as a focused customizer with one active settings section and a persistent live frontend preview.
- Added immediate preview updates for layout, card fields, marker appearance, provider, map style and height.
- Moved latitude, longitude, zoom limits and map language into an Advanced disclosure.
- Kept country/city/address geocoding as the primary way to set the initial map center.
- Added a dedicated Cards section and improved responsive workspace spacing.

## 1.3.2-beta33

- Rebuilt Map Studio settings into a spacious two-column workspace.
- Added place search for the initial map center using the existing admin geocoding endpoint.
- Synchronized saved coordinates and zoom with the live map preview.
- Added a frontend-style locator preview in the Design tab.

## 1.3.2-beta29 - Settings Studio Leaflet init hotfix
- Prevented the legacy admin settings map initializer from running inside the isolated Settings Studio.
- Added a defensive reuse path for an already initialized Leaflet map container.
- Bumped the plugin asset version so browsers reload the fixed admin scripts.

## 1.3.2-beta28 — Isolated Settings Studio P0

- Completely rebuilt the Settings page as a namespaced, isolated BML workspace.
- Made the live map the main element with a 360px inspector and large responsive canvas.
- Reduced navigation to two levels: Map & Publishing and Advanced Settings.
- Added Google Maps API key verification for Maps JavaScript API and Geocoding API.
- Loads every published location through paginated REST requests and fits all points.
- Kept all core layout choices available in the Free version.
- Replaced clickable div navigation with semantic button, label and fieldset controls.
- Advanced controls are rendered only on the Advanced tab.
- Uses local Leaflet assets registered by the plugin; no third-party asset CDN is added by Settings.
- Reworked Russian interface copy and status messages.

## 1.3.2-beta27 — Publish Map Studio

- Rebuilt Settings as a three-step provider, design and publishing workflow.
- Enlarged the live map workspace and added loading of all published locations through REST.
- Added explicit Google Maps API key testing and detailed connection states.
- Kept all core locator layouts and visual controls in the Free version.
- Added a one-click shortcode copy workflow and consolidated provider/design saving.

# 1.3.2-beta27

- Rebuilt Settings to match the Location Editor workspace.
- Added sticky editor-style header, large section tabs, card sections, right preview and dark save bar.
- Unified spacing, borders, shadows and responsive behavior.

## 1.3.2-beta27 - Settings CSS hotfix
- Fixed invalid CSS selector that interrupted Settings form styles.
- Restored full-width tabs and Settings content flow.
- Removed duplicated status badges above the workspace.
- Forced the Leaflet preview container to use the complete available width.
- Added robust map resize handling for layout and viewport changes.

## 1.3.2-beta25 - Compact Map Studio
- Reworked the Settings Map tab into a denser compact Map Studio workspace.
- Replaced the large Map Studio intro with a compact status summary for providers, locations and REST.
- Replaced large provider cards with a segmented OpenStreetMap / Google Maps selector.
- Expanded the map toolbar with desktop, tablet and mobile modes, style selection, reset view and open actions.
- Reworked the map status footer to show provider, REST, location count and coordinates.
- Increased the live preview height and reduced header, inspector, field and save bar spacing.
- Added responsive refinements for 1350px, 1180px and 782px breakpoints.
- Preserved the 1.3.2-beta24.1 location editor address prompt fix.

## 1.3.2-beta24.1 - Location editor address prompt
- Prevent Chrome from offering to save location editor address data as a personal address profile.
- Route visible address fields through neutral field names while preserving the saved location metadata.

## 1.3.2-beta24 — Map Studio Settings

- Rebuilt the Map settings tab as a map-first studio workspace.
- Added a compact 390px inspector with Provider, Starting point, Appearance and Advanced sections.
- Expanded the real live map preview to the main working area with desktop, tablet and mobile modes.
- Added compact health indicators, sticky preview behavior and a reduced save bar.
- Added unsaved-change tracking, Enter-only geocoding and unload protection.
- Preserved existing provider, coordinates, tile, marker and API key setting names for compatibility.

## 1.3.2-beta23 — Settings Workspace 2.0

- Removed the Settings hero and duplicate internal navigation.
- Rebuilt settings around one compact tab row and unified setting surfaces.
- Replaced oversized provider cards and benefit cards with compact selectors and badges.
- Added fixed-width sticky previews with bounded height.
- Reduced spacing, row height and visual nesting across Map, Design, Publishing and System.
- Reworked the save bar into a short non-obstructive workspace action bar.
- Improved responsive behavior and prevented horizontal overflow.

## 1.3.2-beta23 — Settings Experience
- Rebuilt the Settings page as a focused configuration workspace.
- Added a settings summary hero, persistent section navigation and compact system status.
- Reworked Map Setup with provider-aware panels and a sticky live preview.
- Reworked Design, Publishing and System sections with responsive cards and clearer controls.
- Added responsive layouts, sticky save actions, keyboard save support and improved overflow handling.

## 1.3.2-beta23 — Import / Export responsive CSS fix
- Fixed horizontal overflow and clipped controls on medium desktop widths.
- Improved selected-file, import-mode and action layouts.

# 1.3.2-beta23 — Import / Export Experience

- Rebuilt the Import / Export page as a focused data-transfer workspace.
- Added Import, History, Export and Data tools tabs.
- Added recent-job KPI cards and clearer job-state presentation.
- Redesigned CSV upload, selected-file inspection, import mode and progress UI.
- Added a sticky CSV checklist and template resources panel.
- Redesigned import history with a dedicated empty state.
- Redesigned export filters, field presets and Excel compatibility control.
- Redesigned duplicate scanning as a separate maintenance tool.
- Added responsive behavior and persistent active tab state.

# 1.3.2-beta20.1 — Slug & Directory Search Fix

- Fixed Cyrillic/Belarusian/Ukrainian slug transliteration for inline category and city creation.
- Unified server-side slug generation through `SlugGenerator`.
- Fixed category and city search across the full directory instead of only the current page.
- Added Unicode-safe and URL-decoded search matching.

## 1.3.2-beta20.1 — Cities Experience

- Redesigned Cities workspace with KPI cards, directory health, search, filters, sorting and pagination.
- Added sticky city editor, smart slug generation, live preview, usage summary and responsive layouts.
- Added dedicated Cities Experience CSS and JavaScript assets.

## 1.3.2-beta19.1 — Address Save Prompt Fix

- Changed location saves to use the existing authenticated AJAX editor endpoint, preventing Chrome from treating the admin form as a personal-address form.
- Added explicit password-manager and autofill ignore attributes to location address and phone inputs.
- Preserved publish/draft status during AJAX saves and updated the editor URL without reloading the page.

## 1.3.2-beta19 — Locations Experience

- Rebuilt the Locations page as a dedicated network-management workspace.
- Added KPI cards for total, published, draft and review-required locations.
- Added a Data Quality attention panel with direct filters for missing address, coordinates and phone.
- Added richer filtering, reset behavior and quick status chips.
- Added live client-side search across title, address, category, city and phone.
- Replaced the legacy location table with a compact directory showing identity, classification, quality, updated time and status.
- Added category icon fallback, quality percentage indicators and compact row action menus.
- Added Open on map, Duplicate and Delete actions without cluttering the main table.
- Added selected-row counting, improved bulk actions, empty states and keyboard shortcuts.
- Added responsive Locations Experience CSS and dedicated JavaScript.

## 1.3.2-beta18 — Category Experience

- Rebuilt Categories as a dedicated management workspace aligned with Dashboard and Location Editor.
- Added category KPI cards for total, used, unused and missing-icon categories.
- Added a Needs Attention summary and linked quality filters.
- Added live client-side search, used/unused/icon filters and improved sorting.
- Replaced the legacy term table with a richer directory showing icon, description, usage, status and compact actions.
- Added a sticky category editor with smart slug generation, description support and keyboard save shortcut.
- Added a live frontend-style category preview synchronized with name, description and marker icon.
- Added safe category duplication and blocked deletion while locations remain assigned.
- Added responsive Category Experience CSS and dedicated JavaScript.
- Preserved the existing Cities workflow while fixing taxonomy form nonces.

## 1.3.2-beta17 — Admin Experience

- Rebuilt the location editor as an adaptive two-column workspace.
- Added sticky live preview, ready checklist, publication metadata and quick actions.
- Added collapsible editor cards with remembered state.
- Added AJAX draft autosave, unsaved-changes protection and a sticky save bar.
- Improved smart address search, draggable-marker feedback and live preview updates.
- Added keyboard shortcuts: Enter searches an address and Ctrl/Cmd+S saves.
- Added skeleton loading, contextual help and responsive editor behavior.
- Split editor behavior into dedicated view, CSS, JavaScript and AJAX controller responsibilities.

## 1.3.2-beta16 — Dashboard & Admin UX Redesign

- Rebuilt Overview as an operational location-network dashboard.
- Replaced the large welcome banner with a compact Location Network summary.
- Added Locations, Cities, Categories and Data Quality KPI cards.
- Added a Needs Attention panel for drafts, missing data and unused taxonomy terms.
- Simplified Recent Locations by removing coordinates and hiding destructive actions in a compact menu.
- Replaced location thumbnails with consistent location/category-style icons on Overview.
- Added compact Quick Actions and a horizontal System Status strip.
- Added Import CSV as the secondary Overview header action.
- Added responsive layouts for tablet and mobile admin screens.

## 1.3.2-beta15 — Essential card display settings

- Added a compact Location Card settings group to the Free Design & Preview screen.
- Added global toggles for Address, Phone and Navigation visibility.
- Navigation can now be removed from every public location card without per-location configuration.
- The same visibility rules are applied to map popups, result cards, the production preview and the location editor live preview.
- Kept automatic provider-aware navigation behavior when Navigation is enabled.

## 1.3.2-beta14 — Inline publication validation

- Removed the persistent global validation banner from the location editor.
- Kept the Progress Checklist as the always-visible completion guide.
- Validation feedback is now shown only after Create location or Save changes is pressed.
- Added a compact transient notice instead of a page-wide error summary.
- Automatically scrolls to and focuses the first invalid required field.
- Highlights only the fields that need attention, including map coordinates.
- Field errors clear as soon as the user edits the affected value.

## 1.3.2-beta13 — Disable browser address-save prompt

- Disabled browser address/profile autofill on the location editor form.
- Removed semantic autocomplete tokens from location address and phone inputs.
- Added common password-manager ignore attributes to prevent the editor from being treated as a personal address form.
- Location saving behavior and stored data are unchanged.

## 1.3.2-beta12 — Provider-aware Navigation and Card UX

- Desktop Navigation follows the active map provider: Google Maps or OpenStreetMap.
- Mobile Navigation opens the device navigation handler automatically.
- Added a visible Navigation link with icon and label to frontend location cards and the admin live preview.
- Fixed horizontal overflow, map containment and sidebar layout in the location editor.
- Enter in address search only starts geocoding and cannot submit the location form.
- Address selection preserves a detected house number and builds a complete preview address with the selected city.
- A city returned by geocoding is matched automatically to the existing City taxonomy term.

## 1.3.2-beta10 — Free Location Publishing

- Simplified the Free location editor to core publishing fields.
- Kept branch phone and automatic Directions in Free.
- Removed per-location email, website, messengers, social networks, hours, services and media from the Free editor.
- Updated completeness and live preview for the Free workflow.

## 1.3.2-beta9
- Unified admin preview and public popup card geometry.
- Icon order: phone, website, messengers, social networks, Directions.
- Directions remains available in Free.

## 1.3.2-beta8 — Unified Location Cards

- Replaced the Website text action with a recognizable icon in Live Preview.
- Rebuilt the frontend map popup to use the same image, status, title, address, contact-icon and action layout as Live Preview.
- Added website and directions icon actions with accessible labels and tooltips.
- Exposed saved messenger and social channels in the public REST location response.
- Added frontend popup contact icons for phone, email, WhatsApp, Telegram, Viber, Facebook, Instagram, LinkedIn and TikTok.
- Unified card spacing, borders, radii, typography and icon sizing across admin preview and frontend popup.

## 1.3.2-beta7 — Contact Channels & Form Safety

- Prevented accidental location saves when Enter is pressed in form fields.
- Enter in address search now runs geocoding only.
- Added dynamic city helper text for street searches.
- Fixed website normalization and blocked admin URLs as public website values.
- Added WhatsApp, Telegram and Viber normalization and validation.
- Added icon-only WhatsApp, Telegram, Viber, Facebook, Instagram, LinkedIn and TikTok fields.
- Added live contact icons to the location preview.
- Directions and Website preview actions now appear only when usable.
- Contacts are complete only when at least one valid channel exists.

## 1.3.2-beta6 — Basic Information & Address Search

- Reordered the Basic information workflow: Location name, Address and map, Category and city, Operational status.
- Moved the address/map workspace into the main Basic information card.
- Improved OpenStreetMap geocoding for street and house-number searches by adding selected city, region and country context.
- Added direct latitude/longitude search support.
- Added Enter-key address search, clearer search states, deduplication and stronger geocoder error handling.

## 1.3.2-beta5 — Location Editor UX Redesign
- Rebuilt the location editor as a modern single-page workspace.
- Added sticky publication actions and a unified save state.
- Added inline category and city creation with capability checks and nonce protection.
- Added live completeness tracking and section status indicators.
- Improved address search, map layout, coordinate controls and draft handling.
- Added live location card preview and selectable service cards.
- Added client-side publication validation and an error summary.
- Improved media selection with a drag-and-drop style area.

## 1.3.2-beta2
- Fixed Google Maps live preview with a shared loader, timeout and authentication errors.
- Split OpenStreetMap and Google Maps provider settings into contextual panels.
- Aligned initial-view field IDs with the map controller and improved preview layout.

## 1.3.2-beta1 — Settings UX Redesign
- Added guided setup progress with completion states.
- Added live provider, map style and marker color preview.
- Added a status and publish-readiness panel backed by page-level aggregate data.
- Added isolated settings UX assets, tests and implementation documentation.

## 1.3.1-beta15.4
- Added a smart CSV starter area to Import / Export.
- Added downloadable Basic and Full CSV examples inside the plugin package.
- Added a standalone CSV field reference covering formats, values and duplicate errors.
- Added responsive styling for resource cards.

## 1.3.1-beta15.2 — Import workspace UX

- Added selected-file card with name, size, modified time and validated CSV facts.
- Added state-aware upload, job progress, ETA, current row, contextual controls and recent import history.
- Added visual result counters and collapsible error details.
- Removed large duplicate lookup maps from persistent job payload and switched to bounded per-row lookup.

## 1.3.1-beta15 — Admin decomposition

- Removed the legacy `BML_Admin` monolith.
- Split dashboard, locations, taxonomy, import, export, demo, notices, settings, assets, and menu responsibilities under `src/Admin`.
- Registered admin services and actions through the plugin container.
- Removed service construction from admin actions and the import AJAX controller.
- Separated menu registration from business logic and moved location/settings renderers into namespaced components.

## 1.3.1-beta14

- Added UTF-8 validation, BOM handling and automatic comma/semicolon/tab delimiter detection.
- Added duplicate/unknown/required header policies, blank-row handling and locale-independent coordinates.
- Added explicit duplicate conflict codes that prevent ambiguous updates.
- Added formula-injection protection, optional UTF-8 BOM and field allowlisting for CSV export.
- Replaced unbounded export loading with filtered, paginated streaming and multi-term taxonomy output.

## 1.3.1-beta13

- Added ImportJob, ImportJobCounters, ImportJobError and ImportJobSummary DTOs.
- Added versioned, bounded ImportJobPayloadSerializer with legacy payload migration and corruption recovery.
- Added ImportJobRepositoryInterface and owner job listing.
- Moved import logs and errors to the bounded bml_import_job_events table.
- Added independent, repeatable schema migrations versions 1 through 4.

## 1.3.1-beta13

- Added committed/read progress markers for crash-safe CSV batches.
- Added persistent per-row import journal with deterministic source row hashes.
- Added idempotent replay of committed rows and recovery of rows saved before journal commit.
- Added expiring batch leases (`locked_by`, `locked_until`) for safe parallel AJAX processing.
- Added journal cleanup with expired import jobs and uninstall cleanup.
- Added tests for row hashing, progress markers, journal indexes and lease schema.

## 1.3.1-beta11

- Added `ImportJobStatus` enum and centralized `ImportJobStateMachine`.
- Added explicit import error codes and safe public AJAX error responses.
- Added atomic state transitions for process, pause, resume, cancel, complete and failure flows.
- Cancelled jobs are retained as history records; CSV files are deleted and `file_path` is cleared.
- Added separate history TTL for completed and cancelled jobs.
- Added safe retry policy: automatic retry is allowed only when a batch failed before processing its first row.
- Failed jobs that may have partially mutated WordPress data are marked unrecoverable.
- Added `processing`, `retrying` and `failed` jobs to active import file protection.
- Normalized active paths during orphan cleanup.
- Updated database schema version to 1.3.1.
- Added unit coverage for all import job transitions and status behavior.

## 1.3.1-beta10
- Added persistent `bml_import_jobs` storage.
- Removed the shared import registry option and transient job storage.
- Added SHA-256 token storage, optimistic locking, atomic batch claims, expiration indexes, and parallel-import protection.
- Completed jobs remain available as database records until expiry while temporary CSV files are removed.


## 1.3.1-beta9

### Import refactoring

- Split the CSV import monolith into PSR-4 components for CSV reading, mapping, processing, duplicate detection, logging, upload validation and import directory protection.
- Kept `ImportManager` as an orchestration service while preserving the beta8 AJAX and job payload contracts.
- Added `ImportLimits` with filters for batch size, job TTL, maximum rows, maximum record size and file size.
- Added hard safety bounds for every configurable import limit.
- Added unit tests for CSV inspection, header normalization, row mapping, validation, duplicate fingerprints and filtered limits.
- Updated the test bootstrap to use PSR-4 autoloading for import components.

## 1.3.1-beta8

- Added distinct Pause, Resume and final Cancel states for CSV imports.
- Fixed duplicate CSV error counting between inspection and processing.
- Added dedicated Dry Run counters: would create, update, skip and fail.
- Added structured import errors with row, column, code and message fields.
- Hardened in-flight Pause/Cancel handling to prevent cancelled jobs from being recreated.
- Added cleanup regression tests for active, expired and orphaned import files.

## 1.3.1-beta7

- Hardened CSV uploads with extension, MIME and actual file-size validation.
- Added a 10 MB default upload limit, 50,000-row limit and 1 MB CSV-record limit.
- Protected the temporary import directory with index, Apache and IIS deny files.
- Added guaranteed temporary-file cleanup on prepare/process exceptions and cancellation.
- Added owner-bound import sessions to prevent one administrator from controlling another administrator's job.
- Added an hourly cron cleanup registry for expired import jobs and orphaned CSV files.
- Scheduled cleanup automatically after plugin updates and removed the cron hook on deactivation.


## 1.3.1-beta6

### Architecture
- Replaced the legacy locations REST wrapper with the namespaced `BusinessMapLocator\Rest\LocationsController` registered through the plugin container.
- Registered `LocationRepository`, `LocationCache`, `SearchLocationsHandler`, and `LocationResponseFactory` as the single locations search pipeline.
- Removed the legacy `BML_Locations_Controller` and `BML_Location_Search` classes and their class-map entries.
- Kept frontend, shortcode, and Gutenberg consumers on the shared `/business-map/v1/locations` REST endpoint.


## 1.3.1-beta1 — Sprint 1: Core stabilization

- Made `BusinessMapLocator\Plugin` the single application bootstrap.
- Added container-managed registration of Admin, Frontend, REST, Database, cache, indexer, shortcode and provider services.
- Moved post type, taxonomies, post meta, Gutenberg block, translations and privacy registration into `src/WordPress/`.
- Moved settings defaults and tile URL sanitization into `src/Settings/Settings.php`.
- Added dedicated activation and deactivation lifecycle services.
- Reduced `BML_Plugin` to a backward-compatibility facade.
- Added provider registry injection to the frontend module.
- Synchronized plugin and readme PHP/version metadata.

# 1.3.0-beta5.3 — Data foundation

- Added and hardened the `wp_bml_locations_index` database schema.
- Added database version tracking and safe `dbDelta()` migrations.
- Added batched background index rebuilding for locations created before the upgrade.
- Added automatic index synchronization for post saves, newly added or updated meta, taxonomy changes, trash, restore and deletion.
- Added explicit index updates for the custom editor, CSV import, duplication and bulk actions.
- Added safe table-existence guards around index operations.
- Updated uninstall cleanup to remove the index table and migration options when Delete data is enabled.

# 1.3.0-beta5

- Complete SaaS-style rebrand of every plugin admin page.
- New light navigation sidebar with grouped sections and collapse state.
- Unified header, cards, forms, tables, settings, import, taxonomy and editor styling.
- Improved responsive behavior and visual consistency.
- Preserved all existing plugin functionality and compatibility routes.

## 1.3.1-beta8

- Added distinct Pause, Resume and final Cancel states for CSV imports.
- Fixed duplicate CSV error counting between inspection and processing.
- Added dedicated Dry Run counters: would create, update, skip and fail.
- Added structured import errors with row, column, code and message fields.
- Hardened in-flight Pause/Cancel handling to prevent cancelled jobs from being recreated.
- Added cleanup regression tests for active, expired and orphaned import files.

## 1.3.0-beta4.1

- Fixed OpenStreetMap tile template sanitization.
- Preserved Leaflet placeholders `{s}`, `{z}`, `{x}` and `{y}` when settings are saved.
- Automatically repairs previously corrupted OpenStreetMap tile URLs at runtime.
- Prevented repeated `ERR_NAME_NOT_RESOLVED` requests for `y.png`.


## 1.3.0-beta4
- Rebuilt Settings into Map setup, Design & preview, Publishing and System.
- Added a compact Google Maps connection workflow with clearer diagnostics.
- Added address search and a large interactive default-map workspace.
- Added a real production preview powered by the shared locator renderer.
- Added desktop, tablet and mobile preview modes with live layout and control updates.
- Unified shortcode and settings preview through BML_Locator_Renderer.


## 1.3.0-beta3

- Rebuilt the unified Settings workspace into five focused sections: Map provider, Appearance, Publish, Performance and Advanced.
- Added a guided Google Maps setup flow with key visibility control and in-browser verification for Maps JavaScript API, Geocoding API and current-domain access.
- Added a large interactive default-map preview; dragging, clicking and zooming update the selected center and zoom.
- Added provider health indicators, smart unsaved-change status and improved responsive settings layouts.
- Added publishing helpers for Gutenberg, shortcode, PHP and REST usage.
- Separated performance controls from destructive maintenance options.

## 1.3.0-beta2 — Location Workspace
- Rebuilt Add/Edit Location as a wide workspace without the plugin sidebar.
- Added sticky editor header, always-visible save bar and publication state.
- Added tabbed General, Address & Map, Contacts, Hours & Services, and Media & Preview sections.
- Enlarged the map and combined address search, geocoding state and coordinates.
- Added unsaved-change protection and live preview updates.
- Extracted the location editor page, view and save action from the monolithic admin class.

## 1.3.1-beta8

- Added distinct Pause, Resume and final Cancel states for CSV imports.
- Fixed duplicate CSV error counting between inspection and processing.
- Added dedicated Dry Run counters: would create, update, skip and fail.
- Added structured import errors with row, column, code and message fields.
- Hardened in-flight Pause/Cancel handling to prevent cancelled jobs from being recreated.
- Added cleanup regression tests for active, expired and orphaned import files.

## 1.3.0-alpha1 — Foundation

- Local-first Leaflet and MarkerCluster asset architecture.
- CRUD permission, post type, title, coordinates and attachment hardening.
- Google Maps callback-based loading.
- Frontend request cancellation, per-page support, unit formatting and localization.
- Base diagnostics through REST health and admin notices.

## 1.3.0-alpha2 — REST Architecture

- Added a dedicated `BML_Locations_Controller`.
- Added REST pagination, sorting, response metadata and `X-WP-Total` headers.
- Added extended server-side search across title, content, address, region, country, postcode, phone, cities and categories.
- Added server-side category and city filtering.
- Added optional transient REST caching controlled by plugin settings.
- Added automatic cache invalidation for locations, terms and settings changes.
- Updated frontend filtering to request filtered data from REST instead of filtering only the initial client-side dataset.


## 1.3.0-beta1 — Provider Architecture

- Added `BML_Map_Provider_Interface` and `BML_Provider_Registry`.
- Added dedicated OpenStreetMap and Google Maps PHP providers.
- Added provider-specific JavaScript adapters.
- Added provider-neutral `BMLMapController`.
- Refactored frontend runtime so filtering, lists, geolocation and popups no longer contain map-provider-specific logic.
- Added automatic OpenStreetMap fallback when Google Maps is selected without a configured API key.
- Added provider configuration and status data to diagnostics and localized frontend settings.
