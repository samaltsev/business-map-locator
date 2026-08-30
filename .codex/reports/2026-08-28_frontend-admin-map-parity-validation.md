# Frontend/Admin map parity validation

## Root cause and flows

Admin Preview and frontend both obtain current Leaflet/Google bounds, request `/locations/markers`, and render the returned `items`. Frontend already keeps directory cards in `state.items` and viewport markers in `state.markers`; it does not derive markers from the current directory page.

The behavioral differences found were:

- Admin Preview set `limit=200`; frontend used the REST default `1000`, so their marker sets could differ in a dense viewport.
- Both OSM Leaflet tile layers allowed z20, which is unsupported by the standard OSM raster service.
- Frontend used MarkerCluster `zoomToShowLayer()` during focus while accepted marker refreshes clear and rebuild its marker layer, allowing a stale-cluster animation race.

## Implementation

- Admin Preview now requests the same `1000`-marker bounded result limit as frontend.
- Frontend always replaces the OSM marker generation for a latest accepted marker response.
- Equivalent marker data does not skip rendering; a complete fresh Leaflet layer is created and installed for each latest accepted response.
- OSM marker focus uses the current registry marker coordinates directly rather than `zoomToShowLayer()`.
- Frontend and both active Admin Preview Leaflet tile-layer paths cap map and native tile zoom at 19.

## Static and automated validation

- PHP syntax for `FrontendMarkerViewportContractTest.php`: PASS.
- Targeted PHPUnit: PHP 8.4.19, PHPUnit 10.5.64, 2 tests, 28 assertions, PASS.
- Full PHPUnit: PHP 8.4.19, PHPUnit 10.5.64, 52 tests, 167 assertions, PASS.
- JavaScript syntax: NOT RUN — Node unavailable.
- `git diff --check`: PASS.
- Source/stand SHA-256 parity: PASS for `assets/js/map-controller.js`, `assets/js/providers/openstreetmap-provider.js`, `assets/js/admin/settings-ux.js`, and `assets/js/admin.js`.

## Runtime matrix

- Initial frontend pagination: PASS by regression contract; page 1 and configured `per_page` remain in use with one-page Load more.
- Frontend Vitebsk markers: NOT RUN — no browser automation execution surface is available in this session.
- Admin Preview Vitebsk markers: NOT RUN — no browser automation execution surface is available in this session.
- Card → map: NOT RUN — no browser automation execution surface is available in this session.
- Pan/zoom stability: NOT RUN — no browser automation execution surface is available in this session.
- Category/city/search parity: PASS by source contract: frontend marker requests merge active category, city, search, and Near me parameters with current bounds.
- z20 tile check: PASS by provider configuration contract; both Leaflet tile layers use `maxZoom: 19` and `maxNativeZoom: 19`.

## Historical accepted-response rendering follow-up (superseded by final lifecycle update)

The original equivalent-data optimization compared only the cached `MarkerController.locations` array. A provider layer cleared outside that cache could therefore make a later equivalent response return early with no visible markers. The new provider-aware guard requires equivalent data and current `hasMarkers()` confirmation of both registry ids and Leaflet `layer.hasLayer(marker)` membership. The existing `markerSequence` guard remains before both `state.markers` assignment and `setLocations()`, so an older response cannot mutate the visual layer after a newer request wins.

- Empty-layer + equivalent-data contract: PASS — the provider check fails and forces rebuild.
- Equivalent-data + populated current layer contract: PASS — rebuild may be skipped.
- Latest-response visual guard: PASS by source contract.
- Manual frontend Borisov/Vitebsk/Minsk smoke: NOT RUN — user verification required because this session has no browser automation execution surface.

## Historical temporary runtime marker telemetry (superseded and removed)

Temporary diagnostic logging is deployed for the next manual frontend run. Each accepted latest marker response emits `BML marker debug` with sequence, response/valid-item counts, bounds, skip decision, registry and cluster-layer counts before/after, map attachment, zoom, and center. OSM clear/add logging emits `BML marker clear` and `BML marker add` records including actual `layer.hasLayer(marker)` and `map.hasLayer(clusterLayer)` results.

The OSM provider now ensures its marker layer is attached before clear/add and exposes its actual registry/layer/attachment state. This logging is investigation-only and must be removed after runtime evidence is captured.

## Final marker lifecycle update

### Root cause

The frontend refreshed its persistent Leaflet MarkerCluster layer in place with `clearLayers()` and individual marker additions. That lifecycle can overlap focus and refresh handling; the equivalent-data shortcut could also preserve an invalid visual generation. Admin Preview instead creates a complete new layer generation.

### Admin layer lifecycle

Admin Preview removes the prior `leafletLayer`, creates a new marker-cluster or layer-group layer, adds its markers, then attaches that completed layer to the map.

### Old frontend layer lifecycle

Each accepted marker response cleared the active frontend layer and repopulated it marker by marker. The temporary debug telemetry described above was used during diagnosis only.

### New frontend layer lifecycle

The `markerSequence` guard remains before `state.markers` assignment and visual changes. Each latest accepted OSM response now creates a fresh generation, fills it, calls `newLayer.addTo(this.map)`, publishes the new layer/registry/bounds, and then removes the old layer. The generic provider fallback remains unchanged.

### Persistent clearLayers() used for viewport refresh

No. OSM viewport refreshes call `replaceMarkers()`; `clearMarkers()` remains only for teardown and the non-OSM compatibility fallback.

### Equivalent skip

Removed. Every latest accepted OSM marker response installs a fresh marker generation.

### Filtered/unfiltered same render path

Yes. All marker request triggers resolve through `LocatorController.loadMarkers()` and `MarkerController.setLocations()`; request filters change data, not the renderer lifecycle.

### Temporary debug logging removed

Yes. `BML marker debug`, `BML marker add`, and `BML marker clear` have been removed.

### Targeted tests

PHP 8.4.19; PHPUnit 10.5.64; `FrontendMarkerViewportContractTest`: 2 tests, 28 assertions, PASS.

### Full suite

PHP 8.4.19; PHPUnit 10.5.64; 52 tests, 167 assertions, PASS.

### Static validation

PHP syntax for `FrontendMarkerViewportContractTest.php`: PASS. `git diff --check`: PASS. JavaScript syntax: NOT RUN — Node unavailable.

### Fix 03B pagination

Preserved: directory loading remains page-based with configured `per_page`, one-page Load more, and no recursive full-directory loading. Markers remain a separate bounded viewport request.

### OSM zoom 19

Preserved: frontend and both active Admin Preview Leaflet paths use `maxZoom: 19`, `maxNativeZoom: 19`, and do not use `zoomToShowLayer()`.

### Source/stand parity

PASS for every changed production asset: `assets/js/admin.js`, `assets/js/admin/settings-ux.js`, `assets/js/map-controller.js` (`3063C713E2681CA027D7C3BF1A00DE8277B0B04005C1006AD4B3193207B6D361`), and `assets/js/providers/openstreetmap-provider.js` (`CB8EB5E6039524EC9838E3CD799D36B205850C17D06EA3E45341D1C529CB5472`). The final deployment copied only the two lifecycle assets.

### Final runtime verification

Manual runtime verification completed.

- Unfiltered viewport movement, Minsk → Borisov → Brest → Vitebsk → Minsk: PASS.
- Latest `/locations/markers` requests, HTTP 200: PASS.
- Non-empty marker responses render visibly: PASS.
- Repeated pan stress: PASS.
- Repeated zoom stress: PASS.
- Card → map: PASS.
- MarkerCluster `_icon` exception: NOT REPRODUCED.
- OSM z20 requests: NOT PRESENT.
- Filtered category behavior: PASS.
- Filtered city behavior: PASS.
- Filtered/unfiltered rendering parity: PASS.
- Fix 03B pagination: PASS.

Preserved automated evidence: targeted tests (2 tests / 28 assertions), full suite (52 tests / 167 assertions), `git diff --check`, source/stand parity, and atomic fresh marker generation: PASS. Persistent `clearLayers()` for viewport refresh: NOT USED. Equivalent skip: REMOVED. OSM max zoom 19: PASS.

### Verdict

FRONTEND/ADMIN MAP PARITY READY FOR REVIEW
