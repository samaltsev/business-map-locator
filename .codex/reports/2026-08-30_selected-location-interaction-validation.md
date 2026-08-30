# Selected location interaction validation

## Implementation

- Split uses one `selectedLocationId` / `selectedLocationDetail` state, with a small per-ID detail cache.
- Marker, directory card, and popup-detail action all route through `selectLocation()` and the existing on-demand `/locations/{id}` detail request.
- Split detail is rendered inline inside the selected card. If a selected marker is not in the current paginated card page, a dedicated selected-location block is inserted above the normal cards; no directory pages are loaded to find it.
- Detail response renders both the inline sidebar expansion and compact native provider popup.
- OSM and Google providers expose `openMarkerPopup()` / `closePopup()` against their current marker registries. OSM fresh generations retain click handlers and selection reconciles to the current registry after each accepted marker response.
- Selection clears when the active marker generation no longer contains the selected ID. Detail request sequencing and aborts prevent stale A responses from replacing newer B selection.
- Split no longer suppresses native map popups. The normal viewport marker lifecycle remains fresh-generation based.

## Preserved contracts

- Fix 01 hidden detail endpoint protection.
- Fix 03B bounded pagination and Load more.
- Viewport-bounded directory and marker requests, Near-me intersection, aggregate city centering, OSM zoom 19, and no `zoomToShowLayer()`.

## Automated validation

- PHP 8.4.19 / PHPUnit 10.5.64 targeted contracts: 16 tests, 125 assertions, PASS.
- PHP 8.4.19 / PHPUnit 10.5.64 full suite: 60 tests, 245 assertions, PASS.
- PHP syntax: PASS for `SelectedLocationInteractionContractTest.php`.
- `git diff --check`: PASS.
- JavaScript syntax: NOT RUN — Node unavailable.

## Source/stand parity

PASS after deployment:

- `assets/js/map-controller.js`: `69899AC8C3BFE6E18E7D6F0EB85623839C35E30FF26A8A07D3E7C1C762546A37`
- `assets/js/providers/openstreetmap-provider.js`: `E694EF70CC9CC0ABCAD2DFC9D554CE7CB4FE8DFE654CFBAB5B607759C241B755`
- `assets/js/providers/google-maps-provider.js`: `110CA6E15E4EF9ECC0539BEF7B83C7BC67BC9D8454B591B9956437D7FF1F492A`
- `assets/css/frontend.css`: `84873ACEA4EB572E667FC48F45E4C48931D8F387465610E3F4CCADC530E9DD9A`

## Manual user verification required

Not run in this session because browser automation is unavailable.

1. Click one marker, then another: each opens an anchored popup and only its sidebar detail remains expanded.
2. Click a sidebar card: the map focuses it, opens its popup, and expands its detail.
3. In a viewport with more than 24 results, click a marker outside page one: confirm a dedicated selected-location block appears above normal cards with no automatic page loading.
4. Pan/zoom to create fresh marker generations, then click a new marker.
5. Move to another city or change a filter: selection clears only when its marker leaves the active result.

## Verdict

DEPLOYED — USER SELECTED-LOCATION UX TEST REQUIRED
