# Business Map Locator — Location Workspace

Version 1.3.0-beta2 replaces the long Add/Edit Location form with a dedicated workspace.

## Architecture
- `src/Admin/Location/LocationEditorPage.php` prepares view data.
- `src/Admin/Location/View/location-editor.php` renders the workspace.
- `src/Admin/Location/Action/SaveLocationAction.php` validates and saves the location.
- `assets/js/admin/location-editor.js` controls tabs, dirty state, preview and media.
- `assets/js/admin/location-map.js` owns Leaflet, geocoding and coordinates.
- `assets/css/admin/pages/location-editor.css` contains page-specific styles.

## UX
The workspace removes the internal plugin sidebar, keeps saving actions visible, and makes the map the primary address-editing tool.
