# Areas Architecture and City Migration

**Status:** Migration in progress; target architecture not implemented  
**Migration identifier:** `bml_city_to_area_v1`  
**Last verified:** 24 July 2026

Implementation status is owned by [the roadmap](../roadmap/ROADMAP.md).

## Current Runtime

- `bml_city` is the only registered territory taxonomy.
- `bml_city` is non-hierarchical and remains the active editor, index, repository, REST, frontend, shortcode/block, CSV, demo, cache, taxonomy-admin, test, and uninstall contract.
- Current public territory fields and query semantics are City-based.
- No `bml_area` taxonomy, Area migration service, Area REST parameters, Area response fields, or City-to-Area relationship migration exists in runtime.

## Target Architecture

The target is one canonical hierarchical `bml_area` taxonomy while preserving `bml_city` aliases through the beta compatibility window.

```text
Country
└── Region / State
    └── City
        └── District
            └── Custom Area
```

Area remains optional for publication. Required Area properties are hierarchical taxonomy ownership, canonical Location attachment, capability control, required REST availability, localized labels, no unintended public archive, and exactly one registration owner.

Target migration metadata is limited to `bml_area_type`, `bml_legacy_city_term_id`, `bml_migration_source`, and `bml_migrated_at`. Valid types are `country`, `region`, `city`, `district`, and `custom`.

## Migration — In Progress

The dependency audit is complete. Direct `bml_city` dependencies exist in taxonomy registration, editor/save, indexing, repository, REST, frontend, shortcode/block compatibility, CSV, demo data, cache invalidation, taxonomy administration, tests, and uninstall. Therefore no direct relationship migration may run before the dedicated migration layer exists.

### Required migration service

The service must own status, snapshot, dry run, execution, verification, journal, rollback, and reporting. It must not run automatically on ordinary requests. Required states are `not_started`, `dry_run_complete`, `running`, `completed`, `completed_with_warnings`, `failed`, `rolling_back`, `rolled_back`, and `rollback_with_warnings`.

### Required safety rules

- Capture legacy term IDs, hierarchy, metadata, Location relationships, and baseline counts before execution.
- Use a bounded journal that distinguishes created/reused Area terms, added/pre-existing relationships, conflicts, skips, and rollback actions.
- Migrate hierarchy parent-first; cycles and self-parenting are blocking conflicts.
- Reuse only proven mappings; never merge solely by display name when slugs or ownership conflict.
- Add mapped Area relationships without removing City relationships or pre-existing Area relationships.
- Rebuild affected indexes and invalidate caches.
- Roll back only migration-owned relationships, terms, metadata, and journal state when safe; preserve City data and external edits.

### Target filtering and compatibility

- Parent Area filters include descendants.
- No Area filter includes Locations without Area.
- Legacy City filters resolve to mapped Areas where possible.
- A singular compatibility value uses explicit primary Area, then deepest City-type Area, then deepest Area, then lowest term ID.

## Planned Acceptance

- Dry run is non-mutating.
- Execution is resumable and idempotent.
- Every legacy term is mapped or reported as a conflict.
- Eligible legacy relationships receive mapped Area relationships without data loss.
- Area and City REST contracts work together after implementation.
- Parent filtering, indexes, facets, selective rollback, source/stand parity, and evidence pass.
