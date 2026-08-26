# UI-0 Decision Package — Final Approval

All UI-0 defaults in this document are formally **APPROVED**.

Approval type: Product and architecture approval  
Approval scope: UI-0 defaults  
Approval status: FINAL  
UI-0 blocking decisions remaining: 0  
UI-0 readiness: READY

## Approved decisions

### OD-01 — Directory First

Status: **APPROVED**  
Blocks UI-0: **NO**  
Approval scope: UI-0 default

Desktop locator uses Directory First as its canonical default: directory is primary; the synchronized map remains available, never the sole navigation surface. Alternatives considered: balanced split and map-first. Acceptance: directory remains usable without map and both regions use one result set.

### OD-02 — Sticky map behaviour

Status: **APPROVED**  
Blocks UI-0: **NO**  
Approval scope: UI-0 default

Sticky map at ≥1280px; non-sticky split at 1024–1279px; List/Map mode below 1024px. Acceptance: no scroll trap, synchronized selection and accessible short-viewport behaviour.

### OD-03 — Desktop Location detail

Status: **APPROVED**  
Blocks UI-0: **NO**  
Approval scope: UI-0 default

Desktop side panel; tablet side panel or modal only where width permits; mobile full-screen sheet. Dedicated Location route may exist for deep linking/SEO. Acceptance: retain filters and scroll; focus enters/returns; Back closes detail before locator exit; marker sync remains intact.

### OD-04 — Pagination strategy

Status: **APPROVED**  
Blocks UI-0: **NO**  
Approval scope: UI-0 default

Explicit Load More over a server-side page-based API; no automatic infinite scroll in Free 1.0. Acceptance: explicit page state, reset on filter/viewport changes, announced loading, retry and bounded rendered cards.

### OD-05A — Map provider fallback

Status: **APPROVED**  
Blocks UI-0: **NO**  
Approval scope: UI-0 default

On provider failure enter immediate directory-only safe mode and show a non-blocking notice. OSM fallback is explicit, user-initiated and only available when allowed. No silent provider substitution. Covers invalid keys, network/loading/quota/timeout, geocoding and runtime failures.

### OD-05B — Image fallback

Status: **APPROVED**  
Blocks UI-0: **NO**  
Approval scope: UI-0 default

Use a local neutral or category placeholder, preserve card geometry, never render a broken image and never depend on an external placeholder service. It covers missing, invalid, blocked, slow or unsupported images and missing WordPress attachments.

## Approval summary

| Decision | Status | Blocks UI-0 | Approved default |
|---|---|---|---|
| OD-01 | APPROVED | NO | Directory First |
| OD-02 | APPROVED | NO | sticky ≥1280px; non-sticky split 1024–1279px; List/Map <1024px |
| OD-03 | APPROVED | NO | desktop side panel → mobile full-screen sheet |
| OD-04 | APPROVED | NO | explicit Load More over page-based API |
| OD-05A | APPROVED | NO | directory-only safe mode + explicit fallback |
| OD-05B | APPROVED | NO | local neutral/category placeholder |

## Separate non-blocking product decision

External CSV image URL support is **not approved**. It remains a separate product decision and does not block UI-0 foundations.
