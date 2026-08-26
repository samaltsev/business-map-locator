# Business Map Locator MVP

1. Upload `business-map-locator-mvp.zip` through **Plugins → Add New → Upload Plugin**.
2. Activate **Business Map Locator**.
3. Open **Business Map → Dashboard**.
4. Click **Install demo data**, or add categories, cities and locations manually.
5. Insert the Gutenberg block **Business Map Locator**, or use `[business_map_locator]`.

## Working MVP functions

- OpenStreetMap / Leaflet map on the location editor and frontend.
- Click the map or drag the marker to set coordinates.
- Address search and reverse geocoding through a protected WordPress REST proxy.
- Location categories and cities as native WordPress taxonomies.
- Native location CRUD, featured images, filters and bulk actions.
- Frontend text search, category filter, city filter, marker clustering and “Near me”.
- CSV import/export and demo data installer.
- REST endpoints:
  - `/wp-json/business-map/v1/locations`
  - `/wp-json/business-map/v1/filters`
  - `/wp-json/business-map/v1/health`
- Dynamic Gutenberg block and shortcodes.
- Optional Google Maps frontend provider after adding a valid API key.

## Important MVP limitation

Leaflet and MarkerCluster are loaded from unpkg CDN in this build because the build environment could not download and bundle vendor files. Before WordPress.org submission, place those libraries in `assets/js/vendor` and `assets/css/vendor` and switch the enqueue URLs to local plugin files.

## UI layout version 1.1.0

The administration interface has been restyled to follow the supplied Business Map Locator prototype:
- card-based dashboard;
- SaaS-style provider cards;
- two-column location editor with a sticky live OpenStreetMap preview;
- modern form controls, statistics, tables and responsive layout;
- frontend list + map layout matching the supplied prototype.

A static copy of the supplied design reference is included in `docs/ui-reference/` for comparison.


## Admin pages in 1.2.0

Dashboard, Locations, Add Location, Categories, Cities, Map Providers, Import / Export, Display, Gutenberg Block and Settings now use the unified Business Map Locator interface.

## Version 1.3.0-alpha2 vendor assets

The plugin uses local Leaflet/MarkerCluster files automatically when they exist under `assets/vendor/`.
This alpha package includes a helper script:

```bash
sh tools/fetch-vendor.sh
```

When local vendor files are unavailable, the plugin uses pinned CDN URLs as a compatibility fallback and shows a diagnostic warning on **Business Map → Map Providers**.
