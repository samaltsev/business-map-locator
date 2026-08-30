# Selected Split sidebar scroll position fix

## Root cause

Marker selection used `selectedCard.scrollIntoView({ block: 'nearest' })`.
That browser-level ancestor selection could position the card outside the
directory results scrollport, beneath the Split toolbar/filter controls.

## Fix

Marker-driven selection now scrolls only the `.bml-results` container. It uses
the card and container bounding rectangles plus the container's current
`scrollTop`, with a 12px local padding. If the card header is already visible,
it does not scroll. Sidebar-origin selections continue not to auto-scroll.

No REST, map, marker, bounds, pagination, city-centering, or popup code changed.
The prior auto-height expanded-card layout is unchanged.

## Validation

- PHP 8.4.19 / PHPUnit 10.5.64
- Targeted: 12 tests, 104 assertions — PASS
- Full suite: 66 tests, 285 assertions — PASS
- `git diff --check` — PASS
- Source/stand `assets/js/map-controller.js` SHA-256:
  `CF97F44D64DC536307671AF4532D2B2CA6943EBB3E3A5DFCD65EE678AFFA483D` — PASS

## Manual verification required

In Vitebsk, scroll the directory into the middle and click three offscreen map
markers in sequence. Each selected card header must appear below the filters,
with its expanded detail in the results scrollport; the page must not jump.
