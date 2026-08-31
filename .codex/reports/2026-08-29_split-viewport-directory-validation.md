# Split viewport directory validation

## Root cause

Split-directory card requests did not include the live map viewport, while marker requests did. Additionally, repository directory search replaced a supplied viewport box with the Near-me prefilter box, so a Near-me directory result was not necessarily constrained to the visible map.

## Implementation

- Split layout captures the provider's actual visible bounds once per result refresh.
- The same captured bounds object is sent to both `/locations?page=1&per_page=24` and `/locations/markers`.
- A settled Split map move resets card pagination to page 1 and refreshes cards and markers through one debounced viewport handler.
- Load more retains `directoryBounds`; a moved viewport starts a new `cardSequence`, so an older page response cannot append to the new viewport list.
- Directory-only and other non-Split layouts retain unbounded paginated card requests.
- Repository search appends both the supplied viewport bounding box and the Near-me radius bounding box, while retaining the Haversine radius `HAVING` predicate. Totals therefore reflect the intersection.
- Directory rendering does not move or fit the map.

## Automated validation

- PHP 8.4.19 / PHPUnit 10.5.64 focused contracts: 8 tests, 77 assertions, PASS.
- PHP 8.4.19 / PHPUnit 10.5.64 full suite: 56 tests, 204 assertions, PASS.
- PHP syntax: PASS for changed production and focused test files.
- `git diff --check`: PASS.
- JavaScript syntax: NOT RUN — Node unavailable.
- Fix 03B bounded pagination contract: PASS; no full-directory recursion or `load_all` behavior added.
- Atomic OSM marker generation contract: preserved.
- OSM zoom 19 / no `zoomToShowLayer()`: preserved.

## Source/stand parity

PASS after deployment:

- `assets/js/map-controller.js`: `4C30716D9148C19A2F6A0B8F9D26C209E5532935E963E5DC22A079DCDF0B84AB`
- `src/Infrastructure/Database/LocationRepository.php`: `A85D6A30B8CC903E581546F0DE9136230724CB47A362210E2442F7C7EB6476B1`

## Manual runtime verification required

Not run in this session because no browser automation surface is available. Verify Split list/marker parity for unfiltered Minsk → Brest → empty viewport → Minsk; category, city, search, Near me, Reset; card → map; and a viewport with more than 24 results followed by Load more using the unchanged captured bounds.

## Verdict

DEPLOYED — USER VIEWPORT DIRECTORY TEST REQUIRED
