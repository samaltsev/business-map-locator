# Settings UX Redesign — 1.3.2-beta1

## Goal
Turn Settings into a guided setup workspace without mixing data access, rendering and browser behavior.

## Files
- `src/Admin/Settings/SettingsPage.php`: prepares status/readiness data only.
- `src/Admin/Settings/View/SettingsRenderer.php`: renders setup progress, preview and status panel without database queries.
- `src/Admin/Settings/Action/SaveSettingsAction.php`: validates and persists preview style and marker color.
- `src/Settings/Settings.php`: owns defaults.
- `assets/js/admin/settings-ux.js`: provider switching, map style and marker live preview.
- `assets/css/admin/settings-ux.css`: isolated settings components and responsive rules.
- `src/Admin/Assets/AdminAssets.php`: settings-only asset registration.

## Regression boundaries
1. Existing settings tabs and legacy tab aliases remain valid.
2. OSM remains the fallback provider and requires no external key.
3. Google preview is loaded only after Google is selected and a key exists.
4. Renderer receives aggregate context and performs no storage query.
5. Settings are sanitized before persistence.
6. Existing `admin.js` map center controls continue to own coordinates and zoom.

## Test plan
- PHP lint all plugin files.
- JavaScript syntax check for both admin scripts.
- Structural PHPUnit test for the three UX components.
- Manual: switch OSM/Google, style and marker color before save.
- Manual: verify responsive layout at 1180px and 720px.
- Manual: confirm old settings URLs redirect to the matching step.
