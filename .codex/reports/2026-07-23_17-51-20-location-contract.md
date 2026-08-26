# Location Contract P0 evidence

- Backup: `D:\WP plugins\business-map-locator\.codex\backups\2026-07-23_17-43-52_location-contract-p0`
- Deployment parity: `D:\WP plugins\business-map-locator\.codex\reports\2026-07-23_17-50-45-source-stand-manifest.json`
- Postflight: `D:\WP plugins\business-map-locator\.codex\reports\2026-07-23_17-51-03-stand-health.json`
- Database/schema change: none. The existing index already has the required Free columns.

## Contract matrix

| Field | Admin input | Storage | Index | Repository | REST | Test |
|---|---|---|---|---|---|---|
| title | `title` | `post_title` | `title` | `title` | `title` | static regression |
| excerpt/content | `excerpt` / `content` | post fields | `excerpt` | `excerpt` | `excerpt` | static regression |
| image | optional `featured_image_id` / explicit remove | thumbnail meta | `image_id` | `image` | `image` | static regression |
| address/postcode | `bml_location_address` / postcode | `bml_*` meta | columns | mapped | mapped | static regression |
| coordinates | `lat` / `lng` | `bml_lat` / `bml_lng` | columns | valid coordinate predicate | `lat` / `lng` | static regression |
| phone/email/website | `bml_location_*` | `bml_*` meta | columns | mapped | mapped without forced empty values | static regression |
| hours | `bml_location_hours` | `bml_hours` | `hours` | mapped | mapped without forced empty value | static regression |
| operational status | `operational_status` | `bml_operational_status` | column | eligibility | normalized output | static regression |
| category/city | `category_id` / `city_id` | WP taxonomy | optional columns | only filter when supplied | nullable term | static regression |

## Implementation evidence

- Save uses explicit request-presence checks. Absent owned fields, thumbnail, taxonomy, unknown meta and Pro meta remain unchanged.
- Explicitly submitted empty owned fields are saved as empty. Featured image is removed only by `remove_featured_image`.
- Legacy `open` operational status maps to `active`; this is a neutral state, not an `open_now` calculation.
- Repository and legacy filter query no longer impose category/city eligibility predicates.
- REST payload retains existing keys and now uses indexed values or canonical meta fallbacks for email, website, hours and image.

## Checks

- PASS: PHP lint over all plugin PHP files.
- PASS: Location Contract static guards for destructive deletes, forced-empty fields and taxonomy predicates.
- PASS: source/stand manifest parity.
- PASS: stand health checks and safe GETs.
- PASS: `GET /wp-json/business-map/v1/locations?per_page=1` returned HTTP 200, total `808`, and the compatibility response keys including contacts/image/hours.
- SKIPPED: PHPUnit, PHPCS, PHPStan and Node runners are unavailable.

## Runtime data

No temporary locations were created: no safe WordPress CLI/bootstrap runner was available. Existing 808 records were not modified. Consequently, uncategorized and fully populated record assertions are covered by repository/response regression tests but not by a mutating stand scenario.

## Limitations

Browser-only admin interaction and database-level record creation were not run. No release version or ZIP was created.
