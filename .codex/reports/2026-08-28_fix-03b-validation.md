# Fix 03B validation evidence

## Scope and deployment

- Verified backup: `.codex/backups/2026-08-28_11-35-24_fix-03b`.
- Source/stand SHA-256 parity: PASS for `assets/js/map-controller.js` and `includes/Frontend/class-bml-locator-renderer.php`.
- Production scope: Split directory pagination/loading only. No REST schema, database, version, provider, CSV, Areas, or Google clustering changes.

## Fix 03B contract

- Initial directory request `page=1`: PASS.
- Configured `per_page=24`, not `500`: PASS.
- No automatic recursive page `2..N` loading: PASS.
- Bounded marker REST endpoint: PASS.
- The renderer and controller contain none of `load_all`, `loadAll`, `loadAllDirectory`, `directoryBatchSize`, or `loadRemainingDirectoryPages`.
- The controller retains normal per-page pagination, a one-page Load more action, bounded markers, detail-by-ID, AbortController, and stale-response sequencing.
- Baseline `f3cee18` contains `load_all` and `loadAll`; the Fix 03B negative regression assertion fails against that baseline.

## REST and pagination runtime

Pretty REST was restored with `permalink_structure = '/%postname%/'` and valid WordPress `.htaccess` rewrite rules.

- `GET /wp-json/business-map/v1/locations?page=1&per_page=24...`: HTTP 200.
- Headers: `X-WP-Total: 809`; `X-WP-TotalPages: 34`.
- Response pagination: `page: 1`, `perPage: 24`, `total: 809`, `totalPages: 34`.
- `GET /wp-json/business-map/v1/locations/markers?...bounds...`: HTTP 200; `truncated: false`.

Query-style REST compatibility was not tested and is outside Fix 03B scope.

## PHPUnit

- Targeted: PHP 8.4.19, PHPUnit 10.5.64, 2 tests, 12 assertions: PASS.
- Full suite: PHP 8.4.19, PHPUnit 10.5.64, 50 tests, 139 assertions: PASS.

## Static validation

- PHP syntax: PASS.
- `git diff --check`: PASS.
- JavaScript syntax: NOT RUN — Node unavailable.

## Known pre-existing OSM provider defects

Runtime smoke exposed two defects:

1. MarkerCluster throws an `_icon` exception during marker focus/refresh.
2. OSM raster requests at z20 return HTTP 400.

Diagnostic classification:

```text
PRE-EXISTING PROVIDER BUG
FIX 03B CAUSED THIS: NO
```

Accepted marker refreshes clear and recreate MarkerCluster markers while `focusMarker()`/`zoomToShowLayer()` can overlap the refresh. This lifecycle exists in `main` before Fix 03B. The OSM tile layer also already has `maxZoom: 20` in `main`. Fix 03B did not touch the provider implementation, card selection, focus methods, marker replacement, marker timing, or tile configuration.

- Card → map smoke: NOT PASSING DUE TO PRE-EXISTING PROVIDER DEFECT.
- Fix 03B regression: NO.

## Final verdict

FIX 03B: READY FOR FINAL REVIEW

Known blocker outside Fix 03B scope: PRE-EXISTING OSM PROVIDER STABILITY DEFECT — SEPARATE FOLLOW-UP REQUIRED.
