# Free 1.0 production roadmap — status 2026-09-14

## Purpose

This document records the actual delivery stage of the Free plugin and the remaining production gates. It is a delivery roadmap, not a claim that every item from the Master Spec has already been implemented.

Baseline:

- branch: `codex/production-hardening`;
- code baseline: `cb6afa6183d91aef32bd7f8209146d67dd21e6b7` (`Harden migration planning blockers`);
- installed runtime version: `1.3.2-beta40.7`;
- latest automated evidence: targeted migration-planning suite — 7 tests / 20 assertions; full PHPUnit suite — 229 tests / 987 assertions; `git diff --check` passed;
- runtime planning smoke: `INSPECTED → SNAPSHOTTED → READY`, run `313849b1-c0f7-49dc-886b-0a86bd8a58a7`, with 810 Locations, 96 Cities and 0 Areas before and after.

## Current stage

**Pre-Free-1.0 hardening / beta.**

The core catalogue, import/export, REST pagination, OSM/Leaflet, Google provider option, relation-index foundation and admin workflow exist. The Migration control planning slice is deliberately limited to `Inspect → Snapshot → Simulate → READY/BLOCKED`.

It must remain non-destructive:

- no Execute, Resume or Rollback action is exposed;
- no City, Area, Location or relationship mutation is performed by planning;
- no index rebuild is performed by planning;
- only the planning run and snapshot were created in the runtime smoke.

The planning smoke passed, but it is not a production migration approval and it does not make the full Free 1.0 product release-ready.

## Completed or accepted gates

| Gate | Status | Evidence / limitation |
|---|---|---|
| Catalogue foundation: Locations, Categories, Cities, CSV import/export | Accepted beta foundation | Runtime catalogue contains 810 Locations and 96 Cities. |
| REST pagination, bounds and server-distance path | Implemented and tested | Existing REST contract and regression coverage. |
| Location relation-index foundation | Implemented | Separate relation-index table and canonical owner path exist. |
| Migration planning state machine | Accepted | Nonce/capability guarded `Inspect → Snapshot → Simulate`; invalid states and taxonomy blockers are tested. |
| Snapshot durability | Accepted | Unique snapshot suffix prevents same-second collision; legacy snapshot names remain readable. |
| Planning runtime smoke | Passed | Final state `READY`; no business-data change observed in UI counts. |

## Remaining Free 1.0 gates

| Order | Gate | Why it is required before Free production | Current position |
|---:|---|---|---|
| 1 | Canonical Location contract | One save/import/index/REST/frontend definition for address, contacts, website, image, short/full description, hours and status. Prevents data loss and empty public fields. | P0 — not yet closed. |
| 2 | Free cards and public detail contract | Complete compact card, accessible detail panel/popup, correct CTAs and truthful status labels (`Active`, never false `Open now`). | P0 — not yet closed. |
| 3 | Server-driven frontend directory | Stop client-side full-result loading; preserve pagination, filters, bounds and detail loading on the server/API contract. | P0 — not yet closed. |
| 4 | Areas and controlled City → Area migration | Free 1.0 requires hierarchical Areas. Planning is ready, but the data model, admin management, runtime UX and separately approved execution/rollback work remain. | P0 — planning only; execution intentionally deferred. |
| 5 | Free locator configuration | Directory-only layout, consistent global settings, shortcode builder, Gutenberg controls, two Free presets and multiple manual shortcode instances. | P1 — partially present. |
| 6 | Maps parity | Public settings contract for style/marker, Google clustering parity or safe feature gating, OSM default behaviour. | P1 — not fully closed. |
| 7 | Localization and accessibility baseline | English source strings, EN/DE/UK/RU packs/POT workflow, keyboard/focus/error states and WCAG baseline checks. | P1 — not yet closed. |
| 8 | Release hardening | Full regression/runtime matrix, PHP/WordPress compatibility matrix, security review, migration/recovery evidence and performance checks. | P1 — not yet closed. |
| 9 | Release packaging | Version parity, changelog, readme/docs, clean install ZIP, fresh-install/upgrade/uninstall evidence and SHA-256. | Release — not started. |

## Delivery plan

### Cycle A — data and public contract

Close Gate 1, then Gate 2. Each slice must follow:

`admin input → validation → storage → index → REST → frontend → tests → documentation`.

Acceptance: no manual Location save can erase fields that import or REST rely on, and every public card/detail field has one canonical source.

### Cycle B — catalogue runtime

Close Gate 3 and the Free part of Gate 6. Acceptance: no browser-side “load every page” behaviour; bounds, search, category, area and Near me use the server contract; provider capability is truthful.

### Cycle C — Areas decision

Decide whether hierarchical Areas are in Free 1.0.

- If **yes** (the current Master Spec target), implement the Area admin/data/REST/frontend path, then conduct a separately approved disposable-clone execution and rollback programme. The current `READY` run is only evidence that planning is possible.
- If **no**, revise the Master Spec and Free/Pro matrix before release. A Free 1.0 release cannot silently omit a stated required hierarchy.

### Cycle D — authoring and product finish

Close Gate 5, Gate 7 and the remaining Gate 6 work. Acceptance: editors can configure each supported Free layout without undocumented global side effects; translations and accessible interaction are verified.

### Cycle E — Free Release Candidate and GA

Close Gates 8–9 in a release-only round. Freeze features, run the full test/runtime matrix, test upgrade from the current beta and a fresh WordPress installation, assemble a clean ZIP, verify it after unpacking, then tag Free 1.0.

## Estimate of remaining work

There are **five substantive development cycles plus one release cycle** remaining before a credible Free 1.0 production release:

1. Location contract and cards;
2. server-driven directory and map parity;
3. Areas decision and implementation/migration path;
4. locator authoring, localization and accessibility;
5. production hardening;
6. release packaging and release-candidate verification.

This is deliberately expressed as gates rather than a percentage. The open items touch data safety, public UX and release confidence; they cannot responsibly be reduced to “a few screens”.

## Explicit non-goals for the next code round

- Do not add Execute, Resume or Rollback controls.
- Do not run City → Area migration on the current runtime.
- Do not rebuild the relation index as part of migration planning.
- Do not start Pro-only Services, Schedules, Saved Locators, analytics or remote sync before Free gates are closed.

## Recommended next code round

**P0 — canonical Location contract audit and hardening.**

Scope:

- map every Location field across editor, import, storage, relation index, REST and frontend;
- identify conflicts and destructive saves;
- define a canonical DTO/normalizer and compatibility rules;
- add regression tests for manual save, import update, REST read and index update;
- make no Area migration execution changes.

