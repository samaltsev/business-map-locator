# Sprint 1 — Core stabilization

## New composition root

`src/Plugin.php` is the only application bootstrap. It owns the service container, registers core WordPress hooks and starts legacy modules through container-managed instances.

## Moved to `src/`

- `src/Settings/Settings.php` — defaults, settings access and tile URL validation.
- `src/WordPress/ContentTypes.php` — location post type and taxonomies.
- `src/WordPress/MetaRegistrar.php` — location post meta registration.
- `src/WordPress/BlockRegistrar.php` — Gutenberg block registration.
- `src/WordPress/TextDomain.php` — translations.
- `src/WordPress/PrivacyPolicy.php` — privacy policy content.
- `src/Lifecycle/Activator.php` — activation workflow.
- `src/Lifecycle/Deactivator.php` — deactivation workflow.

## Legacy compatibility

`includes/Core/class-bml-plugin.php` no longer owns the plugin boot process. It remains as a compatibility facade for existing code using:

- `BML_Plugin::settings()`
- `BML_Plugin::sanitize_tile_url()`
- legacy activation/deactivation calls
- legacy registration methods

These compatibility methods delegate to services from `src/` and can be removed in a future major release after internal callers are migrated.

## Uninstall

The plugin continues to use WordPress' standard root-level `uninstall.php`. Data is removed only when the `delete_data` setting is enabled.
