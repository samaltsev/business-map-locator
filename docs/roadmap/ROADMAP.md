# Business Map Locator — Development Roadmap

**Current development baseline:** `1.3.2-beta29`  
**Master specification:** [NBH Business Locator Master Spec v3.0](../specification/NBH-Business-Locator-Master-Spec-v3.0.md)  
**Last status update:** 24 July 2026  
**Current implementation round:** Areas Migration

This is the single source of project implementation status. Product requirements and final acceptance criteria remain in the Master Specification; release gates and sequencing remain in the Release Plan.

## Status legend

- ✅ Completed and accepted
- 🟨 In progress
- ⬜ Planned
- ⏸ Deferred
- ⚠ Blocked or requires a decision

## Current status

| Stage | Status | Result |
|---|---|---|
| Runtime Architecture Audit | ✅ | Runtime ownership, legacy overlap and critical execution paths documented |
| Settings Runtime Stabilization | ✅ | Settings runtime stabilized and accepted |
| Development Safety Baseline | ✅ | Automated backup, deployment, rollback, health checks and source/stand parity |
| Location Contract P0 | ✅ | Canonical save behavior and non-destructive Location field handling |
| REST Card / Detail Contract | ✅ | Stable collection/card and lazy detail responses |
| Server-side Frontend Loading | ✅ | Paginated cards, bounds markers, lazy detail, cancellation and instance isolation |
| Areas Migration | 🟨 | Phase A conditionally accepted: isolated `bml_area`, snapshot, dry run and rollback foundation; AC-9 PHPUnit and AC-10 browser acceptance remain before Phase B |
| Directory + Map UX | ⬜ | Full directory, synchronized detail, mobile and accessibility UX |
| Publication Tools | ⬜ | Shortcode Builder, Gutenberg controls, layouts and Free presets |
| Free RC | ⬜ | Release quality, localization, performance, security and compatibility matrix |
| Free GA | ⬜ | Production package, evidence, release assets and owner acceptance |

## Completed foundation

### Runtime and safety

- Runtime Architecture Audit completed and accepted.
- Settings Runtime Stabilization completed and accepted.
- Development Safety Baseline completed and accepted.
- Verified timestamped backups are required before implementation rounds.
- Deployment to the local WordPress stand is automated.
- Product-file parity and stand health checks are part of acceptance.
- Development files, reports and backups must not enter distributable plugin packages.

### Canonical Location and REST contracts

- Location save contract stabilized.
- Manual saving must not delete supported Free fields or unknown extension metadata.
- Category and territory remain optional for public visibility.
- REST card and detail contracts implemented.
- Detail data loads lazily by Location ID.

### Server-side frontend loading

Accepted implementation includes:

- cards load only the requested page;
- marker data loads independently for current map bounds;
- previous card, marker and detail requests are abortable;
- sequence guards prevent stale responses from replacing current state;
- every Locator instance owns its state and listeners;
- OSM and Google provider adapters expose bounds-change hooks;
- the eager all-pages loading loop is removed.

### Current runtime boundary for Areas and markers

- `bml_city` is the only registered territory taxonomy; it remains the active public and internal territory contract.
- `bml_area`, Area REST parameters, Area response fields, and City-to-Area migration are not yet implemented.
- the marker endpoint currently accepts bounds only and returns `items` plus `truncated`;
- marker filter parity and response metadata `returned`, `totalInBounds`, `truncated` remain Areas Migration work.

## Current iteration — Areas Migration

### Completed in this iteration

- Verified backup and preflight.
- Full `bml_city` dependency audit.
- Phase A implementation and verification completed:
  - isolated hierarchical `bml_area` taxonomy registration with dedicated capabilities;
  - read-only migration state inspection;
  - JSON snapshot creation with ownership metadata;
  - non-mutating dry-run statistics;
  - snapshot discovery and validation rollback foundation only.
  - capability schema upgraded to `1.3.0` so existing `1.2.0` installations receive all dedicated Area capabilities.
- Direct dependencies confirmed in:
  - taxonomy registration;
  - Location editor and save flow;
  - index schema and indexer;
  - `LocationRepository`;
  - collection, detail and filters REST layers;
  - frontend controller;
  - shortcode and block compatibility;
  - import/export;
  - demo installer;
  - cache invalidation;
  - taxonomy admin actions and UI;
  - tests and uninstall.

### Next phase — Phase B: validation and dry-run verification

Phase B is blocked until the owner accepts the missing AC-9 PHPUnit-suite and AC-10 browser evidence, or records an explicit decision to accept those environment limitations.

- Validate snapshot schema, ownership, taxonomy state, term hierarchy and relationship counts against the real stand.
- Report conflicts and warnings without creating terms or relationships.
- Prove dry-run idempotency and no data mutation under representative data.
- Do not execute migration, alter index/cache, or expose Area through public surfaces.

### Required implementation order

1. Register canonical hierarchical `bml_area` without changing existing relationships.
2. Introduce a dedicated migration service.
3. Add migration state, snapshot, journal and ownership records.
4. Implement a non-mutating dry run.
5. Validate conflicts, hierarchy and expected relationship changes.
6. Execute the migration only after dry-run validation passes.
7. Preserve every legacy `bml_city` term and relationship.
8. Add canonical Area filtering and descendant semantics.
9. Add public `area` fields while retaining stable `city` aliases.
10. Update index, facets, editor, import/export compatibility and cache invalidation.
11. Add marker metadata: `returned`, `totalInBounds`, `truncated`.
12. Verify selective rollback, idempotency, runtime behavior and source/stand parity.

### Exit criteria

- One canonical hierarchical Area taxonomy exists.
- Migration is explicit, resumable, idempotent and reversible.
- Dry run does not mutate WordPress data.
- Existing City data remains intact.
- Parent Area filters include descendants.
- Locations without Area remain visible when no Area filter is active.
- REST, shortcode, block and CSV legacy aliases remain compatible.
- Marker metadata accurately reports limits and truncation.
- Evidence report is complete and accepted.

## Next iteration — Directory + Map UX

Planned only after Areas Migration is accepted.

Scope:

- reference `Directory + Map` layout;
- compact card and selected detail panel;
- card/marker/detail synchronization;
- result count, sorting and pagination/load more;
- hierarchical Area filter UI;
- loading, empty, error and provider-failure states;
- mobile List/Map switch;
- filter drawer and detail bottom sheet;
- keyboard/focus behavior and WCAG baseline;
- no regression to server pagination, bounds markers or instance isolation.

## Publication Tools

- canonical shortcode contract;
- Shortcode Builder;
- category and Area selectors;
- visible, locked and hidden filter modes;
- Gutenberg controls;
- four layouts;
- two Free visual presets;
- responsive preview;
- ordinary Elementor Shortcode widget compatibility smoke test.

## Free RC

- CSV Area aliases and documentation;
- English source strings;
- EN/DE/UK/RU artifacts;
- Plugin Check;
- PHPCS and agreed PHPStan level;
- automated and environment matrix;
- security, privacy and external-services documentation;
- accessibility and performance reports;
- upgrade rehearsal and source/package parity.

## Free GA

- clean install and beta upgrade accepted;
- release ZIP built from verified source;
- signed hashes and release notes;
- WordPress.org assets;
- rollback package;
- final evidence and owner acceptance.

## Later release tracks

### Pro 1.0

- Services;
- structured schedules;
- Smart Availability;
- Smart Ranking;
- Saved Locators;
- per-Locator settings;
- six total system presets;
- Custom Presets;
- native Elementor Saved Locator widget;
- graceful downgrade.

### WooCommerce Pickup add-on

Separate package after Free/Pro `1.0.x` stabilization:

- shipping-zone method;
- server-side eligibility;
- Classic Checkout;
- Checkout Blocks and Store API;
- HPOS-compatible immutable order snapshot.

## Roadmap governance

After every accepted implementation round:

1. Update this file’s status table.
2. Update an architecture document only when a new architecture contract is accepted.
3. Add a dated entry to [CHANGELOG-DEV](../changelog/CHANGELOG-DEV.md).
4. Add user-visible changes to [CHANGELOG](../changelog/CHANGELOG.md) only when appropriate.
5. Link the final evidence report from the changelog entry.
6. Do not mark a feature complete until its full flow is verified: admin input → validation → storage → index → REST → frontend → tests → documentation.
