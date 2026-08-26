# Changelog

## Unreleased

### Added

- Dedicated REST endpoint for marker loading by current map bounds.
- Lazy loading of complete Location details.
- Independent frontend state for multiple Locator instances.

### Improved

- Location card and detail response behavior.
- Request cancellation and stale-response protection.

### Fixed

- Frontend no longer automatically downloads every page of Location cards.
- Multiple Locator instances no longer share mutable request state.
- Published coordinate-valid Locations are not excluded solely because category or territory is empty.
