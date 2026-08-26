# Server-side frontend loading evidence

- Backup: `D:\WP plugins\business-map-locator\.codex\backups\2026-07-23_18-14-26_server-side-frontend-loading`
- Deployment: source/stand sync PASS; postflight `2026-07-23_18-22-06-stand-health.json` PASS.
- Marker HTTP: valid bounds returned HTTP 200 (54,223 B); marker rows contain only id/title/lat/lng/status/minimal category and truncation metadata.

## Runtime consumer matrix
| Component | Request | State owner | Abort behavior | Notes |
|---|---|---|---|---|
| LocatorController | cards | root instance | card controller | page one only |
| LocatorController | markers | root instance | marker controller | bounds endpoint |
| LocatorController | detail | root instance | detail controller | explicit activation |
| Settings Studio | collection | Settings runtime | unchanged | out of scope |

## Request contract
| Type | Endpoint | Trigger | Replaceable | Response |
|---|---|---|---|---|
| cards | `/locations` | initial/filter | yes | card factory |
| markers | `/locations/markers` | map ready/moveend | yes | lightweight repository rows |
| detail | `/locations/{id}` | card/marker activation | yes | detail factory |

## Implementation

- Removed the public workers loop that fetched every collection page.
- Card requests use configured page and the existing collection compatibility payload.
- Marker bounds are loaded separately through the canonical repository, maximum 1000 (hard capped at 2000), and include `truncated` metadata.
- Independent AbortControllers and sequence IDs protect cards, markers, and detail responses.
- State, selectors, requests and listeners remain per `.bml-locator` controller instance.
- Leaflet and Google adapters expose bounds access/change hooks.

## Limitations

Browser automation was SKIPPED: the required Node REPL browser runtime is not available in this environment. No two-instance browser scenario could be executed. Detail data is currently fetched lazily but the existing popup UI remains compatibility-first rather than a redesigned detail panel. No temporary frontend dataset was created; existing 808 records were not modified.
