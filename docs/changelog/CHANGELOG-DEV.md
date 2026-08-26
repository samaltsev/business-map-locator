# Development Changelog

This changelog records internal architecture and implementation milestones. It is not the public release changelog.

## 2026-07-24

### Documentation structure updated

- Added live roadmap and release-plan documents.
- Added runtime, REST, Areas and contracts architecture records.
- Added development and public changelogs.
- Promoted Master Specification v3.0 as the current product source of truth.
- Updated development orchestration reference from Master Specification v2.0 to v3.0.

### Areas Migration — in progress

Completed:

- verified backup and preflight;
- dependency audit across taxonomy registration, editor/save, index, repository, REST, frontend, shortcodes, import/export, demo data, cache invalidation, taxonomy admin, tests and uninstall.
- Phase A conditionally accepted by owner: registered isolated hierarchical `bml_area` with dedicated capabilities and no consumer integration;
- Phase A correction: upgraded the capability schema to `1.3.0` and added regression coverage proving that a `1.2.0` installation receives every Area capability;
- added read-only migration state inspection, JSON snapshot ownership metadata, non-mutating dry run, and snapshot-only rollback foundation;
- verified source/stand parity, health endpoints, runtime snapshot validation, and that dry run did not mutate Location, City, or Area counts.

Decision:

- relationship migration will not begin before canonical `bml_area` and a dedicated migration service exist;
- migration must include snapshot, dry run, journal, verification and selective rollback;
- accepted marker metadata debt is assigned to the active Areas Migration round.

Next implementation result expected:

- Phase B validation and dry-run verification, without term or relationship mutation.

Phase B remains blocked pending full PHPUnit-suite and browser acceptance evidence, unless the owner separately accepts those environment limitations.

## 2026-07-23

### Server-side Frontend Loading — accepted

Implemented:

- removed eager loading of all REST pages;
- paginated cards load only the current page;
- added bounds-based marker endpoint;
- added lazy detail loading;
- separated AbortControllers and sequence guards for cards, markers and detail;
- isolated state per Locator instance;
- added provider bounds hooks.

Accepted technical debt:

- marker response metadata: `returned`, `totalInBounds`, `truncated`.

Verification recorded:

- PHP syntax passed;
- marker endpoint returned HTTP 200;
- automated deployment passed;
- source/stand parity passed;
- browser automation was unavailable and therefore not counted as passed.

### REST Card / Detail Contract — accepted

- Stable public card representation.
- Lazy detail endpoint.
- Public field allowlist and safe response behavior.

### Location Contract P0 — accepted

- Canonical Location save behavior.
- Supported public fields are no longer destructively cleared by unrelated saves.
- Public eligibility does not require category or City.

### Development Safety Baseline — accepted

- Timestamped backup workflow.
- Automated stand deployment.
- Rollback and health checks.
- Source/stand parity.

### Settings Runtime Stabilization — accepted

- Settings runtime execution stabilized.

### Runtime Architecture Audit — accepted

- Runtime ownership and overlapping legacy/canonical paths documented.
