# Leaflet bulk marker visibility fix

## Proven runtime evidence

The Borisov diagnostic showed matching directory and marker responses (11 each),
11 valid markers, 11 registry entries, 11 layer entries, and an attached marker
layer at zoom 12. The failure was therefore after Leaflet layer attachment.

## Root cause and fix

Split-layout frontend CSS applied `z-index: auto` to every `.leaflet-pane`.
That override defeated Leaflet's defined pane ordering and allowed the visible
tile/map rendering to obscure the populated marker generation.

The override was removed. The fix restores the required Split-map pane order
within the BML map only: tile 200, overlay 400, shadow 500, marker 600,
tooltip 650, popup 700. It also prevents the locator's generic responsive image
rule from constraining Leaflet marker, shadow, or tile image dimensions.

The temporary `bml_debug_markers` panel and provider debug accessors were
removed before deployment.

## Validation

- PHP 8.4.19 / PHPUnit 10.5.64
- Targeted: 10 tests, 99 assertions — PASS
- Full suite: 64 tests, 272 assertions — PASS
- `git diff --check` — PASS
- Leaflet, MarkerCluster, and MarkerCluster Default CSS source assets exist — PASS

## Source/stand parity

- `assets/js/map-controller.js`:
  `69899AC8C3BFE6E18E7D6F0EB85623839C35E30FF26A8A07D3E7C1C762546A37`
- `assets/js/providers/openstreetmap-provider.js`:
  `CDAC9D9FC79C55B04398713EC07F467C7A4A7B9C1F2FE18D244B430FB800089F`
- `assets/css/frontend.css`:
  `56D35CAB6AF4AC2E8C999541F38D1C1D6205D374042DDAB7EE3CD8638A6727E8`

## Manual verification required

Without the debug parameter: reset filters; verify visible bulk markers in
Borisov, Zhodino, and Minsk; then verify marker click opens popup/sidebar detail
without removing the remaining viewport markers.
