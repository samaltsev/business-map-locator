# Canonical Contracts

**Status:** Location and REST baseline accepted; Area and integration contracts planned  
**Last verified:** 24 July 2026

This document describes contract ownership. Current implementation is distinct from target and planned contracts; implementation status is owned by [the roadmap](../roadmap/ROADMAP.md).

## Current Runtime

| Contract | Status | Canonical owner | Current boundary |
|---|---|---|---|
| Location save | Accepted | Application save action/service | Supported fields are non-destructive; unknown extension metadata is preserved. |
| Location repository | Accepted baseline | `LocationRepository` | One canonical search repository. |
| REST card | Accepted | Locations REST controller/DTO | Paginated public card fields. |
| REST detail | Accepted | Locations REST controller/DTO | Lazy allowlisted public detail. |
| Marker loading | Accepted baseline | Locations REST + frontend controller | Bounds-only minimal payload with `truncated`. |
| Territory model | Current legacy | `bml_city` taxonomy | City is the only registered territory taxonomy. |

### Location contracts

- A publicly eligible Location is published, coordinate-valid, and not hidden. Category and City are optional.
- Field sanitization is type-specific.
- Saving one UI section does not clear omitted supported fields.
- Unknown extension metadata is preserved unless its owner explicitly removes it.
- Indexing follows successful source-data mutation; taxonomy and cache invalidation use the same write path.

### Frontend contract

- Cards, markers, and detail are independent request channels.
- Locator instances are isolated.
- The frontend does not fetch all collection pages automatically.
- Stale responses cannot replace newer state.
- Providers expose map operations rather than search ownership.

## Target Architecture

| Contract | Target owner | Required behaviour |
|---|---|---|
| Area model | `bml_area` taxonomy and migration service | Hierarchical Area model with City compatibility aliases. |
| Marker loading | Locations REST + frontend controller | Filter parity and `returned`, `totalInBounds`, `truncated` metadata. |
| Renderer | Canonical Locator renderer | Stable owner for builder, block, and integrations. |

Area target names are `area` and `areas`; legacy `city`, `city_id`, and `city_slug` remain compatible aliases during the beta transition. See [Areas architecture](Areas.md).

## Planned Integration Contracts

Before Pro and ecommerce add-ons, the core must provide `LocationReadModel`, `LocationPublicDto`, `LocationRepositoryInterface`, `SearchLocations`, `ResolveLocator`, `RenderLocator`, `ValidateLocationAvailability`, `GetLocationSnapshot`, and `GetEligibleLocations`.

These contracts require semantic versioning, feature detection, allowlisted public fields, application-service writes, no private-table reads by add-ons, and compatibility support across at least two minor releases for breaking changes.
