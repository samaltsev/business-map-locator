# Provider Architecture

Business Map Locator 1.3.0-beta1 separates map rendering from locator application logic.

## PHP layer

- `BML_Map_Provider_Interface` defines provider configuration and asset methods.
- `BML_Provider_Registry` registers providers and resolves the active provider.
- `BML_OpenStreetMap_Provider` registers Leaflet and MarkerCluster assets.
- `BML_GoogleMaps_Provider` registers Google Maps with callback-based startup.

## JavaScript layer

- `base-provider.js` defines the shared provider surface.
- `openstreetmap-provider.js` implements Leaflet rendering.
- `google-maps-provider.js` implements Google Maps rendering.
- `map-controller.js` is the only map API used by `frontend.js`.

Provider methods:

- `init(container, options)`
- `setLocations(locations)`
- `focusLocation(id, zoom)`
- `fitBounds()`
- `setUserLocation(position, label)`
- `destroy()`
