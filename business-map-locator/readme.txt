=== Business Map Locator ===
Contributors: businessmaplocator
Tags: map, locator, openstreetmap, leaflet, branches
Requires at least: 6.4
Tested up to: 6.8
Requires PHP: 8.1
Stable tag: 1.3.2-beta40.7
License: GPLv2 or later

Modern business and store locator for branches, offices and service points.

== Features ==
* OpenStreetMap and Leaflet out of the box
* Optional Google Maps provider
* Locations CPT, category and city taxonomies
* Click/drag map coordinate editor
* Nominatim address search and reverse geocoding through WordPress REST proxy
* Search, category and city filters
* Near-me sorting and directions link
* CSV import/export
* Demo data installer
* REST API
* Gutenberg block and shortcodes

== Shortcodes ==
[business_map_locator]
[business_map_locator category="office" city="minsk" height="620"]

== External services ==
OpenStreetMap tile servers provide map tiles. Nominatim provides optional address search initiated by an administrator. Google Maps is only loaded when selected and an API key is configured.

== MVP note ==
This beta uses a provider registry and bundled local Leaflet/MarkerCluster assets. Vendor files must be included locally before WordPress.org release; external CDN assets are not loaded automatically.


== Changelog ==

= 1.2.0 =
* Full custom WordPress Admin interface based on the supplied prototype.
* Custom Locations, Add/Edit Location, Categories and Cities screens.
* Interactive Leaflet editor with address search, click-to-place and draggable marker.
* New Providers, Display, Gutenberg and Settings pages.
* Unified admin and frontend design system.


== 1.3.0-alpha2 ==
* Hardened location CRUD permissions and validation.
* Added local map vendor asset registry with explicit diagnostics for missing bundled files.
* Fixed Google Maps async callback startup.
* Added AbortController, per_page, units, localization, debounce and marker lookup by ID to frontend runtime.
* Added richer health diagnostics.


== 1.3.0-beta1 ==
* Added PHP provider contract and provider registry.
* Added OpenStreetMap and Google Maps provider classes.
* Added JavaScript provider adapters and a provider-neutral MapController.
* Removed Leaflet and Google-specific map logic from the frontend application.
* Added provider fallback to OpenStreetMap when Google Maps is not configured.
* Added provider status data to frontend configuration and health diagnostics.
