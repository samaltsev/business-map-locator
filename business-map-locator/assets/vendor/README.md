# Local map vendor assets

Place the official distributions here:

- `leaflet/leaflet.js` and `leaflet/leaflet.css` (Leaflet 1.9.4)
- `leaflet/images/*` marker and layer images
- `markercluster/leaflet.markercluster.js`, `MarkerCluster.css`, `MarkerCluster.Default.css` (Leaflet.markercluster 1.5.3)

The plugin loads only these local vendor files. If any file is missing, restore it before release or installation; no CDN fallback is loaded automatically.
