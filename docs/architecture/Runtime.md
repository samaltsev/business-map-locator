# Runtime Architecture

**Status:** Accepted runtime baseline  
**Last verified:** 24 July 2026

This document distinguishes runtime facts from architecture that has not yet been implemented. Project status is owned by [the roadmap](../roadmap/ROADMAP.md).

## Current Runtime

### Ownership and safety

- A feature has one canonical runtime owner; legacy classes may only be compatibility facades.
- Product changes use a verified backup, automated stand deployment, health checks, source/stand parity, and evidence reporting.
- `.codex`, tests, reports, backups, and build files are excluded from installed packages.
- Database rollback is explicit and migration-owned; a blanket database restore is not a runtime contract.

### Locator instance model

Each rendered Locator instance owns its DOM root, filters, pagination, selected Location, provider instance, event listeners, teardown, request sequence values, and separate card/marker/detail `AbortController` references. No global mutable Locator state is permitted.

### Loading model

- Cards load from the collection endpoint one server page at a time; the browser does not automatically fetch every page.
- Markers load independently by current map bounds and use a minimal marker payload.
- Detail loads lazily by Location ID and is cached only for the lifetime of its Locator instance.
- Card, marker, and detail requests are independently cancellable; sequence guards prevent stale responses from replacing newer state.

### Provider adapter contract

Implemented providers expose initialization/destruction, marker lifecycle, focus/fit operations, `getBounds()`, `onBoundsChanged()`, and provider error state. Providers do not own search or REST state.

## Target Architecture

- Preserve the server-loading and instance-isolation baseline through Areas and Directory + Map work.
- Add complete legacy runtime isolation before GA.
- Add browser automation when an approved runner is available.
- Marker response-limit metadata is defined in [REST architecture](REST.md); only its currently implemented subset may be relied on at runtime.

## Planned

- No additional runtime architecture is accepted by this document until implementation evidence is recorded in the roadmap and development changelog.
