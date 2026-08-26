# REST Architecture

**Status:** Card/detail and server-loading baseline accepted  
**Last verified:** 24 July 2026

This document records the public beta runtime separately from target contract evolution. Project status is owned by [the roadmap](../roadmap/ROADMAP.md).

## Current Runtime

### Public surfaces

- `GET /business-map/v1/locations` provides paginated public cards.
- `GET /business-map/v1/locations/{id}` provides lazy public detail.
- `GET /business-map/v1/locations/markers` requires only `north`, `south`, `east`, and `west` bounds.
- Filter, public-health, and capability-protected diagnostics surfaces remain available through their registered beta routes.

### Card and detail

- Cards include only the public representation required by the current frontend and preserve stable field types.
- Detail is lazy-loaded by ID and exposes the allowlisted public Free fields only.
- Public visibility requires a published, coordinate-valid, non-hidden Location; category and City are optional.
- Parameters are validated server-side, SQL is parameterized, and public diagnostics disclose no secrets.

### Marker endpoint

Current marker requests are bounds-only. The response shape is:

```json
{
  "items": ["minimal marker records"],
  "truncated": false
}
```

`truncated` indicates that the current response limit was exceeded. The current endpoint does **not** accept active category, City, Area, search, radius, or sorting filters, and does **not** return `returned` or `totalInBounds`.

### Concurrency

Cards, markers, and detail use separate cancellation channels and sequence guards. A stale response must not overwrite newer state.

## Target Architecture

### Marker contract

After the corresponding implementation is accepted, marker requests must apply the same applicable public filters as cards and return response-level metadata:

```json
{
  "returned": 1000,
  "totalInBounds": 1874,
  "truncated": true
}
```

- `returned` is the number of marker rows returned.
- `totalInBounds` is the count of all matching public Locations before the response limit.
- `truncated` is `totalInBounds > returned`.

This metadata belongs once at response level, never on every marker record.

### Area transition

The target public names are `area`, `area_id`, `area_slug`, and `areas`. During beta compatibility, `city`, `city_id`, and `city_slug` remain stable aliases. Area parameters take precedence only after Area support is implemented. Parent Area filtering and City-to-Area resolution are target behaviour, not current endpoint behaviour.

### Cache contract

Target cache keys account for locale, filters, bounds, origin/radius, units, sort, pagination, response view, extension fields, and a data-version key. Area term changes must invalidate collection, marker, detail, and facet representations when Area exists.

## Planned

- Implement Area query/response compatibility and descendant semantics during Areas Migration.
- Implement marker filter parity and the complete metadata contract during Areas Migration.
- Publish a route-by-route API reference from verified runtime responses after these changes are accepted.
