# Selected Split sidebar card layout fix

## Root cause

Late compact Split-card styling retained the collapsed-card constraints without
an explicit expanded-state release. The inline detail is appended within the
selected card, so the selected state must explicitly remain in normal document
flow and permit its height to grow.

## Fix

The final stylesheet adds a narrow `.is-expanded` rule after the compact Split
rules. An expanded card and its inline detail now use automatic height, no
maximum height, and visible overflow. The inline detail is explicitly static,
so the following card is pushed downward by normal document flow. Desktop
expanded cards retain block layout; normal cards remain compact. The directory
results container retains its existing scrolling behavior.

No JavaScript, map, REST, marker lifecycle, viewport pagination, or selected
location behavior was changed.

## Validation

- PHP 8.4.19 / PHPUnit 10.5.64
- Targeted: 11 tests, 98 assertions — PASS
- Full suite: 65 tests, 279 assertions — PASS
- `git diff --check` — PASS
- Source/stand `assets/css/frontend.css` SHA-256:
  `73136AAEBBD42A17C1D2A6AE1ECA7A85715E047F6B37F81DD8D25F91B57FDEEA` — PASS

## Manual verification required

Select a detailed Split-sidebar location, confirm its full content pushes the
next card below it and the sidebar remains scrollable; then select another
location and verify the prior card collapses. Check desktop and mobile/tablet.
