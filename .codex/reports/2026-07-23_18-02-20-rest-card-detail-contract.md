# REST Card/Detail Contract evidence

- Backup: `D:\WP plugins\business-map-locator\.codex\backups\2026-07-23_17-59-21_rest-card-detail-contract`
- Routes: `GET /business-map/v1/locations`, `GET /business-map/v1/locations/{id}`.
- Source/stand parity: `2026-07-23_18-01-42-source-stand-manifest.json` — PASS.
- Database/schema changes: none.

## Consumer matrix

| Consumer | Current keys | Final card keys | Detail use later | Compatibility note |
|---|---|---|---|---|
| map-controller.js | image, status, website, hours, category/city, lat/lng, title, address | retained | future detail loading | collection remains compatible |
| Settings Studio preview | items, title, lat/lng, category/city | retained | no | pagination/items retained |
| shortcode/Gutenberg renderer | REST base and filters | unchanged | no | no route or filter break |

## Card contract

| Field | Type | Source | Required | Compatibility |
|---|---|---|---|---|
| id | integer | index | yes | retained |
| title/address/excerpt | string | index | yes/string | retained |
| lat/lng | number | index | yes for public item | retained |
| phone/email/website/hours/image | string | index with canonical fallback | empty string allowed | retained |
| category/city | object or null | index/taxonomy | nullable | retained |
| operational_status | string | index/meta | normalized | retained |
| distance | number or null | query | nullable | retained |

## Detail contract

| Field | Type | Source | Public safety | Test |
|---|---|---|---|---|
| id/title/permalink | integer/string | published post | public post only | runtime PASS |
| content | rendered HTML string | `the_content` filter | no raw meta exposed | serializer review |
| excerpt/contact/hours | string | post/meta | selected public fields only | runtime PASS |
| image/category/city/status | string/object/null/string | thumbnail/taxonomy/meta | selected public fields only | runtime PASS |

## Cache behavior

Collection cache remains in the existing `locations` bucket and varies by query cache key. Detail is intentionally uncached in this round, so it cannot collide with collection entries; existing save/taxonomy invalidation remains authoritative for collection data.

## Runtime smoke

Reusable helper: `.codex/scripts/Invoke-WordPressSmokeTest.ps1` and `.codex/scripts/runtime/location-contract-smoke.php`.

- Temporary ID: `20156`; prefix `BML_CODEX_RUNTIME_`.
- PASS: partial update preserved email and unknown meta.
- PASS: explicit hours clear preserved website.
- PASS: published detail returned 200.
- PASS: draft detail returned 404.
- Cleanup: PASS; temporary post permanently deleted.
- Featured image: not tested because the helper intentionally does not create media.

## HTTP and payload measurements

- collection `per_page=1`: 200, 672 B, 1296 ms.
- collection `per_page=24`: 200, 23478 B, 1273 ms.
- malformed ID: 400.
- missing ID: 404.

Local timings are evidence only, not production performance claims. No contact values are recorded.

## Tests

- PASS: modified PHP lint, safe HTTP checks, source/stand parity, reusable runtime smoke.
- SKIPPED: PHPUnit, PHPCS, PHPStan and Node; runners unavailable.

## Compatibility debt

Collection continues to include email and hours because the pre-existing response contract exposed them. They can be reduced only after the frontend loading round migrates consumers.
