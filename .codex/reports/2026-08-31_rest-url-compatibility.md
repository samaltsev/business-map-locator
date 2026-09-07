# REST URL compatibility validation

## Scope

- Affected runtime builders: `assets/js/map-controller.js` and `assets/js/admin/settings-ux.js`.
- Unaffected admin path: `assets/js/admin.js` continues to use `wp.apiFetch`.
- No REST PHP route, repository, pagination, marker lifecycle, or selected-location behavior was changed.

## Implementation

- Each affected runtime uses a local `buildRestUrl()` helper for every manually-built REST endpoint; the helpers intentionally remain local to avoid an asset enqueue/dependency redesign.
- The helper uses the URL API, appends endpoints to pretty REST paths, appends endpoints to the existing `rest_route` query value for Plain permalinks, and preserves existing query parameters while adding request parameters.
- Frontend coverage includes filters, locations, markers, bounds, and detail. Settings Studio coverage includes markers, bounds, preview locations, and `geocode/search`.
- Example query-style contract: `https://example.com/?rest_route=/business-map/v1/geocode/search&q=M%C3%BCnchen+Hauptbahnhof`. The `q` value remains a separate query parameter.

## Automated validation

- Targeted PHPUnit (`RestUrlCompatibilityContractTest`, `SplitDirectoryPaginationContractTest`, `SplitInteractionParityContractTest`): PASS — 7 tests, 60 assertions.
- Full PHPUnit suite: PASS — 95 tests, 372 assertions.
- PHP lint for the three changed contract tests: PASS.
- `git diff --check`: PASS.
- JavaScript syntax: NOT RUN — Node unavailable.

## Stand deployment and parity

- Verified backup: `.codex/backups/2026-09-07_10-40-46_rest-url-compatibility-hardening`.
- Deployed production assets only: `assets/js/map-controller.js`, `assets/js/admin/settings-ux.js`.
- SHA-256 source/stand parity: PASS.
  - `assets/js/map-controller.js`: `BED87CF354F5E431791D56D605266B8CBE6852B9F527475AA029E9413A3950A9`
  - `assets/js/admin/settings-ux.js`: `8D05B7FC4EBB71162920B9555283D612498FEB0A24E8CB3D363F868A44676301`

## Runtime REST smoke

The normal local permalink structure was `/%postname%/`. It was temporarily set to Plain only to obtain the actual WordPress base, then restored in a `finally` block.

- Actual WordPress Plain base: `https://business-map-locator/index.php?rest_route=/business-map/v1/`.
- Restored permalink structure: `/%postname%/`.
- Both URL styles were requested directly against the local stand.

- Pretty and query-style locations: HTTP 200.
- Pretty and query-style bounded markers: HTTP 200.
- Pretty and query-style bounds: HTTP 200.
- Pretty and query-style detail: HTTP 200.

Browser-only flows (initial UI load, Load More, map move, city filter, marker detail, and Settings geocode) were NOT RUN and require user smoke testing in both permalink modes. No JavaScript runtime is available locally for executable URL-construction coverage.

## Verdict

DEPLOYED — REST URL COMPATIBILITY READY FOR USER SMOKE TEST
