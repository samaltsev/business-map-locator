#!/usr/bin/env sh
set -eu
ROOT="$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)"
mkdir -p "$ROOT/assets/vendor/leaflet/images" "$ROOT/assets/vendor/markercluster"
fetch() { url="$1"; out="$2"; if command -v curl >/dev/null 2>&1; then curl -L --fail "$url" -o "$out"; elif command -v wget >/dev/null 2>&1; then wget -O "$out" "$url"; else echo "curl or wget is required" >&2; exit 1; fi; }

# Development/release helper only. The plugin runtime loads bundled local files
# from assets/vendor and does not fall back to these remote URLs automatically.
fetch https://unpkg.com/leaflet@1.9.4/dist/leaflet.js "$ROOT/assets/vendor/leaflet/leaflet.js"
fetch https://unpkg.com/leaflet@1.9.4/dist/leaflet.css "$ROOT/assets/vendor/leaflet/leaflet.css"
fetch https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png "$ROOT/assets/vendor/leaflet/images/marker-icon.png"
fetch https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png "$ROOT/assets/vendor/leaflet/images/marker-icon-2x.png"
fetch https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png "$ROOT/assets/vendor/leaflet/images/marker-shadow.png"
fetch https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js "$ROOT/assets/vendor/markercluster/leaflet.markercluster.js"
fetch https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css "$ROOT/assets/vendor/markercluster/MarkerCluster.css"
fetch https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css "$ROOT/assets/vendor/markercluster/MarkerCluster.Default.css"
echo "Vendor assets downloaded."
