# Settings provider panels — 1.3.2-beta2

## Behaviour
- Provider selection is always visible.
- Only the selected provider configuration panel is rendered as active.
- Initial map center is shared by both providers.
- Google Maps uses one shared browser loader with callback queue, timeout and auth failure handling.
- Preview failures show actionable messages instead of an endless loading state.

## Manual acceptance
1. Select OSM: only OSM settings are visible and Leaflet preview renders.
2. Select Google: only Google key and diagnostics are visible.
3. Valid Google key: preview initializes and reacts to center/zoom changes.
4. Invalid/restricted key: explicit error appears after auth failure or timeout.
5. Switch repeatedly between providers: scripts are not duplicated and each preview resizes correctly.
