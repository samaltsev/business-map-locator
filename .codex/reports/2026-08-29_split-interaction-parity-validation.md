# Split interaction parity validation

## Diagnostic root causes

- City centering was removed when the earlier Split pagination change removed `focusFilteredLocations()`. The replacement must not fit page-one cards, so the restored flow uses the existing aggregate `/locations/bounds?city=…` endpoint.
- Directory/marker viewport parity is maintained by one `refreshResults()` bounds snapshot for both bounded requests. City selection now fits aggregate bounds first, then refreshes that viewport; the map-move handler never invokes city fitting, avoiding a fit/load loop.
- The atomic OSM generation already passes its `onSelect` callback through `replaceMarkers()` to every new `createMarker()` call. The controller now routes that callback through an explicit `handleMarkerSelection()` path that selects the card and calls the existing `loadDetail()` modal pipeline.

## Contract preserved

- `/locations` and `/locations/markers` receive the same captured Split viewport bounds and active filters.
- Latest card, marker, aggregate-bounds, and detail sequences retain stale-response protection.
- City aggregate bounds do not derive from paginated directory items.
- Fresh marker layers remain atomic; no normal viewport `clearLayers()` rebuild, equivalent skip, or `zoomToShowLayer()` was restored.
- Hidden detail remains protected by the direct-detail publish/status checks.
- OSM remains capped at `maxZoom: 19` and `maxNativeZoom: 19`.
- Fix 03B bounded directory pagination remains intact.

## Automated validation

- PHP 8.4.19 / PHPUnit 10.5.64 focused contracts: 16 tests, 117 assertions, PASS.
- PHP 8.4.19 / PHPUnit 10.5.64 full suite: 58 tests, 225 assertions, PASS.
- PHP syntax: PASS for changed test and relevant REST/repository files.
- `git diff --check`: PASS.
- JavaScript syntax: NOT RUN — Node unavailable.

## Source/stand parity

PASS after deployment for `assets/js/map-controller.js`:
`295C78ACC2E5FF3DEDD665FCA3CBF0A783C21A11D45209E2B7CF8F990C81C039`.

## Manual user verification required

Not run in this session because no browser automation surface is available.

1. Select Vitebsk: map fits aggregate city bounds; bounded cards and visible markers/clusters appear.
2. Click a marker after Vitebsk, then after moving to Brest: the existing detail modal opens.
3. Reset and move Minsk → Brest → Vitebsk: cards and markers refresh together.
4. Test category + city, search, Near me, Reset, empty viewport, and Load more with unchanged viewport bounds.

## Verdict

DEPLOYED — USER FINAL SPLIT INTERACTION TEST REQUIRED
